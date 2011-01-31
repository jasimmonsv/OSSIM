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
require_once ('ossim_db.inc');
require_once ('classes/Databases.inc');
Session::logcheck("MenuConfiguration", "PolicyServers");
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
	<script type="text/javascript" src="../js/ajax_validator.js"></script>
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
	<script type="text/javascript" src="../js/messages.php"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
  
	<script type="text/javascript">
		$(document).ready(function(){

			$('textarea').elastic();
			
			$('.vfield').bind('blur', function() {
			     validate_field($(this).attr("id"), "<?php echo ( GET('name') != "") ? "modifydbs.php" : "newdbs.php" ?>");
			});

		});
	</script>
  
	<style type='text/css'>
		<?php
		if ( GET('withoutmenu') == "1" )
		{
			echo "#table_form {background: transparent; width: 500px;}";
		    echo "#table_form th {width: 150px;}";
		}
		else
		{
			echo "#table_form {background: transparent; width: 500px;}";
		    echo "#table_form th {width: 150px;}";
		}
		?>
		input[type='text'], select, textarea {width: 90%; height: 18px;}
		input[type='file'] {width: 90%; border: solid 1px #CCCCCC;}
		textarea { height: 45px;}
		label {border: none; cursor: default;}
		.bold {font-weight: bold;}
		div.bold {line-height: 18px;}
		.val_error { width: 270px;}
	</style>
  
</head>
<body>
                                                                                
<?php 

$db = new ossim_db();
$conn = $db->connect();

$name = GET('name');

if ( isset($_SESSION['_dbs']) )
{
	$name      = $_SESSION['_dbs']['name'];
	$ip        = $_SESSION['_dbs']['ip'];
	$port      = $_SESSION['_dbs']['port'];
	$user      = $_SESSION['_dbs']['user'];
	$pass      = $_SESSION['_dbs']['pass'];
    $pass2     = $_SESSION['_dbs']['pass2'];
	$icon      = "";
	
	unset($_SESSION['_dbs']);
}
else
{
	$ip    = $user = $pass = "";
	$port  = "3306";
		
	if ($name != '')
	{
		ossim_valid($name, OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_SCORE, 'illegal:' . _("Database Server Name"));
	
		if (ossim_error()) 
			die(ossim_error());
			
		if ($server_list = Databases::get_list($conn, "WHERE name = '$name'"))
		{
			$server    = $server_list[0];
			$name      = $server->get_name();
			$ip        = $server->get_ip();
			$port      = $server->get_port();
			$user      = $server->get_user();
			$pass      = $server->get_pass();
			$icon      = $server->get_name();
            $pass2 = $pass;
        }
	}
	
}



if (GET('withoutmenu') != "1") 
	include ("../hmenu.php"); 
?>


<div id='info_error' class='ossim_error' style='display: none;'></div>

<form name='form_dbs' id='form_dbs' method="POST" action="<?php echo ( GET('name') != "") ? "modifydbs.php" : "newdbs.php" ?>" enctype="multipart/form-data">

<input type="hidden" name="insert" value="insert"/>
<input type="hidden" name="withoutmenu" id='withoutmenu' value="<?php echo GET('withoutmenu')?>"/>

<table align="center" id='table_form'>
	  
	<tr>
		<th><label for='name'><?php echo gettext("Name"); ?></label></th>
		<td class="left">
			<?php 
			if ( GET('name') != '' ) 
			{
				echo "<input type='hidden' class='req_field vfield' name='name' id='name' value='$name'/>";
				echo "<div class='bold'>$name</div>";
			}
			else
			{
				echo "<input type='text' class='req_field vfield' name='name' id='name' value='$name'/>";
				echo "<span style='padding-left: 5px;'>*</span>";
			}
			?>
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
		<th><label for='port'><?php echo gettext("Port"); ?></label></th>
		<td class="left">
			<input type="text" class='req_field vfield' name="port" id="port" value="<?php echo ( !empty($port) ) ? $port : "3306";?>"/>
			<span style="padding-left: 3px;">*</span>
		</td>
	</tr>
  
  	<tr>
		<th><label for='user'><?php echo gettext("User"); ?></label></th>
		<td class="left">
			<input type="text" class='req_field vfield' name="user" id="user" value="<?php echo $user;?>"/>
			<span style="padding-left: 3px;">*</span>
		</td>
	</tr>
	
	<tr>
		<th><label for='pass'><?php echo gettext("Password"); ?></label></th>
		<td class="left">
			<input type="password" class='req_field vfield' name="pass" id="pass" value="<?php echo $pass;?>"/>
			<span style="padding-left: 3px;">*</span>
		</td>
	</tr>
	
	<tr>
		<th><label for='pass2'><?php echo gettext("Repeat Password"); ?></label></th>
		<td class="left">
			<input type="password" class='req_field vfield' name="pass2" id="pass2" value="<?php echo $pass2;?>"/>
			<span style="padding-left: 3px;">*</span>
		</td>
	</tr>
    
    <tr>
		<th><label for='icon'><?php echo gettext("Icon"); ?></label></th>
		<td class="left">
		<?php if ( !empty ($icon) ) {?>
			<div style='padding: 3px 0px;'><img src='getdbsicon.php?name=<?=urlencode($icon)?>' border='0' align="absmiddle"/></div>
		<?php } ?>	
			<input type="file" name="icon" id="icon" size='38'/>
			<div style='padding: 5px 0px;'><?=_("Only 32x32 pixels png icon supported")?></div>
		</td>
	</tr>
  
	<tr>
		<td colspan="2" align="center" style="border-bottom: none; padding: 10px;">
			<input type="button" class="button" id='send' onclick="submit_form();" value="<?php echo _("Update")?>"/>
			<input type="reset"  class="button" value="<?php echo gettext("Clear form");?>"/>
		</td>
	</tr>

  </table>
</form>

</body>
</html>

