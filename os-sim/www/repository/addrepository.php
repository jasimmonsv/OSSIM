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
require_once 'ossim_conf.inc';
Session::logcheck("MenuIncidents", "Osvdb");

$user      = $_SESSION["_user"];
$conf      = $GLOBALS["CONF"];
$link_type = (GET('linktype') != "") ? GET('linktype') : "incident";
$id        = GET('id');
$id_link   = GET('id_link');
$name_link = GET('name_link');
$type_link = GET('type_link');
//$id_document = (GET('id_document') != "") ? GET('id_document') : ((POST('id_document') != "") ? POST('id_document') : "");
$linkdoc = GET('linkdoc');

ossim_valid($link_type, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("link type"));
ossim_valid($id, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("id"));
ossim_valid($id_link, OSS_DIGIT, 'illegal:' . _("id_link"));
ossim_valid($name_link, OSS_ALPHA, OSS_PUNC, OSS_SPACE, 'illegal:' . _("name_link"));
ossim_valid($type_link, OSS_ALPHA, 'illegal:' . _("type_link"));
//ossim_valid($id_document, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("id_document"));
ossim_valid($linkdoc, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Linkdoc"));
ossim_valid(GET('insert'), OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Insert"));
ossim_valid(GET('newlinkname'), OSS_ALPHA, OSS_PUNC, '#', OSS_NULLABLE, 'illegal:' . _("Newlinkname"));
ossim_valid(GET('key_delete'), OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("Key_delete"));
ossim_valid(GET('id_delete'), OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Id_delete"));

if (ossim_error()) {
    die(ossim_error());
}

// DB connect
require_once ("ossim_db.inc");
$db = new ossim_db();
$conn = $db->connect();
list($document_list, $documents_num_rows) = Repository::get_list($conn);

// New link on relationships
if ($linkdoc != "" && GET('insert') == "1") {
    $aux = explode("####", GET('newlinkname'));
    Repository::insert_relationships($conn, $linkdoc , $name_link, $type_link, $id_link);
}

// Delete link on relationships
if (GET('key_delete') != "" && GET('id_delete') != "") {
    Repository::delete_relationships($conn, GET('id_delete') , GET('key_delete'));
}

$rel_list = Repository::get_relationships_by_link($conn, $id_link);


?>
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	
	<script type="text/javascript">
		function send_form()
		{
			document.flinks.insert.value='1';
			document.flinks.submit();
		}
		
		//parent.document.getElementById('rep_iframe').height='<?php echo (120 + (count($rel_list) * 25)) ?>';
		
	</script>
	<style type='text/css'>
		body {margin: 0px; background-color: #FFFFFF;}
		
		table { margin: 10px auto;  }
	</style>
</head>

<body>

<table width="90%">
	<tr><th style='height: 18px;'><?php echo _("RELATIONSHIPS for")." ". $type ?>: <?php echo $name_link ?></th></tr>
	<?php
	if (count($rel_list) > 0) 
	{ 
		?>
		<tr>
			<td>
				<table class="noborder" align="center">
					<tr>
						<th><?php echo _("Name")?></th>
						<th><?php echo _("Action")?></th>
					</tr>
					<?php
					foreach($rel_list as $rel)
					{
						?>
						<tr>
							<td class="nobborder"><?php echo $rel['title'] ?></td>
							<td class="nobborder" style='text-align:center;'><a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>?id=<?php echo $id ?>&id_link=<?php echo $id_link ?>&name_link=<?php echo $name_link ?>&key_delete=<?php echo $id_link ?>&id_delete=<?php echo $rel['id_document'] ?>&type_link=<?php echo $type_link ?>"><img src="../repository/images/del.gif" border="0"/></a></td>
						</tr>
						<?php
					} ?>
				</table>
			</td>
		</tr>
		<?php
	} 
	?>
	
	<form name="flinks" method="GET">
		<input type="hidden" name="id_link" value="<?php echo $id_link?>"/>
		<input type="hidden" name="id" value="<?php echo $id?>"/>
		<input type="hidden" name="name_link" value="<?php echo $name_link?>"/>
		<input type="hidden" name="type_link" value="<?php echo $type_link?>"/>
		<input type="hidden" name="insert" value="0"/>
	<tr>
		<td class='nobborder'>
			<table class="noborder" align="center" width='360px'>
				<tr>
					<th colspan='2'><?php echo _("Document")?></th>
				</tr>
				<tr>
					<td class='nobborder' style='width: 230px;'>
						<select name="linkdoc" style="width:220px">
						<?php
							foreach($document_list as $document) 
							{ 
								?>
								<option value="<?php echo $document->id_document?>"><?php echo $document->title?>
								<?php
							} ?>
						</select>
					</td>
					<td class='nobborder left'><input type="button" class="lbutton" value="Link" onclick="send_form();"/></td>
				</tr>
			</table>
		</td>
	</tr>
	</form>

</table>
</body>
</html>
<?php $db->close($conn); ?>
