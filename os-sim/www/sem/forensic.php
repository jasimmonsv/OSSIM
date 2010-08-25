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
require_once 'classes/Security.inc';
Session::logcheck("MenuEvents", "ControlPanelSEM");
//require_once ("classes/Util.inc");
//include "charts.php";
//include charts.php to access the InsertChart function
$_SESSION['graph_type'] = GET('graph_type');
$_SESSION['cat'] = GET('cat');
ossim_valid($_SESSION['graph_type'], OSS_ALPHA, OSS_NULLABLE, OSS_PUNC, 'illegal:' . _("graph type"));
ossim_valid($_SESSION['cat'], OSS_ALPHA, OSS_NULLABLE, OSS_PUNC, 'illegal:' . _("cat"));
if (ossim_error()) {
    die(ossim_error());
}
//echo InsertChart("charts.swf", "charts_library", "forensic_source.php?" . $_SERVER["QUERY_STRING"], 950, 350, "#FFF");
?>
