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
include "../graphs/charts.php";
function baseLong2IP($long_IP) {
    $tmp_IP = $long_IP;
    if ($long_IP > 2147483647) {
        $tmp_IP = 4294967296 - $tmp_IP;
        $tmp_IP = $tmp_IP * (-1);
    }
    $tmp_IP = long2ip($tmp_IP);
    return $tmp_IP;
}
function IPProto2str($ipproto_code) {
    switch ($ipproto_code) {
        case 0:
            return "IP";
        case 1:
            return "ICMP";
        case 2:
            return "IGMP";
        case 4:
            return "IPIP tunnels";
        case 6:
            return "TCP";
        case 8:
            return "EGP";
        case 12:
            return "PUP";
        case 17:
            return "UDP";
        case 22:
            return "XNS UDP";
        case 29:
            return "SO TP Class 4";
        case 41:
            return "IPv6 header";
        case 43:
            return "IPv6 routing header";
        case 44:
            return "IPv6 fragmentation header";
        case 46:
            return "RSVP";
        case 47:
            return "GRE";
        case 50:
            return "IPSec ESP";
        case 51:
            return "IPSec AH";
        case 58:
            return "ICMPv6";
        case 59:
            return "IPv6 no next header";
        case 60:
            return "IPv6 destination options";
        case 92:
            return "MTP";
        case 98:
            return "Encapsulation header";
        case 103:
            return "PIM";
        case 108:
            return "COMP";
        case 255:
            return "Raw IP";
        default:
            return $ipproto_code;
    }
}
function get_graph_url($index) {
	//var_dump($index);
	//$shortmonths = array('Jan'=>'01', 'Feb'=>'02', 'Mar'=>'03', 'Apr'=>'04', 'May'=>'05', 'Jun'=>'06', 'Jul'=>'07', 'Aug'=>'08', 'Sep'=>'09', 'Oct'=>'10', 'Nov'=>'11', 'Dec'=>'12');
	$months = array('January'=>'01', 'February'=>'02', 'March'=>'03', 'April'=>'04', 'May'=>'05', 'June'=>'06', 'July'=>'07', 'August'=>'08', 'September'=>'09', 'October'=>'10', 'November'=>'11', 'December'=>'12');
	$daysmonths = array('January'=>'31', 'February'=>'28', 'March'=>'31', 'April'=>'30', 'May'=>'31', 'June'=>'30', 'July'=>'31', 'August'=>'31', 'September'=>'30', 'October'=>'31', 'November'=>'30', 'December'=>'31');
	$url = "new=1&submit=Query+DB&num_result_rows=-1";

	//Today (8h)
	if (preg_match("/^(\d+) h/",$index,$found)) {
		$url .= "&time_range=".$_SESSION['time_range']."&time[0][1]=".urlencode(">=");
		$url .= "&time[0][2]=".date("m");
		$url .= "&time[0][3]=".date("d");
		$url .= "&time[0][4]=".date("Y");
		$url .= "&time[0][5]=".$found[1];
		$url .= "&time[0][6]=00&time[0][7]=00";
		$url .= "&time_cnt=2";
		$url .= "&time[1][1]=".urlencode("<=");
		$url .= "&time[1][2]=".date("m");
		$url .= "&time[1][3]=".date("d");
		$url .= "&time[1][4]=".date("Y");
		$url .= "&time[1][5]=".$found[1];
		$url .= "&time[1][6]=59&time[1][7]=59";
	}
	// Last 24 Hours (21 8 -> 21h 8Sep)
	elseif (preg_match("/^(\d+) (\d+)/",$index,$found)) {
		$desde= strtotime($found[2]."-".date("m")."-".date("Y")." ".$found[1].":00:00");
		$fecha_actual = strtotime(date("d-m-Y H:i:00",time()));
		if($fecha_actual<$desde) { $anio = strval((int)date("Y")-1);}
		else $anio = date("Y");
		
		$url .= "&time_range=".$_SESSION['time_range']."&time[0][1]=".urlencode(">=");
		$url .= "&time[0][2]=".date("m");
		$url .= "&time[0][3]=".$found[2];
		$url .= "&time[0][4]=".$anio;
		$url .= "&time[0][5]=".$found[1];
		$url .= "&time[0][6]=00&time[0][7]=00";
		$url .= "&time_cnt=2";
		$url .= "&time[1][1]=".urlencode("<=");
		$url .= "&time[1][2]=".date("m");
		$url .= "&time[1][3]=".$found[2];
		$url .= "&time[1][4]=".$anio;
		$url .= "&time[1][5]=".$found[1];
		$url .= "&time[1][6]=59&time[1][7]=59";
	}
	//Last Week, Last two Weeks, Last Month (5 September)
	elseif (preg_match("/^(\d+) ([A-Z].+)/",$index,$found)) {
		$desde= strtotime($found[1]."-".$months[$found[2]]."-".date("Y")." 00:00:00");
		$fecha_actual = strtotime(date("d-m-Y H:i:00",time()));
		if($fecha_actual<$desde) { $anio = strval((int)date("Y")-1);}
		else $anio = date("Y");
		
		$url .= "&time_range=".$_SESSION['time_range']."&time[0][1]=".urlencode(">=");
		$url .= "&time[0][2]=".$months[$found[2]];
		$url .= "&time[0][3]=".$found[1];
		$url .= "&time[0][4]=".$anio;
		$url .= "&time[0][5]=00";
		$url .= "&time[0][6]=00&time[0][7]=00";
		$url .= "&time_cnt=2";
		$url .= "&time[1][1]=".urlencode("<=");
		$url .= "&time[1][2]=".$months[$found[2]];
		$url .= "&time[1][3]=".$found[1];
		$url .= "&time[1][4]=".$anio;
		$url .= "&time[1][5]=23";
		$url .= "&time[1][6]=59&time[1][7]=59";
	}
	//All (October 2009)
	elseif (preg_match("/^([A-Z].+) (\d+)/",$index,$found)) {
		$url .= "&time_range=".$_SESSION['time_range']."&time[0][1]=".urlencode(">=");
		$url .= "&time[0][2]=".$months[$found[1]];
		$url .= "&time[0][3]=01";
		$url .= "&time[0][4]=".$found[2];
		$url .= "&time[0][5]=00";
		$url .= "&time[0][6]=00&time[0][7]=00";
		$url .= "&time_cnt=2";
		$url .= "&time[1][1]=".urlencode("<=");
		$url .= "&time[1][2]=".$months[$found[1]];
		$url .= "&time[1][3]=".$daysmonths[$found[1]];
		$url .= "&time[1][4]=".$found[2];
		$url .= "&time[1][5]=23";
		$url .= "&time[1][6]=59&time[1][7]=59";
	}

	return $url;
}
function plot_graphic($id, $height, $width, $xaxis, $yaxis, $xticks, $xlabel, $display = false, $bgcolor="#EDEDED", $host="") {
	//var_dump($xlabel);
	//var_dump($xticks);
    $urls="";
    $plot = '<script language="javascript" type="text/javascript">';
    $plot.= '$( function () {';
    $plot.= 'var options = { ';
    $plot.= 'lines: { show:true, labelHeight:0, lineWidth: 0.7},';
    $plot.= 'points: { show:false, radius: 2 }, legend: { show: false },';
    $plot.= 'yaxis: { ticks:[] }, xaxis: { tickDecimals:0, ticks: [';
    if (sizeof($xticks) > 0) {
        foreach($xticks as $k => $v) {
            $plot.= '[' . $v . ',"' . $xlabel[$k] . '"],';
			//echo "[".$k."] ";
			//$urls .= "url['".$yaxis[$k]."-".$v."'] = '../forensics/base_qry_main.php?".get_graph_url($k)."&ip=$host';\n";
        }
        $plot = preg_replace("/\,$/", "", $plot);
    }
    $plot.= ']},';
    $plot.= 'grid: { color: "#8E8E8E", labelMargin:0, backgroundColor: "#FFFFFF", tickColor: "#D2D2D2", hoverable:true, clickable:true}';
    $plot.= ', shadowSize:1 };';
    $plot.= 'var data = [{';
    //$plot.= 'color: "rgb(18,55,95)", label: "Events", ';
	$plot.= 'color: "rgb('.$bgcolor.')", label: "Events", ';
    $plot.= 'lines: { show: true, fill: true},'; //$plot .= 'label: "Day",';
    $plot.= 'data:[';
	foreach($xaxis as $k => $v) {
        $plot.= '[' . $v . ',' . $yaxis[$k] . '],';
        //$urls .= "url['".$yaxis[$k]."-".$v."'] = '?".get_graph_url($k)."';\n";
    }
    $plot = preg_replace("/\,$/", "]", $plot);
    $plot.= ' }];';
    $plot.= 'var plotarea = $("#' . $id . '");';
    if ($display == true) {
        $plot.= 'plotarea.css("display", "");';
        $width = '((window.innerWidth || document.body.clientWidth)/2)';
    }
    $plot.= 'plotarea.css("height", ' . $height . ');';
    $plot.= 'plotarea.css("width", ' . $width . ');';
    $plot.= '$.plot( plotarea , data, options );';
    //if ($display==true) {
    $plot.= 'var previousPoint = null;
			$("#' . $id . '").bind("plothover", function (event, pos, item) {
				if (item) {
					if (previousPoint != item.datapoint) {
						previousPoint = item.datapoint;
						$("#tooltip").remove();
						var x = item.datapoint[0].toFixed(0), y = item.datapoint[1].toFixed(0);
						showTooltip(item.pageX, item.pageY, y + " " + item.series.label,y+"-"+x);
					}
				}
				else {
					$("#tooltip").remove();
					previousPoint = null;
				}
			});';
    //}
    $plot.= "});\n";
    $plot.= $urls.'</script>';
    return $plot;
}
list($x, $y, $xticks, $xlabels) = Status::range_graphic("week");
?>

<table align="center" width="100%" class="bordered">
	<tr>
		<td class="headerpr"><a style="color:black" href="../top.php?option=2&soption=0&url=<?=urlencode("forensics/base_qry_main.php?num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d&ip=$host&date_range=week&hmenu=Forensics&smenu=Forensics")?>" target="topmenu">SIEM Events</a></td>
	</tr>
	<?
	// GRAPH
	$graph = '<div id="plotareag" class="plot"></div>';
	$yy = $sim_gplot;
	//print_r($yy);
	$plot = plot_graphic("plotareag", 60, 800, $x, $yy, $xticks, $xlabels, false, "131,137,175",$host);
	?>
	<tr>
		<td style="text-align:center">
			<table align="center" style="width:auto">
				<tr>
					<td nowrap style="text-align:left"><b><?=$sim_numevents?></b> SIEM total events<br> in <b>week range</b></td>
					<td><?=$graph.$plot?></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td>
			<table>
				<tr>
					<td><iframe frameborder="0" width="310" height="300" src="../graphs/draw_swf_graph.php?source_graph=host_report.php&width=270&height=270"></iframe></td>
					<td valign="top">
						<table>
							<? if (count($unique_events) < 1) { ?>
							<tr><td><?=gettext("No Unique Events Found for")?> <i><?=$host?></i></td></tr>
							<? } else { ?>
							<tr>
								<th><?=gettext("Most Common Event: Last Week")?></th>
								<th>Total #</th>
								<? if ($network) { ?><th>IP src</th><? } ?>
								<? if ($network) { ?><th>IP dst</th><? } ?>
								<th>Sensor #</th>
								<th>Src/Dst Addr.</th>
								<th><?=gettext("Graph")?></th>
							</tr>
							<? $i = 0;foreach ($unique_events as $ev) {
								if ($i >= 6) continue;
								$color = (($i+1)%2==0) ? "#E1EFE0" : "#FFFFFF";
								//$perc = "(".round($ev['sig_cnt'] / $event_cnt * 100)."%)";
								// GRAPH
								$graph = '<div id="plotarea' . $i . '" class="plot"></div>';
								$yy = $plots[$i];
								//print_r($yy);
								$plot = plot_graphic("plotarea" . $i, 37, 300, $x, $yy, $xticks, $xlabels, false, "131,137,175", $host);
								$tmp_rowid = "#1-(" . $ev['sid'] . "-" . $ev['cid'] . ")";
							?>
							<tr>
								<td bgcolor="<?=$color?>"><a href="../top.php?option=2&soption=0&url=<?=urlencode("forensics/base_qry_alert.php?submit=".rawurlencode($tmp_rowid)."&hmenu=Forensics&smenu=Forensics")?>" style="text-align:left;color: #17457c;font-size:10px" target="topmenu"><b><?=$ev['sig_name']?></b></a></td>
								<td bgcolor="<?=$color?>"><?=Util::number_format_locale($ev['sig_cnt'],0)?></td>
								<? if ($network) { ?><td bgcolor="<?=$color?>"><?=long2ip($ev['ip_s'])?></td><? } ?>
								<? if ($network) { ?><td bgcolor="<?=$color?>"><?=long2ip($ev['ip_d'])?></td><? } ?>
								<td bgcolor="<?=$color?>"><?=$ev['num_sensors']?></td>
								<td bgcolor="<?=$color?>"><?=$ev['ip_src']?>/<?=$ev['ip_dst']?></td>
								<td><?=$graph.$plot?></td>
							</tr>
							<? $i++; } ?>
							<? } ?>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
<!--
	<? if (count($sim_events) < 1) { ?>
	<tr><td>No SIEM Events Found for <i><?=$host?></i></td></tr>
	<? } else { ?>
	<tr>
		<td class="nobborder">
			<table class="noborder" width="100%">
				<tr>
					<th>Event</th>
					<th>Date</th>
					<th>Source</th>
					<th>Destination</th>
					<th>Asst</th>
					<th>Prio</th>
					<th>Rel</th>
					<th>Risk</th>
					<th>L4-proto</th>
				</tr>
			<?
			$i = 0;
			foreach ($sim_events as $sim_event) { if ($i >= 5) continue;
				$color = ($i%2==0) ? "#F2F2F2" : "#FFFFFF";
				$current_sip32 = $sim_event['sip'];
				$current_sip = baseLong2IP($current_sip32);
				$current_dip32 = $sim_event['dip'];
				$current_dip = baseLong2IP($current_dip32);
				
				$current_oasset_s = $sim_event['oasset_s'];
				$current_oasset_d = $sim_event['oasset_d'];
				$current_oprio = $sim_event['prio'];
				$current_oreli = $sim_event['rel'];
				$current_oriskc = $sim_event['risk_c'];
				$current_oriska = $sim_event['risk_a'];
				$proto = IPProto2str($sim_event['proto']);
				
				if ($current_sip32 != "") {
					$country = strtolower(geoip_country_code_by_addr($gi, $current_sip));
					$country_name = geoip_country_name_by_addr($gi, $current_sip);
					if ($country) {
						$country_img = " <img src=\"/ossim/pixmaps/flags/" . $country . ".png\" alt=\"$country_name\" title=\"$country_name\">";
					} else {
						$country_img = "";
					}
					$ip_aux = ($sensors[$current_sip] != "") ? $sensors[$current_sip] : (($hosts[$current_sip] != "") ? $hosts[$current_sip] : $current_sip);
					$ip_src = '<A HREF="base_stat_ipaddr.php?ip=' . $current_sip . '&amp;netmask=32">' . $ip_aux . '</A><FONT SIZE="-1">' . $current_sport . '</FONT>' . $country_img;
				} else {
					/* if no IP address was found check if this is a spp_portscan message
					* and try to extract a source IP
					* - contrib: Michael Bell <michael.bell@web.de>
					*/
					if (stristr($current_sig_txt, "portscan")) {
						$line = split(" ", $current_sig_txt);
						foreach($line as $ps_element) {
							if (ereg("[0-9]*\.[0-9]*\.[0-9]*\.[0-9]", $ps_element)) {
								$ps_element = ereg_replace(":", "", $ps_element);
								$ip_src = "<A HREF=\"base_stat_ipaddr.php?ip=" . $ps_element . "&amp;netmask=32\">" . $ps_element . "</A>";
							}
						}
					} else $ip_src = '<A HREF="' . $BASE_urlpath . '/help/base_app_faq.php#1">' . _UNKNOWN . '</A>';
				}
				if ($current_dip32 != "") {
					$country = strtolower(geoip_country_code_by_addr($gi, $current_dip));
					$country_name = geoip_country_name_by_addr($gi, $current_dip);
					if ($country) {
						$country_img = " <img src=\"/ossim/pixmaps/flags/" . $country . ".png\" alt=\"$country_name\" title=\"$country_name\">";
					} else {
						$country_img = "";
					}
					$ip_aux = ($sensors[$current_dip] != "") ? $sensors[$current_dip] : (($hosts[$current_dip] != "") ? $hosts[$current_dip] : $current_dip);
					$ip_dst = '<A HREF="base_stat_ipaddr.php?ip=' . $current_dip . '&amp;netmask32">' . $ip_aux . '</A><FONT SIZE="-1">' . $current_dport . '</FONT>' . $country_img;
				} else $ip_dst = '<A HREF="' . $BASE_urlpath . '/help/base_app_faq.php#1">' . _UNKNOWN . '</A>';
				
				// Asst
				$asst = "<img src=\"../forensics/bar2.php?value=" . $current_oasset_s . "&value2=" . $current_oasset_d . "&max=5\" border='0' align='absmiddle'>&nbsp;";
				
				// Prio
				$prio = "<img src=\"../forensics/bar2.php?value=" . $current_oprio . "&max=5\" border='0' align='absmiddle'>&nbsp;";
				
				// Rel
				$rel = "<img src=\"../forensics/bar2.php?value=" . $current_oreli . "&max=9\" border='0' align='absmiddle'>&nbsp;";
				
				// Risk
				$sim_risk = "<img src=\"../forensics/bar2.php?value=" . $current_oriskc . "&value2=" . $current_oriska . "&max=9&range=1\" border='0' align='absmiddle'>&nbsp;";
			?>
			<tr>
				<td class="nobborder" style="background-color:<?=$color?>;border-right:1px solid white"><?=$sim_event['sig_name']?></td>
				<td bgcolor="<?=$color?>" style="border-right:1px solid white"><?=$sim_event['timestamp']?></td>
				<td bgcolor="<?=$color?>"><?=$ip_src?></td>
				<td bgcolor="<?=$color?>"><?=$ip_dst?></td>
				<td class="nobborder"><?=$asst?></td>
				<td class="nobborder"><?=$prio?></td>
				<td class="nobborder"><?=$rel?></td>
				<td class="nobborder"><?=$sim_risk?></td>
				<td class="nobborder" align="center"><?=$proto?></td>
			</tr>
			<? $i++; } ?>
			</table>
		</td>
	</tr>
	<? } ?>
-->
	<?
	// CLOUDS
	// Default font sizes
	$min_font_size = 12;
	$max_font_size = 35;
	?>
	<tr>
		<td>
			<table height="100%">
				<tr>
					<td width="33%" valign="top">
						<table height="100%">
							<tr><th height="10"><?=gettext("Destination Ports")?></th></tr>
							<? if (count($sim_ports) < 1) { ?>
							<tr><td><?=gettext("No ports found")?></td></tr>
							<? } else { 
								$minimum_count = min(array_values($sim_ports));
								$maximum_count = max(array_values($sim_ports));
								$spread = $maximum_count - $minimum_count;
								if ($spread == 0) {
									$spread = 1;
								}
							?>
							<tr>
								<td bgcolor="#f2f2f2" style="border:1px solid #DDDDDD">
								<? foreach ($sim_ports as $port=>$port_val) { if ($port == 0) continue; 
									$size = $min_font_size + ($port_val - $minimum_count) * ($max_font_size - $min_font_size) / $spread;
								?>
								<a href="../top.php?option=2&soption=0&url=<?=urlencode("forensics/base_qry_main.php?")."tcp_port%5B0%5D%5B0%5D=+&tcp_port%5B0%5D%5B1%5D=layer4_dport&tcp_port%5B0%5D%5B2%5D=%3D&tcp_port%5B0%5D%5B3%5D=$port&tcp_port%5B0%5D%5B4%5D=+&tcp_port%5B0%5D%5B5%5D=+&tcp_flags%5B0%5D=+&layer4=TCP&num_result_rows=-1&current_view=-1&submit=QUERYDBP&sort_order=sig_a&clear_allcriteria=1&clear_criteria=time&hmenu=Forensics&smenu=Forensics"?>" target="topmenu" style="font-size:<?=$size?>px" class="tag_cloud"><?=$port?></a>&nbsp;
								<? } ?>
								</td>
							</tr>
							<? } ?>
						</table>
					</td>
					<td width="33%" valign="top">
						<table height="100%">
							<tr><th height="10"><?=gettext("Event Sources")?></th></tr>
							<? if (count($sim_ipsrc) < 1) { ?>
							<tr><td><?=gettext("No sources found")?></td></tr>
							<? } else { 
								$minimum_count = min(array_values($sim_ipsrc));
								$maximum_count = max(array_values($sim_ipsrc));
								$spread = $maximum_count - $minimum_count;
								if ($spread == 0) {
									$spread = 1;
								}?>
							<tr>
								<td bgcolor="#f2f2f2" style="border:1px solid #DDDDDD">
								<? foreach ($sim_ipsrc as $ip=>$ip_val) { if ($ip == $host) continue; 
									$size = $min_font_size + ($ip_val - $minimum_count) * ($max_font_size - $min_font_size) / $spread;?>
								<a href="../top.php?option=2&soption=0&url=<?=urlencode("forensics/base_qry_main.php?num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d&ip=$ip&date_range=week&hmenu=Forensics&smenu=Forensics")?>" target='topmenu' style="font-size:<?=$size?>px" class="tag_cloud"><?=$ip?></a>&nbsp;&nbsp;
								<? } ?>
								</td>
							</tr>
							<? } ?>
						</table>
					</td>
					<td width="33%" valign="top">
						<table height="100%">
							<tr><th height="10"><?=gettext("Event Destinations")?></th></tr>
							<? if (count($sim_ipdst) < 1) { ?>
							<tr><td><?=gettext("No destinations found")?></td></tr>
							<? } else { 
								$minimum_count = min(array_values($sim_ipdst));
								$maximum_count = max(array_values($sim_ipdst));
								$spread = $maximum_count - $minimum_count;
								if ($spread == 0) {
									$spread = 1;
								}?>
							<tr>
								<td bgcolor="#f2f2f2" style="border:1px solid #DDDDDD">
								<? foreach ($sim_ipdst as $ip=>$ip_val) { if ($ip == $host) continue; 
									$size = $min_font_size + ($ip_val - $minimum_count) * ($max_font_size - $min_font_size) / $spread;?>
								<a href="../top.php?option=2&soption=0&url=<?=urlencode("forensics/base_qry_main.php?num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d&ip=$ip&date_range=week&hmenu=Forensics&smenu=Forensics")?>" target='topmenu' style="font-size:<?=$size?>px" class="tag_cloud"><?=$ip?></a>&nbsp;&nbsp;
								<? } ?>
								</td>
							</tr>
							<? } ?>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr><td><table><tr><td style="text-align:left;font-size:10px"><?=gettext("Time range")?>: <b><?=gettext("Last Week")?></b></td><td style="text-align:right;padding-right:20px"><a style="color:black" href="../top.php?option=2&soption=0&url=<?=urlencode("forensics/base_qry_main.php?num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d&ip=$host&date_range=week&hmenu=Forensics&smenu=Forensics")?>" target='topmenu'><b><?=gettext("More")?> >></b></a></td></tr></table></td></tr>
</table>
