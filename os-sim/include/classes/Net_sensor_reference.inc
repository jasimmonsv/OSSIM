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
* - Net_sensor_reference()
* - get_net_name()
* - get_sensor_name()
* - get_list()
* - insert()
* - delete()
* - in_net_sensor_reference()
* - check_orphan()
* Classes list:
* - Net_sensor_reference
*/
require_once ("ossim_db.inc");
class Net_sensor_reference {
    var $net_name;
    var $sensor_name;
    function Net_sensor_reference($net_name, $sensor_name) {
        $this->net_name = $net_name;
        $this->sensor_name = $sensor_name;
    }
    function get_net_name() {
        return $this->net_name;
    }
    function get_sensor_name() {
        return $this->sensor_name;
    }
    function get_list($conn, $net) {
        $query = OssimQuery("SELECT * FROM net_sensor_reference WHERE net_name
        = '$net'");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list[] = new Net_sensor_reference($rs->fields["net_name"], $rs->fields["sensor_name"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function get_list_array($conn, $net_ips) {
        $query = OssimQuery("SELECT n.*,s.ip FROM net_sensor_reference n,sensor s,net ne WHERE n.sensor_name=s.name AND ne.name=n.net_name AND ne.ips = '$net_ips' ORDER BY s.priority DESC,s.name ASC");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list[] = $rs->fields["ip"];
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $net_name, $sensors) {
        foreach($sensors as $sensor_name) {
            $sql = "INSERT INTO net_sensor_reference VALUES (?, ?)";
            $params = array(
                $net_name,
                $sensor_name
            );
            if ($conn->Execute($sql, $params) === false) {
                print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
                exit;
            }
        }
    }
    function delete($conn, $net_name, $sensor_name = "%") {
        $sql = "DELETE FROM net_sensor_reference WHERE net_name = ? AND sensor_name LIKE ?";
        $params = array(
            $net_name,
            $sensor_name
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function in_net_sensor_reference($conn, $net, $sensor_name) {
        $sql = "SELECT * FROM net_sensor_reference WHERE net_name = ? AND sensor_name = ?";
        $params = array(
            $net,
            $sensor_name
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        }
        return $rs->fields[0];
    }
    function check_orphan($conn) {
        $sql = "SELECT * FROM net_sensor_reference LEFT JOIN sensor ON sensor.name
                = net_sensor_reference.sensor_name WHERE name IS NULL";
        if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                Net_sensor_reference::delete($conn, $rs->fields["net_name"], $rs->fields["sensor_name"]);
                $rs->MoveNext();
            }
        }
    }
}
?>
