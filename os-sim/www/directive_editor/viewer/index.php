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
require_once ("../include/utils.php");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<link type="text/css" rel="stylesheet" href="../style/directives.css"/>
<link rel="stylesheet" type="text/css" href="../../style/greybox.css"/>
<title> <?php
echo gettext("Directive Editor"); ?> </title>
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">

<style>
.nobborder {
	border:0px;
}
.stooltip {
	text-align:left;
	position: absolute;
	padding: 5px;
	z-index: 10;

	color: #303030;
	background-color: #f5f5b5;
	border: 1px solid #DECA7E;

	font-family: arial;
	font-size: 12px;
}

</style>
<script type="text/javascript" src="../../js/jquery-1.3.2.min.js"></script>
<script type="text/javascript" src="../../js/jquery.simpletip.js"></script>
<script type="text/javascript" src="../../js/greybox.js"></script>
<script language="JavaScript1.5" type="text/javascript">
<!--

function Menus(Objet)
{
VarUL=document.getElementById(Objet);
if(VarUL.className=="menuhide") {
VarUL.className="menushow";
} else {
VarUL.className="menuhide";
}
}
//-->
$(document).ready(function(){
	$(".scriptinfo").simpletip({
		position: 'bottom',
		onBeforeShow: function() { 
			var txt = this.getParent().attr('txt');
			this.update(txt);
		}
	});
	$("a.greybox").click(function(){
		var t = this.title || $(this).text() || this.href;
		GB_show(t,this.href,520,'90%');
		return false;
	});
	$("a.greybox_200").click(function(){
		var t = this.title || $(this).text() || this.href;
		GB_show(t,this.href,200,'90%');
		return false;
	});
});
</SCRIPT>
</head>
<body>
<?php
require_once ('classes/Plugin.inc');
require_once ('classes/Plugin_sid.inc');
require_once ('ossim_db.inc');
require_once ('classes/Security.inc');
require_once ('classes/Compliance.inc');
require_once ('classes/Util.inc');
require_once ('classes/Alarm.inc');
require_once ('classes/Repository.inc');

init_groups();
init_categories();

$directive_id = GET('directive');
$directive_xml = $_GET['directive_xml'];
if ($directive_xml == "" && $directive_id != "") $directive_xml = get_directive_real_file($directive_id);
$category_mini = $_GET['category_mini'];
$level = GET('level');
$action = GET('action');
ossim_valid($directive_id, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("directive_id"));
ossim_valid($directive_xml, OSS_ALPHA, OSS_DOT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("directive_xml"));
ossim_valid($level, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("level"));
ossim_valid($action, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("action"));
ossim_valid($category_mini, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("mini"));
if (ossim_error()) {
    die(ossim_error());
}
$db = new ossim_db();
$conn = $db->connect();
?>

<!-- <h1 align="center"> <?php
echo gettext("Directive Editor"); ?> </h1> -->
<?php
if ($directive_id == ''){
    $XML_FILE = '/etc/ossim/server/directives.xml';
    //unset($_SESSION['XML_FILE']);
}elseif ($directive_xml != ""){
    $XML_FILE = '/etc/ossim/server/'.$directive_xml;
    //unset($_SESSION['XML_FILE']);
}elseif ($_SESSION['XML_FILE'] != ""){
    $XML_FILE = $_SESSION['XML_FILE'];
}else{
    $XML_FILE = get_directive_file($directive_id);
}

if ($XML_FILE != '/etc/ossim/server/directives.xml') {
    init_file($XML_FILE);
}
//$_SESSION['XML_FILE']=$XML_FILE;
/* create dom object from a XML file */
if (!$dom = domxml_open_file($XML_FILE, DOMXML_LOAD_SUBSTITUTE_ENTITIES)) {
    echo "<center><span style='color:red'>";
    if ($XML_FILE!="")
        echo _("Error while parsing the document\nMake sure that")." $XML_FILE "._("begins with xml tag!\n");
    else
        echo _("The directive doesn't exist");
        
    echo "</span></center>";
    exit;
}

if ($directive_xml != "") $_SESSION['XML_FILE'] = $XML_FILE;
if ($category_mini != "") $_SESSION['mini'] = $category_mini;

if (!empty($directive_id)) {
	if (is_free($directive_id,$XML_FILE) == "true") $direct = unserialize($_SESSION['directive']);
    else {
		$direct = getDirectiveFromXML($dom, $directive_id);
        $tab_rules = $direct->rules;
    }
    if ($direct->id != $directive_id) {
        echo "<center><span style='color:red'>"._("The directive $directive_id doesn't exist in $XML_FILE")."</span></center>";
        exit;
    }
    $_SESSION['directive'] = serialize($direct);
    if ($XML_FILE != '/etc/ossim/server/directives.xml') {
        release_file($XML_FILE);
    }
    if (!empty($directive_id)) {
        $direct->printDirective($level,$directive_xml);
    }
    
?>
</table>
<?
$directive_name = Plugin_sid::get_name_by_idsid($conn,"1505",$directive_id);
list($properties,$num_properties) = Compliance::get_category($conn,"WHERE sid=$directive_id");
$iso_groups = ISO27001::get_groups($conn,"WHERE SIDSS_Ref LIKE '$directive_id' OR SIDSS_Ref LIKE '$directive_id,%' OR SIDSS_Ref LIKE '%,$directive_id' OR SIDSS_Ref LIKE '%,$directive_id,%'");
$pci_groups = PCI::get_groups($conn,"WHERE SIDSS_ref LIKE '$directive_id' OR SIDSS_ref LIKE '$directive_id,%' OR SIDSS_ref LIKE '%,$directive_id' OR SIDSS_ref LIKE '%,$directive_id,%'");
list($alarms,$num_alarms) = Alarm::get_list3($conn,"","",0,"",null,null,null,null,"",$directive_id);
$kdocs = Repository::get_linked_by_directive($conn,$directive_id);
?>
<table class="transparent" height="100%" width="100%">
	<tr>
		<td class="nobborder" valign="top">
			<table height="100%" width="100%">
				<tr><th colspan="2" height="15"><?=_("Properties")?></th></tr>
				<? if (count($properties) < 1) { ?>
				<tr><td class="nobborder" style="color:gray;padding:10px"><i><?=_("No properties found")?></i></td></tr>
				<? } else { ?>
				<? foreach ($properties as $p) { ?>
				<tr><td class="nobborder" style="text-align:right"><?=_("Targeted")?></td><td class="nobborder" style="padding-right:5px;padding-left:5px"><img align="absmiddle" src="../../pixmaps/tables/<?=($p->get_targeted()) ? "tick-small-circle.png" : "cross-small-circle.png"?>"></td></tr>
				<tr><td class="nobborder" style="text-align:right"><?=_("Approach")?></td><td class="nobborder" style="padding-right:5px;padding-left:5px"><img align="absmiddle" src="../../pixmaps/tables/<?=($p->get_approach()) ? "tick-small-circle.png" : "cross-small-circle.png"?>"></td></tr>
				<tr><td class="nobborder" style="text-align:right"><?=_("Exploration")?></td><td class="nobborder" style="padding-right:5px;padding-left:5px"><img align="absmiddle" src="../../pixmaps/tables/<?=($p->get_exploration()) ? "tick-small-circle.png" : "cross-small-circle.png"?>"></td></tr>
				<tr><td class="nobborder" style="text-align:right"><?=_("Penetration")?></td><td class="nobborder" style="padding-right:5px;padding-left:5px"><img align="absmiddle" src="../../pixmaps/tables/<?=($p->get_penetration()) ? "tick-small-circle.png" : "cross-small-circle.png"?>"></td></tr>
				<tr><td class="nobborder" style="text-align:right" nowrap><?=_("General Malware")?></td><td class="nobborder" style="padding-right:5px;padding-left:5px"><img align="absmiddle" src="../../pixmaps/tables/<?=($p->get_generalmalware()) ? "tick-small-circle.png" : "cross-small-circle.png"?>"></td></tr>
				<tr><td class="nobborder" style="text-align:right"><?=_("IMP QOS")?></td><td class="nobborder" style="padding-right:5px;padding-left:5px"><img align="absmiddle" src="../../pixmaps/tables/<?=($p->get_imp_qos()) ? "tick-small-circle.png" : "cross-small-circle.png"?>"></td></tr>
				<tr><td class="nobborder" style="text-align:right"><?=_("IMP Infleak")?></td><td class="nobborder" style="padding-right:5px;padding-left:5px"><img align="absmiddle" src="../../pixmaps/tables/<?=($p->get_imp_infleak()) ? "tick-small-circle.png" : "cross-small-circle.png"?>"></td></tr>
				<tr><td class="nobborder" style="text-align:right"><?=_("IMP Lawful")?></td><td class="nobborder" style="padding-right:5px;padding-left:5px"><img align="absmiddle" src="../../pixmaps/tables/<?=($p->get_imp_lawful()) ? "tick-small-circle.png" : "cross-small-circle.png"?>"></td></tr>
				<tr><td class="nobborder" style="text-align:right"><?=_("IMP Image")?></td><td class="nobborder" style="padding-right:5px;padding-left:5px"><img align="absmiddle" src="../../pixmaps/tables/<?=($p->get_imp_image()) ? "tick-small-circle.png" : "cross-small-circle.png"?>"></td></tr>
				<tr><td class="nobborder" style="text-align:right"><?=_("IMP Financial")?></td><td class="nobborder" style="padding-right:5px;padding-left:5px"><img align="absmiddle" src="../../pixmaps/tables/<?=($p->get_imp_financial()) ? "tick-small-circle.png" : "cross-small-circle.png"?>"></td></tr>
				<tr><td class="nobborder" style="text-align:right"><?=_("IMP Infleak")?></td><td class="nobborder" style="padding-right:5px;padding-left:5px"><img align="absmiddle" src="../../pixmaps/tables/<?=($p->get_imp_infleak()) ? "tick-small-circle.png" : "cross-small-circle.png"?>"></td></tr>
				<tr><td class="nobborder" style="text-align:right"><?=_("Availability")?></td><td class="nobborder" style="padding-right:5px;padding-left:5px"><img align="absmiddle" src="../../pixmaps/tables/<?=($p->get_D()) ? "tick-small-circle.png" : "cross-small-circle.png"?>"></td></tr>
				<tr><td class="nobborder" style="text-align:right"><?=_("Integrity")?></td><td class="nobborder" style="padding-right:5px;padding-left:5px"><img align="absmiddle" src="../../pixmaps/tables/<?=($p->get_I()) ? "tick-small-circle.png" : "cross-small-circle.png"?>"></td></tr>
				<tr><td class="nobborder" style="text-align:right"><?=_("Confidentiality")?></td><td class="nobborder" style="padding-right:5px;padding-left:5px"><img align="absmiddle" src="../../pixmaps/tables/<?=($p->get_C()) ? "tick-small-circle.png" : "cross-small-circle.png"?>"></td></tr>
				<tr><td class="nobborder" style="text-align:right"><?=_("Net Anomaly")?></td><td class="nobborder" style="padding-right:5px;padding-left:5px"><img align="absmiddle" src="../../pixmaps/tables/<?=($p->get_net_anomaly()) ? "tick-small-circle.png" : "cross-small-circle.png"?>"></td></tr>
				<? } ?>
				<? } ?>
			</table>
		</td>
		<td class="nobborder" valign="top">
			<table height="100%" width="100%">
				<tr><th height="15"><?=_("ISO27001")?></th></tr>
				<? if (count($iso_groups) < 1) { ?>
				<tr><td class="nobborder" style="color:gray;padding:10px"><i><?=_("No ISO27001 found")?></i></td></tr>
				<? } else { ?>
				<? foreach ($iso_groups as $title=>$data) foreach ($data['subgroups'] as $ref=>$iso) { ?>
				<tr><td class="nobborder" style="text-align:left"><b><?=$iso['Ref']?></b> <?=$iso['Security_controls']?></td></tr>
				<? } ?>
				<? } ?>
			</table>
		</td>
		<td class="nobborder" valign="top">
			<table height="100%" width="100%">
				<tr><th height="15"><?=_("PCI")?></th></tr>
				<? if (count($pci_groups) < 1) { ?>
				<tr><td class="nobborder" style="color:gray;padding:10px"><i><?=_("No PCI found")?></i></td></tr>
				<? } else { ?>
				<? foreach ($pci_groups as $title=>$data) foreach ($data['subgroups'] as $ref=>$iso) { ?>
				<tr><td class="nobborder" style="text-align:left"><b><?=$iso['Ref']?></b> <?=$iso['Security_controls']?></td></tr>
				<? } ?>
				<? } ?>
			</table>
		</td>
		<td class="nobborder" valign="top">
			<table height="100%" width="100%">
				<tr><th colspan="3" height="15"><?=_("Alarms")?></th></tr>
				<? if (count($alarms) < 1) { ?>
				<tr><td class="nobborder" style="color:gray;padding:10px"><i><?=_("No Alarms found")?></i></td></tr>
				<? } else { ?>
				<tr>
					<th height="10"><?=_("Name")?></th>
					<th height="10"><?=_("Risk")?></th>
					<th height="10"><?=_("Status")?></th>
				</tr>
				<? $i = 0; foreach ($alarms as $alarm) { if ($i > 5) continue; 
					$bg = "white";
					$color = "black";
					$risk = $alarm->get_risk();
					if ($risk > 7) { $bg="red"; $color="white"; }
					elseif ($risk > 4) { $bg="orange"; $color="black"; }
					elseif ($risk > 2) { $bg="green"; $color="white"; }?>
				<tr>
					<td class="nobborder" style="text-align:left"><?=str_replace("directive_event: ","",$alarm->get_sid_name())?></td>
					<td class="nobborder" style="text-align:center;background-color:<?=$bg?>;color:<?=$color?>"><?=$risk?></td>
					<td class="nobborder" style="text-align:center"><img src="../../pixmaps/<?=($alarm->get_status() == "open") ? "lock-unlock.png" : "lock.png"?>"></td>
				</tr>
				<? $i++; } ?>
				<? if (count($alarms) > 5) { ?>
				<tr><td colspan="3" class="nobborder" style="text-align:right"><a href="../../control_panel/alarm_console.php?hide_closed=1&hmenu=Alarms&smenu=Alarms&directive_id=<?=$directive_id?>" target="main"><?=_("More")?>>></a></td></tr>
				<? } ?>
				<? } ?>
			</table>
		</td>
		<td class="nobborder" valign="top">
			<table height="100%" width="100%">
				<tr><th colspan="3" height="15"><?=_("KDB")?></th></tr>
				<? if (count($kdocs) < 1) { ?>
				<tr><td class="nobborder" style="color:gray;padding:10px"><i><?=_("No KDB linked documents")?></i></td></tr>
				<? } else { ?>
				<tr>
					<th height="10" colspan="2"><?=_("Date")?></th>
					<th height="10"><?=_("Title")?></th>
				</tr>
				<?  foreach ($kdocs as $doc) { ?>
				<tr>
					<td class="nobborder"><a href="javascript:;" class="scriptinfo" txt="<?=nl2br(str_replace("\"","'",$doc["text"]))?>"><img src="../../pixmaps/k.png" border="0" align="absmiddle"></a></td>
					<td class="nobborder" style="text-align:center;font-size:11px"><?=$doc["date"]?></td>
					<!--<td class="nobborder" style="text-align:left"><a href="../../repository/index.php?hmenu=Repository&smenu=Repository&searchstr=<?=urlencode($doc["title"])?>" target="main"><?=$doc["title"]?></a></td>-->
                    <td class="nobborder" style="text-align:left"><a href="javascript:;" onclick="window.open('../../repository/repository_document.php?id_document=<?=$directive_id?>&maximized=1','KDB','top=80, left=100, toolbar=no, status=no,menubar=no,scrollbars=no, resizable=no, width=800,height=500')" target="main"><?=$doc["title"]?></a></td>
                    </tr>
				<?  } ?>
				<? } ?>
			</table>
		</td>
	</tr>
	<tr>
		<td class="nobborder center" colspan="5" style="padding-top:20px">
			<input type="button" style="width: 100px" value="<?php echo _("Back to main")?>" onclick="window.open('../main.php','main')"></input>
		</td>
	</tr>
</table>

<?php
	$db->close($conn);
} else {
?>
<?php
    echo gettext("Click on the left side to view a directive"); ?>.<br/>
<?php
    echo gettext("Click on the categories of directives to expand or collapse them"); ?>.


<hr/><h2 style="text-align: left;"><?php
    echo gettext("Directive numbering"); ?></h2>

<table width = "400px">
<tr><th> <?php
    echo gettext("Del"); ?>
<th> <?php
    echo gettext("Category"); ?>
<!--<th> <?php
    echo gettext("Mini"); ?>
<th> <?php
    echo gettext("Maxi");?>--><?
    $categories = unserialize($_SESSION['categories']);
    $i = 0;
    foreach($categories as $category) { $color = ($i%2 == 0) ? "#F2F2F2" : "#FFFFFF"; ?>
<tr><td style="border:0px;background-color:<?php echo $color?>"><a onclick="javascript:if (confirm('<?php
        echo gettext("Are you sure you want to delete this category ?"); ?>')) { document.location.href='../include/utils.php?query=delete_category&id=<?php
        echo $category->id; ?>'; }" style="marging-left:20px; cursor:pointer" TITLE="<?php
        echo gettext("Delete this category"); ?>"><img src="../../pixmaps/cross-circle-frame.png" border="0" alt="<?php echo _("Delete")?>" title="<?php echo _("Delete")?>"></img></a>
<td style="border:0px;background-color:<?php echo $color?>"><a href="../editor/category/index.php?id=<?php
        echo $category->id; ?>" class="greybox_200" TITLE="<?php
        echo gettext("Click to modify this category"); ?>"><?php
        echo gettext($category->name); ?></a>
<!--<td> <?php
        echo $category->mini; ?>
<td> <?php
        echo $category->maxi; ?>--><?
    $i++; } ?>
<tr><td colspan="2" class="center nobborder"><a href="../editor/category/index.php?id=0" class="greybox_200" TITLE="<?php
    echo gettext("Add a new category"); ?>"><img src="../../pixmaps/plus-small.png" border="0" align="absmiddle"></img> <?php
    echo "<b>".gettext("New")."</b>"; ?></a>
</table>


<hr/><h2 style="text-align: left;"><?php
    echo gettext("Groups"); ?></h2>

<table width = "400px">
<tr><th> <?php
    echo gettext("Del"); ?>
<th> <?php
    echo gettext("Group"); ?>
<th> <?php
    echo gettext("Directives");
    $groups = unserialize($_SESSION['groups']);
    foreach($groups as $group) { ?>
<tr><td><a onclick="javascript:if (confirm('<?php
        echo gettext("Are you sure you want to delete this group ?"); ?>')) { document.location.href='../include/utils.php?query=delete_group&name=<?php
        echo $group->name; ?>'; }" style="marging-left:20px; cursor:pointer" TITLE="<?php
        echo gettext("Delete this group"); ?>"><img src="../../pixmaps/cross-circle-frame.png" border="0" alt="<?php echo _("Delete")?>" title="<?php echo _("Delete")?>"></img></a>
<td><a href="../editor/group/index.php?name=<?php
        echo $group->name; ?>&framed=1" class="greybox" TITLE="<?php
        echo gettext("Click to modify this group"); ?>"><?php
        echo gettext($group->name); ?></a>
<td style="text-align:left"> <?php
        $table = array();
        $table_dir = $dom->get_elements_by_tagname('directive');
        foreach($table_dir as $dir) {
            $table[$dir->get_attribute('id') ] = $dir->get_attribute('name');
        }
        $value = "";
        foreach($group->list as $dir) {
            if ($value != "") $value.= "<br>";
            $xml_file = get_directive_real_file($dir);
            $value.= $dir . " : <a href=\"../index.php?level=1&amp;directive=" . $dir . "&amp;directive_xml=" . $xml_file . "\" target=\"_parent\" TITLE=\"" . gettext("Edit this directive") . "\">" . $table[$dir] . "</a>";
        }
        print $value;
    } ?>
<tr><td colspan="3" class="center nobborder"><a href="../editor/group/index.php?name=0&framed=1" class="greybox" TITLE="<?php
    echo gettext("Add a new group"); ?>"><img src="../../pixmaps/plus-small.png" border="0" align="absmiddle"></img> <?php
    echo "<b>".gettext("New")."</b>"; ?></a>
</table>


<hr/><h3><?php echo _("Directive Rules") ?></h3> 
<div class="level3"> 
 
</div> 
 
<h4><?php echo _("Detector Rule elements") ?></h4> 
<div class="level4"> 
 
</div> 
 
<h5>type</h5> 
<div class="level5"> 
 
<p> 
<?php echo _("What type of rule is this. There are two possible types as of today :") ?>
</p> 
<ul> 
<li class="level1"><div class="li"> monitor</div> 
</li> 
<li class="level1"><div class="li"> detector</div> 
</li> 
</ul> 
 
<p> 
<?php echo _("As we are talking about detector rule elements. Type will take detector as value. Eg: type=\"detector\"") ?>
</p> 
 
</div> 
 
<h5>name</h5> 
<div class="level5"> 
 
<p> 
<?php echo _("The name of the rule describes what the system expects to collect in order to satisfy the condition of the rule for the correlation. This name Eg: name=\"100 <acronym title=\"Secure Shell\">SSH</acronym> Auth Failed events\"") ?>
 
</p> 
 
</div> 
 
<h5>reliability</h5> 
<div class="level5"> 
 
<p> 
<?php echo _("Reliability value of every event generated within the directive. It can be an absolute value 0-10 or incremental +2, +6. When using an incremental value, this will be added to the value that has taken the reliability field in the last event generated within this directive.") ?>
</p> 
 
<p> 
<?php echo _("By assigning the value of reliability for each of the rules is important to remember the formula for calculating the risk in OSSIM. Using high-reliability values at the lowest levels of correlation will get a large number of alarms even when low-valued assets is involved.") ?>
</p> 
 
<p> 
<?php echo _("Eg:");?> reliability="3" reliability="+3"
 
</p> 
 
</div> 
 
<h5>occurrence</h5> 
<div class="level5"> 
 
<p> 
<?php echo _("Number of events matching the conditions given in the rule that have to be collected before the directive generates an event. The first level doesn&#39;t have an occurrences value as it will always be one.") ?>
 
</p> 
 
</div> 
 
<h5>time_out</h5> 
<div class="level5"> 
 
<p> 
<?php echo _("Waiting time before the rule expires and the directive process defined in that rule is discarded. The first rule doesn&#39;t have a time_out value.") ?>
 
</p> 
 
</div> 
 
<h5>from</h5> 
<div class="level5"> 
 
<p> 
<?php echo _("Source IP. There are various possible values for this field :") ?>
</p> 
<ul> 
<li class="level1"><div class="li"> <strong>ANY</strong>: <?php echo _("Just that, any ip address would match") ?></div> 
</li> 
<li class="level1"><div class="li"> <strong><?php echo _("Dotted numerical Ipv4 (x.x.x.x)") ?></strong>: <?php echo _("Self explaining.") ?></div> 
</li> 
<li class="level1"><div class="li"> <strong><?php echo _("Comma separated Ipv4 addresses without netmask") ?></strong></div> 
</li> 
<li class="level1"><div class="li"> <strong><?php echo _("Network Name") ?></strong>: <?php echo _("You can use any network name defined via web (<em>Assets -> Networks</em>).") ?></div> 
</li> 
<li class="level1"><div class="li"> <strong><?php echo _("Relative value") ?></strong>: <?php echo _("This is used to reference ip addresses from previous levels. This should be easier to understand using examples") ?></div> 
<ul> 
<li class="level2"><div class="li"> <?php echo _("1:SRC_IP means use the source ip that matched the condition defined by the previous rule as source ip address.") ?></div> 
</li> 
<li class="level2"><div class="li"> <?php echo _("2:DST_IP means use the destination ip that matched the condition defined two rules below as destination ip address.") ?></div> 
</li> 
</ul> 
</li> 
<li class="level1"><div class="li"> <strong> <?php echo _("Negated elements") ?></strong>: <?php echo _("You can also use negated elements. I.e. : \"!192.168.2.203,INTERNAL_NETWORK\".") ?> </div> 
<ul> 
<li class="level2"><div class="li"> <?php echo _("If INTERNAL_NETWORK == 192.168.2.0/24 this would match the whole class C except 192.168.2.203.") ?></div> 
</li> 
</ul> 
</li> 
<li class="level1"><div class="li"> <strong>HOME_NET</strong>: <?php echo _("This will match only when the Source IP belongs to your Assets, this means that is has been included in the OSSIM inventory as a host or that it belongs to a network or network group that is within your inventory.") ?></div> 
</li> 
</ul> 
 
</div> 
 
<h5>to</h5> 
<div class="level5"> 
 
<p> 
<?php echo _("Destination IP. There are various possible values for this field :") ?>
</p> 
<ul> 
<li class="level1"><div class="li"> <strong>ANY</strong>: <?php echo _("Just that, any ip address would match") ?></div> 
</li> 
<li class="level1"><div class="li"> <strong><?php echo _("Dotted numerical Ipv4 (x.x.x.x)") ?></strong>: <?php echo _("Self explaining.") ?></div> 
</li> 
<li class="level1"><div class="li"> <strong><?php echo _("Comma separated Ipv4 addresses without netmask") ?></strong></div> 
</li> 
<li class="level1"><div class="li"> <strong><?php echo _("Network Name") ?></strong>: <?php echo _("You can use any network name defined via web (<em>Assets -> Networks</em>).") ?></div> 
</li> 
<li class="level1"><div class="li"> <strong><?php echo _("Relative value") ?></strong>: <?php echo _("This is used to reference ip addresses from previous levels. This should be easier to understand using examples") ?> </div> 
<ul> 
<li class="level2"><div class="li"> <?php echo _("1:SRC_IP means use the source ip that matched the condition defined by the previous rule as source ip address.") ?></div> 
</li> 
<li class="level2"><div class="li"> <?php echo _("2:DST_IP means use the destination ip that matched the condition defined two rules below as destination ip address.") ?></div> 
</li> 
</ul> 
</li> 
<li class="level1"><div class="li"> <strong><?php echo _("Negated elements") ?></strong>: <?php echo _("You can also use negated elements. I.e. : \"!192.168.2.203,INTERNAL_NETWORK\".") ?> </div> 
<ul> 
<li class="level2"><div class="li"> <?php echo _("If INTERNAL_NETWORK == 192.168.2.0/24 this would match the whole class C except 192.168.2.203.") ?></div> 
</li> 
</ul> 
</li> 
<li class="level1"><div class="li"> <strong>HOME_NET</strong>: <?php echo _("This will match only when the Source IP belongs to your Assets, this means that is has been included in the OSSIM inventory as a host or that it belongs to a network or network group that is within your inventory.") ?></div> 
</li> 
</ul> 
 
</div> 
 
<h5>sensor</h5> 
<div class="level5"> 
 
<ul> 
<li class="level1"><div class="li"><strong>ANY</strong>: <?php echo _("Just that, any OSSIM Sensor would match.") ?></div>
</li>
<li class="level1"><div class="li"> <strong><?php echo _("Dotted numerical Ipv4 (x.x.x.x)") ?></strong>: <?php echo _("Self explaining.") ?></div> 
</li> 
<li class="level1"><div class="li"> <strong><?php echo _("Comma separated Ipv4 addresses without netmask") ?></strong></div> 
</li> 
<li class="level1"><div class="li"> <strong><?php echo _("Sensor Name") ?></strong>: <?php echo _("You can use any Sensor name defined via web (<em>Assets -> SIEM Components -> Sensors</em>).") ?></div> 
</li> 
<li class="level1"><div class="li"> <strong><?php echo _("Relative value") ?></strong>: <?php echo _("This is used to reference ip addresses from previous levels. This should be easier to understand using examples") ?> </div> 
<ul> 
<li class="level2"><div class="li"> <?php echo _("1:SENSOR means use the Sensor that matched the condition defined by the previous rule") ?></div> 
</li> 
</ul> 
</li> 
<li class="level1"><div class="li"> <strong><?php echo _("Negated elements") ?></strong>: <?php echo _("You can also use negated elements, separated by comma. I.e. : \"!192.168.2.203,ANY\".") ?> </div> 
</li> 
</ul> 
 
</div> 
 
<h5>port_to</h5> 
<div class="level5"> 
 
<p> 
<?php echo _("This can be a port number or a sequence of comma separated port numbers. ANY port can also be used.") ?>
<?php echo _("Hint: 1:DST_PORT or 1:SRC_PORT would mean level 1 src and dest port respectively. They can be used level 2 too. (would be 2:DST_PORT for example).") ?>
</p> 
 
<p> 
<?php echo _("Also you can negate ports. This will negate ports 22 and 21 in the directive: ") ?>
</p> 
 
<p> 
port="!22,25,110,!21"
 
</p> 
 
</div> 
 
<h5>port_from</h5> 
<div class="level5"> 
 
<p> 
<?php echo _("This can be a port number or a sequence of comma separated port numbers. ANY port can also be used.") ?>
<?php echo _("Hint: 1:DST_PORT or 1:SRC_PORT would mean level 1 src and dest port respectively. They can be used level 2 too. (would be 2:DST_PORT for example).") ?>
</p> 
 
<p> 
<?php echo _("Also you can negate ports. This will negate ports 22 and 21 in the directive: ") ?>
</p> 
 
<p> 
port="!22,25,110,!21"
 
</p> 
 
</div> 
 
<h5>protocol</h5> 
<div class="level5"> 
 
<p> 
<?php echo _("This can be one of the following strings:") ?>
 
</p> 
<ul> 
<li class="level1"><div class="li"> TCP</div> 
</li> 
<li class="level1"><div class="li"> UDP</div> 
</li> 
<li class="level1"><div class="li"> ICMP</div> 
</li> 
<li class="level1"><div class="li"> Host_ARP_Event</div> 
</li> 
<li class="level1"><div class="li"> Host_<acronym title="Operating System">OS</acronym>_Event</div> 
</li> 
<li class="level1"><div class="li"> Host_Service_Event</div> 
</li> 
<li class="level1"><div class="li"> Host_IDS_Event</div> 
</li> 
<li class="level1"><div class="li"> Information_Event </div> 
</li> 
</ul> 
 
<p> 
 
<?php echo _("Additionally, you can put just a number with the protocol.") ?>
</p> 
 
<p> 
<?php echo _("Although Host_ARP_Event, Host_<acronym title=\"Operating System\">OS</acronym>_Event, etc, are not really a protocol, you can use them if you want to do directives with ARP, <acronym title=\"Operating System\">OS</acronym>, IDS or Service events. You can also use relative referencing like in 1:TCP, 2:Host_ARP_Event, etc…") ?>
</p> 
 
<p> 
<?php echo _("You can negate the protocol also like this: protocol=\"!Host_ARP_Event,UDP,!ICMP\" This will negate Host_ARP_Event and ICMP, but will match with UDP. ") ?>
 
</p> 
 
</div> 
 
<h5>plugin_id</h5> 
<div class="level5"> 
 
<p> 
<?php echo _("Numerical identifier of the tool that provides the information (Events in detector rules and indicators in monitor rules)") ?>
 
</p> 
 
</div> 
 
<h5>plugin_sid</h5> 
<div class="level5"> 
 
<p> 
<?php echo _("Numerical identifier of the type of event within the tool defined by plugin_id that must met the condition defined by the directive rule. plugin_sid can take ANY as value, or a relative value when it is being used in a second or higher correlation level: Eg plugin_sid=\"1:PLUGIN_SID\"") ?>
 
</p> 
 
</div> 
 
<h5>sticky</h5> 
<div class="level5"> 
 
<p> 
<?php echo _("When the events arrive to the correlation engine they will try to be correlated inside directives whose correlation has been started") ?>
</p> 
 
<p> 
<?php echo _("Using sticky we avoid those events to start the correlation of the same directive again, as they may also meet the conditions given by the same directive.") ?>
Eg: sticky="true" or sticky="false"
</p> 
 
</div> 
 
<h5>sticky_different</h5> 
<div class="level5"> 
 
<p> 
<?php echo _("This variable can be associated to any field in rules with more than one occurrence, to make all the occurrences have a different value in one of the fields.") ?>
</p> 
 
<p> 
Eg: sticky_different="DST_PORT" <?php echo _("(All the events matching the rule must have a different destination port (Port scanning detection))") ?>
</p> 
 
</div> 
 
<h5>Username, password, filename, userdata1, userdata2, userdata3, userdata4, userdata5, userdata6, userdata7, userdata8, userdata9</h5> 
<div class="level5"> 
 
<p> 
<?php echo _("This keywords are optional. They can be used to store special data from agents. Obviously, this only will work if the event has this modificators. The following things are accpeted:") ?>
<?php echo _("You can insert any string to match here. If you want that this matches with any keyword, you can skip these keywords, or use ANY as the value. ") ?>
</p> 
<ul> 
<li class="level1"><div class="li"> <strong>ANY</strong>: <?php echo _("Just that, this will match with any word. You can also avoid this keyword, and it will match too.") ?></div> 
</li> 
<li class="level1"><div class="li"> <strong><?php echo _("Comma separated list") ?></strong>:<?php echo _("You can use any number of words separated by commas") ?></div> 
</li> 
<li class="level1"><div class="li"> <strong><?php echo _("Relative value") ?></strong>: <?php echo _("This is used to reference keywords from previous levels, for example:") ?></div> 
<ul> 
<li class="level2"><div class="li"> 1:FILENAME -> <?php echo _("Means use the filename referenced in the first rule level") ?></div> 
</li> 
<li class="level2"><div class="li"> 2:USERDATA5 -> <?php echo _("Means use some data from USERDATA5 keyword referenced in the second rule level") ?></div> 
</li> 
</ul> 
</li> 
<li class="level1"><div class="li"> <strong><?php echo _("Negated") ?></strong>: <?php echo _("You can also use negated keywords, i.e: \"!johndoe,foobar\". This will match with foobar, but not johndoe") ?></div> 
<ul> 
<li class="level2"><div class="li"> <?php echo _("Here you can see an example of what can be done:") ?> </div> 
</li> 
</ul> 
</li> 
</ul> 
 
<p> 
username="one,two,three,!four4444,five" filename="1:FILENAME,/etc/password,!/etc/shadow" userdata5="el cocherito lere me dijo anoche lere,!2:USERDATA5"
</p> 
 
<p> 
NOTE: There are some kind of events that stores by default some of that fields:
</p> 
<ul> 
<li class="level1"><div class="li"> Arpwatch events:    Userdata1 = MAC</div> 
</li> 
<li class="level1"><div class="li"> Pads events:    Userdata1 = application ; Userdata2 = service</div> 
</li> 
<li class="level1"><div class="li"> P0f Events:    Userdata1 = O.S.</div> 
</li> 
<li class="level1"><div class="li"> Syslog Events:    Username = dest username ; Userdata1 = src username ; Userdata2 = src user uid ; Userdata3 = service</div> 
</li> 
</ul> 
 
</div> 
 
<h4>Monitor Rule elements</h4> 
<div class="level4"> 
 
</div> 
 
<h5>type</h5> 
<div class="level5"> 
 
<p> 
<?php echo _("What type of rule is this. There are two possible types as of today :") ?>
</p> 
<ul> 
<li class="level1"><div class="li"> monitor</div> 
</li> 
<li class="level1"><div class="li"> detector</div> 
</li> 
</ul> 
 
<p> 
<?php echo _("As we are talking about monitor rule elements. Type will take monitor as value. Eg: type=\"monitor\"") ?>
 
</p> 
 
</div> 
 
<h5>name</h5> 
<div class="level5"> 
 
<p> 
<?php echo _("The rule name should describe the type of information that we obtain when querying the tool or device during correlation using the monitor plugin.") ?>
 
</p> 
 
</div> 
 
<h5>reliability</h5> 
<div class="level5"> 
 
<p> 
<?php echo _("Reliability value of every event generated within the directive. It can be an absolute value 0-10 or incremental +2, +6. When using an incremental value, this will be added to the value that has taken the reliability field in the last event generated within this directive.") ?>
</p> 
 
<p> 
<?php echo _("By assigning the value of reliability for each of the rules is important to remember the formula for calculating the risk in OSSIM. Using high-reliability values at the lowest levels of correlation will get a large number of alarms even when low-valued assets is involved.") ?>
</p> 
 
<p> 
Eg: reliability="3" reliability="+3"
 
</p> 
 
</div> 
 
<h5>plugin_id</h5> 
<div class="level5"> 
 
<p> 
<?php echo _("Numerical identifier of the monitor plugin that will query the device or application to feed the correlation engine with indicators while correlation takes place. ") ?>
 
</p> 
 
</div> 
 
<h5>plugin_sid</h5> 
<div class="level5"> 
 
<p> 
<?php echo _("Numerical identifier of the request or query that has to be executed. In this case we can <strong>not</strong> use ANY or a relative value.") ?>
 
</p> 
 
</div> 
 
<h5>time_out</h5> 
<div class="level5"> 
 
<p> 
<?php echo _("Waiting time before the rule expires and the directive process defined in that rule is discarded. The first rule doesn’t have a time_out value.") ?>
 
</p> 
 
</div> 
 
<h5>condition</h5> 
<div class="level5"> 
 
<p> 
<?php echo _("The condition field establishes a logical relation between the value field and the value returned in the monitor plugin request. It can take the following values:") ?>
</p> 
 
<p> 
<center> 
 
</p> 
<table class="inline"> 
	<tr class="row0"> 
		<th class="col0"><strong>eq</strong></th><td class="col1">equal</td> 
	</tr> 
	<tr class="row1"> 
		<th class="col0"><strong>ne</strong></th><td class="col1">non equal</td> 
	</tr> 
	<tr class="row2"> 
		<th class="col0"><strong>lt</strong></th><td class="col1">less than</td> 
	</tr> 
	<tr class="row3"> 
		<th class="col0"><strong>gt</strong></th><td class="col1">greater than</td> 
	</tr> 
	<tr class="row4"> 
		<th class="col0"><strong>le</strong></th><td class="col1">less or equal</td> 
	</tr> 
	<tr class="row5"> 
		<th class="col0"><strong>ge</strong></th><td class="col1">greater or equal</td> 
	</tr> 
</table> 
 
<p> 
</center> 
</p> 
 
</div> 
 
<h5>value</h5> 
<div class="level5"> 
 
<p> 
<?php echo _("This field sets the value that has to be compared with the value returned by the collector after doing the monitor request.") ?>
</p> 
 
<p> 
Value must be an integer. Eg: value="333"
</p> 
 
</div> 
 
<h5>time_out</h5> 
<div class="level5"> 
 
<p> 
<?php echo _("Waiting time before the rule expires and the directive process defined in that rule is discarded. ") ?>
 
</p> 
 
</div> 
 
<h5>interval</h5> 
<div class="level5"> 
 
<p> 
<?php echo _("This value of this field sets the waiting time between each monitor request before the rule is discarded because the time defined by time_out is over. ") ?>
 
</p> 
 
</div> 
 
<h5>absolute</h5> 
<div class="level5"> 
 
<p> 
<?php echo _("This value sets if the value that has to be compared is relative or absolute.") ?>
</p> 
<ul> 
<li class="level1"><div class="li"> Absolute true:  <?php echo _("If the host has more than 1000 bytes  sent during the next  60 seconds. There will be an answer if in 60 seconds  this value is reached.") ?> absolute="true"</div> 
</li> 
<li class="level1"><div class="li"> Absolute false: <?php echo _("If the  host shows an increase of more than 1000 bytes sent. There will be an answer if the host  shows this increase in 60 seconds.") ?> absolute="false"</div> 
</li> 
</ul> 
 
</div> 
 
<h5>from, to, port_from, port, to, protocol, sensor,Username, password, filename, userdata1, userdata2, userdata3, userdata4, userdata5, userdata6, userdata7, userdata8, userdata9</h5> 
<div class="level5"> 
 
<p> 
 
<?php echo _("In monitor type rules, these fields are not used to define a condition that must be matched by the events arriving to the the OSSIM server. These fields will be used to send information to the collector in order to be used in the query that is done through a monitor plugin.") ?>
</p> 
 
<p> 
<?php echo _("For this reason it does <strong>not</strong> makes sense to use values such as HOME_NET or ANY. You will need to write the value that has to be send to the build the query of the monitor plugin: Eg: from=\"192.168.2.2\" or use a relative value such as from=\"1:SRC_IP\" to send to the monitor plugin the ip address that matched as source ip in the previous correlation level.") ?> 
 
</p> 
 
</div>
<?php
}
?>
<br/>
</body>
</html>