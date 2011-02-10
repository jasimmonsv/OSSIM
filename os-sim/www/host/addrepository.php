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
Session::logcheck("MenuPolicy", "ToolsScan");
$user = $_SESSION["_user"];
// get upload dir from ossim config file
require_once 'ossim_conf.inc';
$conf = $GLOBALS["CONF"];
$link_type = (GET('linktype') != "") ? GET('linktype') : "host";
$id_host = GET('id_host');
$name_host = GET('name_host');
//$id_document = (GET('id_document') != "") ? GET('id_document') : ((POST('id_document') != "") ? POST('id_document') : "");
if ($id_host == "" || $name_host == "") exit;
ossim_valid($name_host, OSS_TEXT, 'illegal:' . _("name_host"));
//ossim_valid($id_document , OSS_DIGIT, 'illegal:' . _("id_document"));
ossim_valid($id_host, OSS_TEXT, 'illegal:' . _("id_host"));
ossim_valid($link_type, OSS_ALPHA, 'illegal:' . _("link_type"));
if (ossim_error()) {
    die(ossim_error());
}
// DB connect
require_once ("ossim_db.inc");
$db = new ossim_db();
$conn = $db->connect();
list($document_list, $documents_num_rows) = Repository::get_list($conn);
// New link on relationships
if (GET('linkdoc') != "" && GET('insert') == "1") {
    $aux = explode("####", GET('newlinkname'));
    Repository::insert_relationships($conn, GET('linkdoc') , $name_host, "host", $id_host);
}
// Delete link on relationships
if (GET('key_delete') != "" && GET('id_delete') != "") {
    Repository::delete_relationships($conn, GET('id_delete') , GET('key_delete'));
}
//$document = Repository::get_document($conn,$id_document);
$rel_list = Repository::get_relationships_by_link($conn, $id_host);
//list($hostnet_list,$num_rows) = Repository::get_hostnet($conn,$link_type);

?>
<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>

<body style="margin:5px" onload="self.focus()">
<table width="90%" align="center">
	<tr><th><?=_("RELATIONSHIPS for host")?>: <?php echo $id_host ?></th></tr>
	<?php
if (count($rel_list) > 0) { ?>
	<tr>
		<td>
			<table class="noborder" align="center">
				<tr>
					<th><?=_("Name")?></th>
					<th><?=_("Action")?></th>
				</tr>
				<?php
    foreach($rel_list as $rel) {
?>
				<tr>
					<td class="nobborder"><?php echo $rel['title'] ?></td>
					<td class="nobborder"><a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>?id_host=<?php echo $id_host ?>&name_host=<?php echo $name_host ?>&key_delete=<?php echo $id_host ?>&id_delete=<?php echo $rel['id_document'] ?>"><img src="../repository/images/del.gif" border="0"></a></td>
				</tr>
				<?php
    } ?>
			</table>
		</td>
	</tr>
	<?php
} ?>
<form name="flinks" method="GET">
<input type="hidden" name="id_host" value="<?php echo $id_host
?>">
<input type="hidden" name="name_host" value="<?php echo $name_host
?>">
<input type="hidden" name="insert" value="0">
	<tr>
		<td class="noborder">
			<table class="noborder" align="center">
				<tr>
					<th><?=_("Document")?></th>
					<td></td>
				</tr>
				<tr>
					<td class="noborder">
						<select style="width:300px" name="linkdoc">
						<?php
foreach($document_list as $document) { ?>
						<option value="<?php echo $document->id_document
?>"><?php echo $document->title
?>
						<?php
} ?>
						</select>
					</td>
					<td class="noborder"><input type="button" class="lbutton" value="<?=_("Link")?>" onclick="document.flinks.insert.value='1';document.flinks.submit();"></td>
				</tr>
			</table>
		</td>
	</tr>
</form>
	<tr><td align="center" class="noborder"><input type="button" class="button" onclick="parent.document.location.href='host.php'" value="<?=_("Finish")?>"></td></tr>
</table>
</body>
</html>
<?php
$db->close($conn);
?>
