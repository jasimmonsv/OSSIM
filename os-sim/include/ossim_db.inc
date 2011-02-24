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
* - ossim_db()
* - connect()
* - pconnect()
* - nconnect()
* - snort_connect()
* - opennms_connect()
* - phpgacl_connect()
* - osvdb_connect()
* - close()
* - max_val()
* - get_order()
* Classes list:
* - ossim_db
*/
/**
 ** Common functions to DB management
 *
 */
require_once ('ossim_error.inc');
require_once "ossim_conf.inc";
$conf = new ossim_conf($read_db = false);
if ($conf->get_conf("ossim_type") == 'mysql' && !function_exists('mysql_connect')) {
    die(ossim_error("No mysql support available in your PHP installation, try installing the php-mysql extension/package"));
}
$adodb_path = $conf->get_conf("adodb_path");
if (!is_readable($adodb_path)) {
    $error = new OssimError();
    $error->display("ADODB_PATH");
}
require_once ("$adodb_path/adodb.inc.php");
require_once ('ossim_sql.inc');
class ossim_db {
    var $conf;
    var $conn;
    /*
    *  Connect and close DB functions
    *  Read DB properties from /etc/ossim.conf
    */
    function ossim_db() {
        require_once ('ossim_conf.inc');
        $this->conf = new ossim_conf($read_db = false);
        $adodb_path = $this->conf->get_conf("adodb_path");
        require_once ("$adodb_path/adodb.inc.php");
        $this->conn = null;
    }
	//function __destruct () {
	//	$this->conn->Close();
	//}
    function connect() {
        return $this->nconnect();
    }
    function pconnect() {
        $this->conn = & ADONewConnection($this->conf->get_conf("ossim_type"));
        $this->conn->PConnect($this->conf->get_conf("ossim_host") , $this->conf->get_conf("ossim_user") , $this->conf->get_conf("ossim_pass") , $this->conf->get_conf("ossim_base"));
        return $this->conn;
    }
    function nconnect() {
        $this->conn = & ADONewConnection($this->conf->get_conf("ossim_type"));
        $this->conn->NConnect($this->conf->get_conf("ossim_host") , $this->conf->get_conf("ossim_user") , $this->conf->get_conf("ossim_pass") , $this->conf->get_conf("ossim_base"));
        return $this->conn;
    }
    function custom_connect($host,$user,$pass) {
        $this->conn = & ADONewConnection($this->conf->get_conf("ossim_type"));
        $this->conn->NConnect($host, $user, $pass, $this->conf->get_conf("ossim_base"));
        return $this->conn;
    }
    function snort_connect() {
        $conf = $GLOBALS["CONF"];
        $base = $conf->get_conf("snort_base");
        $user = $conf->get_conf("snort_user");
        $pass = $conf->get_conf("snort_pass");
        $host = $conf->get_conf("snort_host");
        $type = $conf->get_conf("snort_type");
        $this->conn = & ADONewConnection($type);
        $this->conn->NConnect($host, $user, $pass, $base);
        return $this->conn;
    }
	function snort_custom_connect($base) {
        $conf = $GLOBALS["CONF"];
        $user = $conf->get_conf("snort_user");
        $pass = $conf->get_conf("snort_pass");
        $host = $conf->get_conf("snort_host");
        $type = $conf->get_conf("snort_type");
        $this->conn = & ADONewConnection($type);
        $this->conn->NConnect($host, $user, $pass, $base);
        return $this->conn;
    }
    function opennms_connect() {
        $conf = $GLOBALS["CONF"];
        $base = $conf->get_conf("opennms_base");
        $user = $conf->get_conf("opennms_user");
        $pass = $conf->get_conf("opennms_pass");
        $host = $conf->get_conf("opennms_host");
        $type = $conf->get_conf("opennms_type");
        $this->conn = & ADONewConnection($type);
        $this->conn->NConnect($host, $user, $pass, $base);
        return $this->conn;
    }
    function phpgacl_connect() {
        $conf = $GLOBALS["CONF"];
        $base = $conf->get_conf("phpgacl_base");
        $user = $conf->get_conf("phpgacl_user");
        $pass = $conf->get_conf("phpgacl_pass");
        $host = $conf->get_conf("phpgacl_host");
        $type = $conf->get_conf("phpgacl_type");
        $this->conn = & ADONewConnection($type);
        if ($this->conn->NConnect($host, $user, $pass, $base)) return $this->conn;
        else return NULL;
    }
    function osvdb_connect() {
        $conf = $GLOBALS["CONF"];
        $base = $conf->get_conf("osvdb_base");
        $user = $conf->get_conf("osvdb_user");
        $pass = $conf->get_conf("osvdb_pass");
        $host = $conf->get_conf("osvdb_host");
        $type = $conf->get_conf("osvdb_type");
        $this->conn = & ADONewConnection($type);
        if ($this->conn->NConnect($host, $user, $pass, $base)) return $this->conn;
        else return NULL;
    }
    function close($conn) {
        $conn->Close();
    }
    /*
    *  Common functions
    */
    function max_val($conn, $column, $table, $where = "") {
        $query = "SELECT max($column) FROM $table $where;";
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } elseif (!$rs->EOF) {
            return $rs->fields["max($column)"];
        }
    }
    function get_order($column, $order) {
        if ($order == $column) {
            return "$order DESC";
        } else {
            return $column;
        }
    }
}
?>
