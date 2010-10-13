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
* - Host_sensor_reference()
* - get_host_ip()
* - get_sensor_name()
* - get_list()
* - insert()
* - delete()
* - in_host_sensor_reference()
* - check_orphan()
* Classes list:
* - Host_sensor_reference
*/
require_once ("ossim_db.inc");
class Host_sensor_reference {
    var $host_ip;
    var $sensor_name;
    function Host_sensor_reference($host_ip, $sensor_name) {
        $this->host_ip = $host_ip;
        $this->sensor_name = $sensor_name;
    }
    function get_host_ip() {
        return $this->host_ip;
    }
    function get_sensor_name() {
        return $this->sensor_name;
    }
    function get_list($conn, $ip) {
	$sql = "SELECT hs.* FROM sensor s, host_sensor_reference hs
		WHERE hs.host_ip = ? AND s.name = hs.sensor_name ORDER BY s.priority DESC,s.name ASC";
        $params = array(
            $ip
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list[] = new Host_sensor_reference($rs->fields["host_ip"], $rs->fields["sensor_name"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function get_list_array($conn, $ip) {
        $sql = "SELECT h.*,s.ip FROM host_sensor_reference h,sensor s WHERE h.sensor_name=s.name AND h.host_ip = ? ORDER BY s.priority DESC,s.name ASC";
        $params = array(
            $ip
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
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
    function insert($conn, $host_ip, $sensors) {
        foreach($sensors as $sensor_name) {
            $sql = "INSERT INTO host_sensor_reference VALUES (?, ?)";
            $params = array(
                $host_ip,
                $sensor_name
            );
            if ($conn->Execute($sql, $params) === false) {
                print 'Error inserting: ' . $conn->ErrorMsg() . '<BR>';
                exit;
            }
        }
    }
    function delete($conn, $host_ip, $sensor_name = "%") {
        $sql = "DELETE FROM host_sensor_reference WHERE host_ip = ? AND sensor_name LIKE ?";
        $params = array(
            $host_ip,
            $sensor_name
        );
		
        if ($conn->Execute($sql, $params) === false) {
            print 'Error deleting: ' . $conn->ErrorMsg() . '<br/>';
            exit;
        }
		
    }
    function in_host_sensor_reference($conn, $ip, $sensor_name) {
        $sql = "SELECT * FROM host_sensor_reference WHERE host_ip = ? AND sensor_name = ?";
        $params = array(
            $ip,
            $sensor_name
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        }
        return $rs->fields[0];
    }
    function check_orphan($conn) {
        $sql = "SELECT * FROM host_sensor_reference LEFT JOIN sensor ON
                sensor.name = host_sensor_reference.sensor_name WHERE descr IS NULL";
        if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                Host_sensor_reference::delete($conn, $rs->fields["host_ip"], $rs->fields["sensor_name"]);
                $rs->MoveNext();
            }
        }
    }
}
?>
