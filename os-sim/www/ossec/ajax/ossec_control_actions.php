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

$action     	   = strtolower(POST('action'));

$allowed_act       = array("start", "stop", "restart", "status", 
						   "status", "ossec_log", "alerts_log", 
						   "enable_db", "disable_db", "enable_cs", 
						   "disable_cs", "enable_al", "disable_al", "enable_dbg", "disable_dbg");
						   
						   
$check_pattern      = array ( "enable_db"   => "/ossec-dbd is running/", 
							  "disable_db"  => "/ossec-dbd is running/", 
							  "enable_cs"   => "/ossec-csyslogd is running/", 
						      "disable_cs"  => "/ossec-csyslogd is running/", 
						      "enable_al"   => "/ossec-agentlessd is running/", 
							  "disable_al"  => "/ossec-agentlessd is running/",
							  "enable_dbg"  => "/ossec-analysisd -d|ossec-syscheckd -d|ossec-remoted -d|ossec-monitord -d | ossec-analysisd -d/",
							  "disable_dbg" => "/ossec-analysisd -d|ossec-syscheckd -d|ossec-remoted -d|ossec-monitord -d | ossec-analysisd -d/",
							  "system"      => "/ossec-analysisd is running|ossec-syscheckd is running|ossec-remoted is running|ossec-monitord is running/"
						);

if ( in_array($action, $allowed_act) )
{
	
	switch ($action){
	
		case "ossec_log":
			$extra = POST('extra');
			$extra = ( empty($extra) ) ? 50 : $extra;
			
			exec ("tail -n$extra /var/ossec/logs/ossec.log", $result, $ret);
			$result = implode("<br/>", $result);
			$result = str_replace("INFO", "<span style='font-weight: bold; color:#15B103;'>INFO</span>", $result);
			echo str_replace("ERROR", "<span style='font-weight: bold; color:#E54D4d;'>ERROR</span>", $result);
		break;
		
		case "alerts_log":
			$extra = POST('extra');
			$extra = ( empty($extra) ) ? 50 : $extra;
			
			exec ("tail -n$extra /var/ossec/logs/alerts/alerts.log", $result, $ret);
			$result = implode("<br/>", $result);
			echo preg_replace("/\*\* Alert ([0-9]+\.[0-9]+)/", "<span style='font-weight: bold; color:#E54D4d;'>$0</span>", $result);
		break;
		
		default:
			
			$extra = POST('extra');
					
			if ( empty($extra) )
			{
				if ( $action != _("status") )
				{
					exec ("sudo /var/ossec/bin/ossec-control $action", $result, $ret);
					$result = null;
				}
				exec ("sudo /var/ossec/bin/ossec-control status",  $result, $ret);
			}
			else
			{
				exec ("sudo /var/ossec/bin/ossec-control ".$extra);
				
				if ( $action == "enable_dbg" || $action == "disable_dbg")
				{
					exec ("sudo /var/ossec/bin/ossec-control restart", $result, $ret);
					$result = null;
					
					exec ("ps -ef | grep ossec | grep -v grep", $result);
					$status = implode('', $result);
					
					preg_match_all($check_pattern[$action], $status, $match);
					
					$result = null;
					exec ("sudo /var/ossec/bin/ossec-control status", $result, $ret);
					
					if ( (count($match[0]) > 0 && $action == "disable_dbg") || (count($match[0]) == 0 && $action == "enable_dbg") )
						$ret = "error";
								
				}
				else
				{
					exec ("sudo /var/ossec/bin/ossec-control status", $result);
					$status = implode('', $result);
					preg_match($check_pattern[$action], $status, $match);
					
					if ( preg_match ('/enable/', $action) )					
						$ret = ( count($match) >= 1 ) ? 0 : "error";
					else
						$ret = ( count($match) == 0 ) ? 0 : "error";
				}
			}
			
			
			$status = $result; 
			$result = implode("<br/>", $result);
						
			$pattern     = array('/is running/', '/already running/', '/Completed/', '/Started/', '/not running/', '/Killing/', '/Stopped/');
			
			$replacement = array('<span style="font-weight: bold; color:#15B103;">is running</span>', 
								 '<span style="font-weight: bold; color:#15B103;">already running</span>', 
								 '<span style="font-weight: bold; color:#15B103;">Completed</span>',
								 '<span style="font-weight: bold; color:#000000;">Started</span>',
								 '<span style="font-weight: bold; color:#E54D4D;">not running</span>',
								 '<span style="font-weight: bold; color:#000000;">Killing</span>'.
								 '<span style="font-weight: bold; color:#E54D4D;">Stopped</span>');
			
			$output = $ret."###".preg_replace($pattern, $replacement, $result)."###";
			
			$status = implode("", $status);
			preg_match_all($check_pattern['system'], $status, $match);

			if ( count($match[0]) < 4)
				$system_action    = "<span class='not_running'>"._("Ossec service is down")."</span><br/><br/>
									<input type='button' id='system_start' class='lbutton' value='"._("Start")."'/>"; 
			else
				$system_action   = "<span class='running'>"._("Ossec service is up")."</span><br/><br/>
									<input type='button' id='system_stop' class='lbuttond' value='"._("Stop")."'/>"; 
								

			$system_action .= "<input type='button' id='system_restart' class='lbutton' value='"._("Restart")."'/>";
				

			preg_match($check_pattern['enable_cs'], $status, $match_cs);

			$syslog_action    = ( count($match_cs) < 1 ) ? "<span class='not_running'>Client-syslog "._("not running")."</span><br/><br/><input type='button' id='cs_enable' class='lbutton' value='"._("Enable")."'/>" : "<span class='running'>Client-syslog "._("is running")."</span><br/><br/><input type='button' id='cs_disable' class='lbuttond' value='"._("Disable")."'/>";

			preg_match($check_pattern['enable_db'], $status, $match_db);
			$database_action  = ( count($match_db) < 1 ) ? "<span class='not_running'>Database "._("not running")."</span><br/><br/><input type='button' id='db_enable' class='lbutton' value='"._("Enable")."'/>" : "<span class='running'>Database "._("is running")."</span><br/><br/><input type='button' id='db_disable' class='lbuttond' value='"._("Disable")."'/>";


			preg_match($check_pattern['enable_al'], $status, $match_al);
			$agentless_action = ( count($match_al) < 1 ) ? "<span class='not_running'>Agentless "._("not running")."</span><br/><br/><input type='button' id='al_enable' class='lbutton' value='"._("Enable")."''/>" : "<span class='running'>Agentless "._("is running")."</span><br/><br/><input type='button' id='al_disable' class='lbuttond' value='"._("Disable")."'/>";

			exec ("ps -ef | grep ossec | grep -v grep", $res_dbg);
			$status_dbg = implode('', $res_dbg);
			preg_match_all($check_pattern['enable_dbg'], $status_dbg, $match_dbg);
			$debug_action     = ( count($match_dbg[0]) < 1 ) ? "<span class='not_running'>Debug "._(" is disabled")."</span><br/><br/><input type='button' id='dbg_enable' class='lbutton' value='"._("Enable")."''/>" : "<span class='running'>Debug "._("is enabled")."</span><br/><br/><input type='button' id='dbg_disable' class='lbuttond' value='"._("Disable")."'/>";
	
			$output .= "<td class='noborder center pad10' id='cont_db_action'>$database_action</td>
			<td class='noborder center pad10' id='cont_cs_action'>$syslog_action</td>
			<td class='noborder center pad10' id='cont_al_action'>$agentless_action</td>
			<td class='noborder center pad10' id='cont_dbg_action'>$debug_action</td>
			<td class='noborder center pad10' id='cont_system_action'>$system_action</td>";
			
			echo $output;
	
	}
}
else
	echo _("Illegal action");


?>