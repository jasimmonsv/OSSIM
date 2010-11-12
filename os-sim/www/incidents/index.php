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
* - order_img()
* Classes list:
*/
require_once 'classes/Security.inc';
require_once 'classes/Session.inc';
require_once 'ossim_db.inc';
require_once 'classes/Incident.inc';
require_once 'classes/Incident_tag.inc';
require_once 'classes/Incident_ticket.inc';
require_once 'classes/Incident_type.inc';
Session::logcheck("MenuIncidents", "IncidentsIncidents");
function order_img($subject) {
    global $order_by, $order_mode;
    if ($order_by != $subject) return '';
    $img = $order_mode == 'DESC' ? 'abajo.gif' : 'arriba.gif';
    return '&nbsp;<img src="../pixmaps/top/' . $img . '" border=0>';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>  
  <script>
  function checkall() {
    if ($('input[name=ticket0]').attr('checked')) 
        $('input[type=checkbox]').attr('checked',true);
    else
        $('input[type=checkbox]').attr('checked',false);
  }
  </script>
</head>
<body>

<?php
include ("../hmenu.php"); ?>

<?php
$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);
$vars = array(
    'order_by' => OSS_LETTER . OSS_SCORE,
    'order_mode' => OSS_LETTER,
    'ref' => OSS_LETTER,
    'type' => OSS_ALPHA . OSS_SPACE,
    'title' => OSS_ALPHA . OSS_SCORE . OSS_PUNC,
    'related_to_user' => OSS_LETTER,
    'with_text' => OSS_ALPHA . OSS_PUNC . OSS_SCORE . OSS_SPACE,
    'action' => OSS_ALPHA . OSS_PUNC . OSS_SCORE . OSS_SPACE,
    'attachment' => OSS_ALPHA . OSS_SPACE . OSS_PUNC,
    'advanced_search' => OSS_DIGIT,
    'priority' => OSS_LETTER,
    'submitter' => OSS_ALPHA . OSS_SCORE . OSS_PUNC . OSS_SPACE,
    'in_charge' => OSS_ALPHA . OSS_SCORE . OSS_PUNC . OSS_SPACE,
    'status' => OSS_LETTER,
    'tag' => OSS_DIGIT,
    'page' => OSS_DIGIT,
    'close' => OSS_ALPHA . OSS_SPACE
);
foreach($vars as $var => $validate) {
    $$var = GET("$var");
    if (!ossim_valid($$var, array(
        $validate,
        OSS_NULLABLE
    ))) {
        echo "Var '$var' not valid<br>";
        die(ossim_error());
    }
}
if (!$order_by) {
    $order_by = 'life_time';
    $order_mode = 'DESC';
}
if ($page=="" || $page<=0) $page=1;
// First time we visit this page, show by default only Open incidents
// when GET() returns NULL, means that the param is not set
if (GET('status') === null) $status = 'Open';
$db = new ossim_db();
$conn = $db->connect();
// Close selected tickets

if (GET('close')==_("Close selected")) {
    foreach ($_GET as $k => $v) {
        if (preg_match("/^ticket\d+/",$k) && $v!="") {
        $idprio = explode("_",$v);
        if (is_numeric($idprio[0]) && is_numeric($idprio[1]))
          Incident_ticket::insert($conn, $idprio[0], "Closed", $idprio[1], Session::get_session_user(), " ", "", "", array(), null);
        }
    }
}
$criteria = array(
    'ref' => $ref,
    'type' => $type,
    'title' => $title,
    'submitter' => $submitter,
    'in_charge' => $in_charge,
    'with_text' => $with_text,
    'status' => $status,
    'priority_str' => $priority,
    'attach_name' => $attachment,
    'related_to_user' => $related_to_user,
    'tag' => $tag
);
$incident_tag = new Incident_tag($conn);
?>

  <!-- filter -->
  <form name="filter" id="filter" method="GET" action="<?php echo $_SERVER["SCRIPT_NAME"] ?>">
  <input type="hidden" name="page" id="page" value="">
    <?php
if ($advanced_search) { ?>
        <input type="hidden" name="advanced_search" 
               value="1">
    <?php
} ?>
  <table align="center" width="100%">
    <tr>
      <th colspan="7">
<?php
echo _("Filter");
$change_to = _("change to ");
if ($advanced_search) {
    $label = _("Advanced");
    $change_to.= ' ' . _("Simple");
    echo " $label [<a href=\"" . $_SERVER["SCRIPT_NAME"] . "\"
                title=\"$change_to $\">$change_to</a>]";
} else {
    $label = _("Simple");
    $change_to.= ' ' . _("Advanced");
    echo " $label [<a href=\"" . $_SERVER["SCRIPT_NAME"] . "?advanced_search=1\"
                title=\"$change_to $\">$change_to</a>]";
}
?>
      </th>
    </tr>
    <tr><td colspan="7" <?= ($advanced_search) ? "" : " style='border-width: 0px;'" ?>>
      <table width="100%" align="center" style="border-width: 0px;">
          <td class="noborder"> <?php
echo gettext("Class"); /* ref */ ?> </td>
          <td class="noborder"> <?php
echo gettext("Type"); /* type */ ?> </td>
          <td class="noborder"> <?php
echo gettext("Search text in all fields"); ?> </td>
          <td class="noborder"> <?php
echo gettext("In charge"); ?> </td>
          <td class="noborder"> <?php
echo gettext("Status"); ?> </td>
          <td class="noborder"> <?php
echo gettext("Priority"); ?> </td>
          <td class="noborder"> <?php
echo gettext("Actions"); ?> </td>
        </tr>
        <tr>
          <td style="border-width: 0px;">
            <select name="ref" onChange="document.forms['filter'].submit()">
              <option value="">
                <?php
echo gettext("ALL"); ?>
              </option>
              <option <?php
if ($ref == "Alarm") echo "selected" ?> value="Alarm">
    	        <?php
echo gettext("Alarm"); ?>
              </option>
              <option <?php
if ($ref == "Event") echo "selected" ?> value="Event">
    	        <?php
echo gettext("Event"); ?>
              </option>
              <option <?php
if ($ref == "Metric") echo "selected" ?> value="Metric">
    	        <?php
echo gettext("Metric"); ?>
              </option>
              <option <?php
if ($ref == "Anomaly") echo "selected" ?> value="Anomaly">
    	        <?php
echo gettext("Anomaly"); ?>
              </option>
              <option <?php
if ($ref == "Vulnerability") echo "selected" ?> value="Vulnerability">
                <?php
echo gettext("Vulnerability"); ?>
              </option>
            </select>
          </td>
          <td style="border-width: 0px;">
            <select name="type" onChange="document.forms['filter'].submit()">
              <option value="" <?php
if (!$type) echo "selected" ?>>
                <?php
echo gettext("ALL"); ?>
              </option>
              <?php
$customs = array();
foreach(Incident_type::get_list($conn) as $itype) {
    $id = $itype->get_id();
    if (preg_match("/custom/",$itype->get_keywords())) {
    	$customs[] = $itype->get_id();
    }
?>
                  <option <?php
    if ($type == $id) echo "selected" ?> value="<?php echo $id ?>">
                    <?php echo $id
?>
                  </option>
              <?php
} ?>
            </select>
          </td>
          <td style="border-width: 0px;">
            <input type="text" name="with_text" value="<?php echo $with_text
?>" /></td>
          <td style="border-width: 0px;">
            <input type="text" name="in_charge" value="<?php echo $in_charge
?>" /></td>
          <td style="border-width: 0px;">
            <select name="status" onChange="document.forms['filter'].submit()">
              <option value="">
                <?php
echo gettext("ALL"); ?>
              </option>
              <option <?php
if ($status == "Open") echo "selected" ?>
                value="Open">
    	        <?php
echo gettext("Open"); ?>
              </option>
              <option <?php
if ($status == "Closed") echo "selected" ?> value="Closed">
    	        <?php
echo gettext("Closed"); ?>
              </option>
            </select>
          </td>
          <td style="border-width: 0px;">
            <select name="priority" onChange="document.forms['filter'].submit()">
              <option value="">
    	        <?php
echo gettext("ALL"); ?>
              </option>
              <option <?php
if ($priority == "High") echo "selected" ?> value="High">
    	        <?php
echo gettext("High"); ?>
              </option>
              <option <?php
if ($priority == "Medium") echo "selected" ?> value="Medium">
    	        <?php
echo gettext("Medium"); ?>
              </option>
              <option <?php
if ($priority == "Low") echo "selected" ?> value="Low">
    	        <?php
echo gettext("Low"); ?>
              </option>
            </select>
          </td>
          <td nowrap style="border-width: 0px;">
            <input type="submit" name="filter" value="<?=_("Search")?>" class="button" style="font-size:12px"/>
            <input type="submit" name="close" value="<?=_("Close selected")?>" class="button" style="font-size:12px"/>
          </td>
        </tr>
      </tr>
      </table>
    </td></tr>
<?php
if ($advanced_search) {
?>
    <tr><td colspan="7" style="border-width: 0px;">
      <table width="100%" align="center" style="border-width: 0px;">
      <tr>
          <td class="noborder"><?php echo _("with User") ?></td>
          <td class="noborder"><?php echo _("with Submitter") ?> </td>
          <td class="noborder"><?php echo _("with Title") ?></td>
          <td class="noborder"><?php echo _("with Attachment Name") ?></td>
          <td class="noborder"><?php echo _("with Tag") ?></td>
      </tr><tr>
          <td style="border-width: 0px;">
            <input type="text" name="related_to_user" value="<?php echo $related_to_user ?>" /></td>
          <td style="border-width: 0px;">
            <input type="text" name="submitter" value="<?php echo $submitter ?>" /></td>
          <td style="border-width: 0px;">
            <input type="text" name="title" value="<?php echo $title ?>" /></td>
          <td style="border-width: 0px;">
            <input type="text" name="attachment" value="<?php echo $attachment ?>" /></td>
          <td style="border-width: 0px;">
          <select name="tag">
                <option value=""></option>
            <?php
    foreach($incident_tag->get_list() as $t) { ?>
                <?php
        $selected = $tag == $t['id'] ? 'SELECTED' : ''; ?>
                <option value="<?php echo $t['id'] ?>" <?php echo $selected ?>><?php echo $t['name'] ?></option>
            <?php
    } ?>
          </select>
          </td>
      </tr>
      </table>
    </td></tr>
<?php
}
?>
  </table>
  <br/>
  <!-- end filter -->

  <table align="center" width="100%">
<?php
$rows_per_page = 50;
$incident_list = Incident::search($conn, $criteria, $order_by, $order_mode, $page, $rows_per_page);
$total_incidents = Incident::search_count($conn);
if (count($incident_list)>=$total_incidents) {
    $total_incidents = count($incident_list);
    if ($total_incidents>0) $rows_per_page = $total_incidents;
}
if ($total_incidents) {
    $filter = '';
    foreach($criteria as $key => $value) {
        $filter.= "&$key=" . urlencode($value);
    }
    if ($advanced_search) {
        $filter.= "&advanced_search=" . urlencode($advanced_search);
    }
    // Next time reverse the order of the column
    // XXX it reverses the order of all columns, should only
    //     reverse the order of the column previously sorted
    if ($order_mode) {
        $order_mode = $order_mode == 'DESC' ? 'ASC' : 'DESC';
        $filter.= "&order_mode=$order_mode";
    }
?>
    <tr>
      <th><input type="checkbox" name="ticket0" onclick="checkall()"></th>
      <th NOWRAP><a href="?order_by=id<?php echo $filter
?>"><?php echo _("Ticket") . order_img('id') ?></a></th>
      <th NOWRAP><a href="?order_by=title<?php echo $filter ?>"><?php echo _("Title") . order_img('title') ?></a></th>
      <th NOWRAP><a href="?order_by=priority<?php echo $filter ?>"><?php echo _("Priority") . order_img('priority') ?></a></th>
      <th NOWRAP><a href="?order_by=date<?php echo $filter ?>"><?php echo _("Created") . order_img('date') ?></a></th>
      <th NOWRAP><a href="?order_by=life_time<?php echo $filter ?>"><?php echo _("Life Time") . order_img('life_time') ?></a></th>
      <th><?php echo _("In charge") ?></th>
      <th><?php echo _("Submitter") ?></th>
      <th><?php echo _("Type") ?></th>
      <th><?php echo _("Status") ?></th>
      <th><?php echo _("Extra") ?></th>
    </tr>

<?php
    $row = 0;
    foreach($incident_list as $incident) {
?>

    <tr <?php
        if ($row++ % 2) echo 'bgcolor="#EFEFEF"'; ?> valign="middle">
      <td>
        <input type="checkbox" name="ticket<?php echo $row ?>" value="<?php echo $incident->get_id()."_".$incident->get_priority() ?>" <?php if ($incident->get_in_charge_name($conn) != Session::get_session_user() && !Session::am_i_admin()) echo "disabled" ?>>
      </td>
      <td>
        <a href="incident.php?id=<?php echo $incident->get_id() ?>">
        <?php echo $incident->get_ticket(); ?></a>
      </td>
      <td><b>
        <a href="incident.php?id=<?php echo $incident->get_id() ?>">

            <?php echo $incident->get_title(); ?></a></b>
<?php
        if ($incident->get_ref() == "Vulnerability") {
            $vulnerability_list = $incident->get_vulnerabilities($conn);
            // Only use first index, there shouldn't be more
            if (!empty($vulnerability_list)) {
                echo " <font color=\"grey\" size=\"1\">(" . $vulnerability_list[0]->get_ip() . ":" . $vulnerability_list[0]->get_port() . ")</font>";
            }
        }
?>
      </td>
      <?php
        $priority = $incident->get_priority();
?>
      <td><?php echo Incident::get_priority_in_html($priority) ?></td>
      <td NOWRAP><?php echo $incident->get_date() ?></td>
      <td NOWRAP><?php echo $incident->get_life_time() ?></td>
      <?php
      if (preg_match("/pro|demo/i",$version) && preg_match("/^\d+$/",$incident->get_in_charge_name($conn))) {
            list($entity_name, $entity_type) = Acl::get_entity_name_type($conn,$incident->get_in_charge_name($conn));
            $in_charge_name = $entity_name." [".$entity_type."]";
      }
      else {
        $in_charge_name = $incident->get_in_charge_name($conn);
      }
      ?>
      <td><?php echo $in_charge_name ?></td>
      <td><?php echo $incident->get_submitter() ?>&nbsp</td>
      <td><?php echo $incident->get_type() ?></td>
      <td><?php
        Incident::colorize_status($incident->get_status()) ?></td>
      <td>
        <?php
        $rows = 0;
        foreach($incident->get_tags() as $tag_id) {
            echo "<font color=\"grey\" size=\"1\">" . $incident_tag->get_html_tag($tag_id) . "</font><br/>\n";
            $rows++;
        }
        if (!$rows) echo "&nbsp;";
?>
      </td>
    </tr>

<?php
    } /* foreach */
} /* incident_list */
else {
    echo "<p align=\"center\">" . gettext("No Tickets") . "</p>";
}
$db->close($conn);
?>
    <!-- pagination -->
    <tr>
       <td colspan="11" align="right">
            <table align="right"><tr><td style="padding:3px 2px 3px 2px" class="noborder"><b><?=_("Pag")?>. </b></td>
            <?  
                // Pagination variables
                $maxpags = 10;
                $maximo = ($total_incidents % $rows_per_page == 0) ? ($total_incidents/$rows_per_page) : floor($total_incidents/$rows_per_page)+1;
                if ($page>$maximo) $page=$maximo;
                $bloque = ($page % $maxpags==0) ? ($page/$maxpags) : floor($page / $maxpags)+1;
                $hasta_pag = $maxpags * $bloque;
                $desde_pag = $hasta_pag - $maxpags + 1;
                if ($desde_pag<=0) $desde_pag=1;
                if ($hasta_pag>$maximo) $hasta_pag=$maximo;

                if ($bloque>1) echo "<td class=noborder><a href=\"#\" onclick=\"$('#page').val('".($desde_pag-1)."');$('#filter').submit()\"><<</a></td>";
                for ($i = $desde_pag; $i <= $hasta_pag; $i++) {
                    if ($i == $page) echo "<td class=noborder><b>$i</b></td>";
                    else echo "<td class=noborder><a href=\"#\" onclick=\"$('#page').val('$i');$('#filter').submit()\">$i</a></td>";
                }
                if ($hasta_pag<$maximo) echo "<td class=noborder><a href=\"#\" onclick=\"$('#page').val('".($hasta_pag+1)."');$('#filter').submit()\">>></a></td>";
            ?>
            </tr></table>
       </td>
    </tr>
    <tr>
      <td colspan="11" align="center" class='noborder'>

        <!-- new incident form -->
        <form id="formnewincident" method="GET">
           <table valign="absmiddle" align="center" class="noborder">
             <tr>
				<td class="noborder" valign="middle" align="center">
                   <span><?=_("Create new ticket of type: ")?></span>
                </td>
                <td class="noborder" valign="middle" align="center">
					<select id="selectnewincident">
						<optgroup label="<?=_('Generic')?>">
							 <option value="newincident.php?ref=Alarm&title=<?=urlencode(_("New Alarm incident"))?>&priority=1&src_ips=&src_ports=&dst_ips=&dst_ports="><?=_("Alarm")?></option>
							 <option value="newincident.php?ref=Event&title=<?=urlencode(_("New Event incident"))?>&priority=1&src_ips=&src_ports=&dst_ips=&dst_ports="><?=_("Event")?></option>
							 <option value="newincident.php?ref=Metric&title=<?=urlencode(_("New Metric incident"))?>&priority=1&target=&metric_type=&metric_value=0"><?=_("Metric")?></option>
							 <option value="newincident.php?ref=Vulnerability&title=<?=urlencode(_("New Vulnerability incident"))?>&priority=1&ip=&port=&nessus_id=&risk=&description="><?=_("Vulnerability")?></option>
						</optgroup>
						<optgroup label="<?=_('Anomalies')?>">
							 <option value="newincident.php?ref=Anomaly&title=<?=urlencode(_("New Mac Anomaly incident"))?>&priority=1&anom_type=mac"><?=_("Mac")?></option>
							 <option value="newincident.php?ref=Anomaly&title=<?=urlencode(_("New OS Anomaly incident"))?>&priority=1&anom_type=os"><?=_("OS")?></option>
							 <option value="newincident.php?ref=Anomaly&title=<?=urlencode(_("New Service Anomaly incident"))?>&priority=1&anom_type=service"><?=_("Services")?></option>
						</optgroup>
						 <? if (count($customs)>0) { ?>
						 <optgroup label="<?=_('Custom')?>">
						 <? foreach ($customs as $custom) { ?>
							 <option value="newincident.php?ref=Custom&title=<?=urlencode(_("New ".$custom." ticket"))?>&type=<?=urlencode($custom)?>&priority=1"><?=$custom?></option>
						 <? } ?>
						 </optgroup> 
						 <? } ?>
					</select>
					<input type="button" class="button" value="<?=_("Create")?>" onclick='javascript: self.location.href=this.form.selectnewincident.options[this.form.selectnewincident.selectedIndex].value;' />
				</td>
			</tr>
		</table>
	</form>
    <!-- end of new incident form -->
    
</form>

</body>
</html>

