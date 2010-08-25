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
require_once 'classes/Session.inc';
Session::logcheck("MenuControlPanel", "BusinessProcesses");
$view = intval(GET('view'));
?>
<html>
<head>
  <title> <?php echo gettext("Risk Maps"); ?> </title>
</head>
<frameset rows="35,*" border="0" frameborder="0">
	<frame src="top.php?<?=$_SERVER["QUERY_STRING"]?>" scrolling="no" marginwidth=0 marginheight=0>
	<frame src="<?= ($view==1) ? "view.php" : ($view==2 ? "changemap.php" : "index.php") ?>" name="rmap" marginwidth=0 marginheight=0>
</frameset>
</html>

