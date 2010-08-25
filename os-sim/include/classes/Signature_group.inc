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
* - Signature_group()
* - get_name()
* - get_descr()
* - get_reference_signatures()
* - get_list()
* - insert()
* - update()
* - delete()
* Classes list:
* - Signature_group
*/
//require_once 'classes/Signature.inc';
require_once 'classes/Signature_group_reference.inc';
require_once 'classes/Log_action.inc';
require_once ("ossim_db.inc");
class Signature_group {
    var $name;
    var $descr;
    function Signature_group($name, $descr) {
        $this->name = $name;
        $this->descr = $descr;
    }
    function get_name() {
        return $this->name;
    }
    function get_descr() {
        return $this->descr;
    }
    function get_reference_signatures($conn, $sig_group_name) {
        return Signature_group_reference::get_list($conn, "WHERE sig_group_name = '$sig_group_name'");
    }
    function get_list($conn, $args = "") {
        $query = OssimQuery("SELECT * FROM signature_group $args");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list[] = new Signature_group($rs->fields["name"], $rs->fields["descr"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $name, $signature_list, $descr) {
        $sql = "INSERT INTO signature_group VALUES  (?, ?)";
        $params = array(
            $name,
            $descr
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        Signature_group_reference::insert($conn, $name, $signature_list);
        $infolog = array(
            $name
        );
        Log_action::log(36, $infolog);
    }
    function update($conn, $name, $signature_list, $descr) {
        $sql = "UPDATE signature_group SET name = ?, descr = ? WHERE name = ?";
        $params = array(
            $name,
            $descr,
            $name
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error updating: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        Signature_group_reference::update($conn, $name, $signature_list);
        $infolog = array(
            $name
        );
        Log_action::log(37, $infolog);
    }
    function delete($conn, $name) {
        $sql = "DELETE FROM signature_group WHERE name = ?";
        $params = array(
            $name
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        Signature_group_reference::delete($conn, $name);
        $infolog = array(
            $name
        );
        Log_action::log(38, $infolog);
    }
}
?>
