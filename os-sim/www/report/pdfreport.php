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
* - clean_tmp_files()
* - create_image()
* Classes list:
*/
require_once ('classes/Session.inc');
Session::logcheck("MenuReports", "ReportsHostReport");
require_once 'classes/Security.inc';
require_once 'classes/PDF.inc';
session_cache_limiter('private');
$pathtographs = dirname($_SERVER['REQUEST_URI']);
$proto = "http";
$date_from = (POST('date_from') != "") ? POST('date_from') : strftime("%d/%m/%Y %H:%M:%S", time() - (24 * 60 * 60));
$date_to = (POST('date_to') != "") ? POST('date_to') : strftime("%d/%m/%Y %H:%M:%S", time());


if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") $proto = "https";
$datapath = "$proto://" . $_SERVER['SERVER_ADDR'] . ":" . $_SERVER['SERVER_PORT'] . "$pathtographs/graphs";
//This is used to give the name to the created pdf
$date_gen = date("d-m-y");
function clean_tmp_files() {
    if (isset($GLOBALS['tmp_files'])) {
        foreach($GLOBALS['tmp_files'] as $file) {
            unlink($file);
        }
    }
}
register_shutdown_function('clean_tmp_files');
function create_image($url, $args = array()) {
    foreach($args as $k => $v) {
        $_GET[$k] = $v;
    }
    ob_start();
    include $url;
    $cont = ob_get_clean();
    $tmp_name = tempnam('/tmp', 'ossim_');
    $GLOBALS['tmp_files'][] = $tmp_name;
    $fd = fopen($tmp_name, 'w');
    fputs($fd, $cont);
    fclose($fd);
    return $tmp_name;
}


if (POST('submit_security')) {
    $pdf = new PDF("OSSIM Security Report");
    $newpage = false;
    /* rows per table */
    if (!is_numeric($limit = POST('limit'))) $limit = 10;
    if (POST('attacked') == "on") {
		$pdf->AttackedHosts($limit,"", $date_from, $date_to);
        $args = array(
            'limit' => $limit,
            'target' => 'ip_dst',
			'date_from' => $date_from,
			'date_to' => $date_to
        );
        $image = create_image('./graphs/attack_graph.php', $args);
        $pdf->Image($image, $pdf->GetX() , $pdf->GetY() , "110", "70", "PNG");
		$newpage = true;
    }
    if (POST('attacker') == "on") {
        if ($newpage) $pdf->AddPage();
        $pdf->AttackerHosts($limit,"", $date_from, $date_to);
        $args = array(
            'limit' => $limit,
            'target' => 'ip_src',
			'date_from' => $date_from,
			'date_to' => $date_to
        );
        $image = create_image('./graphs/attack_graph.php', $args);
        $pdf->Image($image, $pdf->GetX() , $pdf->GetY() , "110", "70", "PNG");
        $newpage = true;
    }
    if (POST('eventsbyhost') == "on") {
        if ($newpage) $pdf->AddPage();
        $pdf->Events($limit,"", $date_from, $date_to);
        $args = array(
            'hosts' => $limit,
			'date_from' => $date_from,
			'date_to' => $date_to
        );
        $image = create_image('./graphs/events_received_graph.php', $args);
        $pdf->Image($image, $pdf->GetX() , $pdf->GetY() , "120", "60", "PNG");
        $newpage = true;
    }
    if (POST('ports') == "on") {
        if ($newpage) $pdf->AddPage();
        $pdf->Ports($limit,"", $date_from, $date_to);
        $args = array(
            'ports' => $limit,
			'date_from' => $date_from,
			'date_to' => $date_to
        );
        $image = create_image('./graphs/ports_graph.php', $args);
        $pdf->Image($image, $pdf->GetX() , $pdf->GetY() , "110", "70", "PNG");
        $newpage = true;
    }
    if (POST('eventsbyrisk') == "on") {
        if ($newpage) $pdf->AddPage();
        $pdf->EventsByRisk($limit,"", $date_from, $date_to);
    }
    $pdf->Output("OSSIM-" . $date_gen . ".pdf", "I");
} elseif (POST('submit_metrics')) {
    $pdf = new PDF("OSSIM Metrics Report");
    $newpage = false;
    if (POST('time_day') == "on") {
        $pdf->Metrics("day", "compromise", "global");
        $pdf->Metrics("day", "compromise", "net");
        $pdf->Metrics("day", "compromise", "host");
        $pdf->AddPage();
        $pdf->Metrics("day", "attack", "global");
        $pdf->Metrics("day", "attack", "net");
        $pdf->Metrics("day", "attack", "host");
        $newpage = true;
    }
    if (POST('time_week') == "on") {
        if ($newpage) $pdf->AddPage();
        $pdf->Metrics("week", "compromise", "global");
        $pdf->Metrics("week", "compromise", "net");
        $pdf->Metrics("week", "compromise", "host");
        $pdf->AddPage();
        $pdf->Metrics("week", "attack", "global");
        $pdf->Metrics("week", "attack", "net");
        $pdf->Metrics("week", "attack", "host");
        $newpage = true;
    }
    if (POST('time_month') == "on") {
        if ($newpage) $pdf->AddPage();
        $pdf->Metrics("month", "compromise", "global");
        $pdf->Metrics("month", "compromise", "net");
        $pdf->Metrics("month", "compromise", "host");
        $pdf->AddPage();
        $pdf->Metrics("month", "attack", "global");
        $pdf->Metrics("month", "attack", "net");
        $pdf->Metrics("month", "attack", "host");
        $newpage = true;
    }
    if (POST('time_year') == "on") {
        if ($newpage) $pdf->AddPage();
        $pdf->Metrics("year", "compromise", "global");
        $pdf->Metrics("year", "compromise", "net");
        $pdf->Metrics("year", "compromise", "host");
        $pdf->AddPage();
        $pdf->Metrics("year", "attack", "global");
        $pdf->Metrics("year", "attack", "net");
        $pdf->Metrics("year", "attack", "host");
    }
    $pdf->Output("OSSIM-" . $date_gen . ".pdf", "I");
} elseif (POST('submit_incident')) {
    $alarm = POST('Alarm');
    $event = POST('Event');
    $metric = POST('Metric');
    $anomaly = POST('Anomaly');
    $vulnerability = POST('Vulnerability');
    $type = POST('Type');
    $in_charge = POST('In_Charge');
    $title = POST('Title');
    $date = POST('Date');
    $status = POST('Status');
    ossim_valid($type, OSS_NULLABLE, OSS_ALPHA, OSS_PUNC, 'illegal:' . _("type"));
    ossim_valid($status, OSS_NULLABLE, OSS_ALPHA, OSS_PUNC, 'illegal:' . _("status"));
    ossim_valid($in_charge, OSS_NULLABLE, OSS_ALPHA, OSS_PUNC, 'illegal:' . _("in_charge"));
    if (ossim_error()) {
        die(ossim_error());
    }
    $pdf = new PDF("OSSIM Tickets Report", "P", "mm", "A4");
    $pdf->IncidentGeneralData($title, $date);
    $priority = 0;
    if (POST('High')) {
        $priority = " (priority > 7";
        if (POST('Medium')) $priority.= " or ( priority > 4 and priority <= 7 )";
        if (POST('Low')) $priority.= " or ( priority > 0 and priority <= 4 )";
        $priority.= ")";
    } elseif (POST('Medium')) {
        $priority = " (priority > 4 and priority =< 7";
        if (isset($_POST["Low"])) $priority.= " or ( priority > 0 and priority <= 4 )";
        $priority.= ")";
    } elseif (POST('Low')) $priority = " ( priority > 0 and priority <= 4 )";
    $fil = "";
    if ($type != "ALL") $fil.= " and type_id = '$type'";
    if ($status != "ALL") $fil.= " and status = '$status'";
    if ($in_charge != "") $fil.= " and in_charge = '$in_charge'";
   
   /* metrics */
    if (POST('Metric')) {
        $pdf->IncidentSummary(gettext("METRICS") , "Metric", $metrics_notes, $priority, $fil);
        $ids = $pdf->get_metric_ids($priority, $fil);
        foreach($ids as $incident_id) {
            $pdf->Incident($incident_id);
        }
    }
    /* alarms */
    if (POST('Alarm')) {
        $pdf->IncidentSummary(gettext("ALARMS") , "Alarm", $alarms_notes, $priority, $fil);
        $ids = $pdf->get_alarm_ids($priority, $fil);
        foreach($ids as $incident_id) {
            $pdf->Incident($incident_id);
        }
    }
    /* events */
    if (POST('Event')) {
        $pdf->IncidentSummary(gettext("ALERTS") , "Event", $events_notes, $priority, $fil);
        $ids = $pdf->get_event_ids($priority, $fil);
        foreach($ids as $incident_id) {
            $pdf->Incident($incident_id);
        }
    }
    /* anomalies */
    if (POST('Anomaly')) {
        $pdf->IncidentSummary(gettext("ANOMALIES") , "Anomaly", $events_notes, $priority, $fil);
        $ids = $pdf->get_anomaly_ids($priority, $fil);
        foreach($ids as $incident_id) {
            $pdf->Incident($incident_id);
        }
    }
    /* vulnerabilities */
    if (POST('Vulnerability')) {
        $pdf->IncidentSummary(gettext("VULNERABILITIES") , "Vulnerability", $vulnerabilities_notes, $priority, $fil);
        $ids = $pdf->get_vulnerability_ids($priority, $fil);
        foreach($ids as $incident_id) {
            $pdf->Incident($incident_id);
        }
    }
    $pdf->Output("OSSIM-" . $date_gen . ".pdf", "I");
} elseif (POST('submit_alarms')) {
    $report_type = "alarm";
    $pdf = new PDF("OSSIM Alarms Report");
    $newpage = false;
    /* rows per table */
    if (!is_numeric($limit = POST('limit'))) $limit = 10;
    if (POST('attacked') == "on") {
        $pdf->AttackedHosts($limit, "alarm", $date_from, $date_to);
        $args = array(
            'hosts' => $limit,
            'target' => 'dst_ip',
            'type' => 'alarm',
			'date_from' => $date_from,
			'date_to' => $date_to
        );
        $image = create_image('./graphs/attack_graph.php', $args);
        $pdf->Image($image, $pdf->GetX() , $pdf->GetY() , "110", "70", "PNG");
        $newpage = true;
    }
    if (POST('attacker') == "on") {
        if ($newpage) $pdf->AddPage();
        $pdf->AttackerHosts($limit, "alarm", $date_from, $date_to);
        $args = array(
            'hosts' => $limit,
            'target' => 'src_ip',
            'type' => 'alarm',
			'date_from' => $date_from,
			'date_to' => $date_to
        );
        $image = create_image('./graphs/attack_graph.php', $args);
        $pdf->Image($image, $pdf->GetX() , $pdf->GetY() , "110", "70", "PNG");
        $newpage = true;
    }
    if (POST('ports') == "on") {
        if ($newpage) $pdf->AddPage();
        $pdf->Ports($limit, "alarm", $date_from, $date_to);
        $args = array(
            'ports' => $limit,
            'type' => 'alarm',
			'date_from' => $date_from,
			'date_to' => $date_to
        );
        $image = create_image('./graphs/ports_graph.php', $args);
        $pdf->Image($image, $pdf->GetX() , $pdf->GetY() , "110", "70", "PNG");
        $newpage = true;
    }
    if (POST('alarmsbyhost') == "on") {
        if ($newpage) $pdf->AddPage();
        $pdf->Events($limit, "alarm", $date_from, $date_to);
        $args = array(
            'hosts' => $limit,
            'type' => 'alarm',
			'date_from' => $date_from,
			'date_to' => $date_to
        );
        $image = create_image('./graphs/events_received_graph.php', $args);
        $pdf->Image($image, $pdf->GetX() , $pdf->GetY() , "120", "60", "PNG");
        $newpage = true;
    }
    if (POST('alarmsbyrisk') == "on") {
        if ($newpage) $pdf->AddPage();
        $pdf->EventsByRisk($limit, "alarm", $date_from, $date_to);
    }
    $pdf->Output("OSSIM-" . $date_gen . ".pdf", "I");
}
?>
