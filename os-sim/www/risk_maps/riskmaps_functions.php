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
*/
function indicatorAllowed($conn,$type,$type_name,$hosts,$sensors,$nets) {
	$has_perm = 0;
	if ($type == "host") {
		foreach ($hosts as $hip=>$hname) if ($hname == $type_name) $has_perm = 1;
	} elseif ($type == "sensor" || $type == "server") {
		foreach ($sensors as $sip=>$sname) if ($sname == $type_name) $has_perm = 1;
	} elseif ($type == "net") {
		foreach ($nets as $net) if ($net->get_name() == $type_name) $has_perm = 1;
	} elseif ($type == "host_group") {
		if (Session::groupHostAllowed($conn,$type_name)) $has_perm = 1;
	} else $has_perm = 1;
	
	return $has_perm;
}
function get_value_by_digit($digit) {
	if (intval($digit) > 7) {
        return 'r';
    } elseif(intval($digit) > 3) {
        return 'a';
    } elseif($digit < 0) {
    	return 'b';
    } elseif($digit != "" || $digit > -1) {
    	return 'v';
    } else {
    	return 'b';
    }
}
function is_in_assets($conn,$name,$type) {
	if ($type == "host") {
		$sql = "SELECT * FROM host WHERE hostname=\"$name\"";
	} elseif ($type == "sensor") {
		$sql = "SELECT * FROM sensor WHERE name=\"$name\"";
	} elseif ($type == "net") {
		$sql = "SELECT * FROM net WHERE name=\"$name\"";
	} elseif ($type == "host_group") {
		$sql = "SELECT * FROM host_group WHERE name=\"$name\"";
	}
	$result = $conn->Execute($sql);
	return (!$result->EOF) ? 1 : 0;
}
function get_values($conn,$host_types,$type,$name,$ips,$only_values = false) {
	if ($only_values) {
		$RiskValue = -1;
    	$VulnValue = -1;
    	$AvailValue = -1;
	} else {
		$RiskValue = 'b';
    	$VulnValue = 'b';
    	$AvailValue = 'b';
	}
	$params = ($type == "host_group") ? array() : array($name);
    if (in_array($type, $host_types)) {
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
    	if ($only_values) {
        	$RiskValue = ($rs2->fields["severity"] == "") ? -1 : intval($rs2->fields["severity"]);
        } else {
        	$RiskValue = get_value_by_digit($rs2->fields["severity"]);
        }
    }
    if (in_array($type, $host_types)) {
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
        if ($only_values) {
        	$VulnValue = ($rs2->fields["severity"] == "") ? -1 : intval($rs2->fields["severity"]);
        } else {
	        $VulnValue = get_value_by_digit($rs2->fields["severity"]);
        }
    }

    if (in_array($type, $host_types)) {
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
        if ($only_values) {
        	$AvailValue = ($rs2->fields["severity"] == "") ? -1 : intval($rs2->fields["severity"]);
        } else {
	        $AvailValue = get_value_by_digit($rs2->fields["severity"]);
        }
    }
    return array($RiskValue,$VulnValue,$AvailValue,$v_ip,$a_ip);
}
function print_indicator_content($conn,$rs) {
    $name = $rs->fields["type_name"];
    $type = $rs->fields["type"];
    $what = "name"; $ips = $name;
    $host_types = array("host", "server", "sensor");
    $in_assets = is_in_assets($conn,$rs->fields['type_name'],$rs->fields['type']);
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
    
    // Linked to another map: loop by this map indicators
    if (preg_match("/view\.php\?map\=(\d+)/",$rs->fields['url'],$found)) {
    	$query = "select * from risk_indicators where name <> 'rect' AND map= ?";
		$params = array($found[1]);
		if (!$rs4 = &$conn->Execute($query, $params)) {
			print $conn->ErrorMsg();
		} else {
			$RiskValue_max = -1;
			$VulnValue_max = -1;
			$AvailValue_max = -1;
			while (!$rs4->EOF) {
				list ($RiskValue_aux,$VulnValue_aux,$AvailValue_aux,$v_ip_aux,$a_ip_aux) = get_values($conn,$host_types,$rs4->fields["type"],$rs4->fields["type_name"],$ips,true);
				if ($RiskValue_aux > $RiskValue_max) { $RiskValue_max = $RiskValue_aux; }
				if ($VulnValue_aux > $VulnValue_max) { $VulnValue_max = $VulnValue_aux; }
				if ($AvailValue_aux > $AvailValue_max) { $AvailValue_max = $AvailValue_aux; }
				$rs4->MoveNext();
			}
			$RiskValue = get_value_by_digit($RiskValue_max);
			$VulnValue = get_value_by_digit($VulnValue_max);
			$AvailValue = get_value_by_digit($AvailValue_max);
		}
    // Asset Values
    } else {
    	list ($RiskValue,$VulnValue,$AvailValue,$v_ip,$a_ip) = get_values($conn,$host_types,$type,$name,$ips);
    }
    
    $gtype = ($type=="net") ? "net" : "host";
    $ips = ($type=="net") ? $ips : $rs->fields["type_name"];
    $r_url = "../control_panel/show_image.php?ip=".urlencode(($type == "host_group") ? $r_ip : $name)."&range=year&what=compromise&start=N-1Y&end=N&type=$gtype&zoom=1&hmenu=Risk&smenu=Metrics";
    $v_url = "../vulnmeter/index.php?value=".urlencode(($type == "host_group") ? $v_ip : $ips)."&type=hn&hmenu=Vulnerabilities&smenu=Vulnerabilities";
    $a_url = "../nagios/index.php?sensor=".urlencode($sensor)."&hmenu=Availability&smenu=Availability&nagios_link=".urlencode("/cgi-bin/status.cgi?host=all");

    $size = ($rs->fields["size"] > 0) ? $rs->fields["size"] : '100%';
	$icon = $rs->fields["icon"];
	if (preg_match("/\#/",$icon)) {
		$aux = explode("#",$icon);
		$icon = $aux[0]; $bgcolor = $aux[1];
	} else {
		$bgcolor = "transparent";
	}
	$url = ($rs->fields["url"] == "REPORT") ? "../report/index.php?host=".$ips : "javascript:;";
	if (!$in_assets) {
		$icon = "../pixmaps/marker--exclamation.png";
		$size = "16";
	}
	?><table width="100%" border=0 cellspacing=0 cellpadding=1 style="background-color:<?php echo $bgcolor ?>"><tr><td colspan=2 align=center><a href="<?php echo $url ?>" class="ne"><i><?php echo $rs->fields["name"] ?></i></a></td></tr><tr><td><img src="<?php echo $icon ?>" width="<?php echo $size ?>" border=0></td><td><table border=0 cellspacing=0 cellpadding=1><tr><td><a class="ne11" target="main" href="<?php echo $r_url ?>">R</a></td><td><a class="ne11" target="main" href="<?php echo $v_url ?>">V</a></td><td><a class="ne11" target="main" href="<?php echo $a_url ?>">A</a></td></tr><tr><td><img src="images/<?php echo $RiskValue ?>.gif" border=0></td><td><img src="images/<?php echo $VulnValue ?>.gif" border=0></td><td><img src="images/<?php echo $AvailValue ?>.gif" border=0></td></tr></table></td></tr></table><?php
}
function print_indicators($map, $print_inputs = false) {
	require_once 'classes/Host.inc';
	require_once 'classes/Net.inc';
	require_once 'ossim_db.inc';
	$db = new ossim_db();
	$conn = $db->connect();
	list($sensors, $hosts) = Host::get_ips_and_hostname($conn,true);
	$nets = Net::get_list($conn);
	$query = "select * from risk_indicators where name <> 'rect' AND map= ?";
	$params = array($map);
	if (!$rs = &$conn->Execute($query, $params)) {
		print $conn->ErrorMsg();
	} else {
		while (!$rs->EOF) {
			$has_perm = indicatorAllowed($conn,$rs->fields['type'],$rs->fields['type_name'],$hosts,$sensors,$nets);
			if (Session::am_i_admin()) $has_perm = 1;
			if ($print_inputs) {
				echo "<input type=\"hidden\" name=\"dataname".$rs->fields["id"]."\" id=\"dataname".$rs->fields["id"]."\" value=\"".$rs->fields["name"]."\">\n";
				echo "<input type=\"hidden\" name=\"datatype".$rs->fields["id"]."\" id=\"datatype".$rs->fields["id"]."\" value=\"".$rs->fields["type"]."\">\n";
				echo "<input type=\"hidden\" name=\"type_name".$rs->fields["id"]."\" id=\"type_name".$rs->fields["id"]."\" value=\"".$rs->fields["type_name"]."\">\n";
				echo "<input type=\"hidden\" name=\"datanurl".$rs->fields["id"]."\" id=\"dataurl".$rs->fields["id"]."\" value=\"".$rs->fields["url"]."\">\n";
				echo "<input type=\"hidden\" name=\"dataicon".$rs->fields["id"]."\" id=\"dataicon".$rs->fields["id"]."\" value=\"".preg_replace("/\#.*/","",$rs->fields["icon"])."\">\n";
				echo "<input type=\"hidden\" name=\"dataiconsize".$rs->fields["id"]."\" id=\"dataiconsize".$rs->fields["id"]."\" value=\"".$rs->fields["size"]."\">\n";
				echo "<input type=\"hidden\" name=\"dataiconbg".$rs->fields["id"]."\" id=\"dataiconbg".$rs->fields["id"]."\" value=\"".((preg_match("/\#(.+)/",$rs->fields["icon"],$found)) ? $found[1] : "")."\">\n";
			}
			?><div id="alarma<?php echo $rs->fields["id"] ?>" class="itcanbemoved" style="border:1px solid transparent;cursor:pointer;background:url(../pixmaps/1x1.png);visibility:hidden;position:absolute;left:<?php echo $rs->fields["x"] ?>px;top:<?php echo $rs->fields["y"] ?>px;height:<?php echo $rs->fields["h"] ?>px;width:<?php echo $rs->fields["w"] ?>px"><?php print_indicator_content($conn,$rs) ?></div><?php
			$rs->MoveNext();
		}
	}
	$query = "select * from risk_indicators where name='rect' AND map = ?";
	$params = array($map);

	if (!$rs = &$conn->Execute($query, $params)) {            
	print $conn->ErrorMsg();
	} else {
		while (!$rs->EOF) {
			$has_perm = 0;
			$in_assets = is_in_assets($conn,$rs->fields['type_name'],$rs->fields['type']);
			if ($rs->fields['type'] == "host") {
				foreach ($hosts as $hip=>$hname) if ($hname == $rs->fields['type_name']) $has_perm = 1;
			} elseif ($rs->fields['type'] == "sensor" || $rs->fields['type'] == "server") {
				foreach ($sensors as $sip=>$sname) if ($sname == $rs->fields['type_name']) $has_perm = 1;
			} elseif ($rs->fields['type'] == "net") {
				foreach ($nets as $net) if ($net->get_name() == $rs->fields['type_name']) $has_perm = 1;
			} elseif ($rs->fields['type'] == "host_group") {
				if (Session::groupHostAllowed($conn,$rs->fields['type_name'])) $has_perm = 1;
			} else $has_perm = 1;
			if (Session::am_i_admin()) $has_perm = 1;
			if (!$in_assets) {
				echo "<input type=\"hidden\" name=\"dataname".$rs->fields["id"]."\" id=\"dataname".$rs->fields["id"]."\" value=\"".$rs->fields["name"]."\">\n";
				echo "<input type=\"hidden\" name=\"datanurl".$rs->fields["id"]."\" id=\"dataurl".$rs->fields["id"]."\" value=\"".$rs->fields["url"]."\">\n";
				echo "<div id=\"rect".$rs->fields["id"]."\" class=\"itcanbemoved\" style=\"position:absolute;background:url(../pixmaps/1x1.png);visibility:visible;left:".$rs->fields["x"]."px;top:".$rs->fields["y"]."px;height:".$rs->fields["h"]."px;width:".$rs->fields["w"]."px\">";
                echo "<div style='position:absolute;bottom:0px;right:0px'><img src='../pixmaps/resize.gif' border=0></div>";
				echo "<table border=0 cellspacing=0 cellpadding=0 width=\"100%\" height=\"100%\"><tr><td style=\"border:1px dotted black\">&nbsp;</td></tr></table>";
				echo "</div>\n";
			} elseif ($has_perm) {
				echo "<input type=\"hidden\" name=\"dataname".$rs->fields["id"]."\" id=\"dataname".$rs->fields["id"]."\" value=\"".$rs->fields["name"]."\">\n";
				echo "<input type=\"hidden\" name=\"datanurl".$rs->fields["id"]."\" id=\"dataurl".$rs->fields["id"]."\" value=\"".$rs->fields["url"]."\">\n";
				echo "<div id=\"rect".$rs->fields["id"]."\" class=\"itcanbemoved\" style=\"position:absolute;background:url(../pixmaps/1x1.png);visibility:visible;left:".$rs->fields["x"]."px;top:".$rs->fields["y"]."px;height:".$rs->fields["h"]."px;width:".$rs->fields["w"]."px\">";
	            echo "<div style='position:absolute;bottom:0px;right:0px'><img src='../pixmaps/resize.gif' border=0></div>";
				echo "<table border=0 cellspacing=0 cellpadding=0 width=\"100%\" height=\"100%\"><tr><td style=\"border:1px dotted black\">&nbsp;</td></tr></table>";
				echo "</div>\n";
			}
			$rs->MoveNext();
		}
	}
}
?>