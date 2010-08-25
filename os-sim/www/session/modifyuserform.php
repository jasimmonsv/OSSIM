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
* - check_perms()
* Classes list:
*/
require_once ('classes/Session.inc');
//Session::logcheck("MenuConfiguration", "ConfigurationUsers");
require_once ('ossim_acl.inc');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/greybox.js"></script>
  <script type="text/javascript" src="../js/jquery.pstrength.js"></script>
  <script type="text/javascript">
  function kdbperms (users) {
	document.fmodify.knowledgedb_perms.value = users;
  }
  </script>
	
	<script type="text/javascript" src="../js/jquery.checkboxes.js"></script>
	<script type="text/javascript">
	var checks = new Array;
	checks['nets'] = 0;
	checks['sensor'] = 0;
	checks['perms'] = 0;
	function checkall(col) {
		if (checks[col]) {
			$("#fmodify").unCheckCheckboxes("."+col, true)
			checks[col] = 0;
		} else {
			$("#fmodify").checkCheckboxes("."+col, true)
			checks[col] = 1;
		}
	}
function checkpasslength() {
	if ($('#pass1').val() != "") {
	if ($('#pass1').val().length < 5) {
		alert("<?=_("Minimum password size is 5 characters")?>");
		return 0;
	} else return 1;
	} else return 1;
}
function checkpass() {
	if (document.fmodify.pass1.value != document.fmodify.pass2.value) {
		alert("<?=_("Mismatches in passwords")?>");
		return 0;
	} else return 1;
}
function formsubmit() {
	if (checkpasslength() && checkpass()) document.fmodify.submit();
}
	</script>
</head>
<body onload="$('#pass1').pstrength()">
<?php
require_once ("classes/Security.inc");
$user = GET('user');
$networks = GET('networks');
$sensors = GET('sensors');
$perms = GET('perms');
$copy_panels = intval(GET('copy_panels'));
$frommenu = GET('frommenu');
$success = GET('success');

if ($frommenu) echo "<br><br>";
else include ("../hmenu.php");

ossim_valid($user, OSS_USER, OSS_NULLABLE, 'illegal:' . _("User name"));
ossim_valid($user, OSS_USER, 'illegal:' . _("User name"));
ossim_valid($networks, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("networks"));
ossim_valid($sensors, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("sensors"));
ossim_valid($perms, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("perms"));
ossim_valid($frommenu, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("frommenu"));
ossim_valid($success, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("success"));
if (ossim_error()) {
    die(ossim_error());
}

if (!Session::am_i_admin() && Session::get_session_user() != $user)
	die(_("You don't have permission to see this page"));

function check_perms($user, $mainmenu, $submenu) {
    $gacl = $GLOBALS['ACL'];
    return $gacl->acl_check($mainmenu, $submenu, ACL_DEFAULT_USER_SECTION, $user);
}
require_once ('classes/Session.inc');
require_once ('classes/Net.inc');
require_once ('classes/Sensor.inc');
require_once ('ossim_db.inc');
$db = new ossim_db();
$conn = $db->connect();
if ($user_list = Session::get_list($conn, "WHERE login = '$user'")) {
    $user = $user_list[0];
}
$net_list = Net::get_all($conn);
$sensor_list = Sensor::get_all($conn, "ORDER BY name ASC");
?>
<? if ($frommenu) { ?>
<table align="center" style="border: 1px solid rgb(170, 170, 170);background:url(../pixmaps/fondo_hdr2.png) repeat-x">
<tr><td class="center nobborder" style="font-size:14px;color:#333333;font-weight:bold"><?=_("User Profile")?></td></tr>
<? if ($success) { ?><tr><td class="center nobborder" style="color:green"><?=_("Successfully Saved")?></td></tr><? } ?>
<tr><td class="center nobborder">
<? } ?>
<form name="fmodify" id="fmodify" method="post" action="modifyuser.php">
<table align="center">
  <input type="hidden" name="frommenu" value="<?=$frommenu?>">
  <input type="hidden" name="insert" value="insert" />
  <input type="hidden" name="user" value="<?php
echo $user->get_login() ?>" />
  <tr>
    <th> <?php
echo gettext("User login"); ?> </th>
    <td class="left"><b><?php
echo $user->get_login(); ?></b></td>
  </tr>
  <tr>
    <th> <?php
echo gettext("User name"); ?> </th>
    <td class="left"><input type="text" name="name"
        value="<?php
echo $user->get_name(); ?>" /></td>
  </tr>
  <tr>
    <th> <?php
echo gettext("User email"); ?> <img src="../pixmaps/email_icon.gif"></th>
    <td class="left"><input type="text" name="email"
        value="<?php
echo $user->get_email(); ?>" /></td>
  </tr>
  <tr>
    <th> <?php
echo gettext("User language"); ?></th>
    <td class="left">
<?php
$language = array(
    "type" => array(
        "pt_BR" => gettext("Brazilian Portuguese") ,
        "en_GB" => gettext("English") ,
        "fr_FR" => gettext("French") ,
        "de_DE" => gettext("German") ,
        "ja_JP" => gettext("Japanese") ,
        "ru_RU.UTF-8" => gettext("Russian"),
        "zh_CN" => gettext("Simplified Chinese") ,
        "es_ES" => gettext("Spanish") ,
        "zh_TW" => gettext("Traditional Chinese")
    ) ,
    "help" => gettext("")
);
$lform = "<select name=\"language\">";
foreach($language['type'] as $option_value => $option_text) {
    $lform.= "<option ";
    if ($user->get_language() == $option_value) $lform.= " SELECTED ";
    $lform.= "value=\"$option_value\">$option_text</option>";
}
$lform.= "</select>";
echo $lform;
?>
</td>
  </tr>
  <tr>
    <th> <?php
echo gettext("Company"); ?> </th>
    <td class="left"><input type="text" name="company"
        value="<?php
echo $user->get_company(); ?>" /></td>
  </tr>
  <tr>
    <th> <?php
echo gettext("Department"); ?> </th>
    <td class="left"><input type="text" name="department"
        value="<?php
echo $user->get_department(); ?>" /></td>
  </tr>
<tr>
  <th><?php echo _("Ask to change password at next login") ?></th>
    <td align="center">
   <input type="radio" name="first_login" value="1"> <?php echo _("Yes"); ?>
   <input type="radio" name="first_login" value="0" checked> <?php echo _("No"); ?> 
    </td>
</tr>
<?php
if ($user->get_login() != 'admin') { ?>
<!--
<tr>
<th><?php echo _("Pre-set executive panels to admin panels") ?></th>
    <td align="center">
   <input type="radio" name="copy_panels" value="1" <? if ($copy_panels) echo "checked" ?>> <?php echo _("Yes"); ?>
   <input type="radio" name="copy_panels" value="0" <? if (!$copy_panels) echo "checked" ?>> <?php echo _("No"); ?>
    </td>
</tr>
-->
<?php
} else { ?>
   <!--<input type="hidden"  name="copy_panels" value="1" checked>-->
<?php
} ?>
<?php
//if (Session::get_session_user() != $user && (!Session::am_i_admin())) {
?>
  <!--
  <tr>
    <td> <?php
    //echo gettext("Current password"); ?> </td>
    <td class="left"><input type="password" name="oldpass" /></td>
  </tr>-->
<?php
//}
?>
<tr>
    <td> <?php
echo gettext("Enter new password"); ?> </td>
    <td class="left"><input type="password" name="pass1" id="pass1" /></td>
  </tr>
  <tr>
	<td class="nobborder" style="padding:0px"></td>
	<td class="nobborder" style="padding:0px"><div id="pass1_text"></div><div id="pass1_bar"></div></td>
  </tr>
  <tr>
    <td> <?php
echo gettext("Retype new password"); ?> </td>
    <td class="left"><input type="password" name="pass2" id="pass2" /></td>
  </tr>
<tr>
    <td class="nobborder">&nbsp;</td>
    <td align="center" class="center nobborder">
      <input type="button" onclick="formsubmit()" class="btn" value="OK">
      <input type="reset" class="btn" value="<?php
echo gettext("reset"); ?>">
    </td>
</tr>
</table>
<? if (Session::am_i_admin() && $user->get_login() != Session::get_session_user()) { ?>
  <br/>
  <table align="center" cellspacing=8>
  <tr>
    <th> <?php echo gettext("Allowed nets"); ?> </th>
	<td class="nobborder"></td>
    <th> <?php echo gettext("Allowed sensors"); ?> </th>
	<td class="nobborder"></td>
	<th colspan=2> <?php echo gettext("Allowed Sections"); ?> </th>
  </tr>
  <tr>
    <td class="nobborder" valign="top" style="padding-top:8px">
	<a href="#" onclick="checkall('nets');return false;"><?php echo _("Select / Unselect all") ?></a>
	<hr noshade>
<?php
$i = 0;
foreach($net_list as $net) {
    $net_name = $net->get_name();
    $input = "<input type=\"checkbox\" class=\"nets\" name=\"net$i\" value=\"" . $net_name . "\"";
    if (false !== strpos(Session::allowedNets($user->get_login()) , $net->get_ips())) {
        $input.= " checked ";
    }
    if ($networks || ($user->get_login() == 'admin')) {
        $input.= " checked ";
    }
    if ($user->get_login() == 'admin') {
        $input.= "disabled";
    }
    $input.= "/>$net_name<br/>";
    echo $input;
    $i++;
}
?>
      <input type="hidden" name="nnets" value="<?php
echo $i ?>" />
      <br><br><i><?php
echo gettext("NOTE: No selection allows ALL") . " " . gettext("nets"); ?></i>
    </td>
	<td class="noborder" style="border-right:2px solid #E0E0E0"></td>
    <td valign="top" class="nobborder" style="padding-top:8px">
<a href="#" onclick="checkall('sensor');return false;"><?php
    echo gettext("Select / Unselect all"); ?></a>
<hr noshade>
<?php
$i = 0;
foreach($sensor_list as $sensor) {
    $sensor_name = $sensor->get_name();
    $sensor_ip = $sensor->get_ip();
    $input = "<input type=\"checkbox\" class=\"sensor\" name=\"sensor$i\" value=\"" . $sensor_ip . "\"";
    if (false !== strpos(Session::allowedSensors($user->get_login()) , $sensor_ip)) {
        $input.= " checked ";
    }
    if ($sensors || ($user->get_login() == 'admin')) {
        $input.= " checked ";
    }
    if ($user->get_login() == 'admin') {
        $input.= "disabled";
    }
    $input.= "/>$sensor_name<br/>";
    echo $input;
    $i++;
}
?>
      <input type="hidden" name="nsensors" value="<?php
echo $i ?>" />
      <br><br><i><?php
echo gettext("NOTE: No selection allows ALL") . " " . gettext("sensors"); ?></i>
    </td>
	
	<td class="noborder" style="border-right:2px solid #E0E0E0"></td>
    
<td class="nobborder">
	<table class="noborder">
		<tr>
			<td class="nobborder">
				<a href="#" onclick="checkall('perms');return false;"><?php
					echo gettext("Select / Unselect all"); ?></a>
			</td>
			<td class="nobborder" style="color:#777777;text-align:center" nowrap>
				<font style="color:black"><b>Granularity</b> Net / Sensor</font>
				<!--<br><img src="../pixmaps/tick.png"> <i>Checked is filtered</i>-->
			</td>
		</tr>
		<tr><td colspan=2 class="nobborder"><hr noshade></td></tr>
<input type="hidden" name="knowledgedb_perms" value="">
<?php
include ("granularity.php");
include ("perms_sections.php");
foreach($ACL_MAIN_MENU as $mainmenu => $menus) {
    foreach($menus as $key => $menu) {
		$color = ($i++ % 2 != 0) ? "bgcolor='#f2f2f2'" : "";
?>
            <tr <?=$color?>><td class="nobborder">
				<? if ($perms_sections[$key] != "") { ?>
				<a href="<?=$perms_sections[$key]?>?user=<?=$user->get_login()?>" title="Permissions Submenu" class="greybox"><img src="../pixmaps/plus.png" border=0></a>
				<? } else echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"; ?>
				<input type="checkbox" class="perms" name="<?php
        echo $key ?>"
<?php
        $checked = 0;
		if ($user->get_login() == 'admin') echo " disabled";
		if ($perms) $checked = 1;
        if (check_perms($user->get_login() , $mainmenu, $key)) $checked = 1;
        //if ($perms || ($user->get_login() == 'admin')) echo " checked ";
		if ($checked) echo " checked";
?>>
<?php
        $sensor_tick = ($granularity[$mainmenu][$key]['sensor']) ? "<img src='../pixmaps/tick.png'>" : "<img src='../pixmaps/tick_gray.png'>";
		$net_tick = ($granularity[$mainmenu][$key]['net']) ? "<img src='../pixmaps/tick.png'>" : "<img src='../pixmaps/tick_gray.png'>";
		echo $menu["name"] . "</td><td class='nobborder' style='text-align:center'>".$net_tick." ".$sensor_tick."</td></tr>\n";
    }
    echo "<tr><td colspan=2 class='nobborder'><hr noshade></td></tr>";
}
?>
	
	
	</table>
    </td>
  </tr>
</table>

<br/>
<table align="center">
  <tr>
    <td align="center" class="center nobborder">
      <input type="button" onclick="formsubmit()" class="btn" value="OK">
      <input type="reset" class="btn" value="<?php
echo gettext("reset"); ?>">
    </td>
  </tr>
</table>
</form>
<? } ?>
<? if ($frommenu) { ?>
</td></tr></table>
<? } ?>
<script>
$(document).ready(function(){
	GB_TYPE = 'w';
	$("a.greybox").click(function(){
		var t = this.title || $(this).text() || this.href;
		GB_show(t,this.href,300,"70%");
		return false;
	});
});
</script>
</body>
</html>

