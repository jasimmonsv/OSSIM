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
* - echo_values()
* Classes list:
*/
require_once ('classes/Session.inc');
Session::logcheck("MenuEvents", "EventsAnomalies");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?php
echo gettext("Control Panel"); ?> </title>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" href="../style/style.css"/>
</head>


<?php
require_once ('ossim_conf.inc');
require_once ('ossim_db.inc');
require_once ('classes/Host.inc');
require_once ('classes/Net.inc');
require_once ('classes/Sensor.inc');
require_once ('classes/Util.inc');
require_once ('classes/RRD_config.inc');
require_once ('classes/RRD_anomaly.inc');
require_once ('classes/RRD_anomaly_global.inc');
function echo_values($val, $max, $ip, $image) {
    global $acid_link;
    global $acid_prefix;
    if ($val - $max > 0) {
        echo "<a href=\"" . Util::get_acid_info($ip, $acid_link, $acid_prefix) . "\"><font color=\"#991e1e\">$val</font></a>/" . "<a href=\"$image\">" . intval($val * 100 / $max) . "</a>%";
    } else {
        echo "<a href=\"" . Util::get_acid_info($ip, $acid_link, $acid_prefix) . "\">$val</a>/" . "<a href=\"$image\">" . intval($val * 100 / $max) . "</a>%";
    }
}
/* get conf */
$conf = $GLOBALS["CONF"];
$graph_link = $conf->get_conf("graph_link");
$acid_link = $conf->get_conf("acid_link");
$acid_prefix = $conf->get_conf("event_viewer");
$ntop_link = $conf->get_conf("ntop_link");
$nagios_link = $conf->get_conf("nagios_link");
/* connect to db */
$db = new ossim_db();
$conn = $db->connect();
?>

<body>

    <table align="center" width="100%">
    <tr>
    <th colspan=8><?php
echo gettext("RRD global anomalies"); ?>
     <a name="Anomalies" href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?#Anomalies" title=" <?php
echo gettext("Fix"); ?> "><img src="../pixmaps/Hammer2.png" width="24" border="0"></a>
    </th>
    </tr>
    <tr>
    <th colspan=4>&nbsp;</th>
    <th align="center"><A HREF="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?acked=1"> <?php
echo gettext("Acknowledged"); ?> </A></th>
    <th align="center"><A HREF="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?acked=0"> <?php
echo gettext("Not Acknowledged"); ?> </A></th>
    <th align="center"><A HREF="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?acked=-1"> <?php
echo gettext("All"); ?> </A></th>
    </tr>
    <tr>
    <th> <?php
echo gettext("Host"); ?> </th><th> <?php
echo gettext("What"); ?> </th><th> <?php
echo gettext("When"); ?> </th>
    <th> <?php
echo gettext("Not acked count (hours)"); ?> </th><th> <?php
echo gettext("Over threshold (absolute)"); ?> </th>
    <th align="center"> <?php
echo gettext("Ack"); ?> </th>
    <th align="center"> <?php
echo gettext("Delete"); ?> </th>
    </tr>

<form action="handle_anomaly.php" method="GET">
<?php
$where_clause = "where acked = 0";
switch (GET('acked')) {
    case -1:
        $where_clause = "";
        break;

    case 0:
        $where_clause = "where acked = 0";
        break;

    case 1:
        $where_clause = "where acked = 1";
        break;
}
$perl_interval = 3600 / 300;
$count = RRD_anomaly_global::get_list_count($conn);
if ($event_list_global = RRD_anomaly_global::get_list($conn, $where_clause, "order by anomaly_time desc", "0", $count)) {
    foreach($event_list_global as $event) {
        $ip = "Global";
        $tmp_data = explode(" ",$event->get_what());
            if ($rrd_list_temp = RRD_config::get_list($conn, "WHERE profile = \"global\" AND rrd_attrib =\"".end($tmp_data)."\"")) {
            $rrd_temp = $rrd_list_temp[0];
        }
?>
<tr>
<th> 

<A HREF="<?php
        echo "$ntop_link/plugins/rrdPlugin?action=list&key=interfaces/eth0&title=interface%20eth0"; ?>" target="_blank"> 
<?php
        echo $ip; ?></A> </th><td> <?php
        echo $event->get_what(); ?></td>
<td> <?php
        echo $event->get_anomaly_time(); ?></td>
<td> <?php
        echo round(($event->get_count()) / $perl_interval); ?><?=_("h.")?> </td>
<td><font color="red"><?php
        echo ($event->get_over() / $rrd_temp->get_threshold()) * 100; ?>%</font>/<?php
        echo $event->get_over(); ?></td>
<td align="center"><input type="checkbox" name="ack,<?php
        echo $ip ?>,<?php
        echo $event->get_what(); ?>"></input></td>
<td align="center"><input type="checkbox" name="del,<?php
        echo $ip ?>,<?php
        echo $event->get_what(); ?>"></input></td>
</tr>
<?php
    }
}
?>
<tr>
<td align="center" colspan="7" class="noborder">
<input type="submit" class="button" value=" <?php
echo gettext("OK"); ?> ">
<input type="reset" class="button" value=" <?php
echo gettext("reset"); ?> ">
</td>
</tr>
</form>
<br/>
 </table>

<?php
$db->close($conn);
?>

</body>
</html>
