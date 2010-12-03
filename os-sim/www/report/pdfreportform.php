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
* - security_report()
* - metrics_report()
* - incident_report()
* - alarms_report()
* Classes list:
*/
require_once ('classes/Session.inc');
Session::logcheck("MenuReports", "ReportsPDFReport");
require_once 'classes/Incident.inc';
require_once 'classes/Incident_tag.inc';
require_once 'classes/Incident_type.inc';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <link rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
  
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/jquery-ui-1.8.custom.min.js"></script>
</head>
<body>

<?php
require_once 'classes/Security.inc';
include ("../hmenu.php");

$date_from = (GET('date_from') != "") ? GET('date_from') : strftime("%d/%m/%Y ", time())."00:00:00";
$date_to = (GET('date_to') != "") ? GET('date_to') : strftime("%d/%m/%Y %H:%M:%S", time());

$report_type = GET('report_type');
ossim_valid($report_type, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("Report Type"));
if (ossim_error()) {
    die(ossim_error());
}
if (empty($report_type)) $report_type = "security";
require_once ('ossim_conf.inc');
$path_conf = $GLOBALS["CONF"];
$fpdf_path = $path_conf->get_conf("fpdf_path");
if (!is_readable($fpdf_path)) {
    $error = new OssimError();
    $error->display("FPDF_PATH");
}
?>

  <!-- report selector -->
  <form name="report_selector" method="GET">
  <table align="center">
    <tr><td>
    <select name="report_type" 
        onChange="document.forms['report_selector'].submit()">
      <option 
        <?php
if ($report_type == "security") echo " selected "; ?>
        value="security"><?php
echo gettext('Security Report'); ?></option>
      <option 
        <?php
if ($report_type == "alarms") echo " selected "; ?>
        value="alarms"><?php
echo gettext('Alarms Report'); ?></option>
      <option 
        <?php
if ($report_type == "metrics") echo " selected "; ?>
        value="metrics"><?php
echo gettext('Metrics Report'); ?></option>
      <option 
        <?php
if ($report_type == "incident") echo " selected "; ?>
        value="incident"><?php
echo gettext('Incidents Report'); ?></option>
    </select>
    </td></tr>
  </table>
  </form>
  <!-- end report selector -->


  <table align="center">
  <form action="pdfreport.php" method="POST" />
	<tr>
		<td class="nobborder">
			<table class="noborder">
				<tr>
					<td width="20%" style="text-align: center; border-width: 0px">
						<b><?php echo _('Time') ?></b>:
					</td>
					<td style="text-align: left; border-width: 0px" nowrap>
						<?php echo _('from') ?>:<input type="text" style="font-size:9px" size=22 name="date_from" id="date_from"  value="<?php echo $date_from ?>">&nbsp;
						<br><?php echo _('to') ?>:<input type="text" style="font-size:9px" size="24" name="date_to" id="date_to" value="<?php echo $date_to ?>">&nbsp;
					</td>
				</tr>
			</table>
		</td>
	</tr>
<?php
if ($report_type == "security") {
    security_report();
} elseif ($report_type == "metrics") {
    metrics_report();
} elseif ($report_type == "incident") {
    incident_report();
} elseif ($report_type == "alarms") {
    alarms_report();
}
?>
  </form>
  </table>

<?php
function security_report() {
?>
    <tr>
      <th> <?php
    echo gettext("Security Report Options"); ?> </th>
    </tr>
    <tr>
      <!-- security report options -->
      <td>
        <table align="center" valign="center">
          <tr>
            <td class="left">
              <input type="checkbox" name="attacked" checked>
                <?php
    echo gettext("Top Attacked Hosts"); ?> 
              </input>
            </td>
          </tr>
          <tr>
            <td class="left">
              <input type="checkbox" name="attacker" checked>
                <?php
    echo gettext("Top AttackerHosts"); ?> 
              </input>
            </td>
          </tr>
          <tr>
            <td class="left">
              <input type="checkbox" name="ports" checked>
                <?php
    echo gettext("Top Destination Ports"); ?> 
              </input>
            </td>
          </tr>
          <tr>
            <td class="left">
              <input type="checkbox" name="eventsbyhost" checked>
                <?php
    echo gettext("Top Events by Host"); ?> 
              </input>
            </td>
          </tr>
          <tr>
            <td class="left">
              <input type="checkbox" name="eventsbyrisk" checked>
                <?php
    echo gettext("Top Events by Risk"); ?> 
              </input>
            </td>
          </tr>
          <tr>
            <td>
              <?php
    echo gettext("Number of hosts per table"); ?> : 
              <input type="text" size="2" name="limit" value="15" />
            </td>
          </tr>
        </table>
      </td>
      <!-- end security report options -->
    </tr>
    <tr>
      <td><input type="submit" class="btn" name="submit_security" value="<?php
    echo gettext('Generate'); ?>" /></td>
    </tr>
<?php
}
function metrics_report() {
?>
    <tr>
      <th> <?php
    echo gettext("Metrics Report Options"); ?> </th>
    </tr>
    <tr>
      <!-- metrics report -->
      <td>
        <table align="center" valign="center">
          <tr>
            <td class="left">
              <input type="checkbox" checked name="time_day"> 
	          <?php
    echo gettext("Day"); ?> </input>
            </td>
          </tr>
          <tr>
            <td class="left">
              <input type="checkbox" checked name="time_week">
	          <?php
    echo gettext("Week"); ?> </input>
            </td>
          </tr>
          <tr>
            <td class="left">
              <input type="checkbox" checked name="time_month">
	          <?php
    echo gettext("Month"); ?> </input>
            </td>
          </tr>
          <tr>
            <td class="left">
              <input type="checkbox" name="time_year">
	          <?php
    echo gettext("Year"); ?> </input>
            </td>
          </tr>
        </table>
      </td>
      <!-- end metrics report -->
    </tr>
    <tr>
      <td><input type="submit" name="submit_metrics" value="<?php
    echo gettext('Generate'); ?>" /></td>
    </tr>
<?php
}
function incident_report() {
    // First time we visit this page, show by default only Open incidents
    // when GET() returns NULL, means that the param is not set
    if (!(GET('status'))) $status = 'Open';
    $db = new ossim_db();
    $conn = $db->connect();
?>

  <!-- filter -->

  <form name="filter" method="GET" action="<?php echo $_SERVER["SCRIPT_NAME"] ?>">
   <table align="center" width="100%">
    <tr>
      <th colspan="7"><?php
    echo gettext("Incidents Report Options"); ?></th>
    </tr>
   <table align="center" width="100%">
    <tr>
      <table align="center" valign="center" width="100%">
          <tr>
          <td> <?php
    echo gettext("Class"); /* ref */ ?> </td>
          <td> <?php
    echo gettext("Type"); /* type */ ?> </td>
          <td> <?php
    echo gettext("In charge"); ?> </td>
          <td> <?php
    echo gettext("Title"); ?> </td>
          <td> <?php
    echo gettext("Date"); ?> </td>
          <td> <?php
    echo gettext("Status"); ?> </td>
          <td> <?php
    echo gettext("Priority"); ?> </td>
          <td> <?php
    echo gettext("Action"); ?> </td>
          </tr>
        <tr>
          <td class="left">
          <table>
            <tr>
                <td class="left">
                    <input type="checkbox" name="Alarm" checked>
                        <?php
    echo gettext("Alarm"); ?>
                    </input>
                </td>
            </tr>
            <tr>
                <td class="left">
                    <input type="checkbox" name="Event" checked>
                        <?php
    echo gettext("Event"); ?>
                    </input>
                </td>
            </tr>
            <tr>
                <td class="left">
                    <input type="checkbox" name="Metric" checked>
                        <?php
    echo gettext("Metric"); ?>
                    </input>
                </td>
            </tr>
            <tr>
                <td class="left">
                    <input type="checkbox" name="Anomaly" checked>
                        <?php
    echo gettext("Anomaly"); ?>
                    </input>
                </td>
            </tr>
            <tr>
                <td class="left">
                    <input type="checkbox" name="Vulnerability" checked>
                        <?php
    echo gettext("Vulnerability"); ?>
                    </input>
                </td>
            </tr>
           </table>
          </td>
        <td valign="top">
            <select name="Type">
              <option value="ALL">
                <?php
    echo gettext("ALL"); ?>
              </option>
              <?php
    foreach(Incident_type::get_list($conn) as $itype) {
        $id = $itype->get_id();
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
          <td valign="top" >
            <input type="text" name="In_Charge" value="<?php echo $in_charge
?>" /></td>
          <td valign="top" >
            <input type="text" name="Title" value="<?php echo $title
?>" /></td>
          <td valign="top" >
            <input type="text" size="22" name="Date" value="<?php
    echo date("F j, Y, g:i a"); ?>" /></td>
          <td valign="top" >
            <select name="Status">
              <option value="ALL">
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
    if ($status == "Closed") echo "selected" ?>
                value="Closed">
                <?php
    echo gettext("Closed"); ?>
              </option>
            </select>
          </td>
          <td valign="top" >
            <table>
                <tr>
                    <td class="left">
                        <input type="checkbox" name="High" checked>
                            <?php
    echo gettext("High"); ?>
                        </input>
                    </td>
                </tr>
                <tr>
                    <td class="left">
                        <input type="checkbox" name="Medium" checked>
                            <?php
    echo gettext("Medium"); ?>
                        </input>
                    </td>
                </tr>
                <tr>
                    <td class="left">
                        <input type="checkbox" name="Low" checked>
                            <?php
    echo gettext("Low"); ?>
                        </input>
                    </td>
                </tr>
            </table>
            </td>
          <td valign="top" nowrap >
            <input type="submit" name="submit_incident" value="Generate" class="btn" style="font-size:12px">
          </td>
        </tr>
      </tr>
      </table>
    </td></tr>
  </table>
  </form>
  <br/>
  <!-- end filter -->
<?php
}
function alarms_report() {
?>
    <tr>
      <th> <?php
    echo gettext("Alarms Report Options"); ?> </th>
    </tr>
    <tr>
      <!-- alarms report options -->
      <td>
        <table align="center" valign="center">
          <tr>
            <td class="left">
              <input type="checkbox" name="attacked" checked>
                <?php
    echo gettext("Top Attacked Hosts"); ?>
              </input>
            </td>
          </tr>
          <tr>
            <td class="left">
              <input type="checkbox" name="attacker" checked>
                <?php
    echo gettext("Top Attacker Hosts"); ?>
              </input>
            </td>
          </tr>
          <tr>
            <td class="left">
              <input type="checkbox" name="ports" checked>
                <?php
    echo gettext("Top Destination Ports"); ?>
              </input>
            </td>
          </tr>
          <tr>
            <td class="left">
              <input type="checkbox" name="alarmsbyhost" checked>
                <?php
    echo gettext("Top Alarms by Host"); ?>
              </input>
            </td>
          </tr>
          <tr>
            <td class="left">
              <input type="checkbox" name="alarmsbyrisk" checked>
                <?php
    echo gettext("Top Alarms by Risk"); ?>
              </input>
            </td>
          </tr>
          <tr>
            <td>
              <?php
    echo gettext("Number of hosts per table"); ?> :
              <input type="text" size="2" name="limit" value="15" />
            </td>
          </tr>
        </table>
      </td>
      <!-- end security report options -->
    </tr>
    <tr>
      <td><input type="submit" name="submit_alarms" value="<?php
    echo gettext('Generate'); ?>" class="btn" style="font-size:12px"></td>
    </tr>
<?php
}
?>
<script>
// GreyBox
$(document).ready(function(){
	$('#date_from').datepicker();
	$('#date_to').datepicker();
});
</script>
</html>

