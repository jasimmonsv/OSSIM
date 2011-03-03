<?php
require_once ('ossim_db.inc');
require_once ('classes/Alarm.inc');
require_once ('classes/Util.inc');
require_once ('classes/Security.inc');
require_once ('charts.php');

Session::logcheck("MenuControlPanel", "ControlPanelExecutive");

$db = new ossim_db();
$conn = $db->connect();

$query = "select count(*) as num, name from ocsweb.softwares group by name order by num desc limit 10;";


// PHP/SWF Chart License - Licensed to ossim.com. For distribution with ossim only. No other redistribution / usage allowed.
// For more information please check http://www.maani.us/charts/index.php?menu=License_bulk
$chart[ 'license' ] = "J1XF-CMEW9L.HSK5T4Q79KLYCK07EK";

$chart[ 'chart_grid_h' ] = array ( 'thickness'=>0 );
$chart[ 'chart_pref' ] = array ( 'rotation_x'=>60 ); 
$chart[ 'chart_rect' ] = array ( 'x'=>150, 'y'=>120, 'width'=>300, 'height'=>200, 'positive_alpha'=>0 );
//$chart[ 'chart_transition' ] = array ( 'type'=>"spin", 'delay'=>.5, 'duration'=>.75, 'order'=>"category" );
$chart[ 'chart_type' ] = "3d pie";
$chart[ 'chart_value' ] = array ( 'color'=>"000000", 'alpha'=>65, 'font'=>"arial", 'bold'=>true, 'size'=>10, 'position'=>"inside", 'prefix'=>"", 'suffix'=>"", 'decimals'=>0, 'separator'=>"", 'as_percentage'=>true );

$chart[ 'draw' ] = array ( array ( 'type'=>"text", 'color'=>"000000", 'alpha'=>4, 'size'=>40, 'x'=>-50, 'y'=>260, 'width'=>500, 'height'=>50, 'text'=>_("Installed Software"), 'h_align'=>"center", 'v_align'=>"middle" )) ;

$chart[ 'legend_label' ] = array ( 'layout'=>"horizontal", 'bullet'=>"circle", 'font'=>"arial", 'bold'=>true, 'size'=>12, 'color'=>"000000", 'alpha'=>85 ); 
$chart[ 'legend_rect' ] = array ( 'x'=>0, 'y'=>45, 'width'=>50, 'height'=>60, 'margin'=>10, 'fill_color'=>"ffffff", 'fill_alpha'=>10, 'line_color'=>"000000", 'line_alpha'=>0, 'line_thickness'=>0 );  
$chart[ 'legend_transition' ] = array ( 'type'=>"dissolve", 'delay'=>0, 'duration'=>1 );

$chart[ 'series_color' ] = array ( "00ff88", "ffaa00","44aaff", "aa00ff" ); 
$chart[ 'series_explode' ] = array ( 25, 35, 0, 0 );

$chart [ 'link_data' ] = array (   'url'     =>  "handle.php?target_url=inventory&target_var=category",
                                   'target'  =>  "_top"
                              );

$legend = array();
$values = array();

if (!$rs = &$conn->Execute($query)) {
print $conn->ErrorMsg();
exit();
}

array_push($legend, " ");
array_push($values, " ");

while (!$rs->EOF)
{

array_push($legend, $rs->fields["name"]);
array_push($values, $rs->fields["num"]);

$rs->MoveNext();
}
$chart['chart_data'] = array($legend, $values);
SendChartData ( $chart );


?>
