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
/**
* Class and Function List:
* Function list:
* Classes list:
*/
require_once ('classes/Security.inc');
require_once ('classes/Host.inc');
require_once ('classes/Net.inc');
require_once ('classes/Port.inc');
require_once ('classes/Plugingroup.inc');
require_once ('ossim_db.inc');
function GetSourceTypes($db) {
    $srctypes = array();
    $temp_sql = "select distinct source_type from ossim.plugin where source_type is not null order by source_type";
    $tmp_result = $db->Execute($temp_sql);
    while (!$tmp_result->EOF) {
        $myrow = $tmp_result->fields;
        $srctypes[] = $myrow["source_type"];
        $tmp_result->MoveNext();
    }
    $tmp_result->free();
    return $srctypes;
}
function GetPluginCategories($db,$forced_sql="") {
    $categories = array();
    if ($forced_sql!="") 
        $temp_sql = "select distinct category.* from plugin, plugin_sid LEFT JOIN category on category.id=plugin_sid.category_id where category.id is not null AND plugin.id=plugin_sid.plugin_id $forced_sql order by name";
    else
        $temp_sql = "select * from ossim.category order by name";
    $tmp_result = $db->Execute($temp_sql);
    while (!$tmp_result->EOF) {
        $myrow = $tmp_result->fields;
        $categories[$myrow["id"]] = str_replace("_"," ",$myrow["name"]);
        $tmp_result->MoveNext();
    }
    $tmp_result->free();
    return $categories;
}
function GetPluginSubCategories($db,$categories,$forced_sql="") {
    $subcategories = array();
    foreach ($categories as $idcat => $namecat) {
		if ($forced_sql!="") 
		    $temp_sql = "select distinct subcategory.* from plugin, plugin_sid LEFT JOIN subcategory on subcategory.id=plugin_sid.subcategory_id and subcategory.cat_id=$idcat where subcategory.id is not null AND plugin.id=plugin_sid.plugin_id $forced_sql order by name";
		else
		    $temp_sql = "select * from ossim.subcategory where cat_id=$idcat order by name";
	    $tmp_result = $db->Execute($temp_sql);
	    while (!$tmp_result->EOF) {
            $myrow = $tmp_result->fields;
	        $subcategories[$idcat][$myrow["id"]] = str_replace("_"," ",$myrow["name"]);
            $tmp_result->MoveNext();
	    }
	    $tmp_result->free(); 
	}
    return $subcategories;
}
function GetPlugins($db) {
    $plugins = array();
    $temp_sql = "select name,id from plugin";
    $tmp_result = $db->Execute($temp_sql);
    while (!$tmp_result->EOF) {
        $plugins[$tmp_result->fields["id"]] = $tmp_result->fields["name"];
        $tmp_result->MoveNext();
    }
    $tmp_result->free();
    return $plugins;
}
$db = new ossim_db();
$conn = $db->connect();

$str = GET('str');
$qstr = quotemeta($str);

$aux_arr = explode(" ",$_SESSION['forensic_query']);
$current_query = array();
foreach ($aux_arr as $atom) {
	if (preg_match("/\=/",$atom)) {
		$current_query[str_replace("'","",$atom)]++;
	}
}

ossim_valid($str, OSS_DIGIT, OSS_SPACE, OSS_PUNC, "!", "|", OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("str"));
if (ossim_error()) {
    die(ossim_error());
}

$prev = "";

$fnd = array();

if(preg_match("/(.*=)(.*\|)(.*)/", $str, $fnd)) {
    $prev = $fnd[2];
    $str = $fnd[1].$fnd[3];
} 

$data = array();
$top = 10;
$tag_typing = 0;
if (trim($str) != "") {
	list($sensors, $hosts) = Host::get_ips_and_hostname($conn);
	$nets = Net::get_list($conn);
	$plugins = GetPlugins($conn);
	$sourcetypes = GetSourceTypes($conn);
	$plugingroups = Plugingroup::get_list($conn);
	$ports = Port::get_list($conn);
	$categories = GetPluginCategories($conn);
	$subcategories = GetPluginSubCategories($conn,$categories);
	
	// Typing a tag
	if (preg_match("/^(sensor|src|dst|plugin|datasource|plugingroup|dsgroup|src_port|dst_port|product_type|event_category|category|data)(\!?\=)(.*)/i",$str,$found)) {
		$tag_typing = 1;
		$str = $found[3];
		$op = $found[2];
		if ($str == "") $str = ".";
		$qstr = $str;
		if ($found[1] == "sensor") {
			foreach ($sensors as $ip=>$name) {
				if ((preg_match("/^$qstr/i",$name) || preg_match("/^$qstr/i",$ip)) && !preg_match("/$name/i",$fnd[2]) && count($data) < $top && $current_query["sensor$op$ip"] == "") {
					$data[] = array("name"=>"<b>sensor</b>$op$prev$name");
				}
			}
		} elseif ($found[1] == "src" || $found[1] == "dst") {
			foreach ($hosts as $ip=>$name) {
				if ((preg_match("/^$qstr/i",$name) || preg_match("/^$qstr/i",$ip)) && !preg_match("/$name/i",$fnd[2]) && count($data) < $top && $current_query[$found[1].$op.$ip] == "") {
					$data[] = array("name"=>"<b>".$found[1]."</b>$op$prev$name");
				}
			}
			foreach ($nets as $net) {
				$ip = $net->get_ips();
				$name = $net->get_name();
				if ((preg_match("/^$qstr/i",$name) || preg_match("/^$qstr/i",$ip)) && !preg_match("/$name/i",$fnd[2]) && count($data) < $top && $current_query[$found[1].$op.$ip] == "") {
					$data[] = array("name"=>"<b>".$found[1]."</b>$op$prev$name");
				}
			}
		} elseif ($found[1] == "plugin" || $found[1] == "datasource") {
			foreach ($plugins as $plugin_id=>$plugin) {
				if ((preg_match("/^$qstr/i",$plugin)) && !preg_match("/$plugin/i",$fnd[2]) && count($data) < $top && $current_query["plugin_id".$op.$plugin_id] == "") {
					$data[] = array("name"=>"<b>".$found[1]."</b>$op$prev$plugin");
				}
			}
		} elseif ($found[1] == "plugingroup" || $found[1] == "dsgroup") {
			foreach ($plugingroups as $group) {
				$groupname = $group->get_name();
				if ((preg_match("/^$qstr/i",$groupname)) && !preg_match("/$groupname/i",$fnd[2]) && count($data) < $top) {
					$data[] = array("name"=>"<b>".$found[1]."</b>$op$prev$groupname");
				}
			}
		} elseif ($found[1] == "product_type") {
			foreach ($sourcetypes as $sourcetype) {
				$qsourcetype = str_replace("/","\\/",$sourcetype);
				if ((preg_match("/^$qstr/i",$sourcetype)) && !preg_match("/$qsourcetype/i",$fnd[2]) && count($data) < $top) {
					$data[] = array("name"=>"<b>product_type</b>$op$prev$sourcetype");
				}
			}
		} elseif ($found[1] == "category" || $found[1] == "event_category") {
			foreach ($categories as $category_id=>$category) {
				if ((preg_match("/^$qstr/i",$category)) && !preg_match("/$category/i",$fnd[2]) && count($data) < $top) {
					$data[] = array("name"=>"<b>".$found[1]."</b>$op$prev$category");
					foreach ($subcategories[$category_id] as $subcategory_id=>$subcategory) {
						$data[] = array("name"=>"<b>".$found[1]."</b>$op$prev$category-$subcategory");
					}
				}
			}
		} elseif ($found[1] == "src_port" || $found[1] == "dst_port") {
			$lastnumber = -1;
			foreach ($ports as $port) {
				$portnumber = $port->get_port_number();
				if ($portnumber == $lastnumber) { continue; }
				if ((preg_match("/^$qstr/i",$portnumber)) && !preg_match("/$portnumber/i",$fnd[2]) && count($data) < $top && $current_query[$found[1].$op.$portnumber] == "") {
					$data[] = array("name"=>"<b>".$found[1]."</b>$op$prev$portnumber");
					$lastnumber = $portnumber;
				}
			}
		} elseif ($found[1] == "data") {
			if (count($data) < $top/* && !preg_match("/$qstr/i",$fnd[2])*/) {
				$data[] = array("name"=>"<b>data</b>$op$prev$found[3]");
			}
		}
	// Typing anything
	} else {
		foreach ($plugingroups as $group) {
			$groupname = $group->get_name();
			if ((preg_match("/^$qstr/i",$groupname)) && count($data) < $top) {
				$data[] = array("name"=>"<b>dsgroup</b>=$groupname");
				$data[] = array("name"=>"<b>dsgroup</b>!=$groupname");
			}
		}
		foreach ($sourcetypes as $sourcetype) {
			if ((preg_match("/^$qstr/i",$sourcetype)) && count($data) < $top) {
				$data[] = array("name"=>"<b>product_type</b>=$sourcetype");
				$data[] = array("name"=>"<b>product_type</b>!=$sourcetype");
			}
		}
		foreach ($plugins as $plugin_id=>$plugin) {
			if ((preg_match("/^$qstr/i",$plugin)) && count($data) < $top && $current_query["plugin_id=$plugin_id"] == "" && $current_query["plugin_id!=$plugin_id"] == "") {
				$data[] = array("name"=>"<b>datasource</b>=$plugin");
				$data[] = array("name"=>"<b>datasource</b>!=$plugin");
			}
		}
		foreach ($sensors as $ip=>$name) {
			if ((preg_match("/^$qstr/i",$name) || preg_match("/^$qstr/i",$ip)) && count($data) < $top && $current_query["sensor=$ip"] == "" && $current_query["sensor!=$ip"] == "") {
				$data[] = array("name"=>"<b>sensor</b>=$name");
				$data[] = array("name"=>"<b>sensor</b>!=$name");
			}
		}
		foreach ($hosts as $ip=>$name) {
			if ((preg_match("/^$qstr/i",$name) || preg_match("/^$qstr/i",$ip)) && count($data) < $top && $current_query["src_ip=$ip"] == "" && $current_query["src_ip!=$ip"] == "" && $current_query["dst_ip=$ip"] == "" && $current_query["dst_ip!=$ip"] == "") {
				$data[] = array("name"=>"<b>src</b>=$name");
				$data[] = array("name"=>"<b>src</b>!=$name");
				$data[] = array("name"=>"<b>dst</b>=$name");
				$data[] = array("name"=>"<b>dst</b>!=$name");
			}
		}
		foreach ($nets as $net) {
			$ip = $net->get_ips();
			$name = $net->get_name();
			if ((preg_match("/^$qstr/i",$name) || preg_match("/^$qstr/i",$ip)) && count($data) < $top && $current_query["src_net=$ip"] == "" && $current_query["src_net!=$ip"] == "" && $current_query["dst_net=$ip"] == "" && $current_query["dst_net!=$ip"] == "") {
				$data[] = array("name"=>"<b>src</b>=$name");
				$data[] = array("name"=>"<b>src</b>!=$name");
				$data[] = array("name"=>"<b>dst</b>=$name");
				$data[] = array("name"=>"<b>dst</b>!=$name");
			}
		}
		$lastnumber = -1;
		foreach ($ports as $port) {
			$portnumber = $port->get_port_number();
			if ($portnumber == $lastnumber) { continue; }
			if ((preg_match("/^$qstr/i",$portnumber)) && count($data) < $top && $current_query["src_port=$portnumber"] == "" && $current_query["src_port!=$portnumber"] == "" && $current_query["dst_port=$portnumber"] == "" && $current_query["dst_port!=$portnumber"] == "") {
				$data[] = array("name"=>"<b>src_port</b>=$portnumber");
				$data[] = array("name"=>"<b>src_port</b>!=$portnumber");
				$data[] = array("name"=>"<b>dst_port</b>=$portnumber");
				$data[] = array("name"=>"<b>dst_port</b>!=$portnumber");
				$lastnumber = $portnumber;
			}
		}
	}
}
if (count($data) < 1 && !$tag_typing) {
	$data[] = array("name"=>"<b>data</b>=$str");
	$data[] = array("name"=>"<b>data</b>!=$str");
	$data[] = array("name"=>"<b>sensor</b>=$str");
	$data[] = array("name"=>"<b>sensor</b>!=$str");
	$data[] = array("name"=>"<b>src</b>=$str");
	$data[] = array("name"=>"<b>src</b>!=$str");
	$data[] = array("name"=>"<b>dst</b>=$str");
	$data[] = array("name"=>"<b>dst</b>!=$str");
}
//echo JSON to page  
$response = "(" . json_encode($data) . ")";  
echo $response;  
?>