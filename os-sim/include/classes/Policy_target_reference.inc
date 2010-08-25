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
* - Policy_target_reference()
* - get_policy_id()
* - get_target_name()
* - get_list()
* - insert()
* - in_policy_target_reference()
* - delete()
* Classes list:
* - Policy_target_reference
*/
require_once ("ossim_db.inc");
class Policy_target_reference {
    var $policy_id;
    var $target_name;
    function Policy_target_reference($policy_id, $target_name) {
        $this->policy_id = $policy_id;
        $this->target_name = $target_name;
    }
    function get_policy_id() {
        return $this->policy_id;
    }
    function get_target_name() {
        return $this->target_name;
    }
    function get_list($conn, $policy_id) {
        $list = array();
        $policy_id = intval($policy_id);
        $query = OssimQuery("SELECT * FROM policy_target_reference 
                    WHERE policy_id = $policy_id");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list[] = new Policy_target_reference($rs->fields["policy_id"], $rs->fields["target_name"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $target_list, $policy_id) {
        foreach($target_list as $target_name) {
            $sql = "INSERT INTO policy_target_reference VALUES (?, ?)";
            $params = array(
                $policy_id,
                $target_name
            );
            if (!$conn->Execute($sql, $params)) {
                die($conn->ErrorMsg());
            }
        }
    }
    function in_policy_target_reference($conn, $policy_id, $target_name) {
        $policy_id = intval($policy_id);
        $sql = "SELECT * FROM policy_target_reference WHERE policy_id = ? AND target_name = ?";
        $params = array(
            $policy_id,
            $target_name
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        }
        return $rs->fields[0];
    }
    function delete($conn, $policy_id) {
        $policy_id = intval($policy_id);
        $sql = "DELETE FROM policy_target_reference WHERE policy_id = ?";
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
