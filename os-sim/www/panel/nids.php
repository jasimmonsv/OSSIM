<?php
require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
require_once 'classes/Util.inc';
require_once 'sensor_filter.php';
Session::logcheck("MenuEvents", "EventsForensics");

require_once 'ossim_db.inc';
$db = new ossim_db();
$conn = $db->connect();
$ips = $urls = "";
$values = "";
$colors = '"#E9967A","#9BC3CF"';

$db = new ossim_db();
$conn = $db->connect();
$conn2 = $db->snort_connect();

$sensor_where = make_sensor_filter($conn,"a");
$snort_where = "AND (a.plugin_id BETWEEN 1000 AND 1500)";
$forensic_link = "../forensics/base_qry_main.php?clear_allcriteria=1&time_range=all&submit=Query+DB&num_result_rows=-1&sort_order=time_d&hmenu=Forensics&smenu=Forensics";

// TOP NIDS EVENTS
$query = "select count(*) as num_events,p.name,a.plugin_id,a.plugin_sid from snort.acid_event a,ossim.plugin_sid p WHERE p.plugin_id=a.plugin_id AND p.sid=a.plugin_sid $snort_where  $sensor_where group by p.name order by num_events desc limit 10";
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
    $urls .= "'".$forensic_link."&sig_type=1&sig[0]=%3D&sig[1]=".$rs->fields["plugin_id"]."%3B".$rs->fields["plugin_sid"]."',";
    $rs->MoveNext();
}
$values = preg_replace("/,$/","",$values);
$txts = preg_replace("/,$/","",$txts);
$urls = preg_replace("/,$/","",$urls);

// TOP NIDS EVENT CATEGORIES
$query = "select count(a.sid) as num_events,p.category_id,c.name from snort.acid_event a,ossim.plugin_sid p LEFT JOIN ossim.category c ON c.id=p.category_id where p.plugin_id=a.plugin_id AND p.sid=a.plugin_sid $snort_where $sensor_where group by p.category_id order by num_events desc LIMIT 10";
$txts1 = $urls1 = "";
if (!$rs = & $conn->Execute($query)) {
    print $conn->ErrorMsg();
    exit();
}
while (!$rs->EOF) {
	if ($rs->fields["name"]=="") $rs->fields["name"] = _("Unknown category");
    $txts1 .= "['".str_replace("'","\'",$rs->fields["name"])."',".$rs->fields["num_events"]."],";
    $urls1 .= "'".$forensic_link."&category%5B1%5D=&category%5B0%5D=".$rs->fields["category_id"]."',";
    $rs->MoveNext();
}
$txts1 = preg_replace("/,$/","",$txts1);
$urls1 = preg_replace("/,$/","",$urls1);

// TOP NIDS Sources
$values2 = $txts2 = $urls2 = "";
$sqlgraph = "select count(a.sid) as num_events,inet_ntoa(a.ip_src) as name from snort.acid_event a,ossim.plugin_sid p LEFT JOIN ossim.subcategory c ON c.cat_id=p.category_id AND c.id=p.subcategory_id WHERE p.plugin_id=a.plugin_id AND p.sid=a.plugin_sid $snort_where $sensor_where group by a.ip_src order by num_events desc limit 10";
if (!$rg = & $conn->Execute($sqlgraph)) {
    print $conn->ErrorMsg();
} else {
	$i=1;
    while (!$rg->EOF) {
        $values2 .= "[".$rg->fields["num_events"].",$i],"; $i++;
        $txts2 .= "'".$rg->fields["name"]."',";
        $urls2 .= "'".$forensic_link."&ip_addr[0][0]=+&ip_addr[0][1]=ip_src&ip_addr[0][2]=%3D&ip_addr[0][3]=".$rg->fields["name"]."&ip_addr[0][8]=+&ip_addr[0][9]=+&ip_addr_cnt=1',";
        $rg->MoveNext();
    }
}
$urls2 = preg_replace("/,$/","",$urls2);

// TOP NIDS Destinations
$values3 = $txts3 = $urls3 = "";
$sqlgraph = "select count(a.sid) as num_events,inet_ntoa(a.ip_dst) as name from snort.acid_event a,ossim.plugin_sid p LEFT JOIN ossim.subcategory c ON c.cat_id=p.category_id AND c.id=p.subcategory_id WHERE p.plugin_id=a.plugin_id AND p.sid=a.plugin_sid $snort_where $sensor_where group by a.ip_dst order by num_events desc limit 10";
if (!$rg = & $conn->Execute($sqlgraph)) {
    print $conn->ErrorMsg();
} else {
	$i=1;
    while (!$rg->EOF) {
        $values3 .= "[".$rg->fields["num_events"].",$i],"; $i++;
        $txts3 .= "'".$rg->fields["name"]."',";
        $urls3 .= "'".$forensic_link."&ip_addr[0][0]=+&ip_addr[0][1]=ip_dst&ip_addr[0][2]=%3D&ip_addr[0][3]=".$rg->fields["name"]."&ip_addr[0][8]=+&ip_addr[0][9]=+&ip_addr_cnt=1',";
        $rg->MoveNext();
    }
}
$urls3 = preg_replace("/,$/","",$urls3);

$db->close($conn);
$db->close($conn2);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
<head>
	  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	  <title>Bar Charts</title>
	  <!--[if IE]><script language="javascript" type="text/javascript" src="../js/jqplot/excanvas.js"></script><![endif]-->

	  <link rel="stylesheet" type="text/css" href="../style/style.css" />	  
	  <link rel="stylesheet" type="text/css" href="../js/jqplot/jquery.jqplot.css" />
		
	  <!-- BEGIN: load jquery -->
	  <script language="javascript" type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	  <!-- END: load jquery -->
	  
	  <!-- BEGIN: load jqplot -->
	  <script language="javascript" type="text/javascript" src="../js/jqplot/jquery.jqplot.min.js"></script>
	  <script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.barRenderer.js"></script>
	  <script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.categoryAxisRenderer.min.js"></script>
	  <script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.pointLabels.min.js"></script>
	  <script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.canvasTextRenderer.js"></script>
	  <script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.canvasAxisTickRenderer.js"></script>  
	  <script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.pieRenderer.js"></script>
	 
  <!-- END: load jqplot -->

	<style type="text/css">
		
		#chart .jqplot-point-label {
		  border: 1.5px solid #aaaaaa;
		  padding: 1px 3px;
		  background-color: #eeccdd;
		}
		.jqplot-target { font-size:14px; text-align:left; }
		.jqplot-table-legend { border:0px none; text-align:left; font-size:14px; }

	</style>
    
	<script class="code" type="text/javascript">
	
		var links = [<?=$urls?>];
		var links1 = [<?=$urls1?>];
		var links2 = [<?=$urls2?>];
		var links3 = [<?=$urls3?>];

		function myClickHandler(ev, gridpos, datapos, neighbor, plot) {
            //mouseX = ev.pageX; mouseY = ev.pageY;
            if (plot.targetId.match(/chart1/)) {
            	url = links1[neighbor.pointIndex];
            } else if (plot.targetId.match(/chart2/)) {
            	url = links2[neighbor.pointIndex];
            } else if (plot.targetId.match(/chart3/)) {
            	url = links3[neighbor.pointIndex];
            } else {
            	url = links[neighbor.pointIndex];
            }
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
	        		var x = ev.pageX-gridpos.x, y = ev.pageY-gridpos.y-5;
	            	$('#myToolTip').html(neighbor.data[0]).css({left:x, top:y}).show();
	            	isShowing = neighbor.pointIndex
	            }
	        }
        }
        
		$(document).ready(function(){
		
			$.jqplot.config.enablePlugins = true;		
			$.jqplot.eventListenerHooks.push(['jqplotClick', myClickHandler]); 
			//$.jqplot.eventListenerHooks.push(['jqplotMouseMove', myMoveHandler]);
			
			s1 = [<?=$values?>];
			
			plot = $.jqplot('chart', [s1], {
			    legend:{show:false},
			    series:[
			        { pointLabels:{ show: false }, renderer:$.jqplot.BarRenderer }, 
			    ],                                    
			    grid: { background: '#F5F5F5', shadow: false },
			    seriesColors: [ "#B5CF81" ],
				axesDefaults: {
				      tickRenderer: $.jqplot.CanvasAxisTickRenderer ,
				      tickOptions: {
				        angle: 25,
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
            
            s2 = [<?=$txts1?>];
            
			plot1 = $.jqplot('chart1', [s2], {
				grid: {
					drawBorder: false, 
					drawGridlines: false,
					background: 'transparent',
					shadow:false
				},
				seriesColors: [ "#FFD0BF","#FFBFBF","#FF9F9F","#F08080","#FF6347","#FF4500","#FF0000","#DC143C","#B22222","#7F1717" ],
				seriesDefaults:{
                    padding:14,
					renderer:$.jqplot.PieRenderer,
					rendererOptions: {
						showDataLabels: true,
                        dataLabelFormatString: '%d'
					}				
				},
				legend: {
					show: true,
					rendererOptions: {
						numberCols: 1
					},
					location: 'w'
				}
			}); 
			            
			s3 = [<?=$values2?>];
			
			plot2 = $.jqplot('chart2', [s3], {
			    legend:{show:false},
			    seriesDefaults:{
			        renderer:$.jqplot.BarRenderer, 
			        rendererOptions:{barDirection:'horizontal', barPadding:2, barMargin:2}, 
			        shadowAngle:135
			    },
				series:[
			        { pointLabels:{ show: false }, shadow: false, renderer:$.jqplot.BarRenderer }
			    ],			        
			    seriesColors: [ "#8EC336" ],                            
			    grid: { background: '#F5F5F5', shadow: false },
			    axes:{
			        yaxis:{
			        	renderer:$.jqplot.CategoryAxisRenderer,
			        	ticks:[<?=$txts2?>]
			        }, 
			        xaxis:{min:0, tickOptions:{formatString:'%d'}}
			    }
			});
			
			s4 = [<?=$values3?>];
			
			plot3 = $.jqplot('chart3', [s4], {
			    legend:{show:false},
			    seriesDefaults:{
			        renderer:$.jqplot.BarRenderer, 
			        rendererOptions:{barDirection:'horizontal', barPadding:2, barMargin:2}, 
			        shadowAngle:135
			    },
				series:[
			        { pointLabels:{ show: false }, shadow: false, renderer:$.jqplot.BarRenderer }
			    ],			        
			    seriesColors: [ "#80BEF0" ],                            
			    grid: { background: '#F5F5F5', shadow: false },
			    axes:{
			        yaxis:{
			        	renderer:$.jqplot.CategoryAxisRenderer,
			        	ticks:[<?=$txts3?>]
			        }, 
			        xaxis:{min:0, tickOptions:{formatString:'%d'}}
			    }
			});
						
            $('#chart').append('<div id="myToolTip"></div>');

		});
		
	</script>

    
  </head>
	<body style="overflow-y:scroll">
	<?php include("../hmenu.php"); ?>
		
	<br/>
	
	<table border="0" cellpadding="0" cellspacing="0" align="center" class="noborder">
	<tr>
	<td valign="top" class="noborder">

		<table border="0" cellpadding="0" cellspacing="2" align="center">
		<tr><th style="font-size:12px"><?=_("Top 10 NIDS Events")?></th></tr>
		<tr>
			<td style="width:470px;padding:10px" class="noborder">
				<div id="chart" style="width:100%; height:260px;"></div>
			</td>			
		</tr>
		</table>

	</td>
	<td width="20" class="noborder"></td>
	<td valign="top" class="noborder">

		<table border="0" cellpadding="0" cellspacing="2" align="center">
		<tr><th style="font-size:12px"><?=_("Top 10 NIDS Event Categories")?></th></tr>
		<tr>
			<td style="width:470px;padding:10px" class="noborder">
				<div id="chart1" style="width:100%; height:260px;"></div>
			</td>			
		</tr>
		</table>

	</td>
	</tr>
	<tr><td height="20" colspan="3" class="noborder"></td></tr>
	<tr>
	<td valign="top" class="noborder">

		<table border="0" cellpadding="0" cellspacing="2" align="center">
		<tr><th style="font-size:12px"><?=_("Top 10 NIDS Sources")?></th></tr>
		<tr>
			<td style="width:470px;padding:10px" class="noborder">
				<div id="chart2" style="width:100%; height:260px;"></div>
			</td>			
		</tr>
		</table>

	</td>
	<td width="20" class="noborder"></td>
	<td valign="top" class="noborder">

		<table border="0" cellpadding="0" cellspacing="2" align="center">
		<tr><th style="font-size:12px"><?=_("Top 10 NIDS Destinations")?></th></tr>
		<tr>
			<td style="width:470px;padding:10px" class="noborder">
				<div id="chart3" style="width:100%; height:260px;"></div>
			</td>			
		</tr>
		</table>

	</td>
	</tr>	
	</table>
		
	</body>
</html>
