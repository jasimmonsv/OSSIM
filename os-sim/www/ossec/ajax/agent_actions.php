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
require_once ('../utils.php');


function get_last_agent($agents)
{
	$size = count($agents) - 1;
	for($i = $size; $i>=0; $i--)
    {
		if (preg_match("/^[0-9]+,/", $agents[$i]) )
			return $agents[$i];
	}
}



$error = false;

$message_error     = array();
$validation_errors = array();
$agents            = array();

$permitted_actions = array("add_agent"     => "1",
						   "delete_agent"  => "1",
						   "check_agent"   => "1",
						   "restart_agent" => "1",							   
						   "extract_key"   => "1");

$agent_name  	   = POST('agent_name');
$ip          	   = POST('ip');
$action     	   = POST('action');
$id     	       = POST('id');


$validate = array (
	"agent_name" => array("validation"=>"OSS_SCORE, OSS_LETTER, OSS_DIGIT, OSS_DOT", "e_message" => 'illegal:' . _("Agent Name")),
	"ip"         => array("validation"=>"OSS_IP_ADDR", "e_message" => 'illegal:' . _("IP address")));

if ( GET('ajax_validation') == true )
{
	$validation_errors = validate_form_fields('GET', $validate);
	if ( $validation_errors == 1 )
		echo 1;
	else if ( empty($validation_errors) )
		echo 0;
	else
		echo $validation_errors[0];
		
	exit();
}
else
{
	if ( !array_key_exists($action, $permitted_actions) || $permitted_actions[$action] != 1)
	{
		$error           = true;
		$message_error[] = _("Invalid action");
	}
	else
	{
		if ($action != "add_agent")
			$validate = array ("id" => array("validation"=>"OSS_DIGIT", "e_message" => 'illegal:' . _("Agent ID")));
		
		if ($action == "add_agent")
		{
			exec ( "sudo /var/ossec/bin/agent_control -ls", $agents, $ret);
									
			if ($ret !== 0)
				$message_error[] = _("Error to Add Agent")." (1)";
			else
			{
				if ( is_array($agents) )
				{
					foreach ($agents as $k => $agent)
					{
						$agent = explode(",", $agent);
						if ( !empty($agent_name) && $agent[1] == $agent_name )
						{
							$message_error[] =_("Name")." '$agent_name' "._("already present. Please enter a new name.");
							break;
						}
					}
					
					if ( empty($message_error) )
					{
						if (strlen($agent_name) < 2 || strlen($agent_name) > 32 )
							$message_error[] = _("Invalid name")." '$agent_name' "._("given.<br/> Name must contain only alphanumeric characters (min=2, max=32).");
					}
				}
			}
        }
		
		$validation_errors = validate_form_fields('POST', $validate);
				
		if ( ( $validation_errors == 1 ) ||  (is_array($validation_errors) && !empty($validation_errors)) || !empty($message_error) )
		{
			$error = true;
			
			if ( is_array($validation_errors) && !empty($validation_errors) )
				$message_error = array_merge($message_error, $validation_errors);
			else
			{
				if ($validation_errors == 1)
					$message_error [] = _("Invalid send method");
			}	
		}
	}
}


if ($error == true)
	echo "error###".implode( "<br/>", $message_error);
else
{
	$agents = array ();
	$ret    = null;
	
	switch ($action)
	{
		case "add_agent":
			
			exec("echo 'A'$'\n''$agent_name'$'\n''$ip'$'\n'$'\n''y'$'\n''Q'$'\n' | sudo /var/ossec/bin/manage_agents", $agents, $ret);
			
			if ($ret !== 0)
				echo "error###"._("Error to Add Agent")." (2)";
			else
			{
				exec ( "sudo /var/ossec/bin/agent_control -ls", $agents, $ret);
								
				if ( is_array ($agents) )
				{
					$agent       = get_last_agent($agents);
					
					$agent_field = explode(",", $agent);
										
					if (count($agents) == 1 )
					{
						$header = "<tr>
									<th style='width: 100px;'>"._("ID")."</th>
									<th>"._("Name")."</th>
									<th>"._("IP")."</th>
									<th>"._("Status")."</th>
									<th class='agent_actions'>"._("Actions")."</th>
								</tr>";
					}
					else
						$header = '';
																
					if ( is_array($agent_field))
					{
						$more_info = array();
						$ret       = null;
												
						exec ( "sudo /var/ossec/bin/agent_control -i ".$agent_field[0]." -s", $more_info, $ret);
						$more_info = ( $ret !== 0 ) ? _("Information from agent not available") : explode(",",$more_info[0]);
												
						echo "1###"._("Agent added sucessfully")."###".$agent_field[0]."###".$header.
							"<tr id='cont_agent_".$agent_field[0]."'>
								<td id='agent_".$agent_field[0]."'><a class='agent_id'><img src='../pixmaps/plus-small.png' alt='More info' align='absmiddle'/>".$agent_field[0]."</a></td>
								<td>".$agent_field[1]."</td>
								<td>".$agent_field[2]."</td>
								<td>".$agent_field[3]."</td>
								<td class='agent_actions center'>".get_actions($agent_field)."</td>
							</tr>
							<tr id='minfo_".$agent_field[0]."' style='display:none;'>
								<td colspan='5'>";
									if ( !is_array($more_info) )
									{
										echo "<div style='margin:auto; padding:5px; color: #D8000C;'>$more_info</div>";
									}
									else
									{
										echo "<div style='padding: 3px 3px 5px 5px; font-weight: bold;'>"._("Agent information").":</div>";
										
										echo "<div style='float:left; width: 170px; font-weight: bold; padding:0px 3px 5px 15px;'>
												<span>"._("Agent ID").":</span><br/> 
												<span>"._("Agent Name").":</span><br/>
												<span>"._("IP address").":</span><br/>
												<span>"._("Status").":</span><br/><br/>
												<span>"._("Operating system").":</span><br/>
												<span>"._("Client version").":</span><br/>
												<span>"._("Last keep alive").":</span><br/><br/>
												<span>"._("Syscheck last started at").":</span><br/>
												<span>"._("Rootcheck last started at").":</span><br/>
										</div>";
										
										echo "<div style='float:left; width: auto; padding:0px 3px 5px 15px;'>
												<span>".$more_info[0]."</span><br/>  
												<span>".$more_info[1]."</span><br/>
												<span>".$more_info[2]."</span><br/>
												<span>".$more_info[3]."</span><br/><br/>
												<span>".$more_info[4]."</span><br/>
												<span>".$more_info[5]."</span><br/>
												<span>".$more_info[6]."</span><br/><br/>
												<span>".$more_info[7]."</span><br/>
												<span>".$more_info[8]."</span><br/>
											 </div>
										</div>";
									}
						echo "</td>
							</tr>";
					}	
				}
				else
					echo "error###"._("Error to Add Agent")." (3)";
					
			}
		break;
		
		case "delete_agent":
			exec("echo 'R'$'\n''$id'$'\n'$'\n''y'$'\n''Q'$'\n' | sudo /var/ossec/bin/manage_agents", $result, $ret);	
						
			if ($ret !== 0)
				echo "error###"._("Error to delete Agent");
			else
				echo "1###"._("Agent deleted sucessfully");
		break;
		
		case "restart_agent":
			exec ( "sudo /var/ossec/bin/agent_control -R $id", $results, $ret);
						
			if ($ret !== 0)
			{
				$msg = explode("OSSEC HIDS agent_control:", implode("\n",$results));
				$char_list = "\t\n\r\0\x0B";
				$msg = trim(str_replace("**", "", $msg[0]), $char_list);
				echo "error###$msg";
			}	
			else
				echo "1###"._("OSSEC HIDS agent_control.  Agent")." $id "._("restarted");
		break;
		
		case "check_agent":
			exec ( "sudo /var/ossec/bin/agent_control -r -u $id", $results, $ret);
						
			if ($ret !== 0)
			{
				$msg = explode("OSSEC HIDS agent_control:", implode("\n",$results));
				$char_list = "\t\n\r\0\x0B";
				$msg = trim(str_replace("**", "", $msg[0]), $char_list);
				echo "error###$msg";
			}
			else
			{
				echo "1###"._("OSSEC HIDS agent_control: Restarted Syscheck/Rootcheck on agent:")." $id";
			}
		break;
		
		case "extract_key":
			
			exec("echo 'E'$'\n''$id'$'\n'$'\n''Q'$'\n' | sudo /var/ossec/bin/manage_agents", $result, $ret);	
						
			
			if ($ret !== 0)
				echo "error###"._("Error to extract key for agent")." (1)";
			else
			{
				$results = implode("", $result);
				
				$pattern = "/Agent key information for \'$id\' is:(.*?) /";
				if ( preg_match($pattern, $results, $match) !== false )
				{	
					$txt = explode(":", $match[0], 2);
					$key = trim(str_replace('**', '', $txt[1]));
					//$ckey = $key;
					//$key = ( strlen($key) ) > 90 ? substr($key, 0, 90)."<br/>".substr($key, 90) : $key;
					echo "1###<div class='agent_key'>".$txt[0].":<br/><br/><span class='akey'>$key</span></div>";
				}
				else
					echo "error###"._("Error to extract key for agent")." (2)";
					
			}
		break;
	}
}

?>