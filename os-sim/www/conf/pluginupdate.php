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
Session::logcheck("MenuConfiguration", "ConfigurationPlugins");
?>

<html>
<head>
  <title> <?php echo gettext("Plugin Update"); ?> </title>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>
<?php
require_once ('ossim_db.inc');
require_once ('classes/Security.inc');
$id = REQUEST('id');
$sid = REQUEST('sid');
$priority = REQUEST('priority');
$reliability = REQUEST('reliability');
$name = REQUEST('name');
ossim_valid($id, OSS_ALPHA, 'illegal:' . _("id"));
ossim_valid($sid, OSS_ALPHA, 'illegal:' . _("sid"));
ossim_valid($priority, OSS_ALPHA, 'illegal:' . _("priority"));
ossim_valid($reliability, OSS_ALPHA, 'illegal:' . _("reliability"));
ossim_valid($name, OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("name"));
if (ossim_error()) {
    die(ossim_error());
}
if (($priority < 0) or ($priority > 5)) {
    echo "<p align=\"center\"> " . gettext("Priority must be between 0 and 5") . " </p>";
    echo "<p align=\"center\"><a href=\"pluginsid.php?id=$id\"> " . gettext("Back") . " </a></p>";
    exit();
}
if (($reliability < 0) or ($reliability > 10)) {
    echo "<p align=\"center\"> " . gettext("Reliability must be between 0 and 10") . " </p>";
    echo "<p align=\"center\"><a href=\"pluginsid.php?id=$id\"> " . gettext("Back") . " </a></p>";
    exit();
}
require_once ('classes/Plugin_sid.inc');
$db = new ossim_db();
$conn = $db->connect();
Plugin_sid::update($conn, $id, $sid, $priority, $reliability, $name);
$db->close($conn);
?>

<p align="center">
<?php echo gettext("Priority and reliability successfully updated <br/>"); ?>
<?php 
       $location = "pluginsid.php?id=$id";
       echo "<script> document.location.href='$location'; </script> ";
?>
</p>
</body>
</html>

