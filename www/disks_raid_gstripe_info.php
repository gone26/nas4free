<?php
/*
	disks_raid_gstripe_info.php

	Part of NAS4Free (http://www.nas4free.org).
	Copyright (c) 2012-2017 The NAS4Free Project <info@nas4free.org>.
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

$pgtitle = array(gtext("Disks"), gtext("Software RAID"), gtext("RAID0"), gtext("Information"));

function get_raidinfo() {
	exec("/sbin/gstripe list", $rawdata);
	return implode("\n", $rawdata);
}

if (is_ajax()) {
	$raidinfo = get_raidinfo();
	render_ajax($raidinfo);
}
?>
<?php include("fbegin.inc"); ?>
<script type="text/javascript">
//<![CDATA[
$(document).ready(function(){
	var gui = new GUI;
	gui.recall(0, 5000, 'disks_raid_gstripe_info.php', null, function(data) {
		$('#raidinfo').text(data.data);
	});
});
//]]>
</script>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td class="tabnavtbl">
			<ul id="tabnav">
				<li class="tabact"><a href="disks_raid_geom.php" title="<?= gtext('Reload page'); ?>"><span><?= gtext('GEOM'); ?></span></a></li>
				<li class="tabinact"><a href="disks_raid_gvinum.php"><span><?= gtext('RAID 0/1/5'); ?></span></a></li>
			</ul>
		</td>
	</tr>
	<tr>
		<td class="tabnavtbl">
			<ul id="tabnav2">
				<li class="tabinact"><a href="disks_raid_geom.php"><span><?= gtext('Management'); ?></span></a></li>
				<li class="tabinact"><a href="disks_raid_gstripe_tools.php"><span><?= gtext('Maintenance'); ?></span></a></li>
				<li class="tabact"><a href="disks_raid_gstripe_info.php" title="<?= gtext('Reload page'); ?>" ><span><?= gtext('Information'); ?></span></a></li>
			</ul>
		</td>
	</tr>
	<tr> 
		<td class="tabcont">
			<table width="100%" border="0" cellspacing="0" cellpadding="0">
				<?php html_titleline(gtext("RAID 0 Information & Status")); ?>
				<tr>
					<td class="listt">
						<pre><span id="raidinfo"></span></pre>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<?php include("fend.inc"); ?>
