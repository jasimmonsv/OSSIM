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
Session::logcheck("MenuPolicy", "PolicyPorts");
// load column layout
require_once ('../conf/layout.php');
$category = "policy";
$name_layout = "ports_layout";
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
        
        <!-- Right Click Menu -->
	<ul id="myMenu" class="contextMenu" style="width:130px">
	    <li class="hostreport"><a href="#modify" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_edit.png" align="absmiddle"/> <?=_("Modify")?></a></li>
            <li class="hostreport"><a href="#delete" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_row_delete.png" align="absmiddle"/> <?=_("Delete")?></a></li>
            <li class="hostreport"><a href="#newport" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_row_insert.png" align="absmiddle"/> <?=_("New Port")?></a></li>
            <li class="hostreport"><a href="#newpgroup" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_row_insert.png" align="absmiddle"/> <?=_("New Port Group")?></a></li>
        </ul>

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
		if (com=='<?=_("Delete selected")?>') {
			//Delete host by ajax
			if (typeof(items[0]) != 'undefined') {
				$("#flextable").changeStatus('<?=_("Deleting port group")?>...',false);
				$.ajax({
						type: "GET",
						url: "deleteport.php?confirm=yes&portname="+urlencode(items[0].id.substr(3)),
						data: "",
						success: function(msg) {
							if(msg.match("ERROR_CANNOT")) alert("<?=_("Sorry, cannot delete this port because it belongs to a policy")?>");
							else $("#flextable").flexReload();
						}
				});
			}
			else alert('<?=_("You must select a port group")?>');
		}
		else if (com=='<?=_("Modify")?>') {
			if (typeof(items[0]) != 'undefined') document.location.href = 'newportform.php?portname='+urlencode(items[0].id.substr(3))
			else alert('<?=_("You must select a port group")?>');
		}
		else if (com=='<?=_("New port group")?>') {
			document.location.href = 'newportform.php'
		}
		else if (com=='<?=_("New port")?>') { 
			document.location.href = 'newsingleportform.php'
		}
	}
	function save_layout(clayout) {
		$("#flextable").changeStatus('<?=_("Saving column layout")?>...',false);
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
           var port = id;

            if (com=='modify') {
                if (typeof(port) != 'undefined')
                    document.location.href = 'newportform.php?portname='+urlencode(port);
                else
                  alert('<?=_("Port unselected")?>');
            }


            if (com=='delete') {

                if (typeof(port) != 'undefined') {
                        $("#flextable").changeStatus('<?=_("Deleting port group")?>...',false);
                        $.ajax({
                                        type: "GET",
                                        url: "deleteport.php?confirm=yes&portname="+urlencode(port),
                                        data: "",
                                        success: function(msg) {
                                                if(msg.match("ERROR_CANNOT")) alert("<?=_("Sorry, cannot delete this port because it belongs to a policy")?>");
                                                else $("#flextable").flexReload();
                                        }
                        });
                }
                else alert('<?=_("Port Group unselected")?>');
            }

            if (com == 'newport')
              document.location.href = 'newsingleportform.php';

            if (com == 'newpgroup')
              document.location.href = 'newportform.php';


	}





	$("#flextable").flexigrid({
		url: 'getport.php',
		dataType: 'xml',
		colModel : [
		<?php
$default = array(
    "name" => array(
        _("Port group"),
        180,
        'true',
        'left',
        false
    ) ,
    "ports" => array(
        _("Ports"),
        430,
        'false',
        'left',
        false
    ) ,
    "desc" => array(
        _("Description"),
        300,
        'false',
        'left',
        false
    )
);
list($colModel, $sortname, $sortorder, $height) = print_layout($layout, $default, "name", "asc", 300);
echo "$colModel\n";
?>
			],
		buttons : [
			{name: '<?=_("New port group")?>', bclass: 'add', onpress : action},
			{separator: true},
			{name: '<?=_("New port")?>', bclass: 'add', onpress : action},
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
		title: '<?=_("PORTS & PORT GROUPS")?>',
		pagestat: '<?=_("Displaying")?> {from} <?=_("to")?> {to} <?=_("of")?> {total} <?=_("port groups")?>',
		nomsg: '<?=_("No port groups")?>',
		useRp: true,
		rp: 20,
        contextMenu: 'myMenu',
		onContextMenuClick: menu_action,
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
