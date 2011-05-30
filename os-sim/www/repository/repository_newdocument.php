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

require_once ("classes/Repository.inc");
require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
require_once ("ossim_db.inc");
Session::logcheck("MenuIncidents", "Osvdb");

$user       = Session::get_session_user();

$db         = new ossim_db();
$conn       = $db->connect();

$vuser      = POST('user');
$ventity    = POST('entity');

$info_error = null;
$error      = false;


if ( isset($_POST['title']) || isset($_POST['doctext']) ) 
{
	ossim_valid($vuser, OSS_NULLABLE, OSS_USER, 'illegal:' . _("User"));
	ossim_valid($ventity, OSS_NULLABLE, OSS_DIGIT, OSS_ALPHA, 'illegal:' . _("Entity"));
	
	if ( ossim_error() )
	{
		$info_error[] = ossim_get_error();
		ossim_clean_error();
		$error = true;
	}
	
	if ( POST('title') == "" ) 
	{
		$info_error[] = _("Error in the 'title' field (missing required field)");
		$error        = true;
	}
	
	if ( strip_tags(POST('doctext')) == "" ) 
	{
		$info_error[] = _("Error in the 'text' field (missing required field)");
		$error        = true;
	}
}


if ( POST('title') != "" && POST('doctext') != "" && $error == false) 
{
    // Get a list of nets from db
    if($vuser != "")   $user = $vuser;
    if($ventity != "") $user = $ventity;
	
	$title    = POST('title');
	$doctext  = strip_tags(POST('doctext'),'<div><span><ul><li><ol><b><i><u><strike><p><h1><h2><h3><h4><h5><h6><font><br><blockquote>');
	$keywords = POST('keywords');
	   
    $id_inserted = Repository::insert($conn, $title , $doctext , $keywords , $user);
	?>
	
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html>
	<head>
		<title> <?php echo gettext("OSSIM Framework"); ?> </title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
		<META http-equiv="Pragma" content="no-cache">
		<link rel="stylesheet" type="text/css" href="../style/style.css"/>
		<style type='text/css'>
			body { margin: 0px;}
			.ossim_success {width: auto;}
			table { margin:auto; width: 98%; text-align: center;}
						
		</style>
	</head>

	<body>
	
	<table cellpadding='0' cellspacing='2' border='0' class="transparent">
		<tr>
			<td class="center">
				<div class='ossim_success'>
					<?php echo _("Document inserted with id")?>: <?php echo $id_inserted ?>
				</div>
			</td>
		</tr>
		<tr>
			<td class="center" style='padding-top: 30px;'><?php echo _("Do you want to attach a document file?")?> 
				<input type="button" class="button" onclick="document.location.href='repository_attachment.php?id_document=<?php echo $id_inserted ?>'" value="<?php echo _("YES")?>">&nbsp;
				<input class="button" type="button" onclick="parent.document.location.href='index.php'" value="<?php echo _("NO")?>"/>
			</td>
		</tr>
	</table>
<?php
} 
else 
{ 
?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html>
	<head>
		<title> <?php echo gettext("OSSIM Framework"); ?> </title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
		<meta http-equiv="Pragma" content="no-cache"/>
		<link rel="stylesheet" type="text/css" href="../style/style.css"/>
		<link rel="stylesheet" type="text/css" href="../js/CLeditor/jquery.cleditor.css"/>
		<script type="text/javascript" src="../js/jquery-1.4.2.min.js"></script>
		<script type="text/javascript" src="../js/CLeditor/jquery.cleditor.min.js"></script>
		<script type="text/javascript">
			$(document).ready(function() {
				$(document).ready(function() {
					$("#textarea").cleditor({
						height:  250, // height not including margins, borders or padding
						
						controls:     // controls to add to the toolbar
                        "bold italic underline strikethrough style | color highlight removeformat | bullets numbering | outdent " +
                        "indent | alignleft center alignright justify | undo redo | " + " cut copy"
					});
				});
			});
			
			function switch_user(select) {
				if(select=='entity' && $('#entity').val()!=''){
					$('#user').val('');
				}
				else if (select=='user' && $('#user').val()!=''){
					$('#entity').val('');
				}
			}
		</script>
		<style type='text/css'>
			
			html, body { margin: 0px; padding: 0px; }
			
			
			.error_item {
				padding:2px 0px 0px 20px; 
				text-align:left;
			}
			
			.ossim_success{
				width: auto;
			}	
				
			.ossim_error {
				width: auto;
				padding: 10px 10px 10px 40px;
				font-size: 12px;
			}
			
			.rep_section{
				width: 90%;
				margin:auto;
				padding: 3px 0px;
			}
			
			.rep_label {
				text-align: left;
				font-weight: bold;
				padding-bottom: 3px; 
			}
			
			input[type='text']{ 
				text-align: left; 
				width: 400px; 
				height: 18px;
			}
			
			#keywords { 
				width:98%;
				height: 40px;
			}
						
			
			#user {width: 160px;}
			#entity { width: 225px;}
			
			table { 
				margin: auto; 
				width: 98%;
				background: transparent;
				border:none !important;
			}
			
			#update { 
				padding: 15px 0px 0px 0px;
				border: none;
			}
			
		</style>
	</head>

<body>
	<table cellpadding='0' cellspacing='2' border='0'>
		<?php 
		
		if ( $error == true ) 
		{ 
			$info_error = implode($info_error, "</div><div class='error_item'>");
			?>
			<tr>
				<td class='noborder center'>
					<div class='ossim_error'>
						<div class='error_item' style='padding-left: 5px;'><?php echo _("We found the following errors")?>:</div>
						<div class='error_item'><?php echo $info_error?></div>
					</div>
				</td>
			</tr>
			<?php
		}
		?>
		<tr>
			<td class="nobborder">
				<!-- repository insert form -->
				<form name="repository_insert_form" method="POST" action="<?php echo $_SERVER['SCRIPT_NAME'] ?>" enctype="multipart/form-data">
				
				<table cellpadding='0' cellspacing='2' border='0' class="noborder transparent">
					<tr>
						<td class="nobborder">
							<div class='rep_section'>
								<div class='rep_label'><?php echo _("Title")?>:</div>
								<input type="text" name="title" value="<?php echo POST('title') ?>">
							</div>
						</td>
					</tr>
					
					<tr>
						<td class="nobborder">
							<div class='rep_section'>
								<div class='rep_label'><?php echo _("Text")?>:</div>
								<textarea id="textarea" name="doctext"><?php echo POST('doctext') ?></textarea>
							</div>
						</td>
					</tr>
					
					<tr>
						<td class="nobborder">
							<div class='rep_section'>
								<div class='rep_label'><?php echo _("Keywords")?>:</div>
								<textarea name="keywords" id='keywords'><?php echo POST('keywords') ?></textarea>
							</div>
						</td>
					</tr>
					
					<?php
					$users    = Session::get_users_to_assign($conn);
					$entities = Session::get_entities_to_assign($conn);
					?>
					
					<tr>
						<td class="nobborder">
							<div class='rep_section'>
								<div class='rep_label'><?php echo _("Make this document visible for")?>:</div>
								<div>
									<table cellspacing="0" cellpadding="0" class="transparent">
										<tr>
											<td class='nobborder'><span style='margin-right:3px'><?php echo _("User:");?></span></td>
											<td class='nobborder'>				
												<select name="user" id="user" onchange="switch_user('user');return false;">
												<?php
												$num_users = 0;
												foreach( $users as $k => $v )
												{
													$login = $v->get_login();
													$selected = ( $_POST['user'] == $login ) ? "selected='selected'" : "";
													$options .= "<option value='".$login."' $selected>$login</option>\n";
													$num_users++;
												}
												
												if ($num_users == 0)
													echo "<option value='' style='text-align:center !important;'>- "._("No users found")." -</option>";
												else
												{
													echo "<option value='' style='text-align:center !important;'>- "._("Select one user")." -</option>\n";
													echo $options;
												}
																		
												?>
												</select>
											</td>
														
													
											<?php if ( !empty($entities) ) { ?>
											<td style='text-align:center; border:none; !important'><span style='padding:5px;'><?php echo _("OR")?><span></td>
								
											<td class='nobborder'><span style='margin-right:3px'><?php echo _("Entity:");?></span></td>
											<td class='select_entity noborder'>	
												<select name="entity" id="entity" onchange="switch_user('entity');return false;">
													<option value="" style='text-align:center !important;'>- <?php echo _("Select one entity") ?> -</option>
													<?php
													foreach ( $entities as $k => $v ) 
													{
														$selected = ( $_POST['entity'] == $k ) ? "selected='selected'" : "";
														echo "<option value='$k' $selected>$v</option>";
													}
													?>
												</select>
											</td>
											<?php } ?>
										</tr>
									</table>
								</div>
							</div>
						</td>
					</tr>
					
					<tr><td id='update'><input type='submit' class='button' value='<?php echo _("Update")?>'/></td></tr>
					
				</form>
			</td>
		</tr>
	</table>
<?php
} ?>
</body>
</html>
<?php $db->close($conn); ?>
