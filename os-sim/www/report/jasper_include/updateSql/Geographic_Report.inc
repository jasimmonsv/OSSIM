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
set_time_limit(900);
ini_set('memory_limit', '128M');
require_once ('classes/Session.inc');
require_once ('classes/Net.inc');
Session::logcheck("MenuEvents", "EventsForensics");
require_once 'classes/Security.inc';
$date_from = GET('date_from');
$date_to = GET('date_to');
ossim_valid($date_from, OSS_DAY, OSS_NULLABLE, 'illegal:' . _("From date"));
ossim_valid($date_to, OSS_DAY, OSS_NULLABLE, 'illegal:' . _("From date"));
if (ossim_error()) {
    die(_("Error"));
}
include ("geoip.inc");
$gi = geoip_open("/usr/share/geoip/GeoIP.dat", GEOIP_STANDARD);
//
require_once ('ossim_db.inc');
$db = new ossim_db();
$conn = $db->connect();
$query_temp = array();
$port = explode ("\n",`grep 'Listen' /etc/apache2/ports.conf | awk '{print $2}'`);
$_SERVER["APACHE_PORT"]= (is_array($port) && intval($port[0])>0) ? intval($port[0]) : 80;
//
// select src_ip from alarm table and not defined into nets
//
$networks = "";
$nets = Net::get_all($conn);
$nets_ips = $ips = array();
foreach ($nets as $net) $nets_ips[] = $net->get_ips();
$networks = implode(",",$nets_ips);
// src_ips from acid_event
if ($date_from!="") $from = "AND day>='$date_from'";
if ($date_to!="") $to = "AND day<='$date_to'";
$sql = "select inet_ntoa(ip_src) as ip_src,inet_ntoa(ip_dst) as ip_dst from snort.ac_srcaddr_ipdst where 1=1 $from $to";
//echo $sql;

if (!$rs = & $conn->Execute($sql)) {
	print $conn->ErrorMsg();
	return;
}
$already = array();
while (!$rs->EOF) {
    $ip = $rs->fields["ip_src"];
    if (!isset($already[$ip]) && Session::hostAllowed($conn,$rs->fields["ip_dst"])) {
        $already[$ip]++;
        if (!Net::isIpInNet($ip, $networks)) {
            // geoip
            $s_country = strtolower(geoip_country_code_by_addr($gi, $ip));
            $s_country_name = geoip_country_name_by_addr($gi, $ip);
            if ($s_country=="")
                $ips[":Unknown"]++;
            else
                $ips["$s_country:$s_country_name"]++;
        } else {
            $ips["local:Local"]++;
        }
    }
    $rs->MoveNext();
}
//
arsort($ips);
// LIMIT 30
$ips=array_slice($ips, 0, 30);
//
$totalValue=array_sum($ips);
//

$conf = $GLOBALS["CONF"];
foreach ($ips as $country => $val) {
	// type=6 Top Attackers from Country
	$cou = explode(":",$country);
        if($cou[0]==""){
            $flag = 'NULL';
        }else{
            if($cou[0]=="me"||$cou[0]=="eu"||$cou[0]=="ap"){
                $flag = '"NULL"';
            }elseif ($cou[0]=="local") {
                $flag = '"http://'.$_SERVER['SERVER_ADDR'].':'.$_SERVER["APACHE_PORT"];
                $flag.='/ossim/images/homelan.png';
            }else{
                $flag = '"http://'.$_SERVER['SERVER_ADDR'].':'.$_SERVER["APACHE_PORT"];
                $flag.='/ossim/pixmaps/flags/'.$cou[0].'.png"';
            }
        }
        //
        $porcent=$val*100/$totalValue;
        //
	$sql = "insert into report_data (id_report_data_type,user,dataV1,dataV2,dataI1,dataI2) values (6,\"".$_SESSION['_user']."\", '".$cou[1]."',$flag,'$val','$porcent')";
	$query_temp[] = $sql;
}
sql_report_data($conn,$query_temp);
echo "OK";
//
$db->close($conn);

function sql_report_data($conn,$sql=NULL){
	if($sql==NULL){
		die(_("Not enough data for geographic report. Only local data available"));
	}
	$conn->Execute('use datawarehouse');
	$conn->Execute('delete from report_data where id_report_data_type=6 AND user="'.$_SESSION['_user'].'"');
	foreach($sql as $value){
		$conn->Execute($value);
	}
}
?>