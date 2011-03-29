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
* - PDF()
* - Header()
* - Footer()
* - __WriteBold()
* - __WriteTitle()
* - __WriteTable()
* - __AttackHosts()
* - AttackedHosts()
* - AttackerHosts()
* - Events()
* - Ports()
* - EventsByRisk()
* - __get_ids()
* - get_metric_ids()
* - get_alarm_ids()
* - get_event_ids()
* - get_vulnerability_ids()
* - IncidentGeneralData()
* - IncidentSummary()
* - Incident()
* - Metrics()
* - _readstr()
* - _readstr_int()
* - _parsemempng()
* - MemImage()
* - GDImage()
* Classes list:
* - PDF extends FPDF
*/
require_once 'ossim_conf.inc';
require_once 'classes/SecurityReport.inc';
require_once 'classes/Log_action.inc';
require_once 'classes/Incident.inc';
require_once 'classes/Host.inc';
$path_conf = $GLOBALS["CONF"];
$fpdf_path = $path_conf->get_conf("fpdf_path");
define('FPDF_FONTPATH', 'font/');
define('DEFAULT_LINE_HEIGTH', 6);
require ($fpdf_path . '/fpdf.php');
class PDF extends FPDF {
    var $ossim_conn;
    var $snort_conn;
    var $conf;
    var $security_report;
    /*
    Function PDF
    $title = Title given to the PDF report
    $orientation = Can be P (portrait) or L (Landscape)
    $unit = User measure unit:
    * pt: point
    * mm: millimeter
    * cm: centimeter
    * in: inch
    
    $format = The format used for pages:
    * A3
    * A4
    * A5
    * Letter
    * Legal
    */
    function PDF($title = "OSSIM Report", $orientation = 'P', $unit = 'mm', $format = 'A5') {
        $this->FPDF($orientation, $unit, $format);
        $this->title = $title;
        /* ossim framework conf */
        $this->conf = $GLOBALS["CONF"];
        /* connection to ossim/snort database */
        $db = new ossim_db();
        $this->ossim_conn = $db->connect();
        $this->snort_conn = $db->snort_connect();
        /* security report */
        $this->security_report = new SecurityReport();
        /* pdf properties */
        $this->AliasNbPages();
        $this->SetTitle($title);
        $this->SetCreator('OSSIM framework');
        $this->AddPage();
        /* Log pdf created */
        Log_action::log(19, NULL);
    }
    function Header() {
        $www_base = $this->conf->get_conf("base_dir");
        $this->Image($www_base . '/pixmaps/logo3.jpg', 0, 0, 30);
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(20);
        $this->Cell(0, 6, $this->title, 'B', 0, 'R');
        $this->Ln(20);
    }
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, strftime("%d-%b-%Y", time()) , 0, 0, 'L');
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'R');
    }
    function __WriteBold($text, $color = "") {
        $this->SetFont('Arial', 'B', 12);
        if ($color == "red") $this->SetTextColor(130, 15, 15);
        elseif ($color == "orange") $this->SetTextColor(200, 80, 15);
        elseif ($color == "green") $this->SetTextColor(4, 100, 4);
        $this->Write(DEFAULT_LINE_HEIGTH, $text);
        $this->SetFont('Arial', '', 12);
        $this->SetTextColor(0);
    }
    function __WriteTitle($title, $color = "blue") {
        $this->SetFont('Arial', 'B', 14);
        if ($color == "blue") $this->SetTextColor(102, 102, 153);
        elseif ($color == "red") $this->SetTextColor(130, 15, 15);
        elseif ($color == "orange") $this->SetTextColor(200, 80, 15);
        $this->MultiCell(125, 6, $title);
        $this->SetTextColor(0);
        $this->SetFont('', '', 10);
        $this->Ln();
    }
    /*
    __WriteTable()
    
    example of use:
    
    $title   = "TITLE";
    $length  = array (15, 15);
    $content = array (
    array ("host", "occurrences"),
    array ("192.168.0.25", "6"),
    array ("192.168.0.26", "9")
    );
    
    TITLE
    +---------------+---------------+
    | host          | occurrences   |
    +---------------+---------------+
    | 192.168.0.25  | 6             |
    |---------------+---------------|
    | 192.168.0.26  | 9             |
    +---------------+---------------+
    */
    function __WriteTable($title, $length, $content, $adjust, $color = "blue") {
        $fill = 1;
        $count = 0;
        $this->__WriteTitle($title, $color);
        if ($color == "blue") $this->SetFillColor(102, 102, 153);
        elseif ($color == "red") $this->SetFillColor(130, 15, 15);
        elseif ($color == "orange") $this->SetFillColor(200, 80, 15);
        $this->SetTextColor(255);
        $this->SetFont('', 'B', 11);
        $MaxLength = array();
        $count = 0;
        foreach($content as $row) {
            foreach($row as $element) {
                if (!array_key_exists($count, $MaxLength)) $MaxLength[$count] = 0;
                $MaxLength[$count] = max($this->GetStringWidth($element) , $MaxLength[$count]);
                $count++;
            }
            $count = 0;
        }
        foreach($MaxLength as $width) {
            if ($adjust) {
                $length[$count] = min($length[$count], ($width + 2));
            } else {
                $length[$count] = max($length[$count], ($width + 2));
            }
            $count++;
        }
        $count = 0;
        foreach($content as $row) {
            foreach($row as $element) {
                $this->Cell($length[$count], 5, $element, 1, 0, 'C', $fill);
                $count++;
            }
            $this->Ln();
            if ($color == "blue") $this->SetFillColor(195, 195, 215);
            elseif ($color == "red") $this->SetFillColor(255, 235, 215);
            elseif ($color == "orange") $this->SetFillColor(255, 236, 168);
            $this->SetTextColor(0);
            $this->SetFont('', '', 9);
            $fill = !$fill;
            $count = 0;
        }
        $this->Ln(5);
    }
    function __AttackHosts($title, $ip_target, $limit = 10, $color = "blue", $type = "event", $date_from, $date_to) {
        $content[] = array(
            "Host",
            "Occurrences"
        );
        $length = array(
            80,
            25
        );
        $attack = $this->security_report->AttackHost($this->ossim_conn, $ip_target, $limit, $type, $date_from, $date_to);
        /* resolve ips */
        for ($i = 0; $i < count($attack); $i++) $attack[$i][0] = Host::ip2hostname($this->ossim_conn, $attack[$i][0]);
        $content = array_merge($content, $attack);
        $this->__WriteTable($title, $length, $content, 1, $color);
    }
    function AttackedHosts($limit = 10, $type = "event", $date_from, $date_to) {
        $this->__AttackHosts("Top $limit Attacked Hosts", "ip_dst", $limit, "red", $type, $date_from, $date_to);
    }
    function AttackerHosts($limit = 10, $type = "event", $date_from, $date_to) {
        $this->__AttackHosts("Top $limit Attacker Hosts", "ip_src", $limit, "blue", $type, $date_from, $date_to);
    }
    function Events($limit = 10, $type = "event", $date_from, $date_to) {
        $content[] = array(
            "Event",
            "Occurrences"
        );
        $length = array(
            100,
            25
        );
        $content = array_merge($content, $this->security_report->Events($limit, $type, $date_from, $date_to));
        $this->__WriteTable("Top $limit Events", $length, $content, 1);
    }
    function Ports($limit = 10, $type = "event", $date_from, $date_to) {
        $content[] = array(
            "Port",
            "Service",
            "Ocurrences"
        );
        $length = array(
            35,
            40,
            25
        );
        $content = array_merge($content, $this->security_report->Ports($limit, $type, $date_from, $date_to));
        $this->__WriteTable("Top $limit Destination Ports", $length, $content, 1, "orange");
    }
    function EventsByRisk($limit = 10, $type = "event", $date_from, $date_to) {
        $content[] = array(
            "Event",
            "Risk"
        );
        $length = array(
            100,
            25
        );
        $content = array_merge($content, $this->security_report->EventsByRisk($limit, $type, $date_from, $date_to));
        $this->__WriteTable("Top $limit Events by risk", $length, $content, 1);
    }
    /*
    * INCIDENTS
    */
    function __get_ids($ref, $priority, $fil, $host=null, $conn=null) {
        if ($ref != "Metric" && $ref != "Alarm" && $ref != "Event" && $ref != "Anomaly" && $ref != "Vulnerability") return NULL;
        $ids = array();
        //$incident_list = Incident::get_list($this->ossim_conn, "WHERE ref = '$ref' and $priority $fil ORDER BY priority DESC");
        if(is_null($host)){
            // sin buscar por host o redes
            $priority=str_replace('i.priority','priority',  $priority);
            $incident_list[0] = Incident::get_list($this->ossim_conn, "WHERE ref = '$ref' and $priority $fil ORDER BY priority DESC");
        }else{
            // buscando en host o redes
             if(is_array($host)){
                //es una red
                $incident_list=array();
                foreach($host as $value){
                    unset($listTemp);
                    $listTemp=Incident::get_list_filter_ips($conn,$value, " and ref = '$ref' and  $priority $fil");
                    $incident_list[]=$listTemp;
                }
            }else{
                //es un host
                $incident_list[0] = Incident::get_list_filter_ips($conn,$host, " and ref = '$ref' and  $priority $fil");
            }
        }
        if (is_array($incident_list)) {
            foreach($incident_list as $value){
                foreach($value as $incident) {
                    $ids[] = $incident->get_id();
                }
            }
        }
        return $ids;
    }
    function get_metric_ids($priority, $fil, $host=null, $conn=null) {
        return $this->__get_ids("Metric", $priority, $fil, $host, $conn);
    }
    function get_alarm_ids($priority, $fil, $host=null, $conn=null) {
        return $this->__get_ids("Alarm", $priority, $fil, $host, $conn);
    }
    function get_event_ids($priority, $fil, $host=null, $conn=null) {
        return $this->__get_ids("Event", $priority, $fil, $host, $conn);
    }
    function get_anomaly_ids($priority, $fil, $host=null, $conn=null) {
        return $this->__get_ids("Anomaly", $priority, $fil, $host, $conn);
    }
    function get_vulnerability_ids($priority, $fil, $host=null, $conn=null) {
        return $this->__get_ids("Vulnerability", $priority, $fil, $host, $conn);
    }
    function IncidentGeneralData($title, $date) {
        $this->__WriteTitle(gettext("GENERAL DATA"));
        $this->SetFont('Arial', '', 12);
        $this->__WriteBold(gettext("Title: "));
        $this->Write(DEFAULT_LINE_HEIGTH, $title);
        $this->Ln(DEFAULT_LINE_HEIGTH);
        $this->__WriteBold(gettext("Date: "));
        $this->Write(DEFAULT_LINE_HEIGTH, $date);
        $this->Ln(DEFAULT_LINE_HEIGTH * 2);
    }
    function IncidentSummary($title, $ref, $summary, $priority, $fil) {
        $ids = array();
        $this->__WriteTitle($title);
        $this->SetFont('Arial', '', 12);
        /* Summary */
        $this->Write(DEFAULT_LINE_HEIGTH, $summary);
        $this->Ln(DEFAULT_LINE_HEIGTH * 2);
        $this->Write(DEFAULT_LINE_HEIGTH, gettext("List of $ref" . "s:"));
        $this->Ln(DEFAULT_LINE_HEIGTH);
        $incident_list = Incident::get_list($this->ossim_conn, "WHERE ref = '$ref' and $priority $fil ORDER BY priority DESC");
        if (is_array($incident_list)) {
            foreach($incident_list as $incident) {
                $priority = $incident->get_priority($this->ossim_conn);
                $color = $incident->get_priority_bgcolor($priority);
                /* date */
                $this->Write(DEFAULT_LINE_HEIGTH, $incident->get_date() . "  ");
                /* title */
                $this->__WriteBold($incident->get_title() , $color);
                $this->Ln();
                /* priority */
                /*
                $this->Write(DEFAULT_LINE_HEIGTH, "Priority: ");
                $this->__WriteBold($priority, $color);
                $this->Ln();
                */
                $ids[] = $incident->get_id();
            }
        }
        $this->Ln(10);
        return $ids;
    }
    function IncidentSummaryNoPDF($title, $ref, $summary, $priority, $fil, $host=null, $conn=null) {
        $ids = array();
        /**/
        $returnContent = array();
        /**/
        $this->__WriteTitle($title);
        $this->SetFont('Arial', '', 12);
        /* Summary */
        $this->Write(DEFAULT_LINE_HEIGTH, $summary);
        $this->Ln(DEFAULT_LINE_HEIGTH * 2);
        //$this->Write(DEFAULT_LINE_HEIGTH, gettext("List of $ref" . "s:"));
        $this->Ln(DEFAULT_LINE_HEIGTH);
        //$incident_list = Incident::get_list($this->ossim_conn, "WHERE ref = '$ref' and $priority $fil ORDER BY priority DESC");

        if(is_null($host)){
            // sin buscar por host o redes
            $priority=str_replace('i.priority','priority',  $priority);
            $incident_list[0] = Incident::get_list($this->ossim_conn, "WHERE ref = '$ref' and $priority $fil ORDER BY priority DESC");
        }else{
            
            // buscando en host o redes
             if(is_array($host)){
                //es una red
                $incident_list=array();
                foreach($host as $value){
                    unset($listTemp);
                    $listTemp=Incident::get_list_filter_ips($conn,$value, " and ref = '$ref' and  $priority $fil");
                    $incident_list[]=$listTemp;
                }
            }else{
                //es un host
                $incident_list[0] = Incident::get_list_filter_ips($conn,$host, " and ref = '$ref' and  $priority $fil");
            }
            
        }



        if (is_array($incident_list)) {
            foreach($incident_list as $value){
                foreach($value as $incident) {
                    /**/
                    $content = array();
                    /**/
                    $priority = $incident->get_priority($this->ossim_conn);
                    $color = $incident->get_priority_bgcolor($priority);
                    /* date */
                    //$this->Write(DEFAULT_LINE_HEIGTH, $incident->get_date() . "  ");
                    $content[]=$incident->get_date() . "  ";
                    /* title */
                    //$this->__WriteBold($incident->get_title() , $color);
                    $content[]=$incident->get_title();
                    $content[]=$color;
                    $this->Ln();
                    /* priority */
                    /*
                    $this->Write(DEFAULT_LINE_HEIGTH, "Priority: ");
                    $this->__WriteBold($priority, $color);
                    $this->Ln();
                    */
                    $ids[] = $incident->get_id();
                    /**/
                    $TipoIncidente=$incident->get_ref();
                    /**/
                    $returnContent[]=$content;
                }
            }
        }
        
        $this->Ln(10);
        //return $ids;
        $returnContent["TipoIncidente"]="ListSummaryIncident";

        return $returnContent;
    }
    function Incident($incident_id) {
        $incident_list = Incident::get_list($this->ossim_conn, "WHERE incident.id = $incident_id");
        $incident = $incident_list[0];
        /* title */
        $this->__WriteTitle($incident->get_title());
        /* Alarm or Metric specific data */
        if ($incident->get_ref() == "Alarm" or $incident->get_ref() == "Event") {
            if ($alarm_list = $incident->get_alarms($this->ossim_conn)) {
                foreach($alarm_list as $alarm) {
                    $this->__WriteBold("Source ips: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $alarm->get_src_ips());
                    $this->Ln();
                    $this->__WriteBold("Destination ips: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $alarm->get_dst_ips());
                    $this->Ln();
                    $this->__WriteBold("Source ports: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $alarm->get_src_ports());
                    $this->Ln();
                    $this->__WriteBold("Destination ports: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $alarm->get_src_ports());
                    $this->Ln();
                } /*foreach*/
            } /*if*/
        } elseif ($incident->get_ref() == "Metric") {
            if ($metric_list = $incident->get_metrics($this->ossim_conn)) {
                foreach($metric_list as $metric) {
                    $this->__WriteBold("Target: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $metric->get_target());
                    $this->Ln();
                    $this->__WriteBold("Metric type: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $metric->get_metric_type());
                    $this->Ln();
                    $this->__WriteBold("Metric value: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $metric->get_metric_value());
                    $this->Ln();
                } /*foreach*/
            } /*if*/
        } elseif ($incident->get_ref() == "Anomaly") {
            if ($anomaly_list = $incident->get_anomalies($this->ossim_conn)) {
                foreach($anomaly_list as $anomaly) {
                    $this->__WriteBold("IP: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $anomaly->get_ip() . "     ");
                    $this->__WriteBold("Anomaly type: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $anomaly->get_anom_type() . "     ");
                    $this->__WriteBold("Original data: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $anomaly->get_data_orig() . "     ");
                    $this->__WriteBold("New data: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $anomaly->get_data_new() . "     ");
                    $this->Ln();
                } /*foreach*/
            } /*if*/
        } elseif ($incident->get_ref() == "Vulnerability") {
            if ($vulnerability_list = $incident->get_vulnerabilities($this->ossim_conn)) {
                foreach($vulnerability_list as $vulnerability) {
                    $this->__WriteBold("IP: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $vulnerability->get_ip() . "     ");
                    $this->__WriteBold("Port: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $vulnerability->get_port() . "     ");
                    $this->__WriteBold("Nessus ID: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $vulnerability->get_nessus_id() . "     ");
                    $this->Ln();
                } /*foreach*/
            } /*if*/
        } /*elseif*/
        /* in charge */
        $this->Ln();
        $this->__WriteBold("In charge: ");
        $this->Write(DEFAULT_LINE_HEIGTH, $incident->get_in_charge($this->ossim_conn) . "     ");
        /* status */
        $this->__WriteBold("Status: ");
        $status = $incident->get_status($this->ossim_conn);
        $color = "";
        if ($status == "Open") $color = "red";
        $this->__WriteBold($status, $color);
        $this->__WriteBold("     ");
        /* priority */
        $this->__WriteBold("Priority: ");
        $priority = $incident->get_priority($this->ossim_conn);
        $color = $incident->get_priority_bgcolor($priority);
        $this->__WriteBold($priority, $color);
        $this->Ln(DEFAULT_LINE_HEIGTH * 3);
        /* tickets */
        if ($tickets = $incident->get_tickets($this->ossim_conn)) {
            foreach($tickets as $ticket) {
                $this->__WriteBold("[" . $ticket->get_date() . " revision] ");
                $this->Ln();
                $this->__WriteBold("User: ");
                $this->Write(DEFAULT_LINE_HEIGTH, $ticket->get_user());
                $this->Ln();
                $this->__WriteBold("Description: ");
                $this->Write(DEFAULT_LINE_HEIGTH, $ticket->get_description());
                $this->Ln();
                if ($ticket->get_action()) {
                    $this->__WriteBold("Action: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $ticket->get_action());
                    $this->Ln();
                }
                $this->__WriteBold("Priority: ");
                $priority = $ticket->get_priority();
                $color = $incident->get_priority_bgcolor($priority);
                $this->__WriteBold($priority, $color);
                $this->Ln();
                $this->__WriteBold("Status: ");
                $status = $incident->get_status($this->ossim_conn);
                $color = "";
                if ($status == "Open") $color = "red";
                $this->__WriteBold($status, $color);
                $this->Ln();
                $this->__WriteBold("In charge: ");
                $this->Write(DEFAULT_LINE_HEIGTH, $ticket->get_in_charge());
                $this->Ln();
                if ($ticket->get_transferred) {
                    $this->__WriteBold("Transferred: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $ticket->get_transferred());
                    $this->Ln();
                }
                $this->Ln(DEFAULT_LINE_HEIGTH * 2);
            } /*foreach*/
        }
    } /*end function incident*/
    /* JASPER */
    function IncidentNoPDF($incident_id) {
        $incident_list = Incident::get_list($this->ossim_conn, "WHERE incident.id = $incident_id");
        $incident = $incident_list[0];
        /**/
        $content = array();
        /**/
        /* title */
        //$this->__WriteTitle($incident->get_title());
        $content["Title"]=$incident->get_title();
        $content["TipoIncidente"]=$incident->get_ref();
        /* Alarm or Metric specific data */
        //echo "X=".$incident->get_ref()."\n\n\n\n\n";
        if ($incident->get_ref() == "Alarm" or $incident->get_ref() == "Event") {
            if ($alarm_list = $incident->get_alarms($this->ossim_conn)) {
                foreach($alarm_list as $alarm) {
                    /*
                    $this->__WriteBold("Source ips: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $alarm->get_src_ips());
                    $this->Ln();
                    $this->__WriteBold("Destination ips: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $alarm->get_dst_ips());
                    $this->Ln();
                    $this->__WriteBold("Source ports: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $alarm->get_src_ports());
                    $this->Ln();
                    $this->__WriteBold("Destination ports: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $alarm->get_src_ports());
                    $this->Ln();
                     * */
                     $content["SourceIps"]=$alarm->get_src_ips();
                     $content["DestinationIps"]=$alarm->get_dst_ips();
                     $content["SourcePorts"]=$alarm->get_src_ports();
                     $content["DestinationPorts"]=$alarm->get_dst_ports();
                     
                } /*foreach*/
            } /*if*/
        } elseif ($incident->get_ref() == "Metric") {
            if ($metric_list = $incident->get_metrics($this->ossim_conn)) {
                foreach($metric_list as $metric) {
                    /*
                    $this->__WriteBold("Target: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $metric->get_target());
                    $this->Ln();
                    $this->__WriteBold("Metric type: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $metric->get_metric_type());
                    $this->Ln();
                    $this->__WriteBold("Metric value: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $metric->get_metric_value());
                    $this->Ln();
                     */
                    $content["Target"]=$metric->get_target();
                    $content["MetricType"]=$metric->get_metric_type();
                    $content["MetricValue"]=$metric->get_metric_value();
                } /*foreach*/
            } /*if*/
        } elseif ($incident->get_ref() == "Anomaly") {
            if ($anomaly_list = $incident->get_anomalies($this->ossim_conn)) {
                foreach($anomaly_list as $anomaly) {
                    /*
                    $this->__WriteBold("IP: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $anomaly->get_ip() . "     ");
                    $this->__WriteBold("Anomaly type: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $anomaly->get_anom_type() . "     ");
                    $this->__WriteBold("Original data: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $anomaly->get_data_orig() . "     ");
                    $this->__WriteBold("New data: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $anomaly->get_data_new() . "     ");
                    $this->Ln();
                     *
                     */
                    $content["IP"]=$anomaly->get_ip();
                    $content["AnomalyType"]=$anomaly->get_anom_type();
                    $content["OriginalData"]=$anomaly->get_data_orig();
                    $content["NewData"]=$anomaly->get_data_new();
                } /*foreach*/
            } /*if*/
        } elseif ($incident->get_ref() == "Vulnerability") {
            if ($vulnerability_list = $incident->get_vulnerabilities($this->ossim_conn)) {
                foreach($vulnerability_list as $vulnerability) {
                    /*
                    $this->__WriteBold("IP: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $vulnerability->get_ip() . "     ");
                    $this->__WriteBold("Port: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $vulnerability->get_port() . "     ");
                    $this->__WriteBold("Nessus ID: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $vulnerability->get_nessus_id() . "     ");
                    $this->Ln();
                     *
                     */
                    $content["IP"]=$vulnerability->get_ip();
                    $content["Port"]=$vulnerability->get_port();
                    $content["NessusID"]=$vulnerability->get_nessus_id();
                } /*foreach*/
            } /*if*/
        } /*elseif*/
        /* in charge */
        //$this->Ln();
        //$this->__WriteBold("In charge: ");
        //$this->Write(DEFAULT_LINE_HEIGTH, $incident->get_in_charge($this->ossim_conn) . "     ");
        $content["InCharge"]=$incident->get_in_charge($this->ossim_conn);
        /* status */
        //$this->__WriteBold("Status: ");
        //$status = $incident->get_status($this->ossim_conn);
        $content["Status"]=$incident->get_status($this->ossim_conn);
        $color = "";
        if ($status == "Open") $color = "red";
        //$this->__WriteBold($status, $color);
        //$this->__WriteBold("     ");
        /* priority */
        //$this->__WriteBold("Priority: ");
        $content["Priority"]=$incident->get_priority($this->ossim_conn);
        //$priority = $incident->get_priority($this->ossim_conn);
        $color = $incident->get_priority_bgcolor($priority);
        //$this->__WriteBold($priority, $color);
        //$this->Ln(DEFAULT_LINE_HEIGTH * 3);
        /* tickets */
        /*
        if ($tickets = $incident->get_tickets($this->ossim_conn)) {
            foreach($tickets as $ticket) {
                $this->__WriteBold("[" . $ticket->get_date() . " revision] ");
                $this->Ln();
                $this->__WriteBold("User: ");
                $this->Write(DEFAULT_LINE_HEIGTH, $ticket->get_user());
                $this->Ln();
                $this->__WriteBold("Description: ");
                $this->Write(DEFAULT_LINE_HEIGTH, $ticket->get_description());
                $this->Ln();
                if ($ticket->get_action()) {
                    $this->__WriteBold("Action: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $ticket->get_action());
                    $this->Ln();
                }
                $this->__WriteBold("Priority: ");
                $priority = $ticket->get_priority();
                $color = $incident->get_priority_bgcolor($priority);
                $this->__WriteBold($priority, $color);
                $this->Ln();
                $this->__WriteBold("Status: ");
                $status = $incident->get_status($this->ossim_conn);
                $color = "";
                if ($status == "Open") $color = "red";
                $this->__WriteBold($status, $color);
                $this->Ln();
                $this->__WriteBold("In charge: ");
                $this->Write(DEFAULT_LINE_HEIGTH, $ticket->get_in_charge());
                $this->Ln();
                if ($ticket->get_transferred) {
                    $this->__WriteBold("Transferred: ");
                    $this->Write(DEFAULT_LINE_HEIGTH, $ticket->get_transferred());
                    $this->Ln();
                }
                $this->Ln(DEFAULT_LINE_HEIGTH * 2);
            } /*foreach*/ /*
        }*/
        return $content;
    } /*end function incident*/
    /* END JASPER */
    /*
    PRE:
    time_range = day, week, month, year
    type = compromise, attack
    target = global, net, host
    */
    function Metrics($time_range = "week", $type = "attack", $target = "host") {
        require_once ('classes/Control_panel_host.inc');
        require_once ('classes/Session.inc');
        $user = Session::get_session_user();
        $content = array();
        $length = array(
            40,
            40,
            40
        );
        /* table header */
        if ($target == "host") $tmp_array = array(
            "Host",
            "Max " . $type . " level",
            "Date"
        );
        elseif ($target == "net") $tmp_array = array(
            "Net",
            "Max " . $type . " level",
            "Date"
        );
        elseif ($target == "global") $tmp_array = array(
            "Global",
            "Max " . $type . " level",
            "Date"
        );
        array_push($content, $tmp_array);
        if ($target == "host") {
            $hosts = Control_panel_host::get_metric_list($this->ossim_conn, $time_range, $type);
            if (is_array($hosts)) {
                foreach($hosts as $host) {
                    if ($type == "compromise") $tmp_array = array(
                        Host::ip2hostname($this->ossim_conn, $host->get_host_ip()) ,
                        $host->get_max_c() ,
                        $host->get_max_c_date()
                    );
                    elseif ($type == "attack") $tmp_array = array(
                        Host::ip2hostname($this->ossim_conn, $host->get_host_ip()) ,
                        $host->get_max_a() ,
                        $host->get_max_a_date()
                    );
                    $content[] = $tmp_array;
                }
            }
        } elseif ($target == "net") {
            $nets = Control_panel_net::get_metric_list($this->ossim_conn, $time_range, $type);
            if (is_array($nets)) {
                foreach($nets as $net) {
                    if ($type == "compromise") $tmp_array = array(
                        $net->get_net_name() ,
                        $net->get_max_c() ,
                        $net->get_max_c_date()
                    );
                    elseif ($type == "attack") $tmp_array = array(
                        $net->get_net_name() ,
                        $net->get_max_a() ,
                        $net->get_max_a_date()
                    );
                    $content[] = $tmp_array;
                }
            }
        } elseif ($target == "global") {
            $query = "SELECT * FROM control_panel
                WHERE id = 'global_$user' AND time_range = '$time_range'";
            if (!$rs_global = & $this->ossim_conn->Execute($query)) print $conn->ErrorMsg();
            else {
                if ($type == "compromise") $tmp_array = array(
                    "GLOBAL",
                    $rs_global->fields["max_c"],
                    $rs_global->fields["max_c_date"]
                );
                elseif ($type == "attack") $tmp_array = array(
                    "GLOBAL",
                    $rs_global->fields["max_a"],
                    $rs_global->fields["max_a_date"]
                );
                $content[] = $tmp_array;
            }
        }
        if ($type == "compromise") $color = "blue";
        if ($type == "attack") $color = "red";
        $this->__WriteTable("Metrics last $time_range", $length, $content, 0, $color);
    }
    /* JASPER */
    function MetricsNoPDF($time_range = "week", $type = "attack", $target = "host", $assets_filter, $dates_filter) {
        require_once ('classes/Control_panel_host.inc');
        require_once ('classes/Session.inc');
        $user = Session::get_session_user();
        $content = array();
        $length = array(
            40,
            40,
            40
        );
        /* table header */
        if ($target == "host") $tmp_array = array(
            "Host",
            "Max " . $type . " level",
            "Date"
        );
        elseif ($target == "net") $tmp_array = array(
            "Net",
            "Max " . $type . " level",
            "Date"
        );
        elseif ($target == "global") $tmp_array = array(
            "Global",
            "Max " . $type . " level",
            "Date"
        );
        array_push($content, $tmp_array);
        if ($target == "host") {
            $hosts = Control_panel_host::get_metric_list($this->ossim_conn, $time_range, $type, $assets_filter, $dates_filter);
            if (is_array($hosts)) {
                foreach($hosts as $host) {
                    if ($type == "compromise") $tmp_array = array(
                        Host::ip2hostname($this->ossim_conn, $host->get_host_ip()) ,
                        $host->get_max_c() ,
                        $host->get_max_c_date()
                    );
                    elseif ($type == "attack") $tmp_array = array(
                        Host::ip2hostname($this->ossim_conn, $host->get_host_ip()) ,
                        $host->get_max_a() ,
                        $host->get_max_a_date()
                    );
                    $content[] = $tmp_array;
                }
            }
        } elseif ($target == "net") {
            $nets = Control_panel_net::get_metric_list($this->ossim_conn, $time_range, $type, $assets_filter, $dates_filter);
            if (is_array($nets)) {
                foreach($nets as $net) {
                    if ($type == "compromise") $tmp_array = array(
                        $net->get_net_name() ,
                        $net->get_max_c() ,
                        $net->get_max_c_date()
                    );
                    elseif ($type == "attack") $tmp_array = array(
                        $net->get_net_name() ,
                        $net->get_max_a() ,
                        $net->get_max_a_date()
                    );
                    $content[] = $tmp_array;
                }
            }
        } elseif ($target == "global") {
            $query = "SELECT * FROM control_panel
                WHERE id = 'global_$user' AND time_range = '$time_range' $dates_filter";
            if (!$rs_global = & $this->ossim_conn->Execute($query)) print $conn->ErrorMsg();
            else {
                if ($type == "compromise") $tmp_array = array(
                    "GLOBAL",
                    $rs_global->fields["max_c"],
                    $rs_global->fields["max_c_date"]
                );
                elseif ($type == "attack") $tmp_array = array(
                    "GLOBAL",
                    $rs_global->fields["max_a"],
                    $rs_global->fields["max_a_date"]
                );
                $content[] = $tmp_array;
            }
        }
        if ($type == "compromise") $color = "blue";
        if ($type == "attack") $color = "red";
        return $content;
        //$this->__WriteTable("Metrics last $time_range", $length, $content, 0, $color);
    }
    /* END JASPER*/
    /* ---------------------------------------------------------------- */
    /*                                                                  */
    /*                         Print images                             */
    /*                                                                  */
    /* From code by Xavier Nicolay                                      */
    /* Script 25 on www.fpdf.org                                        */
    /* (c) Xavier Nicolay                                               */
    /* V1.0 : 2004-01-17                                                */
    /*                                                                  */
    /* ---------------------------------------------------------------- */
    function _readstr($var, &$pos, $n) {
        //Read some bytes from string
        $string = substr($var, $pos, $n);
        $pos+= $n;
        return $string;
    }
    function _readstr_int($var, &$pos) {
        //Read a 4-byte integer from string
        $i = ord($this->_readstr($var, $pos, 1)) << 24;
        $i+= ord($this->_readstr($var, $pos, 1)) << 16;
        $i+= ord($this->_readstr($var, $pos, 1)) << 8;
        $i+= ord($this->_readstr($var, $pos, 1));
        return $i;
    }
    function _parsemempng($var) {
        $pos = 0;
        $sig = $this->_readstr($var, $pos, 8);
        if ($sig != chr(137) . 'PNG' . chr(13) . chr(10) . chr(26) . chr(10)) $this->Error('Not a PNG image');
        //Read header chunk
        $this->_readstr($var, $pos, 4);
        $ihdr = $this->_readstr($var, $pos, 4);
        if ($ihdr != 'IHDR') $this->Error('Incorrect PNG Image');
        $w = $this->_readstr_int($var, $pos);
        $h = $this->_readstr_int($var, $pos);
        $bpc = ord($this->_readstr($var, $pos, 1));
        if ($bpc > 8) $this->Error('16-bit depth not supported: ' . $file);
        $ct = ord($this->_readstr($var, $pos, 1));
        if ($ct == 0) $colspace = 'DeviceGray';
        elseif ($ct == 2) $colspace = 'DeviceRGB';
        elseif ($ct == 3) $colspace = 'Indexed';
        else $this->Error('Alpha channel not supported: ' . $file);
        if (ord($this->_readstr($var, $pos, 1)) != 0) $this->Error('Unknown compression method: ' . $file);
        if (ord($this->_readstr($var, $pos, 1)) != 0) $this->Error('Unknown filter method: ' . $file);
        if (ord($this->_readstr($var, $pos, 1)) != 0) $this->Error('Interlacing not supported: ' . $file);
        $this->_readstr($var, $pos, 4);
        $parms = '/DecodeParms <</Predictor 15 /Colors ' . ($ct == 2 ? 3 : 1) . ' /BitsPerComponent ' . $bpc . ' /Columns ' . $w . '>>';
        //Scan chunks looking for palette, transparency and image data
        $pal = '';
        $trns = '';
        $data = '';
        do {
            $n = $this->_readstr_int($var, $pos);
            $type = $this->_readstr($var, $pos, 4);
            if ($type == 'PLTE') {
                //Read palette
                $pal = $this->_readstr($var, $pos, $n);
                $this->_readstr($var, $pos, 4);
            } elseif ($type == 'tRNS') {
                //Read transparency info
                $t = $this->_readstr($var, $pos, $n);
                if ($ct == 0) $trns = array(
                    ord(substr($t, 1, 1))
                );
                elseif ($ct == 2) $trns = array(
                    ord(substr($t, 1, 1)) ,
                    ord(substr($t, 3, 1)) ,
                    ord(substr($t, 5, 1))
                );
                else {
                    $pos = strpos($t, chr(0));
                    if (is_int($pos)) $trns = array(
                        $pos
                    );
                }
                $this->_readstr($var, $pos, 4);
            } elseif ($type == 'IDAT') {
                //Read image data block
                $data.= $this->_readstr($var, $pos, $n);
                $this->_readstr($var, $pos, 4);
            } elseif ($type == 'IEND') break;
            else $this->_readstr($var, $pos, $n + 4);
        }
        while ($n);
        if ($colspace == 'Indexed' and empty($pal)) $this->Error('Missing palette in ' . $file);
        return array(
            'w' => $w,
            'h' => $h,
            'cs' => $colspace,
            'bpc' => $bpc,
            'f' => 'FlateDecode',
            'parms' => $parms,
            'pal' => $pal,
            'trns' => $trns,
            'data' => $data
        );
    }
    function MemImage($data, $x, $y, $width = 0, $height = 0, $link = '') {
        $id = md5($data);
        if (!isset($this->images[$id])) {
            $info = $this->_parsemempng($data);
            $info['i'] = count($this->images) + 1;
            $this->images[$id] = $info;
        } else $info = $this->images[$id];
        // Automatic width and height calculation if needed
        if ($width == 0 and $height == 0) {
            // image at 72 dpi
            $width = $info['w'] / $this->k;
            $height = $info['h'] / $this->k;
        }
        if ($width == 0) $width = $height * $info['w'] / $info['h'];
        if ($height == 0) $height = $width * $info['h'] / $info['w'];
        $this->_out(sprintf('q %.2f 0 0 %.2f %.2f %.2f cm /I%d Do Q', $width * $this->k, $height * $this->k, $x * $this->k, ($this->h - ($y + $h)) * $this->k, $info['i']));
    }
    function GDImage($image, $x, $y, $width = 0, $height = 0, $link = '') {
        ob_start();
        imagepng($image);
        $data = ob_get_contents();
        ob_end_clean();
        $this->MemImage($data, $x, $y, $width, $height, $link);
    }
}
?>
