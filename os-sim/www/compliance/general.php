<?php
/*****************************************************************************
*
*    License:
*
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
Session::logcheck("MenuIntelligence", "ComplianceMapping");
// load column layout
require_once ('../conf/layout.php');
$category = "report";
$name_layout = "compliance";
$layout = load_layout($name_layout, $category);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title> <?php echo gettext("OSSIM Framework"); ?> - Compliance </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
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
<script type="text/javascript">
	function get_width(id) {
		if (typeof(document.getElementById(id).offsetWidth)!='undefined') 
			return document.getElementById(id).offsetWidth-20;
		else
			return 700;
	}
	function action(com,grid) {
		var items = $('.trSelected', grid);
		if (com=='<?php echo gettext("Delete selected"); ?>') {
			//Delete host by ajax
			if (typeof(items[0]) != 'undefined') {
				$("#flextable").changeStatus('<?= _('Deleting property...')?>',false);
				$.ajax({
						type: "GET",
						url: "deletegeneral.php?confirm=yes&sid="+urlencode(items[0].id.substr(3)),
						data: "",
						success: function(msg) {
							$("#flextable").flexReload();
						}
				});
			}
			else alert('<?=_('You must select a property')?>');
		}
		else if (com=='<?php echo gettext("Modify"); ?>') {
			if (typeof(items[0]) != 'undefined') document.location.href = 'modifygeneralform.php?sid='+urlencode(items[0].id.substr(3))
			else alert('<?=_('You must select a property')?>');
		}
		else if (com=='<?php echo gettext("Insert new"); ?>') {
			document.location.href = 'newgeneralform.php'
		}
		else if (com=='Apply') {
			document.location.href = '../conf/reload.php?what=hosts&back=<?php echo urlencode($_SERVER["REQUEST_URI"]); ?>'
		}
	}
	function save_layout(clayout) {
		$("#flextable").changeStatus('<?=_('Saving column layout...')?>',false);
		$.ajax({
				type: "POST",
				url: "../conf/layout.php",
				data: { name:"<?php echo $name_layout ?>", category:"<?php echo $category ?>", layout:serialize(clayout) },
				success: function(msg) {
					$("#flextable").changeStatus(msg,true);
				}
		});
	}
	function menu_action(com,id,fg,fp) {
		
	}
var de = document.documentElement;
var h = document.body.scrollHeight
if ((self.innerHeight+window.scrollMaxY) > h) h = self.innerHeight+window.scrollMaxY;
if (de && de.clientHeight > h) h = de.clientHeight;
if (document.body.clientHeight > h) h = document.body.clientHeight;
$("#flextable").flexigrid({
		url: 'getgeneral.php',
		dataType: 'xml',
		colModel : [
		<?php
$default = array(
    "sid" => array(
        _('SID'),
        30,
        'true',
        'center',
        false
    ) ,
	"plugin_name" => array(
        _('Plugin'),
        250,
        'true',
        'left',
        false
    ) ,
    "targeted" => array(
        _('Targeted'),
        50,
        'true',
        'center',
        false
    ) ,
    "untargeted" => array(
        _('Untargeted'),
        60,
        'true',
        'center',
        false
    ) ,
    "approach" => array(
        _('Approach'),
        50,
        'false',
        'center',
        false
    ) ,
    "exploration" => array(
        _('Exploration'),
        50,
        'false',
        'center',
        false
    ) ,
    "penetration" => array(
        _('Penetration'),
        50,
        'false',
        'center',
        false
    ) ,
    "generalmalware" => array(
        _('General Malware'),
        70,
        'false',
        'center',
        false
    ) ,
    "imp_qos" => array(
        _('Impact: QOS'),
        40,
        'false',
        'center',
        false
    ) ,
    "imp_infleak" => array(
        _('Impact: Infleak'),
        50,
        'false',
        'center',
        false
    ) ,
    "imp_lawful" => array(
        _('Impact: Lawful'),
        60,
        'false',
        'center',
        false
    ) ,
    "imp_image" => array(
        _('Impact: Image'),
        50,
        'false',
        'center',
        false
    ) ,
    "imp_financial" => array(
        _('Impact: Financial'),
        60,
        'false',
        'center',
        false
    ) ,
    "D" => array(
        _('Availability'),
        60,
        'false',
        'center',
        false
    ) ,
    "I" => array(
        _('Integrity'),
        50,
        'false',
        'center',
        false
    ) ,
    "C" => array(
        _('Confidentiality'),
        70,
        'false',
        'center',
        false
    ) ,
    "net_anomaly" => array(
        _('Network anomaly'),
        60,
        'false',
        'center',
        false
    )
);
list($colModel, $sortname, $sortorder, $height) = print_layout($layout, $default, "name", "asc", 300);
echo "$colModel\n";
?>
			],
		buttons : [
			{name: '<?=_('Insert new')?>', bclass: 'add', onpress : action},
			{separator: true},
			{name: '<?=_('Delete selected')?>', bclass: 'delete', onpress : action},
			{separator: true},
			{name: '<?=_('Modify')?>', bclass: 'modify', onpress : action},
			{separator: true}
			],
		sortname: "<?php echo $sortname ?>",
		sortorder: "<?php echo $sortorder ?>",
		usepager: true,
		title: '<?=_('PROPERTIES')?>',
		nomsg: '<?=_('No objects')?>',
		pagestat: '<?=_("Displaying {from} to {to} of {total}")?>',
		useRp: true,
		rp: 25,
		contextMenu: 'myMenu',
		onContextMenuClick: menu_action,
		showTableToggleBtn: true,
		singleSelect: true,
		width: get_width('headerh1'),
		height: h-175,
		onColumnChange: save_layout,
		onEndResize: save_layout
	});   
	
	</script>
</body>
</html>
