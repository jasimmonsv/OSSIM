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
require_once ('classes/Host.inc');
require_once ('classes/Host_scan.inc');
require_once ('classes/Util.inc');

Session::logcheck("MenuPolicy", "PolicyHosts");

$error = false;

$hostname     = POST('hostname');
$old_hostname = POST('old_hostname');
$ip           = POST('ip');
$fqdns        = POST('fqdns');
$descr	      = POST('descr');
$asset        = POST('asset');
$nat          = POST('nat');
$sensors      = ( isset($_POST['sboxs'] ) && !empty ( $_POST['sboxs']) ) ? Util::clean_array(POST('sboxs')) : array();
$nagios       = POST('nagios');	
$rrd_profile  = POST('rrd_profile'); 
$threshold_a  = POST('threshold_a');
$threshold_c  = POST('threshold_c');
$os           = POST('os');
$mac          = POST('mac');
$mac_vendor   = POST('mac_vendor');
$latitude     = POST('latitude');
$longitude    = POST('longitude');


$num_sensors = count($sensors);

$validate = array (
	"hostname"     => array("validation"=>"OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_PUNC", "e_message" => 'illegal:' . _("Host name")),
	"old_hostname" => array("validation"=>"OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_PUNC", "e_message" => 'illegal:' . _("Old host name")),
	"ip"           => array("validation"=>"OSS_IP_ADDR", "e_message" => 'illegal:' . _("IP")),
	"fqdns"        => array("validation"=>"OSS_FQDNS, OSS_NULLABLE", "e_message" => 'illegal:' . _("FQDN/Aliases")),
	"descr"        => array("validation"=>"OSS_ALPHA, OSS_NULLABLE, OSS_SPACE, OSS_PUNC, OSS_AT, OSS_NL", "e_message" => 'illegal:' . _("Description")),
	"asset"        => array("validation"=>"OSS_DIGIT", "e_message" => 'illegal:' . _("Asset")),
	"nat"          => array("validation"=>"OSS_NULLABLE, OSS_IP_ADDR", "e_message" => 'illegal:' . _("Nat")),
	"sboxs"        => array("validation"=>"OSS_ALPHA, OSS_SCORE, OSS_PUNC, OSS_AT", "e_message" => 'illegal:' . _("Sensors")),
	"rrd_profile"  => array("validation"=>"OSS_ALPHA, OSS_NULLABLE, OSS_SPACE, OSS_PUNC", "e_message" => 'illegal:' . _("RRD Profile")),
	"threshold_a"  => array("validation"=>"OSS_DIGIT", "e_message" => 'illegal:' . _("Threshold A")),
	"threshold_c"  => array("validation"=>"OSS_DIGIT", "e_message" => 'illegal:' . _("Threshold C")),
	"nagios"       => array("validation"=>"OSS_NULLABLE, OSS_DIGIT", "e_message" => 'illegal:' . _("Nagios")),
	"os"           => array("validation"=>"OSS_NULLABLE, OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_PUNC, OSS_AT", "e_message" => 'illegal:' . _("Os")),
	"mac"          => array("validation"=>"OSS_NULLABLE, OSS_ALPHA, OSS_PUNC", "e_message" => 'illegal:' . _("Mac")),
	"mac_vendor"   => array("validation"=>"OSS_NULLABLE, OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_PUNC, OSS_AT, \"(\", \")\"", "e_message" => 'illegal:' . _("Mac Vendor")),
	"latitude"     => array("validation"=>"OSS_NULLABLE, OSS_DIGIT, OSS_SCORE, OSS_PUNC", "e_message" => 'illegal:' . _("Latitude")),
	"longitude"    => array("validation"=>"OSS_NULLABLE, OSS_DIGIT, OSS_SCORE, OSS_PUNC", "e_message" => 'illegal:' . _("Longitude")),
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
	
	if ( ( $validation_errors == 1 ) ||  (is_array($validation_errors) && !empty($validation_errors)) || $num_sensors == 0)
	{
		$error = true;
		
		$message_error = array();
			
		if( $num_sensors == 0)
			$message_error[] = _("You Need to select at least one Sensor");
		
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
	$_SESSION['_host']['hostname']     = $hostname;
	$_SESSION['_host']['old_hostname'] = ( empty( $old_hostname ) ? $hostname : $old_hostname );
	$_SESSION['_host']['ip']           = $ip;  	
	$_SESSION['_host']['fqdns']        = $fqdns; 
	$_SESSION['_host']['descr']        = $descr; 
	$_SESSION['_host']['asset']        = $asset;
	$_SESSION['_host']['nat']          = $nat;  	
	$_SESSION['_host']['sensors']      = $sensors;  
	$_SESSION['_host']['nagios']       = $nagios;	
	$_SESSION['_host']['rrd_profile']  = $rrd_profile;  
	$_SESSION['_host']['threshold_a']  = $threshold_a; 
	$_SESSION['_host']['threshold_c']  = $threshold_c; 
	$_SESSION['_host']['os']           = $os; 
	$_SESSION['_host']['mac']          = $mac; 
	$_SESSION['_host']['mac_vendor']   = $mac_vendor; 
	$_SESSION['_host']['latitude']     = $latitude; 
	$_SESSION['_host']['longitude']    = $longitude; 
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache">
	<link type="text/css" rel="stylesheet" href="../style/style.css"/>
</head>

<body>

<?php
if (GET('withoutmenu') != "1") 
	include ("../hmenu.php"); 
?>

<h1> <?php echo gettext("Modify Host"); ?> </h1>  

<?php
	
if ( POST('insert') && !empty($ip) )
{
    if ( $error == true)
	{
		$txt_error = "<div>"._("We Found the following errors").":</div><div style='padding:10px;'>".implode( "<br/>", $message_error)."</div>";				
		Util::print_error($txt_error);	
		Util::make_form("POST", "modifyhostform.php?ip=".$ip);
		die();
	}
		
	$db = new ossim_db();
    $conn = $db->connect();
    
	Host::update($conn, $ip, $hostname, $asset, $threshold_c, $threshold_a, $rrd_profile, $alert, $persistence, $nat, $sensors, $descr, $os, $mac, $mac_vendor, $latitude, $longitude, $fqdns);
    
	if ( $hostname != $old_hostname ) {
    	$query = "UPDATE risk_indicators SET type_name=? WHERE type='host' AND type_name=?";
		$params = array($hostname, $hostname_old);
        $conn->Execute($query, $params);
    }
    
	Host_scan::delete($conn, $ip, 3001);
    
	Host_scan::delete($conn, $ip, 2007);
    
	//if (!empty($nessus)) Host_scan::insert($conn, $ip, 3001);
   
    Host_scan::delete($conn, $ip, 3001);
   
	if (!empty($nagios))
	{
        if (!Host_scan::in_host_scan($conn, $ip, 2007)) 
			Host_scan::insert($conn, $ip, 2007, "", $hostname, $sensors, $sensors);
	} 
	else 
	{
        if (Host_scan::in_host_scan($conn, $ip, 2007)) 
			Host_scan::delete($conn, $ip, 2007);
    }
		
    $db->close($conn);
	
}

if ( isset($_SESSION['_host']) )
	unset($_SESSION['_host']);

?>
    <p><?php echo _("Host succesfully updated") ?></p>
    <script>document.location.href="host.php"</script>
	
	<?php 
	// update indicators on top frame
	$OssimWebIndicator->update_display();
	?>

	</body>
</html>

