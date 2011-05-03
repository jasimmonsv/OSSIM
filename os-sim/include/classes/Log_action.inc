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
* - log_action()
* - get_login()
* - get_from()
* - get_date()
* - get_code()
* - get_info()
* - get_descr()
* - get_priority()
* - log()
* - log_action_to_db()
* - get_userip()
* - get_list()
* - get_count()
* - log_action_replace()
* - get_loginfo()
* - get_last_pass()
* Classes list:
* - log_action
*/
require_once 'classes/Session.inc';
require_once 'ossim_db.inc';
require_once 'classes/Util.inc';

Class log_action {
    var $login;
    var $from;
    var $date;
    var $code;
    var $info;
    var $descr;
    var $priority;
    function log_action($login, $from, $date, $code, $info, $descr, $priority) {
        $this->login = $login;
        $this->from = Util::htmlentities($from, ENT_COMPAT, "UTF-8");
        $this->date = $date;
        $this->code = $code;
        $this->info = $info;
        $this->descr = $descr;
        $this->priority = $priority;
    }
    function get_login() {
        return $this->login;
    }
    function get_from() {
        return $this->from;
    }
    function get_date() {
        return $this->date;
    }
    function get_code() {
        return $this->code;
    }
    function get_info() {
        return $this->info;
    }
    function get_descr() {
        return $this->descr;
    }
    function get_priority() {
        return $this->priority;
    }
    function log($log_code, $extrainfo) {
        require_once ('ossim_conf.inc');
        $conf = $GLOBALS["CONF"];
        $logact = $conf->get_conf("user_action_log", FALSE);
        if (!$logact) {
            return;
        }
        $db = new ossim_db();
        $conn = $db->connect();
        if (!Log_action::log_action_to_db($conn, $log_code)) return;
        $user_ip = Log_action::get_userip();
        $user = Session::get_session_user();
        if ($user == "" && $extrainfo[0] != "") {
        	$user = $extrainfo[0];
        }
        $info = Log_action::get_loginfo($conn, $log_code);
        $info = Log_action::log_action_replace($info, $extrainfo);
        $log_syslog = $conf->get_conf("log_syslog", FALSE);
        if ($log_syslog) {
            define_syslog_variables();
            openlog("OSSIM", LOG_PID | LOG_PERROR, LOG_AUTH);
            syslog(LOG_NOTICE, "User event log [$log_code]: $user from $user_ip: $info");
        }
	// tmp fix DK: timestamp is limited to seconds, two users can do something at the same second
        $sql = "INSERT IGNORE INTO log_action (login, ipfrom, date, code, info) VALUES (?, ?, current_timestamp , ?, ?)";

        $params = array(
            $user,
            $user_ip,
            $log_code,
            $info
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'Error inserting log: ' . $conn->ErrorMsg() . '<BR>';
            return;
        }
    }
    function log_action_to_db($conn, $action_log) {
        $sql = "SELECT * FROM log_config where code = ?";
        $params = array(
            $action_log
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
            return 0;
        } else {
            return (intval($rs->fields['log']));
        }
    }
    function get_userip() {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) != "") {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        return $_SERVER['REMOTE_ADDR'];
    }
    function get_list($conn, $filter = "", $order = "", $inf = 0, $sup = 25) {
        /* number of logs per page */
        $nlogs = $sup - $inf;
        $query = OssimQuery("SELECT log_action.*, log_config.priority, log_config.descr from log_action,
log_config where log_config.code = log_action.code $filter $order LIMIT $nlogs
OFFSET $inf");
        $list = array();
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
            return array();
        }
        while (!$rs->EOF) {
            $list[] = new Log_action($rs->fields["login"], $rs->fields["ipfrom"], $rs->fields["date"], $rs->fields["code"], $rs->fields["info"], $rs->fields["descr"], $rs->fields["priority"]);
            $rs->MoveNext();
        }
        return $list;
    }
    function get_last_pass($conn, $user="") {
    	require_once "ossim_conf.inc";
    	$conf = $GLOBALS["CONF"];
    	$list = array();
    	$pass_history = ($conf->get_conf("pass_history", FALSE)) ? $conf->get_conf("pass_history", FALSE) : 0;
    	$query = "SELECT DISTINCT pass FROM pass_history WHERE user='$user' ORDER BY hist_number DESC LIMIT $pass_history";
    	if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
            return array();
        }
        while (!$rs->EOF) 
		{
            $list[] = $rs->fields["pass"];
            $rs->MoveNext();
        }
		
        return $list;
    }
    function get_count($conn, $cfilter) {
        $query = OssimQuery("SELECT count(*) AS count FROM log_action $cfilter
        ");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            return $rs->fields["count"];
        }
    }
    function log_action_replace($infostr, $arrplace) {
        if (count($arrplace)) {
            $n = 1;
            foreach($arrplace as $auxrplc) {
                $infostr = str_replace("%" . $n . "%", $auxrplc, $infostr);
                $n = $n + 1;
            }
        }
        return $infostr;
    }
    function get_loginfo($conn, $code) {
        $sql = "SELECT * from log_config WHERE code = ?";
        $params = array(
            $code
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        } else {
            return $rs->fields["descr"];
        }
    }
    function delete_by_date_info($conn, $date, $info) {
        $params = array(
            $date,
            $info
        );
        $sql = "DELETE FROM log_action WHERE date=? AND info=?";
        if (!$conn->Execute($sql, $params)) {
            die('error deleting: ' . $conn->ErrorMsg());
        }
    }
    function delete_by_user_code($conn, $user, $code) {
        $where = "";
        
        if($user!="") {
            $where.= "where login='$user'";
        }
        
        if($code!="") {
            if($where!="")
                $where.= "and code='$code'";
            else
                $where.= "where code='$code'";
        }

        $sql = "DELETE FROM log_action $where";
        if (!$conn->Execute($sql, $params)) {
            die('error deleting: ' . $conn->ErrorMsg());
        }
    }
}
?>
