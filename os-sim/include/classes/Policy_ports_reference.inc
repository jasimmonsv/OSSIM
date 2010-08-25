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
* - Policy_ports_reference()
* - get_policy_id()
* - get_port_number()
* - get_port_protocol()
* - get_list()
* - insert()
* - update()
* - delete()
* Classes list:
* - Policy_ports_reference
*/
require_once ("ossim_db.inc");
/*
* @deprecated class/file
*/
class Policy_ports_reference {
    var $policy_id;
    var $port_number;
    var $port_protocol;
    function Policy_ports_reference($policy_id, $port_number, $port_protocol) {
        $this->policy_id = $policy_id;
        $this->port_number = $port_number;
        $this->port_protocol = $port_protocol;
    }
    function get_policy_id() {
        return $this->policy_id;
    }
    function get_port_number() {
        return $this->port_number;
    }
    function get_port_protocol() {
        return $this->port_protocol;
    }
    function get_list($conn, $where = "", $order = "") {
        $query = OssimQuery("SELECT * FROM policy_ports_reference $where
        $order");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list[] = new Policy_ports_reference($rs->fields["policy_id"], $rs->fields["port_number"], $rs->fields["port_protocol"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $policy_id, $port_number, $port_protocol) {
        $policy_id = intval($policy_id);
        $port_number = intval($port_number);
        $sql = "INSERT INTO policy_ports_reference VALUES (?, ?, ?)";
        $params = array(
            $policy_id,
            $port_number,
            $port_protocol
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function update($conn, $policy_id, $port_number, $port_protocol) {
        $policy_id = intval($policy_id);
        $port_number = intval($port_number);
        $sql = "UPDATE policy_ports_reference SET policy_id = ?, port_number  = ?,
                port_protocol = ? WHERE policy_id = ? AND port_number = ? AND port_protocol = ?";
        $params = array(
            $policy_id,
            $port_number,
            $port_protocol,
            $policy_id,
            $port_number,
            $port_protocol
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error updating: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function delete($conn, $policy_id, $port_number, $port_protocol) {
        $policy_id = intval($policy_id);
        $port_number = intval($port_number);
        $sql = "DELETE FROM policy_ports_reference WHERE policy_id = ? AND port_number = ? AND port_protocol = ?";
        $params = array(
            $policy_id,
            $port_number,
            $port_protocol
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
}
?>
