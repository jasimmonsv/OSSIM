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

$key = GET('key');
ossim_valid($key, OSS_NULLABLE, OSS_TEXT, OSS_PUNC, 'illegal:' . _("key"));
if (ossim_error()) {
    die(_("Invalid Parameter key"));
}

$cachefile = "/var/ossim/sessions/".$_SESSION["_user"]."_ports_".base64_encode($key).".json";
if (file_exists($cachefile)) {
    readfile($cachefile);
    exit;
}
require_once ('classes/Port_group.inc');
require_once ('ossim_db.inc');
$db = new ossim_db();
$conn = $db->connect();
$port_groups = array();
$buffer = "";
if ($port_group_list = Port_group::get_list($conn, "ORDER BY name")) {
    foreach($port_group_list as $port_group) {
        $pg_name = $port_group->get_name();
        $pg_ports = $port_group->get_reference_ports($conn, $pg_name);
        $port_groups[] = (!preg_match("/ANY/i", $pg_name)) ? array(
            $pg_name,
            $pg_ports
        ) : array(
            $pg_name,
            array()
        );
    }
}

if($key=="") {
    $buffer .= "[ {title: '"._("Port Groups")."', key:'key1', isFolder:true, icon:'../../pixmaps/theme/ports.png', expand:true\n";
    if (count($port_groups) > 0) {
        $buffer .= ", children:[";
        $j = 1;
        foreach($port_groups as $pg) {
            $pg_name = $pg[0];
            $pg_ports = $pg[1];
            $html = "";
            if ($pg_name =="ANY")
                $li = "key:'pg_$pg_name', url:'$pg_name', icon:'../../pixmaps/theme/ports.png', title:'$pg_name'\n";
            else
                $li = "key:'pg_$pg_name', isLazy:true, url:'$pg_name', icon:'../../pixmaps/theme/ports.png', title:'$pg_name'\n";

                if ($html != "") $buffer .= (($j > 1) ? "," : "") . "{ $li, children:[ " . preg_replace("/,$/", "", $html) . " ] }\n";
            else $buffer .= (($j > 1) ? "," : "") . "{ $li }\n";
            $j++;
        }
        $buffer .= "]";
    }
    $buffer .= "}]";
}

else if(preg_match("/pg_(.*)/",$key,$found)) {
    $html = "";
    $buffer .= "[";
    $k=1;
    $port_list = Port_group_reference::get_list($conn, " where port_group_name = '".$found[1]."'");
    foreach ($port_list as $port) {
        $protocol_name = $port->get_protocol_name();
        $protocol_number = $port->get_port_number();
        $html.= "{ key:'$key.$k', url:'noport', icon:'../../pixmaps/theme/ports.png', title:'$protocol_number $protocol_name</font>'},";
        //if ($k++>$limit) break;
    }
    
    if ($html != "") $buffer .= preg_replace("/,$/", "", $html);
    $buffer .= "]";
}
if ($buffer=="" || $buffer=="[]")
    $buffer = "[{title:'"._("No port groups found")."'}]";
echo $buffer;
error_reporting(0);
$f = fopen($cachefile,"w");
fputs($f,$buffer);
fclose($f);
?>
