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
* - get_SIM()
*/
require_once ("ossim_db.inc");
require_once ("classes/Session.inc");
require_once ("classes/Event_viewer.inc");
require_once ("classes/CIDR.inc");
class Status {
	var $snort = false;
	var $conn = false;
	function Status () {
		$db = new ossim_db();
        $this->conn = $db->connect();
        $this->snort = $db->snort_connect();
	}
	function get_sim_sensor_filter ($db) {
		// User sensor filtering
		$sensor_where = "";
		if (Session::allowedSensors() != "") {
			$user_sensors = explode(",",Session::allowedSensors());
			$snortsensors = Event_viewer::GetSensorSids($db);
			$sensor_str = "";
			foreach ($user_sensors as $user_sensor)
				if (count($snortsensors[$user_sensor]) > 0) $sensor_str .= ($sensor_str != "") ? ",".implode(",",$snortsensors[$user_sensor]) : implode(",",$snortsensors[$user_sensor]);
			if ($sensor_str == "") $sensor_str = "0";
			$sensor_where = " AND acid_event.sid in (" . $sensor_str . ")";
		}
		return $sensor_where;
	}
	function get_sim_ip_filter ($ip_src,$ip_dst) {
		$ip_where = "";
		$ip_where_src = "";
		$ip_where_dst = "";
		$network = 0;
		if (preg_match("/\/\d+/",$ip_src) && preg_match("/\/\d+/",$ip_dst)) {
			$network = 1;
			$exp = CIDR::expand_CIDR($ip_src,"SHORT","IP");
			$src_s_range = $exp[0];
			$src_e_range = end($exp);
			$exp = CIDR::expand_CIDR($ip_dst,"SHORT","IP");
			$dst_s_range = $exp[0];
			$dst_e_range = end($exp);
			$ip_where = " AND ((acid_event.ip_src>=INET_ATON('$src_s_range') AND acid_event.ip_src<=INET_ATON('$src_e_range')) OR (acid_event.ip_dst>=INET_ATON('$dst_s_range') AND acid_event.ip_dst<=INET_ATON('$dst_e_range')))";
			$ip_where_dst = " AND (acid_event.ip_dst>=INET_ATON('$dst_s_range') AND acid_event.ip_dst<=INET_ATON('$dst_e_range'))";
			$ip_where_src = " AND (acid_event.ip_src>=INET_ATON('$src_s_range') AND acid_event.ip_src<=INET_ATON('$src_e_range'))";
		}
		// HOST Filtering
		if ($ip_src!="" && $ip_dst!="" && !$network) {
			$ip_where = " AND (acid_event.ip_src=INET_ATON('$ip_src') OR acid_event.ip_dst=INET_ATON('$ip_dst'))";
			$ip_where_dst = " AND acid_event.ip_dst=INET_ATON('$ip_dst')";
			$ip_where_src = " AND acid_event.ip_src=INET_ATON('$ip_src')";
		}
		return array($ip_where,$ip_where_src,$ip_where_dst);
	}
    function get_SIM ($ip_src="",$ip_dst="",$from_date="",$to_date="",$limit=5) {
        require_once ('ossim_db.inc');
		$dbsock = new ossim_db();
		$db = $dbsock->snort_connect();
		
		if ($from_date == "") {
			$from_date = strftime("%Y-%m-%d", time() - (24 * 60 * 60 * 7));
			$today = date("Y-m-d");
		} else $today = $from_date;
		if ($to_date == "") $to_date = strftime("%Y-%m-%d", time());
		
		// User sensor filtering
		$sensor_where = Status::get_sim_sensor_filter($db);
		
		// IP Filtering
		list($ip_where,$ip_where_src,$ip_where_dst) = Status::get_sim_ip_filter($ip_src,$ip_dst);
		
		$sql = "SELECT SQL_CALC_FOUND_ROWS acid_event.sid, plugin_sid.name as sig_name, acid_event.cid, acid_event.plugin_sid, timestamp, acid_event.ip_src, acid_event.ip_dst, acid_event.ip_proto, acid_event.ossim_type, acid_event.ossim_priority, acid_event.ossim_reliability, acid_event.ossim_asset_src, acid_event.ossim_asset_dst, acid_event.ossim_risk_c, acid_event.ossim_risk_a, acid_event.layer4_sport, acid_event.layer4_dport FROM acid_event FORCE INDEX (timestamp) LEFT JOIN ossim.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid WHERE 1 AND (timestamp >='$today 00:00:00' AND timestamp <= '$to_date 23:59:59')$sensor_where $ip_where ORDER BY timestamp DESC";
		//echo $sql;
        $foundrows = 0;
		$highrisk = "-";
		$list = array();
		$risks = array();
		$date = "-";
		if (!$rs = $db->Execute($sql)) {
            print $db->ErrorMsg();
			var_dump($rs);
        } else {
            if (!$rf = $db->Execute("SELECT FOUND_ROWS() as total")) print $db->ErrorMsg();
            else $foundrows = $rf->fields["total"];
			$highrisk = 0;
			$i = 0;
            while (!$rs->EOF && $i < 999) {
				$list[] = array("sig_name"=>$rs->fields['sig_name'],"timestamp"=>$rs->fields['timestamp'],"sip"=>$rs->fields['ip_src'],"dip"=>$rs->fields['ip_dst'],"oasset_s"=>$rs->fields['ossim_asset_src'],"oasset_d"=>$rs->fields['ossim_asset_d'],"oasset_s"=>$rs->fields["ossim_asset_src"],"oasset_d"=>$rs->fields["ossim_asset_dst"],"prio"=>$rs->fields["ossim_priority"],"rel"=>$rs->fields["ossim_reliability"],"risk_c"=>$rs->fields["ossim_risk_c"],"risk_a"=>$rs->fields["ossim_risk_a"],"proto"=>$rs->fields['ip_proto']);
				$risks[$rs->fields['ossim_risk_c']]++;
				if ($rs->fields['ossim_risk_c'] > $highrisk) $highrisk = $rs->fields['ossim_risk_c'];
				if (!$i) $date = $rs->fields['timestamp'];
				$rs->MoveNext();
				$i++;
            }
        }
		if ($risks[$highrisk] == "") { $risknum = 0; $highrisk = "-"; }
		else $risknum = $risks[$highrisk];
		
		list($ports,$ip_sources,$ip_dest) = Status::get_SIM_Clouds($ip_src,$ip_dst);
		$gplot = Status::get_SIM_Plot($ip_src,$ip_dst,$from_date,$to_date);
		list($list2,$plots,$numevents) = Status::get_SIM_Unique($ip_src,$ip_dst,$from_date,$to_date,$limit);
		
        return array($list,$foundrows,$highrisk,$risknum,$date,$list2,$i,$plots,$ports,$ip_sources,$ip_dest,$gplot,$numevents);
    }
	function get_SIM_light ($ip_src="",$ip_dst="") {
        require_once ('ossim_db.inc');
		$dbsock = new ossim_db();
		$db = $dbsock->snort_connect();
		
		$from_date = strftime("%Y-%m-%d", time() - (24 * 60 * 60 * 7));
		
		// User sensor filtering
		$sensor_where = Status::get_sim_sensor_filter($db);
		
		// IP Filtering
		list($ip_where,$ip_where_src,$ip_where_dst) = Status::get_sim_ip_filter($ip_src,$ip_dst);
		
		$sql = "SELECT SQL_CALC_FOUND_ROWS acid_event.sid, plugin_sid.name as sig_name, acid_event.cid, acid_event.plugin_sid, timestamp, acid_event.ip_src, acid_event.ip_dst, acid_event.ip_proto, acid_event.ossim_type, acid_event.ossim_priority, acid_event.ossim_reliability, acid_event.ossim_asset_src, acid_event.ossim_asset_dst, acid_event.ossim_risk_c, acid_event.ossim_risk_a, acid_event.layer4_sport, acid_event.layer4_dport FROM acid_event FORCE INDEX (timestamp) LEFT JOIN ossim.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid WHERE 1 AND (timestamp >='".date("Y-m-d")." 00:00:00')$sensor_where $ip_where ORDER BY timestamp DESC";
		
        $foundrows = 0;
		$highrisk = "-";
		$list = array();
		$risks = array();
		$date = "-";
		if (!$rs = $db->Execute($sql)) {
			print $db->ErrorMsg();
			var_dump($rs);
        } else {
            if (!$rf = $db->Execute("SELECT FOUND_ROWS() as total")) print $db->ErrorMsg();
            else $foundrows = $rf->fields["total"];
			$highrisk = 0;
			$i = 0;
            while (!$rs->EOF && $i < 999) {
				$list[] = array("sig_name"=>$rs->fields['sig_name'],"timestamp"=>$rs->fields['timestamp'],"sip"=>$rs->fields['ip_src'],"dip"=>$rs->fields['ip_dst'],"oasset_s"=>$rs->fields['ossim_asset_src'],"oasset_d"=>$rs->fields['ossim_asset_d'],"oasset_s"=>$rs->fields["ossim_asset_src"],"oasset_d"=>$rs->fields["ossim_asset_dst"],"prio"=>$rs->fields["ossim_priority"],"rel"=>$rs->fields["ossim_reliability"],"risk_c"=>$rs->fields["ossim_risk_c"],"risk_a"=>$rs->fields["ossim_risk_a"],"proto"=>$rs->fields['ip_proto']);
				$risks[$rs->fields['ossim_risk_c']]++;
				if ($rs->fields['ossim_risk_c'] > $highrisk) $highrisk = $rs->fields['ossim_risk_c'];
				if (!$i) $date = $rs->fields['timestamp'];
				$rs->MoveNext();
				$i++;
            }
        }
		return array($list,$foundrows,$highrisk,$risknum,$date);
	}
	function get_SIM_Resume ($ip_src="",$ip_dst="",$from_date="",$to_date="") {
        require_once ('ossim_db.inc');
		$dbsock = new ossim_db();
		$db = $dbsock->snort_connect();
		$foundrows = 0;
		$highrisk = "-";
		$date = "-";
		$risknum = 0;
		
		// User sensor filtering
		$sensor_where = Status::get_sim_sensor_filter($db);
		
		// IP Filtering
		list($ip_where,$ip_where_src,$ip_where_dst) = Status::get_sim_ip_filter($ip_src,$ip_dst);
		
		$sql = "SELECT count(*) as total,max(timestamp) as maxtime,max(ossim_risk_c) as maxrisk FROM acid_event FORCE INDEX (timestamp) WHERE (timestamp BETWEEN '$from_date 00:00:00' AND '$to_date  23:59:59') $sensor_where $ip_where";
		//echo $sql;
		if (!$rs = $db->Execute($sql)) {
			print $db->ErrorMsg();
			var_dump($rs);
        } else {
            if (!$rs->EOF) {
				$foundrows = $rs->fields['total'];
				$date = $rs->fields['maxtime'];
				$highrisk = $rs->fields['maxrisk'];
            }
        }
        if ($highrisk != "-" && $highrisk != "") {
			$sql = "SELECT count(*) as total FROM acid_event FORCE INDEX (timestamp) WHERE ossim_risk_c=$highrisk AND (timestamp BETWEEN '$from_date 00:00:00' AND '$to_date  23:59:59') $sensor_where $ip_where";
			if (!$rs = $db->Execute($sql)) {
				print $db->ErrorMsg();
				var_dump($rs);
	        } else {
	            if (!$rs->EOF) {
					$risknum = $rs->fields['total'];
	            }
	        }
        }
		return array($foundrows,$highrisk,$risknum,$date);
	}
	function get_SIM_Clouds ($ip_src="",$ip_dst="") {
		require_once ('ossim_db.inc');
		$dbsock = new ossim_db();
		$db = $dbsock->snort_connect();
		
		if ($from_date == "") {
			$from_date = strftime("%Y-%m-%d", time() - (24 * 60 * 60 * 7));
			$today = date("Y-m-d");
		} else $today = $from_date;
		if ($to_date == "") $to_date = strftime("%Y-%m-%d", time());
		
		// User sensor filtering
		//$sensor_where = Status::get_sim_sensor_filter($db);
		
		// IP Filtering
		list($ip_where,$ip_where_src,$ip_where_dst) = Status::get_sim_ip_filter($ip_src,$ip_dst);
	
		// Ports
		//$sql = "SELECT DISTINCT layer4_dport, MIN(ip_proto), COUNT(acid_event.cid) as num_events, COUNT( DISTINCT acid_event.sid) as num_sensors, COUNT( DISTINCT plugin_id, plugin_sid ) as num_sig, COUNT( DISTINCT ip_src ) as num_sip, COUNT( DISTINCT ip_dst ) as num_dip, MIN(timestamp) as first_timestamp, MAX(timestamp) as last_timestamp FROM acid_event WHERE ip_proto IN (6, 17) AND 1$sensor_where AND (timestamp >='$from_date' AND timestamp <='$to_date')$ip_where GROUP BY layer4_dport ORDER BY num_events DESC LIMIT 15";
		$sql = "SELECT DISTINCT layer4_dport, COUNT(acid_event.cid) as num_events FROM acid_event WHERE ip_proto IN (6, 17) AND 1$sensor_where AND (timestamp BETWEEN '$from_date 00:00:00' AND '$to_date  23:59:59') $ip_where GROUP BY layer4_dport ORDER BY num_events DESC LIMIT 15";
		$ports = array();
		if (!$rs = $db->Execute($sql)) {
            print $db->ErrorMsg();
			var_dump($rs);
        } else {
            while (!$rs->EOF) {
				$ports[$rs->fields['layer4_dport']] = $rs->fields['num_events'];
				$rs->MoveNext();
			}
		}
		
		// IP sources
		//$sql = "SELECT DISTINCT ip_src, COUNT(acid_event.cid) as num_events, COUNT( DISTINCT acid_event.sid) as num_sensors, COUNT( DISTINCT plugin_id, plugin_sid ) as num_sig, COUNT( DISTINCT ip_dst ) as num_dip FROM acid_event WHERE 1$sensor_where AND (timestamp >='$from_date' AND timestamp <='$to_date') AND $ip_where_dst GROUP BY ip_src ORDER BY num_events DESC LIMIT 10";
		$sql = "SELECT DISTINCT ip_src, COUNT(acid_event.cid) as num_events FROM acid_event WHERE 1 $sensor_where AND (timestamp BETWEEN '$from_date 00:00:00' AND '$to_date  23:59:59') $ip_where_dst GROUP BY ip_src ORDER BY num_events DESC LIMIT 10";
		$ip_sources = array();
		if (!$rs = $db->Execute($sql)) {
            print $db->ErrorMsg();
			var_dump($rs);
        } else {
            while (!$rs->EOF) {
				$ip_sources[long2ip($rs->fields['ip_src'])] = $rs->fields['num_events'];
				$rs->MoveNext();
			}
		}
		
		// IP destinations
		//$sql = "SELECT DISTINCT ip_dst, COUNT(acid_event.cid) as num_events, COUNT( DISTINCT acid_event.sid) as num_sensors, COUNT( DISTINCT plugin_id, plugin_sid ) as num_sig, COUNT( DISTINCT ip_src ) as num_sip FROM acid_event WHERE 1$sensor_where AND (timestamp >='$from_date' AND timestamp <='$to_date') AND $ip_where_src GROUP BY ip_dst ORDER BY num_events DESC LIMIT 10";
		$sql = "SELECT DISTINCT ip_dst, COUNT(acid_event.cid) as num_events FROM acid_event WHERE 1 $sensor_where AND (timestamp BETWEEN '$from_date 00:00:00' AND '$to_date  23:59:59') $ip_where_src GROUP BY ip_dst ORDER BY num_events DESC LIMIT 10";
		$ip_dest = array();
		if (!$rs = $db->Execute($sql)) {
            print $db->ErrorMsg();
			var_dump($rs);
        } else {
            while (!$rs->EOF) {
				$ip_dest[long2ip($rs->fields['ip_dst'])] = $rs->fields['num_events'];
				$rs->MoveNext();
			}
		}
		
		return array($ports,$ip_sources,$ip_dest);
	}
	function get_SIM_Unique ($ip_src="",$ip_dst="",$from_date="",$to_date="",$limit) {
		require_once ('ossim_db.inc');
		$dbsock = new ossim_db();
		$db = $dbsock->snort_connect();
		$numevents = 0;
		
		// User sensor filtering
		$sensor_where = Status::get_sim_sensor_filter($db);
		
		// IP Filtering
		list($ip_where,$ip_where_src,$ip_where_dst) = Status::get_sim_ip_filter($ip_src,$ip_dst);
		
		// Unique Events
		$sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT acid_event.sid,acid_event.cid,acid_event.plugin_id,acid_event.plugin_sid, count(plugin_sid) as sig_cnt, min(timestamp), max(timestamp), plugin_sid.name as sig_name, count(DISTINCT(acid_event.sid)), count(DISTINCT(ip_src)), count(DISTINCT(ip_dst)), ip_src, ip_dst FROM acid_event LEFT JOIN ossim.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid WHERE 1 $sensor_where$ip_where AND (timestamp BETWEEN '$from_date 00:00:00' AND '$to_date  23:59:59') GROUP BY sig_name ORDER BY sig_cnt DESC LIMIT $limit";
		//echo $sql;
		$events = array();
		if (!$rs = $db->Execute($sql)) {
            print $db->ErrorMsg();
			var_dump($rs);
        } else {
            if (!$rf = $db->Execute("SELECT FOUND_ROWS() as total")) print $db->ErrorMsg();
            else $numevents = $rf->fields["total"];
        	while (!$rs->EOF) {
				$events[] = array("plugin_id"=>$rs->fields['plugin_id'],"plugin_sid"=>$rs->fields['plugin_sid'],"sig_cnt"=>$rs->fields['sig_cnt'],"min(timestamp)"=>$rs->fields['min(timestamp)'],"max(timestamp)"=>$rs->fields['max(timestamp)'],"sig_name"=>$rs->fields['sig_name'],"ip_src"=>$rs->fields['count(DISTINCT(ip_src))'],"ip_dst"=>$rs->fields['count(DISTINCT(ip_dst))'],"num_sensors"=>$rs->fields['count(DISTINCT(acid_event.sid))'],"ip_s"=>$rs->fields['ip_src'],"ip_d"=>$rs->fields['ip_dst'],"sid"=>$rs->fields['sid'],"cid"=>$rs->fields['cid']);
				$rs->MoveNext();
            }
        }
        
        list($x, $y, $xticks, $xlabels) = Status::range_graphic("week");
		$sqlgraph = "SELECT count(plugin_id) as sig_cnt, day(timestamp) as intervalo, monthname(timestamp) as suf FROM acid_event WHERE (timestamp BETWEEN '$from_date 00:00:00' AND '$to_date  23:59:59') $sensor_where $ip_where AND plugin_id=PLUGINID AND plugin_sid=PLUGINSID GROUP BY intervalo,suf ORDER BY suf,intervalo";
		$i = 0;
		$plots = array();
		foreach ($events as $ev) {
			if ($i > $limit) continue;
			$sig_name = $ev['sig_name'];
			$plugin_id = $ev['plugin_id'];
			$plugin_sid = $ev['plugin_sid'];
			$sqlgr = str_replace("PLUGINID", $plugin_id, $sqlgraph);
			$sqlgr = str_replace("PLUGINSID", $plugin_sid, $sqlgr);
			//echo $sqlgr."<br>";
			//continue;
			if (!$rs = $db->Execute($sqlgr)) {
				print $db->ErrorMsg();
				var_dump($rs);
			} else {
				$yy = $y;
				while (!$rs->EOF) {
					$label = trim($rs->fields['intervalo'] . " " . $rs->fields['suf']);
					//echo "Label:$label value:".$rs->fields['sig_cnt']."<br>";
					if (isset($yy[$label]) && $yy[$label] == 0) $yy[$label] = $rs->fields['sig_cnt'];
					//$yy[$label] = $rs->fields['sig_cnt'];
					$rs->MoveNext();
				}
				//print_r($yy);
				$plots[$i] = $yy;
				$i++;
			}
		}
        
        return array($events,$plots,$numevents);
	}
	function get_SIM_Plot ($ip_src="",$ip_dst="",$from_date="",$to_date="") {
		require_once ('ossim_db.inc');
		$dbsock = new ossim_db();
		$db = $dbsock->snort_connect();
		
		// User sensor filtering
		$sensor_where = Status::get_sim_sensor_filter($db);
		
		// IP Filtering
		list($ip_where,$ip_where_src,$ip_where_dst) = Status::get_sim_ip_filter($ip_src,$ip_dst);
		
		list($x, $y, $xticks, $xlabels) = Status::range_graphic("week");
		// General Graph
		$sqlgraph = "SELECT count(plugin_sid) as sig_cnt, day(timestamp) as intervalo, monthname(timestamp) as suf FROM acid_event WHERE (timestamp BETWEEN '$from_date 00:00:00' AND '$to_date  23:59:59') $sensor_where $ip_where GROUP BY intervalo,suf ORDER BY suf,intervalo";
		
		$gplot = array();
		$numevents = 0;
		if (!$rs = $db->Execute($sqlgraph)) {
			print $db->ErrorMsg();
			var_dump($rs);
		} else {
			$yy = $y;
			while (!$rs->EOF) {
				$label = trim($rs->fields['intervalo'] . " " . $rs->fields['suf']);
				//echo "Label:$label value:".$rs->fields['sig_cnt']."<br>";
				if (isset($yy[$label]) && $yy[$label] == 0) $yy[$label] = $rs->fields['sig_cnt'];
				//$yy[$label] = $rs->fields['sig_cnt'];
				$numevents += $rs->fields['sig_cnt'];
				$rs->MoveNext();
			}
			//print_r($yy);
			$gplot = $yy;
		}
		return $gplot;
	}
	
    function get_SEM ($query,$start,$end,$sort_order,$uniqueid=1234,$host="") {
		require_once ("classes/Host.inc");
		require_once ("../sem/process.inc");
		require_once ('ossim_db.inc');
		//$config = parse_ini_file("../sem/everything.ini");
		$a = $query;
		$offset = -1;
		$db = new ossim_db();
		$conn = $db->connect();
		if (preg_match("/(.*plugin_id!=)(\S+)(.*)/", $a, $matches) || preg_match("/(.*plugin_id=)(\S+)(.*)/", $a, $matches)) {
			$plugin_name = mysql_real_escape_string($matches[2]);
			$query = "select id from plugin where name like '" . $plugin_name . "%' order by id";
			if (!$rs = & $conn->Execute($query)) {
				print $conn->ErrorMsg();
				exit();
			}
			if ($plugin_id = $rs->fields["id"] != "") {
				$plugin_id = $rs->fields["id"];
			} else {
				$plugin_id = $matches[2];
			}
			$a = $matches[1] . $plugin_id . $matches[3];
		}
		if (preg_match("/(.*sensor!=)(\S+)(.*)/", $a, $matches) || preg_match("/(.*sensor=)(\S+)(.*)/", $a, $matches)) {
			$plugin_name = mysql_real_escape_string($matches[2]);
			$query = "select ip from sensor where name like '" . $plugin_name . "%'";
			if (!$rs = & $conn->Execute($query)) {
				print $conn->ErrorMsg();
				exit();
			}
			if ($plugin_id = $rs->fields["ip"] != "") {
				$plugin_id = $rs->fields["ip"];
			} else {
				$plugin_id = $matches[2];
			}
			$a = $matches[1] . $plugin_id . $matches[3];
		}
		if ($host != "") {
			if (preg_match("/\/(\d+)/",$host,$found)) {
				if ($found[1] >= 24) $host = preg_replace("/\.\d+\/.*/","",$host);
				elseif ($found[1] >= 16) $host = preg_replace("/\.\d+\.\d+\/.*/","",$host);
				elseif ($found[1] >= 8) $host = preg_replace("/\.\d+\.\d+\.\d+\/.*/","",$host);
				$a = "net=$host";
			}
			else $a = "ip=$host";
		}
		$_SESSION["forensic_query"] = $a;
		$_SESSION["forensic_start"] = $start;
		$_SESSION["forensic_end"] = $end;
		$time1 = microtime(true);
		$cmd = process($a, $start, $end, $offset, "date_desc", "logs", $uniqueid);
		$user = $_SESSION["_user"];

		//$status = exec($cmd, $result);
		$result = array();
		$sem_wplot_y = array();
		$sem_wplot_x = array();
		$cmd = "cd ../../www/sem; ".$cmd;
		//echo "<br>$cmd $user<br>";
		//exit;
		$fp = popen("$cmd $user 2>>/dev/null", "r");
		$i = 0;
		while (!feof($fp)) {
			$line = trim(fgets($fp));
			if ($line != "" && !preg_match("/^Accepts two epoch\_timestamps/",$line)) {
				$result[] = $line;
				preg_match("/entry id='([^']+)'\s+fdate='([^']+)'\s+date='([^']+)'\s+plugin_id='([^']+)'\s+sensor='([^']+)'\s+src_ip='([^']+)'\s+dst_ip='([^']+)'\s+src_port='([^']+)'\s+dst_port='([^']+)'\s+tzone='[^']+'+\s+data='([^']+)'(\s+sig='([^']*)')?/", $line, $found);
				$d = strftime("%e %b", strtotime($found[2]));
				$sem_wplot_y[$d]++;
				$i++;
			}
		}
		fclose($fp);
		
		$sem_wplot_y = array_reverse($sem_wplot_y);
		$k = 0;
		foreach ($sem_wplot_y as $key=>$val) {
			$sem_wplot_x[$key] = $k;
			$k++;
		}
		
		$time2 = microtime(true);
		$totaltime = round($time2 - $time1, 2);
		//$num_lines = get_lines($a, $start, $end, $offset, $sort_order, "logs", $uniqueid);
		$sem_numrows = count($result);
		$date = "-";
		if ($sem_numrows > 0) {
			preg_match("/entry id='([^']+)'\s+fdate='([^']+)'\s+date='([^']+)'\s+plugin_id='([^']+)'\s+sensor='([^']+)'\s+src_ip='([^']+)'\s+dst_ip='([^']+)'\s+src_port='([^']+)'\s+dst_port='([^']+)'\s+tzone='[^']+'+\s+data='([^']+)'(\s+sig='([^']*)')?/", $result[0], $matches);
			$date = $matches[2];
		}
		return array($result,$sem_numrows,$date,$sem_wplot_y,$sem_wplot_x);
    }
	
	function get_SEM_Resume ($start,$end,$host="") {
		$start = preg_replace("/(\d\d\d\d)-(\d\d)-(\d\d).*/","\\1\\2\\3",$start);
		$end = preg_replace("/(\d\d\d\d)-(\d\d)-(\d\d).*/","\\1\\2\\3",$end);
		require_once ("classes/CIDR.inc");
		require_once ('ossim_db.inc');
		$db = new ossim_db();
		$conn = $db->connect();
		
		$ip_filter = "1";
		if ($host != "") {
			if (preg_match("/\/(\d+)/",$host,$found)) {
				$range = CIDR::expand_CIDR($host);
				$ip_filter = "(inet_aton(value) >= ".$range[0]." AND inet_aton(value) <= ".$range[1].")";
			}
			else $ip_filter = "value = '$host'";
		}
		
		$sql = "SELECT sum(counter) as total, max(day) as maxdate FROM sem_stats WHERE day >= '$start' AND day <= '$end' AND (type='src_ip' OR type='dst_ip') AND ($ip_filter)";
		if (!$rs = $conn->Execute($sql)) {
			print $conn->ErrorMsg();
			var_dump($rs);
        } else {
            if (!$rs->EOF) {
				$sem_numrows = $rs->fields['total'];
				$date = preg_replace("/(\d\d\d\d)(\d\d)(\d\d)/","\\1-\\2-\\3",$rs->fields['maxdate']);
            }
        }
		return array($sem_numrows,$date);
    }
    
	function get_anomalies ($conn,$host="") {
		require_once 'classes/RRD_anomaly.inc';
		$start = strftime("%Y-%m-%d %H:%M:%S", time() - (24 * 60 * 60));
		
		if (preg_match("/\/(\d+)/",$host,$found)) {
			if ($found[1] >= 24) $host = preg_replace("/\.\d+\/.*/","",$host);
			elseif ($found[1] >= 16) $host = preg_replace("/\.\d+\.\d+\/.*/","",$host);
			elseif ($found[1] >= 8) $host = preg_replace("/\.\d+\.\d+\.\d+\/.*/","",$host);
			$ip_where = "ip LIKE '$host%'";
		} else $ip_where = "ip='$host'";
		
		$event_list = RRD_anomaly::get_list($conn, "WHERE $ip_where AND anomaly_time >= '$start'", "order by anomaly_time desc", "0", "99999");
		$anm_foundrows = count ($event_list);
		$date = ($anm_foundrows > 0) ? $event_list[0]->get_anomaly_time() : "-";
		$start_week = strftime("%Y-%m-%d %H:%M:%S", time() - (24 * 60 * 60 * 7));
		$event_list2 = RRD_anomaly::get_list($conn, "WHERE $ip_where AND anomaly_time >= '$start_week'", "order by anomaly_time desc", "0", "99999");
		$anm_foundrows_week = count ($event_list2);
		
		return array($event_list,$anm_foundrows,$anm_foundrows_week,$date);
	}
	
	function get_vul_events($conn,$host,$date_from="",$date_to="",$truncate=1) {
		/*
		if (preg_match("/\/(\d+)/",$host)) {
			$exp = CIDR::expand_CIDR($host,"SHORT","IP");
			$host_s_range = $exp[0];
			$host_e_range = end($exp);
			$ip_where = "(hp.host_ip>=INET_ATON('$host_s_range') AND hp.host_ip<=INET_ATON('$host_e_range'))";
		}
		else $ip_where = "hp.host_ip=INET_ATON('$host')";
		$sql = "SELECT SQL_CALC_FOUND_ROWS p.sid,p.name,p.priority,INET_NTOA(hp.host_ip) as ip FROM host_plugin_sid hp, plugin_sid p WHERE hp.plugin_id = 3001 AND p.plugin_id = 3001 AND hp.plugin_sid = p.sid AND $ip_where";
        //echo $sql;
		$highrisk = "-";
		$list = array();
		$risks = array();
		$risks["-"] = 0;
		if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
        }
		else {
			if (!$rf = $conn->Execute("SELECT FOUND_ROWS() as total")) print $conn->ErrorMsg();
            else $foundrows = $rf->fields["total"];
			while (!$rs->EOF) {
				$list[] = array("name"=>$rs->fields['name'],"priority"=>$rs->fields['priority'],"ip"=>$rs->fields['ip']);
				$risks[$rs->fields['priority']]++;
				if ($rs->fields['priority'] > $highrisk) $highrisk = $rs->fields['priority'];
				$rs->MoveNext();
			}
		}
        if ($risks[$highrisk] == "") { $risknum = 0; $highrisk = "-"; }
		else $risknum = $risks[$highrisk];
		return array($list,$foundrows,$highrisk,$risknum);
		*/
		if (preg_match("/\/(\d+)/",$host)) {
			$exp = CIDR::expand_CIDR($host,"SHORT","IP");
			$host_s_range = $exp[0];
			$host_e_range = end($exp);
			$ip_where = "(report_id>=INET_ATON('$host_s_range') AND report_id<=INET_ATON('$host_e_range'))";
		}
		else $ip_where = "report_id=INET_ATON('$host')";
		if ($date_from != "" && $date_to != "") {
			$date_where = " AND scantime >= '".preg_replace("/(\s|\-|\:)/","",$date_from)."000000' AND scantime <= '".preg_replace("/(\s|\-|\:)/","",$date_to)."'";
		} else $date_where = "";
		$sql = "SELECT SQL_CALC_FOUND_ROWS msg as name,risk,INET_NTOA(report_id) as ip,scantime FROM vuln_nessus_latest_results WHERE $ip_where $date_where ORDER BY risk DESC,scantime";
        //echo $sql;
		$highrisk = "-";
		$lastdate = "-";
		$list = array();
		$risks = array();
		$risks["-"] = 0;
		if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
        }
		else {
			if (!$rf = $conn->Execute("SELECT FOUND_ROWS() as total")) print $conn->ErrorMsg();
            else $foundrows = $rf->fields["total"];
			while (!$rs->EOF) {
				if ($truncate) $list[] = array("name"=>substr(trim($rs->fields['name']),0,120)." [...]","risk"=>$rs->fields['risk'],"ip"=>$rs->fields['ip']);
				else $list[] = array("name"=>trim($rs->fields['name']),"risk"=>$rs->fields['risk'],"ip"=>$rs->fields['ip']);
				$risks[$rs->fields['risk']]++;
				if ($rs->fields['risk'] > $highrisk) $highrisk = $rs->fields['risk'];
				if ($lastdate == "-") $lastdate = preg_replace("/(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)/","\\1-\\2-\\3 \\4:\\5:\\6",$rs->fields['scantime']);
				$rs->MoveNext();
			}
		}
        if ($risks[$highrisk] == "") { $risknum = 0; $highrisk = "-"; }
		else $risknum = $risks[$highrisk];
		return array($list,$foundrows,$highrisk,$risknum,$lastdate);
	}
	
	function get_availability_events ($conn,$host) {
		// User sensor filtering
		$sensor_where = "";
		if (Session::allowedSensors() != "") {
			$user_sensors = explode(",",Session::allowedSensors());
			$snortsensors = Event_viewer::GetSensorSids($db);
			$sensor_str = "";
			foreach ($user_sensors as $user_sensor)
				if (count($snortsensors[$user_sensor]) > 0) $sensor_str .= ($sensor_str != "") ? ",".implode(",",$snortsensors[$user_sensor]) : implode(",",$snortsensors[$user_sensor]);
			if ($sensor_str == "") $sensor_str = "0";
			$sensor_where = " AND e.sid in (" . $sensor_str . ")";
		}
		
		// Network or Host
		if (preg_match("/\/(\d+)/",$host)) {
			$exp = CIDR::expand_CIDR($host,"SHORT","IP");
			$host_s_range = $exp[0];
			$host_e_range = end($exp);
			$ip_where = "(e.ip_src>=INET_ATON('$host_s_range') AND e.ip_src<=INET_ATON('$host_e_range')) OR (e.ip_dst>=INET_ATON('$host_s_range') AND e.ip_dst<=INET_ATON('$host_e_range'))";
		}
		else $ip_where = "e.ip_src=INET_ATON('$host') OR e.ip_dst=INET_ATON('$host')";
		
		//$sql = "SELECT SQL_CALC_FOUND_ROWS timestamp, ossim_priority as priority FROM acid_event e WHERE e.plugin_id=1525 $sensor_where AND ($ip_where) ORDER BY e.timestamp";
        $sql = "SELECT count(*) as total, max(timestamp) as maxtime, max(ossim_priority) as priority FROM acid_event e WHERE e.plugin_id=1525 $sensor_where AND ($ip_where) ORDER BY e.timestamp";
		
		$max_date = "-";
		$max_prio = 0;
		$prionum = 0;
		$foundrows = 0;
		
		if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
        }
		else {
			if (!$rs->EOF) {
				$foundrows = $rs->fields['total'];
				$max_date = ($rs->fields['maxtime'] != "") ? $rs->fields['maxtime'] : "-";
				$max_prio = $rs->fields['priority'];
			}
		}
		
		if ($foundrows > 0) {
			$sql = "SELECT count(*) as total FROM acid_event e WHERE e.ossim_priority=$max_prio AND e.plugin_id=1525 $sensor_where AND ($ip_where) ORDER BY e.timestamp";
			//echo $sql;
			if (!$rs = & $conn->Execute($sql)) {
	            print $conn->ErrorMsg();
	        }
			else {
				if (!$rs->EOF) {
					$prionum = $rs->fields['total'];
				}
			}
		}
		
		return array($max_date,$foundrows,$max_prio,$prionum);
	}
	
	function range_graphic($timerange,$date_range=null) {
		// for custom
		if($date_range!=null){
			if($timerange=='custom'){
				$date_from_div=explode('-',$date_range['date_from']);
				$date_to_div=explode('-',$date_range['date_to']);
				// calculate number of days
				$date_from_op=gregoriantojd ($date_from_div[1],$date_from_div[2],$date_from_div[0]);
				$date_to_op=gregoriantojd ($date_to_div[1],$date_to_div[2],$date_to_div[0]);
				$n_days=$date_to_op-$date_from_op+1;
				//
				$date_range['date_from'].=' 00:00:00';
				$date_range['date_to'].=' 23:59:59';
				if($n_days==1){
					$timerange='day';
				}elseif($n_days>1&&$n_days<=7){
					$timerange='week';
				}elseif($n_days>7&&$n_days<=31){
					$timerange='weeks';
				}elseif($n_days>31&&$n_days<=155){
					$timerange='custom1';
					echo 'que pasa neng! :)';
				}elseif($n_days>155&&$n_days<=365){
					$timerange='custom2';
					echo 'que pasa neng 2! :)';
				}elseif($n_days>365){
					$timerange='custom3';
					echo 'que pasa neng 3! :)';
				}
			}
		}
		//	
		switch ($timerange) {
			case "today":
				$desde = strtotime(date("Y-m-d 00:00:00"));
				$suf = "h";
				$jump = 3600;
				$noprint = 2;
				$interval = "G";
				$key = "G";
				$hasta = time();
				break;

			case "day":
				if($date_range!=null){
					$desde = strtotime($date_range['date_from']);
					$hasta = strtotime($date_range['date_to']);
				}else{
					$desde = strtotime("-23 hour");
					$hasta = time() + $jump;
				}
				$suf = "";
				$jump = 3600;
				$noprint = 3;
				$interval = "G\h jM";
				$key = "G j";
				break;

			case "week":
				if($date_range!=null){
					$desde = strtotime($date_range['date_from']);
					$hasta = strtotime($date_range['date_to']);
				}else{
					$desde = strtotime("-1 week");
					$hasta = time();
				}
				$suf = "";
				$jump = 86400;
				$noprint = 1;
				$interval = "j M";
				$key = "j F";
				break;

			case "weeks":
				if($date_range!=null){
					$desde = strtotime($date_range['date_from']);
					$hasta = strtotime($date_range['date_to']);
				}else{
					$desde = strtotime("-2 week");
					$hasta = time();
				}
				$suf = "";
				$jump = 86400;
				$noprint = 3;
				$interval = "j M";
				$key = "j F";
				break;

			case "month":
				if($date_range!=null){
					$desde = strtotime($date_range['date_from']);
					$hasta = strtotime($date_range['date_to']);
				}else{
					$desde = strtotime("-1 month");
					$hasta = time();
				}
				$suf = "";
				$jump = 86400; // se muestra por dia
				$noprint = 3;
				$interval = "j M";
				$key = "j F";
				break;
				
			case "custom1":
				$desde = strtotime($date_range['date_from']);
				$hasta = strtotime($date_range['date_to']);
				$suf = "";
				$jump = 1296000; //se muestra por mes  2592000 1296000
				$noprint = 2;  // se imprime cada x valores, por ejemplo si es 2 se imprime 1 3 5 de 1 2 3 4 5 6
				$interval = "d M y";
				$key = "d M y";
				break;
				
			case "custom2":
				$desde = strtotime($date_range['date_from']);
				$hasta = strtotime($date_range['date_to']);
				$suf = "";
				$jump = 2592000; //se muestra por mes  2592000 1296000
				$noprint = 1;  // se imprime cada x valores, por ejemplo si es 2 se imprime 1 3 5 de 1 2 3 4 5 6
				$interval = "d M y";
				$key = "d M y";				
				break;
				
			case "custom3":
				$desde = strtotime($date_range['date_from']);
				$hasta = strtotime($date_range['date_to']);
				$suf = "";
				$jump = 31104000; //se muestra por mes  2592000 1296000
				$noprint = 1;  // se imprime cada x valores, por ejemplo si es 2 se imprime 1 3 5 de 1 2 3 4 5 6
				$interval = "d M y";
				$key = "d M y";
				
				//$jump = 24;
				
				//$interval = "G\h j M";
				//$key = "G j";
				
				
				break;
				
			default:
				$desde = strtotime("-11 month");
				$suf = "";
				$jump = 0;
				$noprint = 2;
				$interval = "M-Y";
				$key = "F Y";
				$hasta = time();
		}
		//
		$x = $y = $ticks = $labels = array();
		$d = $desde;
		$xx = 0;
		while ($d <= $hasta) {
			$now = trim(date($key, $d) . " " . $suf);
			$x["$now"] = $ticks["$now"] = $xx++;
			$y["$now"] = 0; // default value 0
			$labels["$now"] = ($xx % $noprint == 0) ? date($interval, $d) . $suf : "";
			if ($jump == 0) $d+= (date("t", $d) * 86400); // case year
			else $d+= $jump; // next date
			
		}
		//var_dump($labels);
		return array(
			$x,
			$y,
			$ticks,
			$labels
		);
	}
	
	function print_Alarms ($ip,$status) {
		require_once ('classes/Alarm.inc');
		require_once ('classes/Util.inc');
		require_once ('ossim_db.inc');
		$db = new ossim_db();
		$conn = $db->connect();
		
		if ($status) $hide_closed = 1;
		else $hide_closed = 0;
		
		list($alarm_list, $count) = Alarm::get_list3($conn, $ip, $ip, $status, "ORDER BY a.timestamp DESC", 0, 5, "", "", "");
		$ret = "";
		foreach($alarm_list as $alarm) {
			$sid_name = $alarm->get_sid_name();
			$alarm_name = ereg_replace("directive_event: ", "", $sid_name);
			$alarm_name = Util::translate_alarm($conn, $alarm_name, $alarm);
			$ret .= "<tr><td class=\"nobborder\">$alarm_name</td><td class=\"nobborder\">".(($alarm->get_status() == "open") ? "<img src=\"../pixmaps/lock-unlock.png\">" : "<img src=\"../pixmaps/lock.png\">")."</td></tr>";
		}
		if ($count > 5) $ret .= "<tr><td class=\"nobborder\" style=\"text-align:right;padding:3px\"><a href=\"../control_panel/alarm_console.php?&hide_closed=$hide_closed&hmenu=Alarms&smenu=Alarms&src_ip=$ip&dst_ip=$ip\">Show all $count</a></td></tr>";
		return "<table class=\"noborder\" style=\"background:transparent\">".$ret."</table>";
	}
	
	function print_UEvents ($ip) {
		require_once ('ossim_db.inc');
		$dbsock = new ossim_db();
		$db = $dbsock->snort_connect();
		
		// User sensor filtering
		$sensor_where = "";
		if (Session::allowedSensors() != "") {
			$user_sensors = explode(",",Session::allowedSensors());
			$snortsensors = Event_viewer::GetSensorSids($db);
			$sensor_str = "";
			foreach ($user_sensors as $user_sensor)
				if (count($snortsensors[$user_sensor]) > 0) $sensor_str .= ($sensor_str != "") ? ",".implode(",",$snortsensors[$user_sensor]) : implode(",",$snortsensors[$user_sensor]);
			if ($sensor_str == "") $sensor_str = "0";
			$sensor_where = " AND acid_event.sid in (" . $sensor_str . ")";
		}
		
		$ip_where = "";
		// HOST Filtering
		if ($ip!="") {
			$ip_where = " AND (acid_event.ip_src=INET_ATON('$ip') OR acid_event.ip_dst=INET_ATON('$ip'))";
		}
		
		$sql = "SELECT DISTINCT acid_event.sid,acid_event.cid,acid_event.plugin_id,acid_event.plugin_sid, count(plugin_sid) as sig_cnt, min(timestamp), max(timestamp), plugin_sid.name as sig_name, count(DISTINCT(acid_event.sid)), count(DISTINCT(ip_src)), count(DISTINCT(ip_dst)), ip_src, ip_dst FROM acid_event LEFT JOIN ossim.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid WHERE 1 $sensor_where $ip_where ORDER BY sig_cnt DESC GROUP BY plugin_id,plugin_sid";
		//echo $sql."<br>";
		$list2 = array();
		if (!$rs = $db->Execute($sql)) {
            print $db->ErrorMsg();
			var_dump($rs);
        } else {
			$i = 0;
            while (!$rs->EOF) {
				$list2[] = array("plugin_id"=>$rs->fields['plugin_id'],"plugin_sid"=>$rs->fields['plugin_sid'],"sig_cnt"=>$rs->fields['sig_cnt'],"min(timestamp)"=>$rs->fields['min(timestamp)'],"max(timestamp)"=>$rs->fields['max(timestamp)'],"sig_name"=>$rs->fields['sig_name'],"ip_src"=>$rs->fields['count(DISTINCT(ip_src))'],"ip_dst"=>$rs->fields['count(DISTINCT(ip_dst))'],"num_sensors"=>$rs->fields['count(DISTINCT(acid_event.sid))'],"ip_s"=>$rs->fields['ip_src'],"ip_d"=>$rs->fields['ip_dst'],"sid"=>$rs->fields['sid'],"cid"=>$rs->fields['cid']);
				$rs->MoveNext();
				$i++;
            }
        }
		
		$ret = "";
		$k = 1;
		foreach($list2 as $ev) {
			if ($k > 5) continue;
			$ret .= "<tr><td class=\"nobborder\">".$ev['sig_name']."</td></tr>";
			$k++;
		}
		if ($i > 5) $ret .= "<tr><td class=\"nobborder\" style=\"text-align:right;padding:3px\"><a href=\"../forensics/base_stat_alerts.php?ip=$ip\">Show all $count</a></td></tr>";
		return "<table class=\"noborder\" style=\"background:transparent\">".$ret."</table>";
	}
	
	function print_Events ($ip) {
		require_once ('ossim_db.inc');
		$dbsock = new ossim_db();
		$db = $dbsock->snort_connect();
		
		// User sensor filtering
		$sensor_where = "";
		if (Session::allowedSensors() != "") {
			$user_sensors = explode(",",Session::allowedSensors());
			$snortsensors = Event_viewer::GetSensorSids($db);
			$sensor_str = "";
			foreach ($user_sensors as $user_sensor)
				if (count($snortsensors[$user_sensor]) > 0) $sensor_str .= ($sensor_str != "") ? ",".implode(",",$snortsensors[$user_sensor]) : implode(",",$snortsensors[$user_sensor]);
			if ($sensor_str == "") $sensor_str = "0";
			$sensor_where = " AND acid_event.sid in (" . $sensor_str . ")";
		}
		
		$ip_where = "";
		// HOST Filtering
		if ($ip!="") {
			$ip_where = " AND (acid_event.ip_src=INET_ATON('$ip') OR acid_event.ip_dst=INET_ATON('$ip'))";
		}
		
		$sql = "SELECT DISTINCT acid_event.sid,acid_event.cid,acid_event.plugin_id,acid_event.plugin_sid, plugin_sid.name as sig_name, ip_src, ip_dst FROM acid_event LEFT JOIN ossim.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid WHERE 1 $sensor_where $ip_where ORDER BY timestamp DESC";
		//echo $sql;
		$list2 = array();
		if (!$rs = $db->Execute($sql)) {
            print $db->ErrorMsg();
			var_dump($rs);
        } else {
			$i = 0;
            while (!$rs->EOF) {
				$list2[] = array("plugin_id"=>$rs->fields['plugin_id'],"plugin_sid"=>$rs->fields['plugin_sid'],"sig_cnt"=>$rs->fields['sig_cnt'],"min(timestamp)"=>$rs->fields['min(timestamp)'],"max(timestamp)"=>$rs->fields['max(timestamp)'],"sig_name"=>$rs->fields['sig_name'],"ip_src"=>$rs->fields['count(DISTINCT(ip_src))'],"ip_dst"=>$rs->fields['count(DISTINCT(ip_dst))'],"num_sensors"=>$rs->fields['count(DISTINCT(acid_event.sid))'],"ip_s"=>$rs->fields['ip_src'],"ip_d"=>$rs->fields['ip_dst'],"sid"=>$rs->fields['sid'],"cid"=>$rs->fields['cid']);
				$rs->MoveNext();
				$i++;
            }
        }
		
		$ret = "";
		$k = 1;
		foreach($list2 as $ev) {
			if ($k > 5) continue;
			$ret .= "<tr><td class=\"nobborder\">".$ev['sig_name']."</td></tr>";
			$k++;
		}
		if ($i > 5) $ret .= "<tr><td class=\"nobborder\" style=\"text-align:right;padding:3px\"><a href=\"../forensics/base_qry_main.php?num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d&ip=$ip&hmenu=Forensics&smenu=Forensics\" target=\"main\">Show all $count</a></td></tr>";
		return "<table class=\"noborder\" style=\"background:transparent\">".$ret."</table>";
	}
}
?>
