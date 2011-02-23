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
require_once ('classes/Util.inc');
require_once ('classes/Security.inc');
require_once ('ossim_db.inc');
require_once ('classes/Host.inc');
require_once ('classes/Host_group.inc');
require_once ('classes/Host_group_scan.inc');

Session::logcheck("MenuPolicy", "PolicyHosts");

$error = false;

$descr       = POST('descr');
$hgname      = POST('hgname');
$threshold_a = POST('threshold_a');
$threshold_c = POST('threshold_c');
$rrd_profile = POST('rrd_profile');
$hosts       = ( isset($_POST['ips'] ) && !empty ( $_POST['ips']) ) ? Util::clean_array(POST('ips')) : array();
$sensors     = ( isset($_POST['sboxs'] ) && !empty ( $_POST['sboxs']) ) ? Util::clean_array(POST('sboxs')) : array();
$nagios      = POST('nagios');

$num_sensors = count($sensors);
$num_hosts = count($hosts);

$validate = array (
	"hgname"      => array("validation"=>"OSS_NOECHARS, OSS_ALPHA, OSS_SPACE, OSS_PUNC", "e_message" => 'illegal:' . _("Host Group Name")),
	"descr"       => array("validation"=>"OSS_ALPHA, OSS_NULLABLE, OSS_SPACE, OSS_PUNC, OSS_AT, OSS_NL", "e_message" => 'illegal:' . _("Description")),
	"ips"         => array("validation"=>"OSS_IP_ADDR", "e_message" => 'illegal:' . _("Hosts")),
	"sboxs"       => array("validation"=>"OSS_ALPHA, OSS_SCORE, OSS_PUNC, OSS_AT", "e_message" => 'illegal:' . _("Sensors")),
	"rrd_profile" => array("validation"=>"OSS_ALPHA, OSS_NULLABLE, OSS_SPACE, OSS_PUNC", "e_message" => 'illegal:' . _("RRD Profile")),
	"threshold_a" => array("validation"=>"OSS_DIGIT", "e_message" => 'illegal:' . _("Threshold A")),
	"threshold_c" => array("validation"=>"OSS_DIGIT", "e_message" => 'illegal:' . _("Threshold C")),
	"nagios"      => array("validation"=>"OSS_NULLABLE, OSS_DIGIT", "e_message" => 'illegal:' . _("Nagios")));
	
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
	
	if ( ( $validation_errors == 1 ) ||  (is_array($validation_errors) && !empty($validation_errors)) || $num_sensors == 0 || $num_hosts == 0 )
	{
		$error = true;
				
		$message_error = array();
		
		if( $num_hosts == 0)
			$message_error [] = _("You Need to select at least one Host");
		
		if( $num_sensors == 0)
			$message_error [] = _("You Need to select at least one Sensor");
			
			
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
	$_SESSION['_hostgroup']['descr']       = $descr;
	$_SESSION['_hostgroup']['hgname']      = $hgname;
	$_SESSION['_hostgroup']['threshold_a'] = $threshold_a;
	$_SESSION['_hostgroup']['threshold_c'] = $threshold_c;
	$_SESSION['_hostgroup']['rrd_profile'] = $rrd_profile;
	$_SESSION['_hostgroup']['hosts']       = $hosts;
	$_SESSION['_hostgroup']['sensors']     = $sensors;
	$_SESSION['_hostgroup']['nagios']      = $nagios;
	
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>

<body>

<?php
if (POST('withoutmenu') != "1") 
	include ("../hmenu.php"); 
else
	$get_param = "withoutmenu=1";	
?>

<h1> <?php echo gettext("New Host group"); ?> </h1>

<?php

if (POST('insert')) {
    
	if ( $error == true)
	{
		$txt_error = "<div>"._("We Found the following errors").":</div><div style='padding:10px;'>".implode( "<br/>", $message_error)."</div>";			
		Util::print_error($txt_error);	
		Util::make_form("POST", "newhostgroupform.php?".$get_param);
		die();
	}
		
   
    $db = new ossim_db();
    $conn = $db->connect();
	
	/*
	NESSUS - DEPRECATED
        Host_group_scan::delete($conn, $hgname, 3001, 0);
        Host_group_scan::insert($conn, $hgname, 3001, 0);
	*/
	
	Host_group::insert($conn, $hgname, $threshold_c, $threshold_a, $rrd_profile, $sensors, $hosts, $descr);
    
	Host_group_scan::delete($conn, $hgname, 3001, 0);
		
	if ( $nagios )
	{
	    if (!Host_group_scan::in_host_group_scan($conn, $hgname, 2007)) 
			Host_group_scan::insert($conn, $hgname, 2007, 0, $hosts, $sensors);
    } 
	else 
        if (Host_group_scan::in_host_group_scan($conn, $hgname, 2007)) 
			Host_group_scan::delete($conn, $hgname, 2007);
    
    $db->close($conn);
    
	Util::clean_json_cache_files("(policy|vulnmeter|hostgroup)");
			
}

if ( isset($_SESSION['_hostgroup']) )
	unset($_SESSION['_hostgroup']);

?>
    <p> <?php echo gettext("Host group succesfully inserted"); ?> </p>
    <? if ( $_SESSION["menu_sopc"]=="Host groups" && POST('withoutmenu') != "1" ) { ?><script>document.location.href="hostgroup.php"</script><? } ?>

	</body>
</html>

