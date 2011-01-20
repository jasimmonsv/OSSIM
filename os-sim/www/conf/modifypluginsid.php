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
Session::logcheck("MenuConfiguration", "ConfigurationPlugins");
require_once 'classes/Security.inc';
require_once ('classes/Plugin_sid.inc');
$plugin_id = GET('id');
ossim_valid($plugin_id, OSS_DIGIT, 'illegal:' . _("plugin id"));
$sid = GET('sid');
ossim_valid($sid, OSS_DIGIT, 'illegal:' . _("sid"));
if (ossim_error()) {
    die(ossim_error());
}
require_once 'ossim_db.inc';
$db = new ossim_db();
$conn = $db->connect();
$plugin_sid = Plugin_sid::get_list($conn, "WHERE plugin_id = $plugin_id AND sid = $sid");
// Category
require_once 'classes/Category.inc';
$list_categories=Category::get_list($conn);
?>
<html>
<head>
  <title> <?php echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript">
  function load_subcategory (category_id) {
		$("#ajaxSubCategory").html("<img src='../pixmaps/loading.gif' width='20' alt='Loading'>Loading");
		$.ajax({
			type: "GET",
			url: "modifypluginsid_ajax.php",
			data: { category_id:category_id },
			success: function(msg) {
				$("#ajaxSubCategory").html(msg);
			}
		});
	}
  </script>
</head>
<body>
                                                                                
<?php
if (GET('withoutmenu') != "1") include ("../hmenu.php"); ?>

<form method="post" action="pluginupdate.php">
<input type="hidden" name="id" value="<?php echo $plugin_id ?>">
<input type="hidden" name="sid" value="<?php echo $sid ?>">
<table align="center">
  <tr>
    <th> <?php echo gettext("Name"); ?> </th>
    <td class="left"><input type="text" name="name" size="70" value="<?php echo $plugin_sid[0]->get_name() ?>"></td>
  </tr>
  <tr>
    <th> <?php echo gettext("Category"); ?> </th>
    <td class="left">
        <select name="category" onchange="load_subcategory(this.value);">
			<option value='NULL'<?php if($plugin_sid[0]->get_category_id()==''){ echo ' SELECTED'; } ?>>&nbsp;</option>
		<?php foreach ($list_categories as $category) { ?>
			<option value='<?php echo $category->get_id(); ?>'<?php if($plugin_sid[0]->get_category_id()==$category->get_id()){ echo ' SELECTED'; } ?>><?php echo  str_replace('_', ' ', $category->get_name()); ?></option>
		<?php } ?>
        </select>
    </td>
  </tr>
  <tr>
    <th> <?php echo gettext("Subcategory"); ?> </th>
    <td class="left">
	<div id="ajaxSubCategory">
		<select name="subCategory">
		<?php if($plugin_sid[0]->get_subcategory_id()==''){ ?>
			<option value='NULL' SELECTED>&nbsp;</option>
		<?php
		}else{
		// Subcategory
		require_once 'classes/Subcategory.inc';

		$list_subcategories=Subcategory::get_list($conn,'WHERE cat_id='.$plugin_sid[0]->get_category_id().' ORDER BY name');
		foreach ($list_subcategories as $subcategory) {
		?>
			<option value='<?php echo $subcategory->get_id(); ?>'<?php if($plugin_sid[0]->get_subcategory_id()==$subcategory->get_id()){ echo ' SELECTED'; } ?>><?php echo  str_replace('_', ' ', $subcategory->get_name()); ?></option>
		<?php
			}
		}
		?>
		</select>
	</div>
    </td>
  </tr>
  <tr>
    <th> <?php echo gettext("Priority"); ?> </th>
    <td class="left">
        <select name="priority" style="width:50px">
        <option value='0'<?= ($plugin_sid[0]->get_priority() == 0) ? " SELECTED " : "" ?>>0</option>
        <option value='1'<?= ($plugin_sid[0]->get_priority() == 1) ? " SELECTED " : "" ?>>1</option>
        <option value='2'<?= ($plugin_sid[0]->get_priority() == 2) ? " SELECTED " : "" ?>>2</option>
        <option value='3'<?= ($plugin_sid[0]->get_priority() == 3) ? " SELECTED " : "" ?>>3</option>
        <option value='4'<?= ($plugin_sid[0]->get_priority() == 4) ? " SELECTED " : "" ?>>4</option>
        <option value='5'<?= ($plugin_sid[0]->get_priority() == 5) ? " SELECTED " : "" ?>>5</option>
        </select>
    </td>
  </tr>
  <tr>
    <th> <?php echo gettext("Reliability"); ?> </th>
    <td class="left">
        <select name="reliability" style="width:50px">
        <option value='0'<?= ($plugin_sid[0]->get_reliability() == 0) ? " SELECTED " : "" ?>>0</option>
        <option value='1'<?= ($plugin_sid[0]->get_reliability() == 1) ? " SELECTED " : "" ?>>1</option>
        <option value='2'<?= ($plugin_sid[0]->get_reliability() == 2) ? " SELECTED " : "" ?>>2</option>
        <option value='3'<?= ($plugin_sid[0]->get_reliability() == 3) ? " SELECTED " : "" ?>>3</option>
        <option value='4'<?= ($plugin_sid[0]->get_reliability() == 4) ? " SELECTED " : "" ?>>4</option>
        <option value='5'<?= ($plugin_sid[0]->get_reliability() == 5) ? " SELECTED " : "" ?>>5</option>
        <option value='6'<?= ($plugin_sid[0]->get_reliability() == 6) ? " SELECTED " : "" ?>>6</option>
        <option value='7'<?= ($plugin_sid[0]->get_reliability() == 7) ? " SELECTED " : "" ?>>7</option>
        <option value='8'<?= ($plugin_sid[0]->get_reliability() == 8) ? " SELECTED " : "" ?>>8</option>
        <option value='9'<?= ($plugin_sid[0]->get_reliability() == 9) ? " SELECTED " : "" ?>>9</option>
        <option value='10'<?= ($plugin_sid[0]->get_reliability() == 10) ? " SELECTED " : "" ?>>10</option>
        </select>
    </td>
  </tr>
  <tr>
    <td colspan="2" align="center" class="noborder">
      <input type="submit" value="<?php echo _('OK')?>" class="button">
      <input type="reset" value="<?php echo _('reset')?>" class="button">
    </td>
  </tr>
</table>
</form>

</body>
</html>
<?php $db->close($conn); ?>