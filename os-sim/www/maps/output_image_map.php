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
require_once 'classes/Security.inc';
require_once 'classes/Session.inc';
require_once 'classes/User_config.inc';
Session::logcheck("MenuConfiguration", "ConfigurationMaps");
$db = new ossim_db();
$conn = $db->connect();
if (GET('tmp_image')) {
    $config = new User_config($conn);
    $login = Session::get_session_user();
    header("Content-Type: " . $config->get($login, 'maps_tmp_image_type'));
    header("Content-Lenght: " . strlen($config->get($login, 'maps_tmp_image')));
    echo $config->get($login, 'maps_tmp_image');
    exit;
}
if ($map_id = GET('map_id')) {
    $sql = "SELECT engine_data1, engine_data2 FROM map WHERE id = ?";
    $row = $conn->GetRow($sql, array(
        $map_id
    ));
    header("Content-Type: " . $row['engine_data2']);
    echo $row['engine_data1'];
    exit;
}
?>
