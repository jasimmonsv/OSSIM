<?php
/**
* Class and Function List:
* Function list:
* Classes list:
*/
/*******************************************************************************
** OSSIM Forensics Console
** Copyright (C) 2009 OSSIM/AlienVault
** Copyright (C) 2004 BASE Project Team
** Copyright (C) 2000 Carnegie Mellon University
**
** (see the file 'base_main.php' for license details)
**
** Built upon work by Roman Danyliw <rdd@cert.org>, <roman@danyliw.com>
** Built upon work by the BASE Project Team <kjohnson@secureideas.net>
*/

require_once ('classes/Session.inc');
require_once ('classes/Security.inc');

Session::logcheck("MenuEvents", "EventsForensics");
require_once 'ossim_db.inc';

switch ( GET('resolution')){
	
	case "s":
		$iu1 = "Timeline.DateTime.SECOND";
		$iu2 = "Timeline.DateTime.MINUTE";
		$intpx  = 20;
	break;
	
	case "m":
		$iu1 = "Timeline.DateTime.MINUTE";
		$iu2 = "Timeline.DateTime.HOUR";
		$intpx  = 20;
	break;
	
	case "h":
		$iu1 = "Timeline.DateTime.HOUR";
		$iu2 = "Timeline.DateTime.DAY";
		$intpx  = 50;
	break;
	
	case "d":
		$iu1 = "Timeline.DateTime.DAY";
		$iu2 = "Timeline.DateTime.MONTH";
		$intpx  = 50;
	break;
	
	default:
		$iu1 = "Timeline.DateTime.MINUTE";
		$iu2 = "Timeline.DateTime.HOUR";

}



$db = new ossim_db();
$conn = $db->connect();

$sql = "SELECT * FROM `datawarehouse`.`report_data` WHERE id_report_data_type = 33 AND USER = ? AND dataI1 = 49";

$user = $_SESSION['_user'];
settype($user, "string");
$params = array(
	$user
);

			
if (!$rs = $conn->Execute($sql, $params)) {
	print 'Error: ' . $conn->ErrorMsg() . '<br/>';
	exit;
}
else
{
	$date = explode (" ",  $rs->fields['dataV2']);
	$d = explode("-", $date[0]);
	$t = explode(":", $date[1]);

	$timestamp = mktime($t[0], $t[1], $t[2], $d[1], $d[2], $d[0]);
	$format_date = date("M d Y G:i:s", $timestamp)." GMT";

	$init_date = $format_date;
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
<title>Forensics Timeline</title>
<style type="text/css">

body {font-family: Arial, Verdana, Helvetica, sans serif; font-size: 12px;}

.txt_desc { text-align: center; width:98%; margin:auto; padding-bottom:20px;}

a{text-decoration: none; color: #4e487d; font-size: 12px;}
a:hover {text-decoration: underline;}

.timeline-default {
    font-size: 8pt;
    border: 1px solid #aaa;
}
.timeline-event-label { padding-left:5px; }

.timeline-event-bubble-time {display: none;}

.df { color: #4e487d; text-align: center;}

/*#tm { overflow-x:hidden; overflow-y:scroll;}*/


</style>
<!--<script type='text/javascript' src="http://static.simile.mit.edu/timeline/api-2.3.0/timeline-api.js?bundle=true" type="text/javascript"></script>-->
<script>
 Timeline_ajax_url="/ossim/forensics/js/timeline_ajax/simile-ajax-api.js";
 Timeline_urlPrefix='/ossim/forensics/js/timeline_js/';       
 Timeline_parameters='bundle=true';
</script>
<script type='text/javascript' src="/ossim/forensics/js/timeline_js/timeline-api.js" type="text/javascript"></script>
<script type='text/javascript' src="/ossim/forensics/js/jquery-1.3.2.min.js" type="text/javascript"></script>
 
<script type='text/javascript'>
var tl = null;
	function onLoad() {
	$('.timeline-message-container').css('display', 'block');
	var eventSource = new Timeline.DefaultEventSource();
	
	var date = "<?=$init_date?>";
	
	var bandInfos = [
	Timeline.createBandInfo({
		eventSource:    eventSource,
		date:           date,
		width:          "80%", 
		intervalUnit:   <?=$iu1?>, 
		intervalPixels: <?=$intpx?>
	}),
	Timeline.createBandInfo({
		overview:       true,
		eventSource:    eventSource,
		date:           date,
		width:          "20%", 
		intervalUnit:   <?=$iu2?>, 
		intervalPixels: 500
	})
	];
	bandInfos[1].syncWith = 0;
	bandInfos[1].highlight = true;
	tl = Timeline.create(document.getElementById("tm"), bandInfos);
	Timeline.loadXML("base_timeline_xml.php", function(xml, url) { eventSource.loadXML(xml, url); });
	
	$('.timeline-message-container').css('display', 'none');
	}

	var resizeTimerID = null;
	function onResize() {
		if (resizeTimerID == null) {
			resizeTimerID = window.setTimeout(function() {
				resizeTimerID = null;
				tl.layout();
			}, 500);
		}
	}
</script>      

</head>
<body onload="onLoad();" onresize="onResize();">
<div id="tm" class="timeline-default" style="height:400px;margin:0px;padding:0px"></div>

<div class="timeline-message-container" style='display: block'>
	<div style="height: 33px; background: url(js/timeline_ajax/images/message-top-left.png) no-repeat scroll left top transparent; padding-left: 44px;">
		<div style="height: 33px; background: url(js/timeline_ajax/images/message-top-right.png) no-repeat scroll right top transparent;"></div>
	</div>
	<div style="background: url(js/timeline_ajax/images/message-left.png) repeat-y scroll left top transparent; padding-left: 44px;">
		<div style="background: url(js/timeline_ajax/images/message-right.png) repeat-y scroll right top transparent; padding-right: 44px;">
			<div class="timeline-message"><img src="js/timeline_js/images/progress-running.gif"> Loading...</div>
		</div>
	</div>
	<div style="height: 55px; background: url(js/timeline_ajax/images/message-bottom-left.png) no-repeat scroll left bottom transparent; padding-left: 44px;">
		<div style="height: 55px; background: url(js/timeline_ajax/images/message-bottom-right.png) no-repeat scroll right bottom transparent;"></div>
	</div>
</div>


</body>
</html>
