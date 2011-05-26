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
*/
require_once ("ossim_db.inc");
require_once 'classes/Log_action.inc';

class Databases {
    var $name;
    var $ip;
    var $port;
    var $user;
    var $pass;
    var $icon;
	
    function Databases($name, $ip, $port, $user, $pass, $icon, $foundrows = 0) {
        $this->name = $name;
        $this->ip   = $ip;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
        $this->icon = $icon;
        $this->foundrows = $foundrows;
    }
	
    function get_name() {
        return $this->name;
    }
	
    function get_ip() {
        return $this->ip;
    }
	
    function get_port() {
        return $this->port;
    }
	
    function get_user() {
        return $this->user;
    }
	
    function get_pass() {
        return $this->pass;
    }
	
    function get_icon() {
        return $this->icon;
    }
	
    function get_foundrows() {
        return $this->foundrows;
    }
	
    function get_list($conn, $args = "") {
        $list = array();
        $iffoundrows = preg_match("/LIMIT/i", $args) ? true : false;
        $uuid        = Util::get_system_uuid();
		$select      = ($iffoundrows) ? "SQL_CALC_FOUND_ROWS *, AES_DECRYPT(pass,'$uuid') AS pass_decrypt" : "*, AES_DECRYPT(pass,'$uuid') AS pass_decrypt";
        
		$query       = OssimQuery("SELECT $select FROM `databases` $args");
        
		if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } 
		else 
		{
            $foundrows = 0;
            if ($iffoundrows) {
                if (!$rf = & $conn->Execute("SELECT FOUND_ROWS() as total")) print $conn->ErrorMsg();
                else $foundrows = $rf->fields["total"];
            }
            while (!$rs->EOF) {
                $list[] = new Databases($rs->fields["name"], 
										$rs->fields["ip"], 
										$rs->fields["port"], 
										$rs->fields["user"], 
										( empty($rs->fields['pass_decrypt']) ) ? $rs->fields['pass'] : $rs->fields['pass_decrypt'], 
										$rs->fields["icon"], 
										$foundrows);
                $rs->MoveNext();
            }
        }
        return $list;
    }
	
    function insert($conn, $name, $ip, $port, $user, $pass, $icon) {
        
		$uuid   = Util::get_system_uuid();
		$sql    = "INSERT INTO `databases` (name, ip, port, user, pass, icon) VALUES (?, ?, ?, ?, AES_ENCRYPT(?,'$uuid'), ?)";
		$params = array(
			$name,
			$ip,
			$port,
			$user,
			$pass,
			$icon
		);
		
		
        if (!$conn->Execute($sql, $params)) {
            die($conn->ErrorMsg());
        }
        $infolog = array(
            $name,
            "[" . $ip . "]"
        );
        Log_action::log(79, $infolog);
    }
	
    function update($conn, $name, $ip, $port, $user, $pass, $icon) {
        if ( Util::is_fake_pass($pass) )
		{
			$sql = "UPDATE `databases` SET name = ?, ip = ?, port = ?, user = ?, icon = ? WHERE name = ?";
			$params = array(
				$name,
				$ip,
				$port,
				$user,
				$icon,
				$name
			);
		}
		else
		{
			$uuid   = Util::get_system_uuid();
			$sql    = "UPDATE `databases` SET name = ?, ip = ?, port = ?, user = ?, pass = AES_ENCRYPT(?,'$uuid'), icon = ? WHERE name = ?";
			$params = array(
				$name,
				$ip,
				$port,
				$user,
				$pass,
				$icon,
				$name
			);
		}
        
		if (!$conn->Execute($sql, $params)) {
            die($conn->ErrorMsg());
        }
        $infolog = array(
            $name,
            "[" . $ip . "]"
        );
        Log_action::log(80, $infolog);
    }
    function delete($conn, $name) {
    	$sql = "DELETE FROM `databases` WHERE name = ?";
        if (!$conn->Execute($sql, array(
            $name
        ))) {
            die($conn->ErrorMsg());
        }
        if (preg_match("/snort\_restore/",$name)) {
        	$conn->Execute("drop database $name");
        }
        $infolog = array(
            $name
        );
        Log_action::log(81, $infolog);
    }
}
?>
