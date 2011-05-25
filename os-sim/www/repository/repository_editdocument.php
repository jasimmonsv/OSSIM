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
require_once ("classes/Repository.inc");
require_once ('classes/Session.inc');
Session::logcheck("MenuIncidents", "Osvdb");

require_once ("ossim_db.inc");
$db   = new ossim_db();
$conn = $db->connect();

$user        = $_SESSION["_user"];
$error       = false;
$id_document = (GET('id_document') != "") ? GET('id_document') : ((POST('id_document') != "") ? POST('id_document') : "");

ossim_valid($id_document, OSS_DIGIT, 'illegal:' . _("Id_document"));

if ( ossim_error() ) 
{
   $info_error[] =  ossim_get_error();
   $error        = true;
}

if ( isset($_POST['title']) || isset($_POST['doctext']) ) 
{
	if ( POST('title') == "" ) 
	{
		$info_error[] = _("Error in the 'title' field (missing required field)");
		$error        = true;
	}
	
	if ( POST('doctext') == "" ) 
	{
		$info_error[] = _("Error in the 'text' field (missing required field)");
		$error        = true;
	}
	
	if ( $error == false ) 
	{
		
		$title    = POST('title');
		$doctext  = strip_tags(POST('doctext'),'<big><span><div><a><p><ol><ul><li><hr><br>');
		$keywords = POST('keywords');
		
		Repository::update($conn, $id_document, $title , $doctext , $keywords);
	}
}

?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" CONTENT="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<link rel="stylesheet" type="text/css" href="../style/jquery.wysiwyg.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery.wysiwyg.js"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			$('#textarea').wysiwyg({
			css : { fontFamily: 'Arial, Tahoma', fontSize : '13px'}
			});	
		});							
	</script>
	
	<style type='text/css'>
		.cont_delete {
			width: 80%;
			text-align:center;
			margin: 10px auto;
		}
		
		.ossim_error, .ossim_success { width: auto;}
		
		body {margin:0px;}
		
		table {
			width: 98%;
			margin: auto;
		}
		
		.pad_title{ padding:3px 0px 0px 5px; }
		
		#update { 
			padding: 10px 0px 0px 0px;
			border: none;
			margin:auto;
			text-align: center;
		}
		
		.error_item {
			padding:2px 0px 0px 20px; 
			text-align:left;
		}
		
	</style>
</head>

<body>

<?php 
	
	if ( (isset($_POST['title']) || isset($_POST['doctext'] )) && $error == false )
	{
		?>
			<table cellpadding='0' cellspacing='2' border='0' class="transparent">
				<tr>
					<td class="center">
						<div class='ossim_success'>
							<?php echo _("Document successfully updated with id")?>: <?php echo $id_document ?>
						</div>
					</td>
				</tr>
				<tr>
					<td class="center" style='padding:5px;'>
						<input class="button" type="button" onclick="parent.document.location.href='index.php'" value="Finish"/>
					</td>
				</tr>
			</table>
			
			<?php
	}
	else
	{
	
		if ( isset($_POST['title']) || isset($_POST['doctext']) )
		{
			$title    = $_POST['title'];
			$text     = $_POST['doctext'];
			$keywords = $_POST['keywords'];
		}
		else
		{
			$document = Repository::get_document($conn, $id_document);
			$title    = $document['title'];
			$text     = $document['text'];
			$keywords = $document['keywords'];
			
		}
		?>
		<table cellpadding='0' cellspacing='2' border='0' width="99%" class="transparent">
			<?php
			
			if ( $error == true ) 
			{ 
				$info_error = implode($info_error, "</div><div class='error_item'>");
				?>
				<tr>
					<td>
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
						<input type="hidden" name="id_document" value="<?php echo $id_document ?>">
						<table cellpadding='0' cellspacing='2' border='0' class="transparent">
							<tr>
								<td class="nobborder pad_title"><strong><?php echo _("Title")?>:</strong></td>
							</tr>
							<tr>
								<td class="nobborder pad_title"><input type="text" name="title" style="width:473px" value="<?php echo $title;?>"></td>
							</tr>
							<tr>
								<td class="nobborder pad_title"><strong><?php echo _("Text") ?>:</strong></td>
							</tr>
							<tr>
								<td class="nobborder pad_title">
									<textarea id="textarea" name="doctext" rows="4" style="width:460px; height: 150px"><?php echo $text;?></textarea>
								</td>
							</tr>
							
							<tr>
								<td class="nobborder" style="padding-left:5px"><strong><?php echo _("Keywords") ?>:</strong></td>
							</tr>
							<tr>
								<td class="nobborder" style="padding-left:5px">
									<textarea name="keywords" cols="73"><?php echo $keywords;?></textarea>
								</td>
							</tr>
							
							<tr><td class="nobborder" id='update'><input class="button" type="submit" value="<?php echo _("Update") ?>"></td></tr>
						</table>
					</form>
					<!-- end of repository insert form -->
				</td>
			</tr>
		</table>
			
		<?php
	}
	
$db->close($conn);
?>

</body>
</html>
