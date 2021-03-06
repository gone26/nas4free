<?php
/*
	services_afp.php

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

$pgtitle = array(gtext("Services"),gtext("AFP"));

if (!(isset($config['afp']) && is_array($config['afp']))) {
	$config['afp'] = [];
}
if (!(isset($config['afp']['auxparam']) && is_array($config['afp']['auxparam']))) {
	$config['afp']['auxparam'] = [];
}

$pconfig['enable'] = isset($config['afp']['enable']);
$pconfig['afpname'] = !empty($config['afp']['afpname']) ? $config['afp']['afpname'] : "";
$pconfig['guest'] = isset($config['afp']['guest']);
$pconfig['local'] = isset($config['afp']['local']);
$pconfig['auxparam'] = implode("\n", $config['afp']['auxparam']);

if ($_POST) {
	unset($input_errors);
	$pconfig = $_POST;

	if (!empty($_POST['enable']) && (empty($_POST['guest']) && empty($_POST['local']))) {
		$input_errors[] = gtext("You must select at least one authentication method.");
	}

	if (empty($input_errors)) {
		$config['afp']['enable'] = isset($_POST['enable']) ? true : false;
		$config['afp']['afpname'] = $_POST['afpname'];
		$config['afp']['guest'] = isset($_POST['guest']) ? true : false;
		$config['afp']['local'] = isset($_POST['local']) ? true : false;
		
		# Write additional parameters.
		unset($config['afp']['auxparam']);
		foreach (explode("\n", $_POST['auxparam']) as $auxparam) {
			$auxparam = trim($auxparam, "\t\n\r");
			if (!empty($auxparam))
				$config['afp']['auxparam'][] = $auxparam;
		}

		write_config();

		$retval = 0;
		if (!file_exists($d_sysrebootreqd_path)) {
			config_lock();
			$retval |= rc_update_service("netatalk");
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
	document.iform.afpname.disabled = endis;
	document.iform.guest.disabled = endis;
	document.iform.local.disabled = endis;
	document.iform.auxparam.disabled = endis;
}
//-->
</script>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td class="tabnavtbl">
			<ul id="tabnav">
				<li class="tabact"><a href="services_afp.php" title="<?=gtext('Reload page');?>"><span><?=gtext("Settings");?></span></a></li>
				<li class="tabinact"><a href="services_afp_share.php"><span><?=gtext("Shares");?></span></a></li>
			</ul>
		</td>
	</tr>
	<tr>
		<td class="tabcont">
			<form action="services_afp.php" method="post" name="iform" id="iform" onsubmit="spinner()">
				<?php if (!empty($input_errors)) print_input_errors($input_errors);?>
				<?php if (!empty($savemsg)) print_info_box($savemsg);?>
				<table width="100%" border="0" cellpadding="6" cellspacing="0">
					<?php html_titleline_checkbox("enable", gtext("Apple Filing Protocol"), !empty($pconfig['enable']) ? true : false, gtext("Enable"), "enable_change(false)");?>
					<tr>
						<td width="22%" valign="top" class="vncell"><?=gtext("Server Name");?></td>
						<td width="78%" class="vtable">
							<input name="afpname" type="text" class="formfld" id="afpname" size="30" value="<?=htmlspecialchars($pconfig['afpname']);?>" /><br />
							<?=gtext("Name of the server. If this field is left empty the default server is specified.");?><br />
						</td>
					</tr>
					<tr>
						<td width="22%" valign="top" class="vncell"><strong><?=gtext("Authentication");?></strong></td>
						<td width="78%" class="vtable">
							<input name="guest" id="guest" type="checkbox" value="yes" <?php if (!empty($pconfig['guest'])) echo "checked=\"checked\"";?> />
							<?=gtext("Enable guest access.");?><br />
							<input name="local" id="local" type="checkbox" value="yes" <?php if (!empty($pconfig['local'])) echo "checked=\"checked\"";?> />
							<?=gtext("Enable local user authentication.");?>
						</td>
					</tr>
					
					<tr>
					<?php
					$helpinghand = '<a href="'
						. 'http://netatalk.sourceforge.net/3.1/htmldocs/afp.conf.5.html'
						. '" target="_blank">'
						. gtext('Please check the documentation')
						. '</a>.';
					html_textarea("auxparam", gtext("Auxiliary parameters"), $pconfig['auxparam'], sprintf(gtext('Add any supplemental parameters.')) . ' ' . $helpinghand, false, 65, 5, false, false);
					?>
					</tr>
				</table>
				<div id="submit">
					<input name="Submit" type="submit" class="formbtn" value="<?=gtext("Save & Restart");?>" onclick="enable_change(true)" />
				</div>
				<div id="remarks">
					<?php
					$link = '<a href="'
						. 'system_advanced.php'
						. '">'
						. gtext('Zeroconf/Bonjour')
						. '</a>';
					html_remark("note", gtext('Note'), sprintf(gtext("You have to activate %s to advertise this service to clients."), $link));
					?>
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
