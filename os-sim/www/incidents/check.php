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
header('Content-Type: text/xml');
require_once 'classes/Session.inc';
Session::logcheck("MenuIncidents", "IncidentsIncidents");
require_once 'ossim_db.inc';
require_once 'classes/Incident.inc';
require_once 'classes/Security.inc';
$q = GET('q');
ossim_valid($q, OSS_NULLABLE, OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_PUNC, OSS_AT, 'illegal:' . _("q"));
if (ossim_error()) {
    die(ossim_error());
}
$db = new ossim_db();
$conn = $db->connect();
$countquery = OssimQuery("SELECT count(*) as count from incident_ticket
      where description like \"%$q%\"");
$query = OssimQuery("SELECT description from incident_ticket where
      description like \"%$q%\"");
if (!$rs = & $conn->Execute($countquery)) {
    print $conn->ErrorMsg();
} else {
    $num = $rs->fields["count"];
    if ($num == 0) {
?>
<response>
<method>0</method>
<result>no results</result>
</response>
<?php
        exit;
    }
    if (!$rs = & $conn->Execute($query)) {
        print $conn->ErrorMsg();
    } else {
?>
<response>
                <?php
        while (!$rs->EOF) {
?>
<method><?php
            echo $num; ?></method>
<result><?php
            echo $rs->fields["description"]; ?></result>
                    <?php
            $rs->MoveNext();
        }
?>
</response>
            <?php
        exit;
    }
}
// Shouldn't be reached

?>
<response>
  <method>0</method>
  <result>no results</result>
</response>

