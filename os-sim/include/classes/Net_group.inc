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
* - Net_group()
* - get_name()
* - get_threshold_c()
* - get_threshold_a()
* - get_rrd_profile()
* - get_descr()
* - get_foundrows()
* - get_networks()
* - get_list()
* - get_compromise()
* - get_attack()
* - get_sensors()
* - insert()
* - update()
* - delete()
* - netthresh_c()
* - netthresh_a()
* - isNetInGroup()
* - enable_nessus()
* - disable_nessus()
* - isNetInAnyGroup()
* - check_orphan()
* Classes list:
* - Net_group
*/
require_once 'classes/Net_group_reference.inc';
require_once 'classes/Net_sensor_reference.inc';
require_once 'ossim_conf.inc';
require_once 'classes/Log_action.inc';
require_once 'classes/Session.inc';
require_once ("ossim_db.inc");
class Net_group {
    var $name;
    var $threshold_c;
    var $threshold_a;
    var $rrd_profile;
    var $descr;
    function Net_group($name, $threshold_c, $threshold_a, $rrd_profile, $descr, $foundrows = 0) {
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
    function get_networks($conn, $name = "", $plugin = "") {
        if ($name != "") {
            return Net_group_reference::get_list($conn, $name, $plugin);
        } else {
            return Net_group_reference::get_list($conn, $this->name, $plugin);
        }
    }
    /*
    function get_list($conn, $args = "") {
        $iffoundrows = preg_match("/LIMIT/", $args) ? true : false;
        $select = ($iffoundrows) ? "SQL_CALC_FOUND_ROWS *" : "*";
        $query = OssimQuery("SELECT $select FROM net_group $args");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $foundrows = 0;
            if ($iffoundrows) {
                if (!$rf = & $conn->Execute("SELECT FOUND_ROWS() as total")) print $conn->ErrorMsg();
                else $foundrows = $rf->fields["total"];
            }
            $list = array();
            while (!$rs->EOF) {
                if (Session::groupAllowed($conn, stripslashes($rs->fields["name"]))) {
                    $list[] = new Net_group(stripslashes($rs->fields["name"]) , $rs->fields["threshold_c"], $rs->fields["threshold_a"], stripslashes($rs->fields["rrd_profile"]) , stripslashes($rs->fields["descr"]) , $foundrows);
                }
                $rs->MoveNext();
            }
        }
        return $list;
    }
    */
	function get_list($conn, $args = "", $order = "ORDER BY name") {
        $args = preg_replace("/^\s*where /i","",$args);
		$iffoundrows = preg_match("/LIMIT/", $order) ? true : false;
        $select = ($iffoundrows) ? "SQL_CALC_FOUND_ROWS *" : "*";
        $networks = "";
        $all_nets = Net::get_list($conn);
        foreach ($all_nets as $net) {
        	$networks .= ($networks != "") ? ",'".$net->get_name()."'" : "'".$net->get_name()."'";
        }
        $query = OssimQuery("SELECT $select FROM net_group g,net_group_reference r WHERE $args".(($args != "") ? " AND" : "")." g.name=r.net_group_name AND r.net_name in ($networks) $order");
        //echo $query."<br>";
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $foundrows = 0;
            if ($iffoundrows) {
                if (!$rf = & $conn->Execute("SELECT FOUND_ROWS() as total")) print $conn->ErrorMsg();
                else $foundrows = $rf->fields["total"];
            }
            $list = array();
            while (!$rs->EOF) {
                $list[] = new Net_group(stripslashes($rs->fields["name"]) , $rs->fields["threshold_c"], $rs->fields["threshold_a"], stripslashes($rs->fields["rrd_profile"]) , stripslashes($rs->fields["descr"]) , $foundrows);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    /*
    function get_list_pag($conn, $args = "") {
        $list = array(); $i=0;
        $from = 0; $max = 999999999;
        if (preg_match("/LIMIT (\d+),\s*(\d+)/", $args, $found)) {
            $args = preg_replace("/LIMIT \d+,\s*\d+/","",$args);
            $from = $found[1]; $max = $found[2];
        }
        $query = OssimQuery("SELECT * FROM net_group $args");
        
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $foundrows = $rs->RecordCount();
            while (!$rs->EOF) {
                if (Session::groupAllowed($conn, stripslashes($rs->fields["name"]))) {
                    if ($i>=$from && $i<$max+$from) {
                        $list[] = new Net_group(stripslashes($rs->fields["name"]) , $rs->fields["threshold_c"], $rs->fields["threshold_a"], stripslashes($rs->fields["rrd_profile"]) , stripslashes($rs->fields["descr"]) , $foundrows);
                    }
                    $i++;
                }
                $rs->MoveNext();
            }
        }
        return array($list,$i);
    }
    */
    function get_compromise($conn, $name) {
        $compromise = 0;
        $networks = Net_group::get_networks($conn, $name);
        foreach($networks as $network) {
            $name = $network->get_net_name();
            $net_list = Net_qualification::get_list($conn, "WHERE net_name = '$name'");
            if (is_array($net_list)) {
                foreach($net_list as $net) {
                    $compromise+= $net->get_compromise();
                }
            }
        }
        return $compromise;
    }
    function get_attack($conn, $name) {
        $attack = 0;
        $networks = Net_group::get_networks($conn, $name);
        foreach($networks as $network) {
            $name = $network->get_net_name();
            $net_list = Net_qualification::get_list($conn, "WHERE net_name = '$name'");
            if (is_array($net_list)) {
                foreach($net_list as $net) {
                    $attack+= $net->get_attack();
                }
            }
        }
        return $attack;
    }
    function get_sensors($conn, $name) {
        $sensors = array();
        $networks = Net_group::get_networks($conn, $name);
        foreach($networks as $network) {
            $name = $network->get_net_name();
            $sensor_list = Net_sensor_reference::get_list($conn, $name);
            if (is_array($sensor_list)) {
                foreach($sensor_list as $sensor) {
                    if (!in_array($sensor->get_sensor_name() , $sensors)) array_push($sensors, $sensor->get_sensor_name());
                }
            }
        }
        return $sensors;
    }
    function insert($conn, $name, $threshold_c, $threshold_a, $rrd_profile, $nets, $descr) {
        settype($threshold_c, "int");
        settype($threshold_a, "int");
        $rrd_profile = htmlentities($rrd_profile);
        $name = str_replace(" ", "_", $name);
        $sql = "INSERT INTO net_group ( name, threshold_c, threshold_a, rrd_profile, descr) VALUES (?, ?, ?, ?,?)";
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
        Net_group_reference::insert($conn, $name, $nets);
        $infolog = array(
            $name
        );
        Log_action::log(30, $infolog);
    }
    function update($conn, $name, $threshold_c, $threshold_a, $rrd_profile, $nets, $descr) {
        Net_group::delete($conn, $name);
        Net_group::insert($conn, $name, $threshold_c, $threshold_a, $rrd_profile, $nets, $descr);
        $infolog = array(
            $name
        );
        Log_action::log(31, $infolog);
    }
    function delete($conn, $name) {
        $sql = "DELETE FROM net_group WHERE name = ?";
        $params = array(
            $name
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        $name = stripslashes($name);
        Net_group_reference::delete($conn, $name);
        $infolog = array(
            $name
        );
        Log_action::log(32, $infolog);
    }
	function can_delete($conn, $name) {
		$sql = "SELECT count(*) as num FROM policy_net_group_reference WHERE net_group_name = ?";
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
    function netthresh_c($conn, $name) {
        $sql = "SELECT * FROM net_group WHERE name = ?";
        $params = array(
            $name
        );
        if (!$rs = $conn->Execute($sql, $params)) {
            print 'error: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        if (!$rs->EOF) return $rs->fields["threshold_c"];
        else {
            /* network group is not in db, return default threshold */
            $conf = $GLOBALS["CONF"];
            return $conf->get_conf("threshold");
        }
    }
    function netthresh_a($conn, $name) {
        $sql = "SELECT * FROM net_group WHERE name = ?";
        $params = array(
            $name
        );
        if (!$rs = $conn->Execute($sql, $params)) {
            print 'error: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        if (!$rs->EOF) return $rs->fields["threshold_a"];
        else {
            /* network group is not in db, return default threshold */
            $conf = $GLOBALS["CONF"];
            return $conf->get_conf("threshold");
        }
    }
    function isNetInGroup($conn, $group, $net) {
        $sql = "SELECT * from net_group_reference where net_group_name = ? AND net_name = ?";
        $params = array(
            $group,
            $net
        );
        if ($rs = & $conn->Execute($sql, $params) && (!$rs->EOF)) {
            return true;
        }
        return false;
    }
    function enable_nessus($conn, $net_group_name) {
        $sql = "INSERT INTO net_group_scan (net_group_name, plugin_id, plugin_sid) VALUES (?, 3001, 0)";
        $params = array(
            $net_group_name
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function disable_nessus($conn, $net_group_name) {
        $sql = "DELETE FROM net_group_scan WHERE net_group_name = ? AND plugin_id = 3001";
        $params = array(
            $net_group_name
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function isNetInAnyGroup($conn, $net) {
        $sql = "SELECT * from net_group_reference where net_name = ?";
        $params = array(
            $net
        );
        if ($rs = & $conn->Execute($sql, $params) && (!$rs->EOF)) {
            return true;
        }
        return false;
    }
    function check_orphan($conn) {
        $sql = "SELECT * FROM net_sensor_reference LEFT JOIN  sensor ON
                 sensor.name = net_sensor_reference.sensor_name WHERE name IS NULL";
        if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                Net_sensor_rerefence::delete($conn, $rs->fields["event_id"]);
                $rs->MoveNext();
            }
        }
    }
}
?>
