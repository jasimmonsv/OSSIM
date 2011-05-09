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
require_once ('classes/Security.inc');
require_once ('ossim_db.inc');
require_once ('classes/Databases.inc');

Session::logcheck("MenuConfiguration", "PolicyServers");

$error = false;

$sname =  POST('sname');
$ip    =  POST('ip');
$port  =  POST('port');
$user  =  POST('user');
$pass  =  POST('pass');
$pass2 =  POST('pass2');

$validate = array (
	"sname"  => array("validation"=>"OSS_ALPHA, OSS_PUNC" , "e_message" => 'illegal:' . _("Server name")),
	"ip"     => array("validation"=>"OSS_IP_ADDR"         , "e_message" => 'illegal:' . _("Ip address")),
	"port"   => array("validation"=>"OSS_PORT"            , "e_message" => 'illegal:' . _("Port number")),
	"user"   => array("validation"=>"OSS_USER"            , "e_message" => 'illegal:' . _("User")),
	"pass"   => array("validation"=>"OSS_ALPHA, OSS_PUNC" , "e_message" => 'illegal:' . _("Password")),
    "pass2"  => array("validation"=>"OSS_ALPHA, OSS_PUNC" , "e_message" => 'illegal:' . _("Rep. Password"))
    );

if ( GET('ajax_validation') == true )
{
	$validation_errors = validate_form_fields('GET', $validate);
	if ( $validation_errors == 1 )
		echo 1;
	else if ( empty($validation_errors) )
		echo 0;
	else
		echo $validation_errors[0];
		
	exit();
}
else
{
	$validation_errors = validate_form_fields('POST', $validate);
	
	
	if ( ( $validation_errors == 1 ) ||  (is_array($validation_errors) && !empty($validation_errors)) || $pass != $pass2 )
	{
		$error = true;
				
		$message_error = array();
        
       if( $pass != $pass2 )
            $message_error [] = _("Password fields are different");
		
		if ( is_array($validation_errors) && !empty($validation_errors) )
			$message_error = array_merge($message_error, $validation_errors);
		else
		{
			if ($validation_errors == 1)
				$message_error [] = _("Invalid send method");
		}
						
	}	
	
	if ( POST('ajax_validation_all') == true )
	{
		if ( is_array($message_error) && !empty($message_error) )
			echo utf8_encode(implode( "<br/>", $message_error));
		else
			echo 0;
		
		exit();
	}
}


if ( $error == true )
{
	$_SESSION['_dbs']['sname'] = $sname;
	$_SESSION['_dbs']['ip']    = $ip;
	$_SESSION['_dbs']['port']  = $port;
	$_SESSION['_dbs']['user']  = $user;
	$_SESSION['_dbs']['pass']  = $pass;
    $_SESSION['_dbs']['pass2'] = $pass2;
}


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>

<?php
if (POST('withoutmenu') != "1") 
{
	include ("../hmenu.php"); 
	$get_param = "name=$sname";	
}
else
	$get_param = "name=$sname&withoutmenu=1";	
?>
                                                                                
<h1> <?php echo gettext("Update Database Server"); ?> </h1>

<?php

if ( POST('insert') && !empty($sname) )
{
    if ( $error == true)
	{
		$txt_error = "<div>"._("We Found the following errors").":</div><div style='padding:10px;'>".implode( "<br/>", $message_error)."</div>";			
		Util::print_error($txt_error);	
		Util::make_form("POST", "newdbsform.php?".$get_param);
		die();
	}
		
    $db = new ossim_db();
    $conn = $db->connect();
	
	$icon = "";
    
	if (is_uploaded_file($HTTP_POST_FILES['icon']['tmp_name']))
       $icon = file_get_contents($HTTP_POST_FILES['icon']['tmp_name']);
   
    Databases::update($conn, $sname, $ip, $port, $user, $pass, $icon);
	
	$db->close($conn);
}

if ( isset($_SESSION['_dbs']) )
	unset($_SESSION['_dbs']);

?>
    <p> <?php echo gettext("Database server succesfully updated"); ?> </p>
    <? if ( $_SESSION["menu_sopc"]=="DBs" && POST('withoutmenu') != "1" ) { ?><script>document.location.href="dbs.php"</script><? } ?>

</body>
</html>
