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
* - Frameworkd_socket()
* - config()
* Classes list:
* - Frameworkd_socket extends Socket
*/
require_once ('classes/Session.inc');
require_once ('classes/Socket.inc');
require_once ('ossim_db.inc');
require_once ('ossim_conf.inc');
/*** Simple extension of Socket ***/
class Frameworkd_socket extends Socket {
    function Frameworkd_socket() {
        $this->config();
        $this->status = parent::Socket($this->address, $this->port);
    }
    function config() {
        $conf = $GLOBALS["CONF"];
        $this->address = $conf->get_conf("frameworkd_address");
        $this->port = $conf->get_conf("frameworkd_port");
    }
}
/***
Example:

$s=new Frameworkd_socket();
if($s->status)
{
if($s->write('nagios action="add" type="host" list="192.168.1.150 appLIanCE|192.168.1.119 albertossim"'))
echo "OK! :) <br>";
else
echo "oops.. :( <br>";
echo $s->msg_w;
$s->close();
}
else
echo "Can't connect to frameworkd...<br>";
echo "status: ".$s->status."<br>";
***/
?>


