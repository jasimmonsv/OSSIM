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
* - Rule()
* - is_new()
* - print_rule()
* - getXMLNode()
* - delrule()
* - new_id()
* - do_rules()
* - insert()
* - left()
* - right()
* - up()
* - down()
* Classes list:
* - Rule
*/
require_once ("utils.php");
require_once ('classes/Session.inc');
/**
 * This package defines a model to represent a directive, based on the
 * correlation rules specification.
 */
define("IP_PATT", "!?((25[0-4]|(2[0-4]|1[0-9]|[1-9]?)[0-9]\.){3}(25[0-4]|(2[0-4]|1[0-9]|[1-9]?)[0-9]))");
define("NAME_PATT", "!?[0-9a-zA-Z-_]+");
define("PORT_LIST_PATT", "(!?([0-9][0-9]*),)*(!?([0-9][0-9]*))");
define("LIST_PATT", "(" . IP_PATT . "|" . NAME_PATT . ")(,(" . IP_PATT . "|" . NAME_PATT . '))*');
define("SRC_IP_PATT", '^[[:digit:]]+\:SRC_IP$');
define("DEST_IP_PATT", '^[[:digit:]]+\:DST_IP$');
define("SRC_PORT_PATT", '^[[:digit:]]+\:SRC_PORT$');
define("DEST_PORT_PATT", '^[[:digit:]]+\:DST_PORT$');
define("SENSOR_PATT", '^[[:digit:]]+\:SENSOR$');
//ID : dirID-RuleID-fatherID

/**
 * A class representing a rule, ie a collection of fields - the attributes of
 * the XML rule node - and an array of sub-rules.This rule is a node within
 * the rule tree model, and generates its own HTML code and its sub-rules code.
 * In term of graph theory, this model represents a labelled ordered n-ary tree.
 * @package rule
 */
class Rule {
    /**
     * The type of the rule (ie "monitor" or "detector").
     * @access private
     * @var string
     */
    var $plugin_type;
    /**
     * The name of the rule (ie an identificator).
     * @access private
     * @var string
     */
    var $name;
    /**
     * The priority of the rule (ie a string representing a number).
     * @access private
     * @var string
     */
    var $priority;
    /**
     * The reliability of the rule (ie a string representing a number).
     * @access private
     * @var string
     */
    var $reliability;
    /**
     * The time_out of the rule (ie a string representing a number).
     * @access private
     * @var string
     */
    var $time_out;
    /**
     * The occurrence of the rule (ie a string representing a number).
     * @access private
     * @var string
     */
    var $occurrence;
    /**
     * The from field of the rule (ie a list of IPs / network name, negated or not with
     * "!" separated by commas OR
     * the string "ANY" OR "nb:SRC_IP" OR "nb:DST_IP" where nb is a string representing
     * a number).
     * @access private
     * @var string
     */
    var $from;
    /**
     * The to field of the rule (ie a list of IPs / network name, negated or not with
     * "!" separated by commas OR
     * the string "ANY" OR "nb:SRC_IP" OR "nb:DST_IP" where nb is a string representing
     * a number).
     * @access private
     * @var string
     */
    var $to;
    /**
     * The port_from field of the rule (ie a list of ports, negated or not with
     * "!" separated by commas OR
     * the string "ANY" OR "nb:SRC_PORT" OR "nb:DST_PORT" where nb is a string representing
     * a number).
     * @access private
     * @var string
     */
    var $port_from;
    /**
     * The port_to field of the rule (ie a list of ports, negated or not with
     * "!" separated by commas OR
     * the string "ANY" OR "nb:SRC_PORT" OR "nb:DST_PORT" where nb is a string representing
     * a number).
     * @access private
     * @var string
     */
    var $port_to;
    /**
     * The plugin_id field of the rule (ie a string representing a number).
     * @access private
     * @var string
     */
    var $plugin_id;
    /**
     * The plugin_sid field of the rule (ie a number list separated by commas).
     * @access private
     * @var string
     */
    var $plugin_sid;
    /**
     * The condition field of the rule (ie "eq" OR "ne" OR "lt" OR "gt" OR "ge" OR "le").
     * @access private
     * @var string
     */
    var $condition;
    /**
     * The value field of the rule (ie a string representing a number).
     * @access private
     * @var string
     */
    var $value;
    /**
     * The interval field of the rule (ie a string representing a number).
     * @access private
     * @var string
     */
    var $interval;
    /**
     * The absolute field of the rule (ie a "true" or "false").
     * @access private
     * @var string
     */
    var $absolute;
    /**
     * The protocol field of the rule (ie a protocol list - see the OSSIM doc for the
     *complete list, OR the string "ANY" OR "nb:PROTOCOL" OR "nb:PROTOCOL" where nb is a string representing
     * a number).).
     * @access private
     * @var string
     */
    var $protocol;
    /**
     * The sensor field of the rule.
     * @access private
     * @var string
     */
    var $sensor;
    /**
     * The sticky field of the rule (ie ).
     * @access private
     * @var string
     */
    var $sticky;
    /**
     * The sticky_different field of the rule (ie ).
     * @access private
     * @var string
     */
    var $sticky_different;
    /**
     * The userdata 1-9 field of the rule (ie ).
     * @access private
     * @var string
     */
    var $userdata1;
    var $userdata2;
    var $userdata3;
    var $userdata4;
    var $userdata5;
    var $userdata6;
    var $userdata7;
    var $userdata8;
    var $userdata9;
    /**
     * The filename field of the rule (ie ).
     * @access private
     * @var string
     */
    var $filename;
    /**
     * The interface field of the rule (ie ).
     * @access private
     * @var string
     */
    var $iface;
    /**
     * The password field of the rule (ie ).
     * @access private
     * @var string
     */
    var $username;
    /**
     * The interface field of the rule (ie ).
     * @access private
     * @var string
     */
    var $password;
    /**
     * The groups field of the rule (ie ).
     * @access private
     * @var string
     */
    var $groups;
    /**
     * The level of the rule, ie the number of rules that are above this one (or
     * its depth in graph theory)
     * @access private
     * @var int
     */
    var $level;
    /**
     * The height of the whole rule tree that contains this rule, (useful for display)
     * @access private
     * @var int
     */
    var $ilevel;
    /**
     * The rule identificator, composed as follow : "dirID-RuleID-fatherID", where
     * 'dirID' is the identificator of the directive the rule belongs to,
     * 'ruleID' is a unique identifier attributed to each node of the rule tree
     * by a simple DFS and 'fatherID' the id of the direct father of this rule.
     * This numerotation is mostly done calling a method from a Directive
     * object and must be left untouched.
     * @access private
     * @var string
     */
    var $id;
    /**
     * The number of child of this rule
     * @access private
     * @var integer
     */
    var $nb_child;
    /**
     * The xml code of the rule
     * @access private
     * @var Dom element
     */
    var $rule;
    /**
     * The constructor.
     */
    function Rule($id, $level, $rule, $name, $plugin_id, $plugin_type, $plugin_sid, $from, $port_from, $to, $port_to, $protocol, $sensor, $occurrence, $time_out, $reliability, $condition, $value, $interval, $absolute, $sticky, $sticky_different, $userdata1 = "", $userdata2 = "", $userdata3 = "", $userdata4 = "", $userdata5 = "", $userdata6 = "", $userdata7 = "", $userdata8 = "", $userdata9 = "", $filename = "", $iface = "", $username = "", $password = "") {
        $this->id = $id;
        $this->level = $level;
        $this->rule = $rule;
        $this->name = $name;
        $this->plugin_id = $plugin_id;
        $this->plugin_type = $plugin_type;
        $this->plugin_sid = $plugin_sid;
        $this->from = ($from == "") ? "ANY" : $from;
        $this->port_from = ($port_from == "") ? "ANY" : $port_from;
        $this->to = ($to == "") ? "ANY" : $to;
        $this->port_to = ($port_to == "") ? "ANY" : $port_to;
        $this->protocol = ($protocol == "") ? "ANY" : $protocol;
        $this->sensor = ($sensor == "") ? "ANY" : $sensor;
        $this->occurrence = ($occurrence == "") ? "1" : $occurrence;
        $this->time_out = ($time_out == "") ? "None" : $time_out;
        $this->reliability = ($reliability == "") ? "0" : $reliability;
        $this->condition = ($condition == "") ? "Default" : $condition;
        $this->value = ($value == "") ? "Default" : $value;
        $this->interval = ($interval == "") ? "Default" : $interval;
        $this->absolute = ($absolute == "") ? "Default" : $absolute;
        $this->sticky = ($sticky == "") ? "None" : $sticky;
        $this->sticky_different = ($sticky_different == "") ? "None" : $sticky_different;
        $this->userdata1 = $userdata1;
        $this->userdata2 = $userdata2;
        $this->userdata3 = $userdata3;
        $this->userdata4 = $userdata4;
        $this->userdata5 = $userdata5;
        $this->userdata6 = $userdata6;
        $this->userdata7 = $userdata7;
        $this->userdata8 = $userdata8;
        $this->userdata9 = $userdata9;
        $this->filename = $filename;
        $this->iface = $iface;
        $this->username = $username;
        $this->password = $password;
    }
    /* Check if the curent rule is a new rule */
    function is_new() {
        $directive = unserialize($_SESSION['directive']);
        $tab_rules = $directive->rules;
        $id = $this->id;
        $new = TRUE;
        for ($i = 1; $i <= count($tab_rules); $i++) if ($tab_rules[$i]->id == $id) $new = FALSE;
        return $new;
    }
    /**
     * Prints the html code on the output.Should be called to render the current rule
     * (and all of its sub-rules) in a navigator. This method only display <tr> elements, and so
     * other markups (eg <table>) must be printed in order to obtain a valid HTML
     * code.
     */
    function print_rule($level, &$rules, $xml_file = "") {
        global $conn;
        list($id_dir, $id_rule, $id_father) = explode("-", $this->id);
        $newid = new_id($this->id, &$rules);
        $newlevel = $this->level + 1;
        $ilevel = $this->level;
        $directive_id = $_GET['directive'];
        if ($this->level <= $level) {
            if ($this->is_new()) { ?>
      <tr bgcolor="f48222"><?php
            } elseif ($level - $ilevel == 0) { ?>
      <tr bgcolor="#ffffff"><?php
            } elseif ($level - $ilevel == 1) { ?>
      <tr bgcolor="#CCCCCC"><?php
            } elseif ($level - $ilevel == 2) { ?>
      <tr bgcolor="#999999"><?php
            } elseif ($level - $ilevel == 3) { ?>
      <tr bgcolor="#9999CC"><?php
            } elseif ($level - $ilevel == 4) { ?>
      <tr bgcolor="#6699CC"><?php
            }
            if ($ilevel - 1 != 0) { ?>
			<td bgcolor="#ffffff" colspan=<?php
                echo $ilevel - 1 ?>>
      
      </td>
		<?php
            } ?>

		<td class="left" colspan=<?php
            echo $level - $ilevel + 1 ?>>
<?php
            if (isset($_SESSION['rule'])) {
                $newrule = unserialize($_SESSION['rule']);
                list($id_dir2, $id_rule2, $id_father2) = explode("-", $newrule->id);
                if (($id_father2 == $id_rule) && ($id_dir2 == $id_dir)) $this->nb_child = $this->nb_child + 1;
            }
            if (($level - $ilevel == 0) && ($this->nb_child > 0)) {
?>
            <a TARGET ="_self" href="../viewer/index.php?directive=<?php
                echo $directive_id
?>&level=<?php
                echo $level + 1 ?>"><img border="0" src="../viewer/img/flechedf.gif"></a>
    <?php
            } elseif ($this->nb_child > 0) { ?>
            <a TARGET ="_self" href="../viewer/index.php?directive=<?php
                echo $directive_id
?>&level=<?php
                echo $ilevel ?>"><img border="0" src="../viewer/img/flechebf.gif"></a>
    <?php
            } ?>
        </td>

		<?php
            if ($ilevel + 1 > $level) {
                $newlev = $ilevel + 1;
            } else {
                $newlev = $level;
            }
            if ($level > 1) $uplevel = $level - 1;
            else $uplevel = 1;
            //addRule button
            if (!$this->is_new()) {
                print '<td>';
                print "<a TARGET=\"right\" href=\"../include/utils.php?query=add_rule&xml_file=$xml_file&id=" . $newid . "\" TITLE=\"" . gettext("Add a rule") . "\"><img src='../../pixmaps/plus-small.png' border='0'></img></a>";
                print '</td>';
                //removeRule button
                print '<td>';
                if ($this->level > 1) { print "<a onclick=\"javascript:if (confirm('" . gettext("Are you sure you want to delete this rule ?") . "')) { window.open('../include/utils.php?query=del_rule&id=" . $this->id . "','right'); }\" style=\"marging-left:20px; cursor:pointer\" TITLE=\"" . gettext("Delete this rule") . "\"><img src='../../pixmaps/delete-small.gif' border='0'></img></a>"; }
                print '</td>';
                //copy button
                print '<td>';
                if ($this->level > 1) { print "<a TARGET=\"right\" href=\"../include/utils.php?query=copy_rule&id=" . $this->id . "\" TITLE=\"" . gettext("Copy this rule") . "\"><img src='../../pixmaps/copy-small.png' border='0'></img></a>"; }
                print '</td>';
                //left button
                print '<td>';
                if ($this->level > 2) { print "<a TARGET=\"right\" href=\"../include/utils.php?query=move&direction=left&id=" . $this->id . "\" TITLE=\"" . gettext("Move rule left (previous correlation level)") . "\"><img src='../../pixmaps/arrow-180-small.png' border='0'></img></a>"; }
                print '</td>';
                //right button
                print '<td>';
                if ($this->level > 1) { print "<a TARGET=\"right\" href=\"../include/utils.php?query=move&direction=right&id=" . $this->id . "\" TITLE=\"" . gettext("Move rule right (next correlation level)") . "\"><img src='../../pixmaps/arrow-000-small.png' border='0'></img></a>"; }
                print '</td>';
                //up button
                print '<td>';
                if ($this->level > 1) { print "<a TARGET=\"right\" href=\"../include/utils.php?query=move&direction=up&id=" . $this->id . "\" TITLE=\"" . gettext("Move rule up (same correlation level)") . "\"><img src='../../pixmaps/arrow-090-small.png' border='0'></img></a>"; }
                print '</td>';
                //down button
                print '<td>';
                if ($this->level > 1) { print "<a TARGET=\"right\" href=\"../include/utils.php?query=move&direction=down&id=" . $this->id . "\" TITLE=\"" . gettext("Move rule down (same correlation level)") . "\"><img src='../../pixmaps/arrow-270-small.png' border='0'></img></a>"; }
                print '</td>';
            } else {
                print '<td>&nbsp&nbsp&nbsp&nbsp&nbsp</td>';
                print '<td>';
                print "<a TARGET=\"right\" href=\"../include/utils.php?query=del_new_rule&level=" . $uplevel . "\" TITLE=\"Delete this rule.\"><img src='../../pixmaps/minus-small.png' border='0'></img></a>";
                print '</td>';
                for ($i = 0; $i < 5; $i++) print '<td>&nbsp&nbsp&nbsp&nbsp&nbsp</td>';
            }
            if ($this->is_new()) { ?>        
        <td><a href="../include/utils.php?query=add_rule&id=<?php
                echo $this->id ?>&level=<?php
                echo $this->level ?>" TITLE="<?php
                echo gettext("Click to modify this rule"); ?>"><?php
                echo $this->name; ?></a></td>
       <?php
            } else { ?>
        <td><a href="../include/utils.php?query=edit_rule&id=<?php
                echo $this->id ?>&xml_file=<?php echo $xml_file ?>" TITLE="<?php
                echo gettext("Modify this rule"); ?>"><?php
                echo $this->name; ?></a></td>
       <?php
            } ?>
        
        <td><?php
            echo $this->reliability; ?>&nbsp;</td>
        <td><?php
            echo $this->time_out; ?>&nbsp;</td>
        <td><?php
            echo $this->occurrence; ?>&nbsp;</td>
        <td><?php
            echo str_replace(",",",<br>",$this->from); ?>&nbsp;</td>
        <td><?php
            echo str_replace(",",",<br>",$this->to); ?>&nbsp;</td>
        <td><?php
            echo $this->port_from; ?>&nbsp;</td>
        <td><?php
            echo $this->port_to; ?>&nbsp;</td>
        <td><?php
            echo $this->sensor; ?>&nbsp;</td>
        <td>
<?php
            if ($this->plugin_id != "") {
                $plugin_id = $this->plugin_id;
                if ($plugin_list = Plugin::get_list($conn, "WHERE id = $plugin_id")) {
                    $name = $plugin_list[0]->get_name();
                    echo "<a href=\"../../conf/pluginsid.php?id=$plugin_id&" . "name=$name\">$name</a> ($plugin_id)";
                }
            }
?>
        </td>
        <td> 
<?php
            if ($this->plugin_id != "" && $this->plugin_sid != "") {
                $plugin_sid = $this->plugin_sid;
                $plugin_sid_list = split(',', $plugin_sid);
                if (count($plugin_sid_list) > 30) {
?>
        <a style="cursor:pointer;" TITLE="<?php
                    echo gettext("To view or hide the list of plugin sid click here"); ?>" onclick="Menus('plugsid')"> <?php
                    echo gettext("Expand / Collapse"); ?> </a>
        <div id="plugsid" class="menuhide">
<?php
                }
                foreach($plugin_sid_list as $sid_negate) {
                    $sid = $sid_negate;
                    if (!strncmp($sid_negate, "!", 1)) $sid = substr($sid_negate, 1);
                    /* sid == ANY */
                    if (!strcmp($sid, "ANY")) {
                        echo gettext("ANY");
                    }
                    /* sid == X:PLUGIN_SID */
                    elseif (strpos($sid, "PLUGIN_SID")) {
                        echo gettext("$sid");
                    }
                    /* get name of plugin_sid */
                    elseif ($plugin_list = Plugin_sid::get_list($conn, "WHERE plugin_id = $plugin_id AND sid = $sid")) {
                        $name = $plugin_list[0]->get_name();
                        echo "<a title=\"$name\">$sid_negate</a>&nbsp; ";
                    } else {
                        echo "<a title=\"" . gettext("Invalid plugin sid") . "\" style=\"color:red\">$sid_negate</a>&nbsp; ";
                    }
                }
                if (count($plugin_sid_list) > 30) {
?>
         </div>
<?php
                }
            }
?>
	</td>
      </tr>
                
<?php
        }
    }
    /**
     * A function that generates a DomElement object representing the associated
     * XML Dom node.Returns NULL if the Rule object is not well-formed (ie if mandatory
     * tags are NULL or empty, see the OSSIM doc).
     * @return object a XML node, DomElement obj. if he Rule is well-formed, NULL otherwise.
     */
    function getXMLNode($dom) {
        //$node = new DomElement("rule");
        //$XML_FILE = get_directive_file($id_dir);
        //print $XML_FILE."<br />";
        //$dom = open_file($XML_FILE);
        $node = $dom->create_element('rule');
        if ($this->plugin_type != NULL && $this->plugin_type != "") $node->set_attribute("type", $this->plugin_type);
        if ($this->name != NULL && $this->name != "") $node->set_attribute("name", $this->name);
        if ($this->from != NULL && $this->from != "") $node->set_attribute("from", $this->from);
        if ($this->to != NULL && $this->to != "") $node->set_attribute("to", $this->to);
        if ($this->port_from != NULL && $this->port_from != "") $node->set_attribute("port_from", $this->port_from);
        if ($this->port_to != NULL && $this->port_to != "") $node->set_attribute("port_to", $this->port_to);
        if ($this->priority != NULL && $this->priority != "") $node->set_attribute("priority", $this->priority);
        if ($this->reliability != NULL && $this->reliability != "") $node->set_attribute("reliability", $this->reliability);
        if ($this->occurrence != "ANY" && $this->occurrence != "") $node->set_attribute("occurrence", $this->occurrence);
        if ($this->time_out != "None" && $this->time_out != "") $node->set_attribute("time_out", $this->time_out);
        if ($this->plugin_id != NULL && $this->plugin_id != "") $node->set_attribute("plugin_id", $this->plugin_id);
        if ($this->plugin_sid != NULL && $this->plugin_sid != "") $node->set_attribute("plugin_sid", $this->plugin_sid);
        if ($this->condition != "Default" && $this->condition != "") $node->set_attribute("condition", $this->condition);
        if ($this->value != "Default" && $this->value != "") $node->set_attribute("value", $this->value);
        if ($this->absolute != "Default" && $this->absolute != "") $node->set_attribute("absolute", $this->absolute);
        if ($this->interval != "Default" && $this->interval != "") $node->set_attribute("interval", $this->interval);
        if ($this->protocol != "ANY" && $this->protocol != "") $node->set_attribute("protocol", $this->protocol);
        if ($this->sensor != "ANY" && $this->sensor != "") $node->set_attribute("sensor", $this->sensor);
        if ($this->sticky != "" && $this->sticky != "Default" && $this->sticky != "None") $node->set_attribute("sticky", $this->sticky);
        if ($this->sticky_different != "None" && trim($this->sticky_different) != "") $node->set_attribute("sticky_different", $this->sticky_different);
        if ($this->userdata1 != NULL && $this->userdata1 != "") $node->set_attribute("userdata1", $this->userdata1);
        if ($this->userdata2 != NULL && $this->userdata2 != "") $node->set_attribute("userdata2", $this->userdata2);
        if ($this->userdata3 != NULL && $this->userdata3 != "") $node->set_attribute("userdata3", $this->userdata3);
        if ($this->userdata4 != NULL && $this->userdata4 != "") $node->set_attribute("userdata4", $this->userdata4);
        if ($this->userdata5 != NULL && $this->userdata5 != "") $node->set_attribute("userdata5", $this->userdata5);
        if ($this->userdata6 != NULL && $this->userdata6 != "") $node->set_attribute("userdata6", $this->userdata6);
        if ($this->userdata7 != NULL && $this->userdata7 != "") $node->set_attribute("userdata7", $this->userdata7);
        if ($this->userdata8 != NULL && $this->userdata8 != "") $node->set_attribute("userdata8", $this->userdata8);
        if ($this->userdata9 != NULL && $this->userdata9 != "") $node->set_attribute("userdata9", $this->userdata9);
        if ($this->filename != NULL && $this->filename != "") $node->set_attribute("filename", $this->filename);
        if ($this->iface != NULL && $this->iface != "") $node->set_attribute("interface", $this->iface);
        if ($this->username != NULL && $this->username != "") $node->set_attribute("username", $this->username);
        if ($this->password != NULL && $this->password != "") $node->set_attribute("password", $this->password);
        if ($this->nb_child > 0) {
            $temp = $dom->create_element('rules');
            $rulesNode = $node->append_child($temp);
        }
        return $node;
    }
}
/* Deletes a rule */
function delrule($id, &$tab_rules) {
    list($id_dir, $id_rule, $id_father) = explode("-", $id);
    $level = $tab_rules[$id_rule]->level;
    $rule = $tab_rules[$id_rule]->rule;
    list($id_dir, $id_rule, $id_father) = explode("-", $id);
    $nb = 0;
    $i = $id_rule + 1;
    do {
        list($id_dir2, $id_rule2, $id_father2) = explode("-", $tab_rules[$i]->id);
        $nb++;
        $i++;
    }
    while (($id_father2 > $id_father) && ($i <= count($tab_rules)));
    for ($i = $id_rule; $i < count($tab_rules) - $nb; $i++) {
        $tab_rules[$i] = $tab_rules[$i + $nb];
    }
    for ($j = 0; $j < $nb; $j++) {
        array_pop($tab_rules);
    }
    for ($j = 1; $j <= count($tab_rules); $j++) {
        list($id_dir, $id_rule, $id_father) = explode("-", $tab_rules[$j]->id);
        $new_id_rule = $j;
        $newid = $id_dir . "-" . $new_id_rule . "-" . $id_father;
        $tab_rules[$j]->id = $newid;
    }
    $sup = 1;
    foreach($tab_rules as $lign) {
        if ($lign->level >= $sup) {
            $sup = $lign->level;
        }
    }
    $temp = $_GET['level'];
    if ($sup < $_GET['level']) $level = $sup;
    else $level = $_GET['level'];
    $parent = $rule->parent_node();
    $res = $parent->remove_child($rule);
}
/* calculates the id of the possible new child of the rule */
function new_id($id, &$tab_rules) {
    list($id_dir, $id_rule, $id_father) = explode("-", $id);
    $i = $id_rule;
    do {
        $i++;
        list($id_dir2, $id_rule2, $id_father2) = explode("-", $tab_rules[$i]->id);
    }
    while (($id_father2 > $id_father) && ($i <= count($tab_rules)));
    $newid = $id_dir . "-" . $i . "-" . $id_rule;
    return $newid;
}
/* recursive function to create a rule and his children */
function do_rules($id_rule, &$tab_rules, $dom) {
    $father = $tab_rules[$id_rule]->rule;
    $rules = $father->child_nodes();
    foreach($rules as $rule) {
        if (($rule->type == XML_ELEMENT_NODE) && ($rule->tagname() == 'rules')) {
            for ($i = 1; $i <= count($tab_rules); $i++) {
                $lign = $tab_rules[$i];
                list($id_dir, $id_rule2, $id_father) = explode("-", $lign->id);
                if ($id_father == $id_rule) {
                    $node = $tab_rules[$id_rule2]->getXMLNode($dom);
                    $node = $rule->append_child($node);
                    $tab_rules[$id_rule2]->rule = $node;
                    if ($tab_rules[$id_rule2]->nb_child > 0) {
                        do_rules($id_rule2, &$tab_rules, $dom);
                    }
                }
            }
        }
    }
}
/* insert a new rule into xml file */
function insert($id,$XML_FILE="") {
	if ($XML_FILE == "") $XML_FILE = $_SESSION['XML_FILE'];
    $rule = unserialize($_SESSION['rule']);
    $directive = unserialize($_SESSION['directive']);
    $tab_rules = $directive->rules;
    unset($_SESSION['rule']);
    list($id_dir, $id_rule, $id_father) = explode("-", $id);
    init_file($XML_FILE);
    if (!$dom = domxml_open_file($XML_FILE, DOMXML_LOAD_SUBSTITUTE_ENTITIES)) {
        echo _("Error while parsing the document")."\n";
        exit;
    }
    $directive = getDirectiveFromXML($dom, $id_dir, $rule->id, $rule->name);
    if ($id_father != 0) $tab_rules[$id_father]->nb_child = $tab_rules[$id_father]->nb_child + 1;
    if ($id == $tab_rules[$id_rule]->id || $id_rule > count($tab_rules)) {
        $tab_rules[$id_rule] = $rule;
    } else {
        for ($i = count($tab_rules); $i >= $id_rule; $i--) {
            list($id_dir, $id_rule2, $id_father2) = explode("-", $tab_rules[$i]->id);
            $new_id_rule = $i + 1;
            if ($id_father2 >= $id_rule) $new_id_father = $id_father2 + 1;
            else $new_id_father = $idfather2;
            $newid = $id_dir . "-" . $new_id_rule . "-" . $new_id_father;
            $tab_rules[$i]->id = $newid;
            $tab_rules[$i + 1] = $tab_rules[$i];
        }
        $tab_rules[$id_rule] = $rule;
    }
    $direct = $directive->directive;
    if ($direct) {
        $rules = $direct->child_nodes();
        foreach($rules as $rule) {
            $direct->remove_child($rule);
        }
    }
    do_directive($directive, &$tab_rules, $dom);
    $dom->dump_file($XML_FILE);
    release_file($XML_FILE);
    $newlevel = $tab_rules[$id_rule]->level;
    echo "<html><body onload=\"window.open('../viewer/index.php?directive=" . $directive->id . "&level=$newlevel','right')\"></body></html>";
}
/* moves a rule to the left */
function left($dom, $id, &$tab_rules, $directive) {
    list($id_dir, $id_rule, $id_father) = explode("-", $id);
    $rule = $tab_rules[$id_rule]->rule;
    if ($tab_rules[$id_rule]->level > 1) {
        $tab_rules[$id_rule]->level = $tab_rules[$id_rule]->level - 1;
        list($id_dir, $id_rule2, $id_father2) = explode("-", $tab_rules[$id_father]->id);
        $newid = $id_dir . "-" . $id_rule . "-" . $id_father2;
        $tab_rules[$id_rule]->id = $newid;
        $tab_rules[$id_father]->nb_child = $tab_rules[$id_father]->nb_child - 1;
        $i = $id_rule + 1;
        do {
            list($id_dir2, $id_rule2, $id_father2) = explode("-", $tab_rules[$i]->id);
            if ($id_father2 > $id_father) $tab_rules[$i]->level = $tab_rules[$i]->level - 1;
            $i++;
        }
        while (($id_father2 > $id_father) && ($i <= count($tab_rules)));
        $direct = $directive->directive;
        $rules = $direct->child_nodes();
        foreach($rules as $rule) {
            $direct->remove_child($rule);
        }
        do_directive($directive, &$tab_rules, $dom);
        $newlevel = $tab_rules[$id_rule]->level;
        $_POST['new_level'] = $newlevel;
    } else {
        $newlevel = $tab_rules[$id_rule]->level;
        $_POST['new_level'] = $newlevel;
        echo "<script>alert(\"" . gettext("Not allowed for this rule") . "\")</script>";
    }
}
/* moves a rule to the right */
function right($dom, $id, &$tab_rules, $directive) {
    list($id_dir, $id_rule, $id_father) = explode("-", $id);
    if ($id_father == 0) $nbc = $directive->nb_child;
    else $nbc = $tab_rules[$id_father]->nb_child;
    $ind = 0;
    if (($id_rule > 1) && ($nbc > 1)) {
        $stock = array();
        for ($i = 1; $i <= count($tab_rules); $i++) {
            list($id_dir, $id_rule2, $id_father2) = explode("-", $tab_rules[$i]->id);
            if ($id_father2 == $id_father) $stock[] = $tab_rules[$i]->id;
        }
        while ($id != $stock[$ind]) {
            $ind++;
        }
    }
    if ($ind > 0) {
        list($id_dir, $id_rule2, $id_father2) = explode("-", $stock[$ind - 1]);
        $rule = $tab_rules[$id_rule]->rule;
        $tab_rules[$id_rule]->level = $tab_rules[$id_rule]->level + 1;
        if ($id_father != 0) $tab_rules[$id_father]->nb_child = $tab_rules[$id_father]->nb_child - 1;
        else $directive->nb_child = $directive->nb_child - 1;
        $tab_rules[$id_rule2]->nb_child = $tab_rules[$id_rule2]->nb_child + 1;
        $newid = $id_dir . "-" . $id_rule . "-" . $id_rule2;
        $tab_rules[$id_rule]->id = $newid;
        if ($id_rule < count($tab_rules)) {
            $i = $id_rule + 1;
            do {
                list($id_dir2, $id_rule2, $id_father2) = explode("-", $tab_rules[$i]->id);
                if ($id_father2 > $id_father) $tab_rules[$i]->level = $tab_rules[$i]->level + 1;
                $i++;
            }
            while (($id_father2 > $id_father) && ($i <= count($tab_rules)));
        }
        $direct = $directive->directive;
        $rules = $direct->child_nodes();
        foreach($rules as $rule) {
            $direct->remove_child($rule);
        }
        do_directive($directive, &$tab_rules, $dom);
        $newlevel = $tab_rules[$id_rule]->level;
        $_POST['new_level'] = $newlevel;
    } else {
        $newlevel = $tab_rules[$id_rule]->level;
        $_POST['new_level'] = $newlevel;
        echo "<script>alert(\"" . gettext("Not allowed for this rule") . "\")</script>";
    }
}
/* moves a rule up */
function up($dom, $id, &$tab_rules, $directive) {
    list($id_dir, $id_rule, $id_father) = explode("-", $id);
    if ($id_father == 0) $nbc = $directive->nb_child;
    else $nbc = $tab_rules[$id_father]->nb_child;
    if (($id_rule > 1) && ($nbc > 1)) {
        $stock = array();
        for ($i = 1; $i <= count($tab_rules); $i++) {
            list($id_dir, $id_rule2, $id_father2) = explode("-", $tab_rules[$i]->id);
            if ($id_father2 == $id_father) $stock[] = $tab_rules[$i]->id;
        }
        $i = 0;
        while ($i < count($stock)) {
            if ($stock[$i] == $id) $ind = $i;
            $i++;
        }
        if ($ind > 0) {
            list($id_dir, $id_rule2, $id_father2) = explode("-", $stock[$ind - 1]);
            $prof = 0;
            if ($id_rule < count($tab_rules)) {
                $i = $id_rule + 1;
                do {
                    list($id_dir, $id_rule3, $id_father3) = explode("-", $tab_rules[$i]->id);
                    if ($id_father3 > $id_father) $prof++;
                    $i++;
                }
                while (($id_father3 > $id_father) && ($i <= count($tab_rules)));
            }
            $tab_rules[$id_rule]->id = $stock[$ind - 1];
            list($id_dir, $id_rule2, $id_father2) = explode("-", $stock[$ind - 1]);
            $newniv = $id_rule2 + $prof + 1;
            $newid = $id_dir . "-" . $newniv . "-" . $id_father2;
            $tab_rules[$id_rule2]->id = $newid;
            $temp = array();
            $diff = $id_rule - $id_rule2;
            for ($i = 0; $i < $diff; $i++) {
                $temp[$i] = $tab_rules[$id_rule2 + $i];
            }
            $tab_rules[$id_rule2] = $tab_rules[$id_rule];
            for ($i = 1; $i <= $prof; $i++) {
                $j = $id_rule + $i - $diff;
                $tab_rules[$j] = $tab_rules[$j + $diff];
                list($id_dir, $id_rule3, $id_father3) = explode("-", $tab_rules[$j]->id);
                $idf = $id_father3 - $diff;
                $newid = $id_dir . "-" . $j . "-" . $idf;
                $tab_rules[$j]->id = $newid;
            }
            $tab_rules[$id_rule2 + $prof + 1] = $temp[0];
            for ($i = 1; $i < count($temp); $i++) {
                $j = $id_rule2 + $prof + 1 + $i;
                $tab_rules[$j] = $temp[$i];
                list($id_dir, $id_rule3, $id_father3) = explode("-", $temp[$i]->id);
                $idf = $id_father3 + $prof + 1;
                $newid = $id_dir . "-" . $j . "-" . $idf;
                $tab_rules[$j]->id = $newid;
            }
            $direct = $directive->directive;
            $rules = $direct->child_nodes();
            foreach($rules as $rule) {
                $direct->remove_child($rule);
            }
            do_directive($directive, &$tab_rules, $dom);
            $newlevel = $tab_rules[$id_rule]->level;
            $_POST['new_level'] = $newlevel;
        } else {
            $newlevel = $tab_rules[$id_rule]->level;
            $_POST['new_level'] = $newlevel;
            echo "<script>alert(\"" . gettext("Not allowed for this rule") . "\")</script>";
        }
    } else {
        $newlevel = $tab_rules[$id_rule]->level;
        $_POST['new_level'] = $newlevel;
        echo "<script>alert(\"" . gettext("Not allowed for this rule") . "\")</script>";
    }
}
/* moves a rule down */
function down($dom, $id, &$tab_rules, $directive) {
    list($id_dir, $id_rule, $id_father) = explode("-", $id);
    if ($id_father == 0) $nbc = $directive->nb_child;
    else $nbc = $tab_rules[$id_father]->nb_child;
    if (($id_rule < count($tab_rules)) && ($nbc > 1)) {
        $stock = array();
        for ($i = 1; $i <= count($tab_rules); $i++) {
            list($id_dir, $id_rule2, $id_father2) = explode("-", $tab_rules[$i]->id);
            if ($id_father2 == $id_father) $stock[] = $tab_rules[$i]->id;
        }
        $i = 0;
        while ($i < count($stock)) {
            if ($stock[$i] == $id) $ind = $i;
            $i++;
        }
        if ($ind < count($stock) - 1) {
            up($dom, $stock[$ind + 1], &$tab_rules, $directive);
        } else {
            $newlevel = $tab_rules[$id_rule]->level;
            $_POST['new_level'] = $newlevel;
            echo "<script>alert(\"" . gettext("Not allowed for this rule") . "\")</script>";
        }
    } else {
        $newlevel = $tab_rules[$id_rule]->level;
        $_POST['new_level'] = $newlevel;
        echo "<script>alert(\"" . gettext("Not allowed for this rule") . "\")</script>";
    }
}
?>
