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
Session::logcheck("MenuMonitors", "MonitorsSession");
?>

<html>
<head>
  <title> <?php
echo gettext("OSSIM"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>

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
if (!$conf->get_conf("use_ntop_rewrite")) {
    $url_parsed = parse_url($conf->get_conf("ntop_link"));
    $port = $url_parsed["port"];
    $protocol = $url_parsed["scheme"];
    $fr_up = "menu.php?sensor=$sensor&port=$port&proto=$protocol";
    $fr_down = "$protocol://$sensor:$port/NetNetstat.html";
} else { //if use_ntop_rewrite is enabled
    $protocol = "http";
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") $protocol = "https";
    $fr_up = "menu.php?sensor=$sensor";
    $fr_down = "$protocol://" . $_SERVER['SERVER_NAME'] . "/ntop-$sensor/NetNetstat.html";
}
?>

<frameset cols="18%,82%" border="0" frameborder="0">
<frame src="<?php
echo $fr_up ?>">
<frame src="<?php
echo $fr_down ?>" name="ntop">

<body>
</body>
</html>
