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
$interface = GET('interface');
$proto = GET('proto');
$opc = GET('opc');
ossim_valid($sensor, OSS_ALPHA, OSS_PUNC, OSS_SPACE, 'illegal:' . _("Sensor"));
ossim_valid($interface, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("interface"));
ossim_valid($proto, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("proto"));
ossim_valid($opc, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Default option"));
if (ossim_error()) {
    die(ossim_error());
}
require_once ('ossim_db.inc');
$db = new ossim_db();
$conn = $db->connect();
require_once ('ossim_conf.inc');
$conf = $GLOBALS["CONF"];
$ntop_default = parse_url($conf->get_conf("ntop_link"));
require_once ('classes/Sensor.inc');
if (!$conf->get_conf("use_ntop_rewrite")) {
    /* ntop link */
    $scheme = $ntop_default["scheme"] ? $ntop_default["scheme"] : "http";
    $port = $ntop_default["port"] ? $ntop_default["port"] : "3000";
    $ntop = "$scheme://$sensor:$port";
    if ($opc == "throughput") $ntop = "$scheme://$sensor:$port";
    if ($opc == "matrix") $ntop = "$scheme://$sensor:$port";
    if ($opc == "services") $ntop = "$scheme://$sensor:$port";
    $testntop = $ntop;
} else { //if use_ntop_rewrite is enabled
    $protocol = "http";
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") $protocol = "https";
    $ntop = "$protocol://" . $_SERVER['SERVER_NAME'] . "/ntop".(($_SERVER['SERVER_NAME']!=$sensor) ? "_$sensor/" : "/");
    $testntop = "http://" . $sensor . ":3000/";
}
// check $ntop valid
error_reporting(0);
$testlink = get_headers($testntop);
error_reporting(E_ALL ^ E_NOTICE);

if (!preg_match("/200 OK/",$testlink[0])) {
	$ntop = "errmsg.php";
} elseif (!Session::hostAllowed($conn,$sensor)) {
	$ntop = "errmsg.php?msgcode=1";
}
?>
<script type="text/javascript">
    parent.document.getElementById('fr_down').src="<?=$ntop?>"
</script>
<table class="noborder"><td><td valign="top" class="nobborder">
<!-- change sensor -->
<form method="GET" action="menu.php" style="margin:1px">
<input type="hidden" name="opc" value="<?=$opc?>">
<?php
echo gettext("Sensor"); ?>:&nbsp;
<select name="sensor" onChange="submit()">

<?php
/*
* default option (ntop_link at configuration)
*/
/*
$option = "<option ";
if ($sensor == $ntop_default["host"])
$option .= " SELECTED ";
$option .= ' value="'. $ntop_default["host"] . '">default</option>';
print "$option\n";
*/
/* Get highest priority sensor first */

$tmp = Sensor::get_all($conn, "ORDER BY priority DESC LIMIT 1");
$first_sensor = "";
if ($tmp[0] != "") {
	$first_sensor = $tmp[0];
	$option = "<option value='" . $first_sensor->get_ip() . "'>";
    $option.= $first_sensor->get_name() . "</option>";
    print $option;
}
$sensor_list = Sensor::get_all($conn, "ORDER BY name");
if (is_array($sensor_list)) {
    foreach($sensor_list as $s) {
        /* don't show highest priority sensor again.. */
        if ($first_sensor != "" && $s->get_ip() != $first_sensor->get_ip()) {
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
?>
</select>
</form>
<!-- end change sensor -->

</td><td valign="top" class="nobborder">

<!-- interface selector -->
<?php
require_once ('classes/Sensor_interfaces.inc');
require_once ('classes/SecurityReport.inc');
if ($interface) {
    $fd = @fopen("$ntop/switch.html", "r");
    if ($fd != NULL) {
        while (!feof($fd)) {
            $buffer = fgets($fd, 4096);
            if (ereg("VALUE=([0-9]+)[^0-9]*$interface.*", $buffer, $regs)) {
                $fd2 = @fopen("$ntop/switch.html?interface=$regs[1]", "r");
                if ($fd2 != NULL) fclose($fd2);
            }
        }
        fclose($fd);
    }
}
?>

<form method="GET" action="menu.php" style="margin:1px">
<?php
echo gettext("Interface"); ?>:&nbsp;
<input type="hidden" name="proto" value="<?php
echo $proto ?>"/>
<input type="hidden" name="port" value="<?php
echo $port ?>"/>
<input type="hidden" name="sensor" value="<?php
echo $sensor ?>"/>
<select name="interface" onChange="submit()">

<?php
if ($sensor_list = Sensor::get_list($conn, "$sensor_where")) {
    $sflag = 0;
	foreach($sensor_list as $s) {
        if ($sensor == $s->get_ip()) {
            $sflag = 1;
			if ($sensor_interface_list = Sensor_interfaces::get_list($conn, $s->get_ip())) {
				foreach($sensor_interface_list as $s_int) {
?>
<option 
<?php
                    if (!($interface) && ($s_int->get_main() == 1)) {
                        echo "SELECTED";
                    } elseif ($interface == $s_int->get_interface()) {
                        echo "SELECTED";
                    }
?> value="<?php
                    echo $s_int->get_interface(); ?>">
<?php
                    echo SecurityReport::Truncate($s_int->get_name() , 30, "..."); ?></option>
<?php
                }
            } else {
				echo "<option value=''>- "._("No interfaces found")." -";
			}
        }
    }
	if (!$sflag) {
		echo "<option value=''>- "._("No interfaces found")." -";
	}
} else {
	echo "<option value=''>- "._("No interfaces found")." -";
}
$db->close($conn);
?>

</select>
</form>
<!-- end interface selector -->
</td><td valign="top" class="nobborder" style="padding:3px 0px 0px 30px">

<?php
if ($opc == "") { ?>
<!--<a href="<?php
    echo "$ntop/trafficStats.html" ?>"  target="ntop"><?php
    echo gettext("Global"); ?></a><br/> -->
[ <a href="<?php
    echo "$ntop/NetNetstat.html" ?>" 
   target="ntop"><?php
    echo gettext("Sessions"); ?></a> |
<a href="<?php
    echo "$ntop/sortDataProtos.html" ?>"
   target="ntop"><?php
    echo gettext("Protocols"); ?> </a> |
<a href="<?php
    echo "$ntop/localRoutersList.html" ?>"
   target="ntop"><?php
    echo gettext("Gateways, VLANs"); ?> </a> |
<a href="<?php
    echo "$ntop/localHostsFingerprint.html" ?>"
   target="ntop"><?php
    echo gettext("OS and Users"); ?> </a> |
<a href="<?php
    echo "$ntop/domainStats.html" ?>"
   target="ntop"><?php
    echo gettext("Domains"); ?> </a> ]
<?php
} ?>


<?php
if ($opc == "services") { ?>
[ <a href="<?php
    echo "$ntop/sortDataIP.html?showL=0" ?>"
   target="ntop"><?php
    echo gettext("By host: Total"); ?></a> |
<a href="<?php
    echo "$ntop/sortDataIP.html?showL=1" ?>"
   target="ntop"><?php
    echo gettext("By host: Sent"); ?></a> |
<a href="<?php
    echo "$ntop/sortDataIP.html?showL=2" ?>"
   target="ntop"><?php
    echo gettext("By host: Recv"); ?></a> |
<a href="<?php
    echo "$ntop/ipProtoDistrib.html" ?>"
   target="ntop"><?php
    echo gettext("Service statistic"); ?></a> |
<a href="<?php
    echo "$ntop/ipProtoUsage.html" ?>"
   target="ntop"><?php
    echo gettext("By client-server"); ?></a> ]
<?php
} ?>


<?php
if ($opc == "throughput") { ?>
[ <a href="<?php
    echo "$ntop/sortDataThpt.html?col=1&showL=0" ?>"
   target="ntop"><?php
    echo gettext("By host: Total"); ?></a> |
<a href="<?php
    echo "$ntop/sortDataThpt.html?col=1&showL=1" ?>"
   target="ntop"><?php
    echo gettext("By host: Sent"); ?></a> |
<a href="<?php
    echo "$ntop/sortDataThpt.html?col=1&showL=2" ?>"
   target="ntop"><?php
    echo gettext("By host: Recv"); ?></a> |
<a href="<?php
    echo "$ntop/thptStats.html?col=1" ?>"
   target="ntop"><?php
    echo gettext("Total (Graph)"); ?></a> ]
<?php
} ?>


<?php
if ($opc == "matrix") { ?>
[ <a href="<?php
    echo "$ntop/ipTrafficMatrix.html" ?>"
   target="ntop"><?php
    echo gettext("Data Matrix"); ?></a> |
<a href="<?php
    echo "$ntop/dataHostTraffic.html" ?>"
   target="ntop">
   <?php
    echo gettext("Time Matrix"); ?> </a>]
<?php
} ?>


<?php
if ($opc == "gateways") { ?>
[ <a href="<?php
    echo "$ntop/localRoutersList.html" ?>"
   target="ntop"><?php
    echo gettext("Gateways"); ?></a>  |
<a href="<?php
    echo "$ntop/vlanList.html" ?>"
   target="ntop"><?php
    echo gettext("VLANs"); ?></a> ]
<?php
} ?>

</td>
</tr></table>

</body>
</html>

