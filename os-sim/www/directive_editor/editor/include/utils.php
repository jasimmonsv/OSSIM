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
* - dbConnect()
* - dbClose()
* - getPluginList()
* - getPluginSidList()
* - getPluginName()
* - getPluginType()
* - getHostList()
* - getHostGroupList()
* - getSensorList()
* - selectIf()
* - disableIf()
* - checkIf()
* - isAny()
* - isSubLevel()
* - isList()
* - init_file()
* - release_file()
* - open_file()
* Classes list:
*/
require_once 'rule.php';
require_once 'directive.php';
require_once 'category.php';
if (version_compare(PHP_VERSION, '5', '>=')) require_once ("domxml-php4-to-php5.php");
require_once 'ossim_db.inc';
require_once 'classes/Plugin.inc';
require_once 'classes/Plugin_sid.inc';
require_once 'classes/Host.inc';
require_once 'classes/Host_group.inc';
require_once 'classes/Sensor.inc';
require_once 'classes/Security.inc';

if (GET('id') != "") ossim_valid(GET('id'), OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("id"));
if (ossim_error()) {
    die(ossim_error());
}

$db = null;
$conn = null;
function dbConnect() {
    global $db, $conn;
    $db = new ossim_db();
    $conn = $db->connect();
}
function dbClose() {
    global $db, $conn;
    $db->close($conn);
}
function getPluginList($req) {
    global $conn;
    if ($plugin_list = Plugin::get_list($conn, $req)) return $plugin_list;
    return '';
}
function getPluginSidList($plugin_id, $req) {
    global $conn;
    if ($plugin_sid_list = Plugin_sid::get_list($conn, 'WHERE plugin_id = ' . $plugin_id . ' ' . $req)) return $plugin_sid_list;
    return '';
}
function getPluginName($plugin_id) {
    global $conn;
    if ($plugin_id != '') {
        $plugins = Plugin::get_list($conn, 'WHERE id = ' . $plugin_id);
        return $plugins[0]->get_name();
    } else return "";
}
function getPluginType($plugin_id) {
    global $conn;
    if ($plugin_id != '') {
        $plugins = Plugin::get_list($conn, 'WHERE id = ' . $plugin_id);
        if ($plugins[0]->get_type() == '1') return 'detector';
        elseif ($plugins[0]->get_type() == '2') return 'monitor';
        return 'other';
    } else return '';
}
function getHostList() {
    global $conn;
    if ($host_list = Host::get_list($conn, '', '')) return $host_list;
    return "";
}
function getHostGroupList() {
    global $conn;
    if ($host_list = Host_group::get_list($conn, '', '')) return $host_list;
    return '';
}
function getSensorList() {
    global $conn;
    if ($host_list = Sensor::get_list($conn, '', '')) return $host_list;
    return "";
}
/* Return "select=true" if the condition is true. */
function selectIf($cond) {
    if ($cond) return ' selected="selected"';
    else return '';
}
/* Return "disabled=true" if the condition is true. */
function disableIf($cond) {
    if ($cond) return ' disabled="disabled"';
    else return '';
}
/* Return "checked=true" if the condition is true. */
function checkIf($cond) {
    if ($cond) return ' checked="checked\"';
    else return '';
}
/* Return true if the value is any. */
function isAny($value) {
    return $value == 'ANY';
}
/* Return true if the value is a sublevel. */
function isSubLevel($value) {
    $split = split(":", $value);
    if ($split[1] == 'PLUGIN_SID') return true;
    if ($split[1] == 'SRC_IP') return true;
    if ($split[1] == 'DST_IP') return true;
    if ($split[1] == 'SRC_PORT') return true;
    if ($split[1] == 'DST_PORT') return true;
    return false;
}
/* Return true if the value is a list. */
function isList($value) {
    return !isAny($value) && !isSubLevel($value);
}
//###########################################################################
/* initializes the file by adding xml tags at the beginning and at the end */
function init_file($xml_file) {
    $tab = file($xml_file);
    unlink($xml_file);
    $fic = fopen($xml_file, 'w');
    if ($fic) {
        fwrite($fic, $tab[0]);
        fwrite($fic, "<directives>");
        for ($i = 1; $i < count($tab); $i++) {
            fwrite($fic, $tab[$i]);
        }
        fwrite($fic, "</directives>");
        fclose($fic);
    }
}
/* remove xml tags and indent the file*/
function release_file($xml_file) {
    $tab = file($xml_file);
    unlink($xml_file);
    $fic = fopen($xml_file, 'w');
    $max = count($tab) - 1;
    while ((trim($tab[$max]) == "") || (trim($tab[$max]) == "</directives>")) {
        $max--;
    }
    if ($fic) {
        $nb = count($tab);
        for ($i = 0; $i <= $max; $i++) {
            if (trim($tab[$i]) != "</directives>") {
                $string = str_replace("<directives>", "", $tab[$i]);
                $string = str_replace("</directives>", "", $string);
                $string = str_replace("><", ">\n<", $string);
                fwrite($fic, $string);
            }
        }
    }
    fclose($fic);
    $tab = file($xml_file);
    unlink($xml_file);
    $fic = fopen($xml_file, 'w');
    $nb_space = 0;
    $previous = "";
    for ($i = 0; $i < count($tab); $i++) {
        if (strcmp("<rules>", trim($tab[$i])) == 0) $nb_space++;
        elseif (strncmp("<rule ", trim($tab[$i]) , 6) == 0 && strncmp("<rule ", $previous, 6) != 0 && strcmp("</rule>", $previous) != 0) $nb_space++;
        elseif (strcmp("</rule>", trim($tab[$i])) == 0 && strncmp("<rule ", $previous, 6) != 0) $nb_space--;
        elseif (strcmp("</rules>", trim($tab[$i])) == 0 && strcmp("<rules>", $previous) != 0) $nb_space--;
        elseif (strcmp("</directive>", trim($tab[$i])) == 0 || strncmp("<directive ", $previous, 11) == 0) $nb_space = 0;
        $previous = trim($tab[$i]);
        $space = "";
        for ($j = 0; $j < $nb_space; $j++) $space = $space . "   ";
        if (trim($tab[$i]) != '') {
            $string = $space . trim($tab[$i]) . "\n";
            if (strcmp("</directive>", trim($string)) == 0 || strncmp("<?xml ", trim($string) , 6) == 0) $string = $string . "\n";
            fwrite($fic, $string);
        }
    }
    fclose($fic);
}
function open_file($file) {
    init_file($file);
    if (!$dom = domxml_open_file($file, DOMXML_LOAD_SUBSTITUTE_ENTITIES)) {
        echo _("Error while parsing the document")."\n";
        exit;
    }
    return $dom;
}
//###########################################################################
$query = $_GET['query'];
ossim_valid($query, OSS_ALPHA, OSS_SCORE, OSS_NULLABLE, 'illegal:'._("query"));
if (ossim_error()) {
	die(ossim_error());
}
if ($query != "") dbConnect();
if ($query == 'get_plugin_name') {
    $plugin_id = $_GET['plugin_id'];
    $plugin_list = getPluginList('');
    foreach($plugin_list as $plugin) {
        if ($plugin->get_id() == $plugin_id) {
            print $plugin->get_name();
            break;
        }
    }
} elseif ($query == 'get_plugin_type') {
    $plugin_id = $_GET['plugin_id'];
    $plugin_list = getPluginList('');
    foreach($plugin_list as $plugin) {
        if ($plugin->get_id() == $plugin_id) {
            if ($plugin->get_type() == "1") echo "detector";
            elseif ($plugin->get_type() == "2") echo "monitor";
            else echo "other";
            break;
        }
    }
} elseif ($query == 'is_plugin_sid_list') {
    $plugin_id = $_GET['plugin_id'];
    $plugin_sid_list = $_GET['plugin_sid_list'];
    $plugin_sid_array = split(',', $plugin_sid_list);
    $req = 'AND (';
    foreach($plugin_sid_array as $sid) {
        $req.= (($req == 'AND (') ? '' : ' OR ') . "sid = $sid";
    }
    $req.= ')';
    $plugin_list = getPluginSidList($plugin_id, $req);
    if (is_array($plugin_list) && count($plugin_list) == count($plugin_sid_array)) echo "true";
    else echo "false";
} elseif ($query == 'get_category_id_by_directive_id') {
    $directive_id = $_GET['directive_id'];
    echo get_category_id_by_directive_id($directive_id);
} elseif ($query == 'get_new_directive_id') {
    $category_id = $_GET['category_id'];
    echo new_directive_id($category_id);
} elseif ($query == "restart") {
    exec('sudo /etc/init.d/ossim-server restart');
}
/* Test if the directive id is free */
elseif ($query == 'is_free') {
    $directive = $_GET['directive'];
    echo is_free($directive);
} elseif ($query == 'check_mini_maxi') {
    $current_category_id = $_GET['current_category_id'];
    $mini = $_GET['mini'];
    $maxi = $_GET['maxi'];
    echo check_mini_maxi($current_category_id, $mini, $maxi);
}
/* Test if the file exists */
elseif ($query == 'file_exists') {
    $xml_file = $_GET['xml_file'];
    echo (file_exists('/etc/ossim/server/' . $xml_file)) ? "true" : "false";
}
/* Return the category object corresponding to the directive id*/
elseif ($query == 'get_category_by_iddir') {
    $directive = $_GET['directive'];
    echo get_category_by_iddir($directive);
}
/* Edit a rule*/
elseif ($query == "edit_rule") {
    $directive = unserialize($_SESSION['directive']);
    $tab_rules = $directive->rules;
    $rule_id = $_GET["id"];
    list($id_dir, $id_rule, $id_father) = explode("-", $rule_id);
    $_SESSION['rule'] = serialize($tab_rules[$id_rule]);
    //echo "<html><body onload=\"window.open('../right.php?directive=" . $id_dir . "&level=" . $tab_rules[$id_rule]->level . "&action=edit_rule&id=" . $rule_id . "','right')\"></body></html>";
    echo "<script type='text/javascript'>document.location.href='../viewer/index.php?directive=$id_dir&level=".$tab_rules[$id_rule]->level."'</script>";
}
/* Create a new rule object and edit this new rule*/
elseif ($query == "add_rule") {
	$directive = unserialize($_SESSION['directive']);
    $tab_rules = $directive->rules;
    $id = $_GET['id'];
    unset($_SESSION['rule']);
    list($id_dir, $id_rule, $id_father) = explode("-", $id);
    if ($id_father != 0) {
        $newlevel = $tab_rules[$id_father]->level + 1;
        $tab_rules[$id_father]->nb_child = $tab_rules[$id_father]->nb_child + 1;
    } else {
        $newlevel = 1;
    }
    $temp = new Rule($id, $newlevel, "", "New rule", "", "", "ANY", "ANY", "ANY", "ANY", "ANY", "ANY", "ANY", "1", "None", "0", "Default", "Default", "Default", "Default", "Default", "Default", "Default", "", "", "", "", "", "", "", "", "", "", "", "", "");
    $_SESSION['rule'] = serialize($temp);
    //echo "<html><body onload=\"window.open('../right.php?directive=" . $id_dir . "&level=$newlevel&action=add_rule&id=" . $id . "','right')\"></body></html>";
    echo "<script type='text/javascript'>document.location.href='../viewer/index.php?directive=$id_dir&level=$newlevel'</script>";
}
/* create a new rule object with parameters from the form*/
elseif ($query == "save_rule") {
    if ($_POST["plugin_sid"] == "LIST") $_POST["plugin_sid"] = $_POST["plugin_sid_list"];
    if ($_POST["from"] == "LIST") $_POST["from"] = $_POST["from_list"];
    if ($_POST["port_from"] == "LIST") $_POST["port_from"] = $_POST["port_from_list"];
    if ($_POST["to"] == "LIST") $_POST["to"] = $_POST["to_list"];
    if ($_POST["port_to"] == "LIST") $_POST["port_to"] = $_POST["port_to_list"];
    if ($_POST["protocol_any"]) {
        $protocol = "ANY";
    } else {
        $protocol = "";
        if ($_POST["protocol_arp"]) {
            if ($protocol != "") $protocol.= ",";
            $protocol.= "Host_ARP_Event";
        }
        if ($_POST["protocol_ids"]) {
            if ($protocol != "") $protocol.= ",";
            $protocol.= "Host_IDS_Event";
        }
        if ($_POST["protocol_os"]) {
            if ($protocol != "") $protocol.= ",";
            $protocol.= "Host_OS_Event";
        }
        if ($_POST["protocol_service"]) {
            if ($protocol != "") $protocol.= ",";
            $protocol.= "Host_Service_Event";
        }
        if ($_POST["protocol_tcp"]) {
            if ($protocol != "") $protocol.= ",";
            $protocol.= "Host_TCP_Event";
        }
        if ($_POST["protocol_udp"]) {
            if ($protocol != "") $protocol.= ",";
            $protocol.= "Host_UDP_Event";
        }
        if ($_POST["protocol_icmp"]) {
            if ($protocol != "") $protocol.= ",";
            $protocol.= "Host_ICMP_Event";
        }
        for ($i = 1; isset($_POST['protocol_' . $i]); $i++) {
            if ($protocol != '') $protocol.= ',';
            $protocol.= $i . ':PROTOCOL';
        }
    }
    if ($_POST["sensor"] == "LIST") $_POST["sensor"] = $_POST["sensor_list"];
    if ($_POST["occurrence"] == "LIST") $_POST["occurrence"] = $_POST["occurrence_list"];
    if ($_POST["time_out"] == "LIST") $_POST["time_out"] = $_POST["time_out_list"];
    if ($_POST["reliability_op"] == "+") $_POST["reliability"] = "+" . $_POST["reliability"];
    $rule = unserialize($_SESSION['rule']);
    $rule->id = $_POST["id"];
    $rule->level = $_POST["level"];
    $rule->name = stripslashes($_POST["name"]);
    $rule->plugin_id = $_POST["plugin_id"];
    $rule->plugin_type = $_POST["type"];
    $rule->plugin_sid = $_POST["plugin_sid"];
    $rule->from = $_POST["from"];
    $rule->port_from = $_POST["port_from"];
    $rule->to = $_POST["to"];
    $rule->port_to = $_POST["port_to"];
    $rule->protocol = $protocol;
    $rule->sensor = $_POST["sensor"];
    $rule->occurrence = $_POST["occurrence"];
    $rule->time_out = $_POST["time_out"];
    $rule->reliability = $_POST["reliability"];
    $rule->condition = $_POST["condition"];
    $rule->value = $_POST["value"];
    $rule->interval = $_POST["interval"];
    $rule->absolute = $_POST["absolute"];
    $rule->sticky = $_POST["sticky"];
    $rule->sticky_different = $_POST["sticky_different"];
    $rule->groups = $_POST["groups"];
    $rule->iface = $_POST["iface"];
    $rule->filename = $_POST["filename"]; 
    $rule->username = $_POST["username"];
    $rule->password = $_POST["password"];
    $rule->userdata1 = $_POST["userdata1"];
    $rule->userdata2 = $_POST["userdata2"];
    $rule->userdata3 = $_POST["userdata3"];
    $rule->userdata4 = $_POST["userdata4"];
    $rule->userdata5 = $_POST["userdata5"];
    $rule->userdata6 = $_POST["userdata6"];
    $rule->userdata7 = $_POST["userdata7"];
    $rule->userdata8 = $_POST["userdata8"];
    $rule->userdata9 = $_POST["userdata9"];
    $_SESSION['rule'] = serialize($rule);
    $directive = $_POST["directive"];
    $level = $_POST["level"];
    $id = $_POST["id"];
    insert($id);
}
/* Delete a rule*/
elseif ($query == "del_rule") {
    $directive = unserialize($_SESSION['directive']);
    $XML_FILE = get_directive_file($directive->id);
    $dom = open_file($XML_FILE);
    $direct = getDirectiveFromXML($dom, $directive->id);
    $tab_rules = $direct->rules;
    $_SESSION['directive'] = serialize($direct);
    list($id_dir, $id_rule, $id_father) = explode("-", $_GET['id']);
    if ($tab_rules[$id_rule]->level == 1) $level = $tab_rules[$id_rule]->level;
    else $level = $tab_rules[$id_rule]->level - 1;
    delrule($_GET['id'], &$tab_rules);
    $dom->dump_file($XML_FILE);
    release_file($XML_FILE);
    echo "<html><body onload=\"window.open('../viewer/index.php?directive=" . $directive->id . "&level=" . $level . "','right')\"></body></html>";
}
/* cancel adding a new rule */
elseif ($query == "del_new_rule") {
    unset($_SESSION['rule']);
    $directive = unserialize($_SESSION['directive']);
    echo "<html><body onload=\"window.open('../viewer/index.php?directive=" . $directive->id . "&level=" . $_GET['level'] . "','right')\"></body></html>";
}
/* delete all the rules */
elseif ($query == "del_all_rule") {
    $directive = unserialize($_SESSION['directive']);
    $tab_rules = $directive->rules;
    $XML_FILE = get_directive_file($directive->id);
    $dom = open_file($XML_FILE);
    $direct = getDirectiveFromXML($dom, $directive->id);
    $tab_rules = $direct->rules;
    $_SESSION['directive'] = serialize($direct);
    $stock = array();
    for ($i = 0; $i <= count($tab_rules); $i++) {
        list($id_dir, $id_rule, $id_father) = explode("-", $tab_rules[$i]->id);
        if ($id_father == 0) $stock[] = $tab_rules[$i];
    }
    for ($i = 1; $i < count($stock); $i++) {
        list($id_dir, $id_rule, $id_father) = explode("-", $stock[$i]->id);
        $rule = $tab_rules[$id_rule]->rule;
        $parent = $rule->parent_node();
        $res = $parent->remove_child($rule);
    }
    $dom->dump_file($XML_FILE);
    release_file($XML_FILE);
    echo "<html><body onload=\"window.open('../viewer/index.php?directive=" . $directive->id . "&level=1','right')\"></body></html>";
}
/* Move a rule in a direction if it is allowed */
elseif ($query == "move") {
    $directive = unserialize($_SESSION['directive']);
    $XML_FILE = get_directive_file($directive->id);
    $dom = open_file($XML_FILE);
    $direct = getDirectiveFromXML($dom, $directive->id);
    $tab_rules = $direct->rules;
    $move = $_GET['direction'];
    switch ($move) {
        case 'left':
            left($dom, $_GET['id'], &$tab_rules, $direct);
            break;

        case 'right':
            right($dom, $_GET['id'], &$tab_rules, $direct);
            break;

        case 'up':
            up($dom, $_GET['id'], &$tab_rules, $direct);
            break;

        case 'down':
            down($dom, $_GET['id'], &$tab_rules, $direct);
            break;
    }
    $dom->dump_file($XML_FILE);
    release_file($XML_FILE);
    echo "<html><body onload=\"window.open('../viewer/index.php?directive=" . $direct->id . "&level=" . $_POST['new_level'] . "','right')\"></body></html>";
}
/* save a directive */
elseif ($query == "save_directive") {
    $new = FALSE;
    $directive = unserialize($_SESSION['directive']);
    $new_id = $_POST["iddir"];
    $new_priority = $_POST["priority"];
    $new_name = stripslashes($_POST["name"]);
    $XML_FILE = get_directive_file($new_id);
    $dom = open_file($XML_FILE);
    $new_directive = $directive;
    $new_directive->id = $new_id;
    $new_directive->priority = $new_priority;
    $new_directive->name = $new_name;
    $_SESSION['directive'] = serialize($new_directive);
    $node = $dom->create_element('directive');
    $node->set_attribute('id', $new_directive->id);
    $node->set_attribute('name', $new_directive->name);
    $node->set_attribute('priority', $new_directive->priority);
    $tab_directives = $dom->get_elements_by_tagname('directives');
    $directives = $tab_directives[0];
    /* case of a new directive */
    if (is_free($directive->id) == "true") {
        $new_directive->directive = $node;
        $_SESSION['directive'] = serialize($new_directive);
        $directives->append_child($node);
        $dom->dump_file($XML_FILE);
        $new_rule_id = $new_id . "-1-0";
        echo "<html><body onload=\"window.open('../index.php?directive=" . $new_directive->id . "&level=1&action=add_rule&id=" . $new_rule_id . "&nlevel=1','main')\"></body></html>";
    }
    /* if it amends an existing directive */
    else {
        $tab_rules = $directive->rules;
        for ($ind = 1; $ind <= count($tab_rules); $ind++) {
            $rule = $tab_rules[$ind];
            list($id_dir, $id_rule, $id_father) = explode("-", $rule->id);
            if ($id_father == 0) {
                $new_node = $tab_rules[$id_rule]->getXMLNode($dom);
                $new_node = $node->append_child($new_node);
                $tab_rules[$id_rule]->rule = $new_node;
                if ($tab_rules[$id_rule]->nb_child > 0) {
                    do_rules($id_rule, &$tab_rules, $dom);
                }
            }
        }
        $new_directive->directive = $node;
        $_SESSION['directive'] = serialize($new_directive);
        $OLD_XML_FILE = get_directive_file($directive->id);
        if ($OLD_XML_FILE == $XML_FILE) $dom2 = $dom;
        /* if changes category */
        else {
            $dom2 = open_file($OLD_XML_FILE);
        }
        $tab_directive = $dom2->get_elements_by_tagname('directive');
        foreach($tab_directive as $direct) {
            if ($direct->get_attribute('id') == $directive->id) $old_node = $direct;
        }
        $parent = $old_node->parent_node();
        /* if it amends the directive id*/
        if (is_free($new_directive->id) == "true") {
            $parent->remove_child($old_node);
            $directives->append_child($node);
        }
        /* if it keep the directive id*/
        else {
            $old_node->replace_node($node);
        }
        $dom->dump_file($XML_FILE);
        $dom2->dump_file($OLD_XML_FILE);
        if ($OLD_XML_FILE != $XML_FILE) release_file($OLD_XML_FILE);
    }
    release_file($XML_FILE);
    echo "<html><body onload=\"window.open('../index.php?directive=" . $new_directive->id . "&level=1','main')\"></body></html>";
}
/* Delete a directive */
elseif ($query == "delete_directive") {
    $dir_id = $_GET['id'];
    $file = get_directive_file($dir_id);
    $dom = open_file($file);
    $tab_directive = $dom->get_elements_by_tagname('directive');
    foreach($tab_directive as $lign) if ($lign->get_attribute('id') == $dir_id) $directive = $lign;
    $parent = $directive->parent_node();
    $parent->remove_child($directive);
    $dom->dump_file($file);
    release_file($file);
    echo "<html><body onload=\"window.open('../index.php','main')\"></body></html>";
}
/* Add a directive */
elseif ($query == "add_directive") {
    $cat_id = $_GET['id'];
    $category = get_category_by_id($cat_id);
    $XML_FILE = "/etc/ossim/server/" . $category->xml_file;
    $dom = open_file($XML_FILE);
    $id = new_directive_id($category->id);
    $null = NULL;
    $node = $dom->create_element('directive');
    $node->set_attribute('id', $id);
    $node->set_attribute('name', "New directive");
    $node->set_attribute('priority', "0");
    $directive = new Directive($id, "New directive", "0", $null, $node);
    $_SESSION['directive'] = serialize($directive);
    release_file($XML_FILE);
    echo "<html><body onload=\"window.open('../right.php?directive=" . $id . "&level=1&action=edit_dir&id=" . $id . "','right')\"></body></html>";
}
/* Save a category */
elseif ($query == "save_category") {
    $file = '/etc/ossim/server/categories.xml';
    if (!$dom = domxml_open_file($file, DOMXML_LOAD_SUBSTITUTE_ENTITIES)) {
        echo _("Error while parsing the document")."\n";
        exit;
    }
    $category = unserialize($_SESSION['category']);
    $oldid = $category->id;
    $oldfile = $category->xml_file;
    $new_node = $dom->create_element('category');
    $new_node->set_attribute("name", $_POST["name"]);
    $new_node->set_attribute("xml_file", $_POST["xml_file"]);
    $new_node->set_attribute("mini", $_POST["mini"]);
    $new_node->set_attribute("maxi", $_POST["maxi"]);
    $_SESSION['category'] = serialize($category);
    if (get_category_by_id($oldid) != NULL) {
        $tab_category = $dom->get_elements_by_tagname('category');
        foreach($tab_category as $cat) {
            if ($cat->get_attribute('xml_file') == $oldfile) $node = $cat;
        }
        $node->replace_node($new_node);
        $dom->dump_file($file);
        if ($_POST["xml_file"] != $oldfile) {
            $filet = "/etc/ossim/server/" . $oldfile;
            $filen = "/etc/ossim/server/" . $_POST["xml_file"];
            $tab_file = file($filet);
            unlink($filet);
            $fic = fopen($filen, 'w');
            foreach($tab_file as $lign) fwrite($fic, $lign);
            fclose($fic);
        }
    } else {
        $categories = $dom->get_elements_by_tagname('categories');
        $categories = $categories[0];
        $categories->append_child($new_node);
        $dom->dump_file($file);
    }
    echo "<html><body onload=\"window.open('../index.php','main')\"></body></html>";
}
/* delete a category */
elseif ($query == "delete_category") {
    $file = '/etc/ossim/server/categories.xml';
    if (!$dom = domxml_open_file($file, DOMXML_LOAD_SUBSTITUTE_ENTITIES)) {
        echo _("Error while parsing the document")."\n";
        exit;
    }
    $id = $_GET['id'];
    $category = get_category_by_id($id);
    $tab_category = $dom->get_elements_by_tagname('category');
    foreach($tab_category as $cat) {
        if ($cat->get_attribute('xml_file') == $category->xml_file) $node = $cat;
    }
    $parent = $node->parent_node();
    $parent->remove_child($node);
    $dom->dump_file($file);
    unlink("/etc/ossim/server/" . $category->xml_file);
    echo "<html><body onload=\"window.open('../index.php','main')\"></body></html>";
}
if ($query != "") dbClose();
?>
