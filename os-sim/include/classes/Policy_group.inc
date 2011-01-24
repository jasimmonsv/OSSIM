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
* - Policy_group()
* - get_group_id()
* - get_name()
* - get_descr()
* - get_order()
* - get_foundrows()
* - get_list()
* - insert()
* - update()
* - delete()
* - swap_orders()
* Classes list:
* - Policy_group
*/
require_once 'classes/Policy.inc';
require_once 'classes/Log_action.inc';
require_once 'ossim_db.inc';
require_once 'classes/Util.inc';


class Policy_group {
    var $group_id;
    var $name;
    var $descr;
    var $order;
    var $foundrows;
    function Policy_group($group_id, $name, $descr, $order, $foundrows = 0) {
        $this->group_id = $group_id;
        $this->name = $name;
        $this->descr = htmlentities($descr);
        $this->order = $order;
        $this->foundrows = $foundrows;
    }
    function get_group_id() {
        return $this->group_id;
    }
    function get_name() {
        return $this->name;
    }
    function get_descr() {
        return $this->descr;
    }
    function get_order() {
        return $this->order;
    }
    function get_foundrows() {
        return $this->foundrows;
    }
    function get_list($conn, $args = "") {
        $list = array();
        $iffoundrows = preg_match("/LIMIT/", $args) ? true : false;
        $select = ($iffoundrows) ? "SQL_CALC_FOUND_ROWS *" : "*";
        $query = OssimQuery("SELECT $select FROM policy_group $args");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $foundrows = 0;
            if ($iffoundrows) {
                if (!$rf = & $conn->Execute("SELECT FOUND_ROWS() as total")) print $conn->ErrorMsg();
                else $foundrows = $rf->fields["total"];
            }
            if (!preg_match("/WHERE/i", $args)) $list[] = new Policy_group(0, "Default Group", "Default Group Policy objects", 0, $foundrows);
            while (!$rs->EOF) {
                $list[] = new Policy_group($rs->fields["group_id"], $rs->fields["name"], $rs->fields["descr"], $rs->fields["order"], $foundrows);
                $rs->MoveNext();
            }
        }
        if (count($list) == 0 && !preg_match("/WHERE/i", $args)) $list[] = new Policy_group(0, "Default", "Default Policy Group", 0, 1);
        return $list;
    }
    function insert($conn, $name, $descr) {
        $descr = htmlentities($descr);
        $query = OssimQuery("SELECT max(policy_group.order) as max FROM policy_group");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $order = $rs->fields["max"] + 1;
        }
        $sql = "INSERT INTO policy_group (`name`, `descr`, `order`) VALUES (?, ?, ?)";
        $params = array(
            $name,
            $descr,
            $order
        );
        if (!$conn->Execute($sql, $params)) {
            die($conn->ErrorMsg());
        }
        $infolog = array(
            $name,
            "(" . $descr . ")"
        );
        Log_action::log(46, $infolog);
    }
    function update($conn, $id, $name, $descr) {
        $descr = htmlentities($descr);
        $id = intval($id);
        $sql = "UPDATE policy_group SET name=?,descr=? WHERE group_id=?";
        $params = array(
            $name,
            $descr,
            $id
        );
        if (!$conn->Execute($sql, $params)) {
            die($conn->ErrorMsg());
        }
        $infolog = array(
            $id,
            "($name - $descr)"
        );
        Log_action::log(48, $infolog);
    }
    function delete($conn, $id) {
        $id = intval($id);
        $policygrp = Policy_group::get_list($conn, "where group_id=$id");

        if (count($policygrp) != 1) return;
        $params = array(
            $id
        );
        $sql = "DELETE FROM policy_group WHERE group_id = ?";
        $conn->Execute($sql, $params);
        $policy_list = Policy::get_list($conn, "where policy.group=$id");
        foreach($policy_list as $policy) Policy::delete($conn, $policy->get_id());
        
        $reorderpolicygrps = Policy::get_policy_groups($conn, "ORDER BY order");
        $neworder = 1000;
        
        foreach($reorderpolicygrps as $policy) {
            if(intval($policy->get_group_id())!=0) {
                $conn->Execute("UPDATE policy_group SET policy_group.order=$neworder WHERE group_id=".$policy->get_group_id());
                $neworder++;
            }
        }
        
        $infolog = array(
            $id,
            "(" . $policygrp[0]->get_name() . ")"
        );
        Log_action::log(47, $infolog);
    }
    function swap_orders($conn, $id1, $order1, $id2, $order2) {
        $policy_list1 = Policy::get_list($conn, "where policy.group=$id1 order by policy.order");
        $policy_list2 = Policy::get_list($conn, "where policy.group=$id2 order by policy.order");
        if ($policy_list1[0] && $policy_list2[0]) { // al least one policy per group
            // swap policy orders
            $minorder = $policy_list1[0]->get_order();
            foreach($policy_list2 as $policy) {
                $sql = "UPDATE policy SET policy.order=? WHERE id=?";
                $params = array(
                    $minorder,
                    $policy->get_id()
                );
                if (!$conn->Execute($sql, $params)) die($conn->ErrorMsg());
                $minorder++;
            }
            foreach($policy_list1 as $policy) {
                $sql = "UPDATE policy SET policy.order=? WHERE id=?";
                $params = array(
                    $minorder,
                    $policy->get_id()
                );
                if (!$conn->Execute($sql, $params)) die($conn->ErrorMsg());
                $minorder++;
            }
        }
        // swap policy group orders
        $sql = "UPDATE policy_group SET policy_group.order=? WHERE group_id=?";
        $params = array(
            $order2,
            $id1
        );
        if (!$conn->Execute($sql, $params)) die($conn->ErrorMsg());
        $sql = "UPDATE policy_group SET policy_group.order=? WHERE group_id=?";
        $params = array(
            $order1,
            $id2
        );
        if (!$conn->Execute($sql, $params)) die($conn->ErrorMsg());
    }
}
?>
