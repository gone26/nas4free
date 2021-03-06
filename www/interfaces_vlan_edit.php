<?php
/*
	interfaces_vlan_edit.php

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

if (isset($_GET['uuid']))
	$uuid = $_GET['uuid'];
if (isset($_POST['uuid']))
	$uuid = $_POST['uuid'];

$pgtitle = array(gtext("Network"), gtext("Interface Management"), gtext("VLAN"), isset($uuid) ? gtext("Edit") : gtext("Add"));

if (!isset($config['vinterfaces']['vlan']) || !is_array($config['vinterfaces']['vlan']))
	$config['vinterfaces']['vlan'] = array();

$a_vlans = &$config['vinterfaces']['vlan'];
array_sort_key($a_vlans, "if");

if (isset($uuid) && (FALSE !== ($cnid = array_search_ex($uuid, $a_vlans, "uuid")))) {
	$pconfig['enable'] = isset($a_vlans[$cnid]['enable']);
	$pconfig['uuid'] = $a_vlans[$cnid]['uuid'];
	$pconfig['if'] = $a_vlans[$cnid]['if'];
	$pconfig['tag'] = $a_vlans[$cnid]['tag'];
	$pconfig['vlandev'] = $a_vlans[$cnid]['vlandev'];
	$pconfig['desc'] = $a_vlans[$cnid]['desc'];
} else {
	$pconfig['enable'] = true;
	$pconfig['uuid'] = uuid();
	$pconfig['if'] = "vlan" . get_nextvlan_id();
	$pconfig['tag'] = 1;
	$pconfig['vlandev'] = "";
	$pconfig['desc'] = "";
}

if ($_POST) {
	unset($input_errors);
	$pconfig = $_POST;

	if (isset($_POST['Cancel']) && $_POST['Cancel']) {
		header("Location: interfaces_vlan.php");
		exit;
	}

	// Input validation.
	$reqdfields = explode(" ", "vlandev tag");
	$reqdfieldsn = array(gtext("Physical interface"), gtext("VLAN tag"));
	$reqdfieldst = explode(" ", "string numeric");

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);
	do_input_validation_type($_POST, $reqdfields, $reqdfieldsn, $reqdfieldst, $input_errors);

	// Validate tag range.
	if (($_POST['tag'] < '1') || ($_POST['tag'] > '4094')) {
		$input_errors[] = gtext("The VLAN ID must be between 1 and 4094.");
	}

	// Validate if tag is unique. Only check if not in edit mode.
	if (!(isset($uuid) && (FALSE !== $cnid))) {
		class InterfaceFilter {
			function InterfaceFilter($vlandev) { $this->vlandev = $vlandev; }
			function filter($data) { return ($data['vlandev'] === $this->vlandev); }
		}

		if (false !== array_search_ex($_POST['tag'], array_filter($a_vlans, array(new InterfaceFilter($_POST['vlandev']), 'filter')), "tag")) {
			$input_errors[] = sprintf(gtext("A VLAN with the tag %s is already defined on this interface."), $_POST['tag']);
		}
	}

	if (empty($input_errors)) {
		$vlan = array();
		$vlan['enable'] = !empty($_POST['enable']) ? true : false;
		$vlan['uuid'] = $_POST['uuid'];
		$vlan['if'] = $_POST['if'];
		$vlan['tag'] = $_POST['tag'];
		$vlan['vlandev'] = $_POST['vlandev'];
		$vlan['desc'] = $_POST['desc'];

		if (isset($uuid) && (FALSE !== $cnid)) {
			$a_vlans[$cnid] = $vlan;
		} else {
			$a_vlans[] = $vlan;
		}

		write_config();
		touch($d_sysrebootreqd_path);

		header("Location: interfaces_vlan.php");
		exit;
	}
}

function get_nextvlan_id() {
	global $config;

	$id = 0;
	$a_vlan = $config['vinterfaces']['vlan'];

	if (false !== array_search_ex("vlan" . strval($id), $a_vlan, "if")) {
		do {
			$id++; // Increase ID until a unused one is found.
		} while (false !== array_search_ex("vlan" . strval($id), $a_vlan, "if"));
	}

	return $id;
}
?>
<?php include("fbegin.inc");?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td class="tabnavtbl">
		  <ul id="tabnav">
				<li class="tabinact"><a href="interfaces_assign.php"><span><?=gtext("Management");?></span></a></li>
				<li class="tabinact"><a href="interfaces_wlan.php"><span><?=gtext("WLAN");?></span></a></li>
				<li class="tabact"><a href="interfaces_vlan.php" title="<?=gtext('Reload page');?>"><span><?=gtext("VLAN");?></span></a></li>
				<li class="tabinact"><a href="interfaces_lagg.php"><span><?=gtext("LAGG");?></span></a></li>
				<li class="tabinact"><a href="interfaces_bridge.php"><span><?=gtext("Bridge");?></span></a></li>
				<li class="tabinact"><a href="interfaces_carp.php"><span><?=gtext("CARP");?></span></a></li>
			</ul>
		</td>
	</tr>
	<tr>
		<td class="tabcont">
			<form action="interfaces_vlan_edit.php" method="post" name="iform" id="iform" onsubmit="spinner()">
				<?php if ($input_errors) print_input_errors($input_errors);?>
				<table width="100%" border="0" cellpadding="6" cellspacing="0">
					<?php html_inputbox("tag", gtext("VLAN tag"), $pconfig['tag'], gtext("802.1Q VLAN tag (between 1 and 4094)."), true, 4);?>
					<?php $a_if = array(); foreach (get_interface_list() as $ifk => $ifv) { if (preg_match('/vlan/i', $ifk)) { continue; } $a_if[$ifk] = htmlspecialchars("{$ifk} ({$ifv['mac']})"); };?>
					<?php html_combobox("vlandev", gtext("Physical interface"), $pconfig['vlandev'], $a_if, "", true);?>
					<?php html_inputbox("desc", gtext("Description"), $pconfig['desc'], gtext("You may enter a description here for your reference."), false, 40);?>
				</table>
				<div id="submit">
					<input name="Submit" type="submit" class="formbtn" value="<?=(isset($uuid) && (FALSE !== $cnid)) ? gtext("Save") : gtext("Add")?>" />
					<input name="Cancel" type="submit" class="formbtn" value="<?=gtext("Cancel");?>" />
					<input name="enable" type="hidden" value="<?=$pconfig['enable'];?>" />
					<input name="if" type="hidden" value="<?=$pconfig['if'];?>" />
					<input name="uuid" type="hidden" value="<?=$pconfig['uuid'];?>" />
				</div>
				<?php include("formend.inc");?>
			</form>
		</td>
	</tr>
</table>
<?php include("fend.inc");?>
