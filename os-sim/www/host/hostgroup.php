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
Session::logcheck("MenuPolicy", "PolicyHosts");
// load column layout
require_once ('../conf/layout.php');
$category = "policy";
$name_layout = "host_group_layout";
$layout = load_layout($name_layout, $category);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <meta http-equiv="X-UA-Compatible" content="IE=7" />
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <link rel="stylesheet" type="text/css" href="../style/flexigrid.css"/>
  <link rel="stylesheet" type="text/css" href="../style/greybox.css"/>  
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/jquery.flexigrid.js"></script>
  <script type="text/javascript" src="../js/urlencode.js"></script>
  <script type="text/javascript" src="../js/greybox.js"></script>
    
</head>
<body>


	<?php include ("../hmenu.php"); ?>
	<div  id="headerh1" style="width:100%;height:1px">&nbsp;</div>

	<table id="flextable" style="display:none"></table>

	<ul id="myMenu" class="contextMenu" style="width:150px">
	    <li class="hostreport"><a href="#modify" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_edit.png" align="absmiddle"> <?=_("Modify")?></a></li>
	    <li class="hostreport"><a href="#delete" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_row_delete.png" align="absmiddle"> <?=_("Delete")?></a></li>
	    <li class="hostreport"><a href="#nagios" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_sort.png" align="absmiddle"> <?=_("Enable/Disable <b>Nagios</b>")?></a></li>
	    <li class="hostreport"><a href="#new" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_row_insert.png" align="absmiddle"> <?=_("New Host Group")?></a></li>
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
	<script>
	GB_TYPE = 'w';
	function GB_onclose() {
	}
	function GB_edit(url) {
		GB_show("<?=_("Knowledge DB")?>",url,"60%","80%");
		return false;
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
		if (com=='<?=_("Delete selected")?>') {
			//Delete host by ajax
			if (typeof(items[0]) != 'undefined') {
				$("#flextable").changeStatus('<?=_("Deleting host group")?>...',false);
				$.ajax({
						type: "GET",
						url: "deletehostgroup.php?confirm=yes&name="+urlencode(items[0].id.substr(3)),
						data: "",
						success: function(msg) {
							if(msg.match("ERROR_CANNOT")) alert("<?=_("Sorry, cannot delete this host group because it belongs to a policy")?>");
							else $("#flextable").flexReload();
						}
				});
			}
			else alert('<?=_("You must select a host group")?>');
		}
		else if (com=='<?php echo _("Delete Group & Hosts")?>') {
			//Delete host by ajax
			if (typeof(items[0]) != 'undefined') {
				$("#flextable").changeStatus('<?php echo _("Deleting host group")?>...',false);
				$.ajax({
						type: "GET",
						url: "deletehostgroup.php?confirm=yes&name="+urlencode(items[0].id.substr(3))+"&type=groupAndHosts",
						data: "",
						success: function(msg) {
							if(msg.match("ERROR_CANNOT")) alert("<?=_("Sorry, cannot delete this host group because it belongs to a policy")?>");
							else $("#flextable").flexReload();
						}
				});
			}
			else alert('<?=_("You must select a host group")?>');
		}		
		else if (com=='<?=_("Modify")?>') {
			if (typeof(items[0]) != 'undefined') document.location.href = 'newhostgroupform.php?name='+urlencode(items[0].id.substr(3))
			else alert('<?=_("You must select a host group")?>');
		}
		else if (com=='<?=_("New")?>') {
			document.location.href = 'newhostgroupform.php'
		}
		if (com=='<?=_("Enable/Disable <b>Nagios</b>")?>') {
			// Enable/Disable Nessus via ajax
			if (typeof(items[0]) != 'undefined') {
				$("#flextable").changeStatus('<?=_("Toggle Nessus")?>...',false);
				$.ajax({
						type: "GET",
						url: "gethostgroup.php?nessus_action=toggle&host_group_name="+urlencode(items[0].id.substr(3)),
						data: "",
						success: function(msg) {
							$("#flextable").flexReload();
						}
				});
			}
			else alert('<?=_("You must select a host group")?>');
		}
		if (com=='Enable/Disable <b>Nagios</b>') {
			// Enable/Disable Nagios via ajax
			if (typeof(items[0]) != 'undefined') {
				$("#flextable").changeStatus('<?=_("Toggle Nagios")?>...',false);
				$.ajax({
						type: "GET",
						url: "gethostgroup.php?nagios_action=toggle&host_group_name="+urlencode(items[0].id.substr(3)),
						data: "",
						success: function(msg) {
							$("#flextable").flexReload();
						}
				});
			}
			else alert('<?=_("You must select a host group")?>');
		}
	}

        function menu_action(com,id,fg,fp) {

            var hostname = id;

            if (com=='modify') {
                    
                if (typeof(hostname) != 'undefined')
                    document.location.href = 'newhostgroupform.php?name='+urlencode(hostname)
                else
                  alert('<?=_("Host Group unselected")?>');
            }


            if (com=='delete') {
                    
                if (typeof(hostname) != 'undefined') {
                            $("#flextable").changeStatus('<?=_("Deleting host group")?>...',false);
                            $.ajax({
                                            type: "GET",
                                            url: "deletehostgroup.php?confirm=yes&name="+urlencode(hostname),
                                            data: "",
                                            success: function(msg) {
                                                    if(msg.match("ERROR_CANNOT")) alert("<?=_("Sorry, cannot delete this host group because it belongs to a policy")?>");
                                                    else $("#flextable").flexReload();
                                            }
                            });
                }
                else alert('<?=_("Host Group unselected")?>');
            }

            if (com=='nagios') {
                // Enable/Disable Nagios via ajax
                if (typeof(hostname) != 'undefined') {
                        $("#flextable").changeStatus('<?=_("Toggle Nagios")?>...',false);
                        $.ajax({
                                        type: "GET",
                                        url: "gethostgroup.php?nagios_action=toggle&host_group_name="+urlencode(hostname),
                                        data: "",
                                        success: function(msg) {
                                                $("#flextable").flexReload();
                                        }
                        });
                }
                else alert('<?=_("Host Group unselected")?>');
            }
            
            if (com == 'new')
              document.location.href = 'newhostgroupform.php';

	}


	function save_layout(clayout) {
		$("#flextable").changeStatus('<?_("Saving column layout")?>...',false);
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
		url: 'gethostgroup.php',
		dataType: 'xml',
		colModel : [
		<?php
$default = array(
    "name" => array(
        _("Host Group"),
        165,
        'true',
        'left',
        false
    ) ,
    "hosts" => array(
        _("Hosts"),
        200,
        'false',
        'left',
        false
    ) ,
    "desc" => array(
        _("Description"),
        220,
        'false',
        'left',
        false
    ) ,
    "sensors" => array(
        _("Sensors"),
        225,
        'false',
        'center',
        false
    ) ,

    "threshold_c" => array(
        _("Thr_C"),
        40,
        'true',
        'center',
        true
    ) ,
    "threshold_a" => array(
        _("Thr_A"),
        40,
        'true',
        'center',
        true
    ) ,
 /*   "nessus" => array(
        _("Nessus"),
        40,
        'false',
        'center',
        false
    ) ,*/
       
    "repository" => array(
        _("Knowledge DB"),
        80,
        'false',
        'center',
        false
    ),
    "nagios" => array(
        _("Nagios"),
        50,
        'false',
        'center',
        false
    ) 
);
list($colModel, $sortname, $sortorder, $height) = print_layout($layout, $default, "name", "asc", 0);
echo "$colModel\n";
?>
			],
		buttons : [
			{name: '<?=_("New")?>', bclass: 'add', onpress : action},
			{separator: true},
			{name: '<?=_("Modify")?>', bclass: 'modify', onpress : action},
			{separator: true},
			{name: '<?=_("Delete selected")?>', bclass: 'delete', onpress : action},
			{separator: true},
			{name: '<?php echo _("Delete Group & Hosts")?>', bclass: 'delete', onpress : action},
			{separator: true},
			//{name: '<?=_("Enable/Disable")?> <b><?=_("Nessus")?></b>', bclass: 'various', onpress : action},
			//{separator: true},
			{name: '<?=_("Enable/Disable <b>Nagios</b>")?>', bclass: 'various', onpress : action},
			{separator: true}
			],
            searchitems : [
			{display: '<?=_("Host group name")?>', name : 'name', isdefault: true},
			{display: '<?=_("IP")?>', name : 'ip'}
			],
		sortname: "<?php echo $sortname
?>",
		sortorder: "<?php echo $sortorder
?>",
		usepager: true,
		title: '<?=_("Host groups")?>',
		pagestat: '<?=_("Displaying {from} to {to} of {total} host groups")?>',
		nomsg: '<?=_("No host groups")?>',
		useRp: true,
		rp: 20,
		showTableToggleBtn: true,
                contextMenu: 'myMenu',
                onContextMenuClick: menu_action,
		singleSelect: true,
		width: get_width('headerh1'),
		height: get_height(),
		onColumnChange: save_layout,
		onEndResize: save_layout
	});



	
	</script>

</body>
</html>
