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
// menu authentication
require_once ('classes/Session.inc');
Session::logcheck("MenuIncidents", "Osvdb");
$user = $_SESSION["_user"];
// Get a list of nets from db
require_once ("ossim_db.inc");
$db = new ossim_db();
$conn = $db->connect();
require_once ("classes/Repository.inc");
require_once 'ossim_conf.inc';
$conf = $GLOBALS["CONF"];
$nmap_path = $conf->get_conf("nmap_path");
$version = $conf->get_conf("ossim_server_version", FALSE);

if (file_exists($nmap_path)) {
    $nmap_exists = 1;
} else {
    $nmap_exists = 0;
}
$search_str = (GET('searchstr') != "") ? GET('searchstr') : "";
$id_document = (GET('id_document') != "") ? GET('id_document') : "";
$search_bylink = (GET('search_bylink') != "") ? GET('search_bylink') : "";
// Pagination variables
$maxrows = 10;
$pag = (GET('pag') != "") ? GET('pag') : 1;
$from = ($pag - 1) * $maxrows;
$order = (GET('order') != "") ? GET('order') : "";
$torder = (GET('torder')) ? 1 : 0;

// default order (date DESC)
if ($order == "" && !$torder) { $order = "date"; $torder = 1; }

ossim_valid($id_document, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("id_document"));
ossim_valid($pag, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("pag"));
ossim_valid($order, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("order"));
ossim_valid($torder, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("torder"));
ossim_valid($search_str, OSS_TEXT, OSS_NULLABLE, 'illegal:' . _("searchstr"));
if (ossim_error()) {
    die(ossim_error());
}

if ($search_bylink != "")
	list($repository_list, $total) = Repository::get_list_bylink($conn, $from, $maxrows, $search_bylink);
else
	list($repository_list, $total) = Repository::get_list($conn, $from, $maxrows, $search_str, $order, $torder);
$total_pages = floor(($total - 1) / $maxrows) + 1;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/greybox.js"></script>
  <script type="text/javascript" src="../js/urlencode.js"></script>
  <script type="text/javascript">
  function deletesubmit(txt,id) {
	if (confirm(txt+"\nAre you sure?")) {
		//document.getElementById('repository_frame').src="repository_delete.php?id_document="+id;
		GB_show("<?=_("New Document")?>","repository_delete.php?id_document="+id,"80%","80%");
	}	
  }
  function newdoc(url) {
    GB_TYPE = 'w';
    GB_show("<?=_("New Document")?>","repository_newdocument.php?full=1","80%","80%");
  }
  <? if ($id_document == "") { ?>function GB_onclose() { document.location.reload(); }<? } ?>

  // GrayBox
	$(document).ready(function(){
		GB_TYPE = 'w';
		$("a.greybox").click(function(){
			var t = this.title || $(this).text() || this.href;
			GB_show(t,this.href,'80%',500);
			return false;
		});
		$("a.greyboxw").click(function(){
			var t = this.title || $(this).text() || this.href;
			GB_show(t,this.href,400,'80%');
			return false;
		});
		$("a.greyboxo").click(function(){
			var t = this.title || $(this).text() || this.href;
			GB_show(t,this.href,180,300);
			return false;
		});
		<? if ($id_document != "") { ?>
		var ref = 'repository_document.php?maximized=1&id_document=<?=$id_document?>';
		GB_show('<?=$search_str?>',ref,400,'80%');
		<? } ?>
	});
  
  </script>
</head>

<body>
<?php
include ("../hmenu.php"); ?>
<table cellpadding=0 cellspacing=2 border=0 width="100%" class="transparent">
	<tr>
		<td valign="top" class="nobborder">
			<table cellpadding=0 cellspacing=2 border=0 width="100%" class="transparent">
				<tr>
					<td align=center class="nobborder" style="padding-bottom:10px">
					  <table align="center" width="100%" style="border: 1px solid rgb(170, 170, 170);background:url(../pixmaps/fondo_hdr2.png) repeat-x">
					    <!-- repository search form -->
						<form name="repository_search_form" method="GET" action="<?php echo $_SERVER['SCRIPT_NAME'] ?>">
						<tr><td class="center nobborder" style="font-size:14px;color:#333333;font-weight:bold"><?=_("Knowledge DB Document Search")?></td></tr>
						<tr>
					      <td class="center nobborder" style="padding:10px">
					        <?php
echo gettext("Please, type a search term (you can use AND, OR clauses):") ?><input type="text" value="<?php echo $search_str ?>" size="35" name="searchstr" enabled /> <input type="submit" class="btn" value="<?php
echo gettext("Search") ?>" <?php echo (!$nmap_exists) ? "disabled" : "" ?> />
					      </td>
					    </tr>
					    <tr>
					      <td class="center nobborder"><input type="button" class="btn" value="<?=_("New Document")?>" onclick="newdoc()" style="font-weight:bold;font-size:12px"></td>
					    </tr>
						</form>
						<!-- end of repository search form -->
					  </table>
					 </td>
				</tr>
				
				<tr>
					<td class="nobborder"><img src="../pixmaps/arrow_green.gif" align="absmiddle"> <?=_("Showing")?> <b><?=$from+1?></b>-<b><?=($total > $from+$maxrows) ? $from+$maxrows : $total?></b> of <b><?=$total?></b> <?=_("Documents")?></td>
				</tr>
				
				<tr>
					<td class="nobborder">
						<table cellpadding=0 cellspacing=1 border=0 width="100%" style="border: 1px solid rgb(170, 170, 170);border-radius: 0px; -moz-border-radius: 0px; -webkit-border-radius: 0px">
							<tr>
								<td class="kdb" style="border-right:1px solid #CACACA;border-bottom:1px solid #CACACA">
								<? if ($order=="date") echo ($torder) ? "<img src='../forensics/images/order_sign_d.gif' align='absmiddle'>" : "<img src='../forensics/images/order_sign_a.gif' align='absmiddle'>" ?>
								<a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?searchstr=<?=$search_str?>&order=date<? if ($order == "date" && !$torder) echo "&torder=1"?>" style="color:#333333;font-size:12px<? if ($order == "date") echo ";text-decoration:underline" ?>">
						        <?php
echo gettext("Date"); ?></a>
								<? if ($order=="date") echo ($torder) ? "<img src='../forensics/images/order_sign_d.gif' align='absmiddle'>" : "<img src='../forensics/images/order_sign_a.gif' align='absmiddle'>" ?>
								</td>
						        <td class="kdb">
								<? if ($order=="user") echo ($torder) ? "<img src='../forensics/images/order_sign_d.gif' align='absmiddle'>" : "<img src='../forensics/images/order_sign_a.gif' align='absmiddle'>" ?>
								<a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?searchstr=<?=$search_str?>&order=user<? if ($order == "user" && !$torder) echo "&torder=1"?>" style="color:#333333;font-size:12px<? if ($order == "user") echo ";text-decoration:underline" ?>">
						        <?php
echo gettext("Owner"); ?></a>
								<? if ($order=="user") echo ($torder) ? "<img src='../forensics/images/order_sign_d.gif' align='absmiddle'>" : "<img src='../forensics/images/order_sign_a.gif' align='absmiddle'>" ?>
								</td>
								<td class="kdb">
								<? if ($order=="title") echo ($torder) ? "<img src='../forensics/images/order_sign_d.gif' align='absmiddle'>" : "<img src='../forensics/images/order_sign_a.gif' align='absmiddle'>" ?>
								<a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?searchstr=<?=$search_str?>&order=title<? if ($order == "title" && !$torder) echo "&torder=1"?>" style="color:#333333;font-size:12px<? if ($order == "title") echo ";text-decoration:underline" ?>">
						        <?php
echo gettext("Title"); ?></a>
								<? if ($order=="title") echo ($torder) ? "<img src='../forensics/images/order_sign_d.gif' align='absmiddle'>" : "<img src='../forensics/images/order_sign_a.gif' align='absmiddle'>" ?>
								</td>
								<td class="kdb" style="color:#333333;font-size:12px">
						        <?php
echo gettext("Attach"); ?>
								</td>
								<td class="kdb" style="color:#333333;font-size:12px">
						        <?php
echo gettext("Links"); ?>
								</td>
								<?php
if ($search_str != "") { ?>
								<td class="kdb" style="color:#333333;font-size:12px">
						        <?php
    echo gettext("Relevance"); ?>
								</td>
								<?php
} ?>
								<td class="kdb" style="color:#333333;font-size:12px"><?=_("Action")?></td>
							</tr>
							<?php
$i = 0;
foreach($repository_list as $repository_object) {
    if (!$i && $id_document == "" && $search_bylink != "") $color = "#D7DEE4";
    elseif ($id_document == $repository_object->id_document) $color = "#D7DEE4";
    else $color = ($i%2==0) ? "#F2F2F2" : "#FFFFFF";
?>
							<tr bgcolor="<?php echo $color
?>">
								<td class="center nobborder"><?php echo $repository_object->date
?></td><?
    $username = $repository_object->user;
    if(preg_match('/\d+/', $repository_object->user)) {
        list($entities_all, $num_entities) = Acl::get_entities($conn, $repository_object->user);
        $username = $entities_all[$repository_object->user]['name'];
    }?>
								<td class="center nobborder"><?php echo $username
?></td>
								<td class="left nobborder">
									<a href="repository_document.php?id_document=<?php echo $repository_object->id_document
?>&maximized=1&search_bylink=<?php echo $search_bylink
?>&pag=<?php echo $pag
?>" class="greyboxw" title="<?php echo $repository_object->title ?>" style="font-weight:normal;color:black"><?php echo $repository_object->title
?></a>
								</td>
								<td class="nobborder"><table align="center" class="transparent"><tr><?php
    if (count($repository_object->atch) > 0) echo "<td class='nobborder'>(" . count($repository_object->atch) . ")</td>"; ?><td class="nobborder"><a href='repository_attachment.php?id_document=<?php echo $repository_object->id_document ?>' class="greybox" title="<?=_("Attachments for Document")?>: <?php echo preg_replace("/(.............................).*/","\\1...",$repository_object->title) ?>"><img src='images/attach.gif' border=0 alt="<?=_("Attached Files")?>" title="<?=_("Attached Files")?>"></a></td></tr></table></td>
								<td class="nobborder"><table align="center" class="transparent"><tr><?php
    if (count($repository_object->rel) > 0) echo "<td class='nobborder'><small>(" . count($repository_object->rel) . ")</small></td>"; ?><td class="nobborder"><a href="repository_links.php?id_document=<?php echo $repository_object->id_document ?>" class="greybox" title="<?=_("Relationships for Document")?>: <?php echo preg_replace("/(.............................).*/","\\1...",$repository_object->title) ?>"><img src="images/linked2.gif" border=0 alt="<?=_("Linked Elements")?>" title="<?=_("Linked Elements")?>"></a></td></tr></table></td>
								<?php
    if ($search_str != "") { ?>
								<td class="center nobborder"><?php echo $repository_object->get_relevance() ?>%</td>
								<?php
    } ?>
								<td nowrap class="center nobborder">
									<a href="repository_delete.php?id_document=<?php echo $repository_object->id_document
?>" onclick="deletesubmit('Document with attachments will be deleted.',<?php echo $repository_object->id_document
?>);return false;"><img src="../pixmaps/tables/table_row_delete.png" border=0 alt="<?=_("Delete Document")?>" title="<?=_("Delete Document")?>"></a>
									<a href="repository_editdocument.php?id_document=<?php echo $repository_object->id_document
?>" class="greybox" title="<?=_("Edit Document")?>"><img src="../pixmaps/tables/table_edit.png" border=0 alt="<?=_("Edit Document")?>" title="<?=_("Edit Document")?>"></a>
           <?
            if (Session::am_i_admin()) {?>
                    <a class="greyboxo" href="change_user.php?id_document=<?=$repository_object->id_document?>" title="<?=("Change owner")?>"><img src="../pixmaps/group.png" title="<?_("Change owner")?>" alt="<?_("Change owner")?>" border="0"></a>
                <?}
            else if(preg_match("/pro/i",$version)){
                if(Acl::am_i_proadmin()) {?>
                    <a class="greyboxo" href="change_user.php?id_document=<?=$repository_object->id_document?>" title="<?=("Change owner")?>"><img src="../pixmaps/group.png" title="<?_("Change owner")?>" alt="<?_("Change owner")?>" border="0"></a>
                <?}
            }
                ?>
								</td>
							</tr>
							<?php
    $i++;
} 
if (count($repository_list)==0) {
	echo "<tr><td class='nobborder' colspan='7' style='padding-top:5px;padding-bottom:5px;text-align:center'><b>"._("No documents found for current search criteria")."</b></td></tr>";
}
?>

						</table>
					</td>
				</tr>
<?if (count($repository_list)>0) { ?>
				<!-- Pagination -->
				<tr>
					<td class="center nobborder" style="padding-top:10px">
						<table class="transparent" align="center" style="border: 1px solid rgb(170, 170, 170);background:url(../pixmaps/fondo_hdr2.png) repeat-x">
							<tr>
							<td class="nobborder" style="padding:10px"><b><?=_("Pages")?>:</b></td>
							<td class="nobborder" style="padding:5px">
							<?php
for ($i = 1; $i <= $total_pages; $i++) { ?>
							<?php
    if ($i == $pag) { ?>
							<font style="font-size:14px"><b><?php echo $i
?></b></font>&nbsp;
							<?php
    } else { ?>
							<a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>?searchstr=<?php echo $search_str ?>&search_bylink=<?php echo $search_bylink ?>&id_document=<?php echo $id_document ?>&order=<?=$order?>&torder=<?=$torder?>&pag=<?php echo $i ?>" style="color:black"><?php echo $i ?></a>&nbsp;
							<?php
    } ?>
							<?php
} ?>
							</td></tr>
						</table>
					</td>
				</tr>
<?}?>
			</table>
		</td>
		
	</tr>
</table>
</body>
</html>
<?
$db->close($conn);
?>
