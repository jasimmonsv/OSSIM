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

require_once ('classes/Session.inc');

$info_error = null;
$retval     = null;
$error      = false;

exec ("sudo /var/ossec/bin/ossec-control enable agentless", $output, $retval);
if ($retval !== 0)
{
	$info_error = "Fail to enable the agentless monitoring</b>";
	$error      = true;
}

if ($error != true)
{
	exec('expect -version', $output, $retval);	
		
	if ($retval !== 0)
	{
		$info_error = "You don't have the <span class='ibold'>expect library</span> installed on the server. <br/>You can do the following to install: <b># apt-get install expect</b>";
		$error      = true;
	}
	else
	{	
		$output = null;
		exec('ls -la /var/ossec/agentless/.passlist', $output, $retval);	
			
		if ($retval !== 0)
			exec('touch /var/ossec/agentless/.passlist', $output);	
	}
}

if ($error != true)
{
	// load column layout
	require_once ('../conf/layout.php');
	require_once 'ossim_db.inc';

	$category    = "ossec";
	$name_layout = "agentless_layout";
	$layout      = load_layout($name_layout, $category);
	
	$output = null;
	exec(' sudo /var/ossec/agentless/register_host.sh list', $output, $retval);	

	$status 	  = null;
	$apply_status = ( file_exists ("/var/ossec/agentless/.reload") ) ? "reload_red" : "reload";
	
	if ( count($output) == 1 ) 
		$status = array ("not_configured", _("Not configured"));
	else
	{
		$output = null;
		exec ("sudo /var/ossec/bin/ossec-control status",  $output);
		
		$output = implode("\n", $output);
		$pattern = '/ossec-agentlessd not running/';
		
		if ( preg_match($pattern, $output) )
			$status = array ("not_running", _("Not running"));
		else
			$status = array ("running", _("Running"));
    }
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<META http-equiv="Pragma" content="no-cache"/>
	<meta http-equiv="X-UA-Compatible" content="IE=7"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<link rel="stylesheet" type="text/css" href="../style/flexigrid.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery.flexigrid.js"></script>
	<script type="text/javascript" src="../js/urlencode.js"></script>
	
	<?php if ($info_error == null) {?>
	<script type='text/javascript'>
		
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
		
		function get_width(id)
		{
			if (typeof(document.getElementById(id).offsetWidth)!='undefined') 
				return document.getElementById(id).offsetWidth-5;
			else
				return 700;
		}
		
		function get_height()
		{
		   return parseInt($(document).height()) - 200;
		}
						
		function action(com,grid)
		{ 
			var items = $('.trSelected', grid);
									
			if (com=='<?php echo _("Delete selected")?>')
			{
				if (typeof(items[0]) != 'undefined') {
					if( confirm('<?php echo _("Are you sure?") ?>') )
					{
						var sdata = items[0].id.substr(3);
						document.location.href = 'al_delete.php?ip='+urlencode(sdata);
					}
				}
				else 
					alert('<?=_("You must select a host")?>');
			}
			else if (com=='<?php echo _("Modify")?>')
			{
				if (typeof(items[0]) != 'undefined')
				{
					var sdata = items[0].id.substr(3);
					document.location.href = 'al_modifyform.php?ip='+urlencode(sdata);
				}
				else
					alert('<?=_("You must select a host")?>');
			}
			else if (com=='<?php echo _("New")?>')
			{
				document.location.href = 'al_newform.php';
			}
			else if (com=='<?php echo _("Enable/Disabled")?>')
			{
				var sdata = items[0].id.substr(3);
				document.location.href = 'al_enable.php?ip='+urlencode(sdata);
			}
			else if (com=='<?php echo _("Apply Configuration")?>')
			{
				document.location.href = 'al_applyconf.php';
			}
		}
		
		function menu_action(com,id,fg,fp)
		{ 
			
			var ip = id;
            
			if (com=='delete')
			{
				if (typeof(ip) != 'undefined') 
				{	
					if( confirm('<?php echo _("Are you sure?") ?>') )
						document.location.href = 'al_delete.php?ip='+urlencode(ip)
				}
				else 
					alert('<?=_("Host unselected")?>');
			}
			
			if (com=='modify')
			{
				if (typeof(ip) != 'undefined') 
					document.location.href = 'al_modifyform.php?ip='+urlencode(ip);
				else 
					alert('<?=_("Host unselected")?>');
			}
			
			if (com=='enable')
			{
				if (typeof(ip) != 'undefined') 
					document.location.href = 'al_enable.php?ip='+urlencode(ip);
				else 
					alert('<?=_("Host unselected")?>');
			}
			
			if (com=='new')
				document.location.href = 'al_newform.php';
		}
		
		
		function linked_to(rowid) {
			document.location.href = 'al_modifyform.php?ip='+urlencode(rowid);
		}
		
				
		$(document).ready(function() {
			
			$("#flextable").flexigrid({
				url: 'get_agentless.php?sortname=status,hostname&sortorder=asc',
				dataType: 'xml',
				colModel : [
				<?php
					$default = array(
						"hostname" 	=> array(_('Hostname'), 	 220, 'true', 'left',   false),
						"ip"     	=> array(_('IP'),       	 130, 'true', 'center', false),
						"user"   	=> array(_('User'),     	 180, 'true', 'center', false),
						"status" 	=> array(_('Status'),     	 50,  'true', 'center', false),
						"desc"  	=> array(_('Description'),   300, 'true', 'left',   false)
					);
					list($colModel, $sortname, $sortorder, $height) = print_layout($layout, $default, "status,hostname", "asc", 300);
					echo "$colModel\n";
				?>
					],
					
				searchitems : [
					{display: "<?php echo _("IP")?>", name : 'ip', isdefault: true},
					{display: "<?php echo _("Hostname")?>", name : 'hostname'},
					{display: "<?php echo _("User")?>", name : 'user'},
					{display: "<?php echo _("Status")?>", name : 'status'}
				],
				
				buttons : [
					{name: '<?php echo _("New")?>', bclass: 'add', onpress : action},
					{separator: true},
					{name: '<?php echo _("Modify")?>',    bclass: 'modify', onpress : action},
					{separator: true},
					{name: '<?php echo _("Delete selected")?>',     bclass: 'delete', onpress : action},
					{separator: true},
					{name: '<?php echo _("Enable/Disabled")?>',     bclass: 'enable', onpress : action},
					{separator: true},
					{name: '<?php echo _("Apply Configuration")?>', bclass: '<?php echo $apply_status?>', onpress : action},
					{separator: true},
					{name: '<a href="ossec_control.php"><?php echo _("Agentless Status")?></a>: <?php echo $status[1] ?>', bclass: '<?php echo $status[0] ?>', iclass: 'ibutton'}
				],
				sortname: "<?php echo $sortname ?>",
				sortorder: "<?php echo $sortorder ?>",
				usepager: true,
				title: 'Agentless Host',
				pagestat: '<?php echo _("Displaying")?> {from} <?php echo _("to")?> {to} <?php echo _("of")?> {total} <?php echo _("hosts")?>',
				nomsg: '<?=_("No Agenteless Host")?>',
				useRp: true,
				rp: 20,
				contextMenu: 'myMenu',
				onContextMenuClick: menu_action,
				showTableToggleBtn: true,
				singleSelect: true,
				width: get_width('headerh1'),
				height: get_height(),
				onColumnChange: save_layout,
				onDblClick: linked_to,
				onEndResize: save_layout
			});
		});
	
	</script>

	<?php } ?>	
	
	<style type='text/css'>
		#headerh1 {width:100%;height:1px;}
		
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
		.contextMenu a {padding-left: 10px !important;}
		.ibold {font-weight: bold; font-style:italic;}
		
		.not_configured {color:#504D4D;}
		.not_running {color:#E54D4D;}
		.running {color:#15B103';}
		
	</style>
</head>

<body>
                                                                                
	<?php include ("../hmenu.php"); ?>
	
	<div id="headerh1">
		<?php 
			if ($info_error != null)
				echo "<div class='ossim_error'><div class='center'>$info_error</div></div>";
		?>
	</div>

  
	<table class="noborder">
		<tr>
			<td valign="top"><table id="flextable" style="display:none"></table></td>
		<tr>
	</table>
	
	<!-- Right Click Menu -->
	<ul id="myMenu" class="contextMenu">
		<li class="hostreport">
			<a href="#new"><img src="../pixmaps/tables/table_row_insert.png" align="absmiddle"/> <?php echo _("New")?></a>
		</li>
		<li class="hostreport">
			<a href="#modify"><img src="../pixmaps/tables/table_edit.png" align="absmiddle"/><?php echo _("Modify")?></a>
		</li>
		<li class="hostreport">
			<a href="#delete"><img src="../pixmaps/tables/table_row_delete.png" align="absmiddle"/> <?php echo _("Delete")?></a>
		</li>
        <li class="hostreport">
			<a href="#enable"><img src="../pixmaps/tables/enable.png" align="absmiddle"/> <?php echo _("Enable/disabled")?></a>
		</li>
				
		
	</ul>
	
</body>
</html>
