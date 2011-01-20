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
* - get_lines()
* - process()
* Classes list:
*/
require_once 'classes/Session.inc';
if (!$skip_logcheck) Session::logcheck("MenuEvents", "ControlPanelSEM");
function get_lines($a = "root", $start = "2008-10-16 00:00:01", $end = "2008-09-17 00:00:02", $offset = 0, $sort_order = "date", $what = "logs", $uniqueid = "0000000000") {
    $cmd = "";
    $a = escapeshellarg($a);
    if ($a == "") {
        $a = '""';
    }
    $start = escapeshellarg($start);
    $end = escapeshellarg($end);
    $offset = intval($offset);
    $config = parse_ini_file("everything.ini");
    $cache_dir = $config["cache_dir"];
    if (is_dir($cache_dir) && is_writable($cache_dir) && is_readable($cache_dir)) {
        // Cache functions
        $cache_candidates = array();
        $cache_files = array();
        $usable_cache_file = "";
        $perfect_match = 0;
        if ($sort_order == "date") {
            $sort = "date";
        } elseif ($sort_order == "date_desc") {
            $sort = "date_desc";
        } else {
            $sort = "none";
        }
        $long = "";
        foreach(split(" ", $a) as $piece) {
            if ($long != "") {
                $long.= " ";
            }
            $long.= rtrim($piece . " ");
            $long_filename = md5($long);
            $long_filename = $long_filename . "." . $sort . ".cache";
            array_push($cache_candidates, $long_filename);
        }
        // Reverse so we find the closest match first
        $cache_candidates = array_reverse($cache_candidates);
        // This can be vastly improved in performance terms
        if ($handle = opendir($cache_dir)) {
            $i = 0;
            foreach($cache_candidates as $candidate) {
                $tmp_cache = array();
                while (false !== ($file = readdir($handle))) {
                    if (strpos($file, "start")) {
                        continue;
                    }
                    if (strpos($file, "end")) {
                        continue;
                    }
                    //print "Comparing $file and $candidate<br/>";
                    $tmp = strcmp($file, $candidate);
                    if ($tmp > 1) {
                        array_push($tmp_cache, $file);
                        if ($i == 0) {
                            $perfect_match = 1;
                        }
                    }
                }
                rewinddir($handle);
                natsort($tmp_cache);
                $tmp_cache = array_reverse($tmp_cache);
                $cache_files = array_merge($cache_files, $tmp_cache);
                $i++;
            }
        }
        closedir($handle);
        //print "<hr>";
        //print_r($cache_files);
        //print "<hr>";
        foreach($cache_files as $cache_file) {
            if (filectime($cache_dir . "/" . $cache_file) > (time - 3600)) {
                $tmp_start = file_get_contents($cache_dir . "/" . $cache_file . ".start");
                $tmp_end = file_get_contents($cache_dir . "/" . $cache_file . ".end");
                if ($start == $tmp_start && $end == $tmp_end) {
                    $usable_cache_file = $cache_dir . "/" . $cache_file;
                    break;
                } else {
                    $perfect_match = 0;
                }
            }
        }
        if ($usable_cache_file != "") {
            // Issue query on cache file
            if ($what == "logs") {
                $cmd = "perl fetchall.pl $start $end $a $offset 50 $sort_order logs $usable_cache_file $uniqueid";
            } else {
                $cmd = "perl fetchall.pl $start $end $a 0 10 none $what $usable_cache_file $uniqueid";
            }
            $cache_file_name = $usable_cache_file;
            //error_log("1. $cmd\n", 3, "/tmp/fetch");
            if (!$perfect_match) {
                // Create another cache file
                $file_hash = md5($a);
                $file_hash = $file_hash . "." . $sort . ".cache";
                $i = 0;
                while (1) {
                    if (!file_exists($cache_dir . "/" . $file_hash . "." . $i)) {
                        $cache_file_name = $cache_dir . "/" . $file_hash . "." . $i;
                        break;
                    }
                    $i++;
                }
                //print "No cache !!!!!!!!!!<br/>";
                //print "Creating one at $cache_file_name<br/>";
                file_put_contents($cache_file_name . ".start", $start);
                file_put_contents($cache_file_name, $start);
                file_put_contents($cache_file_name . ".end", $end);
                //system("perl fetchall.pl $start $end $a 0 5000000000 $sort_order logs $usable_cache_file $uniqueid  > $cache_file_name");
                $cache_file_name;
            }
        } else {
            // Create cache file and query
            $file_hash = md5($a);
            $file_hash = $file_hash . "." . $sort . ".cache";
            $i = 0;
            while (1) {
                if (!file_exists($cache_dir . "/" . $file_hash . "." . $i)) {
                    $cache_file_name = $cache_dir . "/" . $file_hash . "." . $i;
                    break;
                }
                $i++;
            }
            //print "No cache !!!!!!!!!!<br/>";
            //print "Creating one at $cache_file_name<br/>";
            file_put_contents($cache_file_name . ".start", $start);
            file_put_contents($cache_file_name, $start);
            file_put_contents($cache_file_name . ".end", $end);
            //    system("perl fetchall.pl $start $end $a 0 5000000000 $sort_order logs none $uniqueid > $cache_file_name");
            //print $cmd;
            //print "<br/>";
            if ($what == "logs") {
                $cmd = "perl fetchall.pl $start $end $a $offset 50 $sort_order logs $cache_file_name $uniqueid";
            } else {
                $cmd = "perl fetchall.pl $start $end $a 0 10 none $what $cache_file_name $uniqueid";
            }
        }
    } else {
        if ($what == "logs") {
            $cmd = "perl fetchall.pl $start $end $a $offset 50 $sort_order logs none $uniqueid";
        } else {
            $cmd = "perl fetchall.pl $start $end $a 0 10 none $what none $uniqueid";
        }
    }
    //print "Perfect: $perfect_match<br/>";
    //print $cmd;
    //error_log("2. $cmd\n", 3, "/tmp/fetch");
    /* Part of visualization */
    $status = exec("wc -l $cache_file_name | cut -f 1 -d \" \"", $result);
    return $result[0];
}
function process($a = "root", $start = "2008-10-16 00:00:01", $end = "2008-09-17 00:00:02", $offset = 0, $sort_order = "date", $what = "logs", $uniqueid = "0000000000", $forced_max=51, $mute=0) {
    $forced_max++;
    $tope = $forced_max;
	if ($offset == -1) { $tope = 50000; $offset = 0; }
    $cmd = "";
    $a = escapeshellarg($a);
    if ($a == "") {
        $a = '""';
    }
    $start = escapeshellarg($start);
    $end = escapeshellarg($end);
    $offset = intval($offset);
    $config = parse_ini_file("everything.ini");
    $cache_dir = $config["cache_dir"];
    if (is_dir($cache_dir) && is_writable($cache_dir) && is_readable($cache_dir)) {
        // Cache functions
        $cache_candidates = array();
        $cache_files = array();
        $usable_cache_file = "";
        $perfect_match = 0;
        if ($sort_order == "date") {
            $sort = "date";
        } elseif ($sort_order == "date_desc") {
            $sort = "date_desc";
        } else {
            $sort = "none";
        }
        $long = "";
        foreach(split(" ", $a) as $piece) {
            if ($long != "") {
                $long.= " ";
            }
            $long.= rtrim($piece . " ");
            $long_filename = md5($long);
            $long_filename = $long_filename . "." . $sort . ".cache";
            array_push($cache_candidates, $long_filename);
        }
        // Reverse so we find the closest match first
        $cache_candidates = array_reverse($cache_candidates);
        // This can be vastly improved in performance terms
        if ($handle = opendir($cache_dir)) {
            $i = 0;
            foreach($cache_candidates as $candidate) {
                $tmp_cache = array();
                while (false !== ($file = readdir($handle))) {
                    if (strpos($file, "start")) {
                        continue;
                    }
                    if (strpos($file, "end")) {
                        continue;
                    }
                    //print "Comparing $file and $candidate<br/>";
                    $tmp = strcmp($file, $candidate);
                    if ($tmp > 1) {
                        array_push($tmp_cache, $file);
                        if ($i == 0) {
                            $perfect_match = 1;
                        }
                    }
                }
                rewinddir($handle);
                natsort($tmp_cache);
                $tmp_cache = array_reverse($tmp_cache);
                $cache_files = array_merge($cache_files, $tmp_cache);
                $i++;
            }
        }
        closedir($handle);
        //print "<hr>";
        //print_r($cache_files);
        //print "<hr>";
        foreach($cache_files as $cache_file) {
            if (filectime($cache_dir . "/" . $cache_file) > (time - 3600)) {
                $tmp_start = file_get_contents($cache_dir . "/" . $cache_file . ".start");
                $tmp_end = file_get_contents($cache_dir . "/" . $cache_file . ".end");
                if ($start == $tmp_start && $end == $tmp_end) {
                    $usable_cache_file = $cache_dir . "/" . $cache_file;
                    break;
                } else {
                    $perfect_match = 0;
                }
            }
        }
        if ($usable_cache_file != "") {
            // Issue query on cache file
            if ($what == "logs") {
                $cmd = "perl fetchall.pl $start $end ".str_replace ("|", "#", $a)." $offset $tope $sort_order logs $usable_cache_file $uniqueid";
            } else {
                $cmd = "perl fetchall.pl $start $end ".str_replace ("|", "#", $a)." 0 10 none $what $usable_cache_file $uniqueid";
            }
            $cache_file_name = $usable_cache_file;
            //print $cmd;
            //error_log("3. $cmd\n", 3, "/tmp/fetch");
            if (!$perfect_match) {
                // Create another cache file
                $file_hash = md5($a);
                $file_hash = $file_hash . "." . $sort . ".cache";
                $i = 0;
                while (1) {
                    if (!file_exists($cache_dir . "/" . $file_hash . "." . $i)) {
                        $cache_file_name = $cache_dir . "/" . $file_hash . "." . $i;
                        break;
                    }
                    $i++;
                }
                //print "No cache !!!!!!!!!!<br/>";
                //print "Creating one at $cache_file_name<br/>";
                file_put_contents($cache_file_name . ".start", $start);
                file_put_contents($cache_file_name, $start);
                file_put_contents($cache_file_name . ".end", $end);
                //error_log("4. perl fetchall.pl $start $end ".str_replace ("|", "#", $a)." 0 5000000000 $sort_order logs $usable_cache_file $uniqueid > $cache_file_name\n", 3, "/tmp/fetch");
                system("perl fetchall.pl $start $end ".str_replace ("|", "#", $a)." 0 5000000000 $sort_order logs $usable_cache_file $uniqueid > $cache_file_name 2>/dev/null");
                $cache_file_name;
            }
        } else {
            // Create cache file and query
            $file_hash = md5($a);
            $file_hash = $file_hash . "." . $sort . ".cache";
            $i = 0;
            while (1) {
                if (!file_exists($cache_dir . "/" . $file_hash . "." . $i)) {
                    $cache_file_name = $cache_dir . "/" . $file_hash . "." . $i;
                    break;
                }
                $i++;
            }
            //print "No cache !!!!!!!!!!<br/>";
            //print "Creating one at $cache_file_name<br/>";
            file_put_contents($cache_file_name . ".start", $start);
            file_put_contents($cache_file_name, $start);
            file_put_contents($cache_file_name . ".end", $end);
            //error_log("5. perl fetchall.pl $start $end ".str_replace ("|", "#", $a)." 0 5000000000 $sort_order logs none $uniqueid > $cache_file_name\n", 3, "/tmp/fetch");
            system("perl fetchall.pl $start $end ".str_replace ("|", "#", $a)." 0 5000000000 $sort_order logs none $uniqueid > $cache_file_name 2>/dev/null");
            //print $cmd;
            //print "<br/>";
            if ($what == "logs") {
                $cmd = "perl fetchall.pl $start $end ".str_replace ("|", "#", $a)." $offset $tope $sort_order logs $cache_file_name $uniqueid";
            } else {
                $cmd = "perl fetchall.pl $start $end ".str_replace ("|", "#", $a)." 0 10 none $what $cache_file_name $uniqueid";
            }
        }
    } else {
        if ($what == "logs") {
            $cmd = "perl fetchall.pl $start $end ".str_replace ("|", "#", $a)." $offset $tope $sort_order logs none $uniqueid";
        } else {
            $cmd = "perl fetchall.pl $start $end ".str_replace ("|", "#", $a)." 0 10 none $what none $uniqueid";
        }
    }
    //print "Perfect: $perfect_match<br/>";
    //error_log("6. $cmd\n", 3, "/tmp/fetch");
    //print $cmd;
    /* Part of visualization */
    /*
    $status = exec("wc -l $cache_file_name | cut -f 1 -d \" \"", $result);
    $total_lines = $result[0];
    
    if($result[0] > 0){
    $total_lines = $result[0] - 1;
    } else {
    $total_lines = 0;
    }
    */
    
    if ($what == "logs" && $tope != 50000 && !$mute) {
        //	print "$cmd";
        //print "<center><b>$total_lines total lines</b> - Time Range: <b>$start <-> $end</b></center>";
        print _("UTC Query Time Range").": <b>$start <-> $end</b>";
    }
    system("echo $status >> /tmp/a");
    if ($what != "logs" && ($status > 500000)) {
        return "";
    } else {
        return $cmd;
    }
}
?>
