<?php
/* This script is always called by restoredb.pl
 * Called when restoredb.pl is selective (filter_by parameter != NULL)
 * restoredb.pl has created snort_restore_[entity|user] database
 * This script imports this database and apply entity/user filters
 * Insert finally into snort current database the filtered events
 */
ini_set("include_path", ".:/usr/share/ossim/include:/usr/share/phpgacl");
$filter_by = $argv[1];
$debug = ($argv[2] != "") ? 0 : 1;
require_once ('classes/Session.inc');
require_once ('classes/Databases.inc');
require_once "ossim_db.inc";
$db = new ossim_db();
$conn = $db->connect();

if ($debug) echo "Retrieving Assets from entity/user: $filter_by...";
// Entity
if (preg_match("/^\d+$/",$filter_by)) {
	$allowedSensors = Session::entityPerm($conn,$filter_by,"sensors");
	$allowedNets = Session::entityPerm($conn,$filter_by,"assets");
// Username
} elseif (preg_match("/^[A-Za-z0-9\_\-\.]+$/",$filter_by)) {
	$allowedSensors = Session::allowedSensors($filter_by);
	$allowedNets = Session::allowedNets($filter_by);
}

if ($allowedNets == "" && $allowedSensors == "") {
	if ($debug) echo "no filters for $filter_by\n";
} else {
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
	if ($allowedNets != "") {
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
	if ($debug) echo "ok.\n";
	
	// 2) CLEAN TEMP DATABASE NOT ALLOWED EVENTS
	if ($allowedHosts != "") {
		if ($debug) echo "Filtering acid_event table...";
		$snort_temp_conn = $db->snort_custom_connect("snort_restore_".$filter_by);
		$sql = "DELETE FROM acid_event WHERE INET_NTOA(ip_src) not in ($allowedHosts) AND INET_NTOA(ip_dst) not in ($allowedHosts)";
		$snort_temp_conn->Execute($sql);
		if ($debug) echo "ok.\n";
	}
	
	// REGENERATING AC_* TABLES
	if ($debug) echo "Cleaning ac_* tables...";
	$snort_temp_conn->Execute("DELETE FROM ac_sensor_sid");
	$snort_temp_conn->Execute("DELETE FROM ac_sensor_signature");
	$snort_temp_conn->Execute("DELETE FROM ac_sensor_ipsrc");
	$snort_temp_conn->Execute("DELETE FROM ac_sensor_ipdst");
	$snort_temp_conn->Execute("DELETE FROM ac_alerts_sid");
	$snort_temp_conn->Execute("DELETE FROM ac_alerts_signature");
	$snort_temp_conn->Execute("DELETE FROM ac_alerts_ipsrc");
	$snort_temp_conn->Execute("DELETE FROM ac_alerts_ipdst");
	$snort_temp_conn->Execute("DELETE FROM ac_srcaddr_ipdst");
	$snort_temp_conn->Execute("DELETE FROM ac_srcaddr_ipsrc");
	$snort_temp_conn->Execute("DELETE FROM ac_srcaddr_sid");
	$snort_temp_conn->Execute("DELETE FROM ac_srcaddr_signature");
	$snort_temp_conn->Execute("DELETE FROM ac_dstaddr_ipdst");
	$snort_temp_conn->Execute("DELETE FROM ac_dstaddr_ipsrc");
	$snort_temp_conn->Execute("DELETE FROM ac_dstaddr_sid");
	$snort_temp_conn->Execute("DELETE FROM ac_dstaddr_signature");
	$snort_temp_conn->Execute("DELETE FROM ac_layer4_sport");
	$snort_temp_conn->Execute("DELETE FROM ac_layer4_sport_sid");
	$snort_temp_conn->Execute("DELETE FROM ac_layer4_sport_signature");
	$snort_temp_conn->Execute("DELETE FROM ac_layer4_sport_ipsrc");
	$snort_temp_conn->Execute("DELETE FROM ac_layer4_sport_ipdst");
	$snort_temp_conn->Execute("DELETE FROM ac_layer4_dport");
	$snort_temp_conn->Execute("DELETE FROM ac_layer4_dport_sid");
	$snort_temp_conn->Execute("DELETE FROM ac_layer4_dport_signature");
	$snort_temp_conn->Execute("DELETE FROM ac_layer4_dport_ipsrc");
	$snort_temp_conn->Execute("DELETE FROM ac_layer4_dport_ipdst");
	if ($debug) echo "ok.\n";
	if (!$rs = & $snort_temp_conn->Execute("SELECT * FROM acid_event FORCE INDEX(timestamp) ORDER BY timestamp")) {
	    print $snort_temp_conn->ErrorMsg();
	} else {
		if ($debug) echo "Generating ac_* tables from acid_event...";
		$j=0;
	    while (!$rs->EOF) {
			if ($i==100) { if ($debug) echo "."; $j=0; }
			$sid = $rs->fields["sid"];
			$cid = $rs->fields["cid"];
			$timestamp = $rs->fields["timestamp"];
			$day=preg_replace("/\s.*/","",$timestamp);
			$ip_src = $rs->fields["ip_src"];
			$ip_dst = $rs->fields["ip_dst"];
			$ip_proto = $rs->fields["ip_proto"];
			$layer4_sport = $rs->fields["layer4_sport"];
			$layer4_dport = $rs->fields["layer4_dport"];
			$plugin_id = $rs->fields["plugin_id"];
			$plugin_sid = $rs->fields["plugin_sid"];
			#
			$sqls = array();
			$i=0;
			# AC_SENSOR queries
			$sqls[$i++] = "INSERT INTO ac_sensor_sid (sid,day,cid,first_timestamp,last_timestamp) VALUES ($sid,'$day',1,'$timestamp','$timestamp') ON DUPLICATE KEY UPDATE cid=cid+1,last_timestamp='$timestamp'";
			$sqls[$i++] = "INSERT IGNORE INTO ac_sensor_signature (sid,day,plugin_id,plugin_sid) VALUES ($sid,'$day',$plugin_id,$plugin_sid)";
			$sqls[$i++] = "INSERT IGNORE INTO ac_sensor_ipsrc (sid,day,ip_src) VALUES ($sid,'$day',$ip_src)";
			$sqls[$i++] = "INSERT IGNORE INTO ac_sensor_ipdst (sid,day,ip_dst) VALUES ($sid,'$day',$ip_dst)";
			# AC_ALERTS queries
			$sqls[$i++] = "INSERT INTO ac_alerts_signature (day,sig_cnt,first_timestamp,last_timestamp,plugin_id,plugin_sid) VALUES ('$day',1,'$timestamp','$timestamp',$plugin_id,$plugin_sid) ON DUPLICATE KEY UPDATE sig_cnt=sig_cnt+1,last_timestamp='$timestamp'";
			$sqls[$i++] = "INSERT IGNORE INTO ac_alerts_sid (day,sid,plugin_id,plugin_sid) VALUES ('$day',$sid,$plugin_id,$plugin_sid)";
			$sqls[$i++] = "INSERT IGNORE INTO ac_alerts_ipsrc (day,ip_src,plugin_id,plugin_sid) VALUES ('$day',$ip_src,$plugin_id,$plugin_sid)";
			$sqls[$i++] = "INSERT IGNORE INTO ac_alerts_ipdst (day,ip_dst,plugin_id,plugin_sid) VALUES ('$day',$ip_dst,$plugin_id,$plugin_sid)";
			# AC_SRC_ADDRESS queries
			$sqls[$i++] = "INSERT INTO ac_srcaddr_ipsrc (ip_src,day,cid) VALUES ($ip_src,'$day',1) ON DUPLICATE KEY UPDATE cid=cid+1";
			$sqls[$i++] = "INSERT IGNORE INTO ac_srcaddr_sid (ip_src,day,sid) VALUES ($ip_src,'$day',$sid)";
			$sqls[$i++] = "INSERT IGNORE INTO ac_srcaddr_signature (ip_src,day,plugin_id,plugin_sid) VALUES ($ip_src,'$day',$plugin_id,$plugin_sid)";
			$sqls[$i++] = "INSERT IGNORE INTO ac_srcaddr_ipdst (ip_src,day,ip_dst) VALUES ($ip_src,'$day',$ip_dst)";
			# AC_DST_ADDRESS queries
			$sqls[$i++] = "INSERT INTO ac_dstaddr_ipdst (ip_dst,day,cid) VALUES ($ip_dst,'$day',1) ON DUPLICATE KEY UPDATE cid=cid+1";
			$sqls[$i++] = "INSERT IGNORE INTO ac_dstaddr_sid (ip_dst,day,sid) VALUES ($ip_dst,'$day',$sid)";
			$sqls[$i++] = "INSERT IGNORE INTO ac_dstaddr_signature (ip_dst,day,plugin_id,plugin_sid) VALUES ($ip_dst,'$day',$plugin_id,$plugin_sid)";
			$sqls[$i++] = "INSERT IGNORE INTO ac_dstaddr_ipsrc (ip_dst,day,ip_src) VALUES ($ip_dst,'$day',$ip_src)";
			# AC_LAYER4_SPORT queries
			$sqls[$i++] = "INSERT INTO ac_layer4_sport (layer4_sport,ip_proto,day,cid,first_timestamp,last_timestamp) VALUES ($layer4_sport,$ip_proto,'$day',1,'$timestamp','$timestamp') ON DUPLICATE KEY UPDATE cid=cid+1,last_timestamp='$timestamp'";
			$sqls[$i++] = "INSERT IGNORE INTO ac_layer4_sport_sid (layer4_sport,ip_proto,day,sid) VALUES ($layer4_sport,$ip_proto,'$day',$sid)";
			$sqls[$i++] = "INSERT IGNORE INTO ac_layer4_sport_signature (layer4_sport,ip_proto,day,plugin_id,plugin_sid) VALUES ($layer4_sport,$ip_proto,'$day',$plugin_id,$plugin_sid)";
			$sqls[$i++] = "INSERT IGNORE INTO ac_layer4_sport_ipsrc (layer4_sport,ip_proto,day,ip_src) VALUES ($layer4_sport,$ip_proto,'$day',$ip_src)";
			$sqls[$i++] = "INSERT IGNORE INTO ac_layer4_sport_ipdst (layer4_sport,ip_proto,day,ip_dst) VALUES ($layer4_sport,$ip_proto,'$day',$ip_dst)";
			# AC_LAYER4_SPORT queries
			$sqls[$i++] = "INSERT INTO ac_layer4_dport (layer4_dport,ip_proto,day,cid,first_timestamp,last_timestamp) VALUES ($layer4_dport,$ip_proto,'$day',1,'$timestamp','$timestamp') ON DUPLICATE KEY UPDATE cid=cid+1,last_timestamp='$timestamp'";
			$sqls[$i++] = "INSERT IGNORE INTO ac_layer4_dport_sid (layer4_dport,ip_proto,day,sid) VALUES ($layer4_dport,$ip_proto,'$day',$sid)";
			$sqls[$i++] = "INSERT IGNORE INTO ac_layer4_dport_signature (layer4_dport,ip_proto,day,plugin_id,plugin_sid) VALUES ($layer4_dport,$ip_proto,'$day',$plugin_id,$plugin_sid)";
			$sqls[$i++] = "INSERT IGNORE INTO ac_layer4_dport_ipsrc (layer4_dport,ip_proto,day,ip_src) VALUES ($layer4_dport,$ip_proto,'$day',$ip_src)";
			$sqls[$i++] = "INSERT IGNORE INTO ac_layer4_dport_ipdst (layer4_dport,ip_proto,day,ip_dst) VALUES ($layer4_dport,$ip_proto,'$day',$ip_dst)";
			foreach ($sqls as $sql) {
				$snort_temp_conn->Execute($sql);
			}
			$rs->MoveNext();
			$j++;
	    }
	}
	if ($debug) echo "ok.\n";
	$snort_temp_conn->disconnect();
}
// 3) COPY TEMP TO ORIGINAL SNORT
$conf = $GLOBALS["CONF"];
$snort_user = $conf->get_conf("snort_user");
$snort_port = $conf->get_conf("snort_port");
$snort_pass = $conf->get_conf("snort_pass");
$snort_host = $conf->get_conf("snort_host");
$snort_name = $conf->get_conf("snort_base");
$type = $conf->get_conf("snort_type");
$cmdline = "mysqldump -p$snort_pass -n -t -f --no-autocommit --insert-ignore snort_restore_$filter_by | mysql -u$snort_user -p$snort_pass -h$snort_host -P$snort_port $snort_name";
if ($debug) echo "Merge events into snort database...";
system($cmdline);
if ($debug) echo "ok\n";
// 4) CREATE A NEW Database Profile for SIEM
if ($debug) echo "Creating Database Profile...";
$list = Databases::get_list($conn,"WHERE name='snort_restore_$filter_by'");
if (count($list) < 1) {
	Databases::insert($conn, "snort_restore_".$filter_by, $snort_host, $snort_port, $snort_user, $snort_pass, "");
	if ($debug) echo "ok\n";
} else {
	if ($debug) echo "already exists\n";
}
if ($debug) echo "All Done.\n";
$conn->disconnect();
?>