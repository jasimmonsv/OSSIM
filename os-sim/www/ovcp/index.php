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
header('P3P: CP="CAO PSA OUR"');
require_once ('classes/Session.inc');
require_once ('classes/Util.inc');
Session::logcheck("MenuMonitors", "MonitorsVServers");
?>

<html>
<head>
  <title> <?php
echo gettext("OSSIM"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>
<frameset cols="100%" border="0" frameborder="0">

<?php
$rpath = $opath = "";
if (isset($_GET['rpath'])) $rpath = $_GET['rpath'];
require_once ("classes/Security.inc");
require_once ('ossim_conf.inc');
$conf = $GLOBALS["CONF"];
$header['Content-Dispostition'] = "form-data; name=\"login\"\r\n";
if ($rpath == "") {
    $fr_down = $conf->get_conf("ovcp_link") . "/openvcp/auth/submit";
    $page = Util::ossim_http_request($fr_down, $header, "POST", "loginname=Admin&password=&login=login");
    $fr_down = $conf->get_conf("ovcp_link") . "/openvcp/super/nodes";
    $page = Util::ossim_http_request($fr_down, $header, "GET");
} else {
    $fr_down = $conf->get_conf("ovcp_link") . "/openvcp/auth/submit";
    //$page=ossim_http_request($fr_down, $header, "POST", $data_string);
    $page = Util::ossim_http_request($conf->get_conf("ovcp_link") . "/openvcp" . $rpath);
}
$page = str_replace("/vsadmin/themes/default/theme.css", $conf->get_conf("ovcp_link") . "/themes/default/theme.css", $page);
$page = str_replace("/vsadmin/mods/super/nodes/main.js", $conf->get_conf("ovcp_link") . "/mods/super/nodes/main.js", $page);
$page = str_replace("<img src=/vsadmin/openvcp", "<img src=" . $conf->get_conf("ovcp_link") . "/openvcp", $page);
$page = str_replace("<img src=/vsadmin/themes/default/", "<img src=" . $conf->get_conf("ovcp_link") . "/themes/default/", $page);
$page = str_replace("<div id=\"bannerFrame\">
                &nbsp;
        </div>", "", $page);
echo $page;
?>


</body>
</html>
