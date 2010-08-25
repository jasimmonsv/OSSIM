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
* - Control_panel_host()
* - get_host_ip()
* - get_time_range()
* - get_max_c()
* - get_max_a()
* - get_max_c_date()
* - get_max_a_date()
* - get_list()
* - get_metric_list()
* Classes list:
* - Control_panel_host
*/
require_once ('classes/Host.inc');
require_once ('classes/Net.inc');
require_once ('classes/Session.inc');
require_once ('ossim_conf.inc');
require_once ("ossim_db.inc");
class Control_panel_host {
    var $host_ip;
    var $time_range;
    var $max_c;
    var $max_a;
    var $max_c_date;
    var $max_a_date;
    function Control_panel_host($host_ip, $time_range, $max_c, $max_a, $max_c_date, $max_a_date) {
        $this->host_ip = $host_ip;
        $this->time_range = $time_range;
        $this->max_c = $max_c;
        $this->max_a = $max_a;
        $this->max_c_date = $max_c_date;
        $this->max_a_date = $max_a_date;
    }
    function get_host_ip() {
        return $this->host_ip;
    }
    function get_time_range() {
        return $this->time_range;
    }
    function get_max_c() {
        return $this->max_c;
    }
    function get_max_a() {
        return $this->max_a;
    }
    function get_max_c_date() {
        return $this->max_c_date;
    }
    function get_max_a_date() {
        return $this->max_a_date;
    }
    function get_list($conn, $args, $limit) {
        $query = OssimQuery("SELECT * FROM control_panel $args LIMIT $limit");
        if (!$rs = & $conn->Execute("$query")) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list[] = new Control_panel_host($rs->fields["id"], $rs->fields["time_range"], $rs->fields["max_c"], $rs->fields["max_a"], $rs->fields["max_c_date"], $rs->fields["max_a_date"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    /*
    * get a list of hosts that exceed his threshold
    * ordered by compromise or attack
    */
    function get_metric_list($conn, $time_range, $type, $assets_filter="", $dates_filter) {
        $list = array();
        if ($type == 'compromise') {
            $sql = "SELECT * FROM control_panel 
                WHERE rrd_type = 'host' AND 
                    time_range = ? AND max_c > 0 $assets_filter $dates_filter
                ORDER BY max_c_date DESC";
            $params = array(
                $time_range
            );
        } elseif ($type == 'attack') {
            $sql = "SELECT * FROM control_panel 
                WHERE rrd_type = 'host' AND 
                    time_range = ? AND max_a > 0 $assets_filter $dates_filter
                ORDER BY max_a_date DESC";
            $params = array(
                $time_range
            );
        }
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                /* get host threshold */
                if (!strcmp($type, 'compromise')) {
                    $threshold = Host::ipthresh_c($conn, $rs->fields["id"]);
                } elseif (!strcmp($type, 'attack')) {
                    $threshold = Host::ipthresh_a($conn, $rs->fields["id"]);
                } else {
                    /* defensive programming */
                    $conf = $GLOBALS["CONF"];
                    $threshold = $conf->get_conf("threshold");
                }
                if (((!strcmp($type, 'compromise')) and ($rs->fields["max_c"] > $threshold)) || ((!strcmp($type, 'attack')) and ($rs->fields["max_a"] > $threshold))) {
                    if (Session::hostAllowed($conn, $rs->fields["id"])) {
                        $list[] = new Control_panel_host($rs->fields["id"], $rs->fields["time_range"], $rs->fields["max_c"], $rs->fields["max_a"], $rs->fields["max_c_date"], $rs->fields["max_a_date"]);
                    }
                }
                $rs->MoveNext();
            }
        }
        return $list;
    }
}
?>
