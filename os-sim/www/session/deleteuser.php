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
require_once ('ossim_db.inc');
Session::logcheck("MenuConfiguration", "ConfigurationUsers");

// Get user uuid
function get_report_uuid()
{
	require_once ('classes/Session.inc');
	
	$uuid     = Session::get_secure_id();
	$url      = null;
	
	if ( empty($uuid) )
	{
		$db     = new ossim_db();
		$dbconn = $db->connect();
		
		$user   = Session::get_session_user();
		
		$query  = 'SELECT * FROM `users` WHERE login="'.$user.'"';
		$result = $dbconn->Execute($query);
		
		if ( is_array($result->fields) && !empty($result->fields) )
		{
			$pass = $result->fields["pass"];
			$uuid = sha1($user."#".$pass);
		}
		else
			$uuid = false;
		
	}
	
	return $uuid;

}

$loguser = Session::get_session_user();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv=="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>

	<h1> <?php echo gettext("Delete user"); ?> </h1>

<?php

$user = GET('user');

ossim_valid($user, OSS_USER, 'illegal:' . _("User name"));

if ( ossim_error() )
{
    die(ossim_error());
}


if (!Session::am_i_admin()) 
{
    require_once ("ossim_error.inc");
    $error = new OssimError();
    $error->display("ONLY_ADMIN");
}

if (!GET('confirm'))
{
?>
    <p> <?php echo gettext("Are you sure"); ?> </p>
    <p>
		<a href="<?php echo $_SERVER["SCRIPT_NAME"] . "?user=$user&confirm=yes"; ?>"><?php echo gettext("Yes"); ?> </a> &nbsp;&nbsp;&nbsp;
		<a href="users.php"> <?php  echo gettext("No"); ?> </a>
    </p>
<?php
    exit();
}

if ($loguser == $user) 
{
    require_once ("ossim_error.inc");
    $error = new OssimError();
    $error->display("USER_CANT_REMOVE");
}


$db   = new ossim_db();
$conn = $db->connect();

//Remove associated PDF report 

$uuid = get_report_uuid();
$url  = "/usr/share/ossim/www/tmp/scheduler/$uuid";
if ( is_dir($url) && !empty($uuid) )
	exec("rm -r $url");

Session::delete($conn, $user);

$db->close($conn);
?>

    <p> <?php echo gettext("User deleted"); ?> </p>
<?php
	$location = "users.php";
	sleep(2);
	echo "<script> window.location='$location'; </script>";
	exit(); 
?>

</body>
</html>

