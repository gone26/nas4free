<?php
/*
	status_disks.php

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

$pgtitle = array(gtext("Status"), gtext("Disks"));

// Get all physical disks.
$a_phy_disk = array_merge((array)get_conf_physical_disks_list());
$a_phy_hast = array_merge((array)get_hast_disks_list());

$pconfig['temp_info'] = $config['smartd']['temp']['info'];
$pconfig['temp_crit'] = $config['smartd']['temp']['crit'];

if (!isset($config['disks']['disk']) || !is_array($config['disks']['disk']))
	$config['disks']['disk'] = array();

array_sort_key($config['disks']['disk'], "name");
$a_disk_conf = &$config['disks']['disk'];

$raidstatus = get_sraid_disks_list();
?>
<?php include("fbegin.inc");?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td class="tabcont">
			<table width="100%" border="0" cellpadding="0" cellspacing="0">
			<?php html_titleline(gtext('Status & Information'), 9);?>
				<tr>
					<td width="5%" class="listhdrlr"><?=gtext("Device");?></td>
					<td width="7%" class="listhdrr"><?=gtext("Size");?></td>
					<td width="15%" class="listhdrr"><?=gtext("Device Model"); ?></td>
					<td width="17%" class="listhdrr"><?=gtext("Description");?></td>
					<td width="13%" class="listhdrr"><?=gtext("Serial Number"); ?></td>
					<td width="9%" class="listhdrr"><?=gtext("Filesystem"); ?></td>
					<td width="18%" class="listhdrr"><?=gtext("I/O Statistics");?></td>
					<td width="8%" class="listhdrr"><?=gtext("Temperature");?></td>
					<td width="8%" class="listhdrr"><?=gtext("Status");?></td>
				</tr>
				<?php foreach ($a_disk_conf as $disk):?>
				<?php (($iostat = system_get_device_iostat($disk['name'])) === FALSE) ? $iostat = gtext("n/a") : $iostat = sprintf("%s KiB/t, %s tps, %s MiB/s", $iostat['kpt'], $iostat['tps'], $iostat['mps']);?>
				<?php (($temp = system_get_device_temp($disk['devicespecialfile'])) === FALSE) ? $temp = gtext("n/a") : $temp = sprintf("%s &deg;C", htmlspecialchars($temp));?>
				<?php
					if ($disk['type'] == 'HAST') {
						$role = $a_phy_hast[$disk['name']]['role'];
						$size = $a_phy_hast[$disk['name']]['size'];
						$status = sprintf("%s (%s)", (0 == disks_exists($disk['devicespecialfile'])) ? gtext("ONLINE") : gtext("MISSING"), $role);
						$disk['size'] = $size;
					} else {
						$status = (0 == disks_exists($disk['devicespecialfile'])) ? gtext("ONLINE") : gtext("MISSING");
					}
				?>
				<tr>
					<td class="listlr"><?=htmlspecialchars($disk['name']);?></td>
					<td class="listr"><?=htmlspecialchars($disk['size']);?></td>
					<td class="listr"><?=htmlspecialchars($disk['model']);?>&nbsp;</td>
					<td class="listr"><?=(empty($disk['desc']) ) === FALSE ? htmlspecialchars($disk['desc']) : gtext("n/a");?>&nbsp;</td>
					<td class="listr"><?=(empty($disk['serial']) ) === FALSE ? htmlspecialchars($disk['serial']) : gtext("n/a");?>&nbsp;</td>
					<td class="listr"><?=($disk['fstype']) ? htmlspecialchars(get_fstype_shortdesc($disk['fstype'])) : gtext("Unknown or unformatted")?>&nbsp;</td>
					<td class="listr"><?=htmlspecialchars($iostat);?>&nbsp;</td>
					<td class="listr"><?php
					if ($temp <> gtext("n/a")){
						if (!empty($pconfig['temp_crit']) && $temp >= $pconfig['temp_crit']){
							print "<div class=\"errortext\">".$temp."</div>";
							  }
						else if (!empty($pconfig['temp_info']) && $temp >= $pconfig['temp_info']){
							print "<div class=\"warningtext\">".$temp."</div>";
						}
						else{
							print $temp;
						    }  
					} else { print gtext("n/a"); }
					?>&nbsp;</td>
					<td class="listbg"><?=$status;?>&nbsp;</td>
				</tr>
				<?php endforeach; ?>
				<?php if (isset($raidstatus)):?>
				<?php foreach ($raidstatus as $diskk => $diskv):?>
				<?php (($iostat = system_get_device_iostat($diskk)) === FALSE) ? $iostat = gtext("n/a") : $iostat = sprintf("%s KiB/t, %s tps, %s MiB/s", $iostat['kpt'], $iostat['tps'], $iostat['mps']);?>
				<?php (($temp = system_get_device_temp($diskk)) === FALSE) ? $temp = gtext("n/a") : $temp = sprintf("%s &deg;C", htmlspecialchars($temp));?>
				<tr>
					<td class="listlr"><?=htmlspecialchars($diskk);?></td>
					<td class="listr"><?=htmlspecialchars($diskv['size']);?></td>
					<td class="listr"><?=gtext("n/a");?>&nbsp;</td>
					<td class="listr"><?=gtext("Software RAID");?>&nbsp;</td>
					<td class="listr"><?=gtext("n/a");?>&nbsp;</td>
					<td class="listr"><?=($diskv['fstype']) ? htmlspecialchars(get_fstype_shortdesc($diskv['fstype'])) : gtext("UFS")?>&nbsp;</td>
					<td class="listr"><?=htmlspecialchars($iostat);?>&nbsp;</td>
					<td class="listr"><?php
					if ($temp <> gtext("n/a")){
						if (!empty($pconfig['temp_crit']) && $temp >= $pconfig['temp_crit']){
							print "<div class=\"errortext\">".$temp."</div>";
							}		
						else if (!empty($pconfig['temp_info']) && $temp >= $pconfig['temp_info']){
							print "<div class=\"warningtext\">".$temp."</div>";
							}
						else{
						      print $temp;
						      } 
					} else { print gtext("n/a"); }
					?>&nbsp;</td>
					<td class="listbg"><?=htmlspecialchars($diskv['state']);?>&nbsp;</td>
				</tr>
				<?php endforeach;?>
				<?php endif;?>
			</table>
			</td>
		</tr>
	</table>
<?php include("fend.inc");?>
