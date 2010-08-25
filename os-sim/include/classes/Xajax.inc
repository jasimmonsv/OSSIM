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
* - addTableRow()
* - addTableCell()
* - xajax_debug()
* Classes list:
* - xajaxTableResponse extends xajaxResponse
*/
require_once ('ossim_conf.inc');
//ini_set('display_errors','1');
//ini_set('display_startup_errors','1');
//error_reporting (E_ALL);
$config = $GLOBALS["CONF"];
$xajax_php_path = $config->get_conf("xajax_php_path");
$xajax_included = @include_once "$xajax_php_path/xajax.inc.php";
if (!$xajax_included) {
    // xajax default php location, included in the source
    include_once 'xajax/xajax.inc.php';
}
$xajax_js_path = $config->get_conf("xajax_js_path");
$xajax_js = $xajax_js_path . DIRECTORY_SEPARATOR . 'xajax.js';
if (!is_file($xajax_js)) {
    // xajax default js location, included in the source
    $xajax_js_path = "../js";
}
DEFINE('XAJAX_JS', $xajax_js_path . DIRECTORY_SEPARATOR . 'xajax.js');
class xajaxTableResponse extends xajaxResponse {
    function addTableRow($tableName) {
        $this->addScript("xajax.\$('" . $tableName . "').insertRow(-1);");
    }
    function addTableCell($tableName, $cellContents, $rowNumber = null) {
        if ($rowNumber !== null || !is_numeric($rowNumber)) $rowNumber = "tbl.rows.length-1";
        $this->addScript("var tbl=xajax.\$('" . $tableName . "');tbl.rows[" . $rowNumber . "].insertCell(-1).innerHTML='" . addSlashes($cellContents) . "';");
    }
}
function xajax_debug($var, &$resp) {
    $html = printr($var, false, true);
    $resp->AddAppend("xajax_debug", "innerHTML", $html);
    return $resp;
}
?>
