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
Session::logcheck("MenuPolicy", "PolicyServers");
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
<?include ("../hmenu.php"); ?>

<h1> <?php
echo gettext("New server"); ?> </h1>

<?php
require_once 'classes/Security.inc';
$name = POST('name');
$ip = POST('ip');
$port = POST('port');
$descr = POST('descr');
$correlate = (POST('correlate')) ? 1 : 0;
$cross_correlate = (POST('cross_correlate')) ? 1 : 0;
$store = (POST('store')) ? 1 : 0;
$qualify = (POST('qualify')) ? 1 : 0;
$resend_events = (POST('resend_events')) ? 1 : 0;
$resend_alarms = (POST('resend_alarms')) ? 1 : 0;
$sign = (POST('sign')) ? 1 : 0;
$sem = (POST('sem')) ? 1 : 0;
$sim = (POST('sim')) ? 1 : 0;
ossim_valid($name, OSS_ALPHA, OSS_PUNC, OSS_SPACE, 'illegal:' . _("Server name"));
ossim_valid($ip, OSS_IP_ADDR, 'illegal:' . _("Ip address"));
ossim_valid($port, OSS_DIGIT, 'illegal:' . _("Port number"));
ossim_valid($descr, OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("Description"));
ossim_valid($correlate, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Correlation"));
ossim_valid($cross_correlate, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Cross Correlation"));
ossim_valid($store, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Store"));
ossim_valid($qualify, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Qualify"));
ossim_valid($resend_alarms, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Resend Alarms"));
ossim_valid($resend_events, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Resend Events"));
ossim_valid($sign, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Sign"));
ossim_valid($sem, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Logger"));
ossim_valid($sim, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("SIEM"));
if (ossim_error()) {
    die(ossim_error());
}
if (POST('insert')) {
    require_once 'ossim_db.inc';
    require_once 'classes/Server.inc';
    $db = new ossim_db();
    $conn = $db->connect();
    if(!isset($resend_alarms)) $resend_alarms = 0;
    if(!isset($resend_events)) $resend_events = 0;
    
    Server::insert($conn, $name, $ip, $port, $descr, $correlate, $cross_correlate, $store, $qualify, $resend_alarms, $resend_events, $sign, $sem, $sim);
    $db->close($conn);
}
?>
    <p> <?php
echo gettext("Server succesfully inserted"); ?> </p>
    <? if ($_SESSION["menu_sopc"]=="Servers") { ?><script>document.location.href="server.php"</script><? } ?>

<?php
// update indicators on top frame
$OssimWebIndicator->update_display();
?>
</body>
</html>
