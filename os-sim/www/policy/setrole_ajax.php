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
require_once 'classes/Security.inc';
require_once 'classes/Policy_role_reference.inc';
require_once 'classes/Policy.inc';
require_once ('ossim_db.inc');
/* connect to db */
$db = new ossim_db();
$conn = $db->connect();

$set_role = GET('set');
$id = GET('id');
ossim_valid($set_role, OSS_ALPHA, OSS_SCORE, 'illegal:' . _("set"));
ossim_valid($id, OSS_DIGIT, 'illegal:' . _("id"));
if (ossim_error()) {
    die(ossim_error());
}

// query id from order
$list = Policy::get_list($conn, "WHERE policy.order=$id");
if ($list[0]) {
    $pid = $list[0]->get_id();
    $set = str_replace("change_","",$set_role);
    Policy_role_reference::set($conn,$pid,$set);
}


?>