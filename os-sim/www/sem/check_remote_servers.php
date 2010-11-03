<?php 
/*****************************************************************************
*
*    License:
*
*   Copyright (c) 2007-2010 AlienVault
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
include("classes/Server.inc");
require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
Session::logcheck("MenuEvents", "ControlPanelSEM");
require_once "ossim_conf.inc";
$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);
if (!preg_match("/.*pro.*/i",$version) && !preg_match("/.*demo.*/i",$version)) {
	echo "<html><body><a href='http://www.alienvault.com/information.php?interest=ProfessionalSIEM' target='_blank' title='Proffesional SIEM'><img src='../pixmaps/sem_pro.png' border=0></a></body></tml>";
	exit;
}
require_once "ossim_db.inc";
$db = new ossim_db();
$conn = $db->connect();
$database_servers = Server::get_list($conn,",server_role WHERE server.name=server_role.name AND server_role.sem=1");
$ips = array();
foreach ($database_servers as $db) {
	$name = $db->get_name();
	$ip = $db->get_ip();
	$cmd = 'sudo ./test_remote_ssh.pl '.$ip;
	$res = explode("\n",`$cmd`);
	if ($res[0] == "OK") {
		$ips[$name] = "1";
	} else {
		$ips[$name] = "0";
	}
}
$flag = 0;
foreach ($ips as $name=>$status) {
	if ($flag) echo ";";
	echo "$name:$status";
	$flag = 1;
}
?>