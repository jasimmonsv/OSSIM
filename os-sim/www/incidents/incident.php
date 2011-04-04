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

require_once 'classes/Session.inc';
require_once 'classes/Security.inc';
require_once 'ossim_db.inc';
require_once 'classes/Incident.inc';
require_once 'classes/Incident_ticket.inc';
require_once 'classes/Incident_tag.inc';
require_once 'classes/Osvdb.inc';
require_once 'classes/Repository.inc';
require_once 'incident_common.php';

Session::logcheck("MenuIncidents", "IncidentsIncidents");

// Version
$conf    = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);
$pro     = ( preg_match("/pro|demo/i",$version) ) ? true : false;


$id   = GET('id');
$edit = ( $_GET['edit'] == 1 || $_POST['edit'] == 1 ) ? 1 : 0;

ossim_valid($id, OSS_ALPHA, 'illegal:' . _("Incident Id"));

if ( ossim_error() ) 
    die(ossim_error());
	
$db   = new ossim_db();
$conn = $db->connect();

$incident_list = Incident::search($conn, array('incident_id' => $id));

if (count($incident_list) != 1) {
    die(_("Invalid ticket ID or insufficient permissions"));
}

$incident = $incident_list[0];

//Incident data
$name 				= $incident->get_ticket();
$title 				= $incident->get_title();
$ref 				= $incident->get_ref();
$type 				= $incident->get_type();
$created 			= $incident->get_date();
$life 				= $incident->get_life_time();
$updated 			= $incident->get_last_modification();
$priority 			= $incident->get_priority();
$incident_status    = $incident->get_status();
$incident_in_charge = $incident->get_in_charge();
$users              = Session::get_users_to_assign($conn);
$entities           = Session::get_entities_to_assign($conn);

$incident_tags      = $incident->get_tags();
$incident_tag       = new Incident_tag($conn);
$taga               = array();

foreach($incident_tags as $tag_id) 
	$taga[] = $incident_tag->get_html_tag($tag_id);

$taghtm = count($taga) ? implode(' - ', $taga) : _("n/a");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title><?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<link rel="stylesheet" type="text/css" href="../style/greybox.css"/>

	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/greybox.js"></script>
	<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
	<script type="text/javascript">
				
		var geocoder;
			var map;
					
		function codeAddress(map, address) {
			geocoder.geocode( { 'address': address}, function(results, status) {
			  if (status == google.maps.GeocoderStatus.OK) {
				map.setCenter(results[0].geometry.location);
				var marker = new google.maps.Marker({
					map: map,
					title: address,	
					position: results[0].geometry.location
				});
			  } else {
				  alert('Geocode was not successful for the following reason: ' + status);
			  }
			});
		}
	  
		function initialize(id, address) {
			
			geocoder = new google.maps.Geocoder();
			var myOptions = {
			  zoom: 8,
			  mapTypeId: google.maps.MapTypeId.ROADMAP
			}
		
			map = new google.maps.Map(document.getElementById(id), myOptions);
		
			codeAddress(map, address);
		}
		
		// GreyBox
		function GB_edit(url) {
			GB_show("<?php echo _("Knowledge DB")?>",url,"60%","80%");
			return false;
		}
		
		function GB_onclose() {
			document.location.href = "../incidents/incident.php?id=<?php echo $id?>";
		}
		
		function switch_user(select) {
			if( select=='entity' && $('#transferred_entity').val()!=''){
				$('#user').val('');
			}
			else if (select=='user' && $('#transferred_user').val()!=''){
				$('#entity').val('');
			}
		}
		
		function delete_ticket(id)
		{
			var msg = '<?php echo _("This action will erase the Ticket as well as all the comments on this ticket. Do you want to continue?") ?>';
			
			if ( confirm(msg) ) 
				document.location = 'manageincident.php?action=delincident&incident_id='+id;
		}
		
		<?php 
		if ( Incident::user_incident_perms($conn, $id, 'delticket') ) 
		{ 
		?>
		function delete_comment(ticket_id, incident_id)
		{
			document.location = 'manageincident.php?action=delticket&ticket_id='+ticket_id+'&incident_id='+incident_id;
		}
		<?php 
		} 
		?>
		
		function chg_prio_str()
		{
			prio_num = document.newticket.priority;
			index    = prio_num.selectedIndex;
			prio     = prio_num.options[index].value;
			
			if (prio > 7) {
				document.newticket.prio_str.selectedIndex = 2;
			} 
			else if (prio > 4) 
				document.newticket.prio_str.selectedIndex = 1;
			else 
				document.newticket.prio_str.selectedIndex = 0;
			
		}
		
		function chg_prio_num()
		{
			prio_str = document.newticket.prio_str;
			index = prio_str.selectedIndex;
			prio = prio_str.options[index].value;
			if (prio == 'High') {
				document.newticket.priority.selectedIndex = 7;
			} else if (prio == 'Medium') {
				document.newticket.priority.selectedIndex = 4;
			} else {
				document.newticket.priority.selectedIndex = 2;
			}
		}
	
		$(document).ready(function(){
			
			GB_TYPE = 'w';
			
			$("a.greybox").click(function(){
				var t = this.title || $(this).text() || this.href;
				GB_show(t,this.href,400,'60%');
				return false;
			});
			
			$("a.greybox_2").click(function(){
				var t = this.title || $(this).text() || this.href;
				GB_show(t,this.href,'60%','80%');
				return false;
			});
			
			$('#custom_table tr:odd').css('background', "#F2F2F2");
			
			
			$('#priority').bind('change', function()  { chg_prio_str(); });
			$('#prio_str').bind('change', function()  { chg_prio_num(); });
			
			chg_prio_str();
			
			$('textarea').elastic();
						
		});
			
			
		</script>
  
		 
		<style type='text/css'>
			td { border-width: 0px;}
			a {cursor:pointer;}
								
			#ticket_section_1{ 
				text-align:left;
				padding-left:10px;
				background-color: #efefef;
			}
			
			#ticket_section_2{ text-align:left;padding-left:10px;}
			
			#in_charge_name {color: darkblue; font-weigth: bold;}
			#extra {
				text-align:left;
				padding-left:10px;
				background-color: #efefef;
			}
			
			.documents { padding:0px 3px 0px 3px; height: 18px;}
			
			.disabled img {
				filter:alpha(opacity=50);
				-moz-opacity:0.5;
				-khtml-opacity: 0.5;
				opacity: 0.5;
			}
			
			.email_changes { padding-left: 20px; text-align: left;}
			
			#subscribe_section { text-align: right; padding-right: 10px;}
			
			.format_or { padding:0px 2px 0px 8px;text-align:center; }
			.format_user { width: 48px; text-align:left; padding: 0px 3px;}
			.format_entity { 
				width: 70px; 
				text-align:left;  
				padding: 0px 3px;
			}
			
			#user, #entity {text-align: center !important;}
			#user option {text-align: left;}
			#entity option {text-align: left;}
		</style>
		
		<?php include ("../host_report_menu.php") ?>
</head>

<body>

<?php include ("../hmenu.php"); ?>

<table align="center" width="100%">
	<tr>
		<th> <?php echo _("Ticket ID") ?> </th>
		<th width="600px"><?php echo _("Ticket") ?></th>
		<th> <?php echo _("Status") ?> </th>
		<th> <?php echo _("Priority") ?> </th>
		<th> <?php echo _("Knowledge DB") ?> </th>
		<th> <?php echo _("Action") ?> </th>
	</tr>
	
	<tr>
		<td><strong><?php echo $name?></strong></td>
		
		<td class="left">
			<table width="100%" class="noborder">
				<tr>
					<td>
						<table class="noborder" width="100%">
							<tr>
								<td id='ticket_section_1'>
									<?php echo _("Name") ?>: <strong><?php echo $title ?> </strong><br/>
									<?php echo _("Class") ?>: <?php echo $ref ?><br/>
									<?php echo _("Type") ?>: <?php echo $type ?><br/>
									<?php echo _("Created") ?>: <?php echo $created ?> (<?php echo $life ?>)<br/>
									<?php echo _("Last Update") ?>: <?php echo $updated ?><br/>
									<?php
									if ($incident->get_status($conn) == "Closed") {
										echo _("Resolution time") . ": " . $incident->get_life_time() . "<br/>";
									}
									?>      
								</td>
							</tr>
						
							<tr>
								<td id='ticket_section_2'>
									
									<?php $in_charge_name = format_charge_name($incident->get_in_charge_name($conn), $conn) ?>
									<?php echo _("In charge") ?>: <span id='in_charge_name'><?php echo $in_charge_name; ?></span><br/>
									<?php echo _("Submitter") ?>: <strong><?php echo $incident->get_submitter() ?></strong>
								</td>
							</tr>
							
							<tr><td id='extra'><?php echo _("Extra") ?>: <?php echo $taghtm ?><br/></td></tr>
							
							<?php $td_st = ($ref == 'Custom') ? 'text-align:left;' : 'padding-left:10px; text-align:left;'; ?>
							
													   
							<tr>
								<td style='<?php echo $td_st?>'>
								<?php
								
								if ($ref == 'Alarm' or $ref == 'Event')
								{
									$alarm_list = ( $ref == 'Alarm' ) ? $incident->get_alarms($conn) : $incident->get_events($conn);
									
									foreach($alarm_list as $alarm_data) {
										echo "Source Ips: <a href='../report/host_report.php?host=".$alarm_data->get_src_ips()."' class='HostReportMenu' id='".$alarm_data->get_src_ips().";".$alarm_data->get_src_ips()."'><strong>" . $alarm_data->get_src_ips() . "</strong></a> - " . "Source Ports: <strong>" . $alarm_data->get_src_ports() . "</strong><br/>";
										echo "Dest Ips:   <a href='../report/host_report.php?host=".$alarm_data->get_dst_ips()."' class='HostReportMenu' id='".$alarm_data->get_dst_ips().";".$alarm_data->get_dst_ips()."'><strong>" . $alarm_data->get_dst_ips() . "</strong></a> - " . "Dest Ports: <strong>" . $alarm_data->get_dst_ports() . "</strong>";
									}
								} 
								elseif ($ref == 'Metric')
								{
									$metric_list = $incident->get_metrics($conn);
									
									foreach($metric_list as $metric_data) 
									{
										echo "Target: <strong>" . $metric_data->get_target() . "</strong> - " . "Metric Type: <strong>" . $metric_data->get_metric_type() . "</strong> - " . "Metric Value: <strong>" . $metric_data->get_metric_value() . "</strong>";
									}
								} 
								elseif ($ref == 'Anomaly')
								{
									$anom_list = $incident->get_anomalies($conn);
									
									foreach($anom_list as $anom_data) 
									{
										$anom_type   = $anom_data->get_anom_type();
										$anom_ip     = $anom_data->get_ip();
										$anom_info_o = $anom_data->get_data_orig();
										$anom_info   = $anom_data->get_data_new();
										
										if ($anom_type == 'mac') {
											list($a_sen, $a_date_o, $a_mac_o, $a_vend_o) = explode(",", $anom_info_o);
											list($a_sen, $a_date, $a_mac, $a_vend) = explode(",", $anom_info);
											
											echo "Host: <strong>" . $anom_ip . "</strong><br>";
											echo "Previous Mac: <strong>" . $a_mac_o . "(" . $a_vend_o . ")</strong><br>";
											echo "New Mac: <strong>" . $a_mac . "(" . $a_vend . ")</strong><br>";
										} 
										elseif ($anom_type == 'service') {
											list($a_sen, $a_date, $a_port, $a_prot_o, $a_ver_o) = explode(",", $anom_info_o);
											list($a_sen, $a_date, $a_port, $a_prot, $a_ver) = explode(",", $anom_info);
											
											echo "Host: <strong>" . $anom_ip . "</strong><br>" . "Port: <strong>" . $a_port . "</strong><br>";
											echo "Previous Protocol [Version]: <strong>" . $a_prot_o . " [" . $a_ver_o . "]</strong><br>"; 
											echo "New Protocol [Version]: <strong>" . $a_prot . " [" . $a_ver . "]</strong><br>";
										} 
										elseif ($anom_type == 'os') {
											list($a_sen, $a_date, $a_os_o) = explode(",", $anom_info_o);
											list($a_sen, $a_date, $a_os) = explode(",", $anom_info);
											
											echo "Host: <strong>" . $anom_ip . "</strong><br>"; 
											echo "Previous OS: <strong>" . $a_os_o . "</strong><br>"; 
											echo "New OS: <strong>" . $a_os . "</strong><br>";
										}
									}
								} 
								elseif ($ref == 'Vulnerability')
								{
									$vulnerability_list = $incident->get_vulnerabilities($conn);
									
									foreach($vulnerability_list as $vulnerability_data) 
									{
										// Osvdb starting
										$nessus_id = $vulnerability_data->get_nessus_id();
										$osvdb_id  = Osvdb::get_osvdbid_by_nessusid($conn, $nessus_id);
										if ($osvdb_id) 
											$nessus_id = "<a href=\"osvdb.php?id=" . $osvdb_id . "\">" . $nessus_id . "</a>";
										// Osvdb end 
										// Add name and kdb link
										require_once ("classes/Host.inc");
										require_once ("classes/Repository.inc");
										
										$txt_temp      ='';
										$hostname_temp = Host::ip2hostname($conn,$vulnerability_data->get_ip());
										
										if($hostname_temp!=$vulnerability_data->get_ip()){
											$txt_temp.=$hostname_temp.' - ';
										}
										
										if ($linkedocs = Repository::have_linked_documents($conn, $vulnerability_data->get_ip(), 'host')){
											$txt_temp.="<a href=\"javascript:;\" onclick=\"GB_edit('../repository/repository_list.php?keyname=" . urlencode($vulnerability_data->get_ip()) . "&type=host')\" class='blue' target='main'>[" . $linkedocs . "] "._('Knowledge DB')."</a>";
										}
										
										if($txt_temp!=''){
											$txt_temp=' ('.$txt_temp.')';
										}
										
										echo "<strong>IP:</strong> " . $vulnerability_data->get_ip() .$txt_temp."<br>";
										echo "<strong>Port:</strong> " . $vulnerability_data->get_port() . "<br/>";
										echo "<strong>Scanner ID:</strong> " . $nessus_id . "<br/>";
										echo "<strong>Risk:</strong> " . $vulnerability_data->get_risk() . "<br/>";
										echo "<strong>Description:</strong> " . Osvdb::sanity($vulnerability_data->get_description()) . "<br/>";
									}
								} 
								elseif ($ref == 'Custom')
								{
									$custom_list = $incident->get_custom($conn);
									echo "<table class='noborder' width='100%' id='custom_table'>";
										foreach($custom_list as $custom) 
										{
											echo "<tr>
													<td class='left noborder' align='middle'><strong>".$custom[0].":</strong></td>
													<td class='left'>".Incident::format_custom_field($custom[3],$id,$custom[1], $custom[2])."</td>
												  </tr>\n";
										}
									echo "</table>";
								}
								?>
								
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
		<!-- End incident data -->

		<td valign='top'><?php Incident::colorize_status($incident->get_status($conn)) ?></td>
			
		
		<td valign='top'><?php echo Incident::get_priority_in_html($priority) ?></td>
		

		<td valign="top">
			<?php
			
			$has_found_keys = 0;
			$max_rows       = 10; // Must have the same value in ../repository/index.php
			$keywords       = $incident->get_keywords_from_type($conn);
			
			if ($keywords != "") 
			{
				$keywords = preg_replace("/\s*,\s*/", " OR ", $keywords);
				list($aux_list, $has_found_keys) = Repository::get_list($conn, 0, 5, $keywords);
			}
			
			list($linked_list, $has_linked) = Repository::get_list_bylink($conn, 0, 999, $incident->get_id());
			$keywords_search = ($keywords != "") ? $keywords : $incident->get_title();
			
			if ($has_found_keys > 0) 
				$has_found = "<a href='../repository/?searchstr=" . urlencode($keywords) . "' style='text-decoration:underline'><strong>$has_found_keys</strong></a>";
			else
			{
				if ($has_linked) 
					$has_found = "<a href='../repository/?search_bylink=" . $incident->get_id() . "' style='text-decoration:underline'><strong>$has_linked</strong></a>";
			}
			
			$has_found = ( empty($has_found) ) ? 0 : $has_found;
			
			?>
			<table width="100%">
				<tr><th height="18"><?php echo _("Documents")?></th></tr>
				<?php
				$i = 0;
				if (count($linked_list) == 0) 
					echo "<tr><td height='25'>"._("No linked documents")."</td></tr>";
				
				foreach($linked_list as $document_object) 
				{
					$repository_pag = floor($i / $max_rows) + 1;
					?>
					<tr><td><a href="../repository/repository_document.php?id_document=<?php echo $document_object->id_document ?>" style="hover{border-bottom:0px}" class="greybox"><?php echo $document_object->title ?></a></td></tr>
					<?php
					$i++;
				} ?>
				<tr><th nowrap='nowrap' height="18"><?php echo _("Related documents")?> [ <?php echo $has_found ?> ]</th></tr>
				<tr>
					<th nowrap='nowrap' class='documents'>
						<img align='absmiddle' src="../repository/images/linked2.gif" border='0'/>
						<a href="../repository/addrepository.php?id=<?php echo $id?>&id_link=<?php echo $id?>&name_link=<?php echo urlencode($title) ?>&type_link=incident" class='greybox_2' title='<?php echo _("Link existing document")?>'><?php echo _("Link existing document")?></a>
					</th>
				</tr>
							
				<tr>
					<th nowrap='nowrap' class='documents'>
						<img align='absmiddle' src="../repository/images/editdocu.gif" border='0'/>
						<a href="../repository/index.php"><?php echo _("New document")?></a>
					</th>
				</tr>
			</table>
		</td>
	
		<td valign='top'>
			<table width="100%" class="noborder">
				<tr>
					<td style='white-space:nowrap;'>
						<?php
							if ( Incident::user_incident_perms($conn, $id, 'delincident') )
							{
								$edit_action = "<a href='newincident.php?action=edit&ref=$ref&incident_id=$id'>
									<img src='../vulnmeter/images/pencil.png' border='0' align='absmiddle' title='"._("Edit ticket")."'></a>";
									
								$delete_action = "<a onClick=\"delete_ticket('$id');\"><img src='../pixmaps/delete.gif' border='0' align='absmiddle' title='"._("Delete ticket")."'></a>";
							}
							else
							{
								$edit_action = "<span class='disabled'>
									<img src='../vulnmeter/images/pencil.png' border='0' align='absmiddle' title='"._("Edit ticket")."'></span>";
									
								$delete_action = "<span class='disabled'><img src='../pixmaps/delete.gif' border='0' align='absmiddle' title='"._("Delete ticket")."'></span>";
							}
						
							echo $edit_action; 
							echo $delete_action; 
						?>
						
							<a href='#anchor'><img src="../pixmaps/tables/table_row_insert.png" border="0" align="absmiddle" title="<?php echo _("New comment") ?>"></a>  
					</td>
				</tr>
			</table>
		</td>
	</tr>
  
	<tr>
	<form action="manageincident.php?action=subscrip&incident_id=<?php echo $id ?>" method="POST">
		<td nowrap='nowrap'><strong><?php echo _("Email changes to") ?>:</strong></td>
						
		<td class='email_changes'>
		<?php
		foreach($incident->get_subscribed_users($conn, $id) as $u) {
			echo format_user($u, true, true) . '<br/>';
		}
		?>
		</td>
		
		<td id='subscribe_section' nowrap='nowrap' colspan='4'>
					
				<select name="login">
				<?php 
				
				$current_user = Session:: get_session_user();
				$number_users = count($users);
								
				if( Session::am_i_admin() )
					$filtered_users = $users;
				else
				{
					foreach($users as $u) 
					{
						$login = $u->get_login();
						
						if ( !Session::is_admin($conn, $login) )
						{
							if ( $pro && !Acl::am_i_proadmin() && !Acl::is_proadmin($conn, $login) > 0 )
								$filtered_users[] = $u;
							elseif( $pro && Acl::am_i_proadmin() )
							{
								$filtered_users[] = $u;
							}
							
						}
					}
				}
								
				
				if ( $number_users == 0 ) 
				{ 
					?>
					<option value="">- <?php echo _("No users found")?> -</option>
					<?php 
				} 
				
				
				
				foreach($filtered_users as $u) 
				{ 
					?>
					<option value="<?php echo $u->get_login() ?>"><?php echo format_user($u, false) ?></option>
					<?php 
				} 
				?>
				</select>
						
			<input type="submit" class="button" name="subscribe" value="<?php echo _("Subscribe")?>"/>&nbsp;
			<input type="submit" class="button" name="unsubscribe" value="<?php echo _("Unsubscribe")?>"/>
		</td>
	</form>
	</tr>
</table>
<!-- end incident summary -->

<br/>
<!-- incident ticket list-->
<?php
$tickets_list = $incident->get_tickets($conn);

for ($i = 0; $i < count($tickets_list); $i++)
{
    $ticket        = $tickets_list[$i];
    $ticket_id     = $ticket->get_id();
    $date          = $ticket->get_date();
    $life_time     = Util::date_diff($date, $created);
    $creator       = $ticket->get_user();
    $in_charge     = $ticket->get_in_charge();
    $transferred   = $ticket->get_transferred();
    $creator       = Session::get_list($conn, "WHERE login='$creator'");
    $creator       = count($creator) == 1 ? $creator[0] : false;
	
	
    if (preg_match("/^\d+$/",$in_charge)) 
	{
        $querye = "SELECT ae.name as ename, aet.name as etype FROM acl_entities AS ae, acl_entities_types AS aet WHERE ae.type = aet.id AND ae.id=$in_charge";
        $resulte=$conn->execute($querye);
        list($entity_name, $entity_type) = $resulte->fields;
        $in_charge_name = $in_charge;
    }
	else 
	{
    	$in_charge = Session::get_list($conn, "WHERE login='$in_charge'");
    	$in_charge = count($in_charge) == 1 ? $in_charge[0] : false;
    	$in_charge_name = format_user($in_charge);
    }
		
    
    $transferred = Session::get_list($conn, "WHERE login='$transferred'");
    $transferred = count($transferred) == 1 ? $transferred[0] : false;
    $descrip     = $ticket->get_description();
    $action      = $ticket->get_action();
    $status      = $ticket->get_status();
    $prio        = $ticket->get_priority();
    $prio_str    = Incident::get_priority_string($prio);
    $prio_box    = Incident::get_priority_in_html($prio);
    
	if ($attach = $ticket->get_attachment($conn)) 
	{
        $file_id   = $attach->get_id();
        $file_name = $attach->get_name();
        $file_type = $attach->get_type();
    }
?>
	
    <table width="100%" cellspacing="2" align="center">
		<!-- ticket head -->
		<tr>
			<th width="78%" nowrap='nowrap'><strong><?php echo format_user($creator) ?></strong> - <?php echo $date ?></th>
			<td style="text-align:left; padding-left:3px;">
            <?php
			
			/* Check permissions to delete a ticket*/
			
			if ( ($i == count($tickets_list) - 1) && Incident_ticket::user_tickets_perms($conn, $ticket_id) )
			{
				?>
				<input type="button" name="deleteticket" class="lbutton" value="<?php echo _("Delete ticket") ?>"  onclick="delete_comment('<?php echo $ticket_id?>', '<?php echo $id?>')"/>
				<?php
			}
			
			?>
			&nbsp;
			</td>
		</tr>
		<!-- end ticket head -->
		
		<tr>
			<!-- ticket contents -->
			<td style="width: 600px" valign="top">
				<table style="border-width: 0px;" width="100%" cellspacing="0">
					<tr>
						<td style="text-align:left;">
							<?php
							if ( $attach ) 
							{ 
								?>
									<strong><?php echo _("Attachment") ?>: </strong>
									<a href="attachment.php?id=<?php echo $file_id ?>"><?php echo htm($file_name) ?></a>
									&nbsp;<i>(<?php echo $file_type ?>)</i><br/>
								<?php
							} 
							?>
							
							<strong><?php echo _("Description") ?></strong><p class="ticket_body"><?php echo htm($descrip) ?></p>
							<?php
							if ($action) { 
								?>
									<strong><?php echo _("Action") ?></strong><p class="ticket_body"><?php echo htm($action) ?></p>
								<?php
							} ?>
						</td>
					</tr>
				</table>
			</td>
			<!-- end ticket contents -->
       
			<!-- ticket summary -->
			<td style="border-top-width: 0px; width: 230px" valign="top">
				<table class="noborder">
					<tr>
						<th><strong><?php echo _("Status") ?>: </strong></th>
						<td nowrap='nowrap' style="text-align:left;padding-left:5px;"><?php Incident::colorize_status($status); ?></td>
					</tr>
					
					<tr valign="middle">
						<th><strong><?php echo _("Priority"); ?>: </strong></th>
						<td nowrap='nowrap' style="text-align:left;">
							<table class="noborder"><tr><td><?php echo $prio_box ?></td><td> - <?php echo $prio_str ?></td></table>
						</td>
					</tr>
					<?php
					if ( !$transferred ) 
					{ 
					?>
						<tr>
							<th><strong><?php echo _("In charge") ?>: </strong></th>
							<td nowrap='nowrap' style="text-align:left;padding-left:5px;"><?php echo $in_charge_name ?></td>
						</tr>
					<?php
					} 
					else 
					{ 
					?>
						<tr>
							<th><strong><?php echo _("Transferred To") ?>: </strong></th>
							<td nowrap='nowrap' style="text-align:left;padding-left:5px;"><?php echo format_user($transferred) ?></td>
						</tr>
					<?php
					} ?>
					
					<tr>
						<th nowrap='nowrap'><strong><?php echo _("Since Creation") ?>: </strong></th>
						<td nowrap='nowrap' style="text-align:left;padding-left:5px;"><?php echo $life_time ?></td>
					</tr>
				</table>
			</td>
		</tr>
        <!-- end ticket summary -->
	</table>
	<?php
} 
	?>
<!-- end incident ticket list-->
<br/>

<!-- form for new ticket -->
  
<form name="newticket" method="POST" action="manageincident.php?action=newticket&incident_id=<?php echo $id?>" enctype="multipart/form-data">
	<input type="hidden" name="prev_status" value="<?php echo $incident_status ?>"/>
	<input type="hidden" name="prev_prio" value="<?php echo $priority ?>"/>
	<input type="hidden" name="edit" value="<?php echo $edit ?>"/>

	<table align="center" width="100%" cellspacing="2">
		<tr>
			<td valign="top">
				<table style="text-align: left" id="anchor" align="left" width="1%" class="noborder">
					<tr>
						<th><?php echo _("Status") ?></th>
						<td style="text-align: left">
							<select name="status">
								<option value="Open" <?php if ($incident_status == 'Open') echo "selected='selected'" ?>><?php echo _("Open") ?></option>
								<option value="Closed" <?php if ($incident_status == 'Closed') echo "selected='selected'" ?>><?php echo _("Closed") ?></option>
							</select>
						</td>
					</tr>
					
					<tr>
						<th><?php echo _("Priority") ?></th>
						<td style="text-align: left">
							<select id='priority' name="priority">
								<?php
								for ($i = 1; $i <= 10; $i++) 
								{
									$selected = ( $priority == $i ) ? "selected='selected'" : "";
									echo "<option value='$i' $selected>$i</option>";
								}	
								?>
							</select>
							<img src='../pixmaps/arrow-000-small.png' align='absmiddle' title='<?php echo ("Arrow")?>'/>
							<select id='prio_str' name="prio_str">
								<option value="Low"><?php echo _("Low") ?></option>
								<option value="Medium"><?php echo _("Medium") ?></option>
								<option value="High"><?php echo _("High") ?></option>
							</select>
						 </td>
					</tr>
					
					<tr>
						<th><?php echo _("Transfer To")?></th>
						<td style="text-align: left">
							<table width="85%" cellspacing="0" cellpadding="0" class="transparent">
							
								<tr>
									<td class='format_user'><span><?php echo _("User:");?></span></td>
									<td class='left'>
										<select name="transferred_user" id="user" style="width:150px;" onchange="switch_user('user');return false;">
											<?php
											$num_users = 0;
																									
											foreach( $users as $k => $v )
											{
												$login = $v->get_login();
												if ( $login != $incident_in_charge)
												{
													$options .= "<option value='".$login."'>".format_user($v, false)."</option>\n";
													$num_users++;
												}
											}
											
											if ($num_users == 0)
												echo "<option value='' style='text-align:center !important;'>- "._("No users found")."- </option>";
											else
											{
												echo "<option value='' style='text-align:center !important;' selected='selected'>- "._("Select one user")." -</option>\n";
												echo $options;
											}
											?>
										</select>
									</td>
									<?php if ( !empty($entities) ) { ?>
									
									
									
									<td class="format_or"><?php echo _("OR");?></td>
									<td class='format_entity'><span style='margin-right: 3px;'><?php echo _("Entity:");?></span></td>
									<td class='nobborder'>
										<select name="transferred_entity" id="entity" onchange="switch_user('entity');return false;">
											<?php 
											unset($entities[$incident_in_charge]);
											
											if (count($entities) == 0)
												echo "<option value='' style='text-align:center !important;'>- "._("No entities found")." -</option>";
											else
												echo "<option value='' style='text-align:center !important;'>- "._("Select one entity")." -</option>\n";
											
											
											foreach ( $entities as $k => $v ) 
												echo "<option value='$k'>$v</option>";
											?>
										</select>
									</td>
									<?php } ?>
								</tr>
							</table>
						</td>
					</tr> 
					
					
					<tr>
						<th><?php echo _("Attachment") ?></th>
						<td style="text-align: left"><input type="file" name="attachment" size='31'/></td>
					</tr>
					
					<tr>
						<th ><?php echo _("Description") ?></th>
						<td style="border-width: 0px;">
						<textarea name="description" rows="5" cols="80" wrap='HARD'></textarea>
					</td></tr>
					
					<tr>
						<th><?php echo _("Action") ?></th>
						<td style="border-width: 0px;">
						<textarea name="action" rows="5" cols="80" wrap='HARD'></textarea>
					</td></tr>
					
					<tr>
						<td>&nbsp;</td>
						<td align="center" style="text-align: center">
						<?php 
							$stext = ( $edit == 1 ) ? _("Update ticket") : _("Add ticket");
							$sname = ( $edit == 1 ) ? _("update_ticket") : _("add_ticket");
							
						?>
						<input type="submit" class="button" name="<?php echo $sname ?>" value="<?php echo $stext ?>"/>
						</td>
					</tr>
					<tr><td style='height:10px;'>&nbsp;</td><tr/>
				</table>

			</td>
			
			<td valign="top">
				<table style="text-align: left">
					<tr><th><?php echo _("Tags") ?></th></tr>
					<?php
					foreach($incident_tag->get_list() as $t)
					{ 
						?>
						<tr>
							<td style="text-align: left" nowrap='nowrap'>
								<?php
								$checked = in_array($t['id'], $incident_tags) ? "checked='checked'" : '' ?>
								<input type="checkbox" name="tags[]" value="<?php echo $t['id'] ?>" <?php echo $checked ?>/>
								<label title="<?php echo $t['descr'] ?>"><?php echo $t['name'] ?></label><br/>
							</td>
						</tr>
						<?php
					} 
					?>
				</table>
			</td>
		</tr>
	</table>
</form>

</body>
</html>
