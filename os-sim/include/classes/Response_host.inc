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
* - Response_host()
* - get_response_id()
* - get_host()
* - get_type()
* - get_list()
* - insert()
* Classes list:
* - Response_host
*/
require_once ("ossim_db.inc");
class Response_host {
    var $response_id;
    var $host;
    var $type;
    function Response_host($response_id, $host, $type) {
        $this->response_id = $response_id;
        $this->host = $host;
        $this->type = $type;
    }
    function get_response_id() {
        return $this->response_id;
    }
    function get_host() {
        return $this->host;
    }
    function get_type() {
        return $this->type;
    }
    function get_list($conn, $args = "") {
        require_once ('classes/Session.inc');
        $query = OssimQuery("SELECT * FROM response_host $args");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                if (Session::hostAllowed($conn, $rs->fields["host"])) {
                    $list[] = new Response_host($rs->fields["response_id"], $rs->fields["host"], $rs->fields["_type"]);
                }
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $host, $type) {
        $sql = "INSERT IGNORE INTO response_host (response_id, host, _type)
            VALUES  (LAST_INSERT_ID(), ?, ?)";
        $params = array(
            $host,
            $type
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
}
?>

