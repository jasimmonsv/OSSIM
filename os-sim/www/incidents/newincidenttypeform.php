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
Session::logcheck("MenuIncidents", "IncidentsTypes");
require_once ("ossim_db.inc");
require_once ('classes/Incident_type.inc');

$error   = false;
$display = 'display:none';

if ( isset($_POST['send']) && !empty($_POST['send']) )
{
	$send   = POST('send');
	$id     = POST('id');
	$descr  = POST('descr');
	$custom = POST('custom');
	
	$validate  = array (
				"id"       => array("validation" => "OSS_ALPHA, OSS_SPACE, OSS_PUNC"                   , "e_message" => 'illegal:' . _("Id")),
				"descr"    => array("validation" => "OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_AT, OSS_NL"   , "e_message" => 'illegal:' . _("Description")),
				"custom"   => array("validation" => "OSS_DIGIT, OSS_NULLABLE"                          , "e_message" => 'illegal:' . _("Custom"))
				);
	
	unset($_POST['send']);
	
	foreach ($_POST as $k => $v )
	{
		eval("ossim_valid(\$v, ".$validate[$k]['validation'].", '".$validate[$k]['e_message']."');");
		
			if ( ossim_error() )
			{
				$error        = true;
				$display      = 'display:block';
				$info_error[] = ossim_get_error();
				ossim_clean_error();
			}
	}
	
	if ($error == false)
	{
	
		$db   = new ossim_db();
		$conn = $db->connect();
		
		$custom_type = ( $custom == 1 ) ? "custom" : "";
		$res = Incident_type::insert($conn, $id, $descr, $custom_type);
		$db->close($conn);
		
		if ( $res !== true )
		{
			$error        = true;
			$display      = 'display:block';
			$info_error[] = $res;
		}
	}
	
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<META http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
	<script type="text/javascript">
		
		function change_button()
		{
			if ( $('#type_custom').is(":checked") )
				$('#send').attr("value", "<?php echo _("Next")." >>"?>");
			else
				$('#send').attr("value", "<?php echo _("Ok")?>");
		}
		
		$(document).ready(function() {
			$('textarea').elastic();
			$('#type_custom').bind('click', function()  { change_button()});
		});
	</script>
	
	<style type='text/css'>
		#type_id, textarea {  margin: 0px; padding:0px; width: 320px;}
	    #type_id { height:18px;}
		textarea {height: 40px;}
		#cont_new_ticket {width: 450px;}
		#ticket_ok {padding: 20px 0px 50px 0px; margin:auto; width: 400px; text-align:center;}
	</style>
</head>
<body>


	<?php include ("../hmenu.php"); ?>
	
	<h1> <?php echo gettext("New ticket type"); ?> </h1>
	
	<?php 
		if ( isset($send) && !empty($send) && $error == false )
		{
			echo "<div id='ticket_ok'>".gettext("New Ticket type  succesfully inserted")."</div>";
			$location = ( $custom == 1 ) ? "modifyincidenttypeform.php?id=".urlencode($id) : "incidenttype.php";
			sleep(1);
			echo "<script type='text/javascript'>window.location='$location';</script>";
		}
	
	?>
	
	<div id='info_error' class='ossim_error' style='<?php echo $display;?>'><div style='text-align:center;'><?php echo implode("<br>", $info_error)?></div></div>

	<form method="post" action="newincidenttypeform.php">
	
		<table align="center" id='cont_new_ticket'>
			<tr>
				<th><?php echo gettext("Type id"); ?></th>
				<td class="left">
					<input type="text" id="type_id" name="id"  size="30" value='<?php echo $id;?>'/>
					<span style="padding-left: 3px;">*</span>
				</td>
			</tr>
			
			<tr>
				<th> <?php echo gettext("Description"); ?> </th>
				<td class="left">
					<textarea id="type_descr" name="descr"><?php echo $descr;?></textarea>
					<span style="padding-left: 3px;">*</span>
				</td>
			</tr>
			
			<tr>
				<th> <?php echo gettext("Custom"); ?> </th>
				<td class="left">
					<?php $checked = ( $custom == 1 ) ? "checked='checked'" : "" ?>
					<input type="checkbox" name="custom" id='type_custom' value="1" <?php echo $checked?>/>
				</td>
			</tr>  
			
			<tr>
				<td colspan="2" align="center" valign="top" class='noborder'>
					<?php $send_text = ( $custom == 1 ) ? _("Next")." >>" : _("OK") ?>
					<input type="submit" id='send' name='send' value="<?php echo $send_text?>" class="button"/>
				</td>
			</tr>
		</table>
	</form>

</body>
</html>

