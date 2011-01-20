<?php
/*****************************************************************************
*
*    License:
*
*   Copyright (c) 2003-2006 ossim.net
*   Copyright (c) 2007-2009 AlienVault
*   All rights reserved.
*
*   This package is free software; you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation; version 2 dated June, 1991.
*   You may not use, modify or distribute this program under any other version
*   of the GNU General Public License.
*
*   This package is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this package; if not, write to the Free Software
*   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
*   MA  02110-1301  USA
*
*
* On Debian GNU/Linux systems, the complete text of the GNU General
* Public License can be found in `/usr/share/common-licenses/GPL-2'.
*
* Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
****************************************************************************/
/**
 * Class and Function List:
 * Function list:
 * Classes list:
 */
require_once 'classes/Security.inc';
require_once 'classes/Session.inc';
require_once 'classes/WebIndicator.inc';
Session::useractive("session/login.php");
require_once ('ossim_conf.inc');
$conf = $GLOBALS["CONF"];
$ntop_link = $conf->get_conf("ntop_link", FALSE);
ossim_set_lang();
$uc_languages = array(
    "de_DE.UTF-8",
    "de_DE.UTF8",
    "de_DE",
    "en_GB",
    "es_ES",
    "fr_FR",
    "pt_BR"
);
$sensor_ntop = parse_url($ntop_link);
$ocs_link = $conf->get_conf("ocs_link", FALSE);
$glpi_link = $conf->get_conf("glpi_link", FALSE);
$ovcp_link = $conf->get_conf("ovcp_link", FALSE);
$nagios_link = $conf->get_conf("nagios_link", FALSE);
$sensor_nagios = parse_url($nagios_link);
if (!isset($sensor_nagios['host'])) {
    $sensor_nagios['host'] = $_SERVER['SERVER_NAME'];
}
$menu = array();
$hmenu = array();
$placeholder = gettext("Dashboard");
$placeholder = gettext("Events");
$placeholder = gettext("Monitors");
$placeholder = gettext("Incidents");
$placeholder = gettext("Reports");
$placeholder = gettext("Policy");
$placeholder = gettext("Correlation");
$placeholder = gettext("Configuration");
$placeholder = gettext("Tools");
$placeholder = gettext("Logout");
// Passthrough Vars
$status = "Open";
if (GET('status') != null) $status = GET('status');
/* Menu options */
include ("menu_options.php");
/* Generate reporting server url */
switch ($conf->get_conf("bi_type", FALSE)) {
    case "jasperserver":
    default:
        if ($conf->get_conf("bi_host", FALSE) == "localhost") {
            $bi_host = $_SERVER["SERVER_NAME"];
        } else {
            $bi_host = $conf->get_conf("bi_host", FALSE);
        }
        if (!strstr($bi_host, "http")) {
            $reporting_link = "http://";
        }
        $bi_link = $conf->get_conf("bi_link", FALSE);
        $bi_link = str_replace("USER", $conf->get_conf("bi_user", FALSE) , $bi_link);
        $bi_link = str_replace("PASSWORD", $conf->get_conf("bi_pass", FALSE) , $bi_link);
        $reporting_link.= $bi_host;
        $reporting_link.= ":";
        $reporting_link.= $conf->get_conf("bi_port", FALSE);
        $reporting_link.= $bi_link;
}
$option = intval(GET('option'));
$soption = intval(GET('soption'));
$url = addslashes(GET('url'));
if ($url != "") {
	$url_check = preg_replace("/\.php.*/",".php",$url);
	if (!file_exists($url_check)) {
		echo _("Can't access to $url_check for security reasons");
		exit;
	}
}
if (empty($option)) $option = 0;
if (!isset($soption)) {
    if (isset($_SESSION["_TopMenu_" . $option])) $soption = $_SESSION["_TopMenu_" . $option];
    else $soption = 0;
} else {
    $_SESSION["_TopMenu_" . $option] = $soption;
}
$keys = array_keys($menu);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head><title>Ossim Menu</title>
<link rel="stylesheet" type="text/css" href="style/top.css">
<style>
html, body {height:100%;overflow-y:auto;overflow-x:hidden}
</style>
<link type="text/css" href="style/default/jx.stylesheet.css" rel="stylesheet" />
<script type="text/javascript" src="js/jquery-1.3.2.min.js"></script>
<script type="text/javascript" src="js/jquery.jixedbar.js"></script>
<script src="js/accordian.js" type="text/javascript" ></script>
<script>
var newwindow;
function new_wind(url,name)
{
	newwindow=window.open(url,name,'height=768,width=1024,scrollbars=yes');
	if (window.focus) {newwindow.focus()}
}
function fullwin(){
	window.open("index.php","main_window","fullscreen,scrollbars")
}
$(document).ready(function() {
	new Accordian('basic-accordian',5,'header_highlight');
	$("#side-bar").jixedbar();
}); 
</script>

<body leftmargin='0' topmargin='0' marginwidth='0' marginheight='0' bgcolor="#D2D2D2">

<table width="100%" height="100%" border='0' cellpadding='0' cellspacing='0' style="border:1px solid #AAAAAA">
<tr><td valign="top">

<table width="100%" border='0' cellpadding='0' cellspacing='0'>
<!--<tr><td style="background:#678297 url(pixmaps/top/blueshadow.gif) top left" align="center" height="40" class="white"> :: Operation :: </td></tr> -->
<tr><td>

	<div id="basic-accordian" ><!--Parent of the Accordion-->
	
	<?php
$i = 0;
$moption = $hoption = "";
foreach($menu as $name => $opc) if ($name != "Logout") {
    if (!isset($language)) $language = "";
    $open = ($option == $i) ? "header_highlight" : "";
    $txtopc = (in_array($language, $uc_languages)) ? htmlentities(strtoupper(html_entity_decode(gettext($name)))) : gettext($name);
?>
	
	<!--Start of each accordion item-->
	  <div id="test<?php
    if ($i > 0) echo $i ?>-header" class="accordion_headings <?php
    echo $open ?>">
		&nbsp;<img src="pixmaps/menu/<?php
    echo strtolower($name) ?>.gif" border=0 align="absmiddle"> &nbsp; <?php
    echo $txtopc ?>
	  </div>
	  
	  <!--Prefix of heading (the DIV above this) and content (the DIV below this) to be same... eg. foo-header & foo-content-->
	  <? $default_url = (count($menu[$keys[$i]])==1) ? $menu[$keys[$i]][0]["url"] : ""; ?>
	  <div id="test<?php if ($i > 0) echo $i ?>-content"<?=($default_url!="") ? " url=\"$default_url\"" : ""?>><!--DIV which show/hide on click of header-->

		<!--This DIV is for inline styling like padding...-->
		<div class="accordion_child">
			<table cellpadding=0 cellspacing=0 border=0 width="100%">
			<?php
    if (is_array($menu[$keys[$i]])) {
        foreach($menu[$keys[$i]] as $j => $op) {
            if ($option == $i && $soption == $j && $url == "") {
                $url = $op["url"];
                $hoption = $keys[$i];
                $moption = $op["id"];
            }
            $txtsopc = (in_array($language, $uc_languages)) ? htmlentities(strtoupper(html_entity_decode($op["name"]))) : $op["name"];
            $lnk = (count($menu[$keys[$i]])==1 || ($option == $i && $soption == $j)) ? "on" : "";
            if ($op["id"] != "Help") {
?>
				<tr><td>
					<div class="opc<?php echo $lnk ?>"
                    onclick="document.location.href='<?php echo $SCRIPT_NAME ?>?option=<?php echo $i ?>&soption=<?php echo $j ?>'">
						<table cellpadding=0 cellspacing=0 border=0 width="100%">
						<tr>
							<td class="cell right"><img src="pixmaps/menu/icon0.gif"></td>
							<td class="cell" nowrap>
                                <span class="lnk<?php echo $lnk ?>"><?php echo $txtsopc ?></span>
                            </td>
						</tr>
						</table>
					</div>
				</td></tr>
			<?php
            } else {
?>
				<tr><td>
					<table cellpadding=0 cellspacing=0 border=0 width="100%">
					<tr>
						<td class="opc right"><a href="<?php echo $op["url"] ?>"><img src="pixmaps/menu/help.gif" border="0"></a></td>
						<td class="opc" nowrap><a href="<?php echo $op["url"] ?>" class="help">Help</a></td>
					</tr>
					</table>
				</td></tr>
			<?php
            }
        }
        echo "<tr><td height='2' bgcolor='#575757'></td></tr>";
        echo "<tr><td height='1' bgcolor='#FFFFFF'></td></tr>";
    }
?>
			</table>
		</div>
		
	  </div>
	  
	<?php
    $i++;
}
?>

	</div>

</td></tr>
<!--
<tr><td height="26" class="outmenu">
		<img src="pixmaps/user-green.png" width="12" border=0 align="absmiddle"> &nbsp; <a href="<?=($opensource) ? "session/modifyuserform.php?user=".Session::get_session_user()."&frommenu=1&hmenu=Userprofile&smenu=Userprofile" : "acl/users_edit.php?login=".Session::get_session_user()."&frommenu=1&hmenu=Userprofile&smenu=Userprofile";?>" target="main"><font color="black"><?php echo _("My Profile")?></font></a>
</td></tr>

<tr><td height="26" class="outmenu">
		<img src="pixmaps/users.png" width="12" border='0' align="absmiddle"> &nbsp; <a href="userlog/opened_sessions.php?hmenu=Sessions&smenu=Sessions" target="main"><span style='color:black'><?php echo _("Opened Sessions")?></span></a>
</td></tr>

<tr><td height="26" class="outmenu">
		<img src="pixmaps/menu/logout.png" border=0 align="absmiddle"> &nbsp; <a href="session/login.php?action=logout"><font color="black"><?php echo _("Logout")?></font></a> [<font color="gray"><?php
echo $_SESSION["_user"] ?></font>]
 </td></tr>
 <tr><td height='1' bgcolor='#EEEEEE'></td></tr>
 <tr><td height="26" class="outmenu">
		<img src="pixmaps/menu/maximizep.png" border=0 align="absmiddle"> &nbsp; <a href="#" onClick="fullwin()"><font color="black"><?php echo _("Maximize")?></font></a>
</td></tr>
<?php
if(Session::am_i_admin()){
?>
    <tr><td height="26" class="outmenu">
        <img src="pixmaps/menu/gear.png" border=0 align="absmiddle"> &nbsp; <a href="sysinfo/index.php" target="main"><font color="black"><?php echo _("System Status")?></font></a>
    </td></tr>
<?php 
}
?>
-->
</table>

<div id="side-bar">

		<ul>        
            <li title="<?php echo _("User options")?>"><a href="#"><img src="pixmaps/myprofile.png" alt="<?php echo Session::get_session_user()?>" /></a>
                <ul>
		            <li title="<?php echo _("Logout")?>"><a href="session/login.php?action=logout"><img src="pixmaps/logout.png">&nbsp;&nbsp;&nbsp;<?php echo _("Logout")?></a></li>
		            <li title="<?php echo _("My Profile")?>"><a href="<?=($opensource) ? "session/modifyuserform.php?user=".Session::get_session_user()."&frommenu=1&hmenu=Userprofile&smenu=Userprofile" : "acl/users_edit.php?login=".Session::get_session_user()."&frommenu=1&hmenu=Userprofile&smenu=Userprofile";?>" target="main"><img src="pixmaps/myprofile.png" alt="" />&nbsp;&nbsp;&nbsp;<?php echo _("My Profile")?></a></li>
                </ul>
            </li>
        </ul>
        <span class="jx-separator-left"></span> 
		<?php if(Session::am_i_admin()) { ?>
        <ul>
        	<li title="<?php echo _("Status")?>"><a href="sysinfo/index.php" target="main"><img src="pixmaps/status.png"></a></li>
        </ul>
        <?php } else { ?>
        <ul>
        	<li title="<?php echo _("Status")?>"><img src="pixmaps/status_gray.png"></li>
        </ul>
        <?php } ?>
        <span class="jx-separator-left"></span>        
        <ul>
        	<li title="<?php echo _("Maximize")?>"><a href="#" onClick="fullwin()"><img src="pixmaps/maximize.png"></a></li>
        </ul>
        <!-- <span class="jx-separator-right"></span> -->
</div>

<?
require_once ('classes/DateDiff.inc');
require_once ('ossim_db.inc');
require_once ('ossim_conf.inc');

function is_expired($time)
{
	$conf     = $GLOBALS["CONF"];
	$activity = strtotime($time);
	
	if (!$conf) {
		require_once 'ossim_db.inc';
		require_once 'ossim_conf.inc';
		$conf = new ossim_conf();
	}
		
	$expired_timeout = intval($conf->get_conf("session_timeout", FALSE)) * 60;
	
	if ($expired_timeout == 0)
		return false;
	
	$expired_date = $activity + $expired_timeout;
    $current_date = strtotime(date("Y-m-d H:i:s"));
	
	if ( $expired_date < $current_date )
		return true;
	else
		return false;
}
$db   = new ossim_db();
$conn = $db->connect();
$all_sessions = Session_activity::get_list($conn," ORDER BY activity desc");
$users = 0;
foreach ($all_sessions as $sess) {
	if ($sess->get_id() == session_id()) {
		$ago = str_replace(" ago","",TimeAgo(strtotime($sess->get_logon_date())));
	}
	if (!is_expired($sess->get_activity())) $users++;
}
$db->close($conn);
?>
<div class="jx-bottom-bar jx-bar-rounded-bl jx-bar-rounded-br">
<table><tr><td class="jx-gray">
<?= "<a href='userlog/opened_sessions.php?hmenu=Sysinfo&smenu=Sessions' target='main' class='jx-gray-b'>$ago</a> "._("logon") ?>
<br>
<?= "<a href='userlog/opened_sessions.php?hmenu=Sysinfo&smenu=Sessions' target='main' class='jx-gray-b'>$users</a> "._("users log into the system") ?>
</td></tr></table>
</div>

</td><!-- <td style="background:url('pixmaps/menu/dg_gray.gif') repeat-y top left;width:6"><img src="pixmaps/menu/dg_gray.gif"></td> -->
</tr>
</table>
<?php
if ($url != "") {
	if (preg_match("/hmenu\=/",$url)) { ?>
<script> window.open('<?php echo $url ?>', 'main') </script>
<?php
	} else { ?>
<script> window.open('<?php echo $url . (preg_match("/\?/", $url) ? "&" : "?") . "hmenu=" . urlencode(($hoption=="Policy" && $moption!="Actions") ? $hoption : $moption) . "&smenu=" . urlencode($moption) ?>', 'main') </script>
<? }
}
//$OssimWebIndicator->update_display();
?>
</body>
</html>



