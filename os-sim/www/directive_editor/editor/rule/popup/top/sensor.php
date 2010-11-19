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
require_once ("../../../../include/utils.php");
dbConnect();
/*
$sensor = GET('sensor');
$sensor_list = GET('sensor_list');
ossim_valid($sensor, "ANY", "LIST", OSS_NULLABLE, 'illegal:' . _("sensor"));
ossim_valid($sensor_list, OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_NULLABLE, '!', 'illegal:' . _("sensor_list"));
if (ossim_error()) {
    die(ossim_error());
}
*/
/*
if (substr($sensor_list, 0, 1) == '!') {
    $default_checked = ' checked="checked"';
    $sensor_list = substr($sensor_list, 1);
} else $default_checked = '';
*/
if ($host_list = getSensorList()) {
    foreach($host_list as $host) {
        $hostname = $host->get_name();
        $ip = $host->get_ip();
        if ($sensor == 'ANY') {
            $checked = ' checked="checked"';
        } elseif (in_array($ip, split(',', $sensor_list))) {
            $checked = ($default_checked == '') ? ' checked="checked"' : '';
        } else {
            $checked = $default_checked;
        }
		echo "$ip=$hostname\n";
    }
}
dbClose();
?>
