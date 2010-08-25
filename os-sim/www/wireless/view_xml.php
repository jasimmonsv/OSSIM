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
require_once ('classes/Session.inc');
Session::logcheck("MenuEvents", "ReportsWireless");
require_once 'classes/Security.inc';
require_once 'Wireless.inc';
//
$sensor = GET('sensor');
$file = GET('file');
ossim_valid($sensor, OSS_IP_ADDR, 'illegal: sensor');
ossim_valid($file, OSS_TEXT, 'illegal: file');
if (ossim_error()) {
    die(ossim_error());
}
# sensor list with perms
require_once 'ossim_db.inc';
$db = new ossim_db();
$conn = $db->connect();
if (!validate_sensor_perms($conn,$sensor,"s, sensor_properties p WHERE s.ip=p.ip AND p.has_kismet=1")) {
    echo $_SESSION["_user"]." have not privileges for $sensor";
    $db->close($conn);
    exit;
}
$db->close($conn);
#
echo "[ {title: '"._("Networks")."', isFolder: true, key:'key1', icon:'../../pixmaps/theme/any.png', expand:true, children:[\n";
$path = "/var/ossim/kismet/parsed/$sensor/$file";
if (file_exists($path)) {
    $xml = simplexml_load_file($path);
    $i=1;
    foreach ($xml as $k => $v) if ($k=="wireless-network") {
        $val = trim(str_replace("'","",$v->SSID));
        if ($val=="") $val="<no ssid>";
        $li =  "key:'key1.$i', isFolder:true, icon:'../../pixmaps/theme/wifi.png', title:'".htmlentities($val)." <font style=\"font-size:80%;font-weight:normal\">(".$v->BSSID.")</font>'";
        $j=1; $html = "";
        foreach ($v as $k1 => $v1) if ($k1=="wireless-client") {
            foreach ($v1 as $k2 => $v2) if ($k2=="client-mac") {
                $html.= "{ key:'key1.$i.$j', icon:'../../pixmaps/theme/net.png', title:'$v2' },\n";
                $j++;
            }
        }
        if ($i > 1) echo ",";
        echo ($html != "") ? "{ $li, children:[ " . preg_replace("/,$/", "", $html) . " ] }\n" : "{ $li }\n";
        $i++;
    }
}
echo "]} ]\n";
?>
