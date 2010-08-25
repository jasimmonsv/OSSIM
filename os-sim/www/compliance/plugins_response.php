<?
/*****************************************************************************
*
*    License:
*
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
Session::logcheck("MenuIntelligence", "ComplianceMapping");
require_once ('classes/Security.inc');
require_once 'classes/Compliance.inc';
require_once 'classes/Plugin_sid.inc';
require_once 'ossim_db.inc';
require_once 'ossim_conf.inc';
$db = new ossim_db();
$conn = $db->connect();

$ref = explode ("_",GET('ref'));

$is_pci = (GET('pci') != "") ? 1 : 0;

$groups = ($is_pci) ? PCI::get_groups($conn) : ISO27001::get_groups($conn);
$sids = $groups[$ref[0]]['subgroups'][$ref[1]]['SIDSS_Ref'];
ossim_valid($sids, OSS_DIGIT, ',', 'illegal:' . _("sids"));
if (ossim_error()) {
	die(ossim_error());
}
$plugin_list = Plugin_sid::get_list($conn,"WHERE plugin_id = 1505 AND sid in ($sids)");

if (count($plugin_list) > 0) {
?>
<table width="100%" align="center">
<? foreach ($plugin_list as $p) { ?>
<tr><td><?=$p->get_name()?></td></tr>
<? } ?>
</table>
<? } else { ?>
<table width="100%">
<tr><td class="nobborder" style="padding:10px;text-align:center"><b><?=_("No directives found")?></b></td></tr>
</table>
<? } ?>