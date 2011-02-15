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
Session::logcheck("MenuConfiguration", "ToolsUserLog");

require_once 'ossim_db.inc';
require_once 'classes/Util.inc';
require_once 'classes/Alarm.inc';
require_once 'classes/Log_action.inc';
require_once 'classes/Log_config.inc';
require_once 'classes/Security.inc';
 

/* number of logs per page */
$ROWS      = 50;
$order     = GET('order');
$inf       = GET('inf');
$sup       = GET('sup');
$user      = GET('user');
$code      = GET('code');
$action    = GET('action');
$date_from = GET('date_from');
$date_to   = GET('date_to');

ossim_valid($order, OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("order"));
ossim_valid($inf, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("inf"));
ossim_valid($sup, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("order"));
ossim_valid($user, OSS_USER, OSS_NULLABLE, 'illegal:' . _("hide_closed"));
ossim_valid($code, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("hide_closed"));
ossim_valid($action, OSS_ALPHA, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("action"));
ossim_valid($date_from, OSS_DIGIT, OSS_NULLABLE, "\-", 'illegal:' . _("Date from"));
ossim_valid($date_to, OSS_DIGIT, OSS_NULLABLE, "\-", 'illegal:' . _("Date to"));

if (ossim_error()) {
    die(ossim_error());
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?=_("User action logs")?> </title>
  <meta http-equiv="refresh" content="150"/>
  <META HTTP-EQUIV="Pragma" content="no-cache"/>
  <link rel="stylesheet" href="../style/style.css"/>
  <link rel="stylesheet" type="text/css" href="../style/datepicker.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/datepicker.js"></script>
  <? include ("../host_report_menu.php") ?>
  
  <script type="text/javascript">
    function calendar()
	{
		// CALENDAR
		<?php
			if ($date_from != "") {
				$aux = split("-",$date_from);
				$y = $aux[0]; $m = $aux[1]; $d = $aux[2];
			} else {
				$y = strftime("%Y", time() - ((24 * 60 * 60) * 30));
				$m = strftime("%m", time() - ((24 * 60 * 60) * 30));
				$d = strftime("%d", time() - ((24 * 60 * 60) * 30));
				$date_from = "$y-$m-$d";
			}
			if ($date_to != "") {
				$aux = split("-",$date_to);
				$y2 = $aux[0]; $m2 = $aux[1]; $d2 = $aux[2];
			} else {
				$y2 = date("Y"); $m2 = date("m"); $d2 = date("d");
				$date_to = "$y2-$m2-$d2";
			}

		?>
		var datefrom = new Date(<?php echo $y ?>,<?php echo $m-1 ?>,<?php echo $d ?>);
		var dateto = new Date(<?php echo $y2 ?>,<?php echo $m2-1 ?>,<?php echo $d2 ?>);

		$('#widgetCalendar').DatePicker({
			flat: true,
			format: 'Y-m-d',
			date: [new Date(datefrom), new Date(dateto)],
			calendars: 3,
			mode: 'range',
			starts: 1,
			onChange: function(formated) {
				if (formated[0]!=formated[1]) {
					var f1 = formated[0].split(/-/);
					var f2 = formated[1].split(/-/);
					document.getElementById('date_from').value = f1[0]+'-'+f1[1]+'-'+f1[2];
					document.getElementById('date_to').value = f2[0]+'-'+f2[1]+'-'+f2[2];
					$('#date_str').css('text-decoration', 'underline');
					$('#widgetCalendar').stop().animate({height: state ? 0 : $('#widgetCalendar div.datepicker').get(0).offsetHeight}, 500);
					$('#imgcalendar').attr('src',state ? '../pixmaps/calendar.png' : '../pixmaps/tables/cross.png');
					state = !state;
				}
			}
		});
		
		var state = false;
		$('#widget>a').bind('click', function(){
			$('#widgetCalendar').stop().animate({height: state ? 0 : $('#widgetCalendar div.datepicker').get(0).offsetHeight}, 500);
			$('#imgcalendar').attr('src',state ? '../pixmaps/calendar.png' : '../pixmaps/tables/cross.png');
			state = !state;
			return false;
		});
		
		$('#widgetCalendar div.datepicker').css('position', 'absolute');
	}
	
	
	function postload() { calendar(); }
	
    
	$(document).ready(function() {
		$("#log_list tbody tr:odd").css("background", "#EEEEEE");
		$('#view').bind('click', function() { document.forms['logfilter'].submit(); });
	});
		
		
  </script>
  
  <style type='text/css'>
	.paginator_top, .paginator_bottom {
		width:100%; 
		margin:auto; 
		text-align:center;
		padding: 5px 0px;
	}
	
	#filter {margin: 10px auto;}
	
	.nodata {padding: 10px 0px; color: gray; font-style: italic; font-size: 12px; text-align:center;}
	form {margin: 0px; padding: 0px;}
  </style>
  
</head>

<body>

 
<?php

include ("../hmenu.php");

/* connect to db */
$db = new ossim_db();
$conn = $db->connect();

/* delete logs*/
/*if($action==_("Delete All") && $_SESSION['_user']=="admin"){
    Log_action::delete_by_user_code($conn, $user, $code);
}
else if($action==_("Delete Selected") && $_SESSION['_user']=="admin"){
    foreach ($_GET as $key => $value){
        if(preg_match('/\|/', $key)) {
            $tmp = array();
            $tmp = explode("|", $key);
            Log_action::delete_by_date_info($conn,str_replace("#", " ",$tmp[0]),str_replace("_", " ",$tmp[1]));
        }
    }
}*/

if (empty($order)) $order = "date DESC";
if (empty($inf)) $inf = 0;
if (empty($sup)) $sup = $ROWS;

if ( $_SESSION['_user']=="admin" )
{
?>

    <!-- filter -->
	
    <form name="logfilter" id="logfilter" method="GET" action="<?php echo $_SERVER["SCRIPT_NAME"] ?>">
	
    <table align="center" id='filter'>
		<tr><th colspan="4"><?php echo gettext("Filter"); ?></th></tr>
		
        <tr>
			<th class="nobborder" style="text-align:center;"><?php echo gettext("Date range"); ?></th>
			<th class="nobborder" style="text-align:center;"><?php echo gettext("User"); ?></th>
			<th class="nobborder" colspan='2' style="text-align:center;"><?php echo gettext("Action"); ?></th>
		</tr>
		<tr>
			<td class="nobborder" style="padding:5px;">
				<table width="100%" class="transparent">
					<tr>
						<td class="nobborder" style="padding-left:10px">
							<?php echo _("From:"); ?> <input type="text" name="date_from" id="date_from" readonly='readonly' value="<?php echo $date_from ?>" style="width:80px;"/>
						</td>
						<td class="nobborder">
							&nbsp;&nbsp; <?php echo _("to:"); ?> &nbsp; <input type="text" name="date_to" id="date_to" readonly='readonly' value="<?php echo $date_to ?>" style="width:80px;"/>
							<div id="widget" style="display:inline;">
								<a href="javascript:;"><img src="../pixmaps/calendar.png" id='imgcalendar' border="0" align="absmiddle" style="padding-bottom:1px" /></a>
								<div id="widgetCalendar" style="position:absolute;top:11;z-index:10"></div>
							</div>
						</td>
					</tr>
				</table>
			</td>
		
			<td class="nobborder" style="padding:5px;">
				<select name="user">
					<?php 
					$selected = ( $user == "" ) ? "selected='selected'" : ""; 
					echo "<option $selected value=''>"._("All")."</option>";
									
					if ($session_list = Session::get_list($conn, "ORDER BY login"))
					{
						foreach($session_list as $session)
						{
							$login    = $session->get_login();
							$selected = ( $login == $user ) ? "selected='selected'" : "";
							echo "<option $selected value='$login'>$login</option>";
						}
					}
					?>
				</select>
			</td>
			
			<td class="nobborder" style="padding:5px;">
				<select name="code">
					<?php 
						$selected = ( $code == "" ) ? "selected='selected'" : ""; 
						echo "<option $selected value=''>"._("All")."</option>";
					 					
						if ($code_list = Log_config::get_list($conn, "ORDER BY descr"))
						{
							foreach($code_list as $code_log)
							{
								$code_aux = $code_log->get_code();
								$selected = ( $code_aux == $code ) ? "selected='selected'" : ""; 
								echo "<option $selected value='$code_aux'>[". sprintf("%02d", $code_aux) . "] " . preg_replace('|%.*?%|', "?", $code_log->get_descr())."</option>";
							 
							}
						}
					?>
				</select>
			</td>
			
			<td class='nobborder'><input type='button' id='view' class ='lbutton' value='<?php echo _("View")?>'/></td>
			
		</tr>  
   	</table>

	</form>
	
	
	<?php
	} 
	else 
		$user = $_SESSION['_user']; 
    
	if ( $_SESSION['_user'] == "admin" )
	{ 
	
		$delete_form = "<form method='get' action='user_action_log.php'>
							<center>
								<input type='hidden' name='user' value='$user'>
								<input type='hidden' name='code' value='$code'>
								<input class='button' name='action' type='submit' value='"._("Delete All")."'/>&nbsp;&nbsp;&nbsp;
								<input class='button' name='action' type='submit' value='"._("Delete Selected")."'/>
							</center><br/>";
	
		//echo $delete_form;
    } 
	?>
	
	<div class='paginator_top'>
		<?php
			
			$cfilter = "";
			$filter  = "";

			if (!empty($user))
				$filter = " and '$user' = log_action.login ";

			if (!empty($code)) 
				$filter.= " and '$code' = log_action.code";

			if (!empty($date_from) && !empty($date_to)) 
				$filter.= " AND (log_action.date BETWEEN  '$date_from 00:00:00' AND  '$date_to 23:59:59')";


			if ((!empty($code)) and (!empty($user))) 
				$cfilter = "where '" . $user . "' = log_action.login and '" . $code . "' = code";
			else
			{
				if (!empty($code)) 
					$cfilter = "where '" . $code . "' = code";
				
				if (!empty($user)) 
					$cfilter = "where '" . $user . "' = login";
				
			}
			
			if (!empty($date_from) && !empty($date_to)) 
			{
				if ( $cfilter!="" && preg_match('/where/', $cfilter) )
					$cfilter.= " AND (log_action.date BETWEEN  '$date_from 00:00:00' AND  '$date_to 23:59:59')";
				else
					$cfilter.= " WHERE (log_action.date BETWEEN  '$date_from 00:00:00' AND  '$date_to 23:59:59')";
			}
												
			/*
			* prev and next buttons
			*/
			$inf_link = $_SERVER["SCRIPT_NAME"] . "?order=$order" . "&sup=" . ($sup - $ROWS) . "&inf=" . ($inf - $ROWS). "&user=" . 
						$user . "&code=" . $code . "&date_from=" . $date_from . "&date_to=" . $date_to;
			$sup_link = $_SERVER["SCRIPT_NAME"] . "?order=$order" . "&sup=" . ($sup + $ROWS) . "&inf=" . ($inf + $ROWS). "&user=" . 
						$user . "&code=" . $code . "&date_from=" . $date_from . "&date_to=" . $date_to;
						
			$count = Log_action::get_count($conn, $cfilter);
			
			if ($inf >= $ROWS)
			{
				echo "<a href=\"$inf_link\">&lt;- ";
				printf(gettext("Prev %d") , $ROWS);
				echo "</a>";
			}
			if ($sup < $count)
			{
				echo "&nbsp;&nbsp;(";
				printf(gettext("%d-%d of %d") , $inf, $sup, $count);
				echo ")&nbsp;&nbsp;";
				echo "<a href=\"$sup_link\">";
				printf(gettext("Next %d") , $ROWS);
				echo " -&gt;</a>";
			} 
			else 
			{
				echo "&nbsp;&nbsp;(";
				printf(gettext("%d-%d of %d") , $inf, $count, $count);
				echo ")&nbsp;&nbsp;";
			}
			?>
	
	</div>
    
	<table width="100%" id='log_list'>
		<thead>
			<tr>
				<? if ($_SESSION['_user']=="admin") { ?>
					<!--<th>&nbsp;</th>-->
				<? } ?>
				<th>
					<a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php echo ossim_db::get_order("date", $order); ?><?=(($user!="")?"&user=$user":"")?><?=(($code!="")?"&code=$code":"")?>">
					<?php echo gettext("Date"); ?></a>
				</th>
				
				<th>
					<a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php echo ossim_db::get_order("login", $order); ?><?=(($user!="")?"&user=$user":"")?><?=(($code!="")?"&code=$code":"")?>">
					<?php echo gettext("User"); ?></a>
				</th>
				
				<th>
					<a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php echo ossim_db::get_order("ipfrom", $order); ?><?=(($user!="")?"&user=$user":"")?><?=(($code!="")?"&code=$code":"")?>">
					<?php echo gettext("Source IP"); ?></a>
				</th>
				
				<th>
					<a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php echo ossim_db::get_order("code", $order); ?><?=(($user!="")?"&user=$user":"")?><?=(($code!="")?"&code=$code":"")?>">
					<?php echo gettext("Code"); ?></a>
				</th>
				
				<th>
					<a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php echo ossim_db::get_order("info", $order); ?><?=(($user!="")?"&user=$user":"")?><?=(($code!="")?"&code=$code":"")?>">
					<?php echo gettext("Action"); ?></a>
				</th>
			</tr>
		</thead>
		
		<tbody>
		<?php
		$time_start = time();
		if ($log_list = Log_action::get_list($conn, $filter, "ORDER by $order", $inf, $sup))
		{
			foreach($log_list as $log)
			{
		?>		<tr>
					<? 
					if ( $_SESSION['_user']== "admin" )
					{
						$tmp=str_replace(" ","#",$log->get_date());
						//echo "<td><input type='checkbox' name='$tmp|".$log->get_info()."' value='yes'></td>";
					} 
					?>
					
					<td><?php echo $log->get_date();?></td>
					<td><?php echo $log->get_login(); ?></td>
					<td>
						<div id="<?php echo $log->get_from();?>;<?php echo $log->get_from(); ?>" class="HostReportMenu" style="display:inline"><?php echo $log->get_from(); ?></div>
					</td>
					<td><?php echo $log->get_code(); ?></td>
					<td><?php echo (preg_match('/^[A-Fa-f0-9]{32}$/',$log->get_info())) ? preg_replace('/./','*',$log->get_info()) : $log->get_info(); ?></td>
				</tr>
			<?php
			} /* foreach alarm_list */
		}
		else
			echo "<tr><td colspan='5' class='nodata nobborder'>"._("No data was found for this filter")."</td></tr>";
		?>
		</tbody>
	</table>
	
	<div class='paginator_bottom'>
		<?php
			if ($inf >= $ROWS)
			{
				echo "<a href=\"$inf_link\">&lt;- ";
				printf(gettext("Prev %d") , $ROWS);
				echo "</a>";
			}
			if ($sup < $count)
			{
				echo "&nbsp;&nbsp;(";
				printf(gettext("%d-%d of %d") , $inf, $sup, $count);
				echo ")&nbsp;&nbsp;";
				echo "<a href=\"$sup_link\">";
				printf(gettext("Next %d") , $ROWS);
				echo " -&gt;</a>";
			} 
			else
			{
				echo "&nbsp;&nbsp;(";
				printf(gettext("%d-%d of %d") , $inf, $count, $count);
				echo ")&nbsp;&nbsp;";
			}
		?>
	</div>
	
<? 
if ( $_SESSION['_user']=="admin" )
    echo "</form>" 
?>
	
	<br/>
	<?php
	$time_load = time() - $time_start;
	echo "[ " . gettext("Page loaded in") . " $time_load " . gettext("seconds") . " ]";
	$db->close($conn);
	?>

</body>
</html>
