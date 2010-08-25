<?php
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
require_once ("geoip.inc");
$gi = geoip_open("/usr/share/geoip/GeoIP.dat", GEOIP_STANDARD);
$config = parse_ini_file("../sem/everything.ini");
$color_words = array(
    'warning',
    'error',
    'failure',
    'break',
    'critical',
    'alert'
);
if (preg_match("/\/(\d+)/",$host,$found)) {
    if ($found[1] >= 24) $hst = preg_replace("/\.\d+\/.*/","",$host);
    elseif ($found[1] >= 16) $hst = preg_replace("/\.\d+\.\d+\/.*/","",$host);
    elseif ($found[1] >= 8) $hst = preg_replace("/\.\d+\.\d+\.\d+\/.*/","",$host);
    $lnk = "net=$hst";
}
else $lnk = "ip=$host";
?>
<table width="100%" class="bordered">
	<tr>
		<td class="headerpr"><a style="color:black" href="../top.php?option=2&soption=1&url=<?=urlencode("sem/index.php?hmenu=SEM&smenu=SEM&query=src_ip=$host OR dst_ip=$host")?>" target="topmenu">Logger Events</a></td>
	</tr>
	<? if (count($sem_events_year) > 0) { ?>
	<?
	// GRAPH
	list($x, $y, $xticks, $xlabels) = Status::range_graphic("month");
	//include ("host_report_sem_graph.php");
	$graph = '<div id="plotareasem" class="plot"></div>';
	$xticks = $sem_wplot_x;
	$xlabels = array();
	foreach ($xticks as $tick=>$val) {
		$xlabels[$tick] = $tick;
	}
	$plot = plot_graphic("plotareasem", 60, 800, $sem_yplot_x, $sem_yplot_y, $xticks, $xlabels, false, "239, 214, 209");
	?>
	<tr>
		<td style="text-align:center">
			<table align="center" style="width:auto">
				<tr><td><?=$graph.$plot?></td></tr>
			</table>
		</td>
	</tr>
	<tr>
		<td>
			<table border='0' width='100%' cellpadding='2'>
				<tr>
					<th><?=gettext("ID")?></th>
					<th><?=gettext("Date")?></th>
					<th><?=gettext("Event type")?></th>
					<th><?=gettext("Sensor")?></th>
					<th><?=gettext("Source")?></th>
					<th><?=gettext("Dest")?></th>
					<th><?=gettext("Data")?></th>
				</tr>
				<?
				$inc_counter = 1 + $offset;
				$cont = 0;
				foreach($sem_events_year as $res) if ($cont++ < 5) {
					$bgcolor = (($cont)%2==0) ? "#EFE0E0" : "#FFFFFF";
					$res = str_replace("<", "", $res);
					$res = str_replace(">", "", $res);
					//entry id='2' fdate='2008-09-19 09:29:17' date='1221816557' plugin_id='4004' sensor='192.168.1.99' src_ip='192.168.1.119' dst_ip='192.168.1.119' src_port='0' dst_port='0' data='Sep 19 02:29:17 ossim sshd[2638]: (pam_unix) session opened for user root by root(uid=0)'
					if (preg_match("/entry id='([^']+)'\s+fdate='([^']+)'\s+date='([^']+)'\s+plugin_id='([^']+)'\s+sensor='([^']+)'\s+src_ip='([^']+)'\s+dst_ip='([^']+)'\s+src_port='([^']+)'\s+dst_port='([^']+)'\s+tzone='[^']+'+\s+data='([^']+)'(\s+sig='([^']*)')?/", $res, $matches)) {
						$lf = explode(";", $res);
						$logfile = urlencode(end($lf));
						$data = $matches[10];
						$signature = $matches[12];
						$query = "select name from plugin where id = " . intval($matches[4]);
						if (!$rs = & $conn->Execute($query)) {
							print $conn->ErrorMsg();
							exit();
						}
						$plugin = htmlspecialchars($rs->fields["name"]);
						if ($plugin == "") {
							$plugin = intval($matches[4]);
						}
						$red = 0;
						$color = "black";
						$date = $matches[2];
						$sensor = $matches[5];
						$src_ip = $matches[6];
						$dst_ip = $matches[7];
						$country = strtolower(geoip_country_code_by_addr($gi, $src_ip));
						$country_name = geoip_country_name_by_addr($gi, $src_ip);
						if ($country) {
							$country_img_src = " <img src=\"/ossim/pixmaps/flags/" . $country . ".png\" alt=\"$country_name\" title=\"$country_name\">";
						} else {
							$country_img_src = "";
						}
						$dst_ip = $matches[7];
						$country = strtolower(geoip_country_code_by_addr($gi, $dst_ip));
						$country_name = geoip_country_name_by_addr($gi, $dst_ip);
						if ($country) {
							$country_img_dst = " <img src=\"/ossim/pixmaps/flags/" . $country . ".png\" alt=\"$country_name\" title=\"$country_name\">";
						} else {
							$country_img_dst = "";
						}
						$src_port = $matches[8];
						$dst_port = $matches[9];
						$target = ($greybox) ? "target='main'" : "";
						$line = "<tr>
					<td nowrap bgcolor='$bgcolor'>" . "<a $target href=\"../incidents/newincident.php?" . "ref=Alarm&" . "title=" . urlencode($plugin . " Event") . "&" . "priority=1&" . "src_ips=$src_ip&" . "event_end=$date&" . "src_ports=$src_port&" . "dst_ips=$dst_ip&" . "dst_ports=$dst_port" . "\">" . "<img src=\"../pixmaps/incident.png\" width=\"12\" alt=\"i\" border=\"0\"/></a> " . $inc_counter . "</td>
					<td nowrap bgcolor='$bgcolor'>" . htmlspecialchars($matches[2]) . "</td>
					<td bgcolor='$bgcolor'><font color=\"$color\">$plugin</font></td>
					<td bgcolor='$bgcolor'>";
						$line.= "<font color=\"$color\">" . htmlspecialchars($matches[5]) . "</font></td><td bgcolor='$bgcolor'>";
						$line.= "<font color=\"$color\">" . htmlspecialchars($matches[6]) . ":</font>";
						$line.= "<font color=\"$color\">" . htmlspecialchars($matches[8]) . "</font></td><td bgcolor='$bgcolor'>";
						$line.= "<font color=\"$color\">" . htmlspecialchars($matches[7]) . ":</font>";
						$line.= "<font color=\"$color\">" . htmlspecialchars($matches[9]) . "</font></td>";
						if ($alt) {
							$color = "grey";
							$alt = 0;
						} else {
							$color = "blue";
							$alt = 1;
						}
						$verified = - 1;
						if ($signature != '') {
							$sig_dec = base64_decode($signature);
							$verified = 0;
							$pub_key = openssl_pkey_get_public($config["pubkey"]);
							$verified = openssl_verify($data, $sig_dec, $pub_key);
						}
						$data = $matches[10];
						$encoded_data = base64_encode($data);
						$data = "<td bgcolor='$bgcolor'>";
						// change ,\s* or #\s* adding blank space to force html break line
						$matches[10] = preg_replace("/(\,|\#)\s*/", "\\1 ", $matches[10]);
						foreach(split("[\| \t;:]", $matches[10]) as $piece) {
							$clean_piece = str_replace("(", " ", $piece);
							$clean_piece = str_replace(")", " ", $clean_piece);
							$clean_piece = str_replace("[", " ", $clean_piece);
							$clean_piece = str_replace("]", " ", $clean_piece);
							$clean_piece = htmlspecialchars($piece);
							$red = 0;
							foreach($color_words as $word) {
								if (stripos($clean_piece, $word)) {
									$red = 1;
									break;
								}
							}
							if ($red) {
								$data.= "<font color=\"red\">" . $clean_piece;
							} else {
								$data.= "<font color=\"$color\">" . $clean_piece;
							}
						}
						if ($verified >= 0) {
							if ($verified == 1) {
								$data.= '<img src="' . $config["verified_graph"] . '" height=15 width=15 alt="V" />';
							} else if ($verified == 0) {
								$data.= '<img src="' . $config["failed_graph"] . '" height=15 width=15 alt="F" />';
							} else {
								$data.= '<img src="' . $config["error_graph"] . '" height=15 width=15 alt="E" />';
								$data.= openssl_error_string();
							}
						}
						//$data.= '<a href="validate.php?log=' . $encoded_data . "&start=$start&end=$end&logfile=$logfile" . '" class="thickbox" rel="AjaxGroup" target="_blank"> <small>(Validate signature)</small></a>';
						$data.= "</td>";
						$line.= $data;
						$inc_counter++;
					}
					print $line;
				}
				?>
			</table>
		</td>
	</tr>
	<tr><td><table><tr><td style="text-align:left"><b><?=$sem_foundrows_year?></b> <?=gettext("Logger total events")?> <?=_("in")?> <b><?=_("year range")?></b></td><td style="text-align:right;padding-right:20px"><a style="color:black" href="../top.php?option=2&soption=1&url=<?=urlencode("sem/index.php?hmenu=SEM&smenu=SEM&query=$lnk")?>" target='topmenu'><b><?=gettext("More")?> >></b></a></td></tr></table></td></tr>
	<? } else { ?>
	<tr>
		<td class="nobborder" style="text-align:center"><?=gettext("No Logger Events found for")?> <i><?=$host?></i></td>
	</tr>
	<tr><td><table><tr><td style="text-align:right;padding-right:20px"><a style="color:black" href="../top.php?option=2&soption=1&url=<?=urlencode("sem/index.php?hmenu=SEM&smenu=SEM&query=$lnk")?>" target='topmenu'><b><?=gettext("More")?> >></b></a></td></tr></table></td></tr>
	<? } ?>
</table>
