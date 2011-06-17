<?php
/*****************************************************************************
*
*    License:
*
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
ob_implicit_flush();
ini_set("max_execution_time","300");

require_once ('classes/Session.inc');
require_once ('classes/Sensor.inc');
require_once ('classes/Net_group.inc');
require_once ('classes/Util.inc');
require_once ('ossim_db.inc');

$key  = GET('key');
$page = intval(GET('page')); 


ossim_valid($key,  OSS_NULLABLE, OSS_TEXT,  'illegal:' . _("Key"));
ossim_valid($page, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("Page")); 

if (ossim_error()) {
    die(ossim_error());
}

if ($page == "" || $page<=0) 
	$page = 1;
	
$maxresults = 200;
$to         = $page * $maxresults;
$from       = $to - $maxresults;
$nextpage   = $page + 1;

$withusers          = ( $_SESSION["_with_users"] == 1 )           ? true : false;
$withsiemcomponents = ( $_SESSION["_with_siem_components"]==1 )   ? true : false;

$length_name        = 50;
$h                  = '570';

/* Connect to db */
$db   = new ossim_db();
$conn = $db->connect();

/* Load hosts and nets */
$ossim_hosts      = array();
$total_hosts      = 0;
$ossim_nets       = array();
$all_cclass_hosts = array();
$buffer           = "";
$where_host       = "";

if(preg_match("/host_(.*)/",$key,$found)) {
    $where_host = ", host_sensor_reference hsr WHERE h.ip=hsr.host_ip AND hsr.sensor_name='".$found[1]."'";
}
else if(preg_match("/all_(.*)/",$key,$found)) 
{
    $aux = explode("_",$found[1],2);
    if(count($aux)==2) {
        $where_host = ", host_sensor_reference hsr WHERE h.ip=hsr.host_ip AND hsr.sensor_name='".$aux[1]."' AND h.ip LIKE '".$aux[0].".%'";
        $key="all_".$aux[0];
    }
}

if ( $key == "" || preg_match("/^(all|host|hostgroup)/",$key)) 
{
	if ($host_list = Host::get_list($conn, $where_host, "ORDER BY h.hostname"))
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

/* All assets*/

if ($key == "hostgroup") 
{
	$hg_list = Host_group::get_list($conn, "", "ORDER BY name");
    if (count($hg_list)>0) {
        $buffer .= "[";
        $j = 0;
        foreach($hg_list as $hg) 
		{
            if($j>=$from && $j<$to) 
			{
                $hg_name  = $hg->get_name();
				$hg_key   = "hostgroup_".base64_encode($hg_name);
				$hg_title = Util::htmlentities(utf8_encode($hg_name));
				
				$title    = ( strlen($hg_name) > $length_name ) ? substr($hg_name, 0, $length_name)."..." : $hg_name;	
				$title    = Util::htmlentities(utf8_encode($title));
                $tooltip  = $hg_title;
				
				$h        = '585'; 
				
				$li = "key:'$hg_key', isLazy:true , h:'$h', url:'../host/newhostgroupform.php?name=".urlencode($hg_name)."', icon:'../../pixmaps/theme/host_group.png', title:'$title', tooltip:'$tooltip' \n";
                $buffer .= (($j > $from) ? "," : "") . "{ $li }\n";
            }
            $j++;
        }
		
        if ($j>$to) 
		{
            $li 	 = "key:'hostgroup', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_group.png', title:'"._("next")." $maxresults "._("host group")."'";
            $buffer .= ",{ $li }\n";
        }
        $buffer .= "]";
    }
    
    if ( $buffer =="" || $buffer == "[]" )
        $buffer = "[{title:'"._("No Host Groups Found")."', noLink:true}]";
        
    echo $buffer;
}
else if (preg_match("/snet_(.*)/",$key,$found)) 
{
    $li = array();
    
    $sensor_assets = Sensor::get_assets($conn, $found[1]);
    
	$buffer .= "[";
    foreach($sensor_assets["net"] as $net_name => $net_ips) 
	{
        $snet_key   = "net_".base64_encode($net_name);
		$ips        = "<font style=\"font-size:80%\">(".$net_ips.")</font>";
		$snet_title = Util::htmlentities(utf8_encode($net_name));
		
		$title      = ( strlen($net_name) > $length_name ) ? substr($net_name, 0, $length_name)."..." : $net_name;	
		$title      = Util::htmlentities(utf8_encode($title))." ".$ips;
		
		$tooltip    = $snet_title." (".$net_ips.")";
		$li[]       = "{ key:'$snet_key', h:'$h', url:'../net/newnetform.php?name=$net_name', isLazy:true, icon:'../../pixmaps/theme/host.png', title:'$title', tooltip:'$tooltip' }";
    }
    
    $buffer .= implode(",", $li);
    $buffer .= "]";

    if ( $buffer == "" || $buffer == "[]" )
        $buffer = "[{title:'"._("No Networks Found")."', noLink:true}]";
    
	echo $buffer;
}
else if (preg_match("/shostgroup_(.*)/",$key,$found)) 
{
    $li = array();
    
    $sensor_assets = Sensor::get_assets($conn, $found[1]);
    
	$buffer .= "[";
    foreach($sensor_assets["hgroup"] as $hg_name => $v) 
	{
        $hg_key   = "hostgroup_".base64_encode($hg_name);
        $hg_title = Util::htmlentities(utf8_encode($hg_name));
				
		$title    = ( strlen($hg_name) > $length_name ) ? substr($hg_name, 0, $length_name)."..." : $hg_name;
		$title    = Util::htmlentities(utf8_encode($title));
		$tooltip  = $hg_title;
		$h        = '585';
		
        $li[]= "{ key:'$hg_key', h:'$h', isLazy:true , url:'../host/newhostgroupform.php?name=".urlencode($hg_name)."', icon:'../../pixmaps/theme/host_group.png', title:'$title', tooltip:'$tooltip' }";
    }
    
    $buffer .= implode(",", $li);
    $buffer .= "]";

    if ( $buffer == "" || $buffer == "[]" )
        $buffer = "[{title:'"._("No Host Groups Found")."', noLink:true}]";
    
	echo $buffer;
}
else if (preg_match("/snetgroup_(.*)/",$key,$found)) 
{
    $li = array();
    
    $sensor_assets = Sensor::get_assets($conn, $found[1]);
    
	$buffer .= "[";
    foreach($sensor_assets["ngroup"] as $net_group_name => $v) 
	{
        $ng_key   = base64_encode($net_group_name);
		$ng_title = Util::htmlentities(utf8_encode($net_group_name));
        
		$title    = ( strlen($net_group_name) > $length_name ) ? substr($net_group_name, 0, $length_name)."..." : $net_group_name;	
		$title    = Util::htmlentities(utf8_encode($title));
		$tooltip  = $ng_title;
		
		$h        = '585';
		
        $li[] = "{ key:'netgroup_$ng_key', h:'$h', isLazy:true , url:'../net/newnetgroupform.php?name=".urlencode($net_group_name)."', icon:'../../pixmaps/theme/net_group.png', title:'$title', tooltip:'$tooltip' }";
    }
    
    $buffer .= implode(",", $li);
    $buffer .= "]";

    if ( $buffer == "" || $buffer == "[]" )
        $buffer = "[{title:'"._("No Network Groups Found")."', noLink:true}]";
    echo $buffer;
}
else if (preg_match("/hostgroup_(.*)/",$key,$found)) 
{
    if ($hg_hosts = Host_group::get_hosts($conn, base64_decode($found[1]))) 
	{
        $k         = 0;
        $html      = "";
		$h         = '585';
		$length_hn = 50;
        
		$buffer .= "[";
        
		foreach($hg_hosts as $hosts) 
		{
            if ($k>=$from && $k<$to) 
			{ 
				$ip         = $hosts->get_host_ip();
				$host_key   = utf8_encode($key.$k);
				
				$host_name  = $ossim_hosts[$ip];
				$hname      = ( $host_name != '' ) ? utf8_encode($host_name) : "";
						   
				$aux_hname  = ( strlen($hname) > $length_hn ) ? substr($hname, 0, $length_hn)."..." : $hname;
								
				$title      = ( $host_name == "" ) ? $ip : "$ip <font style=\"font-size:80%\">(".Util::htmlentities($aux_hname).")</font>";
				$tooltip    = ( $host_name == "" ) ? $ip : $ip." (".Util::htmlentities($hname).")";
							
				$html   .= "{ key:'$host_key', h:'$h', url:'../host/modifyhostform.php?ip=$ip', icon:'../../pixmaps/theme/host.png', title:'$title', tooltip:'$tooltip' },\n";
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
   
    if ( $buffer == "" || $buffer == "[]" )
        $buffer = "[{title:'"._("No Hosts Found")."', noLink:true}]";
        
    echo $buffer;
}
else if ($key == "net") 
{
	$wherenet = ($filter!="") ? "ips like '%$filter%'" : "";
	$net_list = Net::get_list($conn, $wherenet);
    
    if (count($net_list)>0) 
	{
        $buffer .= "[";
        $j = 0;
        
		foreach($net_list as $net) 
		{
            if ($j>=$from && $j<$to) 
			{
                $net_name  = $net->get_name();
				$net_title = Util::htmlentities(utf8_encode($net_name));
				
				$net_key   = "net_".base64_encode($net_name);
				$ips_data  = $net->get_ips();
				$ips       = "<font style=\"font-size:80%\">(".$ips_data.")</font>";
								
        		$title     = ( strlen($net_name) > $length_name ) ? substr($net_name, 0, $length_name)."..." : $net_name;	
				$title     = Util::htmlentities(utf8_encode($title))." ".$ips;
				
				$tooltip   = $net_title." (".$ips_data.")";
															
				$li = "key:'$net_key', isLazy:true, h:'$h', url:'../net/newnetform.php?name=".urlencode($net_name)."', icon:'../../pixmaps/theme/net.png', title:'$title', tooltip:'$tooltip' \n";
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
	
    if ( $buffer == "" || $buffer == "[]" )
        $buffer = "[{title:'"._("No Networks Found")."', noLink:true}]";
    
	echo $buffer;
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
                foreach ($host_list_aux as $h) 
				{
                    $hostin[$h->get_ip()] = $h->get_hostname();
                }
            }
        }
    }
    
	$k    = 0;
	$html = "";
    
	$buffer .= "[";
    foreach($hostin as $ip => $host_name) 
	{
        if ($k>=$from && $k<$to) 
		{
            $host_key   = utf8_encode($key.$k);
			$hname      = ( $host_name == $ip ) ? "" : utf8_encode($host_name);
					   
			$aux_hname  = ( strlen($hname) > $length_name ) ? substr($hname, 0, $length_name)."..." : $hname;
								
			$title      = ( $hname == '' ) ? $ip : "$ip <font style=\"font-size:80%\">(".Util::htmlentities($aux_hname).")</font>";
			$tooltip    = ( $hname == '' ) ? $ip : $ip." (".Util::htmlentities($host_name).")";
								
			$h         = '580';			
			
			$html.= "{ key:'$host_key', h:'$h', url:'../host/modifyhostform.php?ip=$ip', icon:'../../pixmaps/theme/host.png', title:'$title', tooltip:'$tooltip' },\n";
        }
        $k++;
    }
	
    if ($html != "") 
		$buffer .= preg_replace("/,$/", "", $html);
    
	if ($k>$to) 
	{
        $li      = "key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host.png', title:'"._("next")." $maxresults "._("hosts")."'";
        $buffer .= ",{ $li }\n";
    }
    
	$buffer .= "]";

    if ( $buffer == "" || $buffer == "[]" )
        $buffer = "[{title:'"._("No Hosts Found")."', noLink:true}]";
    
	echo $buffer;
}
else if ($key=="netgroup")
{
    if ($net_group_list = Net_group::get_list($conn)) 
	{
        $j = 0;
		
		$buffer .= "[";
		foreach($net_group_list as $net_group)
		{
            if ($j>=$from && $j<$to) 
			{
                $ng_name  = $net_group->get_name();
				$ng_key   = "netgroup_".base64_encode($ng_name);
				$ng_title = Util::htmlentities(utf8_encode($ng_name));
				
				$title    = ( strlen($ng_name) > $length_name ) ? substr($ng_name, 0, $length_name)."..." : $ng_name;
				$title    = Util::htmlentities(utf8_encode($title));				
				$tooltip  = $ng_title;
								
                $li = "key:'$ng_key', isLazy:true , h:'$h', url:'../net/newnetgroupform.php?name=".urlencode($ng_name)."', icon:'../../pixmaps/theme/net_group.png', title:'$title', tooltip:'$tooltip'\n";
                $buffer .= (($j > $from) ? "," : "") . "{ $li }\n";
            }
            $j++;
        }
		
        if ($j>$to) 
		{
            $li      = "key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net_group.png', title:'"._("next")." $maxresults "._("net group")."'";
            $buffer .= ",{ $li }\n";
        }
        
		$buffer .= "]";
    }
    
    if ( $buffer == "" || $buffer == "[]" )
        $buffer = "[{title:'"._("No Network Groups Found")."', noLink:true}]";
    
	echo $buffer;
}
else if (preg_match("/netgroup_(.*)/",$key,$found))
{
   
    $html = "";
	$k = 1;
    $j = 0;
    
	$nets = Net_group::get_networks($conn, base64_decode($found[1]));

	$buffer .= "[";
    foreach($nets as $net) 
	{
        $net_name  = $net->get_net_name();
		$net_title = Util::htmlentities(utf8_encode($net_name));
		
		$ips_data  = $net->get_net_ips($conn);
		$ips       = "<font style=\"font-size:80%\">(".$ips_data.")</font>";
			
		$net_key   = utf8_encode($key.$k);
		
		$title     = ( strlen($net_name) > $length_name ) ? substr($net_name, 0, $length_name)."..." : $net_name;	
		$title     = Util::htmlentities(utf8_encode($title))." ".$ips;;
		
		$tooltip   = $net_title." (".$ips_data.")";;
		
        if ($j>=$from && $j<$to) 
		{
            $html.= "{ key:'$net_key', h:'$h', url:'../net/newnetform.php?name=".urlencode($net_name)."', icon:'../../pixmaps/theme/net.png', title:'$title', tooltip:'$tooltip' },\n";
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
    
    if ( $buffer == "" || $buffer == "[]" )
        $buffer = "[{title:'"._("No Networks Found")."', noLink:true}]";
    
	echo $buffer;
}
else if ($key=="all")
{
    $j = 0;
	
	$buffer .= "[";
    foreach($all_cclass_hosts as $cclass => $hg) 
	{
        if ($j>=$from && $j<$to) 
		{
            $title   = $cclass." <font style=\"font-weight:normal;font-size:80%\">(".count($hg)." "._("hosts").")</font>";
			$li      = "key:'all_$cclass', isLazy:true, icon:'../../pixmaps/theme/host_add.png', title:'$title'\n";
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

    if ( $buffer == "" || $buffer == "[]" )
        $buffer = "[{title:'"._("No Hosts Found")."', noLink:true}]";
    
	echo $buffer;
}

else if (preg_match("/all_(.*)/",$key,$found)) 
{
    $html = "";
    $j    = 1;
    $i    = 0;
    $h 	  = '580';
	
	$length_hn = 50;
	
	$buffer .= "[";
    foreach($all_cclass_hosts as $cclass => $hg) if ($found[1]==$cclass) 
	{
        
		foreach($hg as $ip) 
		{
			if ($i>=$from && $i<$to) 
			{
				$host_key   = utf8_encode($key.$j);
				$host_name  = ( $ossim_hosts[$ip] != '' ) ? utf8_encode($ossim_hosts[$ip]) : "";
					   
				$aux_hname  = ( strlen($host_name) > $length_hn ) ? substr($host_name, 0, $length_hn)."..." : $host_name;
								
				$title      = ( $host_name == '' ) ? $ip : "$ip <font style=\"font-size:80%\">(".Util::htmlentities($aux_hname).")</font>";
				$tooltip    = ( $host_name == '' ) ? $ip : $ip." (".Util::htmlentities($host_name).")";
												
				$html      .= "{ key:'$host_key', h:'$h', url:'../host/modifyhostform.php?ip=$ip', icon:'../../pixmaps/theme/host.png', title:'$title', tooltip:'$tooltip' },\n";
			}
            $i++;
        }
        $j++;
    }
	
    if ($i>$to) {
        $html .= "{ key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_group.png', title:'"._("next")." $maxresults "._("hosts")."' },";
    }
    
	if ($html != "") 
		$buffer .= preg_replace("/,$/", "", $html);
    
	$buffer .= "]";
    
    if ( $buffer == "" || $buffer == "[]" )
        $buffer = "[{title:'"._("No Hosts Found")."', noLink:true}]";
    
	echo $buffer;
}
else if(preg_match("/u_(.*)_netgroup/",$key,$found))
{
    $j = 0;
  	
	$netgroup_list = Net_group::get_list($conn);
	
	$buffer .= "[";
	foreach($netgroup_list as $netgroup) 
	{
		if (Session::groupAllowed($conn, $netgroup->get_name(), $found[1])) 
		{
			$ng_name   = $netgroup->get_name();
			$ng_title  = Util::htmlentities(utf8_encode($ng_name));
			
			$title     = ( strlen($ng_name) > $length_name ) ? substr($ng_name, 0, $length_name)."..." : $ng_name;	
			$title     = Util::htmlentities(utf8_encode($title));
			$tooltip   = $ng_title;
						
			$li = "h:'$h', url:'../net/newnetgroupform.php?name=".urlencode($ng_name)."', icon:'../../pixmaps/theme/net_group.png', title:'$title', tooltip:'$tooltip'\n";
			$buffer .= (($j > 0) ? "," : "") . "{ $li }";
			$j++;
		}
	}
    
	$buffer .= "]";
    if ( $buffer == "[]" )  
		$buffer = "[{title:'"._("No Network Groups Found")."', noLink:true}]";
    
	echo $buffer;
}
else if(preg_match("/u_(.*)_net/",$key,$found))
{
    $net_list     = Net::get_list($conn);
    $allowedNets  = Session::allowedNets($found[1]);
    $nets_allowed = array_fill_keys(explode(",",$allowedNets),1);
    
    $buffer .= "[";
    $j = 0;
			
	foreach($net_list as $net) 
	{
    	$cidrs = explode(",",$net->get_ips());
    	
		if ($allowedNets == "" || Acl::cidrs_allowed($cidrs,$nets_allowed)) 
		{
	        $net_name  = $net->get_name();
	        $net_title = Util::htmlentities(utf8_encode($net_name));
	        
			$ips_data  = $net->get_ips();
			$ips       = "<font style=\"font-size:80%\">(".$ips_data.")</font>";
	        
			$title     = ( strlen($net_name) > $length_name ) ? substr($net_name, 0, $length_name)."..." : $net_name;
			$title 	   = Util::htmlentities(utf8_encode($title))." ".$ips;
			$tooltip   = $net_title." (".$ips_data.")"; 
						
			$li        = "h:'$h', url:'../net/newnetform.php?name=".urlencode($net_name)."', icon:'../../pixmaps/theme/net.png', title:'$title', tooltip:'$tooltip'\n";
						
	        $buffer .= (($j > 0) ? "," : "") . "{ $li }";
	        $j++;
	    }
	}
	
    $buffer .= "]";
    
	if ( $buffer == "[]" )  
		$buffer = "[{title:'"._("No Networks Found")."', noLink:true}]";
    
	echo $buffer;
}
else if(preg_match("/u_(.*)_sensor/",$key,$found))
{
    $sensor_list     = Sensor::get_list($conn);
    $allowedSensors  = Session::allowedSensors($found[1]);
    $sensors_allowed = array_fill_keys(explode(",",$allowedSensors),1);
       
    $j = 0;
	
	$buffer .= "[";
    foreach($sensor_list as $sensor) 
	{
		if ($allowedSensors == "" || $sensors_allowed[$sensor->get_ip()]) 
		{
			$sensor_name = $sensor->get_name();
			$s_title     = Util::htmlentities(utf8_encode($sensor_name));
			
			$title     = ( strlen($sensor_name) > $length_name ) ? substr($sensor_name, 0, $length_name)."..." : $sensor_name;	
			$title     = Util::htmlentities(utf8_encode($title));
			$tooltip   = $s_title;
			
			$li = "h:'$h', url:'../sensor/interfaces.php?sensor=".$sensor->get_ip()."&name=".urlencode($sensor_name)."', icon:'../../pixmaps/theme/server.png', title:'$title', tooltip:'$tooltip'\n";
			$buffer .= (($j > 0) ? "," : "") . "{ $li }";
			$j++;
		}
	}
	
    $buffer .= "]";
    
	if ( $buffer == "[]" )    
		$buffer = "[{title:'"._("No Sensors Found")."', noLink:true}]";
    
	echo $buffer;
}
else if(preg_match("/u_(.*)/",$key,$found))
{
    echo "[";
    echo "{ key:'u_".$found[1]."_net', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'"._("Networks")."'},";
    echo "{ key:'u_".$found[1]."_sensor', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/server.png', title:'"._("Sensors")."'},";
    echo "{ key:'u_".$found[1]."_netgroup', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net_group.png', title:'"._("Network Groups")."'}";
    echo "]";
}
else if(preg_match("/e_(.*)_netgroup/",$key,$found))
{
    $entityPerms   = Acl::entityPerms($conn,$found[1]);
    $all           = count($entityPerms["assets"]);
    $nets_allowed  = array_keys($entityPerms["assets"]);
    $net_groups    = Net_group::get_list($conn);
    $netgroup_list = array();
	
    foreach($net_groups as $net_group) 
	{
        $allowed=0;
        $nets = $net_group->get_networks($conn, $net_group->get_name());
        
		foreach($nets as $net) 
		{
            $net_ips = explode(",",$net->get_net_ips($conn));
            if (!$all || Acl::cidrs_allowed($net_ips,$entityPerms["assets"])) 
				$allowed=1;
        }
		
        if ( $allowed ) 
			$netgroup_list[] = $net_group->get_name();
    }
    
    $buffer .= "[";
    $j = 0;
    
	foreach($netgroup_list as $netgroup_name) 
	{
    	$ng_title  = Util::htmlentities(utf8_encode($netgroup_name));
		
		$title     = ( strlen($netgroup_name) > $length_name ) ? substr($netgroup_name, 0, $length_name)."..." : $netgroup_name;	
		$title     = Util::htmlentities(utf8_encode($title));
		$tooltip   = $ng_title;
				
        $li      = "h:'$h', url:'../net/newnetgroupform.php?name=".urlencode($netgroup_name)."', icon:'../../pixmaps/theme/net_group.png', title:'$title', tooltip:'$tooltip'\n";
        $buffer .= (($j > 0) ? "," : "") . "{ $li }";
        $j++;
    }
    
	$buffer .= "]";
    
	if ( $buffer == "[]" )  
		$buffer = "[{title:'"._("No Network Groups Found")."', noLink:true}]";
    
	echo $buffer;
}
else if(preg_match("/e_(.*)_net$/",$key,$found))
{
	$entityPerms = Acl::entityPerms($conn,$found[1]);
	$all         = count($entityPerms["assets"]);
	$nets        = Net::get_list($conn, "", "ORDER BY name ASC", array_keys($entityPerms['sensors'])); //Net::get_all($conn);
   
    $html    = "";

    $p = 0;
	
	$buffer .= "[";
    foreach($nets as $net) 
	{
        $cidrs = explode(",",$net->get_ips());
        if (!$all || Acl::cidrs_allowed($cidrs,$entityPerms["assets"])) 
		{
            if($p>=$from && $p<$to) 
			{
                $net_name  = $net->get_name();
				$ips_data  = $net->get_ips();
				$ips       = "<font style=\"font-size:80%\">(".$ips_data.")</font>";
				$net_title = Util::htmlentities(utf8_encode($net_name));
				
        		$title     = ( strlen($net_name) > $length_name ) ? substr($net_name, 0, $length_name)."..." : $net_name;	
				$title 	   = Util::htmlentities(utf8_encode($title))." ".$ips;
				$tooltip   = $net_title." (".$ips_data.")";
								
                $html     .= "{h:'$h', url:'../net/newnetform.php?name=".urlencode($net->get_name())."', icon:'../../pixmaps/theme/net.png', title:'$title', tooltip:'$tooltip'},";
            }
            $p++;
		}
	}
    
	if ($p>$to) {
        $html.= "{ key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'"._("next")." $maxresults "._("nets")."' }";
    }
	    
	if ($html != "") 
		$buffer .= preg_replace("/,$/", "", $html); 
	
    $buffer .= "]";
    
	if ( $buffer == "[]" )  
		$buffer = "[{title:'"._("No Networks Found")."', noLink:true}]";
    
	echo $buffer;
}

else if(preg_match("/e_(.*)_sensor/",$key,$found))
{
    $entityPerms = Acl::entityPerms($conn,$found[1]);
    $all         = count($entityPerms["sensors"]);
    $sensors     = Sensor::get_all($conn);
    
    $j = 0;
	
	$buffer .= "[";
    foreach($sensors as $sensor) 
	{
		if (!$all || $entityPerms["sensors"][$sensor->get_ip()]) 
		{
			$sensor_name = $sensor->get_name();
			$s_title     = Util::htmlentities(utf8_encode($sensor_name));
			
			$title   = ( strlen($sensor_name) > $length_name ) ? substr($sensor_name, 0, $length_name)."..." : $sensor_name;	
			$title 	 = Util::htmlentities(utf8_encode($title));
			$tooltip = $s_title;
						
			$li = "h:'$h', url:'../sensor/interfaces.php?sensor=".$sensor->get_ip()."&name=".urlencode($sensor_name)."', icon:'../../pixmaps/theme/server.png', title:'$title', tooltip:'$tooltip'\n";
			$buffer .= (($j > 0) ? "," : "") . "{ $li }";
			$j++;
		}
    }
	
    $buffer .= "]";
    
	if ( $buffer == "[]" )  
		$buffer = "[{title:'"._("No Sensors Found")."', noLink:true}]";
    
	echo $buffer;
}
else if(preg_match("/ae_(.*)/",$key,$found))
{
    echo "[";
    echo "{ key:'e_".$found[1]."_net', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'"._("Networks")."'},";
    echo "{ key:'e_".$found[1]."_sensor', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/server.png', title:'"._("Sensors")."'},";
    echo "{ key:'e_".$found[1]."_netgroup', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net_group.png', title:'"._("Network Groups")."'}";
    echo "]";
}
else if(preg_match("/ue_(.*)/",$key,$found))
{
    
	$entity_id     = $found[1];
	$me            = Session::get_session_user();
	
	$admin_users   = array();
    $entities      = Acl::get_entities($conn);
    $entities_list = $entities[0];
	
	foreach ($entities_list as $entity) {
        $admin_users[$entity["id"]] = $entity["admin_user"];
    }
	
	$user_list = Acl::get_users_by_entity($conn, $entity_id);
      
    $buffer    = "[";
   		
	$entity_parents   = Acl::get_entity_parents($conn, $entity_id);
	$entity_parents[] = $entity_id;
	
	$am_i_pro_admin = false;
	
	foreach ($entity_parents as $index => $parent)
	{
		if ( $admin_users[$parent] == $me )
		{
			$am_i_pro_admin = true;
			break;
		}
	}
	
	$j = 0;
    
	foreach ($user_list as $k => $login)
	{
        if ( Session::is_admin($conn, $login) )
			$icon = "../../pixmaps/user-gadmin.png";
		elseif( $admin_users[$entity_id]==$login ) 
            $icon = "../../pixmaps/user-business.png";
        else
            $icon = "../../pixmaps/user-green.png";
		
		$u_key   = "u_".$login;
		
		$u_title = Util::htmlentities(utf8_encode($login));
			
		$title   = ( strlen($login) > $length_name ) ? substr($login, 0, $length_name)."..." : $login;	
		$title   = Util::htmlentities(utf8_encode($title));
		$tooltip = $u_title;
		
		
		$li  = "title:'$title', icon:'$icon', tooltip:'$tooltip', noLink:true";
		$li .= ( Session::am_i_admin() || $am_i_pro_admin || $login == $me ) ? ", key:'$u_key', isLazy:true" : "";
						        
        $buffer .= (($j > 0) ? "," : "") . "{ $li }";
        $j++;
    }
	
    $buffer .= "]";
    
    if ( $buffer == "[]" )  
		$buffer = "[{title:'"._("No Users found")."', noLink:true}]";
    
    echo $buffer;
}
else if(preg_match("/ou/",$key)) 
{
    $users     = array();
	$users_aux = Acl::get_orph_users($conn);
	
	foreach ($users_aux as $user)
	{
		if( $user['login'] != ACL_DEFAULT_OSSIM_ADMIN )
		{
			$icon = ( !Session::is_admin($conn, $user['login']) ) ? "../../pixmaps/user-green.png" : "../../pixmaps/user-gadmin.png";
			$users[$user['login']] = $icon;
		}
	}
	
	if ( !Session::am_i_admin()  )
	{
		$me = Session::get_session_user();
		if ( !empty($users[$me]) ) 
		{
			$users      = null;
			$users[$me] = "../../pixmaps/user-green.png";
		}
		else
			$users = array();
	}
	    
    $buffer = "[";
	
	$j=0;
	foreach ($users as $k => $v)
	{
		$u_title = Util::htmlentities(utf8_encode($k));
		$u_key   = "u_".$k;	
		
		$title   = ( strlen($k) > $length_name ) ? substr($k, 0, $length_name)."..." : $k;	
		$title = Util::htmlentities(utf8_encode($title));
		
		$tooltip = $u_title;
				
		$li      = "title:'$title', tooltip:'$tooltip', key:'$u_key', icon:'$v', isLazy:true, noLink:true";
		$buffer .= (($j > 0) ? "," : "") . "{ $li }";
		$j++;
	}	
	
	$buffer .= "]";
    
    if ( $buffer == "[]" )  
		$buffer = "[{title:'"._("No Users found")."', noLink:true}]";
    
    echo $buffer;
}
else if( preg_match("/servers/",$key) ) 
{
    $buffer = "[";
    
	if (  Session::am_i_admin() )
	{
		require_once ('classes/Server.inc');
		
		$j=0;
		
		$servers = Server::get_list($conn);
		foreach ($servers as $server)
		{
			$icon = "../../pixmaps/theme/host.png";
			
			$server_name = utf8_encode($server->get_name());
			$serv_title  = Util::htmlentities($server_name);
			
			$title   = ( strlen($server_name) > $length_name ) ? substr($server_name, 0, $length_name)."..." : $server_name;	
			$title   = Util::htmlentities(utf8_encode($title));
			
			$tooltip = $serv_title;
						
			$li      = "title:'$title', tooltip:'$tooltip', icon:'$icon', h:'$h', url:'../server/newserverform.php?name=".utf8_encode($server_name)."'";
			$buffer .= (($j > 0) ? "," : "") . "{ $li }";
			$j++;
		}
	}
	else
		$buffer .= "{title:'"._("No Servers Found")."', noLink:true}";
	
    $buffer .= "]";
    
	echo $buffer;
}
else if( preg_match("/databases/",$key) ) 
{
    
	$buffer = "[";
	
	if (  Session::am_i_admin() )
	{
		require_once ('classes/Databases.inc');
		
		$j = 0;
		
		$databases = Databases::get_list($conn);
		foreach ($databases as $database)
		{
			$icon     = "../../pixmaps/database.png";
			$db_name  = $database->get_name();
			$db_title = Util::htmlentities(utf8_encode($db_name));
			
			$title    = ( strlen($db_name) > $length_name ) ? substr($db_name, 0, $length_name)."..." : $db_name;
			$title    = Util::htmlentities(utf8_encode($title));			
			
			$tooltip  = $db_title;
						
			$li      = "title:'$title', tooltip:'$tooltip', icon:'$icon', h:'$h', url:'../server/newdbsform.php?name=".utf8_encode($db_name)."'";
			$buffer .= (($j > 0) ? "," : "") . "{ $li }";
			$j++;
		}
	}
	else
		$buffer .= "{title:'"._("No Databases Found")."', noLink:true}";
    
	$buffer .= "]";
    
	echo $buffer;
}
else if(preg_match("/sensors/",$key))
{
    $j = 0;
    
	$sensors = Sensor::get_list($conn);
       
	$buffer = "[";
	foreach ($sensors as $sensor)
	{
        $icon = "../../pixmaps/server.png";
            
		$sensor_name    = utf8_encode($sensor->get_name());
		$related_assets = Sensor::get_assets($conn, $sensor_name);
		
		$s_title  = Util::htmlentities(utf8_encode($sensor_name));
			
		$title    = ( strlen($sensor_name) > $length_name ) ? substr($sensor_name, 0, $length_name)."..." : $sensor_name;	
		$title    = Util::htmlentities(utf8_encode($title));		
		
		$tooltip  = $s_title;
				

		if ( count($related_assets["host"]) == 0 && count($related_assets["net"]) == 0 && count($related_assets["hgroup"]) == 0 && count($related_assets["ngroup"]) == 0 )
			$li = "title:'$title', tooltip:'$tooltip', icon:'$icon', h:'$h', url:'../sensor/interfaces.php?sensor=".utf8_encode($sensor_name)."&name=".utf8_encode($sensor_name)."'";
		else
			$li = "title:'$title', tooltip:'$tooltip', icon:'$icon', key:'sensor_".utf8_encode($sensor_name)."' ,isLazy:true, h:'$h', url:'../sensor/interfaces.php?sensor=".utf8_encode($sensor_name)."&name=".utf8_encode($sensor_name)."'";

		$buffer .= (($j > 0) ? "," : "") . "{ $li }";
		
		$j++;
        
    }
	
    $buffer .= "]";
    
	echo $buffer;
}
else if(preg_match("/sensor_(.*)/",$key,$found)) 
{
        $buffer = "[";
        $buffer .= "{ key:'shostgroup_".$found[1]."', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_group.png', title:'"._("Host Groups")."'},\n";
        $buffer .= "{ key:'snet_".$found[1]."', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'"._("Networks")."'},\n";
        $buffer .= "{ key:'snetgroup_".$found[1]."', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net_group.png', title:'"._("Network Groups")."'},\n";
        $buffer .= "{ key:'host_".$found[1]."', page:'', isFolder:true, isLazy:true , icon:'../../pixmaps/theme/host.png', title:'"._("Hosts")."'}\n";
        $buffer .= "]";
        echo $buffer;
}
else if (preg_match("/host_(.*)/",$key,$found)) 
{
    $j = 0;
	
	$buffer .= "[";
    foreach($all_cclass_hosts as $cclass => $hg) 
	{
        if ($j>=$from && $j<$to) 
		{
            $title   = "$cclass <font style=\"font-weight:normal;font-size:80%\">(" . count($hg) . " "._("hosts").")</font>";
			$li      = "key:'all_".$cclass."_".$found[1]."', isLazy:true, icon:'../../pixmaps/theme/host_add.png', title:'$title'\n";
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

    if ( $buffer == "" || $buffer == "[]" )
        $buffer = "[{title:'"._("No Host Found")."', noLink:true}]";
    
	echo $buffer;
}
else 
{
    /*   All assets tree   */
    if ($key!="all") {
        $buffer .= "[ {title: '"._("All assets")."', key:'key1', icon:'../../pixmaps/theme/any.png', expand:true, children:[\n";
        $buffer .= "{ key:'hostgroup', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_group.png', title:'"._("Host Groups")."'},\n";
        $buffer .= "{ key:'net', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'"._("Networks")."'},\n";
        $buffer .= "{ key:'netgroup', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net_group.png', title:'"._("Network Groups")."'},\n";
        $buffer .= "{ key:'all', page:'', isFolder:true, isLazy:true , icon:'../../pixmaps/theme/host.png', title:'"._("All Hosts")." <font style=\"font-weight:normal;font-size:80%\">(" . $total_hosts . " "._("hosts").")</font>'}\n";
        $buffer .= "] },";
    }

    if ($buffer=="" || $buffer=="[]")
        $buffer = "[{title:'"._("No Assets found")."', noLink:true}]";

    echo $buffer;
    
   /* SIEM Components tree */
    
    if($withsiemcomponents) 
	{
        echo "{title:'<font style=\"font-weight:normal\">"._("SIEM Components")."</font>', isFolder:true, icon:'../../pixmaps/theme/server_role.png', expand:true, children:[";
        
		if ( Session::am_i_admin() )
		{
			echo "{ key:'servers', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_os.png', title:'"._("Servers")."' },";
			echo "{ key:'databases', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/databases.png', title:'"._("Databases")."' },";
        }
		
		echo "{ key:'sensors', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/server.png', title:'"._("Sensors")."' },";
        echo "]},";
    }

    /*   Entities tree   */
      
    $entities       = Acl::get_entities($conn);
	$entities_types = Acl::get_entities_types($conn);
	
	$num_entities = count($entities[0]);
	
	$expand       = ( $num_entities > 0 ) ? "expand:true" : "expand:false";
		
    echo "{title:'<font style=\"font-weight:normal\">"._("Entities")."</font>', isFolder:true, icon:'../../pixmaps/company.png', $expand, children:[";
    
	$flag = false;
	
	$entities_admin = array();
	
	if ( $num_entities > 0 )
	{
		foreach ($entities[0] as $entity)
		{
			$entity_allowed = Acl::entityAllowed($entity['id']);
			
			if ( $entity['parent_id'] > 0 || $entity['type'] <= 0 || !$entity_allowed ) 
				continue;
						
			if ( $flag ) 
				echo ",";
						
			$flag = true;
			
			$icon           = "../../pixmaps/theme/any.png";
			$entity_name    = $entity['name'];
			
			if (  $entity_allowed == 2 )
			{
				$e_link     = "noLink: false"; 
				$e_url      = "../acl/entities_edit.php?id=".$entity['id'];
			}
			else
			{
				$e_link     = "noLink: true";
				$e_url      = "";
			}
			
			$e_key  = "e_".$entity['id'];
			$e_sn   = ( strlen($entity['name']) > $length_name )	? substr($entity['name'], 0, $length_name)."..." : $entity['name'];	
			$e_name = Util::htmlentities(utf8_encode($entity_name));
			
			
			$entities_admin[$entity['admin_user']] = $entity['id'];
			
			$title   = "<font style=\"font-weight:bold;\">".Util::htmlentities($e_sn)."</font> <font style=\"color:gray\">[".$entities_types[$entity['type']]['name']."]</font>";
			$tooltip = Util::htmlentities($entity['name'])." [".$entities_types[$entity['type']]['name']."]";
			$h       = "400";
						
			echo "{title:'".$title."', h:'$h', ".$e_link.", tooltip:'$tooltip', key:'".$e_key."', icon:'$icon', expand:true,  url:'".$e_url."', name:'$e_name'";
					echochildrens($entities, $entity['id'], $withusers, $entities_admin);
			echo "}";
		}
	}
	else
		echo "{title:'"._("No Entities Found")."', noLink:true}";
		
	echo "]}";
		
	if ( $withusers && Session::am_i_admin() ) 
		echo ",{title:'<font style=\"font-weight:normal\">"._("Others users")."</font>', isFolder:true, icon:'../../pixmaps/menu/assets.gif', isLazy:true, key:'ou'}";
	
	echo "]";
}


function echochildrens($entities,$parent_id, $withusers, $entities_admin) {
    
	$length_name = 50;
	
	echo ",children:[";
    
	/* Connect to db */
	$db   = new ossim_db();
	$conn = $db->connect();

	$users_by_entity = Acl::get_users_by_entity($conn, $parent_id);
	$me              = Session::get_session_user();
	$entities_types  = Acl::get_entities_types($conn);
	
	
	$is_editable     = $parent_id != "" && ( !empty($users_by_entity[$me]) || Session::am_i_admin() || !empty($entities_admin[$me]) );
	    
	if( $is_editable ) 
	{
        echo "{title:'<font style=\"font-weight:normal\">"._("All Assets")."</font>', key:'ae_".$parent_id."', icon:'../../pixmaps/menu/assets.gif', isFolder:true, isLazy:true}";
        
		if ($withusers)
			echo ",{title:'<font style=\"font-weight:normal\">"._("Assets by user")."</font>', key:'ue_".$parent_id."', icon:'../../pixmaps/menu/assets.gif', isFolder:true, isLazy:true}";
    }
			
	$children = Acl::get_entity_childs($conn,$parent_id);
		
	if ( !empty($children) )
	{
		$flag = false;
		
		foreach ($children as $index => $child_id)
		{
			$icon      = "../../pixmaps/theme/any.png";
			$child     = $entities[0][$child_id];
									
			$entity_allowed = Acl::entityAllowed($child_id);
			
			if (  $entity_allowed == 2 )
			{
				
				$child_link     = "noLink: false"; 
				$child_url      = "../acl/entities_edit.php?id=".$child_id;
			}
			else
			{
				$child_link     = "noLink: true";
				$child_url      = "";
			}
			
			$child_key  = "e_".$child_id;
			$child_sn   = ( strlen($child['name']) > $length_name )	? substr($child['name'], 0, $length_name)."..." : $child['name'];	
			$child_name = Util::htmlentities(utf8_encode($child['name']));
			
			$chil_ent_admin                       = $entities_admin;
			$chil_ent_admin[$child['admin_user']] = $child_id;
						
			
			if ( $child['parent_id'] == $parent_id )
			{
				$title   = "<font style=\"font-weight:bold;\">".Util::htmlentities($child_sn)."</font> <font style=\"color:gray\">[".$entities_types[$child['type']]['name']."]</font>";
				$tooltip = Util::htmlentities($child['name'])." [".$entities_types[$child['type']]['name']."]";
				
				if ( $flag || $is_editable) 
					echo ",";
						
				$flag = true;
				$h    = "400";					
				
				echo "{title:'".$title."', h:'$h', ".$child_link.", url:'".$child_url."', tooltip:'$tooltip', key:'".$child_key."', icon:'$icon', expand:true, name:'$child_name'";
					echochildrens($entities, $child_id, $withusers, $entities_admin);
				echo "}";
			}	
		}
	}
    echo "]";
}	

?>