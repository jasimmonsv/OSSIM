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
require_once ("classes/Repository.inc");
require_once("classes/Plugin.inc");
require_once("classes/Plugin_sid.inc");
// menu authentication
require_once ('classes/Session.inc');
Session::logcheck("MenuIncidents", "Osvdb");
$user = $_SESSION["_user"];
// get upload dir from ossim config file
require_once 'ossim_conf.inc';
$conf = $GLOBALS["CONF"];
$link_type = (GET('linktype') != "") ? GET('linktype') : "host";
$id_document = (GET('id_document') != "") ? GET('id_document') : ((POST('id_document') != "") ? POST('id_document') : "");
if (!ossim_valid($id_document, OSS_DIGIT, 'illegal:' . _("id_document"))) exit;
// DB connect
require_once ("ossim_db.inc");
$db = new ossim_db();
$conn = $db->connect();
$pid = intval(GET('pid'));
// New link on relationships
if (GET('newlinkname') != "" && GET('insert') == "1") {
    if ($link_type=="directive") {
        $idd = intval(GET('newlinkname'));
        Repository::insert_relationships($conn, $id_document, $idd, $link_type, $idd);
    } else {
        $aux = explode("####", GET('newlinkname'));
        Repository::insert_relationships($conn, $id_document, $aux[0], $link_type, $aux[1]);
        if ($link_type == "plugin_sid")  Repository::insert_snort_references($conn, $id_document, $aux[1], $aux[0]);
    }
}
// Delete link on relationships
if (GET('key_delete') != "") {
    Repository::delete_relationships($conn, $id_document, GET('key_delete'));
    if ($link_type == "plugin_sid")  Repository::delete_snort_references($conn, $id_document);
}
$document = Repository::get_document($conn, $id_document);
$rel_list = Repository::get_relationships($conn, $id_document);
if ($link_type != "directive" && $link_type != "plugin_sid")
    list($hostnet_list, $num_rows) = Repository::get_hostnet($conn, $link_type);
?>
<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>

<body style="margin:0">
<table width="100%" class="transparent">
	<?php
if (count($rel_list) > 0) { ?>
	<tr>
		<td>
			<table class="noborder" align="center">
				<tr>
					<td class="kdb"><?=("Linked to")?></td>
					<td class="kdb"><?=("Type")?></td>
					<td class="kdb"><?=("Action")?></td>
				</tr>
				<?php
    foreach($rel_list as $rel) {
        if ($rel['type'] == "host") $page = "../report/index.php?host=" . $rel['key'];
        if ($rel['type'] == "net") $page = "../net/net.php";
        if ($rel['type'] == "host_group") $page = "../host/hostgroup.php";
        if ($rel['type'] == "net_group") $page = "../net/netgroup.php";
        if ($rel['type'] == "incident") $page = "../incidents/incident.php?id=" . $rel['key'];
        if ($rel['type'] == "directive") $page = "../directive_editor/index.php?hmenu=Directives&smenu=Directives&level=1&directive=" . $rel['key'];
        if ($rel['type'] == "plugin_sid") $page = "../forensics/base_qry_main.php?clear_allcriteria=1&search=1&sensor=&sip=&plugin=&ossim_risk_a=+&hmenu=Forensics&smenu=Forensics&submit=Signature&search_str=" . urlencode(Plugin_sid::get_name_by_idsid($conn,$rel['key'],$rel['name']));
?>
				<tr>
					<td class="nobborder"><a href="<?php echo $page ?>" target="main"><?php echo ($rel['type'] == "plugin_sid") ? $rel['key']." (".$rel['name'].")" : $rel['name'] ?></a></td>
					<td class="nobborder"><?php echo ($rel['type'] == "incident") ? "ticket" : $rel['type'] ?></td>
					<td class="noborder"><a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>?id_document=<?php echo $id_document ?>&key_delete=<?php echo $rel['key'] ?>&linktype=<?=urlencode($rel['type'])?>"><img src="images/del.gif" border="0"></a></td>
				</tr>
				<?php
    } ?>
			</table>
		</td>
	</tr>
	<?php
} ?>
<form name="flinks" method="GET">
<input type="hidden" name="id_document" value="<?php echo $id_document ?>">
<input type="hidden" name="insert" value="0">
	<tr>
		<td>
			<table class="noborder" align="center">
				<tr>
					<td class="kdb"><?=("Link Type")?></td>
					<td class="kdb"><?=("Value")?></td>
					<td></td>
				</tr>
				<tr>
					<td valign="top" class="nobborder">
						<select name="linktype" onchange="document.flinks.submit();">
						<option value="directive"<?php
if ($link_type == "directive") echo " selected" ?>><?=_("Directive")?>
						<option value="host"<?php
if ($link_type == "host") echo " selected" ?>><?=_("Host")?>
						<option value="host_group"<?php
if ($link_type == "host_group") echo " selected" ?>><?=_("Host Group")?>
						<option value="incident"<?php
if ($link_type == "incident") echo " selected" ?>><?=_("Ticket")?>
						<option value="net"<?php
if ($link_type == "net") echo " selected" ?>><?=_("Net")?>
						<option value="net_group"<?php
if ($link_type == "net_group") echo " selected" ?>><?=_("Net Group")?>
						<option value="plugin_sid"<?php
if ($link_type == "plugin_sid") echo " selected" ?>><?=_("Plugin sid")?>
						</select>
					</td>
					<td valign="top" class="nobborder">
					<?php if ($link_type == "directive") { ?>
						<input type="text" name="newlinkname">
					<?php } elseif ($link_type == "plugin_sid") { 
                            $plugins = Plugin::get_list($conn,"order by name");
                            echo "<select name='pid' onchange='document.flinks.submit()'><option value='0'> "._("Select a plugin");
                            foreach ($plugins as $plugin) {
                                $sel = ($plugin->get_id()==$pid) ? "selected" : "";
                                echo "<option value='".$plugin->get_id()."' $sel>".$plugin->get_name();
                            }
                            echo "</select><br>";
                            if ($pid!="" && $pid!="0") {
                                $sids = Plugin_sid::get_list($conn,"where plugin_id=$pid");
                                echo "<select name='newlinkname' style='width:200px'>";
                                foreach ($sids as $sid) {
                                    echo "<option value='".$sid->get_sid()."####$pid'>".$sid->get_name();
                                }
                                echo "</select>";
                            }
					   } else { ?>
						<select name="newlinkname" style="width:300px">
						<?php foreach($hostnet_list as $hostnet) { ?>
						<option value="<?php echo $hostnet['name'] ?>####<?php echo $hostnet['key'] ?>"><?php echo $hostnet['name'] ?>
						<?php } ?>
						</select>
                    <? } ?>
					</td>
					<td valign="top" class="nobborder"><input class="btn" type="button" value="<?=_("Link")?>" onclick="document.flinks.insert.value='1';document.flinks.submit();"></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr><td align="center"><input class="btn" type="button" onclick="parent.document.location.href='index.php'" value="<?=_("Finish")?>"></td></tr>
</table>
</form>
</body>
</html>
<?php
$db->close($conn);
?>
