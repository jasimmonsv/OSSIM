<?php
require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
require_once 'classes/Util.inc';
Session::logcheck("MenuControlPanel", "ControlPanelExecutive");

require_once 'ossim_db.inc';
$db = new ossim_db();
$conn = $db->connect();
$data = "";
$urls = "";
//$colors = '"#EFBE68", "#B5CF81"';
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

	// Top 10 Events by Product Type - Last Week
	case "source_type":
		$sqlgraph = "select sum(sig_cnt) as num_events,p.source_type from snort.ac_alerts_signature a,ossim.plugin p where p.id=a.plugin_id AND a.day BETWEEN '".date("Y-m-d",time()-604800)."' AND '".date("Y-m-d")."' group by p.source_type order by num_events desc LIMIT 10";
		if (!$rg = & $conn->Execute($sqlgraph)) {
		    print $conn->ErrorMsg();
		} else {
		    while (!$rg->EOF) {
		    	if ($rg->fields["source_type"]=="") $rg->fields["source_type"] = _("Unknown type");
		        $data .= "['".$rg->fields["source_type"]."',".$rg->fields["num_events"]."],";
		        $rg->MoveNext();
		    }
		}
		$colors = '"#D1E8EF","#ADD8E6","#6FE7FF","#00BFFF","#4169E1","#4682B4","#0000CD","#483D8B","#5355DF","#00008B"';
		break;
		
		
	// Top 10 Event Categories - Last Week
	case "category":
		$sqlgraph = "select sum(sig_cnt) as num_events,p.category_id,c.name from snort.ac_alerts_signature a,ossim.plugin_sid p LEFT JOIN ossim.category c ON c.id=p.category_id where p.plugin_id=a.plugin_id AND p.sid=a.plugin_sid AND a.day BETWEEN '".date("Y-m-d",time()-604800)."' AND '".date("Y-m-d")."' group by p.category_id order by num_events desc LIMIT 10";
		if (!$rg = & $conn->Execute($sqlgraph)) {
		    print $conn->ErrorMsg();
		} else {
		    while (!$rg->EOF) {
		    	if ($rg->fields["name"]=="") $rg->fields["name"] = _("Unknown category");
		        $data .= "['".$rg->fields["name"]."',".$rg->fields["num_events"]."],";
		        $rg->MoveNext();
		    }
		}
		$colors = '"#FFD0BF","#FFBFBF","#FF9F9F","#F08080","#FF6347","#FF4500","#FF0000","#DC143C","#B22222","#7F1717"';
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
		        $rg->MoveNext();
		    }
		}
		$colors = '"#E9967A","#9BC3CF"';
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
		        $rg->MoveNext();
		    }
		}
		$colors = '"#E9967A","#E97A7A","#9BC3CF","#9C9BCF"';
		break;        

    // Antivirus - Last Week
	case "virus":
		$taxonomy = make_where($conn,array("Antivirus" => array("Virus_Detected")));
		$sqlgraph = "select count(a.sid) as num_events,inet_ntoa(a.ip_src) as name from snort.acid_event a,ossim.plugin_sid p LEFT JOIN ossim.subcategory c ON c.cat_id=p.category_id AND c.id=p.subcategory_id WHERE p.plugin_id=a.plugin_id AND p.sid=a.plugin_sid AND a.timestamp BETWEEN '".date("Y-m-d H:i:s",time()-604800)."' AND '".date("Y-m-d H:i:s")."' $taxonomy group by a.ip_src order by num_events desc limit 10";
		//print_r($sqlgraph);
		if (!$rg = & $conn->Execute($sqlgraph)) {
		    print $conn->ErrorMsg();
		} else {
		    while (!$rg->EOF) {
		    	if ($rg->fields["name"]=="") $rg->fields["name"] = _("Unknown category");
		        $data .= "['".str_replace("_"," ",$rg->fields["name"])."',".$rg->fields["num_events"]."],";
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
		        $rg->MoveNext();
		    }
		}
		$colors = '';
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
		        $rg->MoveNext();
		    }
		}
		$colors = '';
		break;
        
	default:
		// ['Sony',7], ['Samsumg',13.3], ['LG',14.7], ['Vizio',5.2], ['Insignia', 1.2]
		$data = "['"._("Unknown Type")."', 100]";
}
$data = preg_replace("/,$/","",$data);
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
	  <!--[if IE]><script language="javascript" type="text/javascript" src="jqplot/excanvas.js"></script><![endif]-->
	  
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
	
		var links = [<?=$urls?>];
		
		$(document).ready(function(){
					
			$.jqplot.config.enablePlugins = true;
			$.jqplot.eventListenerHooks.push(['jqplotClick', myClickHandler]); 
			
			s1 = [<?=$data?>];

			plot1 = $.jqplot('chart', [s1], {
				grid: {
					drawBorder: false, 
					drawGridlines: false,
					background: '#ffffff',
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

			function myClickHandler(ev, gridpos, datapos, neighbor, plot) {
	            //mouseX = ev.pageX; mouseY = ev.pageY;
	            url = links[neighbor.pointIndex];
	            if (typeof(url)!='undefined' && url!='') top.document.frames['main'].location.href = url;
            }
    
		});
	</script>

    
  </head>
	<body style="overflow:hidden">
		<div id="chart" style="width:100%; height: 250px;"></div>
	</body>
</html>

