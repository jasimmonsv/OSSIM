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
* - Signature()
* - get_name()
* - get_list()
* - insert()
* - update()
* - delete()
* Classes list:
* - Signature
*/
require_once ("ossim_db.inc");
class Signature {
    var $name;
    function Signature($name) {
        $this->name = $name;
    }
    function get_name() {
        return $this->name;
    }
    function get_list($conn, $where = "") {
        $query = OssimQuery("SELECT * FROM signature $where");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list[] = new Signature($rs->fields["name"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $name) {
        $sql = "INSERT INTO signature VALUES (?)";
        $params = array(
            $name
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function update($conn, $name, $ips, $priority, $threshold_c, $threshold_a, $descr) {
        $sql = "UPDATE signature SET name = ? WHERE name = ?";
        $params = array(
            $name,
            $name
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error updating: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function delete($conn, $name) {
        $sql = "DELETE FROM signature WHERE name = ?'";
        $params = array(
            $name
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
}
?>
