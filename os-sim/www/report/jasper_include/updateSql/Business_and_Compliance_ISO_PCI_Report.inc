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
set_time_limit(900);
//ini_set('memory_limit', '128M');
require_once ('classes/Session.inc');
Session::logcheck("MenuReports", "ReportsReportServer");
require_once 'classes/Security.inc';

//$month = (GET('Month')!="") ? intval(GET('Month')) : intval(date("m"));
$year = (GET('Year')!="") ? intval(GET('Year')) : intval(date("Y"));
ossim_valid($month, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Month"));
ossim_valid($year, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Year"));
if (ossim_error()) {
    die(_("Error"));
}
//
require_once ('ossim_db.inc');
$db = new ossim_db();
$conn = $db->connect();
$user = Session::get_session_user();
$inserts = array();

if (!Session::am_i_admin()) {

    $srcs = array("0.0.0.0");
    $dsts = array("0.0.0.0");

    // SSI
    $sql = "SELECT source,destination FROM datawarehouse.ssi WHERE year=$year"; // AND month=$month";
    //echo $sql;

    if (!$rs = & $conn->Execute($sql)) {
        print $conn->ErrorMsg();
        return;
    }
    // test perms for source or destination ips
    while (!$rs->EOF) {
        $ip_src = $rs->fields["source"];
        $ip_dst = $rs->fields["destination"];
        if (isset($srcs[$ip_src]) || Session::hostAllowed($conn,$ip_src)) {
            $srcs[$ip_src]++;
        }
        if (isset($dsts[$ip_dst]) || Session::hostAllowed($conn,$ip_dst)) {
            $dsts[$ip_dst]++;
        }
        $rs->MoveNext();
    }
    $inserts[] = "INSERT INTO datawarehouse.ssi_user SELECT *,'$user' FROM datawarehouse.ssi WHERE year=$year
        AND (source IN ('".implode("','",array_keys($srcs))."') OR destination IN ('".implode("','",array_keys($dsts))."'))"; //  AND month=$month

    $sql = "SELECT source,destination FROM datawarehouse.incidents_ssi WHERE year=$year"; // AND month=$month
    //echo $sql;

    // INCIDENTS_SSI
    if (!$rs = & $conn->Execute($sql)) {
        print $conn->ErrorMsg();
        return;
    }
    // test perms for source or destination ips
    while (!$rs->EOF) {
        $ip_src = $rs->fields["source"];
        $ip_dst = $rs->fields["destination"];
        if (isset($srcs[$ip_src]) || Session::hostAllowed($conn,$ip_src)) {
            $srcs[$ip_src]++;
        }
        if (isset($dsts[$ip_dst]) || Session::hostAllowed($conn,$ip_dst)) {
            $dsts[$ip_dst]++;
        }
        $rs->MoveNext();
    }
    $inserts[] = "INSERT INTO datawarehouse.incidents_ssi_user SELECT *,'$user' FROM datawarehouse.incidents_ssi WHERE year=$year
        AND (source IN ('".implode("','",array_keys($srcs))."') OR destination IN ('".implode("','",array_keys($dsts))."'))"; //  AND month=$month

} else {

    $inserts[] = "INSERT INTO datawarehouse.ssi_user SELECT *,'$user' FROM datawarehouse.ssi WHERE year=$year"; // AND month=$month
    $inserts[] = "INSERT INTO datawarehouse.incidents_ssi_user SELECT *,'$user' FROM datawarehouse.incidents_ssi WHERE year=$year"; //  AND month=$month



}
// delete first and fill only with permited data
$conn->Execute("DELETE FROM datawarehouse.ssi_user WHERE user='$user'");
$conn->Execute("DELETE FROM datawarehouse.incidents_ssi_user WHERE user='$user'");
foreach($inserts as $insert) {
    //print_r($insert);
    if (!$conn->Execute($insert)) {
        print $conn->ErrorMsg();
        return;
    }
}
echo "OK";
//
$db->close($conn);
?>