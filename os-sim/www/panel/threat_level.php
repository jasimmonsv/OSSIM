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
require_once 'classes/Session.inc';
require_once 'classes/Util.inc';
require_once 'ossim_db.inc';
require_once 'ossim_conf.inc';
Session::logcheck("MenuControlPanel", "ControlPanelExecutive");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
<head>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
        <title>Theat Level</title>
        <!--[if IE]><script language="javascript" type="text/javascript" src="../js/jqplot/excanvas.js"></script><![endif]-->
		<script language="javascript" type="text/javascript" src="../js/jquery-1.3.2.min.js"></script> 
		<script language="javascript" type="text/javascript" src="../js/jqplot/jquery.jqplot.js"></script> 
		<script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.meterGaugeRenderer.js"></script> 
</head>
<style type="text/css"> 
	body { font-family:arial; font-size: 13px; }
	.plot {
	    margin-bottom: 10px;
	    margin-left: auto;
	    margin-right: auto;
	}
	 
	#chart3 .jqplot-meterGauge-tick, #chart0 .jqplot-meterGauge-tic {
	    font-family:arial; font-size: 11px; color:gray;
	}
	.red { background-color:#fe0000; color:white; padding:4px 20px; }
	.orange { background-color:#ee782e; color:white;  padding:4px 20px; }
	.yellow { background-color:#fcc200; color:white; padding:4px 20px; }
	.rough { background-color:#b3ae08; color:white;  padding:4px 20px; }
	.green { background-color:#338e05; color:white;  padding:4px 20px; }
	.grayed { background-color:#BBC6D0; color:#EFEFEF;  padding:4px 20px; }
	
</style> 
<?php
$db = new ossim_db();
$conn = $db->connect();
$user = Session::get_session_user();
$conf = $GLOBALS['CONF'];
$font = $conf->get_conf('font_path');
$range = "day";
$sql = "SELECT c_sec_level, a_sec_level FROM control_panel WHERE id = ? AND time_range = ?";
$params = array(
    "global_$user",
    $range
);
if (!$rs = & $conn->Execute($sql, $params)) {
    die($conn->ErrorMsg());
}
//We want the opposite of the service level, if the service level is 100% the
//thermomether will be 0% (low temperature)
$level = ($rs->fields["c_sec_level"] + $rs->fields["a_sec_level"]) / 2;
$level = 100 - $level;
$level = intval($level);
$db->close($conn);
?> 
<script type="text/javascript"> 
	$(document).ready(function(){
	   $.jqplot.config.enablePlugins = true;
	   s1 = [<?=$level?>];
	   plot3 = $.jqplot('chart3',[s1],{
	       seriesDefaults: {
	           renderer: $.jqplot.MeterGaugeRenderer,
	           rendererOptions: {
	               min: 0,
	               max: 100,
	               intervals:[20, 40, 60, 80, 100],
	               intervalColors:['#338e05', '#b3ae08', '#fcc200', '#ee782e', '#fe0000']
	           }
	       }
	   });   
   });
</script>
<body style="overflow:hidden" scroll="no">
<table border="0" cellpadding="0" cellpadding="0" width="100%">
<tr>
	<td valign="top" align="center" style="padding-top:20px">
		<table border="0" cellpadding="4" cellpadding="1">
		<tr><td class="<?= ($level>80) ? "red" : "grayed" ?>"> <?=_("Very High")?> </td></tr>
		<tr><td class="<?= ($level>60) ? "orange" : "grayed" ?>"> <?=_("High")?> </td></tr>
		<tr><td class="<?= ($level>40) ? "yellow" : "grayed" ?>"> <?=_("Elevated")?> </td></tr>
		<tr><td class="<?= ($level>20) ? "rough" : "grayed" ?>"> <?=_("Precaution")?> </td></tr>
		<tr><td class="green"> <?=_("Low")?> </td></tr>
		</table>
	</td>
	<td align="right"><div id="chart3" class="plot" style="width:300px;height:170px;"></div></td>
</tr>
</table>
</body>
</html>
