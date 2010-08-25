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
* - Host_position()
* - get_host_ip()
* - get_x()
* - get_y()
* - get_z()
* - get_list()
* - insert()
* - update()
* - delete()
* Classes list:
* - Host_position
*/
/*
* XXX Deprecated file
*/
require_once ("ossim_db.inc");
class Host_position {
    var $host_ip;
    var $x;
    var $y;
    var $z;
    function Host_position($host_ip, $x, $y, $z) {
        $this->host_ip = $host_ip;
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
    }
    function get_host_ip() {
        return $this->host_ip;
    }
    function get_x() {
        return $this->x;
    }
    function get_y() {
        return $this->y;
    }
    function get_z() {
        return $this->z;
    }
    function get_list($conn, $where = "", $order = "") {
        $query = OssimQuery("SELECT * FROM host_position $where $order");
        if (!$rs = & $conn->Execute("$query")) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list[] = new Host_position($rs->fields["host_ip"], $rs->fields["x"], $rs->fields["y"], $rs->fields["z"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $ip, $x, $y, $z) {
        $x = intval($x);
        $y = intval($y);
        $z = intval($z);
        $sql = "INSERT INTO host_position VALUES (?, ?, ?, ?)";
        $params = array(
            $ip,
            $x,
            $y,
            $z
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function update($conn, $ip, $x, $y, $z) {
        $x = intval($x);
        $y = intval($y);
        $z = intval($z);
        $sql = "UPDATE host_position SET host_ip = ?, x = ?, y = ?, z = ? WHERE host_ip = ?";
        $params = array(
            $ip,
            $x,
            $y,
            $z,
            $ip
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error updating: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function delete($conn, $ip) {
        $sql = "DELETE FROM host_position WHERE host_ip = ?";
        $params = array(
            $ip
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
}
?>
