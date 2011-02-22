<?
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
* - check_writable_relative()
* Classes list:
*/
require_once 'classes/Session.inc';
Session::logcheck("MenuControlPanel", "BusinessProcesses");

require_once 'ossim_db.inc';
require_once 'classes/Security.inc';

$map = $_GET["map"];

ossim_valid($map, OSS_DIGIT, OSS_ALPHA, ".",'illegal:'._("map"));

if (ossim_error()) {
die(ossim_error());
}

$db = new ossim_db();
$conn = $db->connect();
$params = array($map);
$query = "select * from risk_indicators where name <> 'rect' AND map= ? ";

if (!$rs = &$conn->Execute($query, $params)) {
    print $conn->ErrorMsg();
} else {
    while (!$rs->EOF){
        $name = $rs->fields["type_name"];
        $type = $rs->fields["type"];
        $host_types = array("host", "server", "sensor");
        // r --> bad
        // a --> medium
        // v --> good
        $RiskValue = 'b';
        $VulnValue = 'b';
        $AvailValue = 'b';

        $what = "name"; $ips = $name;

        $in_assets = 1;
        
        if(in_array($type, $host_types)){
            if($type == "host") $what = "hostname";                
            $query = "select ip from $type where $what = ?";
            $params = array($name);
            if ($rs3 = &$conn->Execute($query, $params)) {
                $name = $rs3->fields["ip"];
                if ($rs3->EOF) $in_assets = 0;
            }
            // related sensor
            $sensor = $name;
            if ($type == "host") {
                require_once 'classes/Host.inc';
                $sensors = Host::get_related_sensors($conn,$name);
                $sensor = ($sensors[0]!="") ? $sensors[0] : $name;
            }
        } elseif ($type == "net") {
            $query = "select ips from net where name = ?";
            $params = array($name);
            if ($rs3 = &$conn->Execute($query, $params)) {
                $ips = $rs3->fields["ips"];
                if ($rs3->EOF) $in_assets = 0;
            }
            // related sensor
            require_once 'classes/Net.inc';
            $sensors = Net::get_related_sensors($conn,$name);
            $sensor = ($sensors[0]!="") ? $sensors[0] : $name;
        } elseif ($type == "host_group") {
            $query = "select host_ip from host_group_reference where host_group_name = ?";
            $params = array($name);
            if ($rs3 = &$conn->Execute($query, $params)) {
                $iphg = array();
                while (!$rs3->EOF) {
                    $iphg[] = "'".$rs3->fields["host_ip"]."'";
                    $rs3->MoveNext();
                }
                $ips = (count($iphg) > 0) ? implode(",",$iphg) : "'0.0.0.0'";
                if (count($iphg) == 0) $in_assets = 0;
            }
            // related sensor{
            require_once 'classes/Host_group.inc';
            $sensors = Host_group::get_related_sensors($conn,$name);
            $sensor = ($sensors[0]!="") ? $sensors[0] : $name;
        }
        $params = ($type == "host_group") ? array() : array($name);

        if(in_array($type, $host_types)){
            $query = "select severity,member from bp_member_status where member = ? and measure_type = \"host_metric\"";
        } elseif ($type == "host_group") {
            $query = "select severity,member from bp_member_status where member in ($ips) and measure_type = \"host_metric\" order by severity desc limit 1";
        } else {
            $query = "select severity,member from bp_member_status where member = ? and measure_type = \"net_metric\"";
        }

        if (!$rs2 = &$conn->Execute($query, $params)) {
            print $conn->ErrorMsg();
        } else {
            $r_ip = $rs2->fields["member"];
            if(intval($rs2->fields["severity"]) > 7){
                $RiskValue = 'r';
            } elseif(intval($rs2->fields["severity"]) > 3){
                $RiskValue = 'a';
            }
        }

        if(in_array($type, $host_types)){
            $query = "select severity,member from bp_member_status where member = ? and measure_type = \"host_vulnerability\"";
        } elseif ($type == "host_group") {
            $query = "select severity,member from bp_member_status where member in ($ips) and measure_type = \"host_vulnerability\" order by severity desc limit 1";
        } else {
            $query = "select severity,member from bp_member_status where member = ? and measure_type = \"net_vulnerability\"";
        }
        if (!$rs2 = &$conn->Execute($query, $params)) {
            print $conn->ErrorMsg();
        } else {
            $v_ip = $rs2->fields["member"];
            if(intval($rs2->fields["severity"]) > 7){
                $VulnValue = 'r';
            } elseif(intval($rs2->fields["severity"]) > 3){
                $VulnValue = 'a';
            }
        }

        if(in_array($type, $host_types)) {
            $query = "select severity,member from bp_member_status where member = ? and measure_type = \"host_availability\"";
        } elseif ($type == "host_group") {
            $query = "select severity,member from bp_member_status where member in ($ips) and measure_type = \"host_availability\" order by severity desc limit 1";
        } else {
            $query = "select severity,member from bp_member_status where member = ? and measure_type = \"net_availability\"";
        }
        if (!$rs2 = &$conn->Execute($query, $params)) {
            print $conn->ErrorMsg();
        } else {
            $a_ip = $rs2->fields["member"];
            if(intval($rs2->fields["severity"]) > 7){
                $AvailValue = 'r';
            } elseif(intval($rs2->fields["severity"]) > 3){
                $AvailValue = 'a';
            }
        }

        $new_value = "txt".$RiskValue.$VulnValue.$AvailValue;
        
        $gtype = ($type=="net") ? "net" : "host";
        $ips = ($type=="net") ? $ips : $name;
        $r_url = "../control_panel/show_image.php?ip=".urlencode(($type == "host_group") ? $r_ip : $name)."&range=year&what=compromise&start=N-1Y&end=N&type=$gtype&zoom=1&hmenu=Risk&smenu=Metrics";
        $v_url = "../vulnmeter/index.php?value=".urlencode(($type == "host_group") ? $v_ip : $ips)."&type=hn&hmenu=Vulnerabilities&smenu=Vulnerabilities";
        //$a_url = "../nagios/index.php?sensor=".urlencode($sensor)."&hmenu=Availability&smenu=Availability&detail=".urlencode(($type == "host_group") ? $a_ip : $ips)."";
        $a_url = "../nagios/index.php?sensor=".urlencode($sensor)."&hmenu=Availability&smenu=Availability&nagios_link=".urlencode("/cgi-bin/status.cgi?host=all");
        
        $change_div = "changeDiv('".$rs->fields["id"]."','".$rs->fields["name"]."','".$rs->fields["url"]."','".$rs->fields["icon"]."',$new_value,'$r_url','$v_url','$a_url','$ips',".$rs->fields["size"].");\n";
		if ($in_assets) echo $change_div;
        $rs->MoveNext();
        }
}
$conn->close();
?>
