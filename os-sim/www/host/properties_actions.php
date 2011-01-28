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

require_once 'classes/Session.inc';
require_once 'classes/Security.inc';
require_once 'classes/Host.inc';
require_once 'classes/Host_services.inc';
require_once 'ossim_db.inc';
require_once 'classes/Frameworkd_socket.inc';

$action = POST('action');
$ip     = POST('ip');
$items  = POST('data');

ossim_valid($ip, OSS_IP_ADDR, 'illegal:' . _("Ip Address"));

if ( ossim_error() || empty($items) || empty($action) )
	exit();

$items = explode(',', $items);

$db    = new ossim_db();
$conn  = $db->connect();

switch ($action){

	case "delete":
		foreach ($items as $k => $v)
		{
			$item = explode("###", $v);
			
			if ( preg_match ("/prop4_/", $item[0]) )
			{
				$host     = $item[1];
				$port     = $item[2];
				$protocol = $item[3];
				$service  = $item[4];
				
				ossim_valid($host, OSS_IP_ADDR, 'illegal:' . _("Ip Address"));
				ossim_valid($port, OSS_PORT, 'illegal:' . _("Port"));
				ossim_valid($protocol, OSS_ALPHA, OSS_SPACE, OSS_PUNC, 'illegal:' . _("Protocol"));
				ossim_valid($service, OSS_ALPHA, OSS_SPACE, OSS_PUNC, 'illegal:' . _("Service"));
				
				if ( !ossim_error() )
					Host_services::deleteUnit($conn, $host, $port, $protocol, $service);
				else
					ossim_clean_error();
			
			}
			else
			{
				$id = $item[1];
				
				ossim_valid($id, OSS_DIGIT, 'illegal:' . _("Id property host reference"));
				
				if ( !ossim_error() )
					$ret = Host::delete_property($conn, $ip, $id);
				else
					ossim_clean_error();

			}
		}
	
	break;
	
	case "nagios":
	
		foreach ($items as $k => $item)
		{
			$item   = explode("###", $item);
			
			ossim_valid($item[1], OSS_PORT, 'illegal:' . _("Port"));
			
			if ( !ossim_error() ) 
			{
				if ( $item[2] == "nagios_ok")
					Host_services::set_nagios($conn, $ip, $item[1], 1);
				else
					Host_services::set_nagios($conn, $ip, $item[1], 0);
			}
			else
				ossim_clean_error();
			
						
		}
		
		$s = new Frameworkd_socket();
		if ($s->status) {
			if ( !$s->write('nagios action="reload" "') ) 
				echo _("Frameworkd couldn't recieve a nagios command");
				
			$s->close();
		} 
		else 
			echo _("Couldn't connect to frameworkd");
		
	break;
}

$db->close($conn);	
?>