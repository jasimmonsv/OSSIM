<?
/*****************************************************************************
*
*    License:
*
*   Copyright (c) 2007-2010 AlienVault
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
require_once('classes/Session.inc');
require_once('classes/Security.inc');
require_once('classes/Event_viewer.inc');
require_once ('ossim_conf.inc');
$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);
$opensource = (!preg_match("/pro|demo/i",$version)) ? true : false;

$msg = "";
$edit = GET('edit');
$save = GET('save');
$forcesave = GET('forcesave');
$name = GET('name');
$oldname = GET('oldname');
$columns = GET('selected_cols');
$save_criteria = (GET('save_criteria') != "") ? 1 : 0;
ossim_valid($edit, OSS_NULLABLE, OSS_DIGIT, "Invalid: edit");
ossim_valid($save, OSS_NULLABLE, OSS_ALPHA, "Invalid: save");
ossim_valid($forcesave, OSS_NULLABLE, OSS_DIGIT, "Invalid: forcesave");
ossim_valid($name, OSS_NULLABLE, OSS_ALPHA, OSS_SPACE, OSS_PUNC, "Invalid: name");
ossim_valid($oldname, OSS_NULLABLE, OSS_ALPHA, OSS_SPACE, OSS_PUNC, "Invalid: oldname");
ossim_valid($columns, OSS_NULLABLE, OSS_ALPHA, OSS_PUNC, "Invalid: columns");
ossim_valid($save_criteria, OSS_NULLABLE, OSS_DIGIT, "Invalid: save criteria");
$columns_arr = explode(",",$columns);
if (ossim_error()) {
    die(ossim_error());
}
// New View
if ($save == "insert") {
	if ($name == "") {
		$msg = "<font style='color:red'>"._("Please, insert a name for the view.")."</font>";
	} elseif ($columns == "") {
		$msg = "<font style='color:red'>"._("You must select one column at least.")."</font>";
	} elseif ($_SESSION['views'][$name] != "") {
		$msg = "<font style='color:red'><b>$name</b> "._("already exists, try another view name.")."</font>";
	} elseif($opensource && (in_array("PLUGIN_SOURCE_TYPE",$columns_arr) || in_array("PLUGIN_SID_CATEGORY",$columns_arr) || in_array("PLUGIN_SID_SUBCATEGORY",$columns_arr))) {
		$msg = "<font style='color:red'>"._("You can only select taxonomy columns in Pro version.")."</font>";
	} else {
		require_once('classes/User_config.inc');
		$login = Session::get_session_user();
		$db = new ossim_db();
		$conn = $db->connect();
		$config = new User_config($conn);
		// Columns
		$_SESSION['views'][$name]['cols'] = $columns_arr;
		// Filters
		if ($save_criteria) {
			$session_data = $_SESSION;
			foreach ($_SESSION as $k => $v) {
			if (preg_match("/^(_|black_list|current_cview|views|ports_cache|acid_|report_|graph_radar|siem_event|siem_current_query|siem_current_query_graph).*/",$k))
				unset($session_data[$k]);
			}
			$_SESSION['views'][$name]['data'] = $session_data;
		} else {
			$_SESSION['views'][$name]['data'] = array();
		}
		$config->set($login, 'custom_views', $_SESSION['views'], 'php', 'siem');
		$created = 1;
	}
// Edit the Current View
} elseif ($save == "modify") {
	if ($name == "") {
		$msg = "<font style='color:red'>"._("Please, insert a name for the view.")."</font>";
	} elseif($columns == "") {
		$msg = "<font style='color:red'>"._("You must select one column at least.")."</font>";
	//} elseif($opensource && (in_array("PLUGIN_SOURCE_TYPE",$columns_arr) || in_array("PLUGIN_SID_CATEGORY",$columns_arr) || in_array("PLUGIN_SID_SUBCATEGORY",$columns_arr))) {
	//	$msg = "<font style='color:red'>"._("You can only select taxonomy columns in Pro version.")."</font>";
	} else {
		require_once('classes/User_config.inc');
		$login = Session::get_session_user();
		$db = new ossim_db();
		$conn = $db->connect();
		$config = new User_config($conn);
		if ($name != $oldname) {
			//print_r($_SESSION['views'][$_SESSION['current_cview']]);
			$_SESSION['views'][$name]['data'] = $_SESSION['views'][$_SESSION['current_cview']]['data'];
			$_SESSION['current_cview'] = $name;
			unset($_SESSION['views'][$oldname]);
			$_SESSION['view_name_changed'] = $name; // Uses when closes greybox
		}
		$_SESSION['views'][$name]['cols'] = $columns_arr;
		if (!$save_criteria) {
			$_SESSION['views'][$name]['data'] = array();
		}
		$config->set($login, 'custom_views', $_SESSION['views'], 'php', 'siem');
		$edit = 1;
		$msg = "<font style='color:green'>"._("The view has been successfully updated.")."</font>";
	}
} elseif ($save == _("Default view")) { 
    require_once('classes/User_config.inc');
    $login = Session::get_session_user();
    $db = new ossim_db();
    $conn = $db->connect();
    $config = new User_config($conn);

    $_SESSION['views'][$name]['cols'] = array('SIGNATURE','DATE','IP_PORTSRC','IP_PORTDST','ASSET','PRIORITY','RELIABILITY','RISK','IP_PROTO');
    $config->set($login, 'custom_views', $_SESSION['views'], 'php', 'siem');
    $edit = 1;
    $msg = "<font style='color:green'>"._("The view has been successfully updated.")."</font>";
} elseif ($save == "delete") {
	if ($_SESSION['current_cview'] == "default") {
		$msg = "<font style='color:red'>"._("You cannot delete 'default' view.")."</font>";
	} else {
		require_once('classes/User_config.inc');
		$login = Session::get_session_user();
		$db = new ossim_db();
		$conn = $db->connect();
		$config = new User_config($conn);
		unset($_SESSION['views'][$_SESSION['current_cview']]);
		$config->set($login, 'custom_views', $_SESSION['views'], 'php', 'siem');
		$_SESSION['current_cview'] = "default";
		$deleted = 1;
	}
} elseif ($save == "report" && Session::am_i_admin()) {
	$columns = implode(",",$columns_arr);
	$query1 = $_SESSION['siem_current_query'];
	$query1 = preg_replace("/AND \( timestamp \>\='[^\']+'\s*AND timestamp \<\='[^\']+' \) /i","",$query1);
	$query1 = preg_replace("/AND \( timestamp \>\=\'[^\']+\' \)\s*/","",$query1);
	$query2 = $_SESSION['siem_current_query_graph'];
	$query2 = preg_replace("/AND \( timestamp \>\='[^\']+'\s*AND timestamp \<\='[^\']+' \) /i","",$query2);
	$query2 = preg_replace("/AND \( timestamp \>\=\'[^\']+\' \)\s*/","",$query2);
	
	if ($query1 != "" && $query2 != "" && $columns != "") {
		$db = new ossim_db();
		$conn = $db->connect();
		
		$curid = 0;
		$name = str_replace('"','',$name);
		$query = "SELECT id FROM custom_report_types WHERE name=\"$name\" and file='SIEM/CustomList.php'";
		if (!$rs = & $conn->Execute($query)) {
	            print $conn->ErrorMsg();
	    } else {
	        if (!$rs->EOF) {
	            $curid = $rs->fields['id'];
	        }
	    }
		
		$id = 3000;
	    $result = $conn->Execute("select max(id)+1 from custom_report_types where id>2999");
	    if ( !$result->EOF ) {
	        $id = intval($result->fields[0]);
	        if (!$id) $id=3000;
	    }
		
		if ($curid > 0) {
	    	$sql = "UPDATE custom_report_types SET name=\"$name\",type='Custom SIEM Events',file='SIEM/CustomList.php',inputs='Number of Events:top:text:OSS_DIGIT:25:250',custom_report_types.sql=\"$query1;$query2;$columns\" WHERE id=$curid";
		} else {
			$sql = "INSERT INTO custom_report_types (id,name,type,file,inputs,custom_report_types.sql) VALUES ($id,\"$name\",'Custom SIEM Events','SIEM/CustomList.php','Number of Events:top:text:OSS_DIGIT:25:250',\"$query1;$query2;$columns\")";
		}
		if ($conn->Execute($sql)) {
			$msg = ($curid > 0) ? "<font style='color:green'>"._("The report has been successfully updated")."</font>" : "<font style='color:green'>"._("The report has been successfully created as ")."'Custom SIEM Events - $name'"."</font>";
		} else {
			$msg = "<font style='color:red'>"._("Error creating a new report type.")."</font>";
		}
	} else {
		$msg = "<font style='color:red'>"._("Error creating a new report type.")."</font>";
	}
	
}
$tags = Event_viewer::get_tags();
//print_r($tags);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title> <?php echo gettext("SIEM Custom View"); ?> </title>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
    <link rel="stylesheet" type="text/css" href="../style/style.css"/>
    <link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css" />
    <link type="text/css" rel="stylesheet" href="../style/ui.multiselect.css" rel="stylesheet" />
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
    <script type="text/javascript" src="../js/jquery.tmpl.1.1.1.js"></script>
    <script type="text/javascript" src="../js/ui.multiselect.js"></script>
    <script type="text/javascript" src="../js/combos.js"></script>    
    <script type="text/javascript">
		$(document).ready(function(){
			$(".multiselect").multiselect({
				searchable: false,
				nodeComparator: function (node1,node2){ return 1 },
				dividerLocation: 0.5,
			});
			<?php if (Session::am_i_admin() && $forcesave) { ?>
			document.fcols.save.value='report';document.fcols.selected_cols.value=getselectedcombovalue('cols');document.fcols.submit();
			<?php } ?>
        });
    </script>
</head>
<body>
<table class="transparent" align="center">
<? if ($created) { ?>
<tr><td class="center nobborder"><?=_("The custom view has been successfully created.")?></td></tr>
<script type="text/javascript">parent.change_view('<?=$name?>')</script>
<? } elseif ($deleted) { ?>
<tr><td class="center nobborder"><?=_("The custom view has been deleted.")?></td></tr>
<script type="text/javascript">parent.change_view('default')</script>
<? } else { ?>
<form method="get" name="fcols">
<input type="hidden" name="edit" value="<?=$edit?>">
<input type="hidden" id="action" name="save" value="<?=($edit) ? "modify" : "insert"?>">
<input type="hidden" name="selected_cols" value="">
<input type="hidden" name="oldname" value="<?=$_SESSION['current_cview']?>">
	<tr><td class="center nobborder"><?=_("Select the <b>columns</b> to show in SIEM events listing")?></td></tr>
	<tr><td class="nobborder">
	<select id="cols" class="multiselect" multiple="multiple" name="columns[]">
    <? if ($edit) {
            $rel=0;
            foreach($_SESSION['views'][$_SESSION['current_cview']]['cols'] as $label) { ?>
                <option value="<?=$label?>" selected="selected"><?=($tags[$label] != "") ? $tags[$label] : $label?></option>
    <?      }
       		foreach($tags as $label => $descr) if (!in_array($label,$_SESSION['views'][$_SESSION['current_cview']]['cols'])) { ?>
        		<option value="<?=$label?>"><?=$descr?></option>
    		<? } 
       } else {
      		foreach($tags as $label => $descr) { ?>
       		<option value="<?=$label?>"><?=$descr?></option>
   		<? }
	   }
	?>
    </select>
	</td></tr>
	<tr><td class="center nobborder" id="msg">&nbsp;<?=$msg?></td></tr>
    <tr><td class="center nobborder"><input type="checkbox" name="save_criteria" value="1" checked></input> <?php echo _("Include custom search criteria in this predefined view") ?></td></tr>
    <tr><td class="center nobborder">
		<?php if ($_SESSION['current_cview'] == "default" && $edit) {?>
		<?=_("View Name")?>: <input type="text" value="default" style="color:gray;width:100px" disabled><input type="hidden" name="name" value="default">
		<?php } else {?>
		<?=_("View Name")?>: <input type="text" name="name" style="width:100px" value="<? if ($edit) echo $_SESSION['current_cview'] ?>" <? if ($edit) { ?>onkeyup="document.getElementById('saveasbutton').disabled='';document.getElementById('saveasbutton').style.color='black'"<?php }?>>
		<?php }?>
		<input type="button" onclick="document.fcols.selected_cols.value=getselectedcombovalue('cols');document.fcols.submit()" value="<?=($edit) ? _("Save") : _("Create")?>">
        <? if ($_SESSION['current_cview'] == "default") {?> &nbsp;<input type="button" onclick="$('#action').val('<?=_("Default view")?>');document.fcols.submit()" value="<?=_("Restore Default")?>"> <? } ?>  
		<? if ($edit && $_SESSION['current_cview'] != "default") { ?>&nbsp;<input type="button" onclick="document.fcols.save.value='insert';document.fcols.selected_cols.value=getselectedcombovalue('cols');document.fcols.submit()" value="<?php echo _("Save As")?>" id="saveasbutton" style="color:gray" disabled>&nbsp;<input type="button" onclick="if(confirm('<?=_("Are you sure?")?>')) { document.fcols.save.value='delete';document.fcols.submit() }" value="<?=_("Delete")?>"><? } ?>
		<?php if (Session::am_i_admin() && $edit) { ?>&nbsp;<input type="button" onclick="document.fcols.save.value='report';document.fcols.selected_cols.value=getselectedcombovalue('cols');document.fcols.submit()" value="<?=_("Save as Report")?>"><?php } ?>
		&nbsp;<input type="button" onclick="parent.GB_hide()" value="<?=_("Close window")?>">
		
	</td></tr>
</form>
<? } ?>
</table>
</body>
</html>
