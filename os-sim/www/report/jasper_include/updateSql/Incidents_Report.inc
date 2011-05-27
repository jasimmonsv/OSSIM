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
/*
 */
$NoData=false;

$_GET['report_type']= 'incident';
$_GET['hmenu']      = 'PDF+Report';
$_GET['smenu']      = 'PDF+Report';

$_POST['Alarm']         = 'on';
$_POST['Event']         = 'on';
$_POST['Metric']        = 'on';
$_POST['Anomaly']       = 'on';
$_POST['Vulnerability'] = 'on';

$_POST['Type']      = GET('Type');
$_POST['In_Charge'] ='';
$_POST['Title']     = '';
$_POST['Date']      ='December+10%2C+2009%2C+9%3A12+am';
$_POST['Status']    = GET('Status');
$_POST['High']      = GET('High');
$_POST['Medium']    = GET('Medium');
$_POST['Low']       = GET('Low');
$_POST['Low']       = GET('Low');
$_POST['date_from'] = GET('date_from');
$_POST['date_to']   = GET('date_to');

if ( POST('date_from') != "" && POST('date_to') != "" )
{
	$date_from   = POST('date_from')." 00:00:00";
	$date_to     = POST('date_to')." 23:59:59";
}
else
{
	$date_from = strftime("%d/%m/%Y 00:00:00", time() - (24 * 60 * 60));
	$date_to   = strftime("%d/%m/%Y 23:59:59", time());
}


/*
date_from=10%2F12%2F2009+00%3A00%3A00&
date_to=10%2F12%2F2009+09%3A12%3A38&
Alarm=on&
Event=on&
Metric=on&
Anomaly=on&
Vulnerability=on&
Type=ALL&
In_Charge=&
Title=&
Date=December+10%2C+2009%2C+9%3A12+am&
Status=Open&
High=on&
Medium=on&
Low=on&
submit_incident=Generate
 * */
/*
 */
require_once ('classes/Session.inc');
Session::logcheck("MenuEvents", "EventsForensics");
require_once 'classes/Security.inc';
require_once 'classes/PDF.inc';
require_once ('ossim_db.inc');


session_cache_limiter('private');

$pathtographs = dirname($_SERVER['REQUEST_URI']);
$proto        = "http";

$db    = new ossim_db();
$conn  = $db->connect();


if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") $proto = "https";
$datapath = "$proto://" . $_SERVER['SERVER_ADDR'] . ":" . $_SERVER['SERVER_PORT'] . "$pathtographs/graphs";

//This is used to give the name to the created pdf
$date_gen = date("d-m-y");
function clean_tmp_files() 
{
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
/* Jasper Functions */
function sql_report_data($sql=NULL, $report_data_type){
    
        if($sql==NULL){
            die(_('No SQL'));
        }
        /* database connect */
        $db = new ossim_db();
        $temp_conn = $db->connect();
        $temp_conn->Execute('use datawarehouse');
        $temp_conn->Execute('DELETE FROM report_data WHERE id_report_data_type='.$report_data_type.' AND user="'.$_SESSION['_user'].'"');

        foreach($sql as $value){
            foreach($value as $value2){
                $temp_conn->Execute($value2);
            }
        }
        $db->close($temp_conn);
    }


function CreateSql($array, $type, &$NoData){
    $return = new ArrayObject();

    foreach($array as $key => $value){
        /*
         * Hay 2 tipos compromise y attack
         * Hay 3 subtipos Global, Net y Host
         */
        switch($array["TipoIncidente"]){
            case "ListSummaryIncident":
                $type2=1;
                if($key!=='TipoIncidente'){
                   $return[]="INSERT INTO report_data (id_report_data_type, user, dataV1, dataV2, dataV3, dataI1) VALUES (".$type.", \"".$_SESSION['_user']."\", \"".$value[0]."\", \"".$value[1]."\", \"".$value[2]."\",".$type2.");";
                }
                break;
            case "Alarm":
            case "Event":
                /* Detail of */
                $type2=2;
                $Title=$array['Title'];
                $SourceIps=$array['SourceIps'];
                $DestinationIps=$array['DestinationIps'];
                $SourcePorts=$array['SourcePorts'];
                $DestinationPorts=$array['DestinationPorts'];
                // 1=Open 2=Closed
                $Status=$array['Status']=='Open'?1:2;
                //
                $Priority=$array['Priority'];
                $InCharge=$array['InCharge'];

                $return[]="INSERT INTO report_data (id_report_data_type, user, dataV1, dataV2, dataV3, dataV4, dataV5, dataV6, dataI1, dataI2, dataI3) VALUES (".$type.", \"".$_SESSION['_user']."\", \"".$Title."\", \"".$SourceIps."\", \"".$DestinationIps."\", \"".$SourcePorts."\", \"".$DestinationPorts."\", \"".$InCharge."\", ".$type2.",".$Status.",".$Priority.");";
                break;
            case "Vulnerability":
                /* Detail of */
                $type2=2;
                $Title=$array['Title'];
                $IP=$array['IP'];
                $Port=$array['Port'];
                $NessusID=$array['NessusID'];
                // 1=Open 2=Closed
                $Status=$array['Status']=='Open'?1:2;
                //
                $Priority=$array['Priority'];
                $InCharge=$array['InCharge'];

                $return[]="INSERT INTO report_data (id_report_data_type, user, dataV1, dataV2, dataV3, dataV4, dataV5, dataI1, dataI2, dataI3) VALUES (".$type.", \"".$_SESSION['_user']."\", \"".$Title."\", \"".$IP."\", \"".$Port."\", \"".$NessusID."\", \"".$InCharge."\", ".$type2.",".$Status.",".$Priority.");";
                break;
            case "Metric":
                /* Detail of */
                $type2=2;
                $Title=$array['Title'];
                $Target=$array['Target'];
                $MetricType=$array['MetricType'];
                $MetricValue=$array['MetricValue'];
                // 1=Open 2=Closed
                $Status=$array['Status']=='Open'?1:2;
                //
                $Priority=$array['Priority'];
                $InCharge=$array['InCharge'];

                $return[]="INSERT INTO report_data (id_report_data_type, user, dataV1, dataV2, dataV3, dataV4, dataV5, dataI1, dataI2, dataI3) VALUES (".$type.", \"".$_SESSION['_user']."\", \"".$Title."\", \"".$Target."\", \"".$MetricType."\", \"".$MetricValue."\", \"".$InCharge."\", ".$type2.",".$Status.",".$Priority.");";
                break;
            case "Anomaly":
                /* Detail of */
                $type2=2;
                $Title=$array['Title'];
                $IP=$array['IP'];
                $AnomalyType=$array['AnomalyType'];
                $OriginalData=$array['OriginalData'];
                $NewData=$array['NewData'];
                // 1=Open 2=Closed
                $Status=$array['Status']=='Open'?1:2;
                //
                $Priority=$array['Priority'];
                $InCharge=$array['InCharge'];

                $return[]="INSERT INTO report_data (id_report_data_type, user, dataV1, dataV2, dataV3, dataV4, dataV5, dataV6, dataI1, dataI2, dataI3) VALUES (".$type.", \"".$_SESSION['_user']."\", \"".$Title."\", \"".$IP."\", \"".$AnomalyType."\", \"".$OriginalData."\", \"".$NewData."\", \"".$InCharge."\", ".$type2.",".$Status.",".$Priority.");";
                break;
            default:
                
                break;
        }
        if($array["TipoIncidente"]!=="ListSummaryIncident"){
            break;
        }
    }
    if($return->count()>=1){
        $NoData=true;
    }
    
    return $return;
}
/* END - Jasper Functions */

    $alarm         = POST('Alarm');
    $event         = POST('Event');
    $metric        = POST('Metric');
    $anomaly       = POST('Anomaly');
    $vulnerability = POST('Vulnerability');
    $type          = POST('Type');
    $in_charge     = POST('In_Charge');
    $title         = POST('Title');
    $date          = POST('Date');
    $status        = POST('Status');
    
	ossim_valid($type, OSS_NULLABLE, OSS_ALPHA, OSS_PUNC, 'illegal:' . _("type"));
    ossim_valid($status, OSS_NULLABLE, OSS_ALPHA, OSS_PUNC, 'illegal:' . _("status"));
    ossim_valid($in_charge, OSS_NULLABLE, OSS_ALPHA, OSS_PUNC, 'illegal:' . _("in_charge"));
	
	if (ossim_error()) {
        die(ossim_error());
    }
    
	$pdf = new PDF("OSSIM Tickets Report", "P", "mm", "A4");
    $pdf->IncidentGeneralData($title, $date);
    $priority = 0;
    
	if (POST('High')) 
	{
        $priority = " (priority > 7";
        if (POST('Medium')) $priority.= " or ( priority > 4 and priority <= 7 )";
        if (POST('Low')) $priority.= " or ( priority > 0 and priority <= 4 )";
        $priority.= ")";
    } 
	elseif (POST('Medium')) 
	{
        $priority = " (priority > 4 and priority =< 7";
        if (isset($_POST["Low"])) $priority.= " or ( priority > 0 and priority <= 4 )";
        $priority.= ")";
    } 
	elseif (POST('Low')) $priority = " ( priority > 0 and priority <= 4 )";
    
	$fil = "";
    
	if ($type != "ALL") $fil.= " and type_id = '$type'";
    
	if ($status != "ALL") $fil.= " and status = '$status'";
    
	if ($in_charge != "") $fil.= " and in_charge = '$in_charge'";
   
   /* metrics */
    if (POST('Metric')) {
       
        $query_temp=new ArrayObject();
       
        $query_temp[]=CreateSql($pdf->IncidentSummaryNoPDF(gettext("METRICS") , "Metric", $metrics_notes, $priority, $fil, null, $conn, null),30,&$NoData);
        $ids = $pdf->get_metric_ids($priority, $fil, null, null, $conn);
       
		foreach($ids as $incident_id) {
            $query_temp[]=CreateSql($pdf->IncidentNoPDF($incident_id),30,&$NoData);
        }
        
        sql_report_data($query_temp, 30);
        
    }
	
    /* alarms */
    if (POST('Alarm')) 
	{
        $query_temp=new ArrayObject();
       
        $query_temp[]=CreateSql($pdf->IncidentSummaryNoPDF(gettext("ALARMS") , "Alarm", $alarms_notes, $priority, $fil, null, $conn, null),28,&$NoData);
        $ids = $pdf->get_alarm_ids($priority, $fil, null, null, $conn);
       
	    foreach($ids as $incident_id) {
            $query_temp[]=CreateSql($pdf->IncidentNoPDF($incident_id),28,&$NoData);
        }
        
        sql_report_data($query_temp, 28);
        
    }
	
    /* events */
    if (POST('Event')) 
	{
        $query_temp=new ArrayObject();
        
        $query_temp[]=CreateSql($pdf->IncidentSummaryNoPDF(gettext("ALERTS") , "Event", $events_notes, $priority, $fil, null, $conn, null),29,&$NoData);
        $ids = $pdf->get_event_ids($priority, $fil, null, null, $conn);
        foreach($ids as $incident_id) {
            $query_temp[]=CreateSql($pdf->IncidentNoPDF($incident_id),29,&$NoData);
        }
        
        sql_report_data($query_temp, 29);
        
    }
	
    /* anomalies */
    if (POST('Anomaly'))
	{
        $query_temp=new ArrayObject();
        
        $query_temp[]=CreateSql($pdf->IncidentSummaryNoPDF(gettext("ANOMALIES") , "Anomaly", $events_notes, $priority, $fil, null, $conn, null),31,&$NoData);
        $ids = $pdf->get_anomaly_ids($priority, $fil, null, null, $conn);
        
		foreach($ids as $incident_id) {
            $query_temp[]=CreateSql($pdf->IncidentNoPDF($incident_id),31,&$NoData);
        }
        
        sql_report_data($query_temp, 31);
        
    }
	
    /* vulnerabilities */
    if (POST('Vulnerability'))
	{
        $query_temp=new ArrayObject();
                
		$query_temp[]=CreateSql($pdf->IncidentSummaryNoPDF(gettext("VULNERABILITIES") , "Vulnerability", $vulnerabilities_notes, $priority, $fil, null, $conn, null),32,&$NoData);
        
		$ids = $pdf->get_vulnerability_ids($priority, $fil, null, null, $conn);
        
		foreach($ids as $incident_id) {
            $query_temp[]=CreateSql($pdf->IncidentNoPDF($incident_id),32,&$NoData);
        }
       
        sql_report_data($query_temp, 32);
       
    }
    
    
	if(!$NoData){
        die(_('No data'));
    }
    //$pdf->Output("OSSIM-" . $date_gen . ".pdf", "I");
    echo _('OK');
?>
