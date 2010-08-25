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
Session::logcheck("MenuControlPanel", "ControlPanelMetrics");
require_once ('classes/Locale.inc');
require_once ('classes/Security.inc');
?>
<html>
<head>
  <title> <?php
echo gettext("Control Panel"); ?> </title>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" href="../style/style.css"/>
</head>

<body>

<?php
$backlog_id = GET('backlog_id');
ossim_valid($backlog_id, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("backlog_id"));
if (ossim_error()) {
    die(ossim_error());
}
$proto = "http";
if ($_SERVER['HTTPS'] == "on") $proto = "https";
require_once ("ossim_conf.inc");
$ossim_conf = $GLOBALS["CONF"];
$datapath = $ossim_conf->get_conf("ossim_link") . "/tmp/";
$javapath = $ossim_conf->get_conf("ossim_link") . "/java/";
$origpath = $ossim_conf->get_conf("ossim_link") . "/java/";
$base_dir = $ossim_conf->get_conf("base_dir");
$datapath = "$proto://$_SERVER[SERVER_ADDR]:$_SERVER[SERVER_PORT]/$datapath/$backlog_id.txt";
$imagepath = "$proto://$_SERVER[SERVER_ADDR]:$_SERVER[SERVER_PORT]/$javapath/images/";
$javapath = "$proto://$_SERVER[SERVER_ADDR]:$_SERVER[SERVER_PORT]/$javapath/";
?>
  <h1 align="center"> <?php
echo gettext("Alarm viewer"); ?> </h1>

<applet archive="<?php
echo $origpath; ?>/mm.mysql-2.0.14-bin.jar,<?php
echo $origpath; ?>/scanmap3d.jar" code="net.ossim.scanmap.OssimScanMap3DApplet" width="400" height="400" alt="Applet de prueba">
        <param name="dataUrl" value="<?php
echo $javapath; ?>/scanmap3d.conf">
        <param name="textFileDataUrl" value="<?php
echo $datapath; ?>">
        <param name="imagesBaseUrl" value="<?php
echo $imagepath; ?>">
</applet>


</body>
</html>


