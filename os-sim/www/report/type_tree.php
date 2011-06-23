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
require_once ('classes/Session.inc');
require_once ('classes/Util.inc');
Session::menu_perms("MenuControlPanel", "BusinessProcessesEdit");

$filter = GET('filter');
$key    = GET('key');
$page   = intval(GET('page'));

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
	
$maxresults   = 200;
$to           = $page * $maxresults;
$from         = $to - $maxresults;
$nextpage     = $page + 1;

$length_name  = 30;

/*
$cachefile = "/var/ossim/sessions/".$_SESSION["_user"]."_policy_".base64_encode($key.$filter)."_$page.json";
if (file_exists($cachefile)) {
    readfile($cachefile);
    exit;
}
*/

require_once ('classes/Host.inc');
require_once ('classes/Net.inc');
require_once ('ossim_db.inc');

$db   = new ossim_db();
$conn = $db->connect();

$ossim_hosts      = array();
$total_hosts      = 0;
$ossim_nets       = array();
$all_cclass_hosts = array();
$buffer           = "";

if ($key == "" || preg_match("/^(all|host_group)/",$key)) 
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

if ( $key == "net" )
{

	$wherenet = ($filter!="") ? "ips like '%$filter%'" : "";
    if ($net_list = Net::get_list($conn, $wherenet)) 
	{
        $j = 0;
		
		$buffer .= "[";
        foreach($net_list as $net) 
		{
            if ($j>=$from && $j<$to) 
			{
                $net_name  = $net->get_name();
				$net_title = Util::htmlentities($net_name);
				
				$net_url   = "NETWORK:".Util::htmlentities($net_name);
				
				$ips_data  = $net->get_ips();				
				$ips       = "<font style=\"font-size:80%\">(".$ips_data.")</font>";
				
				$net_key   = utf8_encode("net;".$ips_data);
				
        		$title     = ( strlen($net_name) > $length_name ) ? substr($net_name, 0, $length_name)."..." : $net_name;	
				$title     = Util::htmlentities($title)." ".$ips;
				
				$tooltip   = $net_title." (".$ips_data.")";
								
				$li = "key:'$net_key', url:'$net_url', icon:'../../pixmaps/theme/net.png', title:'$title', tooltip:'$tooltip'\n";
                $buffer .= (($j > $from) ? "," : "") . "{ $li }\n";
            }
            $j++;
        }
        
		if ($j>$to) 
		{
            $li = "key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'"._("next")." $maxresults "._("nets")."'";
            $buffer .= ",{ $li }\n";
        }
        
		$buffer .= "]";
    }
}
else if ( $key=="all" )
{
    $j = 0;
	
	$buffer .= "[";
    foreach($all_cclass_hosts as $cclass => $hg) 
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
        $li      = "key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_add.png', title:'"._("next")." $maxresults "._("c-class")."'";
        $buffer .= ",{ $li }\n";
    }
	
    $buffer .= "]";
}
else if ( preg_match("/all_(.*)/",$key,$found) ) 
{
    $html      = "";
    $j         = 1;
    $i         = 0;
    $length_hn = 15;
	
	$buffer .= "[";
    foreach($all_cclass_hosts as $cclass => $hg) if ($found[1]==$cclass) 
	{
        foreach($hg as $ip) 
		{
			if ($i>=$from && $i<$to) 
			{                    
				$host_key   = "host;".$ip;
				$host_name  = ( $ip == $ossim_hosts[$ip] ) ? "" : utf8_encode($ossim_hosts[$ip]);
						   
				$aux_hname  = ( strlen($host_name) > $length_hn ) ? substr($host_name, 0, $length_hn)."..." : $host_name;
								
				$title      = ( $host_name == "" ) ? $ip : "$ip <font style=\"font-size:80%\">(".Util::htmlentities($aux_hname).")</font>";
				$tooltip    = ( $host_name == "" ) ? $ip : $ip." (".Util::htmlentities($host_name).")";
													
				$html.= "{ key:'$host_key', icon:'../../pixmaps/theme/host.png', title:'$title', tooltip:'$tooltip' },\n";
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
}
else if ( $key !="all" ) 
{
    $buffer .= "[ { key:'all', page:'', isFolder:true, isLazy:true , icon:'../../pixmaps/theme/host.png', title:'"._("Hosts")." <font style=\"font-weight:normal;font-size:80%\">(" . $total_hosts . " "._("hosts").")</font>'},\n";
	$buffer .= "{ key:'net', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'"._("Networks")."'},\n";
    $buffer .= "]";
}

if ( $buffer =="" || $buffer == "[]" )
    $buffer = "[{title:'"._("No Assets found")."', noLink:true}]";

echo $buffer;
error_reporting(0);
$f = fopen($cachefile,"w");
fputs($f,$buffer);
fclose($f);

?>
