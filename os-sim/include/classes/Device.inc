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
* - Device()
* - get_community()
* - get_ip()
* - get_descr()
* - get_foundrows()
* - get_list()
* - insert()
* - delete()
* - update()
*/
require_once 'Port_group_reference.inc';
require_once ('classes/Log_action.inc');
require_once ("ossim_db.inc");
class Device {
    var $ip;
    var $community;
    var $descr;
    function Device($ip, $community, $descr, $foundrows = 0) {
        $this->ip = $ip;
        $this->community = $community;
        $this->descr = $descr;
        $this->foundrows = $foundrows;
    }
    function get_community() {
        return $this->community;
    }
    function get_ip() {
        return long2ip($this->ip);
    }
    function get_descr() {
        return $this->descr;
    }
    function get_foundrows() {
        return $this->foundrows;
    }
    function get_list($conn, $args = "") {
        $iffoundrows = preg_match("/LIMIT/i", $args) ? true : false;
        $select = ($iffoundrows) ? "SQL_CALC_FOUND_ROWS *" : "*";
        $query = OssimQuery("SELECT $select FROM network_device $args");
        //error_log("\nSELECT $select FROM network_device $args",3,"/tmp/nedi.log");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $foundrows = 0;
            if ($iffoundrows) {
                if (!$rf = & $conn->Execute("SELECT FOUND_ROWS() as total")) print $conn->ErrorMsg();
                else $foundrows = $rf->fields["total"];
            }
            $list = array();
            while (!$rs->EOF) {
                $list[] = new Device($rs->fields["ip"], $rs->fields["community"], $rs->fields["descr"], $foundrows);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $ip, $community, $descr) {
        $sql = "INSERT INTO network_device VALUES (inet_aton(?), ?, ?)";
        $params = array(
            $ip,
            $community,
            $descr
        );        
        if ($conn->Execute($sql,$params) === false) {
            print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function update($conn, $ip, $community, $descr) {
        Device::delete($conn, $ip);
        Device::insert($conn, $ip, $community, $descr);
    }
    function delete($conn, $ip) {
        $sql = "DELETE FROM network_device WHERE ip = inet_aton(?)";
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
