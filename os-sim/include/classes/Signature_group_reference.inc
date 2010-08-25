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
* - Signature_group_reference()
* - get_sig_name()
* - get_sig_group_name()
* - get_list()
* - insert()
* - update()
* - delete()
* - in_signature_group_reference()
* Classes list:
* - Signature_group_reference
*/
require_once ("ossim_db.inc");
class Signature_group_reference {
    var $sig_name;
    var $sig_group_name;
    function Signature_group_reference($sig_name, $sig_group_name) {
        $this->sig_name = $sig_name;
        $this->sig_group_name = $sig_group_name;
    }
    function get_sig_name() {
        return $this->sig_name;
    }
    function get_sig_group_name() {
        return $this->sig_group_name;
    }
    function get_list($conn, $where = "", $order = "") {
        $query = OssimQuery("SELECT * FROM signature_group_reference $where $order");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list[] = new Signature_group_reference($rs->fields["sig_name"], $rs->fields["sig_group_name"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $sig_name, $signature_list) {
        foreach($signature_list as $ip) {
            $sql = "INSERT INTO signature_group_reference VALUES (?, ?)";
            $params = array(
                $sig_name,
                $ip
            );
            if ($conn->Execute($sql, $params) === false) {
                print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
                exit;
            }
        }
    }
    function update($conn, $sig_group_name, $signature_list) {
        Signature_group_reference::delete($conn, $sig_group_name);
        Signature_group_reference::insert($conn, $sig_group_name, $signature_list);
    }
    function delete($conn, $sig_group_name) {
        $sql = "DELETE FROM signature_group_reference WHERE sig_group_name = ?";
        $params = array(
            $sig_group_name
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function in_signature_group_reference($conn, $sig_group_name, $sig_name) {
        $sql = "SELECT * FROM signature_group_reference WHERE sig_group_name = ? and sig_name = ?";
        $params = array(
            $sig_group_name,
            $sig_name
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        }
        return $rs->fields[0];
    }
}
?>

