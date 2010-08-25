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
* - upgrade_base()
* - start_upgrade()
* - end_upgrade()
* - reload_acls()
* Classes list:
* - upgrade_base
*/
require_once 'ossim_db.inc';
class upgrade_base {
    /*
    * These properties are set by the Upgrade class,
    * which creates this object
    */
    var $upgrades_path;
    var $conn;
    var $snort;
    var $conf;
    var $about;
    var $ossim_dbtype;
    var $ossim_current_version;
    var $ossim_schema_version;
    function upgrade_base() {
        $db = new ossim_db();
        $this->snort = & $db->snort_connect();
    }
    function start_upgrade() {
        return true;
    }
    function end_upgrade() {
        return true;
    }
    /*
    * Helper method for reloading the users ACL database
    * (needed when new perms are added)
    */
    function reload_acls() {
        $proto = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
        $port = $_SERVER['SERVER_PORT'];
        $host = $_SERVER['SERVER_NAME'];
        $path = $this->conf->get_conf('ossim_link');
        $ossim_acl = $proto . '://' . $host . ':' . $port . '/' . $path . '/setup/ossim_acl.php';
        echo _("Executing") . ": $ossim_acl<br>";
        $handle = fopen($ossim_acl,"r");
        print stream_get_contents($handle);
        fclose($handle);
    }
}
?>
