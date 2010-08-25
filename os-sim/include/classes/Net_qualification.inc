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
* - Net_qualification()
* - get_net_name()
* - get_compromise()
* - get_attack()
* - get_list()
* - insert()
* - update()
* - delete()
* Classes list:
* - Net_qualification
*/
require_once ('classes/Net.inc');
require_once ('classes/Session.inc');
require_once ("ossim_db.inc");
class Net_qualification {
    var $net_name;
    var $compromise;
    var $attack;
    function Net_qualification($net_name, $compromise, $attack) {
        $this->net_name = $net_name;
        $this->compromise = $compromise;
        $this->attack = $attack;
    }
    function get_net_name() {
        return $this->net_name;
    }
    function get_compromise() {
        return $this->compromise;
    }
    function get_attack() {
        return $this->attack;
    }
    function get_list($conn, $where = "", $order = "") {
        $list = array();
        $query = OssimQuery("SELECT * FROM net_qualification $where $order");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                if (Session::netAllowed($conn, $rs->fields["net_name"])) {
                    $list[] = new Net_qualification($rs->fields["net_name"], $rs->fields["compromise"], $rs->fields["attack"]);
                }
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $net_name, $compromise, $attack) {
        $compromise = intval($compromise);
        $attack = intval($attack);
        $sql = "INSERT INTO net_qualification VALUES (?, ?, ?)";
        $params = array(
            $net_name,
            $compromise,
            $attack
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function update($conn, $net_name, $compromise, $attack) {
        $compromise = intval($compromise);
        $attack = intval($attack);
        $sql = "UPDATE net_qualification SET net_name = ?, compromise = ?, attack = ? WHERE net_name = ?";
        $params = array(
            $net_name,
            $compromise,
            $attack,
            $net_name
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error updating: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function delete($conn, $net_name) {
        $sql = "DELETE FROM net_qualification WHERE net_name = ?";
        $params = array(
            $net_name
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
}
?>
