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
* - authenticate()
* - dateRFC()
* Classes list:
*/
/* HTTP Authentication */
function authenticate() {
    header('WWW-Authenticate: Basic realm="OSSIM XML Feed"');
    header('HTTP/1.0 401 Unauthorized');
    echo "You need to enter a valid username and password.";
    exit();
}
function dateRFC() {
    return date("Y-m-d\TH:i:s\Z");
}
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    authenticate();
}
require_once ('classes/Session.inc');
$session = new Session($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], "");
if (!$session->login()) {
    echo 'bad password';
    exit();
}
header('Content-Type: text/xml');
echo "<?xml version=\"1.0\" encoding=\"utf-8\" ?>
<feed xmlns=\"http://www.w3.org/2005/Atom\">
<title>OSSIM Alarm Console</title>
<link rel=\"self\" href=\"http://" . $_SERVER['SERVER_ADDR'] . "/ossim/rss.php\" />
<updated>" . dateRFC() . "</updated>
<id>http://www.ossim.net/</id>
";
require_once ('ossim_db.inc');
require_once ('classes/Host.inc');
require_once ('classes/Host_os.inc');
require_once ('classes/Alarm.inc');
require_once ('classes/Plugin.inc');
require_once ('classes/Plugin_sid.inc');
require_once ('classes/Port.inc');
require_once ('classes/Util.inc');
$ITEMS = 50;
$db = new ossim_db();
$conn = $db->connect();
$inf = 0;
$sup = $ITEMS;
$count = Alarm::get_count($conn, $src_ip, $dst_ip, $hide_closed);
$time_start = time();
if ($alarm_list = Alarm::get_list($conn, $src_ip, $dst_ip, $hide_closed, "ORDER by timestamp DESC", $inf, $sup)) {
    $datemark = "";
    foreach($alarm_list as $alarm) {
        /* hide closed alarmas */
        if ($alarm->get_status() == "closed") continue;
        $id = $alarm->get_plugin_id();
        $sid = $alarm->get_plugin_sid();
        $backlog_id = $alarm->get_backlog_id();
        $sid_name = "";
        if ($plugin_sid_list = Plugin_sid::get_list($conn, "WHERE plugin_id = $id AND sid = $sid")) {
            $sid_name = $plugin_sid_list[0]->get_name();
        } else {
            $sid_name = "Unknown (id=$id sid=$sid)";
        }
        $date = Util::timestamp2date($alarm->get_timestamp());
        if ($backlog_id != 0) {
            $since = Util::timestamp2date($alarm->get_since());
        } else {
            $since = $date;
        }
        $datemark = $date_slices[0];
        $alarm_name = ereg_replace("directive_event: ", "", $sid_name);
        $alarm_name = Util::translate_alarm($conn, $alarm_name, $alarm);
        $alarm_name_orig = $alarm_name;
        if ($backlog_id != 0) {
            $events_link = "events.php?backlog_id=$backlog_id";
            $alarm_name = $events_link;
        } else {
            $events_link = $_SERVER["SCRIPT_NAME"];
            $alarm_link = Util::get_acid_pair_link($date, $alarm->get_src_ip() , $alarm->get_dst_ip());
            $alarm_name = $alarm_link;
        }
        $src_ip = $alarm->get_src_ip();
        $dst_ip = $alarm->get_dst_ip();
        $src_port = Port::port2service($conn, $alarm->get_src_port());
        $dst_port = Port::port2service($conn, $alarm->get_dst_port());
        $sensors = $alarm->get_sensors();
        $risk = $alarm->get_risk();
        $src_link = "report/index.php?host=$src_ip&section=events";
        $dst_link = "report/index.php?host=$dst_ip&section=events";
        $src_name = Host::ip2hostname($conn, $src_ip);
        $dst_name = Host::ip2hostname($conn, $dst_ip);
        $event_id = $alarm->get_event_id();
        $status = $alarm->get_status();
        echo "
    <entry>
    <title>\n Alarm: $alarm_name_orig Risk: $risk</title>
    <id>http://" . $_SERVER['SERVER_ADDR'] . "/" . urlencode($alarm_name) . "</id>
    <link href=\"http://" . $_SERVER['SERVER_ADDR'] . "/" . urlencode($alarm_name) . "\"/>
    <summary>$alarm_name_orig</summary>
    <content type=\"application/xhtml+xml\" xml:space=\"preserve\">
    <div xmlns=\"http://www.w3.org/1999/xhtml\">
    <strong>Alarm:</strong>  $alarm_name_orig<br/>
    <strong>Risk:</strong> $risk<br/>
    <strong>Date:</strong> $since<br/>
";
        foreach($sensors as $sensor) {
            echo "
    <strong>Sensor:</strong>
    <a href=\"http://" . $_SERVER['SERVER_ADDR'] . "/ossim/sensor/sensor_plugins.php?sensor=$sensor\" >$sensor</a>
    (" . Host::ip2hostname($conn, $sensor) . ")<br/>
        ";
        }
        echo "
    <strong>Source IP:</strong>
    <a href=\"http://" . $_SERVER['SERVER_ADDR'] . "/ossim/" . urlencode($src_link) . "\">$src_ip</a><br/>
    <strong>Destination IP:</strong>
    <a href=\"http://" . $_SERVER['SERVER_ADDR'] . "/ossim/" . urlencode($dst_link) . "\">$dst_ip</a><br/>
    </div>
    </content>
    <author>
    <name>\nOSSIM at " . $_SERVER['SERVER_ADDR'] . "\n </name>
    </author>
    <updated>" . Util::timestamp2RFC1459($alarm->get_timestamp()) . "</updated>
    </entry>
";
    }
}
echo "</feed>\n";
$db->close($conn);
?>
