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
Session::logcheck("MenuIntelligence", "CorrelationDirectives");
require_once ('ossim_conf.inc');
require_once ('classes/Security.inc');
require_once ("include/utils.php");
require_once ('include/category.php');
require_once ('include/directive.php');

function xml_backdata($file) {
	$ret = array();
	$lines = file('/etc/ossim/server/'.$file);
	foreach ($lines as $line) {
		if (preg_match("/directive id\=\"(\d+)\"/",$line,$found)) {
			$ret[$found[1]]++;
		}
	}
	return $ret;
}

$action        = GET('action');
$category_file = GET('xml_file');
$category_name = GET('name');
$query = POST('query');
$nohide = ($query != "" || $action != "") ? 1 : 0;
ossim_valid($action, OSS_ALPHA, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("action"));
ossim_valid($category_file, OSS_ALPHA, OSS_DOT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("xml_file"));
ossim_valid($category_name, OSS_LETTER, OSS_DIGIT, OSS_SCORE, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("name"));
ossim_valid($query, OSS_TEXT, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("query"));
if (ossim_error()) {
    die(ossim_error());
}
if ($action == "enable_category") {
	enable_category($category_name,$category_file);
} elseif ($action == "disable_category") {
	disable_category($category_name,$category_file);
}

init_groups();
init_categories();

$conf = $GLOBALS["CONF"];
$XML_FILE = '/etc/ossim/server/directives.xml';
$xml = domxml_open_file($XML_FILE, DOMXML_LOAD_SUBSTITUTE_ENTITIES);

// Edit Disallowed categories
$cannotedit = array(
	"alienvault-attacks.xml" => 1,
	"alienvault-bruteforce.xml" => 1,
	"alienvault-dos.xml" => 1,
	"alienvault-malware.xml" => 1,
	"alienvault-misc.xml" => 1,
	"alienvault-network.xml" => 1,
	"alienvault-policy.xml" => 1,
	"alienvault-scada.xml" => 1,
	"alienvault-scan.xml" => 1,
	"alienvault-worms.xml" => 1,
	"abnormal.xml" => 1,
	"attacks.xml" => 1,
	"dos.xml" => 1,
	"generic.xml" => 1,
	"misc.xml" => 1,
	"network.xml" => 1,
	"scan.xml" => 1,
	"trojans.xml" => 1,
	"webattack.xml" => 1,
	"worms.xml" => 1
);

$categories = unserialize($_SESSION['categories']);
$tab = $xml->get_elements_by_tagname('directive');

$search_results = array();
foreach($tab as $lign) {
	$field = $lign->get_attribute('name');
	if (preg_match("/$query/i",$field)) {
		$search_results[$lign->get_attribute('id')] = $lign;
	}
}
?>
	<html>
		<head>
			<link rel="stylesheet" href="../style/style.css" />
			<link rel="stylesheet" href="style/directives.css" />
			<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
			<script type="text/javascript" language="javascript">

				<?php
if ($_GET["right"] != "") { ?>
				top.frames['main'].document.getElementById('rightframe').src = "<?php echo $_GET["right"] ?>";
				<?php
} ?>

				function restart() {
					$.ajax({
							type: "GET",
							url: "include/utils.php?query=restart",
							data: "",
							success: function(msg) {
								return msg;
							}
					});
				}

				function Menus(Objet,Image) {
					VarDIV = document.getElementById(Objet);
					
					if (VarDIV.style.display == 'none') {
							VarDIV.style.display = 'block';
							Image.src="viewer/img/flechebf.gif";
						} else {
							VarDIV.style.display = 'none';
							Image.src="viewer/img/flechedf.gif";
						}
					}
				
				function init()
				{
					var tab_span = document.getElementsByName("add_dir");
					for (i=0; i<tab_span.length; i++) {
						tab_span[i].style.right = "5%";
						tab_span[i].style.display = "block";
					}        
				}
function hide() {
	//From within a page loaded within a frame
	var f = parent.document.getElementById('frames');
	// to close the left frame
	f.setAttribute('cols', '20,*');
	document.getElementById('showtab').style.visibility = 'visible';
	document.getElementById('leftmenu').style.overflow = 'hidden';
}
function show() {
	//From within a page loaded within a frame
	var f = parent.document.getElementById('frames');
	// to close the left frame
	f.setAttribute('cols', '350,*');
	document.getElementById('showtab').style.visibility = 'hidden';
	document.getElementById('leftmenu').style.overflow = 'auto';
}
function background_delete(id,xml_file) {
	var url = './include/utils.php?query=delete_directive&id='+id+'&directive_xml='+xml_file;
	$.ajax({
		type: "GET",
		url: url,
		data: "",
		success: function(msg){
			document.location.reload();
		}
	});
}
function background_clone(id,xml_file,mini) {
	var url = './include/utils.php?query=copy_directive&id='+id+'&directive_xml='+xml_file+'&mini='+mini;
	$.ajax({
		type: "GET",
		url: url,
		data: "",
		success: function(msg){
			document.location.reload();
		}
	});
}
			</script>
			<style type='text/css'>
				.restart {font-size: 10px; margin-left: 20px;}
				.restart a {cursor: pointer;}
			</style>
		</head>

		<body id="leftmenu" style="overflow-x:hidden"<?php if (!$nohide) { ?> onload="hide()"<?php } ?>>
			<table width="100%" height="100%" cellpadding=0 cellspacing=0 style="border:0px;background-color:transparent">
			<tr>
			<td width="12" align="left" height="100" style="border-bottom:0px;border-left:4px solid #a2a2a2;visibility:hidden" id="showtab" valign="top"><a href="" onclick="show();return false;"><img src="../pixmaps/btn_minimize_right.gif" alt="" border="0"></img></a></td>
			<td style="border:0px;padding-left:1px" valign="top">
			<!-- <h1 align="center" style="margin-top:5px">Directive List</h1> -->
			<table width="100%" style="border:0px;background-color:transparent;">
				
						<tr>
							<th style="font-size:14px">
								<?php echo _("Current Categories");?>
								<span class='restart'>[<a onclick='restart()'><?php echo _("Restart Server") ?></a>]</span>
							</th>
						</tr>
						
						<tr>
							<td class="nobborder">
								<form method="post">
								<table class="transparent">
									<tr>
										<td class="nobborder"><?php echo _("Directive name") ?>:</td>
										<td class="nobborder"><input type="text" name="query" id="query" value="<?php echo $query ?>"></td>
										<td class="nobborder"><input type="submit" value="<?php echo _("Search") ?>"></td>
									</tr>
								</table>
								</form>
							</td>
						</tr>
						
						<?php if ($query != "") { ?>
						<tr>
							<td class="nobborder">
								<table class="transparent" width="100%">
									<tr><th><?php echo count($search_results)." "._("directives found") ?></th></tr>
								</table>
							</td>
						</tr>
						<?php } ?>
						
						<tr><td style="border:0px">
						<?php
							$total = 0;
							foreach($categories as $category) {
								$xmldata = xml_backdata($category->xml_file);
								$tab_this_category = array();
								foreach($tab as $lign) {
									/* Skip id ranges, just check xmldata for compare ids
									if ($lign->get_attribute('id') >= $category->mini && $lign->get_attribute('id') <= $category->maxi) {
										$tab_this_category[$lign->get_attribute('id') ] = $lign;
									}
									*/
									if ($query != "" && !isset($search_results[$lign->get_attribute('id')])) {
										continue;
									}
									if ($xmldata[$lign->get_attribute('id')]) {
										$tab_this_category[$lign->get_attribute('id') ] = $lign;
									}
								}
								if (count($tab_this_category) >= 0) {
									$total += count($tab_this_category);
									ksort($tab_this_category);
									$id_div = explode(".", $category->xml_file);
									$id_div = $id_div[0];
									$name_div = preg_replace("/\..*/", "", str_replace("-", " ", $category->name));
									$name_div = str_replace("style=\"\"","style='text-align:left'",$name_div);
									$url = "index.php?" . (($category->active) ? "disable=" . urlencode($category->name) : "enable=" . urlencode($category->name));
									$onlydir = "&onlydir=1"; //(count($tab_this_category) == 0) ? "&onlydir=1" : "";
							
						?>
							<table width="100%">
								<tr>
									<th style="padding-left:4px" <?php echo ($category->active) ? "" : "style='background:#eeeeee'" ?>>
									<table cellpadding='0' cellspacing='0' style="border:0px; background-color:transparent;" width="100%">
										<tr>
											<td style="border:0px" width="20">
												 <img id="img_<?php echo $id_div; ?>" 
												 align="left"
												 border="0"
												 <?php if ($query != "" && count($tab_this_category) > 0) { ?>
												 src="viewer/img/flechebf<?php if (!$category->active || count($tab_this_category) < 1) echo "_gray" ?>.gif"
												 <?php } else { ?>
												 src="viewer/img/flechedf<?php if (!$category->active || count($tab_this_category) < 1) echo "_gray" ?>.gif"
												 <?php } ?>
												 <?php if ($category->active && count($tab_this_category) > 0) { ?>
												 onclick="Menus('<?php echo $id_div; ?>',this)" 
												 <?php } ?> 
												 title="<?php
												echo gettext("Click here to view or hide this type of directives"); ?>"
												 alt="<?=_("Click here to view or hide this type of directives.")?>"
												 style="cursor:pointer"/>
											</td>
											<td style="text-align:left;border:0px;font-size:12px"><?php echo gettext(ucwords($name_div)); ?><?php if (count($tab_this_category) > 0) { ?> <font style="color:#666666;font-size:10px">[<?php echo count($tab_this_category) ?> <?php echo _("directive"); if (count($tab_this_category) > 1) echo "s"; ?>]</font><?php } ?></td>
											<td width="50" nowrap align="right">
											<?php if (!isset($cannotedit[$category->xml_file])) { ?>
											  <?php if ($category->active) { ?>
											  <a target="main" href="index.php?action=add_directive&xml_file=<?php echo $category->xml_file?>&id=<?php echo $category->id . $onlydir; ?>" title="<?php echo gettext("Add a directive in this category"); ?>"><img src="../pixmaps/plus-small.png" border="0" alt="<?php echo gettext("Add a directive in this category"); ?>" title="<?php echo gettext("Add a directive in this category"); ?>"></img></a>
											  <a target="main" href="editxml.php?xml_file=<?php echo $category->xml_file?>" title="<?php echo gettext("Edit XML directive file"); ?>"><img src="../pixmaps/theme/any.png" border="0" alt="<?php echo gettext("Edit XML directive file"); ?>" title="<?php echo gettext("Edit XML directive file"); ?>"/></a>
											  <?php } else { ?>
											  <img src="../pixmaps/plus-small-gray.png" border="0" style="opacity:.30;filter:Alpha(Opacity=30);"/></a>
											  <img src="../pixmaps/theme/any.png" border="0" style="opacity:.30;filter:Alpha(Opacity=30);"/></a>
											  <?php } ?>
											<?php } ?>
											</td>
											<td width="20" align="right">
											  <?php if ($category->active) { ?>
											  <a href="left.php?action=disable_category&xml_file=<?php echo $category->xml_file?>&name=<?php echo $category->name ?>" style="margin-left:20px;" title="<?php echo gettext("Disable this category"); ?>"><img src="../pixmaps/tick.png" border="0" alt="<?php echo gettext("Disable this category"); ?>" title="<?php echo gettext("Disable this category"); ?>"/></a>
											  <?php } else { ?>
											  <a href="left.php?action=enable_category&xml_file=<?php echo $category->xml_file?>&name=<?php echo $category->name ?>" style="margin-left:20px; " title="<?php echo gettext("Enable this category"); ?>"><img src="../pixmaps/cross-small.png" border="0" alt="<?php echo gettext("Enable this category"); ?>" title="<?php echo gettext("Enable this category"); ?>"/></a>
											  <?php } ?>
											</td>
										</tr>
									</table>
									</th>
								</tr>
							</table>
					<div id="<?php echo $id_div; ?>" <?php if ($query == "" || count($tab_this_category) < 1) { ?>style="display:none"<?php } ?>>
						<table width="100%">
							<tr>
								<td class="nobborder"></td>
								<th><?=_("Id")?></th>
								<th><?=_("Name")?></th>
							</tr>
							<?php
							$i = 0;
							foreach($tab_this_category as $directive)
							{
								$color = ($i%2 == 0) ? "#F2F2F2" : "#FFFFFF";
								$dir_id = $directive->get_attribute('id');
							?>				
								<tr>
									<td style="text-align: center;background-color:<?php echo $color?>;border:0px" width="40px" nowrap>
										<a onclick="javascript:if (confirm('<?php echo gettext("Are you sure you want to delete this directive ?"); ?>')) { background_delete(<?php echo $directive->get_attribute('id'); ?>,'<?=$category->xml_file?>'); }" style="marging-left:20px; cursor:pointer" title="<?php echo gettext("Delete this directive"); ?>"><img src="../pixmaps/delete.gif" alt="<?php echo _("Delete")?>" title="<?php echo _("Delete")?>" border="0"></img></a>
										<a onclick="javascript:if (confirm('<?php echo gettext("Are you sure you want to clone this directive ?"); ?>')) { background_clone(<?php echo $directive->get_attribute('id'); ?>,'<?=$category->xml_file?>','<?=$category->mini?>'); }" style="marging-left:20px; cursor:pointer" title="<?php echo gettext("Clone this directive"); ?>"><img src="../pixmaps/copy.png" alt="<?php echo _("Clone")?>" title="<?php echo _("Clone")?>" border="0"></img></a>
									</td>
									<td style="text-align: left;background-color:<?php echo $color?>;border:0px">
									<?php echo $dir_id; ?>
									</td>
									<td style="text-align: left;background-color:<?php echo $color?>;border:0px" width="100%">
										<a target="main" href="index.php?level=1&amp;directive=<?php
											echo $dir_id; ?>&amp;directive_xml=<?=$category->xml_file?>&category_mini=<?=$category->mini?>" title="<?php
											echo gettext("Edit this directive"); ?>"><?php
											echo $directive->get_attribute('name'); ?>
										</a>
									</td>
								</tr>
								<?php
								$i++; 
							} 
							?>
						</table>
					</div>
		<?php
    }
} ?>
					</td>
				</tr>
			</table>
		</td>
		<td valign="top" style="border-bottom:0px;border-right:4px solid #a2a2a2" width="12" align="right">
			<a href="" onclick="hide();return false;"><img src="../pixmaps/btn_minimize_left.gif" alt="" border="0"></img></a>
		</td>
	</tr></table>
    </body>
	</html>