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
require_once 'classes/Session.inc';
require_once 'classes/Incident.inc';
require_once 'ossim_db.inc';
Session::logcheck("MenuIncidents", "IncidentsReport");
require_once 'classes/Security.inc';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="">
<head>
<link rel="stylesheet" type="text/css" href="../style/style.css"/>

<script type="text/javascript" src="../../js/jquery-1.3.1.js"></script>
<script type="text/javascript" src="../../js/jquery.flot.pie.js"></script>
</head>
<body>
<?
$by = GET('by');
ossim_valid($by, OSS_ALPHA, OSS_SPACE, OSS_SCORE, 'illegal:' . _("Target"));
if (ossim_error()) {
    die(ossim_error());
}
$conf = $GLOBALS["CONF"];
$jpgraph = $conf->get_conf("jpgraph_path");
include ("$jpgraph/jpgraph.php");
include ("$jpgraph/jpgraph_pie.php");
$db = new ossim_db();
$conn = $db->connect();
if ($by == "user") {
    $list = Incident::incidents_by_user($conn);
    $title = _("TICKET BY USER");
} elseif ($by == "type") {
    $list = Incident::incidents_by_type($conn);
    $title = _("TICKET BY TYPE");
} elseif ($by == "type_descr") {
    $list = Incident::incidents_by_type_descr($conn);
    $title = _("TICKET BY TYPE DESCRIPTION");
} elseif ($by == "status") {
    $title = _("TICKET BY STATUS");
    $list = Incident::incidents_by_status($conn);
}
$data_pie = array();
foreach($list as $l) {
    $data_pie[$l[1]] = $l[0];
    //$legend[] = $l[0];
    //$data[] = $l[1];
}
$db->close($conn);
/*
if ($by == "type_descr") {
    // Setup graph
    $graph = new PieGraph(800, 450, "auto");
    //$graph = new PieGraph(500,250,"auto");
    $graph->SetShadow();
    // Setup graph title
    $graph->title->Set($title);
    $graph->title->SetFont(FF_FONT1, FS_BOLD);
    $graph->legend->Pos(0.01, 0.9, 'left', 'bottom');
    //$graph->legend->AbsPos(200,100,'left','top');
    //$graph->legend->SetColumns(1);
    $graph->legend->SetMarkAbsSize(10);
    // Create pie plot
    $p1 = new PiePlot($data);
    //$p1->SetFont(FF_VERDANA,FS_BOLD);
    //$p1->SetFontColor("darkred");
    $p1->SetSize(0.2);
    //$p1->SetCenter(0.30);
    $p1->SetCenter(0.45, 0.3);
    $p1->SetLegends($legend);
    //$graph->legend->Pos(0.5,0.0.5,'left','bottom');
    //$p1->SetLabelType(PIE_VALUE_ABS);
    //$p1->SetStartAngle(M_PI/8);
    //$p1->ExplodeSlice(0);
    
} else {
    // Setup graph
    $graph = new PieGraph(500, 250, "auto");
    $graph->SetShadow();
    // Setup graph title
    $graph->title->Set($title);
    $graph->title->SetFont(FF_FONT1, FS_BOLD);
    // Create pie plot
    $p1 = new PiePlot($data);
    //$p1->SetFont(FF_VERDANA,FS_BOLD);
    //$p1->SetFontColor("darkred");
    $p1->SetSize(0.3);
    $p1->SetCenter(0.30);
    $p1->SetLegends($legend);
    //$p1->SetStartAngle(M_PI/8);
    //$p1->ExplodeSlice(0);
    
}
$graph->Add($p1);
$graph->Stroke();*/
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
div.legend table { border:0 none; }
div.legend td { text-align:left; font-size:11px; font-family:arial }
</style><table cellpadding=0 cellspacing=0 width="100%" align="center" class="noborder">
<tr>
	<td class="noborder">
		<table cellpadding=0 cellspacing=0 width="100%" align="center"><tr><td class="noborder" style="padding:5px 5px 5px 30px" align="center">
		<div id="graph" style="height:190px"></div>
		</td></tr></table>
	</td>
</tr>
</tr></table>

<script>
	$(function () {
		$.plot($("#graph"), [
		<? $i=0;foreach ($data_pie as $data => $label) if ($i<10) { ?>
			<?=($i++==0) ? "" : ","?>{ label: "<?=$label?>",  data: <?=$data?>}
		<? } ?>
		], 
		{
			pie: { 
				show: true, 
				pieStrokeLineWidth: 1, 
				pieStrokeColor: '#FFF', 
				pieChartRadius: 80, 			// by default it calculated by 
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
            <?
            if ($by=="status"){
                echo "colors: [\"#E9967A\",\"#ADD8E6\"],";
            }
            else if ($by=="user"){
                echo "colors: [\"#90EE90\",\"#00FF7F\",\"#7CFC00\",\"#32CD32\",\"#3CB371\",\"#228B22\",\"#006400\"],";
            }
            else if ($by=="type"){
                echo "colors: [\"#EEE8AA\",\"#F0E68C\",\"#FFD700\",\"#FF8C00\",\"#DAA520\",\"#D2691E\",\"#B8860B\"],";
            }
            else {
                echo "colors: [\"#ADD8E6\",\"#00BFFF\",\"#4169E1\",\"#4682B4\",\"#0000CD\",\"#483D8B\",\"#00008B\"],";
            }
            
            ?>
			legend: {
				show: true, 
				position: "b", 
				backgroundOpacity: 0
			}
		});});
</script>
</body>
</html>
