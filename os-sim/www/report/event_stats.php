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
* - stat_image()
* Classes list:
*/
require_once ('classes/Session.inc');
Session::logcheck("MenuEvents", "EventsForensics");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>

<?php
include ("../hmenu.php");
require_once 'classes/Security.inc';
require_once ('ossim_conf.inc');
require_once ('ossim_db.inc');
require_once ('classes/Host_qualification.inc');
require_once ('classes/Control_panel_host.inc');
require_once ('classes/Host.inc');
$conf = $GLOBALS["CONF"];
$acid_link = $conf->get_conf("acid_link");
$acid_prefix = $conf->get_conf("event_viewer");
$event_stats_enable = $conf->get_conf("frameworkd_eventstats");
if (!$event_stats_enable) {
    echo "<center><small>" . _("Event Graphs are disabled at configuration level. Should you want to enable them go to ") . "<a href='../conf/main.php?adv=1'>" . _("Configuration->Main") . "</a>," . _("search 'eventstats' and switch to 'Enable'. Restart ossim-framework from commandline afterwards (/etc/init.d/ossim-framework restart).") . "<br/>" . _("Warning: this may cause serious database performance issues.") . "</small></center>";
}
function stat_image($stat, $range) {
    $framework_conf = $GLOBALS["CONF"];
    $graph_link = $framework_conf->get_conf("graph_link");
    switch ($range) {
        case "month":
            $image1 = "$graph_link?ip=$stat&what=stat&start=N-1M&end=N&type=stat&zoom=1";
            break;

        case "week":
            $image1 = "$graph_link?ip=$stat&what=stat&start=N-7D&end=N&type=stat&zoom=1";
            break;

        case "day":
        default:
            $image1 = "$graph_link?ip=$stat&what=stat&start=N-24h&end=N&type=stat&zoom=1";
            break;
    }
    return $image1;
}
$event_stats = split(",", "sensors,sensors_total,uniq_events,categories,total_events,src_ips,dst_ips,uniq_ip_links,source_ports,dest_ports,source_ports_udp,source_ports_tcp,dest_ports_udp,dest_ports_tcp,tcp_events,udp_events,icmp_events,portscan_events");
$curr_rng = "day";
?>

<h3><?php echo _("Sensor Stats"); ?></h3>
<a href="<?php echo $acid_link . $acid_prefix . "_stat_sensor.php?clear_allcriteria=1&clear_criteria=time" ?>">
<img src="<?php echo stat_image("sensors", $curr_rng); ?>" border="0">
</a>
<a href="<?php echo $acid_link . $acid_prefix . "_stat_sensor.php?clear_allcriteria=1&clear_criteria=time" ?>">
<img src="<?php echo stat_image("sensors_total", $curr_rng); ?>" border="0">
</a>
<br/><br/><hr>
<h3><?php echo _("Event Stats"); ?></h3>
<a href="<?php echo $acid_link . $acid_prefix . "_stat_alerts.php?caller=&sort_order=occur_d&clear_allcriteria=1&clear_criteria=time" ?>">
<img src="<?php echo stat_image("uniq_events", $curr_rng); ?>" border="0">
</a>
<a href="<?php echo $acid_link . $acid_prefix . "_qry_main.php?&num_result_rows=-1&submit=Query+DB&current_view=-1&clear_allcriteria=1&clear_criteria=time" ?>">
<img src="<?php echo stat_image("total_events", $curr_rng); ?>" border="0">
</a>
<a href="<?php echo $acid_link . $acid_prefix . "_stat_class.php?sort_order=class_a" ?>">
<img src="<?php echo stat_image("categories", $curr_rng); ?>" border="0">
</a>
<a href="<?php echo $acid_link . $acid_prefix . "_qry_main.php?layer4=TCP&num_result_rows=-1&sort_order=time_d&submit=Query+DB&clear_allcriteria=1&clear_criteria=time" ?>">
<img src="<?php echo stat_image("tcp_events", $curr_rng); ?>" border="0">
</a>
<a href="<?php echo $acid_link . $acid_prefix . "_qry_main.php?layer4=UDP&num_result_rows=-1&sort_order=time_d&submit=Query+DB&clear_allcriteria=1&clear_criteria=time" ?>">
<img src="<?php echo stat_image("udp_events", $curr_rng); ?>" border="0">
</a>
<a href="<?php echo $acid_link . $acid_prefix . "_qry_main.php?layer4=ICMP&num_result_rows=-1&sort_order=time_d&submit=Query+DB&clear_allcriteria=1&clear_criteria=time" ?>">
<img src="<?php echo stat_image("icmp_events", $curr_rng); ?>" border="0">
</a>
<a href="<?php echo $acid_link . $acid_prefix . "_qry_main.php?layer4=RawIP&num_result_rows=-1&sort_order=time_d&submit=Query+DB&clear_allcriteria=1&clear_criteria=time" ?>">
<img src="<?php echo stat_image("portscan_events", $curr_rng); ?>" border="0">
</a>
<br/><br/><hr>
<h3><?php echo _("IP Stats"); ?></h3>
<a href="<?php echo $acid_link . $acid_prefix . "_stat_uaddr.php?addr_type=1&sort_order=daddr_d&clear_allcriteria=1&clear_criteria=time" ?>">
<img src="<?php echo stat_image("src_ips", $curr_rng); ?>" border="0">
</a>
<a href="<?php echo $acid_link . $acid_prefix . "_stat_uaddr.php?addr_type=2&sort_order=saddr_d&clear_allcriteria=1&clear_criteria=time" ?>">
<img src="<?php echo stat_image("dst_ips", $curr_rng); ?>" border="0">
</a>
<a href="<?php echo $acid_link . $acid_prefix . "_stat_iplink.php?clear_allcriteria=1&clear_criteria=time" ?>">
<img src="<?php echo stat_image("uniq_ip_links", $curr_rng); ?>" border="0">
</a>
<br/><br/><hr>
<h3><?php echo _("Port Stats"); ?></h3>
<a href="<?php echo $acid_link . $acid_prefix . "_stat_ports.php?port_type=1&proto=-1&sort_order=occur_d&clear_allcriteria=1&clear_criteria=time" ?>">
<img src="<?php echo stat_image("source_ports", $curr_rng); ?>" border="0">
</a>
<a href="<?php echo $acid_link . $acid_prefix . "_stat_ports.php?port_type=2&proto=-1&sort_order=occur_d&clear_allcriteria=1&clear_criteria=time" ?>">
<img src="<?php echo stat_image("dest_ports", $curr_rng); ?>" border="0">
</a>
<br/><br/>
<a href="<?php echo $acid_link . $acid_prefix . "_stat_ports.php?port_type=1&proto=6&sort_order=occur_d&clear_allcriteria=1&clear_criteria=time" ?>">
<img src="<?php echo stat_image("source_ports_tcp", $curr_rng); ?>" border="0">
</a>
<a href="<?php echo $acid_link . $acid_prefix . "_stat_ports.php?port_type=2&proto=6&sort_order=occur_d&clear_allcriteria=1&clear_criteria=time" ?>">
<img src="<?php echo stat_image("dest_ports_tcp", $curr_rng); ?>" border="0">
</a>
<br/><br/>
<a href="<?php echo $acid_link . $acid_prefix . "_stat_ports.php?port_type=1&proto=17&sort_order=occur_d&clear_allcriteria=1&clear_criteria=time" ?>">
<img src="<?php echo stat_image("source_ports_udp", $curr_rng); ?>" border="0">
</a>
</a>
<a href="<?php echo $acid_link . $acid_prefix . "_stat_ports.php?port_type=2&proto=17&sort_order=occur_d&clear_allcriteria=1&clear_criteria=time" ?>">
<img src="<?php echo stat_image("dest_ports_udp", $curr_rng); ?>" border="0">
</a>
<br/><br/>

</body>
</html>

