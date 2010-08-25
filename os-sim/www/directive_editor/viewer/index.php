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
ini_set('memory_limit', '128M');
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
$directive_id = GET('directive');
$directive_xml = $_GET['directive_xml'];
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
        $direct->printDirective($level);
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
<table class="noborder" height="100%" width="100%">
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
    foreach($categories as $category) { ?>
<tr><td><a onclick="javascript:if (confirm('<?php
        echo gettext("Are you sure you want to delete this category ?"); ?>')) { window.open('../include/utils.php?query=delete_category&id=<?php
        echo $category->id; ?>','right'); }" style="marging-left:20px; cursor:pointer" TITLE="<?php
        echo gettext("Delete this category"); ?>">x</a>
<td><a href="../right.php?action=edit_file&id=<?php
        echo $category->id; ?>" TARGET="right" TITLE="<?php
        echo gettext("Click to modify this category"); ?>"><?php
        echo gettext($category->name); ?></a>
<!--<td> <?php
        echo $category->mini; ?>
<td> <?php
        echo $category->maxi; ?>--><?
    } ?>
<tr><td colspan="2" class="center nobborder"><a href="../right.php?action=add_file&id=0" TARGET="right" TITLE="<?php
    echo gettext("Add a new category"); ?>"><?php
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
        echo gettext("Are you sure you want to delete this group ?"); ?>')) { window.open('../include/utils.php?query=delete_group&name=<?php
        echo $group->name; ?>','right'); }" style="marging-left:20px; cursor:pointer" TITLE="<?php
        echo gettext("Delete this group"); ?>">x</a>
<td><a href="../right.php?action=edit_group&id=<?php
        echo $group->name; ?>" TARGET="right" TITLE="<?php
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
            $value.= $dir . " : <a href=\"index.php?level=1&amp;directive=" . $dir . "\" target=\"right\" TITLE=\"" . gettext("Edit this directive") . "\">" . $table[$dir] . "</a>";
        }
        print $value;
    } ?>
<tr><td><a href="../right.php?action=add_group&id=0" TARGET="right" TITLE="<?php
    echo gettext("Add a new group"); ?>"><?php
    echo gettext("New"); ?></a>
</table>


<hr/><h2 style="text-align: left;"><?php
    echo gettext("Element of a directive"); ?></h2>

<h3 style="text-align: left;">Type</h3>
<?php
    echo gettext("What type of rule is this. There are two possible types as of today"); ?> :
<ol>
<li> <?php
    echo gettext("Detector"); ?> <br/>
<?php
    echo gettext("Detector rules are those received automatically from the agent as they are recorded. This includes snort, spade, apache, etc"); ?> ...
<li> <?php
    echo gettext("Monitor"); ?> <br/>
<?php
    echo gettext("Monitor rules must be queried by the server ntop data and ntop sessions"); ?> .
</ol>
<h3 style="text-align: left;">Name</h3>
<?php
    echo gettext("The  rule name shown within the event database when the level is matched"); ?> .<br/>
<?php
    echo gettext("Accepts: UTF-8 compliant string"); ?> .
<h3 style="text-align: left;">Priority</h3>
<?php
    echo gettext("When we talk about priority we're talking about threat. It's the importance of the isolated attack. It has nothing to do with your equipment or environment, it only measures the relative importance of the attack"); ?> .<br/>
<?php
    echo gettext("This will become clear using a couple of examples"); ?> .
<ol>
<li> <?php
    echo gettext("Your unix server running samba gets attacked by the sasser worm"); ?> .<br/>
<?php
    echo gettext("The attack") . " "; ?>
<i> <?php
    echo gettext("per se") . " "; ?></i>
<?php
    echo gettext("is dangerous, it has compromised thousands of hosts and is very easy to accomplish. But. does it really matter to you? Surely not, but it's a big security hole so it'll have a high priority"); ?> .
<li> <?php
    echo gettext("You're running a CVS server on an isolated network that is only accessible by your friends and has only access to the outside. Some new exploit tested by one of your friends hits it"); ?> .<br/>
<?php
    echo gettext("Again, the attack is dangerous, it could compromise your machine but surely your host is patched against that particular attack and you don't mind being a test-platform for one of your friends"); ?> .
</ol>
<?php
    echo gettext("Default value"); ?> : 1.
<h3 style="text-align: left;"> Reliability </h3>
<?php
    echo gettext("When talking about classic risk-assessment this would be called") . " "; ?> &quot;
<?php
    echo gettext("probability") . " "; ?> &quot;.
<?php
    echo gettext("Since it's quite difficult to determine how probable it is that our network being attacked through one or another vulnerability, we'll transform this term into something more IDS related: reliability"); ?> .<br/>
<?php
    echo gettext("Surely many of you have seen unreliable signatures on every available NIDS. A host pinging a non-live destination is able to rise hundreds of thousands spade events a day. Snort's recent http-inspect functionality for example, although good implemented needs some heavy tweaking in order to be reliable or you'll get thousands of false positives a day"); ?> .<br/>
<?php
    echo gettext("Coming back to our worm example. If a hosts connects to 5 different hosts on their own subnet using port 445, that could be a normal behaviour. Unreliable for IDS purposes. What happens if they connect to 15 hosts? We're starting to get suspicious. And what if they contact 500 different hosts in less than an hour? That's strange and the attack is getting more and more reliable"); ?> .<br/>
<?php
    echo gettext("Each rule has it's own reliability, determining how reliable this particular rule is within the whole attack chain"); ?> .<br/>
<?php
    echo gettext("Accepts: 0-10. Can be specified as absolute value (i.e. 7) or relative (i.e. +2 means two more than the previous level)"); ?> .<br/>
<?php
    echo gettext("Default value"); ?> : 1.
<h3 style="text-align: left;"> Ocurrence </h3>
<?php
    echo gettext("How many times we have to match a unique") . " "; ?>
&quot;from, to, port_from, port_to, plugin_id &amp; plugin_sid&quot; <?php
    echo " " . gettext("in order to advance one correlation level"); ?> .
<h3 style="text-align: left;">Time_out</h3>
<?php
    echo gettext("We wait a fixed amount of seconds until a rule expires and the directives lifetime is over"); ?> .
<h3 style="text-align: left;">From</h3>
<?php
    echo gettext("Source IP. There are various possible values for this field"); ?> :
<ol>
<li>ANY<br/>
<?php
    echo gettext("Just that, any ip address would match"); ?> .<br/>
<li> <?php
    echo gettext("Dotted numerical Ipv4"); ?> (x.x.x.x)<br/>
<?php
    echo gettext("Self explaining"); ?> .<br/>
<li> <?php
    echo gettext("Comma separated Ipv4 addresses without netmask"); ?> .<br/>
<?php
    echo gettext("You can use any number of ip addresses separated by commas"); ?> .<br/>
<li> <?php
    echo gettext("Using a network name"); ?> .<br/>
<?php
    echo gettext("You can use any network name defined via web"); ?> .<br/>
<li> <?php
    echo gettext("Relative"); ?> .<br/>
<?php
    echo gettext("This is used to reference ip addresses from previous levels. This should be easier to understand using examples"); ?> <br/>
1:SRC_IP <?php
    echo gettext("means use the source ip referenced within the previous rule"); ?> .<br/>
2:DST_IP <?php
    echo gettext("means use the destination ip referenced two rules below as source address"); ?> .<br/>
<li> <?php
    echo gettext("Negated"); ?> .<br/>
<?php
    echo gettext("You can also use negated elements. I.e."); ?> :<br/>
&quot;!192.168.2.203,INTERNAL_NETWORK&quot;.<br/>
<?php
    echo gettext("If ") . " "; ?> INTERNAL_NETWORK == 192.168.2.0/24
<?php
    echo " " . gettext("this would match the whole class C except"); ?> 192.168.2.203.
</ol>
<h3 style="text-align: left;">To</h3>
<?php
    echo gettext("Destination IP. There are various possible values for this field"); ?> :
<ol>
<li>ANY<br/>
<?php
    echo gettext("Just that, any ip address would match"); ?> .<br/>
<li> <?php
    echo gettext("Dotted numerical Ipv4"); ?> (x.x.x.x)<br/>
<?php
    echo gettext("Self explaining"); ?> .<br/>
<li> <?php
    echo gettext("Comma separated Ipv4 addresses without netmask"); ?> .<br/>
<?php
    echo gettext("You can use any number of ip addresses separated by commas"); ?> .<br/>
<li> <?php
    echo gettext("Using a network name"); ?> .<br/>
<?php
    echo gettext("You can use any network name defined via web"); ?> .<br/>
<li> <?php
    echo gettext("Relative"); ?> .<br/>
<?php
    echo gettext("This is used to reference ip addresses from previous levels. This should be easier to understand using examples"); ?> <br/>
1:SRC_IP <?php
    echo gettext("means use the source ip referenced within the previous rule"); ?> .<br/>
2:DST_IP <?php
    echo gettext("means use the destination ip referenced two rules below as source address"); ?> .<br/>
<li> <?php
    echo gettext("Negated"); ?> .<br/>
<?php
    echo gettext("You can also use negated elements. I.e."); ?> :<br/>
&quot;!192.168.2.203,INTERNAL_NETWORK&quot;.<br/>
<?php
    echo gettext("If") . " "; ?> INTERNAL_NETWORK == 192.168.2.0/24
<?php
    echo " " . gettext("this would match the whole class C except") . " "; ?> 192.168.2.203.
</ol>
<?php
    echo gettext("The") . " "; ?> &quot;To&quot; <?php
    echo " " . gettext("field is the field used when referencing monitor data that has no source"); ?> .<br/>
<?php
    echo gettext("Both \"From\" and \"To\" fields should accept input from the database in the near future. Host and Network objects are on the TODO list."); ?>
<h3 style="text-align: left;">Sensor</h3>
<?php
    echo gettext("Sensor IP. There are various possible values for this field"); ?> :
<ol>
<li>ANY<br/>
<?php
    echo gettext("Just that, any ip address would match"); ?> .<br/>
<li> <?php
    echo gettext("Dotted numerical Ipv4"); ?> (x.x.x.x)<br/>
<?php
    echo gettext("Self explaining"); ?> .<br/>
<li> <?php
    echo gettext("Comma separated Ipv4 addresses without netmask"); ?> .<br/>
<?php
    echo gettext("You can use any number of ip addresses separated by commas"); ?> .<br/>
<li> <?php
    echo gettext("Using a sensor name"); ?> .<br/>
<?php
    echo gettext("You can use any sensor name defined via web"); ?> .<br/>
<li> <?php
    echo gettext("Relative"); ?> .<br/>
<?php
    echo gettext("This is used to reference sensor ip addresses from previous levels. This should be easier to understand using examples"); ?> <br/>
1:SENSOR <?php
    echo gettext("means use the sensor ip referenced within the previous rule"); ?> .<br/>
<li> <?php
    echo gettext("Negated"); ?> .<br/>
<?php
    echo gettext("You can also use negated elements. I.e."); ?> :<br/>
&quot;!192.168.2.203&quot;.<br/>
</ol>

<h3 style="text-align: left;">Port_from / Port_to</h3>
<?php
    echo gettext("This can be a port number or a sequence of comma separated port numbers. ANY port can also be used"); ?>.<br/>
<?php
    echo gettext("Hint: 1:DST_PORT or 1:SRC_PORT would mean level 1 src and dest port respectively. They can be used too. (level 2 would be 2:DST_PORT for example)"); ?>.
<br> <br>
<?php
    echo gettext("Also you can negate ports. This will negate ports 22 and 21 in the directive"); ?>:
<br><br>
port="!22,25,110,!21"


<h3 style="text-align: left;">Protocol</h3>
<?php
    echo gettext("This can be one of the following strings"); ?>:<br><br>
<li> TCP
<li> UDP
<li> ICMP
<li> Host_ARP_Event
<li> Host_OS_Event
<li> Host_Service_Event
<li> Host_IDS_Event
<li> Information_Event
<br><br>
<li> <?php
    echo gettext("Additionally, you can put just a number with the protocol"); ?>.
<br><br>
<?php
    echo gettext("Although Host_ARP_Event, Host_OS_Event, etc, are not really a protocol, you can use them if you want to do directives with ARP, OS, IDS or Service events. You can also use relative referencing like in 1:TCP, 2:Host_ARP_Event, etc.."); ?>.
<br><br>
<?php
    echo gettext("You can negate the protocol also like this"); ?>:
protocol="!Host_ARP_Event,UDP,!ICMP"
<?php
    echo gettext("This will negate Host_ARP_Event and ICMP, but will match with UDP"); ?>.
<br/>


<h3 style="text-align: left;">Plugin_id</h3>
<?php
    echo gettext("The numerical id assigned to the referenced plugin"); ?>.
<h3 style="text-align: left;">Plugin_sid</h3>
<?php
    echo gettext("The nummerical sub-id assigned to each plugins events, functions or the like"); ?>.<br/>
<?php
    echo gettext("For example, plugin id 1001 (snort) references it.s rules as normal plugin_sids"); ?>.<br/>
<?php
    echo gettext("Plugin id 1501 (apache) uses the response codes as plugin_sid"); ?> (200 OK, 404 NOT FOUND, ...)<br/>
<?php
    echo gettext("ANY can be used too for plugin_sid"); ?>.
<br><br><?php
    echo gettext("You can negate plugin_sid's: plugin_sid=\"1,2,3,!4\" will negate just the plugin_sid 4"); ?>.

<h3 style="text-align: left;">Condition</h3>
<?php
    echo gettext("This parameter and the following three are only valid for \"monitor\" and certain \"detector\" type rules"); ?>.<br/>
<?php
    echo gettext("The logical condition that has to be met for the rule to match"); ?>:
<ol>
<li>eq - <?php
    echo gettext("Equal"); ?>
<li>ne - <?php
    echo gettext("Not equal"); ?>
<li>lt - <?php
    echo gettext("Less than"); ?>
<li>gt - <?php
    echo gettext("Greater than"); ?>
<li>le - <?php
    echo gettext("Less or equal"); ?>
<li>ge - <?php
    echo gettext("Greater or equal"); ?>
</ol>
<h3 style="text-align: left;">Value</h3>
<?php
    echo gettext("The value that has to be matched using the previous directives"); ?>.
<h3 style="text-align: left;">Interval</h3>
<?php
    echo gettext("This value is similar to time_out but used for \"monitor\" type rules"); ?>.
<h3 style="text-align: left;">Absolute</h3>
<?php
    echo gettext("Determines if the provided value is absolute or relative"); ?>.<br/>
<?php
    echo gettext("For example, providing 1000 as a value, gt as condition and 60 (seconds) as interval, querying ntop for HttpSentBytes would mean"); ?>:<br/>
<ul>
<li><?php
    echo gettext("Absolute true: Match if the host has more than 1000 http sent bytes within the next 60 seconds. Report back when (and only if) this absolute value is reached"); ?>.
<li><?php
    echo gettext("Absolute false: Match if the host shows an increase of 1000 http sent bytes within the next 60 seconds. Report back as soon as this difference is reached (if it was reached...)"); ?>
</ul>
<h3 style="text-align: left;">Sticky</h3>
<?php
    echo gettext("A bit more difficult to explain. Take the worm rule. At the end we want to match 20000 connections involving the same source host and same destination port but we want to avoid 20000 directives from spawning so this is our little helper. Just set this to true or false depending on how you want the system to behave. If it's true, all the vars that aren't ANY or fixed (fixed means defined source or dest host, port or plugin id or sid.) are going to be made sticky so they won't spawn another directive"); ?>.<br/>
<?php
    echo gettext("In our example at level 2 there are two vars that are going to be fixed at correlation level 2: 1:SRC_IP and 1:DST_PORT. Of course plugin_id is already fixed (1104 == spade) and all the other ANY vars are still going to be ANY"); ?>.
<h3 style="text-align: left;">Sticky_different</h3>
<?php
    echo gettext("Only suitable for rules with more than one occurrence. We want to make sure that the specified parameter happens X times (occurrence) and that all the occurrences are different"); ?>.<br/>
<?php
    echo gettext("Take one example. A straight-ahead port-scanning rule. Fix destination with the previous sticky and set sticky_different=\"1:DST_PORT\". This will assure we're going to match \"X occurrences\" against the same hosts having X different destination ports"); ?>.<br/>
<?php
    echo gettext("In our worm rule the most important var is the DST_IP because as the number increases the reliability increases as well. Which (normally operating) host is going to do thousands of connections for the same port against different hosts"); ?>??<br/>
<h3 style="text-align: left;">Groups</h3>
<?php
    echo gettext("As sticky but involving more than one directive. If an event matches against a directive defined within a group and the group is set as \"sticky\" it won't match any other directive"); ?>.
<br><br>
<h3 style="text-align: left;">Username, password, filename, userdata1, userdata2, userdata3, userdata4, userdata5, userdata6, userdata7, userdata8, userdata9</h3>
<?php
    echo gettext("This keywords are optional. They can be used to store special data from agents. Obviously, this only will work if the event has this modificators. The following things are accpeted"); ?>:<br>
<?php
    echo gettext("You can insert any string to match here. If you want that this matches with any keyword, you can skip these keywords, or use ANY as the value"); ?>. <br/>
<ol>
<li> ANY <br> <?php
    echo gettext("Just that, this will match with any word. You can also avoid this keyword, and it will match too"); ?>.
<li> <?php
    echo gettext("Comma separated list"); ?><br>
<?php
    echo gettext("You can use any number of words separated by commas"); ?>
<li> <?php
    echo gettext("Relative value"); ?><br>
<?php
    echo gettext("This is used to reference keywords from previous levels, for example"); ?>:<br>
1:FILENAME -> <?php
    echo gettext("Means use the filename referenced in the first rule level"); ?><br>
2:USERDATA5 -> <?php
    echo gettext("Means use some data from USERDATA5 keyword referenced in the second rule level"); ?>
<li> <?php
    echo gettext("Negated: You can also use negated keywords, i.e"); ?>: <br>
"!johndoe,foobar".<br>
<?php
    echo gettext("This will match with foobar, but not johndoe"); ?>
</ol>
<?php
    echo gettext("Here you can see an example of what can be done"); ?>: <br>

username="one,two,three,!four4444,five" filename="1:FILENAME,/etc/password,!/etc/shadow" userdata5="el cocherito lere me dijo anoche lere,!2:USERDATA5"
<br><br>
NOTE: There are some kind of events that stores by default some of that fields:<br>
<li>  Arpwatch events:&nbsp;&nbsp;&nbsp; Userdata1 = MAC
<li>  Pads events:&nbsp;&nbsp;&nbsp; Userdata1 = application ; Userdata2 = service
<li>  P0f Events:&nbsp;&nbsp;&nbsp; Userdata1 = O.S.<br>
<li>  Syslog Events:&nbsp;&nbsp;&nbsp; Username = dest username ; Userdata1 = src username ; Userdata2 = src user uid ; Userdata3 = service<br>

<br>
<hr/><h2 style="text-align: left;">Risk</h2>
<?php
    echo gettext("The main formula for risk calculation would look like this"); ?>:<br/>

Risk = (<?php
    echo gettext("Asset") . " * " . gettext("Priority") . " * " . gettext("Reliability"); ?>) / 25<br/>
<?php
    echo gettext("Where"); ?>:<ul>
<li><?php
    echo gettext("Asset"); ?> (0-5).
<li><?php
    echo gettext("Priority"); ?> (0-5).
<li><?php
    echo gettext("Reliability"); ?> (0-10).
</ul>
<?php
}
?>
<br/>
</body>
</html>