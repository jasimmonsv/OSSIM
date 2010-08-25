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
* - Net_group_scan()
* - get_net_group_name()
* - get_plugin_id()
* - get_plugin_sid()
* - get_name()
* - get_list()
* - insert()
* - update()
* - delete()
* - in_net_group_scan()
* Classes list:
* - Net_group_scan
*/
require_once ('classes/Net_group.inc');
require_once ("ossim_db.inc");
class Net_group_scan {
    var $net_group_name;
    var $plugin_id;
    var $plugin_sid;
    function Net_group_scan($net_group_name, $plugin_id, $plugin_sid) {
        $this->net_group_name = $net_group_name;
        $this->plugin_id = $plugin_id;
        $this->plugin_sid = $plugin_sid;
    }
    function get_net_group_name() {
        return $this->net_group_name;
    }
    function get_plugin_id() {
        return $this->plugin_id;
    }
    function get_plugin_sid() {
        return $this->plugin_sid;
    }
    function get_name() {
        return $this->net_group_name;
    }
    function get_list($conn, $args = "") {
        $query = OssimQuery("SELECT * FROM net_group_scan $args");
        $list = array();
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                if (Session::groupAllowed($conn, stripslashes($rs->fields["net_group_name"]))) {
					$list[] = new Net_group_scan($rs->fields["net_group_name"], $rs->fields["plugin_id"], $rs->fields["plugin_sid"]);
				}
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $net_group_name, $plugin_id, $plugin_sid = 0) {
        settype($plugin_id, "int");
        settype($plugin_sid, "int");
        $sql = "INSERT INTO net_group_scan VALUES (?, ?, ?)";
        $params = array(
            $net_group_name,
            $plugin_id,
            $plugin_sid
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function update($conn, $net, $plugin_id, $plugin_sid = 0) {
        settype($plugin_id, "int");
        settype($plugin_sid, "int");
        $sql = "UPDATE net_group_scan SET net = ?, plugin_id = ?, plugin_sid = ?";
        $params = array(
            $net,
            $plugin_id,
            $plugin_sid
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error updating: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function delete($conn, $net, $plugin_id) {
        $sql = "DELETE FROM net_group_scan WHERE net_group_name = ? AND plugin_id = ?";
        $params = array(
            $net,
            $plugin_id
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function in_net_group_scan($conn, $net, $plugin_id) {
        $sql = "SELECT * FROM net_group_scan WHERE net_group_name = ? AND plugin_id = ?";
        $params = array(
            $net,
            $plugin_id
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        }
        return $rs->fields[0];
    }
}
?>
