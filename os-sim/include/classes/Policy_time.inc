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
* - Policy_time()
* - get_policy_id()
* - get_begin_hour()
* - get_end_hour()
* - get_begin_day()
* - get_end_day()
* - get_list()
* - insert()
* - delete()
* Classes list:
* - Policy_time
*/
require_once ("ossim_db.inc");
class Policy_time {
    var $policy_id;
    var $begin_hour;
    var $end_hour;
    var $begin_day;
    var $end_day;
    function Policy_time($policy_id, $begin_hour, $end_hour, $begin_day, $end_day) {
        $this->policy_id = $policy_id;
        $this->begin_hour = $begin_hour;
        $this->end_hour = $end_hour;
        $this->begin_day = $begin_day;
        $this->end_day = $end_day;
    }
    function get_policy_id() {
        return $this->policy_id;
    }
    function get_begin_hour() {
        return $this->begin_hour;
    }
    function get_end_hour() {
        return $this->end_hour;
    }
    function get_begin_day() {
        return $this->begin_day;
    }
    function get_end_day() {
        return $this->end_day;
    }
    function get_list($conn, $policy_id) {
        $list = array();
        $policy_id = intval($policy_id);
        $query = OssimQuery("SELECT * FROM policy_time 
                    WHERE policy_id = $policy_id");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list = new Policy_time($rs->fields["policy_id"], $rs->fields["begin_hour"], $rs->fields["end_hour"], $rs->fields["begin_day"], $rs->fields["end_day"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $begin_hour, $end_hour, $begin_day, $end_day, $policy_id) {
        $sql = "INSERT INTO policy_time VALUES (?, ?, ?, ?, ?)";
        $params = array(
            $policy_id,
            $begin_hour,
            $end_hour,
            $begin_day,
            $end_day
        );
        if (!$conn->Execute($sql, $params)) {
            die($conn->ErrorMsg());
        }
    }
    function delete($conn, $policy_id) {
        $policy_id = intval($policy_id);
        $sql = "DELETE FROM policy_time WHERE policy_id = ?";
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
