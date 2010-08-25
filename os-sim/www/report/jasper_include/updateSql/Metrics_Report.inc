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
$_GET['report_type']='metrics';
$_GET['hmenu']='PDF+Report';
$_GET['smenu']='PDF+Report';
$_POST['time_day']='on';
$_POST['time_week']='on';
$_POST['time_month']='on';
$_POST['time_year']='on';
/*
 */
require_once ('classes/Session.inc');
Session::logcheck("MenuControlPanel", "ControlPanelMetrics");
require_once 'classes/Security.inc';
require_once 'classes/PDF.inc';
/**/
require_once ('ossim_db.inc');
/**/
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
/* Jasper Functions */
function sql_report_data($sql=NULL, $report_data_type){
        if($sql==NULL){
            die(_('No SQL'));
        }
        /* database connect */
        $db = new ossim_db();
        $temp_conn = $db->connect();
        $temp_conn->Execute('use datawarehouse');
        $temp_conn->Execute('delete from report_data where id_report_data_type='.$report_data_type.' AND user="'.$_SESSION['_user'].'"');

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
        if($key==0){
            /*
             * Hay 2 tipos compromise y attack
             * Hay 3 subtipos Global, Net y Host
             */
            if(strpos($value[1], 'compromise')=== false){
                /* compromise */
                switch($value[0]){
                    case 'Global':
                        $type2=1;
                        break;
                    case 'Net':
                        $type2=2;
                        break;
                    case 'Host':
                        $type2=3;
                        break;
                    default:
                        $type2=0;
                }
            }else if(strpos($value[1], 'attack')=== false){
                /* attack */
                switch($value[0]){
                    case 'Global':
                        $type2=4;
                        break;
                    case 'Net':
                        $type2=5;
                        break;
                    case 'Host':
                        $type2=6;
                        break;
                    default:
                        $type2=0;
                }
            }else{
                $type2=0;
            }
        }else{
            $return[]="INSERT INTO report_data (id_report_data_type, user, dataV1, dataV2, dataI1, dataI2) VALUES (".$type.", \"".$_SESSION['_user']."\", \"".$value[0]."\", \"".$value[2]."\", ".$value[1].",".$type2.");";
        }
    }
    if($return->count()>=1){
        $NoData=true;
    }
    
    return $return;
}
/* END - Jasper Functions */

    $pdf = new PDF("OSSIM Metrics Report");
    $newpage = false;
    if (POST('time_day') == "on") {
        /**/
        $query_temp=new ArrayObject();
        /**/
        $query_temp[]=CreateSql($pdf->MetricsNoPDF("day", "compromise", "global"),24, &$NoData);
        $query_temp[]=CreateSql($pdf->MetricsNoPDF("day", "compromise", "net"),24, &$NoData);
        $query_temp[]=CreateSql($pdf->MetricsNoPDF("day", "compromise", "host"),24, &$NoData);
            /* New Page */
        $query_temp[]=CreateSql($pdf->MetricsNoPDF("day", "attack", "global"),24, &$NoData);
        $query_temp[]=CreateSql($pdf->MetricsNoPDF("day", "attack", "net"),24, &$NoData);
        $query_temp[]=CreateSql($pdf->MetricsNoPDF("day", "attack", "host"),24, &$NoData);
        $newpage = true;
        /**/
        sql_report_data($query_temp, 24);
        /**/
    }
    if (POST('time_week') == "on") {
        /*if ($newpage) $pdf->AddPage();*/
        /**/
        $query_temp=new ArrayObject();
        /**/
        $query_temp[]=CreateSql($pdf->MetricsNoPDF("week", "compromise", "global"),25, &$NoData);
        $query_temp[]=CreateSql($pdf->MetricsNoPDF("week", "compromise", "net"),25, &$NoData);
        $query_temp[]=CreateSql($pdf->MetricsNoPDF("week", "compromise", "host"),25, &$NoData);
            /* New Page */
        $query_temp[]=CreateSql($pdf->MetricsNoPDF("week", "attack", "global"),25, &$NoData);
        $query_temp[]=CreateSql($pdf->MetricsNoPDF("week", "attack", "net"),25, &$NoData);
        $query_temp[]=CreateSql($pdf->MetricsNoPDF("week", "attack", "host"),25, &$NoData);
        $newpage = true;
        /**/
        sql_report_data($query_temp, 25);
        /**/
    }
    if (POST('time_month') == "on") {
        /*if ($newpage) $pdf->AddPage();*/
        /**/
        $query_temp=new ArrayObject();
        /**/
        $query_temp[]=CreateSql($pdf->MetricsNoPDF("month", "compromise", "global"),26, &$NoData);
        $query_temp[]=CreateSql($pdf->MetricsNoPDF("month", "compromise", "net"),26, &$NoData);
        $query_temp[]=CreateSql($pdf->MetricsNoPDF("month", "compromise", "host"),26, &$NoData);
            /* New Page */
        $query_temp[]=CreateSql($pdf->MetricsNoPDF("month", "attack", "global"),26, &$NoData);
        $query_temp[]=CreateSql($pdf->MetricsNoPDF("month", "attack", "net"),26, &$NoData);
        $query_temp[]=CreateSql($pdf->MetricsNoPDF("month", "attack", "host"),26, &$NoData);
        $newpage = true;
        /**/
        sql_report_data($query_temp, 26);
        /**/
    }
    if (POST('time_year') == "on") {
        /*if ($newpage) $pdf->AddPage();*/
        /**/
        $query_temp=new ArrayObject();
        /**/
        $query_temp[]=CreateSql($pdf->MetricsNoPDF("year", "compromise", "global"),27, &$NoData);
        $query_temp[]=CreateSql($pdf->MetricsNoPDF("year", "compromise", "net"),27, &$NoData);
        $query_temp[]=CreateSql($pdf->MetricsNoPDF("year", "compromise", "host"),27, &$NoData);
            /* New Page */
        $query_temp[]=CreateSql($pdf->MetricsNoPDF("year", "attack", "global"),27, &$NoData);
        $query_temp[]=CreateSql($pdf->MetricsNoPDF("year", "attack", "net"),27, &$NoData);
        $query_temp[]=CreateSql($pdf->MetricsNoPDF("year", "attack", "host"),27, &$NoData);
        /**/
        sql_report_data($query_temp, 27);
        /**/
    }
    //$pdf->Output("OSSIM-" . $date_gen . ".pdf", "I");
    if(!$NoData){
        die(_('No data'));
    }
    echo _('OK');

?>
