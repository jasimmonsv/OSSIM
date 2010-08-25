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
* - Policy_host_reference()
* - get_policy_id()
* - get_host_ip()
* - get_direction()
* - get_list()
* - insert()
* - in_policy_host_reference()
* - delete()
* Classes list:
* - Policy_host_reference
*/
require_once ("ossim_db.inc");
class Policy_host_reference {
    var $policy_id;
    var $host_ip;
    var $direction;
    function Policy_host_reference($policy_id, $host_ip, $direction) {
        $this->policy_id = $policy_id;
        $this->host_ip = $host_ip;
        $this->direction = $direction;
    }
    function get_policy_id() {
        return $this->policy_id;
    }
    function get_host_ip() {
        return $this->host_ip;
    }
    function get_direction() {
        return $this->direction;
    }
    function get_list($conn, $policy_id, $direction) {
        $list = array();
        $policy_id = intval($policy_id);
        $query = OssimQuery("SELECT * FROM policy_host_reference 
                    WHERE policy_id = $policy_id 
                    AND direction = '$direction'");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                $list[] = new Policy_host_reference($rs->fields["policy_id"], $rs->fields["host_ip"], $rs->fields["direction"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $host_list, $direction, $policy_id) {
        foreach($host_list as $host_ip) {
            $sql = "INSERT INTO policy_host_reference VALUES (?, ?, ?)";
            if (!$conn->Execute($sql, array(
                $policy_id,
                $host_ip,
                $direction
            ))) {
                die($conn->ErrorMsg());
            }
        }
    }
    function in_policy_host_reference($conn, $policy_id, $host_ip, $direction) {
        $policy_id = intval($policy_id);
        $query = OssimQuery("SELECT * FROM policy_host_reference 
                    WHERE policy_id = $policy_id and host_ip = '$host_ip' and
                    direction = '$direction'");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        }
        return $rs->fields[0];
    }
    function delete($conn, $policy_id) {
        $policy_id = intval($policy_id);
        $query = OssimQuery("DELETE FROM policy_host_reference 
                    WHERE policy_id = $policy_id");
        if ($conn->Execute($query) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
}
?>
