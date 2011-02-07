<?php
require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
require_once 'classes/Util.inc';
Session::logcheck("MenuControlPanel", "ControlPanelExecutive");

function SIEM_trends() {
	$data = $hours = array();
	require_once 'ossim_db.inc';
	$db = new ossim_db();
	$dbconn = $db->snort_connect();
	$sqlgraph = "SELECT COUNT(acid_event.cid) as num_events, hour(timestamp) as intervalo, day(timestamp) as suf FROM acid_event LEFT JOIN ossim.plugin_sid ON acid_event.plugin_id=plugin_sid.plugin_id AND acid_event.plugin_sid=plugin_sid.sid WHERE timestamp BETWEEN '".date("Y-m-d H:i:s",time()-57600)."' AND '".date("Y-m-d H:i:s")."' GROUP BY suf,intervalo";
	if (!$rg = & $dbconn->Execute($sqlgraph)) {
	    print $conn->ErrorMsg();
	} else {
	    while (!$rg->EOF) {
	        $hours[] = $rg->fields["intervalo"]."h";
	        $data[] = $rg->fields["num_events"];
	        $rg->MoveNext();
	    }
	}
	$db->close($dbconn);
	return array($hours,$data);
}

function Logger_trends() {
	require_once("forensics_stats.inc");
	$data = $hours = array();
	$csv = array_reverse(get_day_csv(date("Y"),date("m"),date("d")));
	$i=0;
	foreach ($csv as $key => $value) {
		if ($i++<=16) {
			$hours[] = $key."h";
			$data[] = $value;
		}
	}
	return array($hours,$data);
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
list($hours,$trend) = SIEM_trends();
list($hours2,$trend2) = Logger_trends();
$max = (count($trend)>count($trend2)) ? count($trend) : count($trend2);
if (count($trend)>count($trend2)) {
	$max  = count($trend);
} else {
	$max  = count($trend2);
	$tmp = $hours;
	$hours = $hours2;
	$hours2 = $hours;
}
?>
<body scroll="no">		
	<table id="data" style="display:none">
        <tfoot>
            <tr>
            	<?	for ($i=0;$i<$max;$i++) {
            			$day = ($hours[$i]!="") ? $hours[$i] : (($hours2[$i]!="") ? $hours2[$i] : "-");
            			echo "<th>$day</th>\n";
            		}
            	?>
            </tr>
        </tfoot>
        <tbody>
            <tr>
            	<?	for ($i=0;$i<$max;$i++) {
            			$value = ($trend[$i]!="") ? $trend[$i] : 0;
            			echo "<td>$value</td>\n"; 
            		}
            	?>
            </tr>
        </tbody>
    </table>
    <table id="data2" style="display:none">
        <tbody>
            <tr>
            	<?	for ($i=0;$i<$max;$i++) {
            			$value = ($trend2[$i]!="") ? $trend2[$i] : 0;
            			echo "<td>$value</td>\n"; 
            		}
            	?>
            </tr>
        </tbody>
    </table>
	
    <script language="javascript">
        logger_url = '';
        siem_url = '../forensics/base_qry_main.php?clear_allcriteria=1&time_range=week&time[0][0]=+&time[0][1]=>%3D&time[0][2]=<?=date("m",time()-$range)?>&time[0][3]=<?=date("d",time()-$range)?>&time[0][4]=<?=date("Y",time()-$range)?>&time[0][5]=HH&time[0][6]=&time[0][7]=&time[0][8]=+&time[0][9]=+&submit=Query+DB&num_result_rows=-1&time_cnt=1&sort_order=time_d&hmenu=Forensics&smenu=Forensics';
    </script>
	<script src="../js/raphael/analytics.js"></script>
	<script src="../js/raphael/popup.js"></script>
    		
	<div id="holder" style='height:300px;width:100%;margin: auto;'></div>

</body>
</html>

