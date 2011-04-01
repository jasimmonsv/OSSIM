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
* - die_error()
* Classes list:
*/
require_once 'classes/Session.inc';
require_once 'classes/Security.inc';
Session::logcheck("MenuIncidents", "IncidentsIncidents");
require_once 'ossim_db.inc';
require_once 'classes/Incident.inc';
require_once 'classes/Incident_type.inc';
require_once 'classes/Incident_ticket.inc';
require_once 'classes/Incident_tag.inc';

function die_error($msg = null, $append = null)
{
    ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html>
    <head>
        <title> <?php echo gettext("OSSIM Framework"); ?> </title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
        <meta http-equiv="Pragma" content="no-cache"/>
        <link rel="stylesheet" type="text/css" href="../style/style.css"/>
    </head>
    <body>
    <?php
		if ($msg) 
			ossim_set_error($msg);
	
		echo ossim_error();
		echo '<table class="noborder transparent" align="center">
				<tr>
					<td class="nobborder"><input type="button" value="' . _("Back") . '" class="button" onclick="history.back()"/></td>
				</tr>
			  </table>';
		echo $append;
    ?>
    </body>
    </html>
    <?php
    exit;
}


$db   	= new ossim_db();
$conn   = $db->connect();

if ( !count($_GET) && count($_POST)>0 ) 
{
	foreach ($_POST as $k => $v) 
		$_GET[$k]=$v;
}


$id        = GET('incident_id');

$action    = ( POST('action' ) == "newincident" )? "newincident": GET('action');
$from_vuln = ( POST('from_vuln') != "" ) ? POST('from_vuln'):GET('from_vuln');
$edit      = ( isset($_GET['edit']) || isset($_POST['edit']) ) ? 1 : 0;

ossim_valid($id, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Id"));
ossim_valid($action, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Action"));
ossim_valid($from_vuln, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("From_vuln"));

if ( ossim_error() ) 
{
   $error = ossim_get_error();
   die_error($error);

}

if ($id != "" && !Incident::user_incident_perms($conn, $id, 'show')) {
	die_error(_("Sorry, you are not allowed to perform this action"));
}

/* Subscriptions Management */
if ($action == 'subscrip') 
{
    // Only admin, entity admin and ticket owner
    if ( !Incident::user_incident_perms($conn, $id, $action) ) 
        die_error(_("You are not allowed to subscribe a new user because you are neither *admin* or the ticket owner"));
    
	if ( POST('login') )
	{
        if (!ossim_valid($id, OSS_DIGIT)) 
            die_error("Wrong ID");
        
        if (ossim_valid(POST('login') , OSS_USER)) 
		{
            if (POST('subscribe')) 
                Incident::insert_subscription($conn, $id, $_POST['login']);
           	elseif (POST('unsubscribe')) 
			    Incident::delete_subscriptions($conn, $id, $_POST['login']);
        } 
		else 
		    die_error("Invalid user");
    }
   
	header("Location: incident.php?id=$id&edit=$edit");
    exit;
}

/* New ticket */
if ($action == 'newticket') 
{
    if (!ossim_valid($id, OSS_DIGIT)) 
		die_error("Wrong ID");
    
	$vals = array(
        'prev_status',
    	'prev_prio',
    	'status',
        'priority',
        'transferred',
        'tag',
        'description',
        'action',
        'transferred_user',
        'transferred_entity'
    );
    
	
	foreach($vals as $var) {
        $$var = POST("$var");
    }
    
		
	if($transferred_user!="")   $transferred = $transferred_user;
    if($transferred_entity!="") $transferred = $transferred_entity;
    
	// Only admin, entity admin and ticket owner can transfer a ticket
    
	if ($transferred != "" && !Incident::user_incident_perms($conn, $id, $action) ) 
        die_error(_("You are not allowed to transfer this incident because you are neither *admin* or the ticket owner"));
   
	
	if ( $priority != $prev_prio && !Incident::user_incident_perms($conn, $id, $action) ) 
        die_error(_("You are not allowed to change priority of this incident because you are neither *admin* or the ticket owner"));
    
	
	if ($status != $prev_status && !Incident::user_incident_perms($conn, $id, $action) ) 
        die_error(_("You are not allowed to change status of this incident because you are neither *admin* or the ticket owner"));
    
    
	if (isset($_FILES['attachment']) && $_FILES['attachment']['tmp_name']) {
        $attachment            = $_FILES['attachment'];
        $attachment['content'] = file_get_contents($attachment['tmp_name']);
    } 
	else 
	{
        $attachment = null;
    }
    
	
	$login = Session::get_session_user();
    $tags  = POST('tags') ? POST('tags') : array();
    
	Incident_ticket::insert($conn, $id, $status, $priority, $login, $description, $action, $transferred, $tags, $attachment);
    
	// Error should be only at the mail() function in Incident_ticket::mail_susbcription()
    
	if (ossim_error()) {
        die_error();
    }
		
	header("Location: incident.php?id=$id&edit=$edit");
    exit;
}

/* Remove a ticket */
if ($action == 'delticket') 
{
    
	if (!GET('ticket_id')) 
        die("Invalid Ticket ID");
	
	// Only admin, entity admin and ticket owner
	
    if (!Incident::user_incident_perms($conn, $id, $action)) 
        die_error(_("You are not allowed to delete this ticket because you are neither *admin* or the ticket owner"));
    
	Incident_ticket::delete($conn, GET('ticket_id'));
    header("Location: incident.php?id=$id&edit=$edit");
    exit;
}

/* Remove an incident */
if ($action == 'delincident') 
{
    // Only admin, entity admin and ticket owner
	
    if (!Incident::user_incident_perms($conn, $id, $action)) 
        die_error(_("You are not allowed to delete this incident because you are neither *admin* or the ticket owner"));
    
	Incident::delete($conn, $id);
    
	header("Location: ./");
    
	exit;
}

/* Updates Incidents*/
if ($action == 'editincident') 
{
    // Only admin, entity admin and ticket owner
    if (!Incident::user_incident_perms($conn, $id, $action))
        die_error(_("You are not allowed to edit this incident because you are neither *admin* or the ticket owner"));
    
		
    if (GET('ref') == 'Alarm' or GET('ref') == 'Event') 
	{
        $method = GET('ref') == 'Alarm' ? 'update_alarm' : 'update_event';
        
		$vars = array(
            'incident_id',
            'title',
            'type',
            'submitter',
            'priority',
            'src_ips',
            'dst_ips',
            'src_ports',
            'dst_ports',
            'event_start',
            'event_end',
			'transferred_user',
			'transferred_entity',
        );
        
		foreach($vars as $v) {
            $$v = GET("$v");
        }
		
		Incident::$method($conn, $incident_id, $title, $type, $submitter, $priority, $src_ips, $dst_ips, $src_ports, $dst_ports, $event_start, $event_end, $transferred_user, $transferred_entity);
    }
    elseif (GET('ref') == 'Metric')
	{
        $vars = array(
            'incident_id',
            'title',
            'type',
            'submitter',
            'priority',
            'target',
            'metric_type',
            'metric_value',
            'event_start',
            'event_end',
			'transferred_user',
			'transferred_entity',
        );
        
		foreach($vars as $v) {
            $$v = GET("$v");
        }
		
		Incident::update_metric($conn, $incident_id, $title, $type, $submitter, $priority, $target, $metric_type, $metric_value, $event_start, $event_end, $transferred_user, $transferred_entity);
    } 
	elseif (GET('ref') == 'Anomaly') 
	{
        if (GET('anom_type') == 'mac') {
            $vars = array(
                'incident_id',
                'title',
                'type',
                'submitter',
                'priority',
                'a_sen',
                'a_date',
                'a_mac',
                'a_mac_o',
                'anom_ip',
                'a_vend',
                'a_vend_o',
				'transferred_user',
				'transferred_entity',
            );
			
            foreach($vars as $v) {
                $$v = GET("$v");
            }
			
			$anom_data_orig = array(
                $a_sen,
                $a_date,
                $a_mac_o,
                $a_vend_o
            );
			
            $anom_data_new = array(
                $a_sen,
                $a_date,
                $a_mac,
                $a_vend
            );
			
            Incident::update_anomaly($conn, $incident_id, $title, $type, $submitter, $priority, 'mac', $anom_ip, $anom_data_orig, $anom_data_new, $transferred_user, $transferred_entity);
        } 
		elseif (GET('anom_type') == 'service')
		{
            $vars = array(
                'incident_id',
                'title',
                'type',
                'submitter',
                'priority',
                'a_sen',
                'a_date',
                'a_port',
                'a_prot_o',
                'a_prot',
                'anom_ip',
                'a_ver',
                'a_ver_o',
				'transferred_user',
				'transferred_entity',
            );
			
            foreach($vars as $v) {
                $$v = GET("$v");
            }
			
			$anom_data_orig = array(
                $a_sen,
                $a_port,
                $a_date,
                $a_prot_o,
                $a_ver_o
            );
			
            $anom_data_new = array(
                $a_sen,
                $a_port,
                $a_date,
                $a_prot,
                $a_ver
            );
			
            Incident::update_anomaly($conn, $incident_id, $title, $type, $submitter, $priority, 'service', $anom_ip, $anom_data_orig, $anom_data_new, $transferred_user, $transferred_entity);
        } 
		elseif (GET('anom_type') == 'os') 
		{
            $vars = array(
                'incident_id',
                'title',
                'type',
                'submitter',
                'priority',
                'a_sen',
                'a_date',
                'a_os',
                'a_os_o',
                'anom_ip',
				'transferred_user',
				'transferred_entity',
            );
            
			foreach($vars as $v) {
                $$v = GET("$v");
            }
            
			$anom_data_orig = array(
                $a_sen,
                $a_date,
                $a_os_o
            );
			
            $anom_data_new = array(
                $a_sen,
                $a_date,
                $a_os
            );
			
            Incident::update_anomaly($conn, $incident_id, $title, $type, $submitter, $priority, 'os', $anom_ip, $anom_data_orig, $anom_data_new, $transferred_user, $transferred_entity);
        } 
    }
    elseif (GET('ref') == 'Vulnerability') 
	{
        $vars = array(
            'incident_id',
            'title',
            'type',
            'submitter',
            'priority',
            'ip',
            'port',
            'nessus_id',
            'risk',
            'description',
			'transferred_user',
			'transferred_entity',
        );
        
		foreach($vars as $v) {
            $$v = GET("$v");
        }
						
        Incident::update_vulnerability($conn, $incident_id, $title, $type, $submitter, $priority, $ip, $port, $nessus_id, $risk, $description, $transferred_user, $transferred_entity);
    }
    
	if ( ossim_error() ) 
		die_error();
	
	header("Location: incident.php?id=$incident_id&edit=$edit");
    exit;
}


/*
	Insert new Incident 
*/

if ($action == 'newincident') 
{
    if (GET('ref') == 'Alarm' or GET('ref') == 'Event') 
	{
        $method = GET('ref') == 'Alarm' ? 'insert_alarm' : 'insert_event';
        $vars = array(
            'title',
            'type',
            'submitter',
            'priority',
            'src_ips',
            'dst_ips',
            'src_ports',
            'dst_ports',
            'backlog_id',
            'event_id',
            'alarm_group_id',
            'event_start',
            'event_end',
            'transferred_user',
            'transferred_entity'
        );
        
		foreach($vars as $v) {
            $$v = GET("$v");
        }
        		        
        if($method == 'insert_alarm')
            $incident_id = Incident::insert_alarm($conn, $title, $type, $submitter, $priority, $src_ips, $dst_ips, $src_ports, $dst_ports, $event_start, $event_end, $backlog_id, $event_id, $alarm_group_id, $transferred_user, $transferred_entity);
        else
            $incident_id = Incident::insert_event($conn, $title, $type, $submitter, $priority, $src_ips, $dst_ips, $src_ports, $dst_ports, $event_start, $event_end, $transferred_user, $transferred_entity);

    }
    elseif (GET('ref') == 'Metric') 
	{
        
		$vars = array(
            'title',
            'type',
            'submitter',
            'priority',
            'target',
            'metric_type',
            'metric_value',
            'event_start',
            'event_end',
            'transferred_user',
            'transferred_entity'
        );
        
		foreach($vars as $v) {
            $$v = GET("$v");
        }
                
        $incident_id = Incident::insert_metric($conn, $title, $type, $submitter, $priority, $target, $metric_type, $metric_value, $event_start, $event_end, $transferred_user, $transferred_entity);
    
	} 
	elseif (GET('ref') == 'Anomaly') 
	{
        if (GET('anom_type') == 'mac') 
		{
            $vars = array(
                'title',
                'type',
                'submitter',
                'priority',
                'a_sen',
                'a_date_o',
                'a_date',
                'a_mac',
                'a_mac_o',
                'anom_ip',
                'a_vend',
                'a_vend_o',
                'transferred_user',
                'transferred_entity'
            );
            
			foreach($vars as $v) {
                $$v = GET("$v");
            }
            
			$anom_data_orig = array(
                $a_sen,
                $a_date,
                $a_mac_o,
                $a_vend_o
            );
			
            $anom_data_new = array(
                $a_sen,
                $a_date,
                $a_mac,
                $a_vend
            );
            
         
            $incident_id = Incident::insert_anomaly($conn, $title, $type, $submitter, $priority, 'mac', $anom_ip, $anom_data_orig, $anom_data_new, $transferred_user, $transferred_entity);
        } 
		elseif (GET('anom_type') == 'service') 
		{
            $vars = array(
                'title',
                'type',
                'submitter',
                'priority',
                'a_sen',
                'a_date',
                'a_port',
                'a_prot_o',
                'a_prot',
                'anom_ip',
                'a_ver',
                'a_ver_o',
                'transferred_user',
                'transferred_entity'
            );
			
            foreach($vars as $v) {
                $$v = GET("$v");
            }
			
            $anom_data_orig = array(
                $a_sen,
                $a_date,
                $a_port,
                $a_prot_o,
                $a_ver_o
            );
			
            $anom_data_new = array(
                $a_sen,
                $a_date,
                $a_port,
                $a_prot,
                $a_ver
            );
            
                     
            $incident_id = Incident::insert_anomaly($conn, $title, $type, $submitter, $priority, 'service', $anom_ip, $anom_data_orig, $anom_data_new, $transferred_user, $transferred_entity);
        } 
		elseif (GET('anom_type') == 'os') 
		{
            $vars = array(
                'title',
                'type',
                'submitter',
                'priority',
                'a_sen',
                'a_date',
                'a_os',
                'a_os_o',
                'anom_ip',
                'transferred_user',
                'transferred_entity'
            );
			
            foreach($vars as $v) {
                $$v = GET("$v");
            }
			
            $anom_data_orig = array(
                $a_sen,
                $a_date,
                $a_os_o
            );
			
            $anom_data_new = array(
                $a_sen,
                $a_date,
                $a_os
            );
            
                       
            $incident_id = Incident::insert_anomaly($conn, $title, $type, $submitter, $priority, 'os', $anom_ip, $anom_data_orig, $anom_data_new, $transferred_user, $transferred_entity);
        } 
    } 
    elseif (GET('ref') == 'Vulnerability' || POST('ref') == 'Vulnerability') 
	{
        $vars = array(
            'title',
            'type',
            'submitter',
            'priority',
            'ip',
            'port',
            'nessus_id',
            'risk',
            'description',
            'transferred_user',
            'transferred_entity'
        );
        
		foreach($vars as $v) {
            $$v = (POST("$v")!="")? POST("$v"):GET("$v"); 
        }
        
               
        $incident_id = Incident::insert_vulnerability($conn, $title, $type, $submitter, $priority, $ip, $port, $nessus_id, $risk, $description, $transferred_user, $transferred_entity);
    } 
    elseif (GET('ref') == 'Custom') 
	{
        $vars = array(
            'title',
            'type',
            'submitter',
            'priority',
            'transferred_user',
            'transferred_entity'
        );
        
		foreach($vars as $v) {
            $$v = GET("$v"); 
        }

		$fields = array();
        
		foreach ($_GET as $k => $v) 
		{
			if (preg_match("/^custom/",$k)) 
			{
				$k           = base64_decode(str_replace("custom_","",$k)); 
				$item        = explode("_####_", $k);
				$custom_type = ( count($item) >= 2 ) ? $item[1] : "Textbox";
				//
				$fields[] =  array ("validate" => 1, "name" => $item[0], "content" => $v, "type"=> $custom_type);
			}
        }
       
		// Uploaded "File" type
        
		foreach ($_FILES as $k => $v) 
		{
			if (preg_match("/^custom/",$k)) 
			{
				$content = $v['tmp_name'];
				$k       = base64_decode(str_replace("custom_","",$k)); 
				$item    = explode("_####_", $k);
				
				if (is_uploaded_file($v['tmp_name']) && !$v['error'])
					$content = file_get_contents($v['tmp_name']);
				else
					$content = _("Failed uploading file. Error: ".$v['error']);
					
				$fields[] =  array ("validate" => 0, "name" => $item[0], "content" => $content, "type"=> "File");
			}
        }
                       
        $incident_id = Incident::insert_custom($conn, $title, $type, $submitter, $priority, $transferred_user, $transferred_entity, $fields);
		
    }
	
    if (ossim_error()) 
	{
        die_error();
    }
    
	if( intval($from_vuln) == 1 )
        header("Location: index.php?hmenu=Tickets&smenu=Tickets"); 
    else
        header("Location: incident.php?id=$incident_id&edit=$edit");
    exit;
}
?>