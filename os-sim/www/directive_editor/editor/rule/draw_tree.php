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
Session::logcheck("MenuIntelligence", "PolicyPolicy");

$filter = GET('filter');
$key    = GET('key');
$page   = intval(GET('page'));

ossim_valid($filter, OSS_NULLABLE, OSS_ALPHA, OSS_DIGIT, OSS_PUNC, 'illegal:' . _("Filter"));
ossim_valid($key, OSS_NULLABLE, OSS_TEXT, OSS_PUNC, '!', 'illegal:' . _("Key"));
ossim_valid($page, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("Page"));

if (ossim_error()) {
    die(ossim_error());
}

if ($filter == "undefined") 
	$filter = "";

if ($page == "" || $page<=0) 
	$page = 1;

$maxresults = 200;
$to       = $page * $maxresults;
$from     = $to - $maxresults;
$nextpage = $page + 1;


$cachefile = "/var/ossim/sessions/".$_SESSION["_user"]."_directive_".base64_encode($key.$filter)."_$page.json";
if (file_exists($cachefile)) {
    readfile($cachefile);
    exit;
}


require_once ('classes/Host.inc');
require_once ('classes/Host_group.inc');
require_once ('classes/Net.inc');
require_once ('classes/Net_group.inc');
require_once ('ossim_db.inc');
$db   = new ossim_db();
$conn = $db->connect();

$ossim_hosts      = array();
$total_hosts      = 0;
$ossim_nets       = array();
$all_cclass_hosts = array();
$buffer           = "";

$length_name = ( !empty($_GET['length_name']) ) ? GET('length_name') : 10 ; 

if ($key== "" || preg_match("/^(all|hostgroup)/",$key)) 
{
	if ($host_list = Host::get_list($conn, "", "ORDER BY hostname")) foreach($host_list as $host) if ($filter == "" || ($filter != "" && (preg_match("/$filter/i", $host->get_ip()) || preg_match("/$filter/i", $host->get_hostname())))) {
	    $ossim_hosts[$host->get_ip() ] = $host->get_hostname();
	    $cclass = preg_replace("/(\d+\.)(\d+\.)(\d+)\.\d+/", "\\1\\2\\3", $host->get_ip());
	    $all_cclass_hosts[$cclass][] = $host->get_ip();
	    $total_hosts++;
	}
}

if ($key == "net") 
{

	$wherenet = ($filter!="") ? "ips like '%$filter%'" : "";
	$net_list = Net::get_list($conn, $wherenet, "ORDER BY name");

    if (count($net_list)>0) 
	{
        $buffer .= "[";
        $j = 0;
        foreach($net_list as $net) {
            if ($j>=$from && $j<$to) {
			
				$ips      = $net->get_ips();
			   	$net_key  = base64_encode($net->get_name());
				
				$net_name = $net->get_name();
				$aux_nname  = ( strlen($net_name) > $length_name ) ? substr($net_name, 0, $length_name)."..." : $net_name;

				$title      = Util::htmlentities($aux_nname)." <font style=\"font-size:80%\">(".$ips.")</font>";
				$tooltip    = Util::htmlentities($net_name)." ".$ips." ";
                
                $li = "key:'net_$net_key', isLazy:true, url:'$ips', icon:'../../pixmaps/theme/net.png', title:'$title', tooltip:'$tooltip'\n";
                $li_not = "key:'net_!$net_key', isLazy:true, url:'!$ips', icon:'../../pixmaps/theme/net.png', title:'!$title', tooltip:'!$tooltip'\n";
                $buffer .= (($j > $from) ? "," : "") . "{ $li },{ $li_not }\n";
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
else if (preg_match("/net_(.*)/",$key,$found))
{
	$hostin   = array();
	$negated  = (preg_match("/^\!/",$found[1])) ? true : false;
	$aux_name = str_replace("!","",$found[1]);
	
	if ($net_list1 = Net::get_list($conn, "name='".base64_decode($aux_name)."'")) 
	{
		require_once("classes/CIDR.inc");
		foreach($net_list1 as $net) 
		{
		    $net_name = $net->get_name();
		    $nets_ips = explode(",",$net->get_ips());
		    foreach ($nets_ips as $net_ips) 
			{
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
    $k 	  = 0;
    $html = "";
	
	$buffer .= "[";
	foreach($hostin as $ip => $host_name) 
	{
    	$host_key   = utf8_encode($key.$k);
					
		$host_name  = $host_name;
	    $aux_hname  = ( strlen($host_name) > $length_name ) ? substr($host_name, 0, $length_name)."..." : $host_name;
						
		$title      = ( $host_name == "" || $host_name == $ip ) ? $ip : "$ip <font style=\"font-size:80%\">(".Util::htmlentities($aux_hname).")</font>";
		$tooltip    = ( $host_name == "" || $host_name == $ip ) ? $ip : $ip." (".Util::htmlentities($host_name).")";
												
		if ($k>=$from && $k<$to) 
		{
            if ($negated) {
            	$html.= "{ key:'$host_key', url:'!$ip', icon:'../../pixmaps/theme/host.png', title:'!$title', tooltip:'!$tooltip' },\n";
            } else {
            	$html.= "{ key:'$host_key', url:'$ip', icon:'../../pixmaps/theme/host.png', title:'$title', tooltip:'$tooltip' },\n";
            }
        }
        
		$k++;
    }
    
	if ($html != "") $buffer .= preg_replace("/,$/", "", $html);
    
	if ($k>$to) {
        $li = "key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host.png', title:'"._("next")." $maxresults "._("hosts")."'";
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
            $title = "$cclass <font style=\"font-weight:normal;font-size:80%\">(" . count($hg) . " "._("hosts").")</font>"; 
			$li = "key:'all_$cclass', isLazy:true, url:'CCLASS:$cclass', icon:'../../pixmaps/theme/host_add.png', title:'$title'\n";
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

else if (preg_match("/all_(.*)/",$key,$found))
{
    $html = "";
    $j    = 1;
    $i    = 0;
	
	$buffer .= "[";
    foreach($all_cclass_hosts as $cclass => $hg) 
	{
		if ($found[1]==$cclass) 
		{
			foreach($hg as $ip) 
			{
				if($i>=$from && $i<$to) 
				{
					$host_key   = utf8_encode($key.$j);
					
					$host_name  = $ossim_hosts[$ip];
					$aux_hname  = ( strlen($host_name) > $length_name ) ? substr($host_name, 0, $length_name)."..." : $host_name;
									
					$title      = ( $host_name == "" || $host_name == $ip ) ? $ip : "$ip <font style=\"font-size:80%\">(".Util::htmlentities($aux_hname).")</font>";
					$tooltip    = ( $host_name == "" || $host_name == $ip ) ? $ip : $ip." (".Util::htmlentities($host_name).")";
															
					$html.= "{ key:'$host_key', url:'$ip', icon:'../../pixmaps/theme/host.png', title:'$title', tooltip:'$tooltip'},\n";
					$html.= "{ key:'$key.$j', url:'!$ip', icon:'../../pixmaps/theme/host.png', title:'!$title', tooltip:'!$tooltip' },\n";
				}
				$i++;
			}
			$j++;
		}
	}
    
	if ($i>$to) {
        $html .= "{ key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_group.png', title:'"._("next")." $maxresults "._("hosts")."' },";
    }
    
	if ($html != "") $buffer .= preg_replace("/,$/", "", $html);
    $buffer .= "]";
}
else if ($key!="all") {
    $buffer .= "[ {title: '"._("ANY")."', tooltip: '"._("ANY")."', key:'key1', url:'ANY', icon:'../../pixmaps/theme/any.png', expand:true, children:[\n";
    $buffer .= "{ key:'net', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'"._("Networks")."', tooltip:'"._("Networks")."'},\n";
    $buffer .= "{ key:'all', page:'', isFolder:true, isLazy:true , icon:'../../pixmaps/theme/host.png', title:'"._("All Hosts")." <font style=\"font-weight:normal;font-size:80%;\">(" . $total_hosts . " "._("hosts").")</font>', tooltip:'"._("All Hosts")." (" . $total_hosts . " "._("hosts").")'}\n";
    $buffer .= "] } ]";
}

if ( $buffer =="" || $buffer == "[]" )
    $buffer = "[{title:'"._("No Assets found")."', noLink:true}]";

echo $buffer;
error_reporting(0);
$f = fopen($cachefile,"w");
fputs($f,$buffer);
fclose($f);

?>
