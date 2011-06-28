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
* - match_os()
* Classes list:
*/
ob_implicit_flush();
require_once ('classes/Session.inc');
require_once ('classes/Host.inc');
require_once ('classes/Protocol.inc');
require_once ('ossim_db.inc');
function scanning_now($ip) {
	$cmd = "ps ax | grep nmap | grep $ip | grep -v grep";
	$output = explode("\n",`$cmd`);
	return (preg_match("/nmap/",$output[0])) ? 1 : 0;
}
Session::logcheck("MenuPolicy", "PolicyHosts");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>
<div id="content"></div><div id="progress"></div>
</body>
</html>
<?php
session_write_close();
$ip = GET('ip');
ossim_valid($ip, OSS_IP_ADDR, 'illegal:' . _("Ip Address"));
if (ossim_error()) {
    die(ossim_error());
}
// Kill nmap
if (GET('action') == "stop") {
	$cmd = "ps ax | grep -v 'grep' | grep '$ip' | grep nmap";
    $fp = popen("$cmd 2>&1", "r");
    $pids = "";
    while (!feof($fp)) {
        $line = trim(fgets($fp));
        $value = explode(" ", $line);
        if ($value[0] != "") $pids.= " " . $value[0];
    }
    fclose($fp);
    $cmd = "kill -9 $pids";
    if (preg_match("/^kill \-9\s+\d+$/",$cmd)) {
    	system($cmd);
    	unlink("/tmp/nmap_scan_$ip.log");
		?><script type="text/javascript">parent.location.href='modifyhostform.php?ip=<?php echo $ip ?>&withoutmenu=<?php echo intval(GET('withoutmenu'))?>'</script><?php
		exit;
    } else {
    	echo "nmap process not found";
    }
}
// Get nmap status for IP
$reload = false;
$cmd = "";
if (scanning_now($ip)) {
	$reload = true;
	?><script type="text/javascript">document.getElementById('content').innerHTML = "[<a href='nmap_process.php?ip=<?php echo $ip ?>&action=stop'>Stop</a>] Running Nmap for <?php echo $ip ?> <img src='../pixmaps/loading.gif' align='absmiddle' width='16'>";</script><?php
	while (scanning_now($ip)) {
		if (file_exists("/tmp/nmap_scan_$ip.log")) {
			$lines = file("/tmp/nmap_scan_$ip.log");
			$perc = 0;
			foreach ($lines as $line) {
				if (preg_match("/About\s+(\d+\.\d+)\%/",$line,$found)) {
					$perc = $found[1];
				}
			}
			if ($perc > 0) {
				?><script type="text/javascript">document.getElementById('progress').innerHTML = "<?php echo $found[1] ?>%";</script><?php
			}
		}
		sleep(3);
	}
}

if ($reload && file_exists("/tmp/nmap_scan_$ip.log")) {
	$db    = new ossim_db();
	$conn  = $db->connect();
	// load protocol ids
	$protocol_ids = array();
	if($protocol_list = Protocol::get_list($conn)) {
	    foreach($protocol_list as $protocol_data) {
	        $protocol_ids[$protocol_data->get_name()] = $protocol_data->get_id(); 
	    }
	}
	$lines = file("/tmp/nmap_scan_$ip.log");
    foreach($lines as $line)
	{
        preg_match('/(\S+)\s+open\s+([\w\-\_\?]+)(\s+)?(.*)$/', $line, $regs);
        
		if (isset($regs[0]))
		{
            list($port, $protocol) = explode("/", $regs[1]);
            $protocol = ($protocol_ids[strtolower(trim($protocol))] != "") ? $protocol_ids[strtolower(trim($protocol))] : "0";
            			
            $service = $regs[2];
            $service_type = $regs[2];
            $version = $regs[4];
            $origin = 1;
            $date = strftime("%Y-%m-%d %H:%M:%S");
            Host_services::insert($conn, $ip, $port, $date, $_SERVER["SERVER_ADDR"], $protocol, $service, $service_type, $version, $origin); // origin = 0 (pads), origin = 1 (nmap)
        }
    }
    unlink("/tmp/nmap_scan_$ip.log");
    ?><script type="text/javascript">parent.location.href='modifyhostform.php?ip=<?php echo $ip ?>&withoutmenu=<?php echo intval(GET('withoutmenu'))?>'</script><?php
}

// Case: Scan is done
?><script type="text/javascript">document.getElementById('content').innerHTML = "[<a href='modifyhostform.php?ip=<?php echo $ip ?>&withoutmenu=<?php echo intval(GET('withoutmenu'))?>' target='_parent'>Reload</a>] Nmap is done for <?php echo $ip ?>";</script>
