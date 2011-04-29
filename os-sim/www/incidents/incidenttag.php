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
* Function list: display_errors()
* Classes list:
*/
/*
* Manage TAGS from this a single script. Different states are
* handled by the $_GET['action'] var. Possible states:
*
* list (default): List TAGs
* new1step: Form for inserting tag
* new2step: Values validation and insertion in db
* delete: Validation and deletion from the db
* mod1step: Form for updating a tag
* mod2step: Values validation and update db
*
*/

function display_errors($info_error)
{
	$errors       = implode ("</div><div style='padding-top: 3px;'>", $info_error);
	$error_msg    = "<div>"._("We found the following errors:")."</div><div style='padding-left: 15px;'><div>$errors</div></div>";
							
	return "<div class='ossim_error'>$error_msg</div>";
}

require_once 'classes/Session.inc';

if( !Session::am_i_admin() )  
	exit; 

	require_once 'classes/Security.inc';

Session::logcheck("MenuIncidents", "IncidentsTags");

require_once 'ossim_db.inc';
require_once 'classes/Incident_tag.inc';

// Avoid the browser resubmit POST data stuff

if (GET('redirect')) 
{
    header('Location: ' . $_SERVER['SCRIPT_NAME']);
    exit;
}

$db   		= new ossim_db();
$conn 		= $db->connect();
$tag  		= new Incident_tag($conn);
$parameters = null;
$info_error = null;
$error      = false;

$action 	= $parameters['action'] = GET('action') ? GET('action') : 'list';
$id     	= $parameters['id']     = GET('id');


if ( $action == 'mod1step' && is_numeric($id) ) 
{
	$f      = $tag->get_list("WHERE td.id = $id");
	$name   = $f[0]['name'];
	$descr  = $f[0]['descr'];
}
elseif ($action == 'new2step' || $action == 'mod2step')
{
	$name   = $parameters['name']   = POST('name');
	$descr  = $parameters['descr']  = POST('descr');

	$validate  = array (
		"id"      => array("validation" => "OSS_DIGIT,OSS_NULLABLE"  , "e_message" => 'illegal:' . _("ID")),
		"name"    => array("validation" => "OSS_LETTER,OSS_PUNC"     , "e_message" => 'illegal:' . _("Name")),
		"descr"   => array("validation" => "OSS_TEXT,OSS_NULLABLE"   , "e_message" => 'illegal:' . _("Description")),
		"action"  => array("validation" => "OSS_TEXT"                , "e_message" => 'illegal:' . _("Action")),
	);

	foreach ($parameters as $k => $v )
	{
		eval("ossim_valid(\$v, ".$validate[$k]['validation'].", '".$validate[$k]['e_message']."');");

		if ( ossim_error() )
		{
			$info_error[] = ossim_get_error();
			ossim_clean_error();
			$error  = true;
		}
	}
		
	if ( $error == false )
	{
		if ( $action == 'new2step' )
			$tag->insert($name, $descr);
		
		if ( $action == 'mod2step' )
			$tag->update($id, $name, $descr);
		
		header('Location: ' . $_SERVER['SCRIPT_NAME']);
	}
}
elseif ( $action == 'delete' )
{
	
	ossim_valid($id, OSS_DIGIT, 'illegal:' . _("ID"));
	if (ossim_error()) 
	{
		$error = true;
		$info_error[] = ossim_last_error();
		ossim_clean_error();
	}
	else
	{
		$tag->delete($id);
		header('Location: ' . $_SERVER['SCRIPT_NAME']);
	}
}
	
if ( $error == true )
	$action = str_replace('2', '1', $action);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
	<script type='text/javascript'>
		function delete_tag(num)
		{
			var msg =  '<?php echo _("There are")?> ' + num + ' <?php echo _("incidents using this tag. Do you really want to delete it?")?>';
			if ( num >= 1 )
				return confirm(msg); 
		}
		
		$(document).ready(function() {
			$('#descr').elastic();
			$('#name').focus();
		});
	</script>
	
	<style type='text/css'>
		.pad3 { padding: 3px;}
	</style>
	
</head>

<body>
<?php 
	include ("../hmenu.php"); 
	
/*
 * FORM FOR NEW/EDIT TAG
 */
if ( $action == 'new1step' || $action == 'mod1step' ) 
{
    if ( $error == true )
		echo display_errors($info_error);
	
	$action = str_replace('1', '2', $action);
	
	?>
	<form method="post" action="?action=<?php echo $action ?>&id=<?php echo $id ?>" name="f">
		<table align="center" width="50%">
			<tr>
				<th><?php echo _("Name") ?></th>
				<td class="left">
					<input type="input" name="name" size="55" value="<?php echo $name ?>"/>
					<span style="padding-left: 3px;">*</span>
				</td>
			</tr>
			<tr>
				<th><?php echo _("Description") ?></th>
				<td class="left"><textarea id='descr' name="descr" cols="55" rows="5"><?php echo $descr ?></textarea></td>
			</tr>
			<tr>
				<td colspan="2" class="nobborder center" style='padding:10px 0px;'>
					<input type="submit" value="<?php echo _("OK")?>" class="button"/>&nbsp;
					<input type="button" class="button" onClick="document.location = '<?php echo $_SERVER['SCRIPT_NAME'] ?>'" value="<?php echo _("Cancel") ?>"/>
				</td>
			</tr>
		</table> 
		
		<table  align="center" class="noborder transparent" width="50%">
			<tr>
				<td class='noborder center' style='padding:10px 0px;'><span style="font-style: italic;"><?php echo gettext("Values marked with (*) are mandatory"); ?></span></td>
			</tr>
		</table>
			
	</form>

	<?php
    /*
    * LIST TAGS
    */
} 
else 
{
	?>
	<table align="center" width="70%">
		<tr>
			<th class='pad3'><?php echo _("Id") ?></th>
			<th class='pad3'><?php echo _("Name") ?></th>
			<th class='pad3'><?php echo _("Description") ?></th>
			<th class='pad3'><?php echo _("Actions") ?></th>
		</tr>
	<?php
		
	foreach($tag->get_list() as $f) 
	{ 
		?>
        <tr>
			<td valign="top"><strong><?php echo $f['id'] ?></strong></td>
			<td valign="top" style="text-align: left;" nowrap='nowrap'><?php echo htm($f['name']) ?></td>
			<td valign="top" style="text-align: left;"><?php echo htm($f['descr']) ?></td>
			<td nowrap='nowrap'> 
			<?php
				if (($f['id'] != '65001') && ($f['id'] != '65002')) 
				{ 
					?>
					<a href="?action=mod1step&id=<?php echo $f['id'] ?>"><img border="0" align="absmiddle" title="<?php echo _("Edit tag")?>" src="../vulnmeter/images/pencil.png"/></a>&nbsp;
					<a href="?action=delete&id=<?php echo $f['id'] ?>" onclick="delete_tag(<?php echo $f['num']?>)"><img border="0" align="absmiddle" title="<?php echo _("Delete tag")?>" src="../pixmaps/delete.gif"/></a>
					<?php
				} 
			?>
				&nbsp;
			</td>
		</tr>
		<?php
    } 
	
	?>
    
	<tr>
		<td colspan="4" class="nobborder center" style='padding:10px 0px;'>
			<input type="button" class="button" onClick="document.location = '<?php echo $_SERVER['SCRIPT_NAME'] ?>?action=new1step'" value="<?php echo _("Add new tag") ?>"/>
		</td>
	</tr>
</table>

<?php
}
?>
