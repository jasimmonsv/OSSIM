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
* - Net_group_reference()
* - get_net_group_name()
* - get_net_name()
* - get_list()
* - insert()
* - delete()
* - in_net_group_reference()
* - check_orphan()
* Classes list:
* - Net_group_reference
*/
require_once ("ossim_db.inc");
class Net_group_reference {
    var $net_group_name;
    var $net_name;
    function Net_group_reference($net_group_name, $net_name) {
        $this->net_group_name = $net_group_name;
        $this->net_name = $net_name;
    }
    function get_net_group_name() {
        return $this->net_group_name;
    }
    function get_net_name() {
        return $this->net_name;
    }
    function get_net_ips($conn) {
        $sql = "SELECT ips FROM net WHERE name = ?";
        $params = array($this->net_name);
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        }
        return $rs->fields[0];
    }
    function get_list($conn, $net, $plugin) {
        $list = array();
        if ($plugin != "") {
            $sql = "SELECT net_group_name, net_group_reference.net_name
                    FROM net_group_reference, net_scan
                    WHERE net_group_reference.net_group_name = ? AND
                          net_group_reference.net_name = net_scan.net_name";
        } else {
            $sql = "SELECT * FROM net_group_reference WHERE net_group_name = ?";
        }
        if (!$rs = & $conn->Execute($sql, array(
            $net
        ))) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                $list[] = new Net_group_reference(stripslashes($rs->fields["net_group_name"]) , stripslashes($rs->fields["net_name"]));
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $net_group_name, $nets) {
        foreach($nets as $net_name) {
            $sql = "INSERT INTO net_group_reference VALUES (?, ?)";
            $params = array(
                $net_group_name,
                $net_name
            );
            if ($conn->Execute($sql, $params) === false) {
                print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
                exit;
            }
        }
    }
    function delete($conn, $net_group_name, $net_name = "%") {
        $sql = "DELETE FROM net_group_reference WHERE net_group_name = ? AND net_name LIKE ?";
        $params = array(
            $net_group_name,
            $net_name
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function in_net_group_reference($conn, $net, $net_name) {
        $sql = "SELECT * FROM net_group_reference WHERE net_group_name = ? AND net_name = ?";
        $params = array(
            $net,
            $net_name
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        }
        return $rs->fields[0];
    }
    function check_orphan($conn) {
        $sql = "SELECT * FROM net_group_reference LEFT JOIN net ON net.name = net_group_reference.net_name WHERE descr IS NULL";
        if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                Net_group_reference::delete($conn, $rs->fields["net_group_name"], $rs->fields["net_name"]);
                $rs->MoveNext();
            }
        }
    }
}
?>
