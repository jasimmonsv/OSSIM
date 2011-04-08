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
* - Port()
* - get_port_number()
* - get_protocol_name()
* - get_service()
* - get_descr()
* - get_list()
* - insert()
* - update()
* - delete()
* - port2service()
* Classes list:
* - Port
*/
require_once ("ossim_db.inc");
class Port {
    var $port_number;
    var $protocol_name;
    var $service;
    var $descr;
    function Port($port_number, $protocol_name, $service, $descr) {
        $this->port_number = $port_number;
        $this->protocol_name = $protocol_name;
        $this->service = $service;
        $this->descr = $descr;
    }
    function get_port_number() {
        return $this->port_number;
    }
    function get_protocol_name() {
        return $this->protocol_name;
    }
    function get_service() {
        return $this->service;
    }
    function get_descr() {
        return $this->descr;
    }
    function get_list($conn, $where = "", $order = "ORDER BY port_number") {
        $query = OssimQuery("SELECT * FROM port $where $order");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list[] = new Port($rs->fields["port_number"], $rs->fields["protocol_name"], $rs->fields["service"], $rs->fields["descr"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $port_number, $protocol_name, $service, $descr) {
        settype($port_number, "int");
        $sql = "INSERT INTO port VALUES (?, ?, ?, ?)";
        $params = array(
            $port_number,
            $protocol_name,
            $service,
            $descr
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function update($conn, $port_number, $protocol_name, $service, $descr) {
        settype($port_number, "int");
        $sql = "UPDATE port SET port_number = ?, protocol_name = ?, service = ?,
                descr = ? WHERE port_number = ? AND protocol_name = ?";
        $params = array(
            $port_number,
            $protocol_name,
            $service,
            $descr,
            $port_number,
            $protocol_name
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error updating: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function delete($conn, $port_number, $protocol_name) {
        settype($port_number, "int");
        $sql = "DELETE FROM port WHERE port_number = ? AND protocol_name = ?";
        $params = array(
            $port_number,
            $protocol_name
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function port2service($conn, $port, $protocol = "tcp") {
        if (($protocol != "tcp") and ($protocol != "udp")) return $port;
        if (isset($_SESSION['_port2service'][$port][$protocol])) return $_SESSION['_port2service'][$port][$protocol];
        /* first look at ossim.port table */
        $sql = "SELECT service FROM port WHERE port_number = ? AND protocol_name = ?";
        $params = array(
            $port,
            $protocol
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        } else {
            if (!$rs->EOF) return $rs->fields["service"];
        }
        /* there is no info about $port-$protocol in ossim.port table */
        if ($service = getservbyport($port, $protocol)) {
            $sql = "INSERT INTO port (port_number, protocol_name, service, descr) VALUES (?, ?, ?, '')";
            $params = array(
                $port,
                $protocol,
                $service
            );
            if ($conn->Execute($sql, $params) === false) {
                print 'error inserting port info: ' . $conn->ErrorMsg() . '<BR>';
            }
            $_SESSION['_port2service'][$port][$protocol] = $service;
            return $service;
        }
        /* getservbyport failed */
        $_SESSION['_port2service'][$port][$protocol] = $port;
        return $port;
    }
}
?>
