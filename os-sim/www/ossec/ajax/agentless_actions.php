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

require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
require_once ('classes/Util.inc');
require_once ('classes/Ossec.inc');
require_once ('../utils.php');

$db   = new ossim_db();
$conn = $db->connect();


$permitted_actions = array("add_monitoring_entry"     => "1",
						   "delete_monitoring_entry"  => "1",
						   "modify_monitoring_entry"  => "1",
						   "add_host_data"  		  => "1",
						   "modify_host_data"         => "1");
						   
						   


$ip          	   = POST('ip');
$id          	   = POST('id');
$action     	   = POST('action');
$type    	       = POST('type');
$frequency 	       = POST('frequency');
$state    	       = POST('state');
$arguments 	       = POST('arguments');

	if ( !array_key_exists($action, $permitted_actions) )
	{
		echo "error###"._("Action not allowed");
		exit();
	}
	
	switch ($action)
	{
		case "add_monitoring_entry":
		
			$validate = array (
			"ip"          => array("validation"=>"OSS_IP_ADDR", "e_message" => 'illegal:' . _("IP")),
			"type"        => array("validation"=>"OSS_NOECHARS, OSS_SCORE, OSS_LETTER", "e_message" => 'illegal:' . _("Type")),
			"frequency"   => array("validation"=>"OSS_DIGIT", "e_message" => 'illegal:' . _("frequency")),
			"state"       => array("validation"=>"OSS_NOECHARS, OSS_SCORE, OSS_LETTER", "e_message" => 'illegal:' . _("State")),
			"arguments"   => array("validation"=>"OSS_NOECHARS, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_AT, OSS_NL, OSS_NULLABLE", "e_message" => 'illegal:' . _("Arguments")));
		
		break;
		
		case "delete_monitoring_entry":
			$validate = array (
				"id"   => array("validation"=>"OSS_DIGIT", "e_message" => 'illegal:' . _("Id")));
		break;
		
		case "modify_monitoring_entry":
			$validate = array (
			"id"   => array("validation"=>"OSS_DIGIT", "e_message" => 'illegal:' . _("Id")),
			"type"        => array("validation"=>"OSS_NOECHARS, OSS_SCORE, OSS_LETTER", "e_message" => 'illegal:' . _("Type")),
			"frequency"   => array("validation"=>"OSS_DIGIT", "e_message" => 'illegal:' . _("frequency")),
			"state"       => array("validation"=>"OSS_NOECHARS, OSS_SCORE, OSS_LETTER", "e_message" => 'illegal:' . _("State")),
			"arguments"   => array("validation"=>"OSS_NOECHARS, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_AT, OSS_NL, OSS_NULLABLE", "e_message" => 'illegal:' . _("Arguments")));
		break;
		
		case "modify_host_data":
			$validate = array (
				"hostname"    => array("validation"=>"OSS_NOECHARS, OSS_SCORE, OSS_LETTER, OSS_DIGIT, OSS_DOT", "e_message" => 'illegal:' . _("Hostname")),
				"ip"          => array("validation"=>"OSS_IP_ADDR", "e_message" => 'illegal:' . _("IP")),
				"user"        => array("validation"=>"OSS_NOECHARS, OSS_ALPHA, OSS_PUNC_EXT", "e_message" => 'illegal:' . _("User")),
				"descr"       => array("validation"=>"OSS_NOECHARS, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_AT, OSS_NL, OSS_NULLABLE", "e_message" => 'illegal:' . _("Description")),
				"pass"        => array("validation"=>"OSS_NOECHARS, OSS_ALPHA, OSS_PUNC_EXT", "e_message" => 'illegal:' . _("Password")),
				"passc"       => array("validation"=>"OSS_NOECHARS, OSS_ALPHA, OSS_PUNC_EXT", "e_message" => 'illegal:' . _("Pass confirm")),
				"ppass"       => array("validation"=>"OSS_NOECHARS, OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE", "e_message" => 'illegal:' . _("Priv. Password")),
				"ppassc"      => array("validation"=>"OSS_NOECHARS, OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE", "e_message" => 'illegal:' . _("Priv. Pass confirm")));
		break;
		
		case "add_host_data":
			$validate = array (
				"hostname"    => array("validation"=>"OSS_NOECHARS, OSS_SCORE, OSS_LETTER, OSS_DIGIT, OSS_DOT", "e_message" => 'illegal:' . _("Hostname")),
				"ip"          => array("validation"=>"OSS_IP_ADDR", "e_message" => 'illegal:' . _("IP")),
				"user"        => array("validation"=>"OSS_NOECHARS, OSS_ALPHA, OSS_PUNC_EXT", "e_message" => 'illegal:' . _("User")),
				"descr"       => array("validation"=>"OSS_NOECHARS, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_AT, OSS_NL, OSS_NULLABLE", "e_message" => 'illegal:' . _("Description")),
				"pass"        => array("validation"=>"OSS_NOECHARS, OSS_ALPHA, OSS_PUNC_EXT", "e_message" => 'illegal:' . _("Password")),
				"passc"       => array("validation"=>"OSS_NOECHARS, OSS_ALPHA, OSS_PUNC_EXT", "e_message" => 'illegal:' . _("Pass confirm")),
				"ppass"       => array("validation"=>"OSS_NOECHARS, OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE", "e_message" => 'illegal:' . _("Priv. Password")),
				"ppassc"      => array("validation"=>"OSS_NOECHARS, OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE", "e_message" => 'illegal:' . _("Priv. Pass confirm")));
		break;
	
	}
		
	$validation_errors = validate_form_fields('POST', $validate);
	
	if ( ( $validation_errors == 1 ) ||  (is_array($validation_errors) && !empty($validation_errors)) )
	{
		$message_error = array();
			
		if ( is_array($validation_errors) && !empty($validation_errors) )
			$message_error = array_merge($message_error, $validation_errors);
		else
		{
			if ($validation_errors == 1)
				$message_error [] = _("Invalid send method");
		}
				
	}

	if ( is_array($message_error) && !empty($message_error) )
	{
		echo "general_error###<div>"._("We found the following errors").":</div><div style='padding:5px;'>".implode( "<br/>", $message_error)."</div>";
		exit();
	}

	
	switch ($action)
	{
		case "add_monitoring_entry":
				
			$id = Agentless::add_monitoring_entry($conn, $ip, $type, $frequency, $state, $arguments);
								
			if ($id !== false)
			{		
				$path = '../pixmaps';
							
				echo "1###".
					"<tr id='m_entry_$id'>
						<td class='nobborder center' id='al_type_$id'>$type</td>
						<td class='nobborder center' id='al_frequency_$id'>$frequency</td>
						<td class='nobborder center' id='al_state_$id'>$state</td>
						<td class='nobborder left' id='al_arguments_$id'>$arguments</td>
						<td class='center nobborder'>
							<a onclick=\"add_values('$id')\"><img src='$path/pencil.png' align='absmiddle' alt='"._("Modify monitoring entry")."' title='"._("Modify monitoring entry")."'/></a>
							<a onclick=\"delete_monitoring('$id')\" style='margin-right:5px;'><img src='$path/delete.gif' align='absmiddle' alt='"._("Delete monitoring entry")."' title='"._("Delete monitoring entry")."'/></a>
						</td>
					</tr>";
			
			}	
			else
				echo "error###"._("Error to Add Monitoring Entry");
		break;
		
		case "delete_monitoring_entry":
			$res = Agentless::delete_monitoring_entry($conn, $id);
								
			if ($res == true)
				echo "Ok";
			else
				echo "error###"._("Error to Delete Monitoring Entry");
			
		break;
						
		case "modify_monitoring_entry":
			$res = Agentless::modify_monitoring_entry($conn, $type, $frequency, $state, $arguments, $id);
							
			if ($res !== false)
			{		
				$path = '../pixmaps';
							
				echo "1###".
					"	<td class='nobborder center' id='al_type_$id'>". get_type($type)."</td>
						<td class='nobborder center' id='al_frequency_$id'>$frequency</td>
						<td class='nobborder center' id='al_state_$id'>$state</td>
						<td class='nobborder left' id='al_arguments_$id'>$arguments</td>
						<td class='center nobborder'>
							<a onclick=\"add_values('$id')\"><img src='$path/pencil.png' align='absmiddle' alt='"._("Modify monitoring entry")."' title='"._("Modify monitoring entry")."'/></a>
							<a onclick=\"delete_monitoring('$id')\" style='margin-right:5px;'><img src='$path/delete.gif' align='absmiddle' alt='"._("Delete monitoring entry")."' title='"._("Delete monitoring entry")."'/></a>
						</td>
					";
			}	
			else
				echo "error###"._("Error to Modify Monitoring Entry");
		break;
		
		case "add_host_data":
			
			$res = Agentless::add_host_data ($conn, POST('ip'), POST('hostname'), POST('user'), POST('pass'), POST('ppass'), POST('descr'), 1);
											
			if ( $res == true )
				echo _("1###Host Sucessfully added");
			else
				echo _("error###Error Adding Monitorig Host Data");
		break;
		
		case "modify_host_data":
			
			$extra     = "WHERE ip = '".POST('ip')."'";
			$agentless = array_shift(Agentless::get_list($conn, $extra));
			
			$status    = ( $agentless->get_status() != 2 ) ? $agentless->get_status() : 1;
	
			$res = Agentless::modify_host_data ($conn, POST('ip'), POST('hostname'), POST('user'), POST('pass'), POST('ppass'), POST('descr'), $status);
											
			if ( $res == true )
				echo _("1###Host Sucessfully updated");
			else
				echo _("error###Error Updating Monitorig Host Data");
		break;
	
	}

$db->close($conn);

?>


