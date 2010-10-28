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
Session::logcheck("MenuIntelligence", "PolicyPolicy");
?>

<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>
                                                                                
  <h1> <?php
echo gettext("New policy"); ?> </h1>

<?php
require_once 'classes/Security.inc';
$priority = POST('priority');
$active = POST('active');
$group = POST('group');
$order = POST('order');
$begin_hour = POST('begin_hour');
$end_hour = POST('end_hour');
$begin_day = POST('begin_day');
$end_day = POST('end_day');
$descr = POST('descr');
$correlate = POST('correlate');
$cross_correlate = POST('cross_correlate');
$store = POST('store');
$qualify = POST('qualify');
$resend_alarms = POST('resend_alarms');
$resend_events = POST('resend_events');
$target_any = POST('target_any');
$sign = POST('sign');
$sem = POST('sem');
$sim = POST('sim');
ossim_valid($priority, OSS_SCORE, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Priority"));
ossim_valid($begin_hour, OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("Begin hour"));
ossim_valid($begin_day, OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("Begin day"));
ossim_valid($end_day, OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("End day"));
ossim_valid($end_hour, OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("End hour"));
ossim_valid($descr, OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_AT, OSS_NULLABLE, 'illegal:' . _("Description"));
ossim_valid($store, OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_AT, OSS_NULLABLE, 'illegal:' . _("Store"));
ossim_valid($target_any, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Target any"));
ossim_valid($group, OSS_ALPHA, OSS_DIGIT, OSS_SPACE, 'illegal:' . _("Group"));
ossim_valid($active, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Active"));
ossim_valid($order, OSS_DIGIT, 'illegal:' . _("Order"));
ossim_valid($correlate, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("correlate"));
ossim_valid($cross_correlate, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("cross_correlate"));
ossim_valid($store, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("store"));
ossim_valid($qualify, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("qualify"));
ossim_valid($resend_alarms, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("resend_alarms"));
ossim_valid($resend_events, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("resend_events"));
ossim_valid($sign, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("sign"));
ossim_valid($sem, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("sem"));
ossim_valid($sim, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("sim"));
if (ossim_error()) {
    die(ossim_error());
}
if (POST('insert')) {
    require_once ('classes/Policy.inc');
	require_once ('classes/Policy_action.inc');
    require_once ('classes/Response.inc');
    require_once ('classes/Plugingroup.inc');
	require_once ('classes/Port_group_reference.inc');
    require_once ('ossim_db.inc');
    $db = new ossim_db();
    $conn = $db->connect();
    /*
    *  Check correct range of dates
    *
    *  Fri 21h = ((5 - 1) * 7) + 21 = 49
    *  Sat 14h = ((6 - 1) * 7) + 14 = 56
    */
    $begin_expr = (($begin_day - 1) * 7) + $begin_hour;
    $end_expr = (($end_day - 1) * 7) + $end_hour;
    /*
    if ($begin_expr >= $end_expr) {
        require_once ("ossim_error.inc");
        $error = new OssimError();
        $error->display("INCORRECT_DATE_RANGE");
    }
    */
    $minsrc = 0;
    /* SOURCES */
    $source_ips = array();
    $source_host_groups = array();
    $source_nets = array();
    $source_net_groups = array();
    $sources = POST('sources');
    foreach($sources as $source) {
        $src = explode(":", trim($source));
        ossim_valid($src[1], OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_AT, OSS_NULLABLE, 'illegal:' . _($src[1]));
        if (ossim_error()) {
            die(ossim_error());
        }
        switch ($src[0]) {
            case "HOST":
                if ($src[1] != "") $source_ips[] = $src[1];
                break;

            case "HOST_GROUP":
                if ($src[1] != "") $source_host_groups[] = $src[1];
                break;

            case "NETWORK":
                if ($src[1] != "") $source_nets[] = $src[1];
                break;

            case "NETWORK_GROUP":
                if ($src[1] != "") $source_net_groups[] = $src[1];
                break;

            case "ANY":
                $source_ips[] = $src[1] = "any";
                break;
            }
            if ($src[1] != "") $minsrc++;
        }
        if ($minsrc < 1) {
            die(ossim_error(_("At least one Source IP, Host group,Net or Net group required")));
        }
        $mindst = 0;
        /* DESTS */
        $dest_ips = array();
        $dest_host_groups = array();
        $dest_nets = array();
        $dest_net_groups = array();
        $dests = POST('dests');
        foreach($dests as $dest) {
            $src = explode(":", trim($dest));
            ossim_valid($src[1], OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_AT, OSS_NULLABLE, 'illegal:' . _($src[1]));
            if (ossim_error()) {
                die(ossim_error());
            }
            switch ($src[0]) {
                case "HOST":
                    if ($src[1] != "") $dest_ips[] = $src[1];
                    break;

                case "HOST_GROUP":
                    if ($src[1] != "") $dest_host_groups[] = $src[1];
                    break;

                case "NETWORK":
                    if ($src[1] != "") $dest_nets[] = $src[1];
                    break;

                case "NETWORK_GROUP":
                    if ($src[1] != "") $dest_net_groups[] = $src[1];
                    break;

                case "ANY":
                    $dest_ips[] = $src[1] = "any";
                    break;
                }
                if ($src[1] != "") $mindst++;
            }
            if ($mindst < 1) {
                die(ossim_error(_("At least one Destination IP, Host group,Net or Net group required")));
            }
            /* ports */
            $ports = array();
            $port = POST('mboxp');
            foreach($port as $name) {
                ossim_valid($name, OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_AT, OSS_NULLABLE, 'illegal:' . _("$name"));
                if (ossim_error()) {
                    die(ossim_error());
                }
                if ($name != "") $ports[] = $name;
            }
            if (!count($ports)) {
                die(ossim_error(_("At least one Port required")));
            }
            /* plugin groups */
            $plug_groups = array();
            $plug_ids = array();
            $plugins = POST('plugins');
            if ($plugins) {
                foreach($plugins as $group_id => $on) {
                    ossim_valid($group_id, OSS_DIGIT, 'illegal:' . _("Plugin Group ID"));
                    $plug_groups[] = $group_id;
                    $ids = Plugingroup::get_list($conn, "plugin_group.group_id=$group_id");
                    if ($ids[0]) foreach($ids[0]->get_plugins() as $plg) $plug_ids[] = $plg['id'];
                }
            }
            if (!count($plug_groups)) {
                die(ossim_error(_("At least one plugin group required")));
            }
            if (ossim_error()) {
                die(ossim_error());
            }
            /* sensors */
            $sensors = array();
            $sensor = POST('mboxs');
            foreach($sensor as $name) {
                ossim_valid(POST("$name") , OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_AT, OSS_NULLABLE, 'illegal:' . _("$name"));
                if (ossim_error()) {
                    die(ossim_error());
                }
                if ($name != "") $sensors[] = $name;
            }
            if (!count($sensors)) {
                die(ossim_error(_("At least one Sensor required")));
            }
            /* targets (sensors) */
            $targets_sen = array();
            for ($i = 1; $i <= POST('targetsensor'); $i++) {
                $name = "targboxsensor" . $i;
                $aux_name = POST("$name");
                ossim_valid(POST("$name") , OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_AT, OSS_NULLABLE, 'illegal:' . _("$name"));
                if (ossim_error()) {
                    die(ossim_error());
                }
                if (!empty($aux_name)) {
                    $targets_sen[] = POST("$name");
                }
            }
            /* targets (servers) */
            $targets_ser = array();
            for ($i = 1; $i <= POST('targetserver'); $i++) {
                $name = "targboxserver" . $i;
                $aux_name = POST("$name");
                ossim_valid(POST("$name") , OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_AT, OSS_NULLABLE, 'illegal:' . _("$name"));
                if (ossim_error()) {
                    die(ossim_error());
                }
                if (!empty($aux_name)) {
                    $targets_ser[] = POST("$name");
                }
            }
            if (!count($targets_sen) && !count($targets_ser) && (strcasecmp($target_any, "any"))) {
                die(ossim_error(_("At least one Target is required")));
            }
            $target = array_merge((array)$targets_sen, (array)$targets_ser);
            if (!strcasecmp($target_any, "any")) array_push($target, "any");
            /* actions / responses */
            $responses = array();
            $actions = POST('actions');
            if ($actions) {
                foreach($actions as $action_id) {
                    ossim_valid($action_id, OSS_DIGIT, 'illegal:' . _("Action ID"));
                    $responses[] = $action_id;
                }
            }
            if ($order == 0) $order = Policy::get_next_order($conn, $group);
            $newid = Policy::insert($conn, $priority, $active, $group, $order, $begin_hour, $end_hour, $begin_day, $end_day, $descr, $source_ips, $source_host_groups, $dest_ips, $dest_host_groups, $source_nets, $source_net_groups, $dest_nets, $dest_net_groups, $ports, $plug_groups, $sensors, $target, $correlate, $cross_correlate, $store, $qualify, $resend_alarms, $resend_events, $sign, $sem, $sim);
            // Response/Actions
            if (count($responses) > 0) { 
				foreach ($responses as $action_id)
					Policy_action::insert($conn,$action_id,$newid);
				
				Response::insert($conn, "policy $newid", $source_nets, $source_ips, $dest_nets, $dest_ips, $sensors, $ports, $ports, $plug_ids, $responses);
			}
?>
    <p> <?php
            echo gettext("Policy succesfully inserted"); ?> </p>
    <script>document.location.href="policy.php"</script>
<?php
            $db->close($conn);
        }
        // update indicators on top frame
        $OssimWebIndicator->update_display();
?>

</body>
</html>

