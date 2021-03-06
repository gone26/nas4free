<?php
/*
	services_nfs.php

	Part of NAS4Free (http://www.nas4free.org).
	Copyright (c) 2012-2017 The NAS4Free Project <info@nas4free.org>.
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice, this
	   list of conditions and the following disclaimer.

	2. Redistributions in binary form must reproduce the above copyright notice,
	   this list of conditions and the following disclaimer in the documentation
	   and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
	ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
	WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
	DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
	ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
	(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
	LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
	ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
	(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
	SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

	The views and conclusions contained in the software and documentation are those
	of the authors and should not be interpreted as representing official policies,
	either expressed or implied, of the NAS4Free Project.
*/
require("auth.inc");
require("guiconfig.inc");

$pgtitle = array(gtext("Services"),gtext("NFS"));

if (!isset($config['nfsd']['share']) || !is_array($config['nfsd']['share']))
	$config['nfsd']['share'] = array();

array_sort_key($config['nfsd']['share'], "path");
$a_share = &$config['nfsd']['share'];

$pconfig['enable'] = isset($config['nfsd']['enable']);
$pconfig['v4enable'] = isset($config['nfsd']['v4enable']);
$pconfig['numproc'] = $config['nfsd']['numproc'];

if ($_POST) {
	unset($input_errors);

	$pconfig = $_POST;

	if (isset($_POST['enable']) && $_POST['enable']) {
		$reqdfields = explode(" ", "numproc");
		$reqdfieldsn = array(gtext("Number of servers"));
		$reqdfieldst = explode(" ", "numeric");

		do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);
		do_input_validation_type($_POST, $reqdfields, $reqdfieldsn, $reqdfieldst, $input_errors);
	}

	if(empty($input_errors)) {
		$config['nfsd']['enable'] = isset($_POST['enable']) ? true : false;
		$config['nfsd']['v4enable'] = isset($_POST['v4enable']) ? true : false;
		$config['nfsd']['numproc'] = $_POST['numproc'];
		$v4state = $config['nfsd']['v4enable'] == true ? "enable" : "disable";

		write_config();

		$retval = 0;
		if (!file_exists($d_sysrebootreqd_path)) {
			config_lock();
			rc_exec_script("/etc/rc.d/nfsuserd forcestop");
			$retval |= mwexec("/usr/local/sbin/rconf service {$v4state} nfsv4_server");
			$retval |= mwexec("/usr/local/sbin/rconf service {$v4state} nfsuserd");
			if (isset($config['nfsd']['enable']) && isset($config['nfsd']['v4enable'])) {
				$retval |= rc_exec_script("/etc/rc.d/nfsuserd start");
			}
			$retval |= rc_update_service("rpcbind"); // !!! Do
			$retval |= rc_update_service("mountd");  // !!! not
			$retval |= rc_update_service("nfsd");    // !!! change
			$retval |= rc_update_service("statd");   // !!! this
			$retval |= rc_update_service("lockd");   // !!! order
			$retval |= rc_update_service("mdnsresponder");
			config_unlock();
		}

		$savemsg = get_std_save_message($retval);
	}
}
?>
<?php include("fbegin.inc");?>
<script type="text/javascript">
<!--
function enable_change(enable_change) {
	var endis = !(document.iform.enable.checked || enable_change);
	document.iform.numproc.disabled = endis;
	document.iform.v4enable.disabled = endis;
}
//-->
</script>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td class="tabnavtbl">
			<ul id="tabnav">
				<li class="tabact"><a href="services_nfs.php" title="<?=gtext('Reload page');?>"><span><?=gtext("Settings");?></span></a></li>
				<li class="tabinact"><a href="services_nfs_share.php"><span><?=gtext("Shares");?></span></a></li>
			</ul>
		</td>
	</tr>
	<tr>
		<td class="tabcont">
			<form action="services_nfs.php" method="post" name="iform" id="iform" onsubmit="spinner()">
				<?php if (!empty($input_errors)) print_input_errors($input_errors);?>
				<?php if (!empty($savemsg)) print_info_box($savemsg);?>
				<table width="100%" border="0" cellpadding="6" cellspacing="0">
					<?php html_titleline_checkbox("enable", gtext("Network File System"), !empty($pconfig['enable']) ? true : false, gtext("Enable"), "enable_change(false)");?>
					<?php html_inputbox("numproc", gtext("Number of servers"), $pconfig['numproc'], gtext("Specifies how many servers to create.") . " " . gtext("There should be enough to handle the maximum level of concurrency from its clients, typically four to six."), false, 2);?>
					<?php html_checkbox("v4enable", gtext("NFSv4"), !empty($pconfig['v4enable']) ? true : false, gtext("Enable NFSv4 server."), "", false);?>
				</table>
				<div id="submit">
					<input name="Submit" type="submit" class="formbtn" value="<?=gtext("Save & Restart");?>" onclick="enable_change(true)" />
				</div>
				<?php include("formend.inc");?>
			</form>
		</td>
	</tr>
</table>
<script type="text/javascript">
<!--
enable_change(false);
//-->
</script>
<?php include("fend.inc");?>
