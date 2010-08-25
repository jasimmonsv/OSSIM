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
* - Log_config()
* - get_code()
* - get_log()
* - get_descr()
* - get_priority()
* - get_list()
* - update_log()
* - update_log_conf()
* Classes list:
* - Log_config
*/
require_once ('ossim_conf.inc');
require_once ('ossim_db.inc');
Class Log_config {
    var $code;
    var $log;
    var $descr;
    var $priority;
    function Log_config($code, $log, $descr, $priority) {
        $this->code = $code;
        $this->log = $log;
        $this->descr = $descr;
        $this->priority = $priority;
    }
    function get_code() {
        return $this->code;
    }
    function get_log() {
        return $this->log;
    }
    function get_descr() {
        return $this->descr;
    }
    function get_priority() {
        return $this->priority;
    }
    function get_list($conn, $args = "") {
        $query = OssimQuery("SELECT * FROM log_config $args");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
            return array();
        }
        while (!$rs->EOF) {
            $list[] = new Log_config($rs->fields["code"], $rs->fields["log"], $rs->fields["descr"], $rs->fields["priority"]);
            $rs->MoveNext();
        }
        return $list;
    }
    function update_log($conn, $code, $log) {
        $sql = "UPDATE log_config SET log = ? WHERE code = ?";
        $params = array(
            $log,
            $code
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        }
    }
    function update_log_conf($conn, $log_conflist) {
        foreach($log_conflist as $log_conf) {
            $sql = "UPDATE log_config SET log= ? WHERE code=''";
            $params = array(
                $log_conf
            );
            if ($this->conn->Execute($sql, $params) === false) {
                print 'error updating: ' . $this->conn->ErrorMsg() . '<BR>';
                exit;
            }
        }
    }
}
?>
