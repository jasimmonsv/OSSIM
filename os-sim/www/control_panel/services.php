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
Session::logcheck("MenuEvents", "EventsAnomalies");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>
                                                                                
  <?php
include ("../hmenu.php"); ?>

<?php
require_once 'ossim_db.inc';
require_once 'classes/Host_services.inc';
require_once 'classes/Host.inc';
require_once 'classes/Util.inc';
require_once 'classes/Security.inc';
?>

<?php
$ROWS = 50;
$inf = GET('inf');
$sup = GET('sup');
$show_anom = GET('show_anom');
$ex_serv = GET('ex_serv');
$ex_servs = GET('ex_servs');
$ex_servp = GET('ex_servp');
$num = GET('num');
ossim_valid($inf, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("inf"));
ossim_valid($sup, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("order"));
ossim_valid($show_anom, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("show_anom"));
ossim_valid($ex_serv, OSS_IP_ADDR, OSS_NULLABLE, 'illegal:' . _("ex_serv"));
ossim_valid($ex_servs, OSS_IP_ADDR, OSS_NULLABLE, 'illegal:' . _("ex_servs"));
ossim_valid($ex_servp, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("ex_servp"));
ossim_valid($num, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("num"));
//casting
$inf = intval($inf);
$sup = intval($sup);
if (ossim_error()) {
    die(ossim_error());
}
if (empty($num)) $num = $ROWS;
if (empty($inf)) $inf = 0;
if ((empty($sup)) && ($num != "all")) $sup = $inf + $num;
?>            

<?php
$db = new ossim_db();
$conn = $db->connect();
if ($show_anom != "1") {
    $count = Host_services::get_list_count($conn);
    if ($num == "all") {
        $sup = $count;
        $inf = 0;
    }
    $Host_services_list = Host_services::get_list($conn, $inf, $sup);
} else {
    $Host_services_list = Host_services::get_anom_list($conn, "all");
    $count = count($Host_services_list);
    $sup = $count;
    $inf = 0;
}
?>

<?php
if ($show_anom != "1") { ?>
<form method="GET" action="services.php">
<?php
    echo gettext("Show"); ?>
<input type="hidden" name="inf" value="<?php
    echo $inf ?>"/>
<select name="num" onChange="submit()">
<option value="10"  <?php
    if ($num == "10") echo "SELECTED"; ?>>10</option>
<option value="50"  <?php
    if ($num == "50") echo "SELECTED"; ?>>50</option>
<option value="100" <?php
    if ($num == "100") echo "SELECTED"; ?>>100</option>
<option value="all" <?php
    if ($num == "all") echo "SELECTED"; ?>><?=_("All")?></option>
</select>
<?php
    echo gettext(" per page"); ?>
</form>
</br>
<?php
} ?>

<?php
if ($show_anom) echo "<a href=\"services.php\">" . gettext("Showing only anomalies, click here to see the complete services list") . "</a>";
else echo "<a href=\"services.php?show_anom=1\">" . gettext("Click here to see the only the anomalies") . "</a>";
?>
<form action="handle_services.php" method="GET">
<table width="100%">
<?php
if ($num != "all") { ?>
    <tr>
       <td colspan="12"> 
<?php
    $inf_link = $_SERVER["SCRIPT_NAME"] . "?sup=" . ($sup - $num) . "&inf=" . ($inf - $num) . "&num=" . $num;
    $sup_link = $_SERVER["SCRIPT_NAME"] . "?sup=" . ($sup + $num) . "&inf=" . ($inf + $num) . "&num=" . $num;
    $first_link = $_SERVER["SCRIPT_NAME"] . "?sup=" . $num . "&inf=" . "0" . "&num=" . $num;
    $last_link = $_SERVER["SCRIPT_NAME"] . "?sup=" . $count . "&inf=" . ($count - $num) . "&num=" . $num;
?>
    <table width="100%" bgcolor="#EFEFEF">    
    <colgroup span=3 width="33%"></colgroup>       
    <tr>   
    <td align=left>
    <?php
    if ($inf != "0") {
        echo "<a href=\"$first_link\">";
        printf(gettext("First"));
        echo "</a>";
    }
?>
    </td>
    <td align="center">
    <?php
    if ($inf >= $num) {
        echo "<a href=\"$inf_link\">&lt;-";
        printf(gettext("Prev %d") , $num);
        echo "</a>";
    }
?>
    <?php
    if ($sup < $count) {
        echo "&nbsp;&nbsp;(";
        printf(gettext("%d-%d of %d") , $inf + 1, $sup, $count);
        echo ")&nbsp;&nbsp;";
        echo "<a href=\"$sup_link\">";
        printf(gettext("Next %d") , $num);
        echo " -&gt;</a>";
    } else {
        echo "&nbsp;&nbsp;(";
        printf(gettext("%d-%d of %d") , $inf + 1, $count, $count);
        echo ")&nbsp;&nbsp;";
    }
?>
    </td>
    <td align="right">
    <?php
    if ($sup < $count) {
        echo "<a href=\"$last_link\">";
        printf(gettext("Last"));
        echo "</a>";
    }
?>
    </td>
    </tr>
    </table>
      </td></tr>

<?php
} ?>

<tr>
<td align="center" colspan="12">
<input type="hidden" name="back" value="<?php
echo urlencode($_SERVER["REQUEST_URI"]); ?>">
<input type="submit" class="btn" value=" <?php
echo gettext("OK"); ?> ">
<input type="reset" class="btn" value=" <?php
echo gettext("reset"); ?> "> </td>
</tr>
<tr>
<th><?php
echo "#"; ?></th>
<th><?php
echo "Host"; ?></th>
<th><?php
echo gettext("Sensor [interface]"); ?> </th>
<th><?php
echo gettext("Port"); ?></th>
<th><?php
echo gettext("Service [Version]"); ?> </th>
<th><?php
echo gettext("Date"); ?></th>
<th><?php
echo gettext("Previous Service [Version]"); ?> </th>
<th><?php
echo gettext("Previous Date"); ?> </th>
<th><?php
echo gettext("Delta"); ?> </th>
<th><?php
echo gettext("Ack"); ?> </th>
<th><?php
echo gettext("Ignore"); ?> </th>
</tr>

<?php
if ($Host_services_list) {
    $row = 0;
    $aux = 0;
    foreach($Host_services_list as $Host_services) {
?>

<tr <?php
        if (($Host_services["service"] != $Host_services["old_service"]) || ($Host_services["version"] != $Host_services["old_version"])) echo 'bgcolor="#f7a099"';
        else echo 'bgcolor="#bbcadd"';
?>>
<?php
        $delta = Util::date_diff($Host_services["date"], $Host_services["old_date"], 'yMdhms');
        if ($delta == "00:00:00") $delta = "-";
?>
<td>
<?php
        if (($ex_serv == $Host_services["ip"]) && ($ex_servs == $Host_services["sensor"]) && ($ex_servp == $Host_services["port"])) {
?>
<a href="<?php
            echo $_SERVER["SCRIPT_NAME"] . "?sup=" . $sup . "&inf=" . $inf . "&num=" . $num;
            if ($show_anom == "1") echo "&show_anom=1"
?>"><img src="../pixmaps/arrow.gif" border="0"></a>
<?php
        } else { ?>
<a href="<?php
            echo $_SERVER["SCRIPT_NAME"] . "?inf=" . $inf . "&sup=" . $sup . "&num=" . $num . "&ex_serv=" . $Host_services["ip"] . "&ex_servs=" . $Host_services["sensor"] . "&ex_servp=" . $Host_services["port"];
            if ($show_anom == "1") echo "&show_anom=1"; ?>"><img src="../pixmaps/arrow2.gif" border="0"></a>
<?php
        }
?>
</td>
<td><?php
        echo $Host_services["ip"]; ?></td>
<td><?php
        echo $Host_services["sensor"] . "[" . $Host_services["interface"] . "]"; ?>&nbsp;</td>
<td><?php
        echo $Host_services["port"]; ?>&nbsp;</td>
<td><?php
        echo $Host_services["service"] . "/" . getprotobynumber($Host_services["protocol"]) . " [" . $Host_services["version"] . "]"; ?>&nbsp;</td>
<td><?php
        echo $Host_services["date"]; ?>&nbsp;</td>
<td><?php
        echo $Host_services["old_service"] . "/" . getprotobynumber($Host_services["old_protocol"]) . " [" . $Host_services["old_version"] . "]"; ?>&nbsp;</td>
<td><?php
        echo $Host_services["old_date"] ?>&nbsp;</td>
<td><?php
        echo $delta; ?>&nbsp;</td>
<td>
<input type="checkbox" name="ip,<?php
        echo $Host_services["ip"]; ?>,<?php
        echo $Host_services["sensor"]; ?>,<?php
        echo $Host_services["date"]; ?>" value="<?php
        echo "ack" . $Host_services["ip"]; ?>" <?php
        if (($Host_services["protocol"] == $Host_services["old_protocol"]) && ($Host_services["version"] == $Host_services["old_version"])) echo "disabled" ?> >
</td>
<td>
<input type="checkbox" name="ip,<?php
        echo $Host_services["ip"]; ?>,<?php
        echo $Host_services["sensor"]; ?>,<?php
        echo $Host_services["old_date"]; ?>" value="<?php
        echo "ignore" . $Host_services["ip"]; ?>"
<?php
        if (($Host_services["protocol"] == $Host_services["old_protocol"]) && ($Host_services["version"] == $Host_services["old_version"])) echo "disabled" ?> >
</td>
</tr>
<?php
        if (($ex_serv == $Host_services["ip"]) && ($ex_servs == $Host_services["sensor"]) && ($ex_servp == $Host_services["port"])) {
            if ($Host_services_ip_list = Host_services::get_ip_list($conn, $Host_services["ip"], $Host_services["sensor"], $Host_services["port"])) {
                foreach($Host_services_ip_list as $Host_services_ip) {
                    $delta = Util::date_diff($Host_services_ip["date"], $Host_services_ip["old_date"], 'yMdhms');
                    if ($delta == "00:00:00") $delta = "-";
?>
	  <tr <?php
                    if (($Host_services_ip["service"] != $Host_services_ip["old_service"]) || ($Host_services_ip["version"] != $Host_services_ip["old_version"])) echo 'bgcolor="#eac3c3"';
                    else echo 'bgcolor="#dfe7f0"'; ?>>
	  <td>&nbsp;</td>
	  <td><?php
                    echo $Host_services_ip["ip"]; ?></td>
	  <td><?php
                    echo $Host_services_ip["sensor"] . "[" . $Host_services_ip["interface"] . "]"; ?></td>
	  <td><?php
                    echo $Host_services_ip["port"]; ?>&nbsp;</td>
      <td><?php
                    echo $Host_services_ip["service"] . "/" . getprotobynumber($Host_services_ip["protocol"]) . " [" . $Host_services_ip["version"] . "]"; ?>&nbsp;</td>
      <td><?php
                    echo $Host_services_ip["date"]; ?>&nbsp;</td>
      <td><?php
                    echo $Host_services_ip["old_service"] . "/" . getprotobynumber($Host_services_ip["old_protocol"]) . " [" . $Host_services_ip["old_version"] . "]"; ?>&nbsp;</td>
	  <td><?php
                    echo $Host_services_ip["old_date"] ?>&nbsp;</td>

<?php //comprobación protocol version
                     ?>
    
      <td><?php
                    echo $delta; ?>
      </td>
<td>
</td>
<td>
</td>
</tr>	  
<?php
                }
            }
        }
    }
}
$db->close($conn);
?>
<tr>
<td align="center" colspan="12">
<input type="submit" class="btn" value=" <?php
echo gettext("OK"); ?> ">
<input type="reset" class="btn" value=" <?php
echo gettext("reset"); ?> "></td>
</tr>

<?php
if ($num != "all") { ?>
     <tr>
        <td colspan="12">
<?php
    $inf_link = $_SERVER["SCRIPT_NAME"] . "?sup=" . ($sup - $num) . "&inf=" . ($inf - $num) . "&num=" . $num;
    $sup_link = $_SERVER["SCRIPT_NAME"] . "?sup=" . ($sup + $num) . "&inf=" . ($inf + $num) . "&num=" . $num;
    $first_link = $_SERVER["SCRIPT_NAME"] . "?sup=" . $num . "&inf=" . $inf . "&num=" . $num;
    $last_link = $_SERVER["SCRIPT_NAME"] . "?sup=" . $count . "&inf=" . ($count - $num) . "&num=" . $num;
?>

    <table width="100%" bgcolor="#EFEFEF">
    <colgroup span=3 width="33%"></colgroup>       
    <tr>
    <td align=left>
    <?php
    if ($inf != "0") {
        echo "<a href=\"$first_link\">";
        printf(gettext("First"));
        echo "</a>";
    }
?>
    </td>
    <td align="center">
    <?php
    if ($inf >= $num) {
        echo "<a href=\"$inf_link\">&lt;-";
        printf(gettext("Prev %d") , $num);
        echo "</a>";
    }
?>
    <?php
    if ($sup < $count) {
        echo "&nbsp;&nbsp;(";
        printf(gettext("%d-%d of %d") , $inf + 1, $sup, $count);
        echo ")&nbsp;&nbsp;";
        echo "<a href=\"$sup_link\">";
        printf(gettext("Next %d") , $num);
        echo " -&gt;</a>";
    } else {
        echo "&nbsp;&nbsp;(";
        printf(gettext("%d-%d of %d") , $inf + 1, $count, $count);
        echo ")&nbsp;&nbsp;";
    }
?>
    </td>
    <td align="right">
    <?php
    if ($sup < $count) {
        echo "<a href=\"$last_link\">";
        printf(gettext("Last"));
        echo "</a>";
    }
?>
    </td>
    </tr>
    </table>

        </td>
      </tr>

<?php
} ?>


</table>
</form>
</body>
</html>

