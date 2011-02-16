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
Session::logcheck("MenuEvents", "EventsForensics");

$shared = new DBA_shared(GET('shared'));
$ips = $shared->get("geoips");
if (!is_array($ips)) $ips = array();

$data_pie = array();
$legend = $data = array();
foreach($ips as $country => $val) {
    $cou = explode(":",$country); $val = round($val,1);
    $legend[] = $cou[1];
    $data[] = $val;
}
//
$conf = $GLOBALS["CONF"];
$colors=array("#E9967A","#F08080","#FF6347","#FF4500","#FF0000","#DC143C","#B22222");

$jpgraph = $conf->get_conf("jpgraph_path");
require_once "$jpgraph/jpgraph.php";
require_once "$jpgraph/jpgraph_pie.php";
require_once "$jpgraph/jpgraph_pie3d.php";
// Setup graph
$graph = new PieGraph(350, 420, "auto");
$graph->SetAntiAliasing();
$graph->SetMarginColor('#fafafa');

$graph->title->SetFont(FF_FONT1, FS_BOLD);
// Create pie plot
$p1 = new PiePlot3d($data);
$p1->SetHeight(12);
$p1->SetSize(0.5);
$p1->SetCenter(0.5,0.25);
$p1->SetLegends($legend);
$graph->legend->SetPos(0.5,0.95,'center','bottom');
$graph->legend->SetShadow('#fafafa',0);
$graph->legend->SetFrameWeight(1);
$graph->legend->SetFillColor('#fafafa');
$graph->legend->SetColumns(2);
$graph->SetFrame(false);
//$p1->SetSliceColors($colors);
//$p1->SetStartAngle(M_PI/8);
$p1->ExplodeSlice(0);
$graph->Add($p1);
$graph->Stroke();
unset($graph);
?>
