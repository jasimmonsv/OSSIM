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
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
require_once ('classes/Session.inc');
Session::logcheck("MenuPolicy", "PolicyServers");
require_once 'ossim_db.inc';
require_once 'ossim_conf.inc';
require_once 'classes/Databases.inc';
require_once 'classes/Security.inc';
$name = GET('name');
ossim_valid($name, OSS_ALPHA, OSS_PUNC, OSS_SPACE, 'illegal:' . _("Database server name"));
if (ossim_error()) {
    die(ossim_error());
}
if ($name == "local") {
	header("Content-type: image/png");
	$image = imagecreatefrompng("../forensics/images/home.png");
	if(imageistruecolor($image)) {
		imageAlphaBlending($image, false);
		imageSaveAlpha($image, true);
	}
	imagepng($image);
	imagedestroy($image);
	exit();
}
$db = new ossim_db();
$conn = $db->connect();
$server_list = Databases::get_list($conn, "WHERE name = '$name' or ip='$name'");
$db->close($conn);
if ($server_list[0]) {
	header("Content-type: image/png");
	$image = @imagecreatefromstring($server_list[0]->get_icon());
	if (!$image) $image = @imagecreatefrompng("../forensics/images/server.png");
	if (imageistruecolor($image)) {
		imageAlphaBlending($image, false);
		imageSaveAlpha($image, true);
	}
	imagepng($image);
	imagedestroy($image);
}
?>


