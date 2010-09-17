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
require_once 'classes/SecurityReport.inc';
require_once 'classes/Security.inc';
require_once 'classes/Util.inc';

//Session::logcheck("MenuReports", "ReportsSecurityReport");
Session::logcheck("MenuIncidents", "ReportsAlarmReport");
$limit = GET('hosts');
$type = GET('type');
$date_from = (GET('date_from') != "") ? GET('date_from') : strftime("%d/%m/%Y %H:%M:%S", time() - (24 * 60 * 60));
$date_to = (GET('date_to') != "") ? GET('date_to') : strftime("%d/%m/%Y %H:%M:%S", time());
ossim_valid($limit, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Limit"));
ossim_valid($type, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Report type"));
ossim_valid($date_from, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("from date"));
ossim_valid($date_to, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("to date"));
$runorder = intval(GET('runorder')); if ($runorder==0) $runorder="";
if (ossim_error()) {
    die(ossim_error());
}
/* hosts to show */
if (empty($limit) || $limit<=0 || $limit>10) {
    $limit = 10;
}
if (empty($type)) {
    $type = "event";
}
$security_report = new SecurityReport();
if ($type == "event" && is_array($_SESSION["SS_TopEvents$runorder"]) && count($_SESSION["SS_TopEvents$runorder"])>0)
	$list = $_SESSION["SS_TopEvents$runorder"];
elseif ($type == "alarm" && is_array($_SESSION["SA_TopAlarms$runorder"]) && count($_SESSION["SA_TopAlarms$runorder"])>0)
	$list = $_SESSION["SA_TopAlarms$runorder"];
else
	$list = $security_report->Events($limit, $type, $date_from, $date_to);
$data_pie = array();
$legend = $data = array();
foreach($list as $key => $l) {
    if($key>=10){
        // ponemos un límite de resultados para la gráfica
        break;
    }
    $data_pie[$l[1]] = SecurityReport::Truncate($l[0], 60);
    $legend[] = Util::signaturefilter(SecurityReport::Truncate($l[0], 60));
    $data[] = $l[1];
}
$conf = $GLOBALS["CONF"];
$colors=array("#ADD8E6","#00BFFF","#4169E1","#4682B4","#0000CD","#483D8B","#00008B","#3636db","#1390fa","#6aafea");
//$colors=array("#D6302C","#3933FC","green","yellow","pink","#40E0D0","#00008B",'#800080','#FFA500','#A52A2A');

$jpgraph = $conf->get_conf("jpgraph_path");
require_once "$jpgraph/jpgraph.php";
require_once "$jpgraph/jpgraph_pie.php";
require_once "$jpgraph/jpgraph_pie3d.php";
// Setup graph
$graph = new PieGraph(400, 400, "auto");
$graph->SetAntiAliasing();
$graph->SetMarginColor('#fafafa');

//$graph->SetShadow();
// Setup graph title
if ($type == "event") {
    //$graph->title->Set(gettext("EVENTS RECEIVED"));
} elseif ($type == "alarm") {
    //$graph->title->Set(gettext("ALARMS RECEIVED"));
}

$graph->title->SetFont(FF_FONT1, FS_BOLD);
// Create pie plot
$p1 = new PiePlot3d($data);
$p1->SetHeight(12);
$p1->SetSize(0.3);
$p1->SetCenter(0.5,0.25);
$p1->SetLegends($legend);
$graph->legend->SetPos(0.5,0.95,'center','bottom');
$graph->legend->SetShadow('#fafafa',0);
$graph->legend->SetFrameWeight(0);
$graph->legend->SetFillColor('#fafafa');
$graph->SetFrame(false);
$p1->SetSliceColors($colors);
//$p1->SetStartAngle(M_PI/8);
//$p1->ExplodeSlice(0);
$graph->Add($p1);
$graph->Stroke();
unset($graph);
exit();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="">
<head>
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
<link rel="stylesheet" type="text/css" href="../style/style.css"/>
<script type="text/javascript" src="../../js/jquery-1.3.2.min.js"></script>
<script language="javascript" type="text/javascript" src="../../js/excanvas.pack.js"></script>
<script type="text/javascript" src="../../js/jquery.flot.pie.js"></script>
</head>
<body scroll="no">
<?
if ($type == "event") {
    $title = _("ALERTS RECEIVED");
} elseif ($type == "alarm") {
    $title = _("ALARMS RECEIVED");
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
<tr>
	<td class="noborder">
		<table cellpadding=0 cellspacing=0 width="100%" align="center"><tr><td class="noborder" style="padding:5px 5px 5px 30px" align="center">
		<div id="graph" style="width:250px;height:190px"></div>
		</td></tr></table>
	</td>
</tr>
</tr></table>

<script>
	$(function () {
		$.plot($("#graph"), [
		<? $i=0;foreach ($data_pie as $data => $label) if ($i<10) {
            $label=addslashes($label);  ?>
			<?=($i++==0) ? "" : ","?>{ label: "<?=Util::signaturefilter($label)?>",  data: <?=$data?>}
		<? } ?>
		], 
		{
			pie: { 
				show: true, 
				pieStrokeLineWidth: 1, 
				pieStrokeColor: '#FFF', 
				//pieChartRadius: 100, 			// by default it calculated by 
				//centerOffsetTop:30,
				//centerOffsetLeft:30, 			// if 'auto' and legend position is "nw" then centerOffsetLeft is equal a width of legend.
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
		});});
</script>
</body>
</html>
