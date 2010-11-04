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
require_once ('classes/Security.inc');
require_once 'ossim_db.inc';
require_once 'classes/Sensor.inc';
require_once 'classes/Util.inc';
Session::logcheck("MenuPolicy", "PolicySensors");
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" CONTENT="no-cache"/>
	<link type="text/css" rel="stylesheet" href="../style/style.css"/>
</head>
<body>
<?php
if (GET('withoutmenu') != "1") 
	include ("../hmenu.php"); ?>

<h1> <?php echo gettext("New sensor"); ?> </h1>

<?php

$name = POST('name');
$ip = POST('ip');
$port = POST('port');
$descr = POST('descr');
$priority = POST('priority');

ossim_valid($name, OSS_ALPHA, OSS_PUNC, OSS_SPACE, 'illegal:' . _("Sensor name"));
ossim_valid($ip, OSS_IP_ADDR, 'illegal:' . _("Ip address"));
ossim_valid($port, OSS_DIGIT, 'illegal:' . _("Port number"));
ossim_valid($descr, OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("Description"));
ossim_valid($priority, OSS_DIGIT, OSS_DOT, 'illegal:' . _("Priority"));

if (ossim_error()) {
    die(ossim_error());
}
if (POST('insert')) {
    $db = new ossim_db();
    $conn = $db->connect();
    Sensor::insert($conn, $name, $ip, $priority, $port, $descr);
    $db->close($conn);
    Util::clean_json_cache_files("sensors");
}
?>
    <p><?php echo gettext("Sensor succesfully inserted"); ?></p>
    <? if ($_SESSION["menu_sopc"]=="SIEM Components") { ?><script>setTimeout("document.location.href='sensor.php'",1000)</script><? } ?>

	<?php
	// update indicators on top frame
	$OssimWebIndicator->update_display();
	?>
	</body>
</html>
