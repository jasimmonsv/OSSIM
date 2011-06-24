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

// Check cache file

$filter = GET('filter');
$filter = (mb_detect_encoding($filter." ",'UTF-8,ISO-8859-1') == 'UTF-8') ? Util::utf8entities($filter) : $filter;


$key  = GET('key');
$page = intval(GET('page'));

ossim_valid($filter, OSS_NULLABLE, OSS_ALPHA, OSS_SPACE, OSS_PUNC, 'illegal:' . _("Filter"));
ossim_valid($key, OSS_NULLABLE, OSS_TEXT, OSS_PUNC_EXT, 'illegal:' . _("Key"));
ossim_valid($page, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("Page"));

if (ossim_error()) {
    die(ossim_error());
}

if ( $filter == "undefined" ) 
	$filter = "";

if ( $page == "" || $page<=0 ) 
	$page = 1;

$maxresults = 200;
$to         = $page * $maxresults;
$from       = $to - $maxresults;
$nextpage   = $page + 1;

$cachefile = "/var/ossim/sessions/".$_SESSION["_user"]."_hostgroup_".base64_encode($key.$filter)."_$page.json";

if (file_exists($cachefile)) {
    readfile($cachefile);
    exit;
}

$buffer = "";

$db   = new ossim_db();
$conn = $db->connect();

$ossim_hosts = $all_hosts = $filterhosts = array();
$total_hosts = 0;
$ossim_nets  = array();

if ($host_list = Host::get_list($conn, "", "ORDER BY hostname")) 
	
	foreach($host_list as $host) 
	{
		$hname = Util::utf8entities($host->get_hostname());
		
		if ($filter == "" || ($filter != "" && (preg_match("/$filter/i", $host->get_ip()) || preg_match("/$filter/i", $hname)))) 
		{
			$hip = $host->get_ip();
			$ossim_hosts[$hip] = (trim($hname) != "") ? $hname : $hip;
			$cclas = preg_replace("/(\d+\.)(\d+\.)(\d+)\.\d+/", "\\1\\2\\3", $hip);
			$all_hosts[$cclas][] = $hip;
			$total_hosts++;
		}	
	}
	
	uasort($all_hosts, 'cmpf');
	
if ($key == "os") 
{
    if ($hg_list = Host_os::get_os_list($conn, $ossim_hosts, $filter)) 
	{
        $j         = 0;
		$length_os = 25;
        
		uasort($hg_list, 'cmpf');
        
		$buffer .= "[";
		foreach($hg_list as $os => $hg) 
		{
            if($j>=$from && $j<$to) 
			{
                $html = "";
                $pix  = Host_os::get_os_pixmap_nodb($os, '../../pixmaps/', true);
                $pix  = ( $pix == "") ? "../../pixmaps/theme/host_group.png" : $pix;
                
				$title   = ( strlen($os) > $length_os ) ? substr($os, 0, $length_os)."..." : $os;
				$title   = Util::htmlentities($title)." <font style=\"font-weight:normal;font-size:80%\">(".count($hg)." "._("hosts").")</font>";	
				$tooltip = $os;
				
				$li = "key:'os_$os', url:'OS:$os', icon:'$pix', isLazy:true, title:'$title', tooltip:'$tooltip'";
                
				$buffer .= (($j>$from) ? "," : "") . "{ $li }\n";
            }
            $j++;
        }
        if ($j>$to) 
		{
            $li      = "key:'os', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_os.png', title:'"._("next")." $maxresults "._("OS")."'";
            $buffer .= ",{ $li }\n";
        }
        
		$buffer .= "]";
    }
}
else if (preg_match("/os_(.*)/",$key,$found)) 
{
    if ($hg_list = Host_os::get_os_list($conn, $ossim_hosts, $filter)) 
	{
       
        $html      = "";
        $k         = 0;
		$length_hn = 20;
		
		$buffer .= "[";
		foreach($hg_list[$found[1]] as $ip => $host_name) 
		{
            if($k>=$from && $k<$to) 
			{
               	$host_key   = utf8_encode($key.$k);
				$host_name  = ( $host_name == $ip || $host_name == '' ) ? "" : utf8_encode($host_name);
						   
				$aux_hname  = ( strlen($host_name) > $length_hn ) ? substr($host_name, 0, $length_hn)."..." : $host_name;
								
				$title      = ( $host_name == "" ) ? $ip : "$ip <font style=\"font-size:80%\">(".Util::htmlentities($aux_hname).")</font>";
				$tooltip    = ( $host_name == "" ) ? $ip : $ip." (".Util::htmlentities($host_name).")";
											
                $html      .= "{ key:'$host_key', url:'$ip', icon:'../../pixmaps/theme/host.png', title:'$title', tooltip:'$tooltip'},\n";
            }
            $k++;
        }
        
		if($k>$to) {
            $html.= "{ key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host.png', title:'"._("next")." $maxresults "._("hosts")."'} ";
        }
		
        if ($html != "") 
			$buffer .= preg_replace("/,$/", "", $html);
        
		$buffer .= "]";
    }
}
else if ($key == "ports") 
{
    if ($hg_list = Host_services::get_port_protocol_list($conn, $ossim_hosts, $filter)) 
	{
        uasort($hg_list, 'cmpf');
        
		$j = 0;
		
		$buffer .= "[";
        foreach($hg_list as $pp => $hg) 
		{
            if($j>=$from && $j<$to) 
			{
                $li      = "key:'port_$pp', url:'PORT:$pp', icon:'../../pixmaps/theme/ports.png', isLazy:true, title:'$pp <font style=\"font-weight:normal;font-size:80%\">(" . count($hg) . " "._("hosts").")</font>'";
                $buffer .= (($j>$from) ? "," : "") . "{ $li }\n";
            }
            $j++;
        }
        
		if ($j>$to) 
		{
            $li      = "key:'ports', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/ports.png', title:'"._("next")." $maxresults "._("ports")."'";
            $buffer .= ",{ $li }\n";
        }
        
		$buffer .= "]";
    }
}
else if (preg_match("/port_(.*)/",$key,$found)) 
{
     if ($hg_list = Host_services::get_port_protocol_list($conn, $ossim_hosts, $filter)) 
	 {
        
        $html        = "";
        $k           = 0;
		$length_hn   = 25;
		
		$buffer .= "[";
        foreach($hg_list[$found[1]] as $ip => $host_name) 
		{
            if($k>=$from && $k<$to) 
			{
               	$host_key   = utf8_encode($key.$k);
				$host_name  = ( $host_name == $ip || $host_name == '' ) ? "" : utf8_encode($host_name);
						   
				$aux_hname  = ( strlen($host_name) > $length_hn ) ? substr($host_name, 0, $length_hn)."..." : $host_name;
								
				$title      = ( $host_name == "" ) ? $ip : "$ip <font style=\"font-size:80%\">(".Util::htmlentities($aux_hname).")</font>";
				$tooltip    = ( $host_name == "" ) ? $ip : $ip." (".Util::htmlentities($host_name).")";
												
				$html      .= "{ key:'$host_key', url:'$ip', icon:'../../pixmaps/theme/host.png', title:'$title', tooltip:'$tooltip' },\n";
            }
            $k++;
        }
        
		if($k>$to) {
            $html.= "{ key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host.png', title:'"._("next")." $maxresults "._("hosts")."'} ";
        }
        
		if ($html != "") 
			$buffer .= preg_replace("/,$/", "", $html);
        
		$buffer .= "]";
    }
}
else if ($key == "macs") 
{
    if ($hg_list = Host_mac::get_mac_vendor_list($conn, $ossim_hosts, $filter)) 
	{
        $j          = 0;
		$length_mac = 30;
        uasort($hg_list, 'cmpf');
		
		$buffer .= "[";
        foreach($hg_list as $mv => $hg) 
		{
            if($j>=$from && $j<$to) 
			{
                $macv = preg_replace("/(..:..:..)-(.*)/", "\\1-<font style=\"font-weight:normal;font-style:italic;font-size:80%\">\\2</font>", $mv);
                $macv = str_replace("'","\'",$macv);
                $mv   = str_replace("'","\'",$mv);
				
				$mac_key = 'mac_'.$mv;
				$mac_url = 'MAC:'.$mv;
				
				$title   = ( strlen($macv) > $length_mac ) ? substr($macv, 0, $length_mac)."..." : $macv;
				$title   = Util::htmlentities($title)." <font style=\"font-weight:normal;font-size:80%\">(".count($hg)." "._("hosts").")</font>";	
				$tooltip = $macv;
								
                $li   = "key:'$mac_key', url:'$mac_url', icon:'../../pixmaps/theme/mac.png', isLazy:true, title:'$title', tooltip:'$tooltip'\n";
                $buffer .= (($j>$from) ? "," : "") . "{ $li }\n";
            }
            
			$j++;
        }
        
		if ($j>$to) 
		{
            $li      = "key:'macs', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/mac.png', title:'"._("next")." $maxresults "._("MAC/Vendor")."'";
            $buffer .= ",{ $li }\n";
        }
        
		$buffer .= "]";
    }
}
else if (preg_match("/mac_(.*)/",$key,$found)) 
{
    if ($hg_list = Host_mac::get_mac_vendor_list($conn, $ossim_hosts, $filter)) 
	{
        $k         = 0;
        $html      = "";
		$length_hn = 20;
       
        uasort($hg_list, 'cmpf');
		$buffer .= "[";
		 
        foreach($hg_list[$found[1]] as $ip => $host_name) 
		{
            if( $k>=$from && $k<$to ) 
			{
               	$host_key   = utf8_encode($key.$k);
				$host_name  = ( $host_name == $ip || $host_name == '' ) ? "" : utf8_encode($host_name);
						   
				$aux_hname  = ( strlen($host_name) > $length_hn ) ? substr($host_name, 0, $length_hn)."..." : $host_name;
								
				$title      = ( $host_name == "" ) ? $ip : "$ip <font style=\"font-size:80%\">(".Util::htmlentities($aux_hname).")</font>";
				$tooltip    = ( $host_name == "" ) ? $ip : $ip." (".Util::htmlentities($host_name).")";
				                
				$html.= "{ key:'$host_key', url:'$ip', icon:'../../pixmaps/theme/host.png', title:'$title', tooltip:'$tooltip'},\n";
            }
            $k++;
        }
        
		if($k>$to) {
            $html.= "{ key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host.png', title:'"._("next")." $maxresults "._("hosts")."'} ";
        }
		
        if ($html != "") 
			$buffer .= preg_replace("/,$/", "", $html);
        
		$buffer .= "]";
    }
}
else if ($key=="all")
{
    
    $j = 0;
	
	$buffer .= "[";
    foreach($all_hosts as $cclass => $hg) 
	{
        if ($j>=$from && $j<$to) 
		{
            $title   = "$cclass <font style=\"font-weight:normal;font-size:80%\">(".count($hg)." "._("hosts").")</font>";
			$li      = "key:'all_$cclass', isLazy:true, icon:'../../pixmaps/theme/host_add.png', title:'$title'\n";
            $buffer .= (($j > $from) ? "," : "") . "{ $li }\n";
        }
        $j++;
    }
    
	if ($j>$to) 
	{
        $li = "key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_add.png', title:'"._("next")." $maxresults "._("c-class")."'";
        $buffer .= ",{ $li }\n";
    }
    
	$buffer .= "]";
}
else if (preg_match("/all_(.*)/",$key,$found)) 
{
	$j         = 1;
	$i         = 0;
	$length_hn = 20;
	
	$buffer .= "[";
	foreach($all_hosts as $cclass => $hg) 
	{
		if ($found[1]==$cclass) 
		{
			foreach($hg as $ip) 
			{
				if($i>=$from && $i<$to) 
				{
					$host_key   = utf8_encode($key.$j);
					$host_name  = ( $ossim_hosts[$ip] == $ip ) ? "": utf8_encode($ossim_hosts[$ip]);
							   
					$aux_hname  = ( strlen($host_name) > $length_hn ) ? substr($host_name, 0, $length_hn)."..." : $host_name;
									
					$title      = ( $host_name == "" ) ? $ip : "$ip <font style=\"font-size:80%\">(".Util::htmlentities($aux_hname).")</font>";
					$tooltip    = ( $host_name == "" ) ? $ip : $ip." (".Util::htmlentities($host_name).")";
															
					$html      .= "{ key:'$host_key', url:'$ip', icon:'../../pixmaps/theme/host.png', title:'$title', tooltip:'$tooltip' },\n";
				}
				$i++;
			}
			$j++;
		}
	}
	
	if ($i>$to) {
		$html .= "{ key:'all', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_group.png', title:'"._("next")." $maxresults "._("hosts")."' },";
	}
	
	if ($html != "") 
		$buffer .= preg_replace("/,$/", "", $html);
	
	$buffer .= "]";

}
else if ($key!="all") {
    $buffer .= "[ {title:'"._("Assets by Property")."', isFolder: true, key:'key1', icon:'../../pixmaps/theme/any.png', expand:true, children:[\n";
    $buffer .= "{ key:'os', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_os.png', title:'"._("OS")."' },\n";
    $buffer .= "{ key:'ports', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/ports.png', title:'"._("Ports")."' },\n";
    $buffer .= "{ key:'macs', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/mac.png', title:'"._("MAC/Vendor")."' },\n";
    $buffer .= "{ key:'all', page:'', isLazy:true, icon:'../../pixmaps/theme/host_group.png', title:'"._("All Hosts")." <font style=\"font-weight:normal;font-size:80%\">(" . $total_hosts . " "._("hosts").")</font>' }\n";
    $buffer .= "] } ]";
}

if ( $buffer == "" || $buffer == "[]" )
    $buffer = "[{title:'"._("No Assets found")."', noLink:true}]";
    
echo $buffer;
$db->close($conn);

error_reporting(0);
$f = fopen($cachefile,"w");
fputs($f,$buffer);
fclose($f);
?>
