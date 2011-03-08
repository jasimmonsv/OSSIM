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
require_once ('classes/Session.inc');
require_once ('classes/Net.inc');
Session::logcheck("MenuEvents", "ControlPanelSEM");
require_once 'classes/Security.inc';
$date_from = GET('date_from');
$date_to = GET('date_to');
ossim_valid($date_from, OSS_DAY, OSS_NULLABLE, 'illegal:' . _("From date"));
ossim_valid($date_to, OSS_DAY, OSS_NULLABLE, 'illegal:' . _("From date"));
if (ossim_error()) {
    die(_("Incorrect Date Format.")."<br />"._("(yyyy-mm-dd)"));
}
include ("geoip.inc");
$gi = geoip_open("/usr/share/geoip/GeoIP.dat", GEOIP_STANDARD);
//
$conf = $GLOBALS["CONF"];
require_once ('ossim_db.inc');
$db = new ossim_db();
$conn = $db->connect();
$query_temp = array();
$allowed = array();
$port = explode ("\n",`grep 'Listen' /etc/apache2/ports.conf | awk '{print $2}'`);
$_SERVER["APACHE_PORT"]= (is_array($port) && intval($port[0])>0) ? intval($port[0]) : 80;
//
if ($date_from!="") $from = "AND day>=".str_replace("-","",$date_from);
if ($date_from!="") $from2 = "day>=".str_replace("-","",$date_from);
if ($date_to!="") $to = "AND day<=".str_replace("-","",$date_to);
#
$dateDiff = strtotime($date_to) - strtotime($date_from);
$ddiff = floor($dateDiff/(60*60*24));
//
// TOP ATTACKER
//
$data = array();
$info = array();

$als = "'".str_replace(",","','",Session::allowedSensors())."'";
$als = ($als != "''") ? "AND sem_stats.sensor in ($als)" : "";

$sql = "select sem_stats.sensor,value,sum(counter) as total,host.hostname,host_os.os from sem_stats left join host on host.ip=sem_stats.value left join host_os on host_os.ip=inet_aton(sem_stats.value) where 1=1 AND ($from2 $to) $als AND type='src_ip' group by sem_stats.sensor,value order by total desc limit 30";
//$sql = "select sem_stats.sensor,value,sum(counter) as total,host.hostname,host_os.os from sem_stats left join host on host.ip=sem_stats.value left join host_os on host_os.ip=inet_aton(sem_stats.value) where 1=1 $from $to AND type='src_ip' group by sem_stats.sensor,value";
if (!$rs = & $conn->Execute($sql)) {
	//print $conn->ErrorMsg();die();
	return;
}

while (!$rs->EOF) {
	//if ($allowed[$rs->fields["sensor"]] || Session::hostAllowed($conn, $rs->fields["sensor"])) {
		$allowed[$rs->fields["sensor"]]=1;
		$data[$rs->fields["value"]] = $rs->fields["total"];
		if (!is_array($info[$rs->fields["value"]])) {
			$s_country = strtolower(geoip_country_code_by_addr($gi, $rs->fields["value"]));
                        
			if($s_country=="me"||$s_country=="eu"||$s_country=="ap"||$s_country==""){
                            $flag='NULL';
                        }else{
                            $flag = '"http'.($_SERVER["APACHE_PORT"]=="443" ? "s" : "").'://'.$_SERVER['SERVER_ADDR'].':'.$_SERVER["APACHE_PORT"];
                            $flag.='/ossim/pixmaps/flags/'.$s_country.'.png';
                        }
                        $os_img='"http'.($_SERVER["APACHE_PORT"]=="443" ? "s" : "").'://'.$_SERVER['SERVER_ADDR'].':'.$_SERVER["APACHE_PORT"];
                        $os_img.='/ossim/pixmaps';
			$os = Host_os::get_os_pixmap_nodb($rs->fields["os"],$os_img,true);
			//$s_country_name = geoip_country_name_by_addr($gi, $ip);
			$info[$rs->fields["value"]] = array ("os" => $os, "hostname" => $rs->fields["hostname"], "flag" => $flag);
		}
	//}
	$rs->MoveNext();
}
//sort and limit
//arsort($data);
//$data=array_slice($data, 0, 30);
//
foreach ($data as $ip => $value) {
	$os = $info[$ip]["os"]!="" ? '"'.$info[$ip]["os"].'"' : 'NULL';
	$hostname = (trim($info[$ip]["hostname"])!="") ? $info[$ip]["hostname"] : $ip;
	//$flag = $info[$ip]["flag"];
        $flag = $info[$ip]["flag"]!="NULL" ? '"'.$info[$ip]["flag"].'"' : 'NULL';
	$sql = "insert into report_data (id_report_data_type,user,dataV1,dataV2,dataV3,dataI1) values (14,\"".$_SESSION['_user']."\", \"".$hostname."\",".$os.",".$flag.",".$value.")";
	$query_temp[] = $sql;
}

//
// TOP ATTACKED
//
$data = array();
$sql = "select sem_stats.sensor,value,sum(counter) as total,host.hostname,host_os.os from sem_stats left join host on host.ip=sem_stats.value left join host_os on host_os.ip=inet_aton(sem_stats.value) where 1=1 AND ($from2 $to) $als AND type='dst_ip' group by sem_stats.sensor,value order by total desc limit 30";
//$sql = "select sem_stats.sensor,value,sum(counter) as total,host.hostname,host_os.os from sem_stats left join host on host.ip=sem_stats.value left join host_os on host_os.ip=inet_aton(sem_stats.value) where 1=1 $from $to AND type='dst_ip' group by sem_stats.sensor,value";
if (!$rs = & $conn->Execute($sql)) {
	//print $conn->ErrorMsg();
	return;
}
while (!$rs->EOF) {
	//if ($allowed[$rs->fields["sensor"]] || Session::hostAllowed($conn, $rs->fields["sensor"])) {
		$allowed[$rs->fields["sensor"]]=1;
		$data[$rs->fields["value"]] = $rs->fields["total"];
		if (!is_array($info[$rs->fields["value"]])) {
			$s_country = strtolower(geoip_country_code_by_addr($gi, $rs->fields["value"]));
			$flag = ($s_country=="me"||$s_country=="eu"||$s_country=="ap"||$s_country=="") ? 'NULL' : 'http://'.$conf->get_conf("bi_host").'/ossim/pixmaps/flags/'.$s_country.'.png';
			$os = Host_os::get_os_pixmap_nodb($rs->fields["os"],'http://'.$conf->get_conf("bi_host").'/ossim/pixmaps',true);
			//$s_country_name = geoip_country_name_by_addr($gi, $ip);
			$info[$rs->fields["value"]] = array ("os" => $os, "hostname" => $rs->fields["hostname"], "flag" => $flag);
		}
	//}
	$rs->MoveNext();
}
//sort and limit
//arsort($data);
//$data=array_slice($data, 0, 30);
//
foreach ($data as $ip => $value) {
	$os = $info[$ip]["os"]!="" ? '"'.$info[$ip]["os"].'"' : 'NULL';
	$hostname = (trim($info[$ip]["hostname"])!="") ? $info[$ip]["hostname"] : $ip;
        $flag = $info[$ip]["flag"]!="NULL" ? '"'.$info[$ip]["flag"].'"' : 'NULL';
	$sql = "insert into report_data (id_report_data_type,user,dataV1,dataV2,dataV3,dataI1) values (15,\"".$_SESSION['_user']."\", \"".$hostname."\",".$os.",".$flag.",".$value.")";
	$query_temp[] = $sql;
}
//
// USED PORTS
//
$data = array();
$sql = "select sensor,value,sum(counter) as total,port.service from sem_stats left join port on port.port_number=sem_stats.value where port.protocol_name='tcp' AND ($from2 $to) $als AND type='dst_port' group by sensor,value order by total desc limit 30";
//$sql = "select sensor,value,sum(counter) as total,port.service from sem_stats left join port on port.port_number=sem_stats.value where port.protocol_name='tcp' $from $to             AND type='dst_port' group by sensor,value";
//$sql = "select sensor,value,sum(counter) as total,port.service from sem_stats left join port on port.port_number=sem_stats.value where port.protocol_name='tcp' $from $to AND type='dst_port' group by sensor,value";
if (!$rs = & $conn->Execute($sql)) {
	//print $conn->ErrorMsg();
	return;
}
while (!$rs->EOF) {
	//if ($allowed[$rs->fields["sensor"]] || Session::hostAllowed($conn, $rs->fields["sensor"])) {
		$allowed[$rs->fields["sensor"]]=1;
		$data[$rs->fields["value"].":".$rs->fields["service"]] = $rs->fields["total"];
	//}
	$rs->MoveNext();
}
//sort and limit
//arsort($data);
//$data=array_slice($data, 0, 30);
//
foreach ($data as $service => $value) {
	$port = explode(":",$service);
	$sql = "insert into report_data (id_report_data_type,user,dataV1,dataV2,dataI1) values (16,\"".$_SESSION['_user']."\", '".$port[0]."','".$port[1]."','$value')";
	$query_temp[] = $sql;
}
//
// EVENT TYPES
//
$data = array();
$sql = "select sensor,value,sum(counter) as total,plugin.name from sem_stats left join plugin on plugin.id=sem_stats.value where 1=1 AND ($from2 $to) $als AND sem_stats.type='plugin_id' group by sensor,value order by total desc limit 30";
//$sql = "select sensor,value,sum(counter) as total,plugin.name from sem_stats left join plugin on plugin.id=sem_stats.value where 1=1 $from $to AND sem_stats.type='plugin_id' group by sensor,value";
if (!$rs = & $conn->Execute($sql)) {
	//print $conn->ErrorMsg();
	return;
}
while (!$rs->EOF) {
	//if ($allowed[$rs->fields["sensor"]] || Session::hostAllowed($conn, $rs->fields["sensor"])) {
		$allowed[$rs->fields["sensor"]]=1;
		$key = ($rs->fields["name"]!="") ? $rs->fields["name"] : $rs->fields["value"];
		$data[$key] = $rs->fields["total"];
	//}
	$rs->MoveNext();
}
//sort and limit
//arsort($data);
//$data=array_slice($data, 0, 30);
//
foreach ($data as $plugin => $value) {
	$sql = "insert into report_data (id_report_data_type,user,dataV1,dataI1) values (17,\"".$_SESSION['_user']."\", '$plugin','$value')";
	$query_temp[] = $sql;
}
//
// EVENT TREND
//
$data = array();
if ($ddiff <= 31) { // days view
        $sql = "select day,sensor,sum(counter) as total from sem_stats_events where 1=1 AND ($from2 $to) $als group by day,sensor order by day";
        //$sql = "select day,sensor,sum(counter) as total from sem_stats_events where 1=1 $from $to group by day,sensor order by day";
	if (!$rs = & $conn->Execute($sql)) {
		//print $conn->ErrorMsg();
		return;
	}
	while (!$rs->EOF) {
		//if ($allowed[$rs->fields["sensor"]] || Session::hostAllowed($conn, $rs->fields["sensor"])) {
			$allowed[$rs->fields["sensor"]]=1;
			$data[$rs->fields["day"]] = $rs->fields["total"];
		//}
		$rs->MoveNext();
	}
	$format = "F, j";
} elseif ($ddiff <= 365) { // months
        $sql = "select month(day) as m,year(day) as y,sensor,sum(counter) as total from sem_stats_events where 1=1 AND ($from2 $to) $als group by m,y,sensor order by y,m";
	//$sql = "select month(day) as m,year(day) as y,sensor,sum(counter) as total from sem_stats_events where 1=1 $from $to group by m,y,sensor order by y,m";
	if (!$rs = & $conn->Execute($sql)) {
		//print $conn->ErrorMsg();
		return;
	}
	while (!$rs->EOF) {
		//if ($allowed[$rs->fields["sensor"]] || Session::hostAllowed($conn, $rs->fields["sensor"])) {
			$allowed[$rs->fields["sensor"]]=1;
			$m = ($rs->fields["m"]<10) ? "0".$rs->fields["m"]."01" : $rs->fields["m"]."01";
			$data[$rs->fields["y"].$m] += $rs->fields["total"];
		//}
		$rs->MoveNext();
	}
	$format = "F Y";
} else {
        $sql = "select year(day) as y,sensor,sum(counter) as total from sem_stats_events where 1=1 AND ($from2 $to) $als group by y,sensor order by y";
	//$sql = "select year(day) as y,sensor,sum(counter) as total from sem_stats_events where 1=1 $from $to group by y,sensor order by y";
	if (!$rs = & $conn->Execute($sql)) {
		//print $conn->ErrorMsg();
		return;
	}
	while (!$rs->EOF) {
		//if ($allowed[$rs->fields["sensor"]] || Session::hostAllowed($conn, $rs->fields["sensor"])) {
			$allowed[$rs->fields["sensor"]]=1;
			$data[$rs->fields["y"]."0101"] += $rs->fields["total"];
		//}
		$rs->MoveNext();
	}
	$format = "Y";
}
//sort by date
foreach ($data as $date => $value) {
	$date = date($format,strtotime($date));
	$sql = "insert into report_data (id_report_data_type,user,dataV1,dataI1) values (18,\"".$_SESSION['_user']."\", '$date','$value')";
	$query_temp[] = $sql;
}
//
// EXECUTE INSERTS
//
sql_report_data($conn,$query_temp);
echo _("OK");
$db->close($conn);

function sql_report_data($conn,$sql=NULL){
	if($sql==NULL){
		die(_("No data"));
	}
	$conn->Execute('use datawarehouse');
	$conn->Execute('delete from report_data where id_report_data_type in (14,15,16,17,18) AND user="'.$_SESSION['_user'].'"');
	foreach($sql as $value){
		//print $value."<br>";
		$conn->Execute($value);
	}
}
?>