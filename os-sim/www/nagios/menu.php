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
Session::logcheck("MenuMonitors", "MonitorsAvailability");
?>

<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body style="overflow:hidden">


<?php
require_once ("classes/Security.inc");
$sensor = GET('sensor'); if (trim($sensor)=="") $sensor="localhost";
$opc = GET('opc');
ossim_valid($sensor, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, OSS_SPACE, 'illegal:' . _("Sensor"));
ossim_valid($opc, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Default option"));
if (ossim_error()) {
    die(ossim_error());
}
require_once ('ossim_db.inc');
$db = new ossim_db();
$conn = $db->connect();
require_once ('ossim_conf.inc');
$conf = $GLOBALS["CONF"];
$nagios_default = parse_url($conf->get_conf("nagios_link"));
require_once ('classes/Sensor.inc');
$sensor_list = Sensor::get_all($conn, "ORDER BY name");
/* nagios link */
$scheme = isset($nagios_default["scheme"]) ? $nagios_default["scheme"] : "http";
$path = isset($nagios_default["path"]) ? $nagios_default["path"] : "/nagios/";
$path = str_replace("//", "/", $path);
if ($path[0] != "/") {
    $path = "/" . $path;
}
$port = isset($nagios_default["port"]) ? ":" . $nagios_default["port"] : "";
$nagios = (($sensor!=$_SERVER["SERVER_ADDR"]) ? "$scheme://$sensor"."$port" : "")  . "$path";
?>

<table class="noborder"><td><td valign="top" class="nobborder" nowrap>

<!-- change sensor -->
<form method="GET" action="menu.php">
<input type="hidden" name="opc" value="<?=$opc?>">
<?php
echo gettext("Sensor"); ?>:&nbsp; <select name="sensor" onChange="submit()">

<?php
/*
* default option (nagios_link at configuration)
*/
$option = "<option ";
if ($sensor == $nagios_default["host"]) $option.= " SELECTED ";
$option.= ' value="' . $nagios_default["host"] . '">default</option>';
print "$option\n";
if (is_array($sensor_list)) {
    foreach($sensor_list as $s) {
        $properties = Sensor::get_properties($conn,$s->get_ip());
        if ($properties["has_nagios"]) {
            /*
            * one more option for each sensor (at policy->sensors)
            */
            $option = "<option ";
            if ($sensor == $s->get_ip()) $option.= " SELECTED ";
            $option.= ' value="' . $s->get_ip() . '">' . $s->get_name() . '</option>';
            print "$option\n";
        }
    }
}
$db->close($conn);
?>
</select>
</form>
<!-- end change sensor -->

</td><td valign="top" class="nobborder" style="padding:3px 0px 0px 30px">

<?php
if ($opc == "") { ?>
<!--

<a href="<?php // echo "$nagios/cgi-bin/tac.cgi"
     ?>"
   target="nagios">Tactical Overview</a> | 
-->

[ <a href="<?php
    echo "$nagios/cgi-bin/status.cgi?host=all" ?>"
   target="nagios"><?php
    echo gettext("Service Detail") ?></a> | 

<a href="<?php
    echo "$nagios/cgi-bin/status.cgi?hostgroup=all&style=hostdetail" ?>"
   target="nagios"><?php
    echo gettext("Host Detail") ?></a> | 

<a href="<?php
    echo "$nagios/cgi-bin/status.cgi?hostgroup=all" ?>"
   target="nagios"><?php
    echo gettext("Status Overview") ?></a> | 

<a href="<?php
    echo "$nagios/cgi-bin/status.cgi?hostgroup=all&style=grid" ?>"
   target="nagios"><?php
    echo gettext("Status Grid") ?></a> | 

<a href="<?php
    echo "$nagios/cgi-bin/statusmap.cgi?host=all" ?>"
   target="nagios"><?php
    echo gettext("Status Map") ?></a> | 

<a href="<?php
    echo "$nagios/cgi-bin/status.cgi?host=all&servicestatustypes=248" ?>"
   target="nagios"><?php
    echo gettext("Service Problems") ?></a> | 

<a href="<?php
    echo "$nagios/cgi-bin/status.cgi?hostgroup=all&style=hostdetail&hoststatustypes=12" ?>"
   target="nagios"><?php
    echo gettext("Host Problems") ?></a> | 

<a href="<?php
    echo "$nagios/cgi-bin/outages.cgi" ?>"
   target="nagios"><?php
    echo gettext("Network Outages") ?></a> | 

<a href="<?php
    echo "$nagios/cgi-bin/extinfo.cgi?&type=3" ?>"
   target="nagios"><?php
    echo gettext("Comments") ?></a> | 

<a href="<?php
    echo "$nagios/cgi-bin/extinfo.cgi?&type=6" ?>"
   target="nagios"><?php
    echo gettext("Downtime") ?></a> | 

<a href="<?php
    echo "$nagios/cgi-bin/extinfo.cgi?&type=0" ?>"
   target="nagios"><?php
    echo gettext("Process Info") ?></a> | 

<a href="<?php
    echo "$nagios/cgi-bin/extinfo.cgi?&type=4" ?>"
   target="nagios"><?php
    echo gettext("Performance Info") ?></a> | 

<a href="<?php
    echo "$nagios/cgi-bin/extinfo.cgi?&type=7" ?>"
   target="nagios"><?php
    echo gettext("Scheduling Queue") ?></a> ]

<?php
} ?>

<?php
if ($opc == "reporting") { ?>

[ <a href="<?php
    echo "$nagios/cgi-bin/trends.cgi" ?>"
   target="nagios"><?php
    echo gettext("Trends") ?></a> | 

<a href="<?php
    echo "$nagios/cgi-bin/avail.cgi" ?>"
   target="nagios"><?php
    echo gettext("Availability") ?></a> | 

<a href="<?php
    echo "$nagios/cgi-bin/histogram.cgi" ?>"
   target="nagios"><?php
    echo gettext("Event Histogram") ?></a> | 

<a href="<?php
    echo "$nagios/cgi-bin/history.cgi?host=all" ?>"
   target="nagios"><?php
    echo gettext("Event History") ?></a> | 

<a href="<?php
    echo "$nagios/cgi-bin/summary.cgi" ?>"
   target="nagios"><?php
    echo gettext("Event Summary") ?></a> | 

<a href="<?php
    echo "$nagios/cgi-bin/notifications.cgi?contact=all" ?>"
   target="nagios"><?php
    echo gettext("Notifications") ?></a> | 

<a href="<?php
    echo "$nagios/cgi-bin/showlog.cgi" ?>"
   target="nagios"><?php
    echo gettext("Performance Info") ?></a> ] 
<?php
} ?>

</td>
</tr></table>

</body>
</html>

