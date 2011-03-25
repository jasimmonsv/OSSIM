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
* - get_mac_vendor_list()
* Classes list:
* - Host_mac
*/
require_once ("ossim_db.inc");
require_once ('classes/Log_action.inc');

class Host_mac {
    /*
    * Return list of the all records (anomalies or not) for each host
    * An inferior and superior limit of record is given
    * (used by secondary specific anomalie screen)
    */
    function get_list($conn, $inf, $sup) {
        require_once ('classes/Session.inc');
        require_once ('classes/Security.inc');
        $nanoms = 0;
        $nanoms = (int)$nanoms;
        $inf = (int)$inf;
        $sup = (int)$sup;
        $nanoms = $sup - $inf;
        $sql = "SELECT distinct ip, sensor FROM host_mac LIMIT ? OFFSET ?";

        $params = array(
            $nanoms,
            $inf
        );
        $list = array();
        if (!$ips = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$ips->EOF) {
                $sql2 = "SELECT *, inet_ntoa(sensor) as ip_sensor, inet_ntoa(ip) as ip_addr
                         from host_mac where  ip= ? and  sensor = ? order by date desc";
                $params2 = array(
                    $ips->fields["ip"],
                    $ips->fields["sensor"]
                );
                if (!$aux = & $conn->Execute($sql2, $params2)) {
                    print $conn->ErrorMsg();
                } elseif ((Session::hostAllowed($conn, $aux->fields["ip_addr"])) and (Session::hostAllowed($conn, $aux->fields["ip_sensor"]))) {
                    if (($aux->fields["anom"] == "0") || ($aux->RecordCount() == "1")) {
                        $list[] = array(
                            "ip" => $aux->fields["ip_addr"],
                            "mac" => $aux->fields["mac"],
                            "date" => $aux->fields["date"],
                            "old_mac" => "-",
                            "old_date" => "-",
                            "old_vendor" => "-",
                            "sensor" => $aux->fields["ip_sensor"],
                            "vendor" => $aux->fields["vendor"],
                            "interface" => $aux->fields["interface"]
                        );
                    } else {
                        $ip = $aux->fields["ip_addr"];
                        $mac = $aux->fields["mac"];
                        $vendor = $aux->fields["vendor"];
                        $date = $aux->fields["date"];
                        $sensor = $aux->fields["ip_sensor"];
                        $interface = $aux->fields["interface"];
                        
                        $old_mac = "-";
                        $old_vendor = "-";
                        $old_date = "-";
                        
                        $previous = false;

                        $aux->MoveNext();
                        while ((!$aux->EOF) && ($aux->fields["anom"] != "0")) {
                            if ($aux->fields["anom"] == "1" && !$previous && $aux->fields["mac"]!=$mac) {
                                $previous = true;
                                $previous_mac = $aux->fields["mac"];
                                $previous_vendor = $aux->fields["vendor"];
                                $previous_date = $aux->fields["date"];
                            }
                            $aux->MoveNext();
                        }
                        
                        if($previous) {
                            $old_mac = $previous_mac;
                            $old_vendor = $previous_vendor;
                            $old_date = $previous_date;
                        }
                        
                        if ($aux->fields["anom"] == "0") {
                            $list[] = array(
                                "ip" => $ip,
                                "mac" => $mac,
                                "date" => $date,
                                "old_mac" => $aux->fields["mac"],
                                "old_date" => $aux->fields["date"],
                                "sensor" => $sensor,
                                "vendor" => $vendor,
                                "old_vendor" => $aux->fields["vendor"],
                                "interface" => $interface
                            );
                        } else {
                            $list[] = array(
                                "ip" => $ip,
                                "mac" => $mac,
                                "date" => $date,
                                "old_mac" => $old_mac,
                                "old_date" => $old_date,
                                "sensor" => $sensor,
                                "vendor" => $vendor,
                                "old_vendor" => $old_vendor,
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
    function get_ip_list($conn, $host, $sensor) {
        require_once ('classes/Session.inc');
        require_once ('classes/Security.inc');
        $list = array();
        if (!(Session::hostAllowed($conn, $host)) or !(Session::hostAllowed($conn, $sensor))) return $list;
        $sql = "SELECT *, inet_ntoa(sensor) as ip_sensor, inet_ntoa(ip) as  ip_addr from host_mac 
                where ip = inet_aton(?) and sensor = inet_aton(?) order by date desc";
        $params = array(
            $host,
            $sensor
        );
        if (!$aux = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
            return $list;
        }
        if ($aux->RecordCount() == "1") {
            $list[] = array(
                "ip" => $aux->fields["ip_addr"],
                "mac" => $aux->fields["mac"],
                "date" => $aux->fields["date"],
                "old_mac" => $aux->fields["mac"],
                "old_date" => $aux->fields["date"],
                "sensor" => $aux->fields["ip_sensor"],
                "vendor" => $aux->fields["vendor"],
                "old_vendor" => $aux->fields["vendor"],
                "interface" => $aux->fields["interface"]
            );
            return $list;
        }
        $ip = $aux->fields["ip_addr"];
        $sensor = $aux->fields["ip_sensor"];
        $interface = $aux->fields["interface"];
        $mac = $aux->fields["mac"];
        $vendor = $aux->fields["vendor"];
        $date = $aux->fields["date"];
        $anom = $aux->fields["anom"];
        $aux->MoveNext();
        do {
            $old_mac = $aux->fields["mac"];
            $old_date = $aux->fields["date"];
            $old_vendor = $aux->fields["vendor"];
            if ($anom == "0") {
                $list[] = array(
                    "ip" => $ip,
                    "mac" => $mac,
                    "date" => $date,
                    "old_mac" => $mac,
                    "old_date" => $date,
                    "sensor" => $sensor,
                    "vendor" => $vendor,
                    "old_vendor" => $vendor,
                    "interface" => $interface
                );
            } else {
                $list[] = array(
                    "ip" => $ip,
                    "mac" => $mac,
                    "date" => $date,
                    "old_mac" => $old_mac,
                    "old_date" => $old_date,
                    "sensor" => $sensor,
                    "vendor" => $vendor,
                    "old_vendor" => $old_vendor,
                    "interface" => $interface
                );
            }
            $mac = $old_mac;
            $date = $old_date;
            $vendor = $old_vendor;
            $anom = $aux->fields["anom"];
            $aux->MoveNext();
        }
        while (!$aux->EOF);
        $list[] = array(
            "ip" => $ip,
            "mac" => $mac,
            "date" => $date,
            "old_mac" => $old_mac,
            "old_date" => $old_date,
            "sensor" => $sensor,
            "vendor" => $vendor,
            "old_vendor" => $old_vendor,
            "interface" => $interface
        );
        return $list;
    }
    /*
    * Return list of the last anomalie for each host (if any)
    * A total of '$limit' records is calculated (one record per host)
    * (used by control pannel/anomalies on host summary and by secondary specific anomalie screen)
    */
    function get_anom_list($conn, $where_clause = "", $limit = 10) {
        require_once ('classes/Session.inc');
        require_once ('classes/Security.inc');
        $list = array();
        $sql = "SELECT distinct inet_ntoa(ip) as ip, inet_ntoa(sensor) as sensor FROM host_mac $where_clause";
        //echo "<br>f[$sql]";
        if (!$ips = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
        } else {
            $num_anom = 0; 
            while ((!$ips->EOF) && (($num_anom < $limit) || ($limit == "all"))) {
                if ((Session::hostAllowed($conn, $ips->fields["ip"])) and (Session::hostAllowed($conn, $ips->fields["sensor"]))) {
					$sql = "SELECT *, inet_ntoa(sensor) AS ip_sensor, inet_ntoa(ip) AS ip_addr FROM host_mac
                            where ip = inet_aton(?) AND sensor = inet_aton(?) ORDER BY date DESC";
                    //echo "<br>d[$sql]";
                    $params = array(
                        $ips->fields["ip"],
                        $ips->fields["sensor"]
                    );
                    if (!$aux = & $conn->Execute($sql, $params)) {
                        print $conn->ErrorMsg();
                    } elseif ($aux->fields["anom"] != "0") {
                        $ip = $aux->fields["ip_addr"];
                        $mac = $aux->fields["mac"];
                        $vendor = $aux->fields["vendor"];
                        $date = $aux->fields["date"];
                        $sensor = $aux->fields["ip_sensor"];
                        $interface = $aux->fields["interface"];
                        
                        $old_mac = "-";
                        $old_vendor = "-";
                        $old_date = "-";

                        $previous = false;
                        $found_zero = false;
                        
                        while ($aux->MoveNext() && !$found_zero) {
                            if ($aux->fields["anom"] == "1" && !$previous && $aux->fields["mac"]!=$mac) {
                                $previous = true;
                                $previous_mac = $aux->fields["mac"];
                                $previous_vendor = $aux->fields["vendor"];
                                $previous_date = $aux->fields["date"];
                            }
                            if ($aux->fields["anom"] == "0") {
                                $old_mac = $aux->fields["mac"];
                                $old_vendor = $aux->fields["vendor"];
                                $old_date = $aux->fields["date"];
                                $found_zero = true;
                            }
                        }
                        
                        if(!$found_zero && $previous) {
                                $old_mac = $previous_mac;
                                $old_vendor = $previous_vendor;
                                $old_date = $previous_date;
                        }
                        
                        $num_anom++;
                        $list[] = array(
                            "ip" => $ip,
                            "mac" => $mac,
                            "date" => $date,
                            "old_mac" => $old_mac,
                            "old_date" => $old_date,
                            "sensor" => $sensor,
                            "vendor" => $vendor,
                            "old_vendor" => $old_vendor,
                            "interface" => $interface
                        );
                        
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
    function get_anom_ip_list($conn, $host, $sensor) {
        require_once ('classes/Session.inc');
        require_once ('classes/Security.inc');
        $list = array();
        if (!(Session::hostAllowed($conn, $host)) or !(Session::hostAllowed($conn, $sensor))) return $list;
        $sql = "SELECT *, inet_ntoa(sensor) AS ip_sensor, inet_ntoa(ip) AS ip_addr FROM host_mac
                WHERE ip = inet_aton(?) AND sensor = inet_aton(?) ORDER BY date DESC";
        $params = array(
            $host,
            $sensor
        );
        if (!$aux = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        } elseif ($aux->fields["anom"] != "0") {
            $mac = $aux->fields["mac"];
            $vendor = $aux->fields["vendor"];
            $date = $aux->fields["date"];
            $sensor = $aux->fields["ip_sensor"];
            $interface = $aux->fields["interface"];
            $next_anom = true;
            while ($aux->MoveNext() && $next_anom) {
                if ($aux->fields["anom"] == "0") $next_anom = false;
                $old_mac = $aux->fields["mac"];
                $old_date = $aux->fields["date"];
                $old_vendor = $aux->fields["vendor"];
                $list[] = array(
                    "ip" => $host,
                    "mac" => $mac,
                    "date" => $date,
                    "vendor" => $vendor,
                    "old_vendor" => $old_vendor,
                    "old_mac" => $old_mac,
                    "old_date" => $old_date,
                    "sensor" => $sensor,
                    "interface" => $interface
                );
                $mac = $old_mac;
                $date = $old_date;
                $vendor = $old_vendor;
            }
        }
        return $list;
    }
    /*
    * Get seen mac for this host (not return anomalies)
    * (used in inventory...)
    */
    function get_ip_data($conn, $host) {
        $sql = "SELECT * from host_mac where ip = inet_aton(?) order by date desc";
        $params = array(
            $host
        );
        if (!$aux = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        } else {
            $mac = $aux->fields["mac"];
            $vendor = $aux->fields["vendor"];
            if (($aux->fields["anom"] != "0") && ($aux->RecordCount() > 1)) {
                while (!$aux->EOF) {
                    if ($aux->fields["anom"] == "0") {
                        return array(
                            "host" => $host,
                            "mac" => $aux->fields["mac"],
                            "vendor" => $aux->fields["vendor"]
                        );
                    }
                    $aux->MoveNext();
                }
            }
            return array(
                "host" => $host,
                "mac" => $mac,
                "vendor" => $vendor
            );
        }
    }
    function get_list_count($conn) {
        $sql = "SELECT count(distinct(ip)) from host_mac";
        if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
        } else {
            return $rs->fields["count(distinct(ip))"];
        }
    }
    function ack_ign($conn, $host, $date, $sensor, $op="") {
        $sql = "SELECT *, inet_ntoa(ip) as ip_addr, inet_ntoa(sensor) as ip_sensor from host_mac 
                where ip = inet_aton(?) and date = ? and sensor = inet_aton(?)";
        $params = array(
            $host,
            $date,
            $sensor
        );
        if (!$aux = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        } else {
            $mac = $aux->fields["mac"];
            $vendor = $aux->fields["vendor"];
            $iface = $aux->fields["interface"];
        }
        $sql = "INSERT IGNORE INTO host_mac (ip, mac, vendor, date, sensor, interface, anom) VALUES 
                (inet_aton(?), ?, ?, CURRENT_TIMESTAMP, inet_aton(?), ?, '0')";
        $params = array(
            $host,
            $mac,
            $vendor,
            $sensor,
            $iface
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        if ($op=="ack"){
            $infolog = array("Mac", $host, $date, $sensor, "");
            Log_action::log(71, $infolog);
        }
        else {
            $infolog = array("Mac", $host, $date, $sensor, "");
            Log_action::log(72, $infolog);
        }
    }
    /*
    * Return list of all ips (group by mac, vendor)
    */
    function get_mac_vendor_list($conn, $hosts, $filter = "") {
        $vendors = array(); // first load vendors
        $sql = "SELECT * from host_mac_vendors";
        if (!$aux = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
            return $vendors;
        }
        do {
            $mac = $aux->fields["mac"];
            $vendor = $aux->fields["vendor"];
            $vendors[$mac] = $vendor;
            $aux->MoveNext();
        }
        while (!$aux->EOF);
        //
        $list = array();
        $sql = "SELECT inet_ntoa(ip) as ip_addr,mac from host_mac order by mac";
        if (!$aux = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
            return $list;
        }
        do {
            $mac = substr($aux->fields["mac"], 0, 8);
            $ip = $aux->fields["ip_addr"];
            $vendor = ($vendors[$mac] != "") ? $vendors[$mac] : $mac;
            $name = ($hosts[$ip] != "") ? $hosts[$ip] : $ip;
            if ($filter == "" || ($filter != "" && (preg_match("/$filter/i", $ip) || preg_match("/$filter/i", $name)))) $list[$vendor][$ip] = $name;
            $aux->MoveNext();
        }
        while (!$aux->EOF);
        return $list;
    }
}
?>
