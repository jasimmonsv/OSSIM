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
* - Sensor_interfaces()
* - get_sensor()
* - get_interface()
* - get_name()
* - get_main()
* - get_list()
* - insert_interfaces()
* - update_interfaces()
* - delete_interfaces()
* Classes list:
* - Sensor_interfaces
*/
require_once ("ossim_db.inc");
class Sensor_interfaces {
    var $sensor;
    var $interface;
    var $name;
    var $main;
    function Sensor_interfaces($sensor, $interface, $name, $main) {
        $this->sensor = $sensor;
        $this->interface = $interface;
        $this->name = $name;
        $this->main = $main;
    }
    function get_sensor() {
        return $this->sensor;
    }
    function get_interface() {
        return $this->interface;
    }
    function get_name() {
        return $this->name;
    }
    function get_main() {
        return $this->main;
    }
    function get_list($conn, $sensor) {
        $query = "SELECT * FROM sensor_interfaces where sensor=? ORDER BY main DESC";
        if (!$rs = & $conn->Execute($query, array(
            $sensor
        ))) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list[] = new Sensor_interfaces($rs->fields["sensor"], $rs->fields["interface"], $rs->fields["name"], $rs->fields["main"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert_interfaces($conn, $sensor, $interface, $name, $main) {
        $main = intval($main);
        $sql = "INSERT INTO sensor_interfaces (sensor, interface, name, main) VALUES (?, ?, ?, ?)";
        $params = array(
            $sensor,
            $interface,
            $name,
            $main
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function update_interfaces($conn, $sensor, $interface, $name, $main) {
        $main = intval($main);
        $sql = "UPDATE sensor_interfaces SET name = ?, main = ? WHERE sensor = ? and interface = ?";
        $params = array(
            $name,
            $main,
            $sensor,
            $interface
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error updating: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function delete_interfaces($conn, $sensor, $interface) {
        $sql = "DELETE FROM sensor_interfaces WHERE sensor = ? and interface = ?";
        $params = array(
            $sensor,
            $interface
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
}
?>
