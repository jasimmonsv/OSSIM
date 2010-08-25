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
Session::logcheck("MenuReports", "ReportsGLPI");
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
$rpath = "";
if (isset($_GET['rpath'])) $rpath = $_GET['rpath'];
require_once ("classes/Security.inc");
require_once ('ossim_conf.inc');
$conf = $GLOBALS["CONF"];
$header['Content-Dispostition'] = "form-data; name=\"log\"\r\n";
if (count($_POST) != 0 || (count($_POST) == 0 && count($_GET) == 0)) {
    $fr_down = $conf->get_conf("glpi_link");
    if (count($_POST) > 0) {
        foreach($_POST as $key => $value) $values[] = "$key=" . urlencode($value);
        $post = implode("&", $values);
        if (isset($rpath)) $fr_down.= "/" . $rpath;
    } else {
        Util::ossim_http_request($fr_down);
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $pass = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $conf->get_conf("md5_salt") , $_SESSION["mdspw"], MCRYPT_MODE_ECB, $iv);
        $post = "login_name=" . $_SESSION['_user'] . "&login_password=" . $pass;
        if (isset($rpath)) $fr_down.= "/login.php";
    }
    $page = Util::ossim_http_request($fr_down, $header, "POST", $post);
} else {
    if (isset($_GET['rpath'])) $fr_down = $conf->get_conf("glpi_link") . str_replace("/glpi", "", $_SERVER["REQUEST_URI"]);
    else $fr_down = $conf->get_conf("glpi_link");
    $page = Util::ossim_http_request($fr_down);
}
$page = str_replace(" src='image/", " src='" . $conf->get_conf("glpi_link") . "/image/", $page);
$page = str_replace(" href='/glpi/css/", " href='" . $conf->get_conf("glpi_link") . "/css/", $page);
$page = str_replace(" href='/glpi/css/", " href='" . $conf->get_conf("glpi_link") . "/css/", $page);
$page = str_replace(" src='/glpi/lib/", " src='" . $conf->get_conf("glpi_link") . "/lib/", $page);
$page = str_replace(" src=\"/glpi/lib/", " src=\"" . $conf->get_conf("glpi_link") . "/lib/", $page);
$page = str_replace(" src='/glpi/script.js", " src='" . $conf->get_conf("glpi_link") . "/script.js", $page);
echo $page;
?>


</body>
</html>
