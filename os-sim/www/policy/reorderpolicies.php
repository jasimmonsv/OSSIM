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
session_start();
//get polocies by normal order
require_once 'ossim_db.inc';
require_once 'classes/Policy.inc';
$db = new ossim_db();
$conn = $db->connect();

$policy_list = Policy::get_list($conn);
foreach($policy_list as $policy) {
	$id_group = $policy->get_group();
	$id = $policy->get_id();
	$rs = $conn->Execute("SELECT name FROM policy_group WHERE group_id=$id_group");
	if ($rs->fields["name"]=="") {
		$conn->Execute("UPDATE policy SET policy.group=0 WHERE id=$id");
	}
}
//
$neworder = 1;
$policy_groups = Policy::get_policy_groups($conn);
foreach($policy_groups as $group) {
	$policy_list = Policy::get_list($conn, "WHERE policy.group=".$group->get_group_id()." ORDER BY policy.priority");
	foreach($policy_list as $policy) {
		$id = $policy->get_id();
		$conn->Execute("UPDATE policy SET policy.order=$neworder WHERE id=$id");
		$neworder++;
	}
}
$db->close($conn);
header("Location: /ossim/policy/policy.php?hmenu=Policy&smenu=Policy");
?>