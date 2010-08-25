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
Session::logcheck("MenuConfiguration", "ConfigurationPlugins");
// load column layout
require_once ('../conf/layout.php');
$category = "conf";
$name_layout = "plugin_layout";
$layout = load_layout($name_layout, $category);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title> <?php
echo gettext("Priority and Reliability configuration"); ?> </title>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
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
	<table id="flextable" style="display:none"></table>
	<script>
	function get_width(id) {
		if (typeof(document.getElementById(id).offsetWidth)!='undefined') 
			return document.getElementById(id).offsetWidth-20;
		else
			return 700;
	}
	function save_layout(clayout) {
		$("#flextable").changeStatus('<?=_('Saving column layout')?>...',false);
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
	$(document).ready(function() {
		$("#flextable").flexigrid({
			url: 'getpluginref.php',
			dataType: 'xml',
			colModel : [
			<?php
$default = array(
    "Action" => array(
        _('Action'),
        50,
        'false',
        'center',
        false
    ) ,
    "name" => array(
        _('Plugin Name'),
        100,
        'true',
        'center',
        false
    ) ,
    "sid name" => array(
        _('Plugin Sid Name'),
        150,
        'true',
        'center',
        false
    ) ,
    "ref name" => array(
        _('Ref Name'),
        100,
        'true',
        'left',
        false
    ) ,
	"ref sid name" => array(
        _('Ref Sid Name'),
        120,
        'true',
        'left',
        false
    )
);
list($colModel, $sortname, $sortorder) = print_layout($layout, $default, "id", "asc");
echo "$colModel\n";
?>
				],
			searchitems : [
				{display: '<?=_('Plugin Name')?>', name : 'name', isdefault: true},
				{display: '<?=_('Plugin Sid Name')?>', name : 'sid name'}
				],
			sortname: "<?php echo $sortname
?>",
			sortorder: "<?php echo $sortorder
?>",
			usepager: true,
			title: '<?=_('EDIT RULES')?>',
			pagestat: '<?=_('Displaying {from} to {to} of {total} rules')?>',
			nomsg: '<?=_('No rules')?>',
			useRp: true,
			rp: 50,
			showTableToggleBtn: true,
			singleSelect: true,
			width: get_width('headerh1'),
			height: 330,
			onColumnChange: save_layout,
			onEndResize: save_layout
		});   
	});
	</script>

</body>
</html>
