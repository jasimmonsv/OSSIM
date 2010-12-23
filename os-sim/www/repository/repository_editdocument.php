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
// menu authentication
require_once ('classes/Session.inc');
Session::logcheck("MenuIncidents", "Osvdb");
$user = $_SESSION["_user"];
$id_document = (GET('id_document') != "") ? GET('id_document') : ((POST('id_document') != "") ? POST('id_document') : "");
if (!ossim_valid($id_document, OSS_DIGIT, 'illegal:' . _("id_document"))) exit;
// DB Connection
require_once ("ossim_db.inc");
$db = new ossim_db();
$conn = $db->connect();
if (POST('title') != "" && POST('doctext') != "") {
    Repository::update($conn, $id_document, POST('title') , POST('doctext') , POST('keywords'));
?>
<html>
<head>
  <title> <?php
    echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>

<body style="margin:0">
<table cellpadding=0 cellspacing=2 border=0 width="100%" class="transparent">
	<tr>
		<td class="center"><?=_("Document successfully updated with id")?>: <?php echo $id_document ?></td>
	</tr>
	<tr><td class="center"><input class="button" type="button" onclick="parent.document.location.href='index.php'" value="Finish"></td></tr>
</table>
<?php
} else {
    $document = Repository::get_document($conn, $id_document);
?>
<html>
<head>
  <title> <?php
    echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
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
</head>

<body style="margin:0">
<table cellpadding=0 cellspacing=2 border=0 width="100%" class="transparent">
	<tr>
		<td class="nobborder">
			<!-- repository insert form -->
			<form name="repository_insert_form" method="POST" action="<?php echo $_SERVER['SCRIPT_NAME'] ?>" enctype="multipart/form-data">
			<input type="hidden" name="id_document" value="<?php echo $id_document ?>">
			<table cellpadding=0 cellspacing=2 border=0 class="transparent">
				<tr>
					<td class="nobborder" style="padding-left:5px"><b><?=_("Title")?>:</b></td>
				</tr>
				<tr>
					<td class="nobborder" style="padding-left:5px"><input type="text" name="title" style="width:473px" value="<?php echo $document['title'] ?>"></td>
				</tr>
				<tr>
					<td class="nobborder" style="padding-left:5px"><b><?php echo _("Text") ?>:</b></td>
				</tr>
				<tr>
					<td class="nobborder" style="padding-left:5px">
						<textarea id="textarea" name="doctext" rows="4" style="width:460px; height: 150px"><?php echo $document['text'] ?></textarea>
					</td>
				</tr>
				
				<tr>
					<td class="nobborder" style="padding-left:5px"><b><?php echo _("Keywords") ?>:</b></td>
				</tr>
				<tr>
					<td class="nobborder" style="padding-left:5px">
						<textarea name="keywords" cols="73"><?php echo $document['keywords'] ?></textarea>
					</td>
				</tr>
				
				<tr><td class="nobborder" style="padding-left:5px;text-align:center"><input class="button" type="submit" value="<?php echo _("Update") ?>"></td></tr>
			</table>
			</form>
			<!-- end of repository insert form -->
		</td>
	</tr>
</table>
<?php
}
$db->close($conn); ?>
</body>
</html>
