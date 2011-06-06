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
require_once ('classes/Security.inc');
require_once ('classes/Sensor.inc');
require_once ('classes/Session.inc');
require_once ('classes/Util.inc');
require_once ('classes/Host_group.inc');
require_once ('classes/Net.inc');
require_once ('classes/Net_group.inc');
require_once ('classes/Net_group_reference.inc');

Session::logcheck("MenuPolicy", "ToolsScan");

$filter = GET('filter');
$key    = GET('key');
$page   = intval(GET('page'));

ossim_valid($filter, OSS_NULLABLE, OSS_ALPHA, OSS_DIGIT, OSS_PUNC, 'illegal:' . _("Filter"));
ossim_valid($key, OSS_NULLABLE, OSS_TEXT, OSS_PUNC, 'illegal:' . _("key"));
ossim_valid($page, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("page"));

if (ossim_error()) {
    die(ossim_error());
}

if ($filter == "undefined") 
	$filter = "";
	
if ($page == "" || $page<=0) 
	$page = 1;
	
$maxresults = 200;
$to         = $page * $maxresults;
$from       = $to - $maxresults;
$nextpage   = $page + 1;

require_once ('classes/Host.inc');
require_once ('classes/Net.inc');
require_once ('ossim_db.inc');

$db   = new ossim_db();
$conn = $db->connect();

$ossim_hosts      = array();
$total_hosts      = 0;
$all_cclass_hosts = array();
$buffer = "";

if ($key == "" || preg_match("/^(hosts)/",$key)) 
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

if ( $key == "hosts" )
{
    $buffer .= "[";
    $j = 0;

    foreach($all_cclass_hosts as $cclass => $hg) 
	{
        if ($j>=$from && $j<$to) {
            $li = "key:'hosts_$cclass', isLazy:true, icon:'../../pixmaps/theme/host_add.png', title:'$cclass <font style=\"font-weight:normal;font-size:80%\">(" . count($hg) . " "._("hosts").")</font>'\n";
            $buffer .= (($j > $from) ? "," : "") . "{ $li }\n";
        }
        $j++;
    }
    
	if ($j>$to) {
        $li = "key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_add.png', title:'"._("next")." $maxresults "._("c-class")."'";
        $buffer .= ",{ $li }\n";
    }
    
	$buffer .= "]";

}
else if ( preg_match("/hosts_(.*)/",$key,$found) ) 
{
    $html="";
    $buffer .= "[";
    $j = 1;
    $i = 0;
    
    foreach($all_cclass_hosts as $cclass => $hg) if ($found[1]==$cclass) 
	{
        foreach($hg as $ip) 
		{
                if ($i>=$from && $i<$to) {
                    $hname = ($ip == $ossim_hosts[$ip]) ? $ossim_hosts[$ip] : "$ip <font style=\"font-size:80%\">(" . $ossim_hosts[$ip] . ")</font>";
                    
					$hname = utf8_encode($hname);
				    $html .= "{ key:'HOST:".$ip."', asset_data:'$ip/32', icon:'../../pixmaps/theme/host.png', title:'$hname' },\n";
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
}
elseif ( $key == "host_group" ) 
{
	$hg_list = Host_group::get_list($conn, "", "ORDER BY name");
	
    if (count($hg_list)>0) 
	{
        $buffer .= "[";
        $j = 0;
        foreach($hg_list as $hg) 
		{
            if($j>=$from && $j<$to) 
			{
                $hg_name = $hg->get_name();
				$hg_key  = utf8_encode("HOSTGROUP:".$hg_name);
				
				$asset_data = array();
				foreach ($hg->get_hosts($conn, $hg_name) as $k => $v)
					$asset_data[] = $v->get_host_ip()."/32"; 
								
                $li      = "key:'".$hg_key."', asset_data:'".implode(" ", $asset_data)."', icon:'../../pixmaps/theme/host_group.png', title:'".Util::htmlentities($hg->get_name())."'\n";
                $buffer .= (($j > $from) ? "," : "") . "{ $li }\n";
            }
            $j++;
        }
		
        if ($j>$to) {
            $li = "key:'host_group', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_group.png', title:'"._("next")." $maxresults "._("host group")."'";
            $buffer .= ",{ $li }\n";
        }
        
		$buffer .= "]";
    }
	
}
elseif ( $key == "nets" )
{
	$wherenet = ($filter!="") ? "ips like '%$filter%'" : "";
    if ($net_list = Net::get_list($conn, $wherenet)) {
        $buffer .= "[";
        $j = 0;
        foreach($net_list as $net) 
		{
            if ($j>=$from && $j<$to) 
			{
                $net_name = $net->get_name();
				$net_key  = utf8_encode("NET:".$net_name);
				$ips      = trim($net->get_ips());
				$li       = "key:'".$net_key."', asset_data:'".$ips."', icon:'../../pixmaps/theme/net.png', title:'".Util::htmlentities($net_name)." <font style=\"font-size:80%\">(".$ips.")</font>'\n";
                
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
}
else if ( $key=="netgroup" )
{
    $whereng = ( $filter=="" ) ? "" : "name like '%$filter%'";
   
    if ($net_group_list = Net_group::get_list($conn, $whereng)) 
	{
        $buffer .= "[";
        $j = 0;
        foreach($net_group_list as $net_group) 
		{
            if ($j>=$from && $j<$to) {
               	$ng_key   = base64_encode($net_group->get_name());
				$ng_title = utf8_encode($net_group->get_name());
                
                $li = "key:'netgroup_$ng_key', isLazy:true , icon:'../../pixmaps/theme/net_group.png', title:'$ng_title'\n";
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
}
else if (preg_match("/netgroup_(.*)/",$key,$found)){
    $buffer .= "[";
    
	$html = "";
    $nets = Net_group::get_networks($conn, base64_decode($found[1]));
    $k = 1;
    $j = 0;
	
    foreach($nets as $net) 
	{
        $net_name = $net->get_net_name();
        if ($j>=$from && $j<$to) 
		{
            $net_key  = utf8_encode("NET:".$net_name);
			$ips      = Net::get_ips_by_name($conn,$net_name);
			
			$html    .= "{ key:'$net_key', asset_data:'".trim($ips)."', icon:'../../pixmaps/theme/net.png', title:'$net_name <font style=\"font-size:80%\">(".trim($ips).")</font>' },\n";
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
}

else if ( $key=="sensor" ) 
{
    if ($sensor_list = Sensor::get_list($conn, "ORDER BY name"))
	{
        $buffer .= "[";
        $j = 0;
        foreach($sensor_list as $sensor) 
		{
            if ($j>=$from && $j<$to) 
			{
              	$sensor_key  = utf8_encode("SENSOR:".$sensor->get_name());
				
				$li = "key:'".$sensor_key."', asset_data:'".$sensor->get_ip()."/32', icon:'../../pixmaps/theme/host.png', title:'".Util::htmlentities($sensor->get_name())."'\n";
                $buffer .= (($j > $from) ? "," : "") . "{ $li }\n";
            }
            $j++;
        }
		
        if ($j>$to) {
            $li = "key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net_group.png', title:'"._("next")." $maxresults "._("net groups")."'";
            $buffer .= ",{ $li }\n";
        }
        $buffer .= "]";
    }
}
else if ( $key !="hosts" ) 
{
    $buffer .= "[ { key:'hosts',      page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host.png', title:'"._("Hosts")." <font style=\"font-weight:normal;font-size:80%\">(" . $total_hosts . " "._("hosts").")</font>'},\n";
	$buffer .= "  { key:'host_group', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_group.png', title:'"._("Host Group")."'},\n";
    $buffer .= "  { key:'nets',       page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'"._("Networks")."'},\n";
    $buffer .= "  { key:'netgroup',   page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net_group.png', title:'"._("Network Groups")."'},\n";
	$buffer .= "  { key:'sensor',     page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_os.png', title:'"._("Sensors")."'}\n";
    $buffer .= "]";
}


if ( $buffer=="" || $buffer=="[]" )
    $buffer = "[{title:'"._("No Assets found")."'}]";

echo $buffer;
error_reporting(0);
$f = fopen($cachefile,"w");
fputs($f,$buffer);
fclose($f);

?>
