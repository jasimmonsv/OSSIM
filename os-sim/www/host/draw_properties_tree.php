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
require_once 'classes/Host_os.inc';
require_once 'classes/Host_services.inc';
require_once 'classes/Util.inc';
require_once 'ossim_db.inc';
require_once '../ossec/utils.php';


function getPropertyImage($property)
{
	switch (strtolower($property)){
		case "software": 
			$icon = "software.png";
		break;
		
		case "operating-system": 
			$icon = "host_os.png";
		break;
		case "cpu": 
			$icon = "cpu.png";
		break;
		
		case "services": 
			$icon = "ports.png";
		break;
		
		case "memory": 
			$icon = "ram.png";
		break;
		
		case "department": 
			$icon = "host_group.png";
		break;
		
		case "macaddress": 
			$icon = "mac.png";
		break;
		
		case "workgroup": 
			$icon = "net_group.png";
		break;
		
		case "role": 
			$icon = "server_role.png";
		break;
		
		case "property": 
			$icon = "notebook.png";
		break;
		
		case "nagios_ok": 
			$icon = "tick.png";
		break;
		
		case "nagios_ko": 
			$icon = "cross.png";
		break;

		case "acl": $icon = "acl.png";
		break;
		
		case "storage": $icon = "storage.png";
		break;
		
		case "route": $icon = "route.png";
		break;
                                                                        		
		default:
			$icon = "folder.png";
    }
	
	if (preg_match("/^OS\=/",$property))
	{
		$os_icon = Host_os::get_os_pixmap_nodb($property,"../",true);
		
		if ($os_icon!="") 
			$icon = $os_icon;
	}
	
	return $icon;

}


$db        = new ossim_db();
$conn 	   = $db->connect();

$ip        = GET('ip');
$tree      = GET('tree');

ossim_valid($ip, OSS_IP_ADDR, 'illegal:' . _("Ip Address"));

if ( ossim_error() ) 
	$ossim_error = true;

$image_url = "../../pixmaps/theme/";

$icon1 		= $image_url.'any.png';
$icon2 		= $image_url.'ltError.gif';
$empty_tree = "[{title: '<span class=\'size12n\'>"._("Properties")."</span>', key:'0', isFolder:true, icon:'$icon1', hideCheckbox: true, 
		children:[{title: '<span>"._("No data found")."</span>', addClass:'bold_red', key:'load_error', isFolder:false, hideCheckbox: true, icon:'$icon2'}]}]";

switch ($tree)
{
	case "tree_container_1":
					
		$properties_types   = Host::get_properties_types($conn);
		$properties    		= Host::get_host_properties($conn,$ip, '', 'ord, date DESC');
		$grouped_properties = array();
		$services_list 		= Host_services::get_ip_data($conn, $ip, '1');
		
		if ( count($properties_types) == 0 || $ossim_error)
		{
			echo $empty_tree;
			exit();
		}

		
		foreach ($properties_types as $k => $v)
		{
			if ( $v['name'] != 'Service')
				$grouped_properties[$v['name']."###".$v['id']."###".$v['description']]	= array();
		}
		
		if (count($properties) > 0 )
		{
			foreach ($properties as $k => $v)
			{
				$grouped_properties[ucwords($v['property'])."###".$v['property_ref']."###".$v['description']][] = array  ("id"   => $v['id'],
																				"sensor"        => $v['sensor'],
																				"date"          => $v['date'],
																				"property_id"   => $v['property_ref'],
																				"property"      => $v['property'],
																				"source_ref"    => $v['source_id'],
																				"source"        => $v['source'],
																				"value"         => $v['value'],
																				"extra"         => $v['extra'],
																				"anom"          => $v['anom']);
			}
		}
		
		
		$source_m = "MANUAL";
			
		
		$json_properties  = "[{";
		$icon             = $image_url.'any.png';
		$json_properties .= "title: '<span class=\'size12n\'>"._("Properties")."</span>', key:'0', isFolder:true, hideCheckbox: true, icon:'$icon', 
					children:[";	
				
		$num_gp = count($grouped_properties);
		$cont_1 = 0;
		
		foreach ($grouped_properties as $i => $property )
		{
			$cont_1++;
			$p      = explode("###", $i);
			$num_p  = count($property);
			$cont_2 = 0;
			$is_folder = ( $num_p > 0 ) ? "true" : "false";
			
			
			
			$json_properties .= "{title: '<span>"._(ucfirst($p[2]))."</span>', addClass:'size12', key:'property_".$p[1]."', isFolder:".$is_folder.", hideCheckbox: true, expand:true, icon:'".$image_url.getPropertyImage($p[0])."', children:[";
								
			foreach ($property as $j => $v )
			{
				$cont_2++;
				$class       = ( $v['anom'] == 1 ) ? "size12ig" : "size12n";
				$to_delete   = ( $v['source'] == $source_m ) ? "false" : "true";
				
				$json_properties .=  "{ title: '<span class=\'$class\'>".utf8_encode($v['value'])."</span>', value:'".utf8_encode($v['value'])."', anom:'".$v['anom']."', hideCheckbox: $to_delete, key:'item_prop_".$p[1]."_$cont_2###".$v['id']."###".$p[1]."', isFolder:true, icon:'".$image_url.getPropertyImage("OS=".$v["value"])."', children:[";
				$json_properties .=  "{ title: '<span class=\'size12n\'>"._("Date").": </span><span class=\'ml3 size12b\'>".$v['date']."</span>', date:'".$v['date']."', hideCheckbox: true, key:'date_".$v['id']."', isFolder:false, icon:'".$image_url.getPropertyImage('property')."'},";
				$json_properties .=  "{ title: '<span class=\'size12n\'>"._("Source").": </span><span class=\'ml3 size12b\'> ".$v['source']."</span>', source:'".$v['source']."',  source_id:'".$v['source_ref']."', hideCheckbox: true, key:'source_".$v['id']."', isFolder:false, icon:'".$image_url.getPropertyImage('property')."'},";
				$json_properties .=  "{ title: '<span class=\'size12n\'>"._("Version").": </span><span class=\'ml3 size12b\'>".utf8_encode($v['extra'])."</span>', extra:'".utf8_encode($v['extra'])."', hideCheckbox: true, key:'extra_".$v['id']."', isFolder:false,icon:'".$image_url.getPropertyImage('property')."'}";
				$json_properties .= ($num_p == $cont_2) ? "]}" : "]},";
			}
			
			$json_properties .= ($num_gp == $cont_1) ? "]}" : "]},";
		}



		$json_properties .= ( $cont_1 > 0 ) ? "," : "";
		
		if ( !is_array($services_list) )
			$services_list = array();	
			
			
		$num_s            = count($services_list);
		$cont_3           = 0;
		$is_folder 		  = ( $num_s > 0 ) ? "true" : "false";
		$json_properties .= "{title: '<span>"._("Services")."</span>', addClass:'size12', key:'property_8', isFolder:".$is_folder.", hideCheckbox: true, expand:true, icon:'".$image_url.getPropertyImage('services')."', children:[";
			
		foreach ($services_list as $k => $v )
		{
			$cont_3++;
			
			$service_name = "<span class=\'size12n\'>".$v['service']."</span><span class=\'size10n ml3\'> (". $v['port']."/".getprotobynumber($v['protocol']).")</span>";
			
			$service_key      = "item_prop_8_$cont_3###".$v['host']."###".$v['port']."###".$v['protocol']."###".$v['service']."###4";
			$json_properties .= "{ title: '<span>".$service_name."</span>', key:'$service_key', isFolder:true, icon:'".$image_url.getPropertyImage("")."', children:[";
			$json_properties .= "{ title: '<span class=\'size12n\'>"._("Version").": </span><span class=\'ml3 size12b\'>".$v['version']."</span>', key:'serv_version_".$cont_3."', hideCheckbox: true, isFolder:false, icon:'".$image_url.getPropertyImage('property')."'},";
			$json_properties .= "{ title: '<span class=\'size12n\'>"._("Date").": </span><span class=\'ml3 size12b\'> ".$v['date']."</span>', key:'serv_date_".$cont_3."', hideCheckbox: true, isFolder:false, icon:'".$image_url.getPropertyImage('property')."'},";
			$img_nagios 	  = ( $v['nagios'] == true ) ? "nagios_ok" : "nagios_ko";
			$nagios_key       = "nagios_$cont_3###".$v['port']."###".$img_nagios;
			$nagios           = "<img src=\'../pixmaps/theme/".getPropertyImage($img_nagios)."\'/>";
			$json_properties .= "{ title: '<span class=\'size12n\'>"._("Nagios").": </span><span class=\'ml3 size12b\'>$nagios</span>', key:'$nagios_key', hideCheckbox: true, isFolder:false, icon:'".$image_url.getPropertyImage('property')."'}";
			$json_properties .= ($num_s == $cont_3) ? "]}" : "]},";
		}
	
		$json_properties .= "]}";
			
		$json_properties .= "]}]";
			
	
	break;
	
	case "tree_container_2":
	
		$services_list 		= Host_services::get_ip_data($conn, $ip, '1');
								
		if ( !is_array($services_list) )
			$services_list = array();	
						
		$num_s     		  = count($services_list);
		$cont      		  = 0;
		$is_folder 		  = ( $num_s > 0 ) ? "true" : "false";
		$json_properties  = "[{title: '<span>"._("Services")."</span>', addClass:'size12', key:'property_8', isFolder:".$is_folder.", hideCheckbox: true, icon:'".$image_url.getPropertyImage('services')."', children:[";
			
		foreach ($services_list as $k => $v )
		{
			$cont++;
			
			$service_name = "<span class=\'size12n nagios\'>".$v['service']."</span><span class=\'size10n ml3 nagios\'> (". $v['port']."/".getprotobynumber($v['protocol']).")</span>";
			
			
			$img_nagios 	  = ( $v['nagios'] == true ) ? "nagios_ok" : "nagios_ko";
			$select_nagios 	  = ( $v['nagios'] == true ) ? "select: true," : "";
			$nagios           = "<img src=\'../pixmaps/theme/".getPropertyImage($img_nagios)."\'/>";
			$service_key      = "nagios_$cont###".$v['port']."###".$img_nagios;
			$json_properties .= "{ title: '<span>".$service_name."</span>', key:'$service_key', $select_nagios isFolder:true, icon:'".$image_url.getPropertyImage("")."', children:[";
			$json_properties .= "{ title: '<span class=\'size12n\'>"._("Version").": </span><span class=\'ml3 size12b\'>".$v['version']."</span>', key:'serv_version_".$cont."', hideCheckbox: true, isFolder:false, icon:'".$image_url.getPropertyImage('property')."'},";
			$json_properties .= "{ title: '<span class=\'size12n\'>"._("Date").": </span><span class=\'ml3 size12b\'> ".$v['date']."</span>', key:'serv_date_".$cont."', hideCheckbox: true, isFolder:false, icon:'".$image_url.getPropertyImage('property')."'},";
			$json_properties .= "{ title: '<span class=\'size12n\'>"._("Nagios").": </span><span class=\'ml3 size12b\'>$nagios</span>', hideCheckbox: true, key:'serv_nag_".$cont."', isFolder:false, icon:'".$image_url.getPropertyImage('property')."'}";
			$json_properties .= ($num_s == $cont) ? "]}" : "]},";
		}
	
		$json_properties .= "]}]";
	
	break;

}



$db->close($conn);	
echo $json_properties;
?>