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
* - Host_plugin_sid()
* - get_host_ip()
* - get_plugin_id()
* - get_plugin_sid()
* - get_list()
* - insert()
* - update()
* - delete()
* Classes list:
* - Host_plugin_sid
*/
require_once ("ossim_db.inc");
class Host_plugin_sid {
    var $host_ip;
    var $plugin_id;
    var $plugin_sid;
    function Host_plugin_sid($host_ip, $plugin_id, $plugin_sid) {
        $this->host_ip = $host_ip;
        $this->plugin_id = $plugin_id;
        $this->plugin_sid = $plugin_sid;
    }
    function get_host_ip() {
        return $this->host_ip;
    }
    function get_plugin_id() {
        return $this->plugin_id;
    }
    function get_plugin_sid() {
        return $this->plugin_sid;
    }
    function get_list($conn, $args = "") {
        $query = OssimQuery("SELECT *, inet_ntoa(host_ip) FROM host_plugin_sid $args");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list[] = new Host_plugin_sid($rs->fields["inet_ntoa(host_ip)"], $rs->fields["plugin_id"], $rs->fields["plugin_sid"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $host_ip, $plugin_id, $plugin_sid = 0) {
        settype($plugin_id, "int");
        settype($plugin_sid, "int");
        $sql = "INSERT INTO host_plugin_sid VALUES (inet_aton(?), ?, ?)";
        $params = array(
            $host_ip,
            $plugin_id,
            $plugin_sid
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function update($conn, $host, $plugin_id, $plugin_sid = 0) {
        settype($plugin_id, "int");
        settype($plugin_sid, "int");
        $sql = "UPDATE host_plugin_sid SET inet_ntoa(host) = ?, plugin_id = ?,  plugin_sid = ?";
        $params = array(
            $host,
            $plugin_id,
            $plugin_sid
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error updating: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function delete($conn, $host, $plugin_id) {
        $sql = "DELETE FROM host_plugin_sid WHERE inet_ntoa(host_ip) = ? AND plugin_id = ?";
        $params = array(
            $host,
            $plugin_id
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
}
?>
