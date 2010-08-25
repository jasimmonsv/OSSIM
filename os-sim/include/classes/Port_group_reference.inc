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
* - Port_group_reference()
* - get_group_name()
* - get_port_number()
* - get_protocol_name()
* - get_list()
* - insert()
* - delete()
* - in_port_group_reference()
* Classes list:
* - Port_group_reference
*/
require_once ("ossim_db.inc");
class Port_group_reference {
    var $port_group_name;
    var $port_number;
    var $protocol_name;
    function Port_group_reference($port_group_name, $port_number, $protocol_name) {
        $this->port_group_name = $port_group_name;
        $this->port_number = $port_number;
        $this->protocol_name = $protocol_name;
    }
    function get_group_name() {
        return $this->port_group_name;
    }
    function get_port_number() {
        return $this->port_number;
    }
    function get_protocol_name() {
        return $this->protocol_name;
    }
    function get_list($conn, $where = "", $order = "") {
        $query = OssimQuery("SELECT * FROM port_group_reference $where
        $order");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list[] = new Port_group_reference($rs->fields["port_group_name"], $rs->fields["port_number"], $rs->fields["protocol_name"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $port_group_name, $port_list) {
        foreach($port_list as $port) {
            /*
            * port_list contains an array like this
            * (80-tcp, 80-udp, 8080-tcp, 8080-udp)
            */
            $port_array = explode('-', $port);
            $port_number = $port_array[0];
            $protocol_name = $port_array[1];
            settype($port_number, "int");
            $sql = "INSERT INTO port_group_reference VALUES (?, ?, ?)";
            $params = array(
                $port_group_name,
                $port_number,
                $protocol_name
            );
            if ($conn->Execute($sql, $params) === false) {
                print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
                exit;
            }
        }
    }
    function delete($conn, $port_group_name) {
        $sql = "DELETE FROM port_group_reference WHERE port_group_name = ?";
        $params = array(
            $port_group_name
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function in_port_group_reference($conn, $port_group_name, $port_number, $protocol_name) {
        settype($port_number, "int");
        $sql = "SELECT * FROM port_group_reference WHERE port_group_name = ? AND port_number = ? AND protocol_name = ?";
        $params = array(
            $port_group_name,
            $port_number,
            $protocol_name
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        }
        return $rs->fields[0];
    }
}
?>

