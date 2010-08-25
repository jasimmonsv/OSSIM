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
if (!isset($uc_languages)) {
    ossim_set_lang();
    $uc_languages = array(
        "de_DE.UTF-8",
        "de_DE.UTF8",
        "de_DE",
        "en_GB",
        "es_ES",
        "fr_FR",
        "pt_BR"
    );
}
$menu_opc = GET('hmenu');
$menu_sopc = GET('smenu');
ossim_valid($menu_opc, OSS_ALPHA, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("Option"));
ossim_valid($menu_sopc, OSS_ALPHA, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("SubOption"));
if (ossim_error()) die(ossim_error());
if ($menu_opc != "") $_SESSION["menu_opc"] = $menu_opc;
if ($menu_sopc != "") $_SESSION["menu_sopc"] = $menu_sopc;
$menu_opc = $_SESSION["menu_opc"];
$menu_sopc = $_SESSION["menu_sopc"];
$menu = array();
$hmenu = array();
// only with a valid value
if ($menu_opc != "" && $menu_sopc != "") {
    $ntop_link = $conf->get_conf("ntop_link", FALSE);
    $sensor_ntop = parse_url($ntop_link);
    $ocs_link = $conf->get_conf("ocs_link", FALSE);
    $glpi_link = $conf->get_conf("glpi_link", FALSE);
    $ovcp_link = $conf->get_conf("ovcp_link", FALSE);
    $nagios_link = $conf->get_conf("nagios_link", FALSE);
    $sensor_nagios = parse_url($nagios_link);
    if (!isset($sensor_nagios['host'])) {
        $sensor_nagios['host'] = $_SERVER['SERVER_NAME'];
    }
    include ("menu_options.php");
?>
<div style="position:absolute;left:0px;top:0px;width:100%;background:#8E8E8E">
	<table width="100%" class="noborder" border=0 cellpadding=0 cellspacing=0 style="background:transparent"><tr>
	<td style="width:15px;border-bottom:1px solid #8E8E8E;vertical-align:bottom">&nbsp;</td>
	<td class="nobborder" style="padding-top:7px">
		<table class="noborder" border=0 cellpadding=0 cellspacing=0 style="background:transparent"><tr>
	<?php
    if (!isset($language)) $language = "";
    if ($hmenu[$menu_opc]) {
    	//
    	// remove ghost tabs if not active
    	foreach($hmenu[$menu_opc] as $j => $op) if ($op["ghost"] && $op["id"] != $menu_sopc) unset($hmenu[$menu_opc][$j]);
    	//
        $tabs = count($hmenu[$menu_opc]) - 1;
        foreach($hmenu[$menu_opc] as $j => $op) if ($op["name"] != "") {
            $txtsopc = (in_array($language, $uc_languages)) ? htmlentities(strtoupper(html_entity_decode($op["name"]))) : $op["name"];
            if (preg_match("/^http/",$op['url']))
				$url = $op["url"] . (preg_match("/\?/", $op["url"]) ? "&" : "?") . "hmenu=" . urlencode($menu_opc) . "&smenu=" . urlencode($op["id"]);
			else
				$url = "../" . $op["url"] . (preg_match("/\?/", $op["url"]) ? "&" : "?") . "hmenu=" . urlencode($menu_opc) . "&smenu=" . urlencode($op["id"]);
            if ($op["id"] == $menu_sopc) {
                $help = $j;
?>
				<td style="vertical-align:bottom" class="nobborder">
					<table class="noborder" border=0 cellpadding=0 cellspacing=0 height="26"><tr>
					<td width="16" class="nobborder"><img src="../pixmaps/menu/tsl<?php echo ($j > 0) ? "2" : "" ?>.gif" border=0></td>
					<td class="nobborder" style="background:url(../pixmaps/menu/bgts.gif) repeat-x bottom left;padding:0px 15px 0px 15px" nowrap><a href="<?php echo $url ?>" <?php echo ($op["target"] != "") ? "target='" . $op["target"] . "'" : "" ?> class="gristabon"><?php echo $txtsopc ?></a></td>
					<td width="16" class="nobborder"><img src="../pixmaps/menu/tsr<?php echo ($j == $tabs) ? "2" : "" ?>.gif" border=0></td>
					<tr></table>
				</td>
		<?php
            } else {
?>
				<td style="vertical-align:bottom" class="nobborder">
					<table class="noborder" border=0 cellpadding=0 cellspacing=0 height="26"><tr>
					<?php
                if ($hmenu[$menu_opc][$j - 1]["id"] != $menu_sopc) { ?><td width="16" class="nobborder"><img src="../pixmaps/menu/tul<?php echo ($j == 0) ? "2" : "" ?>.gif" border=0></td><?php
                } ?>
					<td class="nobborder" height="26" style="background:url(../pixmaps/menu/bgtu.gif) repeat-x bottom left;padding:0px 10px 0px 10px" nowrap><a href="<?php echo $url
?>" <?php echo ($op["target"] != "") ? "target='" . $op["target"] . "'" : "" ?> class="gristab"><?php echo $txtsopc ?></a></td>
					<?php
                if ($j == $tabs) { ?><td width="16" class="nobborder"><img src="../pixmaps/menu/tur.gif" border=0></td><?php
                } ?>
					<tr></table>
				</td>
		<?php
            }
        }
    }
?>
		<td style="width:100%;border-bottom:1px solid #8E8E8E;vertical-align:bottom">&nbsp;</td>
		</tr></table>
	</td>
    
    <td style="vertical-align:bottom;text-align:right;border:0px none" nowrap>

        <table cellpadding=0 cellspacing=0 border=0 align="right" style="margin:0px;padding:0px;background-color:transparent;border:0px none">
        <tr>
        <? if (count($rmenu[$menu_sopc])>0) {
           foreach ($rmenu[$menu_sopc] as $i => $ropc) { ?>
            <td align="right" class="white" style="background-color:transparent;border:0px none" nowrap>
            <?=($i>0) ? "&nbsp;|&nbsp;" : "" ?><a class="white" href="<?=$ropc["url"]?>"<?=($ropc["target"]!="") ? "target='".$ropc["target"]."'" : "" ?>><?=$ropc["name"]?></a>
            </td>
        <?   }  
           } 
           if ($hmenu[$menu_opc][$help]["help"]!="") {
         ?>
           <td style="vertical-align:bottom;padding:0px;padding-left:15px" class="nobborder">
				<table class="noborder" border=0 cellpadding=0 cellspacing=0 height="26"><tr>
				<td width="16" class="nobborder"><img src="../pixmaps/menu/tsl.gif" border=0></td>
				<td class="nobborder" style="background:url(../pixmaps/menu/bgts.gif) repeat-x bottom left;padding-right:4px" nowrap>
					<a href="<?=$hmenu[$menu_opc][$help]["help"]?>"><img align="absmiddle" src="../pixmaps/help_icon.gif" border="0" title="<?=_("Help")?>"></b></a>
				</td>
				<!--<td width="16" class="nobborder"><img src="../pixmaps/menu/tsr<?php echo ($j == $tabs) ? "2" : "" ?>.gif" border=0></td>-->
				<tr></table>
		   </td>
        <?   }  ?>
        </tr>
        </table>

    </td>
	<!--<td style="width:5px;border-bottom:1px solid #8E8E8E;vertical-align:bottom">&nbsp;</td>-->
	</tr></table>
</div>
<table width="100%" class="noborder" style="background-color:transparent" border=0 cellpadding=0 cellspacing=0><tr><td height="36" class="nobborder">&nbsp;</td></tr></table>
<?php
} ?>
