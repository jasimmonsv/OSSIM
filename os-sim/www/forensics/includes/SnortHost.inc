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
* - Host()
* - get_ip()
* - get_hostname()
* - get_asset()
* - get_threshold_c()
* - get_threshold_a()
* - get_rrd_profile()
* - get_alert()
* - get_persistence()
* - get_nat()
* - get_descr()
* - get_ips_and_hostname()
* - get_plugin_list()
* Classes list:
* - SnortHost
*/
require_once 'ossim_conf.inc';
require_once 'classes/Host_sensor_reference.inc';
require_once 'classes/Host_qualification.inc';
require_once 'classes/Host_mac.inc';
require_once 'classes/Host_os.inc';
require_once 'classes/Host_services.inc';
require_once 'classes/Net.inc';
require_once 'classes/Session.inc';
require_once 'classes/Log_action.inc';
require_once 'ossim_db.inc';
require_once 'classes/WebIndicator.inc';
class SnortHost {
    var $ip;
    var $hostname;
    var $asset;
    var $threshold_c;
    var $threshold_a;
    var $rrd_profile;
    var $alert;
    var $persistence;
    var $nat;
    var $descr;
    function Host($ip, $hostname, $asset, $threshold_c, $threshold_a, $rrd_profile, $alert, $persistence, $nat, $descr) {
        $this->ip = htmlentities($ip, ENT_COMPAT, "UTF-8");
        $this->hostname = htmlentities($hostname, ENT_COMPAT, "UTF-8");
        $this->asset = intval($asset);
        $this->threshold_c = intval($threshold_c);
        $this->threshold_a = intval($threshold_a);
        $this->rrd_profile = htmlentities($rrd_profile, ENT_COMPAT, "UTF-8");
        $this->alert = intval($alert);
        $this->persistence = intval($persistence);
        $this->nat = htmlentities($nat, ENT_COMPAT, "UTF-8");
        $this->descr = htmlentities($descr, ENT_COMPAT, "UTF-8");
    }
    function get_ip() {
        return $this->ip;
    }
    function get_hostname() {
        return $this->hostname;
    }
    function get_asset() {
        return $this->asset;
    }
    function get_threshold_c() {
        return $this->threshold_c;
    }
    function get_threshold_a() {
        return $this->threshold_a;
    }
    function get_rrd_profile() {
        return $this->rrd_profile;
    }
    function get_alert() {
        return $this->alert;
    }
    function get_persistence() {
        return $this->persistence;
    }
    function get_nat() {
        return $this->nat;
    }
    function get_descr() {
        return $this->descr;
    }
    // get lists of sensors/hosts => use by RT Event
    function get_ips_and_hostname($conn) {
        $sensors = array();
        $sql = "SELECT ip,name FROM sensor";
        if (!$rs = $conn->Execute($sql)) {
            print 'error: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        // get hash with ip-name
        while (!$rs->EOF) {
            $sensors[$rs->fields["ip"]] = $rs->fields["name"];
            $rs->MoveNext();
        }
        $hosts = array();
        $sql = "SELECT ip,hostname FROM host";
        if (!$rs = $conn->Execute($sql)) {
            print 'error: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        // get hash with ip-name
        while (!$rs->EOF) {
            $hosts[$rs->fields["ip"]] = $rs->fields["hostname"];
            $rs->MoveNext();
        }
        return array(
            $sensors,
            $hosts
        );
    }
    // Plugin list
    function get_plugin_list($conn) {
        $query = "SELECT id,name FROM plugin";
        $list = array();
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                $list[$rs->fields["name"]] = $rs->fields["id"];
                $rs->MoveNext();
            }
        }
        return $list;
    }
}
?>
