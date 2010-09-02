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

function connectBdOssim(){
    $userdb=trim(`grep user /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`);
    $passdb=trim(`grep pass /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`);
    $hostdb=trim(`grep db_ip /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`);

    $conn = mysql_connect($hostdb, $userdb, $passdb);
    if(!mysql_select_db('ossim', $conn)){
        die('DB Error: connect failed');
    }

    return $conn;
}

function getScheduler(){
    $conn=connectBdOssim();
    $results = mysql_query('SELECT * FROM custom_report_scheduler ORDER BY id', $conn) or die(mysql_error());

    $return=array();

    if (mysql_num_rows($results)>0) {
     while ($rowResults = mysql_fetch_assoc($results)) {
         $return[]=array(
                 'schedule_type'=>$rowResults['schedule_type'],
                 'schedule'=>$rowResults['schedule'],
                 'next_launch'=>$rowResults['next_launch'],
                 'id_report'=>$rowResults['id_report'],
                 'name_report'=>$rowResults['name_report'],
                 'email'=>$rowResults['email'],
                 'date_from'=>$rowResults['date_from'],
                 'date_to'=>$rowResults['date_to'],
                 'date_range'=>$rowResults['date_range'],
                 'assets'=>$rowResults['assets']
             );
     }
    }

    mysql_close($conn);
    return $return;
}

function getUserWeb(){
    $conn=connectBdOssim();
    $results = mysql_query('SELECT * FROM users WHERE login="admin"', $conn) or die(mysql_error());

    if (mysql_num_rows($results)>0) {
     while ($rowResults = mysql_fetch_assoc($results)) {
         $return = $rowResults['pass'];
     }
    }

    mysql_close($conn);
    return $return;
}

$server=trim(`grep framework_ip /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`);
$server='http://'.$server.'/ossim';
$user='admin';
$pass=base64_encode(getUserWeb());
$cookieName=date('YmdHis').rand().'.txt';

// Textos para validar
$txtError=array('error'=>'<p><font color="red">Wrong User & Password</font></p>','msg'=>'fail: Wrong User & Password');
$txtError2=array('error'=>'<p>PDF Sent OK</p>','msg'=>'PDF Sent OK');
$txtError3=array('error'=>'<strong>Invalid address:','msg'=>'fail: Send pdf, error email');

// Nos logeamos
$step1=exec('wget --cookies=on --keep-session-cookies --save-cookies='.$cookieName.' --post-data="user='.$user.'&pass='.$pass.'" "'.$server.'/session/login.php" -O -',$output);
if (($result=searchString($output,$txtError))!==FALSE) {
    echo $result;
    clean($cookieName);
    exit();
}

$arrayTemp=array(
    array(
    'time_execute'=>'',
    'id_report'=>'cHJ1ZWViaXRhO2FkbWlu',
    'name_report'=>'prueebita',
    'email'=>'fjnavarro@alienvault.com',
    'date_from'=>'',
    'date_to'=>'',
    'date_range'=>'last15',
    'assets'=>'ALL_ASSETS',
    ),
    array(
    'time_execute'=>'',
    'id_report'=>'QWxhcm0gUmVwb3J0O2FkbWlu',
    'name_report'=>'Alarm Report',
    'email'=>'fjnavarro@alienvault.com',
    'date_from'=>'2010-06-09',
    'date_to'=>'2010-07-09',
    'date_range'=>'',
    'assets'=>'HOST:192.168.10.1',
    )
        );

// vamos lanzando los reportes
foreach (getScheduler() as $value){
    // comprobamos que sea la hora para lanzarlo
    if(checkTimeExecute($value['next_launch'])){
        // nombre pdf temporal
        $pdfName=date('YmdHis').$value['id_report'].rand();

        // Personalizamos los parámetros del reporte
        $params='save=1&assets='.$value['assets'];
        if(empty($value['date_range'])){
            $params.='&date_from='.$value['date_from'].'&date_to='.$value['date_to'].'&date_range=custom';
        }else{
            $params.='&date_range='.$value['date_range'];
        }

        $step2=exec('wget --cookies=on --keep-session-cookies --load-cookies='.$cookieName.' "'.$server.'/report/wizard_custom_run.php?run='.$value['id_report'].'&'.$params.'" -O -');

        // Lanzamos el reporte
        $step3=exec('wget --cookies=on --keep-session-cookies --load-cookies='.$cookieName.' "'.$server.'/report/wizard_run.php?run='.$value['id_report'].'" -O -');

        // Generamos el pdf
        $step4=exec('wget --cookies=on --keep-session-cookies --load-cookies='.$cookieName.' "'.$server.'/report/wizard_run.php?pdf=true&run='.$value['id_report'].'" -O '.$pdfName.'.pdf');

        // Enviamos por email el pdf
        $listEmails=explode(';',$value['email']);
        foreach($listEmails as $value2){
            if($value2!=';'){
                unset($output);
                $step5=exec('wget --cookies=on --keep-session-cookies --load-cookies='.$cookieName.' --post-data="email='.$value2.'&pdfName='.$pdfName.'" "'.$server.'/report/wizard_email_scheduler.php?format=email&run='.$value['name_report'].'" -O -',$output);
                if (($result=searchString($output,$txtError3))!==FALSE) {
                    echo $result;
                }
            }
            
        }
        if(count($listEmails)>1){
            // Teníamos emails que enviar
            // Enviado bien
            if (($result=searchString($output,$txtError2))!==FALSE) {
                echo $result;
            }
        }

        // Erase pdf
        clean(null,$pdfName);
    }
}
// The end
clean($cookieName);

/* Functions */
function checkTimeExecute($date){
    $arrTime = localtime(time(), true);
    $year = 1900 + $arrTime["tm_year"];
       $mon = 1 + $arrTime["tm_mon"];
       if(count($mon)<2){
           $mon='0'.$mon;
       }
       $mday =  $arrTime["tm_mday"];
       if(count($mday)<2){
           $mday='0'.$mday;
       }
       $wday =  $arrTime["tm_wday"];
       $hour = ($arrTime["tm_hour"]<10) ? "0".$arrTime["tm_hour"] : $arrTime["tm_hour"];
       $min = ($arrTime["tm_min"]<10) ? "0".$arrTime["tm_min"] : $arrTime["tm_min"];
       $sec = ($arrTime["tm_sec"]<10) ? "0".$arrTime["tm_sec"] : $arrTime["tm_sec"];
       echo $date.'=='.$year.'-'.$mon.'-'.$mday.' '.$hour.':00:00';
       if($date==$year.'-'.$mon.'-'.$mday.' '.$hour.':00:00'){
           return true;
       }else{
           return false;
       }
    
}

function clean($cookieName,$pdfName=null){
    if($pdfName!==null){
        @unlink($pdfName.'.pdf');
    }
    if($cookieName!==null){
        @unlink($cookieName);
    }
}

function searchString($output,$txtError){
    foreach ($output as $value){
        if(strpos($value,$txtError['error'])!==FALSE){
            return $txtError['msg']."\n";
        }
    }
    return FALSE;
}
?>