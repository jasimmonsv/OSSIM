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
* - Panel()
* - getCategoryName()
* - setup()
* - save()
* - get()
* Classes list:
* - Panel
*/
require_once 'classes/Security.inc';
class Panel {
    var $params = array();
    function Panel() {
        return;
    }
    function getCategoryName() {
        return _("Category Name not configured");
    }
    function setup($params) {
        if (!isset($params['plugin_opts'])) {
            echo "<b>Warning: old format detected, please configure again</b><br>";
            return;
        }
        $all_options = $params['plugin_opts'];
        $plugin_opts = array();
        foreach($this->defaults as $var => $value) {
            if (isset($all_options[$var])) {
                $plugin_opts[$var] = & $all_options[$var];
            } else {
                $plugin_opts[$var] = $value;
            }
        }
        $this->params['plugin'] = $params['plugin'];
        $this->params['plugin_opts'] = $plugin_opts;
        $this->params['window_opts'] = $params['window_opts'];
        if (isset($params['metric_opts'])) {
            $this->params['metric_opts'] = $params['metric_opts'];
        } else {
            $this->params['metric_opts'] = array();
        }
    }
    // This method is called from $ajax->saveConfig(), in case the plugin
    // needs to modify data at save time (ex. the import plugin)
    function save() {
        return $this->get();
    }
    function get($param = null, $category = 'plugin_opts') {
        // if $param is null, return all params
        if ($param === null) {
            return $this->params;
        }
        if (isset($this->params[$category][$param])) {
            $ret = stripslashes($this->params[$category][$param]);
        } else {
            echo "Warning, not defined var '$param', shouldn't occur, please report<br>";
            $ret = null;
        }
        return $ret;
    }
}
?>
