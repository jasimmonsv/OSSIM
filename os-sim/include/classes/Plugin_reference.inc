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
* - Plugin_reference()
* - get_plugin_id()
* - get_plugin_sid()
* - get_reference_id()
* - get_reference_sid()
* - get_count()
* - get_list()
* Classes list:
* - Plugin_reference
*/
require_once ("ossim_db.inc");
require_once 'classes/Log_action.inc';

class Plugin_reference {
    var $plugin_id;
    var $plugin_sid;
    var $reference_id;
    var $reference_sid;
    function Plugin_reference($plugin_id, $plugin_sid, $reference_id, $reference_sid,  $foundrows=0) {
        $this->plugin_id     = $plugin_id;
        $this->plugin_sid    = $plugin_sid;
        $this->reference_id  = $reference_id;
        $this->reference_sid = $reference_sid;
		$this->foundrows     = $foundrows;
    }
    function get_plugin_id() {
        return $this->plugin_id;
    }
    function get_plugin_sid() {
        return $this->plugin_sid;
    }
    function get_reference_id() {
        return $this->reference_id;
    }
    function get_reference_sid() {
        return $this->reference_sid;
    }
	
	function get_foundrows() {
        return $this->foundrows;
    }
	
    function get_count($conn) {
        $sql = "SELECT count(*) FROM plugin_reference";
        if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
        } else {
            return $rs->fields["count(*)"];
        }
    }
    function get_list($conn, $args = "", $inf = 0, $sup = 25) {
        $i = 0;
		$iffoundrows = preg_match("/LIMIT/i", $args) ? true : false;
        $cfrows      = ( $iffoundrows ) ? "SQL_CALC_FOUND_ROWS " : "";
		$query       = OssimQuery("SELECT $cfrows * FROM plugin_reference $args");
        
		$list        = array();
		
		if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } 
		else 
		{
            $foundrows = 0;
			
			if ($iffoundrows) 
			{
                if (!$rf = & $conn->Execute("SELECT FOUND_ROWS() as total")) 
					print $conn->ErrorMsg();
                else 
					$foundrows = $rf->fields["total"];
            }			
			
            while (!$rs->EOF) 
			{
                if ( ($i++ >= $inf) && ($inf++ <= $sup) ) 
				{
                    $list[] = new Plugin_reference($rs->fields["plugin_id"], $rs->fields["plugin_sid"], $rs->fields["reference_id"], $rs->fields["reference_sid"],  $foundrows);
                } 
				elseif ($inf > $sup) 
				{
                    break;
                }
                $rs->MoveNext();
            }
        }
		
        return $list;
    }
	
	
	function get_list2($conn, $args = "") {
		
		$iffoundrows = preg_match("/LIMIT/i", $args) ? true : false;
        $cfrows      = ( $iffoundrows ) ? "SQL_CALC_FOUND_ROWS " : "";
		$query       = OssimQuery("SELECT $cfrows plugin_reference.plugin_id,plugin_reference.plugin_sid,plugin_reference.reference_id,plugin_reference.reference_sid FROM plugin_reference $args");
		
		$list = array();
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } 
		else 
		{	
			$foundrows = 0;
			if ($iffoundrows) 
			{
                if (!$rf = & $conn->Execute("SELECT FOUND_ROWS() as total")) 
					print $conn->ErrorMsg();
                else 
					$foundrows = $rf->fields["total"];
            }
		
            while (!$rs->EOF) {
                $list[] = new Plugin_reference($rs->fields["plugin_id"], $rs->fields["plugin_sid"], $rs->fields["reference_id"], $rs->fields["reference_sid"], $foundrows);
                $rs->MoveNext();
            }
        }
        return $list;
    }
			
	function new_rule($conn, $plugin_id1, $plugin_id2, $plugin_sid1, $plugin_sid2) {
		$query = OssimQuery("INSERT INTO plugin_reference (plugin_id,plugin_sid,reference_id,reference_sid) VALUES ($plugin_id1,$plugin_sid1,$plugin_id2,$plugin_sid2)");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
            return 1;
        } 
        else {
            $infolog = array($plugin_id1, $plugin_sid1, $plugin_id2, $plugin_sid2);
            Log_action::log(90, $infolog);
            return 0;
        }
	}
	
	function delete_rule($conn, $plugin_id1, $plugin_id2, $plugin_sid1, $plugin_sid2) {
		$query = OssimQuery("DELETE FROM plugin_reference WHERE plugin_id=$plugin_id1 AND plugin_sid=$plugin_sid1 AND reference_id=$plugin_id2 AND reference_sid=$plugin_sid2");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
			return 1;
        } else {
            $infolog = array($plugin_id1, $plugin_sid1, $plugin_id2, $plugin_sid2);
            Log_action::log(91, $infolog);
            return 0;
        }
	}
	
	function change_rule($conn, $plugin_id1, $plugin_id2, $plugin_sid1, $plugin_sid2, $id1,$id2,$sid1,$sid2) {
		$query = OssimQuery("UPDATE plugin_reference SET plugin_id=$plugin_id1,plugin_sid=$plugin_sid1,reference_id=$plugin_id2,reference_sid=$plugin_sid2 WHERE plugin_id=$id1 AND reference_id=$id2 AND plugin_sid=$sid1 AND reference_sid=$sid2");
		//echo $query."<br>";
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
			return 1;
        } else {
            $infolog = array($id1, $sid1, $id2, $sid2);
            Log_action::log(91, $infolog);
            
            $infolog = array($plugin_id1, $plugin_sid1, $plugin_id2, $plugin_sid2);
            Log_action::log(90, $infolog);

            return 0;
        }
	}
}
?>
