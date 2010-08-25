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
Session::logcheck("MenuMonitors", "MonitorsNetwork");
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

<?php
require_once ("classes/Security.inc");
$sensor = GET('sensor');
ossim_valid($sensor, OSS_ALPHA, OSS_PUNC, OSS_SPACE, 'illegal:' . _("Sensor"));
if (ossim_error()) {
    die(ossim_error());
}
require_once ('ossim_conf.inc');
$conf = $GLOBALS["CONF"];
//
// get ntop proto and port from default ntop entry at
// /etc/ossim/framework/ossim.conf
// a better solution ??
//
$url_parsed = parse_url($conf->get_conf("ntop_link"));
$port = $url_parsed["port"];
$proto = $url_parsed["scheme"];
require_once ('ossim_db.inc');
require_once ('classes/Sensor.inc');
require_once ('classes/Net.inc');
$db = new ossim_db();
$conn = $db->connect();
?>

<table align="center"><tr><td>
<form method="GET" action="menu_session.php">
<input type="hidden" name="proto" value="<?php
echo $proto ?>"/>
<input type="hidden" name="port" value="<?php
echo $port ?>"/>
<?php
echo gettext("Sensor"); ?>:&nbsp;
<select name="sensor" onChange="submit()">
<?php
/* Get highest priority sensor first */
$tmp = Sensor::get_list($conn, "ORDER BY priority DESC LIMIT 1");
if (is_array($tmp)) {
    $first_sensor = $tmp[0];
    $option = "<option value='" . $first_sensor->get_ip() . "'>";
    $option.= "Sensor: " . $first_sensor->get_name() . "</option>";
    print $option;
}
if ($sensor_list = Sensor::get_list($conn, "ORDER BY name")) {
    foreach($sensor_list as $s) {
        /*  don't show highest priority sensor again.. */
        if ($s->get_ip() != $first_sensor->get_ip()) {
?>
  <option 
<?php
            if ($sensor == $s->get_ip()) echo " SELECTED ";
?>
    value="<?php
            echo $s->get_ip() ?>"><?php
            echo "Sensor: " . $s->get_name() ?></option>
<?php
        }
    }
}
if ($net_list = Net::get_list($conn)) {
    foreach($net_list as $n) {
?>
    <option
<?php
        if (!strcmp($sensor, $n->get_name())) echo " SELECTED ";
?>
    value="<?php
        echo $n->get_name() ?>"><?php
        echo gettext("Net") . ": " . $n->get_name() ?></option>
<?php
    }
}
?>
</select>
<?php
require_once ('ossim_conf.inc');
$conf = $GLOBALS["CONF"];
if (preg_match('/\d+\.\d+\.\d+\.\d+/', $sensor)) {
?>
<?php
    if (!$conf->get_conf("use_ntop_rewrite")) {
        $ntop_link = "$proto://$sensor:$port";
    } else { //if use_ntop_rewrite is enabled
        $protocol = "http";
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") $protocol = "https";
        $ntop_link = "$protocol://" . $_SERVER['SERVER_NAME'] . "/ntop-$sensor";
    }
?>
<a href="<?php
    echo $ntop_link ?>/NetNetstat.html"
       target="ntop">
       <?php
    echo gettext("Reload"); ?> </a>
<?php
} else {
    if ($net_list = Net::get_list($conn, "WHERE name = '$sensor'")) {
        $net_ips = $net_list[0]->get_ips();
    }
?>
<a href="<?php
    echo "net_session.php?net=$net_ips" ?>"
       target="ntop">
       <?php
    echo gettext("Reload"); ?> </a>
<?php
}
$db->close($conn);
?>
</td></tr>
</form>
</table>

</body>
</html>

