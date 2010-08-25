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
Session::logcheck("MenuEvents", "EventsViewer");
?>
<html>
<head>
  <title> <?php
echo gettext("Control Panel"); ?> </title>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" href="../style/style.css"/>
<style type="text/css">

#hintbox{ /*CSS for pop up hint box */
position:absolute;
top: 0;
background-color: lightyellow;
width: 150px; /*Default width of hint.*/ 
padding: 3px;
border:1px solid black;
font:normal 11px Verdana;
line-height:18px;
z-index:100;
border-right: 3px solid black;
border-bottom: 3px solid black;
visibility: hidden;
}

.hintanchor{ /*CSS for link that shows hint onmouseover*/
font-weight: bold;
color: navy;
margin: 3px 8px;
}

</style>

<script type="text/javascript">

/***********************************************
* Show Hint script- © Dynamic Drive (www.dynamicdrive.com)
* This notice MUST stay intact for legal use
* Visit http://www.dynamicdrive.com/ for this script and 100s more.
***********************************************/
		
var horizontal_offset="-10px" //horizontal offset of hint box from anchor link

/////No further editting needed

var vertical_offset="30" //horizontal offset of hint box from anchor link. No need to change.
var ie=document.all
var ns6=document.getElementById&&!document.all

function getposOffset(what, offsettype){
var totaloffset=(offsettype=="left")? what.offsetLeft : what.offsetTop;
var parentEl=what.offsetParent;
while (parentEl!=null){
totaloffset=(offsettype=="left")? totaloffset+parentEl.offsetLeft : totaloffset+parentEl.offsetTop;
parentEl=parentEl.offsetParent;
}
return totaloffset;
}

function iecompattest(){
return (document.compatMode && document.compatMode!="BackCompat")? document.documentElement : document.body
}

function clearbrowseredge(obj, whichedge){
var edgeoffset=(whichedge=="rightedge")? parseInt(horizontal_offset)*-1 : parseInt(vertical_offset)*-1
if (whichedge=="rightedge"){
var windowedge=ie && !window.opera? iecompattest().scrollLeft+iecompattest().clientWidth-30 : window.pageXOffset+window.innerWidth-40
dropmenuobj.contentmeasure=dropmenuobj.offsetWidth
if (windowedge-dropmenuobj.x < dropmenuobj.contentmeasure)
edgeoffset=dropmenuobj.contentmeasure+obj.offsetWidth+parseInt(horizontal_offset)
}
else{
var windowedge=ie && !window.opera? iecompattest().scrollTop+iecompattest().clientHeight-15 : window.pageYOffset+window.innerHeight-18
dropmenuobj.contentmeasure=dropmenuobj.offsetHeight
if (windowedge-dropmenuobj.y < dropmenuobj.contentmeasure)
edgeoffset=dropmenuobj.contentmeasure-obj.offsetHeight
}
return edgeoffset
}

function showhint(menucontents, obj, e, tipwidth){
if ((ie||ns6) && document.getElementById("hintbox")){
dropmenuobj=document.getElementById("hintbox")
dropmenuobj.innerHTML=menucontents
dropmenuobj.style.left=dropmenuobj.style.top=-500
if (tipwidth!=""){
dropmenuobj.widthobj=dropmenuobj.style
dropmenuobj.widthobj.width=tipwidth
}
dropmenuobj.x=getposOffset(obj, "left")
dropmenuobj.y=getposOffset(obj, "top")
dropmenuobj.style.left=dropmenuobj.x-clearbrowseredge(obj, "rightedge")+obj.offsetWidth+"px"
dropmenuobj.style.top=dropmenuobj.y-clearbrowseredge(obj, "bottomedge")+"px"
dropmenuobj.style.visibility="visible"
obj.onmouseout=hidetip
}
}

function hidetip(e){
dropmenuobj.style.visibility="hidden"
dropmenuobj.style.left="-500px"
}

function createhintbox(){
var divblock=document.createElement("div")
divblock.setAttribute("id", "hintbox")
document.body.appendChild(divblock)
}

if (window.addEventListener)
window.addEventListener("load", createhintbox, false)
else if (window.attachEvent)
window.attachEvent("onload", createhintbox)
else if (document.getElementById)
window.onload=createhintbox

</script>
</head>

<body>
<? include ("../hmenu.php") ?>

<?php
require_once ('ossim_db.inc');
require_once ('ossim_conf.inc');
require_once ('classes/Host.inc');
require_once ('classes/Host_os.inc');
require_once ('classes/Alarm.inc');
require_once ('classes/Plugin.inc');
require_once ('classes/Plugin_sid.inc');
require_once ('classes/Port.inc');
require_once ('classes/Util.inc');
require_once ('classes/Security.inc');
/*****************
Not the best place for such a definition, should come from db
*****************/
$default_asset = 2;
/****************/
$backlog_id = GET('backlog_id');
$event_id = GET('event_id');
$show_all = GET('show_all');
ossim_valid($backlog_id, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("backlog_id"));
ossim_valid($event_id, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("event_id"));
ossim_valid($show_all, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("show_all"));
if (ossim_error()) {
    die(ossim_error());
}
$summ_event_count = 0;
$highest_rule_level = 0;
$conf = $GLOBALS["CONF"];
$acid_link = $conf->get_conf("acid_link");
$acid_prefix = $conf->get_conf("event_viewer");
/* connect to db */
$db = new ossim_db();
$conn = $db->connect();
if (empty($show_all)) {
    $show_all = 0;
}
$host_list = Host::get_list($conn);

$assets = array();

foreach($host_list as $host) {
    $assets[$host->get_ip() ] = $host->get_asset();
}
$master_alarm_sid = 0;
?>
    <table width="100%">
   
       <tr>
         <td colspan="9"><a href="alarm_console.php"> <?php
echo gettext("Back to main"); ?> </a></td>
       </tr>
   
       <tr>
        <td></td>
        <th>#</th>
        <th> <?php
echo gettext("Id"); ?> </th>
        <th> <?php
echo gettext("Alarm"); ?> </th>
        <th> <?php
echo gettext("Risk"); ?> </th>
        <th> <?php
echo gettext("Date"); ?> </th>
        <th> <?php
echo gettext("Source"); ?> </th>
        <th> <?php
echo gettext("Destination"); ?> </th>
        <th> <?php
echo gettext("Correlation Level"); ?> </th>
      </tr>

<?php
$have_scanmap = $conf->get_conf("have_scanmap3d");
if ($have_scanmap == 1 && $show_all) {
    // Generate scanmap datafile
    $base_dir = $conf->get_conf("base_dir");
    if (!file_exists("$base_dir/tmp/$backlog_id.txt")) {
        $backlog_file = fopen("$base_dir/tmp/$backlog_id.txt", "w");
        if (!$backlog_file) $have_scanmap = 0;
    } else {
        $have_scanmap = 0;
    }
} else {
    $have_scanmap = 0;
}
if ($alarm_list = Alarm::get_events($conn, $backlog_id, $show_all, $event_id)) {
    $count_events = 0;
    $count_alarms = 0;
    foreach($alarm_list as $alarm) {
        $id = $alarm->get_plugin_id();
        $sid = $alarm->get_plugin_sid();
        $backlog_id = $alarm->get_backlog_id();
        $risk = $alarm->get_risk();
        $snort_sid = $alarm->get_snort_sid();
        $snort_cid = $alarm->get_snort_cid();
        /* get plugin_id and plugin_sid names */
        /*
        * never used?
        *
        $plugin_id_list = Plugin::get_list($conn, "WHERE id = $id");
        $id_name = $plugin_id_list[0]->get_name();
        */
        $sid_name = "";
        if ($plugin_sid_list = Plugin_sid::get_list($conn, "WHERE plugin_id = $id AND sid = $sid")) {
            $sid_name = $plugin_sid_list[0]->get_name();
            $sid_priority = $plugin_sid_list[0]->get_priority();
        } else {
            $sid_name = "Unknown (id=$id sid=$sid)";
            $sid_priority = "N/A";
        }
?>
      <tr>
        <?php
        $name = ereg_replace("directive_event: ", "", $sid_name);
        if (!$master_alarm_sid) {
            $master_alarm_sid = $sid;
        }
        if ($alarm->get_alarm()) $name = Util::translate_alarm($conn, $name, $alarm);
        $name = "<b>$name</b>";
?>

        <!-- expand alarms -->
        <td><?php
        $aid = $alarm->get_event_id();
        if (($event_id == $aid)) {
            $href = $_SERVER["SCRIPT_NAME"] . "?backlog_id=$backlog_id&show_all=0";
            $img = "../pixmaps/arrow.gif";
            echo "&nbsp;<a href=\"$href\"><img src=\"$img\" border=\"0\"/></a>";
        } elseif (($show_all == 0) or ($alarm->get_alarm())) {
            $href = $_SERVER["SCRIPT_NAME"] . "?backlog_id=$backlog_id&show_all=1&event_id=$aid";
            $img = "../pixmaps/arrow2.gif";
            echo "&nbsp;<a href=\"$href\"><img src=\"$img\" border=\"0\"/></a>";
        }
?></td>
        <!-- end expand alarms -->

        <!-- id & name event -->
        <td><?php
        if ($alarm->get_alarm()) echo "<b>" . ++$count_alarms . "</b>";
        else echo ++$count_events;
?></td>
        <td><?php
        echo $aid ?></td>
        <td <?php
        if ($alarm->get_alarm()) echo " bgcolor=\"#eeeeee\"" ?>>
        <?php
        $asset_src = array_key_exists($alarm->get_src_ip() , $assets) ? $assets[$alarm->get_src_ip() ] : $default_asset;
        $asset_dst = array_key_exists($alarm->get_dst_ip() , $assets) ? $assets[$alarm->get_dst_ip() ] : $default_asset;
        if (($snort_sid > 0) and ($snort_cid)) {
            $href = "$acid_link/" . $acid_prefix . "_qry_alert.php?submit=%230-%28" . "$snort_sid-$snort_cid%29";
        } else {
            $href = "#";
        }
		$href = str_replace("//","/",$href);
        //echo "&nbsp;&nbsp;<a href=\"$href\"" . " onMouseOver=\"showhint('" . "<ul><li>Src Asset: $asset_src<br/>" . "<li>Dst Asset: $asset_dst<br/>" . "<li>Priority: $sid_priority</li></ul>" . "', this, event, '200px')\" target=\"_self\">$name</a>";
		?>
		<div class="balloon">
				<a href="<?php echo $href ?>" <?php
            if (GET('box') == "1") { ?>target="_parent"<?php
            } ?>><?php echo $name ?></a>
				<span class="tooltip">
					<span class="top"></span>
					<span class="middle ne11">
						Src Asset: <b><?php echo $asset_src
?></b><br>
						Dst Asset: <b><?php echo $asset_dst
?></b><br>
						Priority: <b><?php echo $sid_priority
?></b>
					</span>
					<span class="bottom"></span>
				</span>
				</div>
		
</td>
        <!-- end id & name event -->
        
        <!-- risk -->
<?php
        $orig_date = $alarm->get_timestamp();
        $date = Util::timestamp2date($orig_date);
        $src_ip = $alarm->get_src_ip();
        $dst_ip = $alarm->get_dst_ip();
        $src_port = $alarm->get_src_port();
        $dst_port = $alarm->get_dst_port();
        if ($have_scanmap) {
            fwrite($backlog_file, "$orig_date,$src_ip,$src_port,$dst_ip,$dst_port\n");
        }
        $src_port = Port::port2service($conn, $src_port);
        $dst_port = Port::port2service($conn, $dst_port);
        if ($risk > 7) {
            echo "<td bgcolor=\"red\"><b>";
            if ($href) echo "<a href=\"$href\">";
            echo "<font color=\"white\">$risk</font>";
            if ($href) echo "</a>";
            echo "</b></td>";
        } elseif ($risk > 4) {
            echo "<td bgcolor=\"orange\"><b>";
            if ($href) echo "<a href=\"$href\">";
            echo "<font color=\"black\">$risk</font>";
            if ($href) echo "</a>";
            echo "</b></td>";
        } elseif ($risk > 2) {
            echo "<td bgcolor=\"green\"><b>";
            if ($href) echo "<a href=\"$href\">";
            echo "<font color=\"white\">$risk</font>";
            if ($href) echo "</a>";
            echo "</b></td>";
        } else {
            echo "<td><b>";
            if ($href) echo "<a href=\"$href\">";
            echo "$risk";
            if ($href) echo "</a>";
            echo "</b></td>";
        }
?>
        <!-- end risk -->

        <td nowrap>
          <a href="<?php
        echo Util::get_acid_date_link($date, $src_ip, "ip_src") ?>" target="_blank">
            <font color="black"><?php
        echo $date ?></font>
          </a>
        </td>

<?php
        $src_link = "../report/index.php?host=$src_ip&section=events";
        $src_title = "<ul><li>Src Asset: $asset_src</li><li>IP: $src_ip</li></ul>";
        $dst_link = "../report/index.php?host=$dst_ip&section=events";
        $dst_title = "<ul><li>Dst Asset: $asset_dst</li><li>IP: $dst_ip</li></ul>";
        $src_name = Host::ip2hostname($conn, $src_ip);
        $dst_name = Host::ip2hostname($conn, $dst_ip);
        $src_img = Host_os::get_os_pixmap($conn, $src_ip);
        $dst_img = Host_os::get_os_pixmap($conn, $dst_ip);
?>
        <!-- src & dst hosts -->
        <td bgcolor="#eeeeee" nowrap>
            <?php
        //echo "<a href=\"$src_link\" onMouseOver=\"showhint('$src_title', this, event, '200px')\" target=\"_blank\">$src_name</a>:$src_port $src_img"; ?>
		<div class="balloon">
				<a href="<?php echo $src_link ?>" <?php
            if (GET('box') == "1") { ?>target="_parent"<?php
            } ?>><?php echo $src_name ?></a>:<?=$src_port." ".$src_img?>
				<span class="tooltip">
					<span class="top"></span>
					<span class="middle ne11"><br><?php echo $src_title ?><br>
					</span>
					<span class="bottom"></span>
				</span>
				</div>
		</td>
        <td bgcolor="#eeeeee" nowrap>
            <?php
        //echo "<a href=\"$dst_link\" onMouseOver=\"showhint('$dst_title', this, event, '200px')\" target=\"_blank\">$dst_name</a>:$dst_port $dst_img"; ?>
		<div class="balloon">
				<a href="<?php echo $dst_link ?>" <?php
            if (GET('box') == "1") { ?>target="_parent"<?php
            } ?>><?php echo $dst_name ?></a>:<?=$dst_port." ".$dst_img?>
				<span class="tooltip">
					<span class="top"></span>
					<span class="middle ne11"><br><?php echo $dst_title ?><br>
					</span>
					<span class="bottom"></span>
				</span>
				</div>
		</td>
        <!-- src & dst hosts -->

        <td><?php
        echo $alarm->get_rule_level() ?></td>
      </tr>

<?php
        if ($highest_rule_level == 0) $highest_rule_level = $alarm->get_rule_level();
        // Alarm summary
        if ((!$show_all) or ($risk > 1)) {
            $summary = Alarm::get_alarm_stats($conn, $backlog_id, $aid);
            $summ_count = $summary["count"];
            $summ_event_count+= $summ_count;
            $summ_dst_ips = $summary["dst_ips"];
            $summ_types = $summary["types"];
            $summ_dst_ports = $summary["dst_ports"];
            echo "
            <tr>
            <td colspan=\"3\"></td>
            <td colspan=\"6\">
              <b>" . gettext("Alarm Summary") . "</b> [ ";
            printf(gettext("Total Events: %d") , $summ_count);
            echo "&nbsp;-&nbsp;";
            printf(gettext("Unique Dst IPAddr: %d") , $summ_dst_ips);
            echo "&nbsp;-&nbsp;";
            printf(gettext("Unique Types: %d") , $summ_types);
            echo "&nbsp;-&nbsp;";
            printf(gettext("Unique Dst Ports: %d") , $summ_dst_ports);
            echo " ] ";
            if ($conf->get_conf("have_scanmap3d")) {
                echo "
              - [ <a href=\"visualize.php?backlog_id=$backlog_id\"> " . gettext("Visualize alarm") . " </a> ]
              ";
            }
            echo "
            </td>
        ";
        }
?>


<?php
    } /* foreach alarm_list */
?>
<tr>
<td colspan="9" bgcolor="#eeeeee">
<font color="black" style="font-size: 9px;"><?php echo _("Total events matched after highest rule level, before timeout:"); ?> <?php echo $summary["total_count"] - $summ_event_count; ?></font>
</td>
</tr>
<?php
} /* if alarm_list */
?>
    </table>
<br/>
<center>
<a href="../directive_editor/viewer/index.php?level=1&directive=<?php echo $master_alarm_sid; ?>" target="_blank">View/Edit current directive definition</a>
</center>

<?php
if ($have_scanmap) fclose($backlog_file);
$db->close($conn);
?>

</body>
</html>
