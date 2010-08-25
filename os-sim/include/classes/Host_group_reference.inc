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
* - Host_group_reference()
* - get_host_group_name()
* - get_host_ip()
* - get_list()
* - insert()
* - delete()
* - in_host_group_reference()
* - check_orphan()
* - get_host_name()
* Classes list:
* - Host_group_reference
*/
require_once ("ossim_db.inc");
class Host_group_reference {
    var $host_group_name;
    var $host_ip;
    function Host_group_reference($host_group_name, $host_ip) {
        $this->host_group_name = $host_group_name;
        $this->host_ip = $host_ip;
    }
    function get_host_group_name() {
        return $this->host_group_name;
    }
    function get_host_ip() {
        return $this->host_ip;
    }
    function get_list($conn, $host, $plugin = "") {
        $list = array();
        if ($plugin != "") if ($plugin == 2007) $sql = "SELECT host_group_reference.host_group_name, host_group_reference.host_ip FROM host_group_reference, host_group_scan WHERE host_group_reference.host_group_name = ? and host_group_scan.host_group_name=host_group_reference.host_group_name and host_group_scan.plugin_id=2007";
        else $sql = "SELECT host_group_name, host_group_reference.host_ip FROM host_group_reference, host_scan WHERE host_group_reference.host_group_name = ? AND host_group_reference.host_ip = inet_ntoa(host_scan.host_ip)";
        else $sql = "SELECT * FROM host_group_reference WHERE host_group_name = ?";
        if (!$rs = & $conn->Execute($sql, array(
            $host
        ))) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                $list[] = new Host_group_reference(stripslashes($rs->fields["host_group_name"]) , stripslashes($rs->fields["host_ip"]));
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $host_group_name, $hosts) {
        foreach($hosts as $host_ip) {
            $sql = "INSERT INTO host_group_reference VALUES (?, ?)";
            $params = array(
                $host_group_name,
                $host_ip
            );
            if ($conn->Execute($sql, $params) === false) {
                print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
                exit;
            }
        }
    }
    function delete($conn, $host_group_name, $host_ip = "%") {
        $sql = "DELETE FROM host_group_reference WHERE host_group_name = ? AND host_ip LIKE ?";
        $params = array(
            $host_group_name,
            $host_ip
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function in_host_group_reference($conn, $host, $host_ip) {
        $sql = "SELECT * FROM host_group_reference WHERE host_group_name = ? AND host_ip = ?";
        $params = array(
            $host,
            $host_ip
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        }
        return $rs->fields[0];
    }
    function check_orphan($conn) {
        $sql = "SELECT * FROM host_group_reference LEFT JOIN host ON host.name = host_group_reference.host_name WHERE descr IS NULL";
        if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                Host_group_reference::delete($conn, $rs->fields["host_group_name"], $rs->fields["host_name"]);
                $rs->MoveNext();
            }
        }
    }
    function get_host_name($conn) {
        $sql = "SELECT hostname FROM host WHERE ip = ?";
        $params = $this->host_ip;
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        }
        return (trim($rs->fields[0]) != "") ? $rs->fields[0] : $this->host_ip;
    }
}
?>
