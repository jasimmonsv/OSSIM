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
* - start_upgrade()
* Classes list:
* - upgrade_099rc4 extends upgrade_base
*/
require_once 'classes/Upgrade_base.inc';
/*
*/
class upgrade_099rc4 extends upgrade_base {
    // Normalize MAC (bad entry make problems in MAC anomalies)
    function start_upgrade() {
        $conn = & $this->conn;
        $snort = & $this->snort;
        $conn->StartTrans();
        $sql = "SELECT * FROM host_mac";
        if (!$rs = $conn->Execute($sql)) {
            die("Error was:<br>\n<b>" . $conn->ErrorMsg() . "</b>");
        }
        while (!$rs->EOF) {
            $mac = $rs->fields['mac'];
            if ($mac != "") {
                $ip = $rs->fields['ip'];
                $date = $rs->fields['date'];
                $sensor = $rs->fields['sensor'];
                $new_mac = strtoupper(vsprintf("%02s:%02s:%02s:%02s:%02s:%02s", split(":", $mac)));
                $sql = 'UPDATE host_mac SET mac=? WHERE ip=? AND date=? AND sensor=?';
                $params = array(
                    $new_mac,
                    $ip,
                    $date,
                    $sensor
                );
                $conn->Execute($sql, $params);
            }
            $rs->MoveNext();
        }
        $res = $conn->CompleteTrans();
        if (!$res) {
            die("Transacion failed: " . $conn->ErrorMsg());
        }
        /* Snort table changes */
        $sql = "ALTER TABLE ossim_event ADD COLUMN plugin_id INTEGER NOT NULL";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "ALTER TABLE ossim_event ADD COLUMN plugin_sid INTEGER NOT NULL";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "CREATE TABLE extra_data (
        sid             INT8 NOT NULL,
        cid             INT8 NOT NULL,
        filename        varchar(255),
        username        varchar(255),
        password        varchar(255),
        userdata1       varchar(255),
        userdata2       varchar(255),
        userdata3       varchar(255),
        userdata4       varchar(255),
        userdata5       varchar(255),
        userdata6       varchar(255),
        userdata7       varchar(255),
        userdata8       varchar(255),
        userdata9       varchar(255), 
        PRIMARY KEY (sid, cid)
);";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        return true;
    }
}
?>
