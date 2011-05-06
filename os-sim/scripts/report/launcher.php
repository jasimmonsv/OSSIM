<?php
/***************************************************************************
*
*   Copyright (c) 2007-2010 AlienVault
*   All rights reserved.
*
****************************************************************************/

ob_implicit_flush();

// Get user uuid
function get_report_uuid($user) {
    
	$uuid    = false;
    
    $conn    = connectBdOssim();
    $result  = mysql_query("SELECT * FROM users WHERE login='".$user."'", $conn); 
	
	if ( !$result ) 
	{
		$to_text .= sprintf("\n%s\n\n", mysql_error());
		echo $to_text;
		mysql_close($conn);
		exit();
	}
		

    if ( mysql_num_rows($result)> 0 ) 
	{
        $rowResult = mysql_fetch_assoc($result);
		
		$uuid       = $rowResult['uuid'];
		
		if ( $uuid == null )
			$uuid = sha1($rowResult['login']."#".$rowResult['pass']);
    }
	    
    mysql_close($conn);
    
	return $uuid;
}


function connectBdOssim()
{
    $userdb=trim(`grep user /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`);
    $passdb=trim(`grep pass /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`);
    $hostdb=trim(`grep db_ip /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`);

    $conn   = mysql_connect($hostdb, $userdb, $passdb);
    $dbconn = mysql_select_db('ossim', $conn);
		
	if ( !$dbconn ) 
	{
		$to_text .= sprintf("\n%s\n\n", _('Not connected').' : ' . mysql_error());
		echo $to_text;
		exit();
	}
	

    return $conn;
}

function getScheduler()
{
    $conn	  = connectBdOssim();
    $results  = mysql_query('SELECT * FROM custom_report_scheduler ORDER BY id', $conn);

	if ( !$results ) 
	{
		$to_text .= sprintf("\n%s\n\n", mysql_error());
		echo $to_text;
		mysql_close($conn);
		exit();
	}
	
    $return  = array();

    if (mysql_num_rows($results)>0) 
	{
		while ($rowResults = mysql_fetch_assoc($results) )
		{
			$return[]=array(
					'id'					=>$rowResults['id'],
					'schedule_type'			=>$rowResults['schedule_type'],
					'schedule_name'			=>$rowResults['schedule_name'],
					'schedule'				=>$rowResults['schedule'],
					'next_launch'			=>$rowResults['next_launch'],
					'id_report'				=>$rowResults['id_report'],
					'name_report'			=>$rowResults['name_report'],
					'user'					=>$rowResults['user'],
					'email'					=>$rowResults['email'],
					'date_from'				=>$rowResults['date_from'],
					'date_to'				=>$rowResults['date_to'],
					'date_range'			=>$rowResults['date_range'],
					'assets'				=>$rowResults['assets'],
					'save_in_repository'    =>$rowResults['save_in_repository']
			);
		}
    }

    mysql_close($conn);
    return $return;
}

function getUserWeb()
{
    $conn    = connectBdOssim();
    $result  = mysql_query('SELECT * FROM users WHERE login="admin"', $conn);
	
	if ( !$result ) 
	{
		$to_text .= sprintf("\n%s\n\n", mysql_error());
		echo $to_text;
		mysql_close($conn);
		exit();
	}
	
    if (mysql_num_rows($result)>0) 
	{
		$rowResult = mysql_fetch_assoc($result);
		$return    = $rowResult['pass'];
	}
    
    mysql_close($conn);
    
	return $return;
}

function checkTimeExecute($date)
{
    $arrTime = localtime(time(), true);
	
	$year    = 1900 + $arrTime["tm_year"];
	$mon     = 1 + $arrTime["tm_mon"];
	$mon     = completionDate($mon);

	$mday    = $arrTime["tm_mday"];
	$mday    = completionDate($mday);

	$wday    =  $arrTime["tm_wday"];
	$hour    = ( $arrTime["tm_hour"]<10 ) ? "0".$arrTime["tm_hour"] : $arrTime["tm_hour"];

	if( substr($date,0,13) == $year.'-'.$mon.'-'.$mday.' '.$hour )
		return true;
	else
		return false;
}

function completionDate($date){
	
	$date = ( strlen($date) < 2 ) ? '0'.$date : $date;
		
	return $date;
}

function clean($cookieName,$dirUser=null)
{
    if( $dirUser!==null )
	{
		foreach(scandir($dirUser) as $value)
		{
			if( $value !='.' && $value!='..' && !is_dir($dirUser.'/'.$value) )
				@unlink($dirUser.'/'.$value);
		}
    }
    
	if($cookieName!==null)
        @unlink($cookieName);
    
}

function searchString($output,$info_text)
{
    if ( is_array ($output) )
	{
		foreach ($output as $value)
		{
			$pattern = "/".$info_text."/";
			if( preg_match($pattern, $value) )
				return true;
		}
	}
	
	return false;
		
}

function newFolder($name)
{
    if ( file_exists($name) )
	{
        return false;
    }
	else{
        mkdir($name,0755,true);
        return true;
    }
}

//Last Day of Month
function lastDayOfMonth($month = '', $year = ''){
   $month = ( empty($month) ) ? date('m') : $month;
   $year  = ( empty($year) )  ? date('Y') : $year;
      
   $result = strtotime("{$year}-{$month}-01");
   $result = strtotime('-1 second', strtotime('+1 month', $result));
   
   return date('d', $result);
}


function updateNextLaunch($schedule,$id){
    switch($schedule['type'])
	{
        case 'O':
            $next_launch = '0000-00-00 00:00:00';
        break;
        
		case 'D':
             $next_launch = date("Y-m-d H:i:s", strtotime('+1 day',strtotime($schedule['next_launch'])));
        break;
        
		case 'W':
            $next_launch = date("Y-m-d H:i:s", strtotime('next '.$schedule['data']['dayofweek'],strtotime($schedule['next_launch'])));
            $next_launch = explode(' ',$next_launch);
            $next_launch = $next_launch[0].' '.$schedule['data']['time_hour'].':00:00';
        break;
        
		case 'M':
			$next_launch_explode = explode('-',$schedule['next_launch']);

			$cyear  = $next_launch_explode[0];
			$cmonth = $next_launch_explode[1];
			do{
				$cmonth++;
				if($cmonth>12){
					$cmonth=1;
					$cyear++;
				}
			}while($schedule['data']['dayofmonth']>lastDayOfMonth($cmonth,$cyear));
			
			$cmonth=completionDate($cmonth);
			
			$next_launch = $cyear.'-'.$cmonth.'-'.$schedule['data']['dayofmonth'].' '.$schedule['data']['time_hour'].':00:00';
        break;
        
		default:
            $next_launch = '0000-00-00 00:00:00';
        break;
    }

    // Update DB
    $conn     = connectBdOssim();
    $result   = mysql_query('UPDATE `custom_report_scheduler` SET `next_launch`="'.$next_launch.'" WHERE `id`="'.$id.'"', $conn);
    
	if ( !$result ) 
	{
		$to_text .= sprintf("\n%s", mysql_error());
		echo $to_text;
		mysql_close($conn);
		exit();
	}
		
	
    mysql_close($conn);
    return true;
}


//Errors text
$info_text  = array( _('Wrong User & Password'), _('Invalid address') );


$server  	= trim(`grep framework_ip /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`);
$https   	= trim(`grep framework_https /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`);
$urlPdf  	= '/usr/share/ossim/www/tmp/scheduler';

$server     = 'http'.(($https=="yes") ? "s" : "").'://'.$server.'/ossim';
$user       = 'admin';
$pass       = base64_encode(getUserWeb());
$cookieName = date('YmdHis').rand().'.txt';


system("clear");
$to_text .= "\n\n"._('Date').': '.date("Y-m-d H:i:s")."\n\n";
$to_text .= _('Starting Report Scheduler')."...\n\n";



// Run reports
$report_list = getScheduler();

foreach ( $report_list as $value)
{
    
	// Login
	$step1 = exec('wget -U "AV Report Scheduler" -q --no-check-certificate --cookies=on --keep-session-cookies --save-cookies='.$cookieName.' --post-data="user='.$user.'&pass='.$pass.'" "'.$server.'/session/login.php" -O -',$output);

	$result = searchString($output,$info_text[0]);

	if ( $result == true )
	{
		$to_text .= sprintf("\n%-15s\n\n", _('ERROR: Wrong User & Password'));
		echo $to_text;
		clean($cookieName);
		exit();
	}
		
	
	$r_data   = base64_decode($value['id_report']);
    $r_data   = explode('###',$r_data);
	$user     = $r_data[1];
	
	$run      = checkTimeExecute($value['next_launch']);
		
	$to_text .= _('Scheduled Report').': '. $value['name_report'].' - Created by: '.$value['user']."\n";	
	
	$text     = _('Next Launch').':';
	$to_text .= sprintf("\t%-20s", $text);
	$to_text .= $value['next_launch']."\n";
	
	$run_text = ( $run == true ) ? _('Yes') : _('No');
	$text     = _('Run now').':';
	$to_text .= sprintf("\t%-20s", $text);
	$to_text .= $run_text."\n";
	
	
	$text     = _('Schedule type').':';
	$to_text .= sprintf("\t%-20s", $text);
	$to_text .= $value['schedule_name']."\n";
	
	// Check time to execute
    if( $run )
	{
		// Path to save PDF
       
		$uuid = get_report_uuid($user);
		
		if ($uuid === false)
			continue;
				
	    $dirUser    = $uuid.'/'.$value['id'].'/';
        $dirUserPdf = $urlPdf.'/'.$dirUser;
        
		newFolder($dirUserPdf);
		
		if( $value['save_in_repository'] == '0' )
		{
			// Delete reports list
			clean(null,$dirUserPdf);
		}
	
        // Set name
		$str_to_replace = array(" ", ":", ".", "&");
		$pdfNameEmail  = str_replace($str_to_replace, "_", $value['name_report'])."_".str_replace($str_to_replace, "_", $value['assets']);
		$subject_email = $value['name_report']." [".$value['assets']."]";
		$pdfName       = $pdfNameEmail."_".time();
				
		$text     = _('Save to').':';
		$to_text .= sprintf("\t%-20s", $text);
		$to_text .= $dirUserPdf.$pdfName.".pdf\n";		
						
		
        // Customize parameters
        $params  ='save=1&assets='.$value['assets'];
        $params .= ( empty($value['date_range']) ) ? '&date_from='.$value['date_from'].'&date_to='.$value['date_to'].'&date_range=custom' : '&date_range='.$value['date_range'];
        
				        
        $step2 = exec('wget -U "AV Report Scheduler" -q --no-check-certificate --cookies=on --keep-session-cookies --load-cookies='.$cookieName.' "'.$server.'/report/wizard_custom_run.php?run='.$value['id_report'].'&'.$params.'" -O -');

		
				
        // Run Report
        $step3 = exec('wget -U "AV Report Scheduler" -q --no-check-certificate --cookies=on --keep-session-cookies --load-cookies='.$cookieName.' "'.$server.'/report/wizard_run.php?run='.$value['id_report'].'" -O -');
		
		
        // Generate PDF
		$text = _('Generating PDF').'...';
		$to_text .= sprintf("\n\t%s", $text);
		
        $step4 = exec('wget -U "AV Report Scheduler" -q --no-check-certificate --cookies=on --keep-session-cookies --load-cookies='.$cookieName.' "'.$server.'/report/wizard_run.php?pdf=true&run='.$value['id_report'].'" -O '.$dirUserPdf.$pdfName.'.pdf', $output);

				
        // Send PDF by email
		
		$listEmails = ( !empty($value['email']) ) ? explode(';',$value['email']) : null;
		$email_ko   = array();
		$email_ok   = array();
		
				
		if ( is_array($listEmails) && !empty($listEmails) )
		{
			$text     = _('Sending E-mails').'...';
			$to_text .= sprintf("\n\t%s\n", $text);
			
			$output   = null;
			
			foreach($listEmails as $value2)
			{
				$step5  = exec('wget -U "AV Report Scheduler" -q --no-check-certificate --cookies=on --keep-session-cookies --load-cookies='.$cookieName.' --post-data="email='.$value2.'&pdfName='.$pdfName.'&pdfDir='.$dirUser.'&subject='.$subject_email.'" "'.$server.'/report/wizard_email_scheduler.php?format=email&run='.$pdfNameEmail.'" -O -',$output);
				
				$result = searchString($output,$info_text[1]);
				
				if ( $result == false ) 
					$email_ok[] = $value2;
				else
					$email_ko[] = $value2; 
				
				$output = null;
			}
			
			if( count($email_ko) > 0 )
				$to_text .= sprintf("\t\t%s\n", _('Invalid address').': '.implode(",",$email_ko));
			
			if( count($email_ok) > 0 )
				$to_text .= sprintf("\t\t%s\n", _('PDF sent OK by email to').': '.implode(",",$email_ok));
		}       

         
        // Update next launch
        $schedule=array(
                'type'        => $value['schedule_type'],
                'next_launch' => $value['next_launch'],
                'data'        => unserialize($value['schedule'])
        );
       
	    updateNextLaunch($schedule,$value['id']);
		
		$text     = _('Updating next launch').'...';
		$to_text .= sprintf("\n\t%s", $text);
		
		
		// Set appropiate permissions 
		$step6    = exec('chown -R "www-data" '.$dirUserPdf);
		
		$text     = _('Process completed');
		$to_text .= sprintf("\n\t%s", $text);
		
    }
	
	$to_text .= "\n\n";
}

// Logout
exec('wget -U "AV Report Scheduler" -q --no-check-certificate --cookies=on --keep-session-cookies --load-cookies='.$cookieName.' "'.$server.'/session/login.php?action=logout" -O /dev/null');


$to_text .= "\n\n"._('Report Scheduler completed')."\n\n";

echo $to_text;

// End
clean($cookieName);

?>