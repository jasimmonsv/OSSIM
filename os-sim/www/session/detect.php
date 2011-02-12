<?php
/*****************************************************************************
*
*    License:
*
*   Copyright (c) 2007-2011 AlienVault
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
* - errorMsg
* - cleanError
* Classes list:
*/
//
ob_implicit_flush();
require_once "classes/Security.inc";
require_once "classes/Session.inc";
require_once "classes/Plugin.inc";
Session::useractive("../session/login.php");
require_once "ossim_db.inc";
require_once "ossim_conf.inc";
$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);
$opensource = (!preg_match("/pro|demo/i",$version)) ? true : false;
$demo = (preg_match("/.*demo.*/i",$version)) ? true : false;
if ($opensource || $_SESSION['_user'] != ACL_DEFAULT_OSSIM_ADMIN) {
	die(_("You're not allowed to see this page"));
}
//
$ip = GET('ip');
$activate = intval(GET('activate'));
ossim_valid($ip, OSS_IP_ADDR, 'illegal:' . _("IP"));
if (ossim_error()) {
	die(ossim_error());
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> AlienVault Unified SIEM. <?php echo gettext("Customize Wizard"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="Shortcut Icon" type="image/x-icon" href="../favicon.ico">
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<link rel="stylesheet" type="text/css" href="../style/customize.css"/>
</head>
<body style="text-align:left">
    <pre style="width:93%">
<?php
        if ($activate) {
            // Launch script with ip and plugin file
            foreach ($_GET as $k => $v) if (preg_match("/\.cfg/",$v)) {
                if (file_exists($v)) {
                    echo "\n-- "._("Activating plugin")." $v ($ip)\n\n";
                    $f = popen("sudo /usr/share/ossim/scripts/detect.pl $ip $v 2>&1","r");
                    while (!feof($f)) {
                        $line = fgets($f);
                        echo "$line"; flush(); ob_flush();
                    }
                    pclose($f);
                } else {
                    echo "\n-- "._("Can't activating plugin")." $v ("._("File not found")."\n\n";
                }
            }
    ?>
    </pre><br/>
<?php
        } else {
            $plugins=0;
            $plugs=array();
            $f = popen("sudo /usr/share/ossim/scripts/detect.pl $ip 2>&1","r");
            while (!feof($f)) {
                $line = fgets($f);
                echo "$line"; flush(); ob_flush();
                if ($plugins && preg_match("/^\[/",$line)) $plugins=0;
                if ($plugins) {
                    if (preg_match("/.*Plugin (.*?): Matched (\d+)/",$line,$found)) {
                        $plugs[$found[1]] = $found[2];
                    }
                }
                if (preg_match("/Top \d+ matching plugins/",$line)) $plugins=1;
            }
            pclose($f);
    ?>
    </pre><br/>
    <?php
            if (count($plugs)>0) {
                $db   = new ossim_db();
                $conn = $db->connect();
                echo "<form action='detect.php' method='get'><input type='hidden' name='ip' value='$ip'><input type='hidden' name='activate' value='1'>\n";
                foreach ($plugs as $plg) {
                    $plugin_name = str_replace(".cfg","",$plg);
                    $rp = Plugin::get_list($conn,"WHERE name='$plugin_name'");
                    if (isset($rp[0]))
                        echo "<input type='radio' name='$plugin_name' value='$plg'> ".$plugin_name.": ".$rp[0]->get_description()."\n";
                    else
                        echo "<input type='radio' name='$plugin_name' value='$plg'> ".$plugin_name."\n";                
                }
                echo "<input type='button' class='lbutton' value='"._("Activate selected plugins")."'>\n";
                echo "</form>\n";
                $db->close($conn);
            } else {
                echo _("Sorry, Seems that $ip doesn't send logs. No plugins found")."<br/><br/>";
            }
        }
    ?>
</body>
</html>