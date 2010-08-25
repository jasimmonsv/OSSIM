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
* - ip_max_occurrences()
* - event_max_occurrences()
* - event_max_risk()
* - port_max_occurrences()
* - less_stable_services()
* Classes list:
*/
/*
 */
$_GET['section']='all';
$_GET['type']='alarm';
$_GET['hmenu']='Alarms';
$_GET['smenu']='Report';
/*
 */
set_time_limit(900);
require_once ('classes/Session.inc');
Session::logcheck("MenuIncidents", "ControlPanelAlarms");
require_once 'classes/Security.inc';
$date_from = GET('date_from');
$date_to = GET('date_to');
ossim_valid($date_from, OSS_DAY, OSS_NULLABLE, 'illegal:' . _("From date"));
ossim_valid($date_to, OSS_DAY, OSS_NULLABLE, 'illegal:' . _("From date"));
if (ossim_error()) {
    die(_("Error"));
}
if (GET('type') == 'alarm') {
    $report_type = "alarm";
} else {
    $report_type = "event";
}

require_once ('ossim_conf.inc');
$path_conf = $GLOBALS["CONF"];
$jpgraph_path = $path_conf->get_conf("jpgraph_path");
if (!is_readable($jpgraph_path)) {
    $error = new OssimError();
    $error->display("JPGRAPH_PATH");
}
require_once ('ossim_db.inc');
require_once ('classes/Host.inc');
require_once ('classes/Host_os.inc');
require_once ('jgraphs/jgraphs.php');
require_once ('classes/SecurityReport.inc');

$security_report = new SecurityReport();
$server = $_SERVER["SERVER_ADDR"];
$file = $_SERVER["REQUEST_URI"];
$port = explode ("\n",`grep 'Listen' /etc/apache2/ports.conf | awk '{print $2}'`);
$_SERVER["APACHE_PORT"]= (is_array($port) && intval($port[0])>0) ? intval($port[0]) : 80;

/* database connect */
$db = new ossim_db();
$conn = $db->connect();
/* Number of hosts to show */
$NUM_HOSTS = 10;
//#############################
// Top attacked hosts
//#############################
$date_from = (GET('date_from') != "") ? GET('date_from') : strftime("%d/%m/%Y ", time())."00:00:00";
$date_to = (GET('date_to') != "") ? GET('date_to') : strftime("%d/%m/%Y %H:%M:%S", time());
//##############################
// Top less stable services
//##############################
if (GET('section') == 'availability') {
    less_stable_services();
} elseif (GET('section') == 'all') {
    ip_max_occurrences(&$NUM_HOSTS, &$security_report, &$report_type, "ip_dst",$date_from,$date_to);

    ip_max_occurrences(&$NUM_HOSTS, &$security_report, &$report_type, "ip_src",$date_from,$date_to);

    port_max_occurrences(&$NUM_HOSTS, &$security_report, &$report_type, $date_from,$date_to);

    event_max_occurrences(&$NUM_HOSTS, &$security_report, &$report_type, $date_from,$date_to);

    event_max_risk(&$NUM_HOSTS, &$security_report, &$report_type, $date_from,$date_to);
}
$db->close($conn);


/*
* return the list of host with max occurrences
* as dest or source
* pre: type is "ip_src" or "ip_dst"
*/
function ip_max_occurrences($NUM_HOSTS, $security_report, $report_type, $target,$date_from,$date_to) {
    /*global $NUM_HOSTS;
    global $security_report;
    global $report_type;*/
	/* ossim framework conf */
    $conf = $GLOBALS["CONF"];
    $acid_link = $conf->get_conf("acid_link");
    $ossim_link = $conf->get_conf("ossim_link");
    $acid_prefix = $conf->get_conf("event_viewer");
    $report_graph_type = $conf->get_conf("report_graph_type");
    /**/
    $query_temp = new ArrayObject();
    /**/
    if (!strcmp($target, "ip_src")) {
        if ($report_type == "alarm") {
            $target = "src_ip";            
        }
        /**/
        $type=19;
        /**/
        $title = _("Attacker hosts");
    } elseif (!strcmp($target, "ip_dst")) {
        if ($report_type == "alarm") {
            $target = "dst_ip";
        }
        /**/
        $type=20;
        /**/
        $title = _("Attacked hosts");
    }

	$list = $security_report->AttackHost($security_report->ossim_conn, $target, $NUM_HOSTS, $report_type, $date_from, $date_to);
    //exit;
    foreach($list as $l) {
        $ip = $l[0];
        $occurrences = number_format($l[1], 0, ",", ".");
        $hostname = Host::ip2hostname($security_report->ossim_conn, $ip);
        $os_pixmap = Host_os::get_os_pixmap($security_report->ossim_conn, $ip);
        /**/
        $query_temp[]="INSERT INTO report_data (id_report_data_type, user, dataV1, dataV2, dataI1) VALUES (".$type.", \"".$_SESSION['_user']."\", \"".$hostname."\", ".formatear_os_pixmap($os_pixmap).", ".$occurrences.");";
        /**/
    }
    /**/
    sql_report_data($query_temp, $type);
    /**/
}

/*
* return the list of ports with max occurrences
*/
function port_max_occurrences($NUM_HOSTS, $security_report, $report_type, $date_from,$date_to) {
    /*global $NUM_HOSTS;
    global $security_report;
    global $report_type;*/
    /* ossim framework conf */
    $conf = $GLOBALS["CONF"];
    $acid_link = $conf->get_conf("acid_link");
    $ossim_link = $conf->get_conf("ossim_link");
    $acid_prefix = $conf->get_conf("event_viewer");
    $report_graph_type = $conf->get_conf("report_graph_type");
    
    /**/
    $query_temp = new ArrayObject();
    /**/

    $list = $security_report->Ports($NUM_HOSTS, $report_type, $date_from, $date_to);
    foreach($list as $l) {
        $port = $l[0];
        $service = $l[1];
        $occurrences = number_format($l[2], 0, ",", ".");
        /**/
        $query_temp[]="INSERT INTO report_data (id_report_data_type, user, dataV1, dataV2, dataI1) VALUES (21, \"".$_SESSION['_user']."\", \"".$port."\", \"".$service."\", ".$occurrences.");";
        /**/
    }
    /**/
    sql_report_data($query_temp, 21);
    /**/
}

/*
* return the event with max occurrences
*/
function event_max_occurrences($NUM_HOSTS, $security_report, $report_type, $date_from,$date_to) {
    /*global $NUM_HOSTS;
    global $security_report;
    global $report_type;*/
    /* ossim framework conf */
    $conf = $GLOBALS["CONF"];
    $acid_link = $conf->get_conf("acid_link");
    $ossim_link = $conf->get_conf("ossim_link");
    $acid_prefix = $conf->get_conf("event_viewer");
    $report_graph_type = $conf->get_conf("report_graph_type");
    
    /**/
    $query_temp = new ArrayObject();
    /**/

    $list = $security_report->Events(15, $report_type, $date_from, $date_to);
    foreach($list as $l) {
        $event = $l[0];
        $short_event = SecurityReport::Truncate($event, 100);
        $occurrences = number_format($l[1], 0, ",", ".");
        /**/
        $query_temp[]="INSERT INTO report_data (id_report_data_type, user, dataV1, dataI1) VALUES (22, \"".$_SESSION['_user']."\", \"".$short_event."\", ".$occurrences.");";
        /**/
    }
    /**/
    sql_report_data($query_temp, 22);
    /**/
}

/*
* return a list of events ordered by risk
*/
function event_max_risk($NUM_HOSTS, $security_report, $report_type, $date_from,$date_to) {
    /*global $NUM_HOSTS;
    global $security_report;
    global $report_type;*/
    
    /**/
    $query_temp = new ArrayObject();
    /**/

    $list = $security_report->EventsByRisk(15, $report_type, $date_from, $date_to);
    foreach($list as $l) {
        $event = $l[0];
        $risk = $l[1];
        /**/
        $query_temp[]="INSERT INTO report_data (id_report_data_type, user, dataV1, dataI1) VALUES (23, \"".$_SESSION['_user']."\", \"".$event."\", ".$risk.");";
        /**/
    }
    /**/
    sql_report_data($query_temp, 23);
    /**/
}

/**/

    function formatear_os_pixmap($url){
        $conf = $GLOBALS["CONF"];
        if($url!=null){
            $url = explode  ('"',  $url);
            $url = explode  ('/',  $url[1],2);
            $url_http = explode  ('/',  $_SERVER['SCRIPT_NAME']);

            $url_http2 = '"http://'.$_SERVER['SERVER_ADDR'].':'.$_SERVER["APACHE_PORT"];
            $url_http2.= '/'.$url_http[1].'/'.$url[1].'"';
        } else {
            $url_http2='NULL';
        }
        
        return $url_http2;
    }

    function sql_report_data($sql=NULL, $report_data_type){
        if($sql==NULL){
            die('No SQL');
        }
        /* database connect */
        $db = new ossim_db();
        $temp_conn = $db->connect();
        $temp_conn->Execute('use datawarehouse');
        $temp_conn->Execute('delete from report_data where id_report_data_type='.$report_data_type.' AND user="'.$_SESSION['_user'].'"');
        foreach($sql as $value){
            $temp_conn->Execute($value);
        }
        $db->close($temp_conn);
    }
?>