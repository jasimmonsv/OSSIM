<?php
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
require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
Session::logcheck("MenuPolicy", "5DSearch");
require_once ('classes/Host.inc');
require_once 'ossim_db.inc';
require_once 'ossim_conf.inc';

include ("functions.php");

// Database Object
$db = new ossim_db();
$conn = $db->connect();

$rules = get_rulesconfig();

$type = GET('type');
$subtype = GET('subtype');
ossim_valid($type, OSS_ALPHA, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("type"));
ossim_valid($subtype, OSS_ALPHA, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("subtype"));
if (ossim_error()) {
    die(ossim_error());
}

$sql = $rules[$type][$subtype]['list'];
if (!$rs = & $conn->Execute($sql)) {
	echo $conn->ErrorMsg();
} else {
	$i = 0;
	while (!$rs->EOF) {
		if ($rs->fields[0] != "") {
			// Filter by sensor user perms
			$aux = str_replace(",","",$rs->fields[0]);
			if (preg_match("/^\d+\.\d+\.\d+\.\d+$/",$aux)) {
				if (!Session::hostAllowed($conn,$aux)) {
					$rs->MoveNext();
					continue;
				}
			}
			
			if ($i) echo ",";
			echo str_replace(",","",$rs->fields[0]);
			if ($rules[$type][$subtype]['match'] == "fixed" || $rules[$type][$subtype]['match'] == "concat" || $rules[$type][$subtype]['match'] == "fixedText") {
				if ($rs->fields[1] != "") echo ";".str_replace(",","",$rs->fields[1]); //id;name
				else echo ";".str_replace(",","",$rs->fields[0]); //name;name
			}
			$rs->MoveNext();
			$i++;
		}
	}
}
?>
