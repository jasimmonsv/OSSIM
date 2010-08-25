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
* - Incident_type()
* - get_id()
* - get_descr()
* - get_list()
* - insert()
* - update()
* - delete()
* Classes list:
* - Incident_type
*/
require_once ('classes/Incident.inc');
require_once 'classes/Log_action.inc';
require_once ("ossim_db.inc");

class Incident_type {
    var $id;
    var $descr;
    function Incident_type($id, $descr) {
        $this->id = $id;
        $this->descr = $descr;
    }
    function get_id() {
        return $this->id;
    }
    function get_descr() {
        return $this->descr;
    }
    function get_list($conn, $args = "") {
        $list = array();
        $query = OssimQuery("SELECT * FROM incident_type $args");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                $list[] = new Incident_type($rs->fields["id"], $rs->fields["descr"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $id, $descr) {
        $sql = "INSERT into incident_type ( id, descr) VALUES   (?, ?)";
        $params = array(
            $id,
            $descr
        );
        if ($conn->Execute($sql, $params) == false) {
            print 'Error inserting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        $infolog = array($id);
        Log_action::log(53, $infolog);
    }
    function update($conn, $id, $descr) {
        $sql = "UPDATE incident_type SET descr= ? WHERE id = ?";
        $params = array(
            $descr,
            $id
        );
        if ($conn->Execute($sql, $params) == false) {
            print 'Error updating: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        $infolog = array($id);
        Log_action::log(52, $infolog);
    }
    function delete($conn, $id) {
        $sql = "DELETE FROM incident_type WHERE id = ?";
        $params = array(
            $id
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        $sql = "UPDATE incident SET type_id = 'Generic' WHERE type_id = ?";
        $params = array(
            $id
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        $infolog = array($id);
        Log_action::log(54, $infolog);
    }
}
?>
