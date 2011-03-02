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

Session::logcheck("MenuPolicy", "PolicyNetworks");

$filter = GET('filter');
$filter = (mb_detect_encoding($filter." ",'UTF-8,ISO-8859-1') == 'UTF-8') ? Util::utf8entities($filter) : $filter;
$key = GET('key');

ossim_valid($filter, OSS_NULLABLE, OSS_ALPHA, OSS_DIGIT, OSS_PUNC, 'illegal:' . _("Filter"));
ossim_valid($key, OSS_NULLABLE, OSS_TEXT, OSS_PUNC, 'illegal:' . _("key"));

if (ossim_error()) {
    die(ossim_error());
}
if ($filter == "undefined") $filter = "";


require_once ('classes/Net.inc');
require_once ('ossim_db.inc');

$filter = str_replace ( "/" , "\/" , $filter);

$db = new ossim_db();
$conn = $db->connect();

$bclasses = array();
$cclasses = array();
$nets     = array();

if($filter!="") {
    if(preg_match("/\d+\./",$filter))   $condition = "ips LIKE '".$filter."%'";
    else                                   $condition = "name LIKE '%".$filter."%'";

    if($key!="")   $condition = " AND ".$condition;
}

if($key=="")    
    $all_nets = Net::get_list($conn, $condition);
    
else if(preg_match("/bclass_(\d+\.\d+)/",$key,$found)) 
    $all_nets = Net::get_list($conn,"(ips LIKE '".$found[1]."%' OR ips LIKE '%,".$found[1]."%')" . $condition);
    
else if(preg_match("/cclass_(\d+\.\d+\.\d+)/",$key,$found))
    $all_nets = Net::get_list($conn,"(ips LIKE '".$found[1]."%' OR ips LIKE '%,".$found[1]."%')" . $condition);

foreach ($all_nets as $net) {
    if($key=="" || preg_match("/bclass_(\d+\.\d+)/",$key,$found)) {
        $acidrs = array();
        $cidrs = trim($net->get_ips());
        $acidrs = explode(",",$cidrs);
        sort($acidrs);
    }

    if($key=="") {
        foreach($acidrs as $cidr) {
            preg_match("/(\d+\.\d+)\..*/",$cidr,$found);
            if(!in_array($found[1],$bclasses))  $bclasses[] = $found[1];
        }
    }
    else if(preg_match("/bclass_(\d+\.\d+)/",$key,$found)) {
        foreach($acidrs as $cidr) {
            preg_match("/(\d+\.\d+\.\d+)\..*/",$cidr,$found);
            if(!in_array($found[1],$cclasses)) $cclasses[] = $found[1];
        }
    }
    else if(preg_match("/cclass_(\d+\.\d+\.\d+)/",$key,$found)) {
        $cidrs = trim($net->get_ips());
        $tmp_cidrs = explode(",",$cidrs);
        if(count($tmp_cidrs)>1) $cidrs = $tmp_cidrs[0]."...".$tmp_cidrs[count($tmp_cidrs)-1];
        $name = trim($net->get_name());
        $nets[$name] = $cidrs;
        ksort($nets);
    }
}

if ($key=="") {
    $buffer .= "[ {title: '"._("Networks")."', key:'keyn', url:'networks', icon:'../../pixmaps/theme/any.png', expand:true, children:[\n";
    foreach($bclasses as $bclass)
        $buffer .= "{ key:'bclass_$bclass', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'$bclass.---.---/--'},";
    $buffer = preg_replace("/,$/", "", $buffer);
    $buffer .= "] } ]";
}
else if(preg_match("/bclass_(\d+\.\d+)/",$key,$found)){

    $buffer .= "[";
    foreach($cclasses as $cclass)
        $buffer .= "{ key:'cclass_$cclass', page:'', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'$cclass.---/--'},";
    $buffer = preg_replace("/,$/", "", $buffer);
    $buffer .= "]";
}
else if(preg_match("/cclass_(\d+\.\d+\.\d+)/",$key,$found)){
    $buffer .= "[";

    foreach($nets as $net_name => $net_cidrs) {
        $buffer .= "{ key:'$net_name', page:'', isFolder:false, isLazy:false, icon:'../../pixmaps/theme/net.png', title:'$net_name ($net_cidrs)'},";
    }

    $buffer = preg_replace("/,$/", "", $buffer);
    $buffer .= "]";
}

if ($buffer=="" || $buffer=="[]")
    $buffer = "[{title:'"._("No Nets Found")."'}]";

echo $buffer;

?>
