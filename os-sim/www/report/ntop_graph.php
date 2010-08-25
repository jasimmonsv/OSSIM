<?
/*****************************************************************************
*
*    License:
*
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
error_reporting(0);
require_once ('classes/Session.inc');
require_once ('classes/Host.inc');
Session::logcheck("MenuReports", "ReportsHostReport");
require_once 'classes/Security.inc';
require_once ('classes/Sensor.inc');
$db = new ossim_db();
$conn = $db->connect();
$n = GET('n');
$host = GET('host');
$title = GET('title');
ossim_valid($n, OSS_DIGIT, 'illegal:' . _("n"));
ossim_valid($host, OSS_IP_ADDRCIDR, 'illegal:' . _("host"));
ossim_valid($title, OSS_TEXT, 'illegal:' . _("title"));
if (ossim_error()) {
    die(ossim_error());
}
$gbh = 300;
$gbw = 370;
// Network
if (preg_match("/\/\d+/",$host)) {
    require_once "ossim_conf.inc";
    $conf = $GLOBALS["CONF"];
    list($protocol, $ip, $port) = split(':', $conf->get_conf("ntop_link"));
    $sensor_ip = (!$conf->get_conf("use_ntop_rewrite")) ? "$protocol:$ip:$port" : "$protocol:$ip/ntop";
    
    $source1 = "$sensor_ip/ipProtoDistribution.png";
	$tit = $title." Service Distribution";
	$tit2 = "Service Distribution";
	$graph = "ntop_graph_thumb.gif";

	$source2 = "$sensor_ip/plugins/rrdPlugin?action=graphSummary&graphId=4&key=interfaces/eth0/&start=now-12h&end=now";
	$gbh = 500;
	$gbw = 520;
	
	$salida1 = get_headers($source1);
	$salida2 = get_headers($source2);

	if ((!preg_match("/Not Found/",$salida1[0]) && $salida1 != null)
		|| !preg_match("/Not Found/",$salida2[0]) && $salida2 != null) { ?>
	<table align="center" class="noborder">
		<tr><td class="nobborder" style="text-align:center"><a href="net_report_graphs.php" class="greybox" gbh="<?=$gbh?>" gbw="<?=$gbw?>" title="<?=$tit?>"><?=$tit2?><br><img src="../pixmaps/<?=$graph?>"></a></td></tr>
	</table>
	<? }
}
// Host
else {
    $ntop_link = Sensor::get_sensor_link($conn,$host);
	if ($n == 1) {
		$source = "$ntop_link/hostTimeTrafficDistribution-$host-65535.png?1";
		$tit = $title." Traffic Sent";
		$tit2 = "Traffic Sent";
		$graph = "ntop_graph_thumb.gif";
	}
	elseif ($n == 2) {
		$source = "$ntop_link/hostTimeTrafficDistribution-$host-65535.png";
		$tit = $title." Traffic Rcvd";
		$graph = "ntop_graph_thumb.gif";
		$tit2 = "Traffic Rcvd";
	}
	//echo "headers de: ".$source."<br>";
	$salida = get_headers($source);
	//print_r($salida);
	if (!preg_match("/Not Found/",$salida[0]) && $salida != null) { ?>
	<table align="center" class="noborder">
		<tr><td class="nobborder" style="text-align:center"><a href="<?=$source?>" class="greybox" gbh="<?=$gbh?>" gbw="<?=$gbw?>" title="<?=$tit?>"><?=$tit2?><br><img src="../pixmaps/<?=$graph?>"></a></td></tr>
	</table>
	<? }
}
?>
