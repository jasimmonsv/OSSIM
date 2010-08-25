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
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>";
Session::logcheck("MenuIntelligence", "PolicyPolicy");
require_once ('classes/Policy.inc');
require_once ('classes/Host.inc');
require_once ('ossim_db.inc');
require_once ('classes/WebIndicator.inc');
$group = GET('group');
if (!ossim_valid($group, OSS_DIGIT, 'illegal:' . _("group"))) {
	echo "<rows><row>\n</row></rows>";
	exit;
}
if (preg_match ("/( AND | OR )/",$order)) {
	echo "<rows><row>\n</row></rows>";
	exit;
}
$order = GET('sortname');
if (empty($order)) $order = POST('sortname');
if (!empty($order)) $order.= (POST('sortorder') == "asc") ? "" : " desc";
$search = GET('query');
if (empty($search)) $search = POST('query');
$field = POST('qtype');
$page = POST('page');
if (empty($page)) $page = 1;
$rp = POST('rp');
if (empty($rp)) $rp = 25;
$lsearch = $search;
ossim_valid($order, "()", OSS_NULLABLE, OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_DIGIT, 'illegal:' . _("order"));
ossim_valid($group, "()", OSS_NULLABLE, OSS_SPACE, OSS_ALPHA, OSS_DIGIT, 'illegal:' . _("order"));
ossim_valid($page, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("page"));
ossim_valid($rp, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("rp"));
ossim_valid($search, OSS_TEXT, OSS_NULLABLE, 'illegal:' . _("search"));
ossim_valid($field, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("field"));
if (ossim_error()) {
    die(ossim_error());
}
if (empty($order)) $order = "priority";
$where = ($group != "") ? "WHERE policy.group=$group" : "";
$start = (($page - 1) * $rp);
$limit = "LIMIT $start, $rp"; // do not use
$db = new ossim_db();
$conn = $db->connect();
$xml = "";
$policy_list = Policy::get_list($conn, "$where ORDER BY policy.$order");
if ($policy_list[0]) {
    $total = $policy_list[0]->get_foundrows();
    if ($total == 0) $total = count($policy_list);
} else $total = 0;
$xml.= "<rows>\n";
$xml.= "<page>$page</page>\n";
$xml.= "<total>$total</total>\n";
foreach($policy_list as $policy) {
    $id = $policy->get_id();
    $order = $policy->get_order();
    $xml.= "<row id='$order'>";
    $tabla = "<img src='../pixmaps/tables/cross.png' border='0'>";
    $active = (!$policy->get_active()) ? $tabla : str_replace("cross", "tick", $tabla);
    $xml.= "<cell><![CDATA[" . $active . "]]></cell>";
    $xml.= "<cell><![CDATA[" . $order . "]]></cell>";
    $xml.= "<cell><![CDATA[" . (($policy->get_priority()==-1) ? "-":$policy->get_priority()) . "]]></cell>";
    $source = "";
    if ($source_host_list = $policy->get_hosts($conn, 'source')) foreach($source_host_list as $source_host) {
        $source.= ($source == "" ? "" : "<br/>") . "<img src='../pixmaps/theme/host.png' align=absbottom> " . Host::ip2hostname($conn, $source_host->get_host_ip());
    }
    if ($source_net_list = $policy->get_nets($conn, 'source')) foreach($source_net_list as $source_net) {
        $source.= ($source == "" ? "" : "<br/>") . "<img src='../pixmaps/theme/net.png' align=absbottom> " . $source_net->get_net_name();
    }
    if ($source_host_list = $policy->get_host_groups($conn, 'source')) foreach($source_host_list as $source_host_group) {
        $source.= ($source == "" ? "" : "<br/>") . "<img src='../pixmaps/theme/host_group.png' align=absbottom> " . $source_host_group->get_host_group_name();
    }
    if ($source_net_list = $policy->get_net_groups($conn, 'source')) foreach($source_net_list as $source_net_group) {
        $source.= ($source == "" ? "" : "<br/>" . "<img src='../pixmaps/theme/net_group.png' align=absbottom> ") . $source_net_group->get_net_group_name();
    }
    $xml.= "<cell><![CDATA[" . $source . "]]></cell>";
    //
    $dest = "";
    if ($dest_host_list = $policy->get_hosts($conn, 'dest')) foreach($dest_host_list as $dest_host) {
        $dest.= ($dest == "" ? "" : "<br/>") . "<img src='../pixmaps/theme/host.png' align=absbottom> " . Host::ip2hostname($conn, $dest_host->get_host_ip());
    }
    if ($dest_net_list = $policy->get_nets($conn, 'dest')) foreach($dest_net_list as $dest_net) {
        $dest.= ($dest == "" ? "" : "<br/>") . "<img src='../pixmaps/theme/net.png' align=absbottom> " . $dest_net->get_net_name();
    }
    if ($dest_host_list = $policy->get_host_groups($conn, 'dest')) foreach($dest_host_list as $dest_host_group) {
        $dest.= ($dest == "" ? "" : "<br/>") . "<img src='../pixmaps/theme/host_group.png' align=absbottom> " . $dest_host_group->get_host_group_name();
    }
    if ($dest_net_list = $policy->get_net_groups($conn, 'dest')) foreach($dest_net_list as $dest_net_group) {
        $dest.= ($dest == "" ? "" : "<br/>") . "<img src='../pixmaps/theme/net_group.png' align=absbottom> " . $dest_net_group->get_net_group_name();
    }
    $xml.= "<cell><![CDATA[" . $dest . "]]></cell>";
    //
    $ports = "";
    if ($port_list = $policy->get_ports($conn)) foreach($port_list as $port_group) {
        $ports.= ($ports == "" ? "" : "<br/>") . $port_group->get_port_group_name();
    }
    $xml.= "<cell><![CDATA[" . $ports . "]]></cell>";
    $plugingroups = "";
    foreach($policy->get_plugingroups($conn, $policy->get_id()) as $group) {
        $plugingroups.= ($plugingroups == "" ? "" : "<br/>") . "<a href='javascript:;' onclick='GB_show(\""._("Plugin groups")."\",\"plugingroups.php?id=" . $group['id'] . "&hmenu=Policy&smenu=Policy&collection=1#".$group['id']."\",450,\"90%\");return false;'>" . $group['name'] . "</a>";
    }
    $xml.= "<cell><![CDATA[" . $plugingroups . "]]></cell>";
    $sensors = "";
    if ($sensor_list = $policy->get_sensors($conn)) foreach($sensor_list as $sensor) {
        $sensors.= ($sensors == "" ? "" : "<br/>") . $sensor->get_sensor_name();
    }
    $xml.= "<cell><![CDATA[" . $sensors . "]]></cell>";
    if ($policy_time = $policy->get_time($conn)) {
        $begin_day = $policy_time->get_begin_day();
        if ($begin_day == 1) $begin_day_char = _("Mon");
        elseif ($begin_day == 2) $begin_day_char = _("Tue");
        elseif ($begin_day == 3) $begin_day_char = _("Wed");
        elseif ($begin_day == 4) $begin_day_char = _("Thu");
        elseif ($begin_day == 5) $begin_day_char = _("Fri");
        elseif ($begin_day == 6) $begin_day_char = _("Sat");
        elseif ($begin_day == 7) $begin_day_char = _("Sun");
        $end_day = $policy_time->get_end_day();
        if ($end_day == 1) $end_day_char = _("Mon");
        elseif ($end_day == 2) $end_day_char = _("Tue");
        elseif ($end_day == 3) $end_day_char = _("Wed");
        elseif ($end_day == 4) $end_day_char = _("Thu");
        elseif ($end_day == 5) $end_day_char = _("Fri");
        elseif ($end_day == 6) $end_day_char = _("Sat");
        elseif ($end_day == 7) $end_day_char = _("Sun");
        $xml.= "<cell><![CDATA[" . $begin_day_char . " " . $policy_time->get_begin_hour() . "h - " . $end_day_char . " " . $policy_time->get_end_hour() . "h" . "]]></cell>";
    } else {
        $xml.= "<cell><![CDATA[null]]></cell>";
    }
    $targets = "";
    if ($target_list = $policy->get_targets($conn)) foreach($target_list as $target) {
        $targets.= ($targets == "" ? "" : "<br/>") . $target->get_target_name();
    }
    $xml.= "<cell><![CDATA[" . $targets . "]]></cell>";
    $desc = html_entity_decode($policy->get_descr());
    if ($desc == "") $desc = "&nbsp;";
    $xml.= "<cell><![CDATA[" . $desc . "]]></cell>";
    $role_list = $policy->get_role($conn);
    if (count($role_list) < 1) {
			$xml.= "<cell></cell>";
			$xml.= "<cell></cell>";
			$xml.= "<cell></cell>";
			$xml.= "<cell></cell>";
			$xml.= "<cell></cell>";
			$xml.= "<cell></cell>";
			$xml.= "<cell></cell>";
			$xml.= "<cell></cell>";
			$xml.= "<cell></cell>";
	} else {
		foreach($role_list as $role) {
			$xml.= "<cell><![CDATA[" . ($role->get_correlate() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
			$xml.= "<cell><![CDATA[" . ($role->get_cross_correlate() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
			$xml.= "<cell><![CDATA[" . ($role->get_store() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
			$xml.= "<cell><![CDATA[" . ($role->get_qualify() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
			$xml.= "<cell><![CDATA[" . ($role->get_resend_alarm() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
			$xml.= "<cell><![CDATA[" . ($role->get_resend_event() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
			$xml.= "<cell><![CDATA[" . ($role->get_sim() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
			$xml.= "<cell><![CDATA[" . ($role->get_sem() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
			$xml.= "<cell><![CDATA[" . ($role->get_sign() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
			break;
		}
	}
    $xml.= "</row>\n";
}
$xml.= "</rows>\n";
echo $xml;
$db->close($conn);
?>


