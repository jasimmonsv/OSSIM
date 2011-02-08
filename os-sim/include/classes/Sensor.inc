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
* - Sensor()
* - get_name()
* - get_ip()
* - get_priority()
* - get_port()
* - get_version()
* - get_connect()
* - get_descr()
* - get_foundrows()
* - get_list()
* - get_all()
* - get_count()
* - check_plugin_rel()
* - insert()
* - update()
* - delete()
* - get_sensor_link()
* - get_sensor_interface()
* - sensor_exists()
* Classes list:
* - Sensor
*/
require_once 'classes/Net_sensor_reference.inc';
require_once 'classes/Host_sensor_reference.inc';
require_once 'classes/Sensor.inc';
require_once 'classes/Session.inc';
require_once 'classes/Net.inc';
require_once 'classes/Log_action.inc';
require_once 'ossim_db.inc';
require_once 'classes/WebIndicator.inc';
class Sensor {
    var $name;
    var $ip;
    var $priority;
    var $port;
    var $tzone;
    var $connect;
    var $descr;
    function Sensor($name, $ip, $priority, $port, $tzone, $version, $connect, $descr, $foundrows = 0) {
        $this->name = $name;
        $this->ip = $ip;
        $this->priority = $priority;
        $this->port = $port;
        $this->tzone = $tzone;
        $this->version = $version;
        $this->connect = $connect;
        $this->descr = $descr;
        $this->foundrows = $foundrows;
    }
    function get_name() {
        return $this->name;
    }
    function get_ip() {
        return $this->ip;
    }
    function get_priority() {
        return $this->priority;
    }
    function get_port() {
        return $this->port;
    }
    function get_tzone() {
        return $this->tzone;
    }
    function get_version() {
        return $this->version;
    }
    function get_connect() {
        return $this->connect;
    }
    function get_descr() {
        return $this->descr;
    }
    function get_foundrows() {
        return $this->foundrows;
    }
    function get_list($conn, $args = "") {
        $list = array();
        $iffoundrows = preg_match("/LIMIT/", $args) ? true : false;
        $select = ($iffoundrows) ? "SQL_CALC_FOUND_ROWS *" : "*";
        $query = OssimQuery("SELECT $select FROM sensor $args");
		if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $foundrows = 0;
            if ($iffoundrows) {
                if (!$rf = & $conn->Execute("SELECT FOUND_ROWS() as total")) print $conn->ErrorMsg();
                else $foundrows = $rf->fields["total"];
            }
            while (!$rs->EOF) {
				if (Session::sensorAllowed($conn, $rs->fields["ip"])) {
					$vrsnqry = OssimQuery("SELECT version FROM sensor_properties WHERE ip='" . $rs->fields["ip"] . "'");
                    $version = ($rv = & $conn->Execute($vrsnqry)) ? $version = $rv->fields["version"] : "unknown";
                    $list[] = new Sensor($rs->fields["name"], $rs->fields["ip"], $rs->fields["priority"], $rs->fields["port"], $rs->fields["tzone"], $version, $rs->fields["connect"], $rs->fields["descr"], $foundrows);
                }
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function get_properties($conn, $sensor) {
        $result = array();
        $query = OssimQuery("SELECT * FROM sensor_properties WHERE ip='".$sensor."'");
        if (!$rs = & $conn->Execute($query)) {
            die($conn->ErrorMsg());
        } 
        else {
            if (!$rs->EOF) {
                $result = $rs->fields;
            }
        }
        return $result;
    }
    function get_vuln_scanner_options($conn, $sensor) {
        $result = array();
        $query = OssimQuery("SELECT port, user, PASSWORD, max_scans FROM vuln_nessus_servers WHERE hostname='".$sensor."'");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } 
        else {
            if (!$rs->EOF) {
                $result = $rs->fields;
            }
        }
			
        return $result;
    }
    
    function update_vuln_nessus_servers($conn, $sensor, $user, $password, $port, $vuln_max_scans, $enabled) {
        $query = "UPDATE vuln_nessus_servers SET user='$user', PASSWORD='$password', port = '$port', max_scans= $vuln_max_scans, enabled = '$enabled', current_scans = 0
                          WHERE hostname='$sensor'";
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        }
        
    }
    function insert_vuln_nessus_servers($conn, $sensor, $name, $enabled) {

        $query = OssimQuery("SELECT id FROM vuln_nessus_servers WHERE hostname='".$sensor."'");

        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } 
        else {
            if ($rs->fields["id"]=="") {
                $conf  = $GLOBALS["CONF"];
				$port  = intval($conf->get_conf("nessus_port", FALSE));
				$query = OssimQuery("INSERT INTO vuln_nessus_servers (name , description, hostname ,
                                        port, user, PASSWORD, server_nversion, server_feedtype, server_feedversion,
                                        max_scans, current_scans, TYPE, site_code, owner, checkin_time, status , enabled)
                                        VALUES (
                                        '$name', 'RemoteHost', '$sensor', '$port', '', '', '2.2.10', 'GPL only', '200704181215', '100', '0', 'R', '', 'admin', NULL , 'A', '$enabled')");
            }
            if (!$rs = & $conn->Execute($query)) {
                print $conn->ErrorMsg();
            }
        }
    }
    function set_properties($conn, $sensor, $has_nagios, $has_ntop, $has_vuln_scanner, $has_kismet) {
        $sql = "insert into sensor_properties (ip,version,has_nagios,has_ntop,has_vuln_scanner,has_kismet)
                values (?,?,?,?,?,?) on duplicate key update has_nagios = ?, has_ntop = ?, 
                has_vuln_scanner = ?, has_kismet = ?";
        $params = array(
            $sensor,"",
            $has_nagios,
            $has_ntop,
            $has_vuln_scanner,
            $has_kismet,
            $has_nagios,
            $has_ntop,
            $has_vuln_scanner,
            $has_kismet
        );
        if (!$conn->Execute($sql, $params)) {
            die($conn->ErrorMsg());
        }
    }
    function delete_properties($conn, $ip) {
        $sql = "delete from sensor_properties where ip=?";
        if (!$conn->Execute($sql, array($ip))) {
            die($conn->ErrorMsg());
        }
    }
    // order by version by default
    function get_list_by_version($conn,$order,$limit) {
        $list = array();
        $query = OssimQuery("SELECT SQL_CALC_FOUND_ROWS s.*,i.version FROM sensor s, sensor_properties i WHERE s.ip=i.ip ORDER BY i.$order $limit");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $foundrows = 0;
            if (!$rf = & $conn->Execute("SELECT FOUND_ROWS() as total")) print $conn->ErrorMsg();
            else $foundrows = $rf->fields["total"];
            while (!$rs->EOF) {
                if (Session::hostAllowed($conn, $rs->fields["ip"])) {
                    $list[] = new Sensor($rs->fields["name"], $rs->fields["ip"], $rs->fields["priority"], $rs->fields["port"], $rs->fields["version"], $rs->fields["connect"], $rs->fields["descr"], $foundrows);
                }
                $rs->MoveNext();
            }
        }
        return $list;
    }
    // use in session/newuserform and when required independency of Host/Net
    function get_all($conn, $order = "ORDER BY name ASC") {
        // User sensor filtering
		$sensor_where = "";
		if (Session::allowedSensors() != "") {
			$user_sensors = explode(",",Session::allowedSensors());
			$sensor_str = "";
			foreach ($user_sensors as $user_sensor) if ($user_sensor != "")
				$sensor_str .= (($sensor_str != "") ? "," : "")."'".$user_sensor."'";
			if ($sensor_str == "") $sensor_str = "0";
			$sensor_where = " WHERE ip in (" . $sensor_str . ")";
		}
		$list = array();
        $sql = "SELECT * FROM sensor$sensor_where $order";
        if (!$rs = $conn->Execute($sql)) {
            die($conn->ErrorMsg());
        }
        while (!$rs->EOF) {
            $vrsnqry = OssimQuery("SELECT version FROM sensor_properties WHERE ip='" . $rs->fields["ip"] . "'");
            $version = ($rv = & $conn->Execute($vrsnqry)) ? $version = $rv->fields["version"] : "unknown";
            $list[] = new Sensor($rs->fields["name"], $rs->fields["ip"], $rs->fields["priority"], $rs->fields["port"], $rs->fields["tzone"], $version, $rs->fields["connect"], $rs->fields["descr"]);
            $rs->MoveNext();
        }
        return $list;
    }
    function get_count($conn) {
        $num = 0;
        $sql = "SELECT count(*) as count FROM sensor;";
        if (!$rs = $conn->Execute($sql)) {
            die($conn->ErrorMsg());
        }
        if (!$rs->EOF) {
            $num = $rs->fields["count"];
        }
        return $num;
    }
    function check_plugin_rel($conn, $sensor, $plugin_id) {
        $plugin_id = intval($plugin_id);
        $has_plugin = false;
        $sql = "SELECT count(*) as count
                FROM net,net_group_scan,net_group_reference,net_sensor_reference,sensor
                WHERE net.name = net_group_reference.net_name AND
                      net_group_scan.plugin_id = ? AND
                      net_sensor_reference.net_name = net_group_reference.net_name AND
                      sensor.name = net_sensor_reference.sensor_name AND
                      net_group_scan.net_group_name = net_group_reference.net_group_name AND
                      sensor.ip = ?";
        $params = array(
            $plugin_id,
            $sensor
        );
        if (!$rs = $conn->Execute($sql, $params)) {
            die($conn->ErrorMsg());
        }
        if (!$rs->EOF) {
            if ($rs->fields["count"] > 0) {
                $has_plugin = true;
            }
        }
        $sql = "SELECT count(*) as count
                FROM net,net_scan,net_sensor_reference,sensor
                WHERE net.name = net_scan.net_name AND
                      net_scan.plugin_id = ? AND 
                      net_sensor_reference.net_name = net_scan.net_name AND
                      sensor.name = net_sensor_reference.sensor_name AND
                      sensor.ip = ?";
        $params = array(
            $plugin_id,
            $sensor
        );
        if (!$rs = $conn->Execute($sql, $params)) {
            die($conn->ErrorMsg());
        }
        if (!$rs->EOF) {
            if ($rs->fields["count"] > 0) {
                $has_plugin = true;
            }
        }
        $sql = "SELECT count(*) as count
                FROM host_scan,host,sensor,host_sensor_reference
                WHERE plugin_id = ? AND
                      host_sensor_reference.sensor_name = sensor.name AND
                      host_sensor_reference.host_ip = inet_ntoa(host_scan.host_ip) AND
                      host.ip = inet_ntoa(host_scan.host_ip) AND
                      sensor.ip = ?";
        if (!$rs = $conn->Execute($sql, $params)) {
            die($conn->ErrorMsg());
        }
        if (!$rs->EOF) {
            if ($rs->fields["count"] > 0) {
                $has_plugin = true;
            }
        }
        return $has_plugin;
    }
    function insert($conn, $name, $ip, $priority, $port, $tzone, $descr) {
        $sql = "INSERT INTO sensor (name, ip, priority, port, tzone, connect, descr)
                VALUES (?, ?, ?, ?, ?, 0, ?)";
        $params = array(
            $name,
            $ip,
            $priority,
            $port,
            $tzone,
            $descr
        );
        if (!$conn->Execute($sql, $params)) {
            die($conn->ErrorMsg());
        }
        $sql = "INSERT INTO sensor_stats (name) VALUES (?)";
        if (!$conn->Execute($sql, array(
            $name
        ))) {
            die($conn->ErrorMsg());
        }
        $infolog = array(
            $name,
            "[" . $ip . "]"
        );
        Log_action::log(33, $infolog);
        WebIndicator::set_on("Reload_sensors");
        WebIndicator::set_on("ReloadPolicy");
    }
    function update($conn, $name, $ip, $priority, $port, $tzone, $descr) {
        $sql = "UPDATE sensor SET name = ?, ip = ?, 
                priority = ?, port = ?, tzone = ?, descr = ? 
                WHERE name = ?";
        $params = array(
            $name,
            $ip,
            $priority,
            $port,
            $tzone,
            $descr,
            $name
        );
        if (!$conn->Execute($sql, $params)) {
            die($conn->ErrorMsg());
        }
        $sql = "UPDATE sensor_stats SET name = ? WHERE name = ?";
        if (!$conn->Execute($sql, array(
            $name,
            $name
        ))) {
            die($conn->ErrorMsg());
        }
        $infolog = array(
            $name,
            "[" . $ip . "]"
        );
        Log_action::log(34, $infolog);
        WebIndicator::set_on("Reload_sensors");
        WebIndicator::set_on("ReloadPolicy");
    }
    function delete($conn, $name) {
        $sql = "SELECT ip FROM sensor WHERE name = ?";
        if (!$rs = $conn->Execute($sql, array($name))) {
            die($conn->ErrorMsg());
        }
		$ip = $rs->fields["ip"];
        $sql = "DELETE FROM sensor WHERE name = ?";
        if (!$conn->Execute($sql, array(
            $name
        ))) {
            die($conn->ErrorMsg());
        }
        $sql = "DELETE FROM sensor_stats WHERE name = ?";
        if (!$conn->Execute($sql, array(
            $name
        ))) {
            die($conn->ErrorMsg());
        }
        $sql = "DELETE FROM vuln_nessus_servers WHERE name = ?";
        if (!$conn->Execute($sql, array(
            $name
        ))) {
            die($conn->ErrorMsg());
        }
        Sensor::delete_properties($conn, $ip);
        Net_sensor_reference::check_orphan($conn);
        Host_sensor_reference::check_orphan($conn);
        $infolog = array(
            $name
        );
        Log_action::log(35, $infolog);
        WebIndicator::set_on("Reload_sensors");
        // clean cache sensors
        require_once "classes/Util.inc";
        Util::clean_json_cache_files("sensors");
        //
        WebIndicator::set_on("ReloadPolicy");
    }
    /*
    * Given a host ip, return its sensor ip asociated.
    *
    * Look at:
    *  1st) host table
    *  2nd) net table
    *  3rd) /etc/ossim.conf
    */
    function get_sensor_link($conn, $host_ip) {
        require_once "classes/Net.inc";
        require_once "ossim_conf.inc";
        $conf = $GLOBALS["CONF"];
        $sensor_ip = '';
        /* read from host table */
        $query = "SELECT sensor.ip FROM host_sensor_reference, sensor 
                  WHERE host_sensor_reference.host_ip = ? AND 
                        sensor.name = host_sensor_reference.sensor_name 
                  ORDER BY sensor.priority DESC LIMIT 1";
        if (!$rs = & $conn->Execute($query, array(
            $host_ip
        ))) {
            print $conn->ErrorMsg();
        } elseif (!$rs->EOF) {
            if (!$conf->get_conf("use_ntop_rewrite")) {
                list($protocol, $ip, $port) = split(':', $conf->get_conf("ntop_link"));
                $sensor_ip = "$protocol://" . $rs->fields["ip"] . ":$port";
            } else { //if use_ntop_rewrite is enabled
                $protocol = "http";
                if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") $protocol = "https";
                $sensor_ip = "$protocol://" . $_SERVER['SERVER_NAME'] . "/ntop-" . $rs->fields["ip"];
            }
            return $sensor_ip;
        }
        /* read from network table */
        /*if ($net_list = Net::get_list($conn)) {
            foreach($net_list as $net) {*/
            	//if (Net::is_ip_in_cache_cidr($conn, $host_ip, $net->get_ips())) {
        		if (count($cidrs = Net::get_cache_cidr($conn, $host_ip)) > 0) {
                    $netname = Net::get_name_by_ip($conn, $cidrs[0]);
        			$query = "SELECT ip
                              FROM sensor, net_sensor_reference 
                              WHERE name = sensor_name AND net_name = ?";
                    if (!$rs = & $conn->Execute($query, array(
                        $netname
                    ))) {
                        print $conn->ErrorMsg();
                    } elseif (!$rs->EOF) {
                        if (!$conf->get_conf("use_ntop_rewrite")) {
                            list($protocol, $ip, $port) = split(':', $conf->get_conf("ntop_link"));
                            $sensor_ip = "$protocol://" . $rs->fields["ip"] . ":$port";
                        } else {
                            $protocol = "http";
                            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") $protocol = "https";
                            $sensor_ip = "$protocol://" . $_SERVER['SERVER_NAME'] . "/ntop-" . $rs->fields["ip"];
                        }
                        return $sensor_ip;
                    }
                }
            /*}
        }*/
        /* read from /etc/ossim.conf */
        if ($sensor_ip == '') {
            $sensor_ip = $conf->get_conf("ntop_link");
        }
        
        return $sensor_ip;
    }
    /*
    * Given a host ip, return its main sensor's interface.
    *
    * Look at:
    *  1st) host table
    *  2nd) net table
    *  3rd) /etc/ossim.conf
    */
    function get_sensor_interface($conn, $host_ip) {
        require_once "classes/Net.inc";
        require_once "ossim_conf.inc";
        $conf = $GLOBALS["CONF"];
        $sensor_interface_name = '';
        /* read from host table */
        $query = "SELECT sensor_interfaces.interface
                  FROM host_sensor_reference, sensor, sensor_interfaces
                  WHERE host_sensor_reference.host_ip = ? AND 
                        sensor.name = host_sensor_reference.sensor_name AND
                        sensor_interfaces.main = 1
                  ORDER BY sensor.priority DESC LIMIT 1";
        if (!$rs = & $conn->Execute($query, array(
            $host_ip
        ))) {
            print $conn->ErrorMsg();
        } elseif (!$rs->EOF) {
            $sensor_interface_name = $rs->fields["interface"];
            return $sensor_interface_name;
        }
        /* read from network table */
        if ($net_list = Net::get_list($conn)) {
            foreach($net_list as $net) {
                if (Net::is_ip_in_cache_cidr($conn, $host_ip, $net->get_ips())) {
                    $query = "SELECT sensor_interfaces.interface
                              FROM sensor, net_sensor_reference, sensor_interfaces
                              WHERE sensor_interfaces.name = sensor.name AND
                                    net_name = ? AND
                                    sensor_interfaces.main = 1";
                    if (!$rs = & $conn->Execute($query, array(
                        $net->get_name()
                    ))) {
                        print $conn->ErrorMsg();
                    } elseif (!$rs->EOF) {
                        $sensor_interface_name = $rs->fields["interface"];
                        return $sensor_interface_name;
                    }
                }
            }
        }
        /* read from /etc/ossim.conf */
        if ($sensor_interface_name == '') {
            $sensor_interface_name = $conf->get_conf("ossim_interface");
        }
        return $sensor_interface_name;
    }
	
	function sensor_exists($conn,$sensor) {
		$sensor_list = Sensor::get_all($conn, "ORDER BY name");
		
		foreach ($sensor_list as $s) {
			if ($s->get_name() == $sensor || $s->get_ip() == $sensor) return true;
		}
		return false;
	}
    function get_sensor_ip($conn, $name = "") {
        $query = OssimQuery("SELECT ip FROM sensor WHERE name=?");
                if (!$rs = & $conn->Execute($query,array($name))) {
            print $conn->ErrorMsg();
        } elseif (!$rs->EOF) {
            return $rs->fields["ip"];
        }
        return $name;
    }
    function get_sensor_name($conn, $ip = "") {
        $query = OssimQuery("SELECT name FROM sensor WHERE ip=? ORDER BY priority DESC");
                if (!$rs = & $conn->Execute($query,array($ip))) {
            print $conn->ErrorMsg();
        } elseif (!$rs->EOF) {
            error_log($rs->fields["name"],3,"/tmp/kk.log");
            return $rs->fields["name"];
        }
        return $ip;
    }
    function get_assets($conn, $name) {
        $list = array();
        $list["host"]= array();
        $list["net"]= array();
        $list["hgroup"]= array();
        $list["ngroup"]= array();
        
        // add hosts
        $sql = "SELECT hsr.host_ip, h.hostname FROM host h, host_sensor_reference hsr WHERE h.ip=hsr.host_ip AND hsr.sensor_name='$name'";
        if (!$rs = $conn->Execute($sql)) {
            die($conn->ErrorMsg());
        }
        while (!$rs->EOF) {
            $list["host"][$rs->fields["hostname"]] = $rs->fields["host_ip"];
            $rs->MoveNext();
        }
        
        // add nets
        $sql = "SELECT n.ips, n.name FROM net n, net_sensor_reference nsr WHERE n.name=nsr.net_name AND nsr.sensor_name='$name'";
        if (!$rs = $conn->Execute($sql)) {
            die($conn->ErrorMsg());
        }
        while (!$rs->EOF) {
            $list["net"][$rs->fields["name"]] = $rs->fields["ips"];
            $rs->MoveNext();
        }
        
        // add host groups
        $sql = "SELECT DISTINCT hgr.host_group_name FROM host_group_reference hgr, host_group_sensor_reference hgsr WHERE hgr.host_group_name=hgsr.group_name AND hgsr.sensor_name='$name'";

        if (!$rs = $conn->Execute($sql)) {
            die($conn->ErrorMsg());
        }
        while (!$rs->EOF) {
            $list["hgroup"][$rs->fields["host_group_name"]] = 1;
            $rs->MoveNext();
        }
        
        // add net groups
        $net_groups = array();
        $sql = "SELECT net_group_name, net_name FROM net_group_reference ORDER BY net_group_name";
        if (!$rs = $conn->Execute($sql)) {
            die($conn->ErrorMsg());
        }
        while (!$rs->EOF) {
            $net_groups[$rs->fields["net_group_name"]][]= $rs->fields["net_name"];
            $rs->MoveNext();
        }
        
        foreach($net_groups as $ng_name => $net_group) {
            $allowed = 1;
            foreach ($net_group as $n => $net_name){
                if (!array_key_exists($net_name,$list["net"])) {
                    $allowed = 0; break;
                }
            }
            if ($allowed) $list["ngroup"][$ng_name] = 1;
        }
        return $list;
    }
}
?>
