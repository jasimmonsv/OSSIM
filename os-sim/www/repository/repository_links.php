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
require_once ("classes/Plugin.inc");
require_once ("classes/Plugin_sid.inc");
require_once ("classes/Session.inc");
require_once ("ossim_conf.inc");
require_once ("ossim_db.inc");

Session::logcheck("MenuIncidents", "Osvdb");
$user = $_SESSION["_user"];

// Get upload dir from ossim config file

$conf        = $GLOBALS["CONF"];
$link_type   = (GET('linktype') != "") ? GET('linktype') : "host";
$id_document = (GET('id_document') != "") ? GET('id_document') : ((POST('id_document') != "") ? POST('id_document') : "");

if (!ossim_valid($id_document, OSS_DIGIT, 'illegal:' . _("id_document"))) exit;
// DB connect


$db   = new ossim_db();
$conn = $db->connect();
$pid  = intval(GET('pid'));

// New link on relationships
if (GET('newlinkname') != "" && GET('insert') == "1") 
{
    if ($link_type=="directive") 
	{
        $idd = intval(GET('newlinkname'));
        Repository::insert_relationships($conn, $id_document, $idd, $link_type, $idd);
    } 
	else 
	{
        $aux = explode("####", GET('newlinkname'));
        Repository::insert_relationships($conn, $id_document, $aux[0], $link_type, $aux[1]);
        if ($link_type == "plugin_sid")  Repository::insert_snort_references($conn, $id_document, $aux[1], $aux[0]);
    }
}

// Delete link on relationships
if (GET('key_delete') != "") 
{
    $key = mb_convert_encoding(urldecode(GET('key_delete')), 'HTML-ENTITIES', 'UTF-8');
						
	Repository::delete_relationships($conn, $id_document, $key);
    if ($link_type == "plugin_sid") 
		Repository::delete_snort_references($conn, $id_document);
}

$document = Repository::get_document($conn, $id_document);
$rel_list = Repository::get_relationships($conn, $id_document);
if ($link_type != "directive" && $link_type != "plugin_sid")
    list($hostnet_list, $num_rows) = Repository::get_hostnet($conn, $link_type);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" CONTENT="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<style type='text/css'>
				
		.ossim_error, .ossim_success { width: auto;}
		
		body { margin: 0px;}
		
		.action{
			margin: auto;
			text-align: center;
			paddding: 5px 0px;
			width: 50px;
		}
		
		.t_style {
			margin-top: 10px;
			width: 100%;
			margin: auto;
			text-align: center;
		}
		
	</style>
</head>

<body>
<table width="90%" class="transparent" align='center'>
	<?php
if (count($rel_list) > 0) 
{ 
	?>
	<tr>
		<td class='nobborder'>
			<table class="noborder transparent t_style" align="center">
				<tr>
					<th style='paddding: 5px 0px;'><?php echo _("Linked to")?></th>
					<th style='paddding: 5px 0px;'><?php echo _("Type")?></th>
					<th class='action'><?php echo _("Action")?></th>
				</tr>
				<?php
				
			foreach($rel_list as $rel) 
			{
				if ($rel['type'] == "host")           $page = "../report/index.php?host=" . $rel['key'];
				elseif ($rel['type'] == "net")        $page = "../net/net.php";
				elseif ($rel['type'] == "host_group") $page = "../host/hostgroup.php";
				elseif ($rel['type'] == "net_group")  $page = "../net/netgroup.php";
				elseif ($rel['type'] == "incident")   $page = "../incidents/incident.php?id=" . $rel['key'];
				elseif ($rel['type'] == "directive")  $page = "../directive_editor/index.php?hmenu=Directives&smenu=Directives&level=1&directive=" . $rel['key'];
				elseif ($rel['type'] == "plugin_sid") $page = "../forensics/base_qry_main.php?clear_allcriteria=1&search=1&sensor=&sip=&plugin=&ossim_risk_a=+&hmenu=Forensics&smenu=Forensics&submit=Signature&search_str=" . urlencode(Plugin_sid::get_name_by_idsid($conn,$rel['key'],$rel['name']));
			?>
				<tr>
					<td class="left nobborder"><a href="<?php echo $page ?>" target="main"><?php echo ($rel['type'] == "plugin_sid") ? $rel['key']." (".$rel['name'].")" : $rel['name'] ?></a></td>
					<td class="left nobborder"><?php echo ($rel['type'] == "incident") ? "ticket" : $rel['type'] ?></td>
					<td class="action noborder">
						<a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>?id_document=<?php echo $id_document ?>&key_delete=<?php echo urlencode($rel['key'])?>&linktype=<?=urlencode($rel['type'])?>">
						<img src="images/del.gif" border="0"/></a>
					</td>
				</tr>
				<?php
			} ?>
			</table>
		</td>
	</tr>
	<?php
} 
?>

<form name="flinks" method="GET">
<input type="hidden" name="id_document" value="<?php echo $id_document ?>"/>
<input type="hidden" name="insert" value="0"/>
	<tr>
		<td class='nobborder'>
			<table class="noborder transparent t_style" align="center">
				<tr>
					<th style='paddding: 5px 0px;'><?php echo _("Link Type")?></th>
					<th style='paddding: 5px 0px;'><?php echo _("Value")?></th>
					<th class='action'><?php echo _("Action")?></th>
				</tr>
				<tr>
					<td valign="top" class="nobborder">
						<select name="linktype" onchange="document.flinks.submit();">
						<?php
							$link_types = array(
								"directive"  => "Directive",
								"host"       => "Host",
								"host_group" => "Host Group",
								"incident"   => "Incident",
								"net"        => "Net",
								"net_group"  => "Net Group",
								"plugin_sid" => "Plugin sid",
							);
							
							foreach ($link_types as $k => $v)
							{
								$selected = ( $k == $link_type ) ? "selected='selected'" : "";
								echo "<option value='$k' $selected>$v</option>";
							}
						?>
						</select>
					</td>
					
					<td valign="top" class="nobborder">
					<?php 
						if ($link_type == "directive") 
						{ 
							?>
							<input type="text" name="newlinkname" style='width:99%'/>
							<?php 
						} 
						elseif ($link_type == "plugin_sid") 
						{ 
                            $plugins = Plugin::get_list($conn,"ORDER BY name");
                            echo "<select name='pid' onchange='document.flinks.submit()'>";
                            foreach ($plugins as $plugin) 
							{
                                $sel = ( $plugin->get_id()==$pid ) ? "selected='selected'" : "";
                                echo "<option value='".$plugin->get_id()."' $sel>".$plugin->get_name();
                            }
                            echo "</select><br>";
                            
							if ( $pid !="" && $pid !="0" ) 
							{
                                $sids = Plugin_sid::get_list($conn,"where plugin_id=$pid");
                                echo "<select name='newlinkname' style='width:200px; margin-top:5px;'>";
                                
								foreach ($sids as $sid) {
                                    echo "<option value='".$sid->get_sid()."####$pid'>".$sid->get_name();
                                }
                                echo "</select>";
                            }
						} 
						else 
						{ 
							?>
							<select name="newlinkname" style="width:300px">
								<?php 
								foreach($hostnet_list as $hostnet) { ?>
									<option value="<?php echo $hostnet['name'] ?>####<?php echo $hostnet['key'] ?>"><?php echo $hostnet['name'] ?>
								<?php } ?>
							</select>
							<?php 
						} 
						?>
					</td>
					<td valign="top" class="nobborder center"><input class="lbutton" type="button" value="<?php echo _("Link")?>" onclick="document.flinks.insert.value='1';document.flinks.submit();"></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr><td class="nobborder center"><input class="button" type="button" onclick="parent.document.location.href='index.php'" value="<?php echo _("Finish")?>"></td></tr>
</table>
</form>

</body>
</html>
<?php $db->close($conn); ?>
