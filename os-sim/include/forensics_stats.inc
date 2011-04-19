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
* - get_date_str()
* - get_all_csv()
* - get_year_csv()
* - get_month_csv()
* - get_day_csv()
* Classes list:
*/
function get_date_str($month = "", $day = "", $type = "", $year = "") {
    if ($month != "") $month = mktime(0, 0, 0, $month, 0, 0);
	if ($day != "" && $year != "") return strftime("%b", $month) . " $day, " . $year;
	if ($year != "") return strftime("%b", $month).", " . $year;
	if ($type == "hours") return " at " . sprintf("%02d", $day) . "(h)";
    //echo $month;
    //echo strftime("%b", $month);
    if ($day != "") return strftime("%b", $month) . " $day, " . date("Y");
    return strftime("%b", $month) . ", " . date("Y");
}
function get_all_csv() {
    $config = parse_ini_file("/usr/share/ossim/www/sem/everything.ini");
    if (file_exists($config['log_dir'] . ".csv_total_events")) $csv = file_get_contents($config['log_dir'] . ".csv_total_events");
    else $csv = array();
    $all = array();
    if (strlen($csv) > 0) $lines = explode("\n", $csv);
    if (count($lines) > 1) foreach($lines as $line) {
        $val = explode(",", $line);
        if ($val[0] >= 1) $all[$val[0]] = $val[1];
    }
    if (count($all) >= 1) for ($i = 2005; $i <= 2015; $i++) if (!isset($all[$i])) $all[$i] = 0;
    ksort($all);
    return $all;
}
function get_year_csv($year) {
    $config = parse_ini_file("/usr/share/ossim/www/sem/everything.ini");
	if (Session::allowedSensors() != "") {
		$sensors = explode (",",Session::allowedSensors());
		$months = array();
		foreach ($sensors as $s) if ($s != "") {
			if (file_exists($config['log_dir'] . "$year/.csv_total_events_$s")) $csv = file_get_contents($config['log_dir'] . "$year/.csv_total_events_$s");
			else $csv = array();
			if (strlen($csv) > 0) $lines = explode("\n", $csv);
			if (count($lines) > 1) foreach($lines as $line) {
				$val = explode(",", $line);
				if ($val[0] >= 1) $months[(int)$val[0]] += $val[1];
			}
		}
		for ($i = 1; $i <= 12; $i++) if (!isset($months[$i])) $months[$i] = 0;
		ksort($months);
	} else {
		if (file_exists($config['log_dir'] . "$year/.csv_total_events")) $csv = file_get_contents($config['log_dir'] . "$year/.csv_total_events");
		else $csv = array();
		$months = array();
		if (strlen($csv) > 0) $lines = explode("\n", $csv);
		if (count($lines) > 1) foreach($lines as $line) {
			$val = explode(",", $line);
			if ($val[0] >= 1) $months[(int)$val[0]] = $val[1];
		}
		for ($i = 1; $i <= 12; $i++) if (!isset($months[$i])) $months[$i] = 0;
		ksort($months);
	}
    return $months;
}

function get_range_csv($date_from,$date_to,$range) {
	$config = parse_ini_file("/usr/share/ossim/www/sem/everything.ini");
	// time format range
	$from = strtotime($date_from);
	$to = strtotime($date_to);
	if ($range == "hour") $add = 3600;
	elseif ($range == "day") $add = 86400;
	else $add = 2592000; // month
	$from += $add;
	$months = array();
	$days = array();
	if (Session::allowedSensors() != "") {
		$sensors = explode (",",Session::allowedSensors());
		foreach ($sensors as $s) if ($s != "") {
			while ($from < $to+$add) {
				$y = date("Y",$from);
				$m = date("m",$from);
				$d = date("d",$from);
				if ($range == "month") $add = 86400*cal_days_in_month(CAL_GREGORIAN, $m, $y);
				//echo "year $lyear month $i file:".$config['log_dir'] . "$lyear/$zero$i/.csv_total_events"."<br>";
				if (file_exists($config['log_dir'] . "$y/$m/.total_events_$s")) $csv = file($config['log_dir'] . "$y/$m/.total_events_$s");
				else $csv[0] = "0";
				$months[$y][$m] = trim($csv[0]);
				if (file_exists($config['log_dir'] . "$y/$m/$d/.total_events_$s")) $csv = file($config['log_dir'] . "$y/$m/$d/.total_events_$s");
				else $csv[0] = "0";
				$days[$y][$m][$d] = trim($csv[0]);
				//dateadd($from,"+ 1 $range");
				$from += $add;
			}
		}
	} else {
		while ($from < $to+$add) {
			$y = date("Y",$from);
			$m = date("m",$from);
			$d = date("d",$from);
			if ($range == "month") $add = 86400*cal_days_in_month(CAL_GREGORIAN, $m, $y);
			//echo "file:".$config['log_dir'] . "$y/$m/.total_events range:$add"."<br>";
			if (file_exists($config['log_dir'] . "$y/$m/.total_events")) $csv = file($config['log_dir'] . "$y/$m/.total_events");
			else $csv[0] = "0";
			$months[$y][$m] = trim($csv[0]);
			if (file_exists($config['log_dir'] . "$y/$m/$d/.total_events")) $csv = file($config['log_dir'] . "$y/$m/$d/.total_events");
			else $csv[0] = "0";
			$days[$y][$m][$d] = trim($csv[0]);
			//dateadd($from,"+ 1 $range");
			$from += $add;
		}
		//ksort($months[$lyear]);
	}
	
	if ($range == "day") return $days;
    else return $months;
}

function get_month_csv($year, $month) {
    $config = parse_ini_file("/usr/share/ossim/www/sem/everything.ini");
	if (Session::allowedSensors() != "") {
		$sensors = explode (",",Session::allowedSensors());
		$days = array();
		foreach ($sensors as $s) if ($s != "") {
			if (file_exists($config['log_dir'] . "$year/$month/.csv_total_events_$s")) $csv = file_get_contents($config['log_dir'] . "$year/$month/.csv_total_events_$s");
			else $csv = array();
			if (strlen($csv) > 0) $lines = explode("\n", $csv);
			if (count($lines) > 1) foreach($lines as $line) {
				$val = explode(",", $line);
				if ($val[0] >= 1) $days[sprintf("%d", $val[0]) ] += $val[1];
			}
		}
	} else {
		if (file_exists($config['log_dir'] . "$year/$month/.csv_total_events")) $csv = file_get_contents($config['log_dir'] . "$year/$month/.csv_total_events");
		else $csv = array();
		$days = array();
		//echo $csv;
		if (strlen($csv) > 0) $lines = explode("\n", $csv);
		if (count($lines) > 1) foreach($lines as $line) {
			$val = explode(",", $line);
			if ($val[0] >= 1) $days[sprintf("%d", $val[0]) ] = $val[1];
		}
	}
	$final = array();
    $num_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
	for ($a = $num_days; $a >= 1; $a--) {
		$ind = sprintf("%d", $a);
		//		echo $days[$ind]."<br>";
		if ($days[$ind] <= 0) $final[$ind] = "0";
		else $final[$ind] = $days[$ind];
	}
    return array_reverse($final);
}

function get_day_csv($year, $month, $day) {
	$config = parse_ini_file("/usr/share/ossim/www/sem/everything.ini");
    if (Session::allowedSensors() != "") {
		$sensors = explode (",",Session::allowedSensors());
		$days = array();
		for ($a = 23; $a >= 0; $a--) $days[$a] = 0;
		foreach ($sensors as $s) if ($s != "") {
			$file = $config['log_dir'] . "$year/$month/" . sprintf("%02d", $day) . "/.csv_total_events_$s";
			if (file_exists($file)) $csv = file_get_contents($file);
			else $csv = array();
			if (strlen($csv) > 0) $lines = explode("\n", $csv);
			if (count($lines) > 1) foreach($lines as $line) {
				$val = explode(",", trim($line));
				if ($val[0]!="") $days[sprintf("%d", $val[0]) ] += $val[1];
			}
		}
	} else {
		$file = $config['log_dir'] . "$year/$month/" . sprintf("%02d", $day) . "/.csv_total_events";
		//print_r($file);
		if (file_exists($file)) $csv = file_get_contents($file);
		else $csv = array();
		$days = array();
		for ($a = 23; $a >= 0; $a--) $days[$a] = 0;
		if (strlen($csv) > 0) $lines = explode("\n", $csv);
		if (count($lines) > 1) foreach($lines as $line) {
			$val = explode(",", trim($line));
			if ($val[0]!="") $days[sprintf("%d", $val[0])] = $val[1];
		}
	}
    return array_reverse($days);
}
?>
