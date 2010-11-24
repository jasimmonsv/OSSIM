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
* - Subcategory()
* - get_id()
* - get_name()
* - get_list()
* - insert()
* - exist()
* - inUse()
* - delete()
* - edit()
* Classes list:
* - Subcategory
*/
require_once ("ossim_db.inc");
class Subcategory {
    var $id;
    var $name;
    var $inUse;
    function Subcategory($id, $name,$inUse) {
        $this->id = $id;
        $this->name = $name;
        $this->inUse = $inUse;
    }
    function get_id() {
        return $this->id;
    }
    function get_name() {
        return $this->name;
    }
	function get_inUse() {
        return $this->inUse;
    }
    function get_list($conn, $args = "") {
        $query = OssimQuery("SELECT * FROM subcategory $args");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
            	$inUse=Subcategory::inUse($conn,$rs->fields["cat_id"],$rs->fields["id"]);
                $list[] = new Subcategory($rs->fields["id"], $rs->fields["name"],$inUse);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $cat_id, $name){
    	if(Subcategory::exist($conn, $cat_id, $name)){
    		echo _('Error: This subcategory already exists');
    		
    		return false;
    	}else{    
    		$sql = 'INSERT INTO subcategory (cat_id, name) VALUES ('.$cat_id.', "'.$name.'")';
    	
    		if ($conn->Execute($sql) === false) {
            	print 'Error inserting subcategory: ' . $conn->ErrorMsg() . '<br/>';
            	exit;
        	}
        
    		return true;
    	}
    }
    function exist($conn, $cat_id, $name){
    	$query = OssimQuery('SELECT * FROM subcategory WHERE cat_id='.$cat_id.' AND name="'.$name.'"');
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
        	if($rs->EOF) {
        		return false;
        	}else{
        		return true;
        	}            
        }
    }
	function inUse($conn, $cat_id, $id){
    	$query = OssimQuery('SELECT COUNT(*) AS total FROM plugin_sid WHERE category_id='.$cat_id.' AND subcategory_id='.$id);
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
        	if($rs->EOF) {
        		return false;
        	}else{
        		return ($rs->fields["total"]>0) ? true : false;
        	}
        }
    }
    function delete($conn, $cat_id, $id){
    	if(!Subcategory::inUse($conn, $cat_id, $id)){
    		$query=OssimQuery('DELETE FROM subcategory WHERE id = '.$id);
    		if (!$rs = & $conn->Execute($query)) {
            	print $conn->ErrorMsg();
            	return false;
        	} else {
        		return true;
        	}
    	}else{
    		echo _('Error, this Subcategory are in use.');
    		return false;
    	}
    }
	function edit($conn, $id, $name){    		
    	$sql = 'UPDATE subcategory SET name="'.$name.'" WHERE id='.$id;

    	if ($conn->Execute($sql) === false) {
           	print 'Error rename Subcategory: ' . $conn->ErrorMsg() . '<br/>';
           	exit;
        }
        
    	return true;
    }
}
?>

