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
* - draw_error()
* - end_configuration()
* - save()
* - add_column()
* - delete_column()
* - move_column()
* - save_column_opts()
* - draw_columns()
* Classes list:
*/
require_once 'classes/Security.inc';
require_once 'classes/Session.inc';
require_once 'classes/Plugingroup.inc';
require_once 'classes/User_config.inc';
require_once 'classes/Event_viewer.inc';
require_once 'classes/Xajax.inc';
require_once 'ossim_db.inc';
Session::logcheck("MenuEvents", "EventsViewer");
$db = new ossim_db();
$conn = $db->connect();
$config = new User_config($conn);
$login = Session::get_session_user();
$xajax = new xajax();
$xajax->registerFunction("draw_columns");
$xajax->registerFunction("draw_error");
$xajax->registerFunction("add_column");
$xajax->registerFunction("delete_column");
$xajax->registerFunction("move_column");
$xajax->registerFunction("save_column_opts");
$xajax->registerFunction("save");
$xajax->registerFunction("end_configuration");
function draw_error($error) {
    global $config, $login;
    $resp = new xajaxResponse();
    //return xajax_debug($error, $resp);
    $html = ossim_error($error);
    $resp->addAssign("errors", "innerHTML", $html);
    return $resp;
}
function end_configuration() {
    global $config, $login;
    $config->del($login, 'event_viewer_tmp');
    $resp = new xajaxResponse();
    $resp->addRedirect("./");
    return $resp;
}
/*
* Copies the temp config var 'event_viewer_tmp' into the final configuration
* option 'event_viewer' and removes the temp
*/
function save($groups_form) {
    global $config, $login, $conn;
    $groups_config = $config->get($login, 'event_viewer_tmp', 'php');
    //$resp = new xajaxResponse(); xajax_debug($groups_config, $resp); return xajax_debug($groups_form, $resp);
    if (isset($groups_form['plugin_groups'])) {
        $cleaned = array();
        foreach($groups_form['plugin_groups'] as $group) {
            if (isset($groups_config[$group])) {
                $cleaned[$group] = $groups_config[$group];
            } else {
                //$resp = new xajaxResponse(); xajax_debug($groups_config, $resp); return xajax_debug($groups_form, $resp);
                list($group_data) = Plugingroup::get_list($conn, "plugin_group.group_id=$group");
                return draw_error(_("Please configure settings for group") . ": <b>" . $group_data->get_name() . "</b>");
            }
        }
        $groups_config = $cleaned;
    } else {
        $groups_config = array();
    }
    $config->set($login, 'event_viewer', $groups_config, 'php');
    return end_configuration();
}
function add_column($group_id) {
    global $config, $login;
    $groups_config = $config->get($login, 'event_viewer_tmp', 'php');
    if (!is_array($groups_config) || !isset($groups_config[$group_id])) {
        $new_col = 1;
    } else {
        $cols = array_keys($groups_config[$group_id]);
        $new_col = count($cols) + 1;
    }
    $groups_config[$group_id][$new_col] = array();
    $config->set($login, 'event_viewer_tmp', $groups_config, 'php');
    return draw_columns($group_id, $new_col);
}
function delete_column($group_id, $col_num) {
    global $config, $login;
    $groups_config = $config->get($login, 'event_viewer_tmp', 'php');
    unset($groups_config[$group_id][$col_num]);
    $tmp = array();
    $orig_cols = array_keys($groups_config[$group_id]);
    // reindex columns
    for ($col = 1; $col <= count($groups_config[$group_id]); $col++) {
        $current = current($orig_cols);
        $tmp[$col] = $groups_config[$group_id][$current];
        next($orig_cols);
    }
    $groups_config[$group_id] = $tmp;
    //$resp = new xajaxResponse(); return xajax_debug($groups_config, $resp);
    $config->set($login, 'event_viewer_tmp', $groups_config, 'php');
    return draw_columns($group_id);
}
function move_column($group_id, $col_num, $to) {
    global $config, $login;
    $groups_config = $config->get($login, 'event_viewer_tmp', 'php');
    $groups = array_keys($groups_config);
    if ($to == 'right') {
        $right = $groups_config[$group_id][$col_num + 1];
        $current = $groups_config[$group_id][$col_num];
        $groups_config[$group_id][$col_num + 1] = $current;
        $groups_config[$group_id][$col_num] = $right;
        $selected = $col_num + 1;
    } else {
        $left = $groups_config[$group_id][$col_num - 1];
        $current = $groups_config[$group_id][$col_num];
        $groups_config[$group_id][$col_num - 1] = $current;
        $groups_config[$group_id][$col_num] = $left;
        $selected = $col_num - 1;
    }
    $config->set($login, 'event_viewer_tmp', $groups_config, 'php');
    return draw_columns($group_id, $selected);
}
function save_column_opts($group_id, $col_num, $form_data) {
    global $config, $login;
    //$resp = new xajaxResponse(); return xajax_debug($form_data, $resp);
    // XXX TODO validation
    $groups_config = $config->get($login, 'event_viewer_tmp', 'php');
    $groups_config[$group_id][$col_num]['label'] = $form_data['label'];
    $groups_config[$group_id][$col_num]['contents'] = $form_data['contents'];
    $groups_config[$group_id][$col_num]['align'] = $form_data['align'];
    if (!empty($form_data['width'])) {
        $groups_config[$group_id][$col_num]['width'] = $form_data['width'];
    }
    if ($form_data['wrap'] == 'no') {
        $groups_config[$group_id][$col_num]['wrap'] = false;
    }
    $config->set($login, 'event_viewer_tmp', $groups_config, 'php');
    return draw_columns($group_id, $col_num);
}
function draw_columns($group_id, $selected_col = 1) {
    global $conn, $config, $login;
    $resp = new xajaxResponse();
    list($group_data) = Plugingroup::get_list($conn, "plugin_group.group_id=$group_id");
    $groups_config = $config->get($login, 'event_viewer_tmp', 'php');
    $html = '<form id="colopts">' . _('Columns display configuration for group') . ': <b>' . $group_data->get_name() . '</b><br>
<table width="100%" align="center" style="border-width: 0px">
<tr>
<td style="border-width: 0px">
';
    if (is_array($groups_config) && isset($groups_config[$group_id])) {
        /*
        * Draw column tabs
        */
        //xajax_debug($groups_config, $resp);
        $html.= '<table width="100%" align="center"><tr>';
        $num_cols = count($groups_config[$group_id]);
        foreach($groups_config[$group_id] as $col_num => $col_conf) {
            if ($col_num == $selected_col) {
                $td_bg = 'background-color: grey';
                $bold = true;
            } else {
                $td_bg = '';
                $bold = false;
            }
            $curr = $groups_config[$group_id][$col_num];
            $curr_label = isset($curr['label']) ? $curr['label'] : $col_num;
            $html.= '<td style="border-width: 0px;' . $td_bg . '">';
            $tmp = '';
            if ($col_num != 1) {
                $tmp = '<a href="#" onClick="javascript: xajax_move_column(' . $group_id . ', ' . $col_num . ', \'left\');">&lt;</a>&nbsp;';
            }
            $tmp.= '<a href="#" onClick="javascript: xajax_draw_columns(' . $group_id . ', ' . $col_num . ')">' . $curr_label . '</a>&nbsp;';
            if ($col_num != $num_cols) {
                $tmp.= '<a href="#" onClick="javascript: xajax_move_column(' . $group_id . ', ' . $col_num . ', \'right\');">&gt;</a>&nbsp;';
            }
            $tmp.= '<small>(<a href="#" onClick="javascript: xajax_delete_column(' . $group_id . ', ' . $col_num . ')">' . _("delete") . '</a>)</small>';
            $html.= ($bold) ? "<b>$tmp</b>" : $tmp;
            $html.= '</td>';
        }
        /*
        * Draw column options
        */
        $current_col = $groups_config[$group_id][$selected_col];
        $col_label = isset($current_col['label']) ? $current_col['label'] : '';
        $col_contents = isset($current_col['contents']) ? $current_col['contents'] : '';
        $col_width = isset($current_col['width']) ? $current_col['width'] : '';
        $col_align = isset($current_col['align']) ? $current_col['align'] : 'left';
        $col_selected_left = $col_selected_center = $col_selected_right = '';
        switch ($col_align) {
            case 'center':
                $col_selected_center = 'selected';
                break;

            case 'right':
                $col_selected_right = 'selected';
                break;

            default:
                $col_selected_left = 'selected';
        }
        $col_wrap = !isset($current_col['wrap']) ? true : false;
        $col_selected_wrap = $col_selected_nowrap = '';
        if ($col_wrap) {
            $col_selected_wrap = 'selected';
        } else {
            $col_selected_nowrap = 'selected';
        }
        // SELECT tag
        $tags = Event_viewer::get_tags();
        $select = '<option value="">' . _("Add replacement tag") . "</option>";
        foreach($tags as $label => $descr) {
            $select.= "<option value= '$label'>$label</option>";
        }
        $select = '<select id="tags" onChange="javascript: add_tag(this)">' . $select . '</select>';
        $html.= '</tr><tr><td colspan="' . $num_cols . '" style="border-width: 0px">
' . _("Options for column") . ': <b>' . $selected_col . '</b><br>
<table width="100%" align="left" style="border-width: 0px">
<tr>
    <th>' . _("Column label") . '</th>
    <td style="text-align: left"><input type="text" value="' . $col_label . '" name="label" size="25"></td>
</tr><tr>
    <th>' . _("Column contents") . '</th>
    <td style="text-align: left" nowrap><input type="text" id="contents" value="' . $col_contents . '" name="contents" size="50">&lt;-' . $select . '</td>
</tr><tr>
    <th>' . _("Column settings") . '</th>
    <td style="text-align: left" nowrap>' . _("Align") . ': <select name="align">
                        <option value="left" ' . $col_selected_left . '>' . _("left") . '</option>
                        <option value="center" ' . $col_selected_center . '>' . _("center") . '</option>
                        <option value="right" ' . $col_selected_right . '>' . _("right") . '</option>
                      </select>&nbsp;' . _("Wrap") . ': <select name="wrap">
                        <option value="yes" ' . $col_selected_wrap . '>' . _("Yes") . '</option>
                        <option value="no" ' . $col_selected_nowrap . '>' . _("No") . '</option>
                      </select>&nbsp;' . _("Width") . ': <input type="text" value="' . $col_width . '" name="width" size="3">% (1-100)
    </td>
</tr><tr>
    <td colspan="2" style="border-width: 0px">
        <input type="button" name="save" value="' . _("save column") . ' ' . $selected_col . '"
               onclick="javascript: xajax_save_column_opts(' . $group_id . ', ' . $selected_col . ', xajax.getFormValues(\'colopts\'))">
    </td>
</tr>
</table>';
        $html.= '</td></tr></table>';
    }
    $html.= '
</td><td>
<td valign="top" style="border-width: 0px; text-align: right"><a href="#" onClick="javascript: xajax_add_column(' . $group_id . ')">' . _("add column") . '</td>
</td>
</tr>
</table>
</form>
';
    $resp->addAssign("columns_config", "innerHTML", $html);
    $resp->addAssign("columns_config", "style.display", '');
    return $resp;
}
$xajax->setRequestURI($_SERVER["REQUEST_URI"]);
$xajax->processRequests();
/************ END AJAX **************/
// start with fresh data
$groups_config = $config->get($login, 'event_viewer', 'php');
$config->set($login, 'event_viewer_tmp', $groups_config, 'php');
$groups = Plugingroup::get_list($conn);
?>
<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
  
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/greybox.js"></script>

  <?php echo $xajax->printJavascript('', XAJAX_JS); ?>
<script language="JavaScript" type="text/javascript">

$(document).ready(function(){
        $("a.greybox").click(function(){
            var t = this.title || $(this).text() || this.href;
            GB_show(t,this.href,'80%','75%');
            return false;
        });
    function GB_onclose() {
        document.location.reload();
    }
	});
  function add_tag(select)
  {
    var i = select.selectedIndex;
    var tag = select[i].value;
	if (i != 0) {
        $('#contents').val(tag);
        select.selectedIndex = 0;
    }
    $('#contents').focus();
    return false;
  }
</script>
  
</head>
<body>
<? include ("../hmenu.php") ?>
<div style="text-align: right"><a href="../policy/plugingroups.php" class="greybox"><?php echo _("Go to DS Groups configuration page") ?></a></div>
<br>
<?php
if (!count($groups)) { ?>
    <center><?php echo _("No DS Groups found") ?></center>
<?php
    exit;
} ?>
<div id="errors"></div>
<form id="groups">
<table width="65%" align="center" border="0">
<tr>
    <th>&nbsp;</th>
    <th><?php echo _("Name") ?></th>
    <th><?php echo _("Description") ?></th>
    <th><?php echo _("Actions") ?></th>
</tr>
<?php
//printr($groups_config);
if (is_array($groups_config)) {
    $selected_groups = array_keys($groups_config);
} else {
    $selected_groups = array();
}
$color=0;
foreach($groups as $group) {
    $id = $group->get_id();
    $checked = in_array($id, $selected_groups) ? "checked" : "";
?>
    <tr <?=_(($color%2==0)? "style=\"background-color:#f2f2f2;\"":"")?>">
        <td class="nborder" NOWRAP><input type="checkbox" name="plugin_groups[]" value="<?php echo $id
?>" <?php echo $checked ?>></td>
        <td NOWRAP><b><?php echo htm($group->get_name()) ?></b></td>
        <td width="70%" style="text-align: left"><?php echo htm($group->get_description()) ?></td>
        <td width="1%" NOWRAP><a href="#Settings" onClick="xajax_draw_columns(<?php echo $id ?>);"><?php echo _("settings") ?></a></td>
    </tr>
<?php
$color++;
} ?>
<tr>
<td colspan="4" style="border-width: 0px; text-align: center">
    <input type="button" name="cancel" value="<?php echo _("cancel") ?>" onClick="javascript: xajax_end_configuration()">&nbsp;
    <input type="button" name="save" value="<?php echo _("save") ?>" onClick="javascript: xajax_save(xajax.getFormValues('groups', 0, 'plugin_groups'))">
</td>
</tr>
</table>
</form>
<br>
<div id="xajax_debug"></div>
<div id="columns_config" align="center" style="display: none; width: 80%; margin: 0 auto; border-width: 1px; border-style: solid; border-color: grey">
</div>
<div id="column_options"></div>
<a name="Settings">&nbsp;</a>
</body></html>
