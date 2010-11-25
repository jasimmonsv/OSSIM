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
* - check_phpgacl_install()
* Classes list:
*/
require_once "ossim_acl.inc";
require_once "ossim_conf.inc";
require_once "ossim_db.inc";
$conf = $GLOBALS["CONF"];
$gacl = $GLOBALS['ACL'];
function bad_browser()
{
    $u_agent = $_SERVER['HTTP_USER_AGENT'];
    if(preg_match('/MSIE 6/i',$u_agent))
    {
        return "Internet Explorer 6";
    }
	if(preg_match('/MSIE 5/i',$u_agent))
    {
        return "Internet Explorer 5";
    }
	return "";
}
function dateDiff($startDate, $endDate)
{
    // Parse dates for conversion
    $startArry = date_parse($startDate);
    $endArry = date_parse($endDate);

    // Convert dates to Julian Days
    $start_date = gregoriantojd($startArry["month"], $startArry["day"], $startArry["year"]);
    $end_date = gregoriantojd($endArry["month"], $endArry["day"], $endArry["year"]);

    // Return difference
    return round(($end_date - $start_date), 0);
}
function check_phpgacl_install() {
    global $gacl;
    $db_table_prefix = $gacl->_db_table_prefix;
    require_once "ossim_db.inc";
    $db = new ossim_db();
    if (!$conn = $db->phpgacl_connect()) {
        echo "<p align=\"center\">
                <b>Can't connect to OSSIM acl database (phpgacl)</b><br/>
                Check for phpgacl values at framework configuration
                </p>";
        exit;
    }
    $query1 = OssimQuery("SELECT * FROM acl");
    $query2 = OssimQuery("SELECT * FROM " . $db_table_prefix . "_acl");
    if ((!$conn->Execute($query1)) and (!$conn->Execute($query2))) {
        echo "
        <p align=\"center\"><b>You need to configure phpGACL</b><br/>
        Remember to setup the database connection at phpGACL config files!
        <br/>
        Click <a href=\"/phpgacl/setup.php\">here</a> to enter setup
        </p>
            ";
        exit;
    }
    $db->close($conn);
}
check_phpgacl_install();
if (!$gacl->acl_check(ACL_DEFAULT_DOMAIN_SECTION, ACL_DEFAULT_DOMAIN_ALL, ACL_DEFAULT_USER_SECTION, ACL_DEFAULT_OSSIM_ADMIN)) {
    echo "
            <p align=\"center\"><b>You need to setup default acls</b>
            <br/>
            Click <a href=\"../setup/ossim_acl.php\">here</a> to enter setup
            </p>
        ";
    exit;
}
require_once 'classes/Session.inc';
require_once 'classes/Security.inc';
$action = REQUEST('action');
ossim_valid($action, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("action"));
if ($action == "logout") {
    require_once 'classes/Log_action.inc';
    $infolog = array(
        Session::get_session_user()
    );
    if (trim($infolog[0]) != "") Log_action::log(2, $infolog);
    Session::logout();
}
$user = REQUEST('user');
$pass = base64_decode(REQUEST('pass'));
$accepted = REQUEST('first_login');
ossim_valid($user, OSS_USER, OSS_NULLABLE, 'illegal:' . _("User name"));
ossim_valid($accepted, OSS_NULLABLE, 'yes', 'no', 'illegal:' . _("First login"));
if (ossim_error()) {
    die(ossim_error());
}
$failed = true;
$first_login = "no";
if (REQUEST('user')) {
    require_once ("classes/Config.inc");
    $session = new Session($user, $pass, "");
    $conf = new Config();
    if ($accepted == "yes") $conf->update("first_login", "no");
	$is_disabled = $session->is_disabled();
	$login_return = $session->login();
	$first_userlogin = $session->first_login();
	$last_pass_change = $session->last_pass_change();
	$login_exists = $session->login_exists();
	
	if ($login_return != true) {
		$failed = true;
        $bad_pass = true;
        $failed_retries = $conf->get_conf("failed_retries", FALSE);
        if ($login_exists && !$is_disabled) {
        	$_SESSION['bad_pass'][$user]++;
	        if ($_SESSION['bad_pass'][$user] >= $failed_retries && $user != ACL_DEFAULT_OSSIM_ADMIN) {
	        	// auto-disable user
	        	$disabled = true;
	        	$session->login_disable();
	        }
        }
	} elseif (!$is_disabled) {
        $_SESSION['bad_pass'] = "";
		$first_login = $conf->get_conf("first_login", FALSE);
        if ($first_login == "" || $first_login == 0 || $first_login == "no") {
            $accepted = "yes";
        }
        $failed = false;
        if ($accepted=="yes") {
            $first_login = "no";
            $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB); //get vector size on ECB mode
            $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND); //Creating the vector
            $_SESSION["mdspw"] = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $conf->get_conf("md5_salt", FALSE) , $pass, MCRYPT_MODE_ECB, $iv);
            require_once 'classes/Log_action.inc';
            $infolog = array(
                REQUEST('user')
            );
            Log_action::log(1, $infolog);
            Log_action::log(92, array(md5($pass)));
            if (POST('maximized') == "1") {
?>
				<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
				<html xmlns="http://www.w3.org/1999/xhtml">
				<body><script>window.open("../index.php","full_main_window","fullscreen,scrollbars")</script></body>
				</html>
				<?php
            } elseif ($first_userlogin) {
				header("Location: first_login.php");
			} elseif ($conf->get_conf("pass_expire", FALSE) == 'yes' && dateDiff($last_pass_change,date("Y-m-d H:i:s")) >= 90) {
				header("Location: first_login.php?expired=1");
			} elseif ($user == ACL_DEFAULT_OSSIM_ADMIN && $pass == "admin") {
				header("Location: first_login.php?changeadmin=1");
			} else {
				header("Location: ../index.php");
			}
            exit;
        }
    }
}
//
// check if exists "enabled" field or create
//
$db = new ossim_db();
$conn = $db->connect();
Session::check_enabled_field($conn);
$db->close($conn);
$version = $conf->get_conf("ossim_server_version", FALSE);
$opensource = (!preg_match("/pro|demo/i",$version)) ? true : false;
$demo = (preg_match("/.*demo.*/i",$version)) ? true : false;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title> <?php echo gettext("AlienVault - ".($opensource ? "Open Source SIEM" : ($demo ? "Professional SIEM Demo" : "Professional SIEM"))); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/jquery.base64.js"></script>
  <link rel="Shortcut Icon" type="image/x-icon" href="../favicon.ico">
<script>
if (location.href != top.location.href) top.location.href = location.href;
var newwindow;
function new_wind(url,name)
{ 
        newwindow=window.open(url,name,'height=768,width=1024,scrollbars=yes');
        if (window.focus) {newwindow.focus()}
}
</script>
</head>
<?php
if ($failed) { ?>
<body onLoad="javascript:document.f.user.focus();" bgcolor=#aaaaaa>

<?php
    require_once 'classes/About.inc';
    $about = new About();
?>
<br/>
<br/>
<br/>
<br/>
<br/>
<br/>
<br/>
<br/>
<br/>
<form name="f" method="POST" action="login.php" onsubmit="$('#pass').val($.base64.encode($('#pass').val()));$('#submit_button').attr('disabled','disabled')" style="margin:1px">

<table align="center" style="padding:1px;background-color:#f2f2f2;border-color:#aaaaaa" class=nobborder><tr><td class="nobborder">
<table align="center" class="noborder" style="background-color:white">

  <tr><td class="noborder" style="text-align:right"><a href="javascript:new_wind('http://www.ossim.net/dokuwiki/doku.php?id=user_manual:introduction','Help')"><img src="../pixmaps/help_icon_gray.png" border="0"></a></td></tr>
  <tr> <td class="nobborder" style="text-align:center;padding:20px 20px 0px 20px">
       <img src="../pixmaps/ossim<?= (preg_match("/.*pro.*/i",$version)) ? "_siem" : ((preg_match("/.*demo.*/i",$version)) ? "_siemdemo" : "") ?>.png" />
  </td> </tr>
 
  <tr>
    <td align="center" class="nobborder" style="text-align:center">
      <br/><br/><br/>
    </td>
  </tr>
   <tr>
    <td class="nobborder center">
	  <table align="center" cellspacing=4 cellpadding=2 style="background-color:#eeeeee;border-color:#dedede">
	  <tr>
	    <td style="text-align:right" class="nobborder"> <?php
    echo gettext("User"); ?> </td>
	    <td style="text-align:left" class="nobborder"><input type="text" name="user" /></td>
	  </tr>
	  <tr>
	    <td style="text-align:right" class="nobborder"> <?php
    echo gettext("Password"); ?> </td>
	    <td style="text-align:left" class="nobborder"><input type="password" id="pass" name="pass" /></td>
	  </tr>
	  </table>
    </td>
  </tr>
  <tr>
    <td class="nobborder" style="text-align:center;height:30px;font-size:12px">

    <input type="checkbox" value="1" name="maximized" style="font-size:7px"> Maximized

    </td>
  </tr>
  <tr>
    <td class="nobborder" style="text-align:center;padding-top:20px">

    <input type="submit" id="submit_button" value="<?php
    echo gettext("Login"); ?>" class="btn" style="font-size:12px">

    </td>
  </tr>
  <tr>
    <td class="nobborder" style="text-align:center">
    <br/>
    </td>
  </tr>
</table>

    </td>
  </tr>
	<? if (($br = bad_browser()) != "") {?>
	<tr>
		<td class="blue" width="200" bgcolor="#FBEDEC" style="border:1px solid #FDA8A8" align="center"><i><?=_("<b>Warning</b>: $br is <b>not compatible</b> with OSSIM.<br> Please use Internet Explorer 7 (or newer), Firefox or Chrome")?></i></td>
	</tr>
	<? } ?>
</table>
</form>

  <?php
	if ($is_disabled) echo "<p><font color=\"red\">" . gettext("The User")." <b>$user</b> "._("is ")."<b>"._("disabled")."</b>."._(" Please contact the administrator.") . "</font></p>";
	elseif (isset($bad_pass)) echo "<p><font color=\"red\">" . gettext("Wrong User & Password") . "</font></p>";
	if ($disabled) echo "<p><font color=\"red\">" . _("This user has been disabled for security reasons. Please contact with the administrator.") . "</font></p>";
?>

</body>

<?php
}
if ($first_login=="yes") { // first login
     ?>

<body bgcolor=#aaaaaa>

<form name="f" method="POST" onsubmit="$('#pass').val($.base64.encode($('#pass').val()))" action="login.php" style="margin:1px">
<input type="hidden" name="user" value="<?php echo $user ?>"/>
<input type="hidden" name="pass" id="pass" value="<?php echo $pass ?>"/>
<input type="hidden" name="first_login" value="yes"/>

<table align="center" style="padding:1px;background-color:#f2f2f2;border-color:#aaaaaa" class=nobborder><tr><td class="nobborder">
<table align="center" class="noborder" style="background-color:white">

  <tr><td class="noborder" style="text-align:right"><a href="javascript:new_wind('http://www.ossim.net/dokuwiki/doku.php?id=user_manual:introduction','Help')"><img src="../pixmaps/help_icon_gray.png" border="0"></a></td></tr>
  <tr> <td class="nobborder" style="text-align:center;padding:10px 20px 0px 20px">
       <img src="../pixmaps/ossim<?= (preg_match("/.*pro.*/i",$version)) ? "_siem" : ((preg_match("/.*demo.*/i",$version)) ? "_siemdemo" : "") ?>.png" />
  </td> </tr>
 
  <tr>
    <td align="center" class="nobborder" style="padding-top:10px">
		<table height="400" width="740"><tr><td class="nobborder">
			<div style="text-align:left;padding:5px;height:400px;overflow-y:scroll">
			<?php
    if (file_exists("../../include/First_login.txt")) {
        require_once ("../../include/First_login.txt");
    }
?>
			</div>
		</td></tr>
		</table>
    </td>
  </tr>
  <tr>
    <td class="nobborder" style="text-align:center;padding-top:20px">
	
	<input type="submit" value="<?php
    echo gettext("Accept"); ?>" class="btn" style="font-size:12px"> &nbsp;&nbsp;&nbsp;
	<input type="button" onclick="document.location.href='login.php'" value="<?php
    echo gettext("Logout"); ?>" class="btn" style="font-size:12px">
	
    </td>
  </tr>
  <tr>
    <td class="nobborder" style="text-align:center">
    <br/>
    </td>
  </tr>
</table>

    </td>
  </tr>
</table>

</form>
</body>

<?php
} ?>
</html>

