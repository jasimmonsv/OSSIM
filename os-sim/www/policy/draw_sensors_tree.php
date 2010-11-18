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
Session::logcheck("MenuIntelligence", "PolicyPolicy");
$cachefile = "/var/ossim/sessions/".$_SESSION["_user"]."_sensors.json";
if (file_exists($cachefile)) {
    readfile($cachefile);
    exit;
}
require_once ('classes/Sensor.inc');
require_once ('ossim_db.inc');
$db = new ossim_db();
$conn = $db->connect();
$sensors = array();
$buffer = "";
if ($sensor_list = Sensor::get_all($conn)) {
	foreach($sensor_list as $sensor) {
		$sensor_name = $sensor->get_name();
        $sensor_ip = $sensor->get_ip();
        $sensors[$sensor_ip] = $sensor_name;
    }
}
$buffer .= "[ {title: '"._("Sensors")."', key:'key1', isFolder:true, icon:'../../pixmaps/theme/server.png', expand:true\n";
if (count($sensors) > 0) {
    $buffer .= ", children:[";
    $j = 1;
    $buffer .= "{  key:'key1.1.$j', url:'ANY', icon:'../../pixmaps/theme/server.png', title:'ANY' },\n";
    foreach($sensors as $ip => $sname) {
        $sname = utf8_encode($sname);
		$buffer .= (($j > 1) ? "," : "") . "{ key:'key1.1.$j', url:'$sname', icon:'../../pixmaps/theme/server.png', title:'".utf8_encode($sname)." ($ip)' }\n";
        $j++;
    }
    $buffer .= "]";
}
$buffer .= "}]\n";
echo $buffer;
error_reporting(0);
$f = fopen($cachefile,"w");
fputs($f,$buffer);
fclose($f);
?>
