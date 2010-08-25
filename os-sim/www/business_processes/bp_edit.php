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
* - draw_assets()
* - html_assets_select()
* - remove_asset()
* - change_asset_severity()
* - draw_asset_details()
* - add_asset()
* - edit_process()
* Classes list:
*/
require_once 'classes/Session.inc';
require_once 'classes/Security.inc';
require_once 'classes/Xajax.inc';
require_once 'classes/Business_Process.inc';
Session::logcheck("MenuControlPanel", "BusinessProcesses");
Session::logcheck("MenuControlPanel", "BusinessProcessesEdit");
$xajax = new xajax();
$xajax->registerFunction("draw_assets");
$xajax->registerFunction("add_asset");
$xajax->registerFunction("remove_asset");
$xajax->registerFunction("draw_asset_details");
$xajax->registerFunction("change_asset_severity");
$xajax->registerFunction("edit_process");
$db = new ossim_db();
$conn = $db->connect();
$id = GET('id');
ossim_valid($id, OSS_DIGIT, 'illegal:ID');
if (ossim_error()) {
    die(ossim_error());
}
function draw_assets($selected_value) {
    global $conn, $id;
    $resp = new xajaxResponse();
    $tpl = '
    <div class="row">
        <div class="col1">%ACTIVE%</div>
        <div class="col2" onChange="javascript: xajax_change_asset_severity(%ID%, xajax.getFormValues(\'bp_form\', true, \'prio_active_%ID%\'))">
            <select name="prio_active_%ID%">
            <option value="0" %0%>' . _("Low") . '</option>
            <option value="1" %1%>' . _("Medium") . '</option>
            <option value="2" %2%>' . _("High") . '</option>
            </select>&nbsp;
            <a onClick="javascript: xajax_draw_asset_details(%ID%)">(' . _("details") . ')</a>&nbsp;
            <a href="./asset_edit.php?id=%ID%&proc_id=' . $id . '">(' . _("edit") . ')</a>&nbsp;
            <a onClick="javascript: xajax_remove_asset(%ID%)">(' . _("delete") . ')</a>
        </div>
    </div><hr>';
    $sql = "SELECT
                bp_asset.id,
                bp_asset.name,
                bp_asset.description,
                 bp_process_asset_reference.severity
            FROM bp_asset, bp_process_asset_reference
            WHERE
                bp_process_asset_reference.process_id = ? AND
                bp_process_asset_reference.asset_id = bp_asset.id
            GROUP BY bp_asset.id
            ORDER BY bp_asset.name";
    $assets = $conn->GetAll($sql, array(
        $id
    ));
    if ($assets === false) {
        $resp->AddAssign("form_errors", "innerHTML", $conn->ErrorMsg());
        return $resp;
    }
    $html = '';
    foreach($assets as $a) {
        $tmp = str_replace('%ACTIVE%', $a['name'], $tpl);
        foreach(array(
            0,
            1,
            2
        ) as $prio) {
            $selected = ($a['severity'] == $prio) ? 'selected' : '';
            $replace = '%' . $prio . '%';
            $tmp = str_replace($replace, $selected, $tmp);
        }
        $html.= str_replace('%ID%', $a['id'], $tmp);
    }
    $resp->addAssign("assets", "innerHTML", $html);
    $resp->addAssign("html_assets_select", "innerHTML", html_assets_select());
    return $resp;
}
/*
* Returns the html needed to generate the SELECT html element with
* the list of assets not assigned to the current process id.
*
* This function is only called by draw_assets()
*/
function html_assets_select() {
    global $conn, $id;
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
    WHERE ref.process_id != ? OR ref.process_id IS NULL
    ORDER BY asset.name";
    if (!$rs = $conn->Execute($sql, array(
        $id
    ))) {
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
    $html = '<select name="bp_new_asset">';
    $belongs = '';
    foreach($assets as $aid => $data) {
        unset($belongs);
        if (isset($asset_ref[$aid])) {
            foreach($asset_ref[$aid] as $proc_id) {
                $belongs[] = $procs[$proc_id];
            }
            $belongs = implode(', ', $belongs);
        } else {
            $belongs = _("not assigned");
        }
        $html.= "<option value='$aid'>" . $data['asset_name'] . " (" . $belongs . ")</option>";
    }
    $html.= '</select>
    <input type="button" value="' . _("Add") . '"
           onClick="javascript: xajax_add_asset(xajax.getFormValues(\'bp_form\', true, \'bp_new_asset\'))">&nbsp;
    <input type="button" value="' . _("Details") . '"
            onClick="javascript: xajax_draw_asset_details(xajax.getFormValues(\'bp_form\', true, \'bp_new_asset\'))"><br/>
    <a href="asset_edit.php?id=0&proc_id=' . $id . '">' . _("Create new asset") . '</a>
    ';
    return $html;
}
function remove_asset($asset_id) {
    // remove reference from db
    global $conn, $id;
    $sql = "DELETE FROM bp_process_asset_reference
            WHERE process_id = ? AND asset_id = ?";
    if (!$conn->Execute($sql, array(
        $id,
        $asset_id
    ))) {
        die($conn->ErrorMsg());
    }
    return draw_assets(false);
}
/*
* @param $severity comes from xajax in the form: Array ( [prio_active_4] => 2 )
*/
function change_asset_severity($asset_id, $severity) {
    global $conn, $id;
    $resp = new xajaxResponse();
    //xajax_debug($asset_id, $resp);
    $s = current($severity);
    $sql = "UPDATE bp_process_asset_reference
            SET severity = ?
            WHERE process_id = ? AND asset_id = ?";
    if (!$conn->Execute($sql, array(
        $s,
        $id,
        $asset_id
    ))) {
        $resp->AddAssign("form_errors", "innerHTML", $conn->ErrorMsg());
    }
    return $resp;
}
function draw_asset_details($asset_id) {
    global $conn;
    $resp = new xajaxResponse();
    $asset = BP_Asset::get($conn, $asset_id);
    $html = '
        <table width="60%" align="center">
        <tr>
            <th width="20%">' . _("Asset Name") . '</th>
            <td style="text-align: left;"><b>' . $asset->get_name() . '</b></td>
        </tr>
        <tr>
            <th>' . _("Description") . '</th>
            <td style="text-align: left;">' . $asset->get_description() . '</td>
        </tr>
        <tr>
            <th colspan="2">' . _("Responsibles") . '</th>
        </tr>';
    foreach($asset->get_responsibles() as $responsible) {
        $str = $responsible['name'] . ' (' . $responsible['login'] . ')';
        $html.= '<tr><td colspan="2" style="text-align: left">' . $str . '</td></tr>';
    }
    $html.= '
    <tr>
        <th width="30%" colspan="2">' . _("Members") . '</th>
    </tr>';
    foreach($asset->get_members() as $mem) {
        $str = '<b>' . $mem['type'] . '</b>: ' . $mem['name'];
        $html.= '<tr><td colspan="2" style="text-align: left">' . $str . '</td></tr>';
    }
    $resp->addAssign("asset-info", "style.display", '');
    $resp->AddAssign("asset-info", "innerHTML", $html);
    return $resp;
}
function add_asset($asset_id) {
    global $conn, $id;
    $sql = "INSERT INTO bp_process_asset_reference
            (process_id, asset_id, severity) VALUES (?, ?, ?)";
    $params = array(
        $id,
        $asset_id['bp_new_asset'],
        1
    );
    if (!$conn->Execute($sql, $params)) {
        die($conn->ErrorMsg());
    }
    return draw_assets(false);
}
function edit_process($form_data) {
    global $conn, $id;
    $resp = new xajaxResponse();
    ossim_valid($form_data['bp_name'], OSS_INPUT, 'illegal:' . _("Name"));
    ossim_valid($form_data['bp_desc'], OSS_TEXT, 'illegal:' . _("Description"));
    if (ossim_error()) {
        $resp->AddAssign("form_errors", "innerHTML", ossim_error());
    } else {
        // Check if there is already a BP with that name
        $sql = "SELECT name FROM bp_process WHERE name=?";
        if ($id != 0) {
            $sql.= " AND id <> $id";
        }
        $params = array(
            $form_data['bp_name']
        );
        if (!$rs = $conn->Execute($sql, $params)) {
            $resp->AddAssign("form_errors", "innerHTML", $conn->ErrorMsg());
            return $resp;
        } elseif (!$rs->EOF) {
            $resp->AddAssign("form_errors", "innerHTML", ossim_error(_("There is already a process with that name")));
            return $resp;
        }
        if ($id == 0) {
            $sql = "INSERT INTO bp_process (id, name, description) VALUES (?, ?, ?)";
            $id = $conn->GenID('bp_seq');
            $params = array(
                $id,
                $form_data['bp_name'],
                $form_data['bp_desc']
            );
            if (!$conn->Execute($sql, $params)) {
                $resp->AddAssign("form_errors", "innerHTML", $conn->ErrorMsg());
            } else {
                $resp->addRedirect($_SERVER['SCRIPT_NAME'] . "?id=$id");
            }
        } else {
            $sql = "UPDATE bp_process SET name=?, description=? WHERE id=?";
            $params = array(
                $form_data['bp_name'],
                $form_data['bp_desc'],
                $id
            );
            if (!$conn->Execute($sql, $params)) {
                $resp->AddAssign("form_errors", "innerHTML", $conn->ErrorMsg());
            } else {
                $resp->addRedirect("./bp_list.php");
            }
        }
    }
    return $resp;
}
$xajax->setRequestURI($_SERVER["REQUEST_URI"]);
$xajax->processRequests();
//-------------- End Ajax -------------------------//
if ($id != 0) {
    $sql = "SELECT name, description
            FROM bp_process
            WHERE id = ?";
    $proc_data = $conn->GetRow($sql, array(
        $id
    ));
    if ($proc_data === false) {
        die($conn->ErrorMsg());
    }
} else {
    $proc_data['name'] = '';
    $proc_data['description'] = '';
}
?>
<html>
<head>
  <title><?php
_("Business Processes") ?></title>
  <link rel="stylesheet" href="../style/style.css"/>
<?php echo $xajax->printJavascript('', XAJAX_JS); ?>
<style type="text/css">
    .contents {
        width: 80%;
    }
    .row {
        clear: both;
    }
    .col1 {
        float: left;
        width: 50%;
    }
    .col2 {
        float: left;
        text-align: right;
        width: 50%;
    }
    hr {
        clear: both;
    }
</style>
  
</head>
<body>
<div id="xajax_debug"></div>
<div id="form_errors"></div>
<form id="bp_form">
<?php
if ($id == 0) { ?>
    <h2><?php echo _("New Business Process wizard") ?></h2>
<?php
} else { ?>
    <h2><?php echo _("Edit Business Process") ?>: <u><?php echo $proc_data['name'] ?></u></h2>
<?php
} ?>
<table width="60%" align="center">
<tr>
    <th colspan="2"><?php echo _("BP properties") ?></td>
</tr>
<tr>
    <th><?php echo _("Name") ?></th>
    <td style="text-align: left; border-width: 0px"><input type="text" size="50" name="bp_name" value="<?php echo $proc_data['name'] ?>"></td>
</tr>
<tr>
    <th><?php echo _("Description") ?></th>
    <td style="text-align: left; border-width: 0px">
        <textarea NAME="bp_desc" COLS="48" ROWS=5 WRAP=HARD><?php echo $proc_data['description'] ?></textarea>
    </td>
</tr>
<?php
if ($id == 0) { ?>
<tr>
  <td colspan="2">
    <input type="button" value="<?php echo _("Cancel") ?>"
           onClick="javascript: history.go(-1);">&nbsp;
    <input type="button" value="<?php echo _("Continue") ?>"
           onClick="javascript: xajax_edit_process(xajax.getFormValues('bp_form'))">
  </td>
</tr>
</table>
<?php
} else {
?>
</table>
<br/>

<h2>Edit <u><?php echo $proc_data['name'] ?></u>'s Assets</h2>
<table width="60%" align="center">
  <tr>
    <th><?php echo _("List of assets") ?></th>
  </tr>
  <tr>
    <td>
<div id="assets" class="contents" style="width: 100%">
<!-- Filled by draw_responsibles() -->
</div>
<script>xajax_draw_assets(false)</script>
    </td>
  </tr>
  <tr>
    <th><?php echo _("Add new assets") ?></th>
  </tr>
  <tr>
    <td>
<div id="html_assets_select" class="row" style="width: 100%">
<!-- Filled by draw_assets(), generated at html_assets_select() -->
</div>
    </td>
  </tr>
</form>
</table>

<br/>
<table align="center" width="60%" class="noborder">
  <tr>
    <td style="border-width: 0px">
      <input type="button" value="<?php echo _("Continue") ?>-&gt;"
       onClick="javascript: xajax_edit_process(xajax.getFormValues('bp_form'))">
    </td>
  </tr>
</table>
<?php
} ?>
<br><br>
<div id="asset-info" style="display: none; text-align: center">
</div>

</body>
</html>
