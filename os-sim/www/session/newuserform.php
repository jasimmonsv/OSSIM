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
Session::logcheck("MenuConfiguration", "ConfigurationUsers");
?>

<?php
require_once ('ossim_acl.inc');
require_once ('ossim_db.inc');
require_once ('classes/Net.inc');
require_once ('classes/Sensor.inc');
$db = new ossim_db();
$conn = $db->connect();
$net_list = Net::get_all($conn);
$sensor_list = Sensor::get_all($conn, "ORDER BY name ASC");
$pass_length_min = ($conf->get_conf("pass_length_min", FALSE)) ? $conf->get_conf("pass_length_min", FALSE) : 7;
$pass_length_max = ($conf->get_conf("pass_length_max", FALSE)) ? $conf->get_conf("pass_length_max", FALSE) : 255;
if ($pass_length_max < $pass_length_min || $pass_length_max < 1) { $pass_length_max = 255; }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
<script type="text/javascript" src="../js/jquery.checkboxes.js"></script>
<script type="text/javascript" src="../js/jquery.pstrength.js"></script>
<script type="text/javascript">
	var checks = new Array;
	checks['nets'] = 0;
	checks['sensor'] = 0;
	checks['perms'] = 0;
	function checkall(col) {
		if (checks[col]) {
			$("#fnewuser").unCheckCheckboxes("."+col, true)
			checks[col] = 0;
		} else {
			$("#fnewuser").checkCheckboxes("."+col, true)
			checks[col] = 1;
		}
	}
function checkpasscomplex(pass) {
	<?php if ($conf->get_conf("pass_complex", FALSE) == "yes") { ?>
	var counter = 0;
	if (pass.match(/[a-z]/)) { counter++; }
	if (pass.match(/[A-Z]/)) { counter++; }
	if (pass.match(/[0-9]/)) { counter++; }
	if (pass.match(/[\!\"\·\$\%\&\/\(\)\|\#\~\€\¬]/)) { counter++; }
	return (counter < 3) ? 0 : 1;
	<?php } else { ?>
	return 1;
	<?php } ?>
}
function checkpasslength() {
	if ($('#pass1').val().length < <?php echo $pass_length_min ?>) {
		alert("<?=_("Minimum password size is ").$pass_length_min._(" characters")?>");
		return 0;
	} else if ($('#pass1').val().length > <?php echo $pass_length_max ?>) {
		alert("<?=_("Maximum password size is ").$pass_length_max._(" characters")?>");
		return 0;
	} else return 1;
}
function checkpass() {
	if (document.fnewuser.pass1.value != "" && !checkpasscomplex(document.fnewuser.pass1.value)) {
		alert("<?=_("Password is not strong enough. Check the password policy configuration for more details")?>");
		return 0;
	}
	else if (document.fnewuser.pass1.value != document.fnewuser.pass2.value) {
		alert("<?=_("Mismatches in passwords")?>");
		return 0;
	} else return 1;
}
function formsubmit() {
	if (checkpasslength() && checkpass()) document.fnewuser.submit();
}
</script>
</head>
<body onload="$('#pass1').pstrength()">

	<?php
include ("../hmenu.php"); ?>

<?php
$user = GET('user');
$pass1 = GET('pass1');
$pass2 = GET('pass2');
$name = GET('name');
$email = GET('email');
$company = GET('company');
$department = GET('department');
$networks = GET('networks');
$sensors = GET('sensors');
$perms = GET('perms');
//$copy_panels = GET('copy_panels');
ossim_valid($user, OSS_USER, OSS_NULLABLE, 'illegal:' . _("User name"));
//ossim_valid($copy_panels, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Copy panels"));
ossim_valid($name, OSS_ALPHA, OSS_PUNC, OSS_AT, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("Name"));
ossim_valid($email, OSS_MAIL_ADDR, OSS_NULLABLE, 'illegal:' . _("e-mail"));
ossim_valid($company, OSS_ALPHA, OSS_PUNC, OSS_AT, OSS_NULLABLE, 'illegal:' . _("Company"));
ossim_valid($department, OSS_ALPHA, OSS_PUNC, OSS_AT, OSS_NULLABLE, 'illegal:' . _("Department"));
ossim_valid($networks, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("Networks"));
ossim_valid($sensors, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("Sensors"));
ossim_valid($perms, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("Perms"));
if (ossim_error()) {
    die(ossim_error());
}
$all = $defaults = array();

?>

<form method="post" action="newuser.php" id="fnewuser" name="fnewuser">
<table align="center">
  <input type="hidden" name="insert" value="insert" />
  <tr>
    <th> <?php echo _("User login") . required() ?></th>
    <td class="left">
        <input type="text" id="1" name="user" value="<?php echo $user ?>" size="30" />
    </td>
  </tr>
  <tr>
    <th> <?php echo _("User full name") . required() ?> </th>
    <td class="left">
        <input type="text" id="2" name="name" value="<?php echo $name ?>" size="30" />
    </td>
  </tr>
  <tr>
    <th> <?php echo _("User Email") . required() ?> <img src="../pixmaps/email_icon.gif"></th>
    <td class="left">
        <input type="text" id="3" name="email" value="<?php echo $email ?>" size="30" />
    </td>
  </tr>
  <tr>
    <th> <?php echo _("Enter password") . required() ?> </th>
    <td class="left">
        <input type="password" id="pass1" name="pass1" value="<?php echo $pass1 ?>" size="30" />
    </td>
  </tr>
  <tr>
	<td class="nobborder" style="padding:0px"></td>
	<td class="nobborder" style="padding:0px"><div id="pass1_text"></div><div id="pass1_bar"></div></td>
  </tr>
  <tr>
    <th> <?php echo _("Re-enter password") . required() ?> </th>
    <td class="left">
        <input type="password" id="pass2" name="pass2" value="<?php echo $pass2 ?>" size="30" />
    </td>
  </tr>
  <tr>
    <th> <?php
echo gettext("User language"); ?></th>
    <td class="left">
<?php
$language = array(
    "type" => array(
        "en_GB" => gettext("English") ,
        "pt_BR" => gettext("Brazilian Portuguese") ,
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
    $lform.= "value=\"$option_value\">$option_text</option>";
}
$lform.= "</select>";
echo $lform;
?>
</td>
  </tr>
  <tr>
    <th> <?php echo _("Company") ?> </th>
    <td class="left">
        <input type="text" id="6" name="company" value="<?php echo $company ?>" size="30" />
    </td>
  </tr>
  <tr>
    <th> <?php echo _("Department") ?> </th>
    <td class="left">
        <input type="text" id="7" name="department" value="<?php echo $department ?>" size="30" />
    </td>
  </tr>

<tr>
<th><?php echo _("Ask to change password at first login") ?></th>
    <td align="center">
   <input type="radio" name="first_login" value="1"> <?php echo _("Yes"); ?>
   <input type="radio" name="first_login" value="0" checked> <?php echo _("No"); ?> 
    </td>
</tr>

<tr>
  <td>&nbsp;</td>
  <td align="center">
    <input type="button" onclick="formsubmit()" class="btn" value="OK">
    <input type="reset" class="btn" value="<?php
echo gettext("reset"); ?>">
  </td>
</tr>
 </table>
 
  <br/>
  <table align="center" cellspacing=8>
  <tr>
    <th><?php echo _("Allowed nets") ?></th>
	<td class="nobborder"></td>
    <th><?php echo _("Allowed sensors") ?></th>
	<td class="nobborder"></td>
    <th colspan="2"> <?php echo _("Allowed Sections") ?> </th>
  </tr>
  <tr>
    <td class="nobborder" valign="top" style="padding-top:8px">


<a href="#" onclick="checkall('nets');return false;"><?php echo _("Select / Unselect all") ?></a>
<hr noshade>

<?php
$i = 0;
foreach($net_list as $net) {
    $all['nets'][] = "net" . $i;
?>
        <input type="checkbox" class="nets" id="<?php echo "net" . $i ?>" name="<?php echo "net" . $i ?>"
               value="<?php echo $net->get_name(); ?>" /><?php echo $net->get_name() ?><br/>
<?php
    $i++;
}
?>
        <input type="hidden" name="nnets" value="<?php
echo $i ?>" /><br>
        <i><?php
echo gettext("NOTE: No selection allows ALL") . " " . gettext("nets"); ?></i>
    </td>
	<td class="noborder" style="border-right:2px solid #E0E0E0"></td>
	<td class="nobborder" valign="top" style="padding-top:8px">
<a href="#" onclick="checkall('sensor');return false;"><?php
    echo gettext("Select / Unselect all"); ?></a>
<hr noshade>

<?php
$i = 0;
foreach($sensor_list as $sensor) {
    $sensor_name = $sensor->get_name();
    $sensor_ip = $sensor->get_ip();
    $input = "<input type=\"checkbox\" class='sensor' name=\"sensor$i\" value=\"" . $sensor_ip . "\"";
    $input.= "/>$sensor_name<br/>";
    echo $input;
    $i++;
}
?>
      <input type="hidden" name="nsensors" value="<?php
echo $i ?>" />
      <br><i><?php
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
	
<?php
include ("granularity.php");
$i=0;
foreach($ACL_MAIN_MENU as $mainmenu => $menus) {
    foreach($menus as $key => $menu) {
		$color = ($i++ % 2 != 0) ? "bgcolor='#f2f2f2'" : "";
?>
            <tr <?=$color?>><td class="nobborder"><input class="perms" type="checkbox" name="<?php
        echo $key ?>"
<?php
        $checked = 0;
		//if ($user->get_login() == 'admin') echo " disabled";
		if ($perms) $checked = 1;
        //if (check_perms($user->get_login() , $mainmenu, $key)) $checked = 1;
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
    <td colspan="2" align="center" valign="top">
      <input type="button" onclick="formsubmit()" class="btn" value="OK">
      <input type="reset" class="btn" value="<?php
echo gettext("reset"); ?>">
    </td>
  </tr>
</table>
</form>  
</body>
</html>
