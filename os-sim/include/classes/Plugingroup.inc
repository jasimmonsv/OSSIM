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
* - Plugingroup()
* - get_id()
* - get_name()
* - get_description()
* - get_plugins()
* - get_list()
* Classes list:
* - Plugingroup
*/
class Plugingroup {
    var $id; // group id
    var $name; // group name
    var $description; // group description
    var $plugins; // array of plugins/sids
    function Plugingroup($id, $name, $description, $plugins) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->plugins = $plugins;
    }
    function get_id() {
        return $this->id;
    }
    function get_name() {
        return $this->name;
    }
    function get_description() {
        return $this->description;
    }
    /*
    * @returns Ordered array of plugins, each one in the form of an assoc array:
    *           0 => array(
    *                  ['id']    =>   // plugin id
    *                  ['name']  =>   // plugin name
    *                  ['sids']  =>   // plugin sids string
    *                  ['descr'] =>   // plugin description
    *                ),
    */
    function get_plugins() {
        return $this->plugins;
    }
    /*
    * @static
    */
    function get_list($conn, $where = '') {
        if ($where) {
            $where = "AND $where";
        }
        $sql = "SELECT
                    plugin_group_descr.group_id,
                    plugin_group_descr.name as group_name,
                    plugin_group_descr.descr as group_description,
                    plugin_group.plugin_id,
                    plugin_group.plugin_sid,
                    plugin.name as plugin_name,
                    plugin.description as plugin_description
                FROM
                    plugin_group_descr, plugin_group, plugin
                WHERE
                    plugin_group.plugin_id = plugin.id AND
                    plugin_group_descr.group_id = plugin_group.group_id
                    $where
                ORDER BY
                    plugin_group_descr.group_id, plugin.name";
        $list = array();
        if (!$rs = $conn->Execute($sql)) {
            die($conn->ErrorMsg());
        }
        $group = $plugins = array();
        $prev_id = null;
        while (!$rs->EOF) {
            // new group started
            if ($prev_id != $rs->fields['group_id']) {
                // first run, do not store yet
                if ($prev_id !== null) {
                    $list[] = new Plugingroup($group['id'], $group['name'], $group['descr'], $plugins);
                }
                // store the header info of the previous plugin group
                $group['id'] = $rs->fields['group_id'];
                $group['name'] = $rs->fields['group_name'];
                $group['descr'] = $rs->fields['group_description'];
                $plugins = array();
                $prev_id = $rs->fields['group_id'];
            }
            $plugins[$rs->fields['plugin_id']] = array(
                'id' => $rs->fields['plugin_id'],
                'sids' => $rs->fields['plugin_sid'],
                'name' => $rs->fields['plugin_name'],
                'descr' => $rs->fields['plugin_description']
            );
            $rs->MoveNext();
        }
        // store the latest record if there where results
        if (count($group)) {
            $list[] = new Plugingroup($group['id'], $group['name'], $group['descr'], $plugins);
        }
        return $list;
    }
    function can_delete($conn, $group_id) {
        $sql = "SELECT count(*) as num FROM policy_plugin_group_reference WHERE group_id = ?";
        $params = array(
            $group_id
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        if ($rs->fields['num'] == '0') return 1;
        else return 0;
    }
    function delete_plugin_id($conn, $group_id, $pid) {
        $sql = "DELETE FROM plugin_group WHERE group_id = ? and plugin_id = ?";
        $params = array($group_id, $pid);
        if (!$rs = & $conn->Execute($sql, $params)) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
        }
    }

}
?>
