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
require_once ('classes/Security.inc');

Session::logcheck("MenuPolicy", "PolicySensors");

$array_priority   = array ("1"=>"1", "2"=>"2", "3"=>"3", "4"=>"4", "5"=>"5", "6"=>"6", "7"=>"7", "8"=>"8", "9"=>"9", "10"=>"10");

$ip = GET('ip');

ossim_valid($ip, OSS_IP_ADDR, OSS_NULLABLE, 'illegal:' . _("Sensor name"));

if (ossim_error()) {
    die(ossim_error());
}

if ( isset($_SESSION['_sensor']) )
{
	$name        = $_SESSION['_sensor']['name'];
	$ip          = $_SESSION['_sensor']['ip'];  	
	$priority    = $_SESSION['_sensor']['priority']; 
	$descr	     = $_SESSION['_sensor']['descr']; 
	$tzone	     = $_SESSION['_sensor']['tzone'];
		
	unset($_SESSION['_sensor']);
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" CONTENT="no-cache"/>
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
			     validate_field($(this).attr("id"), "newsensor.php");
			});

		});
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

<form method="POST" name='formsensor' id='formsensor' action="newsensor.php">

<table align="center" id='table_form'>
	<input type="hidden" name="insert" value="insert"/>
	<input type="hidden" name="withoutmenu" id='withoutmenu' value="<?php echo GET('withoutmenu')?>"/>
	
	<tr>
		<th><label for='name'><?php echo gettext("Name"); ?></label></th>
		<td class="left">
			<input type="text" class='req_field vfield' name="name" id="name" value="<?php echo $name;?>"/>
			<span style="padding-left: 3px;">*</span>
		</td>
	</tr>	
	
	<tr>
		<th><label for='ip'><?php echo gettext("IP"); ?></label></th>
		<td class="left">
			<input type="text" class='req_field vfield' name="ip" id="ip" value="<?php echo $ip ?>"/>
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
		
	<?php $tz=(isset($tzone)) ? $tzone : intval(date("O"))/100; ?> 
	<tr>
		<th> <?php echo gettext("Timezone"); ?> </th>
		<td class="left">
	    <select name="tzone" id="tzone">
	        <option value="-12" <?php if ($tz == "-12") echo "selected" ?>>GMT-12:00</option>
	        <option value="-11" <?php if ($tz == "-11") echo "selected" ?>>GMT-11:00</option>
	        <option value="-10" <?php if ($tz == "-10") echo "selected" ?>>GMT-10:00</option>
	        <option value="-9.5" <?php if ($tz == "-9.5") echo "selected" ?>>GMT-9:30</option>
	        <option value="-9" <?php if ($tz == "-9") echo "selected" ?>>GMT-9:00</option>
	        <option value="-8" <?php if ($tz == "-8") echo "selected" ?>>GMT-8:00</option>
	        <option value="-7" <?php if ($tz == "-7") echo "selected" ?>>GMT-7:00</option>
	        <option value="-6" <?php if ($tz == "-6") echo "selected" ?>>GMT-6:00</option>
	        <option value="-5" <?php if ($tz == "-5") echo "selected" ?>>GMT-5:00</option>
	        <option value="-4.5" <?php if ($tz == "-4.5") echo "selected" ?>>GMT-4:30</option>
	        <option value="-4" <?php if ($tz == "-4") echo "selected" ?>>GMT-4:00</option>
	        <option value="-3.5" <?php if ($tz == "-3.5") echo "selected" ?>>GMT-3:30</option>
	        <option value="-3" <?php if ($tz == "-3") echo "selected" ?>>GMT-3:00</option>
	        <option value="-2" <?php if ($tz == "-2") echo "selected" ?>>GMT-2:00</option>
	        <option value="-1" <?php if ($tz == "-1") echo "selected" ?>>GMT-1:00</option>
	        <option value="0" <?php if ($tz == "0") echo "selected" ?>>UTC</option>
	        <option value="1" <?php if ($tz == "1") echo "selected" ?>>GMT+1:00</option>
	        <option value="2" <?php if ($tz == "2") echo "selected" ?>>GMT+2:00</option>
	        <option value="3" <?php if ($tz == "3") echo "selected" ?>>GMT+3:00</option>
	        <option value="3.5" <?php if ($tz == "3.5") echo "selected" ?>>GMT+3:30</option>
	        <option value="4" <?php if ($tz == "4") echo "selected" ?>>GMT+4:00</option>
	        <option value="4.5" <?php if ($tz == "4.5") echo "selected" ?>>GMT+4:30</option>
	        <option value="5" <?php if ($tz == "5") echo "selected" ?>>GMT+5:00</option>
	        <option value="5.5" <?php if ($tz == "5.5") echo "selected" ?>>GMT+5:30</option>
	        <option value="5.75" <?php if ($tz == "5.75") echo "selected" ?>>GMT+5:45</option>
	        <option value="6" <?php if ($tz == "6") echo "selected" ?>>GMT+6:00</option>
	        <option value="6.5" <?php if ($tz == "6.5") echo "selected" ?>>GMT+6:30</option>
	        <option value="7" <?php if ($tz == "7") echo "selected" ?>>GMT+7:00</option>
	        <option value="8" <?php if ($tz == "8") echo "selected" ?>>GMT+8:00</option>
	        <option value="8.75" <?php if ($tz == "8.75") echo "selected" ?>>GMT+8:45</option>
	        <option value="9" <?php if ($tz == "9") echo "selected" ?>>GMT+9:00</option>
	        <option value="9.5" <?php if ($tz == "9.5") echo "selected" ?>>GMT+9:30</option>
	        <option value="10" <?php if ($tz == "10") echo "selected" ?>>GMT+10:00</option>
	        <option value="10.5" <?php if ($tz == "10.5") echo "selected" ?>>GMT+10:30</option>
	        <option value="11" <?php if ($tz == "11") echo "selected" ?>>GMT+11:00</option>
	        <option value="11.5" <?php if ($tz == "11.5") echo "selected" ?>>GMT+11:30</option>
	        <option value="12" <?php if ($tz == "12") echo "selected" ?>>GMT+12:00</option>
	        <option value="12.75" <?php if ($tz == "12.75") echo "selected" ?>>GMT+12:45</option>
	        <option value="13" <?php if ($tz == "13") echo "selected" ?>>GMT+13:00</option>
	        <option value="14" <?php if ($tz == "14") echo "selected" ?>>GMT+14:00</option>
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
			<input type="button" class="button" id='send' value="<?php echo _("Update");?>" onclick="submit_form();"/>
			<input type="reset"  class="button" value="<?php echo _("Clear form"); ?>"/>
		</td>
	</tr>
</table>

</form>

</body>
</html>

