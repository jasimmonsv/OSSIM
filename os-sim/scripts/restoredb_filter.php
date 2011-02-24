<?php
/* This script is always called by restoredb.pl
 * Called when restoredb.pl is selective (filter_by parameter != NULL)
 * restoredb.pl has created snort_restore_[entity|user] database
 * This script imports this database and apply entity/user filters
 * Insert finally into snort current database the filtered events
 */
ini_set("include_path", ".:/usr/share/ossim/include:/usr/share/phpgacl");
$filter_by = $argv[1];
require_once ('classes/Session.inc');
require_once "ossim_db.inc";
$db = new ossim_db();
$conn = $db->connect();
// Entity
if (preg_match("/^\d+$/",$filter_by)) {
	$allowedSensors = Session::entityPerm($conn,$filter_by,"sensors");
	$allowedNets = Session::entityPerm($conn,$filter_by,"assets");
// Username
} elseif (preg_match("/^[A-Za-z0-9\_\-\.]+$/",$filter_by)) {
	$allowedSensors = Session::allowedSensors($filter_by);
	$allowedNets = Session::allowedNets($filter_by);
}

// 1) GET ALLOWED HOSTS
$sensor_where = "";
if ($allowedSensors != "") {
	$user_sensors = explode(",",$allowedSensors);
	$sensor_str = "";
	foreach ($user_sensors as $user_sensor) if ($user_sensor != "")
		$sensor_str .= (($sensor_str != "") ? "," : "")."'".$user_sensor."'";
	//if ($sensor_str == "") $sensor_str = "AND 0";
	$sensor_where = "h.ip in (select hs.host_ip FROM host_sensor_reference hs,sensor s WHERE hs.sensor_name=s.name AND s.ip in(" . $sensor_str . "))";
}
$network_where = "";
if (($allowedNets = $allowedNets) != "") {
	$query = OssimQuery("SELECT ip FROM host");
	$hosts = "";
	if (!$rs = & $conn->Execute($query)) {
	    print $conn->ErrorMsg();
	} else {
	    while (!$rs->EOF) {
	        if (Net::is_ip_in_cache_cidr($conn, $rs->fields["ip"], $allowedNets)) {
	        	$hosts .= ",'".$rs->fields["ip"]."'";
	        }
	        $rs->MoveNext();
	    }
	    $hosts = preg_replace("/^\,/","",$hosts);
	    if ($hosts != "") {
	     	$network_where = "h.ip in ($hosts)";
	    }
	}
}

$perms_where = $sensor_where;
if ($network_where != "") {
	$perms_where .= ($sensor_where != "") ? " AND ".$network_where : $network_where;
}
$where = "";
if ($perms_where != "") {
	if (preg_match ("/where/i",$where)) {
		$where = preg_replace("/where/i","where ".$perms_where." AND ",$where);
	}
	else {
		$where = "where ".$perms_where;
	}
}
$query = "SELECT * FROM host h $where";
$allowedHosts = "";
if (!$rs = & $conn->Execute($query)) {
    print $conn->ErrorMsg();
} else {
    $foundrows = 0;
    if ($iffoundrows) {
        if (!$rf = & $conn->Execute("SELECT FOUND_ROWS() as total")) print $conn->ErrorMsg();
        else $foundrows = $rf->fields["total"];
    }
    while (!$rs->EOF) {
       $allowedHosts .= ($allowedHosts != "") ? ",'".$rs->fields['ip']."'" : "'".$rs->fields['ip']."'";
       $rs->MoveNext();
    }
}

// 2) CLEAN TEMP DATABASE NOT ALLOWED EVENTS
if ($allowedHosts != "") {
	$snort_temp_conn = $db->snort_custom_connect("snort_restore_".$filter_by);
	$sql = "DELETE FROM acid_event WHERE INET_NTOA(ip_src) not in ($allowedHosts) AND INET_NTOA(ip_dst) not in ($allowedHosts)";
	$snort_temp_conn->Execute($sql);
	$sql = "DELETE FROM ac_alerts_ipdst WHERE INET_NTOA(ip_dst) not in ($allowedHosts)";
	$snort_temp_conn->Execute($sql);
	$sql = "DELETE FROM ac_alerts_ipsrc WHERE INET_NTOA(ip_src) not in ($allowedHosts)";
	$snort_temp_conn->Execute($sql);
	echo "Filtering $filter_by\n";
}

// 3) COPY TEMP TO ORIGINAL SNORT
$conf = $GLOBALS["CONF"];
$snort_user = $conf->get_conf("snort_user");
$snort_port = $conf->get_conf("snort_port");
$snort_pass = $conf->get_conf("snort_pass");
$snort_host = $conf->get_conf("snort_host");
$snort_name = "snort_restore_".$filter_by;
$type = $conf->get_conf("snort_type");
$cmdline = "mysql -u$snort_user -p$snort_pass -h$snort_host -P$snort_port $snort_name";
system("");
?>