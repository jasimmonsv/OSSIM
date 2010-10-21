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
* - update_db()
* Classes list:
*/
// menu authentication
require_once ('classes/Session.inc');
Session::logcheck("MenuTools", "ToolsScan");
?>

<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>
<?php
include ("../hmenu.php");
/*
* scan_db.php
*
* Update ossim database with scan structure
*/
if (isset($_SESSION["_scan"])) {
    $scan = $_SESSION["_scan"];
    update_db($_POST, $scan);
    echo "<br/><a href=\"../netscan/index.php\">" . gettext("Return to Scan Results page") . "</a><br/>";
}
echo "<br/><a href=\"../host/host.php?hmenu=Assets&smenu=Assets\">" . gettext("Return to host's policy") . "</a>";
function update_db($global_info, $scan) {
    require_once 'ossim_db.inc';
    require_once 'classes/Host.inc';
    require_once 'classes/Host_scan.inc';
    require_once 'classes/Host_plugin_sid.inc';
    require_once 'classes/Host_services.inc';
    require_once 'classes/Host_group.inc';
    require_once 'classes/Host_group_scan.inc';
    require_once 'classes/Security.inc';
    $nagios = POST('nagios');
    $db = new ossim_db();
    $conn = $db->connect();
    $ips = $global_info["ips"];
    for ($i = 0; $i < $ips; $i++) {
        if ($ip = $global_info["ip_$i"]) {
            /* sensor info */
            $sensors = array();
            for ($j = 1; $j <= $global_info["nsens"]; $j++) {
                $name = "mboxs" . $j;
                if (isset($global_info[$name])) {
                    ossim_valid($global_info[$name], OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, OSS_SPACE, 'illegal:' . _("Policy id"));
                    if (ossim_error()) {
                        die(ossim_error());
                    }
                    $sensors[] = $global_info[$name];
                }
            }
            $hosts[] = $ip; //gethostbyaddr($ip);
            if (Host::in_host($conn, $ip)) {
                echo "* " . gettext("Updating ") . "$ip..<br/>";
                Host::update($conn, $ip, gethostbyaddr($ip) , $global_info["asset"], $global_info["threshold_c"], $global_info["threshold_a"], $global_info["rrd_profile"], 0, 0, $global_info["nat"], $sensors, $global_info["descr"], $scan["$ip"]["os"], $scan["$ip"]["mac"], $scan["$ip"]["mac_vendor"]);
                $os = $scan[$ip]["os"];
                $os_id = 0;
                if (preg_match('/win/i', $os)) {
                    $os_id = 1;
                } elseif (preg_match('/linux/i', $os)) {
                    $os_id = 2;
                } elseif (preg_match('/cisco/i', $os)) {
                    $os_id = 3;
                } elseif (preg_match('/freebsd/i', $os)) {
                    $os_id = 5;
                } elseif (preg_match('/netbsd/i', $os)) {
                    $os_id = 6;
                } elseif (preg_match('/openbsd/i', $os)) {
                    $os_id = 7;
                } elseif (preg_match('/hp-ux/i', $os)) {
                    $os_id = 8;
                } elseif (preg_match('/solaris/i', $os)) {
                    $os_id = 9;
                } elseif (preg_match('/macos/i', $os)) {
                    $os_id = 10;
                } elseif (preg_match('/plan9/i', $os)) {
                    $os_id = 11;
                } elseif (preg_match('/sco/i', $os)) {
                    $os_id = 12;
                } elseif (preg_match('/aix/i', $os)) {
                    $os_id = 13;
                } elseif (preg_match('/unix/i', $os)) {
                    $os_id = 14;
                }
                if ($os_id != 0) {
                    Host_plugin_sid::delete($conn, $ip, 5001);
                    Host_plugin_sid::insert($conn, $ip, 5001, $os_id);
                }
                Host_scan::delete($conn, $ip, 3001);
                if (isset($global_info["nessus"])) {
                    Host_scan::insert($conn, $ip, 3001, 0);
                }
            } else {
                echo "<font color=\"blue\">\n";
                echo "* " . gettext("Inserting ") . " $ip..<br/>\n";
                echo "</font>\n";
                Host::insert($conn, $ip, gethostbyaddr($ip) , $global_info["asset"], $global_info["threshold_c"], $global_info["threshold_a"], $global_info["rrd_profile"], 0, 0, $global_info["nat"], $sensors, $global_info["descr"], $scan[$ip]["os"], $scan[$ip]["mac"], $scan[$ip]["mac_vendor"]);
                $os = $scan[$ip]["os"];
                $os_id = 0;
                if (preg_match('/win/i', $os)) {
                    $os_id = 1;
                } elseif (preg_match('/linux/i', $os)) {
                    $os_id = 2;
                } elseif (preg_match('/cisco/i', $os)) {
                    $os_id = 3;
                } elseif (preg_match('/freebsd/i', $os)) {
                    $os_id = 5;
                } elseif (preg_match('/netbsd/i', $os)) {
                    $os_id = 6;
                } elseif (preg_match('/openbsd/i', $os)) {
                    $os_id = 7;
                } elseif (preg_match('/hp-ux/i', $os)) {
                    $os_id = 8;
                } elseif (preg_match('/solaris/i', $os)) {
                    $os_id = 9;
                } elseif (preg_match('/macos/i', $os)) {
                    $os_id = 10;
                } elseif (preg_match('/plan9/i', $os)) {
                    $os_id = 11;
                } elseif (preg_match('/sco/i', $os)) {
                    $os_id = 12;
                } elseif (preg_match('/aix/i', $os)) {
                    $os_id = 13;
                } elseif (preg_match('/unix/i', $os)) {
                    $os_id = 14;
                }
                if ($os_id != 0) {
                    Host_plugin_sid::delete($conn, $ip, 5001);
                    Host_plugin_sid::insert($conn, $ip, 5001, $os_id);
                }
                if (isset($global_info["nessus"])) {
                    Host_scan::insert($conn, $ip, 3001, 0);
                }
            }
            if (!empty($nagios)) {
                if (!Host_scan::in_host_scan($conn, $ip, 2007)) {
                    Host_scan::insert($conn, $ip, 2007, "", $ip, $global_info["sensors"], "");
                }
            } else {
                if (Host_scan::in_host_scan($conn, $ip, 2007)) Host_scan::delete($conn, $ip, 2007);
            }
            /* services */
            Host_plugin_sid::delete($conn, $ip, 5002);
            foreach($scan[$ip]["services"] as $port_proto => $service) {
                Host_services::insert($conn, $ip, $service["port"], strftime("%Y-%m-%d %H:%M:%S") , $_SERVER["SERVER_ADDR"], $service["proto"], $service["service"], $service["service"], $service["version"], 1);
                Host_plugin_sid::insert($conn, $ip, 5002, $service["port"]);
            }
            flush();
        }
    }
    // Insert group name
    $groupname = REQUEST("groupname");
    if (!empty($groupname) && !empty($hosts)) {
        ossim_valid(REQUEST("groupname") , OSS_ALPHA, OSS_SCORE, OSS_PUNC, OSS_AT);
        if (ossim_error()) {
            die(ossim_error());
        }
        if(count(Host_group::get_list($conn, "where name='$groupname'"))>0) {
            echo "<br>"._("The group name already exists")."<br>";
        }
        else {
            Host_group::insert($conn, $groupname, $global_info["threshold_c"], $global_info["threshold_a"], $global_info["rrd_profile"], $sensors, $hosts, $global_info["descr"]);
        }
        if (isset($global_info["nessus"])) {
            Host_group_scan::insert($conn, $groupname, 3001, 0);
        }
        if (isset($global_info["nagios"])) {
            Host_group_scan::insert($conn, $groupname, 2007, 0);
        }
    }
    $db->close($conn);
}
?>

</body>
</html>

