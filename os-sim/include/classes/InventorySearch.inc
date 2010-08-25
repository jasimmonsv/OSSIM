<?php
/*****************************************************************************
*
*    License:
*
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
* - InventorySearch()
* - insert()
* - delete()
* - get_all()
*/
require_once 'classes/Security.inc';
require_once 'ossim_db.inc';

class InventorySearch {
    var $type;
	var $subtype;
	var $match;
	var $prelist;
	var $query;

    function InventorySearch($type,$subtype,$match="text",$prelist="",$query="") {
        $this->type = $type;
		$this->subtype = $subtype;
		$this->match = $match;
		$this->prelist = $prelist;
		$this->query = $query;
    }
    
	function get_type() {
		return $this->type;
	}
	function get_subtype() {
		return $this->subtype;
	}
	function get_prelist() {
		return $this->prelist;
	}
	function get_query() {
		return $this->query;
	}
	function get_match() {
		return $this->match;
	}
	
    function insert($conn,$type,$subtype,$match="text",$list="",$query="") {
		$sql = "INSERT INTO inventory_search (type,subtype,inventory_search.match,list,query) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE inventory_search.match=?,list=?,query=?";
		$params = array(
            $type,
			$subtype,
			$match,
			$list,
			$query,
			$match,
			$list,
			$query
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
            exit;
        }
    }
    function delete($conn,$type,$subtype) {
		$sql = "DELETE FROM inventory_search WHERE type=? AND subtype=?";
        $params = array(
            $type,
			$subtype
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
            exit;
        }
	}
	function get_all($conn) {
		$ret = array();
		$sql = "SELECT * FROM inventory_search ORDER BY ruleorder ASC";
        if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
            exit;
        }
		while (!$rs->EOF) {
			$ret[] = new InventorySearch($rs->fields["type"],$rs->fields["subtype"],$rs->fields["match"],$rs->fields["list"],$rs->fields["query"]);
			$rs->MoveNext();
		}
		return $ret;
	}
	function get_rule($conn,$type,$subtype) {
		$ret = "";
		$sql = "SELECT * FROM inventory_search WHERE type=? AND subtype=?";
        $params = array(
            $type,
			$subtype
        );
		if (!$rs = & $conn->Execute($sql,$params)) {
            print $conn->ErrorMsg();
            exit;
        }
		if (!$rs->EOF) {
			$ret = new InventorySearch($rs->fields["type"],$rs->fields["subtype"],$rs->fields["match"],$rs->fields["list"],$rs->fields["query"]);
		}
		return $ret;
	}
	function get_matches($conn) {
		$ret = array();
		$sql = "SELECT DISTINCT inventory_search.match FROM inventory_search";
        if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
            exit;
        }
		while (!$rs->EOF) {
			$ret[] = $rs->fields["match"];
			$rs->MoveNext();
		}
		return $ret;
	}
}
?>
