<?php
/*****************************************************************************
*
*   Copyright (c) 2007-2010 AlienVault
*   All rights reserved.
*
****************************************************************************/
// This script filters the standard input (a Logger event line) by the expression passed as parameter

function GetPluginsBySourceType($sourcetype,$db) {
	$ids = array();
    $temp_sql = "select id from plugin where source_type = ?";
    $tmp_result = $db->Execute($temp_sql,array($sourcetype));
    while (!$tmp_result->EOF) {
        $ids[] = $tmp_result->fields["id"];
        $tmp_result->MoveNext();
    }
    $tmp_result->free();
    return $ids;
}
ini_set("include_path", ".:/usr/share/ossim/include");

// Query
$a = $argv[1];
$plugin_filters = array();
$onlyid = false;
if (preg_match("/taxonomy='(.*)-(.*)-(.*)'/",$a,$found)) {
	require_once 'ossim_db.inc';
	$db = new ossim_db();
	$conn = $db->connect();
	if ($found[1] != "") {
		$plugin_ids = GetPluginsBySourceType(str_replace("_"," ",$found[1]),$conn);
		$plugin_query = "plugin_id in (".implode(",",$plugin_ids).") AND";
	}
	if ($found[2]!="" && $found[2]!='0') {
		$category_id = $found[2]; 
		if ($found[3] != "" && $found[3] !='0') {
			$subcategory_id = $found[3];
			$sql = "select distinct plugin_id,sid from plugin_sid WHERE $plugin_query category_id=$category_id AND subcategory_id=$subcategory_id";
	    } else {
			$sql = "select distinct plugin_id,sid from plugin_sid WHERE $plugin_query category_id=$category_id";
	    }
	    $tmp_result = $conn->Execute($sql);
	    while (!$tmp_result->EOF) {
	        $myrow = $tmp_result->fields;
	        $plugin_filters[$myrow['plugin_id']][$myrow['sid']]++;
	        $tmp_result->MoveNext();
	    }
	    $tmp_result->free();
	} elseif (count($plugin_ids) > 0) {
		$onlyid = true;
		foreach ($plugin_ids as $plugin_id) {
			$plugin_filters[$plugin_id]++;
		}
	}
	$conn->disconnect();
}
//print_r($plugin_filters);
// Line to filter
$in = fopen("php://stdin", "r");
while($line = trim(fgets($in))) {
	if ($a == "") { echo $line; }
	
	// Special attributes matches (plugin_id, plugin_sid for taxonomy filters)
	if (preg_match("/entry id='([^']+)'\s+fdate='([^']+)'\s+date='([^']+)'\s+plugin_id='([^']+)'\s+sensor='([^']+)'\s+src_ip='([^']+)'\s+dst_ip='([^']+)'\s+src_port='([^']+)'\s+dst_port='([^']+)'\s+tzone='[^']+'+\s+data='([^']+)'(\s+sig='[^']*')?\s+plugin_sid='([^']+)'/", $line, $matches)) {
		$plugin_id = $matches[4];
		$plugin_sid = $matches[12];
	} elseif (preg_match("/entry id='([^']+)'\s+fdate='([^']+)'\s+date='([^']+)'\s+plugin_id='([^']+)'\s+plugin_sid='([^']+)'\s+sensor='([^']+)'\s+src_ip='([^']+)'\s+dst_ip='([^']+)'\s+src_port='([^']+)'\s+dst_port='([^']+)'\s+tzone='[^']+'+\s+data='([^']+)'(\s+sig='([^']*)')?/", $line, $matches)) {
		$plugin_id = $matches[4];
		$plugin_sid = $matches[5];
	} elseif (preg_match("/entry id='([^']+)'\s+fdate='([^']+)'\s+date='([^']+)'\s+plugin_id='([^']+)'\s+sensor='([^']+)'\s+src_ip='([^']+)'\s+dst_ip='([^']+)'\s+src_port='([^']+)'\s+dst_port='([^']+)'\s+tzone='[^']+'+\s+data='([^']+)'(\s+sig='([^']*)')?/", $line, $matches)) {
		$plugin_id = $matches[4];
		$plugin_sid = "unknown";
	} else {
		continue;
	}
	
	// Convert OR to regex
	$a = preg_replace("/ or /i","|",$a);
	
	$args = explode(" ", $a);
	
	$negation = 0;
	$filtered = false;
	foreach ($args as $arg) {
		if ($filtered) { continue; }
		if ($arg == "and" || $arg == "AND") { continue; }
		if ($arg == " ") { continue; }
		if ($arg == "") { continue; }
		// TAXONOMY filter
		if (preg_match("/taxonomy='.*-.*-.*'/",$arg)) {
			if ($onlyid) {
				if (!$plugin_filters[$plugin_id]) $filtered = true;
			} else {
				if (!$plugin_filters[$plugin_id][$plugin_sid]) $filtered = true;
			}
		}
		// SINGLE ne: plugin_id!=1234 
		elseif (preg_match("/^\s*(.*)!=(.*)$/",$arg,$found)) {
			$regex = $found[1]."='".$found[2]."'";
			$regex = quotemeta($regex);
			if (preg_match("/$regex/",$line)) $filtered = true;
		}
		// SINGLE eq: plugin_id=1234
		elseif (preg_match("/^\s*(.*)=(.*)$/",$arg,$found)) { 
			if ($found[1] == "src_net" || $found[1] == "dst_net") {
				$value = str_replace("'","",$found[2]);
				$netfield = $found[1];
				$netfield = str_replace("net","ip",$netfield);
				$regex = quotemeta($netfield."='".$value);
				if (!preg_match("/$regex/",$line)) $filtered = true;
			} elseif ($found[1] == "id" || $found[1] == "fdate" || $found[1] == "date" || $found[1] == "plugin_id" || $found[1] == "sensor" || $found[1] == "src_ip" || $found[1] == "dst_ip" || $found[1] == "src_port" || $found[1] == "dst_port" || $found[1] == "tzone"|| $found[1] == "data"){
				$regex = quotemeta($found[1]."='".str_replace("'","",$found[2])."'");
				if (!preg_match("/$regex/",$line)) $filtered = true;
			} elseif ($found[1] == "ip") {
				$regex = quotemeta("src_ip='".str_replace("'","",$found[2])."'|dst_ip='".str_replace("'","",$found[2])."'");
				if (!preg_match("/$regex/",$line)) $filtered = true;
			} elseif ($found[1] == "net") {
				$regex = quotemeta("src_ip='".str_replace("'","",$found[2])."|dst_ip='".str_replace("'","",$found[2]));
				echo "NET!!$regex\n";
				if (!preg_match("/$regex/",$line)) $filtered = true;
			} else {
				$regex = quotemeta($found[1]."=".$found[2]);
				if (!preg_match("/$regex/",$line)) $filtered = true;
			}
		}
		// NEGATION
		elseif ($arg == "not") {
			$negation = 1;
		// DIRECT REGULAR EXPRESSION
		} else {
			$regex = quotemeta($arg);
			if ($negation) {
				if (preg_match("/$regex/",$line)) $filtered = true;
			} else {
				if (!preg_match("/$regex/",$line)) $filtered = true;
			}
			$negation = 0;
		}
	}
	
	// Do not filter this event
	if (!$filtered) echo $line;
}
fclose($in);
?>