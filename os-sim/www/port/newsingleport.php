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
Session::logcheck("MenuPolicy", "PolicyPorts");
?>

<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>
<?
if (!(GET('withoutmenu')==1 || POST('withoutmenu')==1)) include ("../hmenu.php"); 
?>
  <h1> <?php
echo gettext("New port"); ?> </h1>

<?php
require_once 'classes/Security.inc';
require_once 'ossim_db.inc';
require_once 'classes/Port.inc';
$db = new ossim_db();
$conn = $db->connect();
$port = POST('port');
$protocol = POST('protocol');
$service = POST('service');
$descr = POST('descr');
$arr_message = array();
$message = "";
ossim_valid($port, OSS_DIGIT, 'illegal:' . _("Port"));
if (ossim_error() || $port<0 || $port>65535) {
    ossim_set_error(false);
    $arr_message[] = _("Port number isn't valid");
}
ossim_valid($protocol, "tcp", "udp", 'illegal:' . _("Protocol"));
if (ossim_error()) {
    ossim_set_error(false);
    $arr_message[] = _("Protocol isn't valid");
}
ossim_valid($service, OSS_ALPHA, OSS_SPACE, OSS_PUNC, 'illegal:' . _("Service"));
if (ossim_error()) {
    ossim_set_error(false);
    $arr_message[] = _("Service isn't valid");
}
ossim_valid($descr, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_AT, OSS_NULLABLE, 'illegal:' . _("Description"));
if (ossim_error()) {
    ossim_set_error(false);
    $arr_message[] = _("Description isn't valid");
}
if (count($list_aux = Port::get_list($conn, $where = "WHERE port_number='$port' AND protocol_name=\"$protocol\"")) > 0) {
	$arr_message[] = _("This port already exists");
}
$message = implode("<br>",$arr_message);
if ($message!="") {
    echo "<center>";
    echo "$message<br><br>";
    echo "<form method=\"post\" action=\"newsingleportform.php\">";
    if (POST('withoutmenu')==1) {
           echo "<input type=\"hidden\" name=\"withoutmenu\" value=\"1\">";
    }
    if(!preg_match("/port/i", $message)) echo "<input type=\"hidden\" name=\"port\" value=\"".$port."\">";
    if(!preg_match("/protocol/i", $message)) echo "<input type=\"hidden\" name=\"protocol\" value=\"".$protocol."\">";
    if(!preg_match("/service/i", $message)) echo "<input type=\"hidden\" name=\"service\" value=\"".$service."\">";
    if(!preg_match("/description/i", $message)) echo "<input type=\"hidden\" name=\"descr\" value=\"".$descr."\">";
    echo "<input type=\"submit\" value=\""._("Back")."\">";
    echo "<form>";
    echo "</center>";
    echo "</body>";
    echo "</html>";
	$db->close($conn);
    exit(0);
}
if (POST('insert')) {
    Port::insert($conn, $port, $protocol, $service, $descr);
	$db->close($conn);
}

?>
    <p> <?php
echo gettext("Port succesfully inserted"); ?> </p>
    <? if ($_SESSION["menu_sopc"]=="Ports") { ?><script>document.location.href="port.php"</script><? } ?>

</body>
</html>
