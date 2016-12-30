<?php
/*
	services_samba_ad.php

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

$pgtitle = array(gtext("Services"), gtext("Samba AD"));

if (!isset($config['sambaad']['auxparam']) || !is_array($config['sambaad']['auxparam']))
	$config['sambaad']['auxparam'] = array();

$errormsg="";

if ($config['interfaces']['lan']['ipaddr'] == "dhcp") {
	$errormsg .= gtext("Cannot use DHCP for LAN interface.");
	$errormsg .= "<br/>";
}
if ((!empty($config['system']['dnsserver']) && $config['system']['dnsserver'][0] == "")
   && (!empty($config['system']['ipv6dnsserver']) && $config['system']['ipv6dnsserver'][0] == "")) {
	$errormsg .= gtext("DNS server is empty.");
	$errormsg .= "<br/>";
}
if (!isset($config['system']['ntp']['enable'])) {
	$errormsg .= gtext("NTP is not enabled.");
	$errormsg .= "<br/>";
}
/*
if (isset($config['samba']['enable'])) {
	$errormsg .= gtext("CIFS/SMB is enabled.");
	$errormsg .= "<br/>";
}
*/

if ($_POST) {
	unset($input_errors);
	unset($errormsg);

	$pconfig = $_POST;

	if (isset($_POST['enable'])) {
		if (empty($config['sambaad']) || empty($config['sambaad']['path']) ||
		   !file_exists($config['sambaad']['path']."/sysvol")) {
			$input_errors[] = gtext("You must initialize data before enabling.");
		}
	}
	if ($_POST['dns_forwarder'] == "") {
		$input_errors[] = gtext("DNS server is empty.");
	}

	if (empty($input_errors)) {
		$config['sambaad']['enable'] = isset($_POST['enable']) ? true : false;
		$config['samba']['enable'] = isset($_POST['enable']) ? true : false;
		$config['sambaad']['dns_forwarder'] = $_POST['dns_forwarder'];
		$config['sambaad']['user_shares'] = isset($_POST['user_shares']) ? true : false;

		unset($config['sambaad']['auxparam']);
		foreach (explode("\n", $_POST['auxparam']) as $auxparam) {
			$auxparam = trim($auxparam, "\t\n\r");
			if (!empty($auxparam))
				$config['sambaad']['auxparam'][] = $auxparam;
		}

		write_config();
		$retval = 0;
		if (!file_exists($d_sysrebootreqd_path)) {
			config_lock();
			$retval |= rc_update_service("samba");
			$retval |= rc_update_service("mdnsresponder");
			config_unlock();
		}

		$savemsg = get_std_save_message($retval);
	}
}

if (!empty($config['sambaad']['path'])) {
	$pconfig['enable'] = isset($config['sambaad']['enable']);
	$pconfig['dns_domain'] = $config['sambaad']['dns_domain'];
	$pconfig['netbios_domain'] = $config['sambaad']['netbios_domain'];
	$pconfig['dns_forwarder'] = $config['sambaad']['dns_forwarder'];
	$pconfig['path'] = $config['sambaad']['path'];
	$pconfig['fstype'] = $config['sambaad']['fstype'];
	$pconfig['user_shares'] = isset($config['sambaad']['user_shares']);
} else {
	$pconfig['enable'] = false;
	$pconfig['dns_domain'] = "";
	$pconfig['netbios_domain'] = "";
	$pconfig['dns_forwarder'] = "";
	$pconfig['path'] = "";
	$pconfig['fstype'] = "";
	$pconfig['user_shares'] = false;
}
$realm = strtoupper($pconfig['dns_domain']);
$hostname = $config['system']['hostname'];
$netbiosname = strtoupper($config['system']['hostname']);

$pconfig['auxparam'] = "";
if (is_array($config['sambaad']['auxparam']))
	$pconfig['auxparam'] = implode("\n", $config['sambaad']['auxparam']);
?>
<?php include("fbegin.inc");?>
<script type="text/javascript">//<![CDATA[
$(document).ready(function(){
	function enable_change(enable_change) {
		var val = !($('#enable').prop('checked') || enable_change);
	}
	$('#enable').click(function(){
		enable_change(false);
	});
	$('input:submit').click(function(){
		enable_change(true);
	});
	enable_change(false);
});
//]]>
</script>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td class="tabnavtbl">
			<ul id="tabnav">
				<li class="tabact"><a href="services_samba_ad.php" title="<?=gtext('Reload page');?>"><span><?=gtext("Settings");?></span></a></li>
				<li class="tabinact"><a href="services_samba_ad_init.php"><span><?=gtext("Initialize");?></span></a></li>
			</ul>
		</td>
	</tr>
	<tr>
		<td class="tabcont">
			<form action="services_samba_ad.php" method="post" name="iform" id="iform" onsubmit="spinner()">
				<?php
				if (!empty($errormsg)) print_error_box($errormsg);
				if (!empty($input_errors)) print_input_errors($input_errors);
				if (!empty($savemsg)) print_info_box($savemsg);
				?>
				<table width="100%" border="0" cellpadding="6" cellspacing="0">
					<?php
					html_titleline_checkbox("enable", gtext("Samba Active Directory Domain Controller"), !empty($pconfig['enable']) ? true : false, gtext("Enable"), "");
					html_text("hostname", gtext("Hostname"), htmlspecialchars($hostname));
					html_text("netniosname", gtext("NetBIOS name"), htmlspecialchars($netbiosname));
					html_inputbox("dns_forwarder", gtext("DNS forwarder"), $pconfig['dns_forwarder'], "", false, 40);
					html_text("dns_domain", gtext("DNS domain"), htmlspecialchars($pconfig['dns_domain']));
					html_text("netbios_domain", gtext("NetBIOS domain"), htmlspecialchars($pconfig['netbios_domain']));
					html_text("path", gtext("Path"), htmlspecialchars($pconfig['path']));
					html_text("fstype", gtext("Fileserver"), htmlspecialchars($pconfig['fstype']));
					html_checkbox("user_shares", gtext("User shares"), !empty($pconfig['user_shares']) ? true : false, gtext("Append user defined shares"), "", false);
					$helpinghand = '<a href="'
						. 'http://us1.samba.org/samba/docs/man/manpages-3/smb.conf.5.html'
						. '" target="_blank">'
						. gtext('Please check the documentation')
						. '</a>.';
					html_textarea("auxparam", gtext("Auxiliary parameters"), $pconfig['auxparam'], sprintf(gtext("These parameters are added to [Global] section of %s."), "smb4.conf") . " " . $helpinghand, false, 65, 5, false, false);
					?>
				</table>
				<div id="submit">
					<input name="Submit" type="submit" class="formbtn" value="<?=gtext("Save & Restart");?>" />
				</div>
				<div id="remarks">
					<?php
					html_remark("note", gtext('Note'), sprintf("<div id='enumeration'><ul><li>%s</li><li>%s</li><li>%s</li></ul></div>", gtext("When Samba AD is enabled, stand-alone CIFS/SMB file sharing cannot be used."), gtext("NTP must be enabled."), gtext("DHCP cannot be used for LAN interface.")));
					?>
				</div>
				<?php include("formend.inc");?>
			</form>
		</td>
	</tr>
</table>
<?php include("fend.inc");?>
