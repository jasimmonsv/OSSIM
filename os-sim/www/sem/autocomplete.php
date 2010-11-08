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
function GetPlugins($db) {
    $plugins = array();
    $temp_sql = "select name from plugin";
    $tmp_result = $db->Execute($temp_sql);
    while (!$tmp_result->EOF) {
        $plugins[] = $tmp_result->fields["name"];
        $tmp_result->MoveNext();
    }
    $tmp_result->free();
    return $plugins;
}
$db = new ossim_db();
$conn = $db->connect();

$str = GET('str');

$qstr = quotemeta($str);

ossim_valid($str, OSS_DIGIT, OSS_SPACE, OSS_PUNC, "!", OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("str"));
if (ossim_error()) {
    die(ossim_error());
}

$data = array();
$top = 10;
if (trim($str) != "") {
	list($sensors, $hosts) = Host::get_ips_and_hostname($conn);
	$nets = Net::get_list($conn);
	$plugins = GetPlugins($conn);
	$sourcetypes = GetSourceTypes($conn);
	$plugingroups = Plugingroup::get_list($conn);
	
	// Typing a tag
	if (preg_match("/^(sensor|src|dst|plugin|plugingroup)(\!?\=)(.*)/i",$str,$found)) {
		$str = $found[3];
		$op = $found[2];
		if ($str == "") $str = ".";
		$qstr = $str;
		if ($found[1] == "sensor") {
			foreach ($sensors as $ip=>$name) {
				if ((preg_match("/^$qstr/i",$name) || preg_match("/^$qstr/i",$ip)) && count($data) < $top) {
					$data[] = array("name"=>"<b>sensor $op </b>$name");
				}
			}
		} elseif ($found[1] == "src" || $found[1] == "dst") {
			foreach ($hosts as $ip=>$name) {
				if ((preg_match("/^$qstr/i",$name) || preg_match("/^$qstr/i",$ip)) && count($data) < $top) {
					$data[] = array("name"=>"<b>".$found[1]." $op </b>$name");
				}
			}
			foreach ($nets as $net) {
				$ip = $net->get_ips();
				$name = $net->get_name();
				if ((preg_match("/^$qstr/i",$name) || preg_match("/^$qstr/i",$ip)) && count($data) < $top) {
					$data[] = array("name"=>"<b>".$found[1]." $op </b>$name");
				}
			}
		} elseif ($found[1] == "plugin") {
			foreach ($plugins as $plugin) {
				if ((preg_match("/^$qstr/i",$plugin)) && count($data) < $top) {
					$data[] = array("name"=>"<b>plugin $op </b>$plugin");
				}
			}
		} elseif ($found[1] == "plugingroup") {
			foreach ($plugingroups as $group) {
				$groupname = $group->get_name();
				if ((preg_match("/^$qstr/i",$groupname)) && count($data) < $top) {
					$data[] = array("name"=>"<b>plugin group $op </b>$groupname");
				}
			}
		}
	// Typing anything
	} else {
		foreach ($plugingroups as $group) {
			$groupname = $group->get_name();
			if ((preg_match("/^$qstr/i",$groupname)) && count($data) < $top) {
				$data[] = array("name"=>"<b>plugin group = </b>$groupname");
				$data[] = array("name"=>"<b>plugin group != </b>$groupname");
			}
		}
		foreach ($sourcetypes as $sourcetype) {
			if ((preg_match("/^$qstr/i",$sourcetype)) && count($data) < $top) {
				$data[] = array("name"=>"<b>source type = </b>$sourcetype");
				$data[] = array("name"=>"<b>source type != </b>$sourcetype");
			}
		}
		foreach ($plugins as $plugin) {
			if ((preg_match("/^$qstr/i",$plugin)) && count($data) < $top) {
				$data[] = array("name"=>"<b>plugin = </b>$plugin");
				$data[] = array("name"=>"<b>plugin != </b>$plugin");
			}
		}
		foreach ($sensors as $ip=>$name) {
			if ((preg_match("/^$qstr/i",$name) || preg_match("/^$qstr/i",$ip)) && count($data) < $top) {
				$data[] = array("name"=>"<b>sensor = </b>$name");
				$data[] = array("name"=>"<b>sensor != </b>$name");
			}
		}
		foreach ($hosts as $ip=>$name) {
			if ((preg_match("/^$qstr/i",$name) || preg_match("/^$qstr/i",$ip)) && count($data) < $top) {
				$data[] = array("name"=>"<b>src = </b>$name");
				$data[] = array("name"=>"<b>src != </b>$name");
				$data[] = array("name"=>"<b>dst = </b>$name");
				$data[] = array("name"=>"<b>dst != </b>$name");
			}
		}
		foreach ($nets as $net) {
			$ip = $net->get_ips();
			$name = $net->get_name();
			if ((preg_match("/^$qstr/i",$name) || preg_match("/^$qstr/i",$ip)) && count($data) < $top) {
				$data[] = array("name"=>"<b>src = </b>$name");
				$data[] = array("name"=>"<b>src != </b>$name");
				$data[] = array("name"=>"<b>dst = </b>$name");
				$data[] = array("name"=>"<b>dst != </b>$name");
			}
		}
	}
}
if (count($data) < 1) {
	$data[] = array("name"=>"<b>data = </b>$str");
	$data[] = array("name"=>"<b>data != </b>$str");
	$data[] = array("name"=>"<b>sensor = </b>$str");
	$data[] = array("name"=>"<b>sensor != </b>$str");
	$data[] = array("name"=>"<b>src = </b>$str");
	$data[] = array("name"=>"<b>src != </b>$str");
	$data[] = array("name"=>"<b>dst = </b>$str");
	$data[] = array("name"=>"<b>dst != </b>$str");
}
//echo JSON to page  
$response = "(" . json_encode($data) . ")";  
echo $response;  
?>