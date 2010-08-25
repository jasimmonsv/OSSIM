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
* - get_list()
* - get_ip_list()
* - get_anom_list()
* - get_anom_ip_list()
* - get_ip_data()
* - get_list_count()
* - ack_ign()
* - insert()
* - delete()
* - set_nagios()
* - get_port_protocol_list()
* Classes list:
* - Host_services
*/
require_once ("ossim_db.inc");
require_once ('classes/Log_action.inc');

class Host_services {
    /*
    * Return list of the all records (anomalies or not) for each host
    * An inferior and superior limit of record is given
    * (used by secondary specific anomalie screen)
    */
    function get_list($conn, $inf, $sup) {
        require_once ('classes/Session.inc');
        require_once ('classes/Security.inc');
        $list = array();
        $nanoms = $sup - $inf;
        $sql = "SELECT distinct ip, port, sensor FROM host_services LIMIT ? OFFSET ?";
        $params = array(
            $nanoms,
            $inf
        );
        $list = array();
        if (!$ips = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        } else {
            while (!$ips->EOF) {
                $sql = "SELECT *, inet_ntoa(sensor) as ip_sensor, inet_ntoa(ip) as ip_addr from host_services where
                        ip= ? and  sensor = ? and  port = ? order by date desc";
                $params = array(
                    $ips->fields["ip"],
                    $ips->fields["sensor"],
                    $ips->fields["port"]
                );
                if (!$aux = & $conn->Execute($sql, $params)) {
                    print $conn->ErrorMsg();
                } elseif ((Session::hostAllowed($conn, $aux->fields["ip_addr"])) and (Session::hostAllowed($conn, $aux->fields["ip_sensor"]))) {
                    if (($aux->fields["anom"] == "0") || ($aux->RecordCount() == "1")) {
                        $list[] = array(
                            "ip" => $aux->fields["ip_addr"],
                            "port" => $aux->fields["port"],
                            "date" => $aux->fields["date"],
                            "protocol" => $aux->fields["protocol"],
                            "service" => $aux->fields["service"],
                            "service_type" => $aux->fields["service_type"],
                            "version" => $aux->fields["version"],
                            "origin" => $aux->fields["origin"],
                            "old_date" => $aux->fields["date"],
                            "sensor" => $aux->fields["ip_sensor"],
                            "old_protocol" => $aux->fields["protocol"],
                            "old_service" => $aux->fields["service"],
                            "old_service_type" => $aux->fields["service_type"],
                            "old_version" => $aux->fields["version"],
                            "old_origin" => $aux->fields["origin"],
                            "interface" => $aux->fields["interface"]
                        );
                    } else {
                        $ip = $aux->fields["ip_addr"];
                        $port = $aux->fields["port"];
                        $protocol = $aux->fields["protocol"];
                        $service = $aux->fields["service"];
                        $service_type = $aux->fields["service_type"];
                        $version = $aux->fields["version"];
                        $origin = $aux->fields["origin"];
                        $date = $aux->fields["date"];
                        $sensor = $aux->fields["ip_sensor"];
                        $interface = $aux->fields["interface"];
                        $aux->MoveNext();
                        while ((!$aux->EOF) && ($aux->fields["anom"] != "0")) {
                            $old_protocol = $aux->fields["protocol"];
                            $old_service = $aux->fields["service"];
                            $old_service_type = $aux->fields["service_type"];
                            $old_version = $aux->fields["version"];
                            $old_origin = $aux->fields["origin"];
                            $old_date = $aux->fields["date"];
                            $aux->MoveNext();
                        }
                        if ($aux->fields["anom"] == "0") {
                            $list[] = array(
                                "ip" => $ip,
                                "port" => $port,
                                "date" => $date,
                                "old_date" => $aux->fields["date"],
                                "protocol" => $protocol,
                                "old_protocol" => $aux->fields["protocol"],
                                "version" => $version,
                                "old_version" => $aux->fields["version"],
                                "service" => $service,
                                "old_service" => $aux->fields["service"],
                                "origin" => $origin,
                                "old_origin" => $aux->fields["origin"],
                                "sensor" => $sensor,
                                "interface" => $interface
                            );
                        } else {
                            $list[] = array(
                                "ip" => $ip,
                                "port" => $port,
                                "date" => $date,
                                "old_date" => $old_date,
                                "protocol" => $protocol,
                                "old_protocol" => $old_protocol,
                                "version" => $version,
                                "old_version" => $old_version,
                                "service" => $service,
                                "old_service" => $old_service,
                                "origin" => $origin,
                                "old_origin" => $old_origin,
                                "sensor" => $sensor,
                                "interface" => $interface
                            );
                        }
                    }
                }
                $ips->MoveNext();
            }
        }
        return $list;
    }
    /*
    * Return list of all records (anomalies or not) of a specific host
    * (used by secondary specific anomalie screen)
    */
    function get_ip_list($conn, $host, $sensor, $port) {
        require_once ('classes/Session.inc');
        require_once ('classes/Security.inc');
        $list = array();
        if (!(Session::hostAllowed($conn, $host)) or !(Session::hostAllowed($conn, $sensor))) return $list;
        $sql = "SELECT *, inet_ntoa(sensor) as ip_sensor, inet_ntoa(ip) as ip_addr from host_services where 
                ip = inet_aton(?) and port = ? and sensor = inet_aton(?)  order by date desc";
        $params = array(
            $host,
            $port,
            $sensor
        );
        if (!$aux = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
            return $list;
        }
        if ($aux->RecordCount() == "1") {
            $list[] = array(
                "ip" => $aux->fields["ip_addr"],
                "port" => $aux->fields["port"],
                "date" => $aux->fields["date"],
                "old_date" => $aux->fields["date"],
                "protocol" => $aux->fields["protocol"],
                "old_protocol" => $aux->fields["protocol"],
                "version" => $aux->fields["version"],
                "old_version" => $aux->fields["version"],
                "service" => $aux->fields["service"],
                "old_service" => $aux->fields["service"],
                "service_type" => $aux->fields["service_type"],
                "old_service_type" => $aux->fields["service_type"],
                "origin" => $aux->fields["origin"],
                "old_origin" => $aux->fields["origin"],
                "sensor" => $aux->fields["ip_sensor"],
                "interface" => $aux->fields["interface"]
            );
            return $list;
        }
        $ip = $aux->fields["ip_addr"];
        $sensor = $aux->fields["ip_sensor"];
        $interface = $aux->fields["interface"];
        $port = $aux->fields["port"];
        $protocol = $aux->fields["protocol"];
        $version = $aux->fields["version"];
        $service = $aux->fields["service"];
        $service_type = $aux->fields["service_type"];
        $origin = $aux->fields["origin"];
        $date = $aux->fields["date"];
        $anom = $aux->fields["anom"];
        $aux->MoveNext();
        do {
            $old_protocol = $aux->fields["protocol"];
            $old_version = $aux->fields["version"];
            $old_service = $aux->fields["service"];
            $old_service_type = $aux->fields["service_type"];
            $old_origin = $aux->fields["origin"];
            $old_date = $aux->fields["date"];
            if ($anom == "0") {
                $list[] = array(
                    "ip" => $ip,
                    "port" => $port,
                    "date" => $date,
                    "old_date" => $date,
                    "protocol" => $protocol,
                    "old_protocol" => $protocol,
                    "version" => $version,
                    "old_version" => $version,
                    "service" => $service,
                    "old_service" => $service,
                    "service_type" => $service_type,
                    "old_service_type" => $service_type,
                    "origin" => $origin,
                    "old_origin" => $origin,
                    "sensor" => $sensor,
                    "interface" => $interface
                );
            } else {
                $list[] = array(
                    "ip" => $ip,
                    "port" => $port,
                    "date" => $date,
                    "old_date" => $old_date,
                    "protocol" => $protocol,
                    "old_protocol" => $old_protocol,
                    "version" => $version,
                    "old_version" => $old_version,
                    "service" => $service,
                    "old_service" => $old_service,
                    "service_type" => $service_type,
                    "old_service_type" => $old_service_type,
                    "origin" => $origin,
                    "old_origin" => $old_origin,
                    "sensor" => $sensor,
                    "interface" => $interface
                );
            }
            $protocol = $old_protocol;
            $version = $old_version;
            $service = $old_service;
            $service_type = $old_service_type;
            $origin = $old_origin;
            $date = $old_date;
            $anom = $aux->fields["anom"];
            $aux->MoveNext();
        }
        while (!$aux->EOF);
        $list[] = array(
            "ip" => $ip,
            "port" => $port,
            "date" => $date,
            "old_date" => $old_date,
            "protocol" => $protocol,
            "old_protocol" => $old_protocol,
            "version" => $version,
            "old_version" => $old_version,
            "service" => $service,
            "old_service" => $old_service,
            "service_type" => $service_type,
            "old_service_type" => $old_service_type,
            "origin" => $origin,
            "old_origin" => $old_origin,
            "sensor" => $sensor,
            "interface" => $interface
        );
        return $list;
    }
    /*
    * Return list of the last anomalie for each host (if any)
    * A total of '$limit' records is calculated (one record per host)
    * (used by control pannel/anomalies on host summary and by secondary specific anomalie screen)
    */
    function get_anom_list($conn, $where_clause="", $limit = 10) { 
        require_once ('classes/Session.inc');
        require_once ('classes/Security.inc');
        $list = array();
        $sql = "SELECT distinct inet_ntoa(ip) as ip, port, inet_ntoa(sensor) as sensor FROM host_services $where_clause";
        if (!$ips = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
        } else {
            $num_anom = 0; 
            while ((!$ips->EOF) && (($num_anom < $limit) || ($limit == "all"))) {
                if ((Session::hostAllowed($conn, $ips->fields["ip"])) and (Session::hostAllowed($conn, $ips->fields["sensor"]))) {
                    $sql = "SELECT *, inet_ntoa(sensor) as ip_sensor, inet_ntoa(ip) as ip_addr from host_services 
                            where ip = inet_aton(?) and sensor = inet_aton(?) and port = ? order by date desc";
                    //echo "$sql";
                    $params = array(
                        $ips->fields["ip"],
                        $ips->fields["sensor"],
                        $ips->fields["port"]
                    );
                    if (!$aux = & $conn->Execute($sql, $params)) {
                        print $conn->ErrorMsg();
                    } elseif ($aux->fields["anom"] != "0") {
                        $ip = $aux->fields["ip_addr"];
                        $port = $aux->fields["port"];
                        $protocol = $aux->fields["protocol"];
                        $version = $aux->fields["version"];
                        $service = $aux->fields["service"];
                        $service_type = $aux->fields["service_type"];
                        $origin = $aux->fields["origin"];
                        $date = $aux->fields["date"];
                        $sensor = $aux->fields["ip_sensor"];
                        $interface = $aux->fields["interface"];
                        $next_anom = true;
                        $is_anom = false;
                        while ($aux->MoveNext() and $next_anom) {
                            if ($aux->fields["anom"] == "0") $next_anom = false;
                            $old_protocol = $aux->fields["protocol"];
                            $old_version = $aux->fields["version"];
                            $old_service = $aux->fields["service"];
                            $old_service_type = $aux->fields["service_type"];
                            $old_origin = $aux->fields["origin"];
                            $old_date = $aux->fields["date"];
                            $is_anom = true;
                        }
                        if ($is_anom) {
                            $num_anom++;
                            $list[] = array(
                                "ip" => $ip,
                                "port" => $port,
                                "date" => $date,
                                "old_date" => $old_date,
                                "protocol" => $protocol,
                                "old_protocol" => $old_protocol,
                                "service" => $service,
                                "old_service" => $old_service,
                                "service_type" => $service_type,
                                "old_service_type" => $old_service_type,
                                "version" => $version,
                                "old_version" => $old_version,
                                "origin" => $origin,
                                "old_origin" => $old_origin,
                                "sensor" => $sensor,
                                "interface" => $interface
                            );
                        }
                    }
                }
                $ips->MoveNext();
            }
        }
        return $list;
    }
    /*
    * Return list of all last anomalies of a specific host
    * (used by control pannel/anomalies, showing record detail)
    */
    function get_anom_ip_list($conn, $host, $sensor, $port) {
        require_once ('classes/Session.inc');
        require_once ('classes/Security.inc');
        $list = array();
        if (!(Session::hostAllowed($conn, $host)) or !(Session::hostAllowed($conn, $sensor))) return $list;
        $sql = "SELECT *, inet_ntoa(sensor) as ip_sensor, inet_ntoa(ip) as ip_addr from host_services
                where ip = inet_aton(?) and port = ? and sensor = inet_aton(?) order by date desc";
        $params = array(
            $host,
            $port,
            $sensor
        );
        if (!$aux = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        } elseif ($aux->fields["anom"] != "0") {
            $ip = $aux->fields["ip_addr"];
            $port = $aux->fields["port"];
            $protocol = $aux->fields["protocol"];
            $version = $aux->fields["version"];
            $service = $aux->fields["service"];
            $service_type = $aux->fields["service_type"];
            $origin = $aux->fields["origin"];
            $date = $aux->fields["date"];
            $sensor = $aux->fields["ip_sensor"];
            $interface = $aux->fields["interface"];
            $next_anom = true;
            while ($aux->MoveNext() && $next_anom) {
                if ($aux->fields["anom"] == "0") $next_anom = false;
                $old_date = $aux->fields["date"];
                $old_protocol = $aux->fields["protocol"];
                $old_version = $aux->fields["version"];
                $old_service = $aux->fields["service"];
                $old_service_type = $aux->fields["service_type"];
                $old_origin = $aux->fields["origin"];
                $list[] = array(
                    "ip" => $ip,
                    "port" => $aux->fields["port"],
                    "date" => $date,
                    "old_date" => $old_date,
                    "protocol" => $protocol,
                    "old_protocol" => $old_protocol,
                    "version" => $version,
                    "old_version" => $old_version,
                    "service" => $service,
                    "old_service" => $old_service,
                    "service_type" => $service_type,
                    "old_service_type" => $old_service_type,
                    "origin" => $origin,
                    "old_origin" => $old_origin,
                    "sensor" => $sensor,
                    "interface" => $interface
                );
                $protocol = $old_protocol;
                $date = $old_date;
                $version = $old_version;
                $service = $old_service;
                $service_type = $old_service_type;
                $origin = $old_origin;
            }
        }
        return $list;
    }
    /*
    * Get seen all specifics services for this host (not return anomalies)
    *   if origin=0 : passive detection (pads)
    *   if origin=1 : active detection (nmap)
    * (used in inventory...)
    */
    function get_ip_data($conn, $host, $origin) {
        $sql = "SELECT distinct(port) from host_services where ip =
                inet_aton(?)";
        if ($origin != "") $sql.= " and origin = '$origin' ";
        $params = array(
            $host
        );
        $list = array();
        if (!$aux = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        } else {
            while (!$aux->EOF) {
                $sql2 = "SELECT * from host_services where ip =
                inet_aton('$host') and port = '" . $aux->fields['port'] . "'";
                if ($origin != "") {
                    $sql2.= " and origin = '$origin' ";
                }
                $sql2.= " order by date desc";
                if (!$aux2 = & $conn->Execute($sql2)) {
                    print $conn->ErrorMsg();
                } else {
                    if ($aux2->RecordCount() != 0) {
                        $port = $aux2->fields["port"];
                        $nagios = $aux2->fields["nagios"];
                        $protocol = $aux2->fields["protocol"];
                        $version = $aux2->fields["version"];
                        $service = $aux2->fields["service"];
                        $service_type = $aux2->fields["service_type"];
                        $anom_origin = $aux2->fields["origin"];
                        $date = $aux2->fields["date"];
                        if (($aux2->fields["anom"] != "0") && ($aux2->RecordCount() > 1)) {
                            while (!$aux2->EOF) {
                                if ($aux2->fields["anom"] == "0") {
                                    $list[] = array(
                                        "host" => $host,
                                        "port" => $aux2->fields["port"],
                                        "protocol" => $aux2->fields["protocol"],
                                        "nagios" => "$nagios",
                                        "version" => $aux2->fields["version"],
                                        "service" => $aux2->fields["service"],
                                        "serv$ice_type" => $aux2->fields["service_type"],
                                        "date" => $aux2->fields["date"]
                                    );
                                }
                                $aux2->MoveNext();
                            }
                        } else { //if
                            $list[] = array(
                                "host" => $host,
                                "port" => $port,
                                "protocol" => $protocol,
                                "version" => $version,
                                "nagios" => "$nagios",
                                "service" => $service,
                                "service_type" => $service_type,
								"origin" => $anom_origin,
                                "date" => $date
                            );
                        }
                    }
                }
                $aux->MoveNext();
            }
        }
        return $list;
    }
    function get_list_count($conn) {
        $sql = "select count(*) from (SELECT distinct ip, sensor, port from host_services) table_alias";
        if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
        } else {
            return $rs->fields["count(*)"];
        }
    }
    function ack_ign($conn, $host, $port, $date, $sensor) {
        $sql = "SELECT *, inet_ntoa(ip) as ip_addr, inet_ntoa(sensor) as
                ip_sensor from host_services where ip= inet_aton(?) 
                and port = ? and date = ? and sensor=inet_aton(?)";
        $params = array(
            $host,
            $port,
            $date,
            $sensor
        );
        if (!$aux = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        } else {
            $protocol = $aux->fields["protocol"];
            $version = $aux->fields["version"];
            $service = $aux->fields["service"];
            $service_type = $aux->fields["service_type"];
            $origin = $aux->fields["origin"];
            $interface = $aux->fields["interface"];
        }
        $sql = "INSERT INTO host_services (ip, port, protocol, service,
                service_type, origin, date, sensor, interface, anom) 
                VALUES (inet_aton(?), ?, ?, ?, ?, ?, CURRENT_TIMESTAMP,
                inet_aton(?), ?, '0')";
        $params = array(
            $host,
            $port,
            $protocol,
            $service,
            $service_type,
            $origin,
            $sensor,
            $interface
        );
        if ($conn->Execute($sql, $params) === false) {
            print _("error inserting").": " . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        if ($op=="ack"){
            $infolog = array("Service", $host, $date, $sensor, ", port: $port");
            Log_action::log(71, $infolog);
        }
        else {
            $infolog = array("Service", $host, $date, $sensor, ", port: $port");
            Log_action::log(72, $infolog);
        }
    }
    function insert($conn, $host, $port, $date, $sensor, $protocol, $service, $service_type, $version, $origin, $nagios = 1) {
        $sql = "SELECT *, inet_ntoa(ip) as ip_addr, inet_ntoa(sensor) as
                ip_sensor from host_services where ip= inet_aton(?)
                and port = ? and sensor=inet_aton(?)";
        $params = array(
            $host,
            $port,
            $sensor
        );
        if (!$aux = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        } else {
            $protocol_aux = $aux->fields["protocol"];
            $version_aux = $aux->fields["version"];
            $service_aux = $aux->fields["service"];
            $service_type_aux = $aux->fields["service_type"];
        }
        if (($protocol_aux != $protocol) || ($version_aux != $version) || ($service_aux != $service) || ($service_type_aux != $service_type)) {
            $sql = "INSERT INTO host_services (ip, port, protocol, service,
                    service_type, origin, date, sensor, version, anom, nagios) VALUES 
                    (inet_aton(?), ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, inet_aton(?),
                    ?,'0',?)";
            $params = array(
                $host,
                $port,
                $protocol,
                $service,
                $service_type,
                $origin,
                $sensor,
                ($version=="") ? "unknown" : $version,
				$nagios
            );
            if ($conn->Execute($sql, $params) === false) {
                print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
                exit;
            }
        }
    }
    function delete($conn, $host) {
        $sql = "DELETE from host_services where ip = inet_aton(?) ";
        $params = array(
            $host
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function set_nagios($conn, $host, $port, $flag = 1) {
        if ($flag != 0 && $flag != 1) $flag = 1;
        $sql = "update host_services set nagios=? where ip=inet_aton(?) and port=? ";
        $params = array(
            $flag,
            $host,
            $port
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    /*
    * Return list of all ips (group by os)
    */
    function get_port_protocol_list($conn, $hosts, $filter = "") {
        $list = array();
        $sql = "SELECT inet_ntoa(h.ip) as ip_addr,h.port,p.name as protocol from host_services h,protocol p where h.protocol=p.id order by h.port";
        if (!$aux = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
            return $list;
        }
        do {
            $ip = $aux->fields["ip_addr"];
            $port = $aux->fields["port"];
            $proto = $aux->fields["protocol"];
            $name = ($hosts[$ip] != "") ? $hosts[$ip] : $ip;
            if ($filter == "" || ($filter != "" && (preg_match("/$filter/i", $ip) || preg_match("/$filter/i", $name)))) $list[$port . " " . $proto][$ip] = $name;
            $aux->MoveNext();
        }
        while (!$aux->EOF);
        return $list;
    }
}
?>
