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
* - Plugin_scheduler()
* - get_id()
* - get_plugin()
* - get_minute()
* - get_hour()
* - get_day_month()
* - get_month()
* - get_day_week()
* - get_type_scan()
* - get_list()
* - get_sensors()
* - get_netgroups()
* - get_hostgroups()
* - get_nets()
* - get_hosts()
* - insert()
* - delete()
* Classes list:
* - Plugin_scheduler
*/
require_once ('classes/Log_action.inc');
require_once ('classes/Plugin_scheduler_sensor_reference.inc');
require_once ('classes/Plugin_scheduler_netgroup_reference.inc');
require_once ('classes/Plugin_scheduler_hostgroup_reference.inc');
require_once ('classes/Plugin_scheduler_host_reference.inc');
require_once ('classes/Plugin_scheduler_net_reference.inc');
require_once ("ossim_db.inc");
class Plugin_scheduler {
    var $id;
    var $plugin;
    var $minute;
    var $hour;
    var $day_month;
    var $month;
    var $day_week;
    var $type_scan;
    function Plugin_scheduler($id, $plugin, $minute, $hour, $day_month, $month, $day_week, $type_scan) {
        $this->id = $id;
        $this->plugin = $plugin;
        $this->minute = $minute;
        $this->hour = $hour;
        $this->day_month = $day_month;
        $this->month = $month;
        $this->day_week = $day_week;
        $this->type_scan = $type_scan;
    }
    function get_id() {
        return $this->id;
    }
    function get_plugin() {
        return $this->plugin;
    }
    function get_minute() {
        return $this->minute;
    }
    function get_hour() {
        return $this->hour;
    }
    function get_day_month() {
        return $this->day_month;
    }
    function get_month() {
        return $this->month;
    }
    function get_day_week() {
        return $this->day_week;
    }
    function get_type_scan() {
        return $this->type_scan;
    }
    function get_list($conn, $args = "") {
        $list = array();
        $query = OssimQuery("SELECT * FROM plugin_scheduler $args");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list[] = new Plugin_scheduler($rs->fields["id"], $rs->fields["plugin"], $rs->fields["plugin_minute"], $rs->fields["plugin_hour"], $rs->fields["plugin_day_month"], $rs->fields["plugin_month"], $rs->fields["plugin_day_week"], $rs->fields["type_scan"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function get_sensors($conn, $id) {
        return Plugin_scheduler_sensor_reference::get_list($conn, $id);
    }
    function get_netgroups($conn, $id) {
        return Plugin_scheduler_netgroup_reference::get_list($conn, $id);
    }
    function get_hostgroups($conn, $id) {
        return Plugin_scheduler_hostgroup_reference::get_list($conn, $id);
    }
    function get_nets($conn, $id) {
        return Plugin_scheduler_net_reference::get_list($conn, $id);
    }
    function get_hosts($conn, $id) {
        return Plugin_scheduler_host_reference::get_list($conn, $id);
    }
    function insert($conn, $plugin, $minute, $hour, $day_month, $month, $day_week, $sensor_list, $netgroup_list, $hostgroup_list, $net_list, $host_list, $type_scan, $id = null) {
        if (!$id) {
            $id = $conn->GenID('plugin_scheduler_seq');
        }
        $sql = "INSERT INTO plugin_scheduler VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $params = array(
            $id,
            $plugin,
            $minute,
            $hour,
            $day_month,
            $month,
            $day_week,
            $type_scan
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        if ($sensor_list != null) {
            Plugin_scheduler_sensor_reference::insert($conn, $sensor_list, $id);
        }
        if ($netgroup_list != null) {
            Plugin_scheduler_netgroup_reference::insert($conn, $netgroup_list, $id);
        }
        if ($hostgroup_list != null) {
            Plugin_scheduler_hostgroup_reference::insert($conn, $hostgroup_list, $id);
        }
        if ($net_list != null) {
            Plugin_scheduler_net_reference::insert($conn, $net_list, $id);
        }
        if ($host_list != null) {
            Plugin_scheduler_host_reference::insert($conn, $host_list, $id);
        }
    }
    function delete($conn, $id) {
        $sql = "DELETE FROM plugin_scheduler WHERE id  = ?";
        $params = array(
            $id
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        Plugin_scheduler_sensor_reference::delete($conn, $id);
    }
}
?>
