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
require_once 'classes/Session.inc';
require_once 'classes/Net.inc';

Session::logcheck("MenuPolicy", "PolicyNetworks");

$filter = GET('filter');
$filter = (mb_detect_encoding($filter." ",'UTF-8,ISO-8859-1') == 'UTF-8') ? Util::utf8entities($filter) : $filter;
$key    = GET('key');
$entity = GET('entity');


ossim_valid($filter, OSS_NULLABLE, OSS_ALPHA, OSS_DIGIT, OSS_PUNC, 'illegal:' . _("Filter"));
ossim_valid($key, OSS_NULLABLE, OSS_TEXT, OSS_PUNC, 'illegal:' . _("key"));
ossim_valid($entity, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("entity"));

if (ossim_error()) {
    die(ossim_error());
}
if ($filter == "undefined") $filter = "";

$low_limit  = 10;
$high_limit = 100;
 
require_once ('classes/Net.inc');
require_once ('ossim_db.inc');

$db   = new ossim_db();
$conn = $db->connect();

$current_entity_perms['sensors'] = array();
$current_entity_perms['assets'] = array();

if($entity!="") {
    require_once ('classes/Acl.inc');
    $current_entity_perms = Acl::entityPerms($conn,$entity);
}
else 
    $entity = 0;

$filter = str_replace ( "/" , "\/" , $filter);

$aclasses = array();
$bclasses = array();
$cclasses = array();
$nets     = array();

if( $filter!="" ) 
{
    if(preg_match("/\d+\./",$filter))   
		$condition = "ips LIKE '".$filter."%'";
    else                                 
		$condition = "name LIKE '%".$filter."%'";

    if($key!="")   
		$condition = " AND ".$condition;
}

if($key=="")    
    $all_nets = Net::get_list($conn, $condition, "", array_keys($current_entity_perms['sensors']));
else {
    preg_match("/.class_(.*)/",$key,$found);
    $all_nets = Net::get_list($conn,"(ips LIKE '".$found[1].".%' OR ips LIKE '%,".$found[1].".%')" . $condition, "", array_keys($current_entity_perms['sensors']));
}


foreach ($all_nets as $net) 
{
	if ( ($entity > 0 && $current_entity_perms['assets'][$net->get_ips()] || empty($current_entity_perms['assets']) ) || $entity == 0 ) 
	{
        $cidrs = trim($net->get_ips());
        $acidrs = explode(",", $cidrs);
        foreach($acidrs as $cidr) {
            $data = explode(".", $cidr);
            
            if($cclasses[$data[0].".".$data[1].".".$data[2]]!=1)  $cclasses[$data[0].".".$data[1].".".$data[2]] = 1;
            if($cclasses[$data[0].".".$data[1]]!=1)               $bclasses[$data[0].".".$data[1]] = 1;
            if($cclasses[$data[0]]!=1)                            $aclasses[$data[0]] = 1;
        }
        
        $name = trim($net->get_name());
        $nets[$name] = $cidrs;
    }
}

ksort($nets);

if ($key=="") {
    $buffer .= "[ {title: '"._("Networks")."', key:'all_networks', icon:'../../pixmaps/theme/any.png', isFolder:false, expand:true, children:[\n";
    
	if(count($bclasses) <= $low_limit ) {
        foreach($cclasses as $cclass => $v) {
            $buffer .= "{ key:'cclass_$cclass', icon:'../../pixmaps/theme/net.png', title:'$cclass.---/--', expand:true, children:[\n";
            foreach($nets as $net_name => $net_cidrs) if(preg_match("/$cclass\..*/",$net_cidrs)) {
                $buffer .= "{ key:'$net_name', isFolder:false, isLazy:false, icon:'../../pixmaps/theme/net.png', title:'$net_name (".Net::get_cidrs_summary($conn, $net_name).")'},";
            }
            $buffer = preg_replace("/,$/", "", $buffer);
            $buffer .= "]},";
        }
    }
    else if(count($bclasses) > $low_limit && count($bclasses) <= $high_limit) {
        foreach($bclasses as $bclass => $v)
            $buffer .= "{ key:'bclass_$bclass', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'$bclass.---.---/--'},";
    }
    else {
        foreach($aclasses as $aclass => $v)
            $buffer .= "{ key:'aclass_$aclass', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'$aclass.---.---.---/--'},";
    }
    
	$buffer = preg_replace("/,$/", "", $buffer);
    $buffer .= "] } ]";
	
	if (preg_match("/children:\[\n\] \} \]$/", $buffer) ) 
		$buffer = "[{title:'<span>"._("No Networks Found")."<span>', key:'_no_nets_', icon:'../../pixmaps/theme/any.png', addClass:'grey_12'}]";
}
else if(preg_match("/aclass_(\d+)/",$key,$found)){

    $buffer .= "[";
    foreach($bclasses as $bclass => $v)
        $buffer .= "{ key:'bclass_$bclass', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'$bclass.---.---/--'},";
    $buffer = preg_replace("/,$/", "", $buffer);
    $buffer .= "]";
}
else if(preg_match("/bclass_(\d+\.\d+)/",$key,$found)){

    $buffer .= "[";
    foreach($cclasses as $cclass => $v)
        $buffer .= "{ key:'cclass_$cclass', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/net.png', title:'$cclass.---/--'},";
    $buffer = preg_replace("/,$/", "", $buffer);
    $buffer .= "]";
}
else if(preg_match("/cclass_(\d+\.\d+\.\d+)/",$key,$found)){
    $buffer .= "[";

    foreach($nets as $net_name => $net_cidrs) {
        $buffer .= "{ key:'$net_name', isFolder:false, isLazy:false, icon:'../../pixmaps/theme/net.png', title:'$net_name (".Net::get_cidrs_summary($conn, $net_name).")'},";
    }

    $buffer = preg_replace("/,$/", "", $buffer);
    $buffer .= "]";
}

if ($buffer=="" || $buffer=="[]")
    $buffer = "[{title:'<span>"._("No Networks Found")."<span>', key:'_no_nets_', icon:'../../pixmaps/theme/any.png', addClass:'grey_12'}]";

$db->close($conn);
echo $buffer;

?>
