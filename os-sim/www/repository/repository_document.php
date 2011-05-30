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
require_once ("classes/Plugin_sid.inc");
require_once ("classes/Session.inc");
Session::logcheck("MenuIncidents", "Osvdb");
$user        = $_SESSION["_user"];
$id_document = (GET('id_document') != "") ? GET('id_document') : ((POST('id_document') != "") ? POST('id_document') : "");

if (!ossim_valid($id_document, OSS_DIGIT, 'illegal:' . _("id_document"))) exit;

$maximized = (GET('maximized') != "") ? 1 : 0;
// DB Connection

require_once ("ossim_db.inc");
$db   = new ossim_db();
$conn = $db->connect();
$document  = Repository::get_document($conn, $id_document);
$atch_list = Repository::get_attachments($conn, $id_document);
$rel_list  = Repository::get_relationships($conn, $id_document);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" CONTENT="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<style type='text/css'>
		body { margin: 0px;}
		.main_table {
			width: 98%;
			text-align: center;
			margin: 10px auto 5px auto;
			border: none;
		}
	</style>
</head>

<body>
<table cellpadding='0' cellspacing='2' class="transparent main_table">
	<!--
	<tr>
		<?php
if (!$maximized) { ?>
		<td style="padding:3px"><a href="repository_document.php?id_document=<?php echo $id_document
?>&maximized=1" target="_parent"><img src="images/max.gif" align="absmiddle" border=0><?php echo _("Maximize")?></a></td>
		<?php
} else { ?>
		<td style="padding:3px"><a href="index.php?pag=<?php echo GET('pag') ?>&search_bylink=<?php echo GET('search_bylink') ?>"><?php echo _("Back to main") ?></a></td>
		<?php
} ?>
	</tr>-->
	<tr>
		<td class="nobborder">
			<table cellpadding='0' cellspacing='0' border='0' width='100%'>
				<tr>
					<td class="nobborder" valign="top" width="250px" style="padding-right:10px">
						<table cellpadding='0' cellspacing='2' border='0' width="100%" class='noborder'>
							<tr><th class="kdb"><?php echo _("Date")?></th></tr>
							<tr><td class="center" style="padding-left:5px"><?php echo $document['date'] ?></td></tr>
							<tr><th class="kdb"><?php echo _("User")?></th></tr>
							<tr><td class="center" style="padding-left:5px"><?php echo $document['user'] ?></td></tr>
							<tr><th class="kdb"><?php echo _("Keywords")?></th></tr>
							<tr><td class="center" style="padding-left:5px"><?php echo ( !empty($document['keywords']) ) ? $document['keywords'] : " <span style='color:#696969;'>"._("No Keywords defined")."</span> " ?></td></tr>
							<tr><th class="kdb"><?php echo _("Attachments")?></th></tr>
							<!-- Attachments -->
							<tr>
								<td class='nobborder center'>
									<table class="noborder" align="center">
										<?php
										if ( count($atch_list) > 0 )
										{
											foreach($atch_list as $f) 
											{
												$type     = ($f['type'] != "") ? $f['type'] : "unkformat";
												$img      = (file_exists("images/$type.gif")) ? "images/$type.gif" : "images/unkformat.gif";
												$filepath = "../uploads/$id_document/" . $f['id_document'] . "_" . $f['id'] . "." . $f['type'];
											?>
											<tr>
												<td align='center' class="nobborder"><img src="<?php echo $img?>"/></td>
												<td class="nobborder"><a href="view.php?id=<?php echo $f['id_document'] ?>_<?php echo $f['id'] ?>"><?php echo $f['name'] ?></a></td>
												<td class="nobborder"><a href="download.php?id=<?php echo $f['id_document'] ?>_<?php echo $f['id'] ?>"><img src="images/download.gif" border="0"></a></td>
											</tr>
											<?php
											}
										}
										else
											echo "<tr><td class='noborder center'><span style='color:#696969;'>"._("No attached files")."</span></td></tr>";
										
										?>
									</table>
								</td>
							</tr>
							<tr><th class="kdb"><?php echo _("Links")?></th></tr>
							<!-- Relationships -->
							<tr>
								<td class="nobborder">
									<table class="noborder" align="center">
										<?php
										if ( count($rel_list) > 0 )
										{
											foreach($rel_list as $rel) 
											{
												if ($rel['type'] == "host")       $page = "../report/index.php?host=" . $rel['key'];
												if ($rel['type'] == "net")        $page = "../net/net.php";
												if ($rel['type'] == "host_group") $page = "../host/hostgroup.php";
												if ($rel['type'] == "net_group")  $page = "../net/netgroup.php";
												if ($rel['type'] == "incident")   $page = "../incidents/incident.php?id=" . $rel['key'];
												if ($rel['type'] == "directive")  $page = "../directive_editor/index.php?hmenu=Directives&smenu=Directives&level=1&directive=" . $rel['key'];
												if ($rel['type'] == "plugin_sid") $page = "../forensics/base_qry_main.php?clear_allcriteria=1&search=1&sensor=&sip=&plugin=&ossim_risk_a=+&hmenu=Forensics&smenu=Forensics&submit=Signature&search_str=" . urlencode(Plugin_sid::get_name_by_idsid($conn,$rel['key'],$rel['name']));
												?>
												<tr>
													<td class="nobborder"><a href="<?php echo $page?>" target="main"><?php echo ($rel['type'] == "plugin_sid") ? $rel['key']." (".$rel['name'].")" : $rel['name'] ?></a></td>
													<td class="nobborder"><?php echo ($rel['type'] == "incident") ? "ticket" : $rel['type'] ?></td>
												</tr>
												<?php
											} 
										}
										else
											echo "<tr><td class='noborder center'><span style='color:#696969;'>"._("No related links")."</span></td></tr>";
									
										?>
									</table>
								</td>
							</tr>
						</table>
					</td>
					<td valign="top" class="noborder" style='border-left: solid 1px #CCCCCC;'>
						<table cellpadding='0' cellspacing='2' border='0' width='100%' class="noborder">
							<tr>
								<td class="noborder left" style="padding-left:5px;">
									<?php echo $document['text'] ?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<?php $db->close($conn); ?>
</body>
</html>
