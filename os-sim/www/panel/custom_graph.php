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
* - mydie()
* Classes list:
*/
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
require_once 'classes/Security.inc';
require_once 'classes/Session.inc';
require_once 'panel/Ajax_Panel.php';
require_once 'ossim_conf.inc';
require_once 'ossim_db.inc';
function gettabsavt($configs_dir) {
	$tabsavt = array();
	if (is_dir($configs_dir)) {
		if ($dh = opendir($configs_dir)) {
			while (($file = readdir($dh)) !== false) {
				if (preg_match("/\.avt/",$file)) {
					list($avt_id,$avt_values) = getavt($file,$configs_dir);
					$tabsavt[$avt_id] = $avt_values;
				}
			}
			closedir($dh);
		}
	}
	return $tabsavt;
}
function getavt($file,$configs_dir="") {
	if (file_exists($configs_dir."/".$file)) {
		$data = file($configs_dir."/".$file);
		if (preg_match("/([^\_]+)\_([^\_]+)\_([^\_]+)\_disabled\.avt/",$file,$found))
			return array($found[3],array("tab_name"=>base64_decode($found[2]),"tab_file"=>$file,"tab_data"=>$data,"tab_icon_url"=>"../pixmaps/alienvault_icon.gif","disable"=>1));
		elseif (preg_match("/([^\_]+)\_([^\_]+)\_([^\_]+)\.avt/",$file,$found))
			return array($found[3],array("tab_name"=>base64_decode($found[2]),"tab_file"=>$file,"tab_data"=>$data,"tab_icon_url"=>"../pixmaps/alienvault_icon.gif","disable"=>0));
	} else return array("",array());
}
Session::logcheck("MenuControlPanel", "ControlPanelExecutive");
$conf = $GLOBALS["CONF"];
$jpgraph = $conf->get_conf("jpgraph_path");
$configs_dir = $conf->get_conf('panel_configs_dir');
$tabsavt = gettabsavt($configs_dir);
require_once "$jpgraph/jpgraph.php";
//
// This will show errors (both PHP Errors and those detected in the code)
// as graphics, so they can be read.
//
function mydie($errno, $errstr = '', $errfile = '', $errline = '') {
    $err = ($errstr) ? $errstr : $errno;
    if ($errfile) {
        switch ($errno) {
            case 1:
                $errprefix = 'Error';
                break;

            case 2:
                $errprefix = 'Warning';
                break;

            case 8:
                $errprefix = 'Notice';
                break;

            default:
                return; // dont show E_STRICT errors
                
        }
        $err = "$errprefix: $err in '$errfile' line $errline";
}
$error = new JpGraphError();
$error->Raise($err);
exit;
}
set_error_handler('mydie');
$ajax = & new Window_Panel_Ajax();
$filename = (GET('panel_id') >= 1000) ? $configs_dir."/".$tabsavt[GET('panel_id')]['tab_file'] : null;
$all_options = $ajax->loadConfig(GET('id'),$filename);

$options = $all_options['plugin_opts'];
$sql = $options['graph_sql'];
if (!preg_match('/^\s*\(?\s*SELECT\s/i', $sql) || preg_match('/\sFOR\s+UPDATE/i', $sql) || preg_match('/\sINTO\s+OUTFILE/i', $sql) || preg_match('/\sLOCK\s+IN\s+SHARE\s+MODE/i', $sql)) {
    mydie(_("SQL Query invalid due security reasons"));
}

$db = new ossim_db;

// User sensor filtering
$sensor_where = "";
$sensor_where_sid = "";
if (Session::allowedSensors() != "") {
	require_once 'classes/Event_viewer.inc';
	$conn_snort = $db->snort_connect();
	$user_sensors = explode(",",Session::allowedSensors());
	$snortsensors = Event_viewer::GetSensorSids($conn_snort);
	$sensor_str = "";
	foreach ($user_sensors as $user_sensor) if ($user_sensor != "")
		if (count($snortsensors[$user_sensor]) > 0) $sensor_str .= ($sensor_str != "") ? ",".implode(",",$snortsensors[$user_sensor]) : implode(",",$snortsensors[$user_sensor]);
	if ($sensor_str == "") $sensor_str = "0";
	$sensor_where = " alarm.snort_sid in (" . $sensor_str . ")";
	$sensor_where_sid = "sid in (" . $sensor_str . ")";
}

$dbname = $options['graph_db'];
$method = $dbname == 'snort' ? 'snort_connect' : 'connect';
$conn = $db->$method();
$data = array();

// Filtro de SENSOR by USER (DASHBOARD -> SECURITY)
if (preg_match("/from alarm/",$sql) && $dbname == "ossim" && $sensor_where != "") {
	$sql = str_replace ("where","where $sensor_where AND ",$sql);
	//echo "Ejecutando $sql en $dbname<br>"; exit;
}
elseif (preg_match("/from tcphdr/",$sql) && $dbname == "snort" && $sensor_where_sid != "") {
	$sql = str_replace ("where","where tcphdr.$sensor_where_sid AND ",$sql);
	//echo "Ejecutando $sql en $dbname<br>"; exit;
}
elseif (preg_match("/SELECT \* FROM\n\(select count\(\*\) as Today from event where event\.timestamp \> CURDATE\(\)/",$sql) 
		&& $dbname == "snort" && $sensor_where_sid != "") {
	$sql = "SELECT * FROM 
		(select count(*) as Today from acid_event where timestamp > CURDATE() AND $sensor_where_sid) as Today, 
		(select count(*) as Yesterday from acid_event where timestamp > DATE_ADD(CURDATE(), INTERVAL -1 DAY) and timestamp < CURDATE() AND $sensor_where_sid) as Yesterday, 
		(select count(*) as 2_Days_Ago from acid_event where timestamp > DATE_ADD(CURDATE(), INTERVAL -2 DAY) and timestamp < DATE_ADD(CURDATE(), INTERVAL -1 DAY) AND $sensor_where_sid) as 2_Days_Ago, 
		(select count(*)/7 as Week from acid_event where timestamp > DATE_ADD(CURDATE(), INTERVAL -6 DAY) and timestamp < NOW() AND $sensor_where_sid) as Week, 
		(select count(*)/14 as Two_Weeks from acid_event where timestamp > DATE_ADD(CURDATE(), INTERVAL -13 DAY) and timestamp < NOW() AND $sensor_where_sid) as Two_Weeks";
}
elseif (preg_match("/occurrences from incident/",$sql) && $dbname == "ossim") {
	$sql = "(select type_id as Tipo, count(*) as occurrences from incident WHERE in_charge='".$_SESSION['_user']."' group by type_id) UNION (select i.type_id as Tipo, count(*) as occurrences from incident i,incident_ticket t WHERE i.id=t.incident_id AND t.users='".$_SESSION['_user']."' group by i.type_id)";
}

if (!$rs = $conn->Execute($sql)) {
    mydie("Error was: " . $conn->ErrorMsg() . "\n\nQuery was: " . $sql);
}
if ($rs->EOF) mydie("No data available yet.");

// Check options and use columns or rows as legend.
switch ($options['graph_legend_field']) {
    case 'col':
        for ($i = 0; $i < $rs->FieldCount(); $i++) {
            $field = $rs->FetchField($i);
            $data['legend'][] = $field->name;
            $data['values'][] = $rs->fields[$i];
        }
        break;

    case 'row':
        while (!$rs->EOF) {
            $data['legend'][] = $rs->fields[0];
            $data['values'][] = $rs->fields[1];
            $rs->MoveNext();
        }
        break;
}
$data['title'] = $options['graph_title'];
$width = 460;
if ($options['graph_type'] == 'pie') {
    require_once "$jpgraph/jpgraph_pie.php";
    require_once "$jpgraph/jpgraph_pie3d.php";
    // Setup graph
    $graph = new PieGraph($width, 250, "auto");
    $graph->SetFrame(false);
    //$graph->SetShadow();
    if ($options['graph_pie_antialiasing']) {
        $graph->SetAntiAliasing();
    }
    // Setup graph title
    $graph->title->Set($data['title']);
    $graph->title->SetFont(FF_FONT1, FS_BOLD);
    // Create pie plot
    if ($options['graph_pie_3dangle'] == 0) {
        $plot = new PiePlot($data['values']);
    } else {
        $plot = new PiePlot3D($data['values']);
        $plot->setAngle($options['graph_pie_3dangle']);
    }
    //$plot->SetFont(FF_VERDANA,FS_BOLD);
    //$plot->SetFontColor("darkred");
    $plot->SetSize(0.3);
    $plot->setCenter($options['graph_pie_center']);
    if (count($data['legend'] > 10)) {
		$i = 0;
		foreach($data['legend'] as $key=>$val) {
			if ($i > 10) unset ($data['legend'][$key]);
			$i++;
		}
	}
	
	$plot->SetLegends($data['legend']);
    $plot->setTheme($options['graph_pie_theme']);
    if ($options['graph_plotshadow']) {
        $plot->SetShadow();
    }
    //$plot->SetStartAngle(M_PI/8);
    //printr($options['graph_pie_explode_pos']);
    switch ($options['graph_pie_explode']) {
        case 'all':
            $plot->ExplodeAll(10);
            break;

        case 'pos':
            $plot->ExplodeSlice((int)$options['graph_pie_explode_pos']);
            break;
    }
    $graph->Add($plot);
} elseif ($options['graph_type'] == 'bars') {
    require_once "$jpgraph/jpgraph_bar.php";
    $background = "white";
    $color = "navy";
    $color2 = "lightsteelblue";
    // Setup graph
    $graph = new Graph($width, 250, "auto");
    $graph->SetFrame(false);
    $graph->SetScale('textlin', $options['graph_y_min'], $options['graph_y_max'], $options['graph_x_min'], $options['graph_x_max']);
    $graph->yaxis->scale->SetGrace($options['graph_y_top'], $options['graph_y_bot']);
    $graph->xaxis->scale->SetGrace($options['graph_x_top'], $options['graph_x_bot']);
    $graph->SetMarginColor($background);
    $graph->img->SetMargin(40, 30, 20, 40);
    //$graph->SetShadow();
    // Setup graph title
    $graph->title->Set($data['title']);
    $graph->title->SetFont(FF_FONT1, FS_BOLD);
    $plot = new BarPlot($data['values']);
    $plot->SetWidth(0.6); // set bars width in percentage
    $plot->SetAbsWidth(20); // set bars width in pixels
    $color = $options['graph_color'];
    // One plain color
    if ($options['graph_gradient'] == 0) {
        $plot->SetFillColor($color);
        // Gradient color
        
    } elseif ($options['graph_gradient'] != 0) {
        // Made the color $degree darker
        // Given color "#FF1E1E", we split the color into groups
        // (red, gree, blue): "FF", "1E" and "1E" and convert them to decimal.
        // Then sum to each group $degree and transform the result back to hex.
        $degree = - 75;
        $color2 = '';
        for ($x = 1; $x < 6; $x+= 2) {
            $dec = hexdec($color{$x} . $color{$x + 1});
            if (($dec + $degree) < 256) {
                if (($dec + $degree) > - 1) {
                    $dec+= $degree;
                } else {
                    $dec = 0;
                }
            } else {
                $dec = 255;
            }
            $color2.= dechex($dec);
        }
        $color2 = "#$color2";
        $plot->SetFillGradient($color, $color2, $options['graph_gradient']);
    }
    if (!empty($options['graph_show_values'])) {
        if (!isset($plot->value) || !method_exists($plot->value, 'show')) {
            mydie("This JPGraph version does not support 'Show values'");
        }
        $plot->value->Show();
    }
    if ($options['graph_plotshadow']) {
        $plot->SetShadow();
    }
    $graph->Add($plot);
    $graph->xaxis->SetTickLabels($data['legend']);
    //$graph->yaxis->scale->SetGrace(5); //show 5% more values than max
    //$graph->xaxis->SetLabelAngle(40); // only with TTF fonts
    //$graph->title->Set($title);
    //$graph->xaxis->title->Set($titley);
    //$graph->yaxis->title->Set($titlex);
    
} elseif ($options['graph_type'] == 'points') {
    require_once "$jpgraph/jpgraph_line.php";
    $background = "white";
    $incref = false;
    // Setup graph
    $graph = new Graph($width, 250, "auto");
    $graph->SetFrame(false);
    $graph->SetScale('textlin', $options['graph_y_min'], $options['graph_y_max'], $options['graph_x_min'], $options['graph_x_max']);
    $graph->yaxis->scale->SetGrace($options['graph_y_top'], $options['graph_y_bot']);
    $graph->xaxis->scale->SetGrace($options['graph_x_top'], $options['graph_x_bot']);
    $graph->SetMarginColor($background);
    $graph->img->SetMargin(60, 60, 40, 60);
    //$graph->SetShadow();
    $graph->xgrid->Show(true);
    $graph->legend->SetLayout(LEGEND_HOR);
    $graph->legend->Pos(0.5, 0.96, "center", "bottom");
    //$graph->SetShadow();
    // Setup graph title
    $graph->title->Set($data['title']);
    $graph->title->SetFont(FF_FONT1, FS_BOLD);
    $graph->xaxis->SetTickLabels($data['legend']);
    $graph->yaxis->title->SetFont(FF_FONT1, FS_BOLD);
    $graph->xaxis->title->SetFont(FF_FONT1, FS_BOLD);
    $graph->yaxis->SetColor("black");
    // Create the data plot
    $plot = new LinePlot($data['values']);
    $color = $options['graph_color'];
    $plot->SetColor($color);
    $plot->SetWeight(2);
    $plot->mark->SetType(MARK_FILLEDCIRCLE);
    $plot->mark->SetColor('blue');
    if (!empty($options['graph_point_legend'])) {
        $plot->SetLegend($options['graph_point_legend']);
    }
    if (!empty($options['graph_show_values'])) {
        if (!isset($plot->value) || !method_exists($plot->value, 'show')) {
            mydie("This JPGraph version does not support 'Show values'");
        }
        $plot->value->HideZero();
        $plot->value->SetFormat('%u');
        $plot->value->SetFont(FF_FONT1, FS_BOLD);
        $plot->value->SetColor('blue');
        $plot->value->SetMargin(10);
        $plot->value->Show();
    }
    // Add the data plot to the graph
    $graph->Add($plot);
} elseif ($options['graph_type'] == 'radar') {
    require_once "$jpgraph/jpgraph_radar.php";
    $background = "white";
    $incref = false;
    // Setup graph
    $graph = new RadarGraph($width, 250, "auto");
    //$graph->SetShadow();
    $graph->SetFrame(false);
    $graph->title->Set($data['title']);
    $graph->title->SetFont(FF_FONT1, FS_BOLD);
    $graph->SetMarginColor($background);
    $graph->img->SetMargin(40, 30, 20, 40);
    $graph->SetTitles($data['legend']);
    $graph->SetCenter(0.5, 0.55);
    $graph->HideTickMarks();
    $graph->SetColor($background);
    $graph->grid->SetColor('darkgray');
    $graph->grid->Show();
    $graph->axis->title->SetMargin(5);
    $graph->SetGridDepth(DEPTH_BACK);
    $graph->SetSize(0.6);
    $plot = new RadarPlot($data['values']);
    $color = $options['graph_color'];
    $plot->SetColor($color);
    $plot->SetLineWeight(1);
    $plot->mark->SetType(MARK_IMG_SBALL, 'red');
    if (!empty($options['graph_point_legend'])) {
        $plot->SetLegend($options['graph_point_legend']);
    }
    if (!empty($options['graph_radar_fill'])) {
        $plot->SetFillColor($color);
    }
    /*
    // Todo: Add the possibility to add multiple graphs into one radar, they look nifty.
    // Uncomment the lines below if you want to see it (number of $data2 && $data3 array elements must match those of the provided data.
    
    $data2 = array(45,44,90,20,140);
    $data3 = array(23,34,45,8,97);
    
    $plot2 = new RadarPlot($data2);
    $plot2->SetColor('red@0.4');
    $plot2->SetLineWeight(1);
    $plot2->SetLegend("Goal 2008");
    $plot2->SetFillColor('blue@0.7');
    
    $plot3 = new RadarPlot($data3);
    $plot3->SetColor('red@0.4');
    $plot3->SetLineWeight(1);
    $plot3->SetLegend("Goal 2009");
    $plot3->SetFillColor('green@0.7');
    
    $graph->Add($plot2);
    $graph->Add($plot3);
    */
    // Add the data plot to the graph
    $graph->Add($plot);
} else {
    die("Graph type not valid");
}
$graph->Stroke();
// Close db connection
$db->close($conn);
?>
