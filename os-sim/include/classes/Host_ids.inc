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
* - Host_ids()
* - get_ip()
* - get_date()
* - get_hostname()
* - get_sensor()
* - get_sid()
* - get_event_type()
* - get_what()
* - get_target()
* - get_extra_data()
* - get_count()
* - get_list()
* - get_list_reduced()
* - delete()
* - get_desc()
* - beautify_what()
* - Events()
* Classes list:
* - Host_ids
*/
require_once ("ossim_db.inc");
class Host_ids {
    var $ip;
    var $date;
    var $hostname;
    var $sensor;
    var $sid;
    var $event_type;
    var $what;
    var $target;
    var $extra_data;
    var $count;
    function Host_ids($ip, $date, $hostname, $sensor, $sid, $event_type, $what, $target, $extra_data, $count) {
        $this->ip = $ip;
        $this->date = $date;
        $this->hostname = $hostname;
        $this->sensor = $sensor;
        $this->sid = $sid;
        $this->event_type = $event_type;
        $this->what = $what;
        $this->target = $target;
        $this->extra_data = $extra_data;
        $this->count = $count;
    }
    function get_ip() {
        return $this->ip;
    }
    function get_date() {
        return $this->date;
    }
    function get_hostname() {
        return $this->hostname;
    }
    function get_sensor() {
        return $this->sensor;
    }
    function get_sid() {
        return $this->sid;
    }
    function get_event_type() {
        return $this->event_type;
    }
    function get_what() {
        return $this->what;
    }
    function get_target() {
        return $this->target;
    }
    function get_extra_data() {
        return $this->extra_data;
    }
    function get_count() {
        return $this->count;
    }
    function get_list($conn, $where = "", $order = "") {
        $query = OssimQuery("SELECT * FROM host_ids $where $order");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list[] = new Host_ids($rs->fields["inet_ntoa(ip)"], $rs->fields["get_date"], $rs->fields["hostname"], $rs->fields["sensor"], $rs->fields["sid"], $rs->fields["event_type"], $rs->fields["what"], $rs->fields["target"], $rs->fields["extra_data"], 0);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function get_list_reduced($conn, $where = "", $order = "") {
        $list = array();
        $query = OssimQuery("SELECT *, count(sid), inet_ntoa(ip) FROM host_ids $where $order");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                $list[] = new Host_ids($rs->fields["inet_ntoa(ip)"], $rs->fields["date"], $rs->fields["hostname"], $rs->fields["sensor"], $rs->fields["sid"], $rs->fields["event_type"], $rs->fields["what"], $rs->fields["target"], $rs->fields["extra_data"], $rs->fields["count(sid)"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function delete($conn, $ip, $date, $sid, $target) {
        settype($sid, "int");
        $sql = "DELETE FROM host_ids WHERE ip = inet_aton(?)
                AND date = ? AND sid = ? AND targe = ?";
        $params = array(
            $ip,
            $date,
            $sid,
            $target
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function get_desc($conn, $sid, $hids_type = "Osiris") {
        $sid = intval($sid);
        if ($hids_type == "Osiris") {
            $sql = "SELECT name FROM plugin_sid WHERE plugin_id = 4001 AND sid = ?";
            $params = array(
                $sid
            );
            if ($rs = & $conn->Execute($sql, $params) && (!$rs->EOF)) {
                return $rs->fields["name"];
            } else {
                return $sid;
            }
        }
    }
    function beautify_what($what, $hids_ype = "Osiris") {
        list($function, $extra_data) = split(":", $what, 2);
        switch ($function) {
            case 'device':
                return _("Device");
            case 'inode':
                return _("Inode");
            case 'permissions':
                return _("Permissions");
            case 'links':
                return _("Links");
            case 'uid':
                return _("User ID");
            case 'gid':
                return _("Group ID");
            case 'mtime':
                return _("Modification Time");
            case 'atime':
                return _("Access Time");
            case 'ctime':
                return _("Inode Change Time");
            case 'device_type':
                return _("Device Type");
            case 'blocks':
                return _("Blocks");
            case 'block_size':
                return _("Block Size");
            case 'bytes':
                return _("Bytes");
            case 'group':
                return (_("Group: ") . $extra_data);
            case 'kern':
                return (_("Kernel: ") . $extra_data);
            case 'user':
                return (_("User: ") . $extra_data);
            default:
                return $what;
        }
    }
    function Events($limit = 10) {
        $db = new ossim_db();
        $conn = $db->connect();
        $list = array();
        $sql = "SELECT *, count(sid) as occurrences, inet_ntoa(ip) as host FROM host_ids GROUP BY host ORDER BY occurrences DESC LIMIT ?";
        $params = array(
            $limit
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                $tmp_array = array(
                    $rs->fields["host"],
                    $rs->fields["occurrences"]
                );
                array_push($list, $tmp_array);
                $rs->MoveNext();
            }
        }
        $db->close($conn);
        return $list;
    }
}
?>
