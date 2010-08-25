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
* - Host_group_scan()
* - get_host_group_name()
* - get_plugin_id()
* - get_plugin_sid()
* - get_name()
* - get_list()
* - insert()
* - update()
* - delete()
* - in_host_group_scan()
* - can_delete_host_from_nagios()
* Classes list:
* - Host_group_scan
*/
require_once ('classes/Host_group.inc');
require_once ("ossim_db.inc");
class Host_group_scan {
    var $host_group_name;
    var $plugin_id;
    var $plugin_sid;
    function Host_group_scan($host_group_name, $plugin_id, $plugin_sid) {
        $this->host_group_name = $host_group_name;
        $this->plugin_id = $plugin_id;
        $this->plugin_sid = $plugin_sid;
    }
    function get_host_group_name() {
        return $this->host_group_name;
    }
    function get_plugin_id() {
        return $this->plugin_id;
    }
    function get_plugin_sid() {
        return $this->plugin_sid;
    }
    function get_name() {
        return $this->host_group_name;
    }
    function get_list($conn, $args = "") {
        $query = OssimQuery("SELECT * FROM host_group_scan $args");
        $list = array();
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                if (Session::groupHostAllowed($conn, stripslashes($rs->fields["host_group_name"]))) {
					$list[] = new Host_group_scan($rs->fields["host_group_name"], $rs->fields["plugin_id"], $rs->fields["plugin_sid"]);
				}
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $host_group_name, $plugin_id, $plugin_sid = 0, $hosts = array() , $sensors = "") {
        settype($plugin_id, "int");
        settype($plugin_sid, "int");
        $sql = "INSERT INTO host_group_scan VALUES (?, ?, ?)";
        $params = array(
            $host_group_name,
            $plugin_id,
            $plugin_sid
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        if ($plugin_id == 2007) {
            require_once 'classes/NagiosConfigs.inc';
            $q = new NagiosAdm();
            $q->addNagiosHostGroup(new NagiosHostGroup($host_group_name, $hosts, $sensors),$conn);
            $q->close();
        }
    }
    function update($conn, $host, $plugin_id, $plugin_sid = 0) {
        settype($plugin_id, "int");
        settype($plugin_sid, "int");
        $sql = "UPDATE host_group_scan SET host = ?, plugin_id = ?, plugin_sid = ?";
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
    function delete($conn, $host, $plugin_id, $hosts = array()) {
        $sql = "DELETE FROM host_group_scan WHERE host_group_name = ? AND plugin_id = ?";
        $params = array(
            $host,
            $plugin_id
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        if ($plugin_id == 2007) {
            require_once 'classes/NagiosConfigs.inc';
            $q = new NagiosAdm();
            $q->delNagiosHostGroup(new NagiosHostGroup($host, $hosts, ""));
            $q->close();
        }
    }
    function in_host_group_scan($conn, $host, $plugin_id) {
        $sql = "SELECT * FROM host_group_scan WHERE host_group_name = ? AND plugin_id = ?";
        $params = array(
            $host,
            $plugin_id
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        }
        return $rs->fields[0];
    }
    function can_delete_host_from_nagios($conn, $ip, $hostgroupname) {
        // When we delete a hostgroup with nagios enabled, we must look if it's hosts have nagios enabled, and if not, we should delete the host config file
        // This function will tell us that.
        $sql = 'SELECT hgr.host_ip, hgr.host_group_name FROM host_group_reference hgr, host_group_scan hgs WHERE hgr.host_ip in( select inet_ntoa(hs.host_ip) from host_scan hs where inet_ntoa(hs.host_ip) like ? and hs.plugin_id=2007) or hgr.host_ip=? and hgr.host_group_name=hgs.host_group_name and hgs.plugin_id=2007 and hgr.host_group_name not like ? LIMIT 0,1 ';
        $params = array(
            $ip,
            $ip,
            $hostgroupname
        );
        if (!$rs = & $conn->Execute($sql, $params)) print $conn->ErrorMsg();
        if ($rs->EOF) return true;
        return false;
    }
}
?>
