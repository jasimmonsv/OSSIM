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

$conf = $GLOBALS["CONF"];
$XML_FILE = '/etc/ossim/server/directives.xml';
$xml = domxml_open_file($XML_FILE, DOMXML_LOAD_SUBSTITUTE_ENTITIES);
?>
	<html>
		<head>
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

			</script>
		</head>

		<body>
			<!-- <h1 align="center" style="margin-top:5px">Directive List</h1> -->
			
			<?php
$categories = unserialize($_SESSION['categories']);
?>
		<center>
			<a onclick="javascript:if (confirm('<?php
echo gettext('Are you sure you want to restart the OSSIM server ?'); ?>')) {restart();}"
				title="<?php
echo gettext('Click to restart the OSSIM server'); ?>"
				style="cursor:pointer"
			><?php
echo gettext('Restart server'); ?></a>
		</center>
		<br/>
		<center>
			<a href="include/utils.php?query=add_directive&id=<?php
echo $categories[0]->id; ?>" target="right" style="marging-left:20px;" TITLE="<?php
echo gettext("Click to add a directive"); ?>"
			><?php
echo gettext("Add directive"); ?></a>
		</center>
		<br/>
      <?php
foreach($categories as $category) {
	$xmldata = xml_backdata($category->xml_file);
	$tab = $xml->get_elements_by_tagname('directive');
    $tab_this_category = array();
    foreach($tab as $lign) {
		/* Skip id ranges, just check xmldata for compare ids
		if ($lign->get_attribute('id') >= $category->mini && $lign->get_attribute('id') <= $category->maxi) {
            $tab_this_category[$lign->get_attribute('id') ] = $lign;
        }
		*/
		if ($xmldata[$lign->get_attribute('id')]) {
			$tab_this_category[$lign->get_attribute('id') ] = $lign;
		}
    }
    if (count($tab_this_category) >= 0) {
        ksort($tab_this_category);
        $id_div = explode(".", $category->xml_file);
        $id_div = $id_div[0];
        $name_div = preg_replace("/\..*/", "", str_replace("-", " ", $category->name));
        $url = "index.php?" . (($category->active) ? "disable=" . urlencode($category->name) : "enable=" . urlencode($category->name));
        $onlydir = "&onlydir=1"; //(count($tab_this_category) == 0) ? "&onlydir=1" : "";
        
?>
      <table width="100%">
        <tr>
          <th style="padding-left:4px" <?php echo ($category->active) ? "" : "style='background:#eeeeee'" ?>>
            <img id="img_<?php echo $id_div; ?>" 
                 align="left"
                 border="0"
                 src="viewer/img/flechedf.gif"
                 onclick="Menus('<?php
        echo $id_div; ?>',this)" 
                 TITLE="<?php
        echo gettext("Click here to view or hide this type of directives"); ?>"
                 alt="<?=_("Click here to view or hide this type of directives.")?>"
                 style="cursor:pointer"/>
            <?php
        echo gettext(ucwords($name_div)); ?> <a href="<?php echo $url ?>" target="main"><img src="../pixmaps/tables/<?php echo ($category->active) ? "tick" : "cross" ?>-small.png" border=0 align="absmiddle"></a>
            <span id="add_dir" name="add_dir" style="position:absolute; right:5%;"><a href="include/utils.php?query=add_directive&id=<?php
        echo $category->id . $onlydir; ?>" target="right" style="marging-left:20px;" TITLE="<?php
        echo gettext("Add a directive in this category"); ?>">+</a></span>
         </th>
        </tr>
      </table>
      <div id="<?php
        echo $id_div; ?>" style="display:none">
		    <table width="100%">
			    <tr>
			      <th><?=_("Del")?></th>
                  <th><?=_("Id")?></th>
                  <th><?=_("Name")?></th>
          </tr>
      <?php
        foreach($tab_this_category as $directive) {
            $dir_id = $directive->get_attribute('id');
?>				
					<tr>
					  <td style="text-align: center" width="20px">
					    <a onclick="javascript:if (confirm('<?php
            echo gettext("Are you sure you want to delete this directive ?"); ?>')) { window.open('./include/utils.php?query=delete_directive&id=<?php
            echo $directive->get_attribute('id'); ?>&directive_xml=<?=$category->xml_file?>','right'); }" style="marging-left:20px; cursor:pointer" TITLE="<?php
            echo gettext("Delete this directive"); ?>">x</a>
            </td>
						<td style="text-align: left">
              <?php
            echo $dir_id; ?>
            </td>
						<td style="text-align: left" width="100%">
						  <a href="viewer/index.php?level=1&amp;directive=<?php
            echo $dir_id; ?>&amp;directive_xml=<?=$category->xml_file?>&category_mini=<?=$category->mini?>" target="right" TITLE="<?php
            echo gettext("Edit this directive"); ?>"><?php
            echo $directive->get_attribute('name'); ?></a>
						</td>
					</tr>
			<?php
        } ?>
		</table>
	</div>
		<?php
    }
} ?>
	<br><br>
    </body>
	</html>