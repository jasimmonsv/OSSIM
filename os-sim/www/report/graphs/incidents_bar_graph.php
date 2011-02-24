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
$by = GET('by');
// puede ser para el wizard run
$type=GET('type');

ossim_valid($by, OSS_ALPHA, OSS_SPACE, OSS_SCORE, 'illegal:' . _("Target"));
if (ossim_error()) {
    die(ossim_error());
}
// define colors
define('COLOR1','#D6302C');
define('COLOR2','#3933FC');
define('COLOR3','green');
define('COLOR4','yellow');
define('COLOR5','pink');
define('COLOR6','#40E0D0');
define('COLOR7','#00008B');
define('COLOR8','#800080');
define('COLOR9','#FFA500');
define('COLOR10','#A52A2A');
define('COLOR11','#228B22');
define('COLOR12','#D3D3D3');
//
$conf = $GLOBALS["CONF"];
$jpgraph = $conf->get_conf("jpgraph_path");
include "$jpgraph/jpgraph.php";
include "$jpgraph/jpgraph_bar.php";
$db = new ossim_db();
$conn = $db->connect();

if ($by == "monthly_by_status") {
    $year_ago_time = mktime(0, 0, 0, date('m') - 12, date('d') , date('Y'));
    $year_ago_date = date('Y-m-d H:i:s', $year_ago_time);
    for ($i = 12; $i >= 1; $i--) {
        $time = mktime(0, 0, 0, date('m') - $i, date('d') , date('Y'));
        $data[date('M-y', $time) ] = 0;
    }
    //$sql = "SELECT count(status) as num_incidents, status, " . "date_format(last_update, '%Y-%m') as month, " . "date_format(last_update, '%b-%y') as label " . "FROM incident " . "WHERE status='Closed' AND last_update >= ? " . "GROUP BY month";
    $sql=array();
    if($type=="wizard"){
        // se llama desde el wizard
        // obtenemos el host y la red
        $shared = new DBA_shared(GET('shared'));
		$asset = $shared->get('TicketsStatus5');
        if(!is_array($asset)){
            // es un host
            $host[0]=array(
                'host'=>$asset,
                'host2'=>($asset=="%") ? "1=1" : "incident_vulns.ip='".$asset."'"
            );
        }else{
            // es una red
            $host=array();
            foreach($asset as $value){
                if (strpos($value, '/') === false) {
                    // si es un host
                    $host[]=array(
                        'host'=>$value,
                        'host2'=>"incident_vulns.ip in ('".$value."')"
                    );
                }else{
                    // es una red
                    // obtenemos la menor y mayor ip de la red
                    require_once("classes/CIDR.inc");
                    $net_range = CIDR::expand_CIDR($value,"SHORT","IP");
                    $sqlTemp="inet_aton(incident_vulns.ip)>=inet_aton('".$net_range[0]."') && inet_aton(incident_vulns.ip)<=inet_aton('".$net_range[1]."')";
                    $host[]=array(
                        'host'=>$value,
                        'host2'=>$sqlTemp
                    );
                }
            }
        }
        //
        foreach ($host as $value){
            $sql[] = "SELECT count(status) as num_incidents, status, date_format(last_update, '%Y-%m') as month,
            date_format(last_update, '%b-%y') as label FROM (SELECT DISTINCT incident.* FROM incident, incident_event
            WHERE (UPPER(incident_event.src_ips) LIKE '%".$value['host']."%' OR UPPER(incident_event.dst_ips) LIKE '%".$value['host']."%')
            AND incident_event.incident_id = incident.id
            UNION
            SELECT DISTINCT incident.* FROM incident, incident_alarm WHERE (UPPER(incident_alarm.src_ips) LIKE '%".$value['host']."%'
            OR UPPER(incident_alarm.dst_ips) LIKE '%".$value['host']."%') AND incident_alarm.incident_id = incident.id
            UNION
            SELECT DISTINCT incident.* FROM incident, incident_metric WHERE (UPPER(incident_metric.target) LIKE '%".$value['host']."%')
            AND incident_metric.incident_id = incident.id
            UNION
            SELECT DISTINCT incident.* FROM incident, incident_vulns WHERE ".$value['host2']." AND
            incident_vulns.incident_id = incident.id) as i WHERE status='Closed' AND last_update >= ? GROUP BY month";
        }
    }else{
        $sql[0] = "SELECT count(status) as num_incidents, status, " . "date_format(last_update, '%Y-%m') as month, " . "date_format(last_update, '%b-%y') as label " . "FROM incident " . "WHERE status='Closed' AND last_update >= ? " . "GROUP BY month";
    }
    //print_r($sql);
    //var_dump($sql);
    $params = array(
            $year_ago_date
        );
    foreach($sql as $value){
        if (!$rs = $conn->Execute($value, $params)) die($conn->ErrorMsg());
        while (!$rs->EOF) {
            $num_inc = $rs->fields['num_incidents'];
            $month = $rs->fields['label'];
            $data[$month] += $num_inc;
            $rs->MoveNext();
        }
    }
    $labelx = array_keys($data);
    $datay = array_values($data);
    $title = '';
    $titley = _("Month") . '-' . _("Year");
    $titlex = _("Num. Tickets");
    //$width = 700;
    $width = 650;
} elseif ($by == "resolution_time") {
    /*
    //echo "Ticket search";
	$list = Incident::search($conn, array(
        'status' => 'Closed'
    ));*/
    if($type=="wizard"){
        // se llama desde el wizard
        // obtenemos el host y la red
        $shared = new DBA_shared(GET('shared'));
		$asset = $shared->get('TicketsStatus4');
        $list = Incident::incidents_by_status($conn,$asset,true);
        $list=$list[0];
    }else{
        $list = Incident::search($conn, array(
        'status' => 'Closed'
        ));
    }
    
     //   print_r($list);
    $ttl_groups[1] = 0;
    $ttl_groups[2] = 0;
    $ttl_groups[3] = 0;
    $ttl_groups[4] = 0;
    $ttl_groups[5] = 0;
    $ttl_groups[6] = 0;
    $total_days = 0;
    $day_count;
    foreach($list as $incident) {
        if($incident->get_status()=='Closed'){
            $ttl_secs = $incident->get_life_time('s');
            $days = round($ttl_secs / 60 / 60 / 24);
            $total_days+= $days;
            $day_count++;
            if ($days < 1) $days = 1;
            if ($days > 6) $days = 6;
            @$ttl_groups[$days]++;
        }
    }
    $datay = array_values($ttl_groups);
    $labelx = array(
        '1 ' . _("day") ,
        '2 ' . _("days") ,
        '3 ' . _("days") ,
        '4 ' . _("days") ,
        '5 ' . _("days") ,
        '6 ' . _("or more")
    );
    $title = '';
    if ($day_count < 1) $day_count = 1;
    $titley = _("Duration in days.") . " " . _("Average:") . " " . $total_days / $day_count;
    $titlex = _("Num. Tickets");
    //$width = 500;
    $width = 650;
} else {
    die("Invalid by");
}
$background = "white";
$color = "navy";
$color2 = "navy";
//$color2 = "lightsteelblue";
// Setup graph
$graph = new Graph($width, 250, "auto");
$graph->SetScale("textlin");
$graph->SetMarginColor($background);
$graph->img->SetMargin(40, 30, 20, 40);
//$graph->SetShadow();
//Setup Frame
$graph->SetFrame(true, "#ffffff");
// Setup graph title
$graph->title->Set($title);
$graph->title->SetFont(FF_FONT1, FS_BOLD);
$bplot = new BarPlot($datay);
$bplot->SetWidth(0.6);
//$bplot->SetFillGradient($color, $color2, GRAD_MIDVER);
//$bplot->SetColor($color);
// color@transparencia
$bplot->SetFillColor(array(COLOR1."@0.5"));
//
$bplot->SetShadow(array(COLOR1."@0.7"),5,5);
$bplot->SetColor(array(COLOR1."@1"));
//
$graph->Add($bplot);
$graph->xaxis->SetTickLabels($labelx);
//$graph->xaxis->SetLabelAngle(40); // only with TTF fonts
$graph->title->Set($title);
$graph->xaxis->title->Set($titley);
$graph->yaxis->title->Set($titlex);
$graph->Stroke();
?>
