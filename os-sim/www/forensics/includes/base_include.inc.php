<?php
/**
* Class and Function List:
* Function list:
* Classes list:
*/
/*******************************************************************************
** OSSIM Forensics Console
** Copyright (C) 2009 OSSIM/AlienVault
** Copyright (C) 2004 BASE Project Team
** Copyright (C) 2000 Carnegie Mellon University
**
** (see the file 'base_main.php' for license details)
**
** Built upon work by Roman Danyliw <rdd@cert.org>, <roman@danyliw.com>
** Built upon work by the BASE Project Team <kjohnson@secureideas.net>
**/
ini_set('max_execution_time', 1200);
defined('_BASE_INC') or die('Accessing this file directly is not allowed.');
include_once ("$BASE_path/includes/base_db.inc.php");
//
require_once ('ossim_conf.inc');
$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);
$opensource = (!preg_match("/pro|demo/i",$version)) ? true : false;
if (!isset($_SESSION["_user"])) {
    $ossim_link = $conf->get_conf("ossim_link", FALSE);
    $login_location = $ossim_link . '/session/login.php';
	header("Location: $login_location");
	exit;
}
// Solera API
$_SESSION["_solera"] = ($conf->get_conf("solera_enable", FALSE)) ? true : false;
//
// Get Host names to translate IP -> Host Name
require_once ("ossim_db.inc");
$dbo = new ossim_db();
// Multiple Database Server selector
$conn = $dbo->connect();
include("classes/Databases.inc");
$database_servers = Databases::get_list($conn);
$dbo->close($conn);
//
if (is_array($_SESSION["server"]) && $_SESSION["server"][0]!="")
	$conn = $dbo->custom_connect($_SESSION["server"][0],$_SESSION["server"][2],$_SESSION["server"][3]);
else
	$conn = $dbo->connect();
require_once ("$BASE_path/includes/SnortHost.inc");
$sensors = $hosts = $ossim_servers = array();
list($sensors, $hosts, $icons) = SnortHost::get_ips_and_hostname($conn);
//$ossim_servers = OServer::get_list($conn);
//$plugins = SnortHost::get_plugin_list($conn);
require_once ("classes/Net.inc");
//$networks = "";
$_nets = Net::get_all($conn);
$_nets_ips = $_host_ips = $_host = array();
foreach ($_nets as $_net) $_nets_ips[] = $_net->get_ips();
//$networks = implode(",",$_nets_ips);
//
// added default home host/lan to SESSION[ip_addr]
//
if ($_GET["addhomeips"]=="src" || $_GET["addhomeips"]=="dst") {
	// adding all lans
	$local_ips = array();
	$total_ips = 0;
	foreach ($_nets_ips as $current_net) 
		if (preg_match("/(.*)\.(.*)\.(.*)\.(.*)\/(.*)/",$current_net,$fields)) {
			$local_ips[] = array(" ","ip_".$_GET["addhomeips"],"=",$fields[1],$fields[2],$fields[3],$fields[4],$current_net," ","OR",$fields[5]);
			$total_ips++;
		}
	// adding rest of hosts
	$_hosts_ips = array_keys($hosts);
	foreach ($_hosts_ips as $current_ip)
		if (!Net::is_ip_in_cache_cidr($conn, $current_ip)) {
			$fields = explode(".",$current_ip);
			$local_ips[] = array(" ","ip_".$_GET["addhomeips"],"=",$fields[0],$fields[1],$fields[2],$fields[3],$current_ip," ","OR","");
			$total_ips++;
		}
	if (count($local_ips)>0) {
		$local_ips[count($local_ips)-1][9]=" "; // delete last OR
		$_SESSION['ip_addr'] = $local_ips;
		$_SESSION['ip_addr_cnt'] = $total_ips;
	}
	//print_r($_SESSION["ip_addr"]);
}
$dbo->close($conn);
//
include_once ("$BASE_path/includes/base_output_html.inc.php");
include_once ("$BASE_path/includes/base_state_common.inc.php");
include_once ("$BASE_path/includes/base_auth.inc.php");
include_once ("$BASE_path/includes/base_user.inc.php");
include_once ("$BASE_path/includes/base_state_query.inc.php");
include_once ("$BASE_path/includes/base_state_criteria.inc.php");
include_once ("$BASE_path/includes/base_output_query.inc.php");
include_once ("$BASE_path/includes/base_log_error.inc.php");
include_once ("$BASE_path/includes/base_log_timing.inc.php");
include_once ("$BASE_path/includes/base_action.inc.php");
include_once ("$BASE_path/base_common.php");
include_once ("$BASE_path/includes/base_cache.inc.php");
include_once ("$BASE_path/includes/base_net.inc.php");
include_once ("$BASE_path/includes/base_signature.inc.php");
?>
