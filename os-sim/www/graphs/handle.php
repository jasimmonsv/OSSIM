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
* Classes list:
*/
require_once ('ossim_db.inc');
require_once ('classes/Alarm.inc');
require_once ('classes/Util.inc');
require_once ('classes/Security.inc');
$target_url = $_REQUEST["target_url"];
$target_var = $_REQUEST["target_var"];
ossim_valid($target_url, OSS_ALPHA, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("target_url"));
ossim_valid($target_var, OSS_ALPHA, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("target_var"));
if (ossim_error()) {
    die(ossim_error());
}
$url = "";
$today[0] = "CURDATE()";
$today[1] = "NOW()";
$yesterday[0] = "DATE_ADD(CURDATE(), INTERVAL -1 DAY)";
$yesterday[1] = "CURDATE()";
$days_ago_2[0] = "DATE_ADD(CURDATE(), INTERVAL -2 DAY)";
$days_ago_2[1] = "DATE_ADD(CURDATE(), INTERVAL -1 DAY)";
$week[0] = "DATE_ADD(CURDATE(), INTERVAL -6 DAY)";
$week[1] = "NOW()";
$weeks_2[0] = "DATE_ADD(CURDATE(), INTERVAL -13 DAY)";
$weeks_2[1] = "NOW()";
$db = new ossim_db();
$conn = $db->connect();
switch ($target_url) {
        //case "top_nets":
        //$url="/ossim/report/index.php?host=$category";
        //break;
        
    case "top_hosts":
        $url = "/ossim/report/host_report.php?host=" . $_GET['category'];
        break;

    case "events_sensor":
        $plugin = $_REQUEST["category"] ? $_REQUEST["category"] : null;
        $sensor = $_REQUEST["series"] ? $_REQUEST["series"] : null;
        if (strlen($plugin) < 2) {
            // Assume Sensor
            $sql = "select sid from snort.sensor, ossim.sensor where ossim.sensor.ip = snort.sensor.hostname and ossim.sensor.name = ?";
            $params = array(
                $sensor
            );
            if (!$rs = & $conn->Execute($sql, $params)) {
                print $conn->ErrorMsg();
                exit();
            }
            if (!$rs->EOF) {
                $sensor_sid = $rs->fields["sid"];
                $url = "/ossim/forensics/base_qry_main.php?new=1&sensor=$sensor_sid&num_result_rows=-1&submit=Query+DB&hmenu=Forensics&smenu=Forensics";
            }
        } elseif (strlen($sensor) < 2) {
            // Assume Plugin
            $plugin_param = "";
        	$temp_sql = "SELECT distinct id,name FROM plugin WHERE name='$plugin'";
            if (!$rs = & $conn->Execute($temp_sql, $params)) {
                print $conn->ErrorMsg();
                exit();
            }
            if (!$rs->EOF) {
            	$plugin_param = "&plugin=".$rs->fields["id"];
            }
            //$url = "/ossim/index.php?option=2&soption=0&url=%2Fossim/forensics%2F%2Fbase_qry_main.php%3F%26num_result_rows%3D-1%26submit%3DQuery DB%26current_view%3D-1%26sort_order%3Dtime_d";
            $url = "/ossim/forensics/base_qry_main.php?num_result_rows=-1&submit&Query DB&current_view=-1&sort_order=time_d$plugin_param";
        } else {
            // Asume both
            $sql = "select sid from snort.sensor, ossim.sensor where ossim.sensor.ip = substring_index(snort.sensor.hostname,\"-\",1) and ossim.sensor.name = ? and snort.sensor.hostname like ?";
            $params = array(
                $sensor,
                "%" . $plugin . "%"
            );
            if (!$rs = & $conn->Execute($sql, $params)) {
                print $conn->ErrorMsg();
                exit();
            }
            if (!$rs->EOF) {
                $sensor_sid = $rs->fields["sid"];
                $url = "/ossim/forensics/base_qry_main.php?new=1&sensor=$sensor_sid&num_result_rows=-1&submit=Query+DB&hmenu=Forensics&smenu=Forensics";
            } else {
                $sql = "select sid from snort.sensor, ossim.sensor where ossim.sensor.ip = snort.sensor.hostname and ossim.sensor.name = ?";
                $params = array(
                    $sensor
                );
                if (!$rs = & $conn->Execute($sql, $params)) {
                    print $conn->ErrorMsg();
                    exit();
                }
                if (!$rs->EOF) {
                    $sensor_sid = $rs->fields["sid"];
                    $url = "/ossim/forensics/base_qry_main.php?new=1&sensor=$sensor_sid&num_result_rows=-1&submit=Query+DB&hmenu=Forensics&smenu=Forensics";
                }
            }
        }
        if (strlen($url) < 5) {
            $url = "/ossim/forensics/base_qry_main.php?num_result_rows=-1&submit=Query_DB?current_view=-1&sort_order=time_d&hmenu=Forensics&smenu=Forensics";
        }
        break;

    case "host_report":
        $url = "/ossim/report/host_report.php?host=" . $_REQUEST[$target_var];
        break;

    case "incident_status":
        $url = "/ossim/index.php?option=1&soption=0&url=incidents%2Findex.php%3F%26status%3D" . $_REQUEST[$target_var];
        break;

    case "incident_ref":
        $url = "/ossim/incidents/index.php?status=Closed&ref=" . $_REQUEST[$target_var];
        break;

    case "inventory":
        $url = "/ossim/index.php?option=4&soption=0&url=".urlencode("policy/ocs_index.php?hmenu=Assets&smenu=Inventory");
        break;

    case "alarms_events":
        if ($_REQUEST[$target_var] == "Alarms") {
            $url = "/ossim/index.php?option=0&soption=2&url=/ossim/control_panel/alarm_console.php";
        }
        if ($_REQUEST[$target_var] == "Events") {
            //$url = "/ossim/index.php?option=2&soption=0&url=%2Fossim/forensics%2F%2Fbase_qry_main.php%3F%26num_result_rows%3D-1%26submit%3DQuery%2BDB%26current_view%3D-1%26sort_order%3Dtime_d";
            $time_range = $_REQUEST["category"];
            /*
			switch ($time_range) {
                case "Today":
                    $query = "SELECT " . $today[0] . " as datetime, " . $today[1] . " as datetime2";
                    break;

                case "-1Day":
                    $query = "SELECT " . $yesterday[0] . " as datetime, " . $yesterday[1] . " as datetime2";
                    break;

                case "-2Days":
                    $query = "SELECT " . $days_ago_2[0] . " as datetime, " . $days_ago_2[1] . " as datetime2";
                    break;

                case "Week":
                    $query = "SELECT " . $week[0] . " as datetime, " . $week[1] . " as datetime2";
                    break;

                case "2Weeks":
                    $query = "SELECT " . $weeks_2[0] . " as datetime, " . $weeks_2[1] . " as datetime2";
                    break;

                default:
                    $query = "SELECT " . $today[0] . " as datetime, " . $today[1] . " as datetime2";
                    break;
            }
            if (!$rs = & $conn->Execute($query)) {
                die($conn->ErrorMsg());
            } else {
                if (!$rs->EOF) {
                    $start_dates = getdate(strtotime($rs->fields["datetime"]));
                    $end_dates = getdate(strtotime($rs->fields["datetime2"]));
                }
            }
			*/
			switch ($time_range) {
                case "Today":
                    $date = explode("-",strftime("%Y-%m-%d", time()));
					$timerange = "today";
                    break;
				case "-1Day":
					$date = explode("-",strftime("%Y-%m-%d", time() - (24 * 60 * 60)));
					$timerange = "day";
					break;
				case "-2Days":
					$date = explode("-",strftime("%Y-%m-%d", time() - (24 * 60 * 60 * 2)));
					$timerange = "day";
					break;
				case "Week":
					$date = explode("-",strftime("%Y-%m-%d", time() - (24 * 60 * 60 * 7)));
					$timerange = "week";
					break;
				case "2Weeks":
					$date = explode("-",strftime("%Y-%m-%d", time() - (24 * 60 * 60 * 14)));
					$timerange = "weeks";
					break;
			}
            //$url = "/ossim/forensics/base_qry_main.php?new=1& time[0][0]= (&time[0][1]=%3E=&time[0][2]= " . $start_dates[mon] . " &time[0][3]= " . $start_dates[mday] . " &time[0][4]= " . $start_dates[year] . " &time[0][5]= " . $start_dates[hours] . " &time[0][6]= " . $start_dates[minutes] . " &time[0][7]= " . $start_dates[seconds] . "&time[0][8]=)&time[0][9]= AND&time[1][0]= (&time[1][1]=%3C=&time[1][2]= " . $end_dates[mon] . " &time[1][3]= " . $end_dates[mday] . " &time[1][4]= " . $end_dates[year] . " &time[1][5]= " . $end_dates[hours] . " &time[1][6]= " . $end_dates[minutes] . " &time[1][7]= " . $end_dates[seconds] . "&time[1][8]=) &time[1][9]=&ip_addr[0][1]=&ip_addr[0][2]==&ip_addr[0][3]=&sort_order=time_d&submit=Query+DB&num_result_rows=-1&time_cnt=2&ip_addr_cnt=1";
			$params = "?time_range=$timerange&new=1&time%5B0%5D%5B0%5D=+&time%5B0%5D%5B1%5D=%3E%3D&time%5B0%5D%5B2%5D=".$date[1]."&time%5B0%5D%5B3%5D=".$date[2]."&time%5B0%5D%5B4%5D=".$date[0]."&time%5B0%5D%5B5%5D=12&time%5B0%5D%5B6%5D=&time%5B0%5D%5B7%5D=&time%5B0%5D%5B8%5D=+&time%5B0%5D%5B9%5D=+&submit=Query+DB&num_result_rows=-1&time_cnt=1&sort_order=time_d";
			$url = "/ossim/forensics/base_qry_main.php".$params;
        }
        break;

    default:
        $url = "http://www.ossim.net";
        break;
}
$url = preg_replace ("/\/+/","/",$url);
//error_log($sql."\n".$url."\n",3,"/tmp/kk");
header("Location: $url");
exit;
?>
