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
require_once ('classes/Session.inc');
require_once ("classes/Security.inc");
require_once ('classes/User_config.inc');
//Session::logcheck("MenuConfiguration", "ConfigurationUsers");

function dateDiff_min($startDate, $endDate)
{
    $to_time=strtotime($startDate);
	$from_time=strtotime($endDate);
	return floor(abs($to_time - $from_time) / 60);
}

$user        		= POST('user');
$pass1       		= POST('pass1');
$pass2       		= POST('pass2');
$oldpass     		= POST('oldpass');
$name        		= POST('name');
$email       		= POST('email');
$nnets       		= POST('nnets');
$nsensors    		= POST('nsensors');
$company     		= POST('company');
$department  		= POST('department');
$language   		= POST('language');
$frommenu    		= POST('frommenu');
$first_login 		= POST('first_login');
$is_admin           = POST('is_admin') ? POST('is_admin') : 0;
$tzone				= POST('tzone');
$last_pass_change   = POST('last_pass_change');
//$copy_panels = POST('copy_panels');
//ossim_valid($copy_panels, OSS_DIGIT, 'illegal:' . _("Copy Panels"));

ossim_valid($user, OSS_USER, 'illegal:' . _("User name"));
ossim_valid($name, OSS_ALPHA, OSS_PUNC, OSS_AT, OSS_SPACE, 'illegal:' . _("Name"));
ossim_valid($pass1, OSS_ALPHA, OSS_DIGIT, OSS_PUNC_EXT, OSS_NULLABLE, 'illegal:' . _("pass1"));
ossim_valid($pass2, OSS_ALPHA, OSS_DIGIT, OSS_PUNC_EXT, OSS_NULLABLE, 'illegal:' . _("pass2"));
ossim_valid($oldpass, OSS_ALPHA, OSS_DIGIT, OSS_PUNC_EXT, OSS_NULLABLE, 'illegal:' . _("oldpass"));
ossim_valid($email, OSS_NULLABLE, OSS_MAIL_ADDR, 'illegal:' . _("e-mail"));
ossim_valid($nnets, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("nnets"));
ossim_valid($nsensors, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("nsensors"));
ossim_valid($company, OSS_ALPHA, OSS_PUNC, OSS_AT, OSS_NULLABLE, 'illegal:' . _("Company"));
ossim_valid($department, OSS_ALPHA, OSS_PUNC, OSS_AT, OSS_NULLABLE, 'illegal:' . _("Department"));
ossim_valid($language, OSS_ALPHA, OSS_PUNC, OSS_AT, OSS_NULLABLE, 'illegal:' . _("Language"));
ossim_valid($frommenu, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("frommenu"));
ossim_valid($first_login, OSS_DIGIT, 'illegal:' . _("First Login"));
ossim_valid($is_admin, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("is admin"));
ossim_valid($tzone, OSS_DIGIT, '-', '+', '.', 'illegal:' . _("tzone"));
ossim_valid($last_pass_change, OSS_DIGIT, OSS_PUNC_EXT, OSS_NULLABLE, 'illegal:' . _("last pass change"));

$kdbperms = "";
$langchanged = 0;

if (POST('knowledgedb_perms') != "") {
	$arr = split (",",POST('knowledgedb_perms'));
	foreach ($arr as $u) {
		$kdbperms[$u] = 1;
	}
}

if (ossim_error()) 
    die(ossim_error());

if (!Session::am_i_admin() && Session::get_session_user() != $user)
	die(_("You don't have permission to see this page"));
	
/*
if (!Session::am_i_admin()) {
    require_once ("ossim_error.inc");
    $error = new OssimError;
    $error->display("ONLY_ADMIN");
}
*/
/* check OK, insert into DB */
elseif (POST("insert")) 
{
    require_once ('ossim_db.inc');
    require_once ('ossim_acl.inc');
    require_once ('classes/Session.inc');
    require_once ('classes/Net.inc');
    require_once ('classes/Sensor.inc');
    $perms = array();
    
	foreach($ACL_MAIN_MENU as $menus) {
        foreach($menus as $key => $menu) {
            if (POST($key) == "on") $perms[$key] = true;
            else $perms[$key] = false;
        }
    }
    $db = new ossim_db();
    $conn = $db->connect();
    
	/*
	if ($copy_panels == 1) {
        User_config::copy_panel($conn, "admin", $user);
    }
	*/
    $nets = "";
    for ($i = 0; $i < $nnets; $i++)
	{
        $net_name = POST("net$i");
        ossim_valid($net_name, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("net$i"));
        if (ossim_error()) {
            die(ossim_error());
        }
        if ($net_list = Net::get_list($conn, "name = '$net_name'")) {
            foreach($net_list as $net) {
                if ($nets == "") $nets = $net->get_ips();
                else $nets.= "," . $net->get_ips();
            }
        }
    }
	
    $sensors = "";
    
	for ($i = 0; $i < $nsensors; $i++) {
        ossim_valid(POST("sensor$i") , OSS_LETTER, OSS_DIGIT, OSS_DOT, OSS_NULLABLE, 'illegal:' . _("sensor$i"));
        if (ossim_error()) {
            die(ossim_error());
        }
        if ($sensors == "") $sensors = POST("sensor$i");
        else $sensors.= "," . POST("sensor$i");
    }
    
	if (Session::am_i_admin()) {
		require_once("classes/Util.inc");
		Session::update($conn, $user, $name, $email, $perms, $nets, $sensors, $company, $department, $language, $kdbperms, $first_login, $is_admin, $tzone);
		Util::clean_json_cache_files("",$user);
	}
	else {
		Session::update_noperms($conn, $user, $name, $email, $company, $department, $language, $first_login, $is_admin, $tzone);
	}
	
	if ($user == Session::get_session_user() && $language != $_SESSION['_user_language']) {
		$_SESSION['_user_language'] = $language;
		ossim_set_lang($language);
		$langchanged = 1;
	}
	if ($user == Session::get_session_user() && $_SESSION['_timezone'] != $tzone) {
		$_SESSION['_timezone'] = $tzone;
	}
    
	// PASSWORD
	if (POST("pass1") && POST("pass2"))
	{
		/*
		if (!Session::am_i_admin() && (($_SESSION["_user"] != $user) && !POST("oldpass"))) {
			require_once ("ossim_error.inc");
			$error = new OssimError();
			$error->display("FORM_MISSING_FIELDS");
		}
		*/
		
		//Password complexity check
		
		$recent_pass     = Log_action::get_last_pass($conn, $user);
		$pass_length_min = ($conf->get_conf("pass_length_min", FALSE)) ? $conf->get_conf("pass_length_min", FALSE) : 7;
		$pass_length_max = ($conf->get_conf("pass_length_max", FALSE)) ? $conf->get_conf("pass_length_max", FALSE) : 255;
		
		if ($pass_length_max < $pass_length_min || $pass_length_max < 1) { $pass_length_max = 255; }
		$pass_expire_min = ($conf->get_conf("pass_expire_min", FALSE)) ? $conf->get_conf("pass_expire_min", FALSE) : 0;
		
		$user_list = Session::get_list($conn, "WHERE login = '" . $user . "' and pass = '" . md5($oldpass) . "'");
		if (0 != strcmp($pass1, $pass2)) {
			require_once ("ossim_error.inc");
			$error = new OssimError();
			$error->display("PASSWORDS_MISMATCH");
		} 
		elseif (count($user_list = Session::get_list($conn, "WHERE login = '" . $_SESSION["_user"] . "' and pass = '" . md5($oldpass) . "'")) < 1) 
		{
			require_once ("ossim_error.inc");
			$error = new OssimError();
			if ($user != $_SESSION["_user"]) {
				$error->display(_("BAD_ADMIN_PASSWORD"));
			} else {
				$error->display(_("BAD_OLD_PASSWORD"));
			}
		} 
		elseif (strlen($pass1) < $pass_length_min)
		{
			require_once ("ossim_error.inc");
		    $error = new OssimError();
		    $error->display("PASSWORD_SIZE");
		}
		elseif (strlen($pass1) > $pass_length_max)
		{
			require_once ("ossim_error.inc");
		    $error = new OssimError();
		    $error->display("PASSWORD_SIZE_MAX");
		} 
		elseif (!Session::pass_check_complexity($pass1))
		{
			require_once ("ossim_error.inc");
		    $error = new OssimError();
		    $error->display("PASSWORD_ALPHANUM");
		} 
		elseif ($pass_expire_min > 0 && dateDiff_min($last_pass_change,date("Y-m-d H:i:s")) < $pass_expire_min) 
		{
			require_once ("ossim_error.inc");
		    $error = new OssimError();
		    $error->display("PASSWORD_EXPIRE_MIN");
		} 
		elseif (count($recent_pass) > 0 && in_array(md5($pass1),$recent_pass))
		{
			require_once ("ossim_error.inc");
		    $error = new OssimError();
		    $error->display("PASSWORD_RECENT");
		}
		/* check for old password if not actual user or admin */
		/*
		if ((($_SESSION["_user"] != $user) && $_SESSION["_user"] != ACL_DEFAULT_OSSIM_ADMIN) && !is_array($user_list = Session::get_list($conn, "WHERE login = '" . $user . "' and pass = '" . md5($oldpass) . "'"))) {
			require_once ("ossim_error.inc");
			$error = new OssimError();
			$error->display("BAD_OLD_PASSWORD");
		}*/
		/* only the user himself or the admin can change passwords */
		if ((POST('user') != $_SESSION["_user"]) && !Session::am_i_admin()) {
			die(ossim_error(_("To change the password for other user is not allowed")));
		}
		Session::changepass($conn, $user, $pass1, $oldpass);
		Session::log_pass_history($user,md5($pass1));
		
		if ( method_exists('Session_activity', 'expire_my_others_sessions') )
			Session_activity::expire_my_others_sessions($conn, $user);
						
	}
	
	$db->close($conn);

?>	
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html>
	<head>
		<title> <?php echo gettext("OSSIM Framework"); ?> </title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
		<META http-equiv="Pragma" content="no-cache"/>
		<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	</head>
	
	<body>
		<h1> <?php echo gettext("Modify User"); ?> </h1>
	
		<?php
		if ($langchanged) {
			?><script type="text/javascript">top.topmenu.location = '../top.php?option=7&soption=1';</script><?php
		}
		?>
    
		<p> <?php echo gettext("User succesfully updated"); ?> </p>
		<?php
			if ($frommenu) 
				$location = "modifyuserform.php?user=".$user."&frommenu=1&success=1";
			else 
				$location = "users.php";
			sleep(2);
			echo "<script>window.location='$location';</script>";
		
	}
?>


</body>
</html>

