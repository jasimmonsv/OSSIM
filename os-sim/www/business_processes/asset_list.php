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
* - delete_asset()
* - search_assets()
* Classes list:
*/
require_once 'classes/Session.inc';
require_once 'classes/Security.inc';
require_once 'classes/Xajax.inc';
require_once 'classes/Util.inc';
require_once 'classes/Business_Process.inc';
Session::logcheck("MenuControlPanel", "BusinessProcesses");
if (Session::menu_perms("MenuControlPanel", "BusinessProcessesEdit")) {
    $can_edit = true;
} else {
    $can_edit = false;
}
$xajax = new xajax();
$xajax->registerFunction("search_assets");
$xajax->registerFunction("delete_asset");
$db = new ossim_db();
$conn = $db->connect();
function delete_asset($asset_id, $form_data) {
    global $conn, $can_edit;
    if (!$can_edit) {
        return search_assets($form_data);
    }
    BP_Asset::delete($conn, $asset_id);
    return search_assets($form_data);
}
function search_assets($form_data) {
    global $conn, $can_edit;
    $resp = new xajaxResponse();
    //xajax_debug($form_data, $resp);
    // Build SQL from Form data
    $proc_id = $form_data['proc_id'];
    $asset_name = $form_data['asset_name'];
    $where = array();
    if ($proc_id == 'none') {
        $where[] = "proc.id IS NULL";
    } elseif ($proc_id != 'all') {
        $where[] = "proc.id=" . $conn->qstr($proc_id, get_magic_quotes_gpc());
    }
    if ($asset_name) {
        // qstr doesn't seem to quote right if you introduce "\" in the search form
        //$esc = substr($conn->qstr($asset_name, get_magic_quotes_gpc()), 1, -1);
        // XXX too mysql dependant (maybe not so important as Ossim already if very mysql dependant)
        $esc = mysql_real_escape_string($asset_name);
        $where[] = "asset.name LIKE '%$esc%'";
    }
    if (count($where)) {
        $w = 'WHERE ' . implode(' AND ', $where);
    } else {
        $w = '';
    }
    $sql = "
        SELECT 
            asset.id as asset_id,
            asset.name as asset_name,
            asset.description as asset_description,
            proc.id as proc_id,
            proc.name as proc_name,
            proc.description AS proc_description
        FROM
            bp_asset AS asset
        LEFT JOIN bp_process_asset_reference AS ref ON asset.id = ref.asset_id
        LEFT JOIN bp_process AS proc ON ref.process_id = proc.id
        $w
        ORDER BY asset.name";
    if (!$rs = $conn->Execute($sql)) {
        die($conn->ErrorMsg());
    }
    $assets = $procs = $asset_ref = array();
    while (!$rs->EOF) {
        $aid = $rs->fields['asset_id'];
        $pid = $rs->fields['proc_id'];
        if (!empty($pid)) {
            $asset_ref[$aid][] = $pid;
            $procs[$rs->fields['proc_id']] = $rs->fields['proc_name'];
        }
        if (!isset($assets[$aid])) {
            $assets[$aid] = $rs->fields;
        }
        $rs->MoveNext();
    }
    // No results found
    if (!count($assets)) {
        $resp->AddAssign("search-results", "innerHTML", '<center><i>' . _("No results found") . '</i></center>');
        return $resp;
    }
    // Print the results in HTML
    $html = '<table width="60%" align="center">
    <tr>
        <th>' . _("Asset Name") . '</th>
        <th>' . _("Belongs to") . '</th>';
    if ($can_edit) {
        $html.= '<th>' . _("Actions") . '</th>';
    }
    $html.= '</tr>';
    foreach($assets as $aid => $a) {
        $html.= '<tr>
            <td>' . $a['asset_name'] . '</td>
            <td style="text-align: left">';
        if (isset($asset_ref[$aid])) {
            $html.= '<ul>';
            foreach($asset_ref[$aid] as $pid) {
                $html.= '<li>' . $procs[$pid] . '</li>';
            }
            $html.= '</ul>';
        } else {
            $html.= '&nbsp;';
        }
        $html.= '</td>';
        if ($can_edit) {
            $html.= '<td><a href="./asset_edit.php?id=' . $a['asset_id'] . '">(' . _("edit") . ')</a>&nbsp;';
            $html.= '<a href="#" onClick="javascript: xajax_delete_asset(' . $a['asset_id'] . ', xajax.getFormValues(\'search\')); return false;">(' . _("delete") . ')</a></td>';
        }
        $html.= '</tr>';
    }
    $html.= '</table>';
    $resp->AddAssign("search-results", "innerHTML", $html);
    return $resp;
}
$xajax->setRequestURI($_SERVER["REQUEST_URI"]);
$xajax->processRequests();
//-------------- End Ajax -------------------------//
$sql = "SELECT id, name FROM bp_process";
if (!$rs = $conn->Execute($sql)) {
    die($conn->ErrorMsg());
}
$procs = array();
while (!$rs->EOF) {
    $procs[$rs->fields['id']] = $rs->fields['name'];
    $rs->MoveNext();
}
?>
<html>
<head>
<link rel="stylesheet" href="../style/style.css"/>
<?php echo $xajax->printJavascript('', XAJAX_JS); ?>
</head>
<body>
<br>
<div style="width: 100%; text-align: right;">
<a href="./bp_list.php">(<?php echo _("Back to Business Process View") ?>)</a>&nbsp;
<?php
if ($can_edit) { ?>
    <a href="./asset_edit.php?id=0">(<?php echo _("Create New Asset") ?>)</a>&nbsp;
<?php
} ?>
</div><br>
<form id="search">
<table width="90%" align="center">
<tr><th><?php echo _("Search assets") ?></th></tr>
<tr>
    <td><?php echo _("Belong to process") ?>: <select name="proc_id">
            <option value="all"><?php echo _("Any") ?></option>
            <option value="none"><?php echo _("Not assigned") ?></option>
            <?php
foreach($procs as $pid => $pname) { ?>
                <option value="<?php echo $pid
?>"><?php echo $pname
?></option>
            <?php
} ?>
        </select>&nbsp;
        <?php echo _("Name contains") ?>: <input type="text" name="asset_name" size="20">&nbsp;
        <input type="button" value="<?php echo _("Search") ?>"
               onClick="javascript: xajax_search_assets(xajax.getFormValues('search')); return false;">
    </td>
</tr>
</table>
</form>
<br>
<div id="search-results"></div>
<div id="xajax_debug"></div>
</body></html>