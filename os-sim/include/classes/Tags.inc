<?php
/*****************************************************************************
*
*    License:
*
*   Copyright (c) 2007-2010 AlienVault
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
* - insert()
* - delete()
* - get_list()
*/
require_once 'ossim_db.inc';
class Tags {
	var $id;
	var $name;
    var $bgcolor;
    var $fgcolor;
    var $italic;
    var $bold;
	function get_id() {
        return $this->id;
    }
    function get_name() {
        return $this->name;
    }
	function get_bgcolor() {
        return $this->bgcolor;
    }
	function get_fgcolor() {
        return $this->fgcolor;
    }
	function get_italic() {
        return $this->italic;
    }
	function get_bold() {
        return $this->bold;
    }
    function Tags($id, $name, $bgcolor, $fgcolor, $italic, $bold) {
        $this->id = $id;
    	$this->name = $name;
        $this->bgcolor = $bgcolor;
        $this->fgcolor = $fgcolor;
        $this->italic = $italic;
        $this->bold = $bold;
    }
    function get_list($conn, $args = "") {
        $list = array();
        $query = OssimQuery("SELECT * FROM tags_alarm $args");
		if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                $list[] = new Tags($rs->fields["id"], $rs->fields["name"], $rs->fields["bgcolor"], $rs->fields["fgcolor"], $rs->fields["italic"], $version, $rs->fields["bold"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
	function get_list_html($conn, $args = "") {
        $list = array();
        $query = OssimQuery("SELECT * FROM tags_alarm $args");
		if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                $list[$rs->fields["id"]] = '<table class="transparent" cellpadding="2"><tr><td style="border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;border:1px solid #888888;background-color:#'.$rs->fields["bgcolor"].';color:#'.$rs->fields["fgcolor"].';font-weight:'.(($rs->fields["bold"]) ? "bold" : "normal").';text-decoration:'.(($rs->fields["italic"]) ? "italic" : "none").'" nowrap>'.$rs->fields["name"].'</td></tr></table>';
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn,$name,$bgcolor,$fgcolor,$italic,$bold) {
    	$id = -1;
    	$sql = "INSERT INTO tags_alarm (name,bgcolor,fgcolor,italic,bold) VALUES (?,?,?,?,?)";
    	$params = array(
    		$name,
    		$bgcolor,
    		$fgcolor,
    		$italic,
    		$bold
    	);
    	if (!$rs = & $conn->Execute($sql,$params)) {
            print $conn->ErrorMsg();
        } else {
            $res = $conn->query("SELECT LAST_INSERT_ID() as lastid");
        	if ($rw = $res->fetchRow()) $id = $rw["lastid"];
        }
        return $id;
    }
	function update($conn,$id,$name,$bgcolor,$fgcolor,$italic,$bold) {
    	$sql = "UPDATE tags_alarm SET name=?,bgcolor=?,fgcolor=?,italic=?,bold=? WHERE id=?";
    	$params = array(
    		$name,
    		$bgcolor,
    		$fgcolor,
    		$italic,
    		$bold,
    		$id
    	);
    	if (!$rs = & $conn->Execute($sql,$params)) {
            print $conn->ErrorMsg();
        }
    }
	function delete($conn,$id) {
    	$sql = "DELETE FROM tags_alarm WHERE id=?";
    	$params = array(
    		$id
    	);
    	if (!$rs = & $conn->Execute($sql,$params)) {
            print $conn->ErrorMsg();
        }
		$sql = "DELETE FROM alarm_tags WHERE id_tag=?";
    	if (!$rs = & $conn->Execute($sql,$params)) {
            print $conn->ErrorMsg();
        }
    }
    function set_alarm_tag($conn,$alarm_id,$tag_id) {
    	$params = array(
    		$alarm_id,
    		$tag_id
    	);
    	$sql = "INSERT INTO alarm_tags (id_alarm,id_tag) VALUES (?,?) ON DUPLICATE KEY UPDATE id_tag=$tag_id";
    	if (!$rs = & $conn->Execute($sql,$params)) {
            print $conn->ErrorMsg();
        }
    }
	function del_alarm_tag($conn,$alarm_id) {
    	$params = array(
    		$alarm_id
    	);
    	$sql = "DELETE FROM alarm_tags WHERE id_alarm=?";
    	if (!$rs = & $conn->Execute($sql,$params)) {
            print $conn->ErrorMsg();
        }
    }
    function get_tags_from_list($conn,$alarm_list) {
    	$list = array();
    	$ids = "";
    	foreach($alarm_list as $alarm) {
    		$ids .= ($ids != "") ? ",".$alarm->get_backlog_id() : $alarm->get_backlog_id();
    	}
    	if ($ids != "") {
	    	$sql = "SELECT * FROM alarm_tags WHERE id_alarm in ($ids)";
	    	if (!$rs = & $conn->Execute($sql)) {
	            print $conn->ErrorMsg();
	        } else {
		        while (!$rs->EOF) {
	                $list[$rs->fields['id_alarm']] = $rs->fields['id_tag'];
	                $rs->MoveNext();
	            }
	        }
    	}
    	return $list;
    }
}
?>
