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
* - Host_scan()
* - get_host_ip()
* - get_plugin_id()
* - get_plugin_sid()
* - get_list()
* - insert()
* - update()
* - delete()
* - in_host_scan()
* - get_name()
* Classes list:
* - Host_scan
*/
require_once ('classes/Log_action.inc');
require_once ('classes/Host_group.inc');
require_once ("ossim_db.inc");
class Host_scan {
    var $host_ip;
    var $plugin_id;
    var $plugin_sid;
    function Host_scan($host_ip, $plugin_id, $plugin_sid) {
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
        $query = OssimQuery("SELECT *, inet_ntoa(host_ip) FROM host_scan $args");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                if (Session::hostAllowed($conn, $rs->fields["host_ip"])) {
					$list[] = new Host_scan($rs->fields["inet_ntoa(host_ip)"], $rs->fields["plugin_id"], $rs->fields["plugin_sid"]);
				}
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $host_ip, $plugin_id, $plugin_sid = 0, $hostname = "", $sensors = "") {
        settype($plugin_id, "int");
        settype($plugin_sid, "int");
        $sql = "INSERT INTO host_scan VALUES (inet_aton(?), ?, ?)";
        $params = array(
            $host_ip,
            $plugin_id,
            $plugin_sid
        );
		
				
        if ($conn->Execute($sql, $params) === false) {
            print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
			exit;
        }
        $infolog = array(
            $host_ip . ":" . $plugin_id
        );
        Log_action::log(10, $infolog);
        if ($plugin_id == 2007) {
            require_once 'classes/NagiosConfigs.inc';
            $q = new NagiosAdm();
            $q->addHost(new NagiosHost($host_ip, $hostname, $sensors));
            $q->close();
		}
    }
    function update($conn, $host, $plugin_id, $plugin_sid = 0, $hostname = "", $sensors = "") {
        settype($plugin_id, "int");
        settype($plugin_sid, "int");
        $sql = "UPDATE host_scan SET inet_ntoa(host) = ?, plugin_id = ?,  plugin_sid = ?";
        $params = array(
            $host,
            $plugin_id,
            $plugin_sid
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error updating: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        if ($plugin_id == 2007) {
            require_once 'classes/NagiosConfigs.inc';
            $q = new NagiosAdm();
            $q->addHost(new NagiosHost($host, $hostname, $sensors));
            $q->close();
        }
    }
    function delete($conn, $host, $plugin_id) {
        $sql = "DELETE FROM host_scan WHERE inet_ntoa(host_ip) = ? AND plugin_id = ?";
        $params = array(
            $host,
            $plugin_id
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        if ($plugin_id == 2007) {
            if (!Host_group::isHostInGroupWithPlugin($conn, $host, 2007)) {
                require_once 'classes/NagiosConfigs.inc';
                $q = new NagiosAdm();
                $q->delHost(new NagiosHost($host, $host, ""));
                $q->close();
            }
        }
    }
    function in_host_scan($conn, $host, $plugin_id) {
        $sql = "SELECT * FROM host_scan WHERE inet_ntoa(host_ip) = ? AND plugin_id = ?";
        $params = array(
            $host,
            $plugin_id
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        }
        return $rs->fields[0];
    }
    function get_name($conn) {
        $sql = "SELECT hostname FROM host WHERE ip = ?";
        if (!$rs = & $conn->Execute($sql, $this->host_ip)) {
            print $conn->ErrorMsg();
        }
        return $rs->fields[0];
    }
}
?>
