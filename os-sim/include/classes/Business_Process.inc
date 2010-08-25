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
* - BP_Asset()
* - get()
* - get_name()
* - get_description()
* - get_members()
* - get_responsibles()
* - get_measure_link()
* - get_measure_type_str()
* - delete()
* Classes list:
* - BP_Asset
*/
/*
variables example for 'error_msg' and 'error_link'

<NAME>  172.22.0.193
<TYPE>  host
<DATE>  2008-02-05 11:25:20
<MEASURE_TYPE>  host_metric
<SEVERITY>  10
*/
$BP_properties = array(
    'host_alarm' => array(
        'translation' => gettext('Host Alarm') ,
        'error_msg' => gettext('Host {NAME} with open alarm(s)') ,
        'error_link' => '../control_panel/alarm_console.php?src_ip={NAME}&dst_ip={NAME}'
    ) ,
    'host_metric' => array(
        'translation' => gettext('Host Metric') ,
        'error_msg' => gettext('Host {NAME} compromised or attacked') ,
        'error_link' => '../report/metrics.php?host={NAME}'
    ) ,
    'host_vulnerability' => array(
        'translation' => gettext('Host Vulnerabilties') ,
        'error_msg' => gettext('Host {NAME} is vulnerable') ,
        'error_link' => '../vulnmeter/index.php?noimages=1&host={NAME}'
    ) ,
    'host_incident' => array(
        'translation' => gettext('Host Incident') ,
        'error_msg' => gettext('Open incident(s) related to host {NAME}') ,
        'error_link' => '../incidents/index.php?advanced_search=1&title={NAME}'
    ) ,
    'host_incident_alarm' => array(
        'translation' => gettext('Host Incident Alarm') ,
        'error_msg' => gettext('Open incident(s) of type Alarm related to
                                      host {NAME}') ,
        'error_link' => '../incidents/index.php?advanced_search=1&with_text={NAME}&ref=Alarm'
    ) ,
    'host_incident_event' => array(
        'translation' => gettext('Host Incident Event') ,
        'error_msg' => gettext('Open incident(s) of type Event related to
                                      host {NAME}') ,
        'error_link' => '../incidents/index.php?advanced_search=1&with_text={NAME}&ref=Event'
    ) ,
    'host_incident_metric' => array(
        'translation' => gettext('Host Incident Metric') ,
        'error_msg' => gettext('Open incident(s) of type Metric related to
                                      host {NAME}') ,
        'error_link' => '../incidents/index.php?advanced_search=1&with_text={NAME}&ref=Metric'
    ) ,
    'host_incident_anomaly' => array(
        'translation' => gettext('Host Incident Anomaly') ,
        'error_msg' => gettext('Open incident(s) of type Anomaly related
                                      to host {NAME}') ,
        'error_link' => '../incidents/index.php?advanced_search=1&with_text={NAME}&ref=Anomaly'
    ) ,
    'host_incident_vulns' => array(
        'translation' => gettext('Host Incident Vulnerability') ,
        'error_msg' => gettext('Open incident(s) of type Vulnerability 
                                      related to host {NAME}') ,
        'error_link' => '../incidents/index.php?advanced_search=1&with_text={NAME}&ref=Vulnerability'
    ) ,
    'host_availability' => array(
        'translation' => gettext('Host Availability') ,
        'error_msg' => gettext('Host {NAME} is down or unreachable') ,
        'error_link' => '/nagios/cgi-bin/status.cgi?host={NAME}'
    ) ,
    'net_metric' => array(
        'translation' => gettext('Net Metric') ,
        'error_msg' => gettext('Net {NAME} compromised or attacked') ,
        'error_link' => '../control_panel/show_image.php?range=all&ip={NAME}&what=compromise&start=N-1D&type=net&zoom=1'
    ) ,
    'net_vulnerability' => array(
        'translation' => gettext('Net Vulnerability') ,
        'error_msg' => gettext('Net {NAME} is vulnerable') ,
        'error_link' => '../vulnmeter/index.php?noimages=1&net={NAME}'
    ) ,
    'global_metric' => array(
        'translation' => gettext('Global Metric') ,
        'error_msg' => gettext('Increased global metric') ,
        'error_link' => '../control_panel/show_image.php?range=all&ip=global_admin&what=compromise&start=N-1D&type=global&zoom=1'
    ) ,
    'global_vulnerability' => array(
        'translation' => gettext('Global Vulnerability') ,
        // TODO: error_msg & error_link
        
    )
);
class BP_Asset {
    var $id;
    var $name;
    var $description;
    var $members;
    var $responsibles;
    function BP_Asset($id, $name, $description, $members, $responsibles) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->members = $members;
        $this->responsibles = $responsibles;
    }
    /*
    * static
    */
    function get($conn, $id) {
        // Name and Description
        $sql = "SELECT name, description FROM bp_asset WHERE id=?";
        $row = $conn->GetRow($sql, array(
            $id
        ));
        if ($row === false) {
            die($conn->ErrorMsg());
        }
        $name = $row['name'];
        $description = $row['description'];
        // Responsibles
        $sql = "SELECT u.login, u.name, u.email
                FROM users u, bp_asset_responsible resp
                WHERE u.login = resp.login AND resp.asset_id = ?
                ORDER BY u.name";
        $data = $conn->GetAll($sql, array(
            $id
        ));
        if ($data === false) {
            die($conn->ErrorMsg());
        }
        $responsibles = $data;
        // Members
        $sql = "SELECT
                    mem.member as name,
                    mem.member_type as type,
                    status.status_date as date,
                    status.measure_type as measure_type,
                    status.severity as severity
                FROM
                    bp_asset_member mem
                LEFT JOIN bp_member_status AS status ON mem.member = status.member
                WHERE
                    mem.asset_id=?
                ORDER BY mem.member_type, mem.member";
        if (!$rs = $conn->Execute($sql, array(
            $id
        ))) {
            die($conn->ErrorMsg());
        }
        $members = array();
        while (!$rs->EOF) {
            $members[] = $rs->fields;
            $rs->MoveNext();
        }
        return new BP_Asset($id, $name, $description, $members, $responsibles);
    }
    function get_name() {
        return $this->name;
    }
    function get_description() {
        return $this->description;
    }
    function get_members() {
        return $this->members;
    }
    function get_responsibles() {
        return $this->responsibles;
    }
    function get_measure_link($member) {
        global $BP_properties;
        $link = "&nbsp;";
        $measure_type = $member['measure_type'];
        $name = $member['name'];
        if (array_key_exists($measure_type, $BP_properties)) {
            $error_msg = "";
            if (array_key_exists('error_msg', $BP_properties[$measure_type])) {
                $error_msg = $BP_properties[$measure_type]['error_msg'];
                $error_msg = ereg_replace('{NAME}', $name, $error_msg);
                $error_msg = ereg_replace('{TYPE}', $name, $error_msg);
                $error_msg = ereg_replace('{DATE}', $name, $error_msg);
                $error_msg = ereg_replace('{MEASURE_TYPE}', $name, $error_msg);
                $error_msg = ereg_replace('{SEVERITY}', $name, $error_msg);
            }
            $error_link = "";
            if (array_key_exists('error_link', $BP_properties[$measure_type])) {
                $error_link = $BP_properties[$measure_type]['error_link'];
                $error_link = ereg_replace('{NAME}', $name, $error_link);
                $error_link = ereg_replace('{TYPE}', $name, $error_link);
                $error_link = ereg_replace('{DATE}', $name, $error_link);
                $error_link = ereg_replace('{MEASURE_TYPE}', $name, $error_link);
                $error_link = ereg_replace('{SEVERITY}', $name, $error_link);
            }
            if ($error_msg and $error_link) return "<a href=\"$error_link\">$error_msg</a>";
            elseif ($error_msg) return $error_msg;
            elseif ($error_link) return "<a href=\"$error_link\">Reason</a>";
        }
        return $link;
    }
    function get_measure_type_str($measure) {
        global $BP_properties;
        if (array_key_exists($measure, $BP_properties)) {
            return $BP_properties[$measure]['translation'];
        } elseif ($measure == 'all') {
            return "All measures";
        } elseif ($measure == null) {
            return "No measures found";
        } else {
            return $measure;
        }
    }
    /*
    * @static
    */
    function delete($conn, $asset_id) {
        // remove the asset from all processes
        $sql = "DELETE FROM bp_process_asset_reference WHERE asset_id=?";
        if (!$conn->Execute($sql, array(
            $asset_id
        ))) {
            die($conn->ErrorMsg());
        }
        // remove all members who belongs to this asset
        $sql = "DELETE FROM bp_asset_member WHERE asset_id=?";
        if (!$conn->Execute($sql, array(
            $asset_id
        ))) {
            die($conn->ErrorMsg());
        }
        // remove responsibles for that asset
        $sql = "DELETE FROM bp_asset_responsible WHERE asset_id=?";
        if (!$conn->Execute($sql, array(
            $asset_id
        ))) {
            die($conn->ErrorMsg());
        }
        // remove the asset
        $sql = "DELETE FROM bp_asset WHERE id=?";
        if (!$conn->Execute($sql, array(
            $asset_id
        ))) {
            die($conn->ErrorMsg());
        }
        return true;
    }
}
?>
