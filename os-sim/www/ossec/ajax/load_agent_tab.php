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
require_once ('../conf/_conf.php');
require_once ('../utils.php');
require_once ('classes/Xml_parser.inc');

$tab         = POST('tab');
$error       = false;

$array_os    = array ( "Windows" => "Microsoft Windows",
					   "Linux"   => "Linux",
					   "FreeBSD" => "FreeBSD");
					
if($tab == "#tab1")
{
	echo "1###";
	?>
	<table id='agent_table'>
		<tr>
			<th style='width: 100px;'><?php echo _("ID")?></th>
			<th><?php echo _("Name")?></th>
			<th><?php echo _("IP")?></th>
			<th><?php echo _("Status")?></th>
			<th class='agent_actions'><?php echo _("Actions")?></th>
		</tr>
	
	<?php
	
	$agents = array();
	exec ( "sudo /var/ossec/bin/agent_control -ls", $agents, $ret);		
											
	if ( !empty ($agents) )
	{
		foreach ($agents as $k => $agent)
		{
			if ( empty($agent) )
				continue;
				
			$more_info = array();
			$ret       = null;
			
			$agent         = explode(",", $agent);
			$agent_type    = null;
			ossim_valid($agent[0], OSS_DIGIT, 'illegal:' . _("Id agent"));
			
			if ( ossim_error() ) 
			{
				ossim_clean_error();
				$agent_name    = $agent[0];
				$agent_actions = "  --  ";
				$agent_type    = 0;
			}
			else
			{
				exec ( "sudo /var/ossec/bin/agent_control -i ".$agent[0]." -s", $more_info, $ret);
				$more_info     = ( $ret !== 0 ) ? _("Information from agent not available") : explode(",",$more_info[0]);
				$agent_name    = "<a class='agent_id'><img src='../pixmaps/plus-small.png' alt='More info' align='absmiddle'/>".$agent[0]."</a>";
				$agent_actions = get_actions($agent);
				$agent_type    = 1;
			}	
			
			
			echo "<tr id='cont_agent_".$agent[0]."'>
					<td id='agent_".$agent[0]."'>$agent_name</td>
					<td>".$agent[1]."</td>
					<td>".$agent[2]."</td>
					<td>".$agent[3]."</td>
					<td class='agent_actions center'>$agent_actions</td>
				</tr>";
					
			if ( $agent_type === 1 )		
			{		
				echo "<tr id='minfo_".$agent[0]."' style='display:none;'>
						<td colspan='5'>";
							
							if ( !is_array($more_info) )
							{
								echo "<div style='padding:5px; color: #D8000C; text-align:center;'>$more_info</div>";
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
	}
	else
	{
		if ($ret === 0)
		{
			$txt   = _("No agents available");
			$class = "oss_info";
		}
		else
		{
			$txt   = _("You don't have execute permissions");
			$class = "oss_error";
			$error = true;
		}
		echo "<tr id='cont_no_agent'><td colspan='5' class='no_agent bborder_none'><div class='$class info_agent'>$txt</div></td></tr>";
	}
						
	?>
	
	</table>
				
	<?php 
	if ($error !== true) 
	{ 
	?> 
		<table id='agent_actions'>
			<tr>
				<td id='cont_commom_ac'>
					<div class='commom_ac'>
						<a id='show_agent'><img src='../pixmaps/user--plus.png' alt='Arrow' align='absmiddle'/><span><?php echo _("Add agent")?></span></a>
					</div>
				</td>
				<td class='info'></td>
			</tr>
			
			<tr>
				<td colspan='2'>
					<div id='cont_add_agent' class='visible'>
						<form method='POST' name='form_agent' id='form_agent'>
							<table>
								<tr>
									<th><label for='agent_name'><?php echo gettext("Agent Name"); ?></label></th>
									<td class="left">
										<input type='text' name='agent_name' id='agent_name' class='vfield req_field'/>
										<span style="padding-left: 3px;">*</span>
									</td>
								</tr>
								
								<tr>
									<th><label for='ip'><?php echo gettext("IP Address"); ?></label></th>
									<td class="left">
										<input type='text' name='ip' id='ip' class='vfield req_field'/>
										<span style="padding-left: 3px;">*</span>
									</td>
								</tr>
								<tr>
									<td class="cont_send" colspan='2'><input type="button" id='send' class="button" value="<?=_("Update")?>"/></td>
								</tr>
							</table>
						</form>
					</div>
				</td>
			</tr>
					
		</table>
				
	<?php } 
		
}
else if ($tab == '#tab2')
{
	if ( !file_exists($agent_conf) )
	{
		exec("touch $agent_conf");
		echo "1###";
		exit;
	}
	
	$conf_agent = @file_get_contents($agent_conf);
	
	if ($conf_agent === false)
	{
		echo "2###"._("File")." <b>$agent_conf</b> "._("not found or you don't have have permission to access");
		exit();
	}
			
	$result = test_agents(); 	
						
	if ( $result !== true )
		echo "3###".$conf_agent."###$result";
	else
		echo "1###".$conf_agent;
	
}
else
	echo "2###"._("Error: Illegal actions");

?>