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
 */
require_once 'classes/Session.inc';
require_once 'classes/Security.inc';
include("global_score_functions.php");

Session::logcheck("MenuControlPanel", "ControlPanelMetrics");

$group_name = GET('group_name');
$ac = GET('ac');

ossim_valid($group_name, OSS_TEXT, OSS_SPACE, 'illegal:' . _("group_name"));
ossim_valid($ac, OSS_ALPHA, 'illegal:' . _("ac"));
if (ossim_error()) {
    die(ossim_error());
}

$db = new ossim_db();
$conn = $db->connect();

//ajax_set_values();

$host_qualification_cache = get_host_qualification($conn);
$net_qualification_cache = get_net_qualification($conn);
////////////////////////////////////////////////////////////////
// Network Groups
////////////////////////////////////////////////////////////////
// If allowed_nets === null, then permit all
$allowed_nets = Session::allowedNets($user);
if ($allowed_nets) {
    $allowed_nets = explode(',', $allowed_nets);
}
$allowed_sensors = Session::allowedSensors($user);
if ($allowed_sensors) {
    $allowed_sensors = explode(',', $allowed_sensors);
}
$net_where = "";
if ($allowed_sensors != "" || $allowed_nets != "") {
	$nets_aux = Net::get_list($conn);
	$networks = "";
	foreach ($nets_aux as $net) {
		$networks .= ($networks != "") ? ",'".$net->get_name()."'" : "'".$net->get_name()."'"; 
	}
	if ($networks != "") {
		$net_where = " AND net.name in ($networks)";
	}
}
//$net_limit = " LIMIT $from,$max";
// We can't join the control_panel table, because new ossim installations
// holds no data there
$sql = "SELECT
            net_group.name as group_name,
            net_group.threshold_c as group_threshold_c,
            net_group.threshold_a as group_threshold_a,
            net.name as net_name,
            net.threshold_c as net_threshold_c,
            net.threshold_a as net_threshold_a,
            net.ips as net_address
        FROM
            net_group,
            net,
            net_group_reference
        WHERE
            net_group_reference.net_name = net.name AND
            net_group_reference.net_group_name = net_group.name AND net_group.name = \"$group_name\"$net_where";
if (!$rs = & $conn->Execute($sql)) {
    die($conn->ErrorMsg());
}
$groups = array();
$group_max_c = $group_max_a = 0;

while (!$rs->EOF) {
    $group = $rs->fields['group_name'];
    $groups[$group]['name'] = $group;
    // check perms over the network
	// Fixed: netAllowed check net/sensor granularity perms
    //$has_perms = Session::netAllowed($conn, $rs->fields['net_address']);
	//$has_net_perms = check_net_perms($rs->fields['net_address']);
    // if no perms over the network, try perms over the related sensor
    //$has_perms = $has_net_perms ? true : check_sensor_perms($rs->fields['net_address'], 'net');
    // the user only have perms over this group if he has perms over
    // all the networks of this group
    //if (!isset($groups[$group]['has_perms'])) {
      //  $groups[$group]['has_perms'] = $has_perms;
    //} elseif (!$has_perms) {
      //  $groups[$group]['has_perms'] = false;
    //}
    $groups[$group]['has_perms'] = true;
    // If there is no threshold specified for a group, pick the configured default threshold
    $group_threshold_a = $rs->fields['group_threshold_a'] ? $rs->fields['group_threshold_a'] : $conf_threshold;
    $group_threshold_c = $rs->fields['group_threshold_c'] ? $rs->fields['group_threshold_c'] : $conf_threshold;
    $groups[$group]['threshold_a'] = $group_threshold_a;
    $groups[$group]['threshold_c'] = $group_threshold_c;
    $net = $rs->fields['net_name'];
    // current metrics
    $net_current_a = get_current_metric($host_qualification_cache,$net_qualification_cache,$net, 'net', 'attack');
    $net_current_c = get_current_metric($host_qualification_cache,$net_qualification_cache,$net, 'net', 'compromise');
    
    @$groups[$group]['current_a']+= $net_current_a;
    @$groups[$group]['current_c']+= $net_current_c;
    // scores
    $score = get_score($net, 'net');
    @$groups[$group]['max_c']+= $score['max_c'];
    @$groups[$group]['max_a']+= $score['max_a'];
    $net_max_c_time = strtotime($score['max_c_date']);
    $net_max_a_time = strtotime($score['max_a_date']);
    if (!isset($groups[$group]['max_c_date'])) {
        $groups[$group]['max_c_date'] = $score['max_c_date'];
    } else {
        $group_max_c_time = strtotime($groups[$group]['max_c_date']);
        if ($net_max_c_time > $group_max_c_time) {
            $groups[$group]['max_c_date'] = $score['max_c_date'];
        }
    }
    if (!isset($groups[$group]['max_a_date'])) {
        $groups[$group]['max_a_date'] = $score['max_a_date'];
    } else {
        $group_max_a_time = strtotime($groups[$group]['max_a_date']);
        if ($net_max_c_time > $group_max_c_time) {
            $groups[$group]['max_a_date'] = $score['max_a_date'];
        }
    }
    // If there is no threshold specified for a network, pick the group threshold
    // Changed: get networks by AJAX
    
    $net_threshold_a = $rs->fields['net_threshold_a'] ? $rs->fields['net_threshold_a'] : $group_threshold_a;
    $net_threshold_c = $rs->fields['net_threshold_c'] ? $rs->fields['net_threshold_c'] : $group_threshold_c;
    $groups[$group]['nets'][$net] = array(
        'name' => $net,
        'threshold_a' => $net_threshold_a,
        'threshold_c' => $net_threshold_c,
        'max_a' => $score['max_a'],
        'max_c' => $score['max_c'],
        'max_a_date' => $score['max_a_date'],
        'max_c_date' => $score['max_c_date'],
        'address' => $rs->fields['net_address'],
        'current_a' => $net_current_a,
        'current_c' => $net_current_c,
        'has_perms' => $has_perms
    );
    
    $rs->MoveNext();
}

?>
<table width="100%" class="transparent">
	<tr>
        <th colspan="3"><?php echo _("Network") ?></th>
        <th><?php echo _("Max Date") ?></th>
        <th><?php echo _("Max") ?></th>
        <th><?php echo _("Current") ?></th>
    </tr>
<?php
foreach($groups[$group_name]['nets'] as $net_name => $net_data) {
				$net++;
                $num_hosts = isset($net_data['hosts']) ? count($net_data['hosts']) : 0;
?>
                <tr id="net_<?php echo $net
?>_<?php echo $ac
?>">
                    
                    <td width="3%" class="noborder">&nbsp;</td>
                    <td style="text-align: left">
                        <?php
                if ($num_hosts) { ?>
                        <a id="a_<?php echo ++$a
?>_<?php echo $ac
?>" href="javascript: toggle('host', <?php echo $host + 1 ?>, <?php echo $num_hosts ?>, 'a_<?php echo $a ?>');"><img src="../pixmaps/plus-small.png" align="absmiddle" border="0"></a>&nbsp;
                        <?php
                } ?>
                        <?php echo $net_name ?>
                    </td>
                    <?php
                html_set_values($net_name, 'net', $net_data["max_$ac"], $net_data["max_{$ac}_date"], $net_data["current_$ac"], $net_data["threshold_$ac"], $ac);
?>
                    <td nowrap><?php echo html_rrd() ?> <?php echo html_incident() ?></td>
                    <td nowrap><?php echo html_date() ?></td>
                    <?php echo html_max() ?>
                    <?php echo html_current() ?>
                </tr>
                <?php
                if (isset($net_data['hosts'])) {
                    foreach($net_data['hosts'] as $host_ip => $host_data) {
                        $host++;
?>
                        <tr id="host_<?php echo $host
?>_<?php echo $ac
?>" style="display: none">
                            <td width="6%" style="border: 0px;">&nbsp;</td>
                            <td style="text-align: left">&nbsp;&nbsp;
                                <?php echo html_host_report($host_ip, $host_data['name']) ?>
                            </td>
                            <?php
                        html_set_values($host_ip, 'host', $host_data["max_$ac"], $host_data["max_{$ac}_date"], $host_data["current_$ac"], $host_data["threshold_$ac"], $ac);
?>
                            <td nowrap><?php echo html_rrd() ?> <?php echo html_incident() ?></td>
                            <td nowrap><?php echo html_date() ?></td>
                            <?php echo html_max() ?>
                            <?php echo html_current() ?>
                        </tr>   
                   <?php
                    } ?>
               <?php
                } ?>
            <?php
            }
?>
</table>
