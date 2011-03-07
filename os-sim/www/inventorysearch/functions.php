<?
require_once ('classes/Status.inc');
require_once ('classes/Host.inc');
require_once ('classes/Host_os.inc');
require_once ('classes/Net.inc');
include ("geoip.inc");

//$date_from = (GET('date_from') != "Any date" && GET('date_from') != "") ? preg_replace("/(\d\d)\/(\d\d)\/(\d\d\d\d)/","\\3-\\1-\\2",GET('date_from')) : "1970-01-01";
//$date_to = (GET('date_to') != "Any date" && GET('date_to') != "") ? preg_replace("/(\d\d)\/(\d\d)\/(\d\d\d\d)/","\\3-\\1-\\2",GET('date_to')) : "3000-01-01";

$date_from = (GET('date_from') != "Any date" && GET('date_from') != "") ? GET('date_from') : date("Y-m-d",strtotime("-1 year"));
$date_to = (GET('date_to') != "Any date" && GET('date_to') != "") ? GET('date_to') : date("Y-m-d");

ossim_valid($date_from, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("from date"));
ossim_valid($date_to, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("to date"));
// All Empty
$basic_search = array();
$basic_search[0] = array(
	"type"=>"Generic",
	"subtype"=>"None",
	"match"=>"LIKE",
	//"query"=>"SELECT DISTINCT INET_NTOA(ip_dst) AS ip FROM snort.ac_dstaddr_ipsrc WHERE INET_NTOA(ip_src) %op% ? UNION SELECT DISTINCT INET_NTOA(dst_ip) as ip FROM alarm WHERE INET_NTOA(src_ip) %op% ? UNION SELECT DISTINCT INET_NTOA(ip_src) AS ip FROM snort.ac_srcaddr_ipdst WHERE INET_NTOA(ip_dst) %op% ? UNION SELECT DISTINCT INET_NTOA(src_ip) as ip FROM alarm WHERE INET_NTOA(dst_ip) %op% ?",
	"query"=>"SELECT DISTINCT INET_NTOA(ip_dst) AS ip FROM snort.ac_dstaddr_ipsrc WHERE INET_NTOA(ip_src) > 0 UNION SELECT DISTINCT INET_NTOA(dst_ip) as ip FROM alarm WHERE INET_NTOA(src_ip) > 0 UNION SELECT DISTINCT INET_NTOA(ip_src) AS ip FROM snort.ac_srcaddr_ipdst WHERE INET_NTOA(ip_dst) > 0 UNION SELECT DISTINCT INET_NTOA(src_ip) as ip FROM alarm WHERE INET_NTOA(dst_ip) > 0",
	"query_match"=>"boolean");
	
// Network
$basic_search[1] = array(
	"type"=>"Network",
	"subtype"=>"Network is like",
	"match"=>"LIKE",
	"query"=>"SELECT ip FROM host WHERE INET_ATON(ip) BETWEEN ?",
	"query_match"=>"network");

// Inventory
$basic_search[2] = array(
	"type"=>"Inventory",
	"subtype"=>"Has Serv/OS",
	"match"=>"LIKE",
	"query"=>"function:query_inventory",
	"query_match"=>"text");

// Vulnerabilities
$basic_search[3] = array(
	"type"=>"Vulnerabilities",
	"subtype"=>"Vuln contains",
	"match"=>"LIKE",
	/*"query"=>"SELECT DISTINCT INET_NTOA(hp.host_ip) as ip FROM host_plugin_sid hp, plugin_sid p WHERE hp.plugin_id = 3001 AND p.plugin_id = 3001 AND hp.plugin_sid = p.sid AND p.name %op% ? 
		UNION 
		SELECT DISTINCT INET_NTOA(s.host_ip) as ip FROM vuln_nessus_plugins p,host_plugin_sid s WHERE s.plugin_id=3001 and s.plugin_sid=p.id AND p.cve_id %op% ?",*/
	"query"=>"SELECT DISTINCT hostIP as ip FROM vuln_nessus_latest_results WHERE msg %op% ? 
		UNION 
		SELECT DISTINCT hostIP as ip FROM vuln_nessus_plugins p,vuln_nessus_latest_results s WHERE p.id=s.scriptid and p.cve_id %op% ?",
	"query_match"=>"text");

// Tickets
$basic_search[4] = array(
	"type"=>"Incidents",
	"subtype"=>"Incident contains",
	"match"=>"LIKE",
	"query"=>"SELECT DISTINCT a.src_ips as ip FROM incident i,incident_alarm a WHERE i.id=a.incident_id AND i.date >= '$date_from 00:00:00' AND i.date <= '$date_to 23:59:59' AND a.src_ips != '' AND i.title %op% ? 
		UNION 
		SELECT DISTINCT a.dst_ips as ip FROM incident i,incident_alarm a WHERE i.id=a.incident_id AND i.date >= '$date_from 00:00:00' AND i.date <= '$date_to 23:59:59' AND a.dst_ips != '' AND i.title %op% ? 
		UNION 
		SELECT DISTINCT v.ip FROM incident i,incident_vulns v WHERE i.id=v.incident_id AND i.date >= '$date_from 00:00:00' AND i.date <= '$date_to 23:59:59' AND v.ip != '' AND (i.title %op% ? OR v.description %op% ?)
		UNION 
		SELECT DISTINCT inet_ntoa(a.src_ip) as ip FROM alarm a,plugin_sid p WHERE a.plugin_id=p.plugin_id AND a.plugin_sid=p.sid AND a.timestamp >= '$date_from 00:00:00' AND a.timestamp <= '$date_to 23:59:59' AND a.src_ip != '' AND p.name %op% ? 
		UNION 
		SELECT DISTINCT inet_ntoa(a.dst_ip) as ip FROM alarm a,plugin_sid p WHERE a.plugin_id=p.plugin_id AND a.plugin_sid=p.sid AND a.timestamp >= '$date_from 00:00:00' AND a.timestamp <= '$date_to 23:59:59' AND a.dst_ip != '' AND p.name %op% ? 
		UNION
		SELECT r.keyname as ip FROM repository d,repository_relationships r WHERE d.id=r.id_document AND r.type='host' AND keyname!='' AND d.text %op% ?",
	"query_match"=>"text");

// Security Events
$basic_search[5] = array(
	"type"=>"Security Events",
	"subtype"=>"Event contains",
	"match"=>"LIKE",
	"query"=>"SELECT DISTINCT INET_NTOA(ac.ip_src) as ip FROM snort.ac_srcaddr_signature ac,ossim.plugin_sid s WHERE s.plugin_id=ac.plugin_id AND s.sid=ac.plugin_sid AND ac.day >= '$date_from' AND ac.day <= '$date_to' AND s.name %op% ?
		UNION
		SELECT DISTINCT INET_NTOA(ac.ip_dst) as ip FROM snort.ac_dstaddr_signature ac,ossim.plugin_sid s WHERE s.plugin_id=ac.plugin_id AND s.sid=ac.plugin_sid AND ac.day >= '$date_from' AND ac.day <= '$date_to' AND s.name %op% ?",
	"query_match"=>"text");


function get_rulesconfig () {
	require_once ('classes/InventorySearch.inc');
	require_once 'ossim_db.inc';
	require_once 'ossim_conf.inc';
	
	// Database Object
	$db   = new ossim_db();
	$conn = $db->connect();

	$db_rules = InventorySearch::get_all($conn);

	foreach ($db_rules as $rule) {
		$type    = $rule->get_type();
		$subtype = $rule->get_subtype();
		$rules[$type][$subtype]['list'] = $rule->get_prelist();
		$rules[$type][$subtype]['query'] = $rule->get_query();
		$rules[$type][$subtype]['match'] = $rule->get_match();
	}
	return $rules;
}

function build_query ($sql,$value,$match="",$match_type="") {
	if ($match == "" && ($match_type == "text" || $match_type == "ip")) $match = "LIKE"; // LIKE as default
	if ($match == "eq" || $match == "") $match = "=";
	if ($match == "LIKE" && $match_type != "network") $value = "%".$value."%";
	if ($match_type == "network") {
		$ip_range = CIDR::expand_CIDR($value, "SHORT", "IP");
		$value = "INET_ATON('".$ip_range[0]."') AND INET_ATON('".$ip_range[1]."')";
		$sql = str_replace("?",$value,$sql); // ? replace breaks in library, do it here
	}
	// Date
	if (preg_match("/(\d\d)\/(\d\d)\/(\d\d\d\d)/",$value)) $value = preg_replace ("/(\d\d)\/(\d\d)\/(\d\d\d\d)/","\\3-\\1-\\2",$value);
	$sql = str_replace ("%op%",$match,$sql);
	if ($sql != "") $count = substr_count($sql,"?",0,strlen($sql));
	$params = array();
	for ($i = 0; $i < $count; $i++) $params[] = $value;
	return array($sql,$params);
}

function build_query_two_values ($sql,$value,$value2,$match="",$match_type="") {
	if($match == "" && $match_type == "fixedText"){
		$match = "=";
	}
	$sql = str_replace ("%op%",$match,$sql);
	if ($sql != "") $count = substr_count($sql,"?",0,strlen($sql));
	$sql = str_replace ('$value2',$value2,$sql);
	$params = array();
	for ($i = 0; $i < $count; $i++) $params[] = $value;
	
	return array($sql,$params);
}

function build_concat_query ($sql,$value) {
	$values = explode("-",$value);
	if ($sql != "") $count = substr_count($sql,"?",0,strlen($sql))/2;
	$params = array();
	for ($i = 0; $i < $count; $i++) { $params[] = $values[0]; $params[] = $values[1]; }
	return array($sql,$params);
}

function get_params ($value,$sql) {
	$count = substr_count($sql,"?",0,strlen($sql));
	$ret = array();
	for ($i = 0; $i < $count; $i++) $ret[] = $value;
	return $ret;
}

function check_security ($value, $match, $value2=NULL, $userfriendly=false) {
	require_once ("classes/Security.inc");
				
	switch($match) {
		case "text":
			ossim_valid($value, OSS_SPACE, OSS_ALPHA, OSS_SCORE, OSS_SLASH, OSS_DOT, 'illegal:' . _("$match value"));
			break;
		case "ip":
			// "LIKE" patch
			if (preg_match("/^\d+\.\d+\.\d+$/",$value)) $value .= ".0";
			elseif (preg_match("/^\d+\.\d+\$/",$value)) $value .= ".0.0";
			elseif (preg_match("/^\d+$/",$value)) $value .= ".0.0.0";
			ossim_valid($value, OSS_IP_ADDR, 'illegal:' . _("$match value"));
			break;
		case "network":
			ossim_valid($value, OSS_IP_CIDR, 'illegal:' ._("$match value"));
			break;
		case "number":
			ossim_valid($value, OSS_DIGIT, 'illegal:' . _("$match value"));
			break;
		case "fixed":
			ossim_valid($value, OSS_SPACE, OSS_ALPHA, OSS_SCORE, OSS_SLASH, OSS_DOT, 'illegal:' . _("$match value"));
			//ossim_valid($value, OSS_ALPHA, OSS_SCORE, OSS_SLASH, 'illegal:' . _("$match value"));
			break;
		case "concat":
			ossim_valid($value, OSS_ALPHA, '-', 'illegal:' . _("$match value"));
			break;
		case "fixedText":
			ossim_valid($value2, OSS_SPACE, OSS_ALPHA, OSS_SCORE, OSS_SLASH, 'illegal:' . _("$match value"));
			ossim_valid($value, OSS_ALPHA, OSS_SCORE, OSS_SLASH, 'illegal:' . _("$match value"));
			break;
	}
	if ( ossim_error() )
	{
	?>
		<table class="noborder transparent" align="center" width="94%">
			<tr><td class='nobborder'><div class='ossim_error'><?php echo ossim_get_error();?></div></td></tr>
			<tr>
				<td class="nobborder" style="padding:10px 0;text-align:center">
					<?php $location = ( $userfriendly ) ? "/ossim/inventorysearch/userfriendly.php" : "/ossim/inventorysearch/inventory_search.php";  ?>
					<input type="button" value="Back" onclick="document.location.href='<?php echo $location;?>'" class="button"/>
				</td>
			</tr>
		</table>
	<?php
		exit();
	}
}

function isSerialized($str) {
    return ($str == serialize(false) || @unserialize($str) !== false);
}

function host_row ($host,$conn,$criterias,$has_criterias,$networks,$hosts_ips) {
	$ip = $host->get_ip();
	$gi = geoip_open("/usr/share/geoip/GeoIP.dat", GEOIP_STANDARD);
	$country = strtolower(geoip_country_code_by_addr($gi, $ip));
	$country_name = geoip_country_name_by_addr($gi, $ip);
	if ($country) {
		$country_img = " <img src=\"../pixmaps/flags/" . $country . ".png\" alt=\"$country_name\" title=\"$country_name\">";
	} else {
		$country_img = "";
	}
	$homelan = (Net::is_ip_in_cache_cidr($conn, $ip, $networks) || in_array($ip, $hosts_ips)) ? " <a href=\"javascript:;\" class=\"scriptinfo\" style=\"text-decoration:none\" ip=\"".$ip."\"><img src=\"../forensics/images/homelan.png\" border=0></a>" : "";
	$os = Host_os::get_os_pixmap($conn, $ip);
	$row = '
	<tr>
		<td style="padding-bottom:10px" class="nobborder">
			<table class="noborder" style="background-color:white">
				<tr>
					<td class="nobborder"><a href="../report/host_report.php?host='.$ip.'" id="'.$ip.';'.$host->get_hostname().'" class="HostReportMenu" style="color:#17457c;text-decoration:underline;font-size:15px;text-align:left"><b>'.$ip.'</b> <font style="font-size:12px">HostName: <b>'.($host->get_hostname()).'</b>'.$country_img.$homelan.' '.$os.'</font></a></td>
				</tr>
				<tr>
					<td class="nobborder">
						<table class="noborder" style="background-color:white" height="100%"><tr>';
							foreach ($criterias as $type=>$subtypes_arr) {
							$row .= '<td class="nobborder" valign="top">'.Util::print_gadget($type,"white",criteria_row($conn,$ip,$type,$subtypes_arr,$has_criterias)).'</td>';
							}
							$row .= '
						</tr></table>
					</td>
				</tr>
			</table>
		</td>
	</tr>';
	echo str_replace("\n","",str_replace("\r","",$row));
}

function basic_header () {
	?><tr><th><?=_("Assets")?></th><th><?=_("Inventory")?></th><th><?=_("Vulnerabilities")?></th><th><?=_("Incidents")?></th><th><?=_("Events")?></th><th><?=_("Anomalies")?></th><th><?=_("Traffic Profile")?></th></tr><?
}

function host_row_basic ($host,$conn,$criterias,$has_criterias,$networks,$hosts_ips,$i) {
    require_once("classes/Sensor.inc");
	$color = ($i%2==0) ? "#F2F2F2" : "#FFFFFF";
	$ip = $host->get_ip();
	$host_name = ($ip != $host->get_hostname()) ? $host->get_hostname()." ($ip)" : $ip;
	$gi = geoip_open("/usr/share/geoip/GeoIP.dat", GEOIP_STANDARD);
	$country = strtolower(geoip_country_code_by_addr($gi, $ip));
	$country_name = geoip_country_name_by_addr($gi, $ip);
	if ($country) {
		$country_img = " <img src=\"../pixmaps/flags/" . $country . ".png\" alt=\"$country_name\" title=\"$country_name\">";
	} else {
		$country_img = "";
	}
	//$homelan = (Net::isIpInNet($ip, $networks) || in_array($ip, $hosts_ips)) ? " <a href=\"javascript:;\" class=\"scriptinfo\" style=\"text-decoration:none\" ip=\"".$ip."\"><img src=\"../forensics/images/homelan.png\" border=0></a>" : "";
	// Network
	require_once('classes/Net.inc');
	$netname = Net::GetClosestNet($conn, $ip);
	if ($netname != false) {
		$ips = Net::get_ips_by_name($conn,$netname);
		$net = "<b>$netname</b> ($ips)";
	}
	else $net = "<i>"._("Asset Unknown")."</i>";
	// Inventory
	$os_data = Host_os::get_ip_data($conn, $ip);
	if ($os_data["os"] != "") {
		$os = $os_data["os"];
		$os_pixmap = Host_os::get_os_pixmap($conn, $ip);
	} else {
		$os = _("OS Unknown");
		$os_pixmap = "";
	}
	require_once('classes/Host_services.inc');
	$services = Host_services::get_ip_data($conn, $ip, 0);
	$services_arr = array();
	foreach ($services as $serv) {
		$services_arr[$serv['service']]++;
	}
	// Vulnerabilities
	require_once('classes/Status.inc');
	list($vuln_list,$num_vuln,$vuln_highrisk,$vuln_risknum) = Status::get_vul_events($conn,$ip);
	$vuln_list_str = ""; $v=0;
	foreach ($vuln_list as $vuln) if ($v++<20) $vuln_list_str .= $vuln['name']."<br>";
	$vuln_list_str = str_replace("\"","",$vuln_list_str);
	$vuln_caption = ($num_vuln > 0) ?  ' class="greybox_caption" data="'.$vuln_list_str.'"' : ' class="greybox"';
	// Incidents
	$sql = "SELECT count(*) as num FROM alarm WHERE src_ip=INET_ATON(\"$ip\") OR dst_ip=INET_ATON(\"$ip\")";
	if (!$rs = & $conn->Execute($sql)) {
		$num_alarms = _("Error in Query: $sql");
	} else {
		if (!$rs->EOF) {
			$num_alarms = $rs->fields['num'];
		}
	}
	if ($num_alarms > 0) $alarm_link = '<a href="../control_panel/alarm_console.php?&hide_closed=1&hmenu=Alarms&smenu=Alarms&src_ip='.$ip.'&dst_ip='.$ip.'" target="main"><b>'.$num_alarms.'</b></a>';
	else $alarm_link = '<b>'.$num_alarms.'</b>';
	$sql = "SELECT count(*) as num FROM incident_alarm WHERE src_ips=\"$ip\" OR dst_ips=\"$ip\"";
	if (!$rs = & $conn->Execute($sql)) {
		$num_tickets = _("Error in Query: $sql");
	} else {
		if (!$rs->EOF) {
			$num_tickets = $rs->fields['num'];
		}
	}
	if ($num_tickets > 0) $tickets_link = '<a href="../incidents/index.php?status=Open&hmenu=Tickets&smenu=Tickets&with_text='.$ip.'" target="main"><b>'.$num_tickets.'</b></a>';
	else $tickets_link = '<b>'.$num_tickets.'</b>';
	
	// Events
	list($sim_events,$sim_foundrows,$sim_highrisk,$sim_risknum,$sim_date) = Status::get_SIM_light($ip,$ip);
	
	if ($sim_foundrows > 0) $sim_link = '<a href="../forensics/base_qry_main.php?&num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d&ip='.$ip.'&date_range=week&hmenu=Forensics&smenu=Forensics" target="main"><b>'.$sim_foundrows.'</b></a>';
	else $sim_link = '<b>'.$sim_foundrows.'</b>';
	//
	$txt_tmp1=_('Events in the SIEM');
	$txt_tmp2=_('Events in the logger');
	if ($_SESSION['inventory_search']['date_from'] != "" && $_SESSION['inventory_search']['date_from'] !='1700-01-01'){
		$start_week = $_SESSION['inventory_search']['date_from'];		
	} else {
		$start_week = strftime("%Y-%m-%d", time() - (24 * 60 * 60 * 1));
	}
	if ($_SESSION['inventory_search']['date_to'] != "" && $_SESSION['inventory_search']['date_to'] != '3000-01-01'){
		$end = $_SESSION['inventory_search']['date_to'];
	} else {
		$end = strftime("%Y-%m-%d", time());
	}
	if ($start_week == strftime("%Y-%m-%d", time() - (24 * 60 * 60 * 1)) && $end == strftime("%Y-%m-%d", time())) {
		$txt_tmp1.=_(' (Last Week)');
		$txt_tmp2.=_(' (Last Day)');
	}
	$start_week_temp=$start_week;
	$start_week.=' 00:00:00';
	$end_temp=$end;
	$end.=' 23:59:59';
	//
	//$start_week = strftime("%Y-%m-%d %H:%M:%S", time() - (24 * 60 * 60 * 7));
	//$end = strftime("%Y-%m-%d %H:%M:%S", time());
	list($sem_events_week,$sem_foundrows_week,$sem_date,$sem_wplot_y,$sem_wplot_x) = Status::get_SEM("",$start_week,$end,"none",1234,$ip);
	if ($sem_foundrows_week > 0) $sem_link = '<a href="../sem/index.php?hmenu=SEM&smenu=SEM&query='.urlencode($ip).'&start='.urlencode($start_week).'" target="main"><b>'.$sem_foundrows_week.'</b></a>';
	else $sem_link = '<b>'.$sem_foundrows_week.'</b>';
	// Anomalies
	list($event_list,$anm_foundrows,$anm_foundrows_week,$anm_date) = Status::get_anomalies($conn,$ip);
	// Ntp link
	$ntop_lnk = Sensor::get_sensor_link($conn,$ip);
	if (preg_match("/(\d+\.\d+\.\d+\.\d+)/",$ntop_lnk,$fnd)) $ntop_ip = $fnd[1];
	else $ntop_ip = $ip;
	//
	$row = '<tr bgcolor="'.$color.'">
				<td class="nobborder" style="text-align:center;padding:2px"><a href="../report/host_report.php?host='.$ip.'&star_date='.$start_week_temp.'&end_date='.$end_temp.'" id="'.$ip.';'.$host->get_hostname().'" class="HostReportMenu" style="color:#17457c;font-size:15px;text-align:left"><b>'.$host_name.'</b></font></a><br><font style="color:gray">'.$net.'</font></td>
				<td class="nobborder" style="text-align:center;padding:2px">'.$os.' '.$os_pixmap.'<br>'.implode("<br>",array_keys($services_arr)).'</td>
				<td class="nobborder" style="text-align:center;padding:2px"><a href="../vulnmeter/index.php?value='.$ip.'&type=hn&withoutmenu=1&hmenu=Vulnerabilities&smenu=Vulnerabilities" title="Top 20 '._("Vulnerabilities for").' '.$ip.'"'.$vuln_caption.'>'.$num_vuln.'</a></td>
				<td class="nobborder" style="text-align:center;padding:2px">'.$alarm_link.' '._("Alarms").'<br>'.$tickets_link.' '._("Tickets").'</td>
				<td class="nobborder" style="padding:2px">'.$sim_link.' '.$txt_tmp1.'<br>'.$sem_link.' '.$txt_tmp2.'</td>
				<td class="nobborder" style="text-align:center;padding:2px"><a href="../control_panel/anomalies.php?withoutmenu=1" class="greybox" title="'._("Anomalies").'"><b>'.$anm_foundrows.'</b></a></td>
				<td class="nobborder" style="text-align:center;padding:2px">
					<table class="transparent">
						<tr>
							<td class="nobborder"><img src="../pixmaps/ntop_graph_thumb.gif" width="40"></td>
							
							<td class="nobborder"><a href="../ntop/index.php?opc=services&sensor='.$ntop_ip.'&hmenu=Network&smenu=Profiles&link_ip='.$ip.'" target="main">'._("Traffic Sent/Rcvd").'</a></td>
						</tr>
					</table>
				</td>
			</tr>';
	// <td class="nobborder"><a href="'.Sensor::get_sensor_link($conn,$ip).'/hostTimeTrafficDistribution-'.$ip.'-65535.png?1" class="greybox">'._("Traffic Sent").'</a><br><a href="'.Sensor::get_sensor_link($conn,$ip).'/hostTimeTrafficDistribution-'.$ip.'-65535.png" class="greybox">'._("Traffic Rcvd").'</a></td>
	echo str_replace("\n","",str_replace("\r","",str_replace("'","",$row)));
}

function criteria_row ($conn,$ip,$type,$subtype_arr,$has_criterias) {
	if ($type == "Alarms") {
		if ($subtype_arr["Has no Alarm"] != "") {
			// Check
			return ($has_criterias[$type."Has no Alarm"][$ip]) ? "Has no Alarm <img src=\"../pixmaps/tick.png\"><br>" : "no<br>";
		} else {
			// Top 5 alarms listing
			if ($subtype_arr["Has open Alarms"] != "") $status = 1;
			elseif ($subtype_arr["Has closed Alarms"] != "") $status = -1;
			else $status = 0;
			return Status::print_Alarms($ip,$status);
		}
	} elseif ($type == "Events") {
		if ($subtype_arr["Has no Event"] != "") {
			// Check
			return ($has_criterias[$type."Has no Event"][$ip]) ? "Has no Event <img src=\"../pixmaps/tick.png\"><br>" : "no<br>";
		} elseif ($subtype_arr["Has Different"] != "") {
			// Top 5 Unique Events listing
			return Status::print_UEvents($ip);
		} elseif ($subtype_arr["Has Event"] != "") {
			return Status::print_Events($ip);
		} else {
			foreach ($subtype_arr as $subtype=>$val) {
				$ret .= "$subtype <b>$val</b> ".(($has_criterias[$type.$subtype][$ip]) ? "<img src=\"../pixmaps/tick.png\"><br>" : "<img src=\"../pixmaps/cross.png\"><br>");
			}
			return $ret;
		}
	}
	else {
		$ret = "";
		foreach ($subtype_arr as $subtype=>$val) {
			$ret .= "$subtype <b>$val</b> ".(($has_criterias[$type.$subtype][$ip]) ? "<img src=\"../pixmaps/tick.png\"><br>" : "<img src=\"../pixmaps/cross.png\"><br>");
		}
		return $ret;
	}
}

// SPECIAL QUERY FUNCTIONS
function query_inventory ($value) {
	require_once 'ossim_db.inc';
	// Database Object
	$db = new ossim_db();
	$conn = $db->connect();
	
	$date_from = ($_SESSION['inventory_search']['date_from'] != "") ? $_SESSION['inventory_search']['date_from'] : "1700-01-01";
	$date_to = ($_SESSION['inventory_search']['date_to'] != "") ? $_SESSION['inventory_search']['date_to'] : "3000-01-01";
	
	$value = str_replace("/","\/",$value);
	
	$error = "";
	$matches = array();
	$ips = array();
	
	// OS
	$allips = array();
	$sql = "SELECT DISTINCT ip FROM host_os";
	if (!$rs = & $conn->Execute($sql)) {
		$error = _("Error in Query: $sql");
	} else {
		while (!$rs->EOF) {
			$allips[] = $rs->fields['ip'];
			$rs->MoveNext();
		}
	}
	foreach ($allips as $ip) {
		/*
		$anom0os = $anom1os = "";
		$sql2 = "SELECT os FROM host_os WHERE os LIKE '%$value%' AND ip=$ip AND anom=0 AND date >= '$date_from' AND date <= '$date_to' ORDER BY date DESC LIMIT 1";
		if (!$rs = & $conn->Execute($sql2, $params)) {
			$error = _("Error in Query: $sql2");
		} else {
			while (!$rs->EOF) {
				$anom0os = $rs->fields['os'];
				$rs->MoveNext();
			}
		}
		$sql2 = "SELECT os FROM host_os WHERE os LIKE '%$value%' AND ip=$ip AND anom=1 AND date >= '$date_from' AND date <= '$date_to' ORDER BY date DESC LIMIT 1";
		if (!$rs = & $conn->Execute($sql2, $params)) {
			$error = _("Error in Query: $sql2");
		} else {
			while (!$rs->EOF) {
				$anom1os = $rs->fields['os'];
				$rs->MoveNext();
			}
		}
		if ($anom0os != "") $matches[$anom0os][] = long2ip($ip); // Add IP to list
		elseif ($anom1os != "") $matches[$anom1os][] = long2ip($ip);
		*/
		$ret = Host_os::get_ip_data($conn,long2ip($ip));
		$matches[$ret['os']][] = long2ip($ip);
	}
	
	// Services
	$allips = array();
	$sql = "SELECT DISTINCT ip FROM host_services";
	if (!$rs = & $conn->Execute($sql)) {
		$error = _("Error in Query: $sql");
	} else {
		while (!$rs->EOF) {
			$allips[] = $rs->fields['ip'];
			$rs->MoveNext();
		}
	}
	foreach ($allips as $ip) {
		$anom0serv = $anom1serv = "";
		$sql2 = "SELECT service FROM host_services WHERE service LIKE '%$value%' AND ip=$ip AND anom=0 AND date >= '$date_from' AND date <= '$date_to' ORDER BY date DESC LIMIT 1";
		if (!$rs = & $conn->Execute($sql2, $params)) {
			$error = _("Error in Query: $sql2");
		} else {
			while (!$rs->EOF) {
				$anom0serv = $rs->fields['service'];
				$rs->MoveNext();
			}
			//if ($ip == 3232235781) return array(1,"matches IP $anom0serv");
		}
		$sql2 = "SELECT service FROM host_services WHERE service LIKE '%$value%' AND ip=$ip AND anom=1 ORDER BY date AND date >= '$date_from' AND date <= '$date_to' DESC LIMIT 1";
		if (!$rs = & $conn->Execute($sql2, $params)) {
			$error = _("Error in Query: $sql2");
		} else {
			while (!$rs->EOF) {
				$anom1serv = $rs->fields['service'];
				$rs->MoveNext();
			}
		}
		if ($anom0serv != "") $matches[$anom0serv][] = long2ip($ip); // Add IP to list
		elseif ($anom1serv != "") $matches[$anom1serv][] = long2ip($ip);
	}
	
	foreach ($matches as $os_service=>$ips_arr) {
		//echo "found $os_service<br>";
		//return array(1,"matches ".implode(",",array_keys($matches)));
		if (preg_match("/$value/i",$os_service)) {
			$ips = $ips_arr;
		}
	}
	
	
	
	if ($error != "") return array(1,$error);
	else return array(0,$ips);
}
?>
