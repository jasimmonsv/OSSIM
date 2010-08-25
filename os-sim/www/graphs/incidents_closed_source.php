<?php
require_once ('ossim_db.inc');
require_once ('classes/Alarm.inc');
require_once ('classes/Util.inc');
require_once ('classes/Security.inc');
require_once ('charts.php');

Session::logcheck("MenuControlPanel", "ControlPanelExecutive");

$db = new ossim_db();
$conn = $db->connect();

$query = array();

$user_where = " AND in_charge='".$_SESSION['_user']."'";

$year = date("Y");

array_push($query, 'select * from
(select count(*) as "Jan" from incident where date > "'.$year.'-01-01 00:00:00" and ref="Alarm" and date < "'.$year.'-02-01 00:00:00"'.$user_where.') as enero,
(select count(*) as "Feb" from incident where date > "'.$year.'-02-01 00:00:00" and ref="Alarm" and date < "'.$year.'-03-01 00:00:00"'.$user_where.') as febrero,
(select count(*) as "Mar" from incident where date > "'.$year.'-03-01 00:00:00" and ref="Alarm" and date < "'.$year.'-04-01 00:00:00"'.$user_where.') as marzo,
(select count(*) as "Apr" from incident where date > "'.$year.'-04-01 00:00:00" and ref="Alarm" and date < "'.$year.'-05-01 00:00:00"'.$user_where.') as abril,
(select count(*) as "May" from incident where date > "'.$year.'-05-01 00:00:00" and ref="Alarm" and date < "'.$year.'-06-01 00:00:00"'.$user_where.') as mayo,
(select count(*) as "Jun" from incident where date > "'.$year.'-06-01 00:00:00" and ref="Alarm" and date < "'.$year.'-07-01 00:00:00"'.$user_where.') as junio,
(select count(*) as "Jul" from incident where date > "'.$year.'-07-01 00:00:00" and ref="Alarm" and date < "'.$year.'-08-01 00:00:00"'.$user_where.') as julio,
(select count(*) as "Aug" from incident where date > "'.$year.'-08-01 00:00:00" and ref="Alarm" and date < "'.$year.'-09-01 00:00:00"'.$user_where.') as agosto,
(select count(*) as "Sep" from incident where date > "'.$year.'-09-01 00:00:00" and ref="Alarm" and date < "'.$year.'-10-01 00:00:00"'.$user_where.') as septiembre,
(select count(*) as "Oct" from incident where date > "'.$year.'-10-01 00:00:00" and ref="Alarm" and date < "'.$year.'-11-01 00:00:00"'.$user_where.') as octubre,
(select count(*) as "Nov" from incident where date > "'.$year.'-11-01 00:00:00" and ref="Alarm" and date < "'.$year.'-12-01 00:00:00"'.$user_where.') as noviembre,
(select count(*) as "Dec" from incident where date > "'.$year.'-12-01 00:00:00" and ref="Alarm" and date < "'.$year.'-12-31 23:59:59"'.$user_where.') as diciembre;');

array_push($query, 'select * from
(select count(*) as "Jan" from incident where date > "'.$year.'-01-01 00:00:00" and ref="Alert" and date < "'.$year.'-02-01 00:00:00"'.$user_where.') as enero,
(select count(*) as "Feb" from incident where date > "'.$year.'-02-01 00:00:00" and ref="Alert" and date < "'.$year.'-03-01 00:00:00"'.$user_where.') as febrero,
(select count(*) as "Mar" from incident where date > "'.$year.'-03-01 00:00:00" and ref="Alert" and date < "'.$year.'-04-01 00:00:00"'.$user_where.') as marzo,
(select count(*) as "Apr" from incident where date > "'.$year.'-04-01 00:00:00" and ref="Alert" and date < "'.$year.'-05-01 00:00:00"'.$user_where.') as abril,
(select count(*) as "May" from incident where date > "'.$year.'-05-01 00:00:00" and ref="Alert" and date < "'.$year.'-06-01 00:00:00"'.$user_where.') as mayo,
(select count(*) as "Jun" from incident where date > "'.$year.'-06-01 00:00:00" and ref="Alert" and date < "'.$year.'-07-01 00:00:00"'.$user_where.') as junio,
(select count(*) as "Jul" from incident where date > "'.$year.'-07-01 00:00:00" and ref="Alert" and date < "'.$year.'-08-01 00:00:00"'.$user_where.') as julio,
(select count(*) as "Aug" from incident where date > "'.$year.'-08-01 00:00:00" and ref="Alert" and date < "'.$year.'-09-01 00:00:00"'.$user_where.') as agosto,
(select count(*) as "Sep" from incident where date > "'.$year.'-09-01 00:00:00" and ref="Alert" and date < "'.$year.'-10-01 00:00:00"'.$user_where.') as septiembre,
(select count(*) as "Oct" from incident where date > "'.$year.'-10-01 00:00:00" and ref="Alert" and date < "'.$year.'-11-01 00:00:00"'.$user_where.') as octubre,
(select count(*) as "Nov" from incident where date > "'.$year.'-11-01 00:00:00" and ref="Alert" and date < "'.$year.'-12-01 00:00:00"'.$user_where.') as noviembre,
(select count(*) as "Dec" from incident where date > "'.$year.'-12-01 00:00:00" and ref="Alert" and date < "'.$year.'-12-31 23:59:59"'.$user_where.') as diciembre;');

array_push($query,'select * from
(select count(*) as "Jan" from incident where date > "'.$year.'-01-01 00:00:00" and ref="Event" and date < "'.$year.'-02-01 00:00:00"'.$user_where.') as enero,
(select count(*) as "Feb" from incident where date > "'.$year.'-02-01 00:00:00" and ref="Event" and date < "'.$year.'-03-01 00:00:00"'.$user_where.') as febrero,
(select count(*) as "Mar" from incident where date > "'.$year.'-03-01 00:00:00" and ref="Event" and date < "'.$year.'-04-01 00:00:00"'.$user_where.') as marzo,
(select count(*) as "Apr" from incident where date > "'.$year.'-04-01 00:00:00" and ref="Event" and date < "'.$year.'-05-01 00:00:00"'.$user_where.') as abril,
(select count(*) as "May" from incident where date > "'.$year.'-05-01 00:00:00" and ref="Event" and date < "'.$year.'-06-01 00:00:00"'.$user_where.') as mayo,
(select count(*) as "Jun" from incident where date > "'.$year.'-06-01 00:00:00" and ref="Event" and date < "'.$year.'-07-01 00:00:00"'.$user_where.') as junio,
(select count(*) as "Jul" from incident where date > "'.$year.'-07-01 00:00:00" and ref="Event" and date < "'.$year.'-08-01 00:00:00"'.$user_where.') as julio,
(select count(*) as "Aug" from incident where date > "'.$year.'-08-01 00:00:00" and ref="Event" and date < "'.$year.'-09-01 00:00:00"'.$user_where.') as agosto,
(select count(*) as "Sep" from incident where date > "'.$year.'-09-01 00:00:00" and ref="Event" and date < "'.$year.'-10-01 00:00:00"'.$user_where.') as septiembre,
(select count(*) as "Oct" from incident where date > "'.$year.'-10-01 00:00:00" and ref="Event" and date < "'.$year.'-11-01 00:00:00"'.$user_where.') as octubre,
(select count(*) as "Nov" from incident where date > "'.$year.'-11-01 00:00:00" and ref="Event" and date < "'.$year.'-12-01 00:00:00"'.$user_where.') as noviembre,
(select count(*) as "Dec" from incident where date > "'.$year.'-12-01 00:00:00" and ref="Event" and date < "'.$year.'-12-31 23:59:59"'.$user_where.') as diciembre;');

array_push($query,'select * from
(select count(*) as "Jan" from incident where date > "'.$year.'-01-01 00:00:00" and ref="Metric" and date < "'.$year.'-02-01 00:00:00"'.$user_where.') as enero,
(select count(*) as "Feb" from incident where date > "'.$year.'-02-01 00:00:00" and ref="Metric" and date < "'.$year.'-03-01 00:00:00"'.$user_where.') as febrero,
(select count(*) as "Mar" from incident where date > "'.$year.'-03-01 00:00:00" and ref="Metric" and date < "'.$year.'-04-01 00:00:00"'.$user_where.') as marzo,
(select count(*) as "Apr" from incident where date > "'.$year.'-04-01 00:00:00" and ref="Metric" and date < "'.$year.'-05-01 00:00:00"'.$user_where.') as abril,
(select count(*) as "May" from incident where date > "'.$year.'-05-01 00:00:00" and ref="Metric" and date < "'.$year.'-06-01 00:00:00"'.$user_where.') as mayo,
(select count(*) as "Jun" from incident where date > "'.$year.'-06-01 00:00:00" and ref="Metric" and date < "'.$year.'-07-01 00:00:00"'.$user_where.') as junio,
(select count(*) as "Jul" from incident where date > "'.$year.'-07-01 00:00:00" and ref="Metric" and date < "'.$year.'-08-01 00:00:00"'.$user_where.') as julio,
(select count(*) as "Aug" from incident where date > "'.$year.'-08-01 00:00:00" and ref="Metric" and date < "'.$year.'-09-01 00:00:00"'.$user_where.') as agosto,
(select count(*) as "Sep" from incident where date > "'.$year.'-09-01 00:00:00" and ref="Metric" and date < "'.$year.'-10-01 00:00:00"'.$user_where.') as septiembre,
(select count(*) as "Oct" from incident where date > "'.$year.'-10-01 00:00:00" and ref="Metric" and date < "'.$year.'-11-01 00:00:00"'.$user_where.') as octubre,
(select count(*) as "Nov" from incident where date > "'.$year.'-11-01 00:00:00" and ref="Metric" and date < "'.$year.'-12-01 00:00:00"'.$user_where.') as noviembre,
(select count(*) as "Dec" from incident where date > "'.$year.'-12-01 00:00:00" and ref="Metric" and date < "'.$year.'-12-31 23:59:59"'.$user_where.') as diciembre;');

array_push($query,'select * from
(select count(*) as "Jan" from incident where date > "'.$year.'-01-01 00:00:00" and ref="Anomaly" and date < "'.$year.'-02-01 00:00:00"'.$user_where.') as enero,
(select count(*) as "Feb" from incident where date > "'.$year.'-02-01 00:00:00" and ref="Anomaly" and date < "'.$year.'-03-01 00:00:00"'.$user_where.') as febrero,
(select count(*) as "Mar" from incident where date > "'.$year.'-03-01 00:00:00" and ref="Anomaly" and date < "'.$year.'-04-01 00:00:00"'.$user_where.') as marzo,
(select count(*) as "Apr" from incident where date > "'.$year.'-04-01 00:00:00" and ref="Anomaly" and date < "'.$year.'-05-01 00:00:00"'.$user_where.') as abril,
(select count(*) as "May" from incident where date > "'.$year.'-05-01 00:00:00" and ref="Anomaly" and date < "'.$year.'-06-01 00:00:00"'.$user_where.') as mayo,
(select count(*) as "Jun" from incident where date > "'.$year.'-06-01 00:00:00" and ref="Anomaly" and date < "'.$year.'-07-01 00:00:00"'.$user_where.') as junio,
(select count(*) as "Jul" from incident where date > "'.$year.'-07-01 00:00:00" and ref="Anomaly" and date < "'.$year.'-08-01 00:00:00"'.$user_where.') as julio,
(select count(*) as "Aug" from incident where date > "'.$year.'-08-01 00:00:00" and ref="Anomaly" and date < "'.$year.'-09-01 00:00:00"'.$user_where.') as agosto,
(select count(*) as "Sep" from incident where date > "'.$year.'-09-01 00:00:00" and ref="Anomaly" and date < "'.$year.'-10-01 00:00:00"'.$user_where.') as septiembre,
(select count(*) as "Oct" from incident where date > "'.$year.'-10-01 00:00:00" and ref="Anomaly" and date < "'.$year.'-11-01 00:00:00"'.$user_where.') as octubre,
(select count(*) as "Nov" from incident where date > "'.$year.'-11-01 00:00:00" and ref="Anomaly" and date < "'.$year.'-12-01 00:00:00"'.$user_where.') as noviembre,
(select count(*) as "Dec" from incident where date > "'.$year.'-12-01 00:00:00" and ref="Anomaly" and date < "'.$year.'-12-31 23:59:59"'.$user_where.') as diciembre;');

array_push($query,'select * from
(select count(*) as "Jan" from incident where date > "'.$year.'-01-01 00:00:00" and ref="Vulnerability" and date < "'.$year.'-02-01 00:00:00"'.$user_where.') as enero,
(select count(*) as "Feb" from incident where date > "'.$year.'-02-01 00:00:00" and ref="Vulnerability" and date < "'.$year.'-03-01 00:00:00"'.$user_where.') as febrero,
(select count(*) as "Mar" from incident where date > "'.$year.'-03-01 00:00:00" and ref="Vulnerability" and date < "'.$year.'-04-01 00:00:00"'.$user_where.') as marzo,
(select count(*) as "Apr" from incident where date > "'.$year.'-04-01 00:00:00" and ref="Vulnerability" and date < "'.$year.'-05-01 00:00:00"'.$user_where.') as abril,
(select count(*) as "May" from incident where date > "'.$year.'-05-01 00:00:00" and ref="Vulnerability" and date < "'.$year.'-06-01 00:00:00"'.$user_where.') as mayo,
(select count(*) as "Jun" from incident where date > "'.$year.'-06-01 00:00:00" and ref="Vulnerability" and date < "'.$year.'-07-01 00:00:00"'.$user_where.') as junio,
(select count(*) as "Jul" from incident where date > "'.$year.'-07-01 00:00:00" and ref="Vulnerability" and date < "'.$year.'-08-01 00:00:00"'.$user_where.') as julio,
(select count(*) as "Aug" from incident where date > "'.$year.'-08-01 00:00:00" and ref="Vulnerability" and date < "'.$year.'-09-01 00:00:00"'.$user_where.') as agosto,
(select count(*) as "Sep" from incident where date > "'.$year.'-09-01 00:00:00" and ref="Vulnerability" and date < "'.$year.'-10-01 00:00:00"'.$user_where.') as septiembre,
(select count(*) as "Oct" from incident where date > "'.$year.'-10-01 00:00:00" and ref="Vulnerability" and date < "'.$year.'-11-01 00:00:00"'.$user_where.') as octubre,
(select count(*) as "Nov" from incident where date > "'.$year.'-11-01 00:00:00" and ref="Vulnerability" and date < "'.$year.'-12-01 00:00:00"'.$user_where.') as noviembre,
(select count(*) as "Dec" from incident where date > "'.$year.'-12-01 00:00:00" and ref="Vulnerability" and date < "'.$year.'-12-31 23:59:59"'.$user_where.') as diciembre;');

// PHP/SWF Chart License - Licensed to ossim.com. For distribution with ossim only. No other redistribution / usage allowed.
// For more information please check http://www.maani.us/charts/index.php?menu=License_bulk
$chart[ 'license' ] = "J1XF-CMEW9L.HSK5T4Q79KLYCK07EK";

$chart[ 'axis_category' ] = array ( 'size'=>10, 'color'=>"000000", 'alpha'=>75 ); 
$chart[ 'axis_ticks' ] = array ( 'value_ticks'=>true, 'category_ticks'=>true, 'minor_count'=>1 );
$chart[ 'axis_value' ] = array ( 'size'=>10, 'color'=>"FFFFFF", 'alpha'=>75 );

$chart[ 'chart_border' ] = array ( 'top_thickness'=>0, 'bottom_thickness'=>2, 'left_thickness'=>2, 'right_thickness'=>0 );
$chart[ 'chart_grid_h' ] = array ( 'thickness'=>1, 'type'=>"solid" );
$chart[ 'chart_grid_v' ] = array ( 'thickness'=>1, 'type'=>"solid" );
$chart[ 'chart_rect' ] = array ( 'x'=>60, 'y'=>60, 'width'=>400, 'height'=>150, 'positive_color'=>"888888", 'positive_alpha'=>50 );
$chart[ 'chart_pref' ] = array ( 'rotation_x'=>15, 'rotation_y'=>0 ); 
$chart[ 'chart_type' ] = "stacked 3d column" ;
$chart[ 'chart_value' ] = array ( 'color'=>"00ffcc", 'background_color'=>"444488", 'alpha'=>100, 'size'=>12, 'position'=>"cursor" );
$chart[ 'chart_transition' ] = array ( 'type'=>"slide_down", 'delay'=>0, 'duration'=>1, 'order'=>"series" );

$chart[ 'legend_label' ] = array ( 'layout'=>"horizontal", 'font'=>"arial", 'bold'=>true, 'size'=>12, 'color'=>"000000", 'alpha'=>50 ); 
$chart[ 'legend_rect' ] = array ( 'x'=>80, 'y'=>250, 'width'=>350, 'height'=>50, 'margin'=>20, 'fill_color'=>"000000", 'fill_alpha'=>7, 'line_color'=>"000000", 'line_alpha'=>0, 'line_thickness'=>0 ); 

$chart[ 'series_color' ] = array ("ff6600", "88ff00", "8866ff", "ff0000", "00ff00", "0000ff" ); 
$chart[ 'series_gap' ] = array ( 'bar_gap'=>0, 'set_gap'=>20) ; 
$chart[ 'draw' ] = array ( array ( 'type'=>"text", 'color'=>"000000", 'alpha'=>75, 'rotation'=>0, 'size'=>20, 'x'=>70, 'y'=>30, 'width'=>400, 'height'=>200, 'text'=>"Tickets Closed by Month", 'h_align'=>"left", 'v_align'=>"top" ));

$chart [ 'link_data' ] = array (   'url'     =>  "handle.php?target_url=incident_ref&target_var=series",
                                   'target'  =>  "main"
                              );


$legend = array("", "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");
$final_values = array();

$tmp = array("Alarm","Alert","Event","Metric","Anomaly","Vulnerability");
$i = 0;


foreach($query as $quer){
$values = array();
if (!$rs = &$conn->Execute($quer)) {
print $conn->ErrorMsg();
exit();
}

while (!$rs->EOF)
{

array_push($values, $tmp[$i]);
array_push($values, $rs->fields["Jan"]);
array_push($values, $rs->fields["Feb"]);
array_push($values, $rs->fields["Mar"]);
array_push($values, $rs->fields["Apr"]);
array_push($values, $rs->fields["May"]);
array_push($values, $rs->fields["Jun"]);
array_push($values, $rs->fields["Jul"]);
array_push($values, $rs->fields["Aug"]);
array_push($values, $rs->fields["Sep"]);
array_push($values, $rs->fields["Oct"]);
array_push($values, $rs->fields["Nov"]);
array_push($values, $rs->fields["Dec"]);

$rs->MoveNext();
}
array_push($final_values, $values);
$i++;
}

$chart['chart_data'] = array($legend, 
$final_values[0],
$final_values[1],
$final_values[2],
$final_values[3],
$final_values[4],
$final_values[5],
);
//print_r($chart['chart_data']);
SendChartData ( $chart );


?>
