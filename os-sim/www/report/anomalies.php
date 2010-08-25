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
Session::logcheck("MenuReports", "ReportsHostReport");
?>

<html>
<head>
<title> <?php
echo gettext("OSSIM Framework"); ?> </title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<meta http-equiv=REFRESH content=300>
<meta http-equiv=Pragma content=no-cache>
<meta http-equiv=Cache-Control content=no-cache>
<link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>


<body>

<?php
require_once ('ossim_conf.inc');
require_once ('classes/Sensor.inc');
/* get conf */
$conf = $GLOBALS["CONF"];
$graph_ntop = $conf->get_conf("graph_link");
$tune = validateVar($_GET['tune']);
$ip = validateVar($_GET['host'], OSS_IP);
$end = validateVar($_GET['end']);
$start = validateVar($_GET['start']);
$zoom = 1;
if ($tune) {
    $hwparam = validateVar($_GET['hwparam']);
    $hwvalue = validateVar($_GET['hwvalue']);
    $what = "tune&hwparam=$hwparam&hwvalue=$hwvalue";
} else {
    $what = "anomaly";
}
$param = "$graph_ntop?what=$what&ip=$ip&start=$start&end=$end&zoom=$zoom&file=";
/* Graphs init */
$htable = array(
    "image_pktSent" => $param . "pktSent",
    "image_bytesSent" => $param . "bytesSent",
    "image_bytesSentLoc" => $param . "bytesSentLoc",
    "image_udpSentLoc" => $param . "udpSentLoc",
    "image_totCSP" => $param . "totContactedSentPeers",
    "image_IP_NBios_IPSentBytes" => $param . "IP_NBios-IPSentBytes",
    "image_pktRcvd" => $param . "pktRcvd",
    "image_bytesRcvd" => $param . "bytesRcvd",
    "image_bytesSentRem" => $param . "bytesSentRem",
    "image_bytesRcvdFromRem" => $param . "bytesRcvdFromRem",
    "image_tcpSentRem" => $param . "tcpSentRem",
    "image_udpSentRem" => $param . "udpSentRem",
    "image_icmpSent" => $param . "icmpSent",
    "image_tcpRcvdFromRem" => $param . "tcpRcvdFromRem",
    "image_udpRcvdFromRem" => $param . "udpRcvdFromRem",
    "image_synPktsSent" => $param . "synPktsSent",
    "image_web_sessions" => $param . "web_sessions",
    "image_totContactedRcvdPeers" => $param . "totContactedRcvdPeers",
    "image_IP_HTTPSentBytes" => $param . "IP_HTTPSentBytes",
    "image_IP_HTTPRcvdBytes" => $param . "IP_HTTPRcvdBytes",
    "image_IP_DNSSentBytes" => $param . "IP_DNSSentBytes",
    "image_IP_DNSRcvdBytes" => $param . "IP_DNSRcvdBytes",
    "image_IP_DHCP-BOOTPSentBytes" => $param . "IP_DHCP-BOOTPSentBytes"
);
?>

<div style="font-size: 11pt; font-weight: bold; background-color: #eee;
  color: black;
  border-style: solid;
  border-color: #ddd;
  border-width: 1px;
  padding: 3px;
  margin-top: 0px;
  font-family: sans-serif, arial, helvetica;
  margin-bottom: 10px;"><center> <?php
echo gettext("Info about host"); ?> <?php
echo $ip; ?></center></div><br/>


<center>
[ <A HREF="anomalies.php?host=$ip&end=now&start=now-1y"> <?php
echo gettext("year"); ?> </A> ]
[ <A HREF="anomalies.php?host=$ip&end=now&start=now-1m"> <?php
echo gettext("month"); ?> </A> ]
[ <A HREF="anomalies.php?host=$ip&end=now&start=now-1w"> <?php
echo gettext("week"); ?> </A> ]
[ <A HREF="anomalies.php?host=$ip&end=now&start=now-1d"> <?php
echo gettext("day"); ?> </A> ]
[ <A HREF="anomalies.php?host=$ip&end=now&start=now-12h"> <?php
echo gettext("last 12h"); ?> </A> ]
[ <A HREF="anomalies.php?host=$ip$end=now&start=now-6h"> <?php
echo gettext("last 6h"); ?> </A> ]
[ <A HREF="anomalies.php?host=$ip&end=now&start=now-1h"> <?php
echo gettext("last hour"); ?> </A> ]
<br/><br/>


<table>
<tr><th style="background-color: #31527c; color: #ffffff;">Graphs</th><th style="background-color: #31527c; color: #ffffff;">adjust</th></tr>
<?php
foreach($htable as $element) {
    echo "<tr><td>\r\n";
    echo "<img src=\"" . $element . "\">\r\n";
    echo "</td><td>alpha<br/>beta<br/>treshold</td></tr>\r\n";
}
?>

</table>
</center>

<br/>
<br><b> <?php
echo gettext("NOTE: total and average values are NOT absolute but calculated on the specified time interval"); ?> .</b>

</BODY>
</HTML>
