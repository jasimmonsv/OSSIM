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
* - Action_email()
* - get_action_id()
* - get_from()
* - get_to()
* - get_subject()
* - get_message()
* - get_list()
* - insert()
* - update()
* Classes list:
* - Action_email
*/
require_once "ossim_db.inc";
class Action_email {
    var $action_id;
    var $from;
    var $to;
    var $subject;
    var $message;
    function Action_email($action_id, $from, $to, $subject, $message) {
        $this->action_id = $action_id;
        $this->from = $from;
        $this->to = $to;
        $this->subject = $subject;
        $this->message = $message;
    }
    function get_action_id() {
        return $this->action_id;
    }
    function get_from() {
        return $this->from;
    }
    function get_to() {
        return $this->to;
    }
    function get_subject() {
        return $this->subject;
    }
    function get_message() {
        return $this->message;
    }
    function get_list($conn, $args = "") {
        $query = OssimQuery("SELECT * FROM action_email $args");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list[] = new Action_email($rs->fields["action_id"], $rs->fields["_from"], $rs->fields["_to"], $rs->fields["subject"], $rs->fields["message"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $from, $to, $subject, $message) {
        $sql = "INSERT INTO action_email (action_id, _from, _to, subject, message)
                VALUES (LAST_INSERT_ID(), ?, ?, ?, ?)";
        $params = array(
            $from,
            $to,
            $subject,
            $message
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function update($conn, $id, $from, $to, $subject, $message) {
        $sql = "UPDATE action_email SET _from = ?, _to = ?, subject = ?, message = ? 
                WHERE action_id = ?";
        $params = array(
            $from,
            $to,
            $subject,
            $message,
            $id
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error updating: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
}
?>
