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
require_once ('ossim_db.inc');
require_once ('classes/Alarm.inc');
require_once ('classes/Util.inc');
require_once ('classes/Security.inc');
require_once ('classes/Session.inc');

Session::logcheck("MenuControlPanel", "ControlPanelExecutive");

include 'charts.php';
$chart['license'] = "J1XF-CMEW9L.HSK5T4Q79KLYCK07EK";
$chart['axis_category'] = array(
    'size' => 10,
    'color' => "000000",
    'alpha' => 75,
    'skip' => 0,
    'orientation' => "diagonal_up"
);
$chart['axis_ticks'] = array(
    'value_ticks' => false,
    'category_ticks' => false
);
$chart['axis_value'] = array(
    'alpha' => 0
);
$chart['chart_border'] = array(
    'top_thickness' => 0,
    'bottom_thickness' => 0,
    'left_thickness' => 0,
    'right_thickness' => 0
);
$chart['chart_grid_h'] = array(
    'thickness' => 0
);
$chart['chart_grid_v'] = array(
    'thickness' => 0
);
$chart['chart_rect'] = array(
    'x' => - 70,
    'y' => - 35,
    'width' => 500,
    'height' => 250,
    'positive_alpha' => 0
);
$chart['chart_pref'] = array(
    'rotation_x' => 20,
    'rotation_y' => 50
);
$chart['chart_transition'] = array(
    'type' => "none",
    'delay' => 0,
    'duration' => 1,
    'order' => "series"
);
$chart['chart_type'] = "3d column";
$chart['chart_value'] = array(
    'hide_zero' => 'true',
    'color' => "000000",
    'alpha' => 80,
    'size' => 12,
    'position' => "cursor",
    'prefix' => "",
    'suffix' => "",
    'decimals' => 0,
    'separator' => "",
    'as_percentage' => true
);
$chart['legend_label'] = array(
    'layout' => "horizontal",
    'font' => "arial",
    'bold' => true,
    'size' => 12,
    'color' => "000000",
    'alpha' => 50
);
$chart['legend_rect'] = array(
    'x' => 25,
    'y' => 250,
    'width' => 350,
    'height' => 50,
    'margin' => 20,
    'fill_color' => "000000",
    'fill_alpha' => 7,
    'line_color' => "000000",
    'line_alpha' => 0,
    'line_thickness' => 0
);
$chart['legend_transition'] = array(
    'type' => "none",
    'delay' => 0,
    'duration' => 1
);
$chart['series_color'] = array(
    "0000ff",
    "ff0000"
);
$chart['series_gap'] = array(
    'bar_gap' => 0,
    'set_gap' => 20
);
//Number of hosts to be displayed
if (isset($_GET['numnets']) && is_numeric($_GET['numnets'])) $numnets = $_GET['numnets'];
else $numnets = "10";
//Refresh interval
if (isset($_GET['refresh']) && is_numeric($_GET['refresh'])) $refresh = $_GET['refresh'];
else $refresh = 2;
$db = new ossim_db();
$conn = $db->connect();

// net groups
$netgroups = array();
$ngs = Net_group::get_list($conn,"",""); // get only allowed net_groups
foreach ($ngs as $ng) {
	$rns = $ng->get_networks($conn);
	foreach ($rns as $rn) $netgroups[$rn->get_net_group_name()][] = $rn->get_net_name();
}
//
$nets_where = "";
if (Session::allowedNets() != "") {
	$nets = explode (",",Session::allowedNets());
	foreach ($nets as $net) {
		$nets_where .= ($nets_where != "") ? " OR net_name=\"".Net::get_name_by_ip($conn,$net)."\"" : " WHERE net_name=\"".Net::get_name_by_ip($conn,$net)."\"";
	}
}

$query = "select * from net_qualification$nets_where order by (compromise+attack)/2 desc";
if (!$rs = & $conn->Execute($query)) {
    print $conn->ErrorMsg();
    exit();
}
$nets = array();
while (!$rs->EOF) {
    $nets[$rs->fields["net_name"]] = array($rs->fields["compromise"],$rs->fields["attack"]);
    $rs->MoveNext();
}
$addresses = array();
$compromise = array();
$attack = array();
$i = $j = 0;
$addresses[$i] = "Nets";
$compromise[$i] = "Compromise";
$attack[$i] = "Attack";
$addresses[$i + 1] = "no data";
$compromise[$i + 1] = "0";
$attack[$i + 1] = "0";
foreach ($netgroups as $ng_name => $netsgr) {
    $ng_compromise = $ng_attack = 0;
    foreach ($netsgr as $net) {
        if (is_array($nets[$net])) {
            $ng_compromise += $nets[$net][0];
            $ng_attack += $nets[$net][1];
        }
    }
    $i++; $j++;
    $addresses[$i] = $ng_name;
    $compromise[$i] = $ng_compromise;
    $attack[$i] = $ng_attack;
    if ($j>$numnets) break;
}
$chart['live_update'] = array(
    'url' => "top_net_groups.php?bypassexpirationupdate=1&time=" . time() . "&" . $_SERVER['QUERY_STRING'],
    'delay' => $refresh
);
//        $chart [ 'link_data' ] = array (   'url'     =>  "handle.php?target_url=top_nets&target_var=category", 'target'  =>  "_blank");
$chart['chart_data'] = array(
    $addresses,
    $compromise,
    $attack
);
SendChartData($chart);
?>

