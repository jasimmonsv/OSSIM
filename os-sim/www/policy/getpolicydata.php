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
require_once ('classes/Session.inc');
Session::logcheck("MenuIntelligence", "PolicyPolicy");
require_once ('classes/Policy.inc');
require_once ('classes/Response.inc');
require_once ('classes/Host.inc');
require_once ('classes/Host_group.inc');
require_once ('classes/Net.inc');
require_once ('classes/Net_group.inc');
require_once ('classes/Port_group.inc');
require_once ('classes/Plugingroup.inc');
require_once ('classes/Server.inc');
require_once ('classes/Sensor.inc');
require_once ('classes/Action.inc');
require_once ('ossim_db.inc');
$db = new ossim_db();
$conn = $db->connect();
$tab = GET('tab');
ossim_valid($tab, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("tab"));
$id = (GET('id') != "") ? GET('id') : "";

// default vars
$priority = -1;
$correlate = 1;
$cross_correlate = 1;
$store = 1;
$qualify = 1;
$active = 1;
$order = 0;
$resend_alarm = 1;
$resend_event = 1;
$sign = 0;
$sem = 0;
$sim = 1;
if ($group == "") $group = 1;
$desc = "";
$sources = $dests = $ports = $plugingroups = $sensors = $targets = $actions = array();
$timearr = array(
    1,
    0,
    7,
    23
);

if ($id != "") {
    settype($id, "int");
    if ($policies = Policy::get_list($conn, "WHERE policy.order=$id")) {
        $policy = $policies[0];
        $id = $policy->get_id();
        $priority = $policy->get_priority();
        $active = $policy->get_active();
        $group = $policy->get_group();
        $order = $policy->get_order();
        if ($source_host_list = $policy->get_hosts($conn, 'source')) foreach($source_host_list as $source_host) {
            //$host = Host::ip2hostname($conn, $source_host->get_host_ip());
            $sources[] = ($host == "any") ? "ANY" : "HOST:" . $source_host->get_host_ip();
        }
        if ($source_net_list = $policy->get_nets($conn, 'source')) foreach($source_net_list as $source_net) {
            $sources[] = "NETWORK:" . $source_net->get_net_name();
        }
        if ($source_host_list = $policy->get_host_groups($conn, 'source')) foreach($source_host_list as $source_host_group) {
            $sources[] = "HOST_GROUP:" . $source_host_group->get_host_group_name();
        }
        if ($source_net_list = $policy->get_net_groups($conn, 'source')) foreach($source_net_list as $source_net_group) {
            $sources[] = "NETWORK_GROUP:" . $source_net_group->get_net_group_name();
        }
        //
        if ($dest_host_list = $policy->get_hosts($conn, 'dest')) foreach($dest_host_list as $dest_host) {
            //$host = Host::ip2hostname($conn, $dest_host->get_host_ip());
            $dests[] = ($host == "any") ? "ANY" : "HOST:" . $dest_host->get_host_ip();
        }
        if ($dest_net_list = $policy->get_nets($conn, 'dest')) foreach($dest_net_list as $dest_net) {
            $dests[] = "NETWORK:" . $dest_net->get_net_name();
        }
        if ($dest_host_list = $policy->get_host_groups($conn, 'dest')) foreach($dest_host_list as $dest_host_group) {
            $dests[] = "HOST_GROUP:" . $dest_host_group->get_host_group_name();
        }
        if ($dest_net_list = $policy->get_net_groups($conn, 'dest')) foreach($dest_net_list as $dest_net_group) {
            $dests[] = "NETWORK_GROUP:" . $dest_net_group->get_net_group_name();
        }
        //
        if ($port_list = $policy->get_ports($conn)) foreach($port_list as $port_group) {
            $ports[] = $port_group->get_port_group_name();
        }
        foreach($policy->get_plugingroups($conn, $policy->get_id()) as $pgroup) {
            $plugingroups[] = $pgroup['id'];
        }
        if ($sensor_list = $policy->get_sensors($conn)) foreach($sensor_list as $sensor) {
            $sensors[] = $sensor->get_sensor_name();
        }
        $policy_time = $policy->get_time($conn);
        $timearr[0] = $policy_time->get_begin_day();
        $timearr[1] = $policy_time->get_begin_hour();
        $timearr[2] = $policy_time->get_end_day();
        $timearr[3] = $policy_time->get_end_hour();
        if ($target_list = $policy->get_targets($conn)) foreach($target_list as $target) {
            $targets[] = $target->get_target_name();
        }
        $desc = html_entity_decode($policy->get_descr());
        $role_list = $policy->get_role($conn);
        foreach($role_list as $role) {
            $correlate = ($role->get_correlate()) ? 1 : 0;
            $cross_correlate = ($role->get_cross_correlate()) ? 1 : 0;
            $store = ($role->get_store()) ? 1 : 0;
            $qualify = ($role->get_qualify()) ? 1 : 0;
            $resend_alarm = ($role->get_resend_alarm()) ? 1 : 0;
            $resend_event = ($role->get_resend_event()) ? 1 : 0;
            $sign = ($role->get_sign()) ? 1 : 0;
            $sem = ($role->get_sem()) ? 1 : 0;
            $sim = ($role->get_sim()) ? 1 : 0;
            break;
        }
        // responses
        $actions_saved = array();
		if ($response_list = Response::get_list($conn, "WHERE descr='policy $id'")) {
            if ($action_list = $response_list[0]->get_actions($conn)) {
                foreach($action_list as $act) { $actions[] = $act->get_action_id(); $actions_saved[] = $act; }
            }
        }
    }
} else {
    $ports[] = "ANY";
    $targets[] = "any";
    $sensors[] = "any";
}

ossim_valid($id, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("id"));
if (ossim_error()) {
    die(ossim_error());
}
if ($tab == "ports") {
    if ($port_group_list = Port_group::get_list($conn, "ORDER BY name")) {
        $i = 1;
        foreach($port_group_list as $port_group) {
            $port_group_name = $port_group->get_name();
?>
        <option value="<?php echo $port_group_name
?>"> <?php echo $port_group_name . "<br>\n"; ?>
<?php
            $i++;
        }
    }
} elseif ($tab == "plugins") {
    foreach(Plugingroup::get_list($conn) as $g) {
?>
    <input type="checkbox" id="plugin_<?php echo $g->get_name() ?>" onclick="drawpolicy()" name="plugins[<?php echo $g->get_id() ?>]" <?php echo (in_array($g->get_id() , $plugingroups)) ? "checked='checked'" : "" ?>> <a href="../policy/modifyplugingroupsform.php?action=edit&id=<?php echo $g->get_id() ?>&withoutmenu=1" class="greybox" title="<?=_('View plugin group')?>"><?php echo $g->get_name() ?></a><br/>
<?php
    }
} elseif ($tab == "sensors") {
    if ($sensor_list = Sensor::get_list($conn, "ORDER BY name")) {
        $i = 1;
        foreach($sensor_list as $sensor) {
            $sensor_name = $sensor->get_name();
            $sensor_ip = $sensor->get_ip();
?>
        <option value="<?php echo $sensor_name
?>"> <?php echo $sensor_ip . " (" . $sensor_name . ")<br>"; ?>
<?php
            $i++;
        }
        echo "<option value=\"any\">ANY";
    }
} elseif ($tab == "targets") {
    /*
	$i = 1;
    if ($sensor_list = Sensor::get_list($conn, "ORDER BY name")) {
        foreach($sensor_list as $sensor) {
            $sensor_name = $sensor->get_name();
            $sensor_ip = $sensor->get_ip();
            if ($i == 1) {
?>
        <input type="hidden" name="<?php echo "targetsensor"; ?>" value="<?php echo count($sensor_list); ?>">
<?php
            }
?>
        <input type="checkbox" name="<?php echo $name; ?>" value="<?php echo $sensor_name; ?>"> <?php echo $sensor_ip . " (" . $sensor_name . ")<br>"; ?>
<?php
            $i++;
        }
    }*/
    $i = 1;
    if ($server_list = Server::get_list($conn, "ORDER BY name")) {
        foreach($server_list as $server) {
            $server_name = $server->get_name();
            $server_ip = $server->get_ip();
            if ($i == 1) {
?>
        <input type="hidden" name="<?php echo "targetserver"; ?>" value="<?php echo count($server_list); ?>">
<?php
            }
?>
        <input type="checkbox" name="<?php echo $name; ?>" value="<?php echo $server_name; ?>"> <?php echo $server_ip . " (" . $server_name . ")<br>"; ?>

<?php
            $i++;
        }
    }
?>
    <input type="checkbox" name="target_any" value="any">&nbsp;<b><?php echo _("ANY") ?></b>
<?php
} elseif ($tab == "groups") {
    if ($policygroups = Policy_group::get_list($conn, "ORDER BY name")) {
        $i = 0;
        foreach($policygroups as $policygrp) {
            $name = $policygrp->get_name();
            $id = $policygrp->get_group_id();
?>
        <option value="<?php echo $id
?>" <?php echo ($i++ == 0) ? "selected" : "" ?>> <?php echo $name ?>
<?php
        }
    }
} elseif ($tab == "responses") {
    if ($action_list = Action::get_list($conn)) {
        $i = 0;
        foreach($action_list as $action) {
            $name = $action->get_descr();
            $id = $action->get_id();
?>
        <option value="<?php echo $id
?>"> <?php echo $name ?>
<?php
        }
    }
}
$db->close($conn);
?>
