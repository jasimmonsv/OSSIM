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
* - Control_panel_net()
* - get_net_name()
* - get_time_range()
* - get_max_c()
* - get_max_a()
* - get_max_c_date()
* - get_max_a_date()
* - get_list()
* - delete()
* - get_metric_list()
* Classes list:
* - Control_panel_net
*/
require_once ("ossim_db.inc");
class Control_panel_net {
    var $net_name;
    var $time_range;
    var $max_c;
    var $max_a;
    var $max_c_date;
    var $max_a_date;
    function Control_panel_net($net_name, $time_range, $max_c, $max_a, $max_c_date, $max_a_date) {
        $this->net_name = $net_name;
        $this->time_range = $time_range;
        $this->max_c = $max_c;
        $this->max_a = $max_a;
        $this->max_c_date = $max_c_date;
        $this->max_a_date = $max_a_date;
    }
    function get_net_name() {
        return $this->net_name;
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
    function get_list($conn, $args = "", $limit) {
        $count = 0;
        $query = OssimQuery("SELECT * FROM control_panel $args");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                // only insert in list if net exists in net_qualification
                $query = OssimQuery("SELECT * FROM net_qualification
                                             WHERE net_name = '" . $rs->fields["id"] . "'");
                if (!$rs2 = & $conn->Execute($query)) {
                    print $conn->ErrorMsg();
                }
                if (($rs2->fields[0]) && ($count < $limit)) {
                    $list[] = new Control_panel_net($rs->fields["id"], $rs->fields["time_range"], $rs->fields["max_c"], $rs->fields["max_a"], $rs->fields["max_c_date"], $rs->fields["max_a_date"]);
                    $count++;
                }
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function delete($conn, $net_name) {
        $sql = "DELETE FROM control_panel WHERE id = ?";
        $params = array(
            $net_name
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    /*
    * get a list of nets that exceed his threshold
    * ordered by compromise or attack
    */
    function get_metric_list($conn, $time_range, $type, $assets_filter="", $dates_filter) {
        if ($type == 'compromise') {
            $sql = "SELECT * FROM control_panel WHERE rrd_type = 'net' AND
                    time_range = ? $assets_filter $dates_filter ORDER BY max_c_date DESC";
            $params = array(
                $time_range
            );
        } elseif ($type == 'attack') {
            $sql = "SELECT * FROM control_panel WHERE rrd_type = 'net' AND
                    time_range = ? $assets_filter $dates_filter ORDER BY max_a_date DESC";
            $params = array(
                $time_range
            );
        }
        //echo "$sql";
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                // only insert in list if net exists in net_qualification
                $sql = "SELECT * FROM net_qualification  WHERE net_name = ?";
                $params = array(
                    $rs->fields["id"]
                );
                if (!$rs2 = & $conn->Execute($sql, $params)) {
                    print $conn->ErrorMsg();
                }
                if ($rs2->fields[0]) {
                    if ($net_list = Net::get_list($conn, "WHERE name = '" . $rs->fields["id"] . "'")) {
                        if (Session::netAllowed($conn, $net_list[0]->get_name())) {
                            $list[] = new Control_panel_net($rs->fields["id"], $rs->fields["time_range"], $rs->fields["max_c"], $rs->fields["max_a"], $rs->fields["max_c_date"], $rs->fields["max_a_date"]);
                        }
                    }
                }
                $rs->MoveNext();
            }
        }
        return $list;
    }
}
?>
