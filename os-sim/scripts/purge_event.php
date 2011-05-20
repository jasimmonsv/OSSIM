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
// Delete event from main tables
function PurgeAlert($conn, $sid, $cid, $acid_event_input) {
    $del_table_list = array(
        "iphdr",
        "tcphdr",
        "udphdr",
        "icmphdr",
        "opt",
        "extra_data",
        "acid_ag_alert",
        "acid_event"
    );
    if ($acid_event_input!="") $del_table_list[]=$acid_event_input;
    for ($k = 0; $k < count($del_table_list); $k++) {
        /* If trying to add to an BASE table append ag_ to the fields */
        if (strstr($del_table_list[$k], "acid_ag") == "")
        	$sql2 = "DELETE FROM " . $del_table_list[$k] . " WHERE sid='" . $sid . "' AND cid='" . $cid . "'";
        else
        	$sql2 = "DELETE FROM " . $del_table_list[$k] . " WHERE ag_sid='" . $sid . "' AND ag_cid='" . $cid . "'";
        echo "Delete event from ".$del_table_list[$k]."...";
        $conn->Execute($sql2);
        echo "Done\n";
    }
}
// Delete/Update event ac_ tables
function PurgeAlert_ac($conn, $sid, $cid) {

	if (!$rs = & $conn->Execute("SELECT * FROM acid_event WHERE sid=$sid AND cid=$cid")) {
	    print $conn->ErrorMsg();
	    exit();
	} else {
	     if ($rs->EOF) {
			echo "SID:$sid, CID=$cid Not found in acid_event table\n";
			exit;
	     } else {
	     	$myrow = $rs->fields;
	     	//
			$day = preg_replace("/\s.*/","",$myrow['timestamp']);
	        $plugin_id = $myrow['plugin_id'];
	        $plugin_sid = $myrow['plugin_sid'];
	        $ip_src = $myrow['ip_src'];
	        $ip_dst = $myrow['ip_dst'];
	        $layer4_sport = $myrow['layer4_sport'];
	        $layer4_dport = $myrow['layer4_dport'];
	        $ip_proto = $myrow['ip_proto'];
	        // test to not delete if does not exist
	        $delsql = array();
	        if ($plugin_id != "" && $plugin_sid != "" && $ip_src != "" && $ip_dst != "") {
	            // AC_SENSOR
	            $delsql[] = "update ignore ac_sensor_sid set cid=cid-1 WHERE sid=$sid and day='$day' and cid>0";
	            $delsql[] = "delete from ac_sensor_signature WHERE sid=$sid and day='$day' and plugin_id=$plugin_id and plugin_sid=$plugin_sid";
	            $delsql[] = "delete from ac_sensor_ipsrc WHERE sid=$sid and day='$day' and ip_src=$ip_src";
	            $delsql[] = "delete from ac_sensor_ipdst WHERE sid=$sid and day='$day' and ip_dst=$ip_dst";
	            // AC_ALERTS
	            $delsql[] = "update ignore ac_alerts_signature set sig_cnt=sig_cnt-1 WHERE plugin_id=$plugin_id and plugin_sid=$plugin_sid and day='$day' and sig_cnt>0";
	            $delsql[] = "delete from ac_alerts_sid WHERE plugin_id=$plugin_id and plugin_sid=$plugin_sid and day='$day' and sid=$sid";
	            $delsql[] = "delete from ac_alerts_ipsrc WHERE plugin_id=$plugin_id and plugin_sid=$plugin_sid and day='$day' and ip_src=$ip_src";
	            $delsql[] = "delete from ac_alerts_ipdst WHERE plugin_id=$plugin_id and plugin_sid=$plugin_sid and day='$day' and ip_dst=$ip_dst";
	            // AC_ALERTSCLAS
	            //$delsql[] = "update ignore ac_alertsclas_classid set cid=cid-1 WHERE sig_class_id=$sig_class_id and day='$day' and cid>0";
	            //$delsql[] = "delete from ac_alertsclas_sid WHERE sig_class_id=$sig_class_id and day='$day' and sid=$sid";
	            //$delsql[] = "delete from ac_alertsclas_signature WHERE sig_class_id=$sig_class_id and day='$day' and signature=$signature";
	            //$delsql[] = "delete from ac_alertsclas_ipsrc WHERE sig_class_id=$sig_class_id and day='$day' and ip_src=$ip_src";
	            //$delsql[] = "delete from ac_alertsclas_ipdst WHERE sig_class_id=$sig_class_id and day='$day' and ip_dst=$ip_dst";
	            // AC_SRCADDR
	            $delsql[] = "update ignore ac_srcaddr_ipsrc set cid=cid-1 WHERE ip_src=$ip_src and day='$day' and cid>0";
	            $delsql[] = "delete from ac_srcaddr_sid WHERE ip_src=$ip_src and day='$day' and sid=$sid";
	            //$delsql[] = "delete from ac_srcaddr_signature WHERE ip_src=$ip_src and day='$day' and plugin_id=$plugin_id and plugin_sid=$plugin_sid";
	            $delsql[] = "delete from ac_srcaddr_ipdst WHERE ip_src=$ip_src and day='$day' and ip_dst=$ip_dst";
	            // AC_DSTADDR
	            $delsql[] = "update ignore ac_dstaddr_ipdst set cid=cid-1 WHERE ip_dst=$ip_dst and day='$day' and cid>0";
	            $delsql[] = "delete from ac_dstaddr_sid WHERE ip_dst=$ip_dst and day='$day' and sid=$sid";
	            //$delsql[] = "delete from ac_dstaddr_signature WHERE ip_dst=$ip_dst and day='$day' and plugin_id=$plugin_id and plugin_sid=$plugin_sid";
	            $delsql[] = "delete from ac_dstaddr_ipsrc WHERE ip_dst=$ip_dst and day='$day' and ip_src=$ip_src";
	            // AC_LAYER4_SRC
	            $delsql[] = "update ignore ac_layer4_sport set cid=cid-1 WHERE layer4_sport=$layer4_sport and ip_proto=$ip_proto and day='$day' and cid>0";
	            $delsql[] = "delete from ac_layer4_sport_sid WHERE layer4_sport=$layer4_sport and ip_proto=$ip_proto and day='$day' and sid=$sid";
	            $delsql[] = "delete from ac_layer4_sport_signature WHERE layer4_sport=$layer4_sport and ip_proto=$ip_proto and day='$day' and plugin_id=$plugin_id and plugin_sid=$plugin_sid";
	            $delsql[] = "delete from ac_layer4_sport_ipsrc WHERE layer4_sport=$layer4_sport and ip_proto=$ip_proto and day='$day' and ip_src=$ip_src";
	            $delsql[] = "delete from ac_layer4_sport_ipdst WHERE layer4_sport=$layer4_sport and ip_proto=$ip_proto and day='$day' and ip_dst=$ip_dst";
	            // AC_LAYER4_DST
	            $delsql[] = "update ignore ac_layer4_dport set cid=cid-1 WHERE layer4_dport=$layer4_dport and ip_proto=$ip_proto and day='$day' and cid>0";
	            $delsql[] = "delete from ac_layer4_dport_sid WHERE layer4_dport=$layer4_dport and ip_proto=$ip_proto and day='$day' and sid=$sid";
	            $delsql[] = "delete from ac_layer4_dport_signature WHERE layer4_dport=$layer4_dport and ip_proto=$ip_proto and day='$day' and plugin_id=$plugin_id and plugin_sid=$plugin_sid";
	            $delsql[] = "delete from ac_layer4_dport_ipsrc WHERE layer4_dport=$layer4_dport and ip_proto=$ip_proto and day='$day' and ip_src=$ip_src";
	            $delsql[] = "delete from ac_layer4_dport_ipdst WHERE layer4_dport=$layer4_dport and ip_proto=$ip_proto and day='$day' and ip_dst=$ip_dst";
	         	foreach ($delsql as $sql) {
	         		if (preg_match("/update ignore (.*?) set.*/i",$sql,$fnd)) {
				        echo "Update table ".$fnd[1]."...";
				        $conn->Execute($sql);
				        echo "Done\n";	
	         		}
	         		if (preg_match("/delete from (.*?) WHERE.*/i",$sql,$fnd)) {
				        echo "Delete event from ".$fnd[1]."...";
				        $conn->Execute($sql);
				        echo "Done\n";	
	         		}	         	
	         	}   
	        }
	     }
	}
}

// ****************************************** Console Purge Event Script **********************************************
ob_implicit_flush();
ini_set('include_path', '/usr/share/ossim/include');
require_once("ossim_db.inc");

$db = new ossim_db();
$conn = $db->snort_connect();

$acid_event_input = "";
if (!$rs = & $conn->Execute("SELECT table_name FROM INFORMATION_SCHEMA.tables WHERE table_name='acid_event_input'")) {
    print $conn->ErrorMsg();
    exit();
} else {
     if(!$rs->EOF) $acid_event_input = $rs->fields["table_name"];
}

$sid = $argv[1];
$cid = $argv[2];

if ($sid=="" || cid=="") {
	echo "Usage: php purge_event.php SID CID\n";
	exit;
}
PurgeAlert_ac($conn, $sid, $cid);
PurgeAlert($conn, $sid, $cid, $acid_event_input);
echo "\nEvent SID:$sid, CID=$cid successfully deleted.\n\n";
$db->close($conn);
?>
