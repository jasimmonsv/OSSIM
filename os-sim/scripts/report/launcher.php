<?php
/***************************************************************************
*
*   Copyright (c) 2007-2010 AlienVault
*   All rights reserved.
*
****************************************************************************/

ob_implicit_flush();
ini_set('include_path', '/usr/share/ossim/include');

require_once("classes/Util.inc");
require_once("ossim_db.inc");

// Get user uuid
function get_report_uuid($conn, $user) {
    $uuid = "";
    
    if (!$rs = & $conn->Execute("SELECT * FROM users WHERE login='".$user."'")) {
        print $conn->ErrorMsg();
        exit();
    } else {
         if(!$rs->EOF) {
            $uuid = $rs->fields["uuid"];
        }
    }
    if ( $uuid == "" ) {
        $uuid = sha1($rowResult['login']."#".$rowResult['pass']);
    }
    return $uuid;
}

function getScheduler($conn)
{
    $return  = array();
    
    if (!$rs = & $conn->Execute("SELECT * FROM custom_report_scheduler ORDER BY id")) {
        print $conn->ErrorMsg();
        exit();
    } else {
         while (!$rs->EOF) {
            $return[]=array(
                    'id'					=>$rs->fields['id'],
                    'schedule_type'			=>$rs->fields['schedule_type'],
                    'schedule_name'			=>$rs->fields['schedule_name'],
                    'schedule'				=>$rs->fields['schedule'],
                    'next_launch'			=>$rs->fields['next_launch'],
                    'id_report'				=>$rs->fields['id_report'],
                    'name_report'			=>$rs->fields['name_report'],
                    'user'					=>$rs->fields['user'],
                    'email'					=>$rs->fields['email'],
                    'date_from'				=>$rs->fields['date_from'],
                    'date_to'				=>$rs->fields['date_to'],
                    'date_range'			=>$rs->fields['date_range'],
                    'assets'				=>$rs->fields['assets'],
                    'save_in_repository'    =>$rs->fields['save_in_repository']
            );
            $rs->MoveNext();
        }
    }

    return $return;
}

function getUserWeb($conn)
{
    $return = "";
    if (!$rs = & $conn->Execute("SELECT * FROM users WHERE login='admin'")) {
        print $conn->ErrorMsg();
        exit();
    } else {
         if(!$rs->EOF) {
            $return = $rs->fields["pass"];
        }
    }
    
    return $return;
}

function checkTimeExecute($date)
{
	if( substr($date,0,13) == gmdate("Y-m-d H") )
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


function updateNextLaunch($conn, $schedule,$id){

    switch($schedule['type'])
	{
        case 'O':
            $next_launch = '0000-00-00 00:00:00';
        break;
        
		case 'D':
            $next_launch = Util::get_utc_date_calc($conn, $schedule['next_launch'], "1 DAY");
        break;
        
		case 'W':
            $next_launch = Util::get_utc_date_calc($conn, $schedule['next_launch'], "1 WEEK");
        break;
        
		case 'M':
            $next_launch = Util::get_utc_date_calc($conn, $schedule['next_launch'], "1 MONTH");
        break;
        
		default:
            $next_launch = '0000-00-00 00:00:00';
        break;
    }

    // Update DB
    if ($conn->Execute("UPDATE custom_report_scheduler SET next_launch='".$next_launch."' WHERE id='".$id."'") === false) {
            print 'Error updating: ' . $conn->ErrorMsg() . '<br/>';
            exit;
        }
    
    return true;
}
// end functions


// get database connection
$db = new ossim_db();
$conn = $db->connect();

//Errors text
$info_text  = array( _('Wrong User & Password'), _('Invalid address') );


$server  	= trim(`grep framework_ip /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`);
$https   	= trim(`grep framework_https /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`);
$urlPdf  	= '/usr/share/ossim/www/tmp/scheduler';

$server     = 'http'.(($https=="yes") ? "s" : "").'://'.$server.'/ossim';
$user       = 'admin';
$pass       = base64_encode(getUserWeb($conn));
$cookieName = date('YmdHis').rand().'.txt';


system("clear");
$to_text .= "\n\n"._('Date').': '.date("Y-m-d H:i:s")."\n\n";
$to_text .= _('Starting Report Scheduler')."...\n\n";


// Run reports
$report_list = getScheduler($conn);


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
	
	$text     = _('Next Launch (UTC)').':';
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
       
		$uuid = get_report_uuid($conn, $user);
		
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
		
		if ( preg_match("/ENTITY\:(\d+)/", $value["assets"], $fnd)) 
		{
			$entity  = Acl::get_entity($conn,$fnd[1]);
			$assets  = "ENTITY: ".$entity['name'];
		}
		else
			$assets  = $value['assets'];
		
		
		$pdfNameEmail  = str_replace($str_to_replace, "_", $value['name_report'])."_".str_replace($str_to_replace, "_", $assets);
		$subject_email = $value['name_report']." [".$assets."]";
		$pdfName       = $pdfNameEmail."_".time();
				
		$text     = _('Save to').':';
		$to_text .= sprintf("\t%-20s", $text);
		$to_text .= $dirUserPdf.$pdfName.".pdf\n";		
						
		
        // Customize parameters
        $params  ='scheduler=1&assets='.$value['assets'];
        $params .= ( empty($value['date_range']) ) ? '&date_from='.$value['date_from'].'&date_to='.$value['date_to'].'&date_range=custom' : '&date_range='.$value['date_range'];
        
		
				        
        //$step2 = exec('wget -U "AV Report Scheduler" -q --no-check-certificate --cookies=on --keep-session-cookies --load-cookies='.$cookieName.' "'.$server.'/report/wizard_custom_run.php?run='.$value['id_report'].'&'.$params.'" -O -');
		
		
        // Run Report
        $step2 = exec('wget -U "AV Report Scheduler" -q --no-check-certificate --cookies=on --keep-session-cookies --load-cookies='.$cookieName.' "'.$server.'/report/wizard_run.php?run='.$value['id_report'].'&'.$params.'" -O -');
		
		
        // Generate PDF
		$text = _('Generating PDF').'...';
		$to_text .= sprintf("\n\t%s", $text);
		
		$step3 = exec('wget -U "AV Report Scheduler" -q --no-check-certificate --cookies=on --keep-session-cookies --load-cookies='.$cookieName.' "'.$server.'/report/wizard_run.php?pdf=true&extra_data=true&run='.$value['id_report'].'" -O '.$dirUserPdf.$pdfName.'.pdf', $output);
		
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
				$step4  = exec('wget -U "AV Report Scheduler" -q --no-check-certificate --cookies=on --keep-session-cookies --load-cookies='.$cookieName.' --post-data="email='.$value2.'&pdfName='.$pdfName.'&pdfDir='.$dirUser.'&subject='.$subject_email.'" "'.$server.'/report/wizard_email_scheduler.php?format=email&run='.$pdfNameEmail.'" -O -',$output);
				
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
       
	    updateNextLaunch($conn,$schedule,$value['id']);
		
		$text     = _('Updating next launch').'...';
		$to_text .= sprintf("\n\t%s", $text);
		
		
		// Set appropiate permissions 
		$step5    = exec('chown -R "www-data" '.$dirUserPdf);
		
		$text     = _('Process completed');
		$to_text .= sprintf("\n\t%s", $text);
		
    }
	
	$to_text .= "\n";
}

// Logout
exec('wget -U "AV Report Scheduler" -q --no-check-certificate --cookies=on --keep-session-cookies --load-cookies='.$cookieName.' "'.$server.'/session/login.php?action=logout" -O /dev/null');

$to_text .= "\n"._('Report Scheduler completed')."\n\n";

echo $to_text;

// End
$db->close($conn);
clean($cookieName);

?>