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
* - encode()
* - decode()
* - save()
* - getCategoryName()
* - showSubCategoryHTML()
* - showSettingsHTML()
* - showWindowContents()
* Classes list:
* - Plugin_Config_Exchange extends Panel
*/
require_once 'panel/Panel.php';
class Plugin_Config_Exchange extends Panel {
    var $defaults = array(
        'import_text' => ''
    );
    function encode($options) {
        $text = $options['plugin_opts']['exported_plugin'] . '::' . "\n\r";
        $text.= chunk_split(base64_encode(serialize($options)) , 35);
        return $text;
    }
    function decode($text) {
        list($plugin, $data) = explode('::', trim($text));
        $data = preg_replace("/\s*/s", '', $data);
        $data = unserialize(base64_decode($data));
        $data['exported_plugin'] = $plugin;
        return $data;
    }
    function save() {
        $text = $this->get('import_text');
        $data = $this->decode($text);
        $data['plugin'] = $data['plugin_opts']['exported_plugin'];
        return $data;
    }
    function getCategoryName() {
        return _("Config Import");
    }
    function showSubCategoryHTML() {
        $html = _("Import text") . ':<br/>';
        $html.= '<textarea name="import_text" rows="17" cols="36" wrap="off">';
        $html.= $this->get('import_text');
        $html.= '</textarea>';
        return $html;
    }
    function showSettingsHTML() {
        $opts = $this->get();
        $data = $this->decode($opts['plugin_opts']['import_text']);
        foreach($data as $k => $v) {
            echo "[$k] = $v<br>\n";
        }
    }
    // No need to have this method because save() will transform the
    // data to the native plugin so next calls will use the
    // showWindowContents() of the native plugin
    function showWindowContents() {
        return "Error, this method shouldn't be called";
    }
}
?>
