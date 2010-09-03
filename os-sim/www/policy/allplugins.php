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
require_once 'classes/Plugin.inc';
Session::logcheck("MenuConfiguration", "PluginGroups");

$db = new ossim_db();
$conn = $db->connect();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title> <?php echo gettext("Plugins"); ?> </title>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
    <link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery.highlight-3.js"></script>
	<style type="text/css">
		.highlight { background-color: yellow }
	</style>
	<script>
		$(document).ready(function(){
		    $('.blank,.lightgray').disableTextSelect().click(function(event) {
		        field_id = $(this).attr('pid');
		        $("#pluginid",window.parent.document).val(field_id);
		        $("#myform",window.parent.document).submit();
		        return false;
		    });
		});
    </script>
</head>
<body>
    <form method="GET" onsubmit="return false" style="dislay:none">
    <table align="center" width="90%" cellspacing="0" class="noborder" id="content">
    <tr>
        <td width="10%" height="34" class="plfieldhdr pall" nowrap><?= _("Plugin ID") ?></td>
        <td height="34" class="plfieldhdr ptop pbottom pright"><?= _("Plugin Name") ?>
        <td height="34" class="plfieldhdr ptop pbottom pright"><?= _("Plugin Description") ?>        
        	<span style="float:right"></p><input type="text" name="stxt" size="20" id="stxt">&nbsp;<input type="button" class="lbutton" value="<?=_("Highlight")?>" onclick="if ($('#stxt').val()!='') $('#content').removeHighlight().highlight($('#stxt').val());"></span>
        </td>
    </tr>
    <?
	$plugin_list = Plugin::get_list($conn, "ORDER BY name");
	foreach ($plugin_list as $p) {
		$bgclass = ($color++ % 2 != 0) ? "blank" : "lightgray";
        ?>
	    <tr class="<?=$bgclass?>" pid="<?=$p->get_id()?>">
	        <td class="noborder pleft pbottom" style="padding:3px 0px"><b><?= $p->get_id() ?></b>&nbsp;</td>
	        <td class="noborder left pleft pbottom pright">
	            <?=$p->get_name()?>
	        </td>
	        <td class="noborder left pleft pbottom pright">
	            <?=$p->get_description()?>
	        </td>
	    </tr>
	    <?
	}
    ?>
    </table>
    </form>
</body>
</html>
<?
$db->close($conn);
?>
