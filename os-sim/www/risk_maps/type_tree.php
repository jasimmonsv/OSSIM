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
require_once 'classes/Security.inc';
require_once ('classes/Session.inc');
Session::logcheck("MenuIntelligence", "PolicyPolicy");

$filter = GET('filter');
$key = GET('key');
$page = intval(GET('page'));
ossim_valid($filter, OSS_NULLABLE, OSS_ALPHA, OSS_DIGIT, OSS_PUNC, 'illegal:' . _("Filter"));
ossim_valid($key, OSS_NULLABLE, OSS_TEXT, OSS_PUNC, 'illegal:' . _("key"));
ossim_valid($page, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("page"));
if (ossim_error()) {
    die(ossim_error());
}
if ($filter == "undefined") $filter = "";
if ($page == "" || $page<=0) $page = 1;
$maxresults = 200;
$to = $page * $maxresults;
$from = $to - $maxresults;
$nextpage = $page + 1;
/*
$cachefile = "/var/ossim/sessions/".$_SESSION["_user"]."_policy_".base64_encode($key.$filter)."_$page.json";
if (file_exists($cachefile)) {
    readfile($cachefile);
    exit;
}
*/
require_once ('classes/Host.inc');
require_once ('classes/Host_group.inc');
require_once ('classes/Net.inc');
require_once ('classes/Sensor.inc');
require_once ('classes/Server.inc');
require_once ('ossim_db.inc');
$db = new ossim_db();
$conn = $db->connect();

$ossim_hosts = $all_hosts = array();
$total_hosts = 0;
$ossim_nets = array();
$all_cclass_hosts = array();
$buffer = "";
if ($host_list = Host::get_list($conn, "", "ORDER BY hostname")) foreach($host_list as $host) if ($filter == "" || ($filter != "" && (preg_match("/$filter/i", $host->get_ip()) || preg_match("/$filter/i", $host->get_hostname())))) {
    $ossim_hosts[$host->get_ip() ] = $host->get_hostname();
    $all_hosts[$host->get_ip() ] = 1;
    //$cclass = preg_replace("/(\d+\.)(\d+\.)(\d+)\.\d+/", "\\1\\2\\3", $host->get_ip());
	$cclass = $host->get_hostname();
    $all_cclass_hosts[$cclass][] = $host->get_ip();
    $total_hosts++;
}
if ($hg_list = Host_group::get_list($conn, "", "ORDER BY name")) {
    foreach($hg_list as $hg) {
        $hg_hosts = $hg->get_hosts($conn, $hg->get_name());
        foreach($hg_hosts as $hosts) {
            $ip = $hosts->get_host_ip();
            unset($all_hosts[$ip]);
        }
    }
}
$wherenet = ($filter!="") ? "ips like '%$filter%'" : "";
if ($net_list = Net::get_list($conn, $wherenet)) {
    foreach($net_list as $net) {
        $net_name = $net->get_name();
        $net_ips = $net->get_ips();
        $hostin = array();
        foreach($ossim_hosts as $ip => $hname) if ($net->is_ip_in_cache_cidr($conn, $ip, $net_ips)) {
            $hostin[$ip] = $hname;
            unset($all_hosts[$ip]);
        }
        $ossim_nets[$net_name] = $hostin;
    }
}

if ($key == "host_group") {
    if (count($hg_list)>0) {
        $buffer .= "[";
        $j = 0;
        foreach($hg_list as $hg) {
            if($j>=$from && $j<$to) {
                $hg_name = $hg->get_name();
                $li = "key:'host_group;$hg_name', url:'HOST_GROUP:$hg_name', icon:'../../pixmaps/theme/host_group.png', title:'$hg_name'\n";
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
else if ($key == "net") {
    if (count($net_list)>0) {
        $buffer .= "[";
        $j = 0;
        foreach($net_list as $net) {
            if ($j>=$from && $j<$to) {
                $net_name = $net->get_name();
                $ips = $net->get_ips();
                $li = "key:'net;$net_name', url:'NETWORK:$net_name', icon:'../../pixmaps/theme/net.png', title:'$net_name <font style=\"font-size:80%\">(".$ips.")</font>'\n";
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
else if ($key=="sensor") {
    if ($sensor_list = Sensor::get_list($conn, "ORDER BY name")) {
        $buffer .= "[";
        $j = 0;
        foreach($sensor_list as $sensor) {
            if ($j>=$from && $j<$to) {
                $sensor_name = utf8_encode($sensor->get_name());
                $li = "key:'sensor;$sensor_name', url:'', icon:'../../pixmaps/theme/net_group.png', title:'$sensor_name'\n";
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
else if ($key=="server") {
    if ($server_list = Server::get_list($conn, "ORDER BY name")) {
        $buffer .= "[";
        $j = 0;
        foreach($server_list as $server) {
            if ($j>=$from && $j<$to) {
                $server_name = $server->get_name();
                $li = "key:'server;$server_name', url:'', icon:'../../pixmaps/theme/server.png', title:'$server_name'\n";
                $buffer .= (($j > $from) ? "," : "") . "{ $li }\n";
            }
            $j++;
        }
        if ($j>$to) {
            $li = "key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/server.png', title:'"._("next")." $maxresults "._("net group")."'";
            $buffer .= ",{ $li }\n";
        }
        $buffer .= "]";
    }
}
else if ($key=="all"){
    $buffer .= "[";
    $j = 0;
    foreach($all_cclass_hosts as $cclass => $hg) {
        if ($j>=$from && $j<$to) {
            $li = "key:'host;$cclass', url:'CCLASS:$cclass', icon:'../../pixmaps/theme/host_add.png', title:'$cclass <font style=\"font-weight:normal;font-size:80%\">(" . count($hg) . " "._("hosts").")</font>'\n";
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

else if ($key!="all") {
    $buffer .= "[ { key:'all', page:'', isFolder:true, isLazy:true , icon:'../../pixmaps/theme/host.png', title:'"._("Hosts")." <font style=\"font-weight:normal;font-size:80%\">(" . $total_hosts . " "._("hosts").")</font>'},\n";
    $buffer .= "{ key:'host_group', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_group.png', title:'"._("Host Group")."'},\n";
	$buffer .= "{ key:'net', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'"._("Networks")."'},\n";
    $buffer .= "{ key:'sensor', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net_group.png', title:'"._("Sensors")."'}\n";
    if (Session::am_i_admin()) $buffer .= ",{ key:'server', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/server.png', title:'"._("Servers")."'}\n";
    $buffer .= "]";
}

if ($buffer=="" || $buffer=="[]")
    $buffer = "[{title:'"._("No Hosts Found")."'}]";

echo $buffer;
error_reporting(0);
$f = fopen($cachefile,"w");
fputs($f,$buffer);
fclose($f);

?>
