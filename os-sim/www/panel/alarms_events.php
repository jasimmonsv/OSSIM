<?php
require_once ('ossim_db.inc');
require_once ('classes/Alarm.inc');
require_once ('classes/Incident.inc');
require_once ('classes/Util.inc');
require_once ('classes/Security.inc');
require_once ('classes/Session.inc');
Session::logcheck("MenuControlPanel", "ControlPanelExecutive");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
<head>
	  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	  <title>Bar Charts</title>
	  <!--[if IE]><script language="javascript" type="text/javascript" src="../js/jqplot/excanvas.js"></script><![endif]-->
	  
	  <link rel="stylesheet" type="text/css" href="../js/jqplot/jquery.jqplot.css" />
	  <link rel="stylesheet" type="text/css" href="../js/jqplot/examples/examples.css" />
		
	  <!-- BEGIN: load jquery -->
	  <script language="javascript" type="text/javascript" src="../js/jqplot/jquery-1.4.2.min.js"></script>
	  <!-- END: load jquery -->
	  
	  <!-- BEGIN: load jqplot -->
	  <script language="javascript" type="text/javascript" src="../js/jqplot/jquery.jqplot.min.js"></script>
	  <script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.barRenderer.js"></script>
	  <script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.categoryAxisRenderer.min.js"></script>
	  <script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.pointLabels.min.js"></script>
	  <script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.canvasTextRenderer.js"></script>
	  <script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.canvasAxisTickRenderer.js"></script>
	  
	<style type="text/css">
		
		#chart .jqplot-point-label {
		  border: 1.5px solid #aaaaaa;
		  padding: 1px 3px;
		  background-color: #eeccdd;
		}

	</style>

<?php

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
    //array_push($legend,"-2Days","-3Days","-4Days");
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
    //array_push($legend,"-2Days","-3Days","-".$_2Week_value."Days");
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
    //array_push($legend,"-2Days","Week","2Weeks");
}
$query = "SELECT * FROM 
(select count(*) as Today from alarm where alarm.timestamp > CURDATE()$sensor_where_ossim) as Today, 
(select count(*) as Yesterd from alarm where alarm.timestamp > DATE_ADD(CURDATE(), INTERVAL -1 DAY) and alarm.timestamp < CURDATE()$sensor_where_ossim) as Yesterd,
(select count(*)".$_2Ago_div." as 2DAgo from alarm where alarm.timestamp > DATE_ADD(CURDATE(), ".$_2Ago.") and alarm.timestamp < DATE_ADD(".$_2Ago_interv."$sensor_where_ossim, INTERVAL +1 DAY) ) as 2DAgo,
(select count(*)".$_Week_div." as Week from alarm where alarm.timestamp > DATE_ADD(CURDATE(), ".$_Week.") and alarm.timestamp < ".$_Week_interv."$sensor_where_ossim) as Seamana,
(select count(*)".$_2Week_div." as 2Weeks from alarm where alarm.timestamp > DATE_ADD(CURDATE(), ".$_2Week.") and alarm.timestamp < ".$_2Week_interv."$sensor_where_ossim) as 2Weeks ;";
/*
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
*/
if (!$rs = & $conn->Execute($query)) {
    print $conn->ErrorMsg();
    exit();
}
while (!$rs->EOF) {
    $values =  $rs->fields["Today"].",".
        $rs->fields["Yesterd"].",".
        $rs->fields["2DAgo"].",".
        $rs->fields["Week"].",".
        $rs->fields["2Weeks"];
    $rs->MoveNext();
}
/*
if (!$rs = & $conn2->Execute($query2)) {
    print $conn->ErrorMsg();
    exit();
}
while (!$rs->EOF) {
    $values2 = $rs->fields["Today"].",".
        $rs->fields["Yesterd"].",".
        $rs->fields["2DAgo"].",".
        $rs->fields["Week"].",".
        $rs->fields["2Weeks"];
    $rs->MoveNext();
}
*/
$incident_list = Incident::search($conn, array("status"=>"Open","last_update"=>"CURDATE()"), "", "", 1, 99999999);
$today = Incident::search_count($conn);
$incident_list = Incident::search($conn, array("status"=>"Open","last_update"=>array("DATE_ADD(CURDATE(), INTERVAL -1 DAY)","CURDATE()")), "", "", 1, 999999999);
$yday = Incident::search_count($conn);
$incident_list = Incident::search($conn, array("status"=>"Open","last_update"=>array("DATE_ADD(CURDATE(), ".$_2Ago.")",$_2Ago_interv)), "", "", 1, 999999999);
$ago2 = Incident::search_count($conn);
$incident_list = Incident::search($conn, array("status"=>"Open","last_update"=>array("DATE_ADD(CURDATE(), ".$_Week.")",$_Week_interv)), "", "", 1, 999999999);
$week = Incident::search_count($conn);
$incident_list = Incident::search($conn, array("status"=>"Open","last_update"=>array("DATE_ADD(CURDATE(), ".$_2Week.")",$_2Week_interv)), "", "", 1, 999999999);
$week2 = Incident::search_count($conn);
$values2 = $today.",".$yday.",".$ago2.",".$week.",".$week2;
//
$db->close($conn);

?>  
	<script class="code" type="text/javascript">
	
		$(document).ready(function(){
			$.jqplot.config.enablePlugins = true;		
			
			line1 = [<?=$values?>];
			line2 = [<?=$values2?>];
			
			plot1 = $.jqplot('chart', [line1, line2], {
			    legend:{show:true, location:'nw', rowSpacing:'2px'},
			    series:[
			        { pointLabels:{ show: false }, label:'<?=_("Alarms")?>', renderer:$.jqplot.BarRenderer }, 
			        { pointLabels:{ show: false }, label:'<?=_("Tickets")?>', renderer:$.jqplot.BarRenderer },
			    ],                                    
			    grid: { background: '#F5F5F5', shadow: false },
			    seriesColors: [ "#EFBE68", "#B5CF81" ],
			    axes:{
			        xaxis:{
			        	renderer:$.jqplot.CategoryAxisRenderer,
			        	ticks:['<?=_("Today")?>', '<?=_("-1 Day")?>', '<?=_("-2 Days")?>', '<?=_("Week")?>', '<?=_("2 Weeks")?>']
			        }, 
			        yaxis:{min:0, tickOptions:{formatString:'%d'}}
			    }
			});

		});
	</script>

    
    
  </head>
	<body style="overflow:hidden">
		<div id="chart" style="width:100%; height: 290px;"></div>
	</body>
</html>
