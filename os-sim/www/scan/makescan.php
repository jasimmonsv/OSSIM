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
Session::logcheck("MenuConfiguration", "ConfigurationHostScan");
?>

<?php
/* TODO: define internal net */
$DEFAULT_TARGET = "192.168.0.0/24";
require_once 'classes/Security.inc';
$scan = POST('scan');
ossim_valid($scan, OSS_ALPHA, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("Scan"));
if (ossim_error()) {
	die(ossim_error());
}
if ($scan) {
    require_once ('classes/Scan.inc');
    require_once 'ossim_db.inc';
    require_once 'ossim_conf.inc';
    $db = new ossim_db();
    $conn = $db->connect();
    $conf = $GLOBALS["CONF"];
    $target = POST('target');
    ossim_valid($confirm, OSS_ALPHA, OSS_PUNC, 'illegal:' . _("Scan target"));
    if (ossim_error()) {
        die(ossim_error());
    }
    $target = escapeshellcmd($target);
    $nmap = $conf->get_conf("nmap_path");
    $ips = shell_exec("$nmap -sP -v -n $target");
    $ip_list = explode("\n", $ips);
?>

        <a href="scan.php"> <?php
    echo gettext("Back"); ?> </a><br><br>

<?php
    foreach($ip_list as $line) {
        $pattern = "/Host ([^\s]+)/";
        if (preg_match_all($pattern, $line, $regs)) {
            $ip = $regs[1][0];
        }
        $pattern = "/appears to be up/";
        if (preg_match_all($pattern, $line, $regs)) {
            echo "Host $ip appears to be up<br/>";
            if (Scan::in_scan($conn, $ip)) {
                if (!Scan::is_active($conn, $ip)) {
                    Scan::active($conn, $ip);
                }
            } else {
                Scan::insert($conn, $ip, 1);
            }
        }
        $pattern = "/appears to be down/";
        if (preg_match_all($pattern, $line, $regs)) {
            echo "Host $ip appears to be down<br/>";
            if (Scan::in_scan($conn, $ip)) {
                if (Scan::is_active($conn, $ip)) {
                    Scan::disactive($conn, $ip);
                }
            } else {
                Scan::insert($conn, $ip, 0);
            }
        }
    }
    $db->close($conn);
    exit;
}
?>

    <table>
    <form method="post" action="<?php
echo $_SERVER["SCRIPT_NAME"] ?>">
      <tr>
        <td>
          Range: 
          <input type="text" name="target" 
            value="<?php
echo $DEFAULT_TARGET ?>">
        </td>
        <td>
            <input type="submit" name="scan" value="Ping Scan">
        </td>
      </tr>
    </form>

    <!-- use host insert form -->
    <form method="post" action="../host/newhostform.php">
      <tr>
        <td>
          Range: 
          <input type="text" name="target" 
            value="<?php
echo $DEFAULT_TARGET ?>">
        </td>
        <td>
            <input type="submit" name="scan" value="Scan & Update DB">
        </td>
      </tr>
    </form>
    </table>

