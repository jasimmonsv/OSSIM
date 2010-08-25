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
require_once ('classes/Session.inc');
Session::logcheck("MenuPolicy", "PolicyHosts");
?>

<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.1.js"></script>
  <script type="text/javascript" src="../js/jquery.simpletip.js"></script>


<script type="text/javascript">
function check_host (ip) {
	$.ajax({
		type: "GET",
		url: "check_host_response.php?ip="+ip,
		data: "",
		success: function(msg){
			if (msg == "1") {
				if (confirm("Do you want to update host '"+ip+"'?"))
					document.newform.submit();
			}
			else document.newform.submit();
		}
	});
}
function check_net (ip) {
	$('#loading').html('<img src="../pixmaps/loading.gif" width="13" alt="<?=_("Loading")?>">');
	$.ajax({
		type: "GET",
		url: "check_net_response.php?ip="+ip,
		data: "",
		success: function(msg){
			if (msg != "0") {
				var fields = msg.split(";");
				$("#asset").val(fields[0]);
				document.getElementById('threshold_c').value = fields[1];
				document.getElementById('threshold_a').value = fields[2];
			}
			$('#loading').html('');
		}
	});
}



$(document).ready(function(){

    $(".sensor_info").simpletip({
                    position: 'top',
                    offset: [-60, -10],
                    content: '',
					baseClass: 'ytooltip',
                    onBeforeShow: function() {
                            var txt = this.getParent().attr('txt');
                            this.update(txt);
                    }
    });

});





</script>

</head>
<body>

<?php
require_once ('classes/Security.inc');
if (REQUEST('scan')) {
    if (GET('withoutmenu') != "1") include ("../hmenu.php");
    echo "<p>";
    echo gettext("Please, fill these global properties about the hosts you've scaned");
    echo ":</p>";
} else {
    if (GET('withoutmenu') != "1") include ("../hmenu.php");
}
?>

<?php
require_once ('ossim_db.inc');
require_once ('ossim_conf.inc');
require_once ('classes/Sensor.inc');
require_once ('classes/RRD_config.inc');
$ip = REQUEST('ip');
$ips = REQUEST('ips');
$scan = REQUEST('scan');
ossim_valid($ip, OSS_IP_ADDR, OSS_NULLABLE, 'illegal:' . _("ip"));
ossim_valid($ips, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("ips"));
ossim_valid($scan, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("scan"));
if (ossim_error()) {
    die(ossim_error());
}
$db = new ossim_db();
$conn = $db->connect();
$conf = $GLOBALS["CONF"];

$threshold = $conf->get_conf("threshold");
$action = "newhost.php";
if (REQUEST('scan')) {
    $ip = REQUEST('target');
    $action = "../netscan/scan_db.php";
}
?>
    
    <form method="post" name="newform" action="<?php echo $action ?>">
    <table align="center">
      <input type="hidden" name="insert" value="insert">

<?php
if (empty($scan)) {
?>
  <tr>
    <th> <?php echo gettext("Hostname"); ?></th>
    <td class="left"><input type="text" name="hostname" size="25" value="<?=REQUEST('hostname')?>"/><span style="padding-left: 3px;">*</span></td>
  </tr>
  <tr>
    <th> <?php
    echo gettext("IP"); ?></th>
    <td class="left">
      <input type="text" value="<?php echo $ip ?>" size="25" name="ip" id="ip" onchange="check_net(this.value)"><span style="padding-left: 3px;">*</span>
      <div id="loading" style="display:inline"></div>
    </td>
  </tr>
  
<?php
} else {
?>
  <input type="hidden" value="<?php echo $ip ?>" name="ip" id="ip">
  <tr>
    <th> <?php echo gettext("Optional group name"); ?></th>
    <td class="left"><input type="text" name="groupname" value="<?=REQUEST('groupname')?>"><span style="padding-left: 3px;">*</span></td>
  </tr>
<?php
}
?>

  <tr>
    <th> <?php echo gettext("Description"); ?> </th>
    <td class="left">
      <textarea name="descr" rows="3" cols="40"><?=REQUEST('descr')?></textarea>
    </td>
  </tr>

  <tr>
    <th> <?php
echo gettext("Asset Value"); ?></th>
    <td class="left">
      <select name="asset" id="asset">
        <option value="0" <?=((REQUEST('asset') == '0') ? 'selected' : '')?>>
	<?php
echo gettext("0"); ?> </option>
        <option value="1" <?=((REQUEST('asset') == '1') ? 'selected' : '')?>>
	<?php
echo gettext("1"); ?> </option>
        <option value="2" <?=((REQUEST('asset') == '2'|| REQUEST('asset') == '') ? 'selected' : '')?>>
	<?php
echo gettext("2"); ?> </option>
        <option value="3" <?=((REQUEST('asset') == '3') ? 'selected' : '')?>>
	<?php
echo gettext("3"); ?> </option>
        <option value="4" <?=((REQUEST('asset') == '4') ? 'selected' : '')?>>
	<?php
echo gettext("4"); ?> </option>
        <option value="5" <?=((REQUEST('asset') == '5') ? 'selected' : '')?>>
	<?php
echo gettext("5"); ?> </option>
      </select>
      <span style="padding-left: 3px;">*</span>
    </td>
  </tr>

  <tr>
    <th> <?php echo gettext("NAT"); ?> </th>
    <td class="left">
      <input type="text" name="nat" size="25" value="<?=REQUEST('nat')?>">
    </td>
  </tr>

  <tr>
    <th> <?php
echo gettext("Sensors"); ?>
<a style="cursor:pointer; text-decoration: none;" class="sensor_info"  txt="<div style='width: 150px; white-space: normal; font-weight: normal;'>Define which sensors has visibility of this host</div>">
<img src="../pixmaps/help.png" width="16" border="0" align="absmiddle"/></a>
<br/>
        <font size="-2">
          <a href="../sensor/newsensorform.php">
	  <?php echo gettext("Insert new sensor"); ?> ?</a>
        </font>

    </th>

        <td class="left">
<?php
/* ===== sensors ==== */
$i = 1;
if ($sensor_list = Sensor::get_all($conn, "ORDER BY name")) {
    foreach($sensor_list as $sensor) {
        $sensor_name = $sensor->get_name();
        $sensor_ip = $sensor->get_ip();
        if ($i == 1) {
?>
        <input type="hidden" name="<?php echo "nsens"; ?>" value="<?php  echo count($sensor_list); ?>"/>
<?php
        }
        $name = "mboxs" . $i;
?>
        <input type="checkbox" checked name="<?php
        echo $name; ?>" value="<?php echo $sensor_name; ?>" <?=((REQUEST($name) == $sensor_name) ? 'checked' : '')?>/>
            <?php echo $sensor_ip . " (" . $sensor_name . ")<br>"; ?>
        
<?php
        $i++;
    }
}
?>
    </td>
  </tr>
  
  
<tr><td style="text-align: left; border:none; padding-top:3px;"><a onclick="$('.advanced').toggle()" style="cursor:pointer;"><img border="0" align="absmiddle" src="../pixmaps/arrow_green.gif">Advanced</a></td></tr>

<tr class="advanced" style="display:none;">
    <th> <?php echo gettext("Scan options"); ?> </th>
    <td class="left">
    <!--
      <input type="checkbox" name="nessus" value="1" <?=((REQUEST('nessus') == '1') ? 'checked' : '')?>/> <?php
echo gettext("Enable nessus scan"); ?> </input><br/>-->
      <input type="checkbox" name="nagios" value="1" <?=((REQUEST('nagios') == '1') ? 'checked' : '')?>/> <?php
echo gettext("Enable nagios"); ?>
    </td>
</tr>

<tr class="advanced" style="display:none;">
    <th> <?php echo gettext("RRD Profile"); ?><br/>
        <font size="-2">
          <a href="../rrd_conf/new_rrd_conf_form.php">
	  <?php echo gettext("Insert new profile"); ?> ?</a>
        </font>
    </th>
    <td class="left">
      <select name="rrd_profile">
<?php
foreach(RRD_Config::get_profile_list($conn) as $profile) {
    if (strcmp($profile, "global"))
        echo "<option value=\"$profile\" ".((REQUEST('rrd_profile') == $profile) ? 'selected' : '').">$profile</option>\n";
    }
?>
	<option value="" <?=((REQUEST('rrd_profile') == '') ? 'selected' : '')?>>
  <?php
  echo gettext("None"); ?> </option>

      </select>
      <span style="padding-left: 3px;">*</span>
    </td>
  </tr>

  <tr class="advanced" style="display:none;">
    <th> <?php echo gettext("Threshold C"); ?></th>
    <td class="left">
      <input type="text" size="11" value="<?=((REQUEST('threshold_c') == '') ? $threshold : REQUEST('threshold_c'))?>" name="threshold_c" id="threshold_c" size="4"/><span style="padding-left: 3px;">*</span>
    </td>
  </tr>
  <tr class="advanced" style="display:none;">
    <th> <?php echo gettext("Threshold A"); ?></th>
    <td class="left">
      <input type="text" size="11" value="<?=((REQUEST('threshold_a') == '') ? $threshold : REQUEST('threshold_a'))?>" name="threshold_a" id="threshold_a" size="4"/><span style="padding-left: 3px;">*</span>
    </td>
  </tr>
  
<!--
  <tr>
    <th>Alert</th>
    <td class="left">
      <select name="alert">
        <option value="1">Yes</option>
        <option selected value="0">No</option>
      </select>
    </td>
  </tr>
  <tr>
    <th>Persistence</th>
    <td class="left">
      <input type="text" name="persistence" value="15" size="3"></input>min.
    </td>
  </tr>
-->
  
  
<?php
if (empty($scan)) {
?>
  
  <tr><td style="text-align: left; border:none; padding-top:3px;"><a onclick="$('.inventory').toggle()" style="cursor:pointer;"><img border="0" align="absmiddle" src="../pixmaps/arrow_green.gif">Inventory</a></td></tr>

  <tr class="inventory" style="display:none;">
    <th> <?php
    echo gettext("OS"); ?> </th>
    <td class="left">
      <select name="os" style="width:170px;">
        <option value="Unknown" <?=((REQUEST('os') == 'Unknown') ? 'selected' : '')?>> </option>
        <option value="Windows" <?=((REQUEST('os') == 'Windows') ? 'selected' : '')?>><?php echo _("Microsoft Windows"); ?> </option>
        <option value="Linux" <?=((REQUEST('os') == 'Linux') ? 'selected' : '')?>><?php echo _("Linux"); ?> </option>
        <option value="FreeBSD" <?=((REQUEST('os') == 'FreeBSD') ? 'selected' : '')?>><?php echo _("FreeBSD"); ?> </option>
        <option value="NetBSD" <?=((REQUEST('os') == 'NetBSD') ? 'selected' : '')?>><?php echo _("NetBSD"); ?> </option>
        <option value="OpenBSD" <?=((REQUEST('os') == 'OpenBSD') ? 'selected' : '')?>><?php echo _("OpenBSD"); ?> </option>
        <option value="MacOS" <?=((REQUEST('os') == 'MacOS') ? 'selected' : '')?>><?php echo _("Apple MacOS"); ?> </option>
        <option value="Solaris" <?=((REQUEST('os') == 'Solaris') ? 'selected' : '')?>><?php echo _("SUN Solaris"); ?> </option>
        <option value="Cisco" <?=((REQUEST('os') == 'Cisco') ? 'selected' : '')?>><?php echo _("Cisco IOS"); ?> </option>
        <option value="AIX" <?=((REQUEST('os') == 'AIX') ? 'selected' : '')?>><?php echo _("IBM AIX"); ?> </option>
        <option value="HP-UX" <?=((REQUEST('os') == 'HP-UX') ? 'selected' : '')?>><?php echo _("HP-UX"); ?> </option>
        <option value="Tru64" <?=((REQUEST('os') == 'Tru64') ? 'selected' : '')?>><?php echo _("Compaq Tru64"); ?> </option>
        <option value="IRIX" <?=((REQUEST('os') == 'IRIX') ? 'selected' : '')?>><?php echo _("SGI IRIX"); ?> </option>
        <option value="BSD/OS" <?=((REQUEST('os') == 'BSD/OS') ? 'selected' : '')?>><?php echo _("BSD/OS"); ?> </option>
        <option value="SunOS" <?=((REQUEST('os') == 'SunOS') ? 'selected' : '')?>><?php echo _("SunOS"); ?> </option>
        <option value="Plan9" <?=((REQUEST('os') == 'Plan9') ? 'selected' : '')?>><?php echo _("Plan9"); ?> </option> <!-- gdiaz's tribute :) -->
        <option value="IPhone" <?=((REQUEST('os') == 'IPhone') ? 'selected' : '')?>><?php echo _("IPhone"); ?> </option> 

      </select>
    </td>
  </tr>

  <tr class="inventory" style="display:none;">
    <th> <?php
    echo gettext("Mac"); ?> </th>
    <td class="left"><input type="text" size="25" name="mac" value="<?=REQUEST('mac')?>"/></td>
  </tr>

  <tr class="inventory" style="display:none;">
    <th> <?php
    echo gettext("Mac Vendor"); ?></th>
    <td class="left"><input type="text" size="25" name="mac_vendor" value="<?=REQUEST('mac_vendor')?>"/></td>
  </tr>
<?php
} else {
?>
        <input type="hidden" name="ips" value="<?php
    echo $ips ?>" />
<?php
    for ($i = 0; $i < $ips; $i++) {
?>
        <input type="hidden" name="ip_<?php
        echo $i ?>" 
            value="<?php
        echo POST("ip_$i") ?>" />
<?php
    } /* foreach */
} /* if ($scan) */
?>

<tr><td style="text-align: left; border:none; padding-top:3px;"><a onclick="$('.geolocation').toggle()" style="cursor:pointer;"><img border="0" align="absmiddle" src="../pixmaps/arrow_green.gif">Geolocation Info</a></td></tr>


<tr class="geolocation" style="display:none;">
    <th> <?php
    echo gettext("Latitude"); ?></th>
    <td class="left"><input type="text" size="25" name="latitude" value="<?=REQUEST('latitude')?>"></td>
</tr>

<tr class="geolocation" style="display:none;">
    <th> <?php
    echo gettext("Longitude"); ?></th>
    <td class="left"><input type="text" size="25" name="longitude"value="<?=REQUEST('longitude')?>"></td>
</tr>

  <tr>
    <td colspan="2" align="center" style="border-bottom: none; padding: 10px;">
      <input type="button" value="<?=_("OK")?>" onclick="check_host(document.getElementById('ip').value)" class="btn" style="font-size:12px">
      <input type="reset" value="<?php echo gettext("reset"); ?>" class="btn" style="font-size:12px">
    </td>
  </tr>
</table>
</form>

<p align="center" style="font-style: italic;"><?php echo gettext("Values marked with (*) are mandatory"); ?></p>

</body>
</html>

<?php
$db->close($conn);
?>

