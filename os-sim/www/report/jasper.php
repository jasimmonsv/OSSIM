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
Session::useractive();
?>

<html>
<head>
<title> <?php echo gettext("OSSIM Framework"); ?> </title>
</head>

<?php
require_once 'classes/Security.inc';
require_once ('ossim_conf.inc');

$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);
$opensource = (!preg_match("/pro|demo/i",$version)) ? true : false;
#
$mode = GET('mode');
ossim_valid($mode, OSS_ALPHA, 'illegal: mode');
if (ossim_error()) {
    die(ossim_error());
}
//$report = ($mode=="config") ? "Parameters" : 
$report = ($opensource) ? "Reporting+Server" : "OSReports";
if ($mode=="advanced") $mode="manager";
$link = "jasper_$mode.php";
?>

<frameset rows="35,*" border="0" frameborder="0">
<frame src="jasper_top.php?hmenu=Reporting+Server&smenu=<?=$report?>">
<frame src="<?=$link?>" name="report">
</frameset>

<body>
</body>
</html>

