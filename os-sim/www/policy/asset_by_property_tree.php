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
* - cmpf()
* Classes list:
*/
require_once ('classes/Security.inc');
require_once ('classes/Host.inc');
require_once ('classes/Host_os.inc');
require_once ('classes/Host_services.inc');
require_once ('classes/Host_mac.inc');
require_once ('classes/Util.inc');
require_once ('ossim_db.inc');

Session::logcheck("MenuPolicy", "PolicyHosts");
function cmpf($a, $b) {
    return (count($a) < count($b));
}


$from = GET('from');
ossim_valid($from, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("From"));
if (ossim_error()) {
    die(ossim_error());
}

$buffer      = "";
$ossim_hosts = array();
$db          = new ossim_db();
$conn        = $db->connect();

if ($from!="") 
{
	$length_hn = 50;
	
	if ($host_list = Host::get_list($conn, "", "ORDER BY hostname")) 
	{
		foreach($host_list as $host) 
		{
		   $hostname = ( strlen($host->get_hostname()) > $length_hn ) ? substr($host->get_hostname(), 0, $length_hn)."..." :$host->get_hostname();
		   $ossim_hosts[$host->get_ip() ] = $hostname;
		}
	}

	// Json properties data
	$props = Host::get_latest_properties($conn,date("Y-m-d H:i:s",$from),20);
	foreach ($props as $prop) 
	{
		$prop["value"] = str_replace("'","\'",$prop["value"]);
		$prop["extra"] = str_replace("'","\'",$prop["extra"]);
		
		$buffer .= "{
			ip:'".$prop["ip"]."',
			ref:'p".$prop["property_ref"]."',
			value:'".Util::utf8_encode2($prop["value"])."',
			key:'".md5($prop["value"])."',
			extra:'".Util::utf8_encode2($prop["extra"])."',
			name:'".(($ossim_hosts[$prop["ip"]]!="") ? $prop["ip"]." <font style=\"font-size:80%\">(" . $ossim_hosts[$prop["ip"]] . ")</font>" : $host_ip)."'
		},";
	}
	
	$buffer = "[ ".preg_replace("/,$/","",$buffer)." ]";
} 
else 
{
	// Default tree
	$props   = Host::get_properties_types($conn);
    $buffer .= "[ {title: '"._("Asset by Property")." <img src=\"../pixmaps/sem/loading.gif\" style=\"display:none\" id=\"refreshing\" border=0>', isFolder: true, key:'main', icon:'../../pixmaps/theme/any.png', expand:true, children:[\n";
    
	$icons = array (
		"software" 		   => "software",
		"operating-system" => "host_os",
		"cpu" 			   => "cpu",
		"service" 		   => "ports",
		"memory" 		   => "ram",
		"department" 	   => "host_group",
		"macaddress"       => "mac",
		"workgroup" 	   => "net_group",
		"role" 		       => "server_role",
		"acl" 		       => "acl",
		"storage"	       => "storage",
		"route" 		   => "route"
	);
			
	foreach ($props as $prop) 
	{
    	$png     = $icons[strtolower($prop["name"])];
		$png     = ( empty($png) ) ? "folder" : $png;
    	$buffer .= "{ key:'p".$prop["id"]."', isFolder:true, expand:true, icon:'../../pixmaps/theme/$png.png', title:'"._($prop["description"])."' },\n";
    }
    
	$buffer .= "{ key:'all', expand:false, icon:'../../pixmaps/theme/host_add.png', title:'"._("All Hosts")."' }\n";
    $buffer .= "] } ]";
}

if ( $buffer == "" || $buffer == "[  ]" )
    $buffer = "[{title:'"._("No Properties found").", noLink:true'}]";
    
echo $buffer;
$db->close($conn);
?>
