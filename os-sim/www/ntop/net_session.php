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
require_once ('classes/Session.inc');
Session::logcheck("MenuMonitors", "MonitorsSession");
?>

<?php
/*
* net argument in nmap format:
* example: ?net=192.168.1.1-255
*/
require_once ("classes/Security.inc");
$net = GET('net');
ossim_valid($net, OSS_ALPHA, OSS_PUNC, 'illegal:' . _("net"));
if (ossim_error()) {
    die(ossim_error());
}
/*
* get conf
* needed to get nmap path
*/
require_once ('ossim_conf.inc');
$conf = $GLOBALS["CONF"];
$nmap = $conf->get_conf("nmap_path");
/*
* connect to db
* needed to get ntop links associated with hosts
*/
require_once ('ossim_db.inc');
$db = new ossim_db();
$conn = $db->connect();
require_once ('classes/Host.inc');
require_once ('classes/Host_os.inc');
require_once ('classes/Sensor.inc');
/*
* convert net argument into an array of hosts
*/
$ip_string = shell_exec("$nmap -n -sL $net | grep Host | cut -f 2 -d \" \" ");
$ip_list = explode("\n", $ip_string);
array_pop($ip_list);
$found = 0; /* tcp session found in html page */
$show = 0; /* begin of print output  */
foreach($ip_list as $host) {
    /*
    * get ntop link associated with host
    */
    $ntop_link = Sensor::get_sensor_link($conn, $host);
    if ($conf->get_conf("use_ntop_rewrite")) {
        $protocol = "http";
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") $protocol = "https";
        $ntop_link = "$protocol://" . $_SERVER['SERVER_NAME'] . "/ntop-$sensor";
    }
    if ($fd = @fopen("$ntop_link/$host.html", "r")) {
        while (!feof($fd)) {
            $line = fgets($fd, 1024);
            /*
            * search for Sessions section
            */
            if (eregi(">Active.*Sessions<", $line)) {
                $found = 1;
            }
            /*
            * begin to print at the begin of <table>...
            */
            if ($found && eregi('<table', $line)) {
                $show = 1;
                $hostname = Host::ip2hostname($conn, $host);
                $os_pixmap = Host_os::get_os_pixmap($conn, $host);
                if (strcmp($hostname, $host)) $hostname.= " ($host)";
                echo <<<EOF
<HTML>
  <HEAD>
    <TITLE> 
EOF;    
    echo gettext("Active TCP Sessions");
echo <<<EOF
    </TITLE>
    <LINK REL=stylesheet HREF="$ntop_link/style.css" type="text/css">
  </HEAD>
  <BODY BGCOLOR="#FFFFFF" LINK=blue VLINK=blue>
    <H2 align="center">
      <a href="../report/index.php?section=usage&host=$host">$hostname</a>
      $os_pixmap
    </H2>
<CENTER>
EOF;
                
            }
            /*
            * </table> found, session section finished, stop printing
            */
            if ($found && eregi('</table', $line)) {
                $show = 0;
                $found = 0;
                echo <<<EOF
</CENTER>
    </TABLE>
    <BR/>
  </BODY>
</HTML>
EOF;
                
            }
            /*
            * print data, adjusting links
            */
            if ($show && $found) {
                $line = ereg_replace("<img src=\"", "<img src=\"$ntop_link", $line);
                $line = ereg_replace("<a href=\"", "<a href=\"$ntop_link", $line);
                echo $line;
            }
        }
        /*
        * next host!
        */
        fclose($fd);
        $found = 0;
        $show = 0;
    }
}
$db->close($conn);
?>

