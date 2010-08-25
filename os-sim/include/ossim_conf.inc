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
* - ossim_conf()
* - get_conf_from_file()
* - get_conf_from_db()
* - get_conf()
* - is_in_file()
* Classes list:
* - ossim_conf
*/
define('CONF_FILE', "/etc/ossim/framework/ossim.conf");
if (!isset($GLOBALS["CONF"])) $GLOBALS["CONF"] = new ossim_conf();
class ossim_conf {
    var $conf;
    var $db_conf;
    function ossim_conf($read_db = True) {
        $this->conf = ossim_conf::get_conf_from_file(CONF_FILE);
        if ($read_db) $this->db_conf = ossim_conf::get_conf_from_db();
    }
    function get_conf_from_file($conffile) {
        if (!$fd = fopen($conffile, "r")) {
            echo "Can't open config file ($conffile)\n<br/>";
            exit();
        }
        $configuration = NULL;
        while (!feof($fd)) {
            $line = fgets($fd, 2048);
            $line = trim($line);
            if (strncmp($line, "#", 1)) {
                $conf = explode('=', $line);
                $type = trim($conf[0]);
                if (isset($conf[1])) {
                    $value = trim($conf[1]);
                    $configuration["$type"] = $value;
                }
            }
        }
        fclose($fd);
        return $configuration;
    }
    function get_conf_from_db() {
        require_once ("classes/Config.inc");
        $configuration = new Config();
        return $configuration->get_list();
    }
    function get_conf($type, $debug = true) {
        if (isset($this->conf[$type])) return $this->conf[$type];
        elseif (isset($this->db_conf[$type])) return $this->db_conf[$type];
        /*
        * $debug == FALSE
        * don't start output, session cache limiter problem
        */
        if ($debug) {
            print "<b>Warning</b>: Error reading configuration: ";
            print "<b>$type</b> is not set<br/>\n";
        }
        return NULL;
    }
    function is_in_file($type) {
        return isset($this->conf[$type]);
    }
}
?>
