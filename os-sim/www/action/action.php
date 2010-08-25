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
Session::logcheck("MenuIntelligence", "PolicyActions");
// load column layout
require_once ('../conf/layout.php');
$category = "policy";
$name_layout = "actions_layout";
$layout = load_layout($name_layout, $category);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <meta http-equiv="X-UA-Compatible" content="IE=7" />
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <link rel="stylesheet" type="text/css" href="../style/flexigrid.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/jquery.flexigrid.js"></script>
  <script type="text/javascript" src="../js/urlencode.js"></script>
</head>
<body>
                                                                                
	<?php
include ("../hmenu.php"); ?>
	<div  id="headerh1" style="width:100%;height:1px">&nbsp;</div>

	<table class="noborder">
	<tr><td valign="top">
		<table id="flextable" style="display:none"></table>
	</td><tr>
	</table>
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
	<script>
	function get_width(id) {
		if (typeof(document.getElementById(id).offsetWidth)!='undefined') 
			return document.getElementById(id).offsetWidth-5;
		else
			return 700;
	}
        function get_height()
        {
		return parseInt($(document).height()) - 200;
	}
	function action(com,grid) {
		var items = $('.trSelected', grid);
		if (com=='Delete selected') {
			//Delete host by ajax
			if (typeof(items[0]) != 'undefined') {
				$("#flextable").changeStatus('<?=_("Deleting action")?>...',false);
				$.ajax({
						type: "GET",
						url: "deleteaction.php?confirm=yes&id="+urlencode(items[0].id.substr(3)),
						data: "",
						success: function(msg) {
							$("#flextable").flexReload();
						}
				});
			}
			else alert('<?=_("You must select a action")?>');
		}
		else if (com=='Modify') {
			if (typeof(items[0]) != 'undefined') document.location.href = 'modifyactionform.php?id='+urlencode(items[0].id.substr(3))
			else alert('<?=_("You must select a action")?>');
		}
		else if (com=='New') {
			document.location.href = 'newactionform.php'
		}
	}
	function save_layout(clayout) {
		$("#flextable").changeStatus('<?=_("Saving column layout")?>...',false);
		$.ajax({
				type: "POST",
				url: "../conf/layout.php",
				data: { name:"<?php echo $name_layout
?>", category:"<?php echo $category
?>", layout:serialize(clayout) },
				success: function(msg) {
					$("#flextable").changeStatus(msg,true);
				}
		});
	}
	$("#flextable").flexigrid({
		url: 'getaction.php',
		dataType: 'xml',
		colModel : [
		<?php
$default = array(
    "descr" => array(
        _('Description'),
        700,
        'true',
        'left',
        false
    ) ,
    "type" => array(
        _('Type'),
        100,
        'true',
        'left',
        false
    ) ,
);
list($colModel, $sortname, $sortorder, $height) = print_layout($layout, $default, "descr", "asc", 300);
echo "$colModel\n";
?>
			],
		buttons : [
			{name: '<?=_("New")?>', bclass: 'add', onpress : action},
			{separator: true},
			{name: '<?=_("Modify")?>', bclass: 'modify', onpress : action},
			{separator: true},
			{name: '<?=_("Delete selected")?>', bclass: 'delete', onpress : action},
			{separator: true}
			],
		sortname: "<?php echo $sortname
?>",
		sortorder: "<?php echo $sortorder
?>",
		usepager: true,
		title: '<?=_("Actions")?>',
		pagestat: '<?=_("Displaying")?> {from} <?=_("to")?> {to} <?=_("of")?> {total} <?=_("Actions")?>',
		nomsg: '<?=_("No Actions")?>',
		useRp: true,
		rp: 20,
		showTableToggleBtn: true,
		singleSelect: true,
		width: get_width('headerh1'),
		height: get_height(),
		onColumnChange: save_layout,
		onEndResize: save_layout
	});   
	
	</script>

</body>
</html>

