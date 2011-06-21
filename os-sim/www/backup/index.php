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
require_once 'classes/Session.inc';
require_once 'classes/Security.inc';
Session::logcheck("MenuConfiguration", "ToolsBackup");
require_once 'classes/Util.inc';
require_once 'ossim_db.inc';
require_once ('classes/Backup.inc');
$conf = $GLOBALS["CONF"];
$data_dir = $conf->get_conf("data_dir");
$backup_dir = $conf->get_conf("backup_dir");
$version = $conf->get_conf("ossim_server_version", FALSE);
$pro = (preg_match("/pro|demo/i",$version)) ? true : false;
//$backup_dir = "/root/pruebas_backup";

$db = new ossim_db();
$conn = $db->snort_connect();
$conn_ossim = $db->connect();
$insert = Array();
$delete = Array();
if (!is_dir($backup_dir)) {
    die(ossim_error(_("Could not access backup dir") . ": <b>$backup_dir</b>"));
}
$dir = dir($backup_dir);
$query = OssimQuery("SELECT DISTINCT DATE_FORMAT(timestamp, '%Y%m%d') as day FROM acid_event ORDER BY timestamp DESC");
if (!$rs = $conn->Execute($query)) {
    print 'error: ' . $conn->ErrorMsg() . '<BR>';
    exit;
}
// Delete
while (!$rs->EOF) {
    $delete[] = $rs->fields["day"];
    $rs->MoveNext();
}
// Insert
while ($file = $dir->read()) {
    if (preg_match("/^insert\-(.+)\.sql\.gz/", $file, $found)) {
        if (!in_array($found[1], $delete)) $insert[] = $found[1];
    }
}
rsort($insert);
$dir->close();

if($pro) {
    // users
    $users = array();
    if(Session::am_i_admin()) {
        $users_list = Session::get_list($conn_ossim);
        foreach ($users_list as $user_data) {
            $users[] = $user_data->login;
        }
    }
    else {
        $users_list = Acl::get_my_users($conn_ossim,Session::get_session_user());
        foreach ($users_list as $user_data) {
            $users[] = $user_data["login"];
        }
    }
    // entities
    list($entities_all,$num_entities) = Acl::get_entities($conn_ossim);
    list($entities_admin,$num) = Acl::get_entities_admin($conn_ossim, Session::get_session_user());
    $entities_list = array_keys($entities_admin);
}

$db->close($conn);
$db->close($conn_ossim);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<title><?=_('Backup')?></title>
 		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  		<meta http-equiv="Pragma" content="no-cache">
  		<link rel="stylesheet" type="text/css" href="../style/style.css"/>
		<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  		<script language="javascript">
  			function boton (form, act) {
  				form.perform.value = act;
  				form.submit();
  			}
  			function reload_backup() {
				document.location.href="index.php";
  			}
  		</script>
  	</head>
  	<body>
		<?php
include ("../hmenu.php"); ?>
  		<center>
        <?
        if ($message!="") {
            echo "<b><span style='color:#FFA500'>".$message."</span></b><br><br>";
        }
        ?>
  		<form name="backup" action="launch.php" target="process_iframe" method="post">
  	  	<table>
  			<tr>
  				<th colspan="3"><?php
echo gettext("Backup Manager"); ?></th>
			</tr>
  			<tr>
  				<th><?php
echo gettext("Dates to Restore"); ?></th>
  				<td width="10" class="nobborder">&nbsp;</td>
  				<th><?php
echo gettext("Dates in Database"); ?></th>
  			</tr>
  			<tr>
  				<td class="nobborder" style="text-align:center;" valign="top">
  				<table class="transparent">
  				<tr><td class="nobborder">
		<select name="insert[]" size="<?php echo ($pro) ? "7" : "10" ?>" multiple>
<?php
if (count($insert) > 0) {
    foreach($insert as $insert_item) {
?>
       <option value="<?php echo $insert_item
?>">&nbsp;&nbsp;<?php echo preg_replace("/(\d\d\d\d)(\d\d)(\d\d)/", "\\3-\\2-\\1", $insert_item) ?>&nbsp;&nbsp;</option>
<?php
    }
} else { ?>
	<option size="100" disabled>&nbsp;&nbsp;--&nbsp;<?php echo _("NONE") ?>&nbsp;--&nbsp;&nbsp;</option>
<?php
} ?>
	   </select></td></tr>
	   <?php if ($pro) { ?>
	   <tr><td class="nobborder">
	   <select name="user">
	   <option value="">- <?php echo _("All Users") ?> -</option>
	   <?php foreach ($users as $user) { ?>
	   <option value="<?php echo $user ?>"><?php echo $user ?></option>
	   <?php } ?>
	   </select>
	   </td></tr>
	   <tr><td class="nobborder">
	   <select name="entity">
	   <option value="">- <?php echo _("All Entities") ?> -</option>
	   <?php
        foreach ( $entities_all as $entity ) if(Session::am_i_admin() || (Acl::am_i_proadmin() && in_array($entity["id"], $entities_list))) { ?>
            <option value="<?php echo $entity["id"] ?>"><?php echo $entity["name"] ?></option>
        <?php
        } ?>
	   </select>
	   </td></tr>
	   <?php } ?></table>
  				</td>
				<td class="nobborder">&nbsp;</td>
				<td class="nobborder" style="text-align:center;padding-top:3px" valign="top">
		<select name="delete[]" size="10" multiple>
<?php
if (count($delete) > 0) {
    foreach($delete as $delete_item) {
?>
		<option size="100" value="<?php echo $delete_item
?>">&nbsp;&nbsp;<?php echo preg_replace("/(\d\d\d\d)(\d\d)(\d\d)/", "\\3-\\2-\\1", $delete_item) ?>&nbsp;&nbsp;</option>
<?php
    }
} else { ?>
		<option size="100" disabled>&nbsp;&nbsp;--&nbsp;<?php echo _("NONE") ?>&nbsp;--&nbsp;&nbsp;</option>
<?php
} ?>
	   </select>
				</td>
  			</tr>
  			<tr>
  				<td class="nobborder" style="text-align:center">
  					<input type="button" class="button" name="insertB" value="<?php echo gettext("Restore"); ?>" type="submit" onclick="boton(this.form, 'insert')" <?php echo ($isDisabled) ? "disabled" : "" ?> />
  				</td>
  				<td class="nobborder" style="text-align:center">&nbsp;</td>
  				<td class="nobborder" style="text-align:center">
  					<input type="button" class="button" name="deleteB" value="<?php echo gettext("Purge"); ?>" type="submit" onclick="boton(this.form, 'delete')"  <?php echo ($isDisabled) ? "disabled" : "" ?> />
  				</td>
  			</tr>
  			<?php if ($pro) { ?>
  			<tr><td colspan="3" class="nobborder"><table class="transparent"><tr><td class="nobborder"><input type="checkbox" name="nomerge" value="nomerge" checked="checked"></input></td><td class="nobborder"><?php echo _("Restore into a new database") ?></td></tr></table></td></tr>
  			<?php } ?>
  		</table>
  		<input type="hidden" name="perform" value="">
  		</form>
  		<table class="transparent">
  			<tr><td class="nobborder"><iframe name="process_iframe" id="process_iframe" src="launch.php" height="50" frameborder="0"></iframe></td></tr>
  		</table>
		<table align="center">
			<tr>
				<th colspan="5"><?php
echo gettext("Backup Events"); ?></th>
			</tr>
			<tr>
				<th><?php
echo gettext("User"); ?></th>
				<th><?php
echo gettext("Date"); ?></th>
				<th><?php
echo gettext("Action"); ?></th>
				<th><?php
echo gettext("Status"); ?></th>
				<th><?php
echo gettext("Percent"); ?></th>
			</tr>
<?php
$db1 = new ossim_db();
$conn1 = $db1->connect();
$cmd = "ps ax | grep restoredb.pl | grep -v grep";
$output = explode("\n",`$cmd`);
if (count($output) == 1 && $output[0] == "") {
	// Posibily failed
	$conn1->Execute("UPDATE restoredb_log SET status=-1 WHERE status=1");
}
$query = OssimQuery("SELECT * FROM restoredb_log ORDER BY id DESC LIMIT 10");
if (!$rs1 = $conn1->Execute($query)) {
    print 'error: ' . $conn1->ErrorMsg() . '<BR>';
    exit;
}
$results = array();
while (!$rs1->EOF) {
	$results[] = $rs1->fields;
	$rs1->MoveNext();
}
$db1->close($conn1);
if (count($results) < 1) { ?>
			<tr>
				<td colspan="6"><?=_("No Events found")?></td>
			</tr>
<? } else {
foreach ($results as $rs1) {
?>
			<tr>
				<td><?php echo $rs1["users"] ?></td>
				<td><?php echo Util::timestamp2date($rs1["date"]) ?></td>
				<td><?php echo str_replace(",",", ",$rs1["data"]) ?></td>
	<?php
    if ($rs1["status"] == 1) { ?>
				<td><font color="orange"><b><?php
        echo gettext("Running"); ?></b></font></td>
	<?php
    } elseif ($rs1['status'] == -1) { ?>
    			<td><font color="red"><b><?php
        echo gettext("Failed"); ?></b></font></td>
    <?php
    } else { ?>
				<td><font color="green"><b><?php
        echo gettext("Done"); ?></b></font></td>
	<?php
    } ?>
				<td><?php echo $rs1["percent"] ?></td>
			</tr>
<?php
}
}
?>
		</table>
		</center>
  	</body>
</html>
