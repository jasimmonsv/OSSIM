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
require_once ('classes/Session.inc');
require_once ('classes/Plugin.inc');

require_once ('ossim_db.inc');

Session::logcheck("MenuConfiguration", "ConfigurationPlugins");
// load column layout
require_once ('../conf/layout.php');
$category = "conf";
$name_layout = "pluginsid_layout";
$layout = load_layout($name_layout, $category);
//
require_once 'classes/Security.inc';
$id = GET('id');
ossim_valid($id, OSS_ALPHA, 'illegal:' . _("id"));
if (ossim_error()) {
    die(ossim_error());
}

$db = new ossim_db();
$conn = $db->connect();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title> <?php
echo gettext("Event types"); ?> </title>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <link rel="stylesheet" type="text/css" href="../style/flexigrid.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/jquery.flexigrid.js"></script>
  <script type="text/javascript" src="../js/urlencode.js"></script>
</head>
<body>

	<?php include ("../hmenu.php"); ?>
	<div  id="headerh1" style="width:100%;height:1px">&nbsp;</div>

	<!-- Right Click Menu -->
	<ul id="myMenu" class="contextMenu" style="width:110px">
		<li class="hostreport"><a href="#modify" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_edit.png" align="absmiddle"/> <?php echo _("Edit")?></a></li>
    </ul>
	
	<style>
		table, th, tr, td {
			background:transparent;
			border-radius: 0px;
			-moz-border-radius: 0px;
			-webkit-border-radius: 0px;
			border:none;
			padding:0px; margin:0px;
		}
		input, select {
			border-radius: 0px;
			-moz-border-radius: 0px;
			-webkit-border-radius: 0px;
			border: 1px solid #8F8FC6;
			font-size:12px; font-family:arial; vertical-align:middle;
			padding:0px; margin:0px;
		}
	</style>
	<form onsubmit="return false" style="margin:0 auto" name="fc">
	<input type=hidden name="pri" value="">
	<input type=hidden name="rel" value="">
	</form>
	
	<form onsubmit="return false" style="margin:0 auto" name="fv">
	<table id="flextable" style="display:none"></table>
	</form>
	
	<script>
	function change_pri_rel(sid) {
		pri = document.getElementById('priority'+sid)
		rel = document.getElementById('reliability'+sid)
		document.fc.pri.value = pri.options[pri.selectedIndex].value
		document.fc.rel.value = rel.options[rel.selectedIndex].value
		$("#flextable").changeStatus('<?=_('Updating Priority and Reliability')?>...',true);
		$.ajax({
			type: "GET",
			url: "pluginupdate.php?id=<?php echo $id ?>&sid="+sid+"&priority="+document.fc.pri.value+"&reliability="+document.fc.rel.value,
			data: "",
			success: function(msg) {
				$("#flextable").flexReload();
			}
		});
	}
	function get_width(id) {
		if (typeof(document.getElementById(id).offsetWidth)!='undefined') 
			return document.getElementById(id).offsetWidth-20;
		else
			return 700;
	}
	function get_height() {
	   return parseInt($(document).height()) - 200;
	}
	function action(com,grid) {
		var items = $('.trSelected', grid);
		if (com=='<?=("Edit")?>') {
			if (typeof(items[0]) != 'undefined') {
				url = "modifypluginsid.php?id=<?php echo $id ?>&sid="+urlencode(items[0].id.substr(3));
				document.location.href = url;
			}
			else alert('<?=_('You must select a event type')?>');
		}
        else if (com=='<?=("Insert new event type")?>') {
			document.location.href = 'newpluginsidform.php?plugin=<?php echo $id ?>';
		}
	}
	function save_layout(clayout) {
		$("#flextable").changeStatus('<?=_('Saving column layout')?>...',false);
		$.ajax({
				type: "POST",
				url: "../conf/layout.php",
				data: { name:"<?php echo $name_layout ?>", category:"<?php echo $category ?>", layout:serialize(clayout) },
				success: function(msg) {
					$("#flextable").changeStatus(msg,true);
				}
		});
	}
    function linked_to(rowid) {
        document.location.href = 'modifypluginsid.php?id=<?php echo $id; ?>&sid='+urlencode(rowid);
    }	
	function menu_action(com,plugin_id) {
            if (com=='modify') {
                if (typeof(plugin_id) != 'undefined')
                    document.location.href = 'modifypluginsid.php?id=<?php echo $id; ?>&sid='+plugin_id;
                else
                  alert('<?php echo _('You must select a event type')?>');
            }		
	}
	$(document).ready(function() {
		$("#flextable").flexigrid({
			url: 'getpluginsid.php?id=<?php echo $id ?>',
			dataType: 'xml',
			colModel : [
			<?php
$default = array(
    "plugin_id" => array(
        _('Data Source ID'),
        100,
        'false',
        'center',
        false
    ) ,
    "sid" => array(
        _('Event type ID'),
        100,
        'true',
        'center',
        false
    ) ,
    "category" => array(
        _('Category'),
        120,
        'false',
        'center',
        false
    ) ,
	"subcategory" => array(
        _('Subcategory'),
        120,
        'false',
        'center',
        false
    ) ,
    "class" => array(
        _('Class'),
        120,
        'false',
        'center',
        false
    ) ,
    "name" => array(
        _('Name'),
        300,
        'true',
        'left',
        false
    ) ,
    "priority" => array(
        _('Priority'),
        70,
        'true',
        'center',
        false
    ) ,
    "reliability" => array(
        _('Reliability'),
        70,
        'true',
        'center',
        false
    )
);
list($colModel, $sortname, $sortorder) = print_layout($layout, $default, "sid", "asc");
echo "$colModel\n";
?>
				],
			buttons : [
                {name: '<?=_("Insert new event type")?>', bclass: 'add', onpress : action},
                {separator: true},
				{name: '<?=_("Edit")?>', bclass: 'modify', onpress : action},
				{separator: true}
				],
			searchitems : [
				{display: 'Name', name : 'name', isdefault: true}
				],
			sortname: "<?php echo $sortname ?>",
			sortorder: "<?php echo $sortorder ?>",
			usepager: true,
			title: 'EVENT TYPES (<?php echo $id ?>, <?php echo Plugin::get_name_by_id($conn, $id)?>) &nbsp;&nbsp; <a href="javascript:history.go(-1)" style="text-decoration:underline;color:black"><< <?=_('back to Data Source')?></a>',
			pagestat: '<?=_('Displaying {from} to {to} of {total} event types')?>',
			nomsg: '<?=_('No event types')?>',
			useRp: true,
			rp: 25,
			contextMenu: 'myMenu',
			onContextMenuClick: menu_action,
			showTableToggleBtn: true,
			width: get_width('headerh1'),
			height: get_height(),
			onColumnChange: save_layout,
			onDblClick: linked_to,
			onEndResize: save_layout
		});   
	});
	</script>

</body>
</html>
<?php
$db->close($conn);
?>
