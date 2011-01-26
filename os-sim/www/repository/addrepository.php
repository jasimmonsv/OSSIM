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
// get upload dir from ossim config file
require_once 'ossim_conf.inc';
$conf = $GLOBALS["CONF"];
$link_type = (GET('linktype') != "") ? GET('linktype') : "host";
$id = GET('id');
$id_link = GET('id_link');
$name_link = GET('name_link');
$type_link = GET('type_link');
//$id_document = (GET('id_document') != "") ? GET('id_document') : ((POST('id_document') != "") ? POST('id_document') : "");
$linkdoc = GET('linkdoc');
ossim_valid($link_type, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("link type"));
ossim_valid($id, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("id"));
ossim_valid($id_link, OSS_DIGIT, 'illegal:' . _("id_link"));
print_r($name_link);
ossim_valid($name_link, OSS_ALPHA, OSS_PUNC, OSS_SPACE, 'illegal:' . _("name_link"));
ossim_valid($type_link, OSS_ALPHA, 'illegal:' . _("type_link"));
//ossim_valid($id_document, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("id_document"));
ossim_valid($linkdoc, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("linkdoc"));
ossim_valid(GET('insert'), OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("insert"));
ossim_valid(GET('newlinkname'), OSS_ALPHA, OSS_PUNC, '#', OSS_NULLABLE, 'illegal:' . _("newlinkname"));
ossim_valid(GET('key_delete'), OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("key_delete"));
ossim_valid(GET('id_delete'), OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("id_delete"));
if (ossim_error()) {
    die(ossim_error());
}
if ($type_link == "host") $back_link = "../host/host.php";
elseif ($type_link == "incident") $back_link = "../incidents/incident.php?id=$id";
else $back_link = "index.php";

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
//$document = Repository::get_document($conn,$id_document);
$rel_list = Repository::get_relationships_by_link($conn, $id_link);
//list($hostnet_list,$num_rows) = Repository::get_hostnet($conn,$link_type);

?>
<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <script type="text/javascript">parent.document.getElementById('rep_iframe').height='<?php echo (120 + (count($rel_list) * 25)) ?>'</script>
</head>

<body style="margin:0">
<table width="100%">
	<tr><th>RELATIONSHIPS for <?php echo $type ?>: <?php echo $name_link ?></th></tr>
	<?php
if (count($rel_list) > 0) { ?>
	<tr>
		<td>
			<table class="noborder" align="center">
				<tr>
					<th>Name</th>
					<th>Action</th>
				</tr>
				<?php
    foreach($rel_list as $rel) {
?>
				<tr>
					<td class="nobborder"><?php echo $rel['title'] ?></td>
					<td class="nobborder"><a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>?id=<?php echo $id ?>&id_link=<?php echo $id_link ?>&name_link=<?php echo $name_link ?>&key_delete=<?php echo $id_link ?>&id_delete=<?php echo $rel['id_document'] ?>&type_link=<?php echo $type_link ?>"><img src="../repository/images/del.gif" border="0"></a></td>
				</tr>
				<?php
    } ?>
			</table>
		</td>
	</tr>
	<?php
} ?>
<form name="flinks" method="GET">
<input type="hidden" name="id_link" value="<?php echo $id_link
?>">
<input type="hidden" name="id" value="<?php echo $id
?>">
<input type="hidden" name="name_link" value="<?php echo $name_link
?>">
<input type="hidden" name="type_link" value="<?php echo $type_link
?>">
<input type="hidden" name="insert" value="0">
	<tr>
		<td>
			<table class="noborder" align="center">
				<tr>
					<th>Document</th>
					<td></td>
				</tr>
				<tr>
					<td>
						<select name="linkdoc" style="width:220px">
						<?php
foreach($document_list as $document) { ?>
						<option value="<?php echo $document->id_document
?>"><?php echo $document->title
?>
						<?php
} ?>
						</select>
					</td>
					<td><input type="button" class="btn" value="Link" onclick="document.flinks.insert.value='1';document.flinks.submit();"></td>
				</tr>
			</table>
		</td>
	</tr>
</form>
	<tr><td align="center"><input class="btn" type="button" onclick="parent.document.location.href='<?php echo $back_link
?>'" value="Finish"></td></tr>
</table>
</body>
</html>
<?php
$db->close($conn);
?>
