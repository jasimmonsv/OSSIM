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
* - __os2pixmap()
* - scan2html()
* Classes list:
*/
/*
* scan_util.php
*
* methods not included in Scan.inc go here.
*/
function __os2pixmap($os) {
    $pixmap_dir = "../pixmaps/";
    if (preg_match('/win/i', $os)) {
        return "<img src=\"$pixmap_dir/os/win.png\" alt=\"Windows\" />";
    } elseif (preg_match('/linux/i', $os)) {
        return "<img src=\"$pixmap_dir/os/linux.png\" alt=\"Linux\" />";
    } elseif (preg_match('/bsd/i', $os)) {
        return "<img src=\"$pixmap_dir/os/bsd.png\" alt=\"BSD\" />";
    } elseif (preg_match('/mac/i', $os)) {
        return "<img src=\"$pixmap_dir/os/mac.png\" alt=\"MacOS\" />";
    } elseif (preg_match('/sun/i', $os)) {
        return "<img src=\"$pixmap_dir/os/sunos.png\" alt=\"SunOS\" />";
    } elseif (preg_match('/solaris/i', $os)) {
        return "<img src=\"$pixmap_dir/os/sunos.png\" alt=\"Solaris\" />";
    }
}
function scan2html($scan) {
    $count = 0;
    $html = "<br/>";
    foreach($scan as $host) {
        $html.= "<tr>";
        $html.= "<td>" . $host['ip'] . "</td>\n";
        $html.= "<td>" . $host['mac'];
        $html.= "&nbsp;" . $host['mac_vendor'] . "</td>\n";
        $html.= "<td>" . $host['os'] . "&nbsp;";
        $html.= __os2pixmap($host['os']) . "&nbsp;</td>\n";
        $html.= "<td>";
        foreach($host["services"] as $k => $service) {
            $title = $service["port"] . "/" . $service["proto"] . " " . $service["version"];
            $html.= " <span title=\"$title\"> ";
            $html.= ($service["service"]!="") ? $service["service"] : $k;
            $html.= "</span>&nbsp;";
        }
        $html.= "&nbsp</td>\n";
        $html.= "<td><input CHECKED type=\"checkbox\" 
                value=\"" . $host['ip'] . "\" name=\"ip_$count\"/></td>\n";
        $html.= "</tr>";
        $count+= 1;
    }
    echo <<<EOF
    <form action="../host/newhostform.php" method="POST">
      <input type="hidden" name="scan" value="1" />
      <input type="hidden" name="ips" value="$count" />
    <a name="results">
    <table align="center">
      <tr>
EOF;
    echo "<th colspan='5'>" . gettext("Scan results") . "</th>";
    echo "</tr></tr>";
    echo "<th>" . gettext("Host") . "</th>";
    echo "<th>" . gettext("Mac") . "</th>";
    echo "<th>" . gettext("OS") . "</th>";
    echo "<th>" . gettext("Services") . "</th>";
    echo "<th>" . gettext("Insert") . "</th>";
    echo <<<EOF
      </tr>
      $html
      <tr></tr>
      <tr>
        <td colspan="5">
EOF;
    echo "<input type=\"submit\" class=\"btn\" value=\"" . gettext("Update database values") . "\" />";
    echo <<<EOF
        </td>
      </tr>
      <tr>
        <td colspan="5">
          <a href="../netscan/index.php?clearscan=1">
EOF;
    echo _('Clear scan result');
    echo <<<EOF
    </a>
        </td>
      </tr>
    </table>
    </form>
    <br/>
    <br/>
EOF;
    
}
?>
