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
require_once ('classes/Session.inc');
//Session::logcheck("MenuPolicy", "PolicyHosts");
require_once ('classes/Security.inc');
require_once 'ossim_db.inc';
require_once 'classes/Net.inc';

$ip = GET('ip');
if ($ip != "") {
	ossim_valid($ip, OSS_IP_ADDR, OSS_NULLABLE, 'illegal:' . _("ip"));
	if (ossim_error()) {
		//die(ossim_error());
		$conf = $GLOBALS["CONF"];
		$threshold = $conf->get_conf("threshold");
		echo "2;".$threshold.";".$threshold;
		exit();
	}
	$db = new ossim_db();
	$conn = $db->connect();
	$nets = Net::get_all($conn);
		
	foreach ($nets as $net) {
		if (Net::isIpInNet($ip,$net->get_ips())) {
			echo $net->get_asset().";".$net->get_threshold_c().";".$net->get_threshold_a();
			exit;
		}
	}
	echo "0";
	$db->close($conn);
} else echo "0";
?>