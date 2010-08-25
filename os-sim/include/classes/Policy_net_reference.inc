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
* - Policy_net_reference()
* - get_policy_id()
* - get_net_name()
* - get_direction()
* - get_list()
* - insert()
* - in_policy_net_reference()
* - delete()
* Classes list:
* - Policy_net_reference
*/
require_once ("ossim_db.inc");
class Policy_net_reference {
    var $policy_id;
    var $net_name;
    var $direction;
    function Policy_net_reference($policy_id, $net_name, $direction) {
        $this->policy_id = $policy_id;
        $this->net_name = $net_name;
        $this->direction = $direction;
    }
    function get_policy_id() {
        return $this->policy_id;
    }
    function get_net_name() {
        return $this->net_name;
    }
    function get_direction() {
        return $this->direction;
    }
    function get_list($conn, $policy_id, $direction) {
        $list = array();
        $policy_id = intval($policy_id);
        $query = OssimQuery("SELECT * FROM policy_net_reference 
                    WHERE policy_id = $policy_id AND direction = '$direction'");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                $list[] = new Policy_net_reference($rs->fields["policy_id"], $rs->fields["net_name"], $rs->fields["direction"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $net_list, $direction, $policy_id) {
        foreach($net_list as $net_name) {
            $sql = "INSERT INTO policy_net_reference VALUES (?, ?, ?)";
            $params = array(
                $policy_id,
                $net_name,
                $direction
            );
            if (!$conn->Execute($sql, $params)) {
                die($conn->ErrorMsg());
            }
        }
    }
    function in_policy_net_reference($conn, $policy_id, $net_name, $direction) {
        $policy_id = intval($policy_id);
        $sql = "SELECT * FROM policy_net_reference WHERE policy_id = ? and net_name = ? and direction = ?";
        $params = array(
            $policy_id,
            $net_name,
            $direction
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        }
        return $rs->fields[0];
    }
    function delete($conn, $policy_id) {
        $policy_id = intval($policy_id);
        $sql = "DELETE FROM policy_net_reference 
                    WHERE policy_id = ?";
        $params = array(
            $policy_id
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
}
?>
