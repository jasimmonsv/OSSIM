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
require_once ('classes/Session.inc');
Session::logcheck("MenuControlPanel", "ControlPanelHids");
require_once ('classes/Host_ids.inc');
require_once ('classes/Security.inc');
$limit = GET('hosts');
ossim_valid($limit, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("limit"));
if (ossim_error()) {
    die(ossim_error());
}
/* hosts to show */
if (empty($limit)) {
    $limit = 10;
}
$hids = new Host_ids("", "", "", "", "", "", "", "", "", "");
$list = $hids->Events($limit);
$data = $legend = array();
foreach($list as $l) {
    $legend[] = $l[0];
    $data[] = $l[1];
}
$conf = $GLOBALS["CONF"];
$jpgraph = $conf->get_conf("jpgraph_path");
include ("$jpgraph/jpgraph.php");
include ("$jpgraph/jpgraph_pie.php");
// Setup graph
$graph = new PieGraph(400, 240, "auto");
$graph->SetShadow();
// Setup graph title
$graph->title->Set("HIDS Events");
$graph->title->SetFont(FF_FONT1, FS_BOLD);
// Create pie plot
$p1 = new PiePlot($data);
//$p1->SetFont(FF_VERDANA,FS_BOLD);
//$p1->SetFontColor("darkred");
$p1->SetSize(0.2);
$p1->SetCenter(0.35);
$p1->SetLegends($legend);
//$p1->SetStartAngle(M_PI/8);
//$p1->ExplodeSlice(0);
$graph->Add($p1);
$graph->Stroke();
?>
