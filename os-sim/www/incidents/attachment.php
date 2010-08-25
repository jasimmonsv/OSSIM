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
require_once 'classes/Session.inc';
Session::logcheck("MenuIncidents", "IncidentsIncidents");
require_once ('ossim_db.inc');
require_once ('classes/Incident_file.inc');
require_once ('classes/Incident_file.inc');
require_once ('classes/Security.inc');
$id = intval(GET('id'));
ossim_valid($id, OSS_NULLABLE, OSS_ALPHA, OSS_SCORE, 'illegal:' . _("id"));
if (ossim_error()) {
    die(ossim_error());
}
if (!empty($id)) {
    $db = new ossim_db();
    $conn = $db->connect();
    if ($files = Incident_file::get_list($conn, "WHERE id = $id")) {
        $type = $files[0]->get_type();
        $fname = $files[0]->get_name();
        header("Content-type: $type");
        header('Content-Disposition: attachment; filename="' . $fname . '"');
        print $files[0]->get_content();
    }
    $db->close($conn);
} else {
    echo _("Invalid ID");
}
?>


