<?php
/*****************************************************************************
*
*    License:
*
*   Copyright (c) 2003-2006 ossim.net
*   Copyright (c) 2007-2009 AlienVault
*   All rights reserved.
*
*   This package is free software; you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation; version 2 dated June, 1991.
*   You may not use, modify or distribute this program under any other version
*   of the GNU General Public License.
*
*   This package is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this package; if not, write to the Free Software
*   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
*   MA  02110-1301  USA
*
*
* On Debian GNU/Linux systems, the complete text of the GNU General
* Public License can be found in `/usr/share/common-licenses/GPL-2'.
*
* Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
****************************************************************************/
/**
* Class and Function List:
* Function list:
* - match_os()
* Classes list:
*/

require_once ('classes/Session.inc');
require_once ('utils.php');


$m_perms  = array ("MenuEvents", "MenuEvents");
$sm_perms = array ("EventsHids", "EventsHidsConfig");

if ( !Session::menu_perms($m_perms, $sm_perms) )
	Session::unallowed_section(null, 'noback',$m_perms[0], $sm_perms[0]);


$tz     =(isset($_SESSION["_timezone"])) ? intval($_SESSION["_timezone"]) : intval(date("O"))/100;
$timetz = gmdate("U")+(3600*$tz); // time to generate dates with timezone correction


function plot_graphic($id, $height, $width, $xaxis, $yaxis, $xticks, $xlabel, $display = false, $lnk = "") {
	
    $min_h = min($yaxis);
	$max_h = max($yaxis);
	
	$max_h = ( $min_h == $max_h && $max_h === 0) ? $max_h+1 : $max_h;
	
	
	$plot = '<script language="javascript" type="text/javascript">';
    $plot.= '$(document).ready( function() {';
    $plot.= 'var options = { ';
    $plot.= 'lines: { show:true, labelHeight:0, lineWidth: 0.7},';
    $plot.= 'points: { show:false, radius: 2 }, legend: { show: false },';
    $plot.= 'yaxis: { ticks:[], min:'.$min_h.', max:'.$max_h.' }, xaxis: { tickDecimals:0, ticks: [';
    
	if (sizeof($xticks) > 0) {
        foreach($xticks as $k => $v) {
            $plot.= '[' . $v . ',"' . $xlabel[$k] . '"],';
		}
        $plot = preg_replace("/\,$/", "", $plot);
    }
	
    $plot.= ']},';
    $plot.= 'grid: { color: "#8E8E8E", labelMargin:0, backgroundColor: "#EDEDED", tickColor: "#D2D2D2", hoverable:true, clickable:true}';
    $plot.= ', shadowSize:1 };';
    $plot.= 'var data = [{';
    $plot.= 'color: "rgb(170,170,170)", label: "Events", ';
    $plot.= 'lines: { show: true, fill: true},'; 
    $plot.= 'data:[';
	
	foreach($xaxis as $k => $v) {
        $plot.= '[' . $v . ',' . $yaxis[$k] . '],';
    }
	
    $plot = preg_replace("/\,$/", "]", $plot);
    $plot.= ' }];';
    $plot.= 'var plotarea = $("#' . $id . '");';
    if ($display == true) {
        $plot.= 'plotarea.css("display", "");';
        $width = '((window.innerWidth || document.body.clientWidth)/2)';
    }
    $plot.= 'plotarea.css("height", ' . $height . ');';
    $plot.= 'plotarea.css("width", ' . $width . ');';
    $plot.= '$.plot( plotarea , data, options );';
    //if ($display==true) {
    $plot.= 'var previousPoint = null;
			$("#' . $id . '").bind("plothover", function (event, pos, item) {
				if (item) {
					if (previousPoint != item.datapoint) {
						previousPoint = item.datapoint;
						$("#tooltip").remove();
						var x = item.datapoint[0].toFixed(0), y = formatNmb(item.datapoint[1].toFixed(0));
						showTooltip(item.pageX, item.pageY, y + " " + item.series.label);
					}
				}
				else 
				{
					$("#tooltip").remove();
					previousPoint = null;
				}
			});';
    	
    $plot.= "});\n";
    $plot.= '</script>';
    return $plot;
}




?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<link rel="stylesheet" type="text/css" href="../style/ossim_style.css"/>
	<link rel="stylesheet" type="text/css" href="css/ossec.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery.flot.pie.js"></script>
	<script src="../js/jquery.simpletip.js" type="text/javascript"></script>
	
	
	<script type="text/javascript">
			
		<?php 
		function thousands_locale() {
			$locale    = ( isset($_COOKIE['locale']) ? $_COOKIE['locale'] : $_SERVER['HTTP_ACCEPT_LANGUAGE'] );
			$languages = explode(",",$locale);
			switch($languages[0]) {
				case 'es-es':
				case 'de-de':
				case 'es-mx':
					$thousands = '.';
				break;
				default:
					$thousands = ',';
			}
			return $thousands;
		}
		?>
		
		function formatNmb(nNmb){
			var sRes = ""; 
			for (var j, i = nNmb.length - 1, j = 0; i >= 0; i--, j++)
				sRes = nNmb.charAt(i) + ((j > 0) && (j % 3 == 0)? "<?php echo thousands_locale()?>": "") + sRes;
			return sRes;
		}
		
		function showTooltip(x, y, contents) {
			
			$('<div id="tooltip" class="tooltipLabel"><span style="font-size:10px;">' + contents + '</span></div>').css( {
				position: 'absolute',
				display: 'none',
				top: y - 28,
				left: x - 10,
				border: '1px solid #ADDF53',
				padding: '1px 2px 1px 2px',
				'background-color': '#CFEF95',
				opacity: 0.80
			}).appendTo("body").fadeIn(200);
		}
		
		function show_agent(id)
		{
			if ( $("#"+id).hasClass("visible") )
			{
				$("#"+id).show();
				$("#"+id).removeClass("visible");
				$("#"+id).addClass("no_visible");
			}
			else
			{
				$("#"+id).hide();
				$("#"+id).removeClass("no_visible");
				$("#"+id).addClass("visible");
			}
		}
		
		$(document).ready(function() {
			
			$("#list_agent_table tr[id^='cont_agent_']").each(function(index) {
				
				if (index % 2 == 0)
					$(this).css("background-color", "#EEEEEE");
			});
			
			$('#list_agent_table .agent_id').bind('click', function() {
				var id = $(this).text();
				var src  = $(this).find("img").attr("src");
				var src1 = "../pixmaps/minus-small.png";
				var src2 = "../pixmaps/plus-small.png";
				if (src == src1)
				{
					$("#minfo_"+id).css('display', 'none');
					$(this).find("img").attr("src", src2);
				}
				else
				{
					$("#minfo_"+id).css('display', '');
					$(this).find("img").attr("src", src1);
				}
							
			});
		
			$("#list_agent_table tr[id^='minfo_']").each(function(index) {
					
				if (index % 2 != 0)
					$(this).css("background-color", "#EEEEEE");
			});		
			
		});
		
		
				
		
	</script>
	
		
	<style type='text/css'>
		a {cursor:pointer; text-decoration: none !important;}			
		.bborder_none { border-bottom: none !important; background-color: #FFFFFF !important;}
		.load { height: 25px; margin: auto;}
		td.center {text-align: center !important;}
		
		
		.cont_plot  { width:420px; margin:auto; }


		#dhtmltooltip{
			position: absolute;
			width: 150px;
			border: 2px solid black;
			padding: 2px;
			background-color: lightyellow;
			visibility: hidden;
			z-index: 100;
		}

		img{
			vertical-align:middle;
		}
		
		
		#list_agent_table {
			width: 92%; 
			margin: 10px auto; 
			background: transparent;
		}
		
		#list_agent_table th {
			text-align: center; 
			height: 20px; 
			font-size: 12px;
		}
		#list_agent_table tbody td {
			text-align: left; 
			height: 20px; 
			border-bottom: solid 1px #CCCCCC; 
			font-size: 12px; 
			padding: 3px;
		}
		
		.headerpr{text-align: center; padding: 5px 0px !important;}
		
		.status_sec {padding-bottom: 10px; clear: both;}
		
		#ossc_result{
			margin: auto; 
			width: 92%; 
			border: 1px solid #D3D3D3;
			-moz-border-radius:4px;
			-webkit-border-radius: 4px;
			-khtml-border-radius: 4px;
			border: solid 1px #D2D2D2;
		}
		
		.div_pre {
			background-color: #FFFFFF;
			border: none;
			font-family: Courier New,Courier, monospace;
			font-size: 12px;
			text-align: left;
			overflow:auto;
		}
		
		#ossec_header {width: 92%; margin:auto; text-align:center;}
		
		.oss_containter_graph { 
			width:92%; 
			background:transparent; 
			margin: 10px auto 0px auto;
			text-align: center; 
			border: none;		
		}
		
		.oss_graph {
			background: transparent;
			border: 1px solid #BBBBBB;
			color: black;
			-moz-border-radius:8px;
			-webkit-border-radius: 8px;
			-khtml-border-radius: 8px;
		}		
		
	</style>
</head>
<body>

<?php include ("../hmenu.php"); ?>

<div id='container_center'>

	<div class='status_sec'>
	
		<div class="oss_containter_graph">
			<table style="width:100%" cellpadding="0" cellspacing="0">
			<tr><td>
				<table class='oss_graph'>
					<tr><th><?php echo _("Ossec Events Trend")?></th></tr>
					<tr><td><iframe src="../panel/event_trends.php?type=hids" frameborder="0" style="width:470px;height:215px;overflow:hidden"></iframe></td></tr>
				</table>
            </td><td>
				<table class='oss_graph' align="right">
					<tr><th><?php echo _("Ossec Data Sources")?></th></tr>
					<tr><td><iframe src="../panel/pie_graph.php?type=hids" frameborder="0" style="width:470px;height:215px;overflow:hidden"></iframe></td></tr>
				</table>
			</td></tr>
			</table>
		</div>
		
	</div>
	
	<div class='status_sec'>
		<table id='list_agent_table'>
			<thead>
				<tr><td class='headerpr' colspan='5'><?php echo _("Agents List")?></td></tr>
				<tr>
					<th style='width: 100px;'><?php echo _("ID")?></th>
					<th><?php echo _("Name")?></th>
					<th><?php echo _("IP")?></th>
					<th><?php echo _("Status")?></th>
					<th><?php echo _("Trend")?></th>
				</tr>
			</thead>
			<tbody>					
				<?php
				
				$agents = array();
				exec ( "sudo /var/ossec/bin/agent_control -ls", $agents, $ret);		

				if ( !empty ($agents) )
				{
					foreach ($agents as $k => $agent)
					{
						if ( empty($agent) )
							continue;
							
						$more_info     = array();
						$ret           = null;
						
						$agent         = explode(",", $agent);
						$agent_type    = null;
						ossim_valid($agent[0], OSS_DIGIT, 'illegal:' . _("Id agent"));
						
						if ( ossim_error() ) 
						{
							ossim_clean_error();
							$agent_name    = $agent[0];
							$agent_actions = "  --  ";
							$agent_type    = 0;
						}
						else
						{
							exec ( "sudo /var/ossec/bin/agent_control -i ".$agent[0]." -s", $more_info, $ret);
							$more_info     = ( $ret !== 0 ) ? _("Information from agent not available") : explode(",",$more_info[0]);
							$agent_name    = "<a class='agent_id'><img src='../pixmaps/plus-small.png' alt='More info' align='absmiddle'/>".$agent[0]."</a>";
							$agent_actions = get_actions($agent);
							$agent_type    = 1;
						}	
						
						//Agents trends
					    $trend = array();
						
						if ( $agent[2] == "127.0.0.1" )
						{
							require_once ('classes/Sensor.inc');
							
							$db     = new ossim_db();
							$dbconn = $db->connect();
	
							$name   = trim(str_replace("(server)", "", $agent[1]));
							
							$sensor = Sensor::get_list($dbconn, "WHERE name = '$name'");
							
							$ip = ( empty($sensor) ) ? $agent[2] : $sensor[0]->get_ip(); 
						}
						else
							$ip = $agent[2];
						
					    $data  = SIEM_trends_hids($ip);
						
						if ( is_array($data) )
					    {
							$max   = 7;
							for ($ii=$max-1; $ii>=0; $ii--)
							{
								$d         = gmdate("j M",$timetz-(86400*$ii));
								$trend[$d] = ( $data[$d]!="" ) ? $data[$d] : 0;
							}
							
							$i = 0;
							foreach ($trend as $k => $v)
							{
								$x[$k] = $i;
								$i++;
							}
							
							$y       = $trend;
							
							$xticks  = $x;
							
							foreach ($trend as $k => $v)
								$xlabels[$k] = $k;
								
								
							$trend = "<div class='cont_plot'><div id='plotarea_".$agent[0]."'>".plot_graphic("plotarea_".$agent[0], 40, 400, $x, $y, $xticks, $xlabels, false, "")."</div></div>";	
						}
						else
						{
							$trend = "<div style='color:red; margin:auto; text-align:center;'>"._("Trend chart not available")."</div>";
						}
						
								
						echo "<tr id='cont_agent_".$agent[0]."'>
								<td id='agent_".$agent[0]."'>$agent_name</td>
								<td>".$agent[1]."</td>
								<td>".$agent[2]."</td>
								<td>".$agent[3]."</td>
								<td style='width:440px;'>$trend</td>
							</tr>";
								
						if ( $agent_type === 1 )		
						{		
							echo "<tr id='minfo_".$agent[0]."' style='display:none;'>
									<td colspan='5'>";
										
							if ( !is_array($more_info) )
							{
								echo "<div style='padding:5px; color: #D8000C; text-align:center;'>$more_info</div>";
							}
							else
							{
								echo "<div style='padding: 3px 3px 5px 5px; font-weight: bold;'>"._("Agent information").":</div>";
								
								echo "<div style='float:left; width: 170px; font-weight: bold; padding:0px 3px 5px 15px;'>
										<span>"._("Agent ID").":</span><br/> 
										<span>"._("Agent Name").":</span><br/>
										<span>"._("IP address").":</span><br/>
										<span>"._("Status").":</span><br/><br/>
										<span>"._("Operating system").":</span><br/>
										<span>"._("Client version").":</span><br/>
										<span>"._("Last keep alive").":</span><br/><br/>
										<span>"._("Syscheck last started at").":</span><br/>
										<span>"._("Rootcheck last started at").":</span><br/>
								</div>";
							
								echo "<div style='float:left; width: auto; padding:0px 3px 5px 15px;'>
										<span>".$more_info[0]."</span><br/>  
										<span>".$more_info[1]."</span><br/>
										<span>".$more_info[2]."</span><br/>
										<span>".$more_info[3]."</span><br/><br/>
										<span>".$more_info[4]."</span><br/>
										<span>".$more_info[5]."</span><br/>
										<span>".$more_info[6]."</span><br/><br/>
										<span>".$more_info[7]."</span><br/>
										<span>".$more_info[8]."</span><br/>
									 </div>
								</div>";
							}
							echo "</td>
								</tr>";
						}
					}
				}
				else
				{
					if ($ret === 0)
					{
						$txt   = _("No agents available");
						$class = "oss_info";
					}
					else
					{
						$txt   = _("You don't have execute permissions");
						$class = "oss_error";
						$error = true;
					}
					echo "<tr id='cont_no_agent'><td colspan='5' class='no_agent bborder_none'><div class='$class info_agent'>$txt</div></td></tr>";
				}
					
				?>
			</tbody>
		</table>
	</div>
	
	<!--
	<div class='status_sec'>

		<p style="width:92%;text-align:left;margin-top:0px;margin-bottom:5px">
			<a onclick="$('#ossec_header,#ossc_result').toggle();">
				<img src="../pixmaps/arrow_green.gif" align="absmiddle" border="0"/><?php echo _("Ossec Status");?> 
			</a>
		</p>

		<?php
		
			//Ossec Status
			exec ("sudo /var/ossec/bin/ossec-control status", $result);
			$result = implode("<br/>", $result);
			$result = str_replace("is running", "<span style='font-weight: bold; color:#15B103;'>is running</span>", $result);
			$result = str_replace("not running", "<span style='font-weight: bold; color:#E54D4D;'>not running</span>", $result);
		?>
		
		<div class='headerpr' id='ossec_header' style='display:none'><?php echo _("Ossec Status");?></div>
		
		<div id='ossc_result' class='div_pre' style='display:none'>
			<div style='padding: 5px 10px 10px 10px;'><?php echo $result;?></div>
		</div>
	
	</div>
	-->

</div>

</body>
</html>

