<?php
require_once ('ossim_db.inc');
require_once ('classes/Alarm.inc');
require_once ('classes/Util.inc');
require_once ('classes/Security.inc');
require_once ('classes/Incident.inc');
require_once ('charts.php');

Session::logcheck("MenuControlPanel", "ControlPanelExecutive");

$db = new ossim_db();
$conn = $db->connect();

// PHP/SWF Chart License - Licensed to ossim.com. For distribution with ossim only. No other redistribution / usage allowed.
// For more information please check http://www.maani.us/charts/index.php?menu=License_bulk
$chart[ 'license' ] = "J1XF-CMEW9L.HSK5T4Q79KLYCK07EK";

$chart[ 'axis_category' ] = array ( 'font'=>"arial", 'bold'=>true, 'size'=>11, 'color'=>"000000", 'alpha'=>50, 'skip'=>0 );
$chart[ 'axis_ticks' ] = array ( 'value_ticks'=>false, 'category_ticks'=>true, 'major_thickness'=>2, 'minor_thickness'=>1, 'minor_count'=>3, 'major_color'=>"000000", 'minor_color'=>"888888" ,'position'=>"outside" );
$chart[ 'axis_value' ] = array ( 'font'=>"arial", 'bold'=>true, 'size'=>10, 'color'=>"000000", 'alpha'=>50, 'steps'=>4, 'prefix'=>"", 'suffix'=>"", 'decimals'=>0, 'separator'=>"", 'show_min'=>true );
$chart[ 'chart_transition' ] = array ( 'type'=>"zoom", 'delay'=>.5, 'duration'=>.5, 'order'=>"all" );


$chart[ 'chart_border' ] = array ( 'color'=>"000000", 'top_thickness'=>1, 'bottom_thickness'=>2, 'left_thickness'=>0, 'right_thickness'=>0 );
$chart[ 'chart_grid_h' ] = array ( 'alpha'=>10, 'color'=>"0066FF", 'thickness'=>28 );
$chart[ 'chart_grid_v' ] = array ( 'alpha'=>10, 'color'=>"0066FF", 'thickness'=>1 );
$chart[ 'chart_rect' ] = array ( 'x'=> 20, 'y'=>50, 'width'=>370, 'height'=>220, 'positive_color'=>"FFFFFF", 'positive_alpha'=>40 );

$chart[ 'draw' ] = array ( array ( 'type'=>"text", 'color'=>"000000", 'alpha'=>75, 'rotation'=>0, 'size'=>20, 'x'=>0, 'y'=>10, 'width'=>400, 'height'=>200, 'text'=>"Ticket Resolution Time", 'h_align'=>"left", 'v_align'=>"top" ));
       

$chart[ 'legend_rect' ] = array ( 'x'=>-100, 'y'=>-100, 'width'=>10, 'height'=>10, 'margin'=>0 ); 

$chart[ 'series_color' ] = array ( "dd6b66","7e6cee" );
$chart[ 'series_gap' ] = array ( 'set_gap'=>0, 'bar_gap'=>0 );

$ttl_groups=array();
//$list = Incident::search($conn, array('status' => 'Closed'));
// Filtered by USER
$list = Incident::search($conn, array('status' => 'Closed', 'in_charge' => $_SESSION['_user']));
$ttl_groups[1] = 0;
$ttl_groups[2] = 0;
$ttl_groups[3] = 0;
$ttl_groups[4] = 0;
$ttl_groups[5] = 0;
$ttl_groups[6] = 0;

$total_days = 0;
$day_count;

foreach ($list as $incident) {
        $ttl_secs = $incident->get_life_time('s');
        $days = round($ttl_secs/60/60/24);
        $total_days += $days;
        $day_count++;
        if ($days < 1) $days = 1;
        if ($days > 6) $days = 6;
        @$ttl_groups[$days]++;
}

$tempy = array("");
$datay_tmp  = array_values($ttl_groups);
$datay = array_merge($tempy, $datay_tmp);
$labelx = array("", "1 Day","2 Days","3 Days","4 Days","5 Days","6+ Days");
if($day_count < 1) $day_count = 1;
$titley = _("Duration in days.") . " " . _("Average:") . " " .  $total_days/$day_count;
$titlex = _("Num. Incidents");

$chart['chart_data'] = array($labelx, $datay);
SendChartData ( $chart );


?>
