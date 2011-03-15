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
//error_reporting(E_NOTICE);
require_once ('classes/Session.inc');
require_once ('classes/CIDR.inc');
Session::logcheck("MenuPolicy", "PolicyHosts");
// load column layout
require_once ('../conf/layout.php');
$category = "policy";
$name_layout = "host_layout";
$layout = load_layout($name_layout, $category);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
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

	<!-- Right Click Menu -->
	<ul id="myMenu" class="contextMenu" style="width:110px">
		<li class="hostreport"><a href="#hostreport" class="greybox" style="padding:3px"><img src="../pixmaps/reports.png" align="absmiddle"/> <?=_("Asset Report")?></a></li>
		<li class="hostreport"><a href="#modify" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_edit.png" align="absmiddle"/> <?=_("Modify")?></a></li>
		<li class="hostreport"><a href="#delete" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_row_delete.png" align="absmiddle"/> <?=_("Delete")?></a></li>
		<li class="hostreport"><a href="#duplicate" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_duplicate.png" align="absmiddle"/> <?=_("Duplicate")?></a></li>
		<li class="hostreport"><a href="#new" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_row_insert.png" align="absmiddle"/> <?=_("New Host")?></a></li>
		<li class="hostreport"><a href="#credentials" class="greybox" style="padding:3px"><img src="../pixmaps/tables/lock.png" align="absmiddle"/> <?=_("Credentials")?></a></li>
    </ul>
        	
	<table id="flextable" style="display:none"></table>

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
	function view_apps(ip){
		GB_show(ip+" Applications","view_hostapps.php?ip="+urlencode(ip),410,"70%");
		return false;
	}
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
                for (i=0;i<items.length;i++) {
                    $("#flextable").changeStatus('<?=_("Deleting host")?>...',false);
                    $.ajax({
                            type: "GET",
                            url: "deletehost.php?confirm=yes&ip="+urlencode(items[i].id.substr(3)),
                            data: "",
                            success: function(msg) {
                                if(msg.match("ERROR_CANNOT")) alert("<?=_("Sorry, cannot delete this host because it belongs to a policy")?>");
                                else $("#flextable").flexReload();
                            }
                    });
                }
            }
            else alert('You must select a host');
        }
		else if (com=='<?=_("Modify")?>') {
			if (typeof(items[0]) != 'undefined') document.location.href = 'modifyhostform.php?ip='+urlencode(items[0].id.substr(3));
			else alert('You must select a host');
		}
		else if (com=='<?=_("Duplicate selected")?>') {
			if (typeof(items[0]) != 'undefined') document.location.href = 'newhostform.php?ip='+urlencode(items[0].id.substr(3))+'&action=duplicate';
			else alert('You must select a host');
		}
		else if (com=='<?=_("New")?>') {
			document.location.href = 'newhostform.php';
		}
		else if (com=='<?=_("Apply")?>') {
			document.location.href = '../conf/reload.php?what=hosts&back=<?php echo urlencode($_SERVER["REQUEST_URI"]); ?>';
		}
		else if (com=='<?=_("Import CSV")?>') {
			document.location.href = 'import_hosts.php';
		}
		else if (com == '<?=_("Edit Credentials")?>')
		{
			if (typeof(items[0]) != 'undefined')  document.location.href = 'hostcredentialsform.php?ip='+urlencode(items[0].id.substr(3));
			else  alert('<?=_("Host unselected")?>');
		}
        else if (com=='<?=_("Select all")?>') {
            var rows = $("#flextable").find("tr").get();
            if(rows.length > 0) {
                $.each(rows,function(i,n) {
                    $(n).addClass("trSelected");
                });
            }
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
    function linked_to(rowid) {
        document.location.href = 'modifyhostform.php?ip='+urlencode(rowid);
    }
	function menu_action(com,id,fg,fp) {

            var ip = id;
            var hostname = id;

            if (com=='hostreport') {
                    var url = "../report/host_report.php?host="+ip+"&hostname="+hostname;
                    if (hostname == ip) var title = "Host Report: "+ip;
                    else var title = "Host Report: "+hostname+"("+ip+")";
                    //GB_show(title,url,450,'90%');
                    var wnd = window.open(url,'hostreport_'+ip,'fullscreen=yes,scrollbars=yes,location=no,toolbar=no,status=no,directories=no');
                    wnd.focus()
            }

            if (com=='modify') {
                if (typeof(ip) != 'undefined')
                    document.location.href = 'modifyhostform.php?ip='+urlencode(ip);
                else
                  alert('<?=_("Host unselected")?>');
            }
                

            if (com=='duplicate') {
                if (typeof(ip) != 'undefined')
                   document.location.href = 'newhostform.php?ip='+urlencode(ip)+'&action=duplicate';
                 else
                   alert('<?=_("Host unselected")?>');
            }

            if (com=='delete') {

                if (typeof(ip) != 'undefined') {
                        $("#flextable").changeStatus('<?=_("Deleting host")?>...',false);
                        $.ajax({
                                        type: "GET",
                                        url: "deletehost.php?confirm=yes&ip="+urlencode(ip),
                                        data: "",
                                        success: function(msg) {
                                                if(msg.match("ERROR_CANNOT")) alert("<?=_("Sorry, cannot delete this host because it belongs to a policy")?>");
                                                else $("#flextable").flexReload();
                                        }
                        });
                }
                else alert('<?=_("Host unselected")?>');
            }

            if (com == 'new')
              document.location.href = 'newhostform.php';
			 
			if (com == 'credentials')
            {
				if (typeof(ip) != 'undefined')
                   document.location.href = 'hostcredentialsform.php?ip='+urlencode(ip);
                else
                   alert('<?=_("Host unselected")?>');
			} 
									
	}

	$("#flextable").flexigrid({
		url: 'gethost.php',
		dataType: 'xml',
		colModel : [
		<?php
$default = array(
    "hostname" => array(
        _("Hostname"),
        140,
        'true',
        'left',
        false
    ) ,
    "ip" => array(
        _("IP"),
        90,
        'true',
        'center',
        false
    ),
	
	"fqdns" => array(
        _("FQDN/Aliases"),
        150,
        'false',
        'center',
        false
    ) ,
    "desc" => array(
        _("Description"),
        185,
        'false',
        'left',
        false
    ) ,
    "asset" => array(
        _("Asset value"),
        30,
        'true',
        'center',
        false
    ) ,
    "sensors" => array(
        _("Sensors"),
        180,
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
    
    "alert" => array(
        _("Alert"),
        40,
        'true',
        'center',
        true
    ) ,
    "persistence" => array(
        _("Per"),
        40,
        'true',
        'center',
        true
    ) ,
    "rrd_profile" => array(
        _("RRD Profile"),
        70,
        'false',
        'center',
        true
    ) ,
    "apps" => array(
        _("Apps"),
        50,
        'false',
        'center',
        true
    ) ,
    "repository" => array(
        _("Knowledge DB"),
        95,
        'false',
        'center',
        false
    ) ,
    "scantype" => array(
        _("Nagios"),
        45,
        'false',
        'center',
        false
    ) 
);
list($colModel, $sortname, $sortorder, $height) = print_layout($layout, $default, "hostname", "asc", 0);
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
			{name: '<?=_("Duplicate selected")?>', bclass: 'duplicate', onpress : action},
			{separator: true},
			{name: '<?=_("Edit Credentials")?>', bclass: 'credentials', onpress : action},
			{separator: true},
            {name: '<?=_("Select all")?>', bclass: 'various', onpress : action},
            {separator: true},
			{name: '<?=_("Import CSV")?>', bclass: 'i_csv', onpress : action},
			{separator: true},
			{name: '<?=_("Apply")?>', bclass: '<?php echo (WebIndicator::is_on("Reload_hosts")) ? "reload_red" : "reload" ?>', onpress : action},
			{separator: true}
			],
		searchitems : [
            {display: "<?=_("Hostname")?>", name : 'hostname'},
            {display: "<?=_("FQDN/Aliases")?>", name : 'fqdns'},
            {display: "<?=_("IP")?>", name : 'ip', isdefault: true}
            ],
		sortname: "<?php echo $sortname ?>",
		sortorder: "<?php echo $sortorder ?>",
		usepager: true,
		title: '<?=_("Hosts")?>',
		pagestat: '<?=_("Displaying {from} to {to} of {total} hosts")?>',
		nomsg: 'No hosts',
		useRp: true,
		rp: 20,
		contextMenu: 'myMenu',
		onContextMenuClick: menu_action,
		showTableToggleBtn: true,
		//singleSelect: true,
		width: get_width('headerh1'),
                height: get_height(),
		onColumnChange: save_layout,
		onDblClick: linked_to,
		onEndResize: save_layout
	});   
	
	</script>

</body>
</html>

