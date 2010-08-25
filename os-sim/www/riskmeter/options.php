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
Session::logcheck("MenuMonitors", "MonitorsRiskmeter");
?>

<html>
<head>
  <link rel="stylesheet" href="../style/style.css"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
</head>

<body>

<?php
require_once 'classes/Security.inc';
$ip = GET('ip');
ossim_valid($ip, OSS_IP_ADDR, 'illegal:' . _("IP address"));
if (ossim_error()) {
    die(ossim_error());
}
require_once "ossim_conf.inc";
$conf = $GLOBALS["CONF"];
$acid_link = $conf->get_conf("acid_link");
$acid_prefix = $conf->get_conf("event_viewer");
$ntop_link = $conf->get_conf("ntop_link");
$mrtg_link = $conf->get_conf("mrtg_link");
require_once "ossim_db.inc";
$db = new ossim_db();
$conn = $db->connect();
require_once "classes/Sensor.inc";
?>

<p align="center">
  <b><?php
echo $ip ?></b><br/>

[ <a href="<?php
echo "$acid_link/" . $acid_prefix . "_stat_ipaddr.php?ip=$ip&netmask=32" ?>"
     target="main"> <?php
echo gettext("Events"); ?> </a> ] 
[ <a href="<?php
//        echo "$mrtg_link/host_qualification/$ip.html"
echo "../control_panel/show_image.php?range=day&ip=$ip&what=compromise&start=N-1D&type=host&zoom=1"
?>"
     target="main"> <?php
echo gettext("History"); ?> </a> ] 
[ <a href="<?php
echo Sensor::get_sensor_link($conn, $ip) . "/$ip" ?>.html" 
     target="main"> <?php
echo gettext("Monitor"); ?> </a> ]
<!--
[ <a href="<?php
echo "$ntop_link/$ip" ?>.html" 
     target="main">Monitor</a> ]
-->
[ <a href="resetip.php?ip=<?php
echo $ip ?>"
     target="main">Reset</a> ]
</p>

<?php
$db->close($conn);
?>

</body>
</html>
