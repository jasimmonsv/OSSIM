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
 * - Scan()
 * - append_option()
 * - do_scan()
 * - ping_scan()
 * - __ping_scan_process_output()
 * - root_scan()
 * - __root_scan_process_output()
 * - __get_command_output()
 * - debug()
 * - save_scan()
 * - get_scan()
 * - del_scan()
 * Classes list:
 * - Scan
 */
define('NMAP_PING_SCAN_COMMAND', "%s -d1 -sP -n %s");
define('NMAP_ROOT_SCAN_COMMAND', "%s -d1 -sS -O -sV -n %s %s");
define('NMAP_PING_TMP_FILE', "/tmp/nmap_ping.log");
define('NMAP_ROOT_TMP_FILE', "/tmp/nmap_root.log");
define('NMAP_PING_SCAN_REGEXP', "/^Host (\d+\.\d+\.\d+\.\d+) appears to be up/");
define('NMAP_MAC_ADDRESS_REGEXP', "/^MAC Address: (\w{2}:\w{2}:\w{2}:\w{2}:\w{2}:\w{2}) (\(.*\))?$/");
define('NMAP_OS_REGEXP', "/^Running: (.*)$/");
define('NMAP_SERVICE_REGEXP', "/^(\d+)\/(\w+)\s+(open|filtered|unfiltered)\s+(\S+)\s+(.*)$/");
define('NMAP_SERVICE_NOVER_REGEXP', "/^(\d+)\/(\w+)\s+(open|filtered|unfiltered)\s+(\S+)$/");
require_once ('ossim_conf.inc');
require_once ('classes/Log_action.inc');
class Scan {
    var $network;
    var $scan;
    var $nmap;
    var $options;

    function Scan($network, $scan = NULL) {
        /* network to scan */
        $this->network = preg_replace("/\,\s*/",", ",$network);
        /* store scan result */
        if (is_array($scan)) $this->scan = $scan;
        else $this->scan = array();
        /* nmap */
        $conf = $GLOBALS["CONF"];
        $this->nmap = $conf->get_conf("nmap_path");
        /* User action logging */
        $infolog = array(
            $network
        );
        $this->options = "";
        Log_action::log(43, $infolog);
    }

	function scanning_now($ip="") {
		$cmd = ($ip != "") ? "ps ax | grep remote_nmap.php | grep $ip | grep -v grep" : "ps ax | grep remote_nmap.php | grep -v grep";
		$output = explode("\n",`$cmd`);
		return (preg_match("/remote\_nmap/",$output[0])) ? 1 : 0;
	}
	function scanning_what() {
		$cmd = "ps ax | grep remote_nmap.php | grep -v grep";
		$output = explode("\n",`$cmd`);
		$ips = array();
		foreach ($output as $line) {
			if (preg_match("/(\d+\.\d+\.\d+\.\d+\/\d+)/",$line,$found)) {
				$ips[] = $found[1];
			}
		}
		return $ips;
	}
	function stop_nmap() {
		$cmd = "ps ax | grep -v 'grep' | grep nmap";
	    $fp = popen("$cmd 2>&1", "r");
	    $pids = "";
	    while (!feof($fp)) {
	        $line = trim(fgets($fp));
	        $value = explode(" ", $line);
	        if ($value[0] != "") $pids.= " " . $value[0];
	    }
	    fclose($fp);
	    $cmd = "kill -9 $pids";
	    if (preg_match("/^kill \-9\s+[\d\s]+$/",$cmd)) {
	    	system($cmd);
	    }
	    if (file_exists("/tmp/nmap_scanning.log")) unlink("/tmp/nmap_scanning.log");
	    if (file_exists("/tmp/nmap_ping.log")) unlink("/tmp/nmap_ping.log");
	    if (file_exists("/tmp/nmap_root.log")) unlink("/tmp/nmap_root.log");
	}
    
    function append_option($options) {
        if ($this->options)
            $this->options .= " " . $options;
        else
            $this->options = $options;
    }

    function do_scan($full_scan = TRUE) {
    	$this->ping_scan();
        printf("%d alive hosts detected<br/>", count($this->scan)); flush();
        foreach($this->scan as $host) {
            $ip = $host["ip"];
            $resolv_ip = gethostbyaddr($ip);
            echo "<img src='../pixmaps/arrow-315-small.png'/>";
            echo "<b>$ip";
            if ($ip != $resolv_ip) echo " ($resolv_ip)";
            echo "</b>.. <br/>";
            flush();
            if ($full_scan) {
                $this->root_scan($ip);
            }
        }
        $this->save_scan();
    }
    function ping_scan() {
        $command = sprintf(NMAP_PING_SCAN_COMMAND,
                           $this->nmap,
                           $this->network);
        $this->__ping_scan_process_output($this->__get_command_output($command, NMAP_PING_TMP_FILE));
    }
    function __ping_scan_process_output($output) {
    	foreach($output as $line) {
            if (preg_match(NMAP_PING_SCAN_REGEXP, $line, $regs)) {
                $this->scan[$regs[1]] = array(
                    "ip" => $regs[1],
                    "mac" => "",
                    "mac_vendor" => "",
                    "os" => "",
                    "services" => array()
                );
            }
        }
    }
    function root_scan($ip) {
        $command = sprintf(NMAP_ROOT_SCAN_COMMAND,
                           $this->nmap,
                           $this->options,
                           $ip);
        $this->__root_scan_process_output($this->__get_command_output($command, NMAP_ROOT_TMP_FILE) , $ip);
    }
    function __root_scan_process_output($output, $ip) {
        foreach($output as $line) {
            /* mac */
            if (preg_match(NMAP_MAC_ADDRESS_REGEXP, $line, $regs)) {
                $this->scan[$ip]["mac"] = strtoupper($regs[1]);
                $this->scan[$ip]["mac_vendor"] = $regs[2];
            /* os */
            } elseif (preg_match(NMAP_OS_REGEXP, $line, $regs)) {
                $this->scan[$ip]["os"] = $regs[1];
            /* services */
            } elseif (preg_match(NMAP_SERVICE_REGEXP, $line, $regs)) {
                $this->scan[$ip]["services"][$regs[1] . "/" . $regs[2]] = array(
                    "port" => $regs[1],
                    "proto" => $regs[2],
                    "state" => $regs[3],
                    "service" => $regs[4],
                    "version" => $regs[5]
                );
            } elseif (preg_match(NMAP_SERVICE_NOVER_REGEXP, $line, $regs)) {
                $this->scan[$ip]["services"][$regs[1] . "/" . $regs[2]] = array(
                    "port" => $regs[1],
                    "proto" => $regs[2],
                    "state" => $regs[3],
                    "service" => $regs[4],
                    "version" => "unknown"
                );
            }
        }

        /* show realtime results */
        if ($this->scan[$ip]["os"]) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;";
            echo "OS: " . $this->scan[$ip]["os"];
            echo "<br/>";
        }

        if ($this->scan[$ip]["mac"]) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;";
            echo "MAC: " . $this->scan[$ip]["mac"]." ".($this->scan[$ip]["mac_vendor"]);
            echo "<br/>";
        }
        if ($this->scan[$ip]["services"]) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;";
            echo "Services: ";
            foreach ($this->scan[$ip]["services"] as $service) {
                echo $service["service"] . "&nbsp;";
            }
            echo "<br/>";
        }
        echo "<hr/>";
        flush();
    }
    function __get_command_output($cmd,$logfile="") {
    	if ($logfile != "") {
    		system(escapeshellcmd($cmd)." > ".$logfile);
    		$output = file($logfile);
    		unlink($logfile);
    		return $output;
    	} else {
    		$output = shell_exec(escapeshellcmd($cmd));
        	return split("\n", $output);
    	}
    }
    function __get_nmap_output($tmp_file) {
    	return (file_exists($tmp_file)) ? file($tmp_file) : array();
    }
    function debug() {
        // Show scan result
        print "<pre>";
        print_r($this->scan);
        print "</pre>";
    }
    function save_scan() {
    	$f = fopen("/tmp/nmap_completed_scan.log","w");
    	fputs($f,json_encode($this->scan));
    	fclose($f);
    }
    function get_scan() {
        $scan = array();
    	if (file_exists("/tmp/nmap_completed_scan.log")) {
    		$logfile_content = file("/tmp/nmap_completed_scan.log");
    		$json_string = $logfile_content[0];
    		$scan = json_decode($json_string, true);
    	}
    	return $scan;
    }
    function del_scan() {
    	if (file_exists("/tmp/nmap_completed_scan.log")) {
    		unlink("/tmp/nmap_completed_scan.log");
    	}
    }
}

class RemoteScan {
    private $network;
    private $type;
    private $id;
    private $errmsg;
    private $address;
    private $port;
    private $scan;
    private $allids;
    private $timeout;
    
    function __construct($network, $type, $id="") {
        /* network to scan */
        $this->network = preg_replace("/\,\s*/",", ",$network);
        $this->type = $type;
        if ($id!="") $this->id=$id;
        $this->errmsg = "";
        require_once ('ossim_conf.inc');
	    $ossim_conf = $GLOBALS["CONF"];
	    /* get the port and IP address of the frameworkd */
	    $this->address = $ossim_conf->get_conf("frameworkd_address");
	    $this->port = $ossim_conf->get_conf("frameworkd_port");
	    $this->timeout = array('sec' => 15, 'usec' => 0);
    }
    
    function available_scan() {
        $this->allids = array();
    	// try to connect with frameworkd and "getconnectedagents"
	    $socket = socket_create(AF_INET, SOCK_STREAM, 0);
	    if ($socket < 0) {
	        $this->errmsg = _("Can't connect with frameworkd").": ". socket_strerror($socket);
	        return "";
	    }
	    /* connect */
	    $result = @socket_connect($socket, $this->address, $this->port);
	    if (!$result) {
	        $this->errmsg = _("Can't connect with frameworkd")." ($this->address:$this->port)";
	        return "";
	    }
	    socket_set_block($socket);
		socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,$this->timeout);
	    /* first send a connect message to server */
	    $in = 'control action="getconnectedagents"' . "\n";
	    $out = '';
	    socket_write($socket, $in, strlen($in));
	    $out = @socket_read($socket, 2048, PHP_NORMAL_READ);
	    if (!preg_match("/ack(end)?$/",$out)) {
	        $this->errmsg = _("Bad response from frameworkd").": $out";
	        return "";
	    }
		preg_match("/names=\"(.*?)\"/",$out,$found);
		if (count($found)<2) {
	        $this->errmsg = _("Bad response from frameworkd").": $out";
	        return "";
	    }
	    $found[1] = explode("|",$found[1]);
	    socket_close($socket);
	    //print_r($out."<br>");print_r($found);
	    // Network perm test
	    require_once("ossim_db.inc");
	    require_once("classes/Net.inc");
	    require_once("classes/Host.inc");
	    require_once("classes/Sensor.inc");
		$db = new ossim_db();
		$conn = $db->connect();
		// save all names and check $network perms
	    $names = array();
	    for ($i=0;$i<count($found[1]);$i++) $names[Sensor::get_sensor_ip($conn,$found[1][$i])] = $found[1][$i];
	    // Check if this network sensor is available for remote scan
	    $sa = (Session::allowedSensors()=="") ? array_keys($names) : explode(",",Session::allowedSensors());
	    $related_sensors = (preg_match("/\//",$this->network)) 
	    				? Net::get_related_sensors($conn,Net::get_name_by_ip($conn,$this->network))
	    				: (($this->network!="") ? Host::get_related_sensors($conn,$this->network) : $sa);
	    $sensors = array_intersect($related_sensors,array_keys($names));
	    if (count($sensors)<1) {
	    	$db->close($conn);
	        $this->errmsg = _("Network sensors are not available for remote scan");
	        return "";
	    }
	    // use the first available agent id to scan
	    $this->id = $names[$sensors[0]];
	    $this->allids = $names;
	    $db->close($conn);
	    //
		return $sensors[0];
    }
    
    function do_scan($quiet=TRUE) {
    	session_write_close();
    	$this->scan = array();
    	// prepare to launch nmap
		$socket = socket_create(AF_INET, SOCK_STREAM, 0);
	    if ($socket < 0) {
	        $this->errmsg = _("Can't connect with frameworkd").": ". socket_strerror($socket);
	        return false;
	    }
	    /* connect */
	    $result = @socket_connect($socket, $this->address, $this->port);
	    if (!$result) {
	        $this->errmsg = _("Can't connect with frameworkd")." ($this->address:$this->port)";
	        return false;
	    }
	    //
	    // launch nmap
	    //
	    socket_set_block($socket);
		socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,$this->timeout);
	    $in = 'control action="nmap_scan" id="'.$this->id.'" type="'.$this->type.'" target="'.$this->network.'"' . "\n";
	    $out = '';
	    socket_write($socket, $in, strlen($in));
	    $out = @socket_read($socket, 2048, PHP_NORMAL_READ);
	    if (!preg_match("/ack(end)?$/",$out)) {
	        $this->errmsg = _("Bad response from frameworkd").": $in >> $out";
	        return false;
	    }
	    // query until finish nmap job
	    $in = 'control action="nmap_status" id="'.$this->id.'"' . "\n";
	    $out = '';
	    $status=1;
	    if (!$quiet) echo _("Waiting remote scan status\n");
	    while ($status>0) {
	    	if (!$quiet) echo _(" .");
		    socket_write($socket, $in, strlen($in));
		    $out = socket_read($socket, 2048, PHP_NORMAL_READ);
		    if (!preg_match("/ack(end)?$/",$out)) {
		        $this->errmsg = _("Bad response from frameworkd").": $out";
		        return false;
		    }
		    preg_match("/status=\"(.*?)\"/",$out,$found); $status=$found[1];
		    sleep(10); // 10 seconds between requests
		}
		// get scans with nmap_report_list
	    $in = 'control action="nmap_report_list" id="'.$this->id.'"' . "\n";
	    $out = '';
	    socket_write($socket, $in, strlen($in));
	    $out = @socket_read($socket, 2048, PHP_NORMAL_READ);
	    if (!preg_match("/ack(end)?$/",$out)) {
	        $this->errmsg = _("Bad response from frameworkd").": $out";
	        return false;
	    }
	    // select last report
		preg_match_all("/report=\"(.*?)\"/",$out,$found);
		foreach ($found[1] as $rpt) {
			if (preg_match("/".$this->type."/",$rpt))
				$report = $rpt;
		}
		//
		// request report results
		//
	    $in = 'control action="nmap_report_get" id="'.$this->id.'" path="'.$report.'"' . "\n";
	    $out = '';
	    socket_write($socket, $in, strlen($in));
	    while (($out = @socket_read($socket, 2048, PHP_NORMAL_READ)) !== false) {
	            $out = trim($out);
		    if (!preg_match("/ack(end)?$/",$out)) {
		        $this->errmsg = _("Bad response from frameworkd").": $out";
		        return false;
		    }
		    $ip = $mac = $vendor = $os = ""; $services=array();
		    if (preg_match("/ip=\"(.*?)\"/",$out,$found)) $ip = $found[1];
		    if (preg_match("/mac=\"(.*?)\"/",$out,$found)) $mac = $found[1];
		    if (preg_match("/vendor=\"(.*?)\"/",$out,$found)) $vendor = $found[1];
		    if (preg_match("/os=\"(.*?)\"/",$out,$found)) $os = $found[1];
            if (preg_match_all("/port=\"(.*?)\"/",$out,$found)) {
            	foreach ($found[1] as $port) {
            		$regs = explode("|",$port);
            		$services[$regs[0] . "/" . $regs[1]] = array(
	                    "port" => $regs[0],
	                    "proto" => $regs[1],
	                    "state" => $regs[2],
	                    "service" => $regs[3],
	                    "version" => $regs[4]
	                );
            	}
            }
	        if (trim($ip)!="") 
		        $this->scan[$ip] = array(
	                "ip" => $ip,
	                "mac" => $mac,
	                "mac_vendor" => $vendor,
	                "os" => $os,
	                "services" => $services
	            );
		    if (preg_match("/ackend$/",$out)) break;
	    }
		// show results
        if (!$quiet) $this->print_scan();
		socket_close($socket);
    }
    
    function get_scans() {
    	// prepare to launch nmap
		$socket = socket_create(AF_INET, SOCK_STREAM, 0);
	    if ($socket < 0) {
	        $this->errmsg = _("Can't connect with frameworkd").": ". socket_strerror($socket);
	        return false;
	    }
	    /* connect */
	    $result = @socket_connect($socket, $this->address, $this->port);
	    if (!$result) {
	        $this->errmsg = _("Can't connect with frameworkd")." ($this->address:$this->port)";
	        return false;
	    }
	    socket_set_block($socket);
		socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,$this->timeout);
	    /* send nmap_report_list for each id */
	    $reports = array();
	    foreach ($this->allids as $idip => $id) if (trim($id)!="") {
		    $in = 'control action="nmap_report_list" id="'.$id.'"' . "\n";
		    $out = '';
		    socket_write($socket, $in, strlen($in));
		    $out = @socket_read($socket, 2048, PHP_NORMAL_READ);
		    if (!preg_match("/ack(end)?$/",$out)) {
		        $this->errmsg = _("Bad response from frameworkd").": $out";
		        return false;
		    }
		    preg_match_all("/report=\"(.*?)\"/",$out,$found);
		    $reports[$id] = $found[1];
		}
		socket_close($socket);
	    return $reports;
    }

	function import_scan($report) {
    	$this->scan = array();
    	// prepare to launch nmap
		$socket = socket_create(AF_INET, SOCK_STREAM, 0);
	    if ($socket < 0) {
	        $this->errmsg = _("Can't connect with frameworkd").": ". socket_strerror($socket);
	        return false;
	    }
	    /* connect */
	    $result = @socket_connect($socket, $this->address, $this->port);
	    if (!$result) {
	        $this->errmsg = _("Can't connect with frameworkd")." ($this->address:$this->port)";
	        return false;
	    }
	    socket_set_block($socket);
		socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,$this->timeout);
		// request report results
	    $in = 'control action="nmap_report_get" id="'.$this->id.'" path="'.$report.'"' . "\n";
	    $out = '';
	    socket_write($socket, $in, strlen($in));
	    while (($out = @socket_read($socket, 2048, PHP_NORMAL_READ)) !== false) {
	            $out = trim($out);
		    if (!preg_match("/ack(end)?$/",$out)) {
		        $this->errmsg = _("Bad response from frameworkd").": $out";
		        return false;
		    }
		    $ip = $mac = $vendor = $os = ""; $services=array();
		    if (preg_match("/ip=\"(.*?)\"/",$out,$found)) $ip = $found[1];
		    if (preg_match("/mac=\"(.*?)\"/",$out,$found)) $mac = $found[1];
		    if (preg_match("/vendor=\"(.*?)\"/",$out,$found)) $vendor = $found[1];
		    if (preg_match("/os=\"(.*?)\"/",$out,$found)) $os = $found[1];
            if (preg_match_all("/port=\"(.*?)\"/",$out,$found)) {
            	foreach ($found[1] as $port) {
            		$regs = explode("|",$port);
            		$services[$regs[0] . "/" . $regs[1]] = array(
	                    "port" => $regs[0],
	                    "proto" => $regs[1],
	                    "state" => $regs[2],
	                    "service" => $regs[3],
	                    "version" => $regs[4]
	                );
            	}
            }
	        if (trim($ip)!="") 
		        $this->scan[$ip] = array(
	                "ip" => $ip,
	                "mac" => $mac,
	                "mac_vendor" => $vendor,
	                "os" => $os,
	                "services" => $services
	            );
		    if (preg_match("/ackend$/",$out)) break;
	    }
		// show results
		$this->print_scan();
		socket_close($socket);
    }

    function delete_scan($report) {
    	// prepare to launch nmap
		$socket = socket_create(AF_INET, SOCK_STREAM, 0);
	    if ($socket < 0) {
	        $this->errmsg = _("Can't connect with frameworkd").": ". socket_strerror($socket);
	        return false;
	    }
	    /* connect */
	    $result = @socket_connect($socket, $this->address, $this->port);
	    if (!$result) {
	        $this->errmsg = _("Can't connect with frameworkd")." ($this->address:$this->port)";
	        return false;
	    }
	    socket_set_block($socket);
		socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,$this->timeout);
	    /* first send nmap_report_list */
	    $in = 'control action="nmap_report_delete" id="'.$this->id.'" path="'.$report.'"' . "\n";
	    $out = '';
	    socket_write($socket, $in, strlen($in));
	    $out = @socket_read($socket, 2048, PHP_NORMAL_READ);
	    if (!preg_match("/ack(end)?$/",$out)) {
	        $this->errmsg = _("Bad response from frameworkd").": $out";
	        return false;
	    }
		socket_close($socket);
    }
    
	function print_scan() {
        foreach($this->scan as $host) {
            $ip = $host["ip"];
            if (trim($ip)=="") continue;
            $resolv_ip = gethostbyaddr($ip);
            echo "<img src='../pixmaps/arrow-315-small.png' align='absmiddle'/>";
            echo "<b>$ip";
            if ($ip != $resolv_ip) echo " ($resolv_ip)";
            echo "</b>.. <br/>\n";
	        if ($this->scan[$ip]["os"]) {
	            echo "&nbsp;&nbsp;&nbsp;&nbsp;";
	            echo "OS: " . $this->scan[$ip]["os"];
	            echo "<br/>";
	        }
	
	        if ($this->scan[$ip]["mac"]) {
	            echo "&nbsp;&nbsp;&nbsp;&nbsp;";
	            echo "MAC: " . $this->scan[$ip]["mac"]." ".($this->scan[$ip]["mac_vendor"]);
	            echo "<br/>";
	        }
	        if ($this->scan[$ip]["services"]) {
	            echo "&nbsp;&nbsp;&nbsp;&nbsp;";
	            echo "Services: ";
	            foreach ($this->scan[$ip]["services"] as $k => $service) {
	                echo $k . "&nbsp;";
	            }
	            echo "<br/>";
	        }
	        echo "<hr/>";
        }
	}
	
    function err() { 
    	return $this->errmsg;
    }
    function save_scan() {
        $_SESSION["_scan"] = $this->scan;
    }
    function get_scan() {
        if (isset($_SESSION["_scan"])) {
            return $_SESSION["_scan"];
        } else {
            return $this->scan;
        }
    }
    function del_scan() {
        if (isset($_SESSION["_scan"])) {
            unset($_SESSION["_scan"]);
        }
    }
}
?>
