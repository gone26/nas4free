<?php
/*
	diag_ping.php

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

$pgtitle = array(gtext("Diagnostics"), gtext("Ping"));

if ($_POST) {
	unset($input_errors);
	unset($do_ping);

	// Input validation.
	$reqdfields = explode(" ", "host count");
	$reqdfieldsn = array(gtext("Host"), gtext("Count"));

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

	if (empty($input_errors)) {
		$do_ping = true;
		$host = $_POST['host'];
		$interface = $_POST['interface'];
		$count = $_POST['count'];
	}
}

if (!isset($do_ping)) {
	$do_ping = false;
	$host = "";
	$count = 3;
}

function get_interface_addr($ifdescr) {
	global $config;

	// Find out interface name.
	$if = $config['interfaces'][$ifdescr]['if'];

	return get_ipaddr($if);
}
?>
<?php include("fbegin.inc");?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td class="tabnavtbl">
			<ul id="tabnav">
				<li class="tabact"><a href="diag_ping.php" title="<?=gtext('Reload page');?>"><span><?=gtext("Ping");?></span></a></li>
				<li class="tabinact"><a href="diag_traceroute.php"><span><?=gtext("Traceroute");?></span></a></li>
			</ul>
		</td>
	</tr>
	<tr>
		<td class="tabcont">
			<form action="diag_ping.php" method="post" name="iform" id="iform" onsubmit="spinner()">
				<?php if (!empty($input_errors)) print_input_errors($input_errors);?>
				<table width="100%" border="0" cellpadding="6" cellspacing="0">
					<?php html_titleline(gtext("Ping Test"));?>
					<?php html_inputbox("host", gtext("Host"), $host, gtext("Destination host name or IP number."), true, 20);?>
					<?php html_interfacecombobox("interface", gtext("Interface"), !empty($interface) ? $interface : "", gtext("Use the following IP address as the source address in outgoing packets."), true);?>
					<?php $a_count = array(); for ($i = 1; $i <= 10; $i++) { $a_count[$i] = $i; }?>
					<?php html_combobox("count", gtext("Count"), $count, $a_count, gtext("Stop after sending (and receiving) N packets."), true);?>
				</table>
				<div id="submit">
					<input name="Submit" type="submit" class="formbtn" value="<?=gtext("Ping");?>" />
				</div>
				<?php if ($do_ping) {
				echo(sprintf("<div id='cmdoutput'>%s</div>", gtext("Command output:")));
				echo('<pre class="cmdoutput">');
				//ob_end_flush();
				$ifaddr = get_interface_addr($interface);
				if ($ifaddr) {
					exec("/sbin/ping -S {$ifaddr} -c {$count} " . escapeshellarg($host), $rawdata);
				} else {
					exec("/sbin/ping -c {$count} " . escapeshellarg($host), $rawdata);
				}
				echo htmlspecialchars(implode("\n", $rawdata));
				unset($rawdata);
				echo('</pre>');
				}
				?>
				<?php include("formend.inc");?>
			</form>
		</td>
	</tr>
</table>
<?php include("fend.inc");?>
