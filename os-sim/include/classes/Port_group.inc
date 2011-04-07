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
* - Port_group()
* - get_name()
* - get_ip()
* - get_descr()
* - get_foundrows()
* - get_reference_ports()
* - get_list()
* - insert()
* - update()
* - delete()
* Classes list:
* - Port_group
*/
require_once 'Port_group_reference.inc';
require_once ('classes/Log_action.inc');
require_once ("ossim_db.inc");
class Port_group {
    var $name;
    var $descr;
    function Port_group($name, $descr, $foundrows = 0) {
        $this->name = $name;
        $this->descr = $descr;
        $this->foundrows = $foundrows;
    }
    function get_name() {
        return $this->name;
    }
    function get_ip() {
        return $this->ip;
    }
    function get_descr() {
        return $this->descr;
    }
    function get_foundrows() {
        return $this->foundrows;
    }
    function get_reference_ports($conn, $port_group_name) {
        return Port_group_reference::get_list($conn, "WHERE port_group_name = '$port_group_name'");
    }
    function get_list($conn, $args = "") {
        $iffoundrows = preg_match("/LIMIT/i", $args) ? true : false;
        $select = ($iffoundrows) ? "SQL_CALC_FOUND_ROWS *" : "*";
        $query = OssimQuery("SELECT $select FROM port_group $args");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $foundrows = 0;
            if ($iffoundrows) {
                if (!$rf = & $conn->Execute("SELECT FOUND_ROWS() as total")) print $conn->ErrorMsg();
                else $foundrows = $rf->fields["total"];
            }
            $list = array();
            while (!$rs->EOF) {
                $list[] = new Port_group($rs->fields["name"], $rs->fields["descr"], $foundrows);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $name, $port_list, $descr) {
        $sql = "INSERT INTO port_group VALUES (?, ?)";
        
		$params = array(
            $name,
            $descr
        );
        
		if ($conn->Execute($sql, $params) === false) {
            print 'Error inserting: ' . $conn->ErrorMsg() . '<br/>';
            exit;
        }
        Port_group_reference::insert($conn, $name, $port_list);
        $infolog = array(
            $name,
            "(" . $descr . ")"
        );
        Log_action::log(39, $infolog);
    }
	
    function update($conn, $name, $port_list, $descr) {
        
		Port_group::delete($conn, $name);
        Port_group::insert($conn, $name, $port_list, $descr);
        $infolog = array(
            $name,
            "(" . $descr . ")"
        );
		
		Log_action::log(40, $infolog);
    }
	
    function delete($conn, $name) {
        $sql = "DELETE FROM port_group WHERE name = ?";
        $params = array(
            $name
        );
		
        if ($conn->Execute($sql, $params) === false) {
            print 'Error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
		
        Port_group_reference::delete($conn, $name);
        $infolog = array(
            $name
        );
        Log_action::log(41, $infolog);
    }
	function can_delete($conn, $name) {
		$sql = "SELECT count(*) as num FROM policy_port_reference WHERE port_group_name = ?";
		$params = array(
            $name
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
		if ($rs->fields['num'] == '0') return 1;
		else return 0;
	}
}
?>
