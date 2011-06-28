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
*/
header("Expires: Mon, 20 Mar 1998 12:01:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
ob_implicit_flush();
require_once 'classes/Session.inc';
include("riskmaps_functions.php");
Session::logcheck("MenuControlPanel", "BusinessProcesses");
require_once 'ossim_db.inc';
require_once 'classes/Security.inc';

$map          = $_GET["map"];
$print_inputs = ($_GET["print_inputs"] == "1") ? true : false;
$linked       = ($_GET["linked"] =="0" ? 0 : 1 ;


ossim_valid($map, OSS_DIGIT, OSS_ALPHA, ".",'illegal:'._("Map"));

if (ossim_error()) {
die(ossim_error());
}

$db     = new ossim_db();
$conn   = $db->connect();
$params = array($map);
$query  = "SELECT * FROM risk_indicators WHERE name <> 'rect' AND map= ? ";

if (!$rs = &$conn->Execute($query, $params))
    print $conn->ErrorMsg();
else 
{
    while (!$rs->EOF)
	{
		// Output format ID_1####DIV_CONTENT_1@@@@ID_2####DIV_CONTENT_2...
		echo "indicator".$rs->fields["id"] ?>####<?php print_indicator_content($conn,$rs,$linked) ?>@@@@<?php
        //if ($in_assets) echo $change_div;
        $rs->MoveNext();
    }
}

$query = "SELECT * FROM risk_indicators WHERE name = 'rect' AND map= ? ";
if (!$rs = &$conn->Execute($query, $params)) 
    print $conn->ErrorMsg();
else 
{
    while (!$rs->EOF){
		// Output format ID_1####DIV_CONTENT_1@@@@ID_2####DIV_CONTENT_2...
		echo "rect".$rs->fields["id"] ?>####<?php print_rectangle_content($conn,$print_inputs) ?>@@@@<?php
        //if ($in_assets) echo $change_div;
        $rs->MoveNext();
    }
}

$conn->close();
?>
