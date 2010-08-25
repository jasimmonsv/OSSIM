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
* - draw_responsibles()
* - draw_members()
* - remove_responsible()
* - remove_member()
* - edit_asset()
* - get_users()
* - draw_members_select()
* Classes list:
*/
require_once 'classes/Session.inc';
require_once 'classes/Security.inc';
require_once 'classes/Xajax.inc';
require_once 'classes/Util.inc';
Session::logcheck("MenuControlPanel", "BusinessProcesses");
Session::logcheck("MenuControlPanel", "BusinessProcessesEdit");
$db = new ossim_db();
$conn = $db->connect();
$proc_id = GET('proc_id');
$referrer = GET('referrer');
$id = GET('id');
ossim_valid($proc_id, OSS_DIGIT, 'illegal:ProcID');
ossim_valid($referrer, OSS_ALPHA, OSS_SCORE, OSS_NULLABLE, 'illegal:referrer');
ossim_valid($id, OSS_DIGIT, 'illegal:ID');
if (ossim_error()) {
    die(ossim_error());
}
$xajax = new xajax();
$xajax->registerFunction("draw_responsibles");
$xajax->registerFunction("remove_responsible");
$xajax->registerFunction("edit_asset");
$xajax->registerFunction("draw_members");
$xajax->registerFunction("remove_member");
$xajax->registerFunction("draw_members_select");
function draw_responsibles($selected_value) {
    global $id, $conn;
    $resp = new xajaxResponse();
    // insert new row and retrieve full person data
    if (is_array($selected_value) && $login = current($selected_value)) {
        ossim_valid($login, OSS_USER, 'illegal:' . _("User login"));
        if (ossim_error()) {
            $resp->AddAssign("form_errors", "innerHTML", ossim_error());
        } else {
            $sql = "INSERT INTO bp_asset_responsible (asset_id, login) VALUES (?, ?)";
            $params = array(
                $id,
                $login
            );
            if (!$conn->Execute($sql, $params)) {
                $resp->AddAssign("form_errors", "innerHTML", $conn->ErrorMsg());
            }
        }
    }
    // retrieve from db ordered by name
    $persons = get_users($id);
    if ($persons === false) {
        $resp->AddAssign("form_errors", "innerHTML", $conn->ErrorMsg());
        return $resp;
    }
    $tpl = '
    <div class="row">
        <div class="col1">%NAME%</div>
        <div class="col2">
        <a onClick="javascript: xajax_remove_responsible(\'%LOGIN%\')">(' . _("remove") . ')</a>
        </div>
    </div><hr>
    ';
    $html = '';
    foreach($persons as $p) {
        $tmp = str_replace('%NAME%', $p[1] . " (" . $p[0] . ")", $tpl);
        $html.= str_replace('%LOGIN%', $p[0], $tmp);
    }
    $resp->addAssign("responsibles", "innerHTML", $html);
    $resp->addAssign("responsibles", "style.display", '');
    return $resp;
}
function draw_members($form_data) {
    global $id, $conn;
    $resp = new xajaxResponse();
    // insert new member
    if (is_array($form_data)) {
        ossim_valid($form_data["member_type"], OSS_LETTER, OSS_SCORE, OSS_DOT, 'illegal:' . _("Member Type"));
        ossim_valid($form_data["member_name"], OSS_INPUT, 'illegal:' . _("Member Name"));
        if (ossim_error()) {
            $resp->AddAssign("form_errors", "innerHTML", ossim_error());
        } else {
            // Check for duplicates
            $sql = "SELECT member FROM bp_asset_member WHERE asset_id=? AND member=? AND member_type=?";
            if (!$rs = $conn->Execute($sql, array(
                $id,
                $form_data["member_name"],
                $form_data["member_type"]
            ))) {
                $resp->AddAssign("form_errors", "innerHTML", $conn->ErrorMsg());
                return $resp;
            }
            if ($rs->EOF) {
                $sql = "INSERT INTO bp_asset_member (asset_id, member, member_type) " . "VALUES (?, ?, ?)";
                $params = array(
                    $id,
                    $form_data["member_name"],
                    $form_data["member_type"]
                );
                if (!$conn->Execute($sql, $params)) {
                    $resp->AddAssign("form_errors", "innerHTML", $conn->ErrorMsg());
                    return $resp;
                }
            }
            $resp->AddAssign("form_errors", "innerHTML", '');
        }
    }
    // display members
    $sql = "SELECT member, member_type " . "FROM bp_asset_member " . "WHERE asset_id=?";
    $members = $conn->GetAll($sql, array(
        $id
    ));
    if ($members === false) {
        $resp->AddAssign("form_errors", "innerHTML", $conn->ErrorMsg());
        return $resp;
    }
    $tpl = '
    <div class="row">
        <div class="col1"><b>%TYPE%</b>: %NAME%</div>
        <div class="col2">
        <a onClick="javascript: xajax_remove_member(\'%JNAME%\', \'%JTYPE%\')">(' . _("remove") . ')</a>
        </div>
    </div><hr>
    ';
    $html = '';
    foreach($members as $i => $m) {
        $tmp = str_replace('%TYPE%', $m[1], $tpl);
        $tmp = str_replace('%JTYPE%', Util::string2js($m[1]) , $tmp);
        $tmp = str_replace('%NAME%', $m[0], $tmp);
        $html.= str_replace('%JNAME%', Util::string2js($m[0]) , $tmp);
    }
    $resp->addAssign("members", "innerHTML", $html);
    $resp->addAssign("members", "style.display", '');
    return $resp;
}
function remove_responsible($login) {
    global $id, $conn;
    // remove reference from db
    $sql = "DELETE FROM bp_asset_responsible
            WHERE asset_id=? AND login=?";
    $params = array(
        $id,
        $login
    );
    if (!$conn->Execute($sql, $params)) {
        die($conn->ErrorMsg());
    }
    return draw_responsibles(false);
}
function remove_member($name, $type) {
    global $id, $conn;
    $sql = "DELETE FROM bp_asset_member " . "WHERE asset_id=? AND member=? AND member_type=?";
    $params = array(
        $id,
        $name,
        $type
    );
    if (!$conn->Execute($sql, $params)) {
        die($conn->ErrorMsg());
    }
    return draw_members(false);
}
function edit_asset($form_data) {
    global $conn, $id, $proc_id, $referrer;
    $resp = new xajaxResponse();
    ossim_valid($form_data['bp_name'], OSS_INPUT, 'illegal:' . _("Name"));
    ossim_valid($form_data['bp_desc'], OSS_TEXT, 'illegal:' . _("Description"));
    if (ossim_error()) {
        $resp->AddAssign("form_errors", "innerHTML", ossim_error());
    } else {
        // Check if there is already an asset with that name
        $sql = "SELECT name FROM bp_asset WHERE name=?";
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
            $resp->AddAssign("form_errors", "innerHTML", ossim_error(_("There is already an asset with that name")));
            return $resp;
        }
        // New record
        if ($id == 0) {
            $sql = "INSERT INTO bp_asset (id, name, description) VALUES (?, ?, ?)";
            $id = $conn->GenID('bp_seq');
            $params = array(
                $id,
                $form_data['bp_name'],
                $form_data['bp_desc']
            );
            if (!$conn->Execute($sql, $params)) {
                $resp->AddAssign("form_errors", "innerHTML", $conn->ErrorMsg());
            } else {
                $resp->addRedirect($_SERVER['SCRIPT_NAME'] . "?id=$id&proc_id=$proc_id");
            }
            // Continue button, reflect possible changes in name or description
            
        } else {
            $sql = "UPDATE bp_asset SET name=?, description=? WHERE id=?";
            $params = array(
                $form_data['bp_name'],
                $form_data['bp_desc'],
                $id
            );
            if (!$conn->Execute($sql, $params)) {
                $resp->AddAssign("form_errors", "innerHTML", $conn->ErrorMsg());
            } elseif ($referrer == 'bp_list') {
                $resp->addRedirect("./bp_list.php");
            } elseif ($proc_id) {
                $resp->addRedirect("./bp_edit.php?id=$proc_id");
            } else {
                $resp->addRedirect("./asset_list.php");
            }
        }
    }
    return $resp;
}
//
// @returns array or false in case of db error
//
function get_users($asset_id = null) {
    global $conn;
    if ($asset_id) {
        $sql = "SELECT users.login, users.name
                FROM
                    users, bp_asset_responsible
                WHERE
                    users.login = bp_asset_responsible.login AND
                    bp_asset_responsible.asset_id = ?
                ORDER BY users.name";
        $params = array(
            $asset_id
        );
    } else {
        $sql = "SELECT login, name FROM users ORDER BY name";
        $params = array();
    }
    return $conn->getAll($sql, $params);
}
function draw_members_select($form_data) {
    global $conn, $id;
    $resp = new xajaxResponse();
    $type = $form_data['member_type'];
    // The user selected the empty type
    if (!$type) {
        $resp->AddAssign("members_select", "innerHTML", _("Please select a type"));
        return $resp;
    }
    //
    // Get the list of members of the given type
    //
    $options = array();
    switch ($type) {
        case 'host':
            include_once 'classes/Host.inc';
            $list = Host::get_list($conn, null, 'ORDER BY hostname');
            foreach($list as $obj) {
                $descr = $obj->get_descr();
                if (strlen($descr) > 50) {
                    $descr = substr($descr, 0, 47) . '...';
                }
                $options[$obj->get_ip() ] = $obj->get_hostname() . ' ' . $obj->get_ip() . ' - ' . $descr;
            }
            break;

        case 'net':
            include_once 'classes/Net.inc';
            $list = Net::get_list($conn, 'ORDER BY name');
            foreach($list as $obj) {
                $descr = $obj->get_descr();
                if (strlen($descr) > 50) {
                    $descr = substr($descr, 0, 47) . '...';
                }
                $options[$obj->get_name() ] = $obj->get_name() . ' ' . $obj->get_ips() . ' - ' . $descr;
            }
            break;

        case 'host_group':
            include_once 'classes/Host_group.inc';
            $list = Host_group::get_list($conn, 'ORDER BY name');
            foreach($list as $obj) {
                $descr = $obj->get_descr();
                if (strlen($descr) > 50) {
                    $descr = substr($descr, 0, 47) . '...';
                }
                $options[$obj->get_name() ] = $obj->get_name() . ' - ' . $descr;
            }
            break;

        case 'net_group':
            include_once 'classes/Net_group.inc';
            $list = Net_group::get_list($conn, 'ORDER BY name');
            foreach($list as $obj) {
                $descr = $obj->get_descr();
                if (strlen($descr) > 50) {
                    $descr = substr($descr, 0, 47) . '...';
                }
                $options[$obj->get_name() ] = $obj->get_name() . ' - ' . $descr;
            }
            break;
    }
    //
    // Build the SELECT tag
    //
    $html = '<select name="member_name">';
    foreach($options as $name => $description) {
        $html.= "<option value='$name'>$description</option>";
    }
    $html.= '</select>';
    $resp->AddAssign("members_select", "innerHTML", $html);
    return $resp;
}
$xajax->setRequestURI($_SERVER["REQUEST_URI"]);
$xajax->processRequests();
//-------------- End Ajax -------------------------//
$bp_name = $bp_desc = '';
if ($id != 0) {
    $sql = "SELECT name, description FROM bp_asset WHERE id=?";
    $data = $conn->getRow($sql, array(
        $id
    ));
    if ($data === false) {
        die($conn->ErrorMsg());
    }
    list($bp_name, $bp_desc) = $data;
    $sql = "SELECT type_name FROM bp_asset_member_type";
    $bp_types = $conn->getAll($sql);
    if ($bp_types === false) {
        die($conn->ErrorMsg());
    }
    $users = get_users();
    if ($users === false) {
        die($conn->ErrorMsg());
    }
}
?>
<html>
<head>
  <title><?php
_("Business Processes") ?></title>
  <script src="../js/prototype.js" type="text/javascript"></script>
  <link rel="stylesheet" href="../style/style.css"/>
<?php echo $xajax->printJavascript('', XAJAX_JS); ?>
<style type="text/css">
    .contents {
        width: 60%;
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
<?php
if ($id == 0) { ?>
    <h2><?php echo _("New Asset wizard") ?></h2>
<?php
} else { ?>
    <h2><?php echo _("Edit Asset") ?>: <u><?php echo $data['name'] ?></u></h2>
<?php
} ?>

<form id="bp_form">
<table width="60%" align="center">
<tr>
    <th><?php echo _("Name") ?></th>
    <td style="text-align: left; border-width: 0px"><input type="text" size="50" name="bp_name" value="<?php echo $bp_name ?>"></td>
</tr>
<tr>
    <th><?php echo _("Description") ?></th>
    <td style="text-align: left; border-width: 0px">
        <textarea NAME="bp_desc" COLS="48" ROWS=5 WRAP=HARD><?php echo $bp_desc ?></textarea>
    </td>
</tr>

<?php
if ($id == 0) { ?>
<tr>
  <td colspan="2" class="noborder">
    <input type="button" value="<?php echo _("Cancel") ?>"
           onClick="javascript: history.go(-1);">&nbsp;
    <input type="button" value="<?php echo _("Continue") ?>"
           onClick="javascript: xajax_edit_asset(xajax.getFormValues('bp_form'))">
    </td>
</tr>
</table>
<?php
} else {
?>

</table>
<br/>

<h2>Edit <u><?php echo $data['name'] ?></u>'s Responsibles</h2>
<table align="center" width="60%"><tr><th><?php echo _("List of responsibles") ?></th></tr>
<tr><td>
<div id="responsibles" class="contents" style="width: 100%">
<!-- Filled by draw_responsibles() -->
</div>
</td></tr>
<script>xajax_draw_responsibles(false)</script>
<tr><th><?php echo _("Insert new responsible") ?></th></tr>
<tr><td>
<div class="row" style="text-align: center; width: 100%">
    <select name="bp_new_responsible">
    <?php
    foreach($users as $u) { ?>
        <option value="<?php echo $u[0] ?>"><?php echo $u[1] ?> (<?php echo $u[0] ?>)</option>
    <?php
    } ?>
    </select>
    <input type="button" onClick="javascript: xajax_draw_responsibles(xajax.getFormValues('bp_form', true, 'bp_new_responsible'))" value="Add">
</div>
</td></tr>
</table>
<br/>

<h2>Edit <u><?php echo $data['name'] ?></u>'s Members</h2>
<table align="center" width="60%"><tr><th colspan="2"><?php echo _("List of Members") ?></th></tr>
<tr><td colspan="2">
<div id="members" class="contents" style="width: 100%">
<!-- Filled by draw_members() -->
</div>
</td></tr>
<script>xajax_draw_members(false)</script>
<div class="row" style="width: 100%">
<tr><th colspan="2"><?php echo _("Insert New Member") ?></th></tr>
<tr>
    <td width="40%" style="text-align: right"><?php echo _("Type") ?>&nbsp;&nbsp;</td>
    <td style="text-align: left">
        <select name="member_type"
                onChange="javascript: $(members_select).innerHTML = '<b><i><?php echo _("Loading...") ?></i></b>'; xajax_draw_members_select(xajax.getFormValues('bp_form', true, 'member_type'));">
            <option></option>
        <?php
    foreach($bp_types as $i => $type) { ?>
            <option value="<?php echo $type[0] ?>"><?php echo $type[0] ?></option>
        <?php
    } ?>
        </select>
    </td>
</tr>
<tr>
    <td width="40%" style="text-align: right"><?php echo _("Name") ?>&nbsp;&nbsp;</td>
    <td id="members_select" style="text-align: left"><?php echo _("Please select a type") ?></td>
</tr>
<tr>
    <td colspan="2">
    <input type="button" onClick="javascript: xajax_draw_members(xajax.getFormValues('bp_form', true, 'member_'))" value="Add">
    </td>
</tr>
</div>
</table>
<br/>
<table width="100%" class="noborder">
  <tr>
    <td colspan="2" class="noborder">
<input type="button" value="<?php echo _("Continue") ?>-&gt;"
       onClick="javascript: xajax_edit_asset(xajax.getFormValues('bp_form'))">
    </td>
  </tr>
</table>
<?php
} ?>
</form>
</body>
</html>
