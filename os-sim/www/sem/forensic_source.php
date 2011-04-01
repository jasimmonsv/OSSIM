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
if ($argv[1] != "") {
	$path_class = '/usr/share/ossim/include/:/usr/share/ossim/www/sem';
	ini_set('include_path', $path_class);
}
require_once ('classes/Session.inc');
if ($argv[1] == "") Session::logcheck("MenuEvents", "ControlPanelSEM");
require_once ('ossim_db.inc');
require_once ('classes/Util.inc');
require_once ('classes/Security.inc');
#require_once ('../graphs/charts.php');
require_once ('forensics_stats.inc');
$db = new ossim_db();
$conn = $db->connect();

$only_json = 0;
if ($argv[1] != "") {
	$gt = $argv[1];
	$cat = $argv[2];
	$only_json = 1;
} else {
	$gt = $_SESSION["graph_type"];
	$cat = $_SESSION["cat"];
}
$range = "";
$remote_data = array();

// REMOTE GRAPH MERGE
if ($_GET['ips'] != "") {
	$ip_to_name = array();
	foreach ($_SESSION['logger_servers'] as $name=>$ip) {
		$ip_to_name[$ip] = $name;
	}
	$ip_list = $_GET['ips'];
	ossim_valid($ip_list, OSS_DIGIT, OSS_PUNC, 'illegal:' . _("ip_list"));
	if (ossim_error()) {
	    die(ossim_error());
	}
	$cmd = "sudo ./fetchremote_graph.pl '$gt' '$cat' $ip_list";
	//echo $cmd;exit;
	$aux = explode("\n",`$cmd`);
	if (count($aux) > 2) {
		echo "<br><br><center><font style='font-family:arial;font-size:12px'>"._("An <b>error</b> has occured fetching remote data. Please <b>check logger configuration</b> in remote machines.")."</font></center>";
		?><script type="text/javascript">parent.document.getElementById('testLoading2').style.display='none';</script><?php
		exit;
	}
	$string = trim($aux[0]);
	$remote_data_aux = json_decode($string);
	$remote_data_aux2 = (array) $remote_data_aux;
	foreach ($remote_data_aux2 as $key=>$val) {
		$remote_data[$key] = (array) $val;
	}
	//print_r($remote_data);
// LOCAL GRAPH DATA
} else {
	if ($gt == "last_year") {
		$date_from = strftime("%Y-%m-%d %H:%M:%S", time() - ((24 * 60 * 60) * 365));
		$date_to = date("Y-m-d");
		$range = "month";
	}
	if ($gt == "last_month") {
		$date_from = strftime("%Y-%m-%d %H:%M:%S", time() - ((24 * 60 * 60) * 31));
		$date_to = date("Y-m-d");
		$range = "day";
	}
	if ($gt == "last_week") {
		$date_from = strftime("%Y-%m-%d %H:%M:%S", time() - ((24 * 60 * 60) * 7));
		$date_to = date("Y-m-d");
		$range = "day";
	}
	//if(!preg_match("/all|month|year|day/",$cat))
	//  $gt="all";
	switch ($gt) {
	    case "year":
	        $t_year = $cat;
	        break;
	
	    case "month":
	        $tmp = explode(",", $cat);
	        $t_year = str_replace(" ", "", $tmp[1]);
	        $t_month = str_replace(" ", "", $tmp[0]);
	        break;
	
	    case "day":
	        $tmp = explode(",", $cat);
	        $t_year = str_replace(" ", "", $tmp[1]);
	        $tmp = explode(" ", $tmp[0]);
	        $t_month = str_replace(" ", "", $tmp[0]);
	        $t_day = str_replace(" ", "", $tmp[1]);
	        if ($t_day<10) $t_day="0".$t_day;
	        break;
	}
	$t_month = date('m', strtotime("01 " . $t_month . " 2000"));
	//echo "year: $t_year, month: $t_month, day: $t_day";
	//Target allYears by default
	if ($gt == "") $gt = "allYears";
	$chart['link_data'] = array(
	    'url' => "javascript:parent.graph_by_date( _col_, _row_, _value_, _category_, _series_, '" . $t_year . "', '" . $t_month . "', '".$t_day."')",
	    'target' => "javascript"
	);
	$allYears = array();
	
	if ($range != "") {
		if ($gt == "last_year") $years = get_range_csv($date_from,$date_to,$range);
		if ($gt == "last_month") $years = get_range_csv($date_from,$date_to,$range);
		if ($gt == "last_week") $years = get_range_csv($date_from,$date_to,$range);
	} else {
		if ($gt == "all") $allYears = get_all_csv();
		if ($gt == "year") $years = get_year_csv($t_year);
		else $years = get_year_csv(date("Y"));
		if ($gt == "month") $months = get_month_csv($t_year, $t_month);
		else $months = get_month_csv(date("Y") , date("m"));
		if ($gt == "day") $days = get_day_csv($t_year, $t_month, $t_day);
	}
	$general = array();
	$generalV = array();
	$i = 0;
	$j = 0;
	
	$general[$j][$i++] = "NULL";
	if ($gt == "all" || $gt != "month" && $gt != "year" && $gt != "day" && $gt != "last_year" && $gt != "last_month" && $gt != "last_week") foreach($allYears as $k => $v) $general[$j][$i++] = $k;
	if ($gt == "year") foreach($years as $k => $v) $general[$j][$i++] = get_date_str($k + 1,"","",$t_year);
	if ($gt == "last_year")
		foreach($years as $y => $month_arr)
			foreach($month_arr as $k => $v) $general[$j][$i++] = get_date_str($k+1,"","",$y);
	
	if ($gt == "month") foreach($months as $k => $v) $general[$j][$i++] = get_date_str($t_month + 1, $k+1, "days", $t_year); //$general[$j][$i++] = get_date_str($t_month + 1, $k + 1, "days", $t_year);
	if ($gt == "last_month" || $gt == "last_week")
		foreach($years as $y => $month_arr)
		foreach($month_arr as $m => $days_arr)
			//foreach($days_arr as $k => $v) $general[$j][$i++] = get_date_str($m+1, $k+1, "days", $y);
	        foreach($days_arr as $k => $v) $general[$j][$i++] = get_date_str($m+1, $k, "days", $y);
	
	if ($gt == "day") foreach($days as $k => $v) $general[$j][$i++] = get_date_str("", $k, "hours");
	
	for ($a = 1; $a < 5; $a++) {
	    $i = 0;
	    switch ($a) {
	        case 1:
	            //$general[$a][$i++]="Year stats";
	            $general[$a][$i++] = "";
	            break;
	
	        case 2:
	            //$general[$a][$i++]="Month stats";
	            $general[$a][$i++] = "";
	            break;
	
	        case 3:
	            //$general[$a][$i++]="Day stats";
	            $general[$a][$i++] = "";
	            break;
	
	        case 4:
	            //$general[$a][$i++]="Hour stats";
	            $general[$a][$i++] = "";
	            break;
	    }
	    if ($gt == "all" || $gt != "month" && $gt != "year" && $gt != "day" && $gt != "last_year" && $gt != "last_month" && $gt != "last_week") foreach($allYears as $k => $v)
			if ($a == 1) $general[$a][$i++] = $v; //number_format($v,0,',','.');
			else $general[$a][$i++] = "";
	    if ($gt == "year") foreach($years as $k => $v)
			if ($a == 2) $general[$a][$i++] = $v; //number_format($v,0,',','.');
			else $general[$a][$i++] = "";
		if ($gt == "last_year")
			foreach($years as $y => $month_arr)
				foreach($month_arr as $k => $v)
					if ($a == 2) $general[$a][$i++] = $v; //number_format($v,0,',','.');
					else $general[$a][$i++] = "";
	    if ($gt == "month")
			if ($a == 3) foreach($months as $k => $v) $general[$a][$i++] = $v; //number_format($v,0,',','.');
			else $general[$a][$i++] = "";
	    if ($gt == "last_month" || $gt == "last_week")
			foreach($years as $y => $month_arr)
			foreach($month_arr as $m => $days_arr)
				foreach($days_arr as $k => $v)
					if ($a == 3) $general[$a][$i++] = $v; //number_format($v,0,',','.');
					else $general[$a][$i++] = "";
		
	    if ($gt == "day")
			if ($a == 4) foreach($days as $k => $v) $general[$a][$i++] = $v; //number_format($v,0,',','.');
			else $general[$a][$i++] = "";
	}
	//print_r($general);
	$generalV = $general;
	foreach ($generalV as $k=>$v) {
		foreach ($v as $k1=>$v1) {
			if ($v1>0) { $generalV[$k][$k1] = Util::number_format_locale($v1,0);}
		}
	}
	
	$chart['chart_data'] = $general;
	$chart['chart_value_text'] = $generalV;
}

if ($gt == "all" || $gt != "month" && $gt != "year" && $gt != "day" && $gt != "last_year" && $gt != "last_month" && $gt != "last_week") $a = 1;
elseif ($gt == "year" || $gt == "last_year") $a = 2;
elseif ($gt == "month" || $gt == "last_month" || $gt == "last_week") $a = 3;
elseif ($gt == "day") $a = 4;

// IF CALLED BY PROMPT ONLY PRINT DATA (For remote logger graph merge)
if ($only_json) {
	$json = array('chart_data' => $chart['chart_data'],'chart_value_text' => $chart['chart_value_text']);
	echo json_encode($json);
	exit;
}
//print_r($chart['chart_data']);
//SendChartData($chart);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<HTML>
<HEAD>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<META HTTP-EQUIV="pragma" CONTENT="no-cache">
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
<TITLE><?=_("Forensics Console")?> : <?=_("Query Results")?></TITLE>
<style type="text/css">
BODY {
    color: #111111;
    font-family: arial, helvetica, sans-serif;
    font-size: 12px;
    margin: 0px;
    padding: 0px;
}
a { color: #555555; text-decoration:none; }
a:hover { text-decoration: underline; }
.tickLabel { font-size:11px; font-weight:bold; color:#111111; }
.tooltipLabel { font-size:11px; color:#111111; }
</style>
<script src="../js/jquery-1.3.2.min.js" type="text/javascript"></script>
<!--[if IE]><script language="javascript" type="text/javascript" src="../js/excanvas.pack.js"></script><![endif]-->
<script src="../js/jquery.flot.pack.js" type="text/javascript"></script>
<script src="../js/jquery.flot.stack.js" type="text/javascript"></script>
</HEAD>
<BODY onLoad="parent.document.getElementById('testLoading2').style.display='none'">
<div id="plotareaglobal" style="text-align:center;margin:5px 0px 0px 20px;padding:0px;display:none;"></div>
<script>
<?  flush(); sleep(1);
    $row = ($gt=="year" || $gt=="last_year") ? 2 : (($gt=="month" || $gt=="last_month" || $gt=="last_week") ? 3 : ($gt=="day" ? 4 : 1));
    $salto = ($gt=="month" || $gt=="last_month") ? 4 : (($gt=="day") ? 2 : 1);
    $with = ($gt=="month") ? 1 : (($gt=="day") ? 0 : 0);
    if ($_GET['ips'] == "") {
    	array_shift($chart['chart_data'][0]);
    	array_shift($chart['chart_data'][$a]);
    } else {
    	foreach ($remote_data as $ip=>$arr) {
    		array_shift($remote_data[$ip]['chart_data'][0]);
    		array_shift($remote_data[$ip]['chart_data'][$a]);
    	}
    }
?>
	<?php if ($_GET['ips'] != "") { ?>
	var links = []; <? $flag = 0; foreach ($remote_data as $ip=>$arr) { if($flag) continue; $flag = 1; foreach ($arr['chart_data'][0] as $i => $tick) echo "    links[".$i."] = '$tick';\n"; } ?>
	<?php } else { ?>
	var links = []; <? foreach ($chart['chart_data'][0] as $i => $tick) echo "    links[".$i."] = '$tick';\n"; ?>
	<?php } ?>

    function showTooltip(x, y, row, col, contents) {
        var year_str = "";
		if (links[row].match(/..., \d\d\d\d/)) {
			var aux = links[row].split(", ");
			year_str = aux[1];
		}
		$('<div id="tooltip" class="tooltipLabel" onclick="parent.graph_by_date( \''+col+'\', <?=$row?>, 0, \''+links[row]+'\', \'\', \'<?if ($t_year != "") echo $t_year; else { ?>'+year_str+'<? } ?>\',\'<?=$t_month?>\')">'+links[row]+': <a href="javascript:parent.graph_by_date( \''+col+'\', <?=$row?>, 0, \''+links[row]+'\', \'\', \'<?if ($t_year != "") echo $t_year; else { ?>'+year_str+'<? } ?>\', \'<?=$t_month?>\', \'<?=$t_day?>\')" style="font-size:10px;">' + contents + '</a></div>').css( {
            position: 'absolute',
            display: 'none',
            top: y - 10,
            left: x + 5,
            border: '1px solid #ADDF53',
            padding: '5px 7px 5px 7px',
            'background-color': '#CFEF95',
            opacity: 0.80
        }).appendTo("body").fadeIn(200);
    }
    function formatNmb(nNmb){
        var sRes = ""; 
        for (var j, i = nNmb.length - 1, j = 0; i >= 0; i--, j++)
            sRes = nNmb.charAt(i) + ((j > 0) && (j % 3 == 0)? ",": "") + sRes;
        return sRes;
    }
    
    $(document).ready(function(){
		<?php if ($_GET['ips'] != "") { ?>
		var options = {
	        series: {stack: 0,
	                 lines: {show: false, steps: false },
	                 bars: {show: true, barWidth: 0.9, align: 'center'}
	                 },       
	        xaxis: { tickDecimals:0, ticks: [<? $flag = 0; foreach ($remote_data as $ip=>$arr) { if($flag) continue; $flag = 1; foreach ($arr['chart_data'][0] as $i=>$tick) { if ($i > 0) echo ","; if ($i % $salto == $with) { ?>[<?=$i?>,"<?=$tick?>"]<? } else { ?>[<?=$i?>,""]<? } ?><? } }?>] },
	        grid: { color: "#8E8E8E", labelMargin:3, borderWidth:2, backgroundColor: "#EDEDED", tickColor: "#D2D2D2", hoverable:true, clickable:true}, shadowSize:1
	    };
		var data = [
            <?php foreach ($remote_data as $ip=>$arr) { ?>
            {color: "#<?php echo $_SESSION['logger_colors'][$ip_to_name[$ip]]['bcolor']; ?>", label: '<?php echo $ip_to_name[$ip] ?>', data: [<? foreach ($arr['chart_data'][$a] as $i=>$tick) { if ($i > 0) echo ","; ?>[<?=$i?>,<?=$tick?>]<? } ?>]},
            <?php } ?>
        ];
		<?php } else { ?>
        var options = {
            bars: {
                show: true,
                barWidth: 0.9, // in units of the x axis
	            fill: true,
                fillColor: null,
                align: "center" // or "center"
            },
			points: { show:false, radius: 2 },
            legend: { show: false },
            yaxis: { autoscale:true },
            xaxis: { tickDecimals:0, ticks: [<? foreach ($chart['chart_data'][0] as $i=>$tick) { if ($i > 0) echo ","; if ($i % $salto == $with) { ?>[<?=$i?>,"<?=$tick?>"]<? } else { ?>[<?=$i?>,""]<? } ?><? } ?>] },
            grid: { color: "#8E8E8E", labelMargin:3, borderWidth:2, backgroundColor: "#EDEDED", tickColor: "#D2D2D2", hoverable:true, clickable:true}, shadowSize:1 
        };
        var data = [{
            color: "rgb(173,223,83)",
            label: "Events",
            lines: { show: false, fill: true},
            data: [<? foreach ($chart['chart_data'][$a] as $i=>$tick) { if ($i > 0) echo ","; ?>[<?=$i?>,<?=$tick?>]<? } ?>]
        }];
        <?php } ?>
        var plotarea = $("#plotareaglobal");
        plotarea.css("height", 150);
        plotarea.css("width", (window.innerWidth || document.body.clientWidth)-40);
        
        plotarea.toggle();
        $.plot( plotarea , data, options );
        var previousPoint = null;
        $("#plotareaglobal").bind("plothover", function (event, pos, item) {
            if (item) {
                if (previousPoint != item.datapoint) {
                    previousPoint = item.datapoint;
                    $("#tooltip").remove();
                    var x = item.datapoint[0].toFixed(0), y = item.datapoint[1].toFixed(0);
                    showTooltip(item.pageX, item.pageY, x, y, formatNmb(y)+" "+item.series.label);
                }
            }
            else {
                $("#tooltip").remove();
                previousPoint = null;
            }
        });
		$("#plotareaglobal").bind("plotclick", function (event, pos, item) {
			if (item) {
				var x = item.datapoint[0].toFixed(0), y = item.datapoint[1].toFixed(0);
				var year_str = "";
				if (links[x].match(/..., \d\d\d\d/)) {
					var aux = links[x].split(", ");
					year_str = aux[1];
				}
				parent.graph_by_date(y, <?=$row?>, 0, links[x], '', <?if ($t_year != "") echo "'".$t_year."'"; else { ?>year_str<? } ?>, '<?=$t_month?>', '<?=$t_day?>');
            }
		});
    });
</script>
</body>
</html>