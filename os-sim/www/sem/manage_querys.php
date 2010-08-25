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
require_once ('classes/Security.inc');
require_once 'classes/Session.inc';
Session::logcheck("MenuEvents", "ControlPanelSEM");
$query = GET("query");
$action = GET("action");
ossim_valid($query, OSS_TEXT, OSS_NULLABLE, 'illegal:' . _("query"));
ossim_valid($action, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("action"));
if (ossim_error()) {
    die(ossim_error());
}
$config = parse_ini_file("everything.ini");
$encoded = base64_encode($query);
$query_listing = $config["query_listing"];
if (!is_file($query_listing)) {
    //touch($query_listing);
}

//$querys = file_get_contents($query_listing);
$querys = "";
$query_array = split(" ", $querys);
$query_array = array_reverse($query_array);
if ($action == "get") {
    foreach($query_array as $fquery) {
        if ($fquery == "") {
            continue;
        }
        print base64_decode($fquery) . "<br/>";
    }
} elseif ($action == "add") {
    $query_array = array_reverse($query_array);
    array_push($query_array, base64_encode($query));
    file_put_contents($query_listing, implode(" ", $query_array));
    $query_array = array_reverse($query_array);
} elseif ($action == "delete") {
    $tmp_querys = array();
    foreach($query_array as $fquery) {
        if ($fquery != $encoded) {
            array_push($tmp_querys, $fquery);
        }
    }
    file_put_contents($query_listing, implode(" ", $tmp_querys));
    $querys = file_get_contents($query_listing);
    $query_array = split(" ", $querys);
}
print "<ul>";
foreach($query_array as $fquery) {
    if ($fquery == "") {
        continue;
    }
    print "<li><a href=\"javascript:ReplaceSearch('" . base64_decode($fquery) . "');\">" . base64_decode($fquery) . "</a> - <a href=\"javascript:DeleteQuery('" . base64_decode($fquery) . "');\"><img src=\"" . $config["delete_graph"] . "\" border=\"0\" align=\"middle\"></a></li>";
}
print "</ul>";
?>
