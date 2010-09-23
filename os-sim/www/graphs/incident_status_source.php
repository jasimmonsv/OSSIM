<?php
require_once ('ossim_db.inc');
require_once ('classes/Alarm.inc');
require_once ('classes/Util.inc');
require_once ('classes/Security.inc');
require_once ('charts.php');

Session::logcheck("MenuControlPanel", "ControlPanelExecutive");

$db = new ossim_db();
$conn = $db->connect();

//$query = "select  status, count(*) as num from incident group by status;";
// Filtered by user
//$query = "select DISTINCT u.status, count(*) as num from (( SELECT DISTINCT i.status, i.id FROM incident i WHERE i.in_charge = '".$_SESSION['_user']."' ) UNION ( SELECT DISTINCT t.status, t.incident_id AS id FROM incident_ticket t WHERE t.in_charge = '".$_SESSION['_user']."' )) u group by u.status;";

$user = $_SESSION['_user'];
if (Session::am_i_admin()) {
	$query = "SELECT DISTINCT SQL_CALC_FOUND_ROWS incident.* 
	FROM incident";
} else {
	$query = "SELECT DISTINCT SQL_CALC_FOUND_ROWS incident.* 
	FROM incident LEFT JOIN incident_ticket ON incident_ticket.incident_id = incident.id, users, incident_subscrip WHERE  incident_subscrip.incident_id=incident.id AND users.login = incident_subscrip.login AND (incident_ticket.users='$user' OR incident_ticket.in_charge='$user' OR incident_ticket.transferred='$user' OR users.login='$user')
	ORDER BY date";
}
$rs = &$conn->Execute($query);
$status = array("Open"=>0,"Closed"=>0);
while (!$rs->EOF)
{
	$status[$rs->fields['status']]++;
	$rs->MoveNext();
}

// PHP/SWF Chart License - Licensed to ossim.com. For distribution with ossim only. No other redistribution / usage allowed.
// For more information please check http://www.maani.us/charts/index.php?menu=License_bulk
$chart[ 'license' ] = "J1XF-CMEW9L.HSK5T4Q79KLYCK07EK";

//$chart[ 'chart_data' ] = array ( array ( "", "US","UK","India", "Japan","China" ), array ( "", 50,70,55,60,30 ) );
$chart[ 'chart_pref' ] = array ( 'rotation_x'=>60 );
$chart[ 'chart_rect' ] = array ( 'x'=>50, 'y'=>30, 'width'=>130, 'height'=>200, 'positive_alpha'=>0 );
$chart[ 'chart_transition' ] = array ( 'type'=>"scale", 'delay'=>.1, 'duration'=>.3, 'order'=>"category" );
$chart[ 'chart_type' ] = "3d pie";
$chart[ 'chart_value' ] = array ( 'as_percentage'=>true, 'size'=>9, 'color'=>"000000", 'alpha'=>85 );

$chart[ 'legend_label' ] = array ( 'layout'=>"vertical", 'bullet'=>"circle", 'size'=>11, 'color'=>"505050", 'alpha'=>85, 'bold'=>false);
$chart[ 'legend_rect' ] = array ( 'x'=>220, 'y'=>120, 'width'=>20, 'height'=>40, 'fill_alpha'=>0 );

$chart[ 'draw' ] = array ( array ( 'type'=>"text", 'color'=>"000000", 'alpha'=>75, 'rotation'=>0, 'size'=>20, 'x'=>70, 'y'=>30, 'width'=>400, 'height'=>200, 'text'=>"Ticket Status", 'h_align'=>"left", 'v_align'=>"top" ));

$chart[ 'series_color' ] = array ("666666", "4488aa" );
//$chart [ 'series_explode' ] = array ( 0, 50 );

$chart [ 'link_data' ] = array (   'url'     =>  "handle.php?target_url=incident_status&target_var=category",
                                   'target'  =>  "_top"
                              );

$legend = array("");
$values = array("");
foreach ($status as $key=>$val) {
	$legend[] = $key;
	$values[] = $val;
}
/*
if (!$rs = &$conn->Execute($query)) {
print $conn->ErrorMsg();
exit();
}

while (!$rs->EOF)
{

array_push($legend, $rs->fields["status"]);
array_push($values, $rs->fields["num"]);

$rs->MoveNext();
}
*/
$chart['chart_data'] = array($legend, $values);
SendChartData ( $chart );


?>
