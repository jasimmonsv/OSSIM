<?php
require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
require_once ('classes/Util.inc');
require_once ('sensor_filter.php');

$events_hids        = Session::menu_perms("MenuEvents", "EventsHids");
$events_hids_config = Session::menu_perms("MenuEvents", "EventsHidsConfig");
$panel_executive    = Session::menu_perms("MenuControlPanel", "ControlPanelExecutive");

if ( $_SESSION['menu_opc'] == 'Detection' && $_SESSION['menu_sopc'] == 'HIDS' )	
{
	if ( !$events_hids && !$events_hids_config )
		Session::unallowed_section(null, 'noback', "MenuEvents", "EventsHids");
}
else
{
	if ( !$panel_executive)
		Session::unallowed_section(null, 'noback', "MenuControlPanel", "ControlPanelExecutive");
}

session_write_close();

function SIEM_trends($h=24) {
	global $tz;
	$tzc = Util::get_tzc($tz);
	$data = array();
	require_once 'ossim_db.inc';
	$db = new ossim_db();
	$dbconn = $db->snort_connect();
	$sensor_where = make_sensor_filter($dbconn);
	$sqlgraph = "SELECT COUNT(acid_event.sid) as num_events, hour(convert_tz(timestamp,'+00:00','$tzc')) as intervalo, day(convert_tz(timestamp,'+00:00','$tzc')) as suf FROM acid_event WHERE timestamp BETWEEN '".gmdate("Y-m-d H:i:s",gmdate("U")-(3600*$h))."' AND '".gmdate("Y-m-d H:i:s")."' $sensor_where GROUP BY suf,intervalo";
	//print_r($sqlgraph);
	if (!$rg = & $dbconn->Execute($sqlgraph)) {
	    print $dbconn->ErrorMsg();
	} else {
	    while (!$rg->EOF) {
	    	//$tzhour = $rg->fields["intervalo"] + $tz;
	    	//if ($tzhour<0) $tzhour+=24;
	    	//elseif ($tzhour>23) $tzhour-=24;
	        //$data[$tzhour."h"] = $rg->fields["num_events"];
	        $data[$rg->fields["suf"]." ".$rg->fields["intervalo"]."h"] = $rg->fields["num_events"];
	        $rg->MoveNext();
	    }
	}
	$db->close($dbconn);
	return $data;
}

function SIEM_trends_week($param="") {
	global $tz;
	$tzc = Util::get_tzc($tz);
	$data = array();
	$plugins = $plugins_sql = "";
	require_once 'ossim_db.inc';
	$db = new ossim_db();
	$dbconn = $db->connect();
	$sensor_where = make_sensor_filter($dbconn);
	if ($param!="") {
		require_once("classes/Plugin.inc");
		$oss_p_id_name = Plugin::get_id_and_name($dbconn, "WHERE name LIKE '$param'");
		$plugins = implode(",",array_flip ($oss_p_id_name));
		$plugins_sql = "AND acid_event.plugin_id in ($plugins)";
	}
	$sqlgraph = "SELECT COUNT(acid_event.sid) as num_events, day(convert_tz(timestamp,'+00:00','$tzc')) as intervalo, monthname(convert_tz(timestamp,'+00:00','$tzc')) as suf FROM snort.acid_event LEFT JOIN ossim.plugin ON acid_event.plugin_id=plugin.id WHERE timestamp BETWEEN '".gmdate("Y-m-d 00:00:00",gmdate("U")-604800)."' AND '".gmdate("Y-m-d 23:59:59")."' $plugins_sql $sensor_where GROUP BY suf,intervalo ORDER BY suf,intervalo";
	if (!$rg = & $dbconn->Execute($sqlgraph)) {
	    print $dbconn->ErrorMsg();
	} else {
	    while (!$rg->EOF) {
	        $hours = $rg->fields["intervalo"]." ".substr($rg->fields["suf"],0,3);
	        $data[$hours] = $rg->fields["num_events"];
	        $rg->MoveNext();
	    }
	}
	$db->close($dbconn);
	return ($param!="") ? array($data,$oss_plugin_id) : $data;
}

function Logger_trends() {
	require_once("forensics_stats.inc");
	global $tz;
	$data = array();
	$today = gmdate("j");
	$beforeyesterday = gmdate("j",strtotime("-2 day"));
	$yesterday = gmdate("j",strtotime("-1 day"));
	$tomorrow = gmdate("j",strtotime("+1 day"));
	$csy = get_day_csv(gmdate("Y",strtotime("-1 day")),gmdate("m",strtotime("-1 day")),gmdate("d",strtotime("-1 day")));
	$csv = get_day_csv(gmdate("Y"),gmdate("m"),gmdate("d"));
	//print_r($csy); print_r($csv);
	foreach ($csy as $key => $value) {
		$tzhour = $key + $tz;
		$day = $yesterday;
		if ($tzhour<0) { $tzhour+=24; $day=$beforeyesterday; }
		elseif ($tzhour>23) { $tzhour-=24; $day=$today; }
		$data[$day." ".$tzhour."h"] = $value;
	}	
	foreach ($csv as $key => $value) {
		$tzhour = $key + $tz;
		$day = $today;
		if ($tzhour<0) { $tzhour+=24; $day=$yesterday; }
		elseif ($tzhour>23) { $tzhour-=24; $day=$tomorrow; }
		$data[$day." ".$tzhour."h"] = $value;
	}	
	//print_r($data);
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
$max = 16;
$hours = $trend = $trend2 = array();
//
if (GET("type")=="siemday") { 
    $js = "analytics";
    $data = SIEM_trends($max);
    /*foreach ($data as $h => $v) {
    	$hours[] = $h;
    	$trend[] = ($v!="") ? $v : 0;
    }
    $max = count($hours);*/
    for ($i=$max-1; $i>=0; $i--) {
    	$h = gmdate("j G",$timetz-(3600*$i))."h";
    	$hours[] = preg_replace("/\d+ /","",$h);
    	$trend[] = ($data[$h]!="") ? $data[$h] : 0;
    }    
    $siem_url = "../forensics/base_qry_main.php?clear_allcriteria=1&time_range=day&time[0][0]=+&time[0][1]=>%3D&time[0][2]=".gmdate("m",$timetz)."&time[0][3]=".gmdate("d",$timetz)."&time[0][4]=".gmdate("Y",$timetz)."&time[0][5]=HH&time[0][6]=00&time[0][7]=00&time[0][8]=+&time[0][9]=AND&time[1][0]=+&time[1][1]=<%3D&time[1][2]=".gmdate("m",$timetz)."&time[1][3]=".gmdate("d",$timetz)."&time[1][4]=".gmdate("Y",$timetz)."&time[1][5]=HH&time[1][6]=59&time[1][7]=59&time[1][8]=+&time[1][9]=+&submit=Query+DB&num_result_rows=-1&time_cnt=2&sort_order=time_d&hmenu=Forensics&smenu=Forensics";
} elseif (GET("type")=="siemweek") { 
    $js = "analytics";
    $data = SIEM_trends_week();
    $max = 7;
    for ($i=$max-1; $i>=0; $i--) {
    	$d = gmdate("j M",$timetz-(86400*$i));
    	$hours[] = $d;
    	$trend[] = ($data[$d]!="") ? $data[$d] : 0;
    }
    /*foreach ($data as $h => $v) {
    	$hours[] = $h;
    	$trend[] = ($v!="") ? $v : 0;
    }
    $max = count($hours);*/
    $siem_url = "../forensics/base_qry_main.php?clear_allcriteria=1&time_range=day&time[0][0]=+&time[0][1]=>%3D&time[0][2]=MM&time[0][3]=DD&time[0][4]=".gmdate("Y",$timetz)."&time[0][5]=00&time[0][6]=00&time[0][7]=00&time[0][8]=+&time[0][9]=AND&time[1][0]=+&time[1][1]=<%3D&time[1][2]=MM&time[1][3]=DD&time[1][4]=".gmdate("Y",$timetz)."&time[1][5]=23&time[1][6]=59&time[1][7]=59&time[1][8]=+&time[1][9]=+&submit=Query+DB&num_result_rows=-1&time_cnt=2&sort_order=time_d&hmenu=Forensics&smenu=Forensics";
} elseif (GET("type")=="hids") { 
    $js = "analytics";
    list($data,$plugins) = SIEM_trends_week("ossec%");
    $max = 7;
    for ($i=$max-1; $i>=0; $i--) {
    	$d = gmdate("j M",$timetz-(86400*$i));
    	$hours[] = $d;
    	$trend[] = ($data[$d]!="") ? $data[$d] : 0;
    }    
    /*foreach ($data as $h => $v) {
    	$hours[] = $h;
    	$trend[] = ($v!="") ? $v : 0;
    }
    $max = count($hours);*/
    $siem_url = "../forensics/base_qry_main.php?clear_allcriteria=1&time_range=day&time[0][0]=+&time[0][1]=>%3D&time[0][2]=MM&time[0][3]=DD&time[0][4]=".gmdate("Y",$timetz)."&time[0][5]=00&time[0][6]=00&time[0][7]=00&time[0][8]=+&time[0][9]=AND&time[1][0]=+&time[1][1]=<%3D&time[1][2]=MM&time[1][3]=DD&time[1][4]=".gmdate("Y",$timetz)."&time[1][5]=23&time[1][6]=59&time[1][7]=59&time[1][8]=+&time[1][9]=+&submit=Query+DB&num_result_rows=-1&time_cnt=2&sort_order=time_d&hmenu=Forensics&smenu=Forensics&plugin=".$plugins;
} else {
    $js = "analytics_duo";
    $data = SIEM_trends();
    $data2 = Logger_trends();
    for ($i=$max-1; $i>=0; $i--) {
    	$h = gmdate("j G",$timetz-(3600*$i))."h";
    	$hours[] = preg_replace("/^\d+ /","",$h);
    	$trend[] = ($data[$h]!="") ? $data[$h] : 0;
    	$trend2[] = ($data2[$h]!="") ? $data2[$h] : 0;
    }
    $siem_url = "../forensics/base_qry_main.php?clear_allcriteria=1&time_range=day&time[0][0]=+&time[0][1]=>%3D&time[0][2]=".gmdate("m",$timetz)."&time[0][3]=".gmdate("d",$timetz)."&time[0][4]=".gmdate("Y",$timetz)."&time[0][5]=HH&time[0][6]=00&time[0][7]=00&time[0][8]=+&time[0][9]=AND&time[1][0]=+&time[1][1]=<%3D&time[1][2]=".gmdate("m",$timetz)."&time[1][3]=".gmdate("d",$timetz)."&time[1][4]=".gmdate("Y",$timetz)."&time[1][5]=HH&time[1][6]=59&time[1][7]=59&time[1][8]=+&time[1][9]=+&submit=Query+DB&num_result_rows=-1&time_cnt=2&sort_order=time_d&hmenu=Forensics&smenu=Forensics";
    $siem_url_y = "../forensics/base_qry_main.php?clear_allcriteria=1&time_range=day&time[0][0]=+&time[0][1]=>%3D&time[0][2]=".gmdate("m",$timetz-86400)."&time[0][3]=".gmdate("d",$timetz-86400)."&time[0][4]=".gmdate("Y",$timetz-86400)."&time[0][5]=HH&time[0][6]=00&time[0][7]=00&time[0][8]=+&time[0][9]=AND&time[1][0]=+&time[1][1]=<%3D&time[1][2]=".gmdate("m",$timetz-86400)."&time[1][3]=".gmdate("d",$timetz-86400)."&time[1][4]=".gmdate("Y",$timetz-86400)."&time[1][5]=HH&time[1][6]=59&time[1][7]=59&time[1][8]=+&time[1][9]=+&submit=Query+DB&num_result_rows=-1&time_cnt=2&sort_order=time_d&hmenu=Forensics&smenu=Forensics";    
}
//
$empty = true;
?>
<body scroll="no" style="overflow:hidden;font-family:arial;font-size:11px">		
	<table id="data" style="display:none">
        <tfoot>
            <tr>
            	<?	for ($i=0;$i<$max;$i++) {
            			$day = ($hours[$i]!="") ? $hours[$i] : "-";
            			echo "<th>$day</th>\n";
            		}
            	?>
            </tr>
        </tfoot>
        <tbody>
            <tr>
            	<?	for ($i=0;$i<$max;$i++) {
            			$value = ($trend[$i]!="") ? $trend[$i] : 0;
            			if ($value!=0) $empty=false;
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
            			if ($value!=0) $empty=false;
            			echo "<td>$value</td>\n"; 
            		}
            	?>
            </tr>
        </tbody>
    </table>
	
    <script language="javascript">
    	<?php if ($empty) echo "var max_aux=100;\n"; ?>    	
        logger_url = '../sem/index.php?start=<?=urlencode(gmdate("Y-m-d",$timetz)." HH:00:00")?>&end=<?=urlencode(gmdate("Y-m-d",$timetz)." HH:59:59")?>';
        logger_url_y = '../sem/index.php?start=<?=urlencode(gmdate("Y-m-d",$timetz-86400)." HH:00:00")?>&end=<?=urlencode(gmdate("Y-m-d",$timetz-86400)." HH:59:59")?>';        
        siem_url = '<?=$siem_url?>';
        siem_url_y = '<?=$siem_url_y?>';        
        h_now = '<?=gmdate("h",$timetz)?>'
    </script>
    
    <?php if (!empty($hours)) { ?>
	
	<script src="../js/raphael/<?=$js?>.js"></script>
	<script src="../js/raphael/popup.js"></script>		
	<div id="holder" style='height:100%;width:100%;margin:0;'></div>
	
	<? } else { ?>
	
	<table style="width:100%;margin-top:25px">
	<tr><td style="text-align:center"><img src="../pixmaps/shape.png" align="center" border="0"></td></tr>
	<tr><td style="text-align:center;color:gray"><?=_("No events found")?></td></tr>
	</table>
	
	<? } ?>
</body>
</html>

