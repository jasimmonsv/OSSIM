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
    /**
     *The constructor
     */
    function Category($name, $xml_file, $mini, $maxi) {
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
    }
}
function get_categories_maxi() {
    $categories = unserialize($_SESSION['categories']);
    $maxi = 0;
    foreach($categories as $category) {
        if ($category->maxi > $maxi) $maxi = $category->maxi;
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
        echo _("Error while parsing the document")."\n";
        exit;
    }
    $table = $dom->get_elements_by_tagname('category');
    $tab_category = array();
    foreach($table as $lign) {
        $temp = new Category($lign->get_attribute('name') , $lign->get_attribute('xml_file') , $lign->get_attribute('mini') , $lign->get_attribute('maxi'));
        $tab_category[] = $temp;
    }
    usort($tab_category, "sort_categories_by_mini");
    $_SESSION['categories'] = serialize($tab_category);
    /* Create a new version of directives.xml*/
    unlink('/etc/ossim/server/directives.xml');
    $fic = fopen('/etc/ossim/server/directives.xml', 'w');
    fwrite($fic, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n\n<!DOCTYPE directives\n  SYSTEM '/etc/ossim/server/directives.dtd'\n [\n");
    foreach($tab_category as $category) {
        $name = explode(".", $category->xml_file);
        $name = $name[0];
        fwrite($fic, "  <!ENTITY " . $name . " SYSTEM '/etc/ossim/server/" . $category->xml_file . "'>\n");
    }
    fwrite($fic, "  ]>\n<directives>\n\n");
    foreach($tab_category as $category) {
        $name = explode(".", $category->xml_file);
        $name = $name[0];
        fwrite($fic, "  &" . $name . ";\n");
    }
    fwrite($fic, "\n</directives>");
    fclose($fic);
    /* Check if all files in categories.xml exist and creates the missing files */
    foreach($tab_category as $category) {
        $file = "/etc/ossim/server/" . $category->xml_file;
        if (!file_exists($file)) {
            $fic = fopen($file, 'w');
            fwrite($fic, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n\n");
            fclose($fic);
        }
    }
}
function get_category_id_by_directive_id($directive_id) {
    foreach(unserialize($_SESSION['categories']) as $category) {
        if ($category->mini <= $directive_id && $directive_id <= $category->maxi) return $category->id;
    }
    return NULL;
}
function get_directive_file($directive_id) {
    // browses the elements of $_SESSION['categories']
    // search witch category has $directive_id
    // return the xml_file of the category
    $categories = unserialize($_SESSION['categories']);
    foreach($categories as $category) {
        if ($directive_id >= $category->mini && $directive_id <= $category->maxi) return "/etc/ossim/server/" . $category->xml_file;
    }
    return NULL;
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
?>