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
* - beautify()
* - translate_alarm()
* - timestamp2date()
* - timestamp2RFC1459()
* - date_diff()
* - get_acid_date_link()
* - get_acid_pair_link()
* - get_acid_events_link()
* - graph_image_link()
* - get_caption()
* - echocolor()
* - acidIP2long()
* - acidLong2IP()
* - get_acid_info()
* - string2js()
* - draw_js_calendar()
* - cidr_conv()
* - pager_get_data()
* - pager_draw()
* - ossim_http_request()
* Classes list:
* - Util
*/
require_once ('classes/Host.inc');
class Util {
    /* Expects a string with underscore in it and substitutes with spaces.
    Used for DB Integrity */
    function beautify($name) {
        return str_replace("_", " ", $name);
    }
    // Translates the string into human readable form
    function translate_alarm($conn, $name, $alarm) {
        $translations = array(
            "/SRC_IP/" => "get_src_ip()",
            "/DST_IP/" => "get_dst_ip()",
            "/SRC_PORT/" => "get_src_port()",
            "/DST_PORT/" => "get_dst_port()",
            "/PROTOCOL/" => "get_protocol()",
            "/PLUGIN_ID/" => "get_plugin_id()",
            "/PLUGIN_SID/" => "get_plugin_sid()"
        );
        foreach($translations as $k => $v) {
            $pattern = '$name = preg_replace("' . $k . '", %s, $name);';
            switch ($k) {
                case "/PROTOCOL/":
                    $replacement = 'getprotobynumber($alarm->' . $v . ')';
                    break;

                case "/SRC_IP/":
                case "/DST_IP/":
                    $replacement = 'Host::ip2hostname($conn, $alarm->' . $v . ')';
                    break;

                default:
                    $replacement = '$alarm->' . $v;
            }
            $str = sprintf($pattern, $replacement);
            eval($str);
        }
        return $name;
    }
    function timestamp2date($timestamp) {
        if (!$timestamp) return "";
        $num = "[0-9]";
        if (preg_match("/^$num{14}$/", $timestamp)) {
            /*
            MySQL < 4.1:
            TIMESTAMP is returned as a string in 'YYYYMMDDHHMMSS'
            */
            return (substr($timestamp, 0, 4) . '-' . // YYYY
            substr($timestamp, 4, 2) . '-' . // MM
            substr($timestamp, 6, 2) . ' ' . // DD
            substr($timestamp, 8, 2) . ':' . // HH
            substr($timestamp, 10, 2) . ':' . // MM
            substr($timestamp, 12, 2)); // SS
            
        } elseif (preg_match("/$num{4}\-$num{2}\-$num{2} $num{2}\:$num{2}\:$num{2}/", $timestamp)) {
            /*
            MySQL >= 4.1:
            TIMESTAMP is returned as a string in 'YYYY-MM-DD HH:MM:SS'
            */
            return $timestamp;
        } else {
            return $timestamp;
        }
    }
    /* feed xml timestamp format */
    function timestamp2RFC1459($timestamp) {
        if (!$timestamp) return "";
        $num = "[0-9]";
        if (preg_match("/^$num{14}$/", $timestamp)) return (substr($timestamp, 0, 4) . '-' . // YYYY
        substr($timestamp, 4, 2) . '-' . // MM
        substr($timestamp, 6, 2) . 'T' . // DD
        substr($timestamp, 8, 2) . ':' . // HH
        substr($timestamp, 10, 2) . ':' . // MM
        substr($timestamp, 12, 2) . 'Z');
        elseif (preg_match("/$num{4}\-$num{2}\-$num{2} $num{2}\:$num{2}\:$num{2}/", $timestamp)) return $timestamp;
        else return $timestamp;
    }
    /*
    * Format the difference of two dates into human readable
    *
    * @param $dt1 A time string or unix timestamp
    * @param $dt2 A time string or unix timestamp
    * @param $format What to include, accepts:
    *               - "y" show years
    *               - "M" show months
    *               - "w" weeks
    *               - "d" days
    *               - "h" hours, "m" minutes, "s" seconds
    *               ie: "dhm" will return the diff only in
    *                   days, hours and minutes as: 4 Days 18:30
    *                   if days is 0, it won't be shown
    */
    function date_diff($dt1, $dt2, $format = 'yMdhm') {
        if (!is_numeric($dt1)) {
            $date1 = (strtotime($dt1) > 0) ? strtotime($dt1) : $dt1;
        } else {
            $date1 = $dt1;
        }
        if (!is_numeric($dt2)) {
            $date2 = (strtotime($dt2) > 0) ? strtotime($dt2) : $dt2;
        } else {
            $date2 = $dt2;
        }
        $diff = $date1 - $date2;
        $min = 60;
        $hour = 60 * $min;
        $day = 24 * $hour;
        $week = 7 * $day;
        $month = 30 * $day;
        $year = 365 * $day;
        $y = $mo = $w = $d = $h = $m = $s = null;
        if ($diff > $year && (strpos($format, 'y') !== false)) {
            $y = intval($diff / $year);
            $diff = $diff - ($y * $year);
        }
        if ($diff > $month && (strpos($format, 'M') !== false)) {
            $mo = intval($diff / $month);
            $diff = $diff - ($mo * $month);
        }
        if ($diff > $week && (strpos($format, 'w') !== false)) {
            $w = intval($diff / $week);
            $diff = $diff - ($w * $week);
        }
        if ($diff > $day && (strpos($format, 'd') !== false)) {
            $d = intval($diff / $day);
            $diff = $diff - ($d * $day);
        }
        if (strpos($format, 'h') !== false) {
            $h = ($diff > $hour) ? intval($diff / $hour) : 0;
            $diff = $diff - ($h * $hour);
        }
        if (strpos($format, 'm') !== false) {
            $m = ($diff > $min) ? intval($diff / $min) : 0;
            $diff = $diff - ($m * $min);
        }
        if (strpos($format, 's') !== false) {
            $s = $diff;
        }
        $dt = $tm = $diff_arr = array();
        if ($y) {
            $str = ($y > 1) ? _("Years") : _("Year");
            $dt[] = "$y $str";
        }
        if ($mo) {
            $str = ($mo > 1) ? _("Months") : _("Month");
            $dt[] = "$mo $str";
        }
        if ($w) {
            $str = ($w > 1) ? _("Weeks") : _("Week");
            $dt[] = "$w $str";
        }
        if ($d) {
            $str = ($d > 1) ? _("Days") : _("Day");
            $dt[] = "$d $str";
        }
        if (!is_null($h)) $tm[] = sprintf('%02d', $h);
        if (!is_null($m)) $tm[] = sprintf('%02d', $m);
        if (!is_null($s)) $tm[] = sprintf('%02d', $s);
        if (count($dt)) $diff_arr[] = implode(", ", $dt);
        if (count($tm)) $diff_arr[] = implode(":", $tm);
        return implode(" ", $diff_arr);
    }
    /*
    *  PRE: $date must be in format YYYY-MM-DD HH:MM
    *       $target must be ip_src or ip_dst
    */
    function get_acid_date_link($date, $ip = "", $target = "") {
        require_once ('ossim_conf.inc');
        $conf = $GLOBALS["CONF"];
        $acid_link = $conf->get_conf("acid_link");
        $acid_prefix = $conf->get_conf("event_viewer");
        $pattern = "/(\d+)-(\d+)-(\d+) (\d+):(\d+)/";
        /*
        * regs[1] => year
        * regs[2] => month
        * regs[3] => day
        * regs[4] => hour
        * regs[5] => minute
        */
        preg_match($pattern, $date, $regs);
        /* 10 minutes before */
        if ($regs[5] >= 10) { // 3:45 -> 3:35
            $regs[5]-= 10;
        } else {
            $regs[5] = 60 - (10 - $regs[5]);
            if ($regs[4] > 0) { // 3:06 -> 2:56
                $regs[4]-= 1;
            } else { // 0:07 -> 23:57
                $regs[4] = 23;
            }
        }
        $link = "$acid_link" . "/" . $acid_prefix . "_qry_main.php?clear_allcriteria=1&time_range=today&time_cnt=1&time[0][1]=" . urlencode(">=") . "&time[0][2]=" . $regs[2] . "&time[0][3]=" . $regs[3] . "&time[0][4]=" . $regs[1] . "&time[0][5]=" . $regs[4] . "&time[0][6]=" . $regs[5];
        if ($target) {
            $link.= "&ip_addr[0][1]=$target&ip_addr[0][2]==&ip_addr[0][3]=$ip&";
        }
        $link.= "&sort_order=time_a&" . "submit=Query+DB&num_result_rows=-1&ip_addr_cnt=1";
        return str_replace("//", "/", $link);
    }
    /*
    *  PRE: $date must be in format YYYY-MM-DD HH:MM
    *
    */
    function get_acid_single_event_link($snort_sid,$snort_cid) {
        require_once ('ossim_conf.inc');
        $conf = $GLOBALS["CONF"];
        $acid_link = $conf->get_conf("acid_link");
        $acid_prefix = $conf->get_conf("event_viewer");
        $link = "$acid_link" . "/" . $acid_prefix . "_qry_alert.php?submit=%230-%28";
        $link.= "$snort_sid-$snort_cid%29&clear_allcriteria=1";
        return str_replace("//", "/", $link);
    }
    function get_acid_pair_link($date, $ip = "", $ip2 = "") {
        require_once ('ossim_conf.inc');
        $conf = $GLOBALS["CONF"];
        $acid_link = $conf->get_conf("acid_link");
        $acid_prefix = $conf->get_conf("event_viewer");
        $pattern = "/(\d+)-(\d+)-(\d+) (\d+):(\d+)/";
        /*
        * regs[1] => year
        * regs[2] => month
        * regs[3] => day
        * regs[4] => hour
        * regs[5] => minute
        */
        preg_match($pattern, $date, $regs);
        /* 10 minutes before */
        if ($regs[5] >= 10) { // 3:45 -> 3:35
            $regs[5]-= 10;
        } else {
            $regs[5] = 60 - (10 - $regs[5]);
            if ($regs[4] > 0) { // 3:06 -> 2:56
                $regs[4]-= 1;
            } else { // 0:07 -> 23:57
                $regs[4] = 23;
            }
        }
        $link = "$acid_link" . "/" . $acid_prefix . "_qry_main.php?clear_allcriteria=1&time_range=today&time_cnt=1&time[0][1]=" . urlencode(">=") . "&time[0][2]=" . $regs[2] . "&time[0][3]=" . $regs[3] . "&time[0][4]=" . $regs[1] . "&time[0][5]=" . $regs[4] . "&time[0][6]=" . $regs[5];
        $link.= "&ip_addr[0][1]=ip_src&ip_addr[0][2]==&ip_addr[0][3]=$ip&ip_addr[0][9]=AND";
        $link.= "&ip_addr[1][1]=ip_dst&ip_addr[1][2]==&ip_addr[1][3]=$ip2";
        $link.= "&sort_order=time_a&" . "submit=Query+DB&num_result_rows=-1&ip_addr_cnt=2";
        return str_replace("//", "/", $link);
    }
    /*
    *  PRE:
    *     $since and $last must be in format YYYY-MM-DD HH:MM:SS
    *     $desc must be "time_d" or "time_a"
    *     $target must be "ip_src", "ip_dst" or "ip_both"
    */
    function get_acid_events_link($since, $last, $order = "time_d", $ip = "", $target = "ip_both") {
        require_once ('ossim_conf.inc');
        $conf = $GLOBALS["CONF"];
        $acid_link = $conf->get_conf("acid_link");
        $acid_prefix = $conf->get_conf("event_viewer");
        $pattern = "/(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)/";
        /*
        * regs[1] => year
        * regs[2] => month
        * regs[3] => day
        * regs[4] => hour
        * regs[5] => minute
        * regs[6] => secs
        */
        preg_match($pattern, $since, $regs_since);
        preg_match($pattern, $last, $regs_last);
        $link = "$acid_link" . "/" . $acid_prefix . "_qry_main.php?clear_allcriteria=1&time_range=today&time_cnt=2" . "&time[0][0]=&time[0][1]=" . urlencode(">=") . "&time[0][2]=" . $regs_since[2] . "&time[0][3]=" . $regs_since[3] . "&time[0][4]=" . $regs_since[1] . "&time[0][5]=" . $regs_since[4] . "&time[0][6]=" . $regs_since[5] . "&time[0][7]=" . $regs_since[6] . "&time[0][9]=AND" . "&time[1][0]=" . "&time[1][1]=" . urlencode("<=") . "&time[1][2]=" . $regs_last[2] . "&time[1][3]=" . $regs_last[3] . "&time[1][4]=" . $regs_last[1] . "&time[1][5]=" . $regs_last[4] . "&time[1][6]=" . $regs_last[5] . "&time[1][7]=" . $regs_last[6] . "&time[1][8]=&time[1][9]=";
        if ($ip) $link.= "&ip_addr[0][1]=$target&ip_addr[0][2]==&ip_addr[0][3]=$ip";
        $link.= "&sort_order=$order&" . "submit=Query+DB&num_result_rows=-1&ip_addr_cnt=1";
        return str_replace("//", "/", $link);
    }
    function graph_image_link($ip, $type, $what, $start, $end, $zoom, $range) {
        $cgi_link = "show_image.php";
        return "$cgi_link?range=$range&ip=$ip&what=$what&start=$start&end=$end&type=$type&zoom=$zoom";
    }
    function get_caption($ip, $type, $what, $range) {
        $ip = escapeshellcmd($ip);
        $type = escapeshellcmd($type);
        $what = escapeshellcmd($what);
        $range = escapeshellcmd($range);
        if ($range == "day") {
            $range = "1D";
        }
        if ($range == "month") {
            $range = "1M";
        }
        if ($range == "year") {
            $range = "1Y";
        }
        $result = "";
        $data_dir = $conf->get_conf("data_dir");
        $result = shell_exec("$data_dir/scripts/get_date.pl $ip $range $what $type");
        return date("l dS of F Y h:i:s A", $result);
    }
    function echocolor($val, $max, $image) {
        if ($val / $max > 5) {
            echo "<td bgcolor=\"red\"><a href=\"" . $image . "\"><font color=\"white\"><b>" . $val . "</b></font></a></td>";
        } elseif ($val / $max > 3) {
            echo "<td bgcolor=\"orange\"><a href=\"" . $image . "\"><font color=\"black\"><b>" . $val . "</b></font></a></td>";
        } elseif ($val / $max > 1) {
            echo "<td bgcolor=\"green\"><a href=\"" . $image . "\"><font color=\"white\"><b>" . $val . "</b></font></a></td>";
        } else {
            echo "<td><a href=\"" . $image . "\"><font color=\"black\"><b>" . $val . "</b></font></a></td>";
        }
    }
    // ÂNot been use anymore?
    function acidIP2long($IP_str) {
        $tmp_long = ip2long($IP_str);
        if ($tmp_long < 0) $tmp_long = 4294967296 - abs($tmp_long);
        return $tmp_long;
    }
    // Not been used anymore??
    function acidLong2IP($long_IP) {
        $tmp_IP = $long_IP;
        if ($long_IP > 2147483647) {
            $tmp_IP = 4294967296 - $tmp_IP;
            $tmp_IP = $tmp_IP * (-1);
        }
        $tmp_IP = long2ip($tmp_IP);
        return $tmp_IP;
    }
    function get_acid_info($ip, $acid_link, $acid_prefix) {
        return str_replace("//", "/", "$acid_link/" . $acid_prefix . "_qry_main.php?clear_allcriteria=1&new=2&num_result_rows=-1&submit=Query+DB&current_view=-1&ip_addr_cnt=2&ip_addr%5B0%5D%5B0%5D=+&ip_addr%5B0%5D%5B1%5D=ip_src&ip_addr%5B0%5D%5B2%5D=%3D&ip_addr%5B0%5D%5B3%5D=$ip&ip_addr%5B0%5D%5B8%5D=+&ip_addr%5B0%5D%5B9%5D=OR&ip_addr%5B1%5D%5B0%5D=+&ip_addr%5B1%5D%5B1%5D=ip_dst&ip_addr%5B1%5D%5B2%5D=%3D&ip_addr%5B1%5D%5B3%5D=$ip&ip_addr%5B1%5D%5B8%5D=+&ip_addr%5B1%5D%5B9%5D=+&sort_order=time_d");
    }
    function string2js($str) {
        $str = str_replace("'", "\'", $str);
        $str = str_replace('"', '', $str); // XXX fix that
        // escape break lines
        $str = preg_replace("/(\r)|(\r?\n)/", '\n', $str);
        return $str;
    }
    /*
    * $options = array(
    *    'input_name'       => 'document.forms[0].foo' )
    */
    function draw_js_calendar($options, $debug = false, $onclick = '') {
        static $first_time = true;
        static $js_calendar_num = 0;
        $html = '';
        if ($first_time) {
            $html.= '
                <SCRIPT LANGUAGE="JavaScript" SRC="../js/mattkruse/CalendarPopup.js"></SCRIPT>
                <DIV ID="testdiv1" STYLE="position:absolute;visibility:hidden;background-color:white;layer-background-color:white;"></DIV>
            ';
            $first_time = false;
        }
        $js_calendar_num++;
        $js_cal_obj_name = 'cal' . $js_calendar_num;
        $js_anchor_name = 'anchor' . $js_calendar_num;
        $html.= '
        <script>
            var ' . $js_cal_obj_name . ' = new CalendarPopup("testdiv1");
            ' . $js_cal_obj_name . '.setCssPrefix("CALENDAR");
            ' . $js_cal_obj_name . '.showNavigationDropdowns();
            ' . $js_cal_obj_name . '.setMonthNames("' . _("January") . '","' . _("February") . '","' . _("March") . '","' . _("April") . '","' . _("May") . '","' . _("June") . '","' . _("July") . '","' . _("August") . '","' . _("September") . '","' . _("October") . '","' . _("November") . '","' . _("December") . '");
            ' . $js_cal_obj_name . '.setMonthAbbreviations("' . _("Jan") . '","' . _("Feb") . '","' . _("Mar") . '","' . _("Apr") . '","' . _("May") . '","' . _("Jun") . '","' . _("Jul") . '","' . _("Aug") . '","' . _("Sep") . '","' . _("Oct") . '","' . _("Nov") . '","' . _("Dec") . '");
            ' . $js_cal_obj_name . '.setDayHeaders("' . _("Sun") . '","' . _("Mon") . '","' . _("Tue") . '","' . _("Wed") . '","' . _("Thu") . '","' . _("Fri") . '","' . _("Sat") . '");
            ' . $js_cal_obj_name . '.setTodayText("' . _("Today") . '");
            ' . $js_cal_obj_name . '.setYearSelectStartOffset(10);
        </script> 
        <a href="#" name=' . $js_anchor_name . ' id=' . $js_anchor_name . '
           onClick="' . (($onclick != "") ? $onclick . ";" : "") . $js_cal_obj_name . '.select(' . $options['input_name'] . ',\'' . $js_anchor_name . '\',\'yyyy-MM-dd\'); return false;">
        <img src="../pixmaps/calendar.gif" border=0 title="' . _("Date format is") . ' YY-MM-DD"></a>
        ';
        if ($debug) {
            printr($html);
        }
        echo $html;
    }
    function cidr_conv($cidr_address) {
        $last_bin = "";
        $result = array();
        $first = substr($cidr_address, 0, strpos($cidr_address, "/"));
        $netmask = substr(strstr($cidr_address, "/") , 1);
        $first_bin = str_pad(decbin(ip2long($first)) , 32, "0", STR_PAD_LEFT);
        $netmask_bin = str_pad(str_repeat("1", (integer)$netmask) , 32, "0", STR_PAD_RIGHT);
        $temp = $first_bin & $netmask_bin;
        for ($i = 0; $i < 32; $i++) {
            if ($netmask_bin[$i] == "1") $last_bin.= $first_bin[$i];
            else $last_bin.= "1";
        }
        $result[0] = long2ip(bindec($temp));
        $result[1] = long2ip(bindec($last_bin));
        return array(long2ip(bindec($temp)),long2ip(bindec($last_bin)));
    }
    /*
    * Gets all the data needed to paginate results
    * This is an associative array with the following
    * values filled in:
    *
    * array(
    *    'current' => X,    // current page you are
    *    'numrows' => X,    // total number of results
    *    'next'    => X,    // row number where next page starts
    *    'prev'    => X,    // row number where prev page starts
    *    'remain'  => X,    // number of results remaning *in next page*
    *    'numpages'=> X,    // total number of pages
    *    'from'    => X,    // the row to start fetching
    *    'to'      => X,    // the row to stop fetching
    *    'limit'   => X,    // how many results per page
    *    'maxpages'   => X, // how many pages to show (Google style)
    *    'firstpage'  => X, // the row number of the first page
    *    'lastpage'   => X, // the row number where the last page starts
    *    'pages'   => array(    // assoc with page "number => start row"
    *                1 => X,
    *                2 => X,
    *                3 => X
    *                )
    *    );
    * @param int $from    The row to start fetching
    * @param int $limit   How many results per page
    * @param int $numrows Number of results from query
    *
    * @return array associative array with data
    *
    */
    function pager_get_data($from, $limit, $numrows, $maxpages = 10) {
        if (empty($numrows) || ($numrows < 0)) {
            $null = null;
            return $null;
        }
        $from = (empty($from)) ? 0 : $from;
        if ($limit <= 0) {
            die(_("wrong \"limit\" param"));
        }
        // Total number of pages
        $pages = ceil($numrows / $limit);
        $data['numpages'] = $pages;
        // first & last page
        $data['firstpage'] = 1;
        $data['lastpage'] = $pages;
        // Build pages array
        $data['pages'] = array();
        for ($i = 1; $i <= $pages; $i++) {
            $offset = $limit * ($i - 1);
            $data['pages'][$i] = $offset;
            // $from must point to one page
            if ($from == $offset) {
                // The current page we are
                $data['current'] = $i;
            }
        }
        if (!isset($data['current'])) {
            die(_("wrong \"from\" param"));
        }
        // Limit number of pages (Google style)
        if ($maxpages) {
            $radio = floor($maxpages / 2);
            $minpage = $data['current'] - $radio;
            if ($minpage < 1) {
                $minpage = 1;
            }
            $maxpage = $data['current'] + $radio - 1;
            if ($maxpage > $data['numpages']) {
                $maxpage = $data['numpages'];
            }
            if ($data['numpages'] < $maxpages) {
                $minpage = 1;
                $maxpage = $data['numpages'];
            } else {
                // if we're at the top of the range, move minpage down...
                if ($data['lastpage'] - $minpage < $maxpages) {
                    $minpage = $data['lastpage'] - $maxpages + 1;
                }
                // if we're at the bottom of the range, move maxpage up...
                if ($maxpage < $maxpages) {
                    $maxpage = $maxpages;
                }
            }
            foreach(range($minpage, $maxpage) as $page) {
                $tmp[$page] = $data['pages'][$page];
            }
            $data['pages'] = $tmp;
            $data['maxpages'] = $maxpages;
        } else {
            $data['maxpages'] = null;
        }
        // Prev link
        $prev = $from - $limit;
        $data['prev'] = ($prev >= 0) ? $prev : null;
        // Next link
        $next = $from + $limit;
        $data['next'] = ($next < $numrows) ? $next : null;
        // Results remaining in next page & Last row to fetch
        if ($data['current'] == $pages) {
            $data['remain'] = 0;
            $data['to'] = $numrows;
        } else {
            if ($data['current'] == ($pages - 1)) {
                $data['remain'] = $numrows - ($limit * ($pages - 1));
            } else {
                $data['remain'] = $limit;
            }
            $data['to'] = $data['current'] * $limit;
        }
        $data['numrows'] = $numrows;
        $data['from'] = $from + 1;
        $data['limit'] = $limit;
        return $data;
    }
    function pager_draw($from, $limit, $numrows, $my_link = false) {
        $d = Util::pager_get_data($from, $limit, $numrows);
        $html = '
<table width="100%" align="center" style="border-width: 0px">
<tr>
    <td style="border-width: 0px">' . $d['numrows'] . ' ' . _("results found") . '</td>
    <td style="border-width: 0px">(' . sprintf(_("Page %d of %d") , $d['current'], $d['numpages']) . ')</td>
    <td style="border-width: 0px">' . sprintf(_("%d results per page") , $d['limit']) . '</td>
</tr>
<tr>';
        // previous link
        if ($d['current'] > 1) {
            $link = $my_link . "&page_from=" . $d['prev'];
            $prev = '<a href="' . $link . '">&lt; ' . _("Previous") . '</a>';
        } else {
            $prev = '&nbsp;';
        }
        $html.= "<td style=\"border-width: 0px\">$prev</td><td style=\"border-width: 0px\" nowrap>";
        // pages links
        if (count($d['pages']) > 0) {
            foreach($d['pages'] as $page => $row) {
                if ($page == $d['current']) {
                    $page = '<font color="red">' . $page . '</font> ';
                }
                $link = $my_link . "&page_from=" . $row;
                $html.= '&nbsp;<a href="' . $link . '">' . $page . '</a>';
            }
        }
        // next link
        if ($d['current'] < $d['numpages']) {
            $link = $my_link . "&page_from=" . $d['next'];
            $next = '<a href="' . $link . '">' . _("Next") . ' &gt;</a>';
        } else {
            $next = '&nbsp;';
        }
        $html.= "</td><td style=\"border-width: 0px\">$next</td></tr></table>";
        return $html;
    }
    function ossim_http_request($url, $header = array() , $method = 'GET', $data = "", $retry = 10) {
        //global $ch;
        // INITIALIZE ALL VARS
        $Rec_Data = '';
        $Temp_Output = '';
        if (!isset($ch)) $ch = curl_init($url);
        else curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/glpicookies.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/glpicookies.txt');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            curl_setopt($ch, CURLOPT_POST, 0);
        }
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 10);
        curl_setopt($ch, CURLOPT_HEADER, 0); // DO NOT RETURN HTTP HEADERS
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // RETURN THE CONTENTS OF THE CALL
        $Rec_Data = curl_exec($ch);
        return ($Rec_Data);
    }
    function number_format_locale($number,$decimals=2) {
        $locale = ( isset($_COOKIE['locale']) ? 
                        $_COOKIE['locale'] : 
                        $_SERVER['HTTP_ACCEPT_LANGUAGE']
                   );
        $languages = explode(",",$locale);
        switch($languages[0]) {
            case 'es-es':
            case 'de-de':
            case 'es-mx':
                $decimal = ',';
                $thousands = '.';
                break;
            default:
                $decimal = '.';
                $thousands = ',';
        }
        return number_format($number,$decimals,$decimal,$thousands);
    }
    function make_form($post,$action) {
		$form = "<form method=\"post\" action=\"".$action."\">";
		foreach($post as $key=>$value) {
			if (is_array($value)){
				foreach($value as $v){
					$form.= "<input type=\"hidden\" name=\"ips[]\" value=\"".$v."\">";
				}
			}
			else {
				$form.= "<input type=\"hidden\" name=\"".$key."\" value=\"".$value."\">";
			}
		}
		$form.= "<center><input type=\"submit\" value=\"Back\" class=\"btn\" style=\"font-size:12px\"></center>";
		$form.= "</form>";
		echo $form;
    }
	function print_error($message){
		echo "<div style='border: 3px dotted rgb(221, 158, 6); padding: 5px; margin-left: 50px; margin-right: 50px; text-align: center; background-color: rgb(236, 234, 171);'>".gettext($message)."</div>";
		echo "<br>";
	}
	function print_gadget($title,$color,$content) {
		return "<table height=\"100%\" style=\"background:$color;background:url(../pixmaps/fondo_hdr2.png) repeat-x\"><tr><td height=\"15\" class=\"nobborder\" style=\"text-align:center;font-weight:bold;font-size:12px\">$title</td></tr><tr><td class=\"nobborder\" valign=\"top\">".$content."</td></tr></table>";
	}
    function signaturefilter($data) {
        $out = str_replace("directive_event: ", "", $data);
        $out = str_replace("rrd_anomaly: ", "", $out);
		return $out;
	}

    // clean json cache session files
    function clean_json_cache_files($pattern="",$user="") {
    	if ($user=="") $user = $_SESSION["_user"];
        $cachedir = "/var/ossim/sessions";
        if (is_dir($cachedir) && $dh = opendir($cachedir)) {
            while (($file = readdir($dh)) !== false) {
                if(!is_dir($cachedir."/".$file) && preg_match("/^".$user."_".$pattern.".*\.json$/", $file)){
                    unlink($cachedir."/".$file);
                }
            }
        }
    }
    
    function utf8_encode2($string) {
        return (Util::is_utf8($string)) ? $string : utf8_encode($string);
    }
    function is_utf8($str) {
        $c=0; $b=0;
        $bits=0;
        $len=strlen($str);
        for($i=0; $i<$len; $i++){
            $c=ord($str[$i]);
            if ($c >= 128) {
                if(($c >= 254)) return false;
                elseif($c >= 252) $bits=6;
                elseif($c >= 248) $bits=5;
                elseif($c >= 240) $bits=4;
                elseif($c >= 224) $bits=3;
                elseif($c >= 192) $bits=2;
                else return false;
                if(($i+$bits) > $len) return false;
                while($bits > 1){
                    $i++;
                    $b=ord($str[$i]);
                    if($b < 128 || $b > 191) return false;
                    $bits--;
                }
            }
        }
        return true;
    } 
}
?>
