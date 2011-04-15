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
require_once 'classes/Session.inc';
require_once 'classes/Security.inc';
Session::logcheck("MenuIncidents", "IncidentsIncidents");
require_once 'ossim_db.inc';
require_once 'classes/Incident.inc';
require_once 'classes/Incident_type.inc';
require_once 'classes/Incident_alarm.inc';
require_once 'classes/Incident_event.inc';
require_once 'classes/Incident_metric.inc';
require_once 'classes/Incident_anomaly.inc';
require_once 'classes/Incident_vulnerability.inc';
require_once 'classes/Form_builder.inc';
require_once 'ossim_conf.inc';
require_once 'incident_common.php';



$db   = new ossim_db();
$conn = $db->connect();
$edit = ( GET('action') == 'edit' ) ? true : false;
$ref  =  GET('ref');
ossim_valid($ref , OSS_LETTER, 'illegal:' . _("Reference"));

$conf    = $GLOBALS["CONF"];
$map_key = $conf->get_conf("google_maps_key", FALSE);


if (ossim_error()) {
	die(ossim_error());
}

if ( $edit ) 
{
    if (!ossim_valid(GET('incident_id') , OSS_DIGIT, 'illegal:' . _("Incidend id")) ) 
	{
       die(ossim_error());
    }
    
	$incident_id = GET('incident_id');
    
	$list        = Incident::get_list($conn, "WHERE incident.id=$incident_id");
    
	if (count($list) != 1) 
	{
		ossim_set_error(_("You don't have permission to see this page"));
		die(ossim_error());
	}
		
    		
	$incident    = $list[0];
    $title       = $incident->get_title();
    $submitter   = $incident->get_submitter();
    $priority    = $incident->get_priority();
    $event_start = $incident->get_event_start();
    $event_end   = $incident->get_event_end();
    $type        = $incident->get_type();
	$in_charge   = $incident->get_in_charge();
					
	
	switch ($ref) {
        case 'Alarm':
			list($alarm) 	= Incident_alarm::get_list($conn, "WHERE incident_alarm.incident_id=$incident_id");
			$src_ips 		= $alarm->get_src_ips();
            $dst_ips 		= $alarm->get_dst_ips();
            $src_ports 		= $alarm->get_src_ports();
            $dst_ports 		= $alarm->get_dst_ports();
            $backlog_id 	= $alarm->get_backlog_id();
            $event_id 		= $alarm->get_event_id();
            $alarm_group_id = $alarm->get_alarm_group_id();
            break;

        case 'Event':
            list($event) 	= Incident_event::get_list($conn, "WHERE incident_event.incident_id=$incident_id");
            $src_ips 		= $event->get_src_ips();
            $dst_ips 		= $event->get_dst_ips();
            $src_ports 		= $event->get_src_ports();
            $dst_ports 		= $event->get_dst_ports();
            break;

        case 'Metric':
            list($metric) 	= Incident_metric::get_list($conn, "WHERE incident_metric.incident_id=$incident_id");
            $target 		= $metric->get_target();
            $metric_type 	= $metric->get_metric_type();
            $metric_value 	= $metric->get_metric_value();
            break;

        case 'Anomaly':
            list($anomaly) 	= Incident_anomaly::get_list($conn, "WHERE incident_anomaly.incident_id=$incident_id");
            $anom_type 		= $anomaly->get_anom_type();
            $anom_ip 		= $anomaly->get_ip();
            $anom_data_orig = $anomaly->get_data_orig();
            $anom_data_new 	= $anomaly->get_data_new();
            
			if ($anom_type == "mac") 
			{
                list($a_sen, $a_date, $a_mac_o, $a_vend_o) = explode(",", $anom_data_orig);
                list($a_sen, $a_date, $a_mac, $a_vend) = explode(",", $anom_data_new);
            } 
			elseif ($anom_type == "service") {
                list($a_sen, $a_date, $a_port, $a_prot_o, $a_ver_o) = explode(",", $anom_data_orig);
                list($a_sen, $a_date, $a_port, $a_prot, $a_ver) = explode(",", $anom_data_new);
            } 
			elseif ($anom_type == "os") {
                list($a_sen, $a_date, $a_os_o) = explode(",", $anom_data_orig);
                list($a_sen, $a_date, $a_os) = explode(",", $anom_data_new);
            }
            break;

        case 'Vulnerability':
            list($vulnerability) 	= Incident_vulnerability::get_list($conn, "WHERE incident_vulns.incident_id=$incident_id");
            $ip 					= $vulnerability->get_ip();
            $port 					= $vulnerability->get_port();
            $nessus_id 				= $vulnerability->get_nessus_id();
            $risk 					= $vulnerability->get_risk();
            $description 			= $vulnerability->get_description();
        break;
		
		case 'Custom':
			$custom_values          = Incident_custom::get_list($conn, "WHERE incident_custom.incident_id=$incident_id");
		
		break;
		
    }
} 
else 
{
    $title        = GET('title');
    $submitter    = GET('submitter');
    $priority     = GET('priority');
    $type 		  = GET('type');
    $src_ips      = GET('src_ips');
    $dst_ips      = GET('dst_ips');
    $src_ports    = GET('src_ports');
    $dst_ports    = GET('dst_ports');
    $backlog_id   = GET('backlog_id');
    $event_id 	  = GET('event_id');
    $alarm_gid 	  = GET('alarm_gid');
    $target 	  = GET('target');
    $event_start  = GET('event_start');
    $event_end    = GET('event_end');
    $metric_type  = GET('metric_type');
    $metric_value = GET('metric_value');
    $anom_type    = GET('anom_type');
    $anom_ip      = GET('anom_ip');
    $a_sen        = GET('a_sen');
    $a_date       = GET('a_date');
    $a_mac_o      = GET('a_mac_o');
    $a_mac        = GET('a_mac');
    $a_vend_o     = GET('a_vend_o');
    $a_vend       = GET('a_vend');
    $a_ver_o      = GET('a_ver_o');
    $a_ver        = GET('a_ver');
    $a_port       = GET('a_port');
    $a_prot_o     = GET('a_prot_o');
    $a_prot       = GET('a_prot');
    $a_os_o       = GET('a_os_o');
    $a_os         = GET('a_os');
    $ip           = GET('ip');
    $port         = GET('port');
    $nessus_id    = GET('nessus_id');
    $risk         = GET('risk');
    $description  = GET('description');
    
	// Check the validations below, narrow them down a bit
    ossim_valid($title, OSS_PUNC_EXT, OSS_ALPHA, OSS_NULLABLE,             'illegal:' . _("Title"));
    ossim_valid($submitter, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE,             'illegal:' . _("Submitter"));
    ossim_valid($priority, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE,              'illegal:' . _("Priority"));
    ossim_valid($type, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE,                  'illegal:' . _("Type"));
    ossim_valid($src_ips, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE,               'illegal:' . _("Src_ips"));
    ossim_valid($dst_ips, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE,               'illegal:' . _("Dst_ips"));
    ossim_valid($src_ports, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE,             'illegal:' . _("Src_ports"));
    ossim_valid($dst_ports, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE,             'illegal:' . _("Dst_ports"));
    ossim_valid($backlog_id, OSS_DIGIT, OSS_NULLABLE,                      'illegal:' . _("Backlog_id"));
    ossim_valid($event_id, OSS_DIGIT, OSS_NULLABLE,                        'illegal:' . _("Event_id"));
    ossim_valid($alarm_gid, OSS_DIGIT, OSS_NULLABLE,                       'illegal:' . _("Alarm_gid"));
    ossim_valid($target, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE,                'illegal:' . _("Target"));
    ossim_valid($event_start, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE,           'illegal:' . _("Event_start"));
    ossim_valid($event_end, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE,             'illegal:' . _("Event_end"));
    ossim_valid($metric_type, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE,           'illegal:' . _("Metric_type"));
    ossim_valid($metric_value, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE,          'illegal:' . _("Metric_value"));
    ossim_valid($anom_type, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE,             'illegal:' . _("Anom_type"));
    ossim_valid($anom_ip, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE,               'illegal:' . _("Anom_ip"));
    ossim_valid($a_sen, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, "\[\]\(\)",     'illegal:' . _("Anomaly sensor"));
    ossim_valid($a_date, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE,                'illegal:' . _("Anomaly date"));
    ossim_valid($a_mac_o, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE,               'illegal:' . _("Oiginal mac"));
    ossim_valid($a_vend_o, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE,              'illegal:' . _("Original vendor"));
    ossim_valid($a_ver_o, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, "\[\]\(\)",   'illegal:' . _("Original version"));
    ossim_valid($a_ver, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, "\[\]\(\)",     'illegal:' . _("Anomaly version"));
    ossim_valid($a_port, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE,                'illegal:' . _("Anomaly port"));
    ossim_valid($a_prot_o, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE,              'illegal:' . _("Original proto"));
    ossim_valid($a_prot, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE,                'illegal:' . _("Anomaly proto"));
    ossim_valid($a_os_o, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE,                'illegal:' . _("Original os"));
    ossim_valid($a_os, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE,                  'illegal:' . _("Anomaly os"));
    ossim_valid($ip, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE,                    'illegal:' . _("Ip"));
    ossim_valid($port, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE,                  'illegal:' . _("Port"));
    ossim_valid($nessus_id, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE,             'illegal:' . _("Nessus id"));
    ossim_valid($risk, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE,                  'illegal:' . _("Risk"));
    ossim_valid($description, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE,           'illegal:' . _("Description"));
    
	if (ossim_error()) {
        die(ossim_error());
    }
    
	/* get default submitter info */
    if (!$submitter) 
	{
        $session_info = Session::get_session_info();
        $submitter    = $session_info['name'];
        if ($session_info['company'])    $submitter.= '/' . $session_info['company'];
        if ($session_info['department']) $submitter.= '/' . $session_info['department'];
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
	<meta http-equiv="Pragma" content="no-cache"/>

	<link rel="stylesheet" type="text/css" href="../style/datepicker.css"/>
	<link rel="stylesheet" type="text/css" href="../style/jquery-ui-1.7.custom.css"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<link rel="stylesheet" type="text/css" href="../style/tree.css" />
	<link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css" />

	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
	<script type="text/javascript" src="../js/datepicker.js"></script>
	<script type="text/javascript" src="../js/jquery.dynatree.js"></script>
	<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>

	<script type="text/javascript" src="../js/jquery.autocomplete_geomod.js"></script>
	<script type="text/javascript" src="../js/geo_autocomplete.js"></script>

	<script type="text/javascript">
		function switch_user(select) {
			if(select=='entity' && $('#transferred_entity').val()!='')
				$('#user').val('');
			else if (select=='user' && $('#transferred_user').val()!='')
				$('#entity').val('');
		}
	
		function send_form() {
			
			var required_fields = new Array();
			var fields_error = new Array();
			var msg = '';
			var msg_error = '';
			var error = false;
			var cont = 0;
			var element;

			$(".req_field").each(function(index) {
				var tag_name = $(this).get(0).tagName;
				
				if (tag_name == 'INPUT')
				{
					var type = $(this).attr('type');
					element = tag_name+"#"+type;
				}
				else
					element = tag_name;
				
				required_fields[$(this).attr("name")] = element;
			});
		
		
		
			for(var i in required_fields) {
			
				element = required_fields[i].split("#");
				
			
				if (element.length == 2 && (element[1] == "radio" || element[1] == "checkbox"))
				{
					var checked = $("input[name="+i+"]:checked").length;
					
					if (checked == 0)
						error = true;
				}
				else
				{
					var value =  $(element[0]+"[name="+i+"]").val();
					
					if (value == '')
						error = true;
				}
			
				if (error == true)
				{
					fields_error[cont] = $(element[0]+"[name="+i+"]").attr("id");
					cont++;
					msg += $(element[0]+"[name="+i+"]").parents("tr:first").children("th").text() + "<br/>";
					error = false;
				}
							
			}
			
				
			if ( msg != '' )
			{
				msg_error = "<div style='padding: 0px 10px'>The following fields are mandatory:<div><div style='padding: 5px 0px 5px 20px;'>"+msg+"</div>";
				$("#info_error").html(msg_error);
				$("#info_error").css('display', 'block');
				window.scrollTo(0,0);
				return false;
			}
			else
			{
				$("#info_error").css('display', 'none');
				$("#info_error").html("");
				$("#crt").submit();
				return true;
			}
		}

		function delete_file(id)
		{
			$('#delfile_'+id).remove();
			$('#'+id+"_del").val("1");
		}

	</script>
	
	<style type='text/css'>
		
		textarea, .field_fix { width: 100%;}
		select { width: 200px;}
		option {height: 15px;}
		th {padding: 5px 0px; max-width: 300px; white-space: normal;}
		
		input[type='text'],input[type='file'] { width: 100%; height: 18px;}
				
		.ct_slider {float:left; width:430px; heigth: 25px; margin-top:6px;}
		a.ui-slider-handle { top: -7px !important;}
		
		.ac_results li img {float: left;margin-right: 5px;}
		
		.wfl1 {float:left; width: auto;} 
		.wfr1 {float:left; padding: 5px 0px 0px 15px; width: auto;} 

		.wfl2 {float:left; width: 460px; } 
		.wfr2 {float:left; padding: 5px 0px 0px 10px; width: 30px;} 
		
		.wf3 {float:left; width: 480px; }
		
		.format_or { padding:0px 2px 0px 8px;text-align:center; border-bottom: none; }
		.format_user { width: 48px; text-align:left; padding: 0px 3px; border-bottom: none; }
		.format_entity { width: 70px; text-align:left;  padding: 0px 3px; border-bottom: none;}
		
		#user, #entity {text-align: center !important;}
		#user option {text-align: left;}
		#entity option {text-align: left;}
		
	</style>
</head>

<body>
<?php if (GET('nohmenu') == "") include ("../hmenu.php"); ?>

<h1><?php echo " "._($ref)." " . _("Ticket") ?></h1>

<form id='crt' method="POST" action="manageincident.php" enctype="multipart/form-data">
<input type="hidden" name="action" value="<?php echo ($edit) ? 'editincident' : 'newincident' ?>" />
<input type="hidden" name="edit" value="<?php echo $edit ?>" />
<input type="hidden" name="ref" value="<?php echo $ref ?>" />
<input type="hidden" name="incident_id" value="<?php echo $incident_id ?>" />
<input type="hidden" name="submitter" value="<?php echo $submitter ?>" />

<div id='info_error' class='ct_error'></div>

<table align="center">
	<tr>
		<th><?php echo _("Title") ?></th>
		<td class="left">
			<div class='wfl2'>
				<input type="text" name="title" value="<?php echo $title ?>"/>
			</div>
			<div class='wfr2'><span>(*)</span></div>
		</td>
	</tr>
    
<?php

$users      = Session::get_users_to_assign($conn);
$entities   = Session::get_entities_to_assign($conn);
?>

	<tr>
		<th><?php echo _("Assign To")?></th>
		<td style="text-align: left">
			<table width="400" cellspacing="0" cellpadding="0" class="transparent">
				<tr>
					<td class="format_user"><?php echo _("User:");?></td>
					<td class="nobborder">
											
						<select name="transferred_user" id="user" onchange="switch_user('user');return false;">
							
							<?php
														
							$num_users = 0;
							foreach( $users as $k => $v )
							{
								$login = $v->get_login();
								
								$selected = ( $login == $in_charge ) ? "selected='selected'": "";
								$options .= "<option value='".$login."' $selected>".format_user($v, false)."</option>\n";
								$num_users++;
							}
							
							if ($num_users == 0)
								echo "<option value='' style='text-align:center !important;'>- "._("No users found")." -</option>";
							else
							{
								echo "<option value='' style='text-align:center !important;'>- "._("Select one user")." -</option>\n";
								echo $options;
							}
													
							?>
						</select>
					</td>
					<?php if ( !empty($entities) ) { ?>
					<td class="format_or"><?php echo _("OR");?></td>
					<td class="format_entity"><?php echo _("Entity:");?></td>
					<td class="nobborder">
						<select name="transferred_entity" id="entity" onchange="switch_user('entity');return false;">
							<option value="" style='text-align:center !important;'>- <?php echo _("Select one entity") ?> -</option>
							<?php
							foreach ( $entities as $k => $v ) 
							{
								$selected = ( $k == $in_charge ) ? "selected='selected'": "";
								echo "<option value='$k' $selected>$v</option>";
							}
							?>
						</select>
					</td>
					<?php } ?>
				</tr>
			</table>
		</td>
	</tr> 

 
	<tr>
		<th><?php echo _("Priority") ?></th>
		<td class="left">
			<select name="priority">
				<?php
				for ($i = 1; $i <= 10; $i++) 
				{
					$selected = ( $priority == $i ) ? "selected='selected'" : "";
					echo "<option value='$i' $selected>$i</option>";
				}	
				?>
			</select> 
		</td>
	</tr>
	
	<tr>
		<th><?php echo _("Type") ?></th>
		<?php 
		if ( $ref == "Custom" )
			echo "<td class='left'><span style='font-weight:bold;'>$type</span><input type='hidden' name='type' value='$type'/></td>";
		else
			Incident::print_td_incident_type($conn, $type); 
		?>
	</tr>

	<?php if (($ref == "Alarm") or ($ref == "Event")) { ?>
	<tr>
		<th class='thr'><?php echo _("Source Ips") ?></th>
		<td class="left">
			<div class='wf3'>
				<input type="hidden" name="backlog_id" value="<?php echo $backlog_id?>" />
				<input type="hidden" name="event_id" value="<?php echo $event_id?>" />
				<input type="hidden" name="alarm_group_id" value="<?php echo $alarm_gid?>" />
				<input type="text" name="src_ips" value="<?php echo $src_ips ?>" />
			</div>
		</td>
	</tr>
	
	<tr>
		<th class='thr'><?php echo _("Dest Ips") ?></th>
		<td class="left">
			<div class='wf3'><input type="text" name="dst_ips" value="<?php echo $dst_ips ?>" /></div>
		</td>
	</tr>
	
	<tr>
		<th class='thr'><?php echo _("Source Ports") ?></th>
		<td class="left">
			<div class='wf3'><input type="text" name="src_ports" value="<?php echo $src_ports ?>" /></div>
		</td>
	</tr>
	
	<tr>
		<th class='thr'><?php echo _("Dest Ports") ?></th>
		<td class="left">
			<div class='wf3'><input type="text" name="dst_ports" value="<?php echo $dst_ports ?>" /></div></td>
	</tr>
	
	<tr>
		<th class='thr'><?php echo _("Start of related events") ?></th>
		<td class="left">
			<div class='wf3'><input type="text" name="event_start" value="<?php echo $event_start ?>" /></div></td>
	</tr>
	
	<tr>
		<th class='thr'><?php echo _("End of related events") ?></th>
		<td class="left">
			<div class='wf3'><input type="text" name="event_end" value="<?php echo $event_end ?>" /></div></td>
	</tr>

<?php
} elseif ($ref == "Metric") {
?>
	<tr>
		<th class='thr'><?php echo _("Target (net, ip, etc)") ?></th>
		<td class="left">
			<div class='wf3'><input type="text" name="target" value="<?php echo $target ?>" /></div>
		</td>
	</tr>
	
	<tr>
		<th class='thr'><?php echo _("Metric type") ?></th>
		<td class="left">
			<select name="metric_type">
				<?php
				$metric_types = array("Compromise" => "Compromise", "Attack" => "Attack", "Level" => "Level");
				foreach ($metric_types as $k => $v) 
				{
					$selected = ( $metric_type == $k ) ? "selected='selected'" : "";
					echo "<option value='$k' $selected>$v</option>";
				}	
				?>	
			</select>
		</td>
	</tr>
	
	<tr>
		<th class='thr'><?php echo _("Metric value") ?></th>
		<td class="left">
			<div class='wf3'><input type="text" name="metric_value" value="<?php echo $metric_value ?>"/></div>
		</td>
	</tr>
	
	<tr>
		<th class='thr'><?php echo _("Start of related events") ?></th>
		<td class="left">
			<div class='wf3'><input type="text" name="event_start" value="<?php echo $event_start ?>"/></div>
		</td>
	</tr>
	
	<tr>
		<th class='thr'><?php echo _("End of related events") ?></th>
		<td class="left">
			<div class='wf3'><input type="text" name="event_end" value="<?php echo $event_end ?>"/></div>
		</td>
	</tr>
<?php
} elseif ($ref == "Anomaly") {
?>
	<tr>
		<th class='thr'><?php echo _("Anomaly type") ?></th>
		<td class="left">
			<div class='wf3'><input type="text" name="anom_type" size="30" value="<?php echo $anom_type ?>"/></div>
		</td>
	</tr>
	
	<tr>
		<th class='thr'><?php echo _("Host") ?></th>
		<td class="left">
			<div class='wf3'><input type="text" name="anom_ip" size="30" value="<?php echo $anom_ip ?>"/></div>
		</td>
	</tr>
	
	<tr>
		<th class='thr'><?php echo _("Sensor") ?></th>
		<td class="left">
			<div class='wf3'><input type="text" name="a_sen" size="30" value="<?php echo $a_sen ?>" /></div>
		</td>
	</tr>
<?php
    if ($anom_type == "os") {
?>
	<tr>
		<th class='thr'><?php echo _("Old OS") ?></th>
		<td class="left">
			<div class='wf3'><input type="text" name="a_os_o" size="30" value="<?php echo $a_os_o ?>" /></div>
		</td>
	</tr>
	
	<tr>
		<th class='thr'><?php echo _("New OS") ?></th>
		<td class="left">
			<div class='wf3'><input type="text" name="a_os"  size="30" value="<?php echo $a_os ?>" /></div>
		</td>
	</tr>
	
	<tr>
		<th  class='thr'><?php echo _("When") ?></th>
		<td class="left">
			<div class='wf3'><input type="text" name="a_date" size="30" value="<?php echo $a_date ?>" /></div>
		</td>
	</tr>

     
<?php
    } elseif ($anom_type == "mac") {
?>
	<tr>
		<th class='thr'><?php echo _("Old mac") ?></th>
		<td class="left">
			<div class='wf3'><input type="text" name="a_mac_o" size="30" value="<?php echo $a_mac_o ?>" /></div>
		</td>
	</tr>
    
	<tr>
		<th class='thr'><?php echo _("New mac") ?></th>
		<td class="left">
			<div class='wf3'><input type="text" name="a_mac" size="30" value="<?php echo $a_mac ?>" /></div>
		</td>
	</tr>
    
	<tr>
		<th class='thr'><?php echo _("Old vendor") ?></th>
		<td class="left">
			<div class='wf3'><input type="text" name="a_vend_o" size="30" value="<?php echo $a_vend_o ?>" /></div>
		</td>
	</tr>
    
	<tr>
		<th class='thr'><?php echo _("New vendor") ?></th>
		<td class="left">
			<div class='wf3'><input type="text" name="a_vend" size="30" value="<?php echo $a_vend ?>" /></div>
		</td>
	</tr>
	
	<tr>
		<th class='thr'><?php echo _("When") ?></th>
		<td class="left">
			<div class='wf3'><input type="text" name="a_date" size="30" value="<?php echo $a_date ?>" /></div>
		</td>
	</tr>

<?php
    } elseif ($anom_type == "service") {
?>

	<tr>
		<th class='thr'><?php echo _("Port") ?></th>
		<td class="left">
			<div class='wf3'><input type="text" name="a_port" value="<?php echo $a_port ?>" /></div>
		</td>
	</tr>
    
	<tr>
		<th class='thr'><?php echo _("Old Protocol") ?></th>
		<td class="left">
			<div class='wf3'><input type="text" name="a_prot_o" size="30" value="<?php echo $a_prot_o ?>" /></div>
    	</td>
	</tr>
    
	<tr>
		<th class='thr'><?php echo _("Old Version") ?></th>
		<td class="left">
			<div class='wf3'><input type="text" name="a_ver_o" size="30" value="<?php echo $a_ver_o ?>" /></div>
		</td>
	</tr>
    
	<tr>
		<th class='thr'><?php echo _("New Protocol") ?></th>
		<td class="left">
			<div class='wf3'><input type="text" name="a_prot" size="30" value="<?php echo $a_prot ?>" /></div>
		</td>
	</tr>
    
	<tr>
		<th class='thr'><?php echo _("New Version") ?></th>
		<td class="left">
			<div class='wf3'><input type="text" name="a_ver" size="30" value="<?php echo $a_ver ?>" /></div>
		</td>
	</tr>
    
	<tr>
		<th class='thr'><?php echo _("When") ?></th>
		<td class="left">
			<div class='wf3'><input type="text" name="a_date" size="30" value="<?php echo $a_date ?>" /></div>
		</td>
	</tr>

<?php
    }
?>


<?php
} elseif ($ref == "Vulnerability") {
?>

	<tr>
		<th class='thr'><?php echo _("IP") ?></th>
		<td class="left">
			<div class='wf3'><input type="text" name="ip" value="<?php echo $ip ?>" /></div>
		</td>
	</tr>
	
	<tr>
		<th class='thr'><?php echo _("Port") ?></th>
		<td class="left">
			<div class='wf3'><input type="text" name="port" size="30" value="<?php echo $port ?>" /></div>
		</td>
	</tr>
    
	<tr>
		<th class='thr'><?php echo _("Nessus/OpenVas ID") ?></th>
		<td class="left">
			<div class='wf3'><input type="text" name="nessus_id" size="30" value="<?php echo $nessus_id ?>" /></div>
		</td>
	</tr>
    
	<tr>
		<th class='thr'><?php echo _("Risk") ?></th>
		<td class="left">
			<div class='wf3'><input type="text" name="risk" size="30" value="<?php echo $risk ?>" /></div>
		</td>
	</tr>
    
	<tr>
		<th class='thr'><?php echo _("Description") ?></th>
		<td class='left' style="border-width: 0px;">
			<div class='wf3'><textarea name="description" rows="10" cols="80" wrap="hard"><?php echo $description ?></textarea></div>
		</td>
	</tr>

<?php
} elseif ($ref == "Custom") {
	
	$fields       = Incident_custom::get_custom_types($conn,$type);
	$form_builder = new Form_builder();
	$params       = array();
	$cont         = 1;
			
	if ( empty($fields) )
	{
		echo "<tr><td class='nobborder' colspan='2'>";
			$error = new OssimNotice();
			$info  = array(_("You don't have added any custom types or your custom types have been deleted"));
			$error->display(_("DEFAULT"), $info, false);
		echo "</td></tr>";
	}
	else
	{
		foreach ($fields as $field)
		{
			echo "<tr id='item_".$cont."'><th id='name_".$cont."' class='thr'><span>".utf8_decode($field['name'])."</span></th>";
			
			echo "<td style='border-width: 0px;text-align:left'>";
			$params = get_params_field($field, $map_key);
			$form_builder->set_attributes($params);
			
			if ( is_object($custom_values[$field['name']]) )		
			{
				$default_value = $custom_values[$field['name']]->get_content();
				$type          = $custom_values[$field['name']]->get_type();
				$id            = $custom_values[$field['name']]->get_id();
			}
			else
				$default_value = null;
					
			
			$wf1_types = array ('Select box', 'Date','Date Range', 'Checkbox', 'Radio button');
			
			if ( in_array($field['type'], $wf1_types) )
				$class_wf = array('wfl1', 'wfr1');
			else
				$class_wf =  array('wfl2', 'wfr2');
			
			echo "<div class='".$class_wf[0]."'>";
				
				if ( !empty($default_value) && $type == 'File' )
				{	
					echo "<div style='padding-bottom: 3px; text-align: left' id='delfile_".$params['id']."'>";
						echo Incident::format_custom_field($id, $incident_id, $default_value, $type);
						echo "<span style='margin-left: 3px'>
								<a style='cursor:pointer' onclick=\"delete_file('".$params['id']."')\"><img src='../pixmaps/delete.gif' align='absmiddle' title='"._("Delete File")."'/></a>
							  </span>";		
					echo "</div>";
					echo "<input type='hidden' name='".$params['name']."_del' id='".$params['id']."_del' value='0'/>";
				}
				
				echo $form_builder->draw_element($field['type'], $default_value);
			
			echo "</div>";
			
			$req_f_inherent = array('Check True/False', 'Check Yes/No', 'Asset', 'Slider');
			
			$mandatory = ( $field['required'] == 1 && !in_array($field['type'], $req_f_inherent) ) ? "<span>(*)</span>" : "";
				
			echo "<div class='".$class_wf[1]."'>".$mandatory."</div>";
			echo "</td>";
						
			echo"</tr>\n";
			$cont++;
		}
	}
}
?>

	<tr>
		<td colspan="2" class="noborder" style='height:30px;'><input type="button" style='width:40px;' value="<?php echo _("OK")?>" class="button" onclick="send_form();"/></td>
	</tr>
</table>

<table align="center" class='noborder' width="600" style='border: none; background: #FFFFFF;'>
	<tr>
		<td class="noborder center" style='height:50px;' valign='middle'>
			<span><?php echo gettext("Fields marked with (*) are mandatory");?></span>
		</td>
	</tr>
</table>

</form>

<script type='text/javascript'>
	<?php echo $form_builder->get_def_funcs();?>
				
	$(document).ready(function() {
		<?php echo $form_builder->get_scripts();?>
	});
</script>
</body>
</html>

