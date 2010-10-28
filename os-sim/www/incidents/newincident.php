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
require_once ('ossim_conf.inc');

$conf = $GLOBALS["CONF"];
$map_key = $conf->get_conf("google_maps_key", FALSE);


function get_params_field($field){
	
	GLOBAL $map_key;
	$unique_id = md5( uniqid() );
	$fld = "custom_".$unique_id;
	$name = "custom_".base64_encode($field['name']);
	$required = ( $field['required'] == 1 ) ? "req_field" : "";
	
	switch ($field['type']){
		case "Asset":
			$params = array("name" => $name, "id"=>$fld, "class"=>trim($required." ct_assets_sel"));
		break;
		
		case "Check Yes/No":
			$params = array("name" => $name, "id"=>$fld, "class"=>$required);
		break;
		
		case "Check True/False":
			$params = array("name" => $name, "id"=>$fld, "class"=>$required);
		break;
		
		case "Checkbox":
		
			if ($field["options"] != '')
				$options = explode("\n", $field["options"]);
			else
				$options = '';
				
			$num_opt = count($options);
			
			$num_chk = ($options[$num_opt-1] == '' ) ? $num_opt-1 : $num_opt;
			
			for ($i=0; $i<$num_chk; $i++)
				$ids[] = $fld."_".($i+1);
											
			$params = array("name" => $name, "id"=>$ids, "class"=>$required, "values" => $options);
			
		break;
		
		case "Date":
			$params = array("name" => $name, "id"=>$fld, "class"=>$required);
		break;
		
		case "Date Range":
			$params = array("name" => $name, "id"=>$fld, "class"=>$required);
		break;
		
		case "Map":
			$params = array("name" => $name, "id"=>$fld, "class"=>$required, "values"=>array($map_key));
		break;
			
		case "Radio button":
			
			if ($field["options"] != '')
				$options = explode("\n", $field["options"]);
			else
				$options = '';
				
			$num_opt = count($options);
			
			$num_radio = ($options[$num_opt-1] == '' ) ? $num_opt-1 : $num_opt;
			
			for ($i=0; $i<$num_radio; $i++)
				$ids[] = $fld."_".$i;
			
			$params = array("name" => $name, "id"=>$ids, "class"=>$required, "values"=> $options);
			
		break;
		
		case "Select box":
			
			if ($field["options"] != '')
				$options = explode("\n", $field["options"]);
			else
				$options = '';
				
			$params = array("name" => $name, "id"=>$fld, "class"=>$required, "values"=> $options);
		break;
		
		case "Slider":
			
			if ($field["options"] != '')
				$options = explode("\n", $field["options"]);
			else
				$options = '';
				
			$params = array("name" => $name, "id"=>$fld, "class"=>$required, "values"=> $options);
		break;
					
		case "Textarea":
			$params = array("name" => $name, "id"=>$fld, "class"=>$required, "rows"=>"3", "cols"=>"80", "wrap"=>"hard");
		break;
		
		case "Textbox":
			$params = array("name" => $name, "id"=>$fld, "class"=>$required);
		break;
			
	}
	
	return $params;

}

$db = new ossim_db();
$conn = $db->connect();
$edit = GET('action') && GET('action') == 'edit' ? true : false;
$ref = !ossim_valid(GET('ref') , OSS_LETTER) ? die("Ref required") : GET('ref');
if ($edit) {
    if (!ossim_valid(GET('incident_id') , OSS_DIGIT)) {
        die("Wrong ID");
    }
    $incident_id = GET('incident_id');
    $list = Incident::get_list($conn, "WHERE incident.id=$incident_id");
    if (count($list) != 1) die("Wrong ID");
    $incident = $list[0];
    $title = $incident->get_title();
    $submitter = $incident->get_submitter();
    $priority = $incident->get_priority();
    $event_start = $incident->get_event_start();
    $event_end = $incident->get_event_end();
    $type = $incident->get_type();
	switch ($ref) {
        case 'Alarm':
			list($alarm) = Incident_alarm::get_list($conn, "WHERE incident_alarm.incident_id=$incident_id");
			$src_ips = $alarm->get_src_ips();
            $dst_ips = $alarm->get_dst_ips();
            $src_ports = $alarm->get_src_ports();
            $dst_ports = $alarm->get_dst_ports();
            $backlog_id = $alarm->get_backlog_id();
            $event_id = $alarm->get_event_id();
            $alarm_group_id = $alarm->get_alarm_group_id();
            break;

        case 'Event':
            list($event) = Incident_event::get_list($conn, "WHERE incident_event.incident_id=$incident_id");
            $src_ips = $event->get_src_ips();
            $dst_ips = $event->get_dst_ips();
            $src_ports = $event->get_src_ports();
            $dst_ports = $event->get_dst_ports();
            break;

        case 'Metric':
            list($metric) = Incident_metric::get_list($conn, "WHERE incident_metric.incident_id=$incident_id");
            $target = $metric->get_target();
            $metric_type = $metric->get_metric_type();
            $metric_value = $metric->get_metric_value();
            break;

        case 'Anomaly':
            list($anomaly) = Incident_anomaly::get_list($conn, "WHERE incident_anomaly.incident_id=$incident_id");
            $anom_type = $anomaly->get_anom_type();
            $anom_ip = $anomaly->get_ip();
            $anom_data_orig = $anomaly->get_data_orig();
            $anom_data_new = $anomaly->get_data_new();
            if ($anom_type == "mac") {
                list($a_sen, $a_date, $a_mac_o, $a_vend_o) = explode(",", $anom_data_orig);
                list($a_sen, $a_date, $a_mac, $a_vend) = explode(",", $anom_data_new);
            } elseif ($anom_type == "service") {
                list($a_sen, $a_date, $a_port, $a_prot_o, $a_ver_o) = explode(",", $anom_data_orig);
                list($a_sen, $a_date, $a_port, $a_prot, $a_ver) = explode(",", $anom_data_new);
            } elseif ($anom_type == "os") {
                list($a_sen, $a_date, $a_os_o) = explode(",", $anom_data_orig);
                list($a_sen, $a_date, $a_os) = explode(",", $anom_data_new);
            }
            break;

        case 'Vulnerability':
            list($vulnerability) = Incident_vulnerability::get_list($conn, "WHERE incident_vulns.incident_id=$incident_id");
            $ip = $vulnerability->get_ip();
            $port = $vulnerability->get_port();
            $nessus_id = $vulnerability->get_nessus_id();
            $risk = $vulnerability->get_risk();
            $description = $vulnerability->get_description();
            break;
    }
} else {
    $title = GET('title');
    $submitter = GET('submitter');
    $priority = GET('priority');
    $type = GET('type');
    $src_ips = GET('src_ips');
    $dst_ips = GET('dst_ips');
    $src_ports = GET('src_ports');
    $dst_ports = GET('dst_ports');
    $backlog_id = GET('backlog_id');
    $event_id = GET('event_id');
    $alarm_gid = GET('alarm_gid');
    $target = GET('target');
    $event_start = GET('event_start');
    $event_end = GET('event_end');
    $metric_type = GET('metric_type');
    $metric_value = GET('metric_value');
    $anom_type = GET('anom_type');
    $anom_ip = GET('anom_ip');
    $a_sen = GET('a_sen');
    $a_date = GET('a_date');
    $a_mac_o = GET('a_mac_o');
    $a_mac = GET('a_mac');
    $a_vend_o = GET('a_vend_o');
    $a_vend = GET('a_vend');
    $a_ver_o = GET('a_ver_o');
    $a_ver = GET('a_ver');
    $a_port = GET('a_port');
    $a_prot_o = GET('a_prot_o');
    $a_prot = GET('a_prot');
    $a_os_o = GET('a_os_o');
    $a_os = GET('a_os');
    $ip = GET('ip');
    $port = GET('port');
    $nessus_id = GET('nessus_id');
    $risk = GET('risk');
    $description = GET('description');
    // TODO: Check the validations below, narrow them down a bit
    ossim_valid($title, OSS_PUNC_EXT, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("title"));
    ossim_valid($submitter, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("submitter"));
    ossim_valid($priority, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("priority"));
    ossim_valid($type, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("type"));
    ossim_valid($src_ips, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("src_ips"));
    ossim_valid($dst_ips, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("dst_ips"));
    ossim_valid($src_ports, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("src_ports"));
    ossim_valid($dst_ports, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("dst_ports"));
    ossim_valid($backlog_id, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("backlog_id"));
    ossim_valid($event_id, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("event_id"));
    ossim_valid($alarm_gid, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("alarm_gid"));
    ossim_valid($target, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("target"));
    ossim_valid($event_start, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("event_start"));
    ossim_valid($event_end, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("event_end"));
    ossim_valid($metric_type, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("metric_type"));
    ossim_valid($metric_value, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("metric_value"));
    ossim_valid($anom_type, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("anom_type"));
    ossim_valid($anom_ip, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("anom_ip"));
    ossim_valid($a_sen, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("anomaly sensor"));
    ossim_valid($a_date, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("anomaly date"));
    ossim_valid($a_mac_o, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("original mac"));
    ossim_valid($a_vend_o, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("original vendor"));
    ossim_valid($a_ver_o, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("original version"));
    ossim_valid($a_ver, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("anomaly version"));
    ossim_valid($a_port, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("anomaly port"));
    ossim_valid($a_prot_o, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("original proto"));
    ossim_valid($a_prot, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("anomaly proto"));
    ossim_valid($a_os_o, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("original os"));
    ossim_valid($a_os, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("anomaly os"));
    ossim_valid($ip, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("ip"));
    ossim_valid($port, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("port"));
    ossim_valid($nessus_id, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("nessus id"));
    ossim_valid($risk, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("risk"));
    ossim_valid($description, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("description"));
    if (ossim_error()) {
        die(ossim_error());
    }
    /* get default submitter info */
    if (!$submitter) {
        $session_info = Session::get_session_info();
        $submitter = $session_info['name'];
        if ($session_info['company']) $submitter.= '/' . $session_info['company'];
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
	<META HTTP-EQUIV="Pragma" CONTENT="no-cache">

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
	</script>
	
	<style type='text/css'>
		
		textarea, .field_fix { width: 100%;}
		select { width: 200px;}
		option {height: 15px;}
		th {padding: 5px 0px;}
		
		input[type='text'] { width: 100%; height: 18px;}
				
		.thw {width: 150px;}
		.label_name{width: 150px; overflow: hidden;}
		.ct_slider {float:left; width:430px; heigth: 25px; margin-top:6px;}
		a.ui-slider-handle { top: -7px !important;}
		
		.ac_results li img {float: left;margin-right: 5px;}
		
		.wfl1 {float:left; width: auto;} 
		.wfr1 {float:left; padding: 5px 0px 0px 15px; width: auto;} 

		.wfl2 {float:left; width: 460px; } 
		.wfr2 {float:left; padding: 5px 0px 0px 10px; width: 30px;} 
		
		.wf3 {float:left; width: 480px; }
	</style
</head>

<body>
<?php
include ("../hmenu.php"); ?>
<h1><?php echo " $ref " . _("Ticket") ?></h1>
<form id='crt' method="GET" action="manageincident.php">
<input type="hidden" name="action" value="<?php echo ($edit) ? 'editincident' : 'newincident' ?>" />
<input type="hidden" name="ref" value="<?php echo $ref ?>" />
<input type="hidden" name="incident_id" value="<?php echo $incident_id ?>" />
<input type="hidden" name="submitter" value="<?php echo $submitter ?>" />
<div id='info_error' class='ct_error'></div>

<table align="center" width="680">
	<tr>
		<th class='thw'><?php echo _("Title") ?></th>
		<td class="left">
			<div class='wfl2'>
				<input type="text" name="title" value="<?php echo $title ?>"/>
			</div>
			<div class='wfr2'><span>(*)</span></div>
		</td>
	</tr>
    
<?
$users = Session::get_list($conn);

$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);

if(preg_match("/pro|demo/i",$version)) {
    $users_pro_login = array();
    $users_pro = array();
    $entities_pro = array();
    
    if(Session::am_i_admin()) { // admin in professional version
        list($entities_all,$num_entities) = Acl::get_entities($conn);
        $entities_types_aux = Acl::get_entities_types($conn);
        $entities_types = array();

        foreach ($entities_types_aux as $etype) { 
            $entities_types[$etype['id']] = $etype;
        }
        
        ?>
        <tr>
            <th><?php echo _("Assign To") ?></th>
            <td style="text-align: left">
                <table width="400" cellspacing="0" cellpadding="0" class="transparent">
                    <tr>
                        <td class="nobborder left"><?php echo _("User:");?></td>
                        <td class="nobborder">
							<select name="transferred_user" id="user" onchange="switch_user('user');return false;">
								<option value=""><? if (count($users) < 1) { ?>- <?=_("No users found")?> -<? } ?></option>
								<?php
								foreach($users as $u) if(Session::get_session_user()!=$u->get_login()){ ?>
									<option value="<?php echo $u->get_login() ?>"><?php echo format_user($u, false) ?></option>
								<?php
								} ?>
							</select>
							</div>
                        </td>
                        <td style="padding:0px 5px 0px 5px;text-align:center;" class="nobborder"><?php echo _("OR");?></td>
                        <td class="nobborder"><?php echo _("Entity:");?></td>
                        <td class="nobborder">
                            <select name="transferred_entity" id="entity" onchange="switch_user('entity');return false;">
                            <option value=""><? if (count($entities_all) < 1) { ?>- <?=_("No entities found")?> -<? } ?></option>
                            <?php
                                foreach ( $entities_all as $entity ) {
                                ?>
                                <option value="<?php echo $entity["id"]; ?>"><?php echo $entity["name"]." [".$entities_types[$entity["type"]]["name"]."]";?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                </table>
            </td>
        </tr> 
<?}
    elseif(Acl::am_i_proadmin()) { // pro admin
        //users
        $users_admin = Acl::get_my_users($conn,Session::get_session_user()); 
        foreach ($users_admin as $u){
            if($u["login"]!=Session::get_session_user()){
                $users_pro_login[] = $u["login"];
            }
        }
        //if(!in_array(Session::get_session_user(), $users_pro_login) && $incident_in_charge!=Session::get_session_user())   $users_pro_login[] = Session::get_session_user();
        
        //entities
        list($entities_all,$num_entities) = Acl::get_entities($conn);
        list($entities_admin,$num) = Acl::get_entities_admin($conn,Session::get_session_user());
        $entities_list = array_keys($entities_admin);
        
        $entities_types_aux = Acl::get_entities_types($conn);
        $entities_types = array();

        foreach ($entities_types_aux as $etype) { 
            $entities_types[$etype['id']] = $etype;
        }
        
        //save entities for proadmin
        foreach ( $entities_all as $entity ) if(in_array($entity["id"], $entities_list)) {
            $entities_pro[$entity["id"]] = $entity["name"]." [".$entities_types[$entity["type"]]["name"]."]";
        }
        
        // filter users
        foreach($users as $u) {
            if (!in_array($u->get_login(),$users_pro_login)) continue;
            $users_pro[$u->get_login()] = format_user($u, false);
        }
        ?>
        <tr>
            <th><?php echo _("Assign To") ?></th>
            <td style="text-align: left;">
                <table width="400" cellspacing="0" cellpadding="0" class="transparent">
                    <tr>
                        <td class="nobborder left"><?php echo _("User:");?></td>
                        <td class="nobborder">
							<select name="transferred_user" id="user" onchange="switch_user('user');return false;">
								<option value=""><? if (count($users) < 1) { ?>- <?=_("No users found")?> -<? } ?></option>
								<?php foreach($users_pro as $loginu => $nameu) { ?>
								<option value="<?php echo $loginu; ?>"><?php echo $nameu; ?></option>
								<?php } ?>
							</select>
                        </td>
                        <td style="padding:0px 5px 0px 5px;text-align:center;" class="nobborder"><?php echo _("OR");?></td>
                        <td class="nobborder"><?php echo _("Entity:");?></td>
                        <td class="nobborder">
                            <select name="transferred_entity" id="entity" onchange="switch_user('entity');return false;">
                            <option value=""><? if (count($entities_pro) < 1) { ?>- <?=_("No entities found")?> -<? } ?></option>
                            <?php foreach ( $entities_pro as $entity_id => $entity_name ) { ?>
                                <option value="<?php echo $entity_id; ?>"><?php echo $entity_name;?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                </table>
            </td>
        </tr> 
    <?
    }
    else { // normal user
            $brothers = Acl::get_brothers($conn,Session::get_session_user());
            foreach ($brothers as $brother){
                $users_pro_login[] = $brother["login"];
            }
            //if(!in_array(Session::get_session_user(), $users_pro_login))   $users_pro_login[] = Session::get_session_user();
            // filter users
                foreach($users as $u) {
                    if (!in_array($u->get_login(),$users_pro_login)) continue;
                    $users_pro[$u->get_login()] = format_user($u, false);
                }
            ?>
                <tr>
                    <th><?php echo _("Assign To") ?></th>
                    <td style="text-align: left">
						<select name="transferred_user">
							<option value=""><? if (count($users_pro) < 1) { ?>- <?=_("No users found")?> -<? } ?></option>
							<?php foreach($users_pro as $loginu => $nameu) { ?>
							<option value="<?php echo $loginu ?>"><?php echo $nameu ?></option>
							<?php } ?>
                        </select>
                    </td>
                </tr>
            <?
    }
}
else {
    ?>
    <tr>
        <th><?php echo _("Assign To") ?></th>
        <td style="text-align: left">
			<select name="transferred_user">
				<option value=""><? if (count($users) < 1) { ?>- <?=_("No users found")?> -<? } ?></option>
				<?php foreach($users as $u) if ($u->get_login()!=Session::get_session_user()) { ?>
                <option value="<?php echo $u->get_login() ?>"><?php echo format_user($u, false) ?></option>
				<?php } ?>
          </select>
        </td>
    </tr> 
<?}?>
	<tr>
		<th><?php echo _("Priority") ?></th>
		<td class="left">
			<select name="priority">
			<?php
			$options = "";
			for ($i = 1; $i <= 10; $i++) {
				$options.= "<option value=\"$i\"";
				if ($priority == $i) {
					$options.= " selected ";
				}
				$options.= ">$i</option>";
			}
			print $options;
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
				<option value="Compromise"
				<?php
			if ($metric_type == "Compromise") echo " selected "; ?>
					><?=_("Compromise")?></option>
				<option value="Attack"
				<?php
			if ($metric_type == "Attack") echo " selected "; ?>
					><?=_("Attack")?></option>
				<option value="Level"
				<?php
			if ($metric_type == "Level") echo " selected "; ?>
					><?=_("Level")?></option>
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
	
	$fields = Incident_type::get_custom_list($conn,$type);
	$form_builder = new Form_builder();
	$params = array();
	$cont = 1;
	foreach ($fields as $field) {
		

		echo "<tr id='item_".$cont."'><th id='name_".$cont."' class='thr'><div class='label_name'>".utf8_decode($field['name'])."</div></th>";
    	
		echo "<td style='border-width: 0px;text-align:left'>";
		$params = get_params_field($field);
		$form_builder->set_attributes($params);
		
		$wf1_types = array ('Select box', 'Date','Date Range', 'Checkbox', 'Radio button');
		
		if ( in_array($field['type'], $wf1_types) )
			$class_wf = array('wfl1', 'wfr1');
		else
			$class_wf =  array('wfl2', 'wfr2');
		
		echo "<div class='".$class_wf[0]."'>";
			echo $form_builder->draw_element($field['type']);
		echo "</div>";
		
		$req_f_inherent = array('Check True/False', 'Check Yes/No', 'Asset', 'Slider');
		
		$mandatory = ( $field['required'] == 1 && !in_array($field['type'], $req_f_inherent) ) ? "<span>(*)</span>" : "";
			
			echo "<div class='".$class_wf[1]."'>".$mandatory."</div>";
		echo "</td>";
					
    	echo"</tr>\n";
		$cont++;
	}
}
?>

<tr>
	<td colspan="2" class="noborder" style='height:30px;'><input type="button" style='width:40px;' value="<?=_("OK")?>" class="button" onclick="send_form();"/></td>
</tr>
</table>

<table align="center" width="600" style='border: none;'>
	<tr>
		<td class="noborder center" style='height:50px;' valign='absmiddle'>
			<span>Fields marked with (*) are mandatory</span>
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
<?php


function format_user($user, $html = true, $show_email = false) {
    if (is_a($user, 'Session')) {
        $login = $user->get_login();
        $name = $user->get_name();
        $depto = $user->get_department();
        $company = $user->get_company();
        $mail = $user->get_email();
    } elseif (is_array($user)) {
        $login = $user['login'];
        $name = $user['name'];
        $depto = $user['department'];
        $company = $user['company'];
        $mail = $user['email'];
    } else {
        return '';
    }
    $ret = $name;
    if ($depto && $company) $ret.= " / $depto / $company";
    if ($mail && $show_email) $ret = "$ret &lt;$mail&gt;";
    if ($login) $ret = "<label title=\"Login: $login\">$ret</label>";
    if ($mail) {
        $ret = '<a href="mailto:' . $mail . '">' . $ret . '</a>';
    } else {
        $ret = "$ret <font size=small color=red><i>(No email)</i></font>";
    }
    return $html ? $ret : strip_tags($ret);
}
?>
