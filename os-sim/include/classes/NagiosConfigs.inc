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
* - NagiosHostGroup()
* - toAdd()
* - toDel()
* - NagiosHost()
* - toList()
* - NagiosAdm()
* - connect_to_frameworkd()
* - addHost()
* - reload()
* - delHost()
* - addHosts()
* - close()
* - addNagiosHostGroup()
* - delNagiosHostGroup()
* Classes list:
* - NagiosHostGroup
* - NagiosHost
* - NagiosAdm
*/
require_once 'classes/Frameworkd_socket.inc';
require_once 'classes/Host.inc';
/******
* The classes provided in this file are related
* to the Nagios config files using a connection to the frameworkd
******/
class NagiosHostGroup {
    var $host_group_name;
    var $hosts;
    var $sensors;
    function NagiosHostGroup($host_group_name, $hosts, $sensors) {
        $this->host_group_name = $host_group_name;
        $this->hosts = $hosts;
        $this->sensors = $sensors;
    }
    function toAdd($conn) {
        foreach ($this->hosts as $k => $ip) $this->hosts[$k] = "$ip;".Host::ip2hostname($conn,$ip);
        return 'nagios action="add" type="hostgroup"' . " name=\"" . $this->host_group_name . "\" list=\"" . implode($this->hosts, ",") . "\"";
    }
    function toDel() {
        return 'nagios action="del" type="hostgroup"' . " name=\"" . $this->host_group_name . "\" ";
    }
}
class NagiosHost {
    var $hostname;
    var $ip;
    var $sensor;
    function NagiosHost($ip, $hostname, $sensor = "") {
        $this->hostname = $hostname;
        $this->ip = $ip;
        $this->sensor = $sensor;
    }
    function toList() {
        //We are not going to add the sensors yet (but soon)
        return $this->ip . ";" . $this->hostname;
    }
}
class NagiosAdm {
    var $frcon;
    function NagiosAdm() {
    }
    function connect_to_frameworkd() {
        $this->frcon = new Frameworkd_socket();
        if (!$this->frcon->status) {
            echo gettext("Can't connect to frameworkd...<br>");
            echo "status: " . $s->status . "<br>";
        }
    }
    function addHost(&$host) {
       	if (!$this->frcon->status) $this->connect_to_frameworkd();
        $this->frcon->write('nagios action="add" type="host" list="' . $host->toList() . "\"\n");
	}
    function reload() {
        if (!$this->frcon->status) $this->connect_to_frameworkd();
        $this->frcon->write('nagios action="restart"');
    }
    function delHost(&$host) {
        if (!$this->frcon->status) $this->connect_to_frameworkd();
        $this->frcon->write('nagios action="del" type="host" list="' . $host->ip . "\"\n");
    }
    function addHosts($list) {
        if (!$this->frcon->status) $this->connect_to_frameworkd();
        print_r($list);
        echo "<br>";
        // Converting to string
        foreach($list as $k => $host) {
            $strings[$k] = $host->toList();
        }
        $list = implode($strings, "|");
        $this->frcon->write('nagios action="add" type="host" list="' . $list . "\"\n");
    }
    function close() {
        $this->frcon->close();
    }
    function addNagiosHostGroup(&$hg,$conn) {
        if (!$this->frcon->status) $this->connect_to_frameworkd();
        $this->frcon->write($hg->toAdd($conn));
    }
    function delNagiosHostGroup(&$hg) {
        if (!$this->frcon->status) $this->connect_to_frameworkd();
        $this->frcon->write($hg->toDel());
    }
}
/***

Example:

$q=new NagiosAdm();
$q->addHost(new NagiosHost("192.168.1.150","appliance"));
$k[0]=new NagiosHost("192.168.10.1","host1");
$k[2]=new NagiosHost("192.168.10.2","host2");
$k[3]=new NagiosHost("192.168.10.3","host3");
$k[4]=new NagiosHost("192.168.10.4","host4");
$q->addHosts($k);
$q->close();

***/
?>
