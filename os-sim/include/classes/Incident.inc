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
* - incidents_by_class()
* - incidents_by_resolution_time()
* - incidents_closed_by_month()
* - incidentes_by_priority()
* - chk_status()
* Classes list:
* - Incident
*/
require_once 'classes/Security.inc';
require_once 'classes/Util.inc';
require_once 'classes/Log_action.inc';
require_once 'ossim_db.inc';
require_once 'classes/Util.inc';


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
        $this->id              = intval($id);
        $this->title           = $title;
        $this->date            = $date;
        $this->ref             = $ref;
        $this->type            = $type;
        $this->submitter       = $submitter;
        $this->priority        = $priority;
        $this->in_charge       = $in_charge;
        $this->status          = $status;
        $this->last_update     = $last_update;
        $this->tags            = $tags;
        $this->_life_time_diff = $life_time_diff; /* Private property */
        $this->event_start     = $event_start;
        $this->event_end 	   = $event_end;
		$this->src_ips         = $src_ips;
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
                OSS_ALPHA, OSS_PUNC_EXT,
                OSS_SPACE, OSS_SCORE,
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
                OSS_DIGIT,
				'illegal:' . _("Incident id")
            )
        );
        
		$event = array(
            'src_ips' => array(
                OSS_IP_ADDRCIDR,
                OSS_NULLABLE,
                'illegal:' . _("Source Ips")
            ) ,
            'dst_ips' => array(
                OSS_IP_ADDRCIDR,
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
                OSS_IP_ADDRCIDR,
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
                OSS_IP_ADDRCIDR,
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
		
		$anomaly = array(
            'ip' => array(
                OSS_IP_ADDR,
                OSS_NULLABLE,
                'illegal:' . _("Host")
            ),
			
			'sensor' => array(
                OSS_IP_ADDR,
                OSS_NULLABLE,
                'illegal:' . _("Sensor")
            ),
			'port' => array(
                OSS_LETTER,
                OSS_DIGIT,
                OSS_PUNC,
                OSS_SPACE,
                OSS_NULLABLE,
                'illegal:' . _("Port")
            ),
			'new_mac'  => array(
                OSS_MAC,
                OSS_NULLABLE,
                'illegal:' . _("New MAC")
            ),
			'old_mac'  => array(
                OSS_MAC,
                OSS_NULLABLE,
                'illegal:' . _("Old MAC")
            ),
			'new_vendor'  => array(
                OSS_ALPHA,
				OSS_PUNC_EXT,
				OSS_NULLABLE,
                'illegal:' . _("New Vendor")
            ),
			'old_vendor'  => array(
                OSS_ALPHA,
				OSS_PUNC_EXT,
				OSS_NULLABLE,
                'illegal:' . _("Old Vendor")
            ),
			'new_version'  => array(
                OSS_ALPHA,
				OSS_PUNC_EXT,
				OSS_NULLABLE,
                'illegal:' . _("New Version")
            ),
			'old_version'  => array(
                OSS_ALPHA,
				OSS_PUNC_EXT,
				OSS_NULLABLE,
                'illegal:' . _("Old Version")
            ),
			'new_protocol'  => array(
                OSS_ALPHA,
				OSS_PUNC_EXT,
				OSS_NULLABLE,
                'illegal:' . _("New Prococol")
            ),
			'old_protocol'  => array(
                OSS_ALPHA,
				OSS_PUNC_EXT,
				OSS_NULLABLE,
                'illegal:' . _("Old Prococol")
            ),
			'when'  => array(
                OSS_ALPHA,
				OSS_PUNC_EXT,
				OSS_NULLABLE,
                'illegal:' . _("When")
            )
        );
		
        if ($type == 'incident_insert') return array_merge($incident, $incident_insert);
        if ($type == 'incident_update') return array_merge($incident, $incident_update);
        if ($type == 'event')           return $event;
        if ($type == 'metric')          return $metric;
        if ($type == 'vulnerability')   return $vulnerability;
		if ($type == 'anomaly')         return $anomaly;
    }
	
    function colorize_status($status,$returnEcho=false) {
		$status = Util::htmlentities($status, ENT_COMPAT, "UTF-8");
       
		if($returnEcho)
		{
            if ($status == 'Open') $returnHtml= "<font style=\"color:#FF0000\"><b>" . _("Open") . "</b></font>";
            elseif ($status == 'Closed') $returnHtml= "<font style=\"color:#0000FF\"><b>" . _("Closed") . "</b></font>";
            elseif ($status == 'False') $returnHtml= "<b>" . _("Closed (False)") . "</b>";
            else $returnHtml= "<b>$status</b>";

            return $returnHtml;
        }
		else
		{
            if ($status == 'Open') echo "<font color=\"red\"><b>" . _("Open") . "</b></font>";
            elseif ($status == 'Closed') echo "<font color=\"blue\"><b>" . _("Closed") . "</b></font>";
            elseif ($status == 'False') echo "<b>" . _("Closed (False)") . "</b>";
            else echo "<b>$status</b>";
        }
        
    }
    function get_list($conn, $args = "", $user = "") {
        
		require_once ('classes/Session.inc');
        $list  = array();
				
		if ( $user == "" ) 
			$user = Session::get_session_user();
		else
		{
			if ( preg_match("/^\d+$/",$user) )
			{
				if ( !$pro )
					return $list;
				else
					$users = Acl::get_all_users_by_entity($conn, $user);
			}
			
		}
        		
        $sql   = "SELECT incident.id, incident.title, incident.date, incident.ref, 
						 incident.type_id as type, incident.submitter as submitter, 
						 incident.priority, incident.in_charge, incident.status, 
						 incident.last_update, incident.event_start, incident.event_end 
				  FROM incident $args";
		
		
		//echo $sql;
		
		if ( !$rs = & $conn->Execute(OssimQuery($sql)) ) 
		    return $conn->ErrorMsg();
			
      
		while (!$rs->EOF) {
			
			//Users allowed to see this ticket
			
			$allowed = Incident::get_users_list($conn, $rs->fields["id"], $rs->fields["submitter"], $rs->fields["in_charge"], $user);
			
			/*var_dump($user);
			echo "<pre>";
			print_r($allowed);
			echo "</pre>";*/
			
			if ( is_array($users) && !empty($users) ) 
			{
				foreach ($users as $u) 
				{
					if ( !empty($allowed[$u]) )
					{
						$list[] = new Incident($rs->fields["id"], $rs->fields["title"], $rs->fields["date"], $rs->fields["ref"], $rs->fields["type"], $rs->fields["submitter"], $rs->fields["priority"], $rs->fields["in_charge"], $rs->fields["status"], $rs->fields["last_update"], array() , null, $rs->fields["event_start"], $rs->fields["event_end"]);
						break;
					}
				}
				
			} 
			else 
			{
				if ( !empty($allowed[$user]) )
					$list[] = new Incident($rs->fields["id"], $rs->fields["title"], $rs->fields["date"], $rs->fields["ref"], $rs->fields["type"], $rs->fields["submitter"], $rs->fields["priority"], $rs->fields["in_charge"], $rs->fields["status"], $rs->fields["last_update"], array() , null, $rs->fields["event_start"], $rs->fields["event_end"]);
				
			}
			$rs->MoveNext();
		}
        
        return $list;
    }
	
	function get_list_all($conn, $args = "") {
        require_once ('classes/Session.inc');
        $list = array();
        $sql = "SELECT incident.id," . "incident.title," . "incident.date, " . "incident.ref, " . "incident.type_id as type, " . "incident.submitter as submitter, " . "incident.priority, " . "incident.in_charge, " . "incident.status, " . "incident.last_update, " . "incident.event_start, " . "incident.event_end " . "FROM incident $args";

        if ( !$rs = & $conn->Execute(OssimQuery($sql)) )
            die($conn->ErrorMsg());
        else 
		{
            while (!$rs->EOF) 
			{
                $list[] = new Incident($rs->fields["id"], $rs->fields["title"], $rs->fields["date"], $rs->fields["ref"], $rs->fields["type"], $rs->fields["submitter"], $rs->fields["priority"], $rs->fields["in_charge"], $rs->fields["status"], $rs->fields["last_update"], array() , null, $rs->fields["event_start"], $rs->fields["event_end"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
	
    function get_list_filtered($conn, $host_ip, $args = "", $user = "") {
        require_once ('classes/Session.inc');
        require_once ('classes/CIDR.inc');
        
		if ( $user != '' )
		{
			$args = " WHERE 1=1 ". $args; 
			$list = Incident::get_list($conn, $args, $user);
			return $list;
		}
		
		// Network or Host filter
		$list = array();
		
		if (!is_array($host_ip))
			if ($host_ip=="") $assets = array();
			else $assets[]=$host_ip;
		else
			$assets = $host_ip;
		
		$host_where = $host_where_2 = $host_where_3 = "";
		
		foreach ($assets as $range => $asst) 
		{
			if (preg_match("/,/",$range)) 
			{
				$range = explode(",",$range);
				$host_where .= " OR (INET_ATON(a.src_ips) >= ".$range[0]." AND INET_ATON(a.src_ips) <= ".$range[1].") OR (INET_ATON(a.dst_ips) >= ".$range[0]." AND INET_ATON(a.dst_ips) <= ".$range[1].")";
				$host_where_2 = " OR (INET_ATON(a.ip) >= ".$range[0]." AND INET_ATON(a.ip) <= ".$range[1].")";
				$host_where_3 = " OR (INET_ATON(a.target) >= ".$range[0]." AND INET_ATON(a.target) <= ".$range[1].")";
				
				#$host_where   = "a.src_ips LIKE '$host_ip%' OR a.dst_ips LIKE '$host_ip%' OR a.src_ips LIKE ',$host_ip%' OR a.dst_ips LIKE ',$host_ip%'";
	            #$host_where_2 = "a.ip LIKE '$host_ip%'";
	            #$host_where_3 = "a.target LIKE '$host_ip%'";
			} 
			else 
			{
				$host_where .= " OR a.src_ips = '$asst' OR a.dst_ips = '$asst'";
				$host_where_2 .= " OR a.ip = '$asst'";
				$host_where_3 .= " OR a.target = '$asst'";
							
				#$host_where = "a.src_ips LIKE '%$host_ip%' OR a.dst_ips LIKE '%$host_ip%'";
				#$host_where_2 = "a.ip LIKE '%$host_ip%'";
				#$host_where_3 = "a.target LIKE '%$host_ip%'"; 
			}
		}
		
		$host_where   = preg_replace("/^ OR /","",$host_where);
		$host_where_2 = preg_replace("/^ OR /","",$host_where_2);
		$host_where_3 = preg_replace("/^ OR /","",$host_where_3);
		
		if ($host_where=="")   $host_where="1=1";
		if ($host_where_2=="") $host_where_2="1=1";
		if ($host_where_3=="") $host_where_3="1=1";
		
		        
		$sql = "(SELECT i.id, i.title, i.date, i.ref, i.type_id as type, i.submitter as submitter, i.priority, i.in_charge, i.status, i.last_update, i.event_start, i.event_end,a.src_ips 
				 FROM incident i,incident_alarm a 
                 WHERE i.id=a.incident_id AND ($host_where) $args)
                UNION
                ( SELECT i.id, i.title, i.date, i.ref, i.type_id as type, i.submitter as submitter, i.priority, i.in_charge, i.status, i.last_update, i.event_start, i.event_end,a.ip as src_ips 
				  FROM incident i,incident_vulns a 
                  WHERE i.id=a.incident_id AND ($host_where_2) $args)
                UNION
                (SELECT i.id, i.title, i.date, i.ref, i.type_id as type, i.submitter as submitter, i.priority, i.in_charge, i.status, i.last_update, i.event_start, i.event_end,a.src_ips 
				 FROM incident i,incident_event a 
                 WHERE i.id=a.incident_id AND ($host_where) $args)
                UNION
                (SELECT i.id, i.title, i.date, i.ref, i.type_id as type, i.submitter as submitter, i.priority, i.in_charge, i.status, i.last_update, i.event_start, i.event_end,a.ip as src_ips 
				 FROM incident i,incident_anomaly a 
                 WHERE i.id=a.incident_id AND ($host_where_2) $args)
                UNION
                (SELECT i.id, i.title, i.date, i.ref, i.type_id as type, i.submitter as submitter, i.priority, i.in_charge, i.status, i.last_update, i.event_start, i.event_end,a.target as src_ips 
				 FROM incident i,incident_metric a 
                 WHERE i.id=a.incident_id AND ($host_where_3) $args)
                ";
       	
		//print_r($sql);
		
		if (!$rs = $conn->Execute(OssimQuery($sql))) 
            die($conn->ErrorMsg());
        else 
		{
            while (!$rs->EOF) 
			{
                
                $allowed = Incident::get_users_list($conn, $rs->fields["id"], $rs->fields["submitter"], $rs->fields["in_charge"]);
                				
				if ( !empty($allowed[$user]) )  
                    $list[] = new Incident($rs->fields["id"], $rs->fields["title"], $rs->fields["date"], $rs->fields["ref"], $rs->fields["type"], $rs->fields["submitter"], $rs->fields["priority"], $rs->fields["in_charge"], $rs->fields["status"], $rs->fields["last_update"], array() , null, $rs->fields["event_start"], $rs->fields["event_end"], $rs->fields["event_end"], $rs->fields['src_ips']);
                
                $rs->MoveNext();
            }
        }
		
		return $list;
    }
	
    function get_list_type_descr($conn, $args = "") {
        require_once ('classes/Session.inc');
        
		$user = Session::get_session_user();
        $list = array();
        $sql  = "SELECT incident.id, incident.title, incident.date, incident.ref, incident.type_id as type, incident.submitter as submitter, incident.priority, incident.in_charge,
						incident.status, incident.last_update, incident.event_start, incident.event_end, incident_type.descr FROM incident LEFT JOIN incident_type ON (incident.type_id = incident_type.id) $args";
        
		if (!$rs = & $conn->Execute(OssimQuery($sql))) 
            die($conn->ErrorMsg());
        else 
		{
            while (!$rs->EOF) 
			{
                $allowed = Incident::get_users_list($conn, $rs->fields["id"], $rs->fields["submitter"], $rs->fields["in_charge"]);
				
				if ( !empty($allowed[$user]) )  
				{
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
        $id_type  = $this->get_type();
        $sql = "SELECT keywords FROM incident_type WHERE id='$id_type'";
		
		if (!$rs = & $conn->Execute(OssimQuery($sql))) 
            die($conn->ErrorMsg());
        else
            if (!$rs->EOF) $keywords = $rs->fields["keywords"];
        
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
            'last_update',
            'tag'
        );
        
		foreach($search_opts as $o) {
            $opts[$o] = isset($search_criteria[$o]) ? $search_criteria[$o] : null;
        }
        
		$sql   = (!$opts['with_text']) ? "SELECT DISTINCT SQL_CALC_FOUND_ROWS incident.* " : "SELECT DISTINCT incident.* ";
        $where = $parms = array();
		
		        
		if (is_array($opts['last_update'])) 
		{
            $where[] = "incident.last_update between ".$opts['last_update'][0]." AND ".$opts['last_update'][1];
        } 
		elseif ($opts['last_update']) 
		{
            $where[] = "incident.last_update >= ".$opts['last_update'];
        }
        
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
            $where[] = "UPPER(incident_file.name) LIKE ? AND incident_file.incident_id=incident.id";
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
        if ($opts['with_text'])
		{
            $text      = '%' . strtoupper($opts['with_text']) . '%';
            $tmp_w     = $where;
            $all_parms = array();
            //
            // Search over tickets description and action
            //
            $t = "(UPPER(incident_ticket.description) LIKE ?) OR (UPPER(incident_ticket.action) LIKE ?)";
            $all_parms = array_merge($all_parms, $parms, array(
                $text,
                $text
            ));
            $tmp_w[] = "($t) AND incident_ticket.incident_id = incident.id";
            $sqls[] = Incident::_build_sql($sql, $tmp_w);
            
            // search also inside the title if the user didn't specified
            // other specific title search
            if (!$opts['title']) {
                $tmp_w     = $where;
                $tmp_w[]   = "(UPPER(incident.title) LIKE ?)";
                $all_parms = array_merge($all_parms, $parms, array(
                    $text
                ));
                $sqls[] = Incident::_build_sql($sql, $tmp_w);
            }
            
            //
            // search over the IPs in event
            //
            $tmp_w     = $where;
            $tmp_w[]   = "(UPPER(incident_event.src_ips) LIKE ? OR UPPER(incident_event.dst_ips) LIKE ?) AND incident_event.incident_id = incident.id";
            $sqls[]    = Incident::_build_sql($sql, $tmp_w);
            $all_parms = array_merge($all_parms, $parms, array(
                $text,
                $text
            ));
            
			//
            // search over the IPs in alarm
            //
            $tmp_w     = $where;
            $tmp_w[]   = "(UPPER(incident_alarm.src_ips) LIKE ? OR UPPER(incident_alarm.dst_ips) LIKE ?) AND incident_alarm.incident_id = incident.id";
            $sqls[]    = Incident::_build_sql($sql, $tmp_w);
            $all_parms = array_merge($all_parms, $parms, array(
                $text,
                $text
            ));
           
		   //
            // search over the IPs in alarm
            //
            $tmp_w     = $where;
            $tmp_w[]   = "(UPPER(incident_metric.target) LIKE ?) AND incident_metric.incident_id = incident.id";
            $sqls[]    = Incident::_build_sql($sql, $tmp_w);
            $all_parms = array_merge($all_parms, $parms, array(
                $text
            ));
           
		   //
            // Search over the IPs in vulnerabilities
            //
            $tmp_w     = $where;
            $tmp_w[]   = "(UPPER(incident_vulns.ip) = ?) AND incident_vulns.incident_id = incident.id";
            $sqls[]    = Incident::_build_sql($sql, $tmp_w);
            $all_parms = array_merge($all_parms, $parms, array(
                str_replace('%', '', $text)
            ));
           
		   //
            // Search over custom fields
            //
            $tmp_w     = $where;
            $tmp_w[]   = "(incident_custom.content like ?) AND incident_custom.incident_id = incident.id";
            $sqls[]    = Incident::_build_sql($sql, $tmp_w);
            $all_parms = array_merge($all_parms, $parms, array(
                $text
            ));            

            //
            // Build the final big UNION SQL and params
            //
            $sql   = implode("\nUNION\n", $sqls);
            $parms = $all_parms;
            //printr($parms);
            
        } 
		else 
		{
			$sql = Incident::_build_sql($sql, $where);
        }
		
		
		/*echo "<pre>";
			print_r($sql);
		echo "</pre>";*/
		        
		// Order by
        if ($order_by == 'life_time') $order_by = "date";
        $order_by = ($order_by!="") ? "\nORDER BY $order_by $order_mode" : "";
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
        
		
        require_once ('ossim_conf.inc');

        $conf    = $GLOBALS["CONF"];
        $version = $conf->get_conf("ossim_server_version", FALSE);
		$pro     = ( preg_match("/pro|demo/i",$version) ) ? true : false;
    
		$user_where = "";
        $user_tmp   = array();
		
			
        $w    = implode("\nAND ", $where);
        $from = '';
        // Scan needed tables
        if (count($where)) 
		{
            if (preg_match_all('/([a-z_]+)\./', $w, $m)) {
                $from = implode(', ', array_unique($m[1]));
            }
        }
        if (!$from) 
			$from = 'incident';
        
		$sql             = $start_sql . "\nFROM $from";
        $user            = Session::get_session_user();
        $user_tmp[$user] = $user; 
        
		
        if( $pro )
		{
            $dbaux  = new ossim_db();
            $dbconn = $dbaux->connect();
            
			if (Acl::am_i_proadmin()) 
			{
                //My users
				$users = Acl::get_my_users($dbconn, Session::get_session_user());
                foreach ($users as $us) {
                    $user_tmp[$us["login"]] = $us["login"];
                }
                
				//My child entities
				$user_data   = Acl::get_users($dbconn, $user);
				$my_entities = $user_data['entities'];
				
				foreach ($my_entities as $k => $v)
				{
					$user_tmp[$v] = $v;
					$childs = Acl::get_entity_childs($dbconn,$v);
					foreach ($childs as $i => $child)
						$user_tmp[$child] = $child;
				}
				
			}
            else 
			{
                $brothers = Acl::get_brothers($dbconn);
				
				foreach($brothers as $k => $v)
					$user_tmp[$v["login"]] = $v["login"];
					
				$user_data   = Acl::get_users($dbconn, $user);
				
				$my_entities = $user_data['entities'];
				foreach ($my_entities as $k => $v)
					$user_tmp[$v] = $v; 
			}
			
			$dbaux->close($dbconn);
        }
        
		$user_where = "'".implode("', '", $user_tmp)."'";

        if (!Session::am_i_admin()) 
		{ //  $user != ACL_DEFAULT_OSSIM_ADMIN
            if (preg_match("/incident_event/",$w) || preg_match("/incident_alarm/",$w)
                || preg_match("/incident_metric/",$w) || preg_match("/incident_vulns/",$w) || preg_match("/incident_custom/",$w) ) {
                $sql .= " LEFT JOIN incident_subscrip ON incident_subscrip.incident_id=incident.id WHERE incident_subscrip.login in ($user_where)";
            } 
			else 
			{
                $incident_ticket = (!preg_match("/incident_ticket/",$from)) ? true : false;
                $sql .= (($incident_ticket) ? " LEFT JOIN incident_ticket ON incident_ticket.incident_id = incident.id" : "")." LEFT JOIN incident_subscrip ON incident_subscrip.incident_id=incident.id WHERE ".((!$incident_ticket) ? "incident_ticket.incident_id=incident.id AND" : "");
                $sql .= "(incident.in_charge in ($user_where) OR incident_ticket.users in ($user_where) OR incident_ticket.in_charge in ($user_where) OR incident_ticket.transferred in ($user_where) OR incident_subscrip.login in ($user_where))";
            }
            
			if (count($where)) $sql.= "\n AND $w";
        } 
		else 
		{
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
    function get_custom($conn) {
		$list = array();
        $query = OssimQuery("SELECT * FROM incident_custom WHERE incident_id=? ORDER BY id ASC");
        if (!$rs = & $conn->Execute($query,array($this->id))) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
               	$list[] = array($rs->fields["name"],$rs->fields["content"],$rs->fields["type"],$rs->fields["id"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }   
    function get_custom_content($conn,$id,$incident_id) {
        $query = OssimQuery("SELECT name,content FROM incident_custom WHERE id=? and incident_id=?");
        if (!$rs = & $conn->Execute($query,array($id,$incident_id))) {
            print $conn->ErrorMsg();
        } else {
            if (!$rs->EOF) return array($rs->fields["name"],$rs->fields["content"]);
        }
        return array();
    } 
    /* get users that are part of an incident (the main Admin included)
    * (user, in_charge, transferred and subscribed to incidents)
    */
    
	function get_users_list($conn, $incident_id, $submitter, $in_charge, $user=null) {
    	
    	
		$list = array();
		$conf = $GLOBALS["CONF"];
		
		if (!$conf) {
			require_once 'ossim_db.inc';
			require_once 'ossim_conf.inc';
			$conf = new ossim_conf();
		}
						
		$version  = $conf->get_conf("ossim_server_version", FALSE);
		$pro      = ( preg_match("/pro|demo/i",$version) ) ? true : false;
		
		$me       = ( $user == '' ) ? Session::get_session_user() : $user;
		
		//Admin
		if ( Session::is_admin($conn, $me) ) 
			return array("$me" => $me);
        
		//I'm the submmiter or I´m in charge
		if ( $submitter == $me || $in_charge == $me )
			return array("$me" => $me);
		
		if ( $pro && preg_match("/^\d+$/",$in_charge) )
			$list = Acl::get_all_users_by_entity($conn, $in_charge);
		elseif ( $pro && !preg_match("/^\d+$/",$in_charge) )
		{
			$user = Acl::get_users($conn, $in_charge);
			
			if (is_array($user) && !empty($user) )
			{
				$brothers = Acl::get_brothers($conn,$in_charge);
				
				foreach($brothers as $k => $v)
					$list[$v["login"]] = $v["login"];
					
				$my_entities = $user['entities'];
				
				foreach ($my_entities as $k => $v)
				{
					$entity_p                      = Acl::get_entity($conn, $v);
					$list[$entity_p['admin_user']] = $entity_p['admin_user'];
					
					$parents   = Acl::get_entity_parents($conn,$v);
					
					foreach ($parents as $i => $parent)
					{
						$entity  = Acl::get_entity($conn, $parent);
						$list[$entity['admin_user']] = $entity['admin_user'];
					}
				}
			}
		}
		
		$query = OssimQuery("SELECT users, transferred FROM incident_ticket WHERE incident_id = " . $incident_id);
		
		if (!$rs = & $conn->Execute($query)) 
		    die($conn->ErrorMsg());
        else 
		{
            while (!$rs->EOF) 
			{
                $users       =  $rs->fields["users"];
				$transferred =  $rs->fields["transferred"];
				
				if ( !empty($users) ) 
					$list[$users] = $users;
                
				if ( !empty($transferred) )
				{
					if ( $pro && preg_match("/^\d+$/",$transferred) )
						$list = array_merge ($list, Acl::get_all_users_by_entity($conn, $transferred));
					elseif (!preg_match("/^\d+$/",$transferred) )
						$list[$transferred] = $transferred;
				}	
                
				$rs->MoveNext();
            }
        }
			
		
		
		$suscrib = Incident::get_subscribed_users($conn, $incident_id);
        
		foreach($suscrib as $s)
            $list[$s['login']] = $s['login'];
        
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
        
        $sql = "SELECT u.login, u.name, u.email," . " u.company, u.department " . "FROM users u, incident_subscrip s " . "WHERE s.incident_id = ? AND u.login = s.login " . "ORDER BY u.name";
        if (!$rs = & $conn->Execute($sql, $incident_id)) {
            print $conn->ErrorMsg();
        } 
		else 
		{
            while (!$rs->EOF) {
                $list1[] = $rs->fields;
                $rs->MoveNext();
            }
        }
        
        if (preg_match("/\d+/",$entity_id)) 
		{
            $user_list    = array();
            $users_entity = array();
            $users 		  = Acl::get_users($conn);
            $user_list    = $users[0];
            
            foreach ($user_list as $user) 
				if(in_array($entity_id,$user['entities'])) $users_entity[] = "'".$user["login"]."'";
            
            
            if(count($users_entity)>0) 
			{
                $sql = "SELECT u.login, u.name, u.email, u.company, u.department FROM users u WHERE u.login in (".implode(",",$users_entity).") ORDER BY u.name";

                if (!$rs = & $conn->Execute($sql))
				{
                    print $conn->ErrorMsg();
                } 
				else 
				{
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
		@return true if current user have permissions to edit, delete, 
		subscribe and unsubscribe incidents
	*/
	
    function user_incident_perms($conn, $incident_id, $action, $user=null) {
  		
		$me = ( empty($user) ) ? Session::get_session_user() : $user;
		
		$conf = $GLOBALS["CONF"];
						
		if (!$conf) {
			require_once 'ossim_db.inc';
			require_once 'ossim_conf.inc';
			$conf = new ossim_conf();
		}
		
		$version  = $conf->get_conf("ossim_server_version", FALSE);
		$pro      = ( preg_match("/pro|demo/i",$version) ) ? true : false;
				
		
		//I'm admin
		if ( Session::am_i_admin() ) 
			return true;
		
				
		$query = OssimQuery("SELECT submitter, in_charge FROM incident WHERE id = ".$incident_id);
		
		if (!$rs = & $conn->Execute($query)) 
		    return $conn->ErrorMsg();
        else 
		{
            if (!$rs->EOF) 
			{
			    $submitter = $rs->fields["submitter"];
				$in_charge = $rs->fields["in_charge"];
			}
		}
		
		//I'm the submmiter 
		if ( $submitter == $me )
			return true;
		
		
		if ( ($action == 'show' || $action == 'subscrip' || $action == 'newticket') )
		{
			$allowed = Incident::get_users_list($conn, $incident_id, $submitter, $in_charge);
			
			if ( !empty($allowed[$me]) )
				return true;
		}
		elseif ( $action == "delincident" || $action == "editincident" || $action == 'closed' )
		{
			if ( $pro && preg_match("/^\d+$/",$in_charge) )
			{
				$entity = Acl::get_entity($conn, $in_charge);
				
				if ( $me == $entity['admin_user'] ) 
					return true;
				else
				{
					$entities  = Acl::get_entity_parents($conn, $in_charge);
					foreach ($entities as $k => $v )
					{
						$entity = Acl::get_entity($conn, $v);
				
						if ( $me == $entity['admin_user'] ) 
							return true;
					}
				}
			}
		}
				
		return false;
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
        $query = OssimQuery("SELECT in_charge FROM incident_ticket WHERE id = $ticket_id");
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
        $bgcolor = Util::htmlentities($bgcolor, ENT_COMPAT, "UTF-8");
        $fgcolor = Util::htmlentities($fgcolor, ENT_COMPAT, "UTF-8");
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
        if ($list = Incident_type::get_list($conn)) 
		{
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
    
	function __insert($conn, $title, $type, $submitter, $priority, $ref, $event_start, $event_end, $in_charge="") 
	{
        foreach(Incident::get_validations('incident_insert') as $var => $rule) 
		{
            if (!ossim_valid($$var, $rule)) return false;
        }
        
		require_once ('classes/Session.inc');
        
		$user = Session::get_session_user();
        
		if( $in_charge == "" ) $in_charge = $user;
        
        if (empty($event_start)) $event_start = "now";
        if (empty($event_end))   $event_end = "now";
        
		$event_start = date('Y-m-d H:i:s', strtotime($event_start));
        $event_end   = date('Y-m-d H:i:s', strtotime($event_end));
        
		$query = "INSERT INTO incident (title, type_id, ref, submitter, priority, in_charge, status, date, last_update, event_start, event_end) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
		$date   = date('Y-m-d H:i:s');
        
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
            die('Error inserting incident: ' . $conn->ErrorMsg());
        }
        /* Get last inserted id */
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
    
	function insert_alarm($conn, $title, $type, $submitter, $priority, $src_ips, $dst_ips, $src_ports, $dst_ports, $event_start, $event_end, $backlog_id, $event_id, $alarm_group_id, $transferred_user, $transferred_entity) {
        
		require_once ('classes/Session.inc');
		
		$in_charge = ( $transferred_user != "" )  ? $transferred_user : $transferred_entity;  
		$in_charge = ( $in_charge != "" )       ? $in_charge : Session::get_session_user(); 
        
		foreach(Incident::get_validations('event') as $var => $rule) {
           	if (!ossim_valid($$var, $rule)) return false;
        }
        
		$last_id = Incident::__insert($conn, $title, $type, $submitter, $priority, "Alarm", $event_start, $event_end, $in_charge);
		
		if (ossim_error()) return false;
        $sql = "INSERT INTO incident_alarm (incident_id, src_ips, dst_ips, src_ports, dst_ports, backlog_id, event_id, alarm_group_id) VALUES (?, ?, ? ,?, ?,?,?,?)";
        
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
	
    function insert_event($conn, $title, $type, $submitter, $priority, $src_ips, $dst_ips, $src_ports, $dst_ports, $event_start, $event_end, $transferred_user, $transferred_entity) {
       
	   	foreach(Incident::get_validations('event') as $var => $rule) {
            if (!ossim_valid($$var, $rule)) return false;
        }
		
		require_once ('classes/Session.inc');
		
		$in_charge = ( $transferred_user != "" )  ? $transferred_user : $transferred_entity;  
		$in_charge = ( $in_charge != "" )       ? $in_charge : Session::get_session_user(); 
		

        $last_id   = Incident::__insert($conn, $title, $type, $submitter, $priority, "Event", $event_start, $event_end, $in_charge);
        
		if (ossim_error()) return false;
        
		$sql = "INSERT INTO incident_event (incident_id, src_ips, dst_ips, src_ports, dst_ports) VALUES (?, ?, ? ,?, ?)";
        
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
    
	function insert_anomaly($conn, $title, $type, $submitter, $priority, $anom_type, $ip, $data_orig, $data_new, $transferred_user, $transferred_entity) {
        
		$sensor  = $data_orig[0];
		$when    = $data_orig[1];
		
		if ($anom_type == "mac" )
		{
			$old_mac    = $data_orig[2];
			$new_mac    = $data_new[2];
			$old_vendor = $data_orig[3];
			$new_vendor = $data_new[3];
		}
		else if ($anom_type == "os" )
		{
			$old_os  = $data_orig[2];
			$new_os  = $data_new[2];
		}
		else if ($anom_type == "services" )
		{
			$port            = $data_orig[2];
			$old_protocol    = $data_orig[3];
			$new_protocol    = $data_new[3];
			$old_version     = $data_orig[4];
			$new_version     = $data_new[4];
		}
		
		foreach(Incident::get_validations('anomaly') as $var => $rule) {
			if (!ossim_valid($$var, $rule)) return false;
		}
			
		$s_data_orig = implode(",", $data_orig);
        $s_data_new  = implode(",", $data_new);
		
		
		require_once ('classes/Session.inc');
		
		$in_charge = ( $transferred_user != "" )  ? $transferred_user : $transferred_entity;  
		$in_charge = ( $in_charge != "" )         ? $in_charge : Session::get_session_user(); 
		
	    $last_id = Incident::__insert($conn, $title, $type, $submitter, $priority, "Anomaly", "", "", $in_charge);
        
		if (ossim_error()) return false;
        
		$sql = "INSERT INTO incident_anomaly (incident_id, anom_type, ip, data_orig, data_new) VALUES (?, ?, ?, ?, ?)";
        
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
	
    function insert_vulnerability($conn, $title, $type, $submitter, $priority, $ip, $port, $nessus_id, $risk, $description, $transferred_user, $transferred_entity) {
        
		foreach(Incident::get_validations('vulnerability') as $var => $rule) {
            if (!ossim_valid($$var, $rule)) return false;
        }
        
		require_once ('classes/Session.inc');
		
		$in_charge = ( $transferred_user != "" )  ? $transferred_user : $transferred_entity;  
		$in_charge = ( $in_charge != "" )       ? $in_charge : Session::get_session_user(); 
		
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
	
    function insert_custom($conn, $title, $type, $submitter, $priority, $transferred_user, $transferred_entity, $fields=array()) {
        
		foreach($fields as $value)
		{
			if ( $value["validate"] )
				if (!ossim_valid($value["content"], OSS_TEXT, OSS_PUNC_EXT, OSS_NULLABLE, 'illegal:' . _("Custom field"))) 
					return false;
        }
		
		require_once ('classes/Session.inc');
		
		$in_charge = ( $transferred_user != "" )  ? $transferred_user : $transferred_entity;  
		$in_charge = ( $in_charge != "" )       ? $in_charge : Session::get_session_user(); 
		
        $last_id = Incident::__insert($conn, $title, $type, $submitter, $priority, "Custom", "", "", $in_charge);
        
		if (ossim_error()) return false;
        
		foreach ($fields as $value) {
	        $sql = "INSERT INTO incident_custom (incident_id, name, type, content) VALUES (?, ?, ?, ?)";
	        $params = array(
	            $last_id,
	            $value["name"],
				$value["type"],
	            $value["content"]
	        );
	        if (!$conn->Execute($sql, $params)) die($conn->ErrorMsg());
	    }
        return $last_id;
    }
    
	
    function insert_metric($conn, $title, $type, $submitter, $priority, $target, $metric_type, $metric_value, $event_start, $event_end, $transferred_user, $transferred_entity) {
        
		foreach(Incident::get_validations('metric') as $var => $rule) {
            if (!ossim_valid($$var, $rule)) return false;
        }
		
        require_once ('classes/Session.inc');
		
		$in_charge = ( $transferred_user != "" )  ? $transferred_user : $transferred_entity;  
		$in_charge = ( $in_charge != "" )       ? $in_charge : Session::get_session_user(); 
		
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
    
	function __update($conn, $incident_id, $title, $type, $submitter, $priority, $event_start, $event_end, $in_charge){
        
		foreach(Incident::get_validations('incident_update') as $var => $rule) {
            if (!ossim_valid($$var, $rule)) 
				return false;
        }
			
        
		if (empty($event_start)) $event_start = "now";
        if (empty($event_end))   $event_end   = "now";
        
		$event_start = date('Y-m-d H:i:s', strtotime($event_start));
        $event_end   = date('Y-m-d H:i:s', strtotime($event_end));
        
		$sql = "UPDATE incident SET " . "title = ?, type_id = ?, in_charge = ?, submitter = ?, priority = ?, date = date, event_start = ?, event_end = ? " . "WHERE id = ?";
        
		$params = array(
            $title,
            $type,
			$in_charge,
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
	
    function update_alarm($conn, $incident_id, $title, $type, $submitter, $priority, $src_ips, $dst_ips, $src_ports, $dst_ports, $event_start, $event_end, $transferred_user, $transferred_entity) 
	{
        $in_charge = (  $transferred_user != "" ) ? $transferred_user : $transferred_entity;
		
		Incident::__update($conn, $incident_id, $title, $type, $submitter, $priority, $event_start, $event_end, $in_charge);
        
		if ( ossim_error() ) 
			return false;
        		
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
        
		if (!$conn->Execute($sql, $params)) 
			die($conn->ErrorMsg());
        
		return true;
    }
	
    function update_event($conn, $incident_id, $title, $type, $submitter, $priority, $src_ips, $dst_ips, $src_ports, $dst_ports, $event_start, $event_end, $transferred_user, $transferred_entity) {
        
		$in_charge = (  $transferred_user != "" ) ? $transferred_user : $transferred_entity;
		
		Incident::__update($conn, $incident_id, $title, $type, $submitter, $priority, $event_start, $event_end, $in_charge);
        
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
	
    function update_metric($conn, $incident_id, $title, $type, $submitter, $priority, $target, $metric_type, $metric_value, $event_start, $event_end, $transferred_user, $transferred_entity) {
        
		foreach(Incident::get_validations('metric') as $var => $rule) {
            if (!ossim_valid($$var, $rule)) return false;
        }
        
		$in_charge = (  $transferred_user != "" ) ? $transferred_user : $transferred_entity;
		
		Incident::__update($conn, $incident_id, $title, $type, $submitter, $priority, $event_start, $event_end, $in_charge);
        
		if (ossim_error()) 
			return false;
        
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
	
    function update_anomaly($conn, $incident_id, $title, $type, $submitter, $priority, $anom_type, $ip, $data_orig, $data_new, $transferred_user, $transferred_entity) {
        
		$s_data_orig = implode(",", $data_orig);
        $s_data_new  = implode(",", $data_new);
        
		$in_charge   = (  $transferred_user != "" ) ? $transferred_user : $transferred_entity;
		
		Incident::__update($conn, $incident_id, $title, $type, $submitter, $priority, "", "", $in_charge);
        
		if (ossim_error()) 
			return false;
        
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
    
	function update_vulnerability($conn, $incident_id, $title, $type, $submitter, $priority, $ip, $port, $nessus_id, $risk, $description, $transferred_user, $transferred_entity) {
       
		$in_charge = (  $transferred_user != "" ) ? $transferred_user : $transferred_entity;
		
		Incident::__update($conn, $incident_id, $title, $type, $submitter, $priority, "", "", $in_charge);
        if (ossim_error()) 
			return false;
        
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
        $conn->Execute("DELETE FROM incident_custom  WHERE incident_id=$id");
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
	
    /* Incident statistics */
	
    function incidents_by_type($conn, $assets="", $user="") {
        
        $tp_accumulated = array();
		
		$incident_list  = Incident::get_list_filtered($conn, $assets, "AND status=\"Open\" ORDER BY status DESC", $user);

		if (  is_array($incident_list) && !empty($incident_list) )
		{
			foreach($incident_list as $incident) 
			{
				$type                  = $incident->get_type();
				$tp_accumulated[$type] = ( array_key_exists($type, $tp_accumulated) ) ? $tp_accumulated[$type]+1 : 1;
			}
		}
		
	    return $tp_accumulated;
    }
	
	function incidents_by_class($conn, $assets="", $user="") {
        
		$class_accumulated  = array();
	            
		$incident_list      = Incident::get_list_filtered($conn, $assets, "ORDER BY status DESC", $user);

		if (  is_array($incident_list) && !empty($incident_list) )
		{
			foreach($incident_list as $incident) 
			{
				$class                     = $incident->get_ref();
				$class_accumulated[$class] = ( array_key_exists($class, $class_accumulated) ) ? $class_accumulated[$class]+1 : 1;
			}
		}
		
	    return $class_accumulated;
    }
	
	function incidents_by_resolution_time($conn, $assets="", $user="") {
        
		// Gets tags
        $tags    = array();
        $t_sql   = "SELECT incident_tag.tag_id, incident_tag.incident_id FROM incident_tag";
        
		if (!$rs = $conn->Execute($t_sql)) 
			die($conn->ErrorMsg());
        while (!$rs->EOF) 
		{
            $tags[$rs->fields["incident_id"]][] = $rs->fields["tag_id"];
            $rs->MoveNext();
        }
		
		$incident_list = Incident::get_list_filtered($conn, $assets, "AND status = \"Closed\"", $user);

		if (  is_array($incident_list) && !empty($incident_list) )
		{
			foreach($incident_list as $incident) 
			{
				$incident->_life_time_diff = strtotime($incident->get_date()) - strtotime($incident->get_last_modification());
				$incident->tags            = ( isset($tags[$incident->get_id()]) ) ? $tags[$incident->get_id()] : array();
			}
		}
		
	    return $incident_list;
    }
	
	
    function incidents_by_type_descr($conn) 
	{
        $tdescr_accumulated = array();
        
		$incident_list      = Incident::get_list_type_descr($conn);
		
		if (  is_array($incident_list) && !empty($incident_list) )
		{
            foreach($incident_list as $incident) 
			{
                $type_descr                     = $incident->get_type_descr();
                $tdescr_accumulated[$type_descr] = ( array_key_exists($type_descr, $tdescr_accumulated) ) ? $tdescr_accumulated[$type_descr]++ : 1;
            }
        }
        
		asort($tdescr_accumulated);
       
        return $tdescr_accumulated;
    }
	
    function incidents_by_status($conn, $assets="", $user="") {
        		
		$st_accumulated = array();
		
		$incident_list  = Incident::get_list_filtered($conn, $assets, "ORDER BY status DESC", $user);
			
		if ( is_array($incident_list) && !empty($incident_list) )
		{
			$st_accumulated = array("Open" => 0, "Closed" => 0);
			  
			foreach($incident_list as $incident)
			{
				$status = $incident->get_status();
				$st_accumulated[$status]+= 1;
			}
		}
		
		return $st_accumulated;
 
    }
	
    function incidents_by_user($conn, $open_only=true, $assets="", $user="") {
    	
        $user_list       = array();
        $args            = "";
		
		$conf            = $GLOBALS["CONF"];
		$version         = $conf->get_conf("ossim_server_version", FALSE);
		$pro             = ( preg_match("/pro|demo/i",$version) ) ? true : false;
        
		if ( $open_only )  
			$args = "AND status = \"Open\"";
        
		$args .= " ORDER BY status DESC";
        
		$incident_list = Incident::get_list_filtered($conn, $assets, $args, $user);
		
		if (  is_array($incident_list) && !empty($incident_list) )
		{
			foreach($incident_list as $incident) 
			{
				$in_charge  = $incident->get_in_charge($conn);
				if ( $pro && preg_match("/^\d+$/",$in_charge)) 
				{
					   list($entity_name, $entity_type) = Acl::get_entity_name_type($conn, $in_charge);
					   $in_charge                       = $entity_name." [".$entity_type."]";
				}
				
				$user_list[$in_charge] = ( array_key_exists($in_charge, $user_list) ) ? $user_list[$in_charge]++ : 1;
			}
		}
		
		asort($user_list);
       	
        return $user_list;
    }
	
	function incidents_by_priority($conn, $assets="", $user="") {
    	
		$prio_accumulated = array();

		$incident_list = Incident::get_list_filtered($conn, $assets, "AND status = \"Open\" ORDER BY status DESC", $user);
		
		if (  is_array($incident_list) && !empty($incident_list) )
		{
			$prio_accumulated = array(  "0"  => 0, "1"  => 0, "2"  => 0, "3"  => 0,	"4"  => 0,
									    "5"  => 0, "6"  => 0, "7"  => 0, "8"  => 0, "9"  => 0, "10" => 0);
							
			foreach($incident_list as $incident) 
			{
				$priority                    = $incident->get_priority();
				$prio_accumulated[$priority] = $prio_accumulated[$priority] + 1;
			}
		}
		
		return $prio_accumulated;
    }
	
	function incidents_closed_by_month($conn, $assets="", $user="")
	{
		
		$et_by_month  = null;
		$months       = null;
		
		$event_types  = array("Alarm"          => 'ref="Alarm"',
							  "Alert"          => 'ref="Alert"',
							  "Event"          => 'ref="Event"',
							  "Metric"         => 'ref="Metric"',
							  "Anomaly"        => 'ref="Anomaly"',
							  "Vulnerability"  => 'ref="Vulnerability"'
							  );
		
				
		for ($i = 11; $i>=0 ; $i--)
		{
			$time    = strtotime("-".$i." months");
			
			$m_l     = date("F", $time);
			$y       = date("Y", $time);
			
					
			$time    = strtotime("next month $m_l $y - 1 hour");
			
			$y_s     = date("y", $time);
			$m       = date("m", $time);
			$m_s     = date("M", $time);
			$d       = date("d", $time);
				
			$months["$m_s-$y_s"] = "date >= \"$y-$m-01 00:00:00\" and date <= \"$y-$m-$d 23:59:59\""; 
			
		}
		
		foreach($event_types as $et => $sql_et)
		{
			
			$data  = null;
			
			foreach($months as $m => $m_sql )
			{
				$args  = "AND status='Closed' AND $sql_et AND $m_sql";
											
                $incident_list = Incident::get_list_filtered($conn, $assets, $args, $user);
                $data[$m]      = count($incident_list);
			}
			
			$et_by_month[$et] = $data;
		}
			
		
		return $et_by_month;
	}
	
	
		
    function chk_status($status) {
        return in_array((string)$status, array('Open', 'Closed'));
    }
	
	function format_custom_field($id, $incident_id, $value, $type)
	{
		switch ($type)
		{
			case "Map":
				$unique_id = md5( uniqid() );
				$field  = "<span style='font-weight: bold; padding-right: 10px;'>Location:</span><span>".$value."</span>";
				$field .= "<div id='".$unique_id."' style='margin: 10px auto; height:200px; width:100%;'></div>";
				$field .= "<script type='text/javascript'>initialize('".$unique_id."','".$value."')</script>";
			break;

			case "Asset":
                $field = $value;
                $conf = $GLOBALS["CONF"];
                $version = $conf->get_conf("ossim_server_version", FALSE);
                if (preg_match("/pro|demo/i",$version) && preg_match("/ENTITY:\s*(\d+)/",$value,$found)) {
                    require_once("classes/Acl.inc");
                    require_once ('ossim_conf.inc');
                    $db = new ossim_db();
                    $conn = $db->connect();
                    list($name, $type) = Acl::get_entity_name_type($conn,$found[1]);
                    if($type!="" && $name!="") $field = "ENTITY: $name [$type]";
                    $conn->disconnect();
                }
			break;
			
			case "File":
				$field  = "<a href='incident_download_content.php?id=$id&incident_id=$incident_id'>"._("Download content")."</a>";
			break;
						
			default:
				$field = $value;
		}
		
		return $field;
	}
}
?>
