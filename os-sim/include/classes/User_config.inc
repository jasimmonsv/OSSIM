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
* - User_config()
* - get()
* - set()
* - del()
* - copy_panel()
* Classes list:
* - User_config
*/
/*

CREATE TABLE user_config (
login VARCHAR(64)  NOT NULL REFERENCES users (login),
category VARCHAR(64) NOT NULL DEFAULT 'main',
name VARCHAR(64) NOT NULL,
value TEXT,
PRIMARY KEY (login, category, name)
);

Usage:

require_once 'classes/Session.inc';
require_once 'classes/User_config.inc';
require_once 'ossim_db.inc';

$db = new ossim_db();
$conn = $db->connect();

$config = new User_config($conn);
$login = Session::get_session_user();

// Get stored var as simple text (default)

$var = $config->get($login, 'var_name', 'simple');

// Save a var into the database as a serialized PHP var

$config->set($login, 'var_name', $myvar, 'php');

// Removes a config option

$config->del($login, 'var_name');

*/
require_once 'Log_action.inc';

class User_config {
    var $conn;
    function User_config($conn) {
        $this->conn = $conn;
    }
    /*
    * @param $login string - The user's login name
    * @param $name string - What config entry name to retrieve
    * @param $type string - 'simple' for storing simple values (default) or
    *                       'php'  for storing PHP variables
    * @param $category - What's the category of the $name entry
    */
    function get($login, $name, $type = 'simple', $category = 'main') {
        $sql = "SELECT value
                FROM user_config
                WHERE login=? AND category=? AND name=?";
        $params = array(
            $login,
            $category,
            $name
        );
        if (!$rs = $this->conn->Execute($sql, $params)) {
            //die(ossim_error($this->conn->ErrorMsg()));
            die(ossim_error($sql));
        }
        if ($rs->EOF) {
            return null;
        }
        $value = $rs->fields['value'];
        if ($type == 'simple') {
            return $value;
        } elseif ($type == 'php') {
            //echo "<br><br><br>GET<br>"; 
            //var_dump($value);
            //echo "<br><br>";
            //var_dump(unserialize($value));
            return unserialize($value);
        }
        die(ossim_error("Invalid config type: $type"));
    }
	function get_all($login, $category = 'main') {
        $ret = array();
		$sql = "SELECT name
                FROM user_config
                WHERE login=? AND category=?";
        $params = array(
            $login,
            $category
        );
        if (!$rs = $this->conn->Execute($sql, $params)) {
			//die(ossim_error($this->conn->ErrorMsg()));
            die(ossim_error($sql));
        }
		while (!$rs->EOF) {
			$ret[] = $rs->fields['name'];
			$rs->MoveNext();
		}
		return $ret;
    }
    function set($login, $name, $value, $type = 'simple', $category = 'main') {
        if ($type == 'simple') {
            $val = $value;
        } elseif ($type == 'php') {
            $val = serialize($value);
        } else {
            die(ossim_error("Invalid config type: $type"));
        }
	$params = array(
	    $login,
            $category,
            $name,
            $val
        );

	$sql = "REPLACE INTO user_config (login, category, name, value) VALUES (?, ?, ?, ?)";

        if (!$this->conn->Execute($sql, $params)) {
            die(ossim_error($this->conn->ErrorMsg()));
        }
        $infolog = array("Tabs");
        Log_action::log(49, $infolog);
        return true;
    }
	function rename($rename, $login, $name, $category = 'main') {
        $sql = "UPDATE user_config SET name=? WHERE login=? AND name=? AND category=?";
        $params = array(
            $rename,
			$login,
            $name,
            $category
        );
        if (!$this->conn->Execute($sql, $params)) {
            die(ossim_error($this->conn->ErrorMsg()));
        }
        return true;
    }
    function del($login, $name, $category = 'main') {
        $sql = "DELETE FROM user_config WHERE login=? AND name=? AND category=?";
        $params = array(
            $login,
            $name,
            $category
        );
        if (!$this->conn->Execute($sql, $params)) {
            die(ossim_error($this->conn->ErrorMsg()));
        }
        return true;
    }
    function copy_panel($conn, $from, $to) {
        // User should already have been checked but let's sanitize, just in case.
        require_once ('classes/Security.inc');
        require_once 'ossim_conf.inc';
        ossim_valid($from, OSS_USER, 'illegal:' . _("User name"));
        ossim_valid($to, OSS_USER, 'illegal:' . _("User name"));
        if (ossim_error()) {
            print "Unable to copy over panels, returning<br/>";
            return false;
        }
        $conf = $GLOBALS["CONF"];
        $configs_dir = $conf->get_conf('panel_configs_dir');
    	// AVT
        $regexp = "/$from" . "_(.*)" . '$/';
        $hasavt = false;
        if ($handle = opendir($configs_dir)) {
            while (false !== ($file = readdir($handle))) {
                if (preg_match($regexp, $file, $results)) {
                    if (file_exists("$configs_dir/$from" . "_" . $results[1])) {
                		copy("$configs_dir/$from" . "_" . $results[1], "$configs_dir/$to" . "_" . $results[1]);
                		$hasavt = true;
                    }
                }
            }
        }
        // Database
        if (!$hasavt) {
	        $config = new User_config($conn);
	        $tmp_var = $config->get($from, 'panel_tabs', 'php');
	        $config->set($to, 'panel_tabs', $tmp_var, 'php');
        }
    }
}
?>
