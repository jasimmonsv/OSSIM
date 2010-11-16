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
require_once ('ossim_db.inc');
require_once ('classes/Port_group.inc');
require_once ('classes/Port.inc');
require_once ('classes/Port_group_reference.inc');
require_once ('classes/Security.inc');

Session::logcheck("MenuPolicy", "PolicyPorts");

//Autocomplete Ports

$db = new ossim_db();
$conn = $db->connect();

$ports           = array();
$port_list       = array();
$arr_ports_input = array();
$ports_input     = "";

if ($port_list = Port::get_list($conn))
{
    foreach($port_list as $port) 
		$ports[ ] = $port->get_port_number()." - ".$port->get_protocol_name();
}

foreach($ports as $k => $v) 
   	$arr_ports_input[] = '{ txt:"'.$v.'", id: "'.$v.'" }';

$ports_input = implode(",", $arr_ports_input);

if ( isset($_SESSION['_portgroup']) )
{
	$pgname        = $_SESSION['_portgroup']['pgname'];        
	$actives_ports = $_SESSION['_portgroup']['actives_ports']; 
	$descr         = $_SESSION['_portgroup']['descr'];   
	
	unset($_SESSION['_portgroup']);
}
else
{
	$pgname = GET('portname');
	
	$descr  = "";
	$actives_ports = array();
	
	if ($pgname  != '')
	{
		ossim_valid($pgname, OSS_ALPHA, OSS_SCORE, OSS_SPACE, OSS_PUNC, 'illegal:' . _("Port Group Name"));
		
		if (ossim_error()) 
			die(ossim_error());			
			
		
		if ($port_group_list = Port_group::get_list($conn, "WHERE name = '$pgname'")) 
			$port_group = $port_group_list[0];

				
		if ($port_list = Port::get_list($conn))
		{
			foreach($port_list as $port)
			{
				$is_active = Port_group_reference::in_port_group_reference($conn, $port_group->get_name() , $port->get_port_number() , $port->get_protocol_name());
				 
				if ( $is_active ) 
					$actives_ports[] = $port->get_port_number()." - ". $port->get_protocol_name();
			}
		}
		
		$descr = $port_group->get_descr();
		
	}
	
}

$db->close($conn);

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css">
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/combos.js"></script>
	<script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>
	<script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
	<script type="text/javascript" src="../js/ajax_validator.js"></script>
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
	<script type="text/javascript" src="../js/messages.php"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
	
	<script language="javascript">
		$(document).ready(function() {
			var ports = [ <?= $ports_input ?> ];
				$("#ports").autocomplete(ports, {
					minChars: 0,
					width: 225,
					matchContains: "word",
					autoFill: true,
					formatItem: function(row, i, max) {
						return row.txt;
					}
				}).result(function(event, item) {
					$("#ports").val(item.id);
					addto('selected_ports',item.id,item.id);
				});
				
				
				$('textarea').elastic();
				
				$('.vfield').bind('blur', function() {
					 validate_field($(this).attr("id"), "newport.php");
				});
						
		})
	</script>
  
	<style type='text/css'>
		#table_form { width: 500px;}
		#table_form th {width: 150px;}
		input[type='text'], select, textarea {width: 90%; height: 18px;}
		textarea { height: 45px;}
		label {border: none; cursor: default;}
		.bold {font-weight: bold;}
		div.bold {line-height: 18px;}
		#selected_ports { width:90%; margin-top:5px; height:100px;}
		.lbutton, .lbutton:hover, input.lbutton:hover  {margin-right: 0px;}
		.right {text-align: right; padding: 3px 0px;}
	</style>
</head>

<body>
                                                                        
<?php
if (GET('withoutmenu') != "1") 
	include ("../hmenu.php"); 

?>

<div id='info_error' class='ossim_error' style='display: none;'></div>

<form method="post" name='formpg' id='formpg' action="<?php echo ( GET('portname') != "") ? "modifyport.php" : "newport.php" ?>">

<input type="hidden" name="insert" value="insert"/>

<table align="center" id='table_form'>
	<tr>
		<th><label for='namepg'><?php echo gettext("Name"); ?></label></th>
		<td class="left">
			<?php if ( GET('portname') == "" ) {?>
				<input type='text' name='pgname' id='pgname' class='vfield req_field' value="<?php echo $pgname?>"/>
				<span style="padding-left: 3px;">*</span>
			<?php } 
				  else {
			?>	
				<input type='hidden' name='pgname' id='pgname' class='vfield req_field' value="<?php echo $pgname?>"/>
				<div class='bold'><?php echo $pgname?></div>
			<?php }  ?>
		</td>
	</tr>
	
	<tr>
    <th><label for='selected_ports'><?php echo gettext("Ports");?></label></th>
		<td class='left'>
			<table class="transparent" width='100%'>
				<tr><td class="nobborder"><?=_("<span class='bold'>Type</span> here the pair 'port-protocol'")?>:</td></tr>
				<tr><td class="nobborder"><input type="text" id="ports" name="ports" value="" size="32"/></td></tr>
				<tr><td class="nobborder" style="padding-top:10px"><?=_("Selected ports for the group")?>:</td></tr>
				<tr>
					<td class="nobborder">
						<select id="selected_ports" name="act_ports[]" class='req_field vfield' multiple="multiple">
						<?php
							if ( isset($ports) )
							{
								foreach($ports as $k => $v)
								{ 
									$selected = ( in_array($v, $actives_ports) ) ? true : false;
									
									if( $selected == true )
										echo "<option value='$v' selected='selected'>$v</option>";
									
								}
							}
						?>
						</select>
						<span style="padding-left: 3px; vertical-align: top;">*</span>
					</td>
				</tr>
				<tr>
					<td class="right nobborder">
						<div style='width:90%;'><input type="button" value=" [X] " onclick="deletefrom('selected_ports');" class="lbutton"/></div>
					</td>
				</tr>
			</table>
		</td>
    </tr>
  
	<tr>
		<th><label for='descr'><?php echo gettext("Description"); ?></label></th>
		<td class="left nobborder">
			<textarea name="descr" id="descr" class='vfield'><?php echo $descr?></textarea>
		</td>
	</tr>
  
	<tr>
		<td colspan="2" class="nobborder" style="text-align:center;padding:10px">
			<input type="button" id='send' class="button" value="<?=_("Send")?>" onclick="selectall('selected_ports'); submit_form();"/>
			<input type="reset" class="button" value="<?=_("Reset")?>"/>
		</td>
	</tr>
</table>
</form>

</body>
</html>

