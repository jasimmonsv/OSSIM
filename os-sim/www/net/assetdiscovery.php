<?php
/*****************************************************************************
*
*    License:
*
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
require_once ('classes/Session.inc');
Session::logcheck("MenuConfiguration", "NetworkDiscovery");

require_once 'ossim_db.inc';
require_once 'ossim_conf.inc';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title> <?php echo $title ?> </title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<link rel="stylesheet" type="text/css" href="../style/style.css"/>
<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
</head>
<body style="margin:0px">
<?
if (GET('nohmenu') == "") { include ("../hmenu.php"); }

$network_auto_discovery = intval($_GET["network_auto_discovery"]);
ossim_valid($network_auto_discovery, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("network auto discovery"));
if (ossim_error()) {
    die(ossim_error());
}
$update = $_GET["update"];
ossim_valid($update, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("update"));
if (ossim_error()) {
    die(ossim_error());
}

$db = new ossim_db();
$dbconn = $db->connect();

if (intval($update)==1) {
    $query = "UPDATE config SET value = '$network_auto_discovery' WHERE conf='network_auto_discovery'";
    $result = $dbconn->Execute($query);
}

$query = "select value from config
            where conf='network_auto_discovery'";
$result = $dbconn->Execute($query);

echo "<form action=\"assetdiscovery.php\" method=\"get\">";
echo "<input type=\"hidden\" name=\"update\" value=\"1\"/>";
echo "<input type=\"hidden\" name=\"nohmenu\" value=\"".GET('nohmenu')."\"/>";
echo "<table width=\"100%\" class=\"transparent\">";
echo "<tr>";
echo "      <td class=\"nobborder\" style=\"text-align:center;padding-top:10px;\">";
echo "      <input type=\"checkbox\" name=\"network_auto_discovery\" value=\"1\" ".(($result->fields['value']=="1")? "checked":"")."/>";
echo "      &nbsp;"._("Network Auto Discovery");
echo "      </td>";
echo "</tr>";
echo "<tr>";
echo "      <td class=\"nobborder\" style=\"text-align:center;padding-top:10px;\">";
echo "      <input type=\"submit\" class='button' value=\""._("Update")."\"/>";
echo "      </td>";
echo "</tr>";
echo "</table>";
echo "</form>";

$db->close($dbconn);
 ?>
</body>
</html>
