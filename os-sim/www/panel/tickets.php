<?php
require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
require_once ('classes/Util.inc');
require_once ('classes/Incident.inc');
require_once ('sensor_filter.php');
Session::logcheck("MenuControlPanel", "ControlPanelExecutive");

$type = GET("type");
ossim_valid($type, 'ticketsByPriority','ticketsClosedByMonth','ticketResolutionTime','openedTicketsByUser','ticketStatus','ticketTypes', 'illegal:' . _("type"));

if (ossim_error()) {
    die(ossim_error());
}

$data  = null;
$links = ''; 
$h     = ( !empty($_GET['height']) ) ? GET('height') : 250;  // Graph Height

$db    = new ossim_db();
$conn  = $db->connect();

$user  = Session::get_session_user();

// Types
switch($type){
	case 'ticketStatus':
		
		$type_graph = 'pie';
		$legend     = ( empty($_GET['legend']) ) ? "w" : GET('legend');
				
		$ticket_status = Incident::incidents_by_status($conn, null, $user);
		
		if( is_array($ticket_status) && !empty($ticket_status) )
		{
			foreach($ticket_status as $type => $ocurrences)
			{
				$data[] = "['".$type."',".$ocurrences."]";
				$sum = $sum + $ocurrences;
			}
				
			$data   = implode(",", $data);
			$links  = "'../incidents/index.php?&status=open&hmenu=Tickets&smenu=Tickets','../incidents/index.php?&status=closed&hmenu=Tickets&smenu=Tickets'";
			$colors = '"#E9967A","#9BC3CF"';
		}
		else
		{
			$data   = "['"._("No tickets")."',0]";
			$colors = '"#E9967A"';
		}
			
		
				
	break;
	
	case 'ticketTypes':
		
		$type_graph = 'pie';
		$legend     = ( empty($_GET['legend']) ) ? "w" : GET('legend');
		
		$ticket_by_type = Incident::incidents_by_type($conn, null, $user);
		$i = 0;
		
		if( is_array($ticket_by_type) && !empty($ticket_by_type) )
		{
			if ( $i < 10 )
			{
				foreach($ticket_by_type as $type => $ocurrences)
				{
					$type = ( strlen($type) > 28 ) ? substr($type, 0, 25)." [...]" : $type;
					$data[] = "['".$type."',".$ocurrences."]";
				}
			}
			else
				break;
				
			$data  = implode(",", $data);
		}
		else
		{
			$data   = "['"._("No tickets")."',0]";
			$colors = '"#E9967A"';
		}
		
	break;
	
	case 'ticketsByClass':
		
		$type_graph = 'pie';
		$legend     = ( empty($_GET['legend']) ) ? "w" : GET('legend');
		
		$ticket_by_class = Incident::incidents_by_class($conn, null, $user);
		
		if( is_array($ticket_by_class) && !empty($ticket_by_class) )
		{
			foreach($ticket_by_class as $class => $ocurrences)
			{
				$data[] = "['".$class."',".$ocurrences."]";
			}
				
			$data  = implode(",", $data);
		}
		else
		{
			$data   = "['"._("No tickets")."',0]";
			$colors = '"#E9967A"';
		}
		
	break;
		
	case 'openedTicketsByUser':
		
		$type_graph  = 'pie';
		$legend      = ( empty($_GET['legend']) ) ? "w" : GET('legend');
		
		$ticket_by_user = Incident::incidents_by_user($conn, true, null, $user);
		$i = 0;
				
		if( is_array($ticket_by_user) && !empty($ticket_by_user) )
		{
			foreach($ticket_by_user as $user => $ocurrences)
			{
				if ( $i < 10 )
				{
					$user = ( strlen($user) > 28 ) ? substr($user, 0, 25)." [...]" : $user;
					$data[] = "['".$user."',".$ocurrences."]";
				}
				else
					break;
				
				$i++;
			}
				
			$data  = implode(",", $data);
		}
		else
		{
			$data   = "['"._("No tickets")."',0]";
			$colors = '"#E9967A"';
		}
						
		break;
	
	case 'ticketResolutionTime':
		
		$ttl_groups = array();
		
		$type_graph = 'bar';
		$legend     = ( empty($_GET['legend']) ) ? "s" : GET('legend');
				
		$list       = Incident::incidents_by_resolution_time($conn, null, $user);
		
		$ttl_groups = array("1"=>0, "2"=>0, "3"=>0, "4"=>0, "5"=>0, "6"=>0);
		
		$total_days = 0;
		$day_count  = null;
				
		foreach ($list as $incident) 
		{
				$ttl_secs = $incident->get_life_time('s');
				$days     = round($ttl_secs/60/60/24);
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
		$legend     = ( empty($_GET['legend']) ) ? "ne" : GET('legend');
		
		$final_values = array();						
				
		$ticket_closed_by_month = Incident::incidents_closed_by_month($conn, null, $user);
				
		if( is_array($ticket_closed_by_month) && !empty($ticket_closed_by_month) )
		{
			foreach($ticket_closed_by_month as $event_type => $months)
			{
				$label[] = "{label: '".$event_type."'}";
				$final_values[$event_type] = implode(",", $months);
			}
			
			$event_types = array_keys($ticket_closed_by_month);
			$legend_text = array_keys($ticket_closed_by_month[$event_types[0]]);
		}
								
		break;
	
	case 'ticketsByPriority':
		
		$type_graph  = 'pie';
		$colors      = null;
		$legend      = ( empty($_GET['legend']) ) ? "w" : GET('legend');
			
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
		
		$list       = Incident::incidents_by_priority($conn);
				
		
		
		if ( is_array($list) && !empty($list) )	
		{
			foreach ($list as $priority => $v) 
			{
				if ( $v > 0 )
				{
					$data[]                          = "['"._("Priority")." ".$priority."',".$v."]";	
					$colors[$temp_colors[$priority]] = $temp_colors[$priority];
				}
			}
			
			if ( is_array($data) )
			{
				$data   = implode(",", $data);
				$colors = "'".implode("','", $colors)."'";
			}
		}
		else
		{
			$data   = "['"._("No tickets")."',0]";
			$colors = '"#E9967A"';
		}
		
		
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
		<script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.pointLabels.min.js"></script>
		<script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.enhancedLegendRenderer.js"></script>
		<script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.canvasTextRenderer.js"></script>
		<script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.canvasAxisTickRenderer.js"></script>
		
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
					var          k =( class_name.match('bar') ) ? 1 : 0;
														
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
						diameter: '170',
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
					location:'<?php echo $legend; ?>'
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
				
				
					$ticksValue = "'".implode("','", $legend_text)."'";
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
					location:'<?php echo $legend; ?>',
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
				axesDefaults: {
				      tickRenderer: $.jqplot.CanvasAxisTickRenderer ,
				      tickOptions: {
				           fontSize: '10px'
				      }
				},
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

