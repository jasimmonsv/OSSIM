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
ob_implicit_flush();
ini_set('include_path', '/usr/share/ossim/include');

function get_day_csv($year, $month, $day) {
	$config = parse_ini_file("/usr/share/ossim/www/sem/everything.ini");
	$days = $sensors = array();	
	// Subdirectorios dentro de los directorios 
	$path = $config['log_dir'] . "$year/$month/" . sprintf("%02d", $day) . "/";
	if ($dir = opendir($path)) { 
		while (($file = readdir($dir)) !== false) {
			if (preg_match("/\.csv_total_events_(.*)/",$file,$fnd)) 
				$sensors[] = $fnd[1]; 
		}
	}
	foreach ($sensors as $s) if ($s != "") {
		// ini
		for ($a = 23; $a >= 0; $a--) $days[$s][$a] = 0;
		// read content file
		$file = $config['log_dir'] . "$year/$month/" . sprintf("%02d", $day) . "/.csv_total_events_$s";
		if (file_exists($file)) $csv = file_get_contents($file);
		else $csv = array();
		if (strlen($csv) > 0) $lines = explode("\n", $csv);
		if (count($lines) > 1) foreach($lines as $line) {
			$val = explode(",", trim($line));
			if ($val[0]!="") $days[$s][sprintf("%d", $val[0])] += $val[1];
		}
		$days[$s] = array_reverse($days[$s]);
	}
    return $days;
}
    
$tz = floatval($argv[1]); // timezone correction

$data = array();
$today = gmdate("j");
$beforeyesterday = gmdate("j",strtotime("-2 day"));
$yesterday = gmdate("j",strtotime("-1 day"));
$tomorrow = gmdate("j",strtotime("+1 day"));
$csy = get_day_csv(gmdate("Y",strtotime("-1 day")),gmdate("m",strtotime("-1 day")),gmdate("d",strtotime("-1 day")));
$csv = get_day_csv(gmdate("Y"),gmdate("m"),gmdate("d"));
//print_r($csy); print_r($csv);
foreach ($csy as $sensor => $arr) {
	foreach ($arr as $key => $value) {
		$tzhour = $key + $tz;
		$day = $yesterday;
		if ($tzhour<0) { $tzhour+=24; $day=$beforeyesterday; }
		elseif ($tzhour>23) { $tzhour-=24; $day=$today; }
		$data[$day." ".$tzhour."h;$sensor"] = $value;
	}	
}
foreach ($csv as $sensor => $arr) {
	foreach ($arr as $key => $value) {
		$tzhour = $key + $tz;
		$day = $today;
		if ($tzhour<0) { $tzhour+=24; $day=$yesterday; }
		elseif ($tzhour>23) { $tzhour-=24; $day=$tomorrow; }
		$data[$day." ".$tzhour."h;$sensor"] = $value;
	}	
}
// Print data
echo "\n";
foreach ($data as $k => $v) echo "$k=$v\n";
?>
