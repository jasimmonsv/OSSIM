<?php
require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
require_once 'classes/Util.inc';
Session::logcheck("MenuControlPanel", "ControlPanelExecutive");

require_once 'ossim_db.inc';
$db = new ossim_db();
$conn = $db->connect();
$ips = "";
$values = "";
$colors = '"#E9967A","#9BC3CF"';

function make_where ($conn,$arr) {
	include_once("../report/plugin_filters.php");
	$w = "";
	foreach ($arr as $cat => $scs) {
		$id = GetPluginCategoryID($cat,$conn);
		$w .= "(c.cat_id=$id"; 
		$ids = array();
		foreach ($scs as $scat) {
			$ids[] = GetPluginSubCategoryID($scat,$id,$conn);
		}
		if (count($ids)>0) $w .= " AND c.id in (".implode(",",$ids).")";
		$w .= ") OR ";
	}
	return ($w!="") ? "AND (".preg_replace("/ OR $/","",$w).")" : "";
}

$query = "select sum(sig_cnt) as num_events,c.id,c.name from snort.ac_alerts_signature a,ossim.plugin_sid p LEFT JOIN ossim.subcategory c ON c.cat_id=p.category_id AND c.id=p.subcategory_id WHERE p.plugin_id=a.plugin_id AND p.sid=a.plugin_sid AND a.day BETWEEN '".date("Y-m-d",time()-604800)."' AND '".date("Y-m-d")."' TAXONOMY group by c.id,c.name order by num_events desc LIMIT 10";

switch(GET("type")) {       

    // Antivirus - Last Week
	case "virus":
		$taxonomy = make_where($conn,array("Antivirus" => array("Virus_Detected")));
		$sqlgraph = "select count(a.sid) as num_events,inet_ntoa(a.ip_src) as name from snort.acid_event a,ossim.plugin_sid p LEFT JOIN ossim.subcategory c ON c.cat_id=p.category_id AND c.id=p.subcategory_id WHERE p.plugin_id=a.plugin_id AND p.sid=a.plugin_sid AND a.timestamp BETWEEN '".date("Y-m-d H:i:s",time()-604800)."' AND '".date("Y-m-d H:i:s")."' $taxonomy group by a.ip_src order by num_events desc limit 10";
		//print_r($sqlgraph);
		if (!$rg = & $conn->Execute($sqlgraph)) {
		    print $conn->ErrorMsg();
		} else {
			$i=1;
		    while (!$rg->EOF) {
		    	if ($rg->fields["name"]=="") $rg->fields["name"] = _("Unknown category");
		        $values .= "[".$rg->fields["num_events"].",$i],"; $i++;
		        $ips .= "'".str_replace("_"," ",$rg->fields["name"])."',";
		        $rg->MoveNext();
		    }
		}
		$colors = '"#F08080"';
		break;
        
        
	default:
		// ['Sony',7], ['Samsumg',13.3], ['LG',14.7], ['Vizio',5.2], ['Insignia', 1.2]
		$data = "['"._("Unknown Type")."', 100]";
}
$values = preg_replace("/,$/","",$values);
$ips = preg_replace("/,$/","",$ips);
if ($values=="") {
	$values = "0";
	$ips = _("No IPs found");
}
$db->close($conn);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
<head>
	  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	  <title>Bar Charts</title>
	  <!--[if IE]><script language="javascript" type="text/javascript" src="jqplot/excanvas.js"></script><![endif]-->
	  
	  <link rel="stylesheet" type="text/css" href="../js/jqplot/jquery.jqplot.css" />
		
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

	 
  <!-- END: load jqplot -->

	<style type="text/css">
		
		#chart .jqplot-point-label {
		  border: 1.5px solid #aaaaaa;
		  padding: 1px 3px;
		  background-color: #eeccdd;
		}

	</style>
    
	<script class="code" type="text/javascript">
	
		$(document).ready(function(){
			$.jqplot.config.enablePlugins = true;		
			
			line1 = [<?=$values?>];
			
			plot1 = $.jqplot('chart', [line1], {
			    legend:{show:false},
			    seriesDefaults:{
			        renderer:$.jqplot.BarRenderer, 
			        rendererOptions:{barDirection:'horizontal', barPadding:2, barMargin:2}, 
			        shadowAngle:135},
				series:[
			        { pointLabels:{ show: false }, renderer:$.jqplot.BarRenderer }
			    ],			        
			    <? if ($colors!="") { ?>seriesColors: [ <?=$colors?> ], <? } ?>                            
			    grid: { background: '#F5F5F5', shadow: false },
			    axes:{
			        yaxis:{
			        	renderer:$.jqplot.CategoryAxisRenderer,
			        	ticks:[<?=$ips?>]
			        }, 
			        xaxis:{min:0, tickOptions:{formatString:'%d'}}
			    }
			});

		});
		
	</script>

    
  </head>
	<body style="overflow:hidden">
		<div id="chart" style="width:100%; height: 250px;"></div>
	</body>
</html>
