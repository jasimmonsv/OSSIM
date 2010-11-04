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
Session::logcheck("MenuPolicy", "PolicyHosts");
require_once ('classes/Host.inc');
require_once ('ossim_db.inc');
require_once ('classes/RRD_config.inc');
require_once ('classes/Security.inc');

?>

<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>

<?php

$error = false;
$txt_error = array();
$db = new ossim_db();
$conn = $db->connect();

$action    = POST('action');
$ip        = POST('ip');
$type      = POST('type');
$username  = POST('username');
$password  = POST('password');
$password2 = POST('password2');
$extra     = POST('extra');

if ($action ==  "edit")
{
	$txt_start = "Update Host Credentials";
	$txt_end = "Host Credentials succesfully updated";
}
else if ($action == "clean")
{
	$txt_start = "Clean Host Credentials";
	$txt_end = "Host Credentials succesfully cleaned";
}
else
{
	$error = true;
	$txt_error[] = "Illegal action";
}

if ( $error == false )
{
	$fields = array ("ip"=>$ip, 
					"type"=>$type, 
					"username"=>$username, 
					"password"=>$password, 
					"password2"=>$password2,
					"extra"=>$extra);
					
	foreach ($fields as $k => $v)
	{
		switch ($k)
		{
			case "ip":
				ossim_valid($v, OSS_IP_ADDR, 'illegal:' . _("ip"));
			break;
			
			case "type":
				ossim_valid($v, OSS_DIGIT, 'illegal:' . _("type"));
			break;
			
			case "username":
				if ($action == "modify")
					ossim_valid($v, OSS_USER, 'illegal:' . _("username"));
			break;
			
			case "password":
				if ($action == "modify")
					ossim_valid($v, OSS_SCORE, OSS_ALPHA, OSS_PUNC, OSS_AT, 'illegal:' . _("password"));
			break;
			
			case "password2":
				if ($action == "modify")
				{
					ossim_valid($v, OSS_SCORE, OSS_ALPHA, OSS_PUNC, OSS_AT, 'illegal:' . _("password"));
				
					if ($password != $v)
					{
						$error = true;
						$txt_error[] = gettext("Password fields are different");
					}
				}
			break;
			
			case "extra":
				ossim_valid($v, OSS_NULLABLE, OSS_SCORE, OSS_ALPHA, OSS_PUNC, OSS_AT, OSS_NL, 'illegal:' . _("extra"));
			break;
			
			case "action":
				ossim_valid($action, OSS_ALPHA, OSS_SCORE, 'illegal:' . _("action"));
			break;
		}
		
		if (ossim_error())
		{
		   $error = true;
		   $txt_error[] = ossim_get_error_clean();
		   ossim_clean_error();
		}
	
	}
	

	if ( $error == false )
	{
		if ($action == 'edit')
			Host::modify_credentials($conn, $ip, $type, $username, $password, $extra);
		else{
			if ($action == 'clean') {
				Host::clean_credentials($conn, $ip);
			}	
		}
	}
}

$db->close($conn);

echo "<h1>".gettext($txt_start)."</h1>";

if ($error == true)
{
	?>
	<div id='info_error' class='ct_error' style='display: block;'><div style='padding: 10px;'><?php echo implode("<br/>", $txt_error);?></div></div>
	<div style='text-align: center; margin: auto;'>
	<form method='GET' action='hostcredentialsform.php'>
		<input type='submit' value='Back' name='submit' class='button'/>
		<input type='hidden' value='<?php echo $ip?>' name='ip' id='ip'/>
	</form>
	</div>
	
	<?php
}
else
{
    echo "<p>"._($txt_end)."</p>";
    echo "<script type='text/javascript'>document.location.href=\"host.php\"</script>";
	// update indicators on top frame
	
}
?>
</body>
</html>

