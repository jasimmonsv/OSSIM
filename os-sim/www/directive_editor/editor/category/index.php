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
/* directories */
$conf = $GLOBALS["CONF"];
$base_dir = $conf->get_conf("base_dir");
$css_dir = '../../style';
$js_dir = '../javascript';
$js_dir_category = 'javascript';
/* connection to the OSSIM database */
require_once ('../../include/directive.php');
dbConnect();
/* get the category */
$category = get_category_by_id($_GET['id']);
if (!isset($category)) $category = new Category(NULL, NULL, NULL, NULL);
$_SESSION['category'] = serialize($category);
/* width */
$xml_file_width = '300px';
$mini_width = '100px';
$maxi_width = '100px';
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
	<head>
		<link type="text/css" rel="stylesheet"
			href="<?php
echo $css_dir . '/directives.css'; ?>" />

		<style>
			input.editable {width: <?php
echo $right_text_width; ?>}
			select.editable {width: <?php
echo $right_select_width; ?>}
		</style>

		<script type="text/javascript" language="javascript"
			src="<?php
echo $js_dir . '/editor.js'; ?>"></script>

		<script type="text/javascript" language="javascript"
			src="<?php
echo $js_dir . '/editableSelectBox.js'; ?>"></script>

		<script type="text/javascript" language="javascript"
			src="<?php
echo $js_dir_category . '/category.js'; ?>"></script>
	</head>

	<body>
  <div style="
      background-color:#17457c;
      width:100%;
      position:fixed;
      height:2px;
      left:0px;"></div><br>
	<!-- #################### main container #################### -->
	<form method="POST" action="../../include/utils.php?query=save_category">
	<table class="container" style="border-width: 0px" align="center">
	<tr>

	<!-- #################### left container #################### -->
	<td class="container" style="vertical-align: top">
	<table class="container">

	<tr><td class="container">
	<?php
include ("global.inc.php"); ?>
	</td></tr>

	<tr><td class="container">
		<input type="button" style="width: 100px"
			value="<?php
echo gettext('Cancel'); ?>"
			onclick="onClickCancel()"
		/>
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="button" style="width: 100px"
			id="save"
			value="<?php
echo gettext('Save'); ?>"
			onclick="submit()"
		/>
	</td></tr>

	</table>
	</td>
	<!-- #################### END: left container #################### -->

	</tr>
	</table>
	</form>

	<!-- #################### END: main container #################### -->

	</body>
</html>

<?php
dbClose();
?>
