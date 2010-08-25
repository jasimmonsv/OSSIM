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
* - Classification()
* - get_id()
* - get_name()
* - get_description()
* - get_priority()
* - get_list()
* Classes list:
* - Classification
*/
require_once ("ossim_db.inc");
class Classification {
    var $id;
    var $name;
    var $description;
    var $priority;
    function Classification($id, $name, $description, $priority) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->priority = $priority;
    }
    function get_id() {
        return $this->id;
    }
    function get_name() {
        return $this->name;
    }
    function get_description() {
        return $this->description;
    }
    function get_priority() {
        return $this->priority;
    }
    function get_list($conn, $args = "") {
        $query = OssimQuery("SELECT * FROM classification $args");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list[] = new Classification($rs->fields["id"], $rs->fields["name"], $rs->fields["description"], $rs->fields["priority"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
}
?>
