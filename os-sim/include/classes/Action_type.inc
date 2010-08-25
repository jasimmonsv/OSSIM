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
* - Action_type()
* - get_type()
* - get_descr()
* - get_list()
* Classes list:
* - Action_type
*/
require_once ("ossim_db.inc");
class Action_type {
    var $type;
    var $descr;
    function Action_type($type, $descr) {
        $this->type = $type;
        $this->descr = htmlentities($descr, ENT_COMPAT, "UTF-8");
    }
    function get_type() {
        return $this->type;
    }
    function get_descr() {
        return $this->descr;
    }
    function get_list($conn, $args = "") {
        $query = OssimQuery("SELECT * FROM action_type $args");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list[] = new Action_type($rs->fields["_type"], $rs->fields["descr"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
}
?>
