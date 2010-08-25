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
require_once ('classes/Session.inc');
Session::logcheck("MenuIntelligence", "PolicyPolicy");
require_once 'classes/Security.inc';
$group = GET('group');
$order = GET('order');
ossim_valid($group, OSS_DIGIT, 'illegal:' . _("group"));
ossim_valid($order, OSS_ALPHA, OSS_PUNC, 'illegal:' . _("order"));
if (ossim_error()) {
    die(ossim_error());
}
require_once 'ossim_db.inc';
require_once 'classes/Policy_group.inc';
$db = new ossim_db();
$conn = $db->connect();
$group1 = Policy_group::get_list($conn, "where group_id=$group");
if ($group1[0]) {
    if ($order == "up") {
        $group2 = Policy_group::get_list($conn, "where policy_group.order=" . ($group1[0]->get_order() - 1));
        if ($group2[0]) {
            echo "Swapping: id1=" . $group2[0]->get_group_id() . ",order1=" . $group2[0]->get_order() . ",id2=" . $group1[0]->get_group_id() . ",order2=" . $group1[0]->get_order() . "<br>\n";
            Policy_group::swap_orders($conn, $group2[0]->get_group_id() , $group2[0]->get_order() , $group1[0]->get_group_id() , $group1[0]->get_order());
        }
    } elseif ($order == "down") {
        $group2 = Policy_group::get_list($conn, "where policy_group.order=" . ($group1[0]->get_order() + 1));
        if ($group2[0]) {
            echo "Swapping: id1=" . $group1[0]->get_group_id() . ",order1=" . $group1[0]->get_order() . ",id2=" . $group2[0]->get_group_id() . ",order2=" . $group2[0]->get_order() . "<br>\n";
            Policy_group::swap_orders($conn, $group1[0]->get_group_id() , $group1[0]->get_order() , $group2[0]->get_group_id() , $group2[0]->get_order());
        }
    }
}
$db->close($conn);
?>
