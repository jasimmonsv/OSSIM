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
* - getNetList()
* - getSensorList()
* - selectIf()
* - disableIf()
* - checkIf()
* - isAny()
* - isSubLevel()
* - isList()
* - init_file()
* - release_file()
* - indent_categories()
* - indent_groups()
* - open_file()
* Classes list:
*/
require_once 'rule.php';
require_once 'directive.php';
require_once 'category.php';
require_once 'groups.php';
if (version_compare(PHP_VERSION, '5', '>=')) require_once ("domxml-php4-to-php5.php");
require_once 'ossim_db.inc';
require_once 'classes/Plugin.inc';
require_once 'classes/Plugin_sid.inc';
require_once 'classes/Host.inc';
require_once 'classes/Host_group.inc';
require_once 'classes/Log_action.inc';
require_once 'classes/Sensor.inc';
require_once 'classes/Net.inc';
require_once 'classes/Port.inc';
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
function getNetList() {
    global $conn;
    if ($net_list = Net::get_list($conn, '', '')) return $net_list;
    return "";
}
function getPortList() {
    global $conn;
    if ($port_list = Port::get_list($conn, '', '')) return $port_list;
    return "";
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
    return !isAny($value); // && !isSubLevel($value);
    
}
//###########################################################################
/* initializes the file by adding xml tags at the beginning and at the end */
function init_file($xml_file) {
    $tab = file($xml_file);
    if (file_exists($xml_file)) unlink($xml_file);
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
/* indent categories.xml*/
function indent_categories() {
    $xml_file = "/etc/ossim/server/categories.xml";
    $tab = file($xml_file);
    unlink($xml_file);
    $fic = fopen($xml_file, 'w');
    if ($fic) {
        $nb = count($tab);
        for ($i = 0; $i <= $nb; $i++) {
            $string = str_replace("><", ">\n<", $tab[$i]);
            fwrite($fic, $string);
        }
    }
    fclose($fic);
    $tab = file($xml_file);
    unlink($xml_file);
    $fic = fopen($xml_file, 'w');
    $space = "";
    for ($i = 0; $i < count($tab); $i++) {
        if (strncmp("<category ", trim($tab[$i]) , 10) == 0) $space = "   ";
        else $space = "";
        if (trim($tab[$i]) != '') {
            $string = $space . trim($tab[$i]) . "\n";
            if (strncmp("<?xml ", trim($string) , 6) == 0) $string = $string . "\n";
            fwrite($fic, $string);
        }
    }
    fclose($fic);
}
/* indent groups.xml*/
function indent_groups() {
    $xml_file = "/etc/ossim/server/groups.xml";
    $tab = file($xml_file);
    unlink($xml_file);
    $fic = fopen($xml_file, 'w');
    if ($fic) {
        $nb = count($tab);
        for ($i = 0; $i <= $nb; $i++) {
            $string = str_replace("><", ">\n<", $tab[$i]);
            fwrite($fic, $string);
        }
    }
    fclose($fic);
    $tab = file($xml_file);
    unlink($xml_file);
    $fic = fopen($xml_file, 'w');
    $space = "";
    for ($i = 0; $i < count($tab); $i++) {
        if (strncmp("<group ", trim($tab[$i]) , 7) == 0 || strcmp("</group>", trim($tab[$i])) == 0) $space = "   ";
        elseif (strncmp("<append-directive ", trim($tab[$i]) , 18) == 0) $space = "      ";
        else $space = "";
        if (trim($tab[$i]) != '') {
            $string = $space . trim($tab[$i]) . "\n";
            if (strcmp("</group>", trim($string)) == 0 || strncmp("<?xml ", trim($string) , 6) == 0) $string = $string . "\n";
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
require_once 'classes/Security.inc';
$query = GET('query');
ossim_valid($query, OSS_ALPHA, OSS_PUNC, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("Query"));
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
    $plugin_sid_list = ereg_replace("[0-9]+:PLUGIN_SID", "", $plugin_sid_list);
    $plugin_sid_list = ereg_replace(",,", ",", $plugin_sid_list);
    $plugin_sid_list = ereg_replace("^,", "", $plugin_sid_list);
    $plugin_sid_list = ereg_replace(",$", "", $plugin_sid_list);
    if ($plugin_sid_list == '') {
        echo "true";
    } else {
        $plugin_sid_array = split(',', $plugin_sid_list);
        $req = 'AND (';
        foreach($plugin_sid_array as $sid) {
            $req.= (($req == 'AND (') ? '' : ' OR ') . "sid = $sid";
        }
        $req.= ')';
        $plugin_list = getPluginSidList($plugin_id, $req);
        if (is_array($plugin_list) && count($plugin_list) == count($plugin_sid_array)) echo "true";
        else echo "false";
    }
} elseif ($query == 'is_directive_list') {
    $list = $_GET['directive_list'];
    if ($list == '') {
        echo "true";
    } else {
        $directive_id_array = split(',', $list);
        if (!$dom = domxml_open_file('/etc/ossim/server/directives.xml', DOMXML_LOAD_SUBSTITUTE_ENTITIES)) {
            echo _("Error while parsing the document")."\n";
            exit;
        }
        $table = array();
        $table_dir = $dom->get_elements_by_tagname('directive');
        foreach($table_dir as $dir) {
            $table[] = $dir->get_attribute('id');
        }
        $test = true;
        foreach($directive_id_array as $dir) {
            if (!in_array($dir, $table)) $test = false;
        }
        if ($test) echo "true";
        else echo "false";
    }
} elseif ($query == 'is_group_list') {
    $list = $_GET['group_list'];
    if ($list == '') {
        echo "true";
    } else {
        $group_array = split(',', $list);
        $groups = unserialize($_SESSION['groups']);
        $table = array();
        foreach($groups as $group) $table[] = $group->name;
        $test = true;
        foreach($group_array as $group) if (!in_array($group, $table)) $test = false;
        if ($test) echo "true";
        else echo "false";
    }
} elseif ($query == 'is_free_group') {
    $name = $_GET['name'];
    if (!$dom = domxml_open_file('/etc/ossim/server/directives.xml', DOMXML_LOAD_SUBSTITUTE_ENTITIES)) {
        echo _("Error while parsing the document")."\n";
        exit;
    }
    $table = array();
    $table_dir = $dom->get_elements_by_tagname('directive');
    foreach($table_dir as $dir) {
        $table[] = $dir->get_attribute('name');
    }
    if (!in_array($name, $table)) echo "true";
    else echo "false";
} elseif ($query == 'get_category_id_by_directive_id') {
    $directive_id = $_GET['directive_id'];
    echo get_category_id_by_directive_id($directive_id);
} elseif ($query == 'get_new_directive_id') {
    $category_file = $_GET['category'];
	$mini = $_GET['mini'];
    echo new_directive_id_by_directive_file($category_file,$mini);
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
    $xml_file = $_GET['xml_file'];
    list($id_dir, $id_rule, $id_father) = explode("-", $rule_id);
    $_SESSION['rule'] = serialize($tab_rules[$id_rule]);
	$level = $tab_rules[$id_rule]->level;
    echo "<script type='text/javascript'>document.location.href='../editor/rule/index.php?directive=$id_dir&level=".$level."&id=$rule_id&xml_file=$xml_file'</script>";
    //echo "<script type='text/javascript'>document.location.href='../right.php?directive=" . $id_dir . "&level=" . $tab_rules[$id_rule]->level . "&action=edit_rule&id=" . $rule_id . "'</script>";
    //echo "<html><body onload=\"window.open('../right.php?directive=" . $id_dir . "&level=" . $tab_rules[$id_rule]->level . "&action=edit_rule&id=" . $rule_id . "','right')\"></body></html>";
}
/* Create a new rule object and edit this new rule*/
elseif ($query == "add_rule") {
    $directive = unserialize($_SESSION['directive']); 
    //$directive_xml = preg_replace("/.*\//","",$_SESSION['XML_FILE']);
    $directive_xml = $_GET['xml_file'];
    $tab_rules = $directive->rules;
    $id = $_GET['id'];
    $add = $_GET['add'];
    unset($_SESSION['rule']);
    list($id_dir, $id_rule, $id_father) = explode("-", $id);
    $level = 1;
    if ($id_father != 0) {
        $level = $tab_rules[$id_father]->level;
    	$newlevel = $tab_rules[$id_father]->level + 1;
        $tab_rules[$id_father]->nb_child = $tab_rules[$id_father]->nb_child + 1;
    } else {
        $newlevel = 1;
    }
    $temp = new Rule($id, $newlevel, "", "New rule", "", "", "ANY", "ANY", "ANY", "ANY", "ANY", "ANY", "ANY", "1", "None", "0", "Default", "Default", "Default", "Default", "Default", "Default");
    $_SESSION['rule'] = serialize($temp);
    
    $infolog = array("Added rule to", $directive->name);
    Log_action::log(86, $infolog);

    if($directive_xml==""){
        $directive_xml=get_directive_file($id_dir);
        $posFinal=strrpos($directive_xml,'/');
        $directive_xml=substr($directive_xml, $posFinal+1);
    }
    //echo "<html><body onload=\"window.open('../right.php?directive=" . $id_dir . "&level=$newlevel&action=add_rule&id=" . $id . "&directive_xml=" . $directive_xml . "','right')\"></body></html>";
    echo "<script type='text/javascript'>document.location.href='../editor/rule/index.php?directive=$id_dir&level=$level&id=$id&nlevel=$newlevel&xml_file=$directive_xml&add=$add'</script>";
}
/* create a new rule object with parameters from the form*/
elseif ($query == "save_rule") {
	ossim_valid($_POST["xml_file"], OSS_ALPHA, OSS_DOT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("xml_file"));
	if (ossim_error()) {
	    die(ossim_error());
	}
	if ($_POST["plugin_sid"] == "LIST") $_POST["plugin_sid"] = $_POST["plugin_sid_list"];
    if ($_POST["from"] == "LIST") $_POST["from"] = $_POST["from_list"];
    if ($_POST["port_from"] == "LIST") $_POST["port_from"] = $_POST["port_from_list"];
    if ($_POST["to"] == "LIST") $_POST["to"] = $_POST["to_list"];
    if ($_POST["port_to"] == "LIST") $_POST["port_to"] = $_POST["port_to_list"];
    if ($_POST["protocol_any"]) {
        $protocol = "ANY";
    } else {
        $protocol = "";
        if ($_POST["protocol_tcp"]) {
            if ($protocol != "") $protocol.= ",";
            $protocol.= "TCP";
        }
        if ($_POST["protocol_udp"]) {
            if ($protocol != "") $protocol.= ",";
            $protocol.= "UDP";
        }
        if ($_POST["protocol_icmp"]) {
            if ($protocol != "") $protocol.= ",";
            $protocol.= "ICMP";
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
    $xml_file = $_POST['xml_file'];
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
    $rule->iface = $_POST["iface"];
    $rule->filename = $_POST["filename"];
    $rule->username = $_POST["username"];
    $rule->password = $_POST["password"];
    $rule->userdata1 = stripslashes($_POST["userdata1"]);
    $rule->userdata2 = stripslashes($_POST["userdata2"]);
    $rule->userdata3 = stripslashes($_POST["userdata3"]);
    $rule->userdata4 = stripslashes($_POST["userdata4"]);
    $rule->userdata5 = stripslashes($_POST["userdata5"]);
    $rule->userdata6 = stripslashes($_POST["userdata6"]);
    $rule->userdata7 = stripslashes($_POST["userdata7"]);
    $rule->userdata8 = stripslashes($_POST["userdata8"]);
    $rule->userdata9 = stripslashes($_POST["userdata9"]);
    $_SESSION['rule'] = serialize($rule);
    $directive = $_POST["directive"];
    $level = $_POST["level"];
    $id = $_POST["id"];
    insert($id,"/etc/ossim/server/".$xml_file);
}
/* Delete a rule*/
elseif ($query == "del_rule") {
    $directive = unserialize($_SESSION['directive']);
    if ($_SESSION['XML_FILE'] != "") $XML_FILE = $_SESSION['XML_FILE'];
	else $XML_FILE = get_directive_file($directive->id);
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
    
    $infolog = array("Deleted rule of", $direct->name);
    Log_action::log(86, $infolog);
    
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
    $file = $_GET['directive_xml'];
	ossim_valid($file, OSS_ALPHA, OSS_DOT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("directive_xml"));
	if (ossim_error()) {
		die(ossim_error());
	}
    if ($file != "") $XML_FILE = "/etc/ossim/server/".$file;
	else $XML_FILE = get_directive_file($directive->id);
	
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
    
    $infolog = array("Deleted all rules of", $direct->name);
    Log_action::log(86, $infolog);
    
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
/* copy a rule */
elseif ($query == "copy_rule") {
    $directive = unserialize($_SESSION['directive']);
    $XML_FILE = get_directive_file($directive->id);
    $dom = open_file($XML_FILE);
    $direct = getDirectiveFromXML($dom, $directive->id);
    $tab_rules = $direct->rules;
    list($id_dir, $id_rule, $id_father) = explode("-", $_GET['id']);
    $old_rule = $tab_rules[$id_rule];
    $new_rule = $old_rule->rule->clone_node(true);
    $new_rule->set_attribute("name", "Copy of " . $new_rule->get_attribute("name"));
    $parent = $old_rule->rule->parent_node();
    $parent->append_child($new_rule);
    $dom->dump_file($XML_FILE);
    release_file($XML_FILE);
    echo "<html><body onload=\"window.open('../viewer/index.php?directive=" . $direct->id . "&level=" . $old_rule->level . "','right')\"></body></html>";
}
/* save a directive */
elseif ($query == "save_directive") {
    $new = FALSE;
    $directive = unserialize($_SESSION['directive']);
    $old_id = $_POST["iddir_old"];
    $new_id = $_POST["iddir"];
    $new_priority = $_POST["priority"];
    $new_group = $_POST["list"];
    $new_name = stripslashes($_POST["name"]);
    $add = $_POST['add'];
    //$XML_FILE = get_directive_file($new_id);
	ossim_valid($_POST['category'], OSS_ALPHA, OSS_DOT, OSS_SCORE, 'illegal:' . _("directive_xml"));
	ossim_valid($_POST['category_old'], OSS_ALPHA, OSS_DOT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("directive_xml"));
	ossim_valid($add, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("add"));
	if (ossim_error()) {
		die(ossim_error());
	}
	$XML_FILE = "/etc/ossim/server/".$_POST['category'];
	$OLD_XML_FILE = "/etc/ossim/server/".$_POST['category_old'];
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
    if (is_free($old_id,$XML_FILE) == "true") {
        $new_directive->directive = $node;
        $_SESSION['directive'] = serialize($new_directive);
        $directives->append_child($node);
        $dom->dump_file($XML_FILE);
        $new_rule_id = $new_id . "-1-0";
        //insert_dir_in_group($new_id, $new_group);
        echo "<html><body onload=\"top.frames['main'].document.location.href='../index.php?directive=" . $new_directive->id . "&level=1&action=add_rule&add=$add&xml_file=".preg_replace("/.*\//","",$XML_FILE)."&id=" . $new_rule_id . "&nlevel=1'\"></body></html>";
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
        
        if ($OLD_XML_FILE == $XML_FILE) $dom2 = $dom;
        /* if changes category */
        else {
            //echo "Change category<br>";
			$dom2 = open_file($OLD_XML_FILE);
        }
        $tab_directive = $dom2->get_elements_by_tagname('directive');
        foreach($tab_directive as $direct) {
            if ($direct->get_attribute('id') == $old_id) $old_node = $direct;
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
    set_groups($old_id, $new_id, $new_group);
    $infolog = array($new_name);
    Log_action::log(85, $infolog);
    echo "<html><body onload=\"top.frames['main'].document.location.href='../index.php?directive=" . $new_directive->id . "&level=1'\"></body></html>";
}
/* Delete a directive */
elseif ($query == "delete_directive") {
    $dir_id = $_GET['id'];
	$file = $_GET['directive_xml'];
	ossim_valid($file, OSS_ALPHA, OSS_DOT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("directive_xml"));
	if (ossim_error()) {
		die(ossim_error());
	}
    if ($file != "") $file = "/etc/ossim/server/".$file;
	else $file = get_directive_file($dir_id);

    $dom = open_file($file);
    $tab_directive = $dom->get_elements_by_tagname('directive');

    foreach($tab_directive as $lign) if ($lign->get_attribute('id') == $dir_id) $directive = $lign;
    
    $dname = $directive->get_attribute('name');
    $parent = $directive->parent_node();
    $parent->remove_child($directive);
    $dom->dump_file($file);
    release_file($file);
    delete_dir_from_groups($dir_id);
    echo "<html><body onload=\"top.frames['main'].document.location.href='../index.php'\"></body></html>";
    $infolog = array($dname);
    Log_action::log(87, $infolog);
 
}
/* Add a directive */
elseif ($query == "add_directive") {
    $cat_id = $_GET['id'];
    $onlydir = ($_GET['onlydir'] == "1") ? "1" : "0";
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

    echo "<html><body onload=\"window.open('../right.php?add=1&directive=" . $id . "&level=1&action=edit_dir&id=" . $id . "&onlydir=$onlydir&xml_file=" . $category->xml_file . "','right')\"></body></html>";
}
/* copy a directive */
elseif ($query == "copy_directive") {
    $dir_id = $_GET['id'];   

    if ($_GET['directive_xml'] != "") $file = "/etc/ossim/server/".$_GET['directive_xml'];
    elseif ($_SESSION['XML_FILE'] != "") $file = $_SESSION['XML_FILE'];
    else $file = get_directive_file($dir_id);
    $id_category = get_category_id_by_directive_id($dir_id);
    $dom = open_file($file);
    $directive = getDirectiveFromXML($dom, $dir_id); 

    if($directive->directive=="") {
        header("Location: ../viewer/index.php?directive=$dir_id");
    }  

    $mini = $_GET['mini'];
    $new_id = new_directive_id_by_directive_file($file,$mini);
	//$new_id = new_directive_id($id_category);
    $new_directive = $dom->create_element('directive');
    $new_directive->set_attribute('id', $new_id);
    $new_directive->set_attribute('name', "Copy of " . $directive->name);
    $new_directive->set_attribute('priority', $directive->priority);
    $tab_rules = $directive->rules;
    for ($ind = 1; $ind <= count($tab_rules); $ind++) {
        $rule = $tab_rules[$ind];
        list($id_dir, $id_rule, $id_father) = explode("-", $rule->id);
        if ($id_father == 0) {
            $new_node = $tab_rules[$id_rule]->getXMLNode($dom);
            $new_node = $new_directive->append_child($new_node);
            $tab_rules[$id_rule]->rule = $new_node;
            if ($tab_rules[$id_rule]->nb_child > 0) {
                do_rules($id_rule, &$tab_rules, $dom);
            }
        }
    }
    $parent = $directive->directive->parent_node();
    $parent->append_child($new_directive);
    $dom->dump_file($file);
    release_file($file);
    sleep(0.5);
    echo "<html><body onload=\"top.frames['main'].document.location.href='../index.php?directive=" . $new_id . "&action=copy_directive&id=" . $new_id . "'\"></body></html>";
    //echo "<html><body><a href=\"../index.php?directive=".$new_id."&action=copy_directive&id=".$new_id."\">test</a></body></html>";
    
}
/* Save a category */
elseif ($query == "save_category") {
    $file = '/etc/ossim/server/categories.xml';
    if (!$dom = domxml_open_file($file, DOMXML_LOAD_SUBSTITUTE_ENTITIES)) {
        echo _("Error while parsing the document")."\n";
        exit;
    }
	ossim_valid($_POST["xml_file"], OSS_ALPHA, OSS_DIGIT, OSS_DOT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("xml file"));
	ossim_valid($_POST["name"], OSS_ALPHA, OSS_DIGIT, OSS_DOT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("name"));
	if (ossim_error()) {
		die(ossim_error());
	}
	if (!preg_match("/\.xml$/",$_POST['xml_file'])) {
		die(_("xml_file must be a valid XML file name"));
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
    // modify directives.xml now
    enable_category(str_replace(" ", "-", $_POST["name"]) , str_replace(" ", "_", $_POST["xml_file"]));
    echo "<html><body onload=\"top.frames['main'].document.location.href='../numbering.php'\"></body></html>";
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
    // modify directives.xml now
    delete_category(str_replace(" ", "-", $category->name));
    echo "<html><body onload=\"top.frames['main'].document.location.href='../numbering.php'\"></body></html>";
}
/* Save a group */
elseif ($query == "save_group") {
    $file = '/etc/ossim/server/groups.xml';
    if (!$dom = domxml_open_file($file, DOMXML_LOAD_SUBSTITUTE_ENTITIES)) {
        echo _("Error while parsing the document")."\n";
        exit;
    }
    $group = unserialize($_SESSION['group']);
    $oldname = $group->name;
    $oldlist = $group->list;
    $new_node = $dom->create_element('group');
    $new_node->set_attribute("name", $_POST["name"]);
    $list = split(',', $_POST["list"]);
    foreach($list as $dir) {
        $new_child = $dom->create_element('append-directive');
        $new_child->set_attribute("directive_id", $dir);
        $new_node->append_child($new_child);
    }
    //$_SESSION['category'] = serialize($category);
    if (get_group_by_name($oldname) != NULL) {
        $tab_group = $dom->get_elements_by_tagname('group');
        foreach($tab_group as $group) {
            if ($group->get_attribute('name') == $oldname) $node = $group;
        }
        $node->replace_node($new_node);
        $dom->dump_file($file);
    } else {
        $groups = $dom->get_elements_by_tagname('groups');
        $groups = $groups[0];
        $groups->append_child($new_node);
        $dom->dump_file($file);
    }
    echo "<html><body onload=\"top.frames['main'].document.location.href='../numbering.php'\"></body></html>";
}
/* delete a group */
elseif ($query == "delete_group") {
    $file = '/etc/ossim/server/groups.xml';
    if (!$dom = domxml_open_file($file, DOMXML_LOAD_SUBSTITUTE_ENTITIES)) {
        echo _("Error while parsing the document")."\n";
        exit;
    }
    $name = $_GET['name'];
    $dgroup = get_group_by_name($name);
    $tab_groups = $dom->get_elements_by_tagname('group');
    foreach($tab_groups as $group) {
        if ($group->get_attribute('name') == $dgroup->name) $node = $group;
    }
    $parent = $node->parent_node();
    $parent->remove_child($node);
    $dom->dump_file($file);
    echo "<html><body onload=\"top.frames['main'].document.location.href='../index.php'\"></body></html>";
}
if ($query != "") dbClose();
?>
