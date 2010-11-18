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
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
	<script type="text/javascript" src="../js/ajax_validator.js"></script>
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
	<script type="text/javascript" src="../js/messages.php"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
		
	<style type='text/css'>
		<?php
		if ( GET('withoutmenu') == "1" )
		{
			echo "#table_form {background: transparent; width: 400px;}";
		    echo "#table_form th {width: 130px;}";
		}
		else
		{
			echo "#table_form {background: transparent; width: 450px;}";
		    echo "#table_form th {width: 150px;}";
		}
		?>
				
		input[type='text'], select, textarea {width: 90%; height: 18px;}
		textarea { height: 45px;}
		label {border: none; cursor: default;}
		.bold {font-weight: bold;}
		div.bold {line-height: 18px;}
	</style>
	
	<script type="text/javascript">
		$(document).ready(function(){
			$('textarea').elastic();
				
			$('.vfield').bind('blur', function() {
				 validate_field($(this).attr("id"), "newsingleport.php");
			});
		});
	</script>
	
	
</head>
<body>
                                                                                
<?php

if ( isset($_SESSION['_singleport']) )
{
	$port     = $_SESSION['_singleport']['port'];        
	$protocol = $_SESSION['_singleport']['protocol']; 
	$service  = $_SESSION['_singleport']['service']; 
	$descr    = $_SESSION['_singleport']['descr'];   
	
	unset($_SESSION['_singleport']);
}

if (GET('withoutmenu') != "1") 
	include ("../hmenu.php"); 

?>	

<div id='info_error' class='ossim_error' style='display: none;'></div>

<form method="post" id='form_p' name='form_p' action="newsingleport.php">

<input type="hidden" name="insert" value="insert"/>
<input type="hidden" name="withoutmenu" id='withoutmenu' value="<?php echo GET('withoutmenu')?>"/>

<table align="center" id='table_form'>
	<tr>
		<th><label for='port'><?php echo gettext("Port number"); ?></label></th>
		<td class='left' class="nobborder">
			<input type="text" name="port" class='vfield req_field' id='port' value="<?=$port?>">
			<span style="padding-left: 3px;">*</span>
		</td>
	</tr>
	
	<tr>
		<th><label for='protocol'><?php echo gettext("Protocol"); ?></label></th>
		<td class="left">
			<select name="protocol" class='vfield req_field' id='protocol'>
				<option value="udp"<?=(($protocol=="udp") ? "selected='selected'" : "")?>><?php echo gettext("UDP"); ?> </option>
				<option value="tcp"<?=(($protocol=="tcp") ? "selected='selected'" : "")?>><?php echo gettext("TCP"); ?> </option>
			</select>
			<span style="padding-left: 3px;">*</span>
		</td>
	</tr>
	
	<tr>
		<th><label for='service'><?php echo gettext("Service"); ?></label></th>
		<td class="left">
			<input type="text" class='vfield req_field' name="service" id='service' value="<?=$service?>">
			<span style="padding-left: 3px;">*</span>
		</td>
	</tr>
	
	<tr>
		<th><label for='description'><?php echo gettext("Description"); ?></label></th>
		<td class="left noborder">
			<textarea name="descr" class='vfield' id="descr"><?php echo $descr?></textarea>
		</td>
	</tr>
	
	<tr>
		<td colspan="2" class="nobborder" style="text-align:center;padding:10px">
			<input type="button" class="button" id='send' value="<?=_("Send")?>" onclick="submit_form()"/>
			<input type="reset" class="button" value="<?=_("Reset")?>"/>
		</td>
	</tr>
</table>

</form>

</body>
</html>

