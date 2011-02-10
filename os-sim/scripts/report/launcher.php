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
function getUniqueId($user) {
    // get user
    $user = base64_decode($user);
    $user_explode=explode(';',$user);
    $user_explode=$user_explode[1];
    
    $conn=connectBdOssim();
    $results = mysql_query('SELECT pass FROM users WHERE login="'.$user_explode.'"', $conn) or die(mysql_error());

    if (mysql_num_rows($results)>0) {
         while ($rowResults = mysql_fetch_assoc($results)) {
             $pass = $rowResults['pass'];
         }

         $return=sha1($pass);
    }else{
        $return=sha1(md5($user_explode));
    }

    mysql_close($conn);
    return $return;
}

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
                'id'=>$rowResults['id'],
                'schedule_type'=>$rowResults['schedule_type'],
                'schedule'=>$rowResults['schedule'],
                'next_launch'=>$rowResults['next_launch'],
                'id_report'=>$rowResults['id_report'],
                'name_report'=>$rowResults['name_report'],
                'email'=>$rowResults['email'],
                'date_from'=>$rowResults['date_from'],
                'date_to'=>$rowResults['date_to'],
                'date_range'=>$rowResults['date_range'],
                'assets'=>$rowResults['assets'],
				'save_in_repository'=>$rowResults['save_in_repository']
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
$https=trim(`grep framework_https /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`);
$urlPdf='/usr/share/ossim/www/tmp/scheduler';

$server='http'.(($https=="yes") ? "s" : "").'://'.$server.'/ossim';
$user='admin';
$pass=base64_encode(getUserWeb());
$cookieName=date('YmdHis').rand().'.txt';

// Textos para validar
$txtError=array('error'=>'<p><font color="red">Wrong User & Password</font></p>','msg'=>'fail: Wrong User & Password');
$txtError2=array('error'=>'<p>PDF Sent OK</p>','msg'=>'PDF Sent OK');
$txtError3=array('error'=>'<strong>Invalid address:','msg'=>'fail: Send pdf, error email');

// Nos logeamos
$step1=exec('wget -U "AV Report Scheduler" -q --no-check-certificate --cookies=on --keep-session-cookies --save-cookies='.$cookieName.' --post-data="user='.$user.'&pass='.$pass.'" "'.$server.'/session/login.php" -O -',$output);
if (($result=searchString($output,$txtError))!==FALSE) {
    echo $result;
    clean($cookieName);
    exit();
}

// vamos lanzando los reportes
foreach (getScheduler() as $value){
    // comprobamos que sea la hora para lanzarlo
    if(checkTimeExecute($value['next_launch'])){
		
		// ruta en la que guardar los pdfs
        $dirUser=getUniqueId($value['id_report']).'/'.$value['id'].'/';
        $dirUserPdf=$urlPdf.'/'.$dirUser;
        newFolder($dirUserPdf);
		
		if($value['save_in_repository']=='0'){
			// limpiamos los pdf que haya
			clean(null,$dirUserPdf);
		}
	
        // nombre pdf
        $pdfName=time();

        // Personalizamos los par�metros del reporte
        $params='save=1&assets='.$value['assets'];
        if(empty($value['date_range'])){
            $params.='&date_from='.$value['date_from'].'&date_to='.$value['date_to'].'&date_range=custom';
        }else{
            $params.='&date_range='.$value['date_range'];
        }

        $step2=exec('wget -U "AV Report Scheduler" -q --no-check-certificate --cookies=on --keep-session-cookies --load-cookies='.$cookieName.' "'.$server.'/report/wizard_custom_run.php?run='.$value['id_report'].'&'.$params.'" -O -');

        // Lanzamos el reporte
        $step3=exec('wget -U "AV Report Scheduler" -q --no-check-certificate --cookies=on --keep-session-cookies --load-cookies='.$cookieName.' "'.$server.'/report/wizard_run.php?run='.$value['id_report'].'" -O -');

        // Generamos el pdf
        $step4=exec('wget -U "AV Report Scheduler" -q --no-check-certificate --cookies=on --keep-session-cookies --load-cookies='.$cookieName.' "'.$server.'/report/wizard_run.php?pdf=true&run='.$value['id_report'].'" -O '.$dirUserPdf.$pdfName.'.pdf');

        // Enviamos por email el pdf
        $listEmails=explode(';',$value['email']);
        foreach($listEmails as $value2){
            if($value2!=';'){
                unset($output);
                $step5=exec('wget -U "AV Report Scheduler" -q --no-check-certificate --cookies=on --keep-session-cookies --load-cookies='.$cookieName.' --post-data="email='.$value2.'&pdfName='.$pdfName.'&pdfDir='.$dirUser.'" "'.$server.'/report/wizard_email_scheduler.php?format=email&run='.$value['name_report'].'" -O -',$output);
                
                if (($result=searchString($output,$txtError3))!==FALSE) {
                    echo $result;
                }
            }
            
        }
        if(count($listEmails)>1){
            // Ten�amos emails que enviar
            // Enviado bien
            if (($result=searchString($output,$txtError2))!==FALSE) {
                echo $result;
            }
        }

        // Logout
        exec('wget -U "AV Report Scheduler" -q --no-check-certificate --cookies=on --keep-session-cookies --load-cookies='.$cookieName.' "'.$server.'/session/login.php?action=logout" -O /dev/null');

 
        // update next launch
        $schedule=array(
                'type_name'=>$value['schedule_type'],
                'next_launch'=>$value['next_launch'],
                'data'=>unserialize($value['schedule'])
        );
        updateNextLaunch($schedule,$value['id']);
		
		// Cambiamos los permisos del pdf y su directorio
		$step6=exec('chown -R "www-data" '.$dirUserPdf);

    }
}
// The end
clean($cookieName);

/* Functions */
function checkTimeExecute($date){
    $arrTime = localtime(time(), true);
    $year = 1900 + $arrTime["tm_year"];
	$mon = 1 + $arrTime["tm_mon"];
	$mon=completionDate($mon);

	$mday =  $arrTime["tm_mday"];
	$mday=completionDate($mday);

	$wday =  $arrTime["tm_wday"];
	$hour = ($arrTime["tm_hour"]<10) ? "0".$arrTime["tm_hour"] : $arrTime["tm_hour"];

	if(substr($date,0,13)==$year.'-'.$mon.'-'.$mday.' '.$hour){
	   return true;
	}else{
	   return false;
	}
    
}

function completionDate($date){
	if(strlen($date)<2){
		$date='0'.$date;
	}
	
	return $date;
}

function clean($cookieName,$dirUser=null){
    if($dirUser!==null){
		foreach(scandir($dirUser) as $value){
			if($value!='.'&&$value!='..'){
				if (!is_dir($dirUser.'/'.$value)) unlink($dirUser.'/'.$value);
			}
		}
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

function newFolder($name){
    if (file_exists($name)) {
        return false;
    }else{
        mkdir($name,0755,true);
        return true;
    }
}

function lastDayOfMonth($month = '', $year = ''){
   if (empty($month)) {
      $month = date('m');
   }
   if (empty($year)) {
      $year = date('Y');
   }
   $result = strtotime("{$year}-{$month}-01");
   $result = strtotime('-1 second', strtotime('+1 month', $result));
   return date('d', $result);
}

function updateNextLaunch($schedule,$id){
    switch($schedule['type_name']){
        case 'Run Once':
            $next_launch='0000-00-00 00:00:00';
            break;
        case 'Daily':
             $next_launch=date("Y-m-d H:i:s", strtotime('+1 day',strtotime($schedule['next_launch'])));
            break;
        case 'Day of the Week':
            $next_launch=date("Y-m-d H:i:s", strtotime('next '.$schedule['data']['dayofweek'],strtotime($schedule['next_launch'])));
            $next_launch=explode(' ',$next_launch);
            $next_launch=$next_launch[0].' '.$schedule['data']['time_hour'].':00:00';
            break;
        case 'Day of the Month':
			$next_launch_explode=explode('-',$schedule['next_launch']);

			$Cyear=$next_launch_explode[0];
			$Cmonth=$next_launch_explode[1];
			do{
				$Cmonth++;
				if($Cmonth>12){
					$Cmonth=1;
					$Cyear++;
				}
			}while($schedule['data']['dayofmonth']>lastDayOfMonth($Cmonth,$Cyear));
			
			$Cmonth=completionDate($Cmonth);
			
			$next_launch=$Cyear.'-'.$Cmonth.'-'.$schedule['data']['dayofmonth'].' '.$schedule['data']['time_hour'].':00:00';
            break;
        default:
            $next_launch='0000-00-00 00:00:00';
            break;
    }

    // update bd
    $conn=connectBdOssim();
    $results = mysql_query('UPDATE `custom_report_scheduler` SET `next_launch`="'.$next_launch.'" WHERE `id`="'.$id.'"', $conn) or die(mysql_error());
    
    mysql_close($conn);
    return true;
}
?>