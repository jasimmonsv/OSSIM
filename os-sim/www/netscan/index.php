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
* Classes list:
*/
// menu authentication
require_once ('classes/Session.inc');
Session::logcheck("MenuPolicy", "ToolsScan");
// Get a list of nets from db
require_once ("ossim_db.inc");
$db = new ossim_db();
$conn = $db->connect();
require_once ("classes/Net.inc");
require_once ("classes/Scan.inc");
$net_list = Net::get_list($conn);
$networks = "";
foreach ($net_list as $_net) {
	$networks .= '{ txt:"'.$_net->get_name().' ['.$_net->get_ips().']", id: "'.$_net->get_ips().'" },';
}
$db->close($conn);
require_once 'ossim_conf.inc';
$conf = $GLOBALS["CONF"];
$nmap_path = $conf->get_conf("nmap_path");
if (file_exists($nmap_path)) {
    $nmap_exists = 1;
} else {
    $nmap_exists = 0;
}
$nmap_running = Scan::scanning_now();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css">
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>
  <script>
    // enable text input when manual option is selected
    function check_change() {
        form = document.forms['net_form'];
        if (form.net.value != '')
            form.net_input.disabled = true;
        else
            form.net_input.disabled = false;
        form.net_input.value = form.net.value;
    }

    $(document).ready(function(){
    	var networks = [
    	   	<?= preg_replace("/,$/","",$networks); ?>
    	   	];
    	$("#net").autocomplete(networks, {
    	   	minChars: 0,
    	    width: 225,
    	    matchContains: "word",
    	    autoFill: true,
    	    formatItem: function(row, i, max) {
    	    	return row.txt;
    	    }
    	    }).result(function(event, item) {
    	    	$("#net").val(item.id);
    	});
    });
  </script>
  
</head>

<body>

<?php
$typeMenu='horizontal';
include ("../hmenu.php");
if (!$nmap_exists) {
    require_once ("ossim_error.inc");
    $error = new OssimError();
    $error->display("NMAP_PATH");
}
?>
  <!-- net selector form -->
  <form name="net_form" method="GET" action="do_scan.php" target="process">
  <table align="center">
    <tr>
      <th colspan="2">
        <?php
echo gettext("Please, select the network you want to scan:") ?>
      </th>
    </tr>
    <tr>
      <td colspan="2">
        <p align="center">
        <input type="text" value="" name="net" id="net"/>
      	</p>
      </td>
    </tr>

    <tr>
      <th colspan="2"><?=_("Net discover options")?></th>
    </tr>

    <!-- full scan -->
    <tr>
    <td colspan="2">
    <p align="center"><?php echo _("Scan type")?>:&nbsp;
    <select name="full_scan">
      <option value=""><?=_("Normal")?></option>
      <option value="fast"><?=_("Fast Scan")?></option>
      <option value="full"><?=_("Full Scan")?></option>
    </select>
    <br/><small style='color:grey'>
    <?php echo _("<b>Full mode</b> will be much slower but will include OS, services, service versions and MAC address into the inventory")?><br/>
    <?php echo _("<b>Fast mode</b> will scan fewer ports than the default scan") ?>
   </small>
    </p>
    </td>
    </tr>
    <!-- end full scan -->

    <!-- timing template (T0-5) -->
    <tr>
      <td colspan="2">
        <p align="center"><?=_("Timing template")?>:&nbsp;
        <select name="timing_template">
          <option value="-T0">(T0) <?=_("paranoid")?></option>
          <option value="-T1">(T1) <?=_("sneaky")?></option>
          <option value="-T2">(T2) <?=_("polite")?></option>
          <option selected value="-T3">(T3) <?=_("normal")?></option>
          <option value="-T4">(T4) <?=_("aggressive")?></option>
          <option value="-T5">(T5) <?=_("insane")?></option>
        </select>
        <br/>
        <small style="color:grey">
           <?php echo _("<b>Paranoid</b> and <b>Sneaky</b> modes are for IDS evasion<br/>");?>
           <?php echo _("<b>Polite</b> mode slows down the scan to use less bandwidth and target machine resources<br/>");?>
           <?php echo _("<b>Aggressive</b> and <b>Insane</b> modes speed up the scan (fast and reliable networks)<br/>");?>
         </small>
      </td>
    </tr>
    <!-- end timing template -->

    <!-- do scan -->
    <tr>
      <td colspan="2" class="nobborder center">
        <input type="submit" id="scan_button" class="button" onclick="if(document.net_form.net.value=='') {alert('<?php echo _("You must choose a network") ?>');return false;} $('#process_div').show()" style="font-size:12px" value="<?=_("Start Scan") ?>" <?php echo (!$nmap_exists || $nmap_running) ? "disabled" : "" ?> />
        
        <? if (Session::am_i_admin()) { ?>&nbsp;&nbsp;
        <input type="button" class="button" style="font-size:12px" value="<?=_("Manage Remote Scans") ?>" onclick="$('#process_div').show();document.location.href='remote_scans.php'"/>
		<? } ?>
		
		</td>
    </tr>
    <!-- end do scan -->
	<tr><td id="process_div" style="display:<?php echo ($nmap_running) ? "block" : "none" ?>"><IFRAME name="process" id="process" src="<?php if ($nmap_running) echo "do_scan.php?only_status=1" ?>" frameborder="0" width="300"></IFRAME></td></tr>
  </table>
  </form>
  <!-- end of net selector form -->

<?php
require_once ('classes/Scan.inc');
if (GET('clearscan')) {
    Scan::del_scan();
}
$scan = new Scan("");
$lastscan = $scan->get_scan();
if (is_array($lastscan) && count($lastscan)>0) {
    require_once ('scan_util.php');
    scan2html($lastscan);
} else {
    echo "<!-- <p align=\"center\">";
    echo gettext("NOTE: This tool is a nmap frontend. In order to use all nmap functionality, you need root privileges.");
    echo "<br/>";
    echo gettext("For this purpose you can use suphp, or change group to the web-user and set suid to nmap binary (<b>chgrp www-data /usr/bin/nmap ; chmod 4750 /usr/bin/nmap</b>).");
    echo "</p> -->";
}
?>


</body>
</html>
