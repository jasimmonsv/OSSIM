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
require_once 'classes/Security.inc';
$plugin_id = GET('plugin_id');
$plugin_sid = GET('plugin_sid');
$delete_ref_id = GET('delete_ref_id');
$newref_type_id = GET('newref_type');
$newref_value = GET('newref_value');
ossim_valid($plugin_id, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("plugin_id"));
ossim_valid($plugin_sid, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("plugin_sid"));
ossim_valid($delete_ref_id, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("delete_ref_id"));
ossim_valid($newref_type_id, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("newref_type_id"));
ossim_valid($newref_value, OSS_NULLABLE, OSS_DIGIT, OSS_ALPHA, OSS_SCORE, OSS_SPACE, OSS_URL, OSS_COLON, 'illegal:' . _("newref_value"));
if ($plugin_sid == "" || $plugin_id == "") exit;
if (ossim_error()) {
    die(ossim_error());
}

include ("base_conf.php");
include_once ($BASE_path."includes/base_db.inc.php");
include_once ("$BASE_path/includes/base_state_query.inc.php");
include_once ("$BASE_path/includes/base_state_common.inc.php");
/* Connect to the Alert database */
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);
$qs = new QueryState();

if ($delete_ref_id != "") {
	$sql = "DELETE FROM sig_reference WHERE plugin_id=$plugin_id AND plugin_sid=$plugin_sid AND ref_id=$delete_ref_id";
	$qs->ExecuteOutputQueryNoCanned($sql, $db);
}
if ($newref_type_id != "" && $plugin_id != "" && $plugin_sid != "" && $newref_value != "") {
	$sql = "INSERT INTO reference (ref_system_id,ref_tag) VALUES ($newref_type_id,\"$newref_value\")";
	$qs->ExecuteOutputQueryNoCanned($sql, $db);
	$sql = "INSERT INTO sig_reference (plugin_id,plugin_sid,ref_id) VALUES ($plugin_id,$plugin_sid,LAST_INSERT_ID())";
	$qs->ExecuteOutputQueryNoCanned($sql, $db);
}

$sql = "SELECT reference.ref_tag,reference_system.ref_system_id,reference_system.ref_system_name,reference.ref_id FROM reference,reference_system,sig_reference WHERE sig_reference.plugin_id=$plugin_id AND sig_reference.plugin_sid=$plugin_sid AND sig_reference.ref_id=reference.ref_id AND reference.ref_system_id=reference_system.ref_system_id";
$result = $qs->ExecuteOutputQuery($sql, $db);
if ($myrow = $result->baseFetchRow()) {
?>
<table width="100%" class="transparent">
	<tr>
		<th colspan="4"><?=_("References found")?></th>
	</tr>
	<tr>
		<td width="100" nowrap><img src="manage_references_icon.php?id=<?=$myrow['ref_system_id']?>" border="0"> <?=$myrow['ref_system_name']?></td>
		<td><?=$myrow['ref_tag']?></td>
		<td width="20"><a href="javascript:;" onclick="ref_delete_plugin(<?=$plugin_id?>,<?=$plugin_sid?>,<?=$myrow['ref_id']?>);return false;"><img src="../pixmaps/tables/table_row_delete.png" border="0"></a></td>
	</tr>
<? while ($myrow = $result->baseFetchRow()) { ?>
	<tr>
		<td width="50" nowrap><img src="manage_references_icon.php?id=<?=$myrow['ref_system_id']?>" border="0"> <?=$myrow['ref_system_name']?></td>
		<td><?=$myrow['ref_tag']?></td>
		<td width="20"><a href="javascript:;" onclick="ref_delete_plugin(<?=$plugin_id?>,<?=$plugin_sid?>,<?=$myrow['ref_id']?>);return false;"><img src="../pixmaps/tables/table_row_delete.png" border="0"></a></td>
	</tr>
<? } ?>
</table>
<? } ?>