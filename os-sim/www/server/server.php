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
Session::logcheck("MenuPolicy", "PolicyServers");
// load column layout
require_once ('../conf/layout.php');
$category = "policy";
$name_layout = "servers_layout";
$layout = load_layout($name_layout, $category);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" content="no-cache"/>
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

<?php
require_once 'ossim_db.inc';
require_once 'ossim_conf.inc';
require_once 'classes/Server.inc';
require_once 'server_get_servers.php';
$ossim_conf = $GLOBALS["CONF"];
$db = new ossim_db();
$conn = $db->connect();
/* get the port and IP address of the server */
$address = $ossim_conf->get_conf("server_address");
$port = $ossim_conf->get_conf("server_port");
echo _("Master server at") . " <b>" . $address . ":" . $port . "</b> " . _("is") . " ";
if (check_server($conn) == true) {
    echo "<font color=\"green\">";
    echo _("UP");
    echo "</font>";
    // Server up
    
} else {
    echo "<font color=\"red\">";
    echo _("DOWN");
    echo "</font>";
    // Server down
    
}
echo ".";
//first, get the servers connected; all this servers are "actived"
list($server_list, $err) = server_get_servers($conn);
if ($err != "") echo $err;
$server_list_aux = $server_list; //here are stored the connected servers
$server_stack = array(); //here will be stored the servers wich are in DDBB
$server_configured_stack = array();
if ($server_list) {
    foreach($server_list as $server_status) {
        if (in_array($server_status["servername"], $server_stack)) continue;
        array_push($server_stack, $server_status["servername"]);
    }
}
$active_servers = 0;
$total_servers = 0;
if ($server_list = Server::get_list($conn, "")) {
    $total_servers = count($server_list);
    foreach($server_list as $server) {
        if (in_array($server->get_name() , $server_stack)) {
            $active_servers++;
        }
    }
}
$active_servers = ($active_servers == 0) ? "<font color=red><b>$active_servers</b></font>" : "<font color=green><b>$active_servers</b></font>";
$total_servers = "<b>$total_servers</b>";
$db->close($conn);
?>

	<table class="noborder">
	<tr><td valign="top">
		<table id="flextable" style="display:none"></table>
	</td><tr>
	</table>
	
	<!-- Right Click Menu -->
	<ul id="myMenu" class="contextMenu">
            <li class="hostreport"><a href="#hostreport" class="greybox" style="padding:3px"><img src="../pixmaps/reports.png" align="absmiddle"/><?=_("Host Report")?></a></li>
            <li class="hostreport"><a href="#modify" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_edit.png" align="absmiddle"/> <?=_("Modify")?></a></li>
            <li class="hostreport"><a href="#delete" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_row_delete.png" align="absmiddle"/> <?=_("Delete")?></a></li>
            <li class="hostreport"><a href="#newport" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_row_insert.png" align="absmiddle"/> <?=_("New Server")?></a></li>
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
		if (com=='<?php echo _('Delete selected')?>') {
			//Delete host by ajax
			if (typeof(items[0]) != 'undefined') {
				document.location.href = 'deleteserver.php?confirm=yes&name='+urlencode(items[0].id.substr(3))
			}
			else alert('<?=_("You must select a server")?>');
		}
		else if (com=='<?php echo _('Modify')?>') {
			if (typeof(items[0]) != 'undefined') document.location.href = 'newserverform.php?name='+urlencode(items[0].id.substr(3))
			else alert('<?=_("You must select a server")?>');
		}
		else if (com=='<?php echo _('New')?>') {
			document.location.href = 'newserverform.php'
		}
		else if (com=='<?php echo _('Reload')?>') {
			document.location.href = '../conf/reload.php?what=servers&back=<?php echo urlencode($_SERVER["REQUEST_URI"]); ?>'
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
            var ip = id;
            var hostname = id;

            if (com=='<?php echo _('hostreport')?>') {
                var url = "../report/host_report.php?hostname="+hostname;
                if (hostname == ip) var title = "<?=_("Host Report")?>: "+ip;
                else var title = "<?=_("Host Report")?>: "+hostname+"("+ip+")";
                //GB_show(title,url,450,'90%');
                var wnd = window.open(url,'hostreport_'+ip,'fullscreen=yes,scrollbars=yes,location=no,toolbar=no,status=no,directories=no');
                wnd.focus()
            }

            if (com=='<?php echo _('delete')?>') {
                //Delete host by ajax
                if (typeof(hostname) != 'undefined') {
                        document.location.href = 'deleteserver.php?confirm=yes&name='+urlencode(hostname)
                }
                else alert('<?=_("Server unselected")?>');
            }

            if (com=='<?php echo _('modify')?>') {
                if (typeof(hostname) != 'undefined') document.location.href = 'newserverform.php?name='+urlencode(hostname)
                else alert('<?=_("Server unselected")?>');
            }

            if (com == '<?php echo _('newport')?>')
              document.location.href = 'newserverform.php';
	}

	$("#flextable").flexigrid({
		url: 'getserver.php',
		dataType: 'xml',
		colModel : [
		<?php
$default = array(
    "ip" => array(
        _('IP'),
        100,
        'true',
        'center',
        false
    ) ,
    "name" => array(
        _('Name'),
        165,
        'true',
        'center',
        false
    ) ,
    "port" => array(
        _('Port'),
        40,
        'true',
        'center',
        false
    ) ,
    "status" => array(
        _('Status'),
        50,
        'false',
        'center',
        false
    ) ,
    "correlate" => array(
        _('Correlate'),
        30,
        'false',
        'center',
        false
    ) ,
    "cross correlate" => array(
        _('Cross Correlate'),
        30,
        'false',
        'center',
        false
    ) ,
    "store" => array(
        _('Store'),
        30,
        'false',
        'center',
        false
    ) ,
    "qualify" => array(
        _('Qualify'),
        30,
        'false',
        'center',
        false
    ) ,
    "resend_alarms" => array(
        _('Resend Alarms'),
        30,
        'false',
        'center',
        false
    ) ,
    "resend_events" => array(
        _('Resend Events'),
        30,
        'false',
        'center',
        false
    ) ,
    "sign" => array(
        _('Sign'),
        30,
        'false',
        'center',
        false
    ) ,
    "sem" => array(
        _('Logger'),
        30,
        'false',
        'center',
        false
    ) ,
    "sim" => array(
        _('SIEM'),
        30,
        'false',
        'center',
        false
    ) ,
    "desc" => array(
        _('Description'),
        260,
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
			{name: '<?=_("New")?>', bclass: 'add', onpress : action},
			{separator: true},
			{name: '<?=_("Modify")?>', bclass: 'modify', onpress : action},
			{separator: true},
			{name: '<?=_("Delete selected")?>', bclass: 'delete', onpress : action},
			{separator: true},
			{name: '<?=_("Active Children Servers")?>: <?php echo $active_servers ?>', bclass: 'info', iclass: 'ibutton'},
			{name: '<?=_("Total Children Servers")?>: <?php echo $total_servers ?>', bclass: 'info', iclass: 'ibutton'}
			],
		sortname: "<?php echo $sortname ?>",
		sortorder: "<?php echo $sortorder ?>",
		usepager: true,
		title: '<?=_("Servers")?>',
		pagestat: '<?=_("Displaying")?> {from} <?=_("to")?> {to} <?=_("of")?> {total} <?=_("servers")?>',
		nomsg: '<?=_("No servers")?>',
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
