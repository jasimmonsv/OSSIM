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
* - Host_netbios()
* - get_ip()
* - get_name()
* - get_wgroup()
* - get_list()
* Classes list:
* - Host_netbios
*/
require_once ("ossim_db.inc");
class Host_netbios {
    var $ip;
    var $name;
    var $wgroup;
    function Host_netbios($ip, $name, $wgroup) {
        $this->ip = $ip;
        $this->name = $name;
        $this->wgroup = $wgroup;
    }
    function get_ip() {
        return $this->ip;
    }
    function get_name() {
        return $this->name;
    }
    function get_wgroup() {
        return $this->wgroup;
    }
    function get_list($conn, $args = "") {
        require_once ('classes/Session.inc');
        $query = OssimQuery("SELECT * FROM host_netbios $args");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                if (Session::hostAllowed($conn, $rs->fields["ip"])) {
                    $list[] = new Host_netbios($rs->fields["ip"], $rs->fields["name"], $rs->fields["wgroup"]);
                }
                $rs->MoveNext();
            }
        }
        return $list;
    }
}
?>

