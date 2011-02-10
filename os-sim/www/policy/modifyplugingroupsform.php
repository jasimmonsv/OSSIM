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
require_once 'ossim_db.inc';
require_once 'classes/Plugin.inc';
require_once 'classes/Plugingroup.inc';
session_start();
//Session::logcheck("MenuPolicy", "PolicyPluginGroups");
Session::logcheck("MenuConfiguration", "PluginGroups");
$db = new ossim_db();
$conn = $db->connect();

$plugin_list = Plugin::get_list($conn, "ORDER BY name");
// Make list for sayt
$sayt_list = "[";
$plist = "var plist = Array(1000);\n";
$i = 0;
$plugins = array();
foreach ($plugin_list as $p) {
	if ($i) $sayt_list .= ",";
	$sayt_list .= "{ txt:\"".$p->get_name()."\", id: \"".$p->get_id()."\" }";
	$plist .= "plist['".$p->get_name()."'] = '".$p->get_id()."';\n";
	$plugins[$p->get_id()] = array($p->get_name(),$p->get_description());
	$i++;
}
$sayt_list .= "]";
$nump = intval(GET('nump'));
if ($nump == 0) $nump = 500;
if (GET('action') == 'edit') {
    $group_id = GET('id');
    $delete_id = GET('delete');
    ossim_valid($group_id, OSS_DIGIT, 'illegal:ID');
    ossim_valid($delete_id, OSS_DIGIT, OSS_NULLABLE, 'illegal:delete');
    if (ossim_error()) {
        die(ossim_error());
    }
    if ($delete_id!="") Plugingroup::delete_plugin_id($conn, $group_id, $delete_id);
    $where = "plugin_group_descr.group_id=$group_id";
    $list = Plugingroup::get_list($conn, $where);
    if (count($list) != 1) {
        die(_("Empty DS Group ID"));
    }
    $plug_ed = $list[0];
    $name = $plug_ed->get_name();
    $descr = $plug_ed->get_description();
    $plugs = $plug_ed->get_plugins();
} else {
    $group_id = $name = $descr = null;
    ossim_valid(GET('pname') , OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_NULLABLE, 'illegal:Name');
    ossim_valid(GET('pdesc') , OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_NULLABLE, 'illegal:Name');
    if (ossim_error()) {
        die(ossim_error());
    }
    $name = GET('pname');
    $descr = GET('pdesc');
    $plugs = array();
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
  <link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
  <link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css">
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>
  <script type="text/javascript" src="../js/jquery.simpletip.js"></script>
  <script type="text/javascript" src="../js/urlencode.js"></script>
  <script type="text/javascript" src="../js/greybox.js"></script>
  <style type="text/css">
  .bwhite { background: #FFFFFF !important; }
  .bgrey { background: #F2F2F2 !important; }
  .bgreen { background: #28BC04 !important; }
  .fwhite { color:white !important;}
  .fblack { color:black !important;}
  </style>

</head>
<body style="height: auto; margin: 10px 0 10px 0">

<?php
if (GET('withoutmenu') != "1" && $_SESSION["menu_sopc"]=="Plugin Groups") include ("../hmenu.php"); ?>

<script>

var field_id = null;
function changefield(txt) {  if (field_id!=null) $("#"+field_id).val(txt); }
function getfield(txt) {  if (field_id!=null) return $("#"+field_id).val(); }

function validate_sids_str(id)
{
	var sids_str = $("#sid"+id).val();
	$.ajax({
		type: "GET",
		url: "modifyplugingroups.php?interface=ajax&method=validate_sids_str&sids_str="+sids_str+"&pid="+id,
		data: "",
		success: function(msg) {
			if (msg) {
				$("#errorsid"+id).show();
				$("#errorsid"+id).html(msg+'<br/>');
			} else {
				$("#errorsid"+id).hide();
			}
		}
	});
    return false;
}

function chk() {
	while (jQuery.trim($('#pname').val()) == "") {
		$('#pname').val(prompt("<?=_("Please enter a DS Group name:")?>"," "));
	}
	if ($('#pluginid').val()=="0") {
		var autofillp = plist[$('#filter').val()];
		if (typeof autofillp == 'undefined') {
			alert("<?=_("You must select a DS Group")?>");
			$('#filter').focus();
			return false;
		} else {
			$('#pluginid').val(autofillp);
			return true;
		}
	}
	return true;
}

function GB_onclose() {
}

</script>
<form id="myform" name="myform" action="modifyplugingroups.php?action=<?= GET('action') ?>&id=<?= $group_id ?>&withoutmenu=<?= GET('withoutmenu') ?>" method="POST" style="margin:0px" onsubmit="return chk()">
<table align="center" width="90%" cellspacing="0" class="noborder">
    <tr>
        <td width="10%" height="34" class="plfieldhdr pall" nowrap><?= _("Group ID") ?></td>
        <td width="22%" height="34" class="plfieldhdr ptop pbottom pright"><?= _("Group Name") . required() ?></td>
        <td width="63%" height="34" class="plfieldhdr ptop pbottom pright"><?= _("Description") . required() ?></td>
    </tr>
    <tr>
        <td class="noborder pleft"><b><?= $group_id ?></b>&nbsp;</td>
        <td class="noborder">
            <input type="text" name="name" id="pname" value="<?= $name ?>" size="30">
        </td>
        <td class="noborder pright">
          <textarea name="descr" rows="2" id="pdesc" cols="70" wrap="on"><?= $descr ?></textarea>
          <!-- <input type="submit" value="<?= _("Accept") ?>" class="button" style="font-size:12px;vertical-align:top"> -->
        </td>
    </tr>
    <tr>
        <td width="10%" height="34" class="plfieldhdr pall" nowrap><?= _("Data Source") ?></td>
        <td width="22%" height="34" class="plfieldhdr ptop pbottom pright"><?= _("Data Source Name") ?></td>
        <td width="63%" height="34" class="plfieldhdr ptop pbottom pright"><?= _("Data Source Description / Event types") ?></td>
    </tr>
    <tr>
        <td class="pleft"></td>
        <td nowrap>
            <input type="hidden" id="pluginid" name="pluginid" value="0">
            <input type="text" id="filter" name="filter" size="18" value="">&nbsp;<a href="allplugins.php" class="greyboxp" title="<?=_("Explore all data sources")?>"><img src="../pixmaps/plus.png" align="absmiddle" border="0"></a>&nbsp;<input type="submit" value="<?=_("Add Data Source")?>" class="lbutton"><a href="javascript:;" class="scriptinfo" txt="<?=_("Type the name of the data source or double-click to show the data source list")?>"><img src="../pixmaps/help_icon_gray.png" align="absmiddle" border="0"></a>
        </td>
        <td class="pright"></td>
    </tr>
    <?php
    $color = 0;
    foreach ($plugs as $id => $pdata) {
        $sids = $pdata['sids'];
        if ($sids == "0") $sids = "ANY";
        $bgclass = ($color++ % 2 != 0) ? "blank" : "lightgray";
        $bbottom = ($pdata==end($plugs)) ? "pbottom" : "";
    ?>
    <tr class="<?=$bgclass?>" txt="sid<?=$id?>">    
        <td class="noborder pleft <?=$bbottom?>" nowrap>
        	<table class="noborder" style="background:transparent"><tr><td class="nobborder">
        	<? if (count($plugs)>1) { ?><a href="modifyplugingroupsform.php?action=<?= GET('action') ?>&id=<?= $group_id ?>&withoutmenu=<?= GET('withoutmenu') ?>&delete=<?= $id ?>" title="<?=_("Delete data source from group")?>"><img src="../vulnmeter/images/delete.gif" align="absmiddle" border="0"></a>
        	<? } else { ?>
        	<a href="javascript:;" title="<?=_("Add another data source defore delete this")?>"><img src="../vulnmeter/images/delete.gif" align="absmiddle" class="disabled" border="0"></a>
        	<? } ?>
        	</td>
        	<td class="nobborder"><?= $id ?></td></tr></table>
        </td>	
        <td class="noborder pleft pright <?=$bbottom?>"><?= $plugins[$id][0] ?></td>
        <td class="noborder pright <?=$bbottom?>">
            <table class="noborder" style="background:transparent" cellpadding=0 cellspacing=0 width="100%"><tr>
            <td class="nobborder" style="padding-right:10px">&nbsp;<?= $plugins[$id][1] ?></td>
            <td class="nobborder right" style="padding-right:10px" NOWRAP>
                <span id="errorsid<?= $id ?>" style="background: red; display: none"></span>
                <span id="editsid<?= $id ?>" NOWRAP>
                    <b><?=_("Event types")?></b>:<a href="javascript:;" class="scriptinfo" txt="<?=_("All Event Types: ANY or 0<br>Event types separated by coma: 3,4,5<br>Event types range: 30-40<br>Individual selection clicking on magnifying glass icon")?>"><img src="../pixmaps/help_icon_gray.png" align="absmiddle" border="0"></a>&nbsp;
                    <input id="sid<?=$id?>" onBlur="javascript:return validate_sids_str('<?= $id ?>')"
                    type="text" name="sids[<?=$id?>]" value="<?=$sids?>" size="45" style="height:18px"> 
                    <a href="pluginsids.php?id=<?= $id ?>" name="sid<?= $id ?>" class="greybox" title="<?=_("Add/Edit event types selection")?>"><img src="../pixmaps/magadd.png" border=0 align="absmiddle"></a>&nbsp;<a href="allpluginsids.php?id=<?=$id?>" txt="sid<?= $id ?>" class="greyboxe" title="<?=_("Explore selected event types")?>"><img src="../pixmaps/magfit.png" align="absmiddle" border="0"></a>
                </span>
            </td>
            </tr></table>
        </td>
    </tr>
    <? } ?>
    <tr>
        <td class="pleft"></td>
        <td nowrap>
            <input type="text" id="sidsearch" name="sidsearch" size="19" value="">&nbsp;&nbsp;<input type="button" value="<?=_("Event Types Search")?>" class="lbutton" onclick="pluginsid_search()"><a href="javascript:;" class="scriptinfo" txt="<?=_("Search all event types matching this pattern")?>"><img src="../pixmaps/help_icon_gray.png" align="absmiddle" border="0"></a>
        </td>
        <td class="pright" style="text-align:left">
            <span id="loading" style="display:none"><img src="../pixmaps/theme/loading.gif" border="0"></span>
            <span id="addall" style="display:none"><input type="submit" onclick="$('#pluginid').val('')" value="<?=_("Add Selected")?>" class="lbutton"></span>
        </td>
    </tr>
    <tr id="trsearchresults" style="display:none"><td colspan="3" class="pleftright" id="searchresults"></td></tr>
    </table>
</td>
</tr>
</table>
<br>
<center><input type="submit" value="<?= _("Accept") ?>" onclick="$('#pluginid').val('')" class="button" style="font-size:12px"></center>
</form>
<script>
<?=$plist?>

function pluginsid_search() {
	var q = $("#sidsearch").val();
    if (q != "" && q.length>3) {
        $("#loading").toggle();
        $("#addall").hide();
        $("#trsearchresults").hide();
        $.ajax({
            type: "GET",
            url: "pluginsidsearch.php",
            data: { q: q },
            success: function(msg) {
                $("#searchresults").html(msg);
                $("#trsearchresults").show();
                $("#loading").toggle();
                $("#addall").show();
            }
        });
    } else {
    	alert("<?=_("At least 4 chars")?>")
    }
}

function chkall() {
    if ($('#selunsel').attr('checked'))
        $('input[type=checkbox]').attr('checked',true)
    else
        $('input[type=checkbox]').attr('checked',false)
}

$(document).ready(function(){
    GB_TYPE = 'w';
    $("a.greybox").click(function(){
        //var t = this.title || $(this).text() || this.href;
        field_id = $(this).attr('name');
        sids = $('#'+field_id).val();
        GB_show("Signature IDs",this.href+"&field="+urlencode(sids),410,"90%");
        return false;
    });
    $("a.greyboxe").click(function(){
        field_id = $(this).attr('txt');
        sids = $('#'+field_id).val();
        GB_show("<?=_("Explore selected event types")?>",this.href+"&sids="+urlencode(sids),410,"90%");
        return false;
    });
    $("a.greyboxp").click(function(){
        GB_show("<?=_("Explore all data sources")?>",this.href,410,"90%");
        return false;
    });
    $(".scriptinfo").simpletip({
        position: 'right',
        offset: [0, -10],
        baseClass: 'stooltip',
        onBeforeShow: function() { 
            this.update(this.getParent().attr('txt'));
        }
    });
    var sayts = <?=$sayt_list?>;
    $("#filter").focus().autocomplete(sayts, {
        minChars: 0,
        width: 150,
        autoFill: true,
        mustMatch: true,
		max: 200,
		matchContains: true,
        formatItem: function(row, i, max) {
            return row.txt;
        }
    }).result(function(event, item) {
        if (typeof item != 'undefined') $("#pluginid").val(item.id);
    });
    $('.blank,.lightgray').disableTextSelect().dblclick(function(event) {
        field_id = $(this).attr('txt');
        sids = $('#'+field_id).val();
        id = field_id.substr(3);
        GB_show("Signature IDs","pluginsids.php?id="+id+"&field="+urlencode(sids),410,"90%");
        return false;
    });
    $('#sidsearch').bind('keypress', function(e){
    	if ((e.keyCode || e.which) == 13) {
    		pluginsid_search();
    		return false;
    	}
    });

});
</script>
</body>
</html>
