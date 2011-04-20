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
* - GetSensorName()
* Classes list:
*/
session_start();

require_once ('ossim_db.inc');
require_once ('classes/Alarm.inc');
require_once ('classes/Util.inc');
require_once ('classes/Host.inc');
require_once ('classes/Net.inc');
require_once ('classes/CIDR.inc');
require_once ('classes/Event_viewer.inc');
require_once ('classes/Security.inc');
require_once ('charts.php');

Session::logcheck("MenuControlPanel", "ControlPanelExecutive");

$db = new ossim_db();
$conn = $db->snort_connect();
$conn_ossim = $db->connect();

// plugins to resolv
$plugins = array();
$query1 = "SELECT id,name from plugin";
if (!$rs = & $conn_ossim->Execute($query1)) {
    print $conn_ossim->ErrorMsg();
    exit();
}
while (!$rs->EOF) {
    $plugins[$rs->fields["id"]] = preg_replace("/ossec-.*/", "ossec", $rs->fields["name"]);
    $rs->MoveNext();
}

$data = array();
$data[] = "";
$host = $_SESSION['host_report'];

// User sensor filtering
$sensor_where = "";
if (Session::allowedSensors() != "") {
	$user_sensors = explode(",",Session::allowedSensors());
	$snortsensors = Event_viewer::GetSensorSids($conn);
	$sensor_str = "";
	foreach ($user_sensors as $user_sensor)
		if (count($snortsensors[$user_sensor]) > 0) $sensor_str .= ($sensor_str != "") ? ",".implode(",",$snortsensors[$user_sensor]) : implode(",",$snortsensors[$user_sensor]);
	if ($sensor_str == "") $sensor_str = "0";
	$sensor_where = " AND sid in (" . $sensor_str . ")";
}

$hostname = Host::ip2hostname($conn_ossim,$host);
if ($hostname != $host) $title = $hostname."($host)";
else $title = $host;

$_SESSION['host_report'] = $host;
if (preg_match("/\/\d+/",$host)) {
	$exp = CIDR::expand_CIDR($host,"SHORT","IP");
	$src_s_range = $exp[0];
	$src_e_range = end($exp);
	$ip_where = "ip_src>=INET_ATON('$src_s_range') AND ip_src<=INET_ATON('$src_e_range') and";
} elseif($host=='any') {
	$ip_where = "";
} else {
	$ip_where = "ip_src=INET_ATON('$host') and";
}

$time_week = strftime("%Y-%m-%d", time() - (24 * 60 * 60 * 7));

$query = "select count(*) as howmany,plugin_id from acid_event force index(ip_src) where $ip_where timestamp>='$time_week'$sensor_where group by plugin_id order by howmany desc limit 10;";
if (!$rs = & $conn->Execute($query)) {
    print $conn->ErrorMsg();
    exit();
}
$values = array();
$values[] = "";
$values1 = array();
//$values1[] = "Source Host";
while (!$rs->EOF) {
	//$data_keys[$plugins[$rs->fields["plugin_id"]]]++;
	$values1[$plugins[$rs->fields["plugin_id"]]] += $rs->fields["howmany"];
    $rs->MoveNext();
}
$query = "select count(*) as howmany,plugin_id from acid_event force index(ip_dst) where ip_dst=INET_ATON('$host') and timestamp>='$time_week'$sensor_where group by plugin_id order by howmany desc limit 10;";
if (!$rs = & $conn->Execute($query)) {
    print $conn->ErrorMsg();
    exit();
}
//$values2 = array();
//$values2[] = "Destination Host";
while (!$rs->EOF) {
	//$data_keys[$plugins[$rs->fields["plugin_id"]]]++;
	//$values2[] = $rs->fields["howmany"];
	$values1[$plugins[$rs->fields["plugin_id"]]] += $rs->fields["howmany"];
    $rs->MoveNext();
}

foreach ($values1 as $key=>$val) { 
	$values[] = $val;
	$data[] = $key;
}

/*
$header = $events = array();
$header[] = ""; // first row blank
foreach($data as $plugin => $values) {
    ksort($values);
    $arr = array();
    $arr[] = $plugin; // first row series name
    foreach($values as $plugin => $val) {
        if (!in_array($plugin, $header)) $header[] = $plugin;
        $arr[] = $val;
    }
    $events[] = $arr;
}
$results_array = array_merge(array(
    $header
) , $events);*/
//$results_array = array_merge(array($data),array($values1),array($values2));
$results_array = array_merge(array($data),array($values));
//print_r($results_array);exit;
$chart['chart_data'] = $results_array;
// PHP/SWF Chart License - Licensed to ossim.com. For distribution with ossim only. No other redistribution / usage allowed.
// For more information please check http://www.maani.us/charts/index.php?menu=License_bulk
$chart['license'] = "J1XF-CMEW9L.HSK5T4Q79KLYCK07EK";
$chart['axis_category'] = array(
    'size' => 11,
    'color' => "4e627c",
    'alpha' => 75,
    'orientation' => "circular"
);
$chart['axis_ticks'] = array(
    'value_ticks' => false,
    'category_ticks' => false
);
$chart['axis_value'] = array(
    'alpha' => 30
);
$chart['chart_border'] = array(
    'bottom_thickness' => 0,
    'left_thickness' => 0
);
$chart['chart_grid_h'] = array(
    'alpha' => 20,
    'color' => "000000",
    'thickness' => 1,
    'type' => "dashed"
);
$chart['chart_grid_v'] = array(
    'alpha' => 5,
    'color' => "000000",
    'thickness' => 20,
    'type' => "solid"
);
$chart['chart_pref'] = array(
    'point_shape' => "circle",
    'point_size' => 8,
    'fill_shape' => true,
    'grid' => "circular"
);
$chart['chart_rect'] = array(
    'x' => 0,
    'y' => 20,
    'width' => 270,
    'height' => 220,
    'positive_color' => "008888",
    'positive_alpha' => 25
);
/*
$chart['chart_transition'] = array(
    'type' => "zoom",
    'delay' => .5,
    'duration' => .5,
    'order' => "series"
);
*/
$chart['chart_type'] = "polar";
/*
$chart['draw'] = array(
    array(
        'type' => "text",
        'transition' => "slide_right",
        'delay' => 0,
        'duration' => 3,
        'color' => "000000",
        'width' => 500,
        'alpha' => 8,
        'size' => 40,
        'x' => 0,
        'y' => 0,
        'text' => "plugins"
    )
);
*/
$chart['legend_label'] = array(
    'layout' => "vertical",
    'bullet' => "circle",
    'size' => 9,
    'color' => "4e627c",
    'alpha' => 75
);
$chart['legend_rect'] = array(
    'x' => -100,
    'y' => 0,
    'width' => 20,
    'height' => 40,
    'margin' => 0,
    'fill_alpha' => 0
);
$chart['series_color'] = array(
    "ff4400",
    "4e627c"
);
/*
$chart['link_data'] = array(
    'url' => "handle.php?target_url=events_sensor&target_var=category",
    'target' => "main"
);*/
SendChartData($chart);
?>
