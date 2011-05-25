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

$key = GET('key');
$page = intval(GET('page'));
ossim_valid($key, OSS_NULLABLE, OSS_TEXT, OSS_PUNC, 'illegal:' . _("key"));
ossim_valid($page, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("page"));
if (ossim_error()) {
    die(ossim_error());
}
if ($page == "" || $page<=0) $page = 1;
$maxresults = 200;
$to = $page * $maxresults;
$from = $to - $maxresults;
$nextpage = $page + 1;

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

$db = new ossim_db();
$conn = $db->connect();

$ossim_hosts = array();
$total_hosts = 0;
$ossim_nets = array();
$all_cclass_hosts = array();
$buffer = "";

if ($key=="" || preg_match("/^(all|hostgroup)/",$key)) {
	if ($host_list = Host::get_list($conn, "", "ORDER BY hostname"))
		foreach($host_list as $host) if ($filter == "" || ($filter != "" && (preg_match("/$filter/i", $host->get_ip()) || preg_match("/$filter/i", $host->get_hostname())))) {
		    $ossim_hosts[$host->get_ip() ] = $host->get_hostname();
		    $cclass = preg_replace("/(\d+\.)(\d+\.)(\d+)\.\d+/", "\\1\\2\\3", $host->get_ip());
		    $all_cclass_hosts[$cclass][] = $host->get_ip();
		    $total_hosts++;
		}
}
if(preg_match("/e_(\d+)_allassets/",$key,$found)) {

    $result = array();
    
    $entityPerms = Acl::entityPerms($conn,$found[1]);
    $all = count($entityPerms["assets"]);
    $nets = Net::get_all($conn);

    foreach($nets as $net) {
        $cidrs = explode(",",$net->get_ips());
        if (!$all || Acl::cidrs_allowed($cidrs,$entityPerms["assets"])) {
                $result[] = $net->get_ips();
        }
    }

    $all = count($entityPerms["sensors"]);
    $sensors = Sensor::get_all($conn);
    
    foreach($sensors as $sensor) {
        if (!$all || $entityPerms["sensors"][$sensor->get_ip()]) {
            $result[] = $sensor->get_ip();
        }
    }
    
    echo implode("\n",$result);
    $buffer = implode("\n",$result);
}
else if ($key == "hostgroup") {
	$hg_list = Host_group::get_list($conn, "", "ORDER BY name");
    if (count($hg_list)>0) {
        $buffer .= "[";
        $j = 0;
        foreach($hg_list as $hg) {
            if($j>=$from && $j<$to) {
                $hg_key   = base64_encode($hg->get_name());
				$hg_title = utf8_encode($hg->get_name());
                $li = "key:'hostgroup_$hg_key', isLazy:true , url:'NODES:$hg_title', icon:'../../pixmaps/theme/host_group.png', title:'$hg_title'\n";
                $buffer .= (($j>$from) ? "," : "") . "{ $li }\n";
            }
            $j++;
        }
        if ($j>$to) {
            $li = "key:'hostgroup', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_group.png', title:'"._("next")." $maxresults "._("host group")."'";
            $buffer .= ",{ $li }\n";
        }
        $buffer .= "]";
    }
    
    if ( $buffer=="" || $buffer=="[]" )
        echo "[{title:'"._("No assets found")."'}]";
    else 
        echo $buffer;
}
else if (preg_match("/hostgroup_(.*)/",$key,$found)) {
    $buffer .= "[";
    if ($hg_hosts = Host_group::get_hosts($conn, base64_decode($found[1]))) {
        $k = 1;
        $j = 0;
        $html = "";

        foreach($hg_hosts as $hosts) {
            if($j>=$from && $j<$to) {
                $host_ip = $hosts->get_host_ip();
                if (isset($ossim_hosts[$host_ip])) { // test filter
                    $html.= "{ key:'$key.$k', url:'$host_ip', icon:'../../pixmaps/theme/host.png', title:'$host_ip <font style=\"font-size:80%\">(" . utf8_encode($ossim_hosts[$host_ip]) . ")</font>' },\n";
                    $k++;
                }
            }
            $j++;
        }
        if ($j>$to) {
            $html.= "{ key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_group.png', title:'"._("next")." $maxresults "._("hosts")."' }";
        }
        if ($html != "") $buffer .= preg_replace("/,$/", "", $html);

    }
    $buffer .= "]";
    
    if ( $buffer=="" || $buffer=="[]" )
        echo "[{title:'"._("No assets found")."'}]";
    else 
        echo $buffer;
}
else if ($key == "net") {
	$net_list = Net::get_list($conn, "", "ORDER BY name");
    $buffer .= "[";
    if (count($net_list)>0) {

        $j = 0;
        foreach($net_list as $net) {
            if($j>=$from && $j<$to) {
                $ips = $net->get_ips();
              	$net_key = base64_encode($net->get_name());
				$net_title = utf8_encode($net->get_name());                
                $li = "key:'net_$net_key', isLazy:true, url:'$ips', icon:'../../pixmaps/theme/net.png', title:'$net_title <font style=\"font-size:80%\">(".$ips.")</font>'\n";
                $buffer .= (($j>$from) ? "," : "") . "{ $li }\n";
            }
            $j++;
        }
        if ($j>$to) {
            $li = "key:'net', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'"._("next")." $maxresults "._("nets")."'";
            $buffer .= ",{ $li }\n";
        }
        
    }
    $buffer .= "]";
    
    if ($buffer=="" || $buffer=="[]")
        echo "[{title:'"._("No assets found")."'}]";
    else 
        echo $buffer;
}
else if (preg_match("/net_(.*)/",$key,$found)){
	$hostin = array();
	if ($net_list1 = Net::get_list($conn, "name='".base64_decode($found[1])."'")) {
		require_once("classes/CIDR.inc");
		foreach($net_list1 as $net) {
		    $net_name = $net->get_name();
		    $nets_ips = explode(",",$net->get_ips());
		    foreach ($nets_ips as $net_ips) {
			    $net_range = CIDR::expand_CIDR($net_ips,"SHORT","IP");
				$host_list_aux = Host::get_list($conn,"WHERE inet_aton(ip)>=inet_aton('".$net_range[0]."') && inet_aton(ip)<=inet_aton('".$net_range[1]."')");
				foreach ($host_list_aux as $h) {
					$hostin[$h->get_ip()] = $h->get_hostname();
				}
			}
			/*
			foreach($ossim_hosts as $ip => $hname) if ($net->isIpInNet($ip, $net_ips)) {
		        $hostin[$ip] = $hname;
		    }
			*/
		}
	}
    $k = 0;
    $buffer .= "[";
    $html = "";
    foreach($hostin as $ip => $host_name) {
    	$host_name = utf8_encode($host_name);
        if($k>=$from && $k<$to) {
            $html.= "{ key:'$key.$k', url:'$ip', icon:'../../pixmaps/theme/host.png', title:'$ip <font style=\"font-size:80%\">($host_name)</font>' },\n";
        }
        $k++;
    }
    if ($k>$to) {
        $html.= "{ key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host.png', title:'"._("next")." $maxresults "._("hosts")."' }";
    }
    if ($html != "") $buffer .= preg_replace("/,$/", "", $html);
    $buffer .= "]";
    
    if ($buffer=="" || $buffer=="[]")
        echo "[{title:'"._("No assets found")."'}]";
    else 
        echo $buffer;
}
else if ($key=="netgroup") {
    $buffer .= "[";
    if ($net_group_list = Net_group::get_list($conn)) {

        $j = 0;
        foreach($net_group_list as $net_group) {
            if($j>=$from && $j<$to) {
               	$ng_key = base64_encode($net_group->get_name());
				$ng_title = utf8_encode($net_group->get_name());
                //$nets = $net_group->get_networks($conn, $net_group_name);
                $li = "key:'netgroup_$ng_key', isLazy:true , url:'NODES:$ng_title', icon:'../../pixmaps/theme/net_group.png', title:'$ng_title'\n";
                $buffer .= (($j>$from) ? "," : "") . "{ $li }\n";
            }
            $j++;
        }
        if ($j>$to) {
            $li = "key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net_group.png', title:'"._("next")." $maxresults "._("net groups")."'";
            $buffer .= ",{ $li }\n";
        }
    }
    $buffer .= "]";
    
    if ($buffer=="" || $buffer=="[]")
        echo "[{title:'"._("No assets found")."'}]";
    else 
        echo $buffer;
}
else if (preg_match("/netgroup_(.*)/",$key,$found)){
    $buffer .= "[";
    $html = "";
    $nets = Net_group::get_networks($conn, base64_decode($found[1]));
    $k = 0;
    foreach($nets as $net) {
        if($k>=$from && $k<$to) {
            $net_name = $net->get_net_name();
            //if (isset($ossim_nets[$net_name]) && count($ossim_nets[$net_name]) > 0) {
                $html.= "{ key:'$key.$k', url:'".$net->get_net_ips($conn)."', icon:'../../pixmaps/theme/net.png', title:'$net_name' },\n";
            //}
        }
        $k++;
    }
    if ($k>$to) {
        $html.= "{ key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'"._("next")." $maxresults "._("nets")."' }";
    }
    if ($html != "") $buffer .= preg_replace("/,$/", "", $html);
    $buffer .= "]";
    
    if ($buffer=="" || $buffer=="[]")
        echo "[{title:'"._("No assets found")."'}]";
    else 
        echo $buffer;
}
else if ($key=="all"){
    $buffer .= "[";
    $j = 0;
    foreach($all_cclass_hosts as $cclass => $hg) {
        if($j>=$from && $j<$to) {
            $li = "key:'all_$cclass', isLazy:true, url:'NODES:$cclass', icon:'../../pixmaps/theme/host_add.png', title:'$cclass <font style=\"font-weight:normal;font-size:80%\">(" . count($hg) . " "._("hosts").")</font>'\n";
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
    
    if ( $buffer=="" || $buffer=="[]" )
        echo "[{title:'"._("No assets found")."'}]";
    else 
        echo $buffer;
}

else if (preg_match("/all_(.*)/",$key,$found)){
    $html="";
    $buffer .= "[";
    $j = 1;
    $i = 0;
    
    foreach($all_cclass_hosts as $cclass => $hg) if ($found[1]==$cclass) {
        foreach($hg as $ip) {
            $fqdns = array();
            
            if($i>=$from && $i<$to) {
                $hname = ($ip == $ossim_hosts[$ip]) ? $ossim_hosts[$ip] : "$ip <font style=\"font-size:80%\">(" . $ossim_hosts[$ip] . ")</font>";
                $hname = utf8_encode($hname);
                
                if($hname!=$ip) {
                    $fqdns[] = $hname;
                }

                $host_data = Host::get_list($conn, "where ip='$ip'");
                
                $all_fqdn = explode(",", $host_data[0]->get_fqdns());
                
                foreach ($all_fqdn as $fqdn) {
                    $fqdn = trim($fqdn);
                    if ($fqdn!="") {
                        $fqdns[] = $fqdn;
                    }
                }
                if (count($fqdns)>0) {
                    $html.= "{ key:'host_$ip', url:'$ip', isLazy:true, icon:'../../pixmaps/theme/host.png', title:'$hname' },\n";
                }
                else {
                    $html.= "{ key:'$key.$j', url:'$ip', icon:'../../pixmaps/theme/host.png', title:'$hname' },\n";
                }
            }
            $i++;
        }
        $j++;
    }
    
    if ($i>$to) {
        $html .= "{ key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_group.png', title:'"._("next")." $maxresults "._("hosts")."' },";
    }
    
    if ($html != "") $buffer .= preg_replace("/,$/", "", $html);
    $buffer .= "]";
    
    if ($buffer=="" || $buffer=="[]")
        echo "[{title:'"._("No assets found")."'}]";
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
    
    if($hname!=$found[1]) {
        $fqdns[] = $hname;
    }

    $all_fqdn = explode(",", $host_data[0]->get_fqdns());
    
    foreach ($all_fqdn as $fqdn) {
        $fqdn = trim($fqdn);
        if ($fqdn!="") {
            $fqdns[] = $fqdn;
        }
    }
    
    $buffer = "[";

    $j = 1; 
    foreach ($fqdns as $fqdn) {
        $buffer .= "{key:'$key$j', url:'$fqdn', icon:'../../pixmaps/theme/host.png', title:'$fqdn <font style=\"font-weight:normal;font-size:80%\">(" . $found[1] .")</font>'\n},";
        $j++;
    }
    $buffer = preg_replace("/,$/", "", $buffer);
    $buffer .= "]";
    
    if ($buffer=="" || $buffer=="[]")
        echo "[{title:'"._("No assets found")."'}]";
    else 
        echo $buffer;
}
else if(preg_match("/e_(.*)_net$/",$key,$found))
{
	$entityPerms = Acl::entityPerms($conn,$found[1]);
	$all = count($entityPerms["assets"]);
	$nets = Net::get_all($conn);

    $buffer .= "[";
    $html = "";
    $p = 0;
    foreach($nets as $net) {
        $cidrs = explode(",",$net->get_ips());
        if (!$all || Acl::cidrs_allowed($cidrs,$entityPerms["assets"])) {
            if($p>=$from && $p<$to) {
                $net_title  = Util::htmlentities(utf8_encode($net->get_name()));
                $html .= "{url:'".$net->get_ips()."', icon:'../../pixmaps/theme/net.png', title:'$net_title <font style=\"font-size:80%\">(".$net->get_ips().")</font>'},\n";
            }
            $p++;
        }
    }

    if ($p>$to) {
        $html.= "{ key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'"._("next")." $maxresults "._("nets")."' }";
    }
    if ($html != "") $buffer .= preg_replace("/,$/", "", $html);
    
    $buffer .= "]";
    
    if ($buffer=="" || $buffer=="[]")
        echo "[{title:'"._("No assets found")."'}]";
    else 
        echo $buffer;
}
else if(preg_match("/e_(.*)_sensor/",$key,$found))
{
    $entityPerms = Acl::entityPerms($conn,$found[1]);
    $all = count($entityPerms["sensors"]);
    $sensors = Sensor::get_all($conn);
    
    $buffer .= "[";
    $j = 0;
    foreach($sensors as $sensor) 
	{
		if (!$all || $entityPerms["sensors"][$sensor->get_ip()]) 
		{
			$sensor_title  = Util::htmlentities(utf8_encode($sensor->get_name()));
			$li = "url:'".$sensor->get_ip()."', icon:'../../pixmaps/theme/server.png', title:'".$sensor_title."'\n";
			$buffer .= (($j > 0) ? "," : "") . "{ $li }";
			$j++;
		}
    }
	
    $buffer .= "]";
    
    if ($buffer=="" || $buffer=="[]")
        echo "[{title:'"._("No assets found")."'}]";
    else 
        echo $buffer;
}
else if ($key=="entities") {
    $entities = Acl::get_my_entities($conn);

    echo "[";
    
    if ( count($entities) > 0 )
    {
        
        foreach ($entities as $entity)
        {
            if ($entity['parent_id'] > 0 || $entity['type'] <= 0 ) 
                continue;
            
            if ( $flag ) 
                echo ",";
            
            $flag = true;
            
            $icon        = "../../pixmaps/theme/any.png";
            $e_key       = $entity['id'];
            $e_style     = "font-weight:bold";
            $entity_name = $entity['name'];
                    
            echo "{title:'<font style=\"$e_style\">".Util::htmlentities($entity_name)."</font> <font style=\"color:gray\">[".$entity['type_name']."]</font>', key:'e_".$e_key."', icon:'$icon', expand:true,  url:'ENTITY:".$e_key."', name:'".utf8_encode($entity_name)."'";
                    echochildrens($entities, $entity['id']);
            echo "}";
        }
    }
    else
        echo "{title:'"._("No Entities Found")."'}";
        
    echo "]";
    
}
else if ($key!="all") {
    $buffer .= "[ {title: '"._("ANY")."', key:'key1', icon:'../../pixmaps/theme/any.png', expand:true, children:[\n";
    $buffer .= "{ key:'hostgroup', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_group.png', title:'"._("Host Group")."'},\n";
    $buffer .= "{ key:'net', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'"._("Networks")."'},\n";
    $buffer .= "{ key:'netgroup', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net_group.png', title:'"._("Network Groups")."'},\n";
    if($prodemo)
        $buffer .= "{ key:'entities', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/company.png', title:'"._("Entities")."'},\n";
    $buffer .= "{ key:'all', page:'', isFolder:true, isLazy:true , icon:'../../pixmaps/theme/host.png', title:'"._("All Hosts")." <font style=\"font-weight:normal;font-size:80%\">(" . $total_hosts . " "._("hosts").")</font>'}\n";
    $buffer .= "] } ]";
    
    echo $buffer;
}

error_reporting(0);
if ($key!="entities") {
    $f = fopen($cachefile,"w");
    fputs($f,$buffer);
    fclose($f);
}

function echochildrens($entities,$parent_id ) {
    echo ",children:[";
        
	if( $parent_id != "" ) 
	{
        echo "{title:'<font style=\"font-weight:normal\">"._("All Assets")."</font>', url:'AllAssets', key:'e_".$parent_id."_allassets', icon:'../../pixmaps/menu/assets.gif', isFolder:false, expand:true,";
        echo "children:[ ";
        
        echo "{ key:'e_".$parent_id."_net', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'"._("Networks")."'},";
        echo "{ key:'e_".$parent_id."_sensor', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/server.png', title:'"._("Sensors")."'}";
        
        echo "]}";
    }
			
	$children = $entities[$parent_id]['children'];
    
	if ( !empty($children) )
	{
		foreach ($children as $child_id)
		{
			$icon      = "../../pixmaps/theme/any.png";
			$child     = $entities[$child_id];
			$child_key = $child_id;
			$style     = "font-weight:bold";
			
			if ( $child['parent_id'] == $parent_id )
			{
				echo ",{title:'<font style=\"$style\">".Util::htmlentities($child['name'])."</font> <font style=\"color:gray\">[".$child['type_name']."]</font>', url:'ENTITY:".$child_key."', key:'e_".$child_key."', icon:'$icon', expand:true, name:'".utf8_encode($child['name'])."'";
					echochildrens($entities, $child_key );
				echo "}";
			}	
		}
	}
    echo "]";
}	
?>
