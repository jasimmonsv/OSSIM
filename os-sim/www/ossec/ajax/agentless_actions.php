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


function get_type($id)
{
	$array_types = array (  "ssh_integrity_check_bsd" => "Integrity Check BSD",
						"ssh_integrity_check_linux"   => "Integrity Check Linux",
						"ssh_generic_diff" 			  => "Generic Command Diff",
						"ssh_pixconfig_diff"  		  => "Cisco Config Check",
						"ssh_foundry_diff" 			  => "Foundry Config Check ",
						"ssh_asa-fwsmconfig_diff "    => "ASA FWSMconfig Check");
						
	return $array_types[$id];

}

$db   = new ossim_db();
$conn = $db->connect();


$permitted_actions = array("add_monitoring_entry"     => "1",
						   "delete_monitoring_entry"  => "1",
						   "modify_monitoring_entry"  => "1");


$ip          	   = POST('ip');
$id          	   = POST('id');
$action     	   = POST('action');
$type    	       = POST('type');
$frecuency 	       = POST('frecuency');
$state    	       = POST('state');
$arguments 	       = POST('arguments');

	
	switch ($action)
	{
		case "add_monitoring_entry":
			
			$id = Agentless::add_monitoring_entry($conn, $ip, $type, $frecuency, $state, $arguments);
								
			if ($id !== false)
			{		
				$path = '../pixmaps';
							
				echo "1###".
					"<tr id='m_entry_$id'>
						<td class='nobborder center' id='al_type_$id'>". get_type($type)."</td>
						<td class='nobborder center' id='al_frecuency_$id'>$frecuency</td>
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
			$res = Agentless::modify_monitoring_entry($conn, $type, $frecuency, $state, $arguments, $id);
							
			if ($res !== false)
			{		
				$path = '../pixmaps';
							
				echo "1###".
					"	<td class='nobborder center' id='al_type_$id'>". get_type($type)."</td>
						<td class='nobborder center' id='al_frecuency_$id'>$frecuency</td>
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
	
	}

$db->close($conn);

?>


