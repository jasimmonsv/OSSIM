<?php
/*****************************************************************************
*
*    License:
*
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
* - AlarmGroup()
* - get_group_id()
* - get_ocurrences()
* - get_event_id()
* - get_backlog_id()
* - get_timestamp()
* - get_status()
* - get_since()
* - get_plugin_id()
* - get_plugin_sid()
* - get_protocol()
* - get_src_ip()
* - get_dst_ip()
* - get_src_port()
* - get_dst_port()
* - get_risk()
* - get_asset_src()
* - get_asset_dst()
* - get_rule_level()
* - get_sensors()
* - get_snort_sid()
* - get_snort_cid()
* - get_alarm()
* - get_owner()
* - get_descr()
* - get_count()
* - __get_max_sensor()
* - __get_sensors()
* - __get_since()
* - take_alarm()
* - close()
* - open()
* - change_descr()
* - get_group()
* - get_group_from_alarm()
* - ungroup_alarm()
* - group_alarm()
* - delete_group()
* - delete_groups()
* - update_group_timestamp()
* - create_new_group()
* - remove_alarm_from_group()
* - add_alarm2group()
* - get_list()
* - get_list_of_group()
* - get_list2()
* - get_events()
* - get_alarm_stats()
* - delete_day()
* - delete()
* - delete_from_backlog()
* - purge()
* Classes list:
* - AlarmGroup
*/
require_once ('classes/Net.inc');
require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
require_once ('classes/Log_action.inc');
require_once ('classes/Alarm.inc');
require_once ("ossim_db.inc");
class AlarmGroups {
    var $group_id;
    var $ocurrences;
    var $event_id;
    var $backlog_id;
    var $timestamp;
    var $status;
    var $since;
    var $plugin_id;
    var $plugin_sid;
    var $protocol;
    var $src_ip;
    var $dst_ip;
    var $src_port;
    var $dst_port;
    var $risk;
    var $sensors = array();
    var $snort_sid;
    var $snort_cid;
    var $rule_level;
    var $alarm;
    var $owner;
    var $descr;
    /*
    | id        | bigint(20)            | NO   | PRI | NULL              | auto_increment |
    | status    | enum('open','closed') | YES  |     | open              |                |
    | timestamp | timestamp             | NO   |     | CURRENT_TIMESTAMP |                |
    | owner     | varchar(64)           | YES  |     | NULL              |                |
    | descr
    */
    function AlarmGroups($group_id, $ocurrences, $event_id, $backlog_id, $timestamp, $status, $since, $plugin_id, $plugin_sid, $protocol, $src_ip, $dst_ip, $src_port, $dst_port, $risk, $sensors, $snort_sid, $snort_cid, $owner, $descr, $rule_level = "", $alarm = 1) {
        $this->group_id = intval($group_id);
        $this->ocurrences = intval($ocurrences);
        $this->event_id = intval($event_id);
        $this->backlog_id = intval($backlog_id);
        $this->timestamp = $timestamp;
        $this->status = $status;
        $this->since = $since;
        $this->plugin_id = intval($plugin_id);
        $this->plugin_sid = intval($plugin_sid);
        $this->protocol = $protocol;
        $this->src_ip = $src_ip;
        $this->dst_ip = $dst_ip;
        $this->src_port = intval($src_port);
        $this->dst_port = intval($dst_port);
        $this->risk = intval($risk);
        $this->snort_sid = intval($snort_sid);
        $this->snort_cid = intval($snort_cid);
        $this->rule_level = intval($rule_level);
        $this->alarm = $alarm;
        $this->owner = $owner;
        $this->descr = $descr;
        if (is_array($sensors)) $this->sensors = $sensors;
        else $this->sensors = array(
            $sensors
        );
    }
    function get_group_id() {
        return $this->group_id;
    }
    function get_ocurrences() {
        return $this->ocurrences;
    }
    function get_event_id() {
        return $this->event_id;
    }
    function get_backlog_id() {
        return $this->backlog_id;
    }
    function get_timestamp() {
        return $this->timestamp;
    }
    function get_status() {
        return $this->status;
    }
    function get_since() {
        return $this->since;
    }
    function get_plugin_id() {
        return $this->plugin_id;
    }
    function get_plugin_sid() {
        return $this->plugin_sid;
    }
    function get_protocol() {
        return $this->protocol;
    }
    function get_src_ip() {
        return $this->src_ip;
    }
    function get_dst_ip() {
        return $this->dst_ip;
    }
    function get_src_port() {
        return $this->src_port;
    }
    function get_dst_port() {
        return $this->dst_port;
    }
    function get_risk() {
        return $this->risk;
    }
    function get_asset_src() {
        return $this->asset_src;
    }
    function get_asset_dst() {
        return $this->asset_dst;
    }
    function get_rule_level() {
        return $this->rule_level;
    }
    function get_sensors() {
        return $this->sensors;
    }
    function get_snort_sid() {
        return $this->snort_sid;
    }
    function get_snort_cid() {
        return $this->snort_cid;
    }
    function get_alarm() {
        return $this->alarm;
    }
    function get_owner() {
        return $this->owner;
    }
    function get_descr() {
        return $this->descr;
    }
    /*
    function Alarm ($event_id, $backlog_id, $timestamp, $status, $since,
    $plugin_id, $plugin_sid, $protocol, $src_ip, $dst_ip,
    $src_port, $dst_port, $risk, $sensors,
    $snort_sid, $snort_cid, $rule_level = "", $alarm = 1)
    {
    $this->event_id     = intval($event_id);
    $this->backlog_id   = intval($backlog_id);
    $this->timestamp    = $timestamp;
    $this->status       = $status;
    $this->since        = $since;
    $this->plugin_id    = intval($plugin_id);
    $this->plugin_sid   = intval($plugin_sid);
    $this->protocol     = $protocol;
    $this->src_ip       = $src_ip;
    $this->dst_ip       = $dst_ip;
    $this->src_port     = intval($src_port);
    $this->dst_port     = intval($dst_port);
    $this->risk         = intval($risk);
    $this->snort_sid    = intval($snort_sid);
    $this->snort_cid    = intval($snort_cid);
    $this->rule_level   = intval($rule_level);
    $this->alarm        = $alarm;
    
    if (is_array($sensors))
    $this->sensors  = $sensors;
    else
    $this->sensors  = array($sensors);
    }
    
    function get_event_id()         { return $this->event_id;       }
    function get_backlog_id()       { return $this->backlog_id;     }
    function get_timestamp()        { return $this->timestamp;      }
    function get_status()           { return $this->status;         }
    function get_since()            { return $this->since;          }
    function get_plugin_id()        { return $this->plugin_id;      }
    function get_plugin_sid()       { return $this->plugin_sid;     }
    function get_protocol()         { return $this->protocol;       }
    function get_src_ip()           { return $this->src_ip;         }
    function get_dst_ip()           { return $this->dst_ip;         }
    function get_src_port()         { return $this->src_port;       }
    function get_dst_port()         { return $this->dst_port;       }
    function get_risk()             { return $this->risk;           }
    function get_asset_src()        { return $this->asset_src;      }
    function get_asset_dst()        { return $this->asset_dst;      }
    function get_rule_level()       { return $this->rule_level;     }
    function get_sensors()          { return $this->sensors;        }
    function get_snort_sid()        { return $this->snort_sid;      }
    function get_snort_cid()        { return $this->snort_cid;      }
    function get_alarm()            { return $this->alarm;          }
    */
    /* deprecated */
	function get_grouped_alarms($conn, $group_type, $show_options=1, $hide_closed=0, $from_date, $to_date, $ip_src="", $ip_dst="", $limit="LIMIT 0,10") {
		// CRITERIAS
		// Hide Closed
		$closed_where = ($hide_closed) ? " AND status='open'" : "";
		// Show Options
		if ($show_options == 2) {
			$groups_join = ", alarm_groups g";
			$groups_where = " WHERE CONCAT(sha1( alarm_event.name), sha1( alarm_event.src_ip + alarm_event.dst_ip + DATE( alarm_event.timestamp ) ) )=g.group_id AND g.owner='".$_SESSION['_user']."'";
		}
		if ($show_options == 3) {
			$groups_join = "LEFT JOIN alarm_groups g ON CONCAT(sha1( alarm_event.name), sha1( alarm_event.src_ip + alarm_event.dst_ip + DATE( alarm_event.timestamp ) ) )= g.group_id";
			$groups_where = " WHERE g.owner IS NULL OR g.owner = ''";
		}
		if ($show_options == 4) {
			$groups_join = "LEFT JOIN alarm_groups g ON CONCAT(sha1( alarm_event.name), sha1( alarm_event.src_ip + alarm_event.dst_ip + DATE( alarm_event.timestamp ) ) )= g.group_id";
			$groups_where = " WHERE g.owner IS NULL OR g.owner = '".$_SESSION['_user']."'";
		}
		// Date
		if ($from_date != "") {
			$date_where = " AND a.timestamp >= '$from_date'";
		}
		if ($to_date != "") {
			$date_where .= " AND a.timestamp <= '$to_date'";
		}
		// IP
		if ($ip_src != "") $ip_where = " AND a.src_ip=INET_ATON('$ip_src')";
		if ($ip_dst != "") $ip_where .= " AND a.dst_ip=INET_ATON('$ip_dst')";
		
		// User Sensor filter
		$sensor_where = "";
		$sensor_str = "";
		if (Session::allowedSensors() != "") {
			$user_sensors = explode(",",Session::allowedSensors());
			foreach ($user_sensors as $user_sensor) if ($user_sensor!="")
				$sensor_str .= ($sensor_str == "") ? "'".$user_sensor."'" : ",'".$user_sensor."'";
			//echo "<br><br><br><br>SENSOR:$sensor_str de ".Session::allowedSensors();
			$sensor_where = " AND e.sensor in ($sensor_str) AND e.id=a.event_id";
		}
		
		if ($sensor_str != "") {
			$sensor_join = " ,event e";
		} else $sensor_join = "";
		
		// QUERY
		// Group by name (unique)
		if ($group_type == "name") {
			$query = "SELECT SQL_CALC_FOUND_ROWS count( * ) AS howmany,
			sha1( alarm_event.name ) AS group_id,
			alarm_event.name AS name
			FROM (
			(SELECT p.name FROM alarm a, plugin_sid p$sensor_join
			WHERE a.plugin_id = p.plugin_id
			AND a.plugin_sid = p.sid
			$closed_where
			$date_where
			$ip_where
			$sensor_where)
			) as alarm_event $groups_where
			GROUP BY name
			ORDER BY howmany DESC $limit";
		// Group by name, date
		} elseif ($group_type == "namedate") {
		$query = "SELECT SQL_CALC_FOUND_ROWS count( * ) AS howmany,
			CONCAT( sha1( alarm_event.name ) , DATE( alarm_event.timestamp ) ) AS group_id,
			alarm_event.name AS name,
			DATE( alarm_event.timestamp ) AS day
			FROM (
			(SELECT p.name,a.src_ip,a.dst_ip,a.timestamp FROM alarm a, plugin_sid p$sensor_join
			WHERE a.plugin_id = p.plugin_id
			AND a.plugin_sid = p.sid
			$closed_where
			$date_where
			$ip_where
			$sensor_where)
			) as alarm_event $groups_join$groups_where
			GROUP BY name, day
			ORDER BY alarm_event.timestamp DESC $limit";
		// Group by name, ips, date (all)
		} else {
		$query = "SELECT SQL_CALC_FOUND_ROWS count( * ) AS howmany,
			CONCAT( sha1( alarm_event.name ) , CONCAT( 'S', alarm_event.src_ip, 'D', alarm_event.dst_ip + DATE( alarm_event.timestamp ) ) ) AS group_id,
			alarm_event.name AS name,
			INET_NTOA( alarm_event.src_ip ) AS src_ip,
			INET_NTOA( alarm_event.dst_ip ) AS dst_ip,
			DATE( alarm_event.timestamp ) AS day
			FROM (
			(SELECT p.name,a.src_ip,a.dst_ip,a.timestamp FROM alarm a, plugin_sid p$sensor_join
			WHERE a.plugin_id = p.plugin_id
			AND a.plugin_sid = p.sid
			$closed_where
			$date_where
			$ip_where
			$sensor_where)
			) as alarm_event $groups_join$groups_where
			GROUP BY name, src_ip, dst_ip, day
			ORDER BY alarm_event.timestamp DESC $limit";
		}
		//echo "<br><br><br><br>$query<br>";
		
		$ret = array();
		$query = OssimQuery($query);
		
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
			// Total rows
			$total = & $conn->Execute("SELECT FOUND_ROWS() as contador");
			$num_rows = 0;
			if (!$total->EOF) $num_rows = $total->fields['contador'];
            $list = array();
            while (!$rs->EOF) {
				$ret[] = array(
					"group_id" => $rs->fields['group_id'],
					"group_count" => $rs->fields['howmany'],
					"name" => $rs->fields['name'],
					"date" => $rs->fields['day'],
					"ip_src" => $rs->fields['src_ip'],
					"ip_dst" => $rs->fields['dst_ip']
				);
				$rs->MoveNext();
			}
		}
		return array($ret,$num_rows);
	}
	
	function get_unique_alarms($conn, $show_options=1, $hide_closed=0, $from_date, $to_date, $ip_src="", $ip_dst="", $limit="LIMIT 0,10") {
		// CRITERIAS
		// Hide Closed
		$closed_where = ($hide_closed) ? " AND status='open'" : "";
		// Show Options
		/*
		if ($show_options == 2) {
			$groups_join = ", alarm_groups g";
			$groups_where = " WHERE CONCAT(sha1( alarm_event.name), sha1( alarm_event.src_ip + alarm_event.dst_ip + DATE( alarm_event.timestamp ) ) )=g.group_id AND g.owner='".$_SESSION['_user']."'";
		}
		if ($show_options == 3) {
			$groups_join = "LEFT JOIN alarm_groups g ON CONCAT(sha1( alarm_event.name), sha1( alarm_event.src_ip + alarm_event.dst_ip + DATE( alarm_event.timestamp ) ) )= g.group_id";
			$groups_where = " WHERE g.owner IS NULL OR g.owner = ''";
		}
		if ($show_options == 4) {
			$groups_join = "LEFT JOIN alarm_groups g ON CONCAT(sha1( alarm_event.name), sha1( alarm_event.src_ip + alarm_event.dst_ip + DATE( alarm_event.timestamp ) ) )= g.group_id";
			$groups_where = " WHERE g.owner IS NULL OR g.owner = '".$_SESSION['_user']."'";
		}*/
		// Date
		if ($from_date != "") {
			$date_where = " AND a.timestamp >= '$from_date'";
		}
		if ($to_date != "") {
			$date_where .= " AND a.timestamp <= '$to_date'";
		}
		// IP
		if ($ip_src != "") $ip_where = " AND a.src_ip=INET_ATON('$ip_src')";
		if ($ip_dst != "") $ip_where .= " AND a.dst_ip=INET_ATON('$ip_dst')";
		
		// User Sensor filter
		$sensor_where = "";
		$sensor_str = "";
		if (Session::allowedSensors() != "") {
			$user_sensors = explode(",",Session::allowedSensors());
			foreach ($user_sensors as $user_sensor) if ($user_sensor!="")
				$sensor_str .= ($sensor_str == "") ? "'".$user_sensor."'" : ",'".$user_sensor."'";
			//echo "<br><br><br><br>SENSOR:$sensor_str de ".Session::allowedSensors();
			$sensor_where = " AND e.sensor in ($sensor_str) AND e.id=a.event_id";
		}
		
		if ($sensor_str != "") {
			$sensor_join = " ,event e";
		} else $sensor_join = "";
		
		// QUERY
		/*$query = "SELECT SQL_CALC_FOUND_ROWS count( * ) AS howmany,
			CONCAT(sha1( alarm_event.name ), sha1( alarm_event.src_ip + alarm_event.dst_ip + DATE( alarm_event.timestamp ) ) ) AS group_id,
			alarm_event.name AS name,
			INET_NTOA( alarm_event.src_ip ) AS src_ip,
			INET_NTOA( alarm_event.dst_ip ) AS dst_ip,
			DATE( alarm_event.timestamp ) AS day
			FROM (
			(SELECT p.name,a.src_ip,a.dst_ip,a.timestamp FROM alarm a, plugin_sid p$sensor_join
			WHERE a.plugin_id = p.plugin_id
			AND a.plugin_sid = p.sid
			$closed_where
			$date_where
			$ip_where
			$sensor_where)
			) as alarm_event $groups_where
			GROUP BY name, src_ip, dst_ip
			ORDER BY howmany DESC $limit";*/
            
		$query = "SELECT SQL_CALC_FOUND_ROWS count( * ) AS howmany,
			sha1( alarm_event.name ) AS group_id,
			alarm_event.name AS name
			FROM (
			(SELECT p.name FROM alarm a, plugin_sid p$sensor_join
			WHERE a.plugin_id = p.plugin_id
			AND a.plugin_sid = p.sid
			$closed_where
			$date_where
			$ip_where
			$sensor_where)
			) as alarm_event $groups_where
			GROUP BY name
			ORDER BY howmany DESC $limit";
		
		//echo "<br><br><br><br>$query<br>";
		
		$ret = array();
		$query = OssimQuery($query);
		
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
			// Total rows
			$total = & $conn->Execute("SELECT FOUND_ROWS() as contador");
			$num_rows = 0;
			if (!$total->EOF) $num_rows = $total->fields['contador'];
            $list = array();
            while (!$rs->EOF) {
				$ret[] = array(
					"group_id" => $rs->fields['group_id'],
					"group_count" => $rs->fields['howmany'],
					"name" => $rs->fields['name'],
					"date" => $rs->fields['day'],
					"ip_src" => $rs->fields['src_ip'],
					"ip_dst" => $rs->fields['dst_ip']
				);
				$rs->MoveNext();
			}
		}
		return array($ret,$num_rows);
	}
	
	function get_alarms ($conn, $src_ip="", $dst_ip="", $hide_closed = 1, $order, $inf = null, $sup = null, $from_date = null, $to_date = null, $query = "") {
        $list = array();
        $where = array();
        // Pre-Selected Ports (Granada 27 de mayo 2009)
        $ports = array();
        if (!$rsp = & $conn->Execute("SELECT * FROM port")) {
            print $conn->ErrorMsg();
            return array(array(),0);
        }
        while (!$rsp->EOF) {
            $ports[$rsp->fields["protocol_name"]][$rsp->fields["port_number"]] = $rsp->fields["service"];
            $rsp->MoveNext();
        }
        /* ip filter */
        
		if (valid_ip($src_ip)) $src_ip.= "/32";
        if (valid_ip($dst_ip)) $dst_ip.= "/32";
		
        if (isset($dst_ip)) list($dest_start, $dest_end) = Util::cidr_conv($dst_ip);
		if (isset($src_ip)) list($source_start, $source_end) = Util::cidr_conv($src_ip);
       
		/* If both src and dst are equal, try to find all alarms where src OR dst are equal to the IP */
        if ($src_ip && ($src_ip != $dst_ip)) {
            $where[] = "((inet_aton('$source_start') <= a.src_ip AND inet_aton('$source_end') >= a.src_ip)
                        AND
                        (inet_aton('$dest_start') <= a.dst_ip AND inet_aton('$dest_end') >= a.dst_ip))";
        } elseif ($src_ip) {
            $where[] = "(inet_aton('$source_start') <= a.src_ip AND inet_aton('$source_end') >= a.src_ip)";
        } elseif ($dst_ip) {
            $where[] = "(inet_aton('$dest_start') <= a.dst_ip AND inet_aton('$dest_end') >= a.dst_ip)";
        }
        /* hide closed */
        if ($hide_closed == 1) {
            $where[] = "a.status = 'open' ";
        }
        /* date range filter */
        if ($from_date) {
            list($y, $m, $d) = preg_split('#-#', $from_date);
            if (!checkdate($m, $d, $y)) {
                print _("Invalid date") . ": $from_date";
                return array(array(),0);
            }
            $where[] = "(a.timestamp >= '$from_date')";
        }
        if ($to_date) {
            list($y, $m, $d) = preg_split('#-#', $to_date);
            if (!checkdate($m, $d, $y)) {
                print _("Invalid date") . ": $to_date";
                return array(array(),0);
            }
            $where[] = "(a.timestamp <= '$to_date 23:59:59')";
        }
        /* number of events per page */
        $limit = "";
        if ($sup !== null && $inf !== null) {
            $nevents = $sup - $inf;
            $limit = "LIMIT $nevents OFFSET $inf";
        }
        if ($query != "") {
            $where[] = "(p.name LIKE \"%$query%\")";
        }
        $sql_where = count($where) ? 'WHERE a.plugin_id=p.plugin_id AND a.plugin_sid=p.sid AND ' . implode(" AND ", $where) : "WHERE a.plugin_id=p.plugin_id AND a.plugin_sid=p.sid";
        
		$sensor_str = "";
		if (Session::allowedSensors() != "") {
			$user_sensors = explode(",",Session::allowedSensors());
			foreach ($user_sensors as $user_sensor) if ($user_sensor!="")
				$sensor_str .= ($sensor_str == "") ? "e.sensor LIKE '".$user_sensor."%'" : " OR e.sensor LIKE '".$user_sensor."%'";
			//echo "<br><br><br><br>SENSOR:$sensor_str de ".Session::allowedSensors();
			$sensor_str = " AND ($sensor_str) AND e.id=a.event_id";
		}
		
		if ($sensor_str != "") {
			$sensor_join = " ,event e";
			$sql_where .= $sensor_str;
		} else $sensor_join = "";
		
		// ... ", a.timestamp + 0 AS timestamp" what is this?
        $sql = "SELECT SQL_CALC_FOUND_ROWS *, p.name as sid_name, 
                  inet_ntoa(a.src_ip), inet_ntoa(a.dst_ip)
                FROM alarm a,plugin_sid p$sensor_join $sql_where $order $limit";
        //echo "SQL1: ".$sql."<br>";
        //exit;
        if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
            return array();
        }
        // Total rows
        $total = & $conn->Execute("SELECT FOUND_ROWS() as contador");
        $num_rows = 0;
        if (!$total->EOF) $num_rows = $total->fields['contador'];
        while (!$rs->EOF) {
            $sensors = Alarm::__get_sensors($conn, $rs->fields["event_id"], $rs->fields["backlog_id"]);
            $sensor_allowed = 0;
            foreach($sensors as $sensor) {
                if (Session::hostAllowed($conn, $sensor)) $sensor_allowed = 1;
            }
			$sensor_allowed = 1; // Already filtered in query
            if ((Session::hostAllowed($conn, $rs->fields["inet_ntoa(src_ip)"])) or (Session::hostAllowed($conn, $rs->fields["inet_ntoa(dst_ip)"])) or ($sensor_allowed)) {
                $rs->fields["src_port"] = ($ports[$rs->fields["protocol"]][$rs->fields["src_port"]] != "") ? $ports[$rs->fields["protocol"]][$rs->fields["src_port"]] : $rs->fields["src_port"];
                $rs->fields["dst_port"] = ($ports[$rs->fields["protocol"]][$rs->fields["dst_port"]] != "") ? $ports[$rs->fields["protocol"]][$rs->fields["dst_port"]] : $rs->fields["dst_port"];
				$list[] = new Alarm($rs->fields["event_id"], $rs->fields["backlog_id"], $rs->fields["timestamp"], $rs->fields["status"], Alarm::__get_since($conn, $rs->fields["event_id"], $rs->fields["backlog_id"]) , $rs->fields["plugin_id"], $rs->fields["plugin_sid"], $rs->fields["protocol"], $rs->fields["inet_ntoa(a.src_ip)"], $rs->fields["inet_ntoa(a.dst_ip)"], $rs->fields["src_port"], $rs->fields["dst_port"], $rs->fields["risk"], $sensors, $rs->fields["snort_sid"], $rs->fields["snort_cid"], $rs->fields["sid_name"], "", 1);
            }
            $rs->MoveNext();
        }
        return array(
            $list,
            $num_rows
        );
    }
	function get_dbgroups ($conn) {
		$sql = "SELECT * FROM alarm_groups";
		$ret = array();
		if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
            return array();
        }
		else {
			while (!$rs->EOF) {
				$ret[$rs->fields['group_id']] = array("owner"=>$rs->fields['owner'],"status"=>$rs->fields['status'],"descr"=>$rs->fields['description']);
				$rs->MoveNext();
			}
		}
		return $ret;
	}
	function take_group ($conn, $group_id, $user) {
		$sql = "INSERT INTO alarm_groups (group_id,owner) VALUES ('$group_id','$user') ON DUPLICATE KEY UPDATE owner='$user'";
		if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
        }
	}
	function release_group ($conn, $group_id) {
		$sql = "UPDATE alarm_groups SET owner='' WHERE group_id='$group_id'";
		if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
        }
	}
	function delete_group ($conn, $group_id, $user) {
		$sql = "DELETE FROM alarm_groups WHERE group_id='$group_id' AND owner='$user'";
		if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
        }
	}
	function change_descr ($conn, $descr, $group_id) {
		$sql = "INSERT INTO alarm_groups (group_id,description) VALUES ('$group_id','$descr') ON DUPLICATE KEY UPDATE description=\"$descr\"";
		if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
        }
	}
	function change_status ($conn, $group_id, $status) {
		$sql = "INSERT INTO alarm_groups (group_id,status) VALUES ('$group_id','$status') ON DUPLICATE KEY UPDATE status='$status'";
		if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
        }
		$sql = "UPDATE alarm a,plugin_sid p SET a.status='$status' WHERE a.plugin_id=p.plugin_id AND a.plugin_sid=p.sid AND CONCAT( sha1(p.name), sha1( a.src_ip + a.dst_ip + DATE( a.timestamp ))) = '$group_id'";
		if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
        }
	}
}
?>
