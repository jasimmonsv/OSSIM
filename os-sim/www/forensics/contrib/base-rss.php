<?php
/**
* Class and Function List:
* Function list:
* - dec2IP()
* - dec2hex()
* Classes list:
*/
//
//base-rss - Queries the snort database and returns an alerts RSS feed with links to BASE.
//Copyright (C) 2006 Daniel Michitsch
//
//This program is free software; you can redistribute it and/or
//modify it under the terms of the GNU General Public License
//as published by the Free Software Foundation; either version 2
//of the License, or (at your option) any later version.
//
//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.
//
//You should have received a copy of the GNU General Public License
//along with this program; if not, write to the Free Software
//Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
//
//
include ("../base_conf.php");
function dec2IP($dec) {
    $hex = dec2hex($dec);
    if (strlen($hex) == 7) $hex = "0" . $hex;
    $one = hexdec(substr($hex, 0, 2));
    $two = hexdec(substr($hex, 2, 2));
    $three = hexdec(substr($hex, 4, 2));
    $four = hexdec(substr($hex, 6, 2));
    $ip = $one . "." . $two . "." . $three . "." . $four;
    return ($ip);
}
function dec2hex($dec) {
    if ($dec > 2147483648) {
        $result = dechex($dec - 2147483648);
        $prefix = dechex($dec / 268435456);
        $suffix = substr($result, -7);
        $hex = $prefix . str_pad($suffix, 7, "0000000", STR_PAD_LEFT);
    } else {
        $hex = dechex($dec);
    }
    $hex = strtoupper($hex);
    return ($hex);
}
mysql_connect($alert_host, $alert_user, $alert_password);
@mysql_select_db($alert_dbname) or die("Unable to select database");
$query = "SELECT * FROM `acid_event` ORDER BY `timestamp` DESC LIMIT 0 , 50";
$result = mysql_query($query);
$num = mysql_numrows($result);
mysql_close();
header("Content-Type: text/xml");
header("Pragma: no-cache");
header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
echo "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
echo "<rss version=\"0.91\">\n";
echo "  <channel>\n";
echo "    <title>Snort Alerts</title>\n";
echo "    <link>http://" . $_SERVER['SERVER_NAME'] . $BASE_urlpath . "</link>\n";
echo "    <ttl>5</ttl>\n";
$i = 0;
while ($i < $num) {
    $sid = mysql_result($result, $i, "sid");
    $cid = mysql_result($result, $i, "cid");
    $sig_name = mysql_result($result, $i, "sig_name");
    $sig_name = str_replace("<", "&lt;", $sig_name);
    $sig_name = str_replace(">", "&gt;", $sig_name);
    $sig_name = str_replace("&", "&amp;", $sig_name);
    $sig_name = str_replace("%", "&#37;", $sig_name);
    $timestamp = mysql_result($result, $i, "timestamp");
    $ip_src = dec2IP(mysql_result($result, $i, "ip_src"));
    $ip_dst = dec2IP(mysql_result($result, $i, "ip_dst"));
    $layer4_sport = mysql_result($result, $i, "layer4_sport");
    $layer4_dport = mysql_result($result, $i, "layer4_dport");
    $timestamp = mysql_result($result, $i, "timestamp");
    $timechars = array(
        "-",
        " ",
        ":"
    );
    $guid = str_replace($timechars, "", $timestamp) + $cid;
    if (!empty($layer4_sport)) $layer4_sport = ":" . $layer4_sport;
    if (!empty($layer4_dport)) $layer4_dport = ":" . $layer4_dport;
    echo "    <item>\n";
    echo "      <title>$sig_name</title>\n";
    echo "      <link>http://" . $_SERVER['SERVER_NAME'] . $BASE_urlpath . "/base_qry_alert.php?submit=%23$i-%28$sid-$cid%29&amp;sort_order=time_d</link>\n";
    echo "      <description>$ip_src$layer4_sport to $ip_dst$layer4_dport - $timestamp</description>\n";
    echo "      <guid>$guid</guid>\n";
    echo "    </item>\n";
    $i++;
}
echo "  </channel>\n";
echo "</rss>\n";
?>
