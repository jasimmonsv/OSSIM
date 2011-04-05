<?php
require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
require_once ('classes/Util.inc');
require_once ('sensor_filter.php');
Session::logcheck("MenuControlPanel", "ControlPanelExecutive");

$type = GET("type");
ossim_valid($type, 'ticketsByPriority','ticketsClosedByMonth','ticketResolutionTime','openedTicketsByUser','ticketStatus','ticketTypes', 'illegal:' . _("type"));

if (ossim_error()) {
    die(ossim_error());
}

$data  = null;
$links = ''; 
$h     = 250;  // Graph Height

$db    = new ossim_db();
$conn  = $db->connect();

$user  = Session::get_session_user();

//Users that I can see
$users = Session::get_users_to_assign($conn);
			
foreach ($users as $k => $v)
	$my_users[$v->get_login()] = ( strlen($v->get_login()) > 28 ) ? substr($v->get_login(), 0, 25)."[...]" : $v->get_login();

	
//Entities that I can see
$entities = Session::get_entities_to_assign($conn);

foreach ($entities as $k => $v)
{
	$my_entities_keys[$k]  = $k;
	$my_entities_names[$k] =  ( strlen($v) > 28 ) ? substr($v, 0, 25)."[...]" : $v;
}


// Types
switch($type){
	case 'ticketStatus':
		
		$type_graph = 'pie';
		$user       = Session::get_session_user();
		
		if ( !Session::am_i_admin() )
		{
			
			$entities_and_users = array_merge($my_users, $my_entities_keys);
			$admin_where = ( !empty($entities_and_users) ) ?"IN ('".implode("','", $entities_and_users)."')" : "IN('0')";
						
			$query = "SELECT DISTINCT SQL_CALC_FOUND_ROWS incident.* 
					  FROM incident 
					  LEFT JOIN incident_ticket ON incident_ticket.incident_id = incident.id, users, incident_subscrip 
					  WHERE incident_subscrip.incident_id=incident.id 
					  AND users.login = incident_subscrip.login 
					  AND (
						incident_ticket.users $admin_where 
						OR incident_ticket.in_charge $admin_where 
						OR incident_ticket.transferred $admin_where 
						OR users.login $admin_where
					)
					ORDER BY date";
		}
		else
			$query = "SELECT DISTINCT SQL_CALC_FOUND_ROWS incident.* FROM incident ";
		
				
		$rs     = &$conn->Execute($query);
		$status = array("Open" => 0,"Closed" => 0);
		while (!$rs->EOF)
		{
			$status[$rs->fields['status']]++;
			$rs->MoveNext();
		}
		
		if(!empty($status))
		{
			foreach($status as $value => $key){
				$data[] = "['".$value."',".$key."]";
			}
			
			$links = "'../incidents/index.php?&status=open&hmenu=Tickets&smenu=Tickets','../incidents/index.php?&status=closed&hmenu=Tickets&smenu=Tickets'";
		}
		else
			$data[] = "['Open',0],['Closed',0]";
			
		if ( is_array($data) )
			$data = implode(",", $data);
			
			
		$colors = '"#E9967A","#9BC3CF"';
		
		
		break;
	
	case 'ticketTypes':
		
		$type_graph = 'pie';
		
		if ( !Session::am_i_admin() )
		{
			$entities_and_users = array_merge($my_users, $my_entities_keys);
			$admin_where_1      = ( !empty($entities_and_users) ) ? " WHERE i.in_charge IN ('".implode("','", $entities_and_users)."')" : "IN('0')";
			$admin_where_2      = ( !empty($entities_and_users) ) ? "AND t.in_charge IN ('".implode("','", $entities_and_users)."')" : "IN('0')";
		}
		else
			$admin_where_1 = $admin_where_2 = "";	
		
		
		
		$query = "	SELECT u.ref, count(*) as num 
						FROM(
						(
							SELECT i.id,i.ref 
							FROM incident i 
							$admin_where_1
						)
						UNION(
							SELECT i.id,i.ref 
							FROM incident i, incident_ticket t 
							WHERE i.id=t.incident_id $admin_where_2
							)
						) u 
					GROUP BY u.ref ORDER by num desc";
		
		if (!$rs = &$conn->Execute($query)) 
		{
			print $conn->ErrorMsg();
			exit();
		}
		
		while (!$rs->EOF)
		{
			$data.="['".$rs->fields["ref"]."',".$rs->fields["num"]."],";
			$rs->MoveNext();
		}
		
		break;
	
	case 'openedTicketsByUser':
		
		$type_graph  = 'pie';
		
		if ( !Session::am_i_admin() )
		{
			$entities_and_users = array_merge($my_users, $my_entities_keys);
			$admin_where = " AND in_charge IN ('".implode("','", $entities_and_users)."')";
		}
		else
			$admin_where = "";	
		
		$query  = "SELECT in_charge, count(*) as num FROM incident where status='Open'$admin_where GROUP BY in_charge ORDER BY num desc";
			
		if ( !$rs = &$conn->Execute($query) ) 
		{
			print $conn->ErrorMsg();
			exit();
		}
		
		$conf    = $GLOBALS["CONF"];
		$version = $conf->get_conf("ossim_server_version", FALSE);
		$pro     = preg_match("/pro|demo/i",$version);

		while (!$rs->EOF)
		{
			if( $pro && preg_match("/^\d+$/",$rs->fields["in_charge"])) 
				$data[] = "['".$my_entities_names[$rs->fields["in_charge"]]."',".$rs->fields["num"]."]";
			else 
				$data[] = "['".$rs->fields["in_charge"]."',".$rs->fields["num"]."]";
			
			$rs->MoveNext();
		}
		
		if ( is_array($data) )
			$data = implode(",", $data);
			
		break;
	
	case 'ticketResolutionTime':
		
		require_once ('classes/Incident.inc');
		
		$ttl_groups = array();
		
		$type_graph = 'bar';
				
		// Gets tags
        $tags    = array();
        $t_sql   = "SELECT incident_tag.tag_id, incident_tag.incident_id FROM incident_tag";
        
		if (!$rs = $conn->Execute($t_sql)) 
			die($conn->ErrorMsg());
        while (!$rs->EOF) 
		{
            $tags[$rs->fields["incident_id"]][] = $rs->fields["tag_id"];
            $rs->MoveNext();
        }
		
		if ( !Session::am_i_admin() )
		{
			$entities_and_users = array_merge($my_users, $my_entities_keys);
			$admin_where = ( !empty($entities_and_users) ) ?"IN ('".implode("','", $entities_and_users)."')" : "IN('0')";
			
			
			$query = "SELECT DISTINCT SQL_CALC_FOUND_ROWS incident.* 
					  FROM incident 
					  LEFT JOIN incident_ticket ON incident_ticket.incident_id = incident.id, users, incident_subscrip 
					  WHERE incident_subscrip.incident_id=incident.id
					  AND incident.status = 'Closed'					  
					  AND users.login = incident_subscrip.login 
					  AND (
						incident_ticket.users $admin_where 
						OR incident_ticket.in_charge $admin_where 
						OR incident_ticket.transferred $admin_where 
						OR users.login $admin_where
					)
					ORDER BY date";
			
		}
		else
			$query = "SELECT DISTINCT SQL_CALC_FOUND_ROWS incident.* FROM incident WHERE incident.status = 'Closed' ";
		
		
		if ( !$rs = &$conn->Execute($query) ) 
		{
			print $conn->ErrorMsg();
			exit();
		}
			
		
		while (!$rs->EOF) {
           
            $life_time_diff = strtotime($rs->fields["date"]) - strtotime($rs->fields["last_update"]);
            $itags          = (isset($tags[$rs->fields["id"]])) ? $tags[$rs->fields["id"]] : array();
            $list[] = new Incident($rs->fields["id"], $rs->fields["title"], $rs->fields["date"], $rs->fields["ref"], $rs->fields["type_id"], $rs->fields["submitter"], $rs->fields["priority"], $rs->fields["in_charge"], $rs->fields["status"], $rs->fields["last_update"], $itags, $life_time_diff, $rs->fields["event_start"], $rs->fields["event_end"]);
            $rs->MoveNext();
        }
				
		
		$ttl_groups = array("1"=>0, "2"=>0, "3"=>0, "4"=>0, "5"=>0, "6"=>0);
		
		$total_days = 0;
		$day_count;
		
		
		foreach ($list as $incident) 
		{
				$ttl_secs = $incident->get_life_time('s');
				$days = round($ttl_secs/60/60/24);
				$total_days += $days;
				$day_count++;
				if ($days < 1) $days = 1;
				if ($days > 6) $days = 6;
				@$ttl_groups[$days]++;
		}

		$datay  = array_values($ttl_groups);

		$labelx = array( _("1 Day"), _("2 Days"), _("3 Days"), _("4 Days"), _("5 Days"), _("6+ Days"));
		break;
	
	case 'ticketsClosedByMonth':
		
		$type_graph = 'barCumulative';
		$query      = array();
		
		if ( !Session::am_i_admin() )
		{
			$entities_and_users = array_merge($my_users, $my_entities_keys);
			$user_where         = ( !empty($entities_and_users) ) ?"AND status='Closed' AND in_charge IN ('".implode("','", $entities_and_users)."')" : "IN('0')";
		}
		else
			$user_where         = " AND status='Closed'";
		
		
		$year  = date("Y");
		
		$query = 'SELECT * FROM
			(SELECT count(*) as "Jan" FROM incident where date > "'.$year.'-01-01 00:00:00" and ref="%event_type%" and date < "'.$year.'-02-01 00:00:00"'.$user_where.') as enero,
			(SELECT count(*) as "Feb" FROM incident where date > "'.$year.'-02-01 00:00:00" and ref="%event_type%" and date < "'.$year.'-03-01 00:00:00"'.$user_where.') as febrero,
			(SELECT count(*) as "Mar" FROM incident where date > "'.$year.'-03-01 00:00:00" and ref="%event_type%" and date < "'.$year.'-04-01 00:00:00"'.$user_where.') as marzo,
			(SELECT count(*) as "Apr" FROM incident where date > "'.$year.'-04-01 00:00:00" and ref="%event_type%" and date < "'.$year.'-05-01 00:00:00"'.$user_where.') as abril,
			(SELECT count(*) as "May" FROM incident where date > "'.$year.'-05-01 00:00:00" and ref="%event_type%" and date < "'.$year.'-06-01 00:00:00"'.$user_where.') as mayo,
			(SELECT count(*) as "Jun" FROM incident where date > "'.$year.'-06-01 00:00:00" and ref="%event_type%" and date < "'.$year.'-07-01 00:00:00"'.$user_where.') as junio,
			(SELECT count(*) as "Jul" FROM incident where date > "'.$year.'-07-01 00:00:00" and ref="%event_type%" and date < "'.$year.'-08-01 00:00:00"'.$user_where.') as julio,
			(SELECT count(*) as "Aug" FROM incident where date > "'.$year.'-08-01 00:00:00" and ref="%event_type%" and date < "'.$year.'-09-01 00:00:00"'.$user_where.') as agosto,
			(SELECT count(*) as "Sep" FROM incident where date > "'.$year.'-09-01 00:00:00" and ref="%event_type%" and date < "'.$year.'-10-01 00:00:00"'.$user_where.') as septiembre,
			(SELECT count(*) as "Oct" FROM incident where date > "'.$year.'-10-01 00:00:00" and ref="%event_type%" and date < "'.$year.'-11-01 00:00:00"'.$user_where.') as octubre,
			(SELECT count(*) as "Nov" FROM incident where date > "'.$year.'-11-01 00:00:00" and ref="%event_type%" and date < "'.$year.'-12-01 00:00:00"'.$user_where.') as noviembre,
			(SELECT count(*) as "Dec" FROM incident where date > "'.$year.'-12-01 00:00:00" and ref="%event_type%" and date < "'.$year.'-12-31 23:59:59"'.$user_where.') as diciembre;';

	
						
		$legend       = array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");
		$event_types  = array("Alarm","Alert","Event","Metric","Anomaly","Vulnerability");
		$final_values = array();
		
		foreach($event_types as $event_type)
		{
			$values           = array();
					
			$query_to_execute = preg_replace("/%event_type%/", $event_type, $query);
			
			if ( !$rs = &$conn->Execute($query_to_execute) ) 
			{
				print $conn->ErrorMsg();
				exit();
			}
			
			while (!$rs->EOF)
			{
				
				$values = array (   $rs->fields["Jan"],
									$rs->fields["Feb"],
									$rs->fields["Mar"],
									$rs->fields["Apr"],
									$rs->fields["May"],
									$rs->fields["Jun"],
									$rs->fields["Jul"],
									$rs->fields["Aug"],
									$rs->fields["Sep"],
									$rs->fields["Oct"],
									$rs->fields["Nov"],	
									$rs->fields["Dec"]													
								);
				$rs->MoveNext();
			}
			
			$final_values[$event_type] = implode(",", $values);
			$label[] = "{label: '".$event_type."'}";
			
		}
				
		break;
	
	case 'ticketsByPriority':
		
		$type_graph  = 'pie';
				
		if ( !Session::am_i_admin() )
		{
			$entities_and_users = array_merge($my_users, $my_entities_keys);
			$admin_where = ( !empty($entities_and_users) ) ?" IN ('".implode("','", $entities_and_users)."')" : "IN('0')";
			
			$query = "SELECT DISTINCT SQL_CALC_FOUND_ROWS incident.* 
					  FROM incident 
					  LEFT JOIN incident_ticket ON incident_ticket.incident_id = incident.id, users, incident_subscrip 
					  WHERE incident_subscrip.incident_id=incident.id
					  AND incident.status = 'Open'					  
					  AND users.login = incident_subscrip.login 
					  AND (
						incident_ticket.users $admin_where 
						OR incident_ticket.in_charge $admin_where 
						OR incident_ticket.transferred $admin_where 
						OR users.login $admin_where
					)
					GROUP BY incident.priority;";
				
		}
		else
			$query = "SELECT in_charge, priority, count(*) as num FROM incident where status='Open' GROUP BY priority;";
			
			
		if (!$rs = &$conn->Execute($query)) 
		{
			print $conn->ErrorMsg();
			exit();
		}
		
		$conf    = $GLOBALS["CONF"];
		$version = $conf->get_conf("ossim_server_version", FALSE);
		
		$temp_colors = array(   "0"  => "#FFFEAF",
								"1"  => "#FFFD00",
								"2"  => "#FFE200",
								"3"  => "#FFCE00",
								"4"  => "#FFB900",
								"5"  => "#FFA500",
								"6"  => "#FF8C00",
								"7"  => "#FF7700",
								"8"  => "#DA0000",
								"9"  => "#BB0000",
								"10" => "#8B0000",								
		
							);
							
		while (!$rs->EOF)
		{
			$priority = $rs->fields["priority"];
			$data[]   = "['"._("Priority")." ".$rs->fields["priority"]."',".$rs->fields["num"]."]";
			$colors[$temp_colors[$priority]] = $temp_colors[$priority];
			
			$rs->MoveNext();
		}
		
		$colors = "'".implode("','", $colors)."'";
		
		if ( is_array($data) )
			$data = implode(",", $data);
		
		
		break;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title><?php if($type_graph=='pie'){ echo 'Pie'; }elseif($type_graph=='bar'){ echo 'Bar';}?> <?php echo _("Charts")?></title>
	<!--[if IE]><script language="javascript" type="text/javascript" src="../js/jqplot/excanvas.js"></script><![endif]-->

	<link rel="stylesheet" type="text/css" href="../js/jqplot/jquery.jqplot.css" />

	<!-- BEGIN: load jquery -->
	<script language="javascript" type="text/javascript" src="../js/jqplot/jquery-1.4.2.min.js"></script>
	<!-- END: load jquery -->

	<!-- BEGIN: load jqplot -->
	<script language="javascript" type="text/javascript" src="../js/jqplot/jquery.jqplot.min.js"></script>
	
	<?php if( $type_graph=='pie' )
	{ 
		?>
		<script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.pieRenderer.js"></script>
		<?php 
	}
	elseif( $type_graph=='bar' )
	{ 
		?>
		<script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.barRenderer.js"></script>
		<script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.categoryAxisRenderer.min.js"></script>
		<script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.pointLabels.min.js"></script>
		<script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.canvasTextRenderer.js"></script>
		<script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.canvasAxisTickRenderer.js"></script>
		<?php 
	}
	elseif( $type_graph=='barCumulative' )
	{ 
		?>
		<script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.categoryAxisRenderer.js"></script>
		<script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.dateAxisRenderer.js"></script>
		<script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.barRenderer.js"></script>
		<script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.pointLabels.js"></script>
		<script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.enhancedLegendRenderer.js"></script>
		<?php 
	} 
	?> 
	  
  <!-- END: load jqplot -->

	<style type="text/css">
		
		#chart .jqplot-point-label {
		  border: 1.5px solid #aaaaaa;
		  padding: 1px 3px;
		  background-color: #eeccdd;
		}
				
	</style>
	
	<script class="code" type="text/javascript">
	
		var links = [<?php echo $links; ?>];

		function myClickHandler(ev, gridpos, datapos, neighbor, plot) {
            //mouseX = ev.pageX; mouseY = ev.pageY;
            url = links[neighbor.pointIndex];
            if (typeof(url)!='undefined' && url!='') 
				top.frames['main'].location.href = url;
        }
        var isShowing = -1;
		
		function myMoveHandler(ev, gridpos, datapos, neighbor, plot) {
			if (neighbor == null) {
	            $('#myToolTip').hide().empty();
	            isShowing = -1;
	        }
	        if (neighbor != null) {
	        	if (neighbor.pointIndex!=isShowing) 
				{
					var class_name = $('#chart').attr('class');
					var          k =( class_name.match('barCumulative') ) ? 1 : 0;
														
					$('#myToolTip').html(neighbor.data[k]).css({left:gridpos.x, top:gridpos.y-5}).show();
	            	isShowing = neighbor.pointIndex
	            }
	        }
        }
            		
		$(document).ready(function(){
					
			$.jqplot.config.enablePlugins = true;
			$.jqplot.eventListenerHooks.push(['jqplotClick', myClickHandler]); 
			$.jqplot.eventListenerHooks.push(['jqplotMouseMove', myMoveHandler]);
			
						
		<?php if( $type_graph == 'pie' ){ ?>
			
			s1 = [<?php echo $data; ?>];
			
			plot1 = $.jqplot('chart', [s1], {
				grid: {
					drawBorder: false, 
					drawGridlines: false,
					background: 'rgba(255,255,255,0)',
					shadow:false
				},
				<?php if ($colors!="") { ?>seriesColors: [ <?php echo $colors; ?> ], <?php } ?>
				axesDefaults: {
					
				},
				seriesDefaults:{
                    padding:14,
					renderer:$.jqplot.PieRenderer,
					rendererOptions: {
						showDataLabels: true,
						dataLabels: "value",
						dataLabelFormatString: '%d'
					}								
				},
				
				legend: {
					show: true,
					rendererOptions: {
						numberCols: 2
					},
					location: 'w'
				}
			}); 
		<?php }elseif( $type_graph == 'bar' ) {
				
				$lineValue  = implode(",", $datay);
				$ticksValue = "'".implode("','", $labelx)."'";
				
			?>
				line1 = [<?php echo $lineValue; ?>];
				plot1 = $.jqplot('chart', [line1], {
			    legend:{show:false},
			    series:[
					{ pointLabels:{ show: false }, renderer:$.jqplot.BarRenderer }
			    ],                                    
			    grid: { background: '#F5F5F5', shadow: false },
				axesDefaults: {
				      tickRenderer: $.jqplot.CanvasAxisTickRenderer ,
				      tickOptions: {
				        angle: 20,
				        fontSize: '12px'
				      }
				},
			    axes:{
			        xaxis:{
			        	renderer:$.jqplot.CategoryAxisRenderer,
			        	ticks:[<?php echo $ticksValue; ?>]
			        }, 
			        yaxis:{min:0, tickOptions:{formatString:'%d'}}
			    }
			});
			
		<?php }elseif( $type_graph=='barCumulative' ){ 
				
				
					$ticksValue = "'".implode("','", $legend)."'";
					$label      = implode(",", $label);
							
					foreach($final_values as $key => $value)
					{
						$line_values  .= "line_".$key." = [".$value."]; ";
						$line_names[]  = "line_".$key;
					}
				
					$line_names = "[".implode(",",$line_names)."]";
				?>
				
				<?php echo $line_values?>
				plot1 = $.jqplot('chart', <?php echo $line_names;?>, {
				stackSeries: true,
				legend:{
					renderer: $.jqplot.EnhancedLegendRenderer,
					rendererOptions: {
						numberColumns: 3
					},
					show:true, 
					location:'ne',
					yoffset: 0
					},
				
				seriesDefaults: {
					pointLabels:{ show: false },
					renderer: $.jqplot.BarRenderer,
					rendererOptions:{barPadding: 8}
				},
				series: [
					    <?php echo $label;?>],
			    grid: { background: '#F5F5F5', shadow: false },
			    axes:{
					xaxis:{
						renderer:$.jqplot.CategoryAxisRenderer,
			        	ticks:[<?php echo $ticksValue; ?>]
					},
					yaxis:{min:0}
			    }
			});
		<?php } ?>

			$('#chart').append('<div id="myToolTip"></div>');
		});
	</script>
    
  </head>
	<body style="overflow:hidden" scroll="no">
		<div id="chart" style="width:100%; height:<?php echo $h; ?>px;" class='<?php echo $type_graph?>'></div>
	</body>
</html>

