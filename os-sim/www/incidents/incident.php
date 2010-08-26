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
* - format_user()
* Classes list:
*/
require_once 'classes/Session.inc';
require_once 'classes/Security.inc';
Session::logcheck("MenuIncidents", "IncidentsIncidents");
require_once 'ossim_db.inc';
require_once 'classes/Incident.inc';
require_once 'classes/Incident_ticket.inc';
require_once 'classes/Incident_tag.inc';
require_once 'classes/Osvdb.inc';
require_once ("classes/Repository.inc");
$id = GET('id');
ossim_valid($id, OSS_ALPHA, 'illegal:' . _("Incident ID"));
if (ossim_error()) {
    die(ossim_error());
}
$db = new ossim_db();
$conn = $db->connect();
$incident_list = Incident::search($conn, array(
    'incident_id' => $id
));
if (count($incident_list) != 1) {
    die(_("Invalid ticket ID or insufficient permissions"));
}
$incident = $incident_list[0];

$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
  
  <script type="text/javascript" src="../js/jquery-1.3.1.js"></script>
  <script type="text/javascript" src="../js/greybox.js"></script>

  <script type="text/javascript">
  // GrayBox
	$(document).ready(function(){
		GB_TYPE = 'w';
		$("a.greybox").click(function(){
			var t = this.title || $(this).text() || this.href;
			GB_show(t,this.href,400,'60%');
			return false;
		});
	});
    function switch_user(select) {
        if(select=='entity' && $('#transferred_entity').val()!=''){
            $('#user').val('');
        }
        else if (select=='user' && $('#transferred_user').val()!=''){
            $('#entity').val('');
        }
    }
  </script>
  
<style>
td {
    border-width: 0px;
}
</style>
<? include ("../host_report_menu.php") ?>
</head>
<body>
<?php
include ("../hmenu.php"); ?>
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
<?php
function format_user($user, $html = true, $show_email = false) {
    if (is_a($user, 'Session')) {
        $login = $user->get_login();
        $name = $user->get_name();
        $depto = $user->get_department();
        $company = $user->get_company();
        $mail = $user->get_email();
    } elseif (is_array($user)) {
        $login = $user['login'];
        $name = $user['name'];
        $depto = $user['department'];
        $company = $user['company'];
        $mail = $user['email'];
    } else {
        return '';
    }
    $ret = $name;
    if ($depto && $company) $ret.= " / $depto / $company";
    if ($mail && $show_email) $ret = "$ret &lt;$mail&gt;";
    if ($login) $ret = "<label title=\"Login: $login\">$ret</label>";
    if ($mail) {
        $ret = '<a href="mailto:' . $mail . '">' . $ret . '</a>';
    } else {
        $ret = "$ret <font size=small color=red><i>(No email)</i></font>";
    }
    return $html ? $ret : strip_tags($ret);
}
$name = $incident->get_ticket();
$title = $incident->get_title();
$ref = $incident->get_ref();
$type = $incident->get_type();
$created = $incident->get_date();
$life = $incident->get_life_time();
$updated = $incident->get_last_modification();
$priority = $incident->get_priority();
$incident_status = $incident->get_status();
$incident_in_charge = $incident->get_in_charge();
$users = Session::get_list($conn);
$incident_tags = $incident->get_tags();
$incident_tag = new Incident_tag($conn);
$taga = array();
foreach($incident_tags as $tag_id) {
    $taga[] = $incident_tag->get_html_tag($tag_id);
}
$taghtm = count($taga) ? implode(' - ', $taga) : _("n/a");
?>

    <td height="100%"><b><table width="100%" height="100%"><tr height="100%"><td height="100%"><?php echo $name
?></b></td></tr></table></td>
    <td class="left" height="100%">
        <table width="100%" height="100%"><tr height="100%"><td height="100%">
        <tr><td>
            <table class="noborder" width="100%">
            <tr><td style="text-align:left;padding-left:10px;" bgcolor="#efefef">
            <?php echo _("Name") ?>: <b><?php echo $title ?> </b><br/>
            <?php echo _("Class") ?>: <?php echo $ref ?><br/>
            <?php echo _("Type") ?>: <?php echo $type ?><br/>
            <?php echo _("Created") ?>: <?php echo $created ?> (<?php echo $life ?>)<br/>
            <?php echo _("Last Update") ?>: <?php echo $updated ?><br/>
            <?php
if ($incident->get_status($conn) == "Closed") {
    echo _("Resolution time") . ": " . $incident->get_life_time() . "<br/>";
}
?>      </td></tr>
            <tr><td style="text-align:left;padding-left:10px;">
            <!--<hr/>-->
            <?php 
            if (!preg_match("/^\d+$/",$incident->get_in_charge_name($conn))) {
                $in_charge_name = $incident->get_in_charge_name($conn);
            }
            else {
                $querye = "SELECT ae.name as ename, aet.name as etype FROM acl_entities AS ae, acl_entities_types AS aet WHERE ae.type = aet.id AND ae.id=".$incident->get_in_charge_name($conn);
                $resulte=$conn->execute($querye);
                list($entity_name, $entity_type) = $resulte->fields;
                $in_charge_name = $entity_name." [".$entity_type."]";
            }
            ?>
            <?php echo _("In charge") ?>: <b style="color: darkblue"><?php echo $in_charge_name; ?></b><br/>
            <?php echo _("Submitter") ?>: <b><?php echo $incident->get_submitter() ?></b>
            </td></tr>
            <tr><td style="text-align:left;padding-left:10px;" bgcolor="#efefef">
            <!--<hr/>-->
            <?php echo _("Extra") ?>: <?php echo $taghtm ?><br/>
            <!--<hr/>-->
            </td></tr>
            <tr><td style="text-align:left;padding-left:10px;">
    <?php
if ($ref == 'Alarm' or $ref == 'Event') {
    if ($ref == 'Alarm') {
        $alarm_list = $incident->get_alarms($conn);
    } else {
        $alarm_list = $incident->get_events($conn);
    }
    foreach($alarm_list as $alarm_data) {
        echo "Source Ips: <a href='../report/host_report.php?host=".$alarm_data->get_src_ips()."' class='HostReportMenu' id='".$alarm_data->get_src_ips().";".$alarm_data->get_src_ips()."'><b>" . $alarm_data->get_src_ips() . "</b></a> - " . "Source Ports: <b>" . $alarm_data->get_src_ports() . "</b><br/>" . "Dest Ips: <a href='../report/host_report.php?host=".$alarm_data->get_dst_ips()."' class='HostReportMenu' id='".$alarm_data->get_dst_ips().";".$alarm_data->get_dst_ips()."'><b>" . $alarm_data->get_dst_ips() . "</b></a> - " . "Dest Ports: <b>" . $alarm_data->get_dst_ports() . "</b>";
    }
} elseif ($ref == 'Metric') {
    $metric_list = $incident->get_metrics($conn);
    foreach($metric_list as $metric_data) {
        echo "Target: <b>" . $metric_data->get_target() . "</b> - " . "Metric Type: <b>" . $metric_data->get_metric_type() . "</b> - " . "Metric Value: <b>" . $metric_data->get_metric_value() . "</b>";
    }
} elseif ($ref == 'Anomaly') {
    $anom_list = $incident->get_anomalies($conn);
    foreach($anom_list as $anom_data) {
        $anom_type = $anom_data->get_anom_type();
        $anom_ip = $anom_data->get_ip();
        $anom_info_o = $anom_data->get_data_orig();
        $anom_info = $anom_data->get_data_new();
        if ($anom_type == 'mac') {
            list($a_sen, $a_date_o, $a_mac_o, $a_vend_o) = explode(",", $anom_info_o);
            list($a_sen, $a_date, $a_mac, $a_vend) = explode(",", $anom_info);
            echo "Host: <b>" . $anom_ip . "</b><br>" . "Previous Mac: <b>" . $a_mac_o . "(" . $a_vend_o . ")</b><br>" . "New Mac: <b>" . $a_mac . "(" . $a_vend . ")</b><br>";
        } elseif ($anom_type == 'service') {
            list($a_sen, $a_date, $a_port, $a_prot_o, $a_ver_o) = explode(",", $anom_info_o);
            list($a_sen, $a_date, $a_port, $a_prot, $a_ver) = explode(",", $anom_info);
            echo "Host: <b>" . $anom_ip . "</b><br>" . "Port: <b>" . $a_port . "</b><br>" . "Previous Protocol [Version]: <b>" . $a_prot_o . " [" . $a_ver_o . "]</b><br>" . "New Protocol [Version]: <b>" . $a_prot . " [" . $a_ver . "]</b><br>";
        } elseif ($anom_type == 'os') {
            list($a_sen, $a_date, $a_os_o) = explode(",", $anom_info_o);
            list($a_sen, $a_date, $a_os) = explode(",", $anom_info);
            echo "Host: <b>" . $anom_ip . "</b><br>" . "Previous OS: <b>" . $a_os_o . "</b><br>" . "New OS: <b>" . $a_os . "</b><br>";
        }
    }
} elseif ($ref == 'Vulnerability') {
    $vulnerability_list = $incident->get_vulnerabilities($conn);
    foreach($vulnerability_list as $vulnerability_data) {
        // Osvdb starting
        $nessus_id = $vulnerability_data->get_nessus_id();
        $osvdb_id = Osvdb::get_osvdbid_by_nessusid($conn, $nessus_id);
        if ($osvdb_id) $nessus_id = "<a href=\"osvdb.php?id=" . $osvdb_id . "\">" . $nessus_id . "</a>";
        // Osvdb end
        echo "<b>IP:</b> " . $vulnerability_data->get_ip() . "<br> " . "<b>Port:</b> " . $vulnerability_data->get_port() . "<br> " . "<b>Scanner ID:</b> " . $nessus_id . "<br>" . "<b>Risk:</b> " . $vulnerability_data->get_risk() . "<br>" . "<b>Description:</b> " . Osvdb::sanity(nl2br($vulnerability_data->get_description())) . "<br>";
    }
}
?>
        </td></tr>
        </table>
    </td></tr></table>
    </td>
    <!-- end incident data -->

    <td height="100%"><table width="100%" height="100%"><tr height="100%"><td height="100%"><?php
Incident::colorize_status($incident->get_status($conn)) ?></td></tr></table></td>
    <td height="100%"><table width="100%" height="100%"><tr height="100%"><td height="100%"><?php echo Incident::get_priority_in_html($priority) ?>
    </td></tr></table></td>

	<td valign="top">
	<?php
if ($_GET['id_incident'] != "") { ?>
		<IFRAME height="200" id="rep_iframe" src="../repository/addrepository.php?id=<?php echo $id
?>&id_link=<?php echo $_GET['id_incident'] ?>&name_link=<?php echo $_GET['name_incident'] ?>&type_link=incident" frameborder="0"></IFRAME>
	<?php
} else { ?>
	<?php
    $has_found_keys = 0;
    $max_rows = 10; // Must have the same value in ../repository/index.php
    $keywords = $incident->get_keywords_from_type($conn);
    if ($keywords != "") {
        $keywords = preg_replace("/\s*,\s*/", " OR ", $keywords);
        list($aux_list, $has_found_keys) = Repository::get_list($conn, 0, 5, $keywords);
    }
    list($linked_list, $has_linked) = Repository::get_list_bylink($conn, 0, 999, $incident->get_id());
    $keywords_search = ($keywords != "") ? $keywords : $incident->get_title();
    if ($has_found_keys > 0) $has_found_keys = "<a href='../repository/?searchstr=" . urlencode($keywords) . "' style='text-decoration:underline'><b>$has_found_keys</b></a>";
    if ($has_linked) $has_linked = "<a href='../repository/?search_bylink=" . $incident->get_id() . "' style='text-decoration:underline'><b>$has_linked</b></a>";
?>
		<table width="100%">
			<tr><th height="18"><?=_("Documents")?></th></tr>
			<?php
    $i = 0;
    if (count($linked_list) == 0) echo "<tr><td height=\"25\">"._("No linked documents")."</td></tr>";
    foreach($linked_list as $document_object) {
        $repository_pag = floor($i / $max_rows) + 1;
?>
				<tr><td><a href="../repository/repository_document.php?id_document=<?php echo $document_object->id_document ?>" style="hover{border-bottom:0px}" class="greybox"><?php echo $document_object->title ?></a></td></tr>
			<?php
        $i++;
    } ?>
			<tr><th nowrap height="18"><?=_("Related documents")?> [ <?php echo $has_found_keys ?> ]</th></tr>
			<tr><th nowrap style="padding:0px 3px 0px 3px" height="18"><img align='absmiddle' src="../repository/images/linked2.gif" border=0><a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>?id=<?php echo $id ?>&id_incident=<?php echo $incident->get_id() ?>&name_incident=<?php echo $incident->get_title() ?>"><?=_("Link existing document")?></a></th></tr>
			<tr><th nowrap style="padding:0px 3px 0px 3px" height="18"><img align='absmiddle' src="../repository/images/editdocu.gif" border=0><a href="../repository/index.php"><?=_("New document")?></a></th></tr>
		</table>
	<?php
} ?>
	</td>
	
    <td>
        <table width="100%" height="100%"><tr height="100%"><td height="100%">
            <form action="#" method="get">
            <input type="button" name="submit_edit" class="btn" value="<?php echo _("Edit comment") ?>"
                   style="width: 10em;"
                   onClick="document.location = 'newincident.php?action=edit&ref=<?php echo $ref ?>&incident_id=<?php echo $id ?>';"
                   /><br/>
              
            <input type="button" name="submit_delete" class="btn" value="<?php echo _("Delete comment") ?>"
                   style="width: 10em; color: red;"
                   onClick="c = confirm('<?php echo _("This action will erase the Ticket as well as all the comments on this ticket. Do you want to continue?") ?>'); if (c) document.location = 'manageincident.php?action=delincident&incident_id=<?php echo $id ?>';"
                   /><br/>

            <input type="button" name="add_ticket" class="btn" value="<?php echo _("New comment") ?>"
                   style="width: 10em;" onclick="document.location = '#anchor';"/>
     
            </form>
            </td></tr>
        </table>
    </td>
  </tr>
  <tr>
    <td width="20" colspan="6" style="text-align: left;" nowrap>
            <form action="manageincident.php?action=subscrip&incident_id=<?php echo $id ?>" method="POST">
                <table width="100%" style="border-width: 0px;">
                <tr><td><b><?php echo _("Email changes to") ?>:</b></td>
                <td width="45%" style="text-align: left;">
                <?php
foreach($incident->get_subscribed_users($conn, $id) as $u) {
            echo format_user($u, true, true) . '<br/>';
}
?>
                </td><td style="text-align: right;" NOWRAP>
                  <select name="login">
                    <?php if (count($users) < 1) { ?>
                    <option value="">- <?=_("No users found")?> -</option>
                    <?php } else { ?> 
                    <option value=""></option>
                    <?php } ?>
                    <?php
foreach($users as $u) { ?>
                        <option value="<?php echo $u->get_login() ?>"><?php echo format_user($u, false) ?></option>
                    <?php
} ?>
                  </select>
                  <input type="submit" class="btn" name="subscribe" value="<?=_("Subscribe")?>">&nbsp;
                  <input type="submit" class="btn" name="unsubscribe" value="<?=_("Unsubscribe")?>">
                </td></tr></table>
            </form>
    </td>
  </tr>
</table>

<!-- end incident summary -->

<br>
<!-- incident ticket list-->
<?php
$tickets_list = $incident->get_tickets($conn);
for ($i = 0; $i < count($tickets_list); $i++) {
    $ticket = $tickets_list[$i];
    $ticket_id = $ticket->get_id();
    $date = $ticket->get_date();
    $life_time = Util::date_diff($date, $created);
    // Resolve users
    // XXX improve performance
    $creator = $ticket->get_user();
    $in_charge = $ticket->get_in_charge();
    $transferred = $ticket->get_transferred();
    $creator = Session::get_list($conn, "WHERE login='$creator'");
    $creator = count($creator) == 1 ? $creator[0] : false;
    $in_charge = Session::get_list($conn, "WHERE login='$in_charge'");
    $in_charge = count($in_charge) == 1 ? $in_charge[0] : false;
    $transferred = Session::get_list($conn, "WHERE login='$transferred'");
    $transferred = count($transferred) == 1 ? $transferred[0] : false;
    $descrip = $ticket->get_description();
    $action = $ticket->get_action();
    $status = $ticket->get_status();
    $prio = $ticket->get_priority();
    $prio_str = Incident::get_priority_string($prio);
    $prio_box = Incident::get_priority_in_html($prio);
    if ($attach = $ticket->get_attachment($conn)) {
        $file_id = $attach->get_id();
        $file_name = $attach->get_name();
        $file_type = $attach->get_type();
    }
?>
    <table width="100%" cellspacing="2" align="center">
    <!-- ticket head -->
    <tr><th width="78%" nowrap>
        <b><?php echo format_user($creator) ?></b> - <?php echo $date ?>
        </th>
        <!--<td style="background: #ABB7C7;">-->
        <td style="background: #FFFFFF;text-align:left;padding-left:3px;">
            <?php
    //
    // Allow the ticket creator and the admin delete the last ticket
    //
    if (($i == count($tickets_list) - 1) && (Session::am_i_admin() || $creator == Session::get_session_user())) {
?>
            <input type="button" name="deleteticket" class="btn"
                   value="<?php echo _("Delete ticket") ?>"
                   onclick="javascript: document.location = 'manageincident.php?action=delticket&ticket_id=<?php echo $ticket_id ?>&incident_id=<?php echo $id ?>'"
            >
            <?php
    } ?>
        &nbsp;
        </td>
    </tr>
    <!-- end ticket head -->
    <tr>
        <!-- ticket contents -->
        <td style="width: 600px" valign="top">
            <table style="border-width: 0px;" width="100%" cellspacing="0"><tr><td style="text-align:left;">
                <?php
    if ($attach) { ?>
                    <b><?php echo _("Attachment") ?>: </b>
                    <a href="attachment.php?id=<?php echo $file_id ?>"><?php echo htm($file_name) ?></a>
                    &nbsp;<i>(<?php echo $file_type ?>)</i><br/>
                <?php
    } ?>
                <b><?php echo _("Description") ?></b><p class="ticket_body"><?php echo htm($descrip) ?></p>
                <?php
    if ($action) { ?>
                    <b><?php echo _("Action") ?></b><p class="ticket_body"><?php echo htm($action) ?></p>
                <?php
    } ?>
            </td></tr></table>
        </td>
        <!-- end ticket contents -->
        <!-- ticket summary -->
        <td style="border-top-width: 0px; width: 230px" valign="top">
<!--            <table width="100%" height="100%" cellspacing="0" style="background-color:#ffffff"><tr height="100%"><td height="100%" style="text-align:left;padding-left:5px;">-->
                <table class="noborder">
                <tr><th>            
                    <b><?php echo _("Status") ?>: </b></th><td nowrap style="text-align:left;padding-left:5px;"><?php
                    Incident::colorize_status($status) ?>
                </td></tr>
                <tr valign="middle">
                    <th><b><?php echo _("Priority"); ?>: </b></th>
                    <td nowrap style="text-align:left;">
                        <table class="noborder"><tr><td><?php echo $prio_box ?></td><td> - <?php echo $prio_str ?></td></table>
                    </td>
                </tr>
                <?php
        if (!$transferred) { ?>
                <tr><th>
                    <b><?php echo _("In charge") ?>: </b></th><td nowrap style="text-align:left;padding-left:5px;"><?php echo format_user($in_charge) ?>
                </td></tr>
                <?php
        } else { ?>
                <tr><th>
                    <b><?php echo _("Transferred To") ?>: </b></th><td nowrap style="text-align:left;padding-left:5px;"><?php echo format_user($transferred) ?>
                </td></tr>
                <?php
        } ?>
                <tr><th NOWRAP>
                    <b><?php echo _("Since Creation") ?>: </b></th><td nowrap style="text-align:left;padding-left:5px;"><?php echo $life_time ?>
                </td></tr>
                </table>
<!--            </td></tr></table>-->
        </td>
        <!-- end ticket summary -->
    </table>
<?php
} ?>
<!-- end incident ticket list-->
<br>

<!-- form for new ticket -->
<script language="JavaScript" type="text/javascript">
    function chg_prio_str()
    {
        prio_num = document.newticket.priority;
        index = prio_num.selectedIndex;
        prio = prio_num.options[index].value;
        if (prio > 7) {
            document.newticket.prio_str.selectedIndex = 2;
        } else if (prio > 4) {
            document.newticket.prio_str.selectedIndex = 1;
        } else {
            document.newticket.prio_str.selectedIndex = 0;
        }
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
        
</script>
  
<form name="newticket" method="POST"
      action="manageincident.php?action=newticket&incident_id=<?php echo $id
?>"
      ENCTYPE="multipart/form-data">
      <input type="hidden" name="prev_status" value="<?php echo $incident_status ?>"></input>
      <input type="hidden" name="prev_prio" value="<?php echo $priority ?>"></input>
<!--<table align="center" width="100%" style="border-width: 0px" cellspacing="5">-->
<table align="center" width="100%" cellspacing="2">
<tr><td valign="top">

    <table style="text-align: left" id="anchor" align="left" width="1%" class="noborder">
    <tr>
        <th><?php echo _("Status") ?></th>
        <td style="text-align: left">
          <select name="status">
            <option value="Open" <?php
if ($incident_status == 'Open') echo 'SELECTED' ?>><?php echo _("Open") ?></option>
            <option value="Closed" <?php
if ($incident_status == 'Closed') echo 'SELECTED' ?>><?php echo _("Closed") ?></option>
          </select>
        </td>
    </tr>
    <tr>
        <th><?php echo _("Priority") ?></th>
        <td style="text-align: left">
          <select name="priority" onChange="chg_prio_str();">
            <?php
for ($i = 1; $i <= 10; $i++) { ?>
                <?php
    $selected = $priority == $i ? 'SELECTED' : ''; ?>
                <option value="<?php echo $i
?>" <?php echo $selected ?>><?php echo $i ?></option>
            <?php
} ?>
          </select>
          -&gt;
          <select name="prio_str" onChange="chg_prio_num();">
            <option value="Low"><?php echo _("Low") ?></option>
            <option value="Medium"><?php echo _("Medium") ?></option>
            <option value="High"><?php echo _("High") ?></option>
         </td>
    </tr>
    
    <?if(preg_match("/pro/i",$version)) {
        $users_pro_login = array();
        $users_pro = array();
        $entities_pro = array();
        
        if(Session::am_i_admin()) { // admin in professional version
            list($entities_all,$num_entities) = Acl::get_entities($conn);
            $entities_types_aux = Acl::get_entities_types($conn);
            $entities_types = array();

            foreach ($entities_types_aux as $etype) { 
                $entities_types[$etype['id']] = $etype;
            }
            
            ?>
            <tr>
                <th><?php echo _("Transfer To") ?></th>
                <td style="text-align: left">
                    <table width="85%" cellspacing="0" cellpadding="0" class="transparent">
                        <tr>
                            <td><?php echo _("User:");?></td>
                            <td>
                              <select name="transferred_user" id="user" style="width:140px;" onchange="switch_user('user');return false;">
                                <option value=""><? if (count($users) < 1) { ?>- <?=_("No users found")?> -<? } ?></option>
                                <?php
                                foreach($users as $u) { ?>
                                <?php
                                    if ($u->get_login() == $incident_in_charge) continue; // Don't add current in charge
                                     ?>
                                                <option value="<?php echo $u->get_login() ?>"><?php echo format_user($u, false) ?></option>
                                            <?php
                                } ?>
                              </select>
                            </td>
                            <td style="padding:0px 5px 0px 5px;text-align:center;"><?php echo _("OR");?></td>
                            <td><?php echo _("Entity:");?></td>
                            <td>
                                <select name="transferred_entity" id="entity" style="width:140px;" onchange="switch_user('entity');return false;">
                                <option value=""><? if (count($entities_all) < 1) { ?>- <?=_("No entities found")?> -<? } ?></option>
                                <?php
                                    foreach ( $entities_all as $entity ) if($entity["id"]!=$incident_in_charge) {
                                    ?>
                                    <option value="<?php echo $entity["id"]; ?>"><?php echo $entity["name"]." [".$entities_types[$entity["type"]]["name"]."]";?></option>
                                    <?php } ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </td>
                <script>chg_prio_str();</script>
            </tr> 
    <?}
        elseif(Acl::am_i_proadmin()) { // pro admin
            //users
            $users_admin = Acl::get_my_users($conn,Session::get_session_user()); 
            foreach ($users_admin as $u){
                if($u["login"]!=Session::get_session_user()){
                    $users_pro_login[] = $u["login"];
                }
            }
            if(!in_array(Session::get_session_user(), $users_pro_login) && $incident_in_charge!=Session::get_session_user())   $users_pro_login[] = Session::get_session_user();
            
            //entities
            list($entities_all,$num_entities) = Acl::get_entities($conn);
            list($entities_admin,$num) = Acl::get_entities_admin($conn,Session::get_session_user());
            $entities_list = array_keys($entities_admin);
            
            $entities_types_aux = Acl::get_entities_types($conn);
            $entities_types = array();

            foreach ($entities_types_aux as $etype) { 
                $entities_types[$etype['id']] = $etype;
            }
            
            //save entities for proadmin
            foreach ( $entities_all as $entity ) if(in_array($entity["id"], $entities_list)) {
                $entities_pro[$entity["id"]] = $entity["name"]." [".$entities_types[$entity["type"]]["name"]."]";
            }
            
            // filter users
            foreach($users as $u) {
                if (($u->get_login() == $incident_in_charge) || !in_array($u->get_login(),$users_pro_login)) continue;
                $users_pro[$u->get_login()] = format_user($u, false);
            }
            ?>
            <tr>
                <th><?php echo _("Transfer To") ?></th>
                <td style="text-align: left"> 
                    <table width="85%" cellspacing="0" cellpadding="0" class="transparent">
                        <tr>
                            <td><?php echo _("User:");?></td>
                            <td>
                              <select name="transferred_user" id="user" style="width:140px;" onchange="switch_user('user');return false;">
                                <option value=""><? if (count($users_pro) < 1) { ?>- <?=_("No users found")?> -<? } ?></option>
                                <?php
                                foreach($users_pro as $loginu => $nameu) { ?>
                                    <option value="<?php echo $loginu; ?>"><?php echo $nameu; ?></option>
                                <?php
                                } ?>
                              </select>
                            </td>
                            <td style="padding:0px 5px 0px 5px;text-align:center;"><?php echo _("OR");?></td>
                            <td><?php echo _("Entity:");?></td>
                            <td>
                                <select name="transferred_entity" style="width:140px;" id="entity" onchange="switch_user('entity');return false;">
                                <option value=""><? if (count($entities_pro) < 1) { ?>- <?=_("No entities found")?> -<? } ?></option>
                                <?php
                                    foreach ( $entities_pro as $entity_id => $entity_name ) if($entity_id!=$incident_in_charge) {
                                    ?>
                                    <option value="<?php echo $entity_id; ?>"><?php echo $entity_name;?></option>
                                    <?php } ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </td>
                <script>chg_prio_str();</script>
            </tr> 
        <?
        }
        elseif(!preg_match("/^\d+$/",$incident_in_charge) && Session::get_session_user()==$incident_in_charge) { // normal user and incident_in_charge isn't entity
                $brothers = Acl::get_brothers($conn,Session::get_session_user());
                foreach ($brothers as $brother){
                    $users_pro_login[] = $brother["login"]; 
                }
                if(!in_array(Session::get_session_user(), $users_pro_login) && $incident_in_charge!=Session::get_session_user())   $users_pro_login[] = Session::get_session_user();
                // filter users
                    foreach($users as $u) {
                        if (($u->get_login() == $incident_in_charge) || !in_array($u->get_login(),$users_pro_login)) continue;
                        $users_pro[$u->get_login()] = format_user($u, false); 
                    }
                ?>
                    <tr>
                        <th><?php echo _("Transfer To") ?></th>
                        <td style="text-align: left">
                          <select name="transferred_user" style="width:140px;">
                            <option value=""><? if (count($users_pro) < 1) { ?>- <?=_("No users found")?> -<? } ?></option>
                            <?php
                foreach($users_pro as $loginu => $nameu) { ?>
                        <option value="<?php echo $loginu ?>"><?php echo $nameu ?></option>
                <?php
                } ?>
                          </select>
                        </td>
                        <script>chg_prio_str();</script>
                    </tr>
                <?
        }
    }
    else {
        ?>
        <tr>
            <th><?php echo _("Transfer To") ?></th>
            <td style="text-align: left">
              <select name="transferred_user" style="width:140px;">
                <option value=""><? if (count($users) < 1 || (count($users) == 1 && $users[0]->get_login() == $incident_in_charge)) { ?>- <?=_("No users found")?> -<? } ?></option>
                <?php
    foreach($users as $u) { ?>
                    <?php
        if ($u->get_login() == $incident_in_charge) continue; // Don't add current in charge
         ?>
                    <option value="<?php echo $u->get_login() ?>"><?php echo format_user($u, false) ?></option>
                <?php
    } ?>
              </select>
            </td>
            <script>chg_prio_str();</script>
        </tr> 
    <?}?>
    <tr>
        <th><?php echo _("Attachment") ?></th>
        <td style="text-align: left"><input type="file" name="attachment" /></td>
    </tr>
    <tr>
        <th ><?php echo _("Description") ?></th>
        <td style="border-width: 0px;">
        <textarea name="description" rows="10" cols="80" WRAP=HARD></textarea>
    </td></tr>
    <tr>
        <th><?php echo _("Action") ?></th>
        <td style="border-width: 0px;">
        <textarea name="action" rows="10" cols="80" WRAP=HARD></textarea>
    </td></tr>
    <tr>
        <td>&nbsp;</td>
        <td align="center" style="text-align: center">
        <input type="submit" class="btn" name="add_ticket" value="<?php echo _("Add ticket") ?>"/>
    </td></tr>
    </table>

</td>
<td valign="top">
<table style="text-align: left">
    <tr><th><?php echo _("Tags") ?></th></tr>
    <?php
foreach($incident_tag->get_list() as $t) { ?>
    <tr>
        <td style="text-align: left" NOWRAP>
            <?php
    $checked = in_array($t['id'], $incident_tags) ? 'checked' : '' ?>
            <input type="checkbox" name="tags[]" value="<?php echo $t['id'] ?>" <?php echo $checked ?>>
            <label title="<?php echo $t['descr'] ?>"><?php echo $t['name'] ?></label><br/>
        </td>
    </tr>
    <?php
} ?>
</table>
</td>
</tr>
</table>
</form>
</body></html>
