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
Session::logcheck("MenuPolicy", "5DSearch");
require_once 'classes/User_config.inc';
require_once 'ossim_db.inc';
require_once 'ossim_conf.inc';
include ("functions.php");

$db = new ossim_db();
$conn = $db->connect();
$config = new User_config($conn);
$user = Session::get_session_user();

// Read config file with filters rules
$rules = get_rulesconfig ();

if (GET('inv_do') == "export") {
	$inv_session = array();
	for ($i = 1; $i <= $_SESSION['inventory_search']['num']; $i++) {
		$inv_session['data'][$i] = $_SESSION['inventory_search'][$i];
	}
	$inv_session['op'] = GET('op');
	$inv_session['description'] = GET('descr');
	$serialized_inv = serialize ($inv_session);
	$config->set($user, GET('name'), $serialized_inv, 'simple', "inv_search");
}
elseif (GET('inv_do') == "export_last") {
	$inv_session = array();
	for ($i = 1; $i <= $_SESSION['inventory_last_search']['num']; $i++) {
		$inv_session['data'][$i] = $_SESSION['inventory_last_search'][$i];
	}
	$inv_session['op'] = $_SESSION['inventory_last_search_op'];
	//$inv_session['description'] = GET('descr');
	$serialized_inv = serialize ($inv_session);
	$config->set($user, GET('name'), $serialized_inv, 'simple', "inv_search");
}
elseif (GET('inv_do') == "import") {
	$data = $config->get($user, GET('name'), 'php', "inv_search");
	echo "{\"dt\":[";
	$coma = "";
	foreach ($data['data'] as $i=>$filter) {
		echo $coma;
		echo "{\"type\":\"".$filter['type']."\",\"subtype\":\"".$filter['subtype']."\",\"match\":\"".$filter['match']."\",\"value\":\"".$filter['value']."\",\"value2\":\"".$filter['value2']."\"}";
		$coma = ",";
	}
	echo "],\"op\":\"".$data['op']."\",\"description\":\"".$data['description']."\"}";
}
elseif (GET('inv_do') == "delete") {
    $config->del($user, GET('name'), "inv_search");
}
elseif (GET('inv_do') == "getall") {
	$profiles = $config->get_all($user, "inv_search");
	echo implode(",",$profiles);
}
elseif (GET('inv_do') == "rename") {
	$config->rename(GET('new_name'),$user,GET('name'),"inv_search");
}
elseif (GET('inv_do') == "last_search") {
	$data = $_SESSION['inventory_last_search'];
	$op = $_SESSION['inventory_last_search_op'];
	echo "{\"dt\":[";
	$coma = "";
	for ($i=1;$i<=$data['num'];$i++) {
		$filter = $data[$i];
		echo $coma;
		echo "{\"type\":\"".$filter['type']."\",\"subtype\":\"".$filter['subtype']."\",\"match\":\"".$filter['match']."\",\"value\":\"".$filter['value']."\",\"value2\":\"".$filter['value2']."\"}";
		$coma = ",";
	}
	echo "],\"op\":\"".$op."\"}";
}
elseif (GET('inv_do') == "clean") {
	unset($_SESSION['inventory_search']);
	unset($_SESSION['inventory_last_search']);
}
?>
