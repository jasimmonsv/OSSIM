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
ob_implicit_flush();
if ($argv[1] != "") {
	$path_class = '/usr/share/ossim/include/:/usr/share/ossim/www/sem';
	ini_set('include_path', $path_class);
	$skip_logcheck = 1;
	$only_json = 1;
}
function dateDiff($startDate, $endDate)
{
    // Parse dates for conversion
    $startArry = date_parse($startDate);
    $endArry = date_parse($endDate);

    // Convert dates to Julian Days
    $start_date = gregoriantojd($startArry["month"], $startArry["day"], $startArry["year"]);
    $end_date = gregoriantojd($endArry["month"], $endArry["day"], $endArry["year"]);

    // Return difference
    return round(($end_date - $start_date), 0);
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
<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
<script language="javascript" type="text/javascript" src="../js/excanvas.pack.js"></script>
<script type="text/javascript" src="../js/jquery.flot.pie.js"></script>
<script type="text/javascript" src="../js/jquery.progressbar.min.js"></script>
<style type="text/css">
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

.level11  {  background:url(../pixmaps/statusbar/level11.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level10  {  background:url(../pixmaps/statusbar/level10.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level9  {  background:url(../pixmaps/statusbar/level9.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level8  {  background:url(../pixmaps/statusbar/level8.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level7  {  background:url(../pixmaps/statusbar/level7.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level6  {  background:url(../pixmaps/statusbar/level6.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level5  {  background:url(../pixmaps/statusbar/level5.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level4  {  background:url(../pixmaps/statusbar/level4.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level3  {  background:url(../pixmaps/statusbar/level3.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level2  {  background:url(../pixmaps/statusbar/level2.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level1  {  background:url(../pixmaps/statusbar/level1.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level0  {  background:url(../pixmaps/statusbar/level0.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
</style>
</head>
<body scroll="no" style="background:url(../pixmaps/fondo_hdr2.png) repeat-x">
<div id="loading" style="position:absolute;top:50;left:30%">
	<table class="noborder" style="background-color:white">
		<tr>
			<td class="nobborder" style="text-align:center">
				<span class="progressBar" id="pbar"></span>
			</td>
			<td class="nobborder" id="progressText" style="text-align:center;padding-left:5px"><?=gettext("Loading data. Please, wait a few seconds...")?></td>
		</tr>
	</table>
</div>
<script type="text/javascript">
	$("#pbar").progressBar();
	$("#pbar").progressBar(1);
</script>
<?php
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
	$num_servers = 1;
} else {
	$start = $_SESSION["forensic_start"];
	$end = $_SESSION["forensic_end"];
	$uniqueid = $_GET["uniqueid"];
	$ip_list = $_GET['ips'];
	$ip_arr = explode(",",$ip_list);
	$num_servers = count($ip_arr);
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

$perc = 1;
$ndays = dateDiff($start,$end);
if ($ndays < 1) $ndays = 1;
if ($num_servers < 1) $num_servers = 1;
if ($ndays < 1) $ndays = 1;
$inc = 100/($ndays*$num_servers);

if ($cmd != "") {
	if ($ip_list != "") {
		$cmd = str_replace("perl fetchall.pl","sudo ./fetchremote_pies.pl",$cmd);
		//echo "$cmd $user $ip_list";exit;
		$status = exec("$cmd $user $ip_list 2>/dev/null", $result);
		$string = trim($result[0]);
		$remote_data_aux = json_decode($string);
		if (!is_array($remote_data_aux)) $remote_data_aux = array();
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
	    //echo "$cmd $user"; exit;
		//$status = exec("$cmd $user 2>/dev/null", $result);
		$fp = popen("$cmd $user 2>/dev/null", "r");
	    while (!feof($fp)) {
    		$res = fgets($fp);
			if (preg_match("/^Searching in (\d\d\d\d\d\d\d\d)/",$res,$found)) {
		    	ob_flush();
				flush();
				$sdate = date("d F Y",strtotime($found[1]));
				if (!$only_json) { ?><script type="text/javascript">$("#pbar").progressBar(<?php echo floor($perc) ?>);$("#progressText").html('Searching <b>events</b> in <?php echo $sdate?><?php echo $from_str ?>...');</script><?php }
		    	$perc += $inc;
		    	if ($perc > 100) $perc = 100;
		    }
	        elseif(preg_match("/^\s+(\S+)\s+(\d+)\s+(\S+)/", $res, $matches)) {
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
		ob_end_flush();
		exit;
    }
}
?>
<div id="processcontent" style="display:none">
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
</div>
</body>
<script type="text/javascript">
$(document).ready(function(){
	$("#loading").hide();
	$("#processcontent").show();
	
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
		<?=($i++==0) ? "" : ","?>{ label: "<a href=\"javascript:parent.display_info('','','','<?=$found[1]?>','','src')\"><?=$key?></a>",  data: <?=$value?>}
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
		<?=($i++==0) ? "" : ","?>{ label: "<a href=\"javascript:parent.display_info('','','','<?=$found[1]?>','','dst')\"><?=$key?></a>",  data: <?=$value?>}
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
</html>
<?php 
//ob_end_flush();
?>
