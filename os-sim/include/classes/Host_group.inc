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
* - Host_group()
* - get_name()
* - get_threshold_c()
* - get_threshold_a()
* - get_rrd_profile()
* - get_descr()
* - get_foundrows()
* - get_hosts()
* - get_list()
* - get_compromise()
* - get_attack()
* - get_sensors()
* - insert()
* - update()
* - delete()
* - hostthresh_c()
* - hostthresh_a()
* - isHostInOtherGroup()
* - isHostInGroup()
* - isHostInGroupWithPlugin()
* - enable_nessus()
* - disable_nessus()
* - isHostInAnyGroup()
* - check_orphan()
* Classes list:
* - Host_group
*/
require_once 'classes/Host_group_reference.inc';
require_once 'classes/Host_sensor_reference.inc';
require_once 'classes/Host_group_sensor_reference.inc';
require_once 'ossim_conf.inc';
require_once 'classes/Log_action.inc';
require_once 'classes/Session.inc';
require_once ("ossim_db.inc");
class Host_group {
    var $name;
    var $threshold_c;
    var $threshold_a;
    var $rrd_profile;
    var $descr;
    function Host_group($name, $threshold_c, $threshold_a, $rrd_profile, $descr, $foundrows = 0) {
        $this->name = stripslashes($name);
        $this->threshold_c = $threshold_c;
        $this->threshold_a = $threshold_a;
        $this->rrd_profile = $rrd_profile;
        $this->descr = $descr;
        $this->foundrows = $foundrows;
    }
    function get_name() {
        return $this->name;
    }
    function get_threshold_c() {
        return $this->threshold_c;
    }
    function get_threshold_a() {
        return $this->threshold_a;
    }
    function get_rrd_profile() {
        return $this->rrd_profile;
    }
    function get_descr() {
        return $this->descr;
    }
    function get_foundrows() {
        return $this->foundrows;
    }
    function get_hosts($conn, $name = "", $plugin = "") {
        if ($name != "") {
            return Host_group_reference::get_list($conn, $name, $plugin);
        } else {
            return Host_group_reference::get_list($conn, $this->name, $plugin);
        }
    }
    function get_list($conn, $args = "") {
        $iffoundrows = preg_match("/LIMIT/", $args) ? true : false;
        $select = ($iffoundrows) ? "SQL_CALC_FOUND_ROWS host_group.*" : "host_group.*";
        $query = OssimQuery("SELECT DISTINCT $select FROM host_group $args");

        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            $foundrows = 0;
            if ($iffoundrows) {
                if (!$rf = & $conn->Execute("SELECT FOUND_ROWS() as total")) print $conn->ErrorMsg();
                else $foundrows = $rf->fields["total"];
            }
            while (!$rs->EOF) {
                if (Session::groupHostAllowed($conn, stripslashes($rs->fields["name"]))) {
					$list[] = new Host_group(stripslashes($rs->fields["name"]) , $rs->fields["threshold_c"], $rs->fields["threshold_a"], stripslashes($rs->fields["rrd_profile"]) , stripslashes($rs->fields["descr"]) , $foundrows);
                }
                $rs->MoveNext();
            }
        }
        return $list;
    }
	function get_list2($conn, $args = "") {
		include_once("classes/Host.inc");
		
		$host_where = " AND r.host_ip in (";
		$all_host = Host::get_list_pag($conn);
		foreach ($all_host as $host) $host_where .= "'".$host->get_ip()."',";
		$host_where = preg_replace("/,$/",")",$host_where);
		
		$select = "SELECT SQL_CALC_FOUND_ROWS g.* FROM host_group g,host_group_reference r WHERE g.name=r.host_group_name $host_where";
        $query = OssimQuery($select);

        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            $foundrows = 0;
            if ($iffoundrows) {
                if (!$rf = & $conn->Execute("SELECT FOUND_ROWS() as total")) print $conn->ErrorMsg();
                else $foundrows = $rf->fields["total"];
            }
            while (!$rs->EOF) {
                $list[] = new Host_group(stripslashes($rs->fields["name"]) , $rs->fields["threshold_c"], $rs->fields["threshold_a"], stripslashes($rs->fields["rrd_profile"]) , stripslashes($rs->fields["descr"]) , $foundrows);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function get_list_pag($conn, $where = "", $order = "") {
        // Only filter by perms if needed
        $host_where = "";
        if (Session::allowedSensors() != "" || Session::allowedNets() != "") {
	    	include_once("classes/Host.inc");
			$host_where = " AND r.host_ip in (";
			$all_host = Host::get_list_pag($conn);
			foreach ($all_host as $host) {
				$host_where .= "'".$host->get_ip()."',";
			}
			$host_where = preg_replace("/,$/",")",$host_where);
        }
		
		$select = "SELECT DISTINCT SQL_CALC_FOUND_ROWS g.* FROM host_group g,host_group_reference r WHERE g.name=r.host_group_name $host_where$where $order";
        //echo $select;
		$query = OssimQuery($select);

        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            $foundrows = 0;
            if (!$rf = & $conn->Execute("SELECT FOUND_ROWS() as total")) print $conn->ErrorMsg();
            else $foundrows = $rf->fields["total"];
            
            while (!$rs->EOF) {
                $list[] = new Host_group(stripslashes($rs->fields["name"]) , $rs->fields["threshold_c"], $rs->fields["threshold_a"], stripslashes($rs->fields["rrd_profile"]) , stripslashes($rs->fields["descr"]) , $foundrows);
                $rs->MoveNext();
            }
        }
        return array($list,$foundrows);
    }
    function get_compromise($conn, $name) {
        $compromise = 0;
        $hosts = Host_group::get_hosts($conn, $name);
        foreach($hosts as $host) {
            $name = $host->get_host_name();
            $host_list = Host_qualification::get_list($conn, "WHERE host_name = '$name'");
            if (is_array($host_list)) {
                foreach($host_list as $host) {
                    $compromise+= $host->get_compromise();
                }
            }
        }
        return $compromise;
    }
    function get_attack($conn, $name) {
        $attack = 0;
        $hosts = Host_group::get_hosts($conn, $name);
        foreach($hosts as $host) {
            $name = $host->get_host_name();
            $host_list = Host_qualification::get_list($conn, "WHERE host_name = '$name'");
            if (is_array($host_list)) {
                foreach($host_list as $host) {
                    $attack+= $host->get_attack();
                }
            }
        }
        return $attack;
    }

    function get_sensors($conn, $name = "") {
        if ($name != "") {
            return Host_group_sensor_reference::get_list($conn, $name);
        } else {
            return Host_group_sensor_reference::get_list($conn, $this->name);
        }
    }
    
    function get_related_sensors($conn, $name){
	require_once("classes/Sensor.inc");
    	$sensors = array();
	$related = Host_group_sensor_reference::get_list($conn, $name);
    	foreach($related as $rl) {
		$sensors[Sensor::get_sensor_ip($conn,$rl->get_sensor_name())]++;
    	}
    	return array_keys($sensors);
    }

    function insert($conn, $name, $threshold_c, $threshold_a, $rrd_profile, $sensors, $hosts, $descr) {
        settype($threshold_c, "int");
        settype($threshold_a, "int");
        $rrd_profile = htmlentities($rrd_profile);
        $name = str_replace(" ", "_", $name);
        $sql = "INSERT INTO host_group ( name, threshold_c, threshold_a, rrd_profile, descr) VALUES (?, ?, ?, ?,?)";
        $params = array(
            $name,
            $threshold_c,
            $threshold_a,
            $rrd_profile,
            $descr
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
        }
        Host_group_reference::insert($conn, $name, $hosts);
        Host_group_sensor_reference::insert($conn, $name, $sensors);
        $infolog = array(
            $name
        );
        Log_action::log(73, $infolog);
    }
    function update($conn, $name, $threshold_c, $threshold_a, $rrd_profile, $sensors, $hosts, $descr) {
        Host_group::delete($conn, $name);
        Host_group::insert($conn, $name, $threshold_c, $threshold_a, $rrd_profile, $sensors, $hosts, $descr);
        $infolog = array(
            $name
        );
        Log_action::log(74, $infolog);
    }
    function delete($conn, $name) {
        $sql = "DELETE FROM host_group WHERE name = ?";
        $params = array(
            $name
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        $name = stripslashes($name);
        Host_group_reference::delete($conn, $name);
        Host_group_sensor_reference::delete($conn, $name);
        $infolog = array(
            $name
        );
        Log_action::log(75, $infolog);
    }
	function can_delete($conn, $name) {
		$sql = "SELECT count(*) as num FROM policy_host_group_reference WHERE host_group_name = ?";
		$params = array(
            $name
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
		if ($rs->fields['num'] == '0') return 1;
		else return 0;
	}
    function hostthresh_c($conn, $name) {
        $sql = "SELECT * FROM host_group WHERE name = ?";
        $params = array(
            $name
        );
        if (!$rs = $conn->Execute($sql, $params)) {
            print 'error: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        if (!$rs->EOF) return $rs->fields["threshold_c"];
        else {
            /* host group is not in db, return default threshold */
            $conf = $GLOBALS["CONF"];
            return $conf->get_conf("threshold");
        }
    }
    function hostthresh_a($conn, $name) {
        $sql = "SELECT * FROM host_group WHERE name = ?";
        $params = array(
            $name
        );
        if (!$rs = $conn->Execute($sql, $params)) {
            print 'error: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        if (!$rs->EOF) return $rs->fields["threshold_a"];
        else {
            /* host group is not in db, return default threshold */
            $conf = $GLOBALS["CONF"];
            return $conf->get_conf("threshold");
        }
    }
    function isHostInOtherGroup($conn, $group, $host) {
        $sql = "SELECT * from host_group_reference where host_group_name <> ? AND host_name = ?";
        $params = array(
            $group,
            $host
        );
        if ($rs = & $conn->Execute($sql, $params) && (!$rs->EOF)) {
            return true;
        }
        return false;
    }
    function isHostInGroup($conn, $group, $host) {
        $sql = "SELECT * from host_group_reference where host_group_name = ? AND host_name = ?";
        $params = array(
            $group,
            $host
        );
        if ($rs = & $conn->Execute($sql, $params) && (!$rs->EOF)) {
            return true;
        }
        return false;
    }
    function isHostInGroupWithPlugin($conn, $host, $plugin) {
        $sql = "SELECT * FROM host_group_scan h, host_group_reference hg where h.host_group_name=hg.host_group_name and h.plugin_id=? and hg.host_ip=?";
        $params = array(
            $plugin,
            $host
        );
        if ($rs = & $conn->Execute($sql, $params) && (!$rs->EOF)) {
            return true;
        }
        return false;
    }
    function enable_nessus($conn, $host_group_name) {
        $sql = "INSERT INTO host_group_scan (host_group_name, plugin_id, plugin_sid) VALUES (?, 3001, 0)";
        $params = array(
            $host_group_name
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function disable_nessus($conn, $host_group_name) {
        $sql = "DELETE FROM host_group_scan WHERE host_group_name = ? AND plugin_id = 3001";
        $params = array(
            $host_group_name
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function isHostInAnyGroup($conn, $host) {
        $sql = "SELECT * from host_group_reference where host_name = ?";
        $params = array(
            $host
        );
        if ($rs = & $conn->Execute($sql, $params) && (!$rs->EOF)) {
            return true;
        }
        return false;
    }
    function check_orphan($conn) {
        $sql = "SELECT * FROM host_sensor_reference LEFT JOIN  sensor ON
                 sensor.name = host_sensor_reference.sensor_name WHERE name IS NULL";
        if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                Host_sensor_rerefence::delete($conn, $rs->fields["event_id"]);
                $rs->MoveNext();
            }
        }
    }
}
?>
