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
* - ActiveDirectory()
* - get_server()
* - get_binddn()
* - get_password()
* - get_scope()
* - get_list()
* - insert()
* - delete()
* - update()
*/
require_once ('classes/Log_action.inc');
require_once ("ossim_db.inc");
require_once ('classes/Util.inc');

class ActiveDirectory {
    var $id;
    var $server;
    var $binddn;
    var $password;
    var $scope;
    function ActiveDirectory($id, $server, $binddn, $password, $scope, $foundrows = 0) {
        $this->id = $id;
        $this->server = $server;
        $this->binddn = $binddn;
        $this->password = $password;
        $this->scope = $scope;
        $this->foundrows = $foundrows;
    }
    function get_id() {
        return $this->id;
    }
    function get_server() {
        return $this->server;
    }
    function get_binddn() {
        return $this->binddn;
    }
    function get_password() {
        return $this->password;
    }
    function get_scope() {
        return $this->scope;
    }
    function get_foundrows() {
        return $this->foundrows;
    }
    function get_list($conn, $args = "") {
        $iffoundrows = preg_match("/LIMIT/i", $args) ? true : false;
        $select = ($iffoundrows) ? "SQL_CALC_FOUND_ROWS *" : "*";
        $query = OssimQuery("SELECT $select FROM ldap $args");
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
                $list[] = new ActiveDirectory($rs->fields["id"], $rs->fields["ip"], $rs->fields["binddn"], $rs->fields["password"], $rs->fields["scope"], $foundrows);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $server, $binddn, $password, $scope) {
        $sql = "INSERT INTO ldap (ip,binddn,password,scope) VALUES (inet_aton(?), ?, ?, ?)";
        $params = array(
            $server,
            $binddn,
            $password,
            $scope
        );        
        if ($conn->Execute($sql,$params) === false) {
            print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function update($conn, $id, $server, $binddn, $password, $scope) {
        if ( Util::is_fake_pass($password) )
		{
			$sql = "UPDATE ldap SET ip=inet_aton(?),binddn=?,scope=? WHERE id=?";
			$params = array(
				$server,
				$binddn,
				$scope,
				$id
			);  
		
		}
		else
		{
			$sql = "UPDATE ldap SET ip=inet_aton(?),binddn=?,password=?,scope=? WHERE id=?";
			$params = array(
				$server,
				$binddn,
				$password,
				$scope,
				$id
			);   
		}
		
        if ($conn->Execute($sql,$params) === false) {
            print 'error updating: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function delete($conn, $id) {
        $sql = "DELETE FROM ldap WHERE id = ?";
        $params = array(
            $id
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
}
?>
