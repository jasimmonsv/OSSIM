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
* - cmpf()
* Classes list:
*/
require_once 'classes/Security.inc';
require_once ('classes/Host.inc');
require_once ('classes/Host_os.inc');
require_once ('classes/Host_services.inc');
require_once ('classes/Host_mac.inc');
require_once ('classes/Util.inc');
require_once ('ossim_db.inc');

Session::logcheck("MenuPolicy", "PolicyHosts");

$key = GET('key');
ossim_valid($key, OSS_NULLABLE, OSS_TEXT, OSS_PUNC, 'illegal:' . _("key"));

$buffer = "";

$db = new ossim_db();
$conn = $db->connect();

if(preg_match("/cclass_(\d+)_(.+)/",$key) || preg_match("/all_\d+\.\d+\.\d+_(\d+)_(.+)/",$key) ||  preg_match("/all/",$key) || preg_match("/all_\d+\.\d+\.\d+/",$key)) {
    $all_cclass_hosts = array();
    $host_list = array();

    if(preg_match("/cclass_(\d+)_(.+)/",$key, $found) || preg_match("/all_\d+\.\d+\.\d+_(\d+)_(.+)/",$key, $found)) {
        if($found[1]==7) { // MAC
            if($found[2]=="Unknown") { // doesn't exit mac in host_mac_vendors
                $sql = "SELECT hp.ip, h.hostname
                                FROM host_properties AS hp LEFT JOIN host AS h ON hp.ip = h.ip
                                WHERE hp.property_ref=".$found[1]." AND SUBSTRING(hp.value, 1, 8 ) NOT IN (SELECT DISTINCT  mac FROM host_mac_vendors) ORDER BY hp.ip";
            }
            else {
                $sql = "SELECT hp.ip, h.hostname
                        FROM host_properties AS hp LEFT JOIN host AS h ON hp.ip = h.ip,
                        host_mac_vendors AS hmv
                        WHERE hp.property_ref=".$found[1]." AND SUBSTRING( hp.value, 1, 8 )=hmv.mac AND MD5(hmv.vendor)='".$found[2]."' ORDER BY hp.ip";
            }
        }
        else {
            $sql = "SELECT hp.ip, h.hostname
                   FROM host_properties AS hp LEFT JOIN host AS h
                   ON hp.ip = h.ip
                   WHERE hp.property_ref=".$found[1]." AND MD5(hp.value)='".$found[2]."' ORDER BY hp.ip";
        }
    }
    else {
        $sql = "SELECT hp.ip, h.hostname
           FROM host_properties AS hp LEFT JOIN host AS h
           ON hp.ip = h.ip";
    }
    //print_r($sql);
    if (!$rs = & $conn->Execute($sql)){ 
        print $conn->ErrorMsg();
        exit();
    }
    else
    {
        while (!$rs->EOF){
            $host_list[$rs->fields['ip']] = $rs->fields['hostname'];
            $rs->MoveNext();
        }
    }
    foreach($host_list as $hostip => $hostname) {
        $cclass = preg_replace("/(\d+\.)(\d+\.)(\d+)\.\d+/", "\\1\\2\\3", $hostip);
        $all_cclass_hosts[$cclass][] = array($hostip, $hostname);
    }
}


if($key=="") {
    $props = Host::get_properties_types($conn);
    $buffer .= "[ {title: '"._("Asset by Property")."', isFolder: true, key:'main', icon:'../../pixmaps/theme/any.png', expand:true, children:[\n";
    foreach ($props as $prop) {
        switch (strtolower($prop["name"])) {
            case "software": $png = "software";
            break;
            case "operating-system": $png = "host_os";
            break;
            case "cpu": $png = "cpu";
            break;
            case "services": $png = "ports";
            break;
            case "ram": $png = "ram";
            break;
            case "department": $png = "host_group";
            break;
            case "macaddress": $png = "mac";
            break;
            case "workgroup": $png = "net_group";
            break;
            case "role": $png = "server_role";
            break;
        }
        if(count(Host::get_property_values($conn, $prop["id"]))>0) {
            $buffer .= "{ key:'p".$prop["id"]."', isFolder:true, isLazy:true, expand:false, icon:'../../pixmaps/theme/$png.png', title:'"._($prop["name"])."' },\n";
        }
        else {
            $buffer .= "{ icon:'../../pixmaps/theme/$png.png', title:'"._($prop["name"])."' },\n";
        }
    }
    $buffer .= "{ key:'all', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_add.png', title:'"._("All Hosts")."' }\n";
    $buffer .= "] } ]";
}
else if(preg_match("/p(.*)/",$key,$found)) {
        $value_list = Host::get_property_values($conn, $found[1]);
        
        if(intval($found[1])!=7) {
            foreach($value_list as $v) {
                $buffer .= "{ key:'cclass_".$found[1]."_".md5($v["value"])."', isLazy:true , title:'".$v["value"]." <font style=\"font-weight:normal;font-size:80%\">(" . $v["total"] . ")</font>', isFolder:true},";
            }
        }
        else {
            foreach($value_list as $v) {
                $buffer .= "{ icon:'../../pixmaps/theme/mac.png', key:'cclass_".$found[1]."_".(($v["vendor"]=="") ? "Unknown" : md5($v["vendor"]))."', isLazy:true , title:'".(($v["vendor"]=="") ? _("Unknown") : $v["vendor"])." <font style=\"font-weight:normal;font-size:80%\">(" . $v["total"] . ")</font>', isFolder:true},";
            }
        }
        $buffer = "[ ".preg_replace("/,$/","",$buffer)." ]";
}
else if(preg_match("/cclass_(\d+)_(.*)/",$key,$found)) {
    foreach($all_cclass_hosts as $cclass => $hg) {
        $buffer .= "{ key:'all_".$cclass."_".$found[1]."_".$found[2]."', isLazy:true, icon:'../../pixmaps/theme/host_add.png', title:'$cclass <font style=\"font-weight:normal;font-size:80%\">(" . count($hg) . " "._("hosts").")</font>'\n},";
    }
    $buffer = "[ ".preg_replace("/,$/","",$buffer)." ]";
}
else if(preg_match("/all_(\d+\.\d+\.\d+)_(\d+)_(.*)/",$key,$found)) {
    foreach($all_cclass_hosts as $cclass => $host_list) if($cclass==$found[1]) {
        foreach ($host_list as $host_data) {
            $host_name = "";
            if($host_data[1]!="") {
                $host_name = "<font style=\"font-size:80%\">(" . $host_data[1] . ")</font>";
                $url = "url:'../host/modifyhostform.php?ip=".$host_data[0]."',";
            }
            else {
                $url = "url:'../host/newhostform.php?ip=".$host_data[0]."',";
            }
            $buffer.= "{ ".$url." icon:'../../pixmaps/theme/host.png', title:'".$host_data[0]." ".$host_name."' },";
        }
    }
    $buffer = "[ ".preg_replace("/,$/","",$buffer)." ]";
}
else if($key=="all") {
    foreach($all_cclass_hosts as $cclass => $hg) {
        $buffer .= "{ key:'all_$cclass', isLazy:true, icon:'../../pixmaps/theme/host_add.png', title:'$cclass <font style=\"font-weight:normal;font-size:80%\">(" . count($hg) . " "._("hosts").")</font>'\n},";
    }
    $buffer = "[ ".preg_replace("/,$/","",$buffer)." ]";
}
else if(preg_match("/all_(\d+\.\d+\.\d+)/",$key,$found)) {
    foreach($all_cclass_hosts as $cclass => $host_list) if($cclass==$found[1]) {
        foreach ($host_list as $host_data) {
            $host_name = "";
            if($host_data[1]!="") {
                $host_name = "<font style=\"font-size:80%\">(" . $host_data[1] . ")</font>";
                $url = "url:'../host/modifyhostform.php?ip=".$host_data[0]."',";
            }
            else {
                $url = "url:'../host/newhostform.php?ip=".$host_data[0]."',";
            }
            $buffer.= "{ ".$url." icon:'../../pixmaps/theme/host.png', title:'".$host_data[0]." ".$host_name."' },";
        }
    }
    $buffer = "[ ".preg_replace("/,$/","",$buffer)." ]";
}

if ($buffer=="" || $buffer=="[  ]")
    $buffer = "[{title:'"._("No Data found")."'}]";
    
echo $buffer;
$db->close($conn);
?>
