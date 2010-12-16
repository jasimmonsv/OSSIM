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
* - Directive()
* - printDirective()
* - getDirectiveFromXML()
* - new_directive_id()
* - init_directive()
* - findorder()
* - do_directive()
* - is_free()
* Classes list:
* - Directive
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
/**
 * This class represents a Directive, a collection of a name, an id, a priority
 * and a rule, root of the rule tree. Matches the directive node in XML
 */
class Directive {
    /**
     * The directive id (matches the XML attribute)
     * @access private
     * @var string
     */
    var $id;
    /**
     * The directive name (matches the XML attribute)
     * @access private
     * @var string
     */
    var $name;
    /**
     * The directive prority (matches the XML attribute)
     * @access private
     * @var string
     */
    var $priority;
    /**
     * The rule associated with the directive (matches the XML node <rule>)
     * @access private
     * @var array
     */
    var $rules;
    /**
     * The directive (matches the XML node <directive>)
     * @access private
     * @var Dom element
     */
    var $directive;
    /**
     * The constructor.
     */
    function Directive($id, $name, $priority, &$rules, &$directive) {
        $this->id = $id;
        $this->name = $name;
        $this->priority = $priority;
        $this->rules = & $rules;
        $this->directive = & $directive;
    }
    /**
     * Prints on the output the HTML code.
     */
    function printDirective($level,$xml_file="") {
        $id = $this->id;
        $name = $this->name;
        $priority = $this->priority;
        $rules = $this->rules;
        $nbr = count($rules) + 1;
        $newid = $this->id . "-" . $nbr . "-0";
?>
	<!-- rule table -->
    <table align="center">
      <tr><th colspan=<?php
        echo $level + 19; ?>>
        <a TARGET="right" style="font-size:13px" href="../right.php?directive=<?php
        echo $this->id; ?>&level=<?php
        echo $_GET['level']; ?>&action=edit_dir&id=<?php
        echo $this->id; ?>&xml_file=<?php echo $xml_file?>" TITLE="<?php
        echo gettext("Click to modify this directive"); ?>"><img src="../../pixmaps/tables/table_edit.png" align="absmiddle" border="0"></img>&nbsp;<?php
        print "$name<br><font style='font-size:10px'>"._("Directive")." $id ("._("Priority").": $priority )</font> "; ?></a></th></tr>
      <tr>
 		<?php
        for ($i = 0; $i < $level; $i++) print '<td>&nbsp&nbsp&nbsp&nbsp&nbsp</td>';
?>
          <?php
            if(strpos($_SESSION['directive'],'nb_child')===false){
                $urlAddRule='#';
                $jsAddRule=" onClick=\"alert('return false\" ";
            }else{
                $urlAddRule='../include/utils.php?query=add_rule&id='.$newid.'&xml_file='.$xml_file;
                $jsAddRule='';
            }
          ?>
		<td nowrap>&nbsp&nbsp<a <?php echo $jsAddRule; ?> href="<?php echo $urlAddRule; ?>" TITLE="<?php echo gettext("Add a rule at this directive"); ?>"><img src="../../pixmaps/plus.png" border="0"></img></a></td>
		<td nowrap>&nbsp&nbsp<a onclick="javascript:if (confirm('<?php echo gettext("Are you sure you want to delete all rules ?"); ?>')) { window.open('../include/utils.php?query=del_all_rule','right'); }" style="marging-left:20px; cursor:pointer" TITLE="<?php echo gettext("Delete all rules of this directive"); ?>"><img src="../../pixmaps/delete.gif" border="0"></img></a></td>
    	<td nowrap>&nbsp&nbsp<a TARGET="right" href="../include/utils.php?query=copy_directive&id=<?php echo $id ?>" TITLE="<?php echo gettext("Copy this directive to a new"); ?>"><img src="../../pixmaps/copy.png" border="0"></img></a></td>
		<td colspan="4" nowrap></td>
        <th> <?php
        echo gettext("Name"); ?> </th>
        <th> <?php
        echo gettext("Reliability"); ?> </th>
        <th> <?php
        echo gettext("Time_out"); ?> </th>
        <th> <?php
        echo gettext("Occurrence"); ?> </th>
        <th> <?php
        echo gettext("From"); ?> </th>
        <th> <?php
        echo gettext("To"); ?> </th>
        <th> <?php
        echo gettext("Port_from"); ?> </th>
        <th> <?php
        echo gettext("Port_to"); ?> </th>
        <th> <?php
        echo gettext("Sensor"); ?> </th>
        <th nowrap> <?php
        echo gettext("Plugin ID"); ?> </th>
        <th nowrap> <?php
        echo gettext("Plugin SID"); ?> </th>
      </tr>
	<?php
        /* check if the current rule is a new rule and print rules*/
        if (isset($_SESSION['rule'])) {
            $srule = unserialize($_SESSION['rule']);
            list($id_dir, $id_rule, $id_father) = explode("-", $srule->id);
            for ($i = 1; $i <= count($rules); $i++) {
                if (($i == $id_rule) && ($srule->is_new(&$rules)) && ($id_dir == $this->id)) $srule->print_rule($level, &$rules);
                $rules[$i]->print_rule($level, &$rules, $xml_file);
            }
            if (($id_rule > count($rules)) && ($srule->is_new(&$rules)) && ($id_dir == $this->id)) $srule->print_rule($level, &$rules);
        } else {
            for ($i = 1; $i <= count($rules); $i++) {
                $rules[$i]->print_rule($level, &$rules, $xml_file);
            }
        }
    }
}
/* Read the xml file directly to get the directive */
function getDirectiveFromXMLFile($XML_FILE, $directive_id) {
	
}
/* Read the xml code to creates a new directive object and return it*/
function getDirectiveFromXML($dom, $directive_id, $ruleid = 0, $rulename = "") {
    $_POST['ind'] = 0;
    $tab_rules = array();
    $order = findorder($dom, $directive_id);
    if ($directive_id) {
        $doc = $dom->get_elements_by_tagname('directive');
		$dir = $doc[$order];
        if ($dir) {
            $directive = new Directive($dir->get_attribute('id') , $dir->get_attribute('name') , $dir->get_attribute('priority') , &$tab_rules, $dir);
        } else {
            $directive = new Directive($ruleid, $rulename, 2, &$tab_rules, $dir);
        }
    }
    $_POST['dir_id'] = $directive->id;
    init_directive($dir, &$tab_rules, 1, 0);
    $nb_child = 0;
    /* count the number of child of each rule */
    for ($i = 1; $i <= count($tab_rules); $i++) {
        list($id_dir, $id_rule, $id_father) = explode("-", $tab_rules[$i]->id);
        if ($id_father == 0) $nb_child++;
    }
    $directive->nb_child = $nb_child;
    for ($i = 1; $i <= count($tab_rules); $i++) {
        list($id_dir, $id_rule, $id_father) = explode("-", $tab_rules[$i]->id);
        $nb_child = 0;
        for ($j = 1; $j <= count($tab_rules); $j++) {
            list($id_dir2, $id_rule2, $id_father2) = explode("-", $tab_rules[$j]->id);
            if ($id_rule == $id_father2) {
                $nb_child++;
            }
        }
        $tab_rules[$i]->nb_child = $nb_child;
    }
    return $directive;
}
/* calculares the first directive id which is free in a xml file */
function new_directive_id_by_directive_file($XML_FILE,$mini=0) {
	if (!preg_match("/server\//",$XML_FILE)) $XML_FILE = "/etc/ossim/server/" . $XML_FILE;
	$lines = file($XML_FILE);
	$ids = array();
	$ind = $mini;
	foreach ($lines as $line) {
		if (preg_match("/directive id\=\"(\d+)\"/",$line,$found)) {
			$ids[] = $found[1];
		}
	}
	sort($ids);
	foreach ($ids as $id) {
		if ($ind == 0) $ind = $id;
		elseif ($id == $ind+1) $ind = $id;
	}
	
    return ($ind > 0) ? $ind+1 : $mini;
}
/* calculates the first directive id which is free */
function new_directive_id($cat_id) {
    $category = get_category_by_id($cat_id);
    $init = $category->mini;
    $max = $category->maxi;
    $XML_FILE = "/etc/ossim/server/" . $category->xml_file;
    /*
	init_file($XML_FILE);
    if (!$dom = domxml_open_file($XML_FILE, DOMXML_LOAD_SUBSTITUTE_ENTITIES)) {
        echo _("Error while parsing the document")."\n";
        exit;
    }
    $table = $dom->get_elements_by_tagname('directive');
    if (count($table) == 0) {
        $ind = $init;
    } else {
        $i = $init;
        $find = FALSE;
        while (($i < $max) && ($find == FALSE)) {
            $find = TRUE;
            $j = 0;
            while (($j < count($table)) && ($find == TRUE)) {
                $id = $table[$j]->get_attribute('id');
                if (strcmp($i, $id) == 0) {
                    $find = FALSE;
                }
                $j++;
            }
            $ind = $i;
            $i++;
        }
    }
    release_file($XML_FILE);
	*/
	$lines = file($XML_FILE);
	$ids = array();
	$ind = 0;
	foreach ($lines as $line) {
		if (preg_match("/directive id\=\"(\d+)\"/",$line,$found)) {
			$ids[] = $found[1];
		}
	}
	sort($ids);
	foreach ($ids as $id) {
		if ($ind == 0) $ind = $id;
		elseif ($id == $ind+1) $ind = $id;
	}
	
    return ($ind > 0) ? $ind+1 : $init;
}
/* Creates a new directive object composed by all rules of the directive*/
function init_directive($directive, &$tab_rules, $level, $father) {
    if ($directive && $directive->has_child_nodes()) {
        $rules = $directive->child_nodes();
        foreach($rules as $rule) {
            if (($rule->type == XML_ELEMENT_NODE) && ($rule->tagname() == 'rule')) {
                $_POST['ind']++;
                $ind = $_POST['ind'];
                $dir_id = $_POST['dir_id'];
                $id = $dir_id . '-' . $ind . '-' . $father;
                $temp = new Rule($id, $level, $rule, $rule->get_attribute('name') , $rule->get_attribute('plugin_id') , $rule->get_attribute('type') , $rule->get_attribute('plugin_sid') , $rule->get_attribute('from') , $rule->get_attribute('port_from') , $rule->get_attribute('to') , $rule->get_attribute('port_to') , $rule->get_attribute('protocol') , $rule->get_attribute('sensor') , $rule->get_attribute('occurrence') , $rule->get_attribute('time_out') , $rule->get_attribute('reliability') , $rule->get_attribute('condition') , $rule->get_attribute('value') , $rule->get_attribute('interval') , $rule->get_attribute('absolute') , $rule->get_attribute('sticky') , $rule->get_attribute('sticky_different') , $rule->get_attribute('userdata1') , $rule->get_attribute('userdata2') , $rule->get_attribute('userdata3') , $rule->get_attribute('userdata4') , $rule->get_attribute('userdata5') , $rule->get_attribute('userdata6') , $rule->get_attribute('userdata7') , $rule->get_attribute('userdata8') , $rule->get_attribute('userdata9') , $rule->get_attribute('filename') , $rule->get_attribute('interface') , $rule->get_attribute('username') , $rule->get_attribute('password'));
                $tab_rules[$ind] = $temp;
                if ($rule->has_child_nodes()) {
                    $rules = $rule->child_nodes();
                    foreach($rules as $rule) {
                        init_directive($rule, &$tab_rules, $level + 1, $ind);
                    }
                }
            }
        }
    }
}
/* Find the position of a directive in the xml file */
function findorder($dom, $directive_id) {
    $count = 0;
    foreach($dom->get_elements_by_tagname('directive') as $directive) {
        $id = $directive->get_attribute('id');
		$name = $directive->get_attribute('name');
        if (!strcmp($id, $directive_id)) {
            $order = $count;
        }
        $count++;
    }
    return $order;
}
/* recursive function to create a directive */
function do_directive($directive, &$tab_rules, $dom) {
    $direct = $directive->directive;
    for ($ind = 1; $ind <= count($tab_rules); $ind++) {
        $rule = $tab_rules[$ind];
        list($id_dir, $id_rule, $id_father) = explode("-", $rule->id);
        if ($id_father == 0) {
            $node = $tab_rules[$id_rule]->getXMLNode($dom);
            $node = $direct->append_child($node);
            $tab_rules[$id_rule]->rule = $node;
            if ($tab_rules[$id_rule]->nb_child > 0) {
                do_rules($id_rule, &$tab_rules, $dom);
            }
        }
    }
}
/* Check if the directive id is free */
function is_free($directive_id,$XML_FILE="") {
    if ($XML_FILE == "") $XML_FILE = get_directive_file($directive_id);
    //if (!file_exists($XML_FILE)) return "false";
    init_file($XML_FILE);
    $dom = domxml_open_file($XML_FILE, DOMXML_LOAD_SUBSTITUTE_ENTITIES);
    $count = 1;
    $free = "true";
    foreach($dom->get_elements_by_tagname('directive') as $directive) {
        $id = $directive->get_attribute('id');
        $name = $directive->get_attribute('name');
        $count++;
        if (strcmp($id, $directive_id) == 0) {
            $free = "false";
        }
    }
    release_file($XML_FILE);
    return $free;
}
?>