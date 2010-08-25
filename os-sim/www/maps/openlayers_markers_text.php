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
* - get_sensor_status()
* - search_sensor()
* Classes list:
*/
//
// This file is no longer in use and will be probably removed soon
//
ob_start();
require_once 'classes/Security.inc';
require_once 'classes/Session.inc';
require_once '../sensor/get_sensors.php';
ob_end_clean();
Session::logcheck("MenuConfiguration", "ConfigurationMaps");
$map_id = GET('map_id') ? GET('map_id') : die("Invalid map_id");
ossim_valid($map_id, OSS_DIGIT, 'illegal:' . _("map_id"));
if (ossim_error()) {
    die(ossim_error());
}
$db = new ossim_db();
$conn = $db->connect();
function get_sensor_status($ip) {
    global $conn;
    static $sensors = array();
    if (!count($sensors)) {
        ob_start();
        $sensors = server_get_sensors($conn);
        ob_end_clean();
    }
    foreach($sensors as $s) {
        if ($s['sensor'] == $ip) {
            return $s['state'];
        }
    }
    return 'off';
}
function search_sensor($name) {
    global $conn;
    static $sensors = array();
    if (!count($sensors)) {
        $sql = "SELECT ip, name FROM sensor";
        if (!$rs = $conn->Execute($sql)) {
            die($conn->ErrorMsg());
        }
        while (!$rs->EOF) {
            $sensors[$rs->fields['name']] = array(
                'ip' => $rs->fields['ip'],
                'state' => get_sensor_status($rs->fields['ip'])
            );
            $rs->MoveNext();
        }
    }
    return isset($sensors[$name]) ? $sensors[$name] : false;
}
$sql = "SELECT id, type, ossim_element_key, x, y FROM map_element WHERE map_id=?";
if (!$rs = $conn->Execute($sql, array(
    $map_id
))) {
    die($conn->ErrorMsg());
}
$items = array();
while (!$rs->EOF) {
    $s_name = $rs->fields['ossim_element_key'];
    if ($s_data = search_sensor($s_name)) {
        $items[$s_name]['state'] = $s_data['state'];
    } else {
        $items[$s_name]['state'] = false;
    }
    $items[$s_name]['x'] = $rs->fields['x'];
    $items[$s_name]['y'] = $rs->fields['y'];
    $rs->MoveNext();
}
header("Content-type: text/plain");
echo "point\ttitle\tdescription\ticon\n";
foreach($items as $name => $i) {
    $icon = $i['state'] == 'on' ? '../js/OpenLayers/img/marker-green.png' : '../js/OpenLayers/img/marker.png';
    echo $i['y'] . "," . $i['x'] . "\t" . $name . "\t" . "Sensor status: " . $i['state'] . ' ' . $i['y'] . "," . $i['x'] . "\t" . $icon . "\n";
}
?>