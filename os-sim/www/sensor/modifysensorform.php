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
require_once ('classes/Sensor.inc');
require_once ('ossim_db.inc');
require_once ('classes/Security.inc');

Session::logcheck("MenuPolicy", "PolicySensors");

$array_priority   = array ("1"=>"1", "2"=>"2", "3"=>"3", "4"=>"4", "5"=>"5", "6"=>"6", "7"=>"7", "8"=>"8", "9"=>"9", "10"=>"10");

$db = new ossim_db();
$conn = $db->connect();

$name = GET('name');

if ( isset($_SESSION['_sensor']) )
{
	$name       = $_SESSION['_sensor']['name'];
	$ip         = $_SESSION['_sensor']['ip'];  	
	$priority   = $_SESSION['_sensor']['priority']; 
	$descr	    = $_SESSION['_sensor']['descr']; 
	$tzone	    = $_SESSION['_sensor']['tzone']; 
	
	unset($_SESSION['_sensor']);
}
else
{
	if ($name != '')
	{
		ossim_valid($name, OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_SCORE, 'illegal:' . _("Sensor name"));

		if (ossim_error()) 
			die(ossim_error());
	
		if ($sensor_list = Sensor::get_list($conn, "WHERE name = '$name'"))
		{
			$sensor   = $sensor_list[0];
			
			$name     = $sensor->get_name();
			$ip       = $sensor->get_ip();
			$priority = $sensor->get_priority();
			$descr    = $sensor->get_descr();
			$tzone    = $sensor->get_tzone();
						
			unset($_SESSION['_sensor']);
		
		}
	}
	
	$db->close($conn);
}

$tz=$tzone;
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/ajax_validator.js"></script>
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
	<script type="text/javascript" src="../js/messages.php"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
		
	<script type="text/javascript">
		$(document).ready(function(){
			$('textarea').elastic();
			
			$('.vfield').bind('blur', function() {
			     validate_field($(this).attr("id"), "modifysensor.php");
			});			
		});
		
		function ajax_postload() {
			parent.doIframe();
		}
				
	</script>
	
	<style type='text/css'>
		<?php
		if ( GET('withoutmenu') == "1" )
		{
			echo "#table_form {background: transparent; width: 400px;}";
		    echo "#table_form th {width: 130px;}";
		}
		else
		{
			echo "#table_form {background: transparent; width: 500px;}";
		    echo "#table_form th {width: 150px;}";
		}
		?>
		input[type='text'], select, textarea {width: 90%; height: 18px;}
		textarea { height: 45px;}
		label {border: none; cursor: default;}
		.bold {font-weight: bold;}
		div.bold {line-height: 18px;}
		
	</style>
</head>
<body>
                                                                                
<?php

if (GET('withoutmenu') != "1") 
	include ("../hmenu.php"); 
?>


<div id='info_error' class='ossim_error' style='display: none;'></div>

<form method="POST" name='formsensor' id='formsensor' action="modifysensor.php">
<table align="center" id='table_form'>
	
	<input type="hidden" name="insert" value="insert"/>
	<input type="hidden" name="withoutmenu" id='withoutmenu' value="<?php echo GET('withoutmenu')?>"/>
	
	<tr>
		<th><label for='name'><?php echo gettext("Name");?></label></th>
		<td class="nobborder left">
			<input type="hidden" name="name" id='name' class='req_field vfield' value="<?php echo $name; ?>"/>
			<div class='bold'><?php echo $name; ?></div>
		</td>
	</tr>
  
  
	<tr>
		<th><label for='ip'><?php echo gettext("IP"); ?></label></th>
		<td class="left">
			<input type="text" class='req_field vfield' name="ip" id="ip" value="<?php echo $ip?>"/>
			<span style="padding-left: 3px;">*</span>
		</td>
	</tr>
	
	<tr>
		<th><label for='priority'><?php echo gettext("Priority"); ?></label></th>
		<td class="left">
			<select name="priority" id="priority" class='req_field vfield'>
			<?php 
				if ( !in_array($priority, $array_priority) )
					$priority = "5";
				
				foreach ($array_priority as $v)
				{
					$selected = ($priority == $v) ? "selected='selected'" : '';
					echo "<option value='$v' $selected>$v</option>";
				}
			?>
			</select>
		</td>
	</tr>
	
	<tr>
		<th> <?php echo gettext("Timezone"); ?> </th>
		<td class="left">
			<select name="tzone" id="tzone" class='req_field vfield'>
			<option value="-12"<?= ($tz==-12) ? " selected='selected'" : "" ?>>GMT-12</option>
			<option value="-11"<?= ($tz==-11) ? " selected='selected'" : "" ?>>GMT-11</option>
			<option value="-10"<?= ($tz==-10) ? " selected='selected'" : "" ?>>GMT-10</option>
			<option value="-9"<?= ($tz==-9) ? " selected='selected'" : "" ?>>GMT-9</option>
			<option value="-8"<?= ($tz==-8) ? " selected='selected'" : "" ?>>GMT-8</option>
			<option value="-7"<?= ($tz==-7) ? " selected='selected'" : "" ?>>GMT-7</option>
			<option value="-6"<?= ($tz==-6) ? " selected='selected'" : "" ?>>GMT-6</option>
			<option value="-5"<?= ($tz==-5) ? " selected='selected'" : "" ?>>GMT-5</option>
			<option value="-4"<?= ($tz==-4) ? " selected='selected'" : "" ?>>GMT-4</option>
			<option value="-3"<?= ($tz==-3) ? " selected='selected'" : "" ?>>GMT-3</option>
			<option value="-2"<?= ($tz==-2) ? " selected='selected'" : "" ?>>GMT-2</option>
			<option value="-1"<?= ($tz==-1) ? " selected='selected'" : "" ?>>GMT-1</option>
			<option value="0"<?= ($tz==0) ? " selected='selected'" : "" ?>>UTC</option>
			<option value="1"<?= ($tz==1) ? " selected='selected'" : "" ?>>GMT+1</option>
			<option value="2"<?= ($tz==2) ? " selected='selected'" : "" ?>>GMT+2</option>
			<option value="3"<?= ($tz==3) ? " selected='selected'" : "" ?>>GMT+3</option>			
			<option value="4"<?= ($tz==4) ? " selected='selected'" : "" ?>>GMT+4</option>			
			<option value="5"<?= ($tz==5) ? " selected='selected'" : "" ?>>GMT+5</option>			
			<option value="6"<?= ($tz==6) ? " selected='selected'" : "" ?>>GMT+6</option>			
			<option value="7"<?= ($tz==7) ? " selected='selected'" : "" ?>>GMT+7</option>			
			<option value="8"<?= ($tz==8) ? " selected='selected'" : "" ?>>GMT+8</option>			
			<option value="9"<?= ($tz==9) ? " selected='selected'" : "" ?>>GMT+9</option>			
			<option value="10"<?= ($tz==10) ? " selected='selected'" : "" ?>>GMT+10</option>
			<option value="11"<?= ($tz==11) ? " selected='selected'" : "" ?>>GMT+11</option>			
			<option value="12"<?= ($tz==12) ? " selected='selected'" : "" ?>>GMT+12</option
	        </select>
		</td>
	</tr> 
	
	<input type="hidden" class='vfield' name="port" id="port" value="40002"/>
    
	<tr>
		<th><label for='descr'><?php echo gettext("Description"); ?></label></th>
		<td class="left noborder">
			<textarea name="descr" class='vfield' id="descr"><?php echo $descr;?></textarea>
		</td>
	</tr>
	
	<tr>
		<td colspan="2" align="center" style="border-bottom: none; padding: 10px;">
			<input type="button"  class="button" id='send' value="<?php echo _("Update")?>" onclick="submit_form();"/>
			<input type="reset"   class="button" value="<?php echo gettext("Clear form"); ?>"/>
		</td>
	</tr>
</table>
</form>

</body>
</html>

