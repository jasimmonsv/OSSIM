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
* Classes list:
*/
set_time_limit(300);
require_once 'classes/Security.inc';
require_once ('classes/Session.inc');
Session::logcheck("MenuIntelligence", "PolicyPolicy");

$filter = GET('filter');
$filter = (mb_detect_encoding($filter." ",'UTF-8,ISO-8859-1') == 'UTF-8') ? Util::utf8entities($filter) : $filter;

$key  = GET('key');
$page = intval(GET('page'));

ossim_valid($filter, OSS_NULLABLE, OSS_ALPHA, OSS_DIGIT, OSS_PUNC, 'illegal:' . _("Filter"));
ossim_valid($key, OSS_NULLABLE, OSS_TEXT, OSS_PUNC, 'illegal:' . _("Key"));
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

$length_name  = 30;

$cachefile = "/var/ossim/sessions/".$_SESSION["_user"]."_policy_".base64_encode($key.$filter)."_$page.json";
if (file_exists($cachefile)) {
    readfile($cachefile);
    exit;
}

require_once ('classes/Host.inc');
require_once ('classes/Host_group.inc');
require_once ('classes/Net.inc');
require_once ('classes/Net_group.inc');
require_once ('ossim_db.inc');
$filter = str_replace ( "/" , "\/" , $filter);

$db   = new ossim_db();
$conn = $db->connect();

$ossim_hosts      = array();
$total_hosts      = 0;
$ossim_nets       = array();
$all_cclass_hosts = array();
$buffer           = "";

if ($key=="" || preg_match("/^(all|hostgroup)/",$key)) 
{
	if ( $host_list = Host::get_list($conn, "", "ORDER BY hostname") )
	{
		foreach($host_list as $host) 
		{
			if ($filter == "" || ($filter != "" && (preg_match("/$filter/i", $host->get_ip()) || preg_match("/$filter/i", $host->get_hostname())))) 
			{
				$ossim_hosts[$host->get_ip() ] = $host->get_hostname();
				$cclass = preg_replace("/(\d+\.)(\d+\.)(\d+)\.\d+/", "\\1\\2\\3", $host->get_ip());
				$all_cclass_hosts[$cclass][] = $host->get_ip();
				$total_hosts++;
			}
		}
	}
}

if ( $key == "hostgroup" ) 
{
	if ( $filter == "" ) 
	    $wherehg = "";
	else if( preg_match("/\d+.\d+/", $filter) )
	    $wherehg = " AND r.host_ip like '%$filter%'";
	else
	    $wherehg = " AND g.name like '%$filter%'";
	
	$hg_list = Host_group::get_list($conn, $wherehg);
    	
	if (count($hg_list)>0) 
	{
        $j = 0;
		
		$buffer .= "[";
        foreach($hg_list as $hg) 
		{
            if($j>=$from && $j<$to) 
			{
                $hg_name  = $hg->get_name();
				$hg_title = utf8_encode($hg_name);
				$hg_url   = "HOST_GROUP:".$hg_title;
				
				$hg_key   = "hostgroup_".base64_encode($hg_name);
				
				$title    = ( strlen($hg_name) > $length_name ) ? substr($hg_name, 0, $length_name)."..." : $hg_name;	
				$title    = Util::htmlentities(utf8_encode($title));
				$tooltip  = Util::htmlentities($hg_title);
								
				$li       = "key:'$hg_key', isLazy:true , url:'$hg_url', icon:'../../pixmaps/theme/host_group.png', title:'$title', tooltip:'$tooltip'\n";
                
				$buffer  .= (($j > $from) ? "," : "") . "{ $li }\n";
            }
            
			$j++;
        }
        
		if ($j>$to) 
		{
            $li      = "key:'hostgroup', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_group.png', title:'"._("next")." $maxresults "._("host group")."'";
            $buffer .= ",{ $li }\n";
        }
		
        $buffer .= "]";
    }
}
else if (preg_match("/hostgroup_(.*)/",$key,$found)) 
{
    if ($hg_hosts = Host_group::get_hosts($conn, base64_decode($found[1]))) 
	{
        $k         = 0;
        $html      = "";
		$length_hn = 30;
		
        $buffer .= "[";
		foreach($hg_hosts as $hosts) 
		{
            if ($k>=$from && $k<$to) 
			{   
				$ip         = $hosts->get_host_ip();
				$hname      = ( $ossim_hosts[$ip] != "" ) ? $ossim_hosts[$ip] : '';
				$aux_hname  = ( strlen($host_name) > $length_hn ) ? substr($hname, 0, $length_hn)."..." : $hname;
				
				//Filter
				if ( $filter != '' && preg_match("/$filter/",$ip) ) 
				{
					if ( $hname != "" )
						$title = "<strong>$ip</strong> <font style=\"font-size:80%\">(" . Util::htmlentities(utf8_encode($aux_hname)) . ")</font>";
					else
						$title = "<strong>$ip</strong>";
				}
				else
					$title  = ( $hname != "" ) ? "$ip <font style=\"font-size:80%\">(" . Util::htmlentities(utf8_encode($aux_hname)) . ")</font>" : $ip;
				
				$tooltip = ( $hname != "" ) ? $ip." (".$hname.")" : $ip;
				
                $html.= "{ key:'$key.$k', url:'HOST:$ip', icon:'../../pixmaps/theme/host.png', title:'$title', tooltip:'$tooltip' },\n";
            }
            $k++;
        }
        
		if ($html != "") 
			$buffer .= preg_replace("/,$/", "", $html);
        
		if ($k>$to) 
		{
            $li      = "key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_group.png', title:'"._("next")." $maxresults "._("hosts")."'";
            $buffer .= ",{ $li }\n";
        }
        $buffer .= "]";
    }
}
else if ($key == "net") 
{

	$wherenet = ($filter!="") ? "(ips like '%$filter%' OR name like '%$filter%')" : "";
	$net_list = Net::get_list($conn, $wherenet, "ORDER BY name");

    if (count($net_list)>0) 
	{
        $j = 0;
        
		$buffer .= "[";
		foreach($net_list as $net) 
		{
            if ($j>=$from && $j<$to) 
			{
              	$net_name  = $net->get_name();
				$net_title = Util::htmlentities(utf8_encode($net_name));
				
				$net_key   = "net_".base64_encode($net_name);
				$net_url   = "NETWORK:".$net_title;	
				
				$ips_data  = $net->get_ips();				
				$ips       = "<font style=\"font-size:80%\">(".$ips_data.")</font>";
				
				
        		$title     = ( strlen($net_name) > $length_name ) ? substr($net_name, 0, $length_name)."..." : $net_name;	
				$title     = Util::htmlentities(utf8_encode($title))." ".$ips;
				
				$tooltip   = $net_title." (".$ips_data.")";
								
				$li = "key:'$net_key', isLazy:true, url:'$net_url', icon:'../../pixmaps/theme/net.png', title:'$title', tooltip:'$tooltip'\n";
                $buffer .= (($j > $from) ? "," : "") . "{ $li }\n";
            }
            $j++;
        }
        
		if ($j>$to) 
		{
            $li      = "key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'"._("next")." $maxresults "._("nets")."'";
            $buffer .= ",{ $li }\n";
        }
        
		$buffer .= "]";
    }
}
else if (preg_match("/net_(.*)/",$key,$found))
{
	$hostin = array();
	if ($net_list1 = Net::get_list($conn, "name='".base64_decode($found[1])."'")) 
	{
		require_once("classes/CIDR.inc");
		foreach($net_list1 as $net) 
		{
		    $net_name = $net->get_name();
		    $nets_ips = explode(",",$net->get_ips());
		    foreach ($nets_ips as $net_ips) 
			{
			    $net_range     = CIDR::expand_CIDR($net_ips,"SHORT","IP");
				$host_list_aux = Host::get_list($conn,"WHERE inet_aton(ip)>=inet_aton('".$net_range[0]."') && inet_aton(ip)<=inet_aton('".$net_range[1]."')");
				
				foreach ($host_list_aux as $h) {
					$hostin[$h->get_ip()] = $h->get_hostname();
				}
			}
		}
	}
    $k         = 0;
    $html      = "";
	$length_hn = 30;
	
	$buffer .= "[";
	foreach($hostin as $ip => $host_name) 
	{
    	if ($k>=$from && $k<$to) 
		{
            $host_name  = utf8_encode($host_name);
			$host_key   = utf8_encode($key.$k);
			$host_url   = "HOST:".$ip;
			
			$hname      = ( $ip == $host_name ) ? '' : $host_name;
			
			$aux_hname  = ( strlen($hname) > $length_hn ) ? substr($hname, 0, $length_hn)."..." : $hname;
	
			$title      = ( $hname == '' ) ? $ip : "$ip <font style=\"font-size:80%\">(" . Util::htmlentities(utf8_encode($aux_hname)) . ")</font>";
			$tooltip    = ( $hname == '' ) ? $ip : $ip." (".$hname.")";
						
			$html.= "{ key:'$host_key', url:'$host_url', icon:'../../pixmaps/theme/host.png', title:'$title', tooltip:'$tooltip' },\n";
        }
        
		$k++;
    }
    if ($html != "") $buffer .= preg_replace("/,$/", "", $html);
    
	if ($k>$to) 
	{
        $li      = "key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host.png', title:'"._("next")." $maxresults "._("hosts")."'";
        $buffer .= ",{ $li }\n";
    }
    
	$buffer .= "]";
}
else if ($key=="netgroup") 
{
    $whereng = ( $filter == "" ) ? "" : "name like '%$filter%'";
    
    if ($net_group_list = Net_group::get_list($conn, $whereng)) 
	{
        $j = 0;
		 
		$buffer .= "[";
        foreach($net_group_list as $net_group) 
		{
            if ($j>=$from && $j<$to) 
			{
               	$ng_name  = $net_group->get_name();
				$ng_title = Util::htmlentities(utf8_encode($ng_name));
				
				$ng_key   = "netgroup_".base64_encode($ng_name);
				$ng_url   = "NETWORK_GROUP:".$ng_title;
				
				$title    = ( strlen($ng_name) > $length_name ) ? substr($ng_name, 0, $length_name)."..." : $ng_name;	
				$title    = Util::htmlentities(utf8_encode($title));
				$tooltip  = $ng_title;
			   		   
                $li = "key:'$ng_key', isLazy:true , url:'$ng_url', icon:'../../pixmaps/theme/net_group.png', title:'$title', tooltip:'$tooltip'\n";
                $buffer .= (($j > $from) ? "," : "") . "{ $li }\n";
            }
            $j++;
        }
		
        if ($j>$to) 
		{
            $li = "key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net_group.png', title:'"._("next")." $maxresults "._("net group")."'";
            $buffer .= ",{ $li }\n";
        }
		
        $buffer .= "]";
    }
}
else if (preg_match("/netgroup_(.*)/",$key,$found))
{
    
    $html = "";
    $k    = 1;
    $j    = 0;
	
	$nets = Net_group::get_networks($conn, base64_decode($found[1]));
	
	$buffer .= "[";
    foreach($nets as $net) 
	{
        if ($j>=$from && $j<$to) 
		{
            $net_name = $net->get_net_name();
			
			$net_key   = utf8_encode($key.$k);
			$ips_data  = $net->get_net_ips($conn);				
			$ips       = "<font style=\"font-size:80%\">(".$ips_data.")</font>";
			
			$net_title = Util::htmlentities(utf8_encode($net_name));
			$title     = ( strlen($net_name) > $length_name ) ? substr($net_name, 0, $length_name)."..." : $net_name;	
			$title     = Util::htmlentities(utf8_encode($title))." ".$ips;
				
			$tooltip   = $net_title." (".$ips_data.")";
						
			$html.= "{ key:'$net_key', url:'NETWORK:$net_title', icon:'../../pixmaps/theme/net.png', title:'$title', tooltip:'$tooltip' },\n";
            $k++;
        }
        $j++;
    }
    
	if ($html != "") 
		$buffer .= preg_replace("/,$/", "", $html);
    
	if ($j>$to) 
	{
        $li      = "key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'"._("next")." $maxresults "._("net")."'";
        $buffer .= ",{ $li }\n";
    }
    
	$buffer .= "]";
}
else if ($key=="all")
{
    $j = 0;
	
	$buffer .= "[";
    foreach($all_cclass_hosts as $cclass => $hg) 
	{
        if ($j>=$from && $j<$to) 
		{
            $title   = "$cclass <font style=\"font-weight:normal;font-size:80%\">(" . count($hg) . " "._("hosts").")</font>";
			$li      = "key:'all_$cclass', isLazy:true, url:'CCLASS:$cclass', icon:'../../pixmaps/theme/host_add.png', title:'$title'\n";
            $buffer .= (($j > $from) ? "," : "") . "{ $li }\n";
        }
        $j++;
    }
    
	if ($j>$to) 
	{
        $li      = "key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_add.png', title:'"._("next")." $maxresults "._("c-class")."'";
        $buffer .= ",{ $li }\n";
    }
    $buffer .= "]";
}

else if (preg_match("/all_(.*)/",$key,$found))
{
       
    $j         = 1;
    $i 		   = 0;
	$html      = "";
	$length_hn = 30;
	
	$buffer .= "[";
    foreach($all_cclass_hosts as $cclass => $hg) 
	{
		if ($found[1]==$cclass) 
		{
			foreach($hg as $ip) 
			{
				if($i>=$from && $i<$to) 
				{
					$hname      = ( $ip == $ossim_hosts[$ip] ) ? "" : $ossim_hosts[$ip];
					$host_key   = utf8_encode($key.$j);
					$host_url   = "HOST:".$ip;
									
					$aux_hname  = ( strlen($hname) > $length_hn ) ? substr($hname, 0, $length_hn)."..." : $hname;
			
					$title      = ( $hname == '' ) ? $ip : "$ip <font style=\"font-size:80%\">(" . Util::htmlentities(utf8_encode($aux_hname)) . ")</font>";
					$tooltip    = ( $hname == '' ) ? $ip : $ip." (".$hname.")";
											
					$html.= "{ key:'$host_key', url:'$host_url', icon:'../../pixmaps/theme/host.png', title:'$title', tooltip:'$tooltip' },\n";
				}
				$i++;
			}
			$j++;
		}
	}
    
	if ($i>$to) {
        $html .= "{ key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_group.png', title:'"._("next")." $maxresults "._("hosts")."' },";
    }
    
	if ($html != "") 
		$buffer .= preg_replace("/,$/", "", $html);
    
	$buffer .= "]";
}
else if ($key!="all") 
{
    $buffer .= "[ {title: '"._("ANY")."', key:'key1', url:'ANY', icon:'../../pixmaps/theme/any.png', expand:true, children:[\n";
    $buffer .= "{ key:'hostgroup', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_group.png', title:'"._("Host Groups")."'},\n";
    $buffer .= "{ key:'net', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'"._("Networks")."'},\n";
    $buffer .= "{ key:'netgroup', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net_group.png', title:'"._("Network Groups")."'},\n";
    $buffer .= "{ key:'all', page:'', isFolder:true, isLazy:true , icon:'../../pixmaps/theme/host.png', title:'"._("All Hosts")." <font style=\"font-weight:normal;font-size:80%;\">(" . $total_hosts . " "._("hosts").")</font>'}\n";
    $buffer .= "] } ]";
}

if ( $buffer == "" || $buffer == "[]" )
    $buffer = "[{title:'"._("No Assets found")."', noLink:true}]";

echo $buffer;
error_reporting(0);
$f = fopen($cachefile,"w");
fputs($f,$buffer);
fclose($f);

?>
