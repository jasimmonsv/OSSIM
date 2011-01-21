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
						   "disable_cs", "enable_al", "disable_al");
						   
						   
$allowed_proccess = array ( "enable_db"  => "ossec-dbd", 
							"disable_db" => "ossec-dbd", 
							"enable_cs"  => "ossec-csyslogd", 
						    "disable_cs" => "ossec-csyslogd", 
						    "enable_al"  => "ossec-agentlessd", 
							"disable_al" => "ossec-agentlessd"
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
				exec ("sudo /var/ossec/bin/ossec-control status", $result);
				
				exec ("ps -ef | grep ".$allowed_proccess[$action]."| grep -v grep", $output, $ret);
				
				if ( preg_match("/enable/", $action) )
					$ret = ( $output >= 1 ) ? 1 : "error";
				else
					$ret = ( $output >= 1 ) ? "error" : 1;
			}
						
						
			$result = implode("<br/>", $result);
						
			$pattern     = array('/is running/', '/already running/', '/Completed/', '/Started/', '/not running/', '/Killing/', '/Stopped/');
			
			$replacement = array('<span style="font-weight: bold; color:#15B103;">is running</span>', 
								 '<span style="font-weight: bold; color:#15B103;">already running</span>', 
								 '<span style="font-weight: bold; color:#15B103;">Completed</span>',
								 '<span style="font-weight: bold; color:#000000;">Started</span>',
								 '<span style="font-weight: bold; color:#E54D4D;">not running</span>',
								 '<span style="font-weight: bold; color:#000000;">Killing</span>'.
								 '<span style="font-weight: bold; color:#E54D4D;">Stopped</span>');
			
			echo $ret."###".preg_replace($pattern, $replacement, $result);
	
	}
}
else
	echo _("Illegal action");


?>