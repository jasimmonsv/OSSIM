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
* - Response_net()
* - get_response_id()
* - get_net()
* - get_type()
* - get_list()
* - insert()
* Classes list:
* - Response_net
*/
require_once ("ossim_db.inc");
class Response_net {
    var $response_id;
    var $net;
    var $type;
    function Response_net($response_id, $net, $type) {
        $this->response_id = $response_id;
        $this->net = $net;
        $this->type = $type;
    }
    function get_response_id() {
        return $this->response_id;
    }
    function get_net() {
        return $this->net;
    }
    function get_type() {
        return $this->type;
    }
    function get_list($conn, $args = "") {
        $query = OssimQuery("SELECT * FROM response_net $args");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list[] = new Response_net($rs->fields["response_id"], $rs->fields["net"], $rs->fields["_type"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $net, $type) {
        $sql = "INSERT IGNORE INTO response_net (response_id, net, _type) VALUES  (LAST_INSERT_ID(), ?, ?)";
        $params = array(
            $net,
            $type
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
}
?>

