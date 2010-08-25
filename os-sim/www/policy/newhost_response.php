<?php
/*****************************************************************************
*
*    License:
*
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
include ("classes/Security.inc");
include ("classes/Host.inc");
include ("classes/Sensor.inc");
require_once ('ossim_conf.inc');
require_once 'ossim_db.inc';
$ip = GET('host');
ossim_valid($ip, OSS_IP_ADDR, 'illegal:' . _("Host"));
if (ossim_error()) {
    die(ossim_error());
}
$db = new ossim_db();
$conn = $db->connect();
$asset = 2;
$conf = $GLOBALS["CONF"];
$threshold = $conf->get_conf("threshold");
$alert = 0;
$persistence = 0;
$nat = "";

$sensor_list = Sensor::get_all($conn, "ORDER BY name");
$nsens = count($sensor_list);
$sensors = array();
$num_sens = 0;
foreach($sensor_list as $sensor) {
	$sensor_name = $sensor->get_name();
	$num_sens++;
	$sensors[] = $sensor_name;
}

$descr = "";
$os = "";
$mac = "";
$mac_vendor = "";
$latitude = 0;
$longitude = 0;

if (!Host::in_host($conn, $ip)) {
	Host::insert($conn, $ip, $ip, $asset, $threshold, $threshold, "", $alert, $persistence, $nat, $sensors, $descr, $os, $mac, $mac_vendor, $latitude, $longitude);
} else {
	echo _("Warning: the host inserted already exists, inventory insert skipped.");
	exit;
}
$db->close($conn);
echo _("Host ").$host._(" Successfully inserted into inventory with default values.");
?>
