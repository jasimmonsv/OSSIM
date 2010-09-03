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
require_once 'classes/Incident_ticket.inc';
require_once 'classes/Incident_tag.inc';
function die_error($msg = null, $append = null) {
    if ($msg) ossim_set_error($msg);
    echo ossim_error();
    echo '<table class="noborder" align="center"><tr><td class="nobborder"><input type="button" value="' . _("Back") . '" class="btn" onclick="history.go(-1)"></td></tr></table>';
    echo $append;
    exit;
}

$db = new ossim_db();
$conn = $db->connect();
$id = GET('incident_id');
$action = (POST('action')=="newincident")? "newincident":GET('action');
$from_vuln = (POST('from_vuln')!="")? POST('from_vuln'):GET('from_vuln');

ossim_valid($id, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("id"));
ossim_valid($action, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("action"));
ossim_valid($from_vuln, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("from_vuln"));

if (ossim_error()) {
    die(ossim_error());
}
$user = Session::get_session_user();
//if ($id != "" && !in_array($user, Incident::get_users_list($conn, $id))) {
if ($id != "" && !Incident::user_incident_perms($conn, $user, $id)) {
	die_error(_("Sorry, you are not allowed to perform this action"));
}
/*
if ($id != "" && !Incident::user_incident_perms($conn, $user, $id)) {
	die_error(_("You are not allowed to access this page because you are
                 neither *admin* or the ticket owner"));
}
*/
//
// Subscriptions management
//
if ($action == 'subscrip') {
    // only admin and ticket owner
    if (!Incident::user_incident_perms($conn, $user, $id)) {
        die_error(_("You are not allowed to subscribe a new user because
                     you are neither *admin* or the ticket owner"));
    }
	if (POST('login')) {
        if (!ossim_valid($id, OSS_DIGIT)) {
            die_error("Wrong ID");
        }
        if (ossim_valid(POST('login') , OSS_USER)) {
            if (POST('subscribe')) {
                Incident::insert_subscription($conn, $id, $_POST['login']);
            } elseif (POST('unsubscribe')) {
                Incident::delete_subscriptions($conn, $id, $_POST['login']);
            }
        } else {
            die_error("Invalid user");
        }
    }
    header("Location: incident.php?id=$id");
    exit;
}
//
// Ticket new
//
if ($action == 'newticket') {
    if (!ossim_valid($id, OSS_DIGIT)) die_error("Wrong ID");
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
    // only admin and ticket owner can transfer a ticket
    if ($transferred != "") {
        if (!Incident::user_incident_perms($conn, $user, $id)) {
            die_error(_("You are not allowed to transfer this incident because
                         you are neither *admin* or the ticket owner"));
        }
    }
	if ($priority != $prev_prio) {
        if (!Incident::user_incident_perms($conn, $user, $id)) {
            die_error(_("You are not allowed to change priority of this incident because
                         you are neither *admin* or the ticket owner"));
        }
    }
	if ($status != $prev_status) {
        if (!Incident::user_incident_perms($conn, $user, $id)) {
            die_error(_("You are not allowed to change status of this incident because
                         you are neither *admin* or the ticket owner"));
        }
    }
    if (isset($_FILES['attachment']) && $_FILES['attachment']['tmp_name']) {
        $attachment = $_FILES['attachment'];
        $attachment['content'] = file_get_contents($attachment['tmp_name']);
    } else {
        $attachment = null;
    }
    $user = Session::get_me($conn);
    $login = $user->get_login();
    $tags = POST('tags') ? POST('tags') : array();
    Incident_ticket::insert($conn, $id, $status, $priority, $login, $description, $action, $transferred, $tags, $attachment);
    // Error should be only at the mail() function in Incident_ticket::mail_susbcription()
    if (ossim_error()) {
        die_error(null, "<table class='noborder' align='center'><tr><td class='nobborder'><input type='button' onclick=\"document.location.href='incident.php?id=$id'\" value=" . _("Continue") . ' class="btn"></td></tr></table>');
    }
    header("Location: incident.php?id=$id");
    exit;
}
//
// Ticket deletion
//
if ($action == 'delticket') {
    // only admin and ticket owner
    if (!Incident::user_incident_perms($conn, $user, $id)) {
        die_error(_("You are not allowed to delete this ticket because
                     you are neither *admin* or the ticket owner"));
    }
	if (!GET('ticket_id')) {
        die("Invalid Ticket ID");
    }
    Incident_ticket::delete($conn, GET('ticket_id'));
    header("Location: incident.php?id=$id");
    exit;
}
//
// Incident deletion
//
if ($action == 'delincident') {
    // only admin and ticket owner
    if (!Incident::user_incident_perms($conn, $user, $id)) {
        die_error(_("You are not allowed to delete this incident because
                     you are neither *admin* or the ticket owner"));
    }
	Incident::delete($conn, $id);
    header("Location: ./");
    exit;
}
//
// Incident edit
//
if ($action == 'editincident') {
    // only admin and ticket owner
    if (!Incident::user_incident_perms($conn, $user, $id)) {
        die_error(_("You are not allowed to edit this incident because
                     you are neither *admin* or the ticket owner"));
    }
	/* update alarm|event incident */
    if (GET('ref') == 'Alarm' or GET('ref') == 'Event') {
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
            'event_end'
        );
        foreach($vars as $v) {
            $$v = GET("$v");
        }
        Incident::$method($conn, $incident_id, $title, $type, $submitter, $priority, $src_ips, $dst_ips, $src_ports, $dst_ports, $event_start, $event_end);
    }
    /* update metric incident */
    elseif (GET('ref') == 'Metric') {
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
            'event_end'
        );
        foreach($vars as $v) {
            $$v = GET("$v");
        }
        Incident::update_metric($conn, $incident_id, $title, $type, $submitter, $priority, $target, $metric_type, $metric_value, $event_start, $event_end);
    } elseif (GET('ref') == 'Anomaly') {
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
                'a_vend_o'
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
            Incident::update_anomaly($conn, $incident_id, $title, $type, $submitter, $priority, 'mac', $anom_ip, $anom_data_orig, $anom_data_new);
        } elseif (GET('anom_type') == 'service') {
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
                'a_ver_o'
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
            Incident::update_anomaly($conn, $incident_id, $title, $type, $submitter, $priority, 'service', $anom_ip, $anom_data_orig, $anom_data_new);
        } elseif (GET('anom_type') == 'os') {
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
                'anom_ip'
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
            Incident::update_anomaly($conn, $incident_id, $title, $type, $submitter, $priority, 'os', $anom_ip, $anom_data_orig, $anom_data_new);
        } /*elseif os*/
    } /*elseif anomaly*/
    elseif (GET('ref') == 'Vulnerability') {
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
            'description'
        );
        foreach($vars as $v) {
            $$v = GET("$v");
        }
        Incident::update_vulnerability($conn, $incident_id, $title, $type, $submitter, $priority, $ip, $port, $nessus_id, $risk, $description);
    } /*elseif vulnerability*/
    if (ossim_error()) die_error();
    header("Location: incident.php?id=$incident_id");
    exit;
}
//
// Incident new
//
if ($action == 'newincident') {
    /* insert new alarm|event incident */
	
	if (GET('ref') == 'Alarm' or GET('ref') == 'Event') {
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
        if($transferred_user!="")   $transferred = $transferred_user;  
        if($transferred_entity!="") $transferred = $transferred_entity;
        if($transferred=="") $transferred = Session::get_session_user();
        
        if($method == 'insert_alarm')
            $incident_id = Incident::insert_alarm($conn, $title, $type, $submitter, $priority, $src_ips, $dst_ips, $src_ports, $dst_ports, $event_start, $event_end, $backlog_id, $event_id, $alarm_group_id, $transferred);
        else
            $incident_id = Incident::insert_event($conn, $title, $type, $submitter, $priority, $src_ips, $dst_ips, $src_ports, $dst_ports, $event_start, $event_end, $transferred);

        }
    /* insert new metric incident */
    elseif (GET('ref') == 'Metric') {
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
        if($transferred_user!="")   $transferred = $transferred_user;
        if($transferred_entity!="") $transferred = $transferred_entity;
        if($transferred=="") $transferred = Session::get_session_user();
        
        $incident_id = Incident::insert_metric($conn, $title, $type, $submitter, $priority, $target, $metric_type, $metric_value, $event_start, $event_end, $transferred);
    } elseif (GET('ref') == 'Anomaly') {
        if (GET('anom_type') == 'mac') {
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
            
        if($transferred_user!="")   $transferred = $transferred_user;
        if($transferred_entity!="") $transferred = $transferred_entity;
        if($transferred=="") $transferred = Session::get_session_user();
            
            $incident_id = Incident::insert_anomaly($conn, $title, $type, $submitter, $priority, 'mac', $anom_ip, $anom_data_orig, $anom_data_new, $transferred);
        } elseif (GET('anom_type') == 'service') {
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
            
            if($transferred_user!="")   $transferred = $transferred_user;
            if($transferred_entity!="") $transferred = $transferred_entity;
            if($transferred=="") $transferred = Session::get_session_user();
            
            $incident_id = Incident::insert_anomaly($conn, $title, $type, $submitter, $priority, 'service', $anom_ip, $anom_data_orig, $anom_data_new, $transferred);
        } elseif (GET('anom_type') == 'os') {
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
            
            if($transferred_user!="")   $transferred = $transferred_user;
            if($transferred_entity!="") $transferred = $transferred_entity;
            if($transferred=="") $transferred = Session::get_session_user();
            
            $incident_id = Incident::insert_anomaly($conn, $title, $type, $submitter, $priority, 'os', $anom_ip, $anom_data_orig, $anom_data_new, $transferred);
        } /*elseif os*/
    } /*elseif anomaly*/
    /* insert new vulnerability incident */
    elseif (GET('ref') == 'Vulnerability' || POST('ref') == 'Vulnerability') {
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
        
        if($transferred_user!="")   $transferred = $transferred_user;
        if($transferred_entity!="") $transferred = $transferred_entity;
        if($transferred=="") $transferred = Session::get_session_user();
        
        $incident_id = Incident::insert_vulnerability($conn, $title, $type, $submitter, $priority, $ip, $port, $nessus_id, $risk, $description, $transferred);
    }
    if (ossim_error()) {
        die_error();
    }
    if(intval($from_vuln)==1)
        header("Location: index.php?hmenu=Tickets&smenu=Tickets"); 
    else
        header("Location: incident.php?id=$incident_id");
    exit;
}
?>
