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
* - Plugin_sid()
* - get_plugin_id()
* - get_sid()
* - get_category_id()
* - get_class_id()
* - get_name()
* - get_reliability()
* - get_priority()
* - get_foundrows()
* - get_list()
* - update()
* Classes list:
* - Plugin_sid
*/
require_once ("ossim_db.inc");
class Plugin_sid {
    var $plugin_id;
    var $sid;
    var $category_id;
    var $class_id;
    var $name;
    var $reliability;
    var $priority;
    function Plugin_sid($plugin_id, $sid, $category_id, $class_id, $name, $reliability, $priority, $foundrows = 0) {
        $this->plugin_id = $plugin_id;
        $this->sid = $sid;
        $this->category_id = $category_id;
        $this->class_id = $class_id;
        $this->name = $name;
        $this->reliability = $reliability;
        $this->priority = $priority;
        $this->foundrows = $foundrows;
    }
    function get_plugin_id() {
        return $this->plugin_id;
    }
    function get_sid() {
        return $this->sid;
    }
    function get_category_id() {
        return $this->category_id;
    }
    function get_class_id() {
        return $this->class_id;
    }
    function get_name() {
        return ereg_replace("<([^>]*)>", "(\\1)", $this->name);
    }
    function get_reliability() {
        return $this->reliability;
    }
    function get_priority() {
        return $this->priority;
    }
    function get_foundrows() {
        return $this->foundrows;
    }
    function get_list($conn, $args = "") {
        $iffoundrows = preg_match("/LIMIT/", $args) ? true : false;
        $select = ($iffoundrows) ? "SQL_CALC_FOUND_ROWS *" : "*";
        $query = OssimQuery("SELECT $select FROM plugin_sid $args");
        //echo "SELECT $select FROM plugin_sid $args<br>";
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $foundrows = 0;
            if ($iffoundrows) {
                if (!$rf = & $conn->Execute("SELECT FOUND_ROWS() as total")) print $conn->ErrorMsg();
                else $foundrows = $rf->fields["total"];
            }
            $list = array();
            while (!$rs->EOF) {
                $list[] = new Plugin_sid($rs->fields["plugin_id"], $rs->fields["sid"], $rs->fields["category_id"], $rs->fields["class_id"], $rs->fields["name"], $rs->fields["reliability"], $rs->fields["priority"], $foundrows);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function update($conn, $id, $sid, $priority, $reliability, $name='') {
        settype($priority, "int");
        settype($reliability, "int");
        if ($name!='') {
           $sql = "UPDATE plugin_sid SET priority = ?, reliability = ?, name = ? WHERE plugin_id = ? AND sid = ?";
           $params = array( $priority, $reliability, $name, $id, $sid );
        } else {
           $sql = "UPDATE plugin_sid SET priority = ?, reliability = ? WHERE plugin_id = ? AND sid = ?";
           $params = array( $priority, $reliability, $id, $sid );
        }
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        }
    }
    function get_name_by_idsid($conn,$id,$sid) {
        $query = OssimQuery("SELECT name FROM plugin_sid WHERE plugin_id=$id AND sid=$sid");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
            return 0;
        } else {
            return $rs->fields["name"];
        }
    }
    function get_sids_by_id($conn,$id) {
        $query = OssimQuery("SELECT sid FROM plugin_sid where plugin_id=$id");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list[] = $rs->fields["sid"];
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function search_sids($conn,$query) {
        $query = OssimQuery("SELECT plugin_sid.*,plugin.name as plugin_name FROM plugin_sid LEFT JOIN plugin ON plugin.id=plugin_sid.plugin_id WHERE plugin_sid.name like '%".str_replace("'","\'",$query)."%' ORDER BY plugin_sid.plugin_id");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list[] = $rs->fields;
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $plugin, $name, $sid, $reliability, $priority) {
        settype($plugin, "int");
        settype($sid, "int");
        settype($reliability, "int");
        settype($priority, "int");

        $sql = "INSERT INTO plugin_sid (plugin_id, name, sid, reliability, priority) VALUES (?, ?, ?, ?, ?)";
        $params = array(
            $plugin,
            $name,
            $sid,
            $reliability,
            $priority
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error inserting sid: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
}
?>
