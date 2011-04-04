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
require_once ("classes/Session.inc");
require_once ("classes/Security.inc");
require_once ("classes/Util.inc");
$what = GET('what');
$back = GET('back');
ossim_valid($what, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("What"));
ossim_valid($back, OSS_TEXT, OSS_PUNC_EXT, 'illegal:' . _("back"));
if (ossim_error()) {
    die(ossim_error());
}
/* what to reload... */
if (empty($what)) $what = 'all';

if ($what == "policies") {
	Session::logcheck("MenuIntelligence", "PolicyPolicy");
} elseif ($what == "hosts") {
	Session::logcheck("MenuPolicy", "PolicyHosts");
} elseif ($what == "nets") {
	Session::logcheck("MenuPolicy", "PolicyNetworks");
} else {
	Session::logcheck("MenuConfiguration", "PolicyServers"); // Who manage server can reload server conf
}

require_once ('ossim_conf.inc');
$ossim_conf = $GLOBALS["CONF"];
/* get the port and IP address of the server */
$address = $ossim_conf->get_conf("server_address");
$port = $ossim_conf->get_conf("server_port");
/* create socket */
$socket = socket_create(AF_INET, SOCK_STREAM, 0);
if ($socket < 0) {
    printf(gettext("socket_create() failed: reason: %s\n") , socket_strerror($socket));
}
/* connect */
$result = socket_connect($socket, $address, $port);
if ($result < 0) {
    printf(gettext("socket_connect() failed: reason: %s %s\n") , $result, socket_strerror($result));
}
$in = 'connect id="1" type="web"' . "\n";
$out = '';
socket_write($socket, $in, strlen($in));
$out = socket_read($socket, 2048);
if (strncmp($out, 'ok id="1"', 9) != 0) {
    // If the server is down / unavailable, clear the need to reload
    // Switch off web indicator
    require_once ('classes/WebIndicator.inc');
    if ($what == "all") {
        WebIndicator::set_off("Reload_policies");
        WebIndicator::set_off("Reload_hosts");
        WebIndicator::set_off("Reload_nets");
        WebIndicator::set_off("Reload_sensors");
        WebIndicator::set_off("Reload_plugins");
        WebIndicator::set_off("Reload_directives");
        WebIndicator::set_off("Reload_servers");
    } else {
        WebIndicator::set_off("Reload_" . $what);
    }
    // Reset main indicator if no more policy reload need
    if (!WebIndicator::is_on("Reload_policies") && !WebIndicator::is_on("Reload_hosts") && !WebIndicator::is_on("Reload_nets") && !WebIndicator::is_on("Reload_sensors") && !WebIndicator::is_on("Reload_plugins") && !WebIndicator::is_on("Reload_directives") && !WebIndicator::is_on("Reload_servers")) {
        WebIndicator::set_off("ReloadPolicy");
    }
    // update indicators on top frame
    $OssimWebIndicator->update_display();
    echo gettext("Error connecting to server") . " ...\n";
    echo "<p><b>"._("socket error")."</b>: " . gettext("Is OSSIM server running at") . " $address:$port?</p>";
    exit;
}
$in = 'reload-' . $what . ' id="2"' . "\n";
$out = '';
socket_write($socket, $in, strlen($in));
$out = socket_read($socket, 2048);
if (strncmp($out, 'ok id="2"', 9) != 0) {
    echo gettext("Bad response from server") . " ...\n";
    echo "<p><b>"._("socket error")."</b>: " . gettext("Is OSSIM server running at") . " $address:$port?</p>";
    exit;
}
socket_close($socket);
// Switch off web indicator
require_once ('classes/WebIndicator.inc');
if ($what == "all") {
    WebIndicator::set_off("Reload_policies");
    WebIndicator::set_off("Reload_hosts");
    WebIndicator::set_off("Reload_nets");
    WebIndicator::set_off("Reload_sensors");
    WebIndicator::set_off("Reload_plugins");
    WebIndicator::set_off("Reload_directives");
    WebIndicator::set_off("Reload_servers");
} else {
    WebIndicator::set_off("Reload_" . $what);
}
// Reset main indicator if no more policy reload need
if (!WebIndicator::is_on("Reload_policies") && !WebIndicator::is_on("Reload_hosts") && !WebIndicator::is_on("Reload_nets") && !WebIndicator::is_on("Reload_sensors") && !WebIndicator::is_on("Reload_plugins") && !WebIndicator::is_on("Reload_directives") && !WebIndicator::is_on("Reload_servers")) {
    WebIndicator::set_off("ReloadPolicy");
}
// update indicators on top frame
$OssimWebIndicator->update_display();
Util::clean_json_cache_files();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body><br>
<?php
include ("../hmenu.php"); ?>
  <p> <?php echo gettext("Reload completed successfully"); ?> </p>
<?php
$location = urldecode($back);
sleep(2);
echo "<script> window.location='$location'; </script>";
?>
</body>
</html>


