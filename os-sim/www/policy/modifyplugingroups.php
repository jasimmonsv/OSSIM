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
* - validate_sids_str()
* - validate_post_params()
* Classes list:
*/
require_once 'classes/Security.inc';
require_once 'classes/Session.inc';
require_once 'ossim_db.inc';
require_once 'classes/Plugin.inc';
require_once 'classes/Plugingroup.inc';
session_start();
Session::logcheck("MenuConfiguration", "PluginGroups");
$db = new ossim_db();
$conn = $db->connect();
$plugin_list = Plugin::get_list($conn, "ORDER BY name");
/*
* Sample valid $str values:
*      '0' => ALL SIDs
*      '1' => only SID 1
*      '1,2' => SIDs 1 and 2
*      '1-4' => All SIDs between 1 and 4 (both inclusive)
*      '1,3-5' => SID 1 and range 3 to 5
*      '3-5,46,47,110-170' => Valid too
*/
function validate_sids_str($str) {
    //    // $str = '';
    if ($str == '') {
        return array(
            false,
            _("Signature ID can not be empty. Specify '0' if you want ALL sids")
        );
    }
    $values = preg_split('/(\s*,\s*)/', $str);
    $ret = $m = array();
    foreach($values as $v) {
        if ($v == "ANY") $v = 0;
        if (preg_match('/^([1-9][0-9]*)-([1-9][0-9]*)$/', $v, $m)) {
            list($start, $end) = array(
                $m[1],
                $m[2]
            );
            if ($start >= $end) {
                return array(
                    false,
                    _("Invalid range: '$v'")
                );
            }
            $ret[] = $v;
        } elseif (preg_match('/^[0-9]+$/', $v, $m)) {
            $ret[] = $v;
        } else {
            return array(
                false,
                _("Invalid sid: '$str'")
            );
        }
    }
    // $str = '0,1,2'
    if (count($ret) > 1 && in_array(0, $ret)) {
        return array(
            false,
            _("'0' or 'ANY' should be alone and means ALL sids, sid: '$str' not valid")
        );
    }
    // $str = '';
    if (!count($ret)) {
        return array(
            false,
            _("Signature ID can not be empty. Specify '0' or 'ANY' if you want ALL sids")
        );
    }
    return array(
        true,
        implode(',', $ret)
    );
}
function get_checked_plugins($arr) {
	$imported_plugins = array();
	// get checkbox values with format psidID_SID
	foreach ($arr as $k => $v) if (preg_match("/psid(\d+)_(\d+)/",$k,$found) && $v==1) {
		$imported_plugins[$found[1]][] = $found[2];
	}
	return $imported_plugins;
}
/*
* Validates the POST data: name, description, plugins and SIDs
*
* @return Processed array($name, $description, array(plug_id => sid string))
*/
function validate_post_params($name, $descr, $sids, $imported_sids) {
    $vals = array(
        'name' => array(
            OSS_INPUT,
            'illegal:' . _("Name")
        ) ,
        'descr' => array(
            OSS_TEXT,
            OSS_NULLABLE,
            'illegal:' . _("Description")
        ) ,
    );
    ossim_valid($name, $vals['name']);
    ossim_valid($descr, $vals['descr']);
    $plugins = array();
    $sids = is_array($sids) ? $sids : array();
    if (intval(POST('pluginid')) > 0) $sids[POST('pluginid')] = "0";
    foreach($sids as $plugin => $sids_str) {
        if ($sids_str !== '') {
            list($valid, $data) = validate_sids_str($sids_str);
            if (!$valid) {
                ossim_set_error(_("Error for plugin ") . $plugin . ': ' . $data);
                break;
            }
            if ($sids_str == "ANY") $sids_str = "0";
            $plugins[$plugin] = $sids_str;
        }
    }
    /*$delvar = array();
    foreach($_SESSION as $k => $sids_str) if (preg_match("/pid(\d+)/", $k, $found)) {
        $plugin = $found[1];
        if ($sids_str !== '') {
            list($valid, $data) = validate_sids_str($sids_str);
            if (!$valid) {
                ossim_set_error(_("Error for plugin ") . $plugin . ': ' . $data);
                break;
            }
            if ($sids_str == "ANY") $sids_str = "0";
            if ($plugins[$plugin] == "") $plugins[$plugin] = $sids_str;
        }
        $delvar[] = $k;
    }
    foreach($delvar as $k) unset($_SESSION[$k]); */
    //
    if (!count($plugins) && !count($imported_sids)) {
        ossim_set_error(_("No plugins or Signature IDs selected"));
    }
    if (ossim_error()) {
        die(ossim_error());
    }
    return array(
        $name,
        $descr,
        $plugins
    );
}
if (GET('interface') && GET('method')) {
    if (GET('method') == "deactivate" && GET('pid')) {
        unset($_SESSION["pid" . GET('pid') ]);
        //print "Unset ".GET('pid')."\n";
        
    } else {
        list($valid, $data) = validate_sids_str($_GET['sids_str']);
        if (!$valid) {
            echo $data;
        } elseif (GET('pid')) {
            $_SESSION["pid" . GET('pid') ] = $_GET['sids_str'];
        }
    }
    exit;
}
$db = new ossim_db();
$conn = $db->connect();
//
// Insert new
//
if (GET('action') == 'new') {
    $imported_plugins = get_checked_plugins($_POST);
    list($name, $descr, $plugins) = validate_post_params(POST('name') , POST('descr') , POST('sids'), $imported_plugins);
    // Insert section
    //
    $group_id = $conn->GenID('plugin_group_descr_seq');
    $conn->StartTrans();
    $sql = "INSERT INTO plugin_group_descr" . "(group_id, name, descr) " . "VALUES (?, ?, ?)";
    $conn->Execute($sql, array(
        $group_id,
        $name,
        $descr
    ));
    $sql = "INSERT IGNORE INTO plugin_group " . "(group_id, plugin_id, plugin_sid) " . "VALUES (?, ?, ?)";
    foreach($plugins as $plugin => $sids_str) {
        if ($sids_str == "ANY") $sids_str = "0";
        $conn->Execute($sql, array(
            $group_id,
            $plugin,
            $sids_str
        ));
    }
    //
    foreach($imported_plugins as $plugin => $sids_arr) {
    	$sids_str = implode(",",array_unique($sids_arr));
        $conn->Execute($sql, array(
            $group_id,
            $plugin,
            $sids_str
        ));
    }
    //
    $conn->CompleteTrans();
    if ($conn->HasFailedTrans()) {
        die($conn->ErrorMsg());
    }
    header("Location: modifyplugingroupsform.php?action=edit&id=$group_id".(GET('withoutmenu') == "1" ? "&withoutmenu=1" : ""));
    exit;
    //
    // Edit group
    //
    
} elseif (GET('action') == 'edit') {
    //print_r(POST('sids'));
    //print_r($_SESSION);
    $imported_plugins = get_checked_plugins($_POST);
    list($name, $descr, $plugins) = validate_post_params(POST('name') , POST('descr') , POST('sids'), $imported_plugins);
    $group_id = GET('id');
    ossim_valid($group_id, OSS_DIGIT, 'illegal:ID');
    if (ossim_error()) {
        die(ossim_error());
    }
    $conn->StartTrans();
    $sql = "UPDATE plugin_group_descr
            SET name=?, descr=?
            WHERE group_id=?";
    $conn->Execute($sql, array(
        $name,
        $descr,
        $group_id
    ));
    //
    foreach($plugins as $plugin => $sids_str) {
        if ($sids_str == "ANY") $sids_str = "0";
        $sids = explode(",",trim($sids_str));
        foreach ($sids as $sid) {
        	if ($sid==0) $imported_plugins[$plugin] = array($sid);
        	else $imported_plugins[$plugin][] = $sid;
        }
    }
    $conn->Execute("DELETE FROM plugin_group WHERE group_id=$group_id");
    $sql = "INSERT INTO plugin_group " . "(group_id, plugin_id, plugin_sid) " . "VALUES (?, ?, ?)";
    foreach($imported_plugins as $plugin => $sids_arr) {
    	$sids_str = implode(",",$sids_arr);
        $conn->Execute($sql, array(
            $group_id,
            $plugin,
            $sids_str
        ));
    }
    //
    $conn->CompleteTrans();
    if ($conn->HasFailedTrans()) {
        die($conn->ErrorMsg());
    }
    if (intval(POST('pluginid')) > 0) {
        header("Location: modifyplugingroupsform.php?action=edit&id=$group_id".(GET('withoutmenu') == "1" ? "&withoutmenu=1" : ""));
        exit;
    }
    //
    // Delete group
    //
    
} elseif (GET('action') == 'delete') {
    $group_id = GET('id');
    ossim_valid($group_id, OSS_DIGIT, 'illegal:ID');
    if (ossim_error()) {
        die(ossim_error());
    }
	if (Plugingroup::can_delete($conn,$group_id)) {
		$conn->StartTrans();
		$conn->Execute("DELETE FROM plugin_group WHERE group_id=$group_id");
		$conn->Execute("DELETE FROM policy_plugin_group_reference WHERE group_id=$group_id");
		$conn->Execute("DELETE FROM plugin_group_descr WHERE group_id=$group_id");
		$conn->CompleteTrans();
		if ($conn->HasFailedTrans()) {
			die($conn->ErrorMsg());
		}
	}
	else { ?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
		  <title> <?php
		echo gettext("OSSIM Framework"); ?> </title>
		  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
		  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
		  <meta http-equiv="X-UA-Compatible" content="IE=7" />
		  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
		</head>
		<body>
        <? if (GET('withoutmenu') != "1" && $_SESSION["menu_sopc"]=="Plugin Groups") include ("../hmenu.php"); ?>
		<table align="center">
			<tr><td class="nobborder" style="text-align:center"><?=_("Sorry, cannot delete this Plugin Group because it belongs to a policy")?></td></tr>
			<tr><td class="nobborder" style="text-align:center"><input type="button" value="<?=_('Back')?>" class="btn" onclick="document.location.href='plugingroups.php'"></td></tr>
		</table>
		</body>
		</html>
	<? exit; }
}
header('Location: plugingroups.php'.($group_id!="" ? "?id=$group_id" : ""));
?>
