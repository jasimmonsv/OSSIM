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
* - getCategoryName()
* - showSubCategoryHTML()
* - showSettingsHTML()
* - _pear_error()
* - showWindowContents()
* Classes list:
* - Plugin_Rss extends Panel
*/
class Plugin_Rss extends Panel {
    var $defaults = array(
        'rss_source_url' => 'http://secunia.com/information_partner/anonymous/o.rss',
        'rss_max_entries' => '5',
        'rss_max_title_len' => '0',
        'rss_max_desc_len' => '150',
        'rss_fade_out' => '0'
    );
    function getCategoryName() {
        return _("RSS Feed");
    }
    function showSubCategoryHTML() {
        $rss_fade_out_yes = $rss_fade_out_no = '';
        if ($this->get('rss_fade_out') == '1') {
            $rss_fade_out_yes = 'checked';
        } else {
            $rss_fade_out_no = 'checked';
        }
        $html = "";
        $html.= _("Feed url");
        $html.= ':<br/><input type ="text" size="60" name="rss_source_url" value ="' . $this->get('rss_source_url') . '"><br/>';
        $html.= _("Max entries to show");
        $html.= ':<br/><input type ="text" size="5" name="rss_max_entries" value ="' . $this->get('rss_max_entries') . '"><br/>';
        $html.= _("Max title length in chars");
        $html.= ':<br/><input type ="text" size="5" name="rss_max_title_len" value ="' . $this->get('rss_max_title_len') . '"><br/>';
        $html.= _("Max description length in chars");
        $html.= ':<br/><input type ="text" size="5"name="rss_max_desc_len" value ="' . $this->get('rss_max_desc_len') . '"><br/>';
        $html.= _("Fade older RSS entries using smaller text?") . ':<br/>
            <input type="radio" name="rss_fade_out" value="1" ' . $rss_fade_out_yes . '>' . _("Yes") . '<br/>
            <input type="radio" name="rss_fade_out" value="0" ' . $rss_fade_out_no . '>' . _("No") . '
            <br/>
        ';
        return $html;
    }
    function showSettingsHTML() {
        return _("No extra settings needed for this category");
    }
    function _pear_error() {
        $error = _("Could not find XML_Parser and/or XML_RSS Pear packages.") . "<br>";
        $error.= _("Try installing them with the command: 'pear install -a xml_parser xml_rss' as root") . "<br>";
        $error.= _("If you are using Debian just try: 'apt-get install php-xml-parser php-xml-rss");
        return $error;
    }
    function showWindowContents() {
        $included = @include_once ("XML/Parser.php");
        if (!$included) {
            return $this->_pear_error();
        }
        $included = @include_once ("XML/RSS.php");
        if (!$included) {
            return $this->_pear_error();
        }
        if (ini_get("allow_url_fopen") != 1) {
            return _("You need 'allow_url_fopen=On' in your php.ini for this to work");
        }
        $html = "";
        $numitems = 0;
        $rss = & new XML_RSS($this->get('rss_source_url'));
        $rss->parse();
        $max_title_length = $this->get('rss_max_title_len');
        $max_desc_length = $this->get('rss_max_desc_len');
        $html.= "<dl>\n";
        $closing_small = $this->get('rss_fade_out') == 1 ? '' : "</small>";
        foreach($rss->getItems() as $item) {
            if ($numitems >= $this->get('rss_max_entries')) {
                break;
            }
            if ($max_title_length > 0) {
                $title = substr($item['title'], 0, $max_title_length);
                if (strlen($item['title']) > strlen($title)) $title.= "...";
            } else {
                $title = $item['title'];
            }
            if ($max_desc_length > 0) {
                $desc = substr($item['description'], 0, $max_desc_length);
                if (strlen($item['description']) > strlen($desc)) $desc.= "...";
            } else {
                $desc = $item['description'];
            }
            $title = utf8_decode($title);
            $desc = utf8_decode($desc);
            // intentionally left out closing <small>, needs testing on different browsers.
            $html.= "<li><a href=\"" . $item['link'] . "\" target=\"_blank\">" . $title . "</a> <small>" . $desc . $closing_small . "</li>\n";
            $numitems++;
        }
        if ($this->get("rss_fade_out") == 1) {
            for ($i = 0; $i < $numitems; $i++) {
                $html.= "</small>";
            }
        }
        $html.= "</dl>\n";
        return $html;
    }
}
?>
