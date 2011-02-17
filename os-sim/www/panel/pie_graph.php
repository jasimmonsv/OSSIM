<?php
require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
require_once ('classes/Util.inc');
require_once 'sensor_filter.php';
Session::logcheck("MenuControlPanel", "ControlPanelExecutive");

require_once 'ossim_db.inc';
$db = new ossim_db();
$conn = $db->connect();
$data = "";
$urls = "";
$colors = '"#E9967A","#9BC3CF"';

$range =  604800; // Week
$h = 250; // Graph Height
$forensic_link = "../forensics/base_qry_main.php?clear_allcriteria=1&time_range=week&time[0][0]=+&time[0][1]=>%3D&time[0][2]=".gmdate("m",$timetz-$range)."&time[0][3]=".gmdate("d",$timetz-$range)."&time[0][4]=".gmdate("Y",$timetz-$range)."&time[0][5]=&time[0][6]=&time[0][7]=&time[0][8]=+&time[0][9]=+&submit=Query+DB&num_result_rows=-1&time_cnt=1&sort_order=time_d&hmenu=Forensics&smenu=Forensics";

$sensor_where = make_sensor_filter($conn,"a");
$query = "select count(a.sid) as num_events,c.cat_id,c.id,c.name from snort.acid_event a,ossim.plugin_sid p,ossim.subcategory c WHERE c.id=p.subcategory_id AND p.plugin_id=a.plugin_id AND p.sid=a.plugin_sid AND a.timestamp BETWEEN '".gmdate("Y-m-d 00:00:00",gmdate("U")-$range)."' AND '".gmdate("Y-m-d 23:59:59")."' $sensor_where TAXONOMY group by c.id,c.name order by num_events desc LIMIT 10";

switch(GET("type")) {

	// Top 10 Events by Product Type - Last Week
	case "source_type":
		$sqlgraph = "select count(a.sid) as num_events,p.source_type from snort.acid_event a,ossim.plugin p where p.id=a.plugin_id AND a.timestamp BETWEEN '".gmdate("Y-m-d 00:00:00",gmdate("U")-$range)."' AND '".gmdate("Y-m-d 23:59:59")."' $sensor_where group by p.source_type order by num_events desc LIMIT 10";
		if (!$rg = & $conn->Execute($sqlgraph)) {
		    print $conn->ErrorMsg();
		} else {
		    while (!$rg->EOF) {
		    	if ($rg->fields["source_type"]=="") $rg->fields["source_type"] = _("Unknown type");
		        $data .= "['".$rg->fields["source_type"]."',".$rg->fields["num_events"]."],";
                $urls .= "'".$forensic_link."&sourcetype=".urlencode($rg->fields["source_type"])."',";
		        $rg->MoveNext();
		    }
		}
		$colors = '"#D1E8EF","#ADD8E6","#6FE7FF","#00BFFF","#4169E1","#4682B4","#0000CD","#483D8B","#5355DF","#00008B"';
		break;
		
		
	// Top 10 Event Categories - Last Week
	case "category":
		$sqlgraph = "select count(a.sid) as num_events,p.category_id,c.name from snort.acid_event a,ossim.plugin_sid p,ossim.category c WHERE c.id=p.category_id AND p.plugin_id=a.plugin_id AND p.sid=a.plugin_sid AND a.timestamp BETWEEN '".gmdate("Y-m-d 00:00:00",gmdate("U")-$range)."' AND '".gmdate("Y-m-d 23:59:59")."' $sensor_where group by p.category_id order by num_events desc LIMIT 10";
		if (!$rg = & $conn->Execute($sqlgraph)) {
		    print $conn->ErrorMsg();
		} else {
		    while (!$rg->EOF) {
		    	if ($rg->fields["name"]=="") $rg->fields["name"] = _("Unknown category");
		        $data .= "['".str_replace("_"," ",$rg->fields["name"])."',".$rg->fields["num_events"]."],";
                $urls .= "'".$forensic_link."&category%5B1%5D=&category%5B0%5D=".$rg->fields["category_id"]."',";
		        $rg->MoveNext();
		    }
		}
		$colors = '"#FFD0BF","#FFBFBF","#FF9F9F","#F08080","#FF6347","#FF4500","#FF0000","#DC143C","#B22222","#7F1717"';
		break;
				
	// Top 10 Ossec Categories - Last Week
	case "hids":
		require_once("classes/Plugin.inc");
		$oss_p_id_name = Plugin::get_id_and_name($conn, "WHERE name LIKE 'ossec%'");
		$plugins = implode(",",array_flip ($oss_p_id_name));
		$sqlgraph = "select count(a.sid) as num_events,p.id,p.name from snort.acid_event a,ossim.plugin p WHERE p.id=a.plugin_id AND a.timestamp BETWEEN '".gmdate("Y-m-d 00:00:00",gmdate("U")-$range)."' AND '".gmdate("Y-m-d 23:59:59")."' AND a.plugin_id in ($plugins) $sensor_where group by p.name order by num_events desc LIMIT 8";
		if (!$rg = & $conn->Execute($sqlgraph)) {
		    print $conn->ErrorMsg();
		} else {
		    while (!$rg->EOF) {
		    	$name = ucwords(str_replace("_"," ",str_replace("ossec-","ossec: ",$rg->fields["name"])));
		        $data .= "['".$name."',".$rg->fields["num_events"]."],";
                $urls .= "'".$forensic_link."&plugin=".$rg->fields["id"]."',";
		        $rg->MoveNext();
		    }
		}
		$colors = '"#FFD0BF","#FFBFBF","#FF9F9F","#F08080","#FF6347","#FF4500","#FF0000","#DC143C","#B22222","#7F1717"';
		$h = 220;
		break;
						
	// Authentication Login vs Failed Login Events - Last Week
	case "login":
		$taxonomy = make_where($conn,array("Authentication" => array("Login","Failed")));
		$sqlgraph = str_replace("TAXONOMY",$taxonomy,$query);
		//print_r($sqlgraph);
		if (!$rg = & $conn->Execute($sqlgraph)) {
		    print $conn->ErrorMsg();
		} else {
		    while (!$rg->EOF) {
		    	if ($rg->fields["name"]=="") $rg->fields["name"] = _("Unknown category");
		        $data .= "['".str_replace("_"," ",$rg->fields["name"])."',".$rg->fields["num_events"]."],";
                $urls .= "'".$forensic_link."&category%5B0%5D=".$rg->fields["cat_id"]."&category%5B1%5D=".$rg->fields["id"]."',";
		        $rg->MoveNext();
		    }
		}
		$colors = '"#E9967A","#9BC3CF"';
		break;
		
	// Malware - Last Week
	case "malware":
		$taxonomy = make_where($conn,array("Malware" => array("Spyware","Adware","Fake_Antivirus","KeyLogger","Trojan","Virus","Worm","Generic","Backdoor")));
		$sqlgraph = str_replace("TAXONOMY",$taxonomy,$query);
		//print_r($sqlgraph);
		if (!$rg = & $conn->Execute($sqlgraph)) {
		    print $conn->ErrorMsg();
		} else {
		    while (!$rg->EOF) {
		    	if ($rg->fields["name"]=="") $rg->fields["name"] = _("Unknown category");
		        $data .= "['".str_replace("_"," ",$rg->fields["name"])."',".$rg->fields["num_events"]."],";
                $urls .= "'".$forensic_link."&category%5B0%5D=".$rg->fields["cat_id"]."&category%5B1%5D=".$rg->fields["id"]."',";
		        $rg->MoveNext();
		    }
		}
		$colors = '"#FFD0BF","#FFBFBF","#FF9F9F","#F08080","#FF6347","#FF4500","#FF0000","#DC143C","#B22222","#7F1717"';
		break;        

    // Firewall permit vs deny - Last Week
	case "firewall":
		$taxonomy = make_where($conn,array("Access" => array("Firewall_Permit","Firewall_Deny","ACL_Permit","ACL_Deny")));
		$sqlgraph = str_replace("TAXONOMY",$taxonomy,$query);
		//print_r($sqlgraph);
		if (!$rg = & $conn->Execute($sqlgraph)) {
		    print $conn->ErrorMsg();
		} else {
		    while (!$rg->EOF) {
		    	if ($rg->fields["name"]=="") $rg->fields["name"] = _("Unknown category");
		        $data .= "['".str_replace("_"," ",$rg->fields["name"])."',".$rg->fields["num_events"]."],";
                $urls .= "'".$forensic_link."&category%5B0%5D=".$rg->fields["cat_id"]."&category%5B1%5D=".$rg->fields["id"]."',";
		        $rg->MoveNext();
		    }
		}
		$colors = '"#E9967A","#E97A7A","#9BC3CF","#9C9BCF"';
		break;        

    // Antivirus - Last Week
	case "virus":
		$taxonomy = make_where($conn,array("Antivirus" => array("Virus_Detected")));
		$sqlgraph = "select count(a.sid) as num_events,inet_ntoa(a.ip_src) as name from snort.acid_event a,ossim.plugin_sid p LEFT JOIN ossim.subcategory c ON c.cat_id=p.category_id AND c.id=p.subcategory_id WHERE p.plugin_id=a.plugin_id AND p.sid=a.plugin_sid AND a.timestamp BETWEEN '".gmdate("Y-m-d H:i:s",gmdate("U")-$range)."' AND '".gmdate("Y-m-d H:i:s")."' $taxonomy group by a.ip_src order by num_events desc limit 10";
		//print_r($sqlgraph);
		if (!$rg = & $conn->Execute($sqlgraph)) {
		    print $conn->ErrorMsg();
		} else {
		    while (!$rg->EOF) {
		    	if ($rg->fields["name"]=="") $rg->fields["name"] = _("Unknown category");
		        $data .= "['".str_replace("_"," ",$rg->fields["name"])."',".$rg->fields["num_events"]."],";
                $urls .= "'".$forensic_link."&category%5B0%5D=".$rg->fields["cat_id"]."&category%5B1%5D=".$rg->fields["id"]."',";
		        $rg->MoveNext();
		    }
		}
		$colors = '';
		break;
        
    // Exploits by type - Last Week
	case "exploits":
		$taxonomy = make_where($conn,array("Exploits" => array("Shellcode","SQL_Injection","Browser","ActiveX","Command_Execution","Cross_Site_Scripting","FTP","File_Inclusion","Windows","Directory_Traversal","Attack_Response","Denial_Of_Service","PDF","Buffer_Overflow","Spoofing","Format_String","Misc","DNS","Mail","Samba","Linux")));
		$sqlgraph = str_replace("TAXONOMY",$taxonomy,$query);
		//print_r($sqlgraph);
		if (!$rg = & $conn->Execute($sqlgraph)) {
		    print $conn->ErrorMsg();
		} else {
		    while (!$rg->EOF) {
		    	if ($rg->fields["name"]=="") $rg->fields["name"] = _("Unknown category");
		        $data .= "['".str_replace("_"," ",$rg->fields["name"])."',".$rg->fields["num_events"]."],";
                $urls .= "'".$forensic_link."&category%5B0%5D=".$rg->fields["cat_id"]."&category%5B1%5D=".$rg->fields["id"]."',";
		        $rg->MoveNext();
		    }
		}
		$colors = '"#D1E8EF","#ADD8E6","#6FE7FF","#00BFFF","#4169E1","#4682B4","#0000CD","#483D8B","#5355DF","#00008B"';
		break;

    // System status - Last Week
	case "system":
		$taxonomy = make_where($conn,array("System" => array("Warning","Emergency","Critical","Error","Notification","Information","Debug","Alert")));
		$sqlgraph = str_replace("TAXONOMY",$taxonomy,$query);
		//print_r($sqlgraph);
		if (!$rg = & $conn->Execute($sqlgraph)) {
		    print $conn->ErrorMsg();
		} else {
		    while (!$rg->EOF) {
		    	if ($rg->fields["name"]=="") $rg->fields["name"] = _("Unknown category");
		        $data .= "['".str_replace("_"," ",$rg->fields["name"])."',".$rg->fields["num_events"]."],";
                $urls .= "'".$forensic_link."&category%5B0%5D=".$rg->fields["cat_id"]."&category%5B1%5D=".$rg->fields["id"]."',";
		        $rg->MoveNext();
		    }
		}
		$colors = '"#FFFBCF","#EEE8AA","#F0E68C","#FFD700","#FF8C00","#DAA520","#D2691E","#B8860B","#7F631F"';
		break;
        
	default:
		// ['Sony',7], ['Samsumg',13.3], ['LG',14.7], ['Vizio',5.2], ['Insignia', 1.2]
		$data = "['"._("Unknown Type")."', 100]";
}
$data = preg_replace("/,$/","",$data);
$urls = preg_replace("/,$/","",$urls);
if ($data=="") {
	$data = "['"._("No events")."', 100]";
}
$db->close($conn);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
<head>
	  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	  <title>Pie Charts</title>
	  <!--[if IE]><script language="javascript" type="text/javascript" src="../js/jqplot/excanvas.js"></script><![endif]-->
	  
	  <link rel="stylesheet" type="text/css" href="../js/jqplot/jquery.jqplot.css" />
		
	  <!-- BEGIN: load jquery -->
	  <script language="javascript" type="text/javascript" src="../js/jqplot/jquery-1.4.2.min.js"></script>
	  <!-- END: load jquery -->
	  
	  <!-- BEGIN: load jqplot -->
	  <script language="javascript" type="text/javascript" src="../js/jqplot/jquery.jqplot.min.js"></script>
	  <script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.pieRenderer.js"></script>
	  	 
  <!-- END: load jqplot -->

	<style type="text/css">
		
		#chart .jqplot-point-label {
		  border: 1.5px solid #aaaaaa;
		  padding: 1px 3px;
		  background-color: #eeccdd;
		}

	</style>
    
	<script class="code" type="text/javascript">
	
		var links     = [<?=$urls?>];
		var isShowing = -1;

		function myClickHandler(ev, gridpos, datapos, neighbor, plot) {
            //mouseX = ev.pageX; mouseY = ev.pageY;
            url = links[neighbor.pointIndex];
            if (typeof(url)!='undefined' && url!='') top.frames['main'].location.href = url;
        }
        
				
		function myMoveHandler(ev, gridpos, datapos, neighbor, plot) {
			if (neighbor == null) {
	            $('#myToolTip').hide().empty();
	            isShowing = -1;
	        }
	        if (neighbor != null) {
	        	if ( neighbor.pointIndex != isShowing ) {
	            	$('#myToolTip').html(neighbor.data[0]).css({left:gridpos.x, top:gridpos.y-5}).show();
	            	isShowing = neighbor.pointIndex;
	            }
	        }
        }
            		
		$(document).ready(function(){
					
			$.jqplot.config.enablePlugins = true;
			$.jqplot.eventListenerHooks.push(['jqplotClick', myClickHandler]); 
			$.jqplot.eventListenerHooks.push(['jqplotMouseMove', myMoveHandler]);
			
			s1 = [<?=$data?>];

			plot1 = $.jqplot('chart', [s1], {
				grid: {
					drawBorder: false, 
					drawGridlines: false,
					background: 'rgba(255, 255, 255, 0)',
					shadow:false
				},
				<? if ($colors!="") { ?>seriesColors: [ <?=$colors?> ], <? } ?>
				axesDefaults: {
					
				},
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
			
			$('#chart').append('<div id="myToolTip"></div>');
    
		});
	</script>

    
  </head>
	<body style="overflow:hidden" scroll="no">
		<div id="chart" style="width:100%; height:<?=$h?>px;"></div>
	</body>
</html>

