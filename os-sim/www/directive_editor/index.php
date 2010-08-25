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
require_once ('classes/Security.inc');
require_once ('include/category.php');
require_once ('include/groups.php');
require_once ("include/utils.php");
ossim_valid($_GET["enable"], OSS_LETTER, OSS_DIGIT, OSS_SCORE, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("enable"));
ossim_valid($_GET["disable"], OSS_LETTER, OSS_DIGIT, OSS_SCORE, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("disable"));
if (ossim_error()) {
    die(ossim_error());
}
if ($_GET["enable"] != "") enable_category($_GET["enable"]);
if ($_GET["disable"] != "") disable_category($_GET["disable"]);
init_groups();
init_categories();
$pattern = "/firefox/i";
$test = preg_match($pattern, $_SERVER['HTTP_USER_AGENT']);
if ($test == 0) $cols = "262,100%";
else $cols = "280,100%";
?>

<html>
  </head>
	<frameset rows="35,*" frameborder=0 framespacing=0>
	<frame src="top.php?<?php echo $_SERVER['QUERY_STRING'] ?>">
		<frameset id="frames" cols="<?php echo $cols ?>" frameborder="yes" border=2 framespacing=0>
			<?php
if ($_GET['directive'] != '') {
    $action = $_GET['action'];
    if ($action == 'add_rule') {
        $id = $_GET['id'];
        $right = "include/utils.php?query=add_rule&id=$id";
        $scroll = "no";
    }
    if ($action == 'copy_directive') {
        $id = $_GET['id'];
        $right = "right.php?directive=$id&level=1&action=edit_dir&id=$id";
        $scroll = "no";
    } else {
        $directive = $_GET['directive'];
        $level = $_GET['level'];
        $variables = '?directive=' . $directive;
        $variables.= '&level=' . $level;
        $right = "viewer/index.php" . $variables;
        $scroll = "no";
    }
} else {
    $right = "viewer/index.php";
    $scroll = "auto";
}
?>
			<frame src="left.php?right=<?php echo urlencode($right) ?>" name="left" id="leftframe">
			<frame src="" name="right" scrolling="<?php echo $scroll ?>" id="rightframe">
		</frameset>
	</frameset>
</html>
