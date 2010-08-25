<?
/*****************************************************************************
*
*    License:
*
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
require_once ('classes/Session.inc');
Session::logcheck("MenuEvents", "ControlPanelSEM");
require_once ('ossim_db.inc');
require_once ('classes/Util.inc');
require_once ('classes/Security.inc');
require_once ('../graphs/charts.php');
require_once ('forensics_stats.inc');

$gt = "month";
$cat = "Sep, 2009";

//if(!preg_match("/all|month|year|day/",$cat))
//  $gt="all";
switch ($gt) {
    case "year":
        $t_year = $cat;
        break;

    case "month":
        $tmp = explode(",", $cat);
        $t_year = str_replace(" ", "", $tmp[1]);
        $t_month = str_replace(" ", "", $tmp[0]);
        break;

    case "day":
        $tmp = explode(",", $cat);
        $t_year = str_replace(" ", "", $tmp[1]);
        $tmp = explode(" ", $tmp[0]);
        $t_month = str_replace(" ", "", $tmp[0]);
        $t_day = str_replace(" ", "", $tmp[1]);
        break;
}
$t_month = date('m', strtotime("01 " . $t_month . " 2000"));
//echo "year: $t_year, month: $t_month, day: $t_day";
//Target allYears by default
if ($gt == "") $gt = "allYears";
$chart['link_data'] = array(
    'url' => "javascript:parent.graph_by_date( _col_, _row_, _value_, _category_, _series_, '" . $t_year . "','" . $t_month . "')",
    'target' => "javascript"
);
$allYears = array();
if ($gt == "all") $allYears = get_all_csv();
if ($gt == "year") $years = get_year_csv($t_year);
else $years = get_year_csv(date("Y"));
if ($gt == "month") $months = get_month_csv($t_year, $t_month);
else $months = get_month_csv(date("Y") , date("m"));
if ($gt == "day") $days = get_day_csv($t_year, $t_month, $t_day);
$general = array();
$generalV = array();
$i = 0;
$j = 0;

$general[$j][$i++] = "NULL";
if ($gt == "all" || $gt != "month" && $gt != "year" && $gt != "day") foreach($allYears as $k => $v) $general[$j][$i++] = $k;
if ($gt == "year") foreach($years as $k => $v) $general[$j][$i++] = get_date_str($k + 1);
if ($gt == "month") foreach($months as $k => $v) $general[$j][$i++] = get_date_str($t_month + 1, $k + 1, "days");
if ($gt == "day") foreach($days as $k => $v) $general[$j][$i++] = get_date_str("", $k, "hours");
for ($a = 1; $a < 5; $a++) {
    $i = 0;
    switch ($a) {
        case 1:
            //$general[$a][$i++]="Year stats";
            $general[$a][$i++] = "";
            break;

        case 2:
            //$general[$a][$i++]="Month stats";
            $general[$a][$i++] = "";
            break;

        case 3:
            //$general[$a][$i++]="Day stats";
            $general[$a][$i++] = "";
            break;

        case 4:
            //$general[$a][$i++]="Hour stats";
            $general[$a][$i++] = "";
            break;
    }
    if ($gt == "all" || $gt != "month" && $gt != "year" && $gt != "day") foreach($allYears as $k => $v) if ($a == 1) $general[$a][$i++] = $v; //number_format($v,0,',','.');
    else $general[$a][$i++] = "";
    if ($gt == "year") foreach($years as $k => $v) if ($a == 2) $general[$a][$i++] = $v; //number_format($v,0,',','.');
    else $general[$a][$i++] = "";
    if ($gt == "month") if ($a == 3) foreach($months as $k => $v) $general[$a][$i++] = $v; //number_format($v,0,',','.');
    else $general[$a][$i++] = "";
    if ($gt == "day") if ($a == 4) foreach($days as $k => $v) $general[$a][$i++] = $v; //number_format($v,0,',','.');
    else $general[$a][$i++] = "";
}
$generalV = $general;
foreach ($generalV as $k=>$v) {
	foreach ($v as $k1=>$v1) {
		if ($v1>0) { $generalV[$k][$k1] = Util::number_format_locale($v1,0);}
	}
}

$sem_plot = array();
$x = array();
for ($i=1; $i<count($general[0]); $i++) {
	$sem_plot[$general[0][$i]] = $general[3][$i];
	$x[$general[0][$i]] = $i-1;
}
$yy = $sem_plot;
?>
