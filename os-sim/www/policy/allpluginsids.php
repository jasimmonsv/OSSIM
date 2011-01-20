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
require_once 'classes/Plugin_sid.inc';
Session::logcheck("MenuConfiguration", "PluginGroups");

$id = (GET('id')!="")? GET('id'):POST('id');
$sids = GET('sids');

ossim_valid($id, OSS_DIGIT, 'illegal:' . _("ID"));
ossim_valid($sids, OSS_NULLABLE, OSS_DIGIT, ",-", "ANY", OSS_SPACE);
if (ossim_error()) {
    die(ossim_error());
}
$db = new ossim_db();
$conn = $db->connect();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title> <?php echo gettext("Plugin SIDs"); ?> </title>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
    <link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery.highlight-3.js"></script>
	<style type="text/css">
		.highlight { background-color: yellow }
	</style>
</head>
<body>
    <form method="GET" onsubmit="return false" style="dislay:none">
    <input type="hidden" name="id" value="<?=$id?>">
    <table align="center" width="90%" cellspacing="0" class="noborder" id="content">
    <tr>
        <td width="10%" height="34" class="plfieldhdr pall" nowrap><?= _("Event Type") ?></td>
        <td height="34" class="plfieldhdr ptop pbottom pright"><?= _("Event Type Name") ?>
        
        	<span style="float:right"><input type="text" name="stxt" size="20" id="stxt">&nbsp;<input type="button" class="lbutton" value="<?=_("Highlight")?>" onclick="if ($('#stxt').val()!='') $('#content').removeHighlight().highlight($('#stxt').val());"></span>
        </td>
    </tr>
    <?
    if ($sids=="ANY" || $sids=="0") {
    	$plugin_list = Plugin_sid::get_list($conn, "WHERE plugin_id=$id");    
    } else {
		$sids = explode(",",$sids);
		$range = "";
		$sin = array();
		foreach ($sids as $sid) {
			if (preg_match("/(\d+)-(\d+)/",$sid,$found)) {
				$range .= " OR (sid BETWEEN ".$found[1]." AND ".$found[2].")"; 
			} else { 
				$sin[] = $sid;
			}
		}
		if (count($sin)>0) $where = "sid in (".implode(",",$sin).") $range";
		else $where = preg_replace("/^ OR /","",$range);
	    $plugin_list = Plugin_sid::get_list($conn, "WHERE plugin_id=$id AND ($where)");
	}
    foreach($plugin_list as $plugin) {
        ?>
	    <tr>
	        <td class="noborder pleft pbottom" style="padding:3px 0px"><b><?= $plugin->get_sid() ?></b>&nbsp;</td>
	        <td class="noborder pleft pbottom pright">
	            <?=$plugin->get_name()?>
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
