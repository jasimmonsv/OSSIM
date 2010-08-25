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
* - check_server()
* - server_get_servers()
* Classes list:
*/
require_once ('classes/Session.inc');
require_once ('classes/Util.inc');
function check_server($conn) {
    require_once ('ossim_conf.inc');
    $ossim_conf = $GLOBALS["CONF"];
    /* get the port and IP address of the server */
    $address = $ossim_conf->get_conf("server_address");
    $port = $ossim_conf->get_conf("server_port");
    /* create socket */
    $socket = socket_create(AF_INET, SOCK_STREAM, 0);
    if ($socket < 0) {
        echo _("socket_create() failed: reason: "). socket_strerror($socket) . "\n";
    }
    /* connect */
    $result = @socket_connect($socket, $address, $port);
    if (!$result) {
        return false;
    }
    return true;
}
function server_get_servers($conn) {
    $name = GET('name');
    ossim_valid($name, OSS_ALPHA, OSS_PUNC, OSS_SPACE, 'illegal:' . _("Server name"));
    require_once ('ossim_conf.inc');
    $ossim_conf = $GLOBALS["CONF"];
    /* get the port and IP address of the server */
    $address = $ossim_conf->get_conf("server_address");
    $port = $ossim_conf->get_conf("server_port");
    /* create socket */
    $socket = socket_create(AF_INET, SOCK_STREAM, 0);
    if ($socket < 0) {
        echo _("socket_create() failed: reason: "). socket_strerror($socket) . "\n";
    }
    $list = array();
    $err = "";
    /* connect */
    $result = @socket_connect($socket, $address, $port);
    if (!$result) {
        $err = "<p><b>"._("socket error")."</b>: " . gettext("Is OSSIM server running at") . " $address:$port?</p>";
        return array(
            $list,
            $err
        );
    }
    /* first send a connect message to server */
    $in = 'connect id="1" type="web"' . "\n";
    $out = '';
    socket_write($socket, $in, strlen($in));
    $out = socket_read($socket, 2048, PHP_NORMAL_READ);
    if (strncmp($out, "ok id=", 4)) {
        $err = "<p><b>" . gettext("Bad response from server") . "</b></p>";
        return array(
            $list,
            $err
        );
    }
    /* get servers from server */
    if ($name != NULL) $in = 'server-get-servers id="2" servername="' . $name . '"' . "\n";
    else $in = 'server-get-servers id="2"' . "\n";
    $out = '';
    socket_write($socket, $in, strlen($in));
    $pattern = '/server host="([^"]*)" servername="([^"]*)"/ ';
    while ($out = socket_read($socket, 2048, PHP_NORMAL_READ)) {
        if (preg_match($pattern, $out, $regs)) {
            if (Session::hostAllowed($conn, $regs[1])) {
                $s["host"] = $regs[1];
                $s["servername"] = $regs[2];
                //# This should be checked in the server TODO FIXME
                if (!in_array($s, $list)) $list[] = $s;
            }
        } elseif (!strncmp($out, "ok id=", 4)) {
            break;
        }
    }
    socket_close($socket);
    return array(
        $list,
        $err
    );
}
//
// debug
// print_r(server_get_sensors());
//

?>


