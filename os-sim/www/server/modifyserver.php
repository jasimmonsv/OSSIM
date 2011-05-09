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
require_once ('classes/Server.inc');
require_once ('classes/Util.inc');

Session::logcheck("MenuConfiguration", "PolicyServers");

$error = false;

$sname           =  POST('sname');
$ip              =  POST('ip');
$port            =  POST('port');
$descr           =  POST('descr');
$correlate       = (POST('correlate')) ? 1 : 0;
$cross_correlate = (POST('cross_correlate')) ? 1 : 0;
$store           = (POST('store')) ? 1 : 0;
$qualify         = (POST('qualify')) ? 1 : 0;
$resend_events   = (POST('resend_events')) ? 1 : 0;
$resend_alarms   = (POST('resend_alarms')) ? 1 : 0;
$sign            = (POST('sign')) ? 1 : 0;
$multi           = (POST('multi')) ? 1 : 0;
$sem             = (POST('sem')) ? 1 : 0;
$sim             = (POST('sim')) ? 1 : 0;


$validate = array (
	"sname"  	      => array("validation"=>"OSS_ALPHA, OSS_PUNC", "e_message" => 'illegal:' . _("Server name")),
	"ip"        	  => array("validation"=>"OSS_IP_ADDR", "e_message" => 'illegal:' . _("Ip")),
	"port"      	  => array("validation"=>"OSS_PORT", "e_message" => 'illegal:' . _("Port number")),
	"descr"     	  => array("validation"=>"OSS_ALPHA, OSS_NULLABLE, OSS_PUNC, OSS_AT, OSS_NL", "e_message" => 'illegal:' . _("Description")),
	"correlate"       => array("validation"=>"OSS_ALPHA, OSS_NULLABLE", "e_message" => 'illegal:' . _("Correlation")),
	"cross_correlate" => array("validation"=>"OSS_ALPHA, OSS_NULLABLE", "e_message" => 'illegal:' . _("Cross Correlation")),
	"store" 		  => array("validation"=>"OSS_ALPHA, OSS_NULLABLE", "e_message" => 'illegal:' . _("Store")),
	"qualify" 		  => array("validation"=>"OSS_ALPHA, OSS_NULLABLE", "e_message" => 'illegal:' . _("Qualify")),
	"resend_alarms"   => array("validation"=>"OSS_ALPHA, OSS_NULLABLE", "e_message" => 'illegal:' . _("Resend Alarms")),
	"resend_events"   => array("validation"=>"OSS_ALPHA, OSS_NULLABLE", "e_message" => 'illegal:' . _("Resend Events")),
	"sign" 			  => array("validation"=>"OSS_ALPHA, OSS_NULLABLE", "e_message" => 'illegal:' . _("Sign")),
	"multi"			  => array("validation"=>"OSS_ALPHA, OSS_NULLABLE", "e_message" => 'illegal:' . _("Multilevel")),
	"sem" 			  => array("validation"=>"OSS_ALPHA, OSS_NULLABLE", "e_message" => 'illegal:' . _("Logger")),
	"sim"			  => array("validation"=>"OSS_ALPHA, OSS_NULLABLE", "e_message" => 'illegal:' . _("SIEM")));
	
	
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
	
	if ( ( $validation_errors == 1 ) ||  (is_array($validation_errors) && !empty($validation_errors))  )
	{
		$error = true;
				
		$message_error = array();
		
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
	$_SESSION['_server']['sname']           = $sname;
	$_SESSION['_server']['ip']              = $ip;
	$_SESSION['_server']['port']            = $port;
	$_SESSION['_server']['descr']           = $descr;
	$_SESSION['_server']['correlate']       = $correlate;
	$_SESSION['_server']['cross_correlate'] = $cross_correlate;
	$_SESSION['_server']['qualify']    		= $qualify;
	$_SESSION['_server']['store']    		= $store ;
	$_SESSION['_server']['resend_events']   = $resend_events;
	$_SESSION['_server']['resend_alarms']   = $resend_alarms;
	$_SESSION['_server']['sign']   			= $sign;
	$_SESSION['_server']['multi']  			= $multi;
	$_SESSION['_server']['sem']   		    = $sem;
	$_SESSION['_server']['sim']   		    = $sim ;
}

?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" CONTENT="no-cache"/>
	<link type="text/css" rel="stylesheet" href="../style/style.css"/>
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

<h1><?php echo gettext("Update Server"); ?></h1>


<?php


if ( POST('insert') && !empty($sname) )
{
	if ( $error == true)
	{
		$txt_error = "<div>"._("We Found the following errors").":</div><div style='padding:10px;'>".implode( "<br/>", $message_error)."</div>";			
		Util::print_error($txt_error);	
		Util::make_form("POST", "newserverform.php?".$get_param);
		die();
	}
		
    $db   = new ossim_db();
    $conn = $db->connect();
	
    if(!isset( $resend_alarms) ) $resend_alarms = 0;
    if(!isset( $resend_events) ) $resend_events = 0;
    
    Server::update($conn, $sname, $ip, $port, $descr, $correlate, $cross_correlate, $store, $qualify, $resend_alarms, $resend_events, $sign, $sem, $sim);
    
	$db->close($conn);
	
}

if ( isset($_SESSION['_server']) )
	unset($_SESSION['_server']);

?>
    <p> <?php echo gettext("Server succesfully updated"); ?> </p>
    <? if ($_SESSION["menu_sopc"]=="Servers" && POST('withoutmenu') != "1") { ?><script>document.location.href="server.php"</script><? } ?>
	
</body>
</html>

