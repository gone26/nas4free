<?php
/*
	login.php

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
require("guiconfig.inc");
unset($input_errors);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	if(is_validlogin($_POST['username'])){
		Session::start();

		if ($_POST['username'] === $config['system']['username'] &&
			password_verify($_POST['password'], $config['system']['password'])) {
			Session::initAdmin();
			header('Location: index.php');
			exit;
		} else {
			$users = system_get_user_list();
			foreach ($users as $userk => $userv) {
				$password = crypt($_POST['password'], $userv['password']);
				if (($_POST['username'] === $userv['name']) && ($password === $userv['password'])) {
					// Check if it is a local user
					if (empty($config['access']['user']) || FALSE === ($cnid = array_search_ex($userv['uid'], $config['access']['user'], "id")))
						break;
					// Is user allowed to access the user portal?
					if (!isset($config['access']['user'][$cnid]['userportal']))
						break;
					Session::initUser($userv['uid'], $userv['name']);
					header('Location: index.php');
					exit;
				}
			}
		}

		write_log(sprintf('Authentication error for illegal user: %s from %s', $_POST['username'], $_SERVER['REMOTE_ADDR']));
		$input_errors = gtext('Invalid username or password.') . '</br>' . gtext('Please try again.');
	}
	else {
		write_log(sprintf('Username contains invalid character(s): %s from %s', $_POST['username'], $_SERVER['REMOTE_ADDR']));
		$input_errors = gtext('Username field : '.htmlspecialchars($_POST['username']).' contains illegal characters.');
	}
}
?>
<?php header("Content-Type: text/html; charset=" . system_get_language_codeset());?>
<?php
function gentitle($title) {
	$navlevelsep = "|"; // Navigation level separator string.
	//return join($navlevelsep, $title);
}

function genhtmltitle($title) {
	return system_get_hostname() . " - " . gentitle($title);
}

// Menu items.
// Info and Manual
$menu['info']['desc'] = gtext('Information & Manuals');
$menu['info']['visible'] = true;
$menu['info']['link'] = "http://wiki.nas4free.org/";
$menu['info']['menuitem']['visible'] = FALSE;
// Forum
$menu['forum']['desc'] = gtext("Forum");
$menu['forum']['link'] = "http://forums.nas4free.org";
$menu['forum']['visible'] = TRUE;
$menu['forum']['menuitem']['visible'] = FALSE;
// IRC
$menu['irc']['desc'] = gtext("IRC NAS4Free");
$menu['irc']['visible'] = TRUE;
$menu['irc']['link'] = "http://webchat.freenode.net/?channels=#nas4free";
$menu['irc']['menuitem']['visible'] = FALSE;
// Donate
$menu['donate']['desc'] = gtext("Donate");
$menu['donate']['visible'] = TRUE;
$menu['donate']['link'] = "https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=info%40nas4free%2eorg&lc=US&item_name=NAS4Free%20Project&no_note=0&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest";
$menu['donate']['menuitem']['visible'] = FALSE;


function display_menu($menuid) {
	global $menu;

	// Is menu visible?
	if (!isset($menu[$menuid]) || !$menu[$menuid]['visible'])
		return;

	$link = $menu[$menuid]['link'];
	if ($link == '') $link = 'index.php';
	echo "<li>\n";
	echo "	<a href=\"{$link}\" onmouseover=\"mopen('{$menuid}')\" onmouseout=\"mclosetime()\">" . $menu[$menuid]['desc'] . "</a>\n";
	echo "	<div id=\"{$menuid}\" onmouseover=\"mcancelclosetime()\" onmouseout=\"mclosetime()\">\n";

	# Display menu items.
	foreach ($menu[$menuid]['menuitem'] as $menuk => $menuv) {
		# Is menu item visible?
		if (!$menuv['visible']) {
			continue;
		}
		if ("separator" !== $menuv['type']) {
			# Display menuitem.
			$link = $menuv['link'];
			if ($link == '') $link = 'index.php';
			echo "<a href=\"{$link}\" target=\"" . (empty($menuv['target']) ? "_self" : $menuv['target']) . "\" title=\"" . $menuv['desc'] . "\">" . $menuv['desc']."</a>\n";
		} else {
			# Display separator.
			echo "<span class=\"tabseparator\">&nbsp;</span>";
		}
	}

	echo "	</div>\n";
	echo "</li>\n";
}
?>
<?php header("Content-Type: text/html; charset=" . system_get_language_codeset());?>
<?php
  // XML declarations
/*
  some browser might be broken.
  echo '<?xml version="1.0" encoding="'.system_get_language_codeset().'"?>';
  echo "\n";
*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=system_get_language_code();?>" lang="<?=system_get_language_code();?>">
<head>
	<title><?=htmlspecialchars(genhtmltitle(isset($pgtitle) ? $pgtitle : ""));?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?=system_get_language_codeset();?>" />
	<meta http-equiv="Content-Script-Type" content="text/javascript" />
	<meta http-equiv="Content-Style-Type" content="text/css" />
	<?php if (isset($pgrefresh) && $pgrefresh):?>
	<meta http-equiv="refresh" content="<?=$pgrefresh;?>" />
	<?php endif;?>
	<link href="css/gui.css" rel="stylesheet" type="text/css" />
	<link href="css/navbar.css" rel="stylesheet" type="text/css" />
	<link href="css/tabs.css" rel="stylesheet" type="text/css" />
	<link href="css/login.css" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="js/jquery.min.js"></script>
	<script type="text/javascript" src="js/gui.js"></script>
<?php
	if (isset($pglocalheader) && !empty($pglocalheader)) {
		if (is_array($pglocalheader)) {
			foreach ($pglocalheader as $pglocalheaderv) {
		 		echo $pglocalheaderv;
				echo "\n";
			}
		} else {
			echo $pglocalheader;
			echo "\n";
		}
	}
?>
</head>
<script type="text/javascript">
<!--
window.onload=function() {
	document.loginform.username.focus();
}
//-->
</script>
<body>
     <div class="loginwrapper">
		 <div class="tabcont" style="border-radius:4px;">
    <div class="loginwrap">
	<h1 class="logintitle"><span class="iconfa-lock"><img src="images/lock.png"></span><a title="www.<?=get_product_url();?>" href="http://<?=get_product_url();?>" target="_blank"><img src="images/header_logo.png" alt="logo" /></a>
	<span class="subtitle">
	<?=system_get_hostname();?>&nbsp;
	</span></h1>
        <div class="loginwrapperinner">
            <form id="loginform" action="" method="post" name="loginform">
                <p class="allocate"><input type="text" id="username" name="username" onFocus="value=''" placeholder="<?=gtext("Username");?>" value="<?=gtext("Username");?>"></p>
                <p class="allocate"><input type="password" id="password" name="password" onFocus="value=''" placeholder="<?=gtext("Password");?>" value="password"></p>
                <p class="allocate"><input class="btn formbtn" type="submit" value="<?=gtext("Login");?>" /></p>
            </form>
            </div>
             <br>
            <div id="all_links">
	<ul>
		<?=display_menu("system");?>
		<?=display_menu("network");?>
		<?=display_menu("disks");?>
		<?=display_menu("services");?>
		<!-- Begin extension section -->
		<?php if (Session::isAdmin() && is_dir("{$g['www_path']}/ext")):?>
		<li>
			<a href="index.php" onmouseover="mopen('extensions')" onmouseout="mclosetime()"><?=gtext("Extensions");?></a>
			<div id="extensions" onmouseover="mcancelclosetime()" onmouseout="mclosetime()">
				<?php
				$dh = @opendir("{$g['www_path']}/ext");
				if ($dh) {
					while (($extd = readdir($dh)) !== false) {
						if (($extd === ".") || ($extd === ".."))
							continue;
						@include("{$g['www_path']}/ext/" . $extd . "/menu.inc");
					}
					closedir($dh);
				}?>
			</div>
		</li>
		<?php endif;?>
		<left>
		<!-- End extension section -->
		<?=display_menu("forum");?> - 
		<?=display_menu("info");?> - 
		<?=display_menu("irc");?> - 
		<?=display_menu("donate");?>
		</left>
	</ul>
        </div>
	<div id="loginerror">
		<?php 
			if (!empty($input_errors)) {
				echo $input_errors;
				echo "\n";
			}
		?>
	</div>
    </div>
     </div>
        </div>
           </div>
        <div id="pagefooter">
		<span><p><a title="www.<?=get_product_url();?>" href="http://<?=get_product_url();?>" target="_blank"></a> <?=str_replace("Copyright (C)","&copy;",get_product_copyright());?></p></span>
	</div>
	</body>
</html>
