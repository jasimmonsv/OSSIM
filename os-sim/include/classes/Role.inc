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
* - Role()
* - get_name()
* - get_correlate()
* - get_cross_correlate()
* - get_store()
* - get_qualify()
* - get_resend_alarm()
* - get_resend_event()
* - get_sign()
* - get_sem()
* - get_sim()
* - get_list()
* - insert()
* - update()
* - delete()
* Classes list:
* - Role
*/
require_once 'classes/Role.inc';
require_once ("ossim_db.inc");
class Role {
    var $name;
    var $correlate;
    var $cross_correlate;
    var $store;
    var $qualify;
    var $resend_alarm;
    var $resend_event;
    var $sign;
    var $sem;
    var $sim;
    function Role($name, $correlate, $cross_correlate, $store, $qualify, $resend_alarm, $resend_event, $sign, $sem, $sim) {
        $this->name = $name;
        $this->correlate = $correlate;
        $this->cross_correlate = $cross_correlate;
        $this->store = $store;
        $this->qualify = $qualify;
        $this->resend_alarm = $resend_alarm;
        $this->resend_event = $resend_event;
        $this->sign = $sign;
        $this->sem = $sem;
        $this->sim = $sim;
    }
    function get_name() {
        return $this->name;
    }
    function get_correlate() {
        return $this->correlate;
    }
    function get_cross_correlate() {
        return $this->cross_correlate;
    }
    function get_store() {
        return $this->store;
    }
    function get_qualify() {
        return $this->qualify;
    }
    function get_resend_alarm() {
        return $this->resend_alarm;
    }
    function get_resend_event() {
        return $this->resend_event;
    }
    function get_sign() {
        return $this->sign;
    }
    function get_sem() {
        return $this->sem;
    }
    function get_sim() {
        return $this->sim;
    }
    function get_list($conn, $name) {
        $list = array();
        $query = OssimQuery("SELECT * FROM server_role 
                    WHERE name = '$name'");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list[] = new Role($rs->fields["name"], $rs->fields["correlate"], $rs->fields["cross_correlate"], $rs->fields["store"], $rs->fields["qualify"], $rs->fields["resend_alarm"], $rs->fields["resend_event"], $rs->fields["sign"], $rs->fields["sem"], $rs->fields["sim"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $correlate, $cross_correlate, $store, $qualify, $resend_alarm, $resend_event, $sign, $sem, $sim, $name) {
        $correlate = intval($correlate);
        $cross_correlate = intval($cross_correlate);
        $store = intval($store);
        $qualify = intval($qualify);
        $resend_alarm = intval($resend_alarm);
        $resend_event = intval($resend_event);
        $sign = intval($sign);
        $sem = intval($sem);
        $sim = intval($sim);
        $sql = "INSERT INTO server_role VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = array(
            $name,
            $correlate,
            $cross_correlate,
            $store,
            $qualify,
            $resend_alarm,
            $resend_event,
            $sign,
            $sem,
            $sim
        );
        if (!$conn->Execute($sql, $params)) {
            die($conn->ErrorMsg());
        }
    }
    function update($conn, $correlate, $cross_correlate, $store, $qualify, $resend_alarm, $resend_event, $sign, $sem, $sim, $name) {
        $correlate = intval($correlate);
        $cross_correlate = intval($cross_correlate);
        $store = intval($store);
        $qualify = intval($qualify);
        $resend_alarm = intval($resend_alarm);
        $resend_event = intval($resend_event);
        $sql = "UPDATE server_role SET name = ?, correlate  = ?,
                cross_correlate = ?, store = ?, qualify = ?, resend_alarm = ?, resend_event =?, sign =?, sem =?, sim =? WHERE name = ?";
        $params = array(
            $name,
            $correlate,
            $cross_correlate,
            $store,
            $qualify,
            $resend_alarm,
            $resend_event,
            $sign,
            $sem,
            $sim,
            $name
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error updating: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function delete($conn, $name) {
        $name = intval($name);
        $sql = "DELETE FROM server_role WHERE name = ?";
        $params = array(
            $name
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
}
?>
