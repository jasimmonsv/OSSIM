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
* - Category()
* - get_categories_maxi()
* - sort_categories_by_mini()
* - init_categories()
* - get_category_id_by_directive_id()
* - get_directive_file()
* - get_category_by_id()
* - check_mini_maxi()
* - enable_category()
* - disable_category()
* - delete_category()
* Classes list:
* - Category
*/
require_once ("utils.php");
require_once ('classes/Session.inc');
/**
 * This class represents a Category, a collection of a name, an id, a xml file
 * a mini and a maxi. It can manage the different categories and associated files.
 */
class Category {
    /**
     * The category name
     * @ccess private
     * @var string
     */
    var $name;
    /**
     * The xml file witch contain directives of this category
     * @ccess private
     * @var string
     */
    var $xml_file;
    /**
     * The minimum directive id of this category
     * @ccess private
     * @var integer
     */
    var $mini;
    /**
     * The maximum directive id of this category
     * @ccess private
     * @var integer
     */
    var $maxi;
    /**
     * The id of this category, composed as follow: 'mini-maxi'
     * @ccess private
     * @var string
     */
    var $id;
    var $active;
    /**
     *The constructor
     */
    function Category($name, $xml_file, $mini, $maxi, $active = true) {
        $index = 1;
        if ($xml_file == NULL) {
            $xml_file = 'new_category_';
            while (file_exists('/etc/ossim/server/' . $xml_file . $index)) {
                $index++;
            }
            $xml_file.= $index . '.xml';
        }
        $this->xml_file = $xml_file;
        $this->name = ($name == NULL) ? 'New category ' . $index : $name;
        $this->mini = ($mini == NULL) ? get_categories_maxi() + 1 : $mini;
        if ($maxi == NULL) {
            for ($i = 2900; $i < 3900; $i++) {
                if (($this->mini + $i) % 1000 == 0) {
                    $this->maxi = $this->mini + $i - 1;
                    break;
                }
            }
        } else $this->maxi = $maxi;
        $this->id = $mini . '-' . $maxi;
        $this->active = $active;
    }
}
function get_categories_maxi() {
    $categories = unserialize($_SESSION['categories']);
    $maxi = 0;
    if (is_array($categories)) {
        foreach($categories as $category) {
            if ($category->maxi > $maxi) $maxi = $category->maxi;
        }
    }
    return $maxi;
}
function sort_categories_by_mini($category1, $category2) {
    if ($category1->mini == $category2->mini) return 0;
    return ($category1->mini < $category2->mini) ? -1 : 1;
}
/* Create categories array */
function init_categories() {
    if (!$dom = domxml_open_file('/etc/ossim/server/categories.xml', DOMXML_LOAD_SUBSTITUTE_ENTITIES)) {
        echo "Error while parsing the document\n";
        exit;
    }
    $table = $dom->get_elements_by_tagname('category');
    $minimaxi = array();
    foreach($table as $lign) $minimaxi[$lign->get_attribute('xml_file') ] = array(
        $lign->get_attribute('mini') ,
        $lign->get_attribute('maxi')
    );
    //
    $data = array();
    $f = fopen("/etc/ossim/server/directives.xml", "r");
    while (!feof($f)) {
        $line = trim(fgets($f));
        if (preg_match("/\<\!ENTITY (.*) SYSTEM '(.*)'>/", $line, $found)) {
            $data[$found[1]]["name"] = $found[1];
            $data[$found[1]]["file"] = $found[2];
            $data[$found[1]]["active"] = false;
        }
        if (preg_match("/^\s*\&(.*);/", $line, $found)) {
            $data[$found[1]]["active"] = true;
        }
    }
    fclose($f);
    $tab_category = array();
    $from = 50000;
    $jump = 1000;
    foreach($data as $k => $v) {
        $mini = 999999999;
        $maxi = 0;
        $file = $v["file"];
        if (isset($minimaxi[basename($file) ])) {
            // get from categories.xml
            $mini = $minimaxi[basename($file) ][0];
            $maxi = $minimaxi[basename($file) ][1];
        } else {
            if (file_exists($file)) {
                // calc max/min values
                $xf = fopen($file, "r");
                while (!feof($xf)) {
                    $lin = trim(fgets($xf));
                    if (preg_match("/\<directive id=\"(\d+)\"/", $lin, $fnd)) {
                        if ($fnd[1] > $maxi) $maxi = $fnd[1];
                        if ($fnd[1] < $mini) $mini = $fnd[1];
                    }
                }
                fclose($xf);
            }
        }
        if ($mini == 999999999 || $maxi == 0) {
            $mini = $from;
            $maxi = $mini + $jump + 1;
            $from+= $jump;
        }
        $temp = new Category($v["name"], basename($v["file"]) , $mini, $maxi, $v["active"]);
        $tab_category[] = $temp;
    }
    $_SESSION['categories'] = serialize($tab_category);
    /* Create a new version of directives.xml
    unlink('/etc/ossim/server/directives.xml');
    $fic = fopen('/etc/ossim/server/directives.xml', 'w');
    
    fwrite($fic,"<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n\n<!DOCTYPE directives\n  SYSTEM '/etc/ossim/server/directives.dtd'\n [\n");
    foreach($tab_category as $category)
    {
    $name = explode(".",$category->xml_file);
    $name = $name[0];
    fwrite($fic,"  <!ENTITY ".$name." SYSTEM '/etc/ossim/server/".$category->xml_file."'>\n");
    }
    fwrite($fic,"  ]>\n<directives>\n\n");
    foreach($tab_category as $category)
    {
    $name = explode(".",$category->xml_file);
    $name = $name[0];
    fwrite($fic,"  &".$name.";\n");
    }
    
    $group_list = file('/etc/ossim/server/groups.xml');
    fwrite($fic,"\n");
    $first = true;
    foreach ($group_list as $group)
    if ($first != true)
    fwrite($fic,$group);
    else
    $first = false;
    
    fwrite($fic,"\n\n</directives>");
    fclose($fic);
    */
    /* Check if all files in categories.xml exist and creates the missing files */
    foreach($tab_category as $category) {
        $file = "/etc/ossim/server/" . $category->xml_file;
        if (!file_exists($file)) {
            $fic = fopen($file, 'w');
            fwrite($fic, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n\n");
            fclose($fic);
        }
    }
    indent_groups();
    indent_categories();
}
function get_category_id_by_directive_id($directive_id) {
    foreach(unserialize($_SESSION['categories']) as $category) {
        if ($category->mini <= $directive_id && $directive_id <= $category->maxi) return $category->id;
    }
    return NULL;
}
function get_directive_file($directive_id,$XML_RET="") {
    if ($XML_RET != "") return $XML_RET;
	// browses the elements of $_SESSION['categories']
    // search witch category has $directive_id
    // return the xml_file of the category
    $categories = unserialize($_SESSION['categories']);
    foreach($categories as $category) {
        if ($directive_id >= $category->mini && $directive_id <= $category->maxi) return "/etc/ossim/server/" . $category->xml_file;
    }
    return NULL;
}
function get_directive_real_file($directive_id) {
	$categories = unserialize($_SESSION['categories']);
    foreach($categories as $category) {
	    $lines = file("/etc/ossim/server/".$category->xml_file);
		foreach ($lines as $line) {
			if (preg_match("/directive id\=\"$directive_id\"/",$line)) {
				return $category->xml_file;
			}
		}
    }
    return "";
}
function get_category_by_id($category_id) {
    // browses the elements of $_SESSION['categories']
    // search the one witch has the right id
    $categories = unserialize($_SESSION['categories']);
    foreach($categories as $category) {
        if ($category->id == $category_id) return $category;
    }
    return NULL;
}
/* check that the new range does not exceed over another */
function check_mini_maxi($current_category_id, $mini, $maxi) {
    $categories = unserialize($_SESSION['categories']);
    foreach($categories as $category) {
        if ($category->id != $current_category_id && ($category->mini <= $mini && $mini <= $category->maxi || $category->mini <= $maxi && $maxi <= $category->maxi || $category->mini >= $mini && $maxi >= $category->maxi)) {
            return "false";
        }
    }
    return "true";
}
function enable_category($name, $xmlfile = "") {
    $file = "/etc/ossim/server/directives.xml";
	$xml = file($file);
    $entity = $amp = false;
    $i = $e = $a = 0;
    $le = $la = 0;
	$directive_pos = 26;
    foreach($xml as $line) {
        $i++;
        if (preg_match("/\<\!ENTITY (.*) SYSTEM /", $line, $found)) {
            $e = 1;
            if ($found[1] == $name) $entity = true;
        } else {
            if ($e == 1) {
                $le = $i;
                $e = 0;
            }
        }
        //
        if (preg_match("/^\s*\&(.*);/", $line, $found)) {
            $a = 1;
            if ($found[1] == $name) $amp = true;
        } else {
            if ($a == 1) {
                $la = $i;
                $a = 0;
            }
        }
		
		if (preg_match("/\<directives\>/",$line)) {
			$directive_pos = $i;
		}
    }
	
	if (!$amp && $la == 0) $la = $directive_pos+1; // When directives enabled is empty
	
    $xmlnew = array();
    $i = 0;
    $xmlf = ($xmlfile != "") ? $xmlfile : $name . ".xml";
    foreach($xml as $line) {
        $i++;
        if (!$entity && $le == $i) $xmlnew[] = "<!ENTITY $name SYSTEM '/etc/ossim/server/$xmlf'>";
        if (!$amp && $la == $i) $xmlnew[] = "&" . $name . ";";
        $xmlnew[] = $line;
    }
    $xf = fopen($file, "w");
    foreach($xmlnew as $line) fputs($xf, trim($line) . "\n");
    fclose($xf);
}
function disable_category($name) {
    $file = "/etc/ossim/server/directives.xml";
    $xml = file($file);
    $xf = fopen($file, "w");
    foreach($xml as $line) {
        if (!preg_match("/^\s*\&" . $name . ";/", $line)) fputs($xf, trim($line) . "\n");
    }
    fclose($xf);
}
function delete_category($name) {
    $file = "/etc/ossim/server/directives.xml";
    $xml = file($file);
    $xf = fopen($file, "w");
    foreach($xml as $line) {
        if (!preg_match("/^\s*\&" . $name . ";/i", $line) && !preg_match("/\<\!ENTITY " . $name . " SYSTEM /i", $line)) fputs($xf, trim($line) . "\n");
    }
    fclose($xf);
}
?>