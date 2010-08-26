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
* - Group()
* - init_groups()
* - get_group_by_name()
* - insert_dir_in_group()
* - delete_dir_from_groups()
* - set_groups()
* Classes list:
* - Group
*/
require_once ("utils.php");
require_once ('classes/Session.inc');
/**
 * This class represents a Category, a collection of a name, an id, a xml file
 * a mini and a maxi. It can manage the different categories and associated files.
 */
class Group {
    /**
     * The group name
     * @ccess private
     * @var string
     */
    var $name;
    /**
     * The list of directives contained in the group
     * @ccess private
     * @var array
     */
    var $list;
    /**
     *The constructor
     */
    function Group($name, $list) {
        $this->name = $name;
        $this->list = $list;
    }
}
/* Create group array */
function init_groups() {
    if (!$dom = domxml_open_file('/etc/ossim/server/groups.xml', DOMXML_LOAD_SUBSTITUTE_ENTITIES)) {
        echo _("Error while parsing the document")."\n";
        exit;
    }
    $table = $dom->get_elements_by_tagname('group');
    $tab_group = array();
    foreach($table as $lign) {
        $tab_directive = array();
        $tab_tmp = $lign->child_nodes();
        foreach($tab_tmp as $child) if ($child->type != 3) $tab_directive[] = $child->get_attribute('directive_id');
        $temp = new Group($lign->get_attribute('name') , $tab_directive);
        $tab_group[] = $temp;
    }
    $_SESSION['groups'] = serialize($tab_group);
}
function get_group_by_name($name) {
    // browses the elements of $_SESSION['groups']
    // search the one witch has the right name
    $groups = unserialize($_SESSION['groups']);
    foreach($groups as $group) {
        if ($group->name == $name) return $group;
    }
    return NULL;
}
function insert_dir_in_group($dir_id, $group_name) {
    $file = '/etc/ossim/server/groups.xml';
    if (!$dom = domxml_open_file($file, DOMXML_LOAD_SUBSTITUTE_ENTITIES)) {
        echo _("Error while parsing the document")."\n";
        exit;
    }
    $table = $dom->get_elements_by_tagname('group');
    foreach($table as $lign) {
        if (in_array($lign->get_attribute('name') , split(',', $group_name))) {
        	$new_child = $dom->create_element('append-directive');
            $new_child->set_attribute("directive_id", $dir_id);
            //$lign->append_child($new_child);
        }
    }
    $dom->dump_file($file);
}
function delete_dir_from_groups($dir_id) {
    $file = '/etc/ossim/server/groups.xml';
    if (!$dom = domxml_open_file($file, DOMXML_LOAD_SUBSTITUTE_ENTITIES)) {
        echo _("Error while parsing the document")."\n";
        exit;
    }
    $table = $dom->get_elements_by_tagname('group');
    foreach($table as $lign) {
        $tab_tmp = $lign->child_nodes();
        foreach($tab_tmp as $child) {
            if ($child->type != 3 && $child->get_attribute('directive_id') == $dir_id) {
                $lign->remove_child($child);
            }
        }
    }
    $dom->dump_file($file);
}
function set_groups($old_id, $new_id, $new_group) {
    $groups = unserialize($_SESSION['groups']);
    $file = '/etc/ossim/server/groups.xml';
    if (!$dom = domxml_open_file($file, DOMXML_LOAD_SUBSTITUTE_ENTITIES)) {
        echo _("Error while parsing the document")."\n";
        exit;
    }
    $table = $dom->get_elements_by_tagname('group');
    $tab_group = array();
    foreach($groups as $group) {
        if (in_array($old_id, $group->list)) {
            if ($list_group != "") $list_group.= ",";
            $list_group.= $group->name;
        }
    }
    if ($old_id == $new_id && $new_group != $list_group) {
        foreach($table as $lign) {
            $insert = false;
            $tab_tmp = $lign->child_nodes();
            foreach($tab_tmp as $child) {
                if ($child->type != 3 && $child->get_attribute('directive_id') == $new_id && in_array($lign->get_attribute('name') , split(',', $list_group)) && !in_array($lign->get_attribute('name') , split(',', $new_group))) {
                    $lign->remove_child($child);
                } elseif ($child->type != 3 && !$insert && !in_array($lign->get_attribute('name') , split(',', $list_group)) && in_array($lign->get_attribute('name') , split(',', $new_group))) {
                    $new_child = $dom->create_element('append-directive');
                    $new_child->set_attribute("directive_id", $new_id);
                    $lign->append_child($new_child);
                    $insert = true;
                }
            }
        }
    } elseif ($old_id != $new_id && $new_group == $list_group) {
        foreach($table as $lign) {
            $tab_tmp = $lign->child_nodes();
            foreach($tab_tmp as $child) {
                if ($child->type != 3 && $child->get_attribute('directive_id') == $old_id) {
                    $new_child = $dom->create_element('append-directive');
                    $new_child->set_attribute("directive_id", $new_id);
                    $lign->replace_child($new_child, $child);
                }
            }
        }
    } elseif ($old_id != $new_id && $new_group != $list_group) {
        foreach($table as $lign) {
            $tab_tmp = $lign->child_nodes();
            foreach($tab_tmp as $child) {
                if ($child->type != 3 && $child->get_attribute('directive_id') == $old_id && in_array($lign->get_attribute('name') , split(',', $new_group)) && in_array($lign->get_attribute('name') , split(',', $list_group))) {
                    $new_child = $dom->create_element('append-directive');
                    $new_child->set_attribute("directive_id", $new_id);
                    $lign->replace_child($new_child, $child);
                } elseif ($child->type != 3 && $child->get_attribute('directive_id') == $old_id && !in_array($lign->get_attribute('name') , split(',', $new_group)) && in_array($lign->get_attribute('name') , split(',', $list_group))) {
                    $lign->remove_child($child);
                } elseif ($child->type != 3 && $child->get_attribute('directive_id') == $old_id && !in_array($lign->get_attribute('name') , split(',', $list_group)) && in_array($lign->get_attribute('name') , split(',', $new_group))) {
                    $new_child = $dom->create_element('append-directive');
                    $new_child->set_attribute("directive_id", $new_id);
                    $lign->append_child($new_child);
                }
            }
        }
    }
    $dom->dump_file($file);
}
?>