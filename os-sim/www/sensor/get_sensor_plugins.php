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
* - server_get_sensor_plugins()
* Classes list:
*/
ini_set("max_execution_time","300");
require_once ('classes/Session.inc');
Session::logcheck("MenuConfiguration", "PolicySensors");

function server_get_sensor_plugins($sensor_ip="") {
    require_once ('ossim_conf.inc');
    $ossim_conf = $GLOBALS["CONF"];
    /* get the port and IP address of the server */
    if($sensor_ip=="")
        $address = $ossim_conf->get_conf("server_address");
    else
        $address = $sensor_ip;
    $port = $ossim_conf->get_conf("server_port");
    /* create socket */
    $socket = socket_create(AF_INET, SOCK_STREAM, 0);
    if ($socket < 0) {
        echo _("socket_create() failed: reason: ") . socket_strerror($socket) . "\n";
    }
    $list = array();
    $timeout = array('sec' => 5, 'usec' => 0);
    /* connect */
    socket_set_block($socket);
    socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,$timeout);
    $result = @socket_connect($socket, $address, $port);
    if (!$result) {
        echo "<p><b>"._("socket error")."</b>: " . gettext("Is OSSIM server running at") . " $address:$port?</p>";
        return $list;
    }
    /* first send a connect message to server */
    $in = 'connect id="1" type="web"' . "\n";
    $out = '';
    socket_write($socket, $in, strlen($in));
    $out = @socket_read($socket, 2048, PHP_BINARY_READ);
    if (strncmp($out, "ok id=", 4)) {
        echo "<p><b>" . gettext("Bad response from server") . "</b></p>";
        echo "<p><b>"._("socket error")."</b>: " . gettext("Is OSSIM server running at") . " $address:$port?</p>";
        return $list;
    }
    /* get sensor plugins from server */
    $in = 'server-get-sensor-plugins id="2"' . "\n";
    $out = '';
    socket_write($socket, $in, strlen($in));
    $pattern = '/sensor="([^"]*)" plugin_id="([^"]*)" ' . 'state="([^"]*)" enabled="([^"]*)"/';
    while ($out = socket_read($socket, 2048, PHP_BINARY_READ)) {
        if (preg_match($pattern, $out, $regs)) {
            $s["sensor"] = $regs[1];
            $s["plugin_id"] = $regs[2];
            $s["state"] = $regs[3];
            $s["enabled"] = $regs[4];
            if (!in_array($s, $list)) $list[] = $s;
        } elseif (!strncmp($out, "ok id=", 4)) {
            break;
        }
    }
    socket_close($socket);
    return $list;
}
//
// debug
// print_r(server_get_sensor_plugins());
//

?>
