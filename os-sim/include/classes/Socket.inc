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
* - Socket()
* - connect()
* - write()
* - read()
* - close()
* Classes list:
* - Socket
*/
require_once ('classes/Session.inc');
require_once ('ossim_db.inc');
class Socket {
    var $socket;
    var $len_w;
    var $len_r;
    var $address;
    var $port;
    var $status;
    var $msg_r;
    var $msg_w;
    function Socket($host, $port) {
        $this->address = $host;
        $this->port = $port;
        $this->status = $this->connect();
        return $this->status;
    }
    function connect() {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, 0);
        if ($this->socket < 0) {
            require_once ("ossim_error.inc");
            $error = new OssimError();
            $error->display("CRE_SOCKET", array(
                socket_strerror($this->socket)
            ));
            $this->status = 0;
            return 0;
        } else {
            $d_s_t = ini_get("default_socket_timeout");
            ini_set("default_socket_timeout", "15");
            $result = @socket_connect($this->socket, $this->address, $this->port);
            ini_set("default_socket_timeout", $d_s_t);
            if (!$result) {
                require_once ("ossim_error.inc");
                $error = new OssimError();
                $error->display("FRAMW_NOTRUN", array(
                    $this->address . ":" . $this->port
                ));
                $this->status = 0;
                return 0;
            } else {
                $this->status = 1;
                return 1;
            }
        }
    }
    function write($msg) {
        $this->len_w = socket_write($this->socket, $msg, strlen($msg));
        $this->msg_w = $msg;
        if (strlen($msg) == $this->len_w) return 1;
        else return 0;
    }
    function read($bytes = 512) {
        $this->msg_r = socket_read($this->socket, $bytes);
        $this->len_r = strlen($this->msg_r);
        if ($this->len_r > 0) {
            $this->status = 1;
            return 1;
        } else {
            $this->status = 0;
            return 0;
        }
    }
    function close() {
        socket_close($this->socket);
    }
}
/***
Example:

$s=new Socket("localhost","40003");
if($s->status)
{
if($s->write('nagios action="add" type="host" list="192.168.1.150 appLIanCE|192.168.1.119 albertossim"'))
echo "OK! :) <br>";
else
echo "oops.. :( <br>";
$s->close();
}
else
echo "Can't connect to frameworkd...<br>";
echo "status: ".$s->status."<br>";
***/
?>


