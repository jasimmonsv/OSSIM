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
//require_once("classes/Session.inc");
if ($argv[1] != "") {
	$path_class = '/usr/share/ossim/include/:/usr/share/ossim/www/sem';
	ini_set('include_path', $path_class);
}
require_once("classes/Security.inc");
//Session::logcheck("MenuControlPanel", "ControlPanelSEM");
$uniqueid = ($argv[1] != "") ? $argv[1] : $_GET["uniqueid"];
$ips = $_GET['ips'];
ossim_valid($uniqueid, OSS_ALPHA, OSS_DIGIT, OSS_DOT, 'illegal:' . _("uniqueid"));
ossim_valid($ips, OSS_DIGIT, OSS_DOT, ',', OSS_NULLABLE, 'illegal:' . _("ips"));
if (ossim_error()) {
    die(ossim_error());
}
if ($ips != "") {
	$cmd = "sudo ./fetchremote_kill.pl $ips '$uniqueid'";
	system($cmd);
} else {
	if ($uniqueid != "") {
	    $pids = "";
	    $cmd = "ps ax | grep -v 'grep' | grep '$uniqueid'";
	    $fp = popen("$cmd 2>&1", "r");
	    while (!feof($fp)) {
	        $line = trim(fgets($fp));
	        echo _("Found")." $line\n";
	        $value = explode(" ", $line);
	        if ($value[0] != "") $pids.= " " . $value[0];
	    }
	    fclose($fp);
	    // also cat / tac
	    $cmd = "ps ax | grep -v 'grep' | egrep 'cat \/|tac \/|perl'";
	    $fp = popen("$cmd 2>&1", "r");
	    while (!feof($fp)) {
	        $line = trim(fgets($fp));
	        echo _("Found")." $line\n";
	        $value = explode(" ", $line);
	        if ($value[0] != "") $pids.= " " . $value[0];
	    }
	    fclose($fp);
        //error_log("$pids\n",3,"/tmp/kills");
	    echo _("Killing pids")." $pids\n";
	    system("kill -9 $pids");
	}
}
?>