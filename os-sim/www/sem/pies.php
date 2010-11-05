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
* Classes list:
*/
if ($argv[1] != "") {
	$path_class = '/usr/share/ossim/include/:/usr/share/ossim/www/sem';
	ini_set('include_path', $path_class);
	$skip_logcheck = 1;
	$only_json = 1;
}
require_once ('ossim_db.inc');
require_once ('classes/Alarm.inc');
require_once ('classes/Util.inc');
require_once ('classes/Security.inc');
require_once ('classes/Session.inc');
if ($argv[1] == "") Session::logcheck("MenuEvents", "ControlPanelSEM");
require_once ('../graphs/charts.php');
require_once ('process.inc');
if (!$only_json) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="">
<head>
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
<link rel="stylesheet" href="../forensics/styles/ossim_style.css">
<script type="text/javascript" src="jquery-1.3.2.min.js"></script>
<script language="javascript" type="text/javascript" src="../js/excanvas.pack.js"></script>
<script type="text/javascript" src="../js/jquery.flot.pie.js"></script>
</head>
<body scroll="no" style="background:url(../pixmaps/fondo_hdr2.png) repeat-x" onLoad="parent.document.getElementById('testLoading').style.display='none'">
<?
}
$db = new ossim_db();
$conn = $db->connect();
//$a = $_SESSION["forensic_query"];
$ip_list = "";
if ($argv[1] != "") {
	$start = $argv[1];
	$end = $argv[2];
	$uniqueid = $argv[3];
	$user = $argv[4];
} else {
	$start = $_SESSION["forensic_start"];
	$end = $_SESSION["forensic_end"];
	$uniqueid = $_GET["uniqueid"];
	$ip_list = $_GET['ips'];
}

$sensors = array();
$event_type = array();
$ips_src = array();
$ips_dst = array();

ossim_valid($uniqueid, OSS_DIGIT, OSS_ALPHA, OSS_NULLABLE, OSS_DOT, 'illegal:' . _("uniqueid"));
ossim_valid($ip_list, OSS_DIGIT, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("ip_list"));
if (ossim_error()) {
    die(ossim_error());
}

$cmd = process($a, $start, $end, $offset, $sort_order, "all", $uniqueid);
if ($user == "") $user = $_SESSION["_user"];

if ($cmd != "") {
	if ($ip_list != "") {
		$cmd = str_replace("perl fetchall.pl","sudo ./fetchremote_pies.pl",$cmd);
		//echo "$cmd $user $ip_list";exit;
		$status = exec("$cmd $user $ip_list 2>/dev/null", $result);
		
		$string = trim($result[0]);
		$remote_data_aux = json_decode($string);
		foreach ($remote_data_aux as $ip=>$arr) {
			foreach ($arr as $type=>$type_data) {
				foreach ($type_data as $key=>$val) {
					$val = str_replace(",","",$val);
					if ($type == "sensors") {
						$sensors[$key] = ($sensors[$key] != "") ? $val : $val + $sensors[$key];
					} elseif ($type == "event_type") {
						$event_type[$key] = ($event_type[$key] != "") ? $val : $val + $event_type[$key];
					} elseif ($type == "ips_src") {
						$ips_src[$key] = ($ips_src[$key] != "") ? $val : $val + $ips_src[$key];
					} elseif ($type == "ips_dst") {
						$ips_dst[$key] = ($ips_dst[$key] != "") ? $val : $val + $ips_dst[$key];
					}
				}
			}
		}
	} else {
	    $status = exec("$cmd $user 2>/dev/null", $result);
	    foreach($result as $res) {
	        if(preg_match("/^\s+(\S+)\s+(\d+)\s+(\S+)/", $res, $matches)) {
	            //sensors
	            if($matches[1]=="sensor"){
	                $query = "select name from sensor where ip = \"" . $matches[3] . "\"";
	                if (!$rs = & $conn->Execute($query)) {
	                    print $conn->ErrorMsg();
	                    exit();
	                }
	                if ($rs->fields["name"] != "" && $rs->fields["name"] != $matches[3]) {
	                    $sensors[$rs->fields["name"]." (".$matches[3].") [".Util::number_format_locale($matches[2],0)."]"] = $matches[2];
	                } else {
	                    $sensors[$matches[3]." [".Util::number_format_locale($matches[2],0)."]"] = $matches[2];
	                }
	            }
	            //Events types
	            if($matches[1]=="plugin_id") {
	                $query = "select name from plugin where id = \"" . $matches[3] . "\"";
	                if (!$rs = & $conn->Execute($query)) {
	                    print $conn->ErrorMsg();
	                    exit();
	                }
	                if ($rs->fields["name"] != "") {
	                    $event_type[$rs->fields["name"]." [".Util::number_format_locale($matches[2],0)."]"] = $matches[2];
	                }
	                else {
	                    $event_type[$matches[3]." [".Util::number_format_locale($matches[2],0)."]"] = $matches[2];
	                }
	            }
	            // sources
	            if($matches[1]=="src_ip") {
	                $query = "select hostname from host where ip = \"" . $matches[3] . "\"";
	                if (!$rs = & $conn->Execute($query)) {
	                    print $conn->ErrorMsg();
	                    exit();
	                }
	                if ($rs->fields["hostname"] != "" && $rs->fields["hostname"] != $matches[3]) {
	                    $ips_src[$rs->fields["hostname"]." (".$matches[3].") [".Util::number_format_locale($matches[2],0)."]"] = $matches[2];
	                }
	                else {
	                    $ips_src[$matches[3]." [".Util::number_format_locale($matches[2],0)."]"] = $matches[2];
	                }
	            }
	            // destinations
	            if($matches[1]=="dst_ip") {
	                $query = "select hostname from host where ip = \"" . $matches[3] . "\"";
	                if (!$rs = & $conn->Execute($query)) {
	                    print $conn->ErrorMsg();
	                    exit();
	                }
	                if ($rs->fields["hostname"] != "" && $rs->fields["hostname"] != $matches[3]) {
	                    $ips_dst[$rs->fields["hostname"]." (".$matches[3].") [".Util::number_format_locale($matches[2],0)."]"] = $matches[2];
	                }
	                else {
	                    $ips_dst[$matches[3]." [".Util::number_format_locale($matches[2],0)."]"] = $matches[2];
	                }
	            }
	        }
	    }
    }
    if ($only_json) {
    	$json = array('sensors' => $sensors, 'event_type' => $event_type, 'ips_src' => $ips_src, 'ips_dst' => $ips_dst);
		echo json_encode($json);
		exit;
    }
?>
<style>
.pieLabel div{
	font-size: 10px;
	border: 1px solid gray;
	background: #f2f2f2;
	padding: 1px;
	text-align: center;
}
.legendColorBox { border:0 none; }
.legendLabel { border:0 none; }
div.legend { text-align:left; }
div.legend table { border:0 none; padding-left:5px; }
div.legend td { text-align:left; font-size:11px; padding:1px; line-height:11px; }
div.legend td a { padding:0px; margin:0px; line-height:11px; }
</style>

<table cellpadding=0 cellspacing=0 width="100%" align="center" class="noborder">
<tr height="36">
    <td style="border-left: 1px solid rgb(170, 170, 170);border-top: 1px solid rgb(170, 170, 170);border-right: 1px solid rgb(170, 170, 170); border-bottom: 1px solid rgb(170, 170, 170); background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 50% 50%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: rgb(34, 34, 34); font-size: 12px; font-weight: bold;text-align:center;" width="130"><?= _("Sensors");?></td>
    <td width="20" class="noborder"></td>
    <td style="border-left: 1px solid rgb(170, 170, 170);border-top: 1px solid rgb(170, 170, 170);border-right: 1px solid rgb(170, 170, 170); border-bottom: 1px solid rgb(170, 170, 170); background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 50% 50%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: rgb(34, 34, 34); font-size: 12px; font-weight: bold;text-align:center;" width="130"><?= _("Event types");?></td>
    <td width="20" class="noborder"></td>
    <td style="border-left: 1px solid rgb(170, 170, 170);border-top: 1px solid rgb(170, 170, 170);border-right: 1px solid rgb(170, 170, 170); border-bottom: 1px solid rgb(170, 170, 170); background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 50% 50%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: rgb(34, 34, 34); font-size: 12px; font-weight: bold;text-align:center;" width="130"><?= _("Sources");?></td>
    <td width="20" class="noborder"></td>
    <td style="border-left: 1px solid rgb(170, 170, 170);border-top: 1px solid rgb(170, 170, 170);border-right: 1px solid rgb(170, 170, 170); border-bottom: 1px solid rgb(170, 170, 170); background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 50% 50%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: rgb(34, 34, 34); font-size: 12px; font-weight: bold;text-align:center;" width="130"><?= _("Destinations");?></td>
</tr>
<tr height="390">
	<td style="border-left: 1px solid rgb(170, 170, 170);border-bottom: 1px solid rgb(170, 170, 170);border-right: 1px solid rgb(170, 170, 170);" valign="top">
		<table cellpadding=0 cellspacing=0 width="100%" align="center"><tr><td class="noborder" style="padding:5px 5px 5px 5px" align="center">
		<div id="sensors_pie" style="width:180px;height:185px;text-align:center;"><? if (count($sensors) < 1) { ?><font style="color:gray"><i><?=_("No Sensor Data")?></i></font><? } ?></div>
		</td></tr></table>
	</td>
	<td width="20" class="noborder">&nbsp;</td>
	<td style="border-left: 1px solid rgb(170, 170, 170);border-bottom: 1px solid rgb(170, 170, 170);border-right: 1px solid rgb(170, 170, 170);" valign="top">
		<table cellpadding=0 cellspacing=0 width="100%" align="center"><tr><td class="noborder" style="padding:5px 5px 5px 5px" align="center">
		<div id="event_type_pie" style="width:180px;height:185px;text-align:center;"><? if (count($event_type) < 1) { ?><font style="color:gray"><i><?=_("No Event Type Data")?></i></font><? } ?></div>
		</td></tr></table>
	</td>
	<td width="20" class="noborder">&nbsp;</td>
	<td style="border-left: 1px solid rgb(170, 170, 170);border-bottom: 1px solid rgb(170, 170, 170);border-right: 1px solid rgb(170, 170, 170);" valign="top">
		<table cellpadding=0 cellspacing=0 width="100%" align="center"><tr><td class="noborder" style="padding:5px 5px 5px 5px" align="center">
		<div id="sources_pie" style="width:180px;height:185px;text-align:center;"><? if (count($ips_src) < 1) { ?><font style="color:gray"><i><?=_("No Source IP Data")?></i></font><? } ?></div>
		</td></tr></table>
	</td>
	<td width="20" class="noborder">&nbsp;</td>
	<td style="border-left: 1px solid rgb(170, 170, 170);border-bottom: 1px solid rgb(170, 170, 170);border-right: 1px solid rgb(170, 170, 170);" valign="top">
		<table cellpadding=0 cellspacing=0 width="100%" align="center"><tr><td class="noborder" style="padding:5px 5px 5px 5px" align="center">
		<div id="destinations_pie" style="width:180px;height:185px;text-align:center;"><? if (count($ips_dst) < 1) { ?><font style="color:gray"><i><?=_("No Dest IP Data")?></i></font><? } ?></div>
		</td></tr></table>
	</td>
</tr>
</tr></table>
<script>
	
	$(function () {
		<? if (count($sensors) > 0) { ?>
		$.plot($("#sensors_pie"), [
		<? $i=0;foreach ($sensors as $key => $value){ ?>
			<?=($i++==0) ? "" : ","?>{ label: "<a href=\"javascript:parent.display_info('','','','<?=preg_replace("/\s+\(.*|\s+\[.*/","",$key)?>','','sensor')\"><?=$key?></a>",  data: <?=$value?>}
		<? } ?>
		], 
		{
			pie: { 
				show: true, 
				pieStrokeLineWidth: 1, 
				pieStrokeColor: '#FFF', 
				//pieChartRadius: 80, 			// by default it calculated by 
				//centerOffsetTop:15,
				centerOffsetLeft:0, 			// if 'auto' and legend position is "nw" then centerOffsetLeft is equal a width of legend.
				showLabel: true,				//use ".pieLabel div" to format looks of labels
				labelOffsetFactor: 5/6, 		// part of radius (default 5/6)
				//labelOffset: 0        		// offset in pixels if > 0 then labelOffsetFactor is ignored
				labelBackgroundOpacity: 0.55, 	// default is 0.85
				labelFormatter: function(serie){// default formatter is "serie.label"
					//return serie.label;
					//return serie.data;
					//return serie.label+'<br/>'+Math.round(serie.percent)+'%';
					return Math.round(serie.percent)+'%';
				}
			},
			colors: ["#E9967A","#F08080","#FF6347","#FF4500","#FF0000","#DC143C","#B22222"],
			legend: {
				show: true, 
				position: "b", 
				backgroundOpacity: 0
			}
		});
		<? } ?>
		<? if (count($event_type) > 0) { ?>
    		$.plot($("#event_type_pie"), [
		<? $i=0;foreach ($event_type as $key => $value){ ?>
			<?=($i++==0) ? "" : ","?>{ label: "<a href=\"javascript:parent.display_info('','','','<?=preg_replace("/\s+\[.*/","",$key)?>','','plugin')\"><?=$key?></a>",  data: <?=$value?>}
		<? } ?>
		], 
		{
			pie: { 
				show: true, 
				pieStrokeLineWidth: 1, 
				pieStrokeColor: '#FFF', 
				//pieChartRadius: 80, 			// by default it calculated by 
				//centerOffsetTop:15,
				centerOffsetLeft:0, 			// if 'auto' and legend position is "nw" then centerOffsetLeft is equal a width of legend.
				showLabel: true,				//use ".pieLabel div" to format looks of labels
				labelOffsetFactor: 5/6, 		// part of radius (default 5/6)
				//labelOffset: 0        		// offset in pixels if > 0 then labelOffsetFactor is ignored
				labelBackgroundOpacity: 0.55, 	// default is 0.85
				labelFormatter: function(serie){// default formatter is "serie.label"
					//return serie.label;
					//return serie.data;
					//return serie.label+'<br/>'+Math.round(serie.percent)+'%';
					return Math.round(serie.percent)+'%';
				}
			},
			colors: ["#90EE90","#00FF7F","#7CFC00","#32CD32","#3CB371","#228B22","#006400"],
			legend: {
				show: true, 
				position: "b", 
				backgroundOpacity: 0
			}
		});
		<? } ?>
		<? if (count($ips_src) > 0) { ?>
    		$.plot($("#sources_pie"), [
		<? $i=0;foreach ($ips_src as $key => $value){ preg_match("/(\d+\.\d+\.\d+\.\d+)/",$key,$found); ?>
			<?=($i++==0) ? "" : ","?>{ label: "<a href=\"javascript:parent.display_info('','','','<?=$found[1]?>','','source')\"><?=$key?></a>",  data: <?=$value?>}
		<? } ?>
		], 
		{
			pie: { 
				show: true, 
				pieStrokeLineWidth: 1, 
				pieStrokeColor: '#FFF', 
				//pieChartRadius: 80, 			// by default it calculated by 
				//centerOffsetTop:15,
				centerOffsetLeft:0, 			// if 'auto' and legend position is "nw" then centerOffsetLeft is equal a width of legend.
				showLabel: true,				//use ".pieLabel div" to format looks of labels
				labelOffsetFactor: 5/6, 		// part of radius (default 5/6)
				//labelOffset: 0        		// offset in pixels if > 0 then labelOffsetFactor is ignored
				labelBackgroundOpacity: 0.55, 	// default is 0.85
				labelFormatter: function(serie){// default formatter is "serie.label"
					//return serie.label;
					//return serie.data;
					//return serie.label+'<br/>'+Math.round(serie.percent)+'%';
					return Math.round(serie.percent)+'%';
				}
			},
			colors: ["#ADD8E6","#00BFFF","#4169E1","#4682B4","#0000CD","#483D8B","#00008B"],
			legend: {
				show: true, 
				position: "b", 
				backgroundOpacity: 0
			}
		});
		<? } ?>
		<? if (count($ips_dst) > 0) { ?>
    		$.plot($("#destinations_pie"), [
		<? $i=0;foreach ($ips_dst as $key => $value){ preg_match("/(\d+\.\d+\.\d+\.\d+)/",$key,$found); ?>
			<?=($i++==0) ? "" : ","?>{ label: "<a href=\"javascript:parent.display_info('','','','<?=$found[1]?>','','destination')\"><?=$key?></a>",  data: <?=$value?>}
		<? } ?>
		], 
		{
			pie: { 
				show: true, 
				pieStrokeLineWidth: 1, 
				pieStrokeColor: '#FFF', 
				//pieChartRadius: 80, 			// by default it calculated by 
				//centerOffsetTop:15,
				centerOffsetLeft:0, 			// if 'auto' and legend position is "nw" then centerOffsetLeft is equal a width of legend.
				showLabel: true,				//use ".pieLabel div" to format looks of labels
				labelOffsetFactor: 5/6, 		// part of radius (default 5/6)
				//labelOffset: 0        		// offset in pixels if > 0 then labelOffsetFactor is ignored
				labelBackgroundOpacity: 0.55, 	// default is 0.85
				labelFormatter: function(serie){// default formatter is "serie.label"
					//return serie.label;
					//return serie.data;
					//return serie.label+'<br/>'+Math.round(serie.percent)+'%';
					return Math.round(serie.percent)+'%';
				}
			},
			colors: ["#EEE8AA","#F0E68C","#FFD700","#FF8C00","#DAA520","#D2691E","#B8860B"],
			legend: {
				show: true, 
				position: "b", 
				backgroundOpacity: 0
			}
		});
		<? } ?>
	});
	
</script>
<?
//echo "<br><br>";
//var_dump($sensors);
//var_dump($event_type);
//var_dump($ips_src);
//var_dump($ips_dst);
}
?>
</body>
</html>
