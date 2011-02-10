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
require_once "classes/Security.inc";
require_once "classes/Session.inc";
require_once "ossim_conf.inc";
require_once ('ossim_db.inc');

/* connect to db */
$db   = new ossim_db();
$conn = $db->connect();

if ($_SESSION['_user'])
{
	$user = $_SESSION['_user'];
	$_SESSION['_backup_user'] = $_SESSION['_user'];
	unset($_SESSION['_user']); // destroy session to force password change
} else 
	$user = $_SESSION['_backup_user'];


$recent_pass = Log_action::get_last_pass($conn, $user);

$conf        = $GLOBALS["CONF"];

if (!isset($_SESSION["_user"]) && !isset($_SESSION["_backup_user"]))
{
    $ossim_link = $conf->get_conf("ossim_link", FALSE);
    $login_location = $ossim_link . '/session/login.php';
	header("Location: $login_location");
	exit;
}

$version    =  $conf->get_conf("ossim_server_version", FALSE);
$opensource = (!preg_match("/.*pro.*/i",$version) && !preg_match("/.*demo.*/i",$version)) ? true : false;
$demo       = (preg_match("/.*demo.*/i",$version)) ? true : false;

$pass1          = base64_decode(POST('pass1'));
$pass2 			= base64_decode(POST('pass2'));
$current_pass   = base64_decode(POST('current_pass'));

$flag           = POST('flag');
$changeadmin    = POST('changeadmin');
$expired        = POST('expired');

ossim_valid($current_pass, OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE, 'illegal:' . _("Current Password"));
ossim_valid($pass1, OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE, 'illegal:' . _("Password"));
ossim_valid($pass2, OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE, 'illegal:' . _("Rewrite Password"));

ossim_valid($flag, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("flag"));
ossim_valid($changeadmin, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("changeadmin"));
ossim_valid($expired, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("expired"));

if (ossim_error()) {
    die(ossim_error());
}

if ($flag != "") {
	
	/* check passwords */
	$pass_length_min = ($conf->get_conf("pass_length_min", FALSE)) ? $conf->get_conf("pass_length_min", FALSE) : 7;
	$pass_length_max = ($conf->get_conf("pass_length_max", FALSE)) ? $conf->get_conf("pass_length_max", FALSE) : 255;
	
	if ( $pass_length_max < $pass_length_min || $pass_length_max < 1 ) 
		$pass_length_max = 255;
		
	if ( empty($current_pass) )
		$msg = _("Current password is empty");
	elseif (0 != strcmp($pass1, $pass2)) 
		$msg = _("Passwords mismatches");
	elseif (strlen($pass1) < $pass_length_min) 
		$msg = _("Password is long enought. The minimum is ").$pass_length_min._(" characters.");
	elseif (strlen($pass1) > $pass_length_max) 
		$msg = _("Password is too long. The maximum is ").$pass_length_max._(" characters.");
	elseif (!Session::pass_check_complexity($pass1)) 
		$msg = _("Password is not complex enought.");
	elseif (count($recent_pass) > 0 && in_array(md5($pass1),$recent_pass)) 
		$msg = _("This password is recently used. Try another.");
	elseif (count($user_list = Session::get_list($conn, "WHERE login = '" . $user . "' and pass = '" . md5($pass1) . "'")) > 0) 
		$msg = _("You must change your old password.");
	else 
	{
		$_SESSION['_user'] = $_SESSION['_backup_user'];
		
		unset($_SESSION['_backup_user']);
		
		if (preg_match("/pro|demo/",$conf->get_conf("ossim_server_version", FALSE)))
			$res = Acl::changepass($conn, $user, $pass1, $current_pass);
		else
			$res = Session::changepass($conn, $user, $pass1, $current_pass);
			
		if ($res > 0)
		{
			if (preg_match("/pro|demo/",$conf->get_conf("ossim_server_version", FALSE))) 
				Acl::changefirst($conn, $user);
			else 
				Session::changefirst($conn, $user);
			
			Session::log_pass_history($user,md5($pass1));
			if (GET('expired') == "" && GET('changeadmin') == "" && $user == ACL_DEFAULT_OSSIM_ADMIN && preg_match("/.*pro.*/i",$version)) {
				header("location:customize_logos.php");
			} else {
				header("location:../index.php");
			}
		}
		else
			$msg = "Current password does not match";
	}
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title> <?php echo gettext("AlienVault - ".($opensource ? "Open Source SIEM" : ($demo ? "Professional SIEM Demo" : "Professional SIEM"))); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<META http-equiv="Pragma" CONTENT="no-cache"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery.pstrength.js"></script>
	<script type="text/javascript" src="../js/jquery.base64.js"></script>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<link rel="Shortcut Icon" type="image/x-icon" href="../favicon.ico">
	
	<script type='text/javascript'>
		$(document).ready(function() {
			$('#fnewpass').bind('submit', function() {
				
				var pass1        = $('#pass1u').val().trim();
				var pass2        = $('#pass2u').val().trim();
				var current_pass = $('#current_passu').val().trim();
				
				if ( pass1 != '' )        $('#pass1').val($.base64.encode(pass1));
				if ( pass2 != '' )        $('#pass2').val($.base64.encode(pass2));
				if ( current_pass != '' ) $('#current_pass').val($.base64.encode(current_pass));
			});
		});
						
	</script>
  
  
</head>

<body onload="$('#pass1u').pstrength()">

<br/><br/><br/><br/><br/><br/><br/><br/><br/>
<form id='fnewpass' name='fnewpass' method='POST'>
	<input type="hidden" name="flag" value="1"/>
	<input type="hidden" name="changeadmin" value="<?=$changeadmin?>"/>
	<input type="hidden" name="expired" value="<?=$expired?>">
	
	<table align="center" style="padding:1px;background-color:#f2f2f2;border-color:#AAAAAA;" class='nobborder'/>
		<tr>
			<td class="nobborder">
				<table align="center" class="noborder" style="background-color:white;">
	
					<tr>
						<td class="nobborder" style="text-align:center;padding:30px 20px 0px 20px">
							<img src="../pixmaps/ossim<?= (preg_match("/.*pro.*/i",$version)) ? "_siem" : ((preg_match("/.*demo.*/i",$version)) ? "_siemdemo" : "") ?>.png" />
						</td>
					</tr>
					
					<? if ($changeadmin) { ?>
					<tr><td class="center nobborder" style="padding-top:20px;padding-bottom:20px"><?=_("The administrator has a <b>vulnerable password</b>. You must change it now.")?></td></tr>
					<? } elseif ($expired) { ?>
					<tr><td class="center nobborder" style="padding-top:20px;padding-bottom:20px"><?=_("Your password has <b>expired</b>.<br>Please enter your new password.")?></td></tr>
					<? } else { ?>
					<tr><td class="center nobborder" style="padding-top:20px;padding-bottom:20px"><?=_("The administrator has requested a <b>password change after first login</b>.<br>Please enter your new password")?></td></tr>
					<? } ?>
		
					<tr>
						<td class="nobborder center">
							<table align="center" cellspacing='4' cellpadding='2' style="background-color:#eeeeee;border-color:#dedede">
								<tr>
									<td style="text-align:right;padding-top:4px" valign="top" class="nobborder"> <?php 	echo gettext("Current Password"); ?> </td>
									<td style="text-align:left" class="nobborder">
										<input type="password" name="current_passu" id="current_passu"/>
										<input type="hidden" name="current_pass" id="current_pass"/>
									</td>
								</tr>
								
								<tr>
									<td style="text-align:right;padding-top:4px" valign="top" class="nobborder"> <?php 	echo gettext("New Password"); ?> </td>
									<td style="text-align:left" class="nobborder">
										<input type="password" name="pass1u" id="pass1u"/>
										<input type="hidden" name="pass1" id="pass1"/>
									</td>
								</tr>
								
								<tr>
									<td style="text-align:right" class="nobborder"> <?php echo gettext("Rewrite Password"); ?> </td>
									<td style="text-align:left" class="nobborder">
										<input type="password" name="pass2u" id="pass2u"/>
										<input type="hidden" name="pass2" id="pass2"/>
									</td>
								</tr>
							</table>
						</td>
					</tr>
   
					<? if ($msg != "") { ?>
					<tr><td class="center nobborder" style="color:red"><?=$msg?></td></tr>
					<? } ?>
					
					<tr>
						<td class="nobborder" style="text-align:center;padding-top:20px">
							<input type="submit" value="<?php echo gettext("Change"); ?>" class="button"/>
						</td>
					</tr>
					
					<tr>
						<td class="nobborder" style="text-align:center"><br/></td>
					</tr>
			
				</table>
			</td>
		</tr>
	</table>
</form>
</body>
</html>

