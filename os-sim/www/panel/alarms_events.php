<?php
require_once ('ossim_db.inc');
require_once ('classes/Alarm.inc');
require_once ('classes/Incident.inc');
require_once ('classes/Util.inc');
require_once ('classes/Security.inc');
require_once ('classes/Session.inc');
require_once 'sensor_filter.php';
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

$db = new ossim_db();
$conn = $db->connect();
$conn2 = $db->snort_connect();

if (GET("type")=="alarms")
	$link = "'../control_panel/alarm_console.php?num_alarms_page=50&hmenu=Alarms&smenu=Alarms&hide_closed=1&query=QQQ',";
else
	$link = "'../forensics/base_qry_main.php?clear_allcriteria=1&time_range=all&submit=Query+DB&sig_type=1&sig%5B0%5D=%3D&sig%5B1%5D=QQQ&sort_order=time_d&hmenu=Forensics&smenu=Forensics',";

$sensor_where = "";
$sensor_where_ossim = "";
if (Session::allowedSensors() != "") {
	$user_sensors = explode(",",Session::allowedSensors());
	$snortsensors = GetSnortSensorSids($conn2);
	$sids = array();
	foreach ($user_sensors as $user_sensor) {
		//echo "Sids de $user_sensor ".$snortsensors[$user_sensor][0]."<br>";
		if (count($snortsensors[$user_sensor]) > 0)
			foreach ($snortsensors[$user_sensor] as $sid) if ($sid != "")
				$sids[] = $sid;
	}
	if (count($sids) > 0) {
		$sensor_where = " AND a.sid in (".implode(",",$sids).")";
		$sensor_where_ossim = " AND a.snort_sid in (".implode(",",$sids).")";
	}
	else {
		$sensor_where = " AND a.sid in (0)"; // Vacio
		$sensor_where_ossim = " AND a.snort_sid in (0)"; // Vacio
	}
}
session_write_close();

if (GET("type")=="alarms") {
	$color = '"#EFBE68"';
	$query = "select count(*) as num_events,p.name from ossim.alarm a,ossim.plugin_sid p WHERE p.plugin_id=a.plugin_id AND p.sid=a.plugin_sid $sensor_where_ossim group by p.name order by num_events desc limit 5";
} else {
	$color = '"#B5CF81"';
	$query = "select count(*) as num_events,p.name,p.plugin_id,p.sid from snort.acid_event a,ossim.plugin_sid p WHERE p.plugin_id=a.plugin_id AND p.sid=a.plugin_sid $sensor_where group by p.name order by num_events desc limit 5";
}

$values = $txts = $urls = "";

if (!$rs = & $conn->Execute($query)) {
    print $conn->ErrorMsg();
    exit();
}
while (!$rs->EOF) {
    $values .= $rs->fields["num_events"].",";
    $name = Util::signaturefilter($rs->fields["name"]);
    if (strlen($name)>35) $name=substr($name,0,35)."..";
    $txts .= "'".str_replace("'","\'",$name)."',";
    $urls .= (GET("type")=="alarms") ? str_replace("QQQ",$rs->fields["name"],$link) : str_replace("QQQ",$rs->fields["plugin_id"]."%3B".$rs->fields["sid"],$link);
    $rs->MoveNext();
}
$values = preg_replace("/,$/","",$values);
$txts = preg_replace("/,$/","",$txts);
$urls = preg_replace("/,$/","",$urls);
//
$db->close($conn);
$db->close($conn2);

//
?>  
	<script class="code" type="text/javascript">
	
		var links = [<?=$urls?>];

		function myClickHandler(ev, gridpos, datapos, neighbor, plot) {
            //mouseX = ev.pageX; mouseY = ev.pageY;
            url = links[neighbor.pointIndex];
            if (neighbor.seriesIndex==1) url = '../incidents/index.php?status=&hmenu=Tickets&smenu=Tickets';
            if (typeof(url)!='undefined' && url!='') top.frames['main'].location.href = url;
        }
        var isShowing = -1;
		function myMoveHandler(ev, gridpos, datapos, neighbor, plot) {
			if (neighbor == null) {
	            $('#myToolTip').hide().empty();
	            isShowing = -1;
	        }
	        if (neighbor != null) {
	        	if (neighbor.pointIndex!=isShowing) {
	            	$('#myToolTip').html(neighbor.data[1]).css({left:gridpos.x, top:gridpos.y-5}).show();
	            	isShowing = neighbor.pointIndex
	            }
	        }
        }
        
		$(document).ready(function(){
			
			$.jqplot.config.enablePlugins = true;		
			$.jqplot.eventListenerHooks.push(['jqplotClick', myClickHandler]); 
			$.jqplot.eventListenerHooks.push(['jqplotMouseMove', myMoveHandler]);
						
			line1 = [<?=$values?>];
			
			plot1 = $.jqplot('chart', [line1], {
			    legend:{show:false},
			    series:[
			        { pointLabels:{ show: false }, renderer:$.jqplot.BarRenderer }, 
			    ],                                    
			    grid: { background: '#F5F5F5', shadow: false },
			    seriesColors: [ <?=$color?> ],
				axesDefaults: {
				      tickRenderer: $.jqplot.CanvasAxisTickRenderer ,
				      tickOptions: {
				        angle: 20,
				        fontSize: '12px'
				      }
				},	    
			    axes:{
			        xaxis:{
			        	renderer:$.jqplot.CategoryAxisRenderer,
			        	ticks:[<?=$txts?>]
			        }, 
			        yaxis:{min:0, tickOptions:{formatString:'%d'}}
			    }
			});
			
			$('#chart').append('<div id="myToolTip"></div>');

		});
	</script>

    
    
  </head>
	<body style="overflow:hidden" scroll="no">
		<div id="chart" style="width:100%; height: 290px;"></div>
	</body>
</html>
