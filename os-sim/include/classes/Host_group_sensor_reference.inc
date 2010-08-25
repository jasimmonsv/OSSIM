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
* - Host_group_sensor_reference()
* - get_group_name()
* - get_sensor_name()
* - get_list()
* - insert()
* - delete()
* - in_host_group_sensor_reference()
* - check_orphan()
* Classes list:
* - Host_group_sensor_reference
*/
require_once ("ossim_db.inc");
class Host_group_sensor_reference {
    var $group_name;
    var $sensor_name;
    function Host_group_sensor_reference($group_name, $sensor_name) {
        $this->group_name = $group_name;
        $this->sensor_name = $sensor_name;
    }
    function get_group_name() {
        return $this->group_name;
    }
    function get_sensor_name() {
        return $this->sensor_name;
    }
    function get_list($conn, $group_name) {
        $sql = "SELECT * FROM host_group_sensor_reference WHERE group_name = ?";
        $params = array(
            $group_name
        );
        $list = array();
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list[] = new Host_group_sensor_reference($rs->fields["group_name"], $rs->fields["sensor_name"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $group_name, $sensors) {
        foreach($sensors as $sensor_name) {
            $sql = "INSERT INTO host_group_sensor_reference VALUES (?, ?)";
            $params = array(
                $group_name,
                $sensor_name
            );
            if ($conn->Execute($sql, $params) === false) {
                print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
                exit;
            }
        }
    }
    function delete($conn, $group_name, $sensor_name = "%") {
        $sql = "DELETE FROM host_group_sensor_reference WHERE group_name = ? AND sensor_name LIKE ?";
        $params = array(
            $group_name,
            $sensor_name
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function in_host_group_sensor_reference($conn, $group_name, $sensor_name) {
        $sql = "SELECT * FROM host_group_sensor_reference WHERE group_name = ? AND sensor_name = ?";
        $params = array(
            $group_name,
            $sensor_name
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        }
        return $rs->fields[0];
    }
    function check_orphan($conn) {
        $sql = "SELECT * FROM host_group_sensor_reference LEFT JOIN sensor ON
                sensor.name = host_group_sensor_reference.sensor_name WHERE descr IS NULL";
        if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                Host_sensor_reference::delete($conn, $rs->fields["group_name"], $rs->fields["sensor_name"]);
                $rs->MoveNext();
            }
        }
    }
}
?>
