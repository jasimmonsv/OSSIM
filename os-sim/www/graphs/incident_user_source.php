<?php
require_once ('ossim_db.inc');
require_once ('classes/Alarm.inc');
require_once ('classes/Util.inc');
require_once ('classes/Security.inc');
require_once ('charts.php');
require_once ('classes/Session.inc');
require_once ('ossim_conf.inc'); 

Session::logcheck("MenuControlPanel", "ControlPanelExecutive");

$db = new ossim_db();
$conn = $db->connect();

$admin_where = (Session::am_i_admin()) ? "" : " AND in_charge!='admin'";
$query = "select in_charge, count(*) as num from incident where status='Open'$admin_where group by in_charge;";

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

$chart[ 'draw' ] = array ( array ( 'type'=>"text", 'color'=>"000000", 'alpha'=>75, 'rotation'=>0, 'size'=>20, 'x'=>70, 'y'=>30, 'width'=>400, 'height'=>200, 'text'=>"Opened Tickets by User", 'h_align'=>"left", 'v_align'=>"top" ));

$chart[ 'series_color' ] = array ( "cc6600", "aaaa22", "8800dd", "666666", "4488aa" );
//$chart [ 'series_explode' ] = array ( 0, 50 );

$legend = array("");
$values = array("");

if (!$rs = &$conn->Execute($query)) {
print $conn->ErrorMsg();
exit();
}

$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);

while (!$rs->EOF)
{
    if(preg_match("/pro|demo/i",$version) && preg_match("/^\d+$/",$rs->fields["in_charge"])) {
        list($name, $type) = Acl::get_entity_name_type($conn,$rs->fields["in_charge"]);
        if($type!="" && $name!="")
            array_push($legend, $name." [".$type."]");
    }
    else {
        array_push($legend, $rs->fields["in_charge"]);
    }
    array_push($values, $rs->fields["num"]);

    $rs->MoveNext();
}

$chart['chart_data'] = array($legend, $values);
SendChartData ( $chart );


?>
