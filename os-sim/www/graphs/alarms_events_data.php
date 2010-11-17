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
require_once ('classes/Session.inc');
require_once 'charts.php';

Session::logcheck("MenuControlPanel", "ControlPanelExecutive");

function GetSensorSids($conn2) {
	$query = "SELECT * FROM sensor";
	if (!$rs = & $conn2->Execute($query)) {
		print $conn2->ErrorMsg();
		exit();
	}
	while (!$rs->EOF) {
		$sname = ($rs->fields['sensor']!="") ? $rs->fields['sensor'] : preg_replace("/-.*/","",preg_replace("/.*\]\s*/","",$rs->fields['hostname']));
		$ret[$sname][] = $rs->fields['sid'];
		$rs->MoveNext();
	}
	return $ret;
}

// PHP/SWF Chart License - Licensed to ossim.com. For distribution with ossim only. No other redistribution / usage allowed.
// For more information please check http://www.maani.us/charts/index.php?menu=License_bulk
$chart['license'] = "J1XF-CMEW9L.HSK5T4Q79KLYCK07EK";
//this chart places another chart in its canvas by using the draw function
//this is the source code for the first chart
$chart['axis_category'] = array(
    'size' => 10,
    'color' => "000000",
    'alpha' => 50
);
$chart['axis_ticks'] = array(
    'value_ticks' => false,
    'category_ticks' => false
);
$chart['axis_value'] = array(
    'alpha' => 0
);
$chart['chart_border'] = array(
    'bottom_thickness' => 0,
    'left_thickness' => 0
);
//$chart[ 'chart_data' ] = array ( array ( "", "JAN", "FEB", "MAR", "APR", "MAY", "JUN" ), array ( "product 1", 60,90,40,90,50,40 ), array ("product 2", 85,70,80,40,90,95 ) );
$chart['chart_grid_h'] = array(
    'alpha' => 0
);
$chart['chart_grid_v'] = array(
    'alpha' => 0
);
$chart['chart_pref'] = array(
    'rotation_x' => 15
);
$chart['chart_rect'] = array(
    'x' => 20,
    'y' => - 40,
    'width' => 350,
    'height' => 220,
    'positive_alpha' => 0
);
$chart['chart_transition'] = array(
    'type' => "zoom",
    'delay' => .1,
    'duration' => .5,
    'order' => "series"
);
$chart['chart_type'] = "3d column";
$chart['chart_value'] = array(
    'position' => "cursor",
    'size' => 10,
    'color' => "000000",
    'alpha' => 90,
    'background_color' => "444444"
);
$chart['draw'] = array(
    array(
        'type' => "image",
        'url' => "/ossim/graphs/charts.swf??timeout=120&library_path=" . urlencode("/ossim/graphs/charts_library") . "&php_source=" . urlencode("/ossim/graphs/alarms_events_data2.php?bypassexpirationupdate=1")
    )
);
$chart['legend_label'] = array(
    'layout' => "vertical",
    'bullet' => "square",
    'size' => 11,
    'color' => "202020",
    'alpha' => 85
);
$chart['legend_rect'] = array(
    'x' => 20,
    'y' => 75,
    'width' => 20,
    'height' => 20,
    'fill_alpha' => 0
);
$chart['series_color'] = array(
    "cc9944",
    "556688"
);
$chart['link_data'] = array(
    'url' => "/ossim/graphs/handle.php?target_url=alarms_events&target_var=series",
    'target' => "main"
);

$db = new ossim_db();
$conn = $db->connect();
$conn2 = $db->snort_connect();

$sensor_where = "";
$sensor_where_ossim = "";
if (Session::allowedSensors() != "") {
	$user_sensors = explode(",",Session::allowedSensors());
	$snortsensors = GetSensorSids($conn2);
	$sids = array();
	foreach ($user_sensors as $user_sensor) {
		//echo "Sids de $user_sensor ".$snortsensors[$user_sensor][0]."<br>";
		if (count($snortsensors[$user_sensor]) > 0)
			foreach ($snortsensors[$user_sensor] as $sid) if ($sid != "")
				$sids[] = $sid;
	}
	if (count($sids) > 0) {
		$sensor_where = " AND sid in (".implode(",",$sids).")";
		$sensor_where_ossim = " AND alarm.snort_sid in (".implode(",",$sids).")";
	}
	else {
		$sensor_where = " AND sid in (0)"; // Vacio
		$sensor_where_ossim = " AND alarm.snort_sid in (0)"; // Vacio
	}
}
//
$legend = array(
    "",
    "Today",
    "-1Day"
);
if($conf->get_conf("backup_day")<=5){
    $_2Ago='INTERVAL -2 DAY';
    $_2Ago_div='';
    $_2Ago_interv='DATE_ADD(CURDATE(), INTERVAL -2 DAY)';
    //
    $_Week='INTERVAL -3 DAY';
    $_Week_div='';
    $_Week_interv='DATE_ADD(CURDATE(), INTERVAL -3 DAY)';
    //
    $_2Week='INTERVAL -4 DAY';
    $_2Week_div='';
    $_2Week_interv='DATE_ADD(CURDATE(), INTERVAL -4 DAY)';
    //
    array_push($legend,"-2Days","-3Days","-4Days");
}elseif($conf->get_conf("backup_day")>=6&&$conf->get_conf("backup_day")<=10){
    $_2Ago='INTERVAL -2 DAY';
    $_2Ago_div='';
    $_2Ago_interv='DATE_ADD(CURDATE(), INTERVAL -2 DAY)';
    //
    $_Week='INTERVAL -3 DAY';
    $_Week_div='';
    $_Week_interv='DATE_ADD(CURDATE(), INTERVAL -3 DAY)';
    //
    $_2Week_value=($conf->get_conf("backup_day")-3)+2;
    $_2Week='INTERVAL -'.$_2Week_value.' DAY';
    $_2Week_div='';
    $_2Week_interv='DATE_ADD(CURDATE(), INTERVAL -'.$_2Week_value.' DAY)';
    //
    array_push($legend,"-2Days","-3Days","-".$_2Week_value."Days");
}elseif($conf->get_conf("backup_day")>=11){
    $_2Ago='INTERVAL -2 DAY';
    $_2Ago_div='';
    $_2Ago_interv='DATE_ADD(CURDATE(), INTERVAL -2 DAY)';
    //
    $_Week='INTERVAL -6 DAY';
    $_Week_div='/7';
    $_Week_interv='NOW()';
    //
    $_2Week='INTERVAL -13 DAY';
    $_2Week_div='/14';
    $_2Week_interv='NOW()';
    //
    array_push($legend,"-2Days","Week","2Weeks");
}
/*
 elseif($conf->get_conf("backup_day")>=11&&$conf->get_conf("backup_day")<=16){
    $_2Ago='INTERVAL -2 DAY';
    $_2Ago_div='';
    $_2Ago_interv='DATE_ADD(CURDATE(), INTERVAL -2 DAY)';
    //
    $_Week='INTERVAL -3 DAY';
    $_Week_div='';
    $_Week_interv='DATE_ADD(CURDATE(), INTERVAL -3 DAY)';
    //
    $_2Week_value=($conf->get_conf("backup_day")-3)+2;
    $_2Week='INTERVAL -'.$_2Week_value.' DAY';
    $_2Week_div='/7';
    $_2Week_interv='DATE_ADD(CURDATE(), INTERVAL -'.$_2Week_value.' DAY)';
    //
    array_push($legend,"-2Days","-3Days","Week");
}elseif($conf->get_conf("backup_day")>=17){
    $_2Ago='INTERVAL -2 DAY';
    $_2Ago_div='';
    $_2Ago_interv='DATE_ADD(CURDATE(), INTERVAL -1 DAY)';
    //
    $_Week='INTERVAL -6 DAY';
    $_Week_div='/7';
    $_Week_interv='NOW()';
    //
    $_2Week='INTERVAL -13 DAY';
    $_2Week_div='/14';
    $_2Week_interv='NOW()';
    //
    array_push($legend,"-2Days","Week","2Weeks");
}
 */
/*
 if($conf->get_conf("backup_day")<=5){
    $_2Ago='INTERVAL -2 DAY';
    $_2Ago_div='';
    //
    $_Week='INTERVAL -6 DAY';
    $_Week_div='/7';
    //
    $_2Week='INTERVAL -13 DAY';
    $_2Week_div='/14';
    //
    array_push($legend,"-2Days","Week","2Weeks");
}
 */
//
$query = "SELECT * FROM 
(select count(*) as Today from alarm where alarm.timestamp > CURDATE()$sensor_where_ossim) as Today, 
(select count(*) as Yesterd from alarm where alarm.timestamp > DATE_ADD(CURDATE(), INTERVAL -1 DAY) and alarm.timestamp < CURDATE()$sensor_where_ossim) as Yesterd,
(select count(*)".$_2Ago_div." as 2DAgo from alarm where alarm.timestamp > DATE_ADD(CURDATE(), ".$_2Ago.") and alarm.timestamp < DATE_ADD(".$_2Ago_interv."$sensor_where_ossim, INTERVAL +1 DAY) ) as 2DAgo,
(select count(*)".$_Week_div." as Week from alarm where alarm.timestamp > DATE_ADD(CURDATE(), ".$_Week.") and alarm.timestamp < ".$_Week_interv."$sensor_where_ossim) as Seamana,
(select count(*)".$_2Week_div." as 2Weeks from alarm where alarm.timestamp > DATE_ADD(CURDATE(), ".$_2Week.") and alarm.timestamp < ".$_2Week_interv."$sensor_where_ossim) as 2Weeks ;";
//echo $query."\n\n";
if ($sensor_where != "")
	$query2 = "SELECT * FROM
	(select count(*) as Today from acid_event where timestamp > CURDATE()$sensor_where) as Today,
	(select count(*) as Yesterd from acid_event where timestamp > DATE_ADD(CURDATE(), INTERVAL -1 DAY) and timestamp <= CURDATE()$sensor_where) as Yesterd,
	(select count(*)".$_2Ago_div." as 2DAgo  from acid_event where timestamp > DATE_ADD(CURDATE(), ".$_2Ago.") and timestamp <= ".$_2Ago_interv."$sensor_where ) as 2DAgo,
	(select count(*)".$_Week_div." as Week from acid_event where timestamp > DATE_ADD(CURDATE(), ".$_Week.") and timestamp <= ".$_Week_interv."$sensor_where) as Seamana,
	(select count(*)".$_2Week_div." as 2Weeks from acid_event where timestamp > DATE_ADD(CURDATE(), ".$_2Week.") and timestamp <= ".$_2Week_interv."$sensor_where) as 2Weeks;";
else
	$query2 = "SELECT * FROM
	(select sum(sig_cnt) as Today from ac_alerts_signature where day >= CURDATE()) as Today,
	(select sum(sig_cnt) as Yesterd from ac_alerts_signature where day >= DATE_ADD(CURDATE(), INTERVAL -1 DAY) and day < CURDATE()) as Yesterd,
	(select sum(sig_cnt)".$_2Ago_div." as 2DAgo  from ac_alerts_signature where day >= DATE_ADD(CURDATE(), ".$_2Ago.") and day <= ".$_2Ago_interv." ) as 2DAgo,
	(select sum(sig_cnt)".$_Week_div." as Week from ac_alerts_signature where day >= DATE_ADD(CURDATE(), ".$_Week.") and day <= ".$_Week_interv.") as Seamana,
	(select sum(sig_cnt)".$_2Week_div." as 2Weeks from ac_alerts_signature where day >= DATE_ADD(CURDATE(), ".$_2Week.") and day <= ".$_2Week_interv.") as 2Weeks;";
//echo $query2;
//echo $query2;
/*
 $query = "SELECT * FROM
(select count(*) as Today from alarm where alarm.timestamp > CURDATE()$sensor_where_ossim) as Today,
(select count(*) as Yesterd from alarm where alarm.timestamp > DATE_ADD(CURDATE(), INTERVAL -1 DAY) and alarm.timestamp < CURDATE()$sensor_where_ossim) as Yesterd,
(select count(*)".$_2Ago_div." as 2DAgo from alarm where alarm.timestamp > DATE_ADD(CURDATE(), ".$_2Ago.") and alarm.timestamp < ".$_2Ago_interv."$sensor_where_ossim ) as 2DAgo,
(select count(*)".$_Week_div." as Week from alarm where alarm.timestamp > DATE_ADD(CURDATE(), ".$_Week.") and alarm.timestamp < ".$_Week_interv."$sensor_where_ossim) as Seamana,
(select count(*)".$_2Week_div." as 2Weeks from alarm where alarm.timestamp > DATE_ADD(CURDATE(), ".$_2Week.") and alarm.timestamp < ".$_2Week_interv."$sensor_where_ossim) as 2Weeks ;";
//echo $query."\n\n";
if ($sensor_where != "")
$query2 = "SELECT * FROM
(select count(*) as Today from acid_event where timestamp > CURDATE()$sensor_where) as Today,
(select count(*) as Yesterd from acid_event where timestamp > DATE_ADD(CURDATE(), INTERVAL -1 DAY) and timestamp < CURDATE()$sensor_where) as Yesterd,
(select count(*)".$_2Ago_div." as 2DAgo  from acid_event where timestamp > DATE_ADD(CURDATE(), ".$_2Ago.") and timestamp < ".$_2Ago_interv."$sensor_where ) as 2DAgo,
(select count(*)".$_Week_div." as Week from acid_event where timestamp > DATE_ADD(CURDATE(), ".$_Week.") and timestamp < ".$_Week_interv."$sensor_where) as Seamana,
(select count(*)".$_2Week_div." as 2Weeks from acid_event where timestamp > DATE_ADD(CURDATE(), ".$_2Week.") and timestamp < ".$_2Week_interv."$sensor_where) as 2Weeks;";
else
$query2 = "SELECT * FROM
(select sum(sig_cnt) as Today from ac_alerts_signature where day >= CURDATE()) as Today,
(select sum(sig_cnt) as Yesterd from ac_alerts_signature where day >= DATE_ADD(CURDATE(), INTERVAL -1 DAY) and day < CURDATE()) as Yesterd,
(select sum(sig_cnt)".$_2Ago_div." as 2DAgo  from ac_alerts_signature where day >= DATE_ADD(CURDATE(), ".$_2Ago.") and day <= ".$_2Ago_interv." ) as 2DAgo,
(select sum(sig_cnt)".$_Week_div." as Week from ac_alerts_signature where day >= DATE_ADD(CURDATE(), ".$_Week.") and day <= ".$_Week_interv.") as Seamana,
(select sum(sig_cnt)".$_2Week_div." as 2Weeks from ac_alerts_signature where day >= DATE_ADD(CURDATE(), ".$_2Week.") and day <= ".$_2Week_interv.") as 2Weeks;";
 */
if (!$rs = & $conn->Execute($query)) {
    print $conn->ErrorMsg();
    exit();
}
while (!$rs->EOF) {
    $values = array(
        "Alarms",
        $rs->fields["Today"],
        $rs->fields["Yesterd"],
        $rs->fields["2DAgo"],
        $rs->fields["Week"],
        $rs->fields["2Weeks"]
    );
    $rs->MoveNext();
}
if (!$rs = & $conn2->Execute($query2)) {
    print $conn->ErrorMsg();
    exit();
}
//print_r($values);
while (!$rs->EOF) {
    $values2 = array(
        "Events",
        $rs->fields["Today"],
        $rs->fields["Yesterd"],
        $rs->fields["2DAgo"],
        $rs->fields["Week"],
        $rs->fields["2Weeks"]
    );
    $rs->MoveNext();
}
$chart['chart_data'] = array(
    $legend,
    $values,
    $values2
);
$chart['live_update'] = array(
    'url' => "/ossim/graphs/alarms_events_data.php?bypassexpirationupdate=1&time=" . time() ,
    'delay' => 30
);
SendChartData($chart);
?>
