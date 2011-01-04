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
require_once ('ossim_db.inc');
require_once ('classes/Port_group.inc');
require_once ('classes/Util.inc');

Session::logcheck("MenuPolicy", "PolicyPorts");

$db = new ossim_db();
$conn = $db->connect();

$error = false;

$pgname        = POST('pgname');
$actives_ports = POST('act_ports');
$descr         = POST('descr');
$num_act_ports = count($actives_ports);


$validate = array (
	"pgname"    => array("validation"=>"OSS_ALPHA, OSS_SCORE, OSS_SPACE, OSS_PUNC", "e_message" => 'illegal:' . _("Port Group Name")),
	"act_ports" => array("validation"=>"OSS_PORT", "e_message" => 'illegal:' . _("Pair Port - Protocol")),
	"descr"     => array("validation"=>"OSS_ALPHA, OSS_NULLABLE, OSS_SPACE, OSS_PUNC, OSS_AT, OSS_NL", "e_message" => 'illegal:' . _("Description")));
	
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
	
	if ( ( $validation_errors == 1 ) ||  (is_array($validation_errors) && !empty($validation_errors)) || $num_act_ports == 0 )
	{
		$error = true;
		
		$message_error = array();
	
		if ( $num_act_ports == 0 )
			$message_error [] = _("You must select a port");
		
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
			echo implode( "<br/>", $message_error);
		else
			echo 0;
		
		exit();
	}
	
}


if ( $error == true )
{
	$_SESSION['_portgroup']['pgname']        = $pgname;        
	$_SESSION['_portgroup']['actives_ports'] = $actives_ports; 
	$_SESSION['_portgroup']['descr']         = $descr; 
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title><?php echo gettext("OSSIM Framework");?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" CONTENT="no-cache">
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>

<?php
if (POST('withoutmenu') != "1") 
	include ("../hmenu.php"); 
else
	$get_param = "withoutmenu=1";	
?>

<h1> <?php echo gettext("New port"); ?> </h1>

<?php

if ( $error == true)
{
	$txt_error = "<div>"._("We Found the following errors").":</div><div style='padding:10px;'>".implode( "<br/>", $message_error)."</div>";			
	Util::print_error($txt_error);	
	Util::make_form("POST", "newportform.php?".$get_param);
	die();
}


if (POST('insert')) {
    
    Port_group::insert($conn, $pgname, $actives_ports, $descr);
    Util::clean_json_cache_files("ports");
	
	if ( isset($_SESSION['_portgroup']) )
		unset($_SESSION['_portgroup']);
}

$db->close($conn);

?>
    <p> <?php echo gettext("Port Group succesfully inserted"); ?> </p>
    <? if ( $_SESSION["menu_sopc"]=="Ports" && POST('withoutmenu') != "1" ) { ?><script>document.location.href="port.php"</script><? } ?>

</body>
</html>

