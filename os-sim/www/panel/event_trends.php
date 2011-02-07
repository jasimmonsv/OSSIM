<?php
require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
require_once 'classes/Util.inc';
Session::logcheck("MenuControlPanel", "ControlPanelExecutive");

function SIEM_trends() {
	$data = array();
	require_once 'ossim_db.inc';
	$db = new ossim_db();
	$dbconn = $db->snort_connect();
	$sqlgraph = "SELECT COUNT(acid_event.cid) as num_events, hour(timestamp) as intervalo, day(timestamp) as suf FROM acid_event LEFT JOIN ossim.plugin_sid ON acid_event.plugin_id=plugin_sid.plugin_id AND acid_event.plugin_sid=plugin_sid.sid WHERE timestamp BETWEEN '".date("Y-m-d H:i:s",time()-57600)."' AND '".date("Y-m-d H:i:s")."' GROUP BY suf,intervalo";
	if (!$rg = & $dbconn->Execute($sqlgraph)) {
	    print $conn->ErrorMsg();
	} else {
	    while (!$rg->EOF) {
	        $data[$rg->fields["intervalo"]."h"] = $rg->fields["num_events"];
	        $rg->MoveNext();
	    }
	}
	$db->close($dbconn);
	return $data;
}

function Logger_trends() {
	require_once("forensics_stats.inc");
	$data = array();
	$csv = array_reverse(get_day_csv(date("Y"),date("m"),date("d")));
	$i=0;
	foreach ($csv as $key => $value) {
		if ($i++<=16) $data[$key."h"] = $value;
	}
	return $data;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
<head>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
        <title>Event Trends</title>
		<script language="javascript" src="../js/raphael/raphael.js"></script>
        <script language="javascript" src="../js/jquery-1.3.2.min.js"></script>
        <style type="text/css"> body { overflow:hidden; } </style>
</head>
<?php
//$siem = (GET("type")=="siem") ? true : false; 
//$trend = ($siem) ? SIEM_trends() : Logger_trends();
$trend = SIEM_trends();
$trend2 = Logger_trends();
?>
<body scroll="no">		
	<table id="data" style="display:none">
        <tfoot>
            <tr>
            	<? $i=0; foreach ($trend as $day => $value) echo "<th>$day</th>\n"; ?>
            </tr>
        </tfoot>
        <tbody>
            <tr>
            	<? foreach ($trend as $day => $value) echo "<td>$value</td>\n"; ?>
            </tr>
        </tbody>
    </table>
    <table id="data2" style="display:none">
        <tfoot>
            <tr>
            	<? $i=0; foreach ($trend2 as $day => $value) echo "<th>$day</th>\n"; ?>
            </tr>
        </tfoot>
        <tbody>
            <tr>
            	<? foreach ($trend2 as $day => $value) echo "<td>$value</td>\n"; ?>
            </tr>
        </tbody>
    </table>
	
	<script src="../js/raphael/analytics.js"></script>
	<script src="../js/raphael/popup.js"></script>
    		
	<div id="holder" style='height:300px;width:100%;margin: auto;'></div>

</body>
</html>

