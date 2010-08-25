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
* - Action()
* - get_id()
* - get_action_type()
* - get_descr()
* - get_foundrows()
* - get_action_by_id()
* - get_action()
* - get_list()
* - insert()
* - insertEmail()
* - insertExec()
* - update()
* - updateEmail()
* - updateExec()
* - delete()
* Classes list:
* - Action
*/
require_once ('classes/Action_email.inc');
require_once ('classes/Action_exec.inc');
require_once ('classes/Log_action.inc');
require_once ("ossim_db.inc");
class Action {
    var $id;
    var $action_type;
    var $descr;
    function Action($id, $action_type, $cond, $on_risk, $descr, $foundrows = 0) {
        $this->id = $id;
        $this->action_type = $action_type;
		$this->cond = $cond;
		$this->on_risk = $on_risk;
        $this->descr = htmlentities($descr);
        $this->foundrows = $foundrows;
    }
    function get_id() {
        return $this->id;
    }
    function get_action_type() {
        return $this->action_type;
    }
	function get_cond() {
		return $this->cond;
	}
	function is_on_risk() {
		return $this->on_risk;
	}
    function get_descr() {
        return $this->descr;
    }
    function get_foundrows() {
        return $this->foundrows;
    }
    function get_action_by_id($conn, $id) {
        $sql = "SELECT descr FROM action WHERE id = ?";
        $params = array(
            $id
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        } else {
            if (!$rs->EOF) {
                $action = new Action($rs->fields["id"], $rs->fields["action_type"], $rs->fields["cond"], $rs->fields["on_risk"], $rs->fields["descr"]);
            }
        }
        return $action;
    }
    /* get an action object of email or exec classes */
    function get_action($conn) {
        $id = $this->id;
        $type = $this->action_type;
        if ($type == "email") {
            if (is_array($action_list = Action_email::get_list($conn, " WHERE action_id = '$id' "))) {
                return $action_list[0];
            }
            return null;
        } elseif ($type == "exec") {
            if (is_array($action_list = Action_exec::get_list($conn, " WHERE action_id = '$id' "))) {
                return $action_list[0];
            }
            return null;
        } else {
            return null;
        }
    }
    function get_list($conn, $args = "") {
        $iffoundrows = preg_match("/LIMIT/", $args) ? true : false;
        $select = ($iffoundrows) ? "SQL_CALC_FOUND_ROWS *" : "*";
        $query = OssimQuery("SELECT $select FROM action $args");
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
                $list[] = new Action($rs->fields["id"], $rs->fields["action_type"], $rs->fields["cond"], $rs->fields["on_risk"], $rs->fields["descr"], $foundrows);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $action_type, $cond, $on_risk, $descr) {
        $sql = "INSERT INTO action (id, action_type, cond, on_risk, descr) VALUES (NULL, ?, ?, ?, ?)";
        $params = array(
            $action_type,
			$cond,
			$on_risk,
            $descr
        );
        if ($conn->Execute($sql, $params) === false) {
            print _('error inserting:').' '.$conn->ErrorMsg() . '<BR>';
            exit;
        }
        $infolog = array(
            $action_type,
            "(" . $descr . ")"
        );
        Log_action::log(82, $infolog);
    }
    function insertEmail($conn, $action_type, $cond, $on_risk, $descr, $from, $to, $subject, $message) {
        Action::insert($conn, $action_type, $cond, $on_risk, $descr);
        Action_email::insert($conn, $from, $to, $subject, $message);
    }
    function insertExec($conn, $action_type, $cond, $on_risk, $descr, $command) {
        Action::insert($conn, $action_type, $cond, $on_risk, $descr);
        Action_exec::insert($conn, $command);
    }
    function update($conn, $id, $action_type, $cond, $on_risk, $descr) {
        $sql = "UPDATE action SET action_type = ?, cond = ?, on_risk = ?, descr = ? WHERE id = ?";
        $params = array(
            $action_type,
			$cond,
			$on_risk,
            $descr,
            $id
        );
        if ($conn->Execute($sql, $params) === false) {
            print _('error updating: '). $conn->ErrorMsg() . '<BR>';
            exit;
        }
        $infolog = array(
            $action_type,
            "(" . $descr . ")"
        );
        Log_action::log(83, $infolog);
    }
    function updateEmail($conn, $id, $action_type, $cond, $on_risk, $descr, $from, $to, $subject, $message) {
        Action::update($conn, $id, $action_type, $cond, $on_risk, $descr);
        Action_email::update($conn, $id, $from, $to, $subject, $message);
    }
    function updateExec($conn, $id, $action_type, $cond, $on_risk, $descr, $command) {
        Action::update($conn, $id, $action_type, $cond, $on_risk, $descr);
        Action_exec::update($conn, $id, $command);
    }
    function delete($conn, $id) {
        $desc = $conn->GetOne("SELECT descr FROM action WHERE id=$id");
        
        $Action = Action::get_action_by_id($conn, $id);
        $type = $Action->action_type;
        $sql = "DELETE FROM action WHERE id = ?";
        $params = array(
            $id
        );
        if ($conn->Execute($sql, $params) === false) {
            print _("error deleting")." ($sql): " . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        if ($type != "") {
            $sql = "DELETE FROM action_$type WHERE action_id = ?";
            $params = array(
                $id
            );
            if ($conn->Execute($sql, $params) === false) {
                echo _("error deleting")." ($sql): " . $conn->ErrorMsg() . '<BR>';
                exit;
            }
        }
        $sql = "DELETE FROM response_action WHERE action_id = ?";
        $params = array(
            $id
        );
        if ($conn->Execute($sql, $params) === false) {
            echo _("error deleting")." ($sql): " . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        $infolog = array($desc);
        Log_action::log(84, $infolog);
        
    }
}
?>

