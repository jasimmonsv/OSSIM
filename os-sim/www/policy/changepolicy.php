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
* - logprint()
* Classes list:
*/
require_once ('classes/Session.inc');
Session::logcheck("MenuIntelligence", "PolicyPolicy");
require_once 'classes/Security.inc';
function logprint($conn) {
    $policys = Policy::get_list($conn, "order by policy.order");
    foreach($policys as $p) {
        echo "&nbsp;policy: order:" . $p->get_order() . " group:" . $p->get_group() . "<br>";
    }
}
$src = GET('src');
$dst = GET('dst');

ossim_valid($src, OSS_ALPHA, 'illegal:' . _("Src"));
ossim_valid($dst, OSS_ALPHA, OSS_PUNC, 'illegal:' . _("Dst"));
if (ossim_error()) {
    die(ossim_error());
}
require_once 'ossim_db.inc';
require_once 'classes/Policy.inc';
$db = new ossim_db();
$conn = $db->connect();
echo "src=$src and dst=$dst<br>\n";
$groups = array();
$groups[0] = Policy::get_group_from_order($conn, $src);
if (preg_match("/\:/", $dst)) {
    // dst is a group
    $groups[1] = (preg_replace("/\:.*/", "", $dst)) * 1;
    $dst = Policy::get_order_from_group($conn, $groups[1], $groups[0], "min"); // grupo destino, grupo origen
    
} else {
    $groups[1] = Policy::get_group_from_order($conn, $dst);
}
$change_group = $groups[1]; // need a final group change
echo "change from $src ($groups[0]) and $dst ($groups[1])<br>\n";
//logprint($conn);
if ($groups[0] == $groups[1]) {
    // same group => swap
    echo "change simple $src <> $dst<br>\n";
	Policy::swap_simple_orders($conn, $src, $dst);
} else {
    // different group => especial swap
    if ($src < $dst) {
        echo "$src < $dst<br>\n";
        // Only change group (do not change order value)
		if ($src == $dst - 1) Policy::change_group($conn,$src,$groups[1]);
		// Else change orders and group
		else {
			for ($i = $src; $i < $dst-1; $i++) {
				echo "change $i <-> " . ($i + 1) . " and group=" . $groups[1] . "<br>\n";
				Policy::swap_orders($conn, $i, $i + 1, $groups[1], "src");
			}
		}
    } else {
        if ($src == $dst) {
            Policy::change_group($conn, $dst, $change_group);
            echo "$src >= $dst to group $change_group<br>\n";
        }
        for ($i = $src; $i > $dst; $i--) {
            echo "change " . ($i - 1) . " <-> $i and group=" . $change_group . "<br>\n";
            Policy::swap_orders($conn, $i - 1, $i, $change_group, "dst");
            //logprint($conn);
            
        }
    }
}
$db->close($conn);
if (GET('back') != '') echo "<script>document.location.href='" . GET('back') . "';</script>\n";
?>
