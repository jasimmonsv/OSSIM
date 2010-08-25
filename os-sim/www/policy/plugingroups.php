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
require_once 'classes/Security.inc';
require_once 'classes/Session.inc';
require_once 'classes/Plugingroup.inc';
require_once 'ossim_db.inc';
//Session::logcheck("MenuPolicy", "PolicyPluginGroups");
Session::logcheck("MenuConfiguration", "PluginGroups");
$db = new ossim_db();
$conn = $db->connect();
$plgid = intval(GET('id'));
$groups = Plugingroup::get_list($conn);

foreach ($_SESSION as $key=>$val) {
	if (preg_match("/^pid/",$key)) unset($_SESSION[$key]);
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
</head>
<body style="height: auto; margin: 0 0 10px 0">

<?php
if (GET('withoutmenu') != "1" && GET('collection') != "1" && ($_SESSION["menu_sopc"]=="Plugin Groups" || $_SESSION["menu_sopc"]=="Plugins")) include ("../hmenu.php");
?>

<script>
$(document).ready(function(){
    $('.blank,.lightgray').disableTextSelect().dblclick(function(event) {
        id = $(this).attr('txt');
        document.location.href='modifyplugingroupsform.php?action=edit&id='+id
        return false;
    }).click(function(event) {
        toggle_info($(this).attr('txt'));
        return false;
    });
});
function toggle_info(id) {
    $('#plugins'+id).toggle();
    var img = '#img'+id;
    if ($(img).attr('src').match(/minus/)) {
        $(img).attr('src','../pixmaps/plus-small.png');
    } else {
        $(img).attr('src','../pixmaps/minus-small.png');
    }
}
    
</script>
<center>
<table width="85%" class="noborder" style="background:transparent;" cellspacing="0" cellpadding="0">
    <tr><td style="text-align:right;" class="nobborder">
    <form><input type="button" class="button" onclick="document.location.href='modifyplugingroupsform.php?action=new'" value="<?=_("Insert new group")?>"></form>
    </td>
    </tr>
</table>
</center>
<br>
<table width="85%" align="center" class="noborder" cellspacing="0" cellpadding="0">
    <tr>
        <td height="34" class="plfieldhdr pall"><?php echo _("ID") ?></td>
        <td height="34" class="plfieldhdr ptop pbottom pright"><?php echo _("Name") ?></td>
        <td height="34" class="plfieldhdr ptop pbottom pright"><?php echo _("Description") ?></td>
        <td height="34" class="plfieldhdr ptop pbottom pright"><?php echo _("Actions") ?></td>
    </tr>
    <?php
$i = 0;
foreach($groups as $group) {
    $id = $group->get_id();
    $color = ($i%2==0) ? "lightgray" : "blank";
    if ($id == $plgid) $color = "lightyellow";
?>
        <tr class="<?=$color?>" txt="<?=$id?>">
            <td width="70" class="pleft"><a name="<?=$id?>"></a>
                <img id="img<?php echo $id?>" src="../pixmaps/plus-small.png" align="absmiddle" border="none"> <b><?php echo $id?></b>
            </td>
            <td style="padding-left:4px;padding-right:4px" width="200"><b><?php echo htm($group->get_name()) ?></b></td>
            <td style="text-align:left;padding-left:5px"><?php echo htm($group->get_description()) ?></td>
            <td width="130" class="pright" style="padding:2px">
            <input type="button" class="button" onclick="document.location.href='modifyplugingroupsform.php?action=edit&id=<?php echo $id?>'" value=<?php echo _("Edit") ?>>&nbsp;
            <input type="button" class="button" onclick="document.location.href='modifyplugingroups.php?action=delete&id=<?php echo $id?>'" value=<?php echo _("Delete") ?>>
            </td>
        </tr>
        <tr id="plugins<?php echo $id ?>" style="display:none;background:#FFFFFF;padding-left:4px">
            <td class="noborder pbottom" bgcolor="white" style="padding-bottom:3px" height="100%">
                <table cellspacing="0" cellpadding="0" bgcolor="white" class="noborder" height="100%">
                    <tr bgcolor="white"><td class="nobborder" height="29"><img src="../pixmaps/bktop.gif" border="0"></td></tr>
                    <tr bgcolor="white"><td class="nobborder" style="background: url(&quot;../pixmaps/bkbg.gif&quot;) repeat-y scroll 0% 0% transparent;">&nbsp;</td></tr>
                    <tr bgcolor="white"><td class="nobborder" height="51"><img src="../pixmaps/bkcenter.gif" border="0"></td></tr>
                    <tr bgcolor="white"><td class="nobborder" style="background: url(&quot;../pixmaps/bkbg.gif&quot;) repeat-y scroll 0% 0% transparent;">&nbsp;</td></tr>
                    <tr bgcolor="white"><td class="nobborder" height="29"><img src="../pixmaps/bkdown.gif" border="0"></td></tr>
                </table>
            </td>
            <td class="nobborder pbottom" colspan="3" style="text-align: left;padding-bottom:3px" NOWRAP valign="top">
                <table width="100%" height="100%" align="left" style="border-width:0px;background:#FFFFFF" cellspacing="0" cellpadding="0">
                <tr>
                <td height="17" class="plfieldhdr pleft pbottom pright" style="padding:0px 5px 0px 5px"><?=_("ID")?></td>
                <td height="17" class="plfieldhdr pright pbottom" style="padding:0px 5px 0px 5px"><?=_("Name")?></td>
                <td height="17" class="plfieldhdr pright pbottom" style="padding:0px 5px 0px 5px"><?=_("Description")?></td>
                <td height="17" class="plfieldhdr pright pbottom" style="padding:0px 5px 0px 5px"><?=_("SIDs:")?></td>
                </tr>
                <?php
    foreach($group->get_plugins() as $p) { ?>
                    <tr>
                        <td class="pleft pbottom pright"><?php echo $p['id'] ?></td>
                        <td class="pbottom pright"><?php echo $p['name'] ?></td>
                        <td class="pbottom pright" nowrap><?php echo $p['descr'] ?></td>
                        <td class="pbottom pright" style="text-align:left;white-space:normal;padding:0px 0px 0px 5px;"><?php echo ($p['sids'] == "0") ? "ANY" : str_replace(",",", ",$p['sids']) ?></td>
                    </tr>
                <?php
    } ?>        
                </table>
            </td>
        </tr>
    <?php $i++;
} ?>
</table>
<?php
if ($plgid != "") { ?>
<script>toggle_info('<?= $plgid ?>');document.location.href='#<?= $plgid ?>'</script>
<?php
} ?>