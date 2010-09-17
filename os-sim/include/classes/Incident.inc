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
* - Incident()
* - get_id()
* - get_title()
* - get_ref()
* - get_type()
* - get_type_keywords()
* - set_type_keywords()
* - set_type_descr()
* - get_type_descr()
* - get_submitter()
* - get_priority()
* - get_status()
* - get_in_charge()
* - get_tags()
* - get_event_start()
* - get_event_end()
* - get_in_charge_name()
* - get_last_modification()
* - get_date()
* - get_life_time()
* - get_ticket()
* - get_priority_bgcolor()
* - get_priority_fgcolor()
* - get_priority_string()
* - get_priority_in_html()
* - get_validations()
* - colorize_status()
* - get_list()
* - get_list_type_descr()
* - get_keywords_from_type()
* - search()
* - _build_sql()
* - _sort_by_life_time()
* - get_tickets()
* - get_alarms()
* - get_events()
* - get_metrics()
* - get_anomalies()
* - get_vulnerabilities()
* - get_users_list()
* - get_subscribed_users()
* - user_incident_perms()
* - user_ticket_perms()
* - print_td_priority()
* - print_td_incident_type()
* - __insert()
* - insert_alarm()
* - insert_event()
* - insert_anomaly()
* - insert_vulnerability()
* - insert_metric()
* - insert_subscription()
* - __update()
* - update_alarm()
* - update_event()
* - update_metric()
* - update_anomaly()
* - update_vulnerability()
* - delete()
* - delete_subscriptions()
* - incident_search()
* - incidents_by_type()
* - incidents_by_type_descr()
* - incidents_by_status()
* - incidents_by_user()
* - chk_status()
* Classes list:
* - Incident
*/
require_once 'classes/Security.inc';
require_once 'classes/Util.inc';
require_once 'classes/Log_action.inc';
require_once 'ossim_db.inc';
define('HIGH_PRIORITY', 7);
define('MEDIUM_PRIORITY', 4);
define('LOW_PRIORITY', 1);
class Incident {
    var $id;
    var $title;
    var $date;
    var $ref;
    var $type;
    var $type_descr;
    var $type_keywords;
    var $submitter;
    var $priority;
    var $in_charge;
    var $status;
    var $last_update;
    var $event_start;
    var $event_end;
	var $src_ips;
    /*
    * array of TAG IDs related to this incident
    */
    var $tags;
    var $_life_time_diff;
    function Incident($id, $title, $date, $ref, $type, $submitter, $priority, $in_charge, $status, $last_update, $tags, $life_time_diff = null, $event_start, $event_end, $src_ips="") {
        $this->id = intval($id);
        $this->title = $title;
        $this->date = $date;
        $this->ref = $ref;
        $this->type = $type;
        $this->submitter = $submitter;
        $this->priority = $priority;
        $this->in_charge = $in_charge;
        $this->status = $status;
        $this->last_update = $last_update;
        $this->tags = $tags;
        $this->_life_time_diff = $life_time_diff; /* Private property */
        $this->event_start = $event_start;
        $this->event_end = $event_end;
		$this->src_ips = $src_ips;
    }
    function get_id() {
        return intval($this->id);
    }
    function get_title() {
        return $this->title;
    }
    function get_ref() {
        return $this->ref;
    }
    function get_type() {
        return $this->type;
    }
    function get_type_keywords() {
        return $this->type_keywords;
    }
    function set_type_keywords($keywds) {
        $this->type_keywords = $keywds;
    }
    function set_type_descr($descr) {
        $this->type_descr = $descr;
    }
    function get_type_descr() {
        return $this->type_descr;
    }
    function get_submitter() {
        return $this->submitter;
    }
    function get_priority() {
        return $this->priority;
    }
    function get_status() {
        return $this->status == 'Open' ? "Open" : "Closed";
    }
    function get_in_charge() {
        return $this->in_charge;
    }
    function get_tags() {
        return $this->tags;
    }
    function get_event_start() {
        return $this->event_start;
    }
    function get_event_end() {
        return $this->event_end;
    }
	function get_src_ips() {
        return $this->src_ips;
    }
    function get_in_charge_name($conn) {
        $in_charge_login = Incident::get_in_charge($conn);
        $sql = "SELECT name FROM users WHERE login = ?";
        $params = array(
            $in_charge_login
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
            exit;
        }
        if ($rs->EOF) {
            $in_charge_name = $in_charge_login;
        } else {
            $in_charge_name = $rs->fields["name"];
        }
        return $in_charge_name;
    }
    function get_last_modification() {
        return Util::date_diff(time() , $this->last_update);
    }
    /*
    * Returns an Incident's event duration
    */
    /*
    *
    * Broken, DK. (2007-01-23)
    *
    */
    /*
    function get_event_duration($format='yMdhm')
    {
    $id  = $this->id;
    $status = $this->status;
    $start_date = $this->event_start;
    $end_date = $this->event_end;
    
    print "A $start_date - $end_date B";
    
    return  Util::date_diff($end_date, $start_date, $format);
    }
    */
    function get_date() {
        return Util::timestamp2date($this->date);
    }
    /*
    * Returns the life time of an incident
    *
    * If the status is:
    *
    *  - Closed: Life time is the difference between incident creation date
    *            and the last time it was modified
    *  - Open: Life time is the difference between incident date and now
    */
    function get_life_time($format = 'yMdhm') {
        $id = $this->id;
        $status = $this->status;
        $start_date = $this->date;
        $last_update = $this->last_update;
        if ($status == 'Closed') {
            $end_date = $last_update;
        } else {
            $end_date = time();
        }
        return Util::date_diff($end_date, $start_date, $format);
    }
    /*
    * @deprecated
    */
    function get_ticket() {
        $id = ($this->id >= 10) ? $this->id : "0" . $this->id;
        return strtoupper(substr($this->ref, 0, 3)) . $id;
    }
    function get_priority_bgcolor($priority) {
        if ($priority > HIGH_PRIORITY) return "red";
        elseif ($priority > MEDIUM_PRIORITY) return "orange";
        elseif ($priority > LOW_PRIORITY) return "green";
        else return "white";
    }
    function get_priority_fgcolor($priority) {
        if ($priority > HIGH_PRIORITY) return "white";
        elseif ($priority > MEDIUM_PRIORITY) return "black";
        elseif ($priority > LOW_PRIORITY) return "white";
        else return "black";
    }
    function get_priority_string($priority) {
        if ($priority > HIGH_PRIORITY) return _("High");
        elseif ($priority > MEDIUM_PRIORITY) return _("Medium");
        else return _("Low");
    }
    /*
    * Static function
    */
    function get_priority_in_html($priority,$link="") {
        $bgcolor = Incident::get_priority_bgcolor($priority);
        $fgcolor = Incident::get_priority_fgcolor($priority);
        if ($link != "")
			$html = '
			  <table align="center" bgcolor="' . $bgcolor . '" fgcolor="' . $fgcolor . '" class="transparent" width="20">
				<tr>
				  <td bgcolor="' . $bgcolor . '" fgcolor="' . $fgcolor . '" style="border-width: 0px;" width="20">
					<b>&nbsp;<a href="'.$link.'" style="color:' . $fgcolor . '" target="topmenu">' . $priority . '</a>&nbsp;</b>
				  </td>
				</tr>
			  </table>';
		else
			$html = '
			  <table align="center" bgcolor="' . $bgcolor . '" fgcolor="' . $fgcolor . '" class="transparent" width="20">
				<tr>
				  <td bgcolor="' . $bgcolor . '" fgcolor="' . $fgcolor . '" style="border-width: 0px;" width="20">
					<b>&nbsp;<font color="' . $fgcolor . '">' . $priority . '</font>&nbsp;</b>
				  </td>
				</tr>
			  </table>';
        return $html;
    }
    /*
    * @static
    */
    function get_validations($type) {
        $incident = array(
            'title' => array(
                OSS_ALPHA,
                OSS_SPACE,
                OSS_PUNC_EXT,
                'illegal:' . _("Title")
            ) ,
            'type' => array(
                OSS_LETTER,
                OSS_SPACE,
                'illegal:' . _("Type")
            ) ,
            'priority' => array(
                OSS_DIGIT,
                'illegal:' . _("Priority")
            ) ,
        );
        $incident_insert = array(
            'ref' => array(
                OSS_LETTER,
                'illegal:' . _("Incident Ref")
            )
        );
        $incident_update = array(
            'incident_id' => array(
                OSS_DIGIT
            )
        );
        $event = array(
            'src_ips' => array(
                OSS_LETTER,
                OSS_DIGIT,
                OSS_PUNC,
                OSS_SPACE,
                OSS_NULLABLE,
                'illegal:' . _("Source Ips")
            ) ,
            'dst_ips' => array(
                OSS_LETTER,
                OSS_DIGIT,
                OSS_PUNC,
                OSS_SPACE,
                OSS_NULLABLE,
                'illegal:' . _("Dest Ips")
            ) ,
            'src_ports' => array(
                OSS_LETTER,
                OSS_DIGIT,
                OSS_PUNC,
                OSS_SPACE,
                OSS_NULLABLE,
                'illegal:' . _("Source Ports")
            ) ,
            'dst_ports' => array(
                OSS_LETTER,
                OSS_DIGIT,
                OSS_PUNC,
                OSS_SPACE,
                OSS_NULLABLE,
                'illegal:' . _("Dest Ports")
            )
        );
        $metric = array(
            'target' => array(
                OSS_PUNC,
                OSS_ALPHA,
                OSS_SPACE,
                OSS_NULLABLE,
                'illegal:' . _("Target")
            ) ,
            'metric_type' => array(
                OSS_ALPHA,
                OSS_SPACE,
                OSS_NULLABLE,
                'illegal:' . _("Metric type")
            ) ,
            'metric_value' => array(
                OSS_ALPHA,
                OSS_SPACE,
                OSS_PUNC,
                OSS_NULLABLE,
                'illegal:' . _("Metric value")
            )
        );
        $vulnerability = array(
            'ip' => array(
                OSS_LETTER,
                OSS_DIGIT,
                OSS_PUNC,
                OSS_SPACE,
                OSS_NULLABLE,
                'illegal:' . _("Ip")
            ) ,
            'port' => array(
                OSS_LETTER,
                OSS_DIGIT,
                OSS_PUNC,
                OSS_SPACE,
                OSS_NULLABLE,
                'illegal:' . _("Port")
            ) ,
            'nessus_id' => array(
                OSS_LETTER,
                OSS_DIGIT,
                OSS_PUNC,
                OSS_SPACE,
                OSS_NULLABLE,
                'illegal:' . _("Nessus ID")
            ) ,
            'risk' => array(
                OSS_LETTER,
                OSS_DIGIT,
                OSS_PUNC,
                OSS_SPACE,
                OSS_NULLABLE,
                'illegal:' . _("Risk")
            )
        );
        if ($type == 'incident_insert') return array_merge($incident, $incident_insert);
        if ($type == 'incident_update') return array_merge($incident, $incident_update);
        if ($type == 'event') return $event;
        if ($type == 'metric') return $metric;
        if ($type == 'vulnerability') return $vulnerability;
    }
    function colorize_status($status,$returnEcho=false) {
        $status = htmlentities($status, ENT_COMPAT, "UTF-8");
        if($returnEcho){
            if ($status == 'Open') $returnHtml= "<font style=\"color:#FF0000\"><b>" . _("Open") . "</b></font>";
            elseif ($status == 'Closed') $returnHtml= "<font style=\"color:#0000FF\"><b>" . _("Closed") . "</b></font>";
            elseif ($status == 'False') $returnHtml= "<b>" . _("Closed (False)") . "</b>";
            else $returnHtml= "<b>$status</b>";

            return $returnHtml;
        }else{
            if ($status == 'Open') echo "<font color=\"red\"><b>" . _("Open") . "</b></font>";
            elseif ($status == 'Closed') echo "<font color=\"blue\"><b>" . _("Closed") . "</b></font>";
            elseif ($status == 'False') echo "<b>" . _("Closed (False)") . "</b>";
            else echo "<b>$status</b>";
        }
        
    }
    function get_list($conn, $args = "", $user = "") {
        require_once ('classes/Session.inc');
        if ($user == "" || (is_array($user) && count($user) < 1)) $user = Session::get_session_user();
        $list = array();
        $sql = "SELECT incident.id," . "incident.title," . "incident.date, " . "incident.ref, " . "incident.type_id as type, " . "incident.submitter as submitter, " . "incident.priority, " . "incident.in_charge, " . "incident.status, " . "incident.last_update, " . "incident.event_start, " . "incident.event_end " . "FROM incident $args";

        if (!$rs = & $conn->Execute(OssimQuery($sql))) {
            die($conn->ErrorMsg());
        } else {
            while (!$rs->EOF) {
                // XXX This should be improved, this is doing 2 queries
                //     per record
                $allowed = Incident::get_users_list($conn, $rs->fields["id"]);
                //print_r($allowed);
                if (is_array($user)) {
                	$flag = false;
                	foreach ($user as $u) {
                		if (in_array($u, $allowed)) {
	                    	$flag = true;
                		}
                	}
                	if ($flag) {
                		$list[] = new Incident($rs->fields["id"], $rs->fields["title"], $rs->fields["date"], $rs->fields["ref"], $rs->fields["type"], $rs->fields["submitter"], $rs->fields["priority"], $rs->fields["in_charge"], $rs->fields["status"], $rs->fields["last_update"], array() , null, $rs->fields["event_start"], $rs->fields["event_end"]);
                	}
                } else {
                	if (in_array($user, $allowed)) {
                    	$list[] = new Incident($rs->fields["id"], $rs->fields["title"], $rs->fields["date"], $rs->fields["ref"], $rs->fields["type"], $rs->fields["submitter"], $rs->fields["priority"], $rs->fields["in_charge"], $rs->fields["status"], $rs->fields["last_update"], array() , null, $rs->fields["event_start"], $rs->fields["event_end"]);
                	}
                }
                $rs->MoveNext();
            }
        }
        return $list;
    }
	function get_list_all($conn, $args = "") {
        require_once ('classes/Session.inc');
        $list = array();
        $sql = "SELECT incident.id," . "incident.title," . "incident.date, " . "incident.ref, " . "incident.type_id as type, " . "incident.submitter as submitter, " . "incident.priority, " . "incident.in_charge, " . "incident.status, " . "incident.last_update, " . "incident.event_start, " . "incident.event_end " . "FROM incident $args";

        if (!$rs = & $conn->Execute(OssimQuery($sql))) {
            die($conn->ErrorMsg());
        } else {
            while (!$rs->EOF) {
                $list[] = new Incident($rs->fields["id"], $rs->fields["title"], $rs->fields["date"], $rs->fields["ref"], $rs->fields["type"], $rs->fields["submitter"], $rs->fields["priority"], $rs->fields["in_charge"], $rs->fields["status"], $rs->fields["last_update"], array() , null, $rs->fields["event_start"], $rs->fields["event_end"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function get_list_filter_ips($conn, $host_ip, $args = "") {
        require_once ('classes/Session.inc');
        $user = Session::get_session_user();
        $list = array();
		// Network or Host
		if (preg_match("/\/(\d+)/",$host_ip,$found)) {
			if ($found[1] >= 24) $host_ip = preg_replace("/\.\d+\/.*/","",$host_ip);
			elseif ($found[1] >= 16) $host_ip = preg_replace("/\.\d+\.\d+\/.*/","",$host_ip);
			elseif ($found[1] >= 8) $host_ip = preg_replace("/\.\d+\.\d+\.\d+\/.*/","",$host_ip);
			$host_where = "a.src_ips LIKE '$host_ip%' OR a.dst_ips LIKE '$host_ip%' OR a.src_ips LIKE ',$host_ip%' OR a.dst_ips LIKE ',$host_ip%'";
            $host_where_2 = "a.ip LIKE '$host_ip%'";
            $host_where_3 = "a.target LIKE '$host_ip%'";
            } else {
                $host_where = "a.src_ips LIKE '%$host_ip%' OR a.dst_ips LIKE '%$host_ip%'";
                $host_where_2 = "a.ip LIKE '%$host_ip%'";
                $host_where_3 = "a.target LIKE '%$host_ip%'"; 
            }
		
		//$sql = "SELECT i.id, i.title, i.date, i.ref, i.type_id as type, i.submitter as submitter, i.priority, i.in_charge, i.status, i.last_update, i.event_start, i.event_end,a.src_ips FROM incident i,incident_alarm a WHERE i.id=a.incident_id AND ($host_where) $args";
        /*$sql = "(SELECT i.id, i.title, i.date, i.ref, i.type_id as type, i.submitter as submitter, i.priority, i.in_charge, i.status, i.last_update, i.event_start, i.event_end,a.src_ips FROM incident i,incident_alarm a 
                    WHERE i.id=a.incident_id AND ($host_where) $args)
                UNION
                (SELECT i.id, i.title, i.date, i.ref, i.type_id as type, i.submitter as submitter, i.priority, i.in_charge, i.status, i.last_update, i.event_start, i.event_end,a.ip as src_ips FROM incident i,incident_vulns a 
                    WHERE i.id=a.incident_id AND ($host_where_2) $args)
                UNION
                (SELECT i.id, i.title, i.date, i.ref, i.type_id as type, i.submitter as submitter, i.priority, i.in_charge, i.status, i.last_update, i.event_start, i.event_end,a.src_ips FROM incident i,incident_event a 
                    WHERE i.id=a.incident_id AND ($host_where) $args)
                UNION
                (SELECT i.id, i.title, i.date, i.ref, i.type_id as type, i.submitter as submitter, i.priority, i.in_charge, i.status, i.last_update, i.event_start, i.event_end,a.ip as src_ips FROM incident i,incident_anomaly a 
                    WHERE i.id=a.incident_id AND ($host_where_2) $args)
                ";*/
        $sql = "(SELECT i.id, i.title, i.date, i.ref, i.type_id as type, i.submitter as submitter, i.priority, i.in_charge, i.status, i.last_update, i.event_start, i.event_end,a.src_ips FROM incident i,incident_alarm a 
                    WHERE i.id=a.incident_id AND ($host_where) $args)
                UNION
                (SELECT i.id, i.title, i.date, i.ref, i.type_id as type, i.submitter as submitter, i.priority, i.in_charge, i.status, i.last_update, i.event_start, i.event_end,a.ip as src_ips FROM incident i,incident_vulns a 
                    WHERE i.id=a.incident_id AND ($host_where_2) $args)
                UNION
                (SELECT i.id, i.title, i.date, i.ref, i.type_id as type, i.submitter as submitter, i.priority, i.in_charge, i.status, i.last_update, i.event_start, i.event_end,a.src_ips FROM incident i,incident_event a 
                    WHERE i.id=a.incident_id AND ($host_where) $args)
                UNION
                (SELECT i.id, i.title, i.date, i.ref, i.type_id as type, i.submitter as submitter, i.priority, i.in_charge, i.status, i.last_update, i.event_start, i.event_end,a.ip as src_ips FROM incident i,incident_anomaly a 
                    WHERE i.id=a.incident_id AND ($host_where_2) $args)
                UNION
                (SELECT i.id, i.title, i.date, i.ref, i.type_id as type, i.submitter as submitter, i.priority, i.in_charge, i.status, i.last_update, i.event_start, i.event_end,a.target as src_ips FROM incident i,incident_metric a 
                    WHERE i.id=a.incident_id AND ($host_where_3) $args)
                ";
        //error_log("$sql\n", 3, "/tmp/incident.log"); 
        //echo "***";
        //echo $sql;
        //echo "***";
		if (!$rs = $conn->Execute(OssimQuery($sql))) {
            die($conn->ErrorMsg());
        } else {
            while (!$rs->EOF) {
                // XXX This should be improved, this is doing 2 queries
                //     per record
                $allowed = Incident::get_users_list($conn, $rs->fields["id"]);
                if (in_array($user, $allowed)) {
                    $list[] = new Incident($rs->fields["id"], $rs->fields["title"], $rs->fields["date"], $rs->fields["ref"], $rs->fields["type"], $rs->fields["submitter"], $rs->fields["priority"], $rs->fields["in_charge"], $rs->fields["status"], $rs->fields["last_update"], array() , null, $rs->fields["event_start"], $rs->fields["event_end"], $rs->fields["event_end"], $rs->fields['src_ips']);
                }
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function get_list_type_descr($conn, $args = "") {
        require_once ('classes/Session.inc');
        $user = Session::get_session_user();
        $list = array();
        $sql = "SELECT incident.id," . "incident.title," . "incident.date, " . "incident.ref, " . "incident.type_id as type, " . "incident.submitter as submitter, " . "incident.priority, " . "incident.in_charge, " . "incident.status, " . "incident.last_update, " . "incident.event_start, " . "incident.event_end, " . "incident_type.descr " . "FROM incident LEFT JOIN incident_type ON (incident.type_id = incident_type.id) $args";
        if (!$rs = & $conn->Execute(OssimQuery($sql))) {
            die($conn->ErrorMsg());
        } else {
            while (!$rs->EOF) {
                // XXX This should be improved, this is doing 2 queries
                //     per record
                $allowed = Incident::get_users_list($conn, $rs->fields["id"]);
                if (in_array($user, $allowed)) {
                    $my_incident = new Incident($rs->fields["id"], $rs->fields["title"], $rs->fields["date"], $rs->fields["ref"], $rs->fields["type"], $rs->fields["submitter"], $rs->fields["priority"], $rs->fields["in_charge"], $rs->fields["status"], $rs->fields["last_update"], array() , null, $rs->fields["event_start"], $rs->fields["event_end"]);
                    $my_incident->set_type_descr($rs->fields["descr"]);
                    $list[] = $my_incident;
                }
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function get_keywords_from_type($conn) {
        $keywords = "";
        $id_type = $this->get_type();
        $sql = "SELECT keywords FROM incident_type WHERE id='$id_type'";
        if (!$rs = & $conn->Execute(OssimQuery($sql))) {
            die($conn->ErrorMsg());
        } else {
            if (!$rs->EOF) $keywords = $rs->fields["keywords"];
        }
        return $keywords;
    }
    function search($conn, $search_criteria, $order_by = 'life_time', $order_mode = 'DESC', $page=1, $rows_per_page=50) {
        $search_opts = array(
            'incident_id',
            'ref',
            'type',
            'title',
            'with_text',
            'status',
            'priority_str',
            'attach_name',
            'submitter',
            'in_charge',
            'related_to_user',
            'tag'
        );
        foreach($search_opts as $o) {
            $opts[$o] = isset($search_criteria[$o]) ? $search_criteria[$o] : null;
        }
        $sql = (!$opts['with_text']) ? "SELECT DISTINCT SQL_CALC_FOUND_ROWS incident.* " : "SELECT DISTINCT incident.* ";
        $where = $parms = array();
        if ($opts['incident_id']) {
            $where[] = "incident.id = ?";
            $parms[] = $opts['incident_id'];
        }
        if ($opts['ref']) {
            $where[] = "incident.ref = ?";
            $parms[] = $opts['ref'];
        }
        if ($opts['type']) {
            $where[] = "incident.type_id = ?";
            $parms[] = $opts['type'];
        }
        if ($opts['title']) {
            $where[] = "UPPER(incident.title) LIKE ?";
            // XXX use that strange trick to overpass Adodb quoting limitations
            $parms[] = '%' . strtoupper($opts['title']) . '%';
        }
        if ($opts['status']) {
            $where[] = "incident.status = ?";
            $parms[] = $opts['status'];
        }
        if ($opts['priority_str']) {
            $p = $opts['priority_str'];
            if ($p == 'High') {
                $where[] = "incident.priority > 7";
            } elseif ($p == 'Medium') {
                $where[] = "incident.priority < 7 AND incident.priority > 4";
            } else {
                $where[] = "incident.priority <= 4";
            }
        }
        if ($opts['attach_name']) {
            $where[] = "UPPER(incident_file.name) LIKE ? " . "AND incident_file.incident_id=incident.id";
            $parms[] = '%' . strtoupper($opts['attach_name']) . '%';
        }
        if ($opts['submitter']) {
            $where[] = "UPPER(incident.submitter) LIKE ?";
            $parms[] = '%' . strtoupper($opts['submitter']) . '%';
        }
        if ($opts['in_charge']) {
            $where[] = "incident.in_charge = ?";
            //echo "filter by: ".$opts['in_charge'];
            $parms[] = $opts['in_charge'];
        }
        if ($opts['tag']) {
            $where[] = "incident_tag.tag_id=? AND incident_tag.incident_id = incident.id";
            $parms[] = $opts['tag'];
        }
        // Big brother search
        if ($opts['with_text']) {
            $text = '%' . strtoupper($opts['with_text']) . '%';
            $tmp_w = $where;
            $all_parms = array();
            //
            // Search over tickets description and action
            //
            $t = "(UPPER(incident_ticket.description) LIKE ?) " . "OR (UPPER(incident_ticket.action) LIKE ?)";
            $all_parms = array_merge($all_parms, $parms, array(
                $text,
                $text
            ));
            $tmp_w[] = "($t) AND incident_ticket.incident_id = incident.id";
            $sqls[] = Incident::_build_sql($sql, $tmp_w);
            
            // search also inside the title if the user didn't specified
            // other specific title search
            if (!$opts['title']) {
                $tmp_w = $where;
                $tmp_w[] = "(UPPER(incident.title) LIKE ?)";
                $all_parms = array_merge($all_parms, $parms, array(
                    $text
                ));
                $sqls[] = Incident::_build_sql($sql, $tmp_w);
            }
            
            //
            // search over the IPs in event
            //
            $tmp_w = $where;
            $tmp_w[] = "(UPPER(incident_event.src_ips) LIKE ? " . "OR UPPER(incident_event.dst_ips) LIKE ?) " . "AND incident_event.incident_id = incident.id";
            $sqls[] = Incident::_build_sql($sql, $tmp_w);
            $all_parms = array_merge($all_parms, $parms, array(
                $text,
                $text
            ));
            //
            // search over the IPs in alarm
            //
            $tmp_w = $where;
            $tmp_w[] = "(UPPER(incident_alarm.src_ips) LIKE ? " . "OR UPPER(incident_alarm.dst_ips) LIKE ?) " . "AND incident_alarm.incident_id = incident.id";
            $sqls[] = Incident::_build_sql($sql, $tmp_w);
            $all_parms = array_merge($all_parms, $parms, array(
                $text,
                $text
            ));
            //
            // search over the IPs in alarm
            //
            $tmp_w = $where;
            $tmp_w[] = "(UPPER(incident_metric.target) LIKE ?) " . "AND incident_metric.incident_id = incident.id";
            $sqls[] = Incident::_build_sql($sql, $tmp_w);
            $all_parms = array_merge($all_parms, $parms, array(
                $text
            ));
            //
            // Search over the IPs in vulnerabilities
            //
            $tmp_w = $where;
            $tmp_w[] = "(UPPER(incident_vulns.ip) = ?) " . "AND incident_vulns.incident_id = incident.id";
            $sqls[] = Incident::_build_sql($sql, $tmp_w);
            $all_parms = array_merge($all_parms, $parms, array(
                str_replace('%', '', $text)
            ));
            //
            // Build the final big UNION SQL and params
            //
            $sql = implode("\nUNION\n", $sqls);
            $parms = $all_parms;
            //printr($parms);
            
        } else {
			$sql = Incident::_build_sql($sql, $where);
        }
        
        // order by
        if ($order_by == 'life_time') $order_by = "date";
        $order_by = "\nORDER BY $order_by $order_mode";
        $sql.= $order_by;
        // limit
        if (!$opts['with_text']) $sql .= " LIMIT ".(abs($page-1)*$rows_per_page).",$rows_per_page";
        
        // Gets tags
        $tags = array();
        $t_sql = "SELECT incident_tag.tag_id, incident_tag.incident_id FROM incident_tag";
        if (!$rs = $conn->Execute($t_sql)) die($conn->ErrorMsg());
        while (!$rs->EOF) {
            $tags[$rs->fields["incident_id"]][] = $rs->fields["tag_id"];
            $rs->MoveNext();
        }
        //printr($sql);
        //$conn->debug = true;
        if (!$rs = $conn->Execute($sql, $parms)) {
            die($conn->ErrorMsg());
        }
        // Build the resultant list of incidents
        $list = array();
        while (!$rs->EOF) {
            // Life Time calculations:
            if ($rs->fields["status"] == 'Open') {
                $life_time_diff = time() - strtotime($rs->fields["date"]);
            } else {
                $life_time_diff = strtotime($rs->fields["date"]) - strtotime($rs->fields["last_update"]);
            }
            $itags = (isset($tags[$rs->fields["id"]])) ? $tags[$rs->fields["id"]] : array();
            $list[] = new Incident($rs->fields["id"], $rs->fields["title"], $rs->fields["date"], $rs->fields["ref"], $rs->fields["type_id"], $rs->fields["submitter"], $rs->fields["priority"], $rs->fields["in_charge"], $rs->fields["status"], $rs->fields["last_update"], $itags, $life_time_diff, $rs->fields["event_start"], $rs->fields["event_end"]);
            $rs->MoveNext();
        }
        return $list;
    }
    function search_count($conn) {
        $foundrows = 0;
        if ($rf = &$conn->Execute("SELECT FOUND_ROWS() as total")) $foundrows = $rf->fields["total"];
        return $foundrows;
    }
    function _build_sql($start_sql, $where) {
        $user_where = "";
        $user_tmp = array();
        require_once ('ossim_conf.inc');

        $conf = $GLOBALS["CONF"];
        $version = $conf->get_conf("ossim_server_version", FALSE);
    
        $w = implode("\nAND ", $where);
        $from = '';
        // scan needed tables
        if (count($where)) {
            if (preg_match_all('/([a-z_]+)\./', $w, $m)) {
                $from = implode(', ', array_unique($m[1]));
            }
        }
        if (!$from) $from = 'incident';
        $sql = $start_sql . "\nFROM $from";
        $user = Session::get_session_user();
        
        
        if(preg_match("/pro|demo/i",$version)) {
            $db = new ossim_db();
            $dbconn = $db->connect();
            if (Acl::am_i_proadmin()) {
                //list($entities_admin,$num) = Acl::get_entities_admin($dbconn,Session::get_session_user());
                //$user_tmp = array_keys($entities_admin);
            
                $users = Acl::get_my_users($dbconn, Session::get_session_user());
                foreach ($users as $us) {
                    $user_tmp[] = $us["login"];
                }
                $entities = Acl::get_user_entities($user);
                foreach ($entities as $entity) {
                    $user_tmp[] = $entity;
                }                
            }
            else {
                $entities = Acl::get_user_entities($user);
                foreach ($entities as $entity) {
                    $user_tmp[] = $entity;
                }
                //$brothers = Acl::get_brothers($dbconn,Session::get_session_user());
                //foreach ($brothers as $brother){
                //    $user_tmp[] = $brother["login"]; 
                //}
            }
            $db->close($dbconn);
        }
        if(!in_array($user, $user_tmp))   $user_tmp[] = $user; 
        
        $user_where = "'".implode("', '", $user_tmp)."'";

        if ($user != ACL_DEFAULT_OSSIM_ADMIN) {
            if (preg_match("/incident_event/",$w) || preg_match("/incident_alarm/",$w)
                || preg_match("/incident_metric/",$w) || preg_match("/incident_vulns/",$w)) {
                $sql .= "LEFT JOIN incident_subscrip ON incident_subscrip.incident_id=incident.id WHERE incident_subscrip.login in ($user_where)";
            } else {
                $incident_ticket = (!preg_match("/incident_ticket/",$from)) ? true : false;
                $sql .= (($incident_ticket) ? " LEFT JOIN incident_ticket ON incident_ticket.incident_id = incident.id" : "")." LEFT JOIN incident_subscrip ON incident_subscrip.incident_id=incident.id WHERE ".((!$incident_ticket) ? "incident_ticket.incident_id=incident.id AND" : "");
                $sql .= "(incident.in_charge in ($user_where) OR incident_ticket.users in ($user_where) OR incident_ticket.in_charge in ($user_where) OR incident_ticket.transferred in ($user_where) OR incident_subscrip.login in ($user_where))";
            }
            if (count($where)) $sql.= "\n AND $w";
        } else {
            if (count($where)) $sql.= "\n WHERE $w";
        }
        return $sql;
    }
    /*
    * Used by the search() function
    */
    function _sort_by_life_time($a, $b) {
        $diff1 = $a->_life_time_diff;
        $diff2 = $b->_life_time_diff;
        if ($diff1 == $diff2) return 0;
        return ($diff1 > $diff2) ? 1 : -1;
    }
    /*
    * @deprecated
    */
    function get_tickets($conn) {
        require_once ('classes/Incident_ticket.inc');
        return Incident_ticket::get_list($conn, "WHERE incident_id = " . $this->id . " ORDER BY date");
    }
    function get_alarms($conn) {
        require_once ('classes/Incident_alarm.inc');
        return Incident_alarm::get_list($conn, "WHERE incident_id = " . $this->id . " ORDER BY id");
    }
    function get_events($conn) {
        require_once ('classes/Incident_event.inc');
        return Incident_event::get_list($conn, "WHERE incident_id = " . $this->id . " ORDER BY id");
    }
    function get_metrics($conn) {
        require_once ('classes/Incident_metric.inc');
        return Incident_metric::get_list($conn, "WHERE incident_id = " . $this->id . " ORDER BY id");
    }
    function get_anomalies($conn) {
        require_once ('classes/Incident_anomaly.inc');
        return Incident_anomaly::get_list($conn, "WHERE incident_id = " . $this->id . " ORDER BY id");
    }
    function get_vulnerabilities($conn) {
        require_once ('classes/Incident_vulnerability.inc');
        return Incident_vulnerability::get_list($conn, "WHERE incident_id = " . $this->id . " ORDER BY id");
    }
    /* get users that are part of an incident (the main Admin included)
    * (user, in_charge, transferred and subscribed to incidents)
    */
    function get_users_list($conn, $incident_id) {
        $list = array();
        $query = OssimQuery("SELECT users, in_charge, transferred FROM incident_ticket WHERE incident_id = " . $incident_id);
        if (!$rs = & $conn->Execute($query)) {
            die($conn->ErrorMsg());
        } else {
            while (!$rs->EOF) {
                if (($rs->fields["users"]) and !in_array($rs->fields["users"], $list)) $list[] = $rs->fields["users"];
                if (($rs->fields["in_charge"]) and !in_array($rs->fields["in_charge"], $list)) $list[] = $rs->fields["in_charge"];
                if (($rs->fields["transferred"]) and !in_array($rs->fields["transferred"], $list)) $list[] = $rs->fields["transferred"];
                $rs->MoveNext();
            }
        }
        $suscrib = Incident::get_subscribed_users($conn, $incident_id);
        foreach($suscrib as $s) {
            if (!in_array($s['login'], $list)) {
                $list[] = $s['login'];
            }
        }
        if (!in_array(ACL_DEFAULT_OSSIM_ADMIN, $list)) {
            $list[] = ACL_DEFAULT_OSSIM_ADMIN;
        }
        return $list;
    }
    /*
    * @return array in the form:
    *              array(0 => array('login' => 'foo',
    *                               'name' => 'bar',
    *                               'email' => XXX,
    *                               'company' => XXX,
    *                               'department' => XXX))
    */
    function get_subscribed_users($conn, $incident_id, $entity_id="") {
        $list1 = array();
        $list2 = array();
        
        $sql = "SELECT u.login, u.name, u.email," . "      u.company, u.department " . "FROM users u, incident_subscrip s " . "WHERE s.incident_id = ? AND u.login = s.login " . "ORDER BY u.name";
        if (!$rs = & $conn->Execute($sql, $incident_id)) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                $list1[] = $rs->fields;
                $rs->MoveNext();
            }
        }
        
        if (preg_match("/\d+/",$entity_id)) {
            $user_list = array();
            $users_entity = array();
            $users = Acl::get_users($conn);
            $user_list = $users[0];
            
            foreach ($user_list as $user) if(in_array($entity_id,$user['entities'])) $users_entity[] = "'".$user["login"]."'";
            
            
            if(count($users_entity)>0) {
                $sql = "SELECT u.login, u.name, u.email, u.company, u.department FROM users u WHERE u.login in (".implode(",",$users_entity).") ORDER BY u.name";

                if (!$rs = & $conn->Execute($sql)) {
                    print $conn->ErrorMsg();
                } else {
                    while (!$rs->EOF) {
                        $list2[] = $rs->fields;
                        $rs->MoveNext();
                    }
                }
            }
        }
        return array_merge($list1, $list2);
    }
    /*
    * @deprecated
    */
    /* returns True if user is admin or is in charge
    * of the incident, instead returns False
    */
    function user_incident_perms($conn, $user, $incident_id) {
        /*require_once 'ossim_acl.inc';
        require_once ('ossim_conf.inc');
        
        $conf = $GLOBALS["CONF"];
        $version = $conf->get_conf("ossim_server_version", FALSE);
        
        if(preg_match("/pro|demo/i",$version)) {
            $user_tmp = array();
            if (Acl::am_i_proadmin()) {
                list($entities_admin,$num) = Acl::get_entities_admin($conn,Session::get_session_user());
                $user_tmp = array_keys($entities_admin);
            
                $users = Acl::get_my_users($conn, Session::get_session_user());
                foreach ($users as $us) {
                    $user_tmp[] = $us["login"];
                }
            }
        }
        
        if ($user == ACL_DEFAULT_OSSIM_ADMIN) return true;
        
        if (is_array($incident_list = Incident::get_list($conn, "WHERE incident.id = $incident_id", $user_tmp)) && count($incident_list) > 0) {
            $incident = $incident_list[0];
            if ($user == $incident->get_in_charge($conn) || (preg_match("/pro|demo/i",$version) && in_array($incident->get_in_charge($conn),$user_tmp))) return true;
        }
        return false;
        */
        if ($user == ACL_DEFAULT_OSSIM_ADMIN) return true;
        $incident_list = Incident::search($conn, array( 'incident_id' => $incident_id ));
        return (count($incident_list)>0) ? true : false;
    }
    /*
    * @deprecated
    */
    /* returns True if user is admin or is in charge
    * of the ticket, instead returns False
    */
    function user_ticket_perms($conn, $user, $ticket_id) {
        require_once ('ossim_acl.inc');
        if ($user == ACL_DEFAULT_OSSIM_ADMIN) return true;
        $query = OssimQuery("SELECT in_charge FROM incident_ticket WHERE id =
        $ticket_id");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
            return false;
        } elseif (!$rs->EOF) {
            if ($rs->fields["in_charge"] == $user) return true;
        }
        return false;
    }
    /*
    * @deprecated
    */
    function print_td_priority($priority, $bgcolor, $fgcolor) {
        $priority = intval($priority);
        $bgcolor = htmlentities($bgcolor, ENT_COMPAT, "UTF-8");
        $fgcolor = htmlentities($fgcolor, ENT_COMPAT, "UTF-8");
        $priority_td = "
        <td>
          <table align=\"center\" bgcolor=\"$bgcolor\" fgcolor=\"$fgcolor\">
            <tr>
              <td";
        if ($bgcolor) $priority_td.= " bgcolor=\"$bgcolor\" fgcolor=\"$fgcolor\"";
        $priority_td.= "><b>&nbsp;";
        if ($fgcolor) $priority_td.= "<font color=\"$fgcolor\">$priority</font>";
        else $priority_td.= $priority;
        $priority_td.= "&nbsp;</b>
              </td>
            </tr>
          </table>
        </td>";
        echo $priority_td;
    }
    function print_td_incident_type($conn, $selected = "") {
        require_once ('classes/Incident_type.inc');
        $output = '<td class="left"><select name="type">';
        if ($list = Incident_type::get_list($conn)) {
            foreach($list as $type) {
                if ($type->get_id() != "") {
                    $output.= "<option ";
                    if ($selected == $type->get_id()) $output.= " selected ";
                    $output.= " value=\"" . $type->get_id() . "\">";
                    $output.= $type->get_id();
                    $output.= "</option>";
                }
            }
        }
        $output.= '</select></td>';
        print $output;
    }
    function __insert($conn, $title, $type, $submitter, $priority, $ref, $event_start, $event_end, $in_charge="") {
        foreach(Incident::get_validations('incident_insert') as $var => $rule) {
            if (!ossim_valid($$var, $rule)) return false;
        }
        require_once ('classes/Session.inc');
        $user = Session::get_session_user();
        if($in_charge=="") $in_charge = $user;
        
        if (empty($event_start)) $event_start = "now";
        if (empty($event_end)) $event_end = "now";
        $event_start = date('Y-m-d H:i:s', strtotime($event_start));
        $event_end = date('Y-m-d H:i:s', strtotime($event_end));
        $query = "INSERT INTO incident " . "(title, type_id, ref, submitter, priority, " . "in_charge, status, date, last_update, event_start, event_end) " . "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $date = date('Y-m-d H:i:s');
        $params = array(
            $title,
            $type,
            $ref,
            $submitter,
            $priority,
            $in_charge,
            'Open',
            $date,
            $date,
            $event_start,
            $event_end
        );
        if ($conn->Execute($query, $params) === false) {
            die('error inserting incident: ' . $conn->ErrorMsg());
        }
        /* get last inserted id */
        $query = OssimQuery("SELECT LAST_INSERT_ID() as id FROM incident");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
            exit;
        }
        $last_id = $rs->fields["id"];
        // Automatically subscribe incident creator
        Incident::insert_subscription($conn, $last_id, $user);
        return $last_id;
    }
    function insert_alarm($conn, $title, $type, $submitter, $priority, $src_ips, $dst_ips, $src_ports, $dst_ports, $event_start, $event_end, $backlog_id, $event_id, $alarm_group_id, $in_charge="") {
        foreach(Incident::get_validations('event') as $var => $rule) {
            if (!ossim_valid($$var, $rule)) return false;
        }
        $last_id = Incident::__insert($conn, $title, $type, $submitter, $priority, "Alarm", $event_start, $event_end, $in_charge);
		if (ossim_error()) return false;
        $sql = "INSERT INTO incident_alarm
                (incident_id, src_ips, dst_ips, src_ports, dst_ports, backlog_id, event_id, alarm_group_id)
                VALUES (?, ?, ? ,?, ?,?,?,?)";
        $params = array(
            $last_id,
            $src_ips,
            $dst_ips,
            $src_ports,
            $dst_ports,
            $backlog_id,
            $event_id,
            $alarm_group_id
        );
        if (!$conn->Execute($sql, $params)) die($conn->ErrorMsg());
        return $last_id;
    }
    function insert_event($conn, $title, $type, $submitter, $priority, $src_ips, $dst_ips, $src_ports, $dst_ports, $event_start, $event_end, $in_charge="") {
        foreach(Incident::get_validations('event') as $var => $rule) {
            if (!ossim_valid($$var, $rule)) return false;
        }

        $last_id = Incident::__insert($conn, $title, $type, $submitter, $priority, "Event", $event_start, $event_end, $in_charge);
        if (ossim_error()) return false;
        $sql = "INSERT INTO incident_event
                (incident_id, src_ips, dst_ips, src_ports, dst_ports)
                VALUES (?, ?, ? ,?, ?)";
        $params = array(
            $last_id,
            $src_ips,
            $dst_ips,
            $src_ports,
            $dst_ports
        );
        //$conn->debug = true;
        if (!$conn->Execute($sql, $params)) die($conn->ErrorMsg());
        return $last_id;
    }
    function insert_anomaly($conn, $title, $type, $submitter, $priority, $anom_type, $ip, $data_orig, $data_new, $in_charge="") {
        $s_data_orig = implode(",", $data_orig);
        $s_data_new = implode(",", $data_new);
        $last_id = Incident::__insert($conn, $title, $type, $submitter, $priority, "Anomaly", "", "", $in_charge);
        if (ossim_error()) return false;
        $sql = "INSERT INTO incident_anomaly
                (incident_id, anom_type, ip, data_orig, data_new)
                VALUES (?, ?, ?, ?, ?)";
        $params = array(
            $last_id,
            $anom_type,
            $ip,
            $s_data_orig,
            $s_data_new
        );
        //$conn->debug = true;
        if (!$conn->Execute($sql, $params)) die($conn->ErrorMsg());
        return $last_id;
    }
    function insert_vulnerability($conn, $title, $type, $submitter, $priority, $ip, $port, $nessus_id, $risk, $description, $in_charge="") {
        foreach(Incident::get_validations('vulnerability') as $var => $rule) {
            if (!ossim_valid($$var, $rule)) return false;
        }
        $last_id = Incident::__insert($conn, $title, $type, $submitter, $priority, "Vulnerability", "", "", $in_charge);
        if (ossim_error()) return false;
        $sql = "INSERT INTO incident_vulns 
                (id, incident_id, ip, port, nessus_id, risk, description) 
                VALUES (?,?, ?, ?, ?, ?, ?)";
        $vuln_entry_id = $conn->GenID('incident_vulns_seq');
        $params = array(
            $vuln_entry_id,
            $last_id,
            $ip,
            $port,
            $nessus_id,
            $risk,
            $description
        );
        if (!$conn->Execute($sql, $params)) die($conn->ErrorMsg());
        return $last_id;
    }
    /*
    * @static
    */
    function insert_metric($conn, $title, $type, $submitter, $priority, $target, $metric_type, $metric_value, $event_start, $event_end, $in_charge="") {
        foreach(Incident::get_validations('metric') as $var => $rule) {
            if (!ossim_valid($$var, $rule)) return false;
        }
        $last_id = Incident::__insert($conn, $title, $type, $submitter, $priority, "Metric", $event_start, $event_end, $in_charge);
        if (ossim_error()) return false;
        // XXX move to sequences
        $sql = "INSERT INTO incident_metric " . "(incident_id, target, metric_type, metric_value) " . "VALUES (?, ?, ?, ?)";
        $params = array(
            $last_id,
            $target,
            $metric_type,
            $metric_value
        );
        if (!$conn->Execute($sql, $params)) die($conn->ErrorMsg());
        return $last_id;
    }
    function insert_subscription($conn, $incident_id, $login) {
        ossim_valid($incident_id, OSS_DIGIT, 'illegal:' . _("ID"));
        ossim_valid($login, OSS_USER, 'illegal:' . _("Login"));
        if (ossim_error()) {
            die(ossim_error());
        }
        // Check if he's already subscribed
        $sql = "SELECT login FROM incident_subscrip WHERE login=? AND incident_id=?";
        if (!$rs = $conn->Execute($sql, array(
            $login,
            $incident_id
        ))) {
            die($conn->ErrorMsg());
        }
        if (!$rs->EOF) return false;
        $sql = "INSERT INTO incident_subscrip VALUES (?, ?)";
        if (!$conn->Execute($sql, array(
            $login,
            $incident_id
        ))) {
            die($conn->ErrorMsg());
        }
        return true;
    }
    function __update($conn, $incident_id, $title, $type, $submitter, $priority, $event_start, $event_end) {
        foreach(Incident::get_validations('incident_update') as $var => $rule) {
            if (!ossim_valid($$var, $rule)) return false;
        }
        if (empty($event_start)) $event_start = "now";
        if (empty($event_end)) $event_end = "now";
        $event_start = date('Y-m-d H:i:s', strtotime($event_start));
        $event_end = date('Y-m-d H:i:s', strtotime($event_end));
        $sql = "UPDATE incident SET " . "title = ?, type_id = ?, submitter = ?, priority = ?, date = date, event_start = ?, event_end = ? " . "WHERE id = ?";
        $params = array(
            $title,
            $type,
            $submitter,
            $priority,
            $event_start,
            $event_end,
            $incident_id
        );
        if (!$conn->Execute($sql, $params)) die($conn->ErrorMsg());
        // XXX would be nice to add here a ticket telling that
        // incident fields were updated by someone
        //$infolog = array(
        //    $title
        //);
        //Log_action::log(15, $infolog);
    }
    function update_alarm($conn, $incident_id, $title, $type, $submitter, $priority, $src_ips, $dst_ips, $src_ports, $dst_ports, $event_start, $event_end) {
        Incident::__update($conn, $incident_id, $title, $type, $submitter, $priority, $event_start, $event_end);
        if (ossim_error()) return false;
        foreach(Incident::get_validations('event') as $var => $rule) {
            if (!ossim_valid($$var, $rule)) return false;
        }
        $sql = "UPDATE incident_alarm " . "SET src_ips = ?, dst_ips = ?, src_ports = ?, dst_ports = ? " . "WHERE incident_id = ?";
        $params = array(
            $src_ips,
            $dst_ips,
            $src_ports,
            $dst_ports,
            $incident_id
        );
        if (!$conn->Execute($sql, $params)) die($conn->ErrorMsg());
        return true;
    }
    function update_event($conn, $incident_id, $title, $type, $submitter, $priority, $src_ips, $dst_ips, $src_ports, $dst_ports, $event_start, $event_end) {
        Incident::__update($conn, $incident_id, $title, $type, $submitter, $priority, $event_start, $event_end);
        if (ossim_error()) return false;
        foreach(Incident::get_validations('event') as $var => $rule) {
            if (!ossim_valid($$var, $rule)) return false;
        }
        $sql = "UPDATE incident_event " . "SET src_ips = ?, dst_ips = ?, src_ports = ?, dst_ports = ? " . "WHERE incident_id = ?";
        $params = array(
            $src_ips,
            $dst_ips,
            $src_ports,
            $dst_ports,
            $incident_id
        );
        if (!$conn->Execute($sql, $params)) die($conn->ErrorMsg());
        return true;
    }
    function update_metric($conn, $incident_id, $title, $type, $submitter, $priority, $target, $metric_type, $metric_value, $event_start, $event_end) {
        foreach(Incident::get_validations('metric') as $var => $rule) {
            if (!ossim_valid($$var, $rule)) return false;
        }
        Incident::__update($conn, $incident_id, $title, $type, $submitter, $priority, $event_start, $event_end);
        if (ossim_error()) return false;
        $sql = "UPDATE incident_metric " . "SET target = ?, metric_type = ?, metric_value = ? " . "WHERE incident_id = ?";
        $params = array(
            $target,
            $metric_type,
            $metric_value,
            $incident_id
        );
        if (!$conn->Execute($sql, $params)) die($conn->ErrorMsg());
        return true;
    }
    function update_anomaly($conn, $incident_id, $title, $type, $submitter, $priority, $anom_type, $ip, $data_orig, $data_new) {
        $s_data_orig = implode(",", $data_orig);
        $s_data_new = implode(",", $data_new);
        Incident::__update($conn, $incident_id, $title, $type, $submitter, $priority, "", "");
        if (ossim_error()) return false;
        $sql = "UPDATE incident_anomaly " . "SET anom_type = ?, ip = ?, data_orig = ?, data_new = ? " . "WHERE incident_id = ?";
        $params = array(
            $anom_type,
            $ip,
            $s_data_orig,
            $s_data_new,
            $incident_id
        );
        if (!$conn->Execute($sql, $params)) die($conn->ErrorMsg());
        return true;
    }
    function update_vulnerability($conn, $incident_id, $title, $type, $submitter, $priority, $ip, $port, $nessus_id, $risk, $description) {
        Incident::__update($conn, $incident_id, $title, $type, $submitter, $priority, "", "");
        if (ossim_error()) return false;
        $sql = "UPDATE incident_vulns " . "SET ip = ?, port = ?, nessus_id = ?, risk = ?, description = ? " . "WHERE incident_id = ?";
        $params = array(
            $ip,
            $port,
            $nessus_id,
            $risk,
            $description,
            $incident_id
        );
        if (!$conn->Execute($sql, $params)) die($conn->ErrorMsg());
        
        $infolog = array($incident_id);
        Log_action::log(15, $infolog);

        return true;
    }
    function delete($conn, $id) {
        if (!ossim_valid($id, OSS_DIGIT)) {
            die("invalid ID");
        }
        $conn->StartTrans();
        $conn->Execute("DELETE FROM incident_ticket WHERE incident_id=$id");
        $conn->Execute("DELETE FROM incident_alarm  WHERE incident_id=$id");
        $conn->Execute("DELETE FROM incident_event  WHERE incident_id=$id");
        $conn->Execute("DELETE FROM incident_file   WHERE incident_id=$id");
        $conn->Execute("DELETE FROM incident_subscrip  WHERE incident_id=$id");
        $conn->Execute("DELETE FROM incident_tag    WHERE incident_id=$id");
        $conn->Execute("DELETE FROM incident_vulns  WHERE incident_id=$id");
        $conn->Execute("DELETE FROM incident WHERE id=$id");
        $conn->CompleteTrans();
        if ($conn->HasFailedTrans()) {
            die('error deleting: ' . $conn->ErrorMsg());
        }
        $infolog = array(
            $id
        );
        Log_action::log(16, $infolog);
    }
    /*
    * Deletes all the subscriptions associated with an Incident Id.
    * If you supply the $login, only that subscription will be removed
    */
    function delete_subscriptions($conn, $incident_id, $login = '') {
        $params[] = $incident_id;
        $sql = "DELETE FROM incident_subscrip WHERE incident_id=?";
        if ($login) {
            $sql.= " AND login=?";
            $params[] = $login;
        }
        if (!$conn->Execute($sql, $params)) {
            die($conn->ErrorMsg());
        }
        return true;
    }
    /*
    * @deprecated
    */
    function incident_search($conn, $query_string) {
        $search_array = array(
            "incident_ticket" => array(
                "return_column" => "incident_id",
                "check_columns" => array(
                    "date",
                    "users",
                    "description",
                    "action",
                    "in_charge",
                    "transferred",
                    "copy"
                )
            ) ,
            "incident" => array(
                "return_column" => "id",
                "check_columns" => array(
                    "title",
                    "date",
                    "ref",
                    "type_id"
                )
            ) ,
            "incident_alarm" => array(
                "return_column" => "incident_id",
                "check_columns" => array(
                    "src_ips",
                    "dst_ips",
                    "src_ports",
                    "dst_ports"
                )
            ) ,
            "incident_event" => array(
                "return_column" => "incident_id",
                "check_columns" => array(
                    "src_ips",
                    "dst_ips",
                    "src_ports",
                    "dst_ports"
                )
            )
        );
        $qs = $query_string; //shorter
        foreach(array_keys($search_array) as $table) {
            $query = "SELECT " . $search_array[$table]["return_column"] . " FROM " . $table . " WHERE ";
            foreach($search_array[$table]["check_columns"] as $column) {
                $query.= $column . " LIKE \"%" . $qs . "%\" OR ";
            }
            // chop last " OR " and sanitize
            $query = OssimQuery(substr($query, 0, strlen($query) - 4));
            if (!$rs = & $conn->Execute($query)) {
                print $conn->ErrorMsg();
            } else {
                while (!$rs->EOF) {
                    $list[] = $rs->fields[$search_array[$table]["return_column"]];
                    $rs->MoveNext();
                }
            }
        }
        if (is_array($list)) {
            // Avoid duplicates, nice trick found on the array_unique() manual
            // page
            $list = array_flip(array_flip($list));
            $where = "WHERE incident.id = " . array_pop($list) . " ";
            foreach($list as $incident_id) {
                $where.= "OR incident.id = \"$incident_id\" ";
            }
            $incident_list = Incident::get_list($conn, $where);
        }
        return $incident_list;
    }
    /*
    incident statistics
    */
    function incidents_by_type($conn,$host=null) {
        $list = array();
        $type = array();
        if(is_null($host)){
            // sin buscar por host o redes
            if ($incident_list = Incident::get_list($conn)) {
                foreach($incident_list as $incident) {
                    $tmp = $incident->get_type();
                    if (array_key_exists($tmp, $type)) $type[$tmp]+= 1;
                    else $type[$tmp] = 1;
                }
            }
        }else{
            // buscando en host o redes
            if(is_array($host)){
                //es una red
                $incident_list=array();
                foreach($host as $value){
                    unset($listTemp);
                    $listTemp=Incident::get_list_filter_ips($conn,$value, "ORDER BY status DESC");
                    $incident_list[]=$listTemp;
                }
            }else{
                //es un host
                $incident_list[0] = Incident::get_list_filter_ips($conn,$host, "ORDER BY status DESC");
            }

            // Parseamos el resultado
            // sumando y ordenando los type
            $arrayContenedor=array();

            foreach($incident_list as $value){
                foreach($value as $value2){
                    if (array_key_exists($value2->get_type(), $arrayContenedor)) {
                        // Ya tenemos el estatus en el array a devolver
                        // le sumamos una unidad
                        $arrayContenedor[$value2->get_type()]++;
                    }else{
                        // No tenemos el estatus en el array a devolver
                        // lo creamos y le añadimos una unidad
                        $arrayContenedor[$value2->get_type()]=1;
                    }
                }
            }

            $arrayReturn=array();
            // inicialmente la aplicación obtiene los datos sin indice textual, 0 es key 1 es valor
            foreach($arrayContenedor as $key => $value){
                $arrayReturn[]=array(0=>$key,1=>$value);
            }
            //

            return $arrayReturn;

        }
        
        arsort($type);
        foreach($type as $key => $value) {
            array_push($list, array(
                $key,
                $value
            ));
        }
        return $list;
    }
    function incidents_by_type_descr($conn) {
        $list = array();
        $type = array();
        if ($incident_list = Incident::get_list_type_descr($conn)) {
            foreach($incident_list as $incident) {
                $tmp = $incident->get_type_descr();
                if (array_key_exists($tmp, $type)) $type[$tmp]+= 1;
                else $type[$tmp] = 1;
            }
        }
        arsort($type);
        foreach($type as $key => $value) {
            array_push($list, array(
                $key,
                $value
            ));
        }
        return $list;
    }
    function incidents_by_status($conn,$host=null,$instancia=false) {
        $list = array();
        $status = array();
        if(is_null($host)){
            // sin buscar por host o redes
            if ($incident_list = Incident::get_list($conn)) {
                if($instancia){
                   // si queremos devolver istancias en vez de un array
                    return $incident_list;
                }
                foreach($incident_list as $incident) {
                    $tmp = $incident->get_status($conn);
                    if (array_key_exists($tmp, $status)) $status[$tmp]+= 1;
                    else $status[$tmp] = 1;
                }
            }
        }else{
            // buscando en host o redes
             if(is_array($host)){
                //es una red
                $incident_list=array();
                foreach($host as $value){
                    unset($listTemp);
                    $listTemp=Incident::get_list_filter_ips($conn,$value, "ORDER BY status DESC");
                    $incident_list[]=$listTemp;
                }
            }else{
                //es un host
                $incident_list[0] = Incident::get_list_filter_ips($conn,$host, "ORDER BY status DESC");
            }

            if($instancia){
               // si queremos devolver istancias en vez de un array
                return $incident_list;
            }

            // Parseamos el resultado
            // sumando y ordenando los type
            $arrayContenedor=array();

            foreach($incident_list as $value){
                foreach($value as $value2){
                    if (array_key_exists($value2->get_status(), $arrayContenedor)) {
                        // Ya tenemos el estatus en el array a devolver
                        // le sumamos una unidad
                        $arrayContenedor[$value2->get_status()]++;
                    }else{
                        // No tenemos el estatus en el array a devolver
                        // lo creamos y le añadimos una unidad
                        $arrayContenedor[$value2->get_status()]=1;
                    }
                }
            }

            $arrayReturn=array();
            // inicialmente la aplicación obtiene los datos sin indice textual, 0 es key 1 es valor
            foreach($arrayContenedor as $key => $value){
                $arrayReturn[]=array(0=>$key,1=>$value);
            }
            //

            return $arrayReturn;
        }

        foreach($status as $key => $value) {
            array_push($list, array(
                $key,
                $value
            ));
        }
        return $list;
    }
    function incidents_by_user($conn, $open_only = true,$host=null) {
    	$conf = $GLOBALS["CONF"];
		$version = $conf->get_conf("ossim_server_version", FALSE);
        $list = array();
        $status = array();
        $args = "";
        if ($open_only) $args = "WHERE status = \"Open\"";
        if(is_null($host)){
            // sin buscar por host o redes
            if ($incident_list = Incident::get_list($conn, $args)) {
                foreach($incident_list as $incident) {
                    $tmp = $incident->get_in_charge($conn);
                    if (array_key_exists($tmp, $status)) $status[$tmp]+= 1;
                    else $status[$tmp] = 1;
                }
            }
        }else{
            // buscando en host o redes
             if(is_array($host)){
                //es una red
                $incident_list=array();
                foreach($host as $value){
                    unset($listTemp);
                    $listTemp=Incident::get_list_filter_ips($conn,$value, "ORDER BY status DESC");
                    $incident_list[]=$listTemp;
                }
            }else{
                //es un host
                $incident_list[0] = Incident::get_list_filter_ips($conn,$host, "ORDER BY status DESC");
            }

            // Parseamos el resultado
            // sumando y ordenando los type
            $arrayContenedor=array();

            foreach($incident_list as $value){
                foreach($value as $value2){
                    if (array_key_exists($value2->get_in_charge(), $arrayContenedor)) {
                        // Ya tenemos el estatus en el array a devolver
                        // le sumamos una unidad
                        $arrayContenedor[$value2->get_in_charge()]++;
                    }else{
                        // No tenemos el estatus en el array a devolver
                        // lo creamos y le añadimos una unidad
                        $arrayContenedor[$value2->get_in_charge()]=1;
                    }
                }
            }

            $arrayReturn=array();
            // inicialmente la aplicación obtiene los datos sin indice textual, 0 es key 1 es valor
            foreach($arrayContenedor as $key => $value){
	        	if (preg_match("/pro|demo/i",$version) && preg_match("/^\d+$/",$key)) {
		           list($entity_name, $entity_type) = Acl::get_entity_name_type($conn,$key);
		           $key = $entity_name." [".$entity_type."]";
		        }
                if ($key!=" []") $arrayReturn[]=array(0=>$key,1=>$value);
            }
            //

            return $arrayReturn;
        }

        arsort($status);
        foreach($status as $key => $value) {
        	if (preg_match("/pro|demo/i",$version) && preg_match("/^\d+$/",$key)) {
	           list($entity_name, $entity_type) = Acl::get_entity_name_type($conn,$key);
	           $key = $entity_name." [".$entity_type."]";
	        }
            if ($key!=" []") array_push($list, array(
                $key,
                $value
            ));
        }
        return $list;
    }
    function chk_status($status) {
        return in_array((string)$status, array(
            'Open',
            'Closed'
        ));
    }
}
?>
