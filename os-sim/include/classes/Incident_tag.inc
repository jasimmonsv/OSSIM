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
* - Incident_tag()
* - get_list()
* - insert()
* - update()
* - delete()
* - get_tag_info()
* - get_html_tag()
* Classes list:
* - Incident_tag
*/
class Incident_tag {
    // A valid Adodb connection object
    var $conn = null;
    function Incident_tag($conn) {
        $this->conn = $conn;
    }
    function get_list($args = '') {
        $sql = "SELECT td.id, td.name, td.descr, count(t.incident_id) as num " . "FROM incident_tag_descr td LEFT JOIN incident_tag t " . "ON t.tag_id = td.id " . "$args " . "GROUP BY td.id " . "ORDER BY td.id";
        if (!$rs = & $this->conn->Execute(OssimQuery($sql))) {
            die($this->conn->ErrorMsg());
        }
        $list = array();
        while (!$rs->EOF) {
            $list[] = $rs->fields;
            $rs->MoveNext();
        }
        return $list;
    }
    function insert($name, $descr) {
        $f['id'] = $this->conn->GenID('incident_tag_descr_seq');
        $f['name'] = $name;
        $f['descr'] = $descr;
        $ok = $this->conn->AutoExecute('incident_tag_descr', $f, 'INSERT');
        //return (!$ok) ? ossim_set_error($this->conn->ErrorMsg()) : true;
        if (!$ok){
            return ossim_set_error($this->conn->ErrorMsg());
        }
        else{
            $infolog = array($name);
            Log_action::log(55, $infolog);
            return true;
        }
    }
    function update($id, $name, $descr) {
        $f['name'] = $name;
        $f['descr'] = $descr;
        $ok = $this->conn->AutoExecute('incident_tag_descr', $f, 'UPDATE', "id = $id");
        //return (!$ok) ? ossim_set_error($this->conn->ErrorMsg()) : true;
        
        if (!$ok){
            return ossim_set_error($this->conn->ErrorMsg());
        }
        else{
            $infolog = array($name);
            Log_action::log(56, $infolog);
            return true;
        }
    }
    function delete($id) {
        $conn = $this->conn;
        $conn->StartTrans();
        $conn->Execute("DELETE FROM incident_tag WHERE tag_id=?", array(
            $id
        ));
        $conn->Execute("DELETE FROM incident_tag_descr WHERE id=?", array(
            $id
        ));
        $conn->CompleteTrans();
        if ($conn->HasFailedTrans()) {
            return ossim_set_error($conn->ErrorMsg());
        }
        
        $infolog = array($id);
        Log_action::log(57, $infolog);
        
        return true;
    }
    /*
    * @param $field string 'id', 'name' or 'descr'
    */
    function get_tag_info($tag_id, $field = false) {
        static $tag_list = array();
        if (!count($tag_list)) {
            $sql = "SELECT id, name, descr FROM incident_tag_descr";
            if (!$rs = $this->conn->Execute($sql)) die($this->conn->ErrorMsg());
            while (!$rs->EOF) {
                $id = $rs->fields["id"];
                $name = $rs->fields["name"];
                $descr = $rs->fields["descr"];
                $tag_list[$id] = array(
                    'id' => $id,
                    'name' => $name,
                    'descr' => $descr
                );
                $rs->MoveNext();
            }
            $rs->close();
        }
        if (isset($tag_list[$tag_id])) {
            return $field ? $tag_list[$tag_id][$field] : $tag_list[$tag_id];
        }
        return false;
    }
    /*
    * Returns the complete info of the supplied Tag ID in HTML
    */
    function get_html_tag($tag_id) {
        if (!$info = $this->get_tag_info($tag_id)) return 'none';
        $name = $info["name"];
        $descr = $info["descr"];
        $ret = '<b><i><label title="' . $descr . '">' . $name . '</label></i></b>';
        return $ret;
    }
}
?>
