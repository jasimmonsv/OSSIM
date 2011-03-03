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
*/
include ("base_conf.php");
include ("vars_session.php");
include ("$BASE_path/includes/base_constants.inc.php");
include ("$BASE_path/includes/base_include.inc.php");
include_once ("$BASE_path/base_db_common.php");
include_once ("$BASE_path/base_common.php");
include_once ("$BASE_path/base_qry_common.php");

if (GET('sensor') != "") ossim_valid(GET('sensor'), OSS_DIGIT, 'illegal:' . _("sensor"));
$cc = GET('cc');
$location = GET('location');
$query = GET('query');
ossim_valid($cc, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("cc"));
ossim_valid($location, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("location"));
ossim_valid($query, OSS_ALPHA, OSS_NULLABLE, OSS_PUNC_EXT, 'illegal:' . _("query"));
if (ossim_error()) {
    die(ossim_error());
}
$hosts_ips = array_keys($hosts);

// Geoip
include ("geoip.inc");
$gi = geoip_open("/usr/share/geoip/GeoIP.dat", GEOIP_STANDARD);

// The below three lines were moved from line 87 because of the odd errors some users were having
/* Connect to the Alert database */
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);
$qs = new QueryState();

if ($query!="")
	$sql = base64_decode($query);
else
	$sql = "(SELECT DISTINCT ip_src,'S', FROM ac_alerts_ipsrc) UNION (SELECT DISTINCT ip_dst,'D' FROM ac_alerts_ipdst)";

$result = $qs->ExecuteOutputQueryNoCanned($sql, $db);
$country_src = array();
$country_dst = array();

if (is_array($_SESSION["server"]) && $_SESSION["server"][0]!="")
	$_conn = $dbo->custom_connect($_SESSION["server"][0],$_SESSION["server"][2],$_SESSION["server"][3]);
else
	$_conn = $dbo->connect();
	
while ($myrow = $result->baseFetchRow()) {
    $currentIP = long2ip($myrow[0]);
    $ip_type = $myrow[1];
    $country_name = "";
    if ($cc == "local") { // local ip
        if (Net::is_ip_in_cache_cidr($_conn, $currentIP) || in_array($currentIP, $hosts_ips)) {
            if ($ip_type=='S') $country_src[] = $currentIP;
        	else $country_dst[] = $currentIP;
        }
    } else { // geoip
        if ($currentIP!="") {
            $country = strtolower(geoip_country_code_by_addr($gi, $currentIP));
            $country_name = geoip_country_name_by_addr($gi, $currentIP);
        }
        if ($country == "" && !(Net::is_ip_in_cache_cidr($_conn, $currentIP) || in_array($currentIP, $hosts_ips))) $country = 'unknown';
        if ($country == $cc) {
        	if ($ip_type=='S') $country_src[] = $currentIP;
        	else $country_dst[] = $currentIP;
        }
    }
}
$result->baseFreeRows();
$dbo->close($_conn);
geoip_close($gi);
//
if ($location == "srcaddress") $country_dst=array();
if ($location == "dstaddress") $country_src=array();
//
$ips = array();
$i = 1;
$total = count($country_src)+count($country_dst);
foreach ($country_src as $ip) {
	$or = ($i < $total) ? "OR" : "";
	$fields = explode(".",$ip);
	$ips[] = array(" ","ip_src","=",$fields[0],$fields[1],$fields[2],$fields[3],$ip," ",$or,"");
	//$ips[] = array(" ","ip_src","=",$ip,"","","",""," ",$or,"");
	$i++;
}
foreach ($country_dst as $ip) {
	$or = ($i < $total) ? "OR" : "";
	$fields = explode(".",$ip);
	$ips[] = array(" ","ip_dst","=",$fields[0],$fields[1],$fields[2],$fields[3],$ip," ",$or,"");
	//$ips[] = array(" ","ip_src","=",$ip,"","","",""," ",$or,"");
	$i++;
}

$_SESSION['ip_addr'] = $ips;
$_SESSION['ip_addr_cnt'] = $total;
$_SESSION['layer4'] = "";
$_SESSION["ip_field"] = array (
	array ("","","=")
);
$_SESSION["ip_field_cnt"] = 1;

//print_r($_SESSION["ip_addr"]); exit();
if ($location == "alerts") Header('Location:base_stat_alerts.php?sort_order=occur_d');
if ($location == "address" || $location == "srcaddress") Header('Location:base_stat_uaddr.php?addr_type=1&sort_order=occur_d');
if ($location == "dstaddress") Header('Location:base_stat_uaddr.php?addr_type=2&sort_order=occur_d');
?>
