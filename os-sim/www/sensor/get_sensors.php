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
* - server_get_sensors()
* Classes list:
*/
require_once ('classes/Session.inc');
require_once ('classes/Util.inc');
function server_get_sensors($conn) {
    require_once ('ossim_conf.inc');
	$allowed_sensors = explode (",",Session::allowedSensors());
    $ossim_conf = $GLOBALS["CONF"];
    /* get the port and IP address of the server */
    $address = $ossim_conf->get_conf("server_address");
    $port = $ossim_conf->get_conf("server_port");
    /* create socket */
    $socket = socket_create(AF_INET, SOCK_STREAM, 0);
    if ($socket < 0) {
        echo _("socket_create() failed: reason: ") . socket_strerror($socket) . "\n";
		return array(
            $list,
            $err
        );
    }
    $list = array();
    /* connect */
    socket_set_block($socket);
    socket_set_option( $socket,SOL_SOCKET,SO_RCVTIMEO, array('sec' => 4, 'usec' => 0) );
	socket_set_option( $socket,SOL_SOCKET,SO_SNDTIMEO, array('sec' => 4, 'usec' => 0) );
	    
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
    $out = @socket_read($socket, 2048, PHP_BINARY_READ);
    if (strncmp($out, "ok id=", 4)) {
        $err = "<p><b>" . gettext("Bad response from server") . "</b></p>";
        $err .= "<p><b>"._("socket error")."</b>: " . gettext("Is OSSIM server running at") . " $address:$port?</p>";
        return array(
            $list,
            $err
        );
    }
    /* get sensors from server */
    $in = 'server-get-sensor-plugins id="2"' . "\n";
    $output = '';
    socket_write($socket, $in, strlen($in));
    //$pattern = '/sensor host="([^"]*)" state="([^"]*)"/ ';
    $pattern = '/sensor="([^"]*)" plugin_id="([^"]*)" state="([^"]*)" enabled="([^"]*)"/ ';
    $plugins = array();
    while ($output = socket_read($socket, 2048, PHP_BINARY_READ)) {
        $lines = explode("\n",$output);
        foreach ($lines as $out) {
	    	if (preg_match($pattern, $out, $regs)) {
	            //if (Session::hostAllowed($conn, $regs[1])) {
				if (in_array($regs[1],$allowed_sensors) || Session::allowedSensors() == "") {
					//$s["sensor"] = $regs[1];
					//$s["state"] = $regs[3];
	                //# This should be checked in the server TODO FIXME
	                //if (!in_array($s, $list)) $list[] = $s;
	                $list[$regs[1]][$regs[2]]['enabled'] = $regs[4];
	                $list[$regs[1]][$regs[2]]['state'] = $regs[3];
	            }
	        } elseif (!strncmp($out, "ok id=", 4)) {
	            break;
	        }
        }
    }
    socket_close($socket);
    return array(
        $list,
        ""
    );
}
function send_msg($cmd, $ip, $id) {
	/*
	*  Send message to server
	*    sensor-plugin-CMD sensor="" plugin_id=""
	*  where CMD can be (start|stop|enable|disable)
	*/
	require_once ('ossim_conf.inc');
	$ossim_conf = $GLOBALS["CONF"];
	/* get the port and IP address of the server */
	$address = $ossim_conf->get_conf("server_address");
	$port    = $ossim_conf->get_conf("server_port");
	/* create socket */
	$socket = socket_create(AF_INET, SOCK_STREAM, 0);
	if ($socket < 0) 
	{
		echo ossim_error ( _("socket_create() failed: reason: ") . socket_strerror($socket) );
		exit();
	}
	
	/* connect  */
	socket_set_block($socket);
	socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO, array('sec' => 10, 'usec' => 0));
	socket_set_option($socket,SOL_SOCKET,SO_SNDTIMEO, array('sec' => 5, 'usec' => 0));
	
	$result = socket_connect($socket, $address, $port);
	if ($result < 0) {
		 echo ossim_error( _("socket_connect() failed.\nReason:")." ($result) " . socket_strerror($result) );
		exit();
	}
	/* first send a connect message to server */
	$in = 'connect id="1" type="web"' . "\n";
	$out = '';
	socket_write($socket, $in, strlen($in));
	$out = socket_read($socket, 2048, PHP_BINARY_READ);
	if (strncmp($out, "ok id=", 4)) 
	{
		echo "<p><b>" . gettext("Bad response from server") . "</b></p>";
		break;
	}
	/* send command */
	$msg = "sensor-plugin-$cmd sensor=\"$ip\" plugin_id=\"$id\"\n";
	socket_write($socket, $msg, strlen($msg));
	socket_close($socket);
	/* wait for
	*   framework => server -> agent -> server => framework
	* messages */
	//sleep(5);
}
?>