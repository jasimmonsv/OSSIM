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
* - Category()
* - get_id()
* - get_name()
* - get_list()
* - insert()
* - exist()
* - newId()
* - inUse()
* - delete()
* - edit()
* Classes list:
* - Category
*/
require_once ("ossim_db.inc");
class Category {
    var $id;
    var $name;
    var $inUse;
    function Category($id, $name, $inUse) {
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
    function get_list($conn, $args = "ORDER BY name") {
        $query = OssimQuery("SELECT * FROM category $args");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
            	$inUse=Category::inUse($conn,$rs->fields["id"]);
                $list[] = new Category($rs->fields["id"], $rs->fields["name"], $inUse);
                $rs->MoveNext();
            }
        }
        return $list;
    }
	function insert($conn, $name){
    	if(Category::exist($conn, $name)){
    		echo _('Error: This category already exists');
    		
    		return false;
    	}else{
    		$newId=Category::newID($conn);
    		
    		$sql = 'INSERT INTO category (id,name) VALUES ('.$newId.',"'.$name.'")';

    		if ($conn->Execute($sql) === false) {
            	print 'Error inserting category: ' . $conn->ErrorMsg() . '<br/>';
            	exit;
        	}
        
    		return true;
    	}
    }
    function exist($conn, $name){
    	$query = OssimQuery('SELECT * FROM category WHERE name="'.$name.'"');
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
    function newID($conn){
    	$query = OssimQuery('SELECT MAX(id) FROM category');
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
        	if($rs->EOF) {
        		return false;
        	}else{
        		return (int)$rs->fields[0]+1;
        	}            
        }
    }
    function inUse($conn, $id){
    	$query = OssimQuery('SELECT COUNT(*) AS total FROM plugin_sid WHERE category_id='.$id);
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
	function delete($conn, $id){
    	if(!Category::inUse($conn,$id)){
    	
    		$query=OssimQuery('DELETE FROM category WHERE id = '.$id);
    		if (!$rs = & $conn->Execute($query)) {
            	print $conn->ErrorMsg();
            	return false;
        	} else {
        		// clean subcategory
        		require_once 'classes/Subcategory.inc';
        		$list_subcategories=Subcategory::get_list($conn,'WHERE cat_id='.$id.' ORDER BY name');
				foreach ($list_subcategories as $subcategory) {
					Subcategory::delete($conn, $subcategory->get_id());
				}
        		return true;
        	}
    	}else{
    		echo _('Error, this Category are in use.');
    		return false;
    	}
    }
	function edit($conn, $id, $name){    		
    	$sql = 'UPDATE category SET name="'.$name.'" WHERE id='.$id;

    	if ($conn->Execute($sql) === false) {
           	print 'Error rename Category: ' . $conn->ErrorMsg() . '<br/>';
           	exit;
        }
        
    	return true;
    }
}
?>

