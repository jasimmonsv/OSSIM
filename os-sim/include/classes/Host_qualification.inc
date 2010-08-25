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
* - Host_qualification()
* - get_host_ip()
* - get_compromise()
* - get_attack()
* - get_ip_attack()
* - get_ip_compromise()
* - __get_global()
* - get_global_compromise()
* - get_global_attack()
* - get_list()
* - insert()
* - update()
* - delete()
* Classes list:
* - Host_qualification
*/
require_once ('classes/Net.inc');
require_once ('classes/Host.inc');
require_once ('classes/Session.inc');
require_once ("ossim_db.inc");
class Host_qualification {
    var $host_ip;
    var $compromise;
    var $attack;
    function Host_qualification($host_ip, $compromise, $attack) {
        $this->host_ip = $host_ip;
        $this->compromise = $compromise;
        $this->attack = $attack;
    }
    function get_host_ip() {
        return $this->host_ip;
    }
    function get_compromise() {
        return $this->compromise;
    }
    function get_attack() {
        return $this->attack;
    }
    function get_ip_attack($conn, $ip) {
        $sql = "SELECT * FROM host_qualification WHERE host_ip = ?";
        $params = array(
            $ip
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        } else {
            if (!$rs->EOF) return $rs->fields["attack"];
            else {
                /* there is no attack level for this host */
                return 0;
            }
        }
    }
    function get_ip_compromise($conn, $ip) {
        $sql = OssimQuery("SELECT * FROM host_qualification WHERE host_ip = ?");
        $params = array(
            $ip
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        } else {
            if (!$rs->EOF) return $rs->fields["compromise"];
            else {
                /* there is no compromise level for this host */
                return 0;
            }
        }
    }
    function __get_global($conn, $type) {
        if (($type != 'compromise') and ($type != 'attack')) return -1;
        $global = 0;
        $sql = "SELECT $type, host_ip FROM host_qualification";
        if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                if (Session::hostAllowed($conn, $rs->fields["host_ip"])) {
                    $value = $rs->fields[$type];
                    $asset = Host::ip_asset($conn, $rs->fields["host_ip"]);
                    if ($type == "compromise") {
                        $thresh = Host::ipthresh_c($conn, $rs->fields["host_ip"]);
                    } else {
                        $thresh = Host::ipthresh_a($conn, $rs->fields["host_ip"]);
                    }
                    //print ">> $type, " . $rs->fields["host_ip"] . ", $thresh, $asset, $value<br/>";
                    $real_val = ($value < ($thresh * $asset)) ? $value : ($thresh * $asset);
                    $global+= $real_val;
                }
                $rs->MoveNext();
            }
        }
        return $global;
    }
    function get_global_compromise($conn) {
        return Host_qualification::__get_global($conn, "compromise");
    }
    function get_global_attack($conn) {
        return Host_qualification::__get_global($conn, "attack");
    }
    function get_list($conn, $where = "", $order = "") {
        $query = OssimQuery("SELECT * FROM host_qualification $where
        $order");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                if (Session::hostAllowed($conn, $rs->fields["host_ip"])) {
                    $list[] = new Host_qualification($rs->fields["host_ip"], $rs->fields["compromise"], $rs->fields["attack"]);
                }
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $host_ip, $compromise, $attack) {
        $compromise = intval($compromise);
        $attack = intval($attack);
        $sql = "INSERT INTO host_qualification VALUES (?, ?, ?)";
        $params = array(
            $host_ip,
            $compromise,
            $attack
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function update($conn, $host_ip, $compromise, $attack) {
        $compromise = intval($compromise);
        $attack = intval($attack);
        $sql = "UPDATE host_qualification SET host_ip = ?, compromise = ?, attack = ? WHERE host_ip = ?";
        $params = array(
            $host_ip,
            $compromise,
            $attack,
            $host_ip
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error updating: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function delete($conn, $host_ip) {
        $sql = "DELETE FROM host_qualification WHERE host_ip = ?";
        $params = array(
            $host_ip
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
}
?>
