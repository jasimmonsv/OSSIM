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
require_once ('classes/Security.inc');
require_once ('classes/Session.inc');
require_once ('ossim_conf.inc');

Session::logcheck("MenuEvents", "EventsVulnerabilities");

$key  = GET('key');
$page = intval(GET('page'));


ossim_valid($key, OSS_NULLABLE, OSS_TEXT, OSS_PUNC, 'illegal:' . _("Key"));
ossim_valid($page, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("Page"));

if (ossim_error()) {
    die(ossim_error());
}

if ( $page == "" || $page<=0 ) 
	$page = 1;
	
$maxresults   = 200;
$to           = $page * $maxresults;
$from         = $to - $maxresults;
$nextpage     = $page + 1;

$length_name = ( !empty($_GET['length_name']) ) ? GET('length_name') : 30;




$cachefile = "/var/ossim/sessions/".$_SESSION["_user"]."_vulnmeter_".base64_encode($key)."_$page.json";
if (file_exists($cachefile) && $key!="entities") {
    readfile($cachefile);
    exit;
}

require_once ('classes/Host.inc');
require_once ('classes/Host_group.inc');
require_once ('classes/Net.inc');
require_once ('classes/Net_group.inc');
require_once ('ossim_db.inc');

$conf           = $GLOBALS["CONF"];
$version        = $conf->get_conf("ossim_server_version", FALSE);
$prodemo        = (preg_match("/pro|demo/i",$version)) ? true : false;

$noprint = true;

$db   = new ossim_db();
$conn = $db->connect();

$ossim_hosts      = array();
$total_hosts      = 0;
$ossim_nets       = array();
$all_cclass_hosts = array();
$buffer           = "";

if ( $key =="" || preg_match("/^(all|hostgroup)/",$key) ) 
{
	if ($host_list = Host::get_list($conn, "", "ORDER BY hostname"))
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
if(preg_match("/e_(\d+)_allassets/",$key,$found))
 {

    $result      = array();
    
    $entityPerms = Acl::entityPerms($conn,$found[1]);
    $all         = count($entityPerms["assets"]);
    
	$nets        = Net::get_list($conn);

    foreach($nets as $net) 
	{
        $cidrs = explode(",",$net->get_ips());
        if (!$all || Acl::cidrs_allowed($cidrs,$entityPerms["assets"])) {
                $result[] = $net->get_ips();
        }
    }

    $all     = count($entityPerms["sensors"]);
    $sensors = Sensor::get_all($conn);
    
    foreach($sensors as $sensor) 
	{
        if (!$all || $entityPerms["sensors"][$sensor->get_ip()]) {
            $result[] = $sensor->get_ip();
        }
    }
    
    echo implode("\n",$result);
    
	$buffer = implode("\n",$result);
}
else if ($key == "hostgroup") 
{
	$hg_list = Host_group::get_list($conn, "", "ORDER BY name");
    if (count($hg_list)>0) 
	{
        $j = 0;
        
		$buffer .= "[";
		foreach($hg_list as $hg) 
		{
            if($j>=$from && $j<$to) 
			{
                $hg_name  = $hg->get_name();
				$hg_title = Util::htmlentities($hg_name);
				
				$hg_key   = "hostgroup_".base64_encode($hg_name);
				$hg_url   = "NODES:".$hg_title;
								           
				$title    = ( strlen($hg_name) > $length_name ) ? substr($hg_name, 0, $length_name)."..." : $hg_name;	 
				$title    = Util::htmlentities($title);
				$tooltip  = $hg_title;
								
				$li      = "key:'$hg_key', isLazy:true , url:'$hg_url', icon:'../../pixmaps/theme/host_group.png', title:'$title', tooltip:'$tooltip'\n";
                $buffer .= (($j>$from) ? "," : "") . "{ $li }\n";
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
    
    if ( $buffer=="" || $buffer=="[]" )
        echo "[{title:'"._("No Host groups Found")."', noLink:true}]";
    else 
        echo $buffer;
}
else if (preg_match("/hostgroup_(.*)/",$key,$found)) 
{
    $length_hn = $length_name+5;
	
	$buffer .= "[";
    if ($hg_hosts = Host_group::get_hosts($conn, base64_decode($found[1]))) 
	{
        $k    = 1;
        $j    = 0;
        $html = "";

        foreach($hg_hosts as $hosts) 
		{
            if($j>=$from && $j<$to) 
			{
                $ip = $hosts->get_host_ip();
                if ( isset($ossim_hosts[$ip]) ) 
				{   
                    // Test filter
					$hname      = ( $ip == $ossim_hosts[$ip] ) ? "" : $ossim_hosts[$ip];
					$host_key   = utf8_encode($key.$k);
														
					$aux_hname  = ( strlen($hname) > $length_hn ) ? substr($hname, 0, $length_hn)."..." : $hname;
			
					$title      = ( $hname == '' ) ? $ip : "$ip <font style=\"font-size:80%\">(" . Util::htmlentities($aux_hname) . ")</font>";
					$tooltip    = ( $hname == '' ) ? $ip : $ip." (".$hname.")";
															
					$html      .= "{ key:'$host_key', url:'$ip', icon:'../../pixmaps/theme/host.png', title:'$title', tooltip:'$tooltip' },\n";
                    $k++;
                }
            }
            $j++;
        }
        if ($j>$to) {
            $html.= "{ key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_group.png', title:'"._("next")." $maxresults "._("hosts")."' }";
        }
		
        if ($html != "") 
			$buffer .= preg_replace("/,$/", "", $html);

    }
    $buffer .= "]";
    
    if ( $buffer== "" || $buffer== "[]" )
        echo "[{title:'"._("No Hosts Found")."', noLink:true}]";
    else 
        echo $buffer;
}
else if ($key == "net") {
    $buffer = Net::draw_nets_by_class($conn, $key, $filter, $length_name, 1);
    echo $buffer;
}
else if ( preg_match("/^.class_(.*)/",$key,$found) ) {
    $buffer = Net::draw_nets_by_class($conn, $key, $filter, $length_name, 1);
    echo $buffer;
}
else if (preg_match("/net_(.*)/",$key,$found))
{
	$hostin    = array();
	$length_hn = $length_name+5;
	
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
				$host_list_aux = Host::get_list($conn,"WHERE inet_aton(ip)>=inet_aton('".$net_range[0]."') && inet_aton(ip)<=inet_aton('".$net_range[1]."')", "ORDER BY ip");
				foreach ($host_list_aux as $h) {
					$hostin[$h->get_ip()] = $h->get_hostname();
				}
			}
		}
	}

    $k = 0;
    
	$net_name  = base64_decode($found[1]);
						
	$ips_data  = $net_list1[0]->get_ips();				
	$ips       = "<font style=\"font-size:80%\">(".$ips_data.")</font>";

	$tooltip   = "!".$ips_data." (".$net_name.")";
	
	$buffer .= "[";

    if($page==1) {
        $title     = "<span style=\"color: #B3B5DD;\">!".$ips_data." <font style=\"font-weight:normal;font-size:80%\">(".$net_name.")</font></span>";
        $buffer .= "{url:'!".$ips_data."', icon:'../../pixmaps/theme/net.png', title:'$title', tooltip:'$tooltip'\n},";
    }
    
    $html = "";
    foreach($hostin as $ip => $host_name) 
	{
    	if($k>=$from && $k<$to) 
		{
            // Test filter
			$hname      = ( $ip == $host_name ) ? "" : $host_name;
			$host_key   = utf8_encode($key.$k);
												
			$aux_hname  = ( strlen($hname) > $length_hn ) ? substr($hname, 0, $length_hn)."..." : $hname;
	
			$title      = ( $hname == '' ) ? $ip : "$ip <font style=\"font-size:80%\">(" . Util::htmlentities($aux_hname) . ")</font>";
			$tooltip    = ( $hname == '' ) ? $ip : $ip." (".$hname.")";
									
			$html      .= "{ key:'$host_key', url:'$ip', icon:'../../pixmaps/theme/host.png', title:'$title', tooltip:'$tooltip' },\n";
        }
        $k++;
    }
    
	if ($k>$to) {
        $html.= "{ key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host.png', title:'"._("next")." $maxresults "._("hosts")."' }";
    }
    
	if ($html != "") 
		$buffer .= preg_replace("/,$/", "", $html);
    
	$buffer .= "]";
    
    if ( $buffer == "" || $buffer == "[]" )
        echo "[{title:'"._("No Hosts Found")."', noLink:true}]";
    else 
        echo $buffer;
}
else if ($key=="netgroup") 
{
    $buffer .= "[";
    if ($net_group_list = Net_group::get_list($conn)) 
	{
        $j = 0;
        foreach($net_group_list as $net_group) 
		{
            if($j>=$from && $j<$to) 
			{
               	$ng_name  = $net_group->get_name();
				$ng_key   = "netgroup_".base64_encode($ng_name);
				$ng_title = Util::htmlentities($ng_name);
				
				$title    = ( strlen($ng_name) > $length_name ) ? substr($ng_name, 0, $length_name)."..." : $ng_name;
				$title    = Util::htmlentities($title);				
				$tooltip  = $ng_title;
								
				$li      = "key:'$ng_key', isLazy:true , url:'NODES:$ng_title', icon:'../../pixmaps/theme/net_group.png', title:'$title', tooltip:'$tooltip'\n";
                $buffer .= (($j>$from) ? "," : "") . "{ $li }\n";
            }
            $j++;
        }
		
        if ($j>$to) 
		{
            $li      = "key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net_group.png', title:'"._("next")." $maxresults "._("net groups")."'";
            $buffer .= ",{ $li }\n";
        }
    }
    
	$buffer .= "]";
    
    if ( $buffer == "" || $buffer == "[]" )
        echo "[{title:'"._("No Network groups Found")."', noLink:true}]";
    else 
        echo $buffer;
}
else if (preg_match("/netgroup_(.*)/",$key,$found))
{
    $html = "";
    $k    = 0;
	
	$nets = Net_group::get_networks($conn, base64_decode($found[1]));
	  
	$buffer .= "[";
    foreach($nets as $net) 
	{
        if($k>=$from && $k<$to) 
		{
            $net_name  = $net->get_net_name();
			$net_title = Util::htmlentities($net_name);
			
			$net_key   = utf8_encode($key.$k);
			$ips_data  = $net->get_net_ips($conn);				
			$ips       = "<font style=\"font-size:80%\">(".$ips_data.")</font>";
						
			$title     = ( strlen($net_name) > $length_name ) ? substr($net_name, 0, $length_name)."..." : $net_name;	
			$title     = Util::htmlentities($title)." ".$ips;
			
			$tooltip   = $net_title." (".$ips_data.")";
			
			$html.= "{ key:'$net_key', url:'$ips_data', icon:'../../pixmaps/theme/net.png', title:'$title', tooltip:'$tooltip' },\n";
        }
        $k++;
    }
    
	if ($k>$to) {
        $html.= "{ key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'"._("next")." $maxresults "._("nets")."' }";
    }
    
	if ($html != "") 
		$buffer .= preg_replace("/,$/", "", $html);
	
	$buffer .= "]";
    
    if ( $buffer == "" || $buffer == "[]" )
        echo "[{title:'"._("No Networks Found")."', noLink:true}]";
    else 
        echo $buffer;
}
else if ($key=="all")
{
    $j = 0;
    
	$buffer .= "[";
	foreach($all_cclass_hosts as $cclass => $hg) 
	{
        if($j>=$from && $j<$to) 
		{
            $title   = "$cclass <font style=\"font-weight:normal;font-size:80%\">(" . count($hg) . " "._("hosts").")</font>";
			$li      = "key:'all_$cclass', isLazy:true, url:'NODES:$cclass', icon:'../../pixmaps/theme/host_add.png', title:'$title'\n";
            $buffer .= (($j>$from) ? "," : "") . "{ $li }\n";
        }
        $j++;
    }
    
	if ($j>$to) {
        $buffer.= ", { key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_add.png', title:'"._("next")." $maxresults "._("c-class")."' }";
    }
    
    // Add FQDNs
    /*
    $all_fqdns = array();
    $fqdns_to_tree = array();
    $query = "SELECT fqdns FROM host";

    $res = $conn->Execute($query);
    while (!$res->EOF) {
        if ($res->fields['fqdns']!="") {
            $all_fqdns = explode(",", $res->fields['fqdns']);
            foreach($all_fqdns as $fqdn) {
                $fqdn = trim($fqdn);
                $auxf = explode(".",$fqdn);
                if($fqdns_to_tree[$auxf[0]]=="") {
                    $fqdns_to_tree[$auxf[0]] = 1;
                }
                else {
                    $fqdns_to_tree[$auxf[0]]++;
                }
            }
        }
        $res->MoveNext();
    }
    foreach ($fqdns_to_tree as $fqdn => $count) {
        $buffer .= ",{key:'fqdn_".$fqdn."', isLazy:true, url:'NODES:".$fqdn."', icon:'../../pixmaps/theme/host_add.png', title:'$fqdn <font style=\"font-weight:normal;font-size:80%\">(" . $count . " "._("FQDNs").")</font>'\n}";
    }*/
    
    $buffer .= "]";
    
    if ( $buffer == "" || $buffer == "[]" )
        echo "[{title:'"._("No Hosts Found")."', noLink:true}]";
    else 
        echo $buffer;
}

else if (preg_match("/all_(.*)/",$key,$found))
{
    $html = "";
    
    $j         = 1;
    $i         = 0;
		
	$buffer .= "[";
	
	foreach($all_cclass_hosts as $cclass => $hg) 
	{
		if ( $found[1] == $cclass ) 
		{
			foreach($hg as $ip) 
			{
				$fqdns = array();
				
				if($i>=$from && $i<$to) 
				{
					$hname      = ( $ip == $ossim_hosts[$ip] ) ? "" : $ossim_hosts[$ip];
					$host_key   = "host_".$ip;
														
					$aux_hname  = ( strlen($hname) > $length_name ) ? substr($hname, 0, $length_name)."..." : $hname;
			
					$title      = ( $hname == '' ) ? $ip : "$ip <font style=\"font-size:80%\">(" . Util::htmlentities($aux_hname) . ")</font>";
					$tooltip    = ( $hname == '' ) ? $ip : $ip." (".$hname.")";
								   
					$html      .= "{ key:'$host_key', url:'$ip', isLazy:true, icon:'../../pixmaps/theme/host.png', title:'$title', tooltip:'$tooltip' },\n";
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
    
    if ( $buffer == "" || $buffer == "[]" )
        echo "[{title:'"._("No Hosts Found")."', noLink:true}]";
    else 
        echo $buffer;
}
/*else if (preg_match("/fqdn_(.*)/",$key,$found)){
    
    $buffer = "[";
    $all_fqdns = array();
    $fqdns_to_tree = array();
    $query = "SELECT ip, fqdns FROM host WHERE fqdns LIKE '%".$found[1].".%'";

    $res = $conn->Execute($query);
    while (!$res->EOF) {
        if ($res->fields['fqdns']!="") {
            $all_fqdns = explode(",", $res->fields['fqdns']);
            foreach($all_fqdns as $fqdn) {
                $fqdn = trim($fqdn);
                if (preg_match("/".$found[1]."\./",$fqdn)){
                    $fqdns_to_tree[$fqdn] = $res->fields['ip'];
                }
            }
        }
        $res->MoveNext();
    }
    $j = 1;
    foreach ($fqdns_to_tree as $fqdn=>$ip) {
        $buffer .= "{key:'$key$j', url:'$fqdn', icon:'../../pixmaps/theme/host.png', title:'$fqdn <font style=\"font-weight:normal;font-size:80%\">(" . $ip .")</font>'\n},";
        $j++;
    }
    $buffer = preg_replace("/,$/", "", $buffer);
    $buffer .= "]";

}*/
else if (preg_match("/host_(.*)/",$key,$found)){
    
    $host_data = Host::get_list($conn, "where ip='".$found[1]."'");
    
    $hname = $host_data[0]->get_hostname();
    
    if( $hname!=$found[1] ) 
	{
        $fqdns[] = $hname;
    }

    $all_fqdn = explode(",", $host_data[0]->get_fqdns());
    
    foreach ($all_fqdn as $fqdn) 
	{
        $fqdn = trim($fqdn);
        if ($fqdn!="") {
            $fqdns[] = $fqdn;
        }
    }
    
    $buffer = "[";
    
    $name      = "";
    	    
	if ( $found[1] != $hname ) 
	{  
		$ip         = $found[1];
		$host_name  = $hname;
			
		$host_tooltip = "!".$ip." (".Util::htmlentities($host_name).")";
		$aux_hname    = ( strlen($host_name) > $length_name ) ? substr($host_name, 0, $length_name)."..." : $host_name;

		$title   = "<span style=\"color: #B3B5DD;\">!$ip (".Util::htmlentities($aux_hname).")</span>";
		$tooltip = $host_tooltip;
	}
    else
    {
		$ip      = $found[1];
		$title   = "<span style=\"color: #B3B5DD;\">!$ip</span>";
		$tooltip = "!".$ip;
	}
    
	$buffer .= "{url:'!".$found[1]."', icon:'../../pixmaps/theme/host.png', title:'$title', tooltip:'$tooltip'\n},";
    	
	if( count($fqdns)>0 ) 
	{
        $j = 1; 
        foreach ($fqdns as $fqdn) 
		{
           	$ip         = $found[1];
			$host_key   = utf8_encode($key.$j);
		
			$host_tooltip =  Util::htmlentities($fqdn)." (" . $ip . ")";
			$aux_fqdn     = ( strlen($fqdn) > $length_name ) ? substr($fqdn, 0, $length_name)."..." : $fqdn;
	
			$title   = "$aux_fqdn <font style=\"font-size:80%\">(" . $ip . ")</font>";
			$tooltip = $host_tooltip;
						
			$buffer .= "{key:'$host_key', url:'$fqdn', icon:'../../pixmaps/theme/host.png', title:'$title', tooltip:'$tooltip'\n},";
            $j++;
        }
    }
    
	$buffer = preg_replace("/,$/", "", $buffer);
    
	$buffer .= "]";
    
    if ($buffer=="" || $buffer=="[]")
        echo "[{title:'"._("No Hosts Found")."', noLink:true}]";
    else 
        echo $buffer;
}
else if(preg_match("/^e_(.*)_net$/",$key)) {
    $buffer = Net::draw_nets_by_class($conn, $key, $filter, $length_name, 1);
    echo $buffer;
}
else if(preg_match("/^e_(.*)_.class_(.*)/",$key)) {
    $buffer = Net::draw_nets_by_class($conn, $key, $filter, $length_name, 1);
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
			$s_title     = Util::htmlentities($sensor_name);
			$sensor_key  = utf8_encode("sensor;".$sensor_name);
			
			$title    = ( strlen($sensor_name) > $length_name ) ? substr($sensor_name, 0, $length_name)."..." : $sensor_name;	
			$title    = Util::htmlentities($title);
			$tooltip  = $s_title;
						
			$li = "url:'".$sensor->get_ip()."', icon:'../../pixmaps/theme/server.png', title:'$title', tooltip:'$tooltip'\n";
			$buffer .= (($j > 0) ? "," : "") . "{ $li }";
			$j++;
		}
    }
	
    $buffer .= "]";
    
    if ( $buffer == "" || $buffer == "[]" )
        echo "[{title:'"._("No Sensors Found")."', noLink:true}]";
    else 
        echo $buffer;
}
else if ($key=="entities") 
{
    $entities       = Acl::get_entities($conn);
	$entities_types = Acl::get_entities_types($conn);
	
	$num_entities = count($entities[0]);
	$expand       = ( $num_entities > 0 ) ? "expand:true" : "expand:false";
		
    echo "[";
    
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
			
			$e_key  = "e_".$entity['id'];
			$e_sn   = ( strlen($entity['name']) > $length_name )	? substr($entity['name'], 0, $length_name)."..." : $entity['name'];	
			$e_name = Util::htmlentities($entity_name); 
			
			$entities_admin[$entity['admin_user']] = $entity['id'];
			
			$title   = "<font style=\"font-weight:bold;\">".Util::htmlentities($e_sn)."</font> <font style=\"color:gray\">[".$entities_types[$entity['type']]['name']."]</font>";
			$tooltip = Util::htmlentities($entity['name'])." [".$entities_types[$entity['type']]['name']."]";
									
			echo "{title:'".$title."', noLink: true, tooltip:'$tooltip', key:'".$e_key."', icon:'$icon', expand:true,  name:'$e_name'";
					echochildrens($entities, $entity['id'], $entities_admin);
			echo "}";
		}
	}
	else
		echo "{title:'"._("No Entities Found")."', noLink:true}";
		
	echo "]";
}
else if ($key!="all") {
    $buffer .= "[";
    $buffer .= "{ key:'hostgroup', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_group.png', title:'"._("Host Groups")."'},\n";
    $buffer .= "{ key:'net', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'"._("Networks")."'},\n";
    $buffer .= "{ key:'netgroup', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net_group.png', title:'"._("Network Groups")."'},\n";
    if($prodemo)
        $buffer .= "{ key:'entities', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/company.png', title:'"._("Entities")."'},\n";
    $buffer .= "{ key:'all', page:'', isFolder:true, isLazy:true , icon:'../../pixmaps/theme/host.png', title:'"._("All Hosts")." <font style=\"font-weight:normal;font-size:80%\">(" . $total_hosts . " "._("hosts").")</font>'}\n";
    $buffer .= "]";
    
    echo $buffer;
}

error_reporting(0);

if ( $key!="entities" ) 
{
    $f = fopen($cachefile,"w");
    fputs($f,$buffer);
    fclose($f);
}

function echochildrens($entities, $parent_id, $entities_admin) {
        
	/* Connect to db */
	$db   = new ossim_db();
	$conn = $db->connect();

	$users_by_entity = Acl::get_users_by_entity($conn, $parent_id);
	$me              = Session::get_session_user();
	$entities_types  = Acl::get_entities_types($conn);
	
	$length_name = ( !empty($_GET['length_name']) ) ? GET('length_name') : 30; 
	
	echo ",children:[";
	
	$is_editable     = $parent_id != "" && ( !empty($users_by_entity[$me]) || Session::am_i_admin() || !empty($entities_admin[$me]) );
	    
	if( $is_editable ) 
	{
        echo "{title:'<font style=\"font-weight:normal\">"._("All Assets")."</font>', url:'AllAssets', key:'e_".$parent_id."_allassets', icon:'../../pixmaps/menu/assets.gif', isFolder:false, expand:true,";
        echo "children:[ ";
        
        echo "{ key:'e_".$parent_id."_net', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'"._("Networks")."'},";
        echo "{ key:'e_".$parent_id."_sensor', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/server.png', title:'"._("Sensors")."'}";
        
        echo "]}";
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
			
			$child_key   = "e_".$child_id;
			$child_sn    = ( strlen($child['name']) > $length_name )	? substr($child['name'], 0, $length_name)."..." : $child['name'];	
			$child_name  = Util::htmlentities($child['name']); 
			
			$chil_ent_admin                       = $entities_admin;
			$chil_ent_admin[$child['admin_user']] = $child_id;
						
			if ( $child['parent_id'] == $parent_id )
			{
				
				$title   = "<font style=\"font-weight:bold;\">".Util::htmlentities($child_sn)."</font> <font style=\"color:gray\">[".$entities_types[$child['type']]['name']."]</font>";
				$tooltip = Util::htmlentities($child['name'])." [".$entities_types[$child['type']]['name']."]";
											
				if ( $flag || $is_editable) 
					echo ",";
						
				$flag = true;
									
				echo "{title:'".$title."', tooltip:'$tooltip', noLink: true, url:'".$child_url."', key:'".$child_key."', icon:'$icon', expand:true, name:'$child_name'";
					echochildrens($entities, $child_id, $withusers, $entities_admin, $length_name);
				echo "}";
			}	
		}
	}
    echo "]";
}	

?>