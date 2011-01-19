<?
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
require_once ('classes/Session.inc');
require_once ('classes/Sensor.inc');
require_once ('classes/Net_group.inc');
require_once ('ossim_db.inc');

$key = GET('key');
ossim_valid($key, OSS_NULLABLE, OSS_TEXT, OSS_PUNC, 'illegal:' . _("key"));

$page = intval(GET('page')); 
ossim_valid($page, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("page")); 

if (ossim_error()) {
    die(ossim_error());
}

if ($page == "" || $page<=0) $page = 1;
$maxresults = 200;
$to = $page * $maxresults;
$from = $to - $maxresults;
$nextpage = $page + 1;

$withusers = ($_SESSION["_with_users"]==1) ? true : false;
$withsiemcomponents = ($_SESSION["_with_siem_components"]==1) ? true : false;


/* connect to db */
$db = new ossim_db();
$conn = $db->connect();

/* load hosts and nets */
$ossim_hosts = $all_hosts = array();
$total_hosts = 0;
$ossim_nets = array();
$all_cclass_hosts = array();
$buffer = "";
$where_host = "";

if(preg_match("/host_(.*)/",$key,$found)) {
    $where_host = "h, host_sensor_reference hsr WHERE h.ip=hsr.host_ip AND hsr.sensor_name='".$found[1]."'";
}
else if(preg_match("/all_(.*)/",$key,$found)) {
    $aux = explode("_",$found[1],2);
    if(count($aux)==2) {
        $where_host = "h, host_sensor_reference hsr WHERE h.ip=hsr.host_ip AND hsr.sensor_name='".$aux[1]."' AND h.ip LIKE '".$aux[0].".%'";
        $key="all_".$aux[0];
    }
}

if ($host_list = Host::get_list_pag($conn, $where_host, "ORDER BY hostname")) {
	foreach($host_list as $host){
        //echo "Fuera ".$host->get_ip()."<br>";
		if ($filter == "" || ($filter != "" && (preg_match("/$filter/i", $host->get_ip()) || preg_match("/$filter/i", $host->get_hostname())))) 
		{
        //echo "Dentro ".$host->get_ip()."<br>";
			$ossim_hosts[$host->get_ip() ] = $host->get_hostname();
			$all_hosts[$host->get_ip() ] = 1;
			$cclass = preg_replace("/(\d+\.)(\d+\.)(\d+)\.\d+/", "\\1\\2\\3", $host->get_ip());
			$all_cclass_hosts[$cclass][] = $host->get_ip();
			$total_hosts++;
		}
    }
	if ($hg_list = Host_group::get_list($conn, "ORDER BY name"))
	{
		foreach($hg_list as $hg) {
			$hg_hosts = $hg->get_hosts($conn, $hg->get_name());
			foreach($hg_hosts as $hosts) {
				$ip = $hosts->get_host_ip();
				unset($all_hosts[$ip]);
			}
		}
	}
}

$wherenet = ($filter!="") ? "WHERE ips like '%$filter%' ORDER BY name" : "ORDER BY name";
$net_list = Net::get_list($conn, $wherenet);

/* All assets*/

if ($key == "hostgroup") {
    if (count($hg_list)>0) {
        $buffer .= "[";
        $j = 0;
        foreach($hg_list as $hg) {
            if($j>=$from && $j<$to) {
                $hg_name  = $hg->get_name();
				$hg_key   = base64_encode($hg->get_name());
				$hg_title = utf8_encode($hg->get_name());
                $li = "key:'hostgroup_$hg_key', isLazy:true , url:'../host/newhostgroupform.php?name=".urlencode($hg_name)."', icon:'../../pixmaps/theme/host_group.png', title:'$hg_title'\n";
                $buffer .= (($j > $from) ? "," : "") . "{ $li }\n";
            }
            $j++;
        }
        if ($j>$to) {
            $li = "key:'hostgroup', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_group.png', title:'"._("next")." $maxresults "._("host group")."'";
            $buffer .= ",{ $li }\n";
        }
        $buffer .= "]";
    }
    
    if ($buffer=="" || $buffer=="[]")
        $buffer = "[{title:'"._("No Assets Found")."'}]";
        
    echo $buffer;
}
else if (preg_match("/snet_(.*)/",$key,$found)) {
    $buffer .= "[";
    $li = array();
    
    $sensor_assets = Sensor::get_assets($conn, $found[1]);
    
    foreach($sensor_assets["net"] as $net_name => $net_ips) {
        $li[] = "{ key:'net_".base64_encode($net_name)."', url:'../net/newnetform.php?name=$net_name', isLazy:true, icon:'../../pixmaps/theme/host.png', title:'$net_name  <font style=\"font-size:80%\">(".$net_ips.")</font>' }";
    }
    
    $buffer .= implode(",", $li);
    $buffer .= "]";

    if ($buffer=="" || $buffer=="[]")
        $buffer = "[{title:'"._("No Assets Found")."'}]";
    echo $buffer;
}
else if (preg_match("/shostgroup_(.*)/",$key,$found)) {
    $buffer .= "[";
    $li = array();
    
    $sensor_assets = Sensor::get_assets($conn, $found[1]);
    
    foreach($sensor_assets["hgroup"] as $hg_name => $v) {
        $hg_key   = base64_encode($hg_name);
        $hg_title = utf8_encode($hg_name);
        $li[]= "{ key:'hostgroup_$hg_key', isLazy:true , url:'../host/newhostgroupform.php?name=".urlencode($hg_name)."', icon:'../../pixmaps/theme/host_group.png', title:'$hg_title' }";
    }
    
    $buffer .= implode(",", $li);
    $buffer .= "]";

    if ($buffer=="" || $buffer=="[]")
        $buffer = "[{title:'"._("No Assets Found")."'}]";
    echo $buffer;
}
else if (preg_match("/snetgroup_(.*)/",$key,$found)) {
    $buffer .= "[";
    $li = array();
    
    $sensor_assets = Sensor::get_assets($conn, $found[1]);
    
    foreach($sensor_assets["ngroup"] as $net_group_name => $v) {
        $ng_key         = base64_encode($net_group_name);
        $ng_title       = utf8_encode($net_group_name);
        $li[] = "{ key:'netgroup_$ng_key', isLazy:true , url:'../net/newnetgroupform.php?name=".urlencode($net_group_name)."', icon:'../../pixmaps/theme/net_group.png', title:'$ng_title' }";
    }
    
    $buffer .= implode(",", $li);
    $buffer .= "]";

    if ($buffer=="" || $buffer=="[]")
        $buffer = "[{title:'"._("No Assets Found")."'}]";
    echo $buffer;
}
else if (preg_match("/hostgroup_(.*)/",$key,$found)) {
    if ($hg_hosts = $hg->get_hosts($conn, base64_decode($found[1]))) {
        $k = 0;
        $html = "";
        $buffer .= "[";
        foreach($hg_hosts as $hosts) {
            $host_ip = $hosts->get_host_ip();
            if ($k>=$from && $k<$to) { // test filter
                $hname = ($ossim_hosts[$host_ip]!="") ? "$host_ip <font style=\"font-size:80%\">(" . $ossim_hosts[$host_ip] . ")</font>" : $host_ip;
                $html.= "{ key:'$key.$k', url:'../host/modifyhostform.php?ip=$host_ip', icon:'../../pixmaps/theme/host.png', title:'$hname' },\n";
            }
            $k++;
        }
        if ($html != "") $buffer .= preg_replace("/,$/", "", $html);
        if ($k>$to) {
            $li = "key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_group.png', title:'"._("next")." $maxresults "._("hosts")."'";
            $buffer .= ",{ $li }\n";
        }
        $buffer .= "]";
    }
    if ($buffer=="" || $buffer=="[]")
        $buffer = "[{title:'"._("No Assets Found")."'}]";
        
    echo $buffer;
}
else if ($key == "net") {
    if (count($net_list)>0) {
        $buffer .= "[";
        $j = 0;
        foreach($net_list as $net) {
            if ($j>=$from && $j<$to) {
                $net_name  = $net->get_name();
				$net_key   = base64_encode($net->get_name());
				$net_title = utf8_encode($net->get_name());
                $ips = $net->get_ips();
                $li = "key:'net_$net_key', isLazy:true, url:'../net/newnetform.php?name=".urlencode($net_name)."', icon:'../../pixmaps/theme/net.png', title:'$net_title <font style=\"font-size:80%\">(".$ips.")</font>'\n";
                $buffer .= (($j > $from) ? "," : "") . "{ $li }\n";
            }
            $j++;
        }
        if ($j>$to) {
            $li = "key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'"._("next")." $maxresults "._("nets")."'";
            $buffer .= ",{ $li }\n";
        }
        $buffer .= "]";
    }
    if ($buffer=="" || $buffer=="[]")
        $buffer = "[{title:'"._("No Assets Found")."'}]";
    echo $buffer;
}
else if (preg_match("/net_(.*)/",$key,$found)){
    $hostin = array();
   
	if ($net_list1 = Net::get_list($conn, "WHERE name='".base64_decode($found[1])."'")) {
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
        if ($k>=$from && $k<$to) {
            $host_title = utf8_encode($host_name);
			$html.= "{ key:'$key.$k', url:'../host/modifyhostform.php?ip=$ip', icon:'../../pixmaps/theme/host.png', title:'$ip <font style=\"font-size:80%\">($host_title)</font>' },\n";
        }
        $k++;
    }
    if ($html != "") $buffer .= preg_replace("/,$/", "", $html);
    if ($k>$to) {
        $li = "key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host.png', title:'"._("next")." $maxresults "._("hosts")."'";
        $buffer .= ",{ $li }\n";
    }
    $buffer .= "]";

    if ($buffer=="" || $buffer=="[]")
        $buffer = "[{title:'"._("No Assets Found")."'}]";
    echo $buffer;
}
else if ($key=="netgroup") {
    if ($net_group_list = Net_group::get_list($conn, "ORDER BY name")) {
        $buffer .= "[";
        $j = 0;
        foreach($net_group_list as $net_group) {
            if ($j>=$from && $j<$to) {
                $net_group_name = $net_group->get_name();
				$ng_key         = base64_encode($net_group_name);
				$ng_title       = utf8_encode($net_group_name);
                $nets           = $net_group->get_networks($conn, $net_group_name);
                $li = "key:'netgroup_$ng_key', isLazy:true , url:'../net/newnetgroupform.php?name=".urlencode($net_group_name)."', icon:'../../pixmaps/theme/net_group.png', title:'$ng_title'\n";
                $buffer .= (($j > $from) ? "," : "") . "{ $li }\n";
            }
            $j++;
        }
        if ($j>$to) {
            $li = "key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net_group.png', title:'"._("next")." $maxresults "._("net group")."'";
            $buffer .= ",{ $li }\n";
        }
        $buffer .= "]";
    }
    
    if ($buffer=="" || $buffer=="[]")
        $buffer = "[{title:'"._("No Assets Found")."'}]";
    echo $buffer;
}
else if (preg_match("/netgroup_(.*)/",$key,$found)){
    $buffer .= "[";
    $html = "";
    $nets = Net_group::get_networks($conn, base64_decode($found[1]));
	$k = 1;
    $j = 0;
    foreach($nets as $net) {
        $net_name  = $net->get_net_name();
		$net_title = utf8_encode($net_name);

        if ($j>=$from && $j<$to) {
            $html.= "{ key:'$key.$k', url:'../net/newnetform.php?name=".urlencode($net_name)."', icon:'../../pixmaps/theme/net.png', title:'$net_title' },\n";
            $k++;
        }
        $j++;
    }
    if ($html != "") $buffer .= preg_replace("/,$/", "", $html);
    if ($j>$to) {
        $li = "key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'"._("next")." $maxresults "._("net")."'";
        $buffer .= ",{ $li }\n";
    }
    $buffer .= "]";
    
    if ($buffer=="" || $buffer=="[]")
        $buffer = "[{title:'"._("No Assets Found")."'}]";
    echo $buffer;
}
else if ($key=="all"){
    $buffer .= "[";
    $j = 0;
    foreach($all_cclass_hosts as $cclass => $hg) {
        if ($j>=$from && $j<$to) {
            $li = "key:'all_$cclass', isLazy:true, icon:'../../pixmaps/theme/host_add.png', title:'$cclass <font style=\"font-weight:normal;font-size:80%\">(" . count($hg) . " "._("hosts").")</font>'\n";
            $buffer .= (($j > $from) ? "," : "") . "{ $li }\n";
        }
        $j++;
    }
    if ($j>$to) {
        $li = "key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_add.png', title:'"._("next")." $maxresults "._("c-class")."'";
        $buffer .= ",{ $li }\n";
    }
    $buffer .= "]";

    if ($buffer=="" || $buffer=="[]")
        $buffer = "[{title:'"._("No Assets Found")."'}]";
    echo $buffer;
}

else if (preg_match("/all_(.*)/",$key,$found)) {
    $html="";
    $buffer .= "[";
    $j = 1;
    $i = 0;
    
    foreach($all_cclass_hosts as $cclass => $hg) if ($found[1]==$cclass) {
        foreach($hg as $ip) {
                if ($i>=$from && $i<$to) {
                    $hname = ($ip == $ossim_hosts[$ip]) ? $ossim_hosts[$ip] : "$ip <font style=\"font-size:80%\">(" . $ossim_hosts[$ip] . ")</font>";
                    $hname = utf8_encode($hname);
                    $html.= "{ key:'$key.$j', url:'../host/modifyhostform.php?ip=$ip', icon:'../../pixmaps/theme/host.png', title:'$hname' },\n";
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
        $buffer = "[{title:'"._("No Assets Found")."'}]";
    echo $buffer;
}

/* Entities */
else if(preg_match("/u_(.*)_netgroup/",$key,$found)){
    $netgroup_list = Net_group::get_list($conn);
    $buffer .= "[";
    $j = 0;
    foreach($netgroup_list as $netgroup) if (Session::groupAllowed($conn, $netgroup->get_name(), $found[1])) {
        $netgroup_name = $netgroup->get_name();
        $netgroup_title = utf8_encode($netgroup_name);
        $li = "url:'../net/newnetgroupform.php?name=".urlencode($netgroup_name)."', icon:'../../pixmaps/theme/net_group.png', title:'$netgroup_title'\n";
        $buffer .= (($j > 0) ? "," : "") . "{ $li }";
        $j++;
    }
    $buffer .= "]";
    if ($buffer=="[]")  $buffer = "[{title:'"._("No Net Groups Found")."'}]";
    echo $buffer;
}
else if(preg_match("/u_(.*)_net/",$key,$found)){
    $net_list = Net::get_list($conn);
    $allowedNets = Session::allowedNets($found[1]);
    $buffer .= "[";
    $j = 0;
    foreach($net_list as $net) if (in_array($net->get_ips(), explode(",",$allowedNets)) || $allowedNets=="") { 
        $net_name = $net->get_name();
        $ips = $net->get_ips();
        $li = "url:'../net/newnetform.php?name=".urlencode($net_name)."', icon:'../../pixmaps/theme/net.png', title:'$net_name <font style=\"font-size:80%\">(".$ips.")</font>'\n";
        $buffer .= (($j > 0) ? "," : "") . "{ $li }";
        $j++;
    }
    $buffer .= "]";
    if ($buffer=="[]")  $buffer = "[{title:'"._("No Nets Found")."'}]";
    echo $buffer;
}
else if(preg_match("/u_(.*)_sensor/",$key,$found)){
    $sensor_list = Sensor::get_list($conn);
    $allowedSensors = Session::allowedSensors($found[1]);
    $buffer .= "[";
    $j = 0;
    foreach($sensor_list as $sensor) if (in_array($sensor->get_ip(), explode(",",$allowedSensors)) || $allowedSensors=="") {
        $sensor_name = $sensor->get_name();
        $sensor_title = utf8_encode($sensor_name);
        $li = "url:'../sensor/interfaces.php?sensor=".$sensor->get_ip()."&name=".urlencode($sensor_name)."', icon:'../../pixmaps/theme/server.png', title:'$sensor_title'\n";
        $buffer .= (($j > 0) ? "," : "") . "{ $li }";
        $j++;
    }
    $buffer .= "]";
    if ($buffer=="[]")  $buffer = "[{title:'"._("No Sensors Found")."'}]";
    echo $buffer;
}
else if(preg_match("/u_(.*)/",$key,$found)){
    echo "[";
    echo "{ key:'u_".$found[1]."_net', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'"._("Networks")."'},";
    echo "{ key:'u_".$found[1]."_sensor', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/server.png', title:'"._("Sensors")."'},";
    echo "{ key:'u_".$found[1]."_netgroup', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net_group.png', title:'"._("Networks Groups")."'}";
    echo "]";
}
else if(preg_match("/e_(.*)_netgroup/",$key,$found)){
    $entityPerms = Acl::entityPerms($conn,$found[1]);
    $nets_allowed = array_keys($entityPerms["assets"]);
    $net_groups = Net_group::get_list($conn);
    $netgroup_list = array();
    foreach($net_groups as $net_group) {
        $allowed=1;
        $nets = $net_group->get_networks($conn, $net_group->get_name());
        foreach($nets as $net) {
            $net_ips = explode(",",$net->get_net_ips($conn));
            foreach ($net_ips as $cidr) if (!in_array($cidr,$nets_allowed) && count($nets_allowed)>0) $allowed=0;
        }
        if ($allowed) $netgroup_list[] = $net_group->get_name();
    }
    //
    $buffer .= "[";
    $j = 0;
    foreach($netgroup_list as $netgroup_name) {
    	$netgroup_title = utf8_encode($netgroup_name);
        $li = "url:'../net/newnetgroupform.php?name=".urlencode($netgroup_name)."', icon:'../../pixmaps/theme/net_group.png', title:'$netgroup_title'\n";
        $buffer .= (($j > 0) ? "," : "") . "{ $li }";
        $j++;
    }
    $buffer .= "]";
    if ($buffer=="[]")  $buffer = "[{title:'"._("No Net Groups Found")."'}]";
    echo $buffer;
}
else if(preg_match("/e_(.*)_net/",$key,$found)){
    $entityPerms = Acl::entityPerms($conn,$found[1]);
    $nets = Net::get_all($conn);
    $allowed_nets = array_keys($entityPerms["assets"]);
    $buffer .= "[";
    $j = 0;
    foreach($nets as $net) if (in_array($net->get_name(),$allowed_nets) || !count($allowed_nets)) {
        $net_title  = utf8_encode($net->get_name());
		$li = "url:'../net/newnetform.php?name=".urlencode($net->get_name())."', icon:'../../pixmaps/theme/net.png', title:'".$net_title." <font style=\"font-size:80%\">(".$net->get_ips().")</font>'\n";
        $buffer .= (($j > 0) ? "," : "") . "{ $li }";
        $j++;
    }
    $buffer .= "]";
    if ($buffer=="[]")  $buffer = "[{title:'"._("No Nets Found")."'}]";
    echo $buffer;
}
else if(preg_match("/e_(.*)_sensor/",$key,$found)){
    $entityPerms = Acl::entityPerms($conn,$found[1]);
    $sensors = Sensor::get_all($conn);
    $allowed_sensors = array_keys($entityPerms["sensors"]);
    $buffer .= "[";
    $j = 0;
    foreach($sensors as $sensor) if (in_array($sensor->get_ip(),$allowed_sensors) || !count($allowed_sensors)) {
        $sensor_title  = utf8_encode($sensor->get_name());
		$li = "url:'../sensor/interfaces.php?sensor=".$sensor->get_ip()."&name=".urlencode($sensor->get_name())."', icon:'../../pixmaps/theme/server.png', title:'".$sensor_title."'\n";
        $buffer .= (($j > 0) ? "," : "") . "{ $li }";
        $j++;
    }
    $buffer .= "]";
    if ($buffer=="[]")  $buffer = "[{title:'"._("No Sensor Found")."'}]";
    echo $buffer;
}
else if(preg_match("/ae_(.*)/",$key,$found)){
    echo "[";
    echo "{ key:'e_".$found[1]."_net', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'"._("Networks")."'},";
    echo "{ key:'e_".$found[1]."_sensor', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/server.png', title:'"._("Sensors")."'},";
    echo "{ key:'e_".$found[1]."_netgroup', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net_group.png', title:'"._("Networks Groups")."'}";
    echo "]";
}
else if(preg_match("/ue_(.*)/",$key,$found)){
    $admin_users = array();
    $entities = Acl::get_entities($conn);
    $entities_list = $entities[0];
    foreach ($entities_list as $entity) {
        $admin_users[$entity["id"]] = $entity["admin_user"];
    }
    $buffer = "[";
    $users = Acl::get_users($conn);
    $user_list = $users[0];
    $j = 0;
    foreach ($user_list as $user){
        if(in_array($found[1],$user['entities'])){
            if($admin_users[$found[1]]==$user["login"]) 
                $icon = "../../pixmaps/user-business.png";
            else
                $icon = "../../pixmaps/user-green.png";
            $li = "title:'".$user["login"]."', key:'u_".$user["login"]."', icon:'$icon', isLazy:true";
            $buffer .= (($j > 0) ? "," : "") . "{ $li }";
            $j++;
        }
    }
    $buffer .= "]";
    echo $buffer;
}
else if(preg_match("/ou/",$key)) {
    $users_aux = Acl::get_orph_users($conn);
    $j=0;
    $buffer = "[";
    foreach ($users_aux as $user){
        if($user["login"]!="admin"){
                $icon = "../../pixmaps/user-green.png";
            $li = "title:'".$user["login"]."', key:'u_".$user["login"]."', icon:'$icon', isLazy:true";
            $buffer .= (($j > 0) ? "," : "") . "{ $li }";
            $j++;
        }
    }
    $buffer .= "]";
    echo $buffer;
}
else if(preg_match("/servers/",$key)) {
    require_once ('classes/Server.inc');
    
    $servers = Server::get_list($conn);
    $j=0;
    $buffer = "[";
    foreach ($servers as $server){
        if($user["login"]!="admin"){
                $icon = "../../pixmaps/theme/host.png";
            $li = "title:'".utf8_encode($server->get_name())."', icon:'$icon', url:'../server/newserverform.php?name=".utf8_encode($server->get_name())."'";
            $buffer .= (($j > 0) ? "," : "") . "{ $li }";
            $j++;
        }
    }
    $buffer .= "]";
    echo $buffer;
}
else if(preg_match("/databases/",$key)) {
    require_once ('classes/Databases.inc');
    
    $databases = Databases::get_list($conn);
    $j=0;
    $buffer = "[";
    foreach ($databases as $database){
        if($user["login"]!="admin"){
            $icon = "../../pixmaps/database.png";
            $li = "title:'".utf8_encode($database->get_name())."', icon:'$icon', url:'../server/newdbsform.php?name=".utf8_encode($database->get_name())."'";
            $buffer .= (($j > 0) ? "," : "") . "{ $li }";
            $j++;
        }
    }
    $buffer .= "]";
    echo $buffer;
}
else if(preg_match("/sensors/",$key)) {
    
    $sensors = Sensor::get_list($conn);
    $j=0;
    $buffer = "[";
    foreach ($sensors as $sensor){
        if($user["login"]!="admin"){
            $icon = "../../pixmaps/server.png";
            
            $related_assets = Sensor::get_assets($conn, $sensor->get_name());

            if (count($related_assets["host"])==0 && count($related_assets["net"])==0 && count($related_assets["hgroup"])==0 && count($related_assets["ngroup"])==0)
                $li = "title:'".utf8_encode($sensor->get_name())."', icon:'$icon', url:'../sensor/interfaces.php?sensor=".utf8_encode($sensor->get_name())."&name=".utf8_encode($sensor->get_name())."'";
            else
                $li = "key:'sensor_".utf8_encode($sensor->get_name())."' ,isLazy:true, title:'".utf8_encode($sensor->get_name())."', icon:'$icon', url:'../sensor/interfaces.php?sensor=".utf8_encode($sensor->get_name())."&name=".utf8_encode($sensor->get_name())."'";

            $buffer .= (($j > 0) ? "," : "") . "{ $li }";
            $j++;
        }
    }
    $buffer .= "]";
    echo $buffer;
}
else if(preg_match("/sensor_(.*)/",$key,$found)) {
        $buffer = "[";
        $buffer .= "{ key:'shostgroup_".$found[1]."', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_group.png', title:'"._("Host Group")."'},\n";
        $buffer .= "{ key:'snet_".$found[1]."', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'"._("Networks")."'},\n";
        $buffer .= "{ key:'snetgroup_".$found[1]."', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net_group.png', title:'"._("Network Groups")."'},\n";
        $buffer .= "{ key:'host_".$found[1]."', page:'', isFolder:true, isLazy:true , icon:'../../pixmaps/theme/host.png', title:'"._("Hosts")."'}\n";
        $buffer .= "]";
        echo $buffer;
}
else if (preg_match("/host_(.*)/",$key,$found)) {
    $buffer .= "[";
    $j = 0;
    foreach($all_cclass_hosts as $cclass => $hg) {
        if ($j>=$from && $j<$to) {
            $li = "key:'all_".$cclass."_".$found[1]."', isLazy:true, icon:'../../pixmaps/theme/host_add.png', title:'$cclass <font style=\"font-weight:normal;font-size:80%\">(" . count($hg) . " "._("hosts").")</font>'\n";
            $buffer .= (($j > $from) ? "," : "") . "{ $li }\n";
        }
        $j++;
    }
    if ($j>$to) {
        $li = "key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_add.png', title:'"._("next")." $maxresults "._("c-class")."'";
        $buffer .= ",{ $li }\n";
    }
    $buffer .= "]";

    if ($buffer=="" || $buffer=="[]")
        $buffer = "[{title:'"._("No Assets Found")."'}]";
    echo $buffer;
}

else {
    /*   All assets tree   */
    if ($key!="all") {
        $buffer .= "[ {title: '"._("All assets")."', key:'key1', icon:'../../pixmaps/theme/any.png', expand:true, children:[\n";
        $buffer .= "{ key:'hostgroup', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_group.png', title:'"._("Host Group")."'},\n";
        $buffer .= "{ key:'net', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'"._("Networks")."'},\n";
        $buffer .= "{ key:'netgroup', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net_group.png', title:'"._("Network Groups")."'},\n";
        $buffer .= "{ key:'all', page:'', isFolder:true, isLazy:true , icon:'../../pixmaps/theme/host.png', title:'"._("All Hosts")." <font style=\"font-weight:normal;font-size:80%\">(" . $total_hosts . " "._("hosts").")</font>'}\n";
        $buffer .= "] },";
    }

    if ($buffer=="" || $buffer=="[]")
        $buffer = "[{title:'"._("No Hosts Found")."'}]";

    echo $buffer;
    
   /* SIEM Components tree */
    
    if($withsiemcomponents) {
        echo "{title:'<font style=\"font-weight:normal\">"._("SIEM Components")."</font>', isFolder:true, icon:'../../pixmaps/theme/server_role.png', expand:true, children:[";
        echo "{ key:'servers', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_os.png', title:'"._("Servers")."' },";
        echo "{ key:'databases', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/databases.png', title:'"._("Databases")."' },";
        echo "{ key:'sensors', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/server.png', title:'"._("Sensors")."' },";
        echo "]},";
    }

    /*   Entities tree   */
    list($entities,$children,$num_rows) = Acl::get_entities_and_users($conn);
    $users_aux = Acl::get_orph_users($conn); $users = array();
    foreach ($users_aux as $user) if (Acl::userAllowed($user['login'])) $users[] = $user;
    //print_r($entities);
    //print_r($children);
    $entities_types_aux = Acl::get_entities_types($conn);
    $entities_types = array();
    foreach ($entities_types_aux as $etype) { 
        $entities_types[$etype['id']] = $etype;
    }
    echo "{title:'<font style=\"font-weight:normal\">"._("Entities")."</font>', isFolder:true, icon:'../../pixmaps/company.png', expand:true, children:[";
    $flag = false;
    // Users 
    if (count($users) > 0 && $withusers) {
        foreach ($users as $user) {
            if($user['login']!="admin") {
                $icon = "../../pixmaps/user-green.png";
                $style = (Acl::userAllowed($user['login']) == 2) ? "font-weight:bold;text-decoration:underline" : "font-weight:bold";
                if ($flag) echo ",";
                echo "{title:'<font style=\"$style\">".utf8_encode($user['login'])."</font>', url:'USER:".$user['login']."', isFolder:true, isLazy:true, key:'u_".$user['login']."', icon:'$icon'}";
                $flag = true;
            }
        }
    }

    // Entities
    foreach ($entities as $entity) {
        if ($entity['parent_id'] > 0 || $entity['type'] <= 0 || !Acl::entityAllowed($entity['id'])) continue;
        if ($flag) echo ",";
        $flag = true;
        //$icon = (file_exists("../pixmaps/".strtolower(str_replace(" ","_",$entities_types[$entity['type']]['name'])).".png")) ? "../../pixmaps/".strtolower(str_replace(" ","_",$entities_types[$entity['type']]['name'])).".png" : "../../pixmaps/theme/any.png";
        $icon = "../../pixmaps/theme/any.png";
        $e_key = (Acl::entityAllowed($entity['id']) == 2) ? $entity['id'] : "";
        $e_style = "font-weight:bold";
        echo "{title:'<font style=\"$e_style\">".utf8_encode($entity['name'])."</font> <font style=\"color:gray\">[".$entities_types[$entity['type']]['name']."]</font>', key:'e_".$e_key."', icon:'$icon', expand:true, name:'".$entity['name']."'";
        if (count($children[$entity['id']]) > 0) {
            echochildrens($entities,$children,$children[$entity['id']],$entities_types,$entity['id'],$withusers);
        }
        echo "}";
    }
    if ($withusers) echo "]},{title:'<font style=\"font-weight:normal\">"._("Assets by user")."</font>', isFolder:true, icon:'../../pixmaps/menu/assets.gif', isLazy:true, key:'ou'}";
    else echo "]}";
    echo "]";
}
function echochildrens($entities,$children,$childs,$entities_types,$parent_id,$withusers) {
    echo ",children:[";
    //$flag = false;
    if($parent_id!="") {
        echo "{title:'<font style=\"font-weight:normal\">"._("All Assets")."</font>', key:'ae_".$parent_id."', icon:'../../pixmaps/menu/assets.gif', isFolder:true, isLazy:true}";
        if ($withusers) echo ",{title:'<font style=\"font-weight:normal\">"._("Assets by user")."</font>', key:'ue_".$parent_id."', icon:'../../pixmaps/menu/assets.gif', isFolder:true, isLazy:true}";
    }
    foreach ($childs as $child_id) {
        $child = $entities[$child_id];
        if ($child['type'] < 0) continue;
        if ($child['type'] > 0 && !Acl::entityAllowed($child_id)) continue;
        if ($child['type'] == 0 && !Acl::userAllowed($child['name'])) continue;
        //if ($flag) echo ",";
        //$flag = true;
        //$icon = (file_exists("../pixmaps/".strtolower(str_replace(" ","_",$entities_types[$child['type']]['name'])).".png")) ? "../../pixmaps/".strtolower(str_replace(" ","_",$entities_types[$child['type']]['name'])).".png" : "../../pixmaps/theme/any.png";
        $icon = "../../pixmaps/theme/any.png";
        if ($child['type'] == 0) $icon = "../../pixmaps/user-green.png"; // user
        if ($child['type'] == -1) $icon = "../../pixmaps/building_key.png"; // template
        if ($child['isadmin'][$parent_id]) $icon = "../../pixmaps/user-business.png";
        $t = ($child['type'] <= 0) ? "" : "[".$entities_types[$child['type']]['name']."]";
        if ($child['type'] == 0) {
            $child_key = (Acl::userAllowed($child_id) == 2) ? $child_id : "";
            $style = "";
        } elseif ($child['type'] == -1) {
            $child_key = $child_id;
        } else {
            $child_key = (Acl::entityAllowed($child_id) == 2) ? $child_id : "";
            $style = "font-weight:bold";
        }

        if(!($child['type'] == 0 || $child['isadmin'][$parent_id])) // entity
            echo ",{title:'<font style=\"$style\">".utf8_encode($child['name'])."</font> <font style=\"color:gray\">".$t."</font>', key:'e_".$child_key."', icon:'$icon', expand:true, name:'".$child['name']."'";

        if ($child['type'] > 0 && count($children[$child['id']]) > 0) // recursive unless template case
            echochildrens($entities,$children,$children[$child['id']],$entities_types,$child['id'],$withusers);
            
        if(!($child['type'] == 0 || $child['isadmin'][$parent_id])) 
            echo "}";
    }
    echo "]";
}
?>
