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
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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
$opc = GET('opc');
ossim_valid($sensor, OSS_ALPHA, OSS_PUNC, OSS_SPACE, 'illegal:' . _("Sensor"));
ossim_valid($opc, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Default option"));
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
    $fr_up = "menu.php?sensor=$sensor&port=$port&proto=$protocol&opc=$opc";
    #$fr_down = "$protocol://$sensor:$port/trafficStats.html";
    #if ($opc == "services") $fr_down = "$protocol://$sensor:$port/sortDataIP.html?showL=0";
    #if ($opc == "throughput") $fr_down = "$protocol://$sensor:$port/sortDataThpt.html?col=1&showL=0";
    #if ($opc == "matrix") $fr_down = "$protocol://$sensor:$port/ipTrafficMatrix.html";
    #if ($opc == "gateways") $fr_down = "$protocol://$sensor:$port/localRoutersList.html";
    #if ($opc == "osandusers") $fr_down = "$protocol://$sensor:$port/localHostsFingerprint.html";
    #if ($opc == "domains") $fr_down = "$protocol://$sensor:$port/domainStats.html";    
} else { //if use_ntop_rewrite is enabled
    $protocol = "http";
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") $protocol = "https";
    $fr_up = "menu.php?sensor=$sensor&opc=$opc";
    #$fr_down = "$protocol://" . $_SERVER['SERVER_NAME'] . "/ntop".(($_SERVER['SERVER_NAME']!=$sensor) ? "-$sensor/" : "/");
}
//$fr_down = preg_replace("/https?:\/\/(.*?)\//","/ntop/",$fr_down);
?>
<frameset rows="35,*" border="0" frameborder="0">
	<frame src="top.php?<?php echo $_SERVER['QUERY_STRING'] ?>" scrolling='no'>
	<frameset rows="40,*" border="0" frameborder="0">
		<frame id="fr_up" src="<?php echo $fr_up ?>">
		<frame id="fr_down" src="" name="ntop">
	</frameset>
</frameset>
<body>
</body>
</html>

