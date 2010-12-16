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
* - Event_viewer()
* - get_tags()
* - init_plugins_conf()
* - get_list()
* - _my_sort_by_event_count()
* - display_by()
* - _resolve_plugin()
* - _my_rsort_by_key_lenght()
* - parse_col_tpl()
* - draw_event_info()
* - draw()
* Classes list:
* - Event_viewer
*/
/*
* TODO:
* - Custom search
* - Event delete/ignore (all, by filter, etc)
* - Graph stats (maybe in another file/page)
* - Integratation with the report system?
*/
require_once 'classes/Session.inc';
require_once 'classes/Security.inc';
require_once 'classes/Plugingroup.inc';
require_once 'classes/Util.inc';
require_once 'classes/Host.inc';
require_once 'classes/Net.inc';
class Event_viewer {
    /*
    The plugin_conf array tells the event viewer what info should be shown
    when the user displays the event detail.
    
    The array format is:
    
    $plugin_conf = array(
    // the plugin id in the format of single id or id range, ex: 234 or 230-250
    "ossim plugin id" => array(
    // the plugin sid in the format of single sid or sid range, ex: 10 or
    // 1-100
    "ossim plugin sid" => array(
    "field to display" => "label for that field",
    "field2 to display" => "label for that field2",
    ),
    // This default entry will be used when the event matches "ossim plugin id
    // but there aren't "ossim plugin sid" specified.
    "default" => array(
    "field to display" => "label for that field"
    )
    ),
    // This default entry will be used when the event plugin has no
    // a specific notation
    "default" => array (
    "field to display" => "label for that field"
    )
    
    )
    
    The available fields are:
    sid: snort sensor id
    cid: snort event id
    date: received event date
    plugin_id: ossim plugin id
    plugin_name: ossim plugin name
    plugin_desc: ossim plugin description
    plugin_sid: ossim plugin sid
    sid_name: signature name
    filename: field from snort.extra_data table
    username: ''
    password: ''
    userdata1: ''
    userdata2: ''
    userdata3: ''
    userdata4: ''
    userdata5: ''
    userdata6: ''
    userdata7: ''
    userdata8: ''
    userdata9: ''
    ip_src: the source ip of the event
    ip_dst: the destination ip of the event
    ip_proto: the ip protocol
    port_src: the source port
    port_dst: the destination port
    ip_port_src: the source ip and port in the format ip:port
    ip_port_dst: the destination ip and port in the format ip:port
    tcp_sport: if protocol is TCP, the source port
    tcp_dport: if protocol is TCP, the destination port
    udp_sport: if protocol is UDP, the source port
    udp_dport: if protocol is UDP, the destination port
    */
    var $plugin_conf = array();
    var $conn = false;
    var $snort = false;
    var $plugin_db = array();
    var $total_rows = false;
    var $results_per_page = 150;
    var $plugin_group = false;
    function Event_viewer($page, $table) {
        $db = new ossim_db();
        $this->conn = $db->connect();
        $this->snort = $db->snort_connect();
        if (isset($page['results_per_page'])) {
            $this->results_per_page = $page['results_per_page'];
        }
        if (!empty($page['plugin_group'])) {
            $this->plugin_group = $page['plugin_group'];
        }
        $this->table = $table;
    }
    /*
    * @static
    */
    function get_tags() {
        return array(
            'SID' => 'SID',
            'CID' => 'CID',
            'DATE' => 'Received event date',
            'PLUGIN_ID' => 'Plugin id',
            'PLUGIN_SID' => 'Plugin sid',
            'PLUGIN_NAME' => 'Plugin name',
            'PLUGIN_DESC' => 'Plugin description',
            'PLUGIN_SOURCE_TYPE' => 'Plugin source type',
            'PLUGIN_SID_CATEGORY' => 'Plugin SID category',
            'PLUGIN_SID_SUBCATEGORY' => 'Plugin SID subcategory',
            'SIGNATURE' => 'Signature name',
            'FILENAME' => 'ExtraData Filename',
            'USERNAME' => 'ExtraData Username',
            'PASSWORD' => 'ExtraData Password',
            'USERDATA1' => 'UserData 1',
            'USERDATA2' => 'UserData 2',
            'USERDATA3' => 'UserData 3',
            'USERDATA4' => 'UserData 4',
            'USERDATA5' => 'UserData 5',
            'USERDATA6' => 'UserData 6',
            'USERDATA7' => 'UserData 7',
            'USERDATA8' => 'UserData 8',
            'USERDATA9' => 'UserData 9',
            'PAYLOAD' => 'Payload',
            'IP_SRC' => 'Source IP',
            'IP_SRC_FQDN' => 'Resolved source IP FQDN',
            'IP_DST' => 'Destination IP',
            'IP_DST_FQDN' => 'Resolved destination IP FQDN',
            'IP_PROTO' => 'IP protocol',
            'PORT_SRC' => 'Source port',
            'PORT_DST' => 'Destination port',
            'IP_PORTSRC' => 'Source IP and port with IP:port format',
            'IP_PORTDST' => 'Destination IP and port with IP:port format',
            'RELIABILITY' => 'Reliability',
            'PRIORITY' => 'Priority',
            'ASSET' => 'Asset Value (Source and Destination)',
            'RISK' => 'Event Risk (Source and Destination)',
            'CONTEXT' => 'Context Analysis'
        );
    }
    function init_plugins_conf($conf = false) {
        if ($conf) {
            $this->plugin_conf = & $conf;
            return true;
        }
        $this->plugin_conf = array(
            "1518" => array(
                "10" => array(
                    'username' => _("Username") ,
                    'filename' => _("Printed Document") ,
                ) ,
                "592" => array(
                    'username' => _("Username") ,
                    'userdata1' => _("System Date") ,
                    'userdata2' => _("Process ID's") ,
                ) ,
                "default" => array(
                    'username' => _("Username") ,
                    'userdata1' => _("Userdata 1") ,
                    'userdata2' => _("Userdata 2") ,
                    'userdata3' => _("Userdata 3")
                ) ,
            ) ,
            "default" => array(
                'username' => _("Username") ,
                'userdata1' => _("Userdata 1") ,
                'userdata2' => _("Userdata 2") ,
                'userdata3' => _("Userdata 3") ,
                'userdata4' => _("Userdata 4") ,
                'userdata5' => _("Userdata 5") ,
                'userdata6' => _("Userdata 6") ,
                'userdata7' => _("Userdata 7") ,
                'userdata8' => _("Userdata 8") ,
                'userdata9' => _("Userdata 9")
            )
        );
    }
    /*
    * $options = array('host', 'date_from', 'date_to', 'results_per_page',
    *                  'from',
    *                  'total_rows'                  // if this param is not
    * passed, the function will calculate the total rows and save that value in
    * $total_rows
    */
	function GetSensorSids($db) {
		require_once ('ossim_db.inc');
		$dbsock = new ossim_db();
		$db = $dbsock->snort_connect();
		$sensors = array();
		$temp_sql = "SELECT * FROM sensor";
		$rs = $db->Execute($temp_sql);
		while (!$rs->EOF) {
			$sname = ($rs->fields["sensor"]) ? $rs->fields["sensor"] : preg_replace("/-.*/","",preg_replace("/.*\]\s*/","",$rs->fields['hostname']));
			$sensors[$sname][] = $rs->fields['sid'];
			$rs->MoveNext();
		}
		return $sensors;
	}
    function get_list($options) {
		$sensors = $hosts = $ossim_servers = array();
		list($sensors, $hosts) = Host::get_ips_and_hostname($this->conn);
		$networks = "";
		$_nets = Net::get_all($this->conn);
		$_nets_ips = $_host_ips = $_host = array();
		foreach ($_nets as $_net) $_nets_ips[] = $_net->get_ips();
		$networks = implode(",",$_nets_ips);
		$hosts_ips = array_keys($hosts);
		
        $from_date = !empty($options['date_from']) ? $options['date_from'] : false;
        $to_date = !empty($options['date_to']) ? $options['date_to'] : false;
        $host = !empty($options['host']) ? $options['host'] : false;
        /*user y event*/
		$from_user = !empty($options['username']) ?  $options['username'] : false;
		$from_event = !empty($options['event']) ? $options['event'] : false;
        $where = array();
        
		// User sensor filter:
		if (Session::allowedSensors() != "") {
			$user_sensors = explode(",",Session::allowedSensors());
			$snortsensors = $this->GetSensorSids($this->snort);
			$sensor_str = "";
			foreach ($user_sensors as $user_sensor)
				if (count($snortsensors[$user_sensor]) > 0) $sensor_str .= ($sensor_str != "") ? ",".implode(",",$snortsensors[$user_sensor]) : implode(",",$snortsensors[$user_sensor]);
			if ($sensor_str == "") $sensor_str = "0";
			$where[] = " e.sid in (" . $sensor_str . ")";
		}
		
		/* host filter */
        if ($host) {
            $where[] = "inet_aton('$host') = e.ip_src";
        }
        /* date range filter */
        if ($from_date) {
            list($y, $m, $d) = preg_split('#-#', $from_date);
            if (!checkdate($m, $d, $y)) {
                print _("Invalid date") . ": $from_date";
                return array();
            }
            $where[] = "(e.timestamp >= '$from_date')";
        }
        if ($to_date) {
            list($y, $m, $d) = preg_split('#-#', $to_date);
            if (!checkdate($m, $d, $y)) {
                print _("Invalid date") . ": $to_date";
                return array();
            }
            $where[] = "(e.timestamp <= '$to_date 23:59:59')";
        }
        /* username */
		 if ($from_user) {
            $where[] = "ed.username = '$from_user'";
        }
        /* restrict by plugin group */
        if ($this->plugin_group) {
            list($group) = Plugingroup::get_list($this->conn, "plugin_group.group_id = {$this->plugin_group}");
            $plugins = array();
            foreach($group->get_plugins() as $plugin_id => $data) {
                if ($data['sids'] == 0) {
                    $plugins[] = "e.plugin_id = '$plugin_id'";
                } else {
                    $sids = array();
                    foreach(explode(',', $data['sids']) as $sid) {
                        $sids[] = "e.plugin_sid = $sid";
                    }
                    $sids_sql = '(' . implode(' OR ', $sids) . ')';
                    $plugins[] = "e.plugin_id = '$plugin_id' AND $sids_sql";
                }
            }
            $where[] = '(' . implode(' OR ', $plugins) . ')';
        }
         /* event */
		 if ($from_event) {
            $where[] = "e.plugin_sid = '$from_event'";
        }
        /* number of events per page */
        $limit = "";
        if (isset($options['results_per_page']) && isset($options['from'])) {
            $limit = "LIMIT {$options['results_per_page']} OFFSET {$options['from']}";
        }
        $sql_where = count($where) ? 'AND ' . implode(" AND ", $where) : "";
        /* calculate total rows */
        if (!$this->total_rows) {
            $sql = "
            SELECT
                count(*) as nrows
            FROM
                acid_event e,
                extra_data ed,
                iphdr i
            WHERE
                e.sid = ed.sid AND
                e.cid = ed.cid AND
                e.sid = i.sid AND
                e.cid = i.cid
                $sql_where
            ";
            if (!$rs = $this->snort->Execute($sql)) {
                die($this->snort->ErrorMsg());
            }
            $this->total_rows = $rs->fields['nrows'];
        }
		/*
        $sql = "
        SELECT
            e.sid,
            e.cid,
            e.timestamp as date,
            e.plugin_id AS plugin_id,
            e.plugin_sid AS plugin_sid,
            ed.filename,
            ed.username,
            ed.password,
            ed.userdata1,
            ed.userdata2,
            ed.userdata3,
            ed.userdata4,
            ed.userdata5,
            ed.userdata6,
            ed.userdata7,
            ed.userdata8,
            ed.userdata9,
            inet_ntoa(ip.ip_src) AS ip_src,
            inet_ntoa(ip.ip_dst) AS ip_dst,
            ip.ip_proto AS ip_proto,
            tcp.tcp_sport,
            tcp.tcp_dport,
            udp.udp_sport,
            udp.udp_dport
        FROM
            acid_event e,
            extra_data ed,
            iphdr ip
        LEFT JOIN tcphdr AS tcp ON ip.sid = tcp.sid AND ip.cid = tcp.cid
        LEFT JOIN udphdr AS udp ON ip.sid = udp.sid AND ip.cid = udp.cid
        WHERE
            e.sid = ed.sid AND
            e.sid = ip.sid AND
            e.cid = ed.cid AND
            e.cid = ip.cid
            $sql_where
        order by e.timestamp desc
        $limit
        ";
		*/
		$sql = "SELECT e.sid, e.cid, e.timestamp as date,e.plugin_id AS plugin_id, e.plugin_sid AS plugin_sid, ed.filename, ed.username, ed.password, ed.userdata1, ed.userdata2, ed.userdata3, ed.userdata4, ed.userdata5, ed.userdata6, ed.userdata7, ed.userdata8, ed.userdata9, inet_ntoa(e.ip_src) AS ip_src, inet_ntoa(e.ip_dst) AS ip_dst, e.ip_proto AS ip_proto, e.layer4_sport as tcp_sport, e.layer4_dport as tcp_dport FROM acid_event e  LEFT JOIN extra_data AS ed ON ed.sid=e.sid AND ed.cid=e.cid  WHERE 1 $sql_where order by e.timestamp desc $limit";
        //print_r($sql); exit;
        $this->snort->SetFetchMode(ADODB_FETCH_ASSOC);
        if (!$rs = $this->snort->Execute($sql)) {
            die($this->snort->ErrorMsg());
        }
        $list = array();
        while (!$rs->EOF) {
            $plugin = $this->_resolve_plugin($rs->fields['plugin_id'], $rs->fields['plugin_sid']);
            if ($plugin) {
                $rs->fields['plugin_name'] = $plugin['name'];
                $rs->fields['plugin_desc'] = $plugin['description'];
                $rs->fields['sid_name'] = $plugin['sid_name'];
            } else {
                $rs->fields['plugin_name'] = _("plugin id") . " " . $rs->fields['plugin_id'];
                $rs->fields['plugin_desc'] = _("n/a");
                $rs->fields['sid_name'] = _("plugin sid") . " " . $rs->fields['plugin_sid'];
            }
            $src = $rs->fields['ip_src'] != '0.0.0.0' ? $rs->fields['ip_src'] : '';
            $dst = $rs->fields['ip_dst'] != '0.0.0.0' ? $rs->fields['ip_dst'] : '';
			$homelan_src = (Net::isIpInNet($src, $networks) || in_array($src, $hosts_ips)) ? " <a href='javascript:;' class='scriptinfo' style='text-decoration:none' ip='$src'><img src=\"../forensics/images/homelan.png\" align=absmiddle border=0></a>" : "";
			$homelan_dst = (Net::isIpInNet($dst, $networks) || in_array($dst, $hosts_ips)) ? " <a href='javascript:;' class='scriptinfo' style='text-decoration:none' ip='$dst'><img src=\"../forensics/images/homelan.png\" align=absmiddle border=0></a>" : "";
            switch ($rs->fields['ip_proto']) {
                case 6: //tcp
					$rs->fields['port_src'] = $rs->fields['tcp_sport'];
                    $rs->fields['port_dst'] = $rs->fields['tcp_dport'];
                    $rs->fields['ip_portsrc'] = $src ? $rs->fields['ip_src'] . ':' . $rs->fields['tcp_sport'] . $homelan_src : '';
                    $rs->fields['ip_portdst'] = $dst ? $rs->fields['ip_dst'] . ':' . $rs->fields['tcp_dport'] . $homelan_dst : '';
                    
					break;

                case 17: //udp
                    $rs->fields['port_src'] = $rs->fields['udp_sport'];
                    $rs->fields['port_dst'] = $rs->fields['udp_dport'];
                    $rs->fields['ip_portsrc'] = $src ? $rs->fields['ip_src'] . ':' . $rs->fields['udp_sport'] . $homelan_src : '';
                    $rs->fields['ip_portdst'] = $dst ? $rs->fields['ip_dst'] . ':' . $rs->fields['udp_dport'] . $homelan_dst : '';
                    break;

                default:
                    $rs->fields['port_src'] = '';
                    $rs->fields['port_dst'] = '';
                    $rs->fields['ip_portsrc'] = $src ? $rs->fields['ip_src'] . $homelan_src : '';
                    $rs->fields['ip_portdst'] = $dst ? $rs->fields['ip_dst'] . $homelan_dst : '';
            }
            $list[] = $rs->fields;
            $rs->MoveNext();
        }
        return $list;
    }
    /*
    * Used inside display_by()
    */
    function _my_sort_by_event_count($a, $b) {
        global $list;
        return (count($list[$a]) < count($list[$b])) ? 1 : 0;
    }
    function display_by($field, $data) {
        global $list;
        $list = array();
        foreach($data as $values) {
            list($day,) = explode(' ', $values['date']);
            $values['day'] = $day;
            $list[$values[$field]][] = $values;
        }
        // Sort by number of entries when not ordered by date
        if ($field != 'day') {
            uksort($list, array(
                'Event_viewer',
                '_my_sort_by_event_count'
            ));
        }
        return $list;
    }
    function _resolve_plugin($plugin_id, $plugin_sid) {
        if (!isset($this->plugin_db[$plugin_id][$plugin_sid])) {
            $sql = "SELECT
                        sid.plugin_id as id,
                        sid.sid,
                        p.type,
                        p.description,
                        p.name,
                        sid.name as sid_name
                    FROM
                        plugin p,
                        plugin_sid sid
                    WHERE
                        p.id = sid.plugin_id AND
                        sid.plugin_id = ? AND
                        sid.sid = ?
                    ";
            if (!$rs = $this->conn->Execute($sql, array(
                $plugin_id,
                $plugin_sid
            ))) {
                die($this->conn->ErrorMsg());
            }
            if (!$rs->EOF) {
                $this->plugin_db[$rs->fields['id']][$rs->fields['sid']] = $rs->fields;
                return $rs->fields;
            }
            return false;
        }
        return $this->plugin_db[$plugin_id][$plugin_sid];
    }
    function _my_rsort_by_key_lenght($a, $b) {
        if (strlen($a) == strlen($b)) {
            return 0;
        }
        return (strlen($a) > strlen($b)) ? -1 : 1;
    }
    function parse_col_tpl($data, $col) {
        $tpl = $this->table[$col]['contents'];
        $data = array_change_key_case($data, CASE_UPPER);
        // order by reverse key lenght to avoid wrong replacements
        // ie with the string SIDNAME avoid 1518NAME instead of "Attack foo"
        uksort($data, array(
            'Event_viewer',
            '_my_rsort_by_key_lenght'
        ));
        return str_replace(array_keys($data) , array_values($data) , $tpl);
    }
    function draw_event_info($info) {
        $conf = $this->plugin_conf;
        $html = '<table width="100%"><tr><td style="border-width: 0px; text-align: left">';
        $choice = $conf['default'];
        unset($conf['default']);
        // Run all the IDs
        foreach($conf as $ids => $sids) {
            if (strpos($ids, '-') === false) {
                $min = $max = $ids;
            } else {
                list($min, $max) = explode('-', $ids);
            }
            // Found the range in IDs
            if (($info['plugin_id'] >= $min) && ($info['plugin_id'] <= $max)) {
                // Use default if any
                if (isset($sids['default'])) {
                    $choice = $sids['default'];
                    unset($sids['default']);
                }
                // Run all the SIDs
                foreach($sids as $sid => $sid_conf) {
                    if (strpos($sid, '-') === false) {
                        $min = $max = $sid;
                    } else {
                        list($min, $max) = explode('-', $sid);
                    }
                    // Found the range in SIDs
                    if (($info['plugin_sid'] >= $min) && ($info['plugin_sid'] <= $max)) {
                        $choice = $sid_conf;
                        break 2;
                    }
                }
                break;
            }
        }
        // Print plugin info if any
        if ($info['plugin_name']) {
            $html.= "
            <b>Plugin:</b> {$info['plugin_name']} ({$info['plugin_id']})<br/>
            <b>Plugin SID:</b> {$info['sid_name']} ({$info['plugin_sid']})<br/>";
        }
        // Print rows with custom format
        foreach($choice as $col => $label) {
            if ($info[$col]) {
                $html.= "<b>$label:</b> " . $info[$col] . "<br/>";
            }
        }
        $html.= '</td></tr></table>';
        return $html;
    }
    function draw() {
        include 'classes/Event_viewer_draw.inc';
        exit;
    }
}
?>
