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

require_once 'ossim_db.inc';
require_once 'classes/Security.inc';
require_once 'classes/Plugin_reference.inc';
require_once 'classes/Plugin.inc';
require_once 'classes/Plugin_sid.inc';

$num = GET('num');
$plugin_id = GET('plugin_id');
ossim_valid($num, OSS_DIGIT);
ossim_valid($plugin_id, OSS_DIGIT);
if (ossim_error()) {
    die(ossim_error());
}
if ($plugin_id == "") exit;

$db = new ossim_db();
$conn = $db->connect();

$plugin_list = Plugin_sid::get_list($conn, "WHERE plugin_id=$plugin_id ORDER BY name", 0);
?>
<?=($num==2) ? "Reference" : "Plugin"?> SID: 
<select id="sidajax<?=$num?>" onchange="document.frules.plugin_sid<?=$num?>.value=this.value<? if (GET('manage')) { ?>;load_refs()<? } ?>" style="width:200px">
<option value="">Select <?=($num==2) ? "Reference" : "Plugin"?> SID
<?
foreach($plugin_list as $plugin) {
?>
<option value="<?=$plugin->get_sid()?>"><?=$plugin->get_name()?>
<?
}
?>
</select>
