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
* - update_db()
* Classes list:
*/
// menu authentication
require_once ('classes/Session.inc');
require_once ('ossim_db.inc');
require_once ('classes/Host.inc');
require_once ('classes/Host_scan.inc');
require_once ('classes/Host_plugin_sid.inc');
require_once ('classes/Host_services.inc');
require_once ('classes/Host_group.inc');
require_once ('classes/Host_group_scan.inc');
require_once ('classes/Protocol.inc');
require_once ('classes/Security.inc');
require_once ('classes/Util.inc');
	
Session::logcheck("MenuPolicy", "ToolsScan");

function update_db($global_info, $scan)
{
    $db   = new ossim_db();
    $conn = $db->connect();
	
	$array_os = array ( "win"     => "1",  "linux"   => "2",  "cisco" => "3",  "freebsd" => "5",
						"netbsd"  => "6",  "openbsd" => "7",  "hp-ux" => "8",  "solaris" => "9",
						"macos"   => "10", "plan9"   => "11", "sco"   => "12", "aix"     => "13",
						"unix"    => "14");
	
	$ips     = $global_info["ips"];
	$sensors = $global_info["sboxs"];
	$nagios  = $global_info['nagios'];
	
    // load protocol ids
    $protocol_ids = array();
    if($protocol_list = Protocol::get_list($conn)) {
        foreach($protocol_list as $protocol_data) {
            $protocol_ids[$protocol_data->get_name()] = $protocol_data->get_id(); 
        }
    }    
    
    for ($i = 0; $i < $ips; $i++)
	{
        $ip = $global_info["ip_$i"];
		if ( !empty($ip) )
		{
            $hosts[] = $ip; //gethostbyaddr($ip);
			$os      = $scan[$ip]["os"];
			$os_id   = 0;
			foreach ($array_os as $k => $v)
			{
				if ( preg_match("/$k/i", $os) ) 
				{
					$os_id = $v;
					break;
				}
			}
			
            if (Host::in_host($conn, $ip))
			{
                echo "* " . gettext("Updating") . " $ip..<br/>";
                Host::update($conn, $ip, gethostbyaddr($ip) , $global_info["asset"], $global_info["threshold_c"], $global_info["threshold_a"], $global_info["rrd_profile"], 0, 0, $global_info["nat"], $sensors, $global_info["descr"], $scan["$ip"]["os"], $scan["$ip"]["mac"], $scan["$ip"]["mac_vendor"]);
               
                Host_scan::delete($conn, $ip, 3001);
                
				//if (isset($global_info["nessus"])) { Host_scan::insert($conn, $ip, 3001, 0); }
            }
			else
			{
                echo "<span style='color='blue'>\n";
                echo "* " . gettext("Inserting") . " $ip..<br/>\n";
                echo "</span>\n";
                
				Host::insert($conn, $ip, gethostbyaddr($ip) , $global_info["asset"], $global_info["threshold_c"], $global_info["threshold_a"], $global_info["rrd_profile"], 0, 0, $global_info["nat"], $sensors, $global_info["descr"], $scan[$ip]["os"], $scan[$ip]["mac"], $scan[$ip]["mac_vendor"]);
                              
				// if (isset($global_info["nessus"])) { Host_scan::insert($conn, $ip, 3001, 0); }
            }
            
			
			if ( $os_id != 0 )
			{
				Host_plugin_sid::delete($conn, $ip, 5001);
				Host_plugin_sid::insert($conn, $ip, 5001, $os_id);
            }
			
					
			if ( !empty($nagios) )
			{
                if ( !Host_scan::in_host_scan($conn, $ip, 2007) ) 
					Host_scan::insert($conn, $ip, 2007, "", $ip, $sensors, "");
            } 
			else 
                if (Host_scan::in_host_scan($conn, $ip, 2007)) 
					Host_scan::delete($conn, $ip, 2007);
            
			
			
            /* Services */
            
			Host_plugin_sid::delete($conn, $ip, 5002);
            
			foreach($scan[$ip]["services"] as $port_proto => $service)
			{
                $service["proto"] = $protocol_ids[strtolower(trim($service["proto"]))];
                Host_services::insert($conn, $ip, $service["port"], strftime("%Y-%m-%d %H:%M:%S") , $_SERVER["SERVER_ADDR"], $service["proto"], $service["service"], $service["service"], $service["version"], 1);
                Host_plugin_sid::insert($conn, $ip, 5002, $service["port"]);
            }
            
			flush();
        }
    }
    
	// Insert group name
    $groupname = $global_info["groupname"];
    
	if ( !empty($groupname) && !empty($hosts) )
	{
        $exists_hosts = count(Host_group::get_list($conn, " AND g.name='$groupname'"))>0;
		
		if( $exists_hosts ) 
            echo "<br/>"._("The group name already exists")."<br/>";
        else 
            Host_group::insert($conn, $groupname, $global_info["threshold_c"], $global_info["threshold_a"], $global_info["rrd_profile"], $sensors, $hosts, $global_info["descr"]);
        
		//if (isset($global_info["nessus"])) { Host_group_scan::insert($conn, $groupname, 3001, 0); }
        
		if ( !empty($nagios) ) 
            Host_group_scan::insert($conn, $groupname, 2007, 0);
        
    }
	
    $db->close($conn);
}


$error         = false;
$invalid_hosts = false;
$message_error = array();
$num_hosts     = 0;
$num_sensors   = 0;

$ips           = POST('ips');
$groupname     = POST('groupname');
$descr	       = POST('descr');
$asset         = POST('asset');
$nat           = POST('nat');
$sensors       = ( isset($_POST['sboxs'] ) && !empty ( $_POST['sboxs']) ) ? Util::clean_array(POST('sboxs')) : array();
$nagios        = POST('nagios');	
$rrd_profile   = POST('rrd_profile'); 
$threshold_a   = POST('threshold_a');
$threshold_c   = POST('threshold_c');


if ( is_numeric($ips) )
{
	for ($i=0; $i<$ips; $i++)
	{
		$item_ip  = "ip_$i";
		$$item_ip = POST($item_ip);
		$num = $i+1;
		$hostname = "Host $num";
		ossim_valid($$item_ip, OSS_IP_ADDR, OSS_NULLABLE, 'illegal:' . _($hostname));
		
		if ( ossim_error() ) {
			$message_error [] = ossim_get_error();
			$error            = true;
			$invalid_hosts    = true;
			ossim_clean_error();
		}
		else
		{
			if ( !empty ($$item_ip) )
				$num_hosts++;
		}
	}	
}


$num_sensors = count($sensors);

$validate = array (
	"ips"         => array("validation"=>"OSS_DIGIT", "e_message"                           => 'illegal:' . _("Hosts")),
	"groupname"   => array("validation"=>"OSS_SCORE, OSS_INPUT, OSS_NULLABLE", "e_message"  => 'illegal:' . _("Group name")),
	"descr"       => array("validation"=>"OSS_TEXT, OSS_NULLABLE, OSS_AT", "e_message" 		=> 'illegal:' . _("Description")),
	"asset"       => array("validation"=>"OSS_DIGIT", "e_message" 							=> 'illegal:' . _("Asset")),
	"nat"         => array("validation"=>"OSS_NULLABLE, OSS_IP_ADDR", "e_message" 			=> 'illegal:' . _("Nat")),
	"sboxs"       => array("validation"=>"OSS_SCORE, OSS_INPUT, OSS_AT", "e_message" 		=> 'illegal:' . _("Sensors")),
	"rrd_profile" => array("validation"=>"OSS_INPUT, OSS_NULLABLE", "e_message" 			=> 'illegal:' . _("RRD Profile")),
	"threshold_a" => array("validation"=>"OSS_DIGIT", "e_message" 							=> 'illegal:' . _("Threshold A")),
	"threshold_c" => array("validation"=>"OSS_DIGIT", "e_message" 							=> 'illegal:' . _("Threshold C")),
	"nagios"      => array("validation"=>"OSS_NULLABLE, OSS_DIGIT", "e_message" 			=> 'illegal:' . _("Nagios")));

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
		
		if( $num_hosts == 0)
			$message_error [] = _("You Need at least one Host");
		
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
			echo implode( "<br/>", $message_error);
		else
			echo 0;
		
		exit();
	}
}


if ( $error == true )
{
	$_SESSION['_host']['groupname']   = $groupname; 
	$_SESSION['_host']['descr']       = $descr; 
	$_SESSION['_host']['asset']       = $asset;
	$_SESSION['_host']['nat']         = $nat;  	
	$_SESSION['_host']['sensors']     = $sensors;  
	$_SESSION['_host']['nagios']      = $nagios ;	
	$_SESSION['_host']['rrd_profile'] = $rrd_profile;  
	$_SESSION['_host']['threshold_a'] = $threshold_a; 
	$_SESSION['_host']['threshold_c'] = $threshold_c; 
	
	if ( $invalid_hosts == false && is_numeric($ips) )
	{
		for ($i = 0; $i < $ips; $i++) 
		{
			$item_ip = "ip_$i";
			$_SESSION['_host'][$item_ip]  = $$item_ip;
		}
	}
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
include ("../hmenu.php");
/*
* scan_db.php
*
* Update ossim database with scan structure
*/

if ( $error == true)
{
	$txt_error = "<div>"._("We Found the following errors").":</div><div style='padding:10px;'>".implode( "<br/>", $message_error)."</div>";				
	Util::print_error($txt_error);	
	
	if ( is_numeric($ips) && isset($_SESSION["_scan"]) && $num_hosts>0 && !$invalid_hosts )
		Util::make_form("POST", "../host/newhostform.php?scan=1&ips=$ips");
	else
	{
		if ( !$invalid_hosts )
			unset($_SESSION["_scan"]);
		Util::make_form("POST", "../netscan/index.php");
	}
	die();
}


/*
echo "<pre>";
	print_r($_SESSION["_scan"]);
echo "</pre>";
*/

if ( isset($_SESSION["_scan"]) )
{
    $scan = $_SESSION["_scan"];
    update_db($_POST, $scan);
    echo "<br/><a href=\"../netscan/index.php\">"._("Return to Scan Results page")."</a><br/>";
	echo "<br/><a href=\"../host/host.php?hmenu=Assets&smenu=Hosts\">"._("Return to host's policy")."</a>";
}
else
{
	echo ossim_error(_("Error to update database values"));
	Util::make_form("POST", "../netscan/index.php");
}




?>

</body>
</html>

