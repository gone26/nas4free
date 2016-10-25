<?php
/*
	disks_zfs_snapshot_info.php

	Part of NAS4Free (http://www.nas4free.org).
	Copyright (c) 2012-2016 The NAS4Free Project <info@nas4free.org>.
	All rights reserved.

	Portions of freenas (http://www.freenas.org).
	Copyright (c) 2005-2011 by Olivier Cochard <olivier@freenas.org>.
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

$pgtitle = array(gtext("Disks"), gtext("ZFS"), gtext("Snapshots"), gtext("Information"));

if (!isset($config['zfs']['snapshots']['snapshot']) || !is_array($config['zfs']['snapshots']['snapshot']))
	$config['zfs']['snapshots']['snapshot'] = array();

// snapshot is always reading from the pool
//$a_snapshot = &$config['zfs']['snapshots']['snapshot'];

function zfs_snapshot_display_list() {
	mwexec2("zfs list -t snapshot 2>&1", $rawdata);
	return implode("\n", $rawdata);
}

function zfs_snapshot_display_properties() {
	mwexec2("zfs list -H -o name -t snapshot 2>&1", $rawdata);
	$snaps = implode(" ", $rawdata);
	$rawdata2 = array();
	if (!empty($snaps)) {
		mwexec2("zfs get all $snaps 2>&1", $rawdata2);
	}
	return implode("\n", $rawdata2);
}
?>
<?php include("fbegin.inc");?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td class="tabnavtbl">
			<ul id="tabnav">
				<li class="tabinact"><a href="disks_zfs_zpool.php"><span><?=gtext("Pools");?></span></a></li>
				<li class="tabinact"><a href="disks_zfs_dataset.php"><span><?=gtext("Datasets");?></span></a></li>
				<li class="tabinact"><a href="disks_zfs_volume.php"><span><?=gtext("Volumes");?></span></a></li>
				<li class="tabact"><a href="disks_zfs_snapshot.php" title="<?=gtext('Reload page');?>"><span><?=gtext("Snapshots");?></span></a></li>
				<li class="tabinact"><a href="disks_zfs_config.php"><span><?=gtext("Configuration");?></span></a></li>
			</ul>
		</td>
	</tr>
	<tr>
		<td class="tabnavtbl">
			<ul id="tabnav2">
				<li class="tabinact"><a href="disks_zfs_snapshot.php"><span><?=gtext("Snapshot");?></span></a></li>
				<li class="tabinact"><a href="disks_zfs_snapshot_clone.php"><span><?=gtext("Clone");?></span></a></li>
				<li class="tabinact"><a href="disks_zfs_snapshot_auto.php"><span><?=gtext("Auto Snapshot");?></span></a></li>
				<li class="tabact"><a href="disks_zfs_snapshot_info.php" title="<?=gtext('Reload page');?>"><span><?=gtext("Information");?></span></a></li>
			</ul>
		</td>
	</tr>
	<tr>
		<td class="tabcont">
			<table width="100%" border="0">
				<?php html_titleline(gtext("ZFS Snapshot Information & Status"));?>
				<tr>
					<td class="listt">
						<pre><span id="zfs_snapshot_list"><?=zfs_snapshot_display_list();?></span></pre>
					</td>
				</tr>
				<?php html_titleline(gtext("ZFS Snapshot Properties"));?>
				<tr>
					<td class="listt">
						<pre><span id="zfs_snapshot_properties"><?=zfs_snapshot_display_properties();?></span></pre>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<?php include("fend.inc");?>
