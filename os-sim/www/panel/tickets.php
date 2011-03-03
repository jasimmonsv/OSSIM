<?php
require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
require_once 'classes/Util.inc';
require_once 'sensor_filter.php';
Session::logcheck("MenuControlPanel", "ControlPanelExecutive");
//
$type=GET("type");
ossim_valid($type, 'ticketsByPriority','ticketsClosedByMonth','ticketResolutionTime','openedTicketsByUser','ticketStatus','ticketTypes', 'illegal:' . _("type"));
if (ossim_error()) {
    die(ossim_error());
}
$data='';
$links='';
$h = 250; // Graph Height

$db = new ossim_db();
$conn = $db->connect();
// types
switch($type){
	case 'ticketStatus':
		//Ticket Status
		$type_graph='pie';
		$user = $_SESSION['_user'];
		if (Session::am_i_admin()) {
			$query = "SELECT DISTINCT SQL_CALC_FOUND_ROWS incident.* 
			FROM incident ";
		} else {
			$query = "SELECT DISTINCT SQL_CALC_FOUND_ROWS incident.* 
			FROM incident LEFT JOIN incident_ticket ON incident_ticket.incident_id = incident.id, users, incident_subscrip WHERE  incident_subscrip.incident_id=incident.id AND users.login = incident_subscrip.login AND (incident_ticket.users='$user' OR incident_ticket.in_charge='$user' OR incident_ticket.transferred='$user' OR users.login='$user')
			ORDER BY date";
		}
		$rs = &$conn->Execute($query);
		$status = array("Open"=>0,"Closed"=>0);
		while (!$rs->EOF){
			$status[$rs->fields['status']]++;
			$rs->MoveNext();
		}
		if(!empty($status)){
			foreach($status as $value => $key){
				$data.="['".$value."',".$key."],";
			}
			$links="'../incidents/index.php?&status=open&hmenu=Tickets&smenu=Tickets','../incidents/index.php?&status=closed&hmenu=Tickets&smenu=Tickets'";
		}else{
			$data="['Open',0],['Closed',0]";
		}
		$colors = '"#E9967A","#9BC3CF"';
		break;
	case 'ticketTypes':
		//Ticket Types
		$type_graph='pie';
		$query = "select u.ref, count(*) as num from 
		((SELECT i.id,i.ref FROM incident i WHERE i.in_charge='".$_SESSION['_user']."')
		UNION
		(SELECT i.id,i.ref FROM incident i, incident_ticket t WHERE i.id=t.incident_id AND t.in_charge='".$_SESSION['_user']."')) u group by u.ref order by num desc";
		
		if (!$rs = &$conn->Execute($query)) {
			print $conn->ErrorMsg();
			exit();
		}
		while (!$rs->EOF){
			$data.="['".$rs->fields["ref"]."',".$rs->fields["num"]."],";
			$rs->MoveNext();
		}
		break;
	case 'openedTicketsByUser':
		//Opened Tickets by User
		$type_graph='pie';
		$admin_where = (Session::am_i_admin()) ? "" : " AND in_charge!='admin'";
		$query = "select in_charge, count(*) as num from incident where status='Open'$admin_where group by in_charge order by num desc";
		
		if (!$rs = &$conn->Execute($query)) {
			print $conn->ErrorMsg();
			exit();
		}
		
		$conf = $GLOBALS["CONF"];
		$version = $conf->get_conf("ossim_server_version", FALSE);

		while (!$rs->EOF){
			if(preg_match("/pro|demo/i",$version) && preg_match("/^\d+$/",$rs->fields["in_charge"])) {
				list($name, $type) = Acl::get_entity_name_type($conn,$rs->fields["in_charge"]);
				if($type!="" && $name!="")
					$data.="['$name ($type)',".$rs->fields["num"]."],";
			}
			else {
				$data.="['".$rs->fields["in_charge"]."',".$rs->fields["num"]."],";
			}
			
			$rs->MoveNext();
		}
		break;
	case 'ticketResolutionTime':
		$type_graph='bar';
		require_once ('classes/Incident.inc');
		$ttl_groups=array();
		//$list = Incident::search($conn, array('status' => 'Closed'));
		// Filtered by USER
		$list = Incident::search($conn, array('status' => 'Closed', 'in_charge' => $_SESSION['_user']));
		$ttl_groups[1] = 0;
		$ttl_groups[2] = 0;
		$ttl_groups[3] = 0;
		$ttl_groups[4] = 0;
		$ttl_groups[5] = 0;
		$ttl_groups[6] = 0;

		$total_days = 0;
		$day_count;

		foreach ($list as $incident) {
				$ttl_secs = $incident->get_life_time('s');
				$days = round($ttl_secs/60/60/24);
				$total_days += $days;
				$day_count++;
				if ($days < 1) $days = 1;
				if ($days > 6) $days = 6;
				@$ttl_groups[$days]++;
		}

		$datay  = array_values($ttl_groups);

		$labelx = array("1 Day","2 Days","3 Days","4 Days","5 Days","6+ Days");
		break;
	case 'ticketsClosedByMonth':
		$type_graph='barCumulative';
		$query = array();

		$user_where = " AND status='Closed' AND in_charge='".$_SESSION['_user']."'";

		$year = date("Y");

		array_push($query, 'select * from
		(select count(*) as "Jan" from incident where date > "'.$year.'-01-01 00:00:00" and ref="Alarm" and date < "'.$year.'-02-01 00:00:00"'.$user_where.') as enero,
		(select count(*) as "Feb" from incident where date > "'.$year.'-02-01 00:00:00" and ref="Alarm" and date < "'.$year.'-03-01 00:00:00"'.$user_where.') as febrero,
		(select count(*) as "Mar" from incident where date > "'.$year.'-03-01 00:00:00" and ref="Alarm" and date < "'.$year.'-04-01 00:00:00"'.$user_where.') as marzo,
		(select count(*) as "Apr" from incident where date > "'.$year.'-04-01 00:00:00" and ref="Alarm" and date < "'.$year.'-05-01 00:00:00"'.$user_where.') as abril,
		(select count(*) as "May" from incident where date > "'.$year.'-05-01 00:00:00" and ref="Alarm" and date < "'.$year.'-06-01 00:00:00"'.$user_where.') as mayo,
		(select count(*) as "Jun" from incident where date > "'.$year.'-06-01 00:00:00" and ref="Alarm" and date < "'.$year.'-07-01 00:00:00"'.$user_where.') as junio,
		(select count(*) as "Jul" from incident where date > "'.$year.'-07-01 00:00:00" and ref="Alarm" and date < "'.$year.'-08-01 00:00:00"'.$user_where.') as julio,
		(select count(*) as "Aug" from incident where date > "'.$year.'-08-01 00:00:00" and ref="Alarm" and date < "'.$year.'-09-01 00:00:00"'.$user_where.') as agosto,
		(select count(*) as "Sep" from incident where date > "'.$year.'-09-01 00:00:00" and ref="Alarm" and date < "'.$year.'-10-01 00:00:00"'.$user_where.') as septiembre,
		(select count(*) as "Oct" from incident where date > "'.$year.'-10-01 00:00:00" and ref="Alarm" and date < "'.$year.'-11-01 00:00:00"'.$user_where.') as octubre,
		(select count(*) as "Nov" from incident where date > "'.$year.'-11-01 00:00:00" and ref="Alarm" and date < "'.$year.'-12-01 00:00:00"'.$user_where.') as noviembre,
		(select count(*) as "Dec" from incident where date > "'.$year.'-12-01 00:00:00" and ref="Alarm" and date < "'.$year.'-12-31 23:59:59"'.$user_where.') as diciembre;');

		array_push($query, 'select * from
		(select count(*) as "Jan" from incident where date > "'.$year.'-01-01 00:00:00" and ref="Alert" and date < "'.$year.'-02-01 00:00:00"'.$user_where.') as enero,
		(select count(*) as "Feb" from incident where date > "'.$year.'-02-01 00:00:00" and ref="Alert" and date < "'.$year.'-03-01 00:00:00"'.$user_where.') as febrero,
		(select count(*) as "Mar" from incident where date > "'.$year.'-03-01 00:00:00" and ref="Alert" and date < "'.$year.'-04-01 00:00:00"'.$user_where.') as marzo,
		(select count(*) as "Apr" from incident where date > "'.$year.'-04-01 00:00:00" and ref="Alert" and date < "'.$year.'-05-01 00:00:00"'.$user_where.') as abril,
		(select count(*) as "May" from incident where date > "'.$year.'-05-01 00:00:00" and ref="Alert" and date < "'.$year.'-06-01 00:00:00"'.$user_where.') as mayo,
		(select count(*) as "Jun" from incident where date > "'.$year.'-06-01 00:00:00" and ref="Alert" and date < "'.$year.'-07-01 00:00:00"'.$user_where.') as junio,
		(select count(*) as "Jul" from incident where date > "'.$year.'-07-01 00:00:00" and ref="Alert" and date < "'.$year.'-08-01 00:00:00"'.$user_where.') as julio,
		(select count(*) as "Aug" from incident where date > "'.$year.'-08-01 00:00:00" and ref="Alert" and date < "'.$year.'-09-01 00:00:00"'.$user_where.') as agosto,
		(select count(*) as "Sep" from incident where date > "'.$year.'-09-01 00:00:00" and ref="Alert" and date < "'.$year.'-10-01 00:00:00"'.$user_where.') as septiembre,
		(select count(*) as "Oct" from incident where date > "'.$year.'-10-01 00:00:00" and ref="Alert" and date < "'.$year.'-11-01 00:00:00"'.$user_where.') as octubre,
		(select count(*) as "Nov" from incident where date > "'.$year.'-11-01 00:00:00" and ref="Alert" and date < "'.$year.'-12-01 00:00:00"'.$user_where.') as noviembre,
		(select count(*) as "Dec" from incident where date > "'.$year.'-12-01 00:00:00" and ref="Alert" and date < "'.$year.'-12-31 23:59:59"'.$user_where.') as diciembre;');

		array_push($query,'select * from
		(select count(*) as "Jan" from incident where date > "'.$year.'-01-01 00:00:00" and ref="Event" and date < "'.$year.'-02-01 00:00:00"'.$user_where.') as enero,
		(select count(*) as "Feb" from incident where date > "'.$year.'-02-01 00:00:00" and ref="Event" and date < "'.$year.'-03-01 00:00:00"'.$user_where.') as febrero,
		(select count(*) as "Mar" from incident where date > "'.$year.'-03-01 00:00:00" and ref="Event" and date < "'.$year.'-04-01 00:00:00"'.$user_where.') as marzo,
		(select count(*) as "Apr" from incident where date > "'.$year.'-04-01 00:00:00" and ref="Event" and date < "'.$year.'-05-01 00:00:00"'.$user_where.') as abril,
		(select count(*) as "May" from incident where date > "'.$year.'-05-01 00:00:00" and ref="Event" and date < "'.$year.'-06-01 00:00:00"'.$user_where.') as mayo,
		(select count(*) as "Jun" from incident where date > "'.$year.'-06-01 00:00:00" and ref="Event" and date < "'.$year.'-07-01 00:00:00"'.$user_where.') as junio,
		(select count(*) as "Jul" from incident where date > "'.$year.'-07-01 00:00:00" and ref="Event" and date < "'.$year.'-08-01 00:00:00"'.$user_where.') as julio,
		(select count(*) as "Aug" from incident where date > "'.$year.'-08-01 00:00:00" and ref="Event" and date < "'.$year.'-09-01 00:00:00"'.$user_where.') as agosto,
		(select count(*) as "Sep" from incident where date > "'.$year.'-09-01 00:00:00" and ref="Event" and date < "'.$year.'-10-01 00:00:00"'.$user_where.') as septiembre,
		(select count(*) as "Oct" from incident where date > "'.$year.'-10-01 00:00:00" and ref="Event" and date < "'.$year.'-11-01 00:00:00"'.$user_where.') as octubre,
		(select count(*) as "Nov" from incident where date > "'.$year.'-11-01 00:00:00" and ref="Event" and date < "'.$year.'-12-01 00:00:00"'.$user_where.') as noviembre,
		(select count(*) as "Dec" from incident where date > "'.$year.'-12-01 00:00:00" and ref="Event" and date < "'.$year.'-12-31 23:59:59"'.$user_where.') as diciembre;');

		array_push($query,'select * from
		(select count(*) as "Jan" from incident where date > "'.$year.'-01-01 00:00:00" and ref="Metric" and date < "'.$year.'-02-01 00:00:00"'.$user_where.') as enero,
		(select count(*) as "Feb" from incident where date > "'.$year.'-02-01 00:00:00" and ref="Metric" and date < "'.$year.'-03-01 00:00:00"'.$user_where.') as febrero,
		(select count(*) as "Mar" from incident where date > "'.$year.'-03-01 00:00:00" and ref="Metric" and date < "'.$year.'-04-01 00:00:00"'.$user_where.') as marzo,
		(select count(*) as "Apr" from incident where date > "'.$year.'-04-01 00:00:00" and ref="Metric" and date < "'.$year.'-05-01 00:00:00"'.$user_where.') as abril,
		(select count(*) as "May" from incident where date > "'.$year.'-05-01 00:00:00" and ref="Metric" and date < "'.$year.'-06-01 00:00:00"'.$user_where.') as mayo,
		(select count(*) as "Jun" from incident where date > "'.$year.'-06-01 00:00:00" and ref="Metric" and date < "'.$year.'-07-01 00:00:00"'.$user_where.') as junio,
		(select count(*) as "Jul" from incident where date > "'.$year.'-07-01 00:00:00" and ref="Metric" and date < "'.$year.'-08-01 00:00:00"'.$user_where.') as julio,
		(select count(*) as "Aug" from incident where date > "'.$year.'-08-01 00:00:00" and ref="Metric" and date < "'.$year.'-09-01 00:00:00"'.$user_where.') as agosto,
		(select count(*) as "Sep" from incident where date > "'.$year.'-09-01 00:00:00" and ref="Metric" and date < "'.$year.'-10-01 00:00:00"'.$user_where.') as septiembre,
		(select count(*) as "Oct" from incident where date > "'.$year.'-10-01 00:00:00" and ref="Metric" and date < "'.$year.'-11-01 00:00:00"'.$user_where.') as octubre,
		(select count(*) as "Nov" from incident where date > "'.$year.'-11-01 00:00:00" and ref="Metric" and date < "'.$year.'-12-01 00:00:00"'.$user_where.') as noviembre,
		(select count(*) as "Dec" from incident where date > "'.$year.'-12-01 00:00:00" and ref="Metric" and date < "'.$year.'-12-31 23:59:59"'.$user_where.') as diciembre;');

		array_push($query,'select * from
		(select count(*) as "Jan" from incident where date > "'.$year.'-01-01 00:00:00" and ref="Anomaly" and date < "'.$year.'-02-01 00:00:00"'.$user_where.') as enero,
		(select count(*) as "Feb" from incident where date > "'.$year.'-02-01 00:00:00" and ref="Anomaly" and date < "'.$year.'-03-01 00:00:00"'.$user_where.') as febrero,
		(select count(*) as "Mar" from incident where date > "'.$year.'-03-01 00:00:00" and ref="Anomaly" and date < "'.$year.'-04-01 00:00:00"'.$user_where.') as marzo,
		(select count(*) as "Apr" from incident where date > "'.$year.'-04-01 00:00:00" and ref="Anomaly" and date < "'.$year.'-05-01 00:00:00"'.$user_where.') as abril,
		(select count(*) as "May" from incident where date > "'.$year.'-05-01 00:00:00" and ref="Anomaly" and date < "'.$year.'-06-01 00:00:00"'.$user_where.') as mayo,
		(select count(*) as "Jun" from incident where date > "'.$year.'-06-01 00:00:00" and ref="Anomaly" and date < "'.$year.'-07-01 00:00:00"'.$user_where.') as junio,
		(select count(*) as "Jul" from incident where date > "'.$year.'-07-01 00:00:00" and ref="Anomaly" and date < "'.$year.'-08-01 00:00:00"'.$user_where.') as julio,
		(select count(*) as "Aug" from incident where date > "'.$year.'-08-01 00:00:00" and ref="Anomaly" and date < "'.$year.'-09-01 00:00:00"'.$user_where.') as agosto,
		(select count(*) as "Sep" from incident where date > "'.$year.'-09-01 00:00:00" and ref="Anomaly" and date < "'.$year.'-10-01 00:00:00"'.$user_where.') as septiembre,
		(select count(*) as "Oct" from incident where date > "'.$year.'-10-01 00:00:00" and ref="Anomaly" and date < "'.$year.'-11-01 00:00:00"'.$user_where.') as octubre,
		(select count(*) as "Nov" from incident where date > "'.$year.'-11-01 00:00:00" and ref="Anomaly" and date < "'.$year.'-12-01 00:00:00"'.$user_where.') as noviembre,
		(select count(*) as "Dec" from incident where date > "'.$year.'-12-01 00:00:00" and ref="Anomaly" and date < "'.$year.'-12-31 23:59:59"'.$user_where.') as diciembre;');

		array_push($query,'select * from
		(select count(*) as "Jan" from incident where date > "'.$year.'-01-01 00:00:00" and ref="Vulnerability" and date < "'.$year.'-02-01 00:00:00"'.$user_where.') as enero,
		(select count(*) as "Feb" from incident where date > "'.$year.'-02-01 00:00:00" and ref="Vulnerability" and date < "'.$year.'-03-01 00:00:00"'.$user_where.') as febrero,
		(select count(*) as "Mar" from incident where date > "'.$year.'-03-01 00:00:00" and ref="Vulnerability" and date < "'.$year.'-04-01 00:00:00"'.$user_where.') as marzo,
		(select count(*) as "Apr" from incident where date > "'.$year.'-04-01 00:00:00" and ref="Vulnerability" and date < "'.$year.'-05-01 00:00:00"'.$user_where.') as abril,
		(select count(*) as "May" from incident where date > "'.$year.'-05-01 00:00:00" and ref="Vulnerability" and date < "'.$year.'-06-01 00:00:00"'.$user_where.') as mayo,
		(select count(*) as "Jun" from incident where date > "'.$year.'-06-01 00:00:00" and ref="Vulnerability" and date < "'.$year.'-07-01 00:00:00"'.$user_where.') as junio,
		(select count(*) as "Jul" from incident where date > "'.$year.'-07-01 00:00:00" and ref="Vulnerability" and date < "'.$year.'-08-01 00:00:00"'.$user_where.') as julio,
		(select count(*) as "Aug" from incident where date > "'.$year.'-08-01 00:00:00" and ref="Vulnerability" and date < "'.$year.'-09-01 00:00:00"'.$user_where.') as agosto,
		(select count(*) as "Sep" from incident where date > "'.$year.'-09-01 00:00:00" and ref="Vulnerability" and date < "'.$year.'-10-01 00:00:00"'.$user_where.') as septiembre,
		(select count(*) as "Oct" from incident where date > "'.$year.'-10-01 00:00:00" and ref="Vulnerability" and date < "'.$year.'-11-01 00:00:00"'.$user_where.') as octubre,
		(select count(*) as "Nov" from incident where date > "'.$year.'-11-01 00:00:00" and ref="Vulnerability" and date < "'.$year.'-12-01 00:00:00"'.$user_where.') as noviembre,
		(select count(*) as "Dec" from incident where date > "'.$year.'-12-01 00:00:00" and ref="Vulnerability" and date < "'.$year.'-12-31 23:59:59"'.$user_where.') as diciembre;');
		
		$legend = array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");
		$final_values = array();

		$tmp = array("Alarm","Alert","Event","Metric","Anomaly","Vulnerability");
		$i = 0;


		foreach($query as $quer){
			$values = array();
			if (!$rs = &$conn->Execute($quer)) {
				print $conn->ErrorMsg();
				exit();
			}

			while (!$rs->EOF)
			{
				array_push($values, $tmp[$i]);
				array_push($values, $rs->fields["Jan"]);
				array_push($values, $rs->fields["Feb"]);
				array_push($values, $rs->fields["Mar"]);
				array_push($values, $rs->fields["Apr"]);
				array_push($values, $rs->fields["May"]);
				array_push($values, $rs->fields["Jun"]);
				array_push($values, $rs->fields["Jul"]);
				array_push($values, $rs->fields["Aug"]);
				array_push($values, $rs->fields["Sep"]);
				array_push($values, $rs->fields["Oct"]);
				array_push($values, $rs->fields["Nov"]);
				array_push($values, $rs->fields["Dec"]);

				$rs->MoveNext();
			}
			array_push($final_values, $values);
			$i++;
		}
		break;
	case 'ticketsByPriority':
		//Opened Tickets by User
		$type_graph='pie';
		$admin_where = (Session::am_i_admin()) ? "" : " AND in_charge!='admin'";
		$query = "select in_charge, priority, count(*) as num from incident where status='Open'$admin_where group by priority;";

		if (!$rs = &$conn->Execute($query)) {
			print $conn->ErrorMsg();
			exit();
		}
		
		$conf = $GLOBALS["CONF"];
		$version = $conf->get_conf("ossim_server_version", FALSE);
		
		$temp_colors='';
		while (!$rs->EOF){
				$data.="['".$rs->fields["priority"]."',".$rs->fields["num"]."],";
				switch($rs->fields["priority"]){
					case 10:
						// red
						$temp_colors['#8B0000']=true;
						break;
					case 9:
						// red
						$temp_colors['#bb0000']=true;
						break;
					case 8:
						// red
						$temp_colors['#da0000']=true;
						break;
					case 7:
						// orange
						$temp_colors['#ff7700']=true;
						break;						
					case 6:
						// orange
						$temp_colors['#FF8C00']=true;
						break;
					case 5:
						// orange
						$temp_colors['#ffa500']=true;
						break;
					case 4:
						// verde
						$temp_colors['#ffb900']=true;
						break;
					case 3:
						// verde
						$temp_colors['#ffce00']=true;
						break;
					case 2:
						// verde
						$temp_colors['#ffe200']=true;
						break;
					case 1:
						$temp_colors['#fffd00']=true;
						break;
					case 0:
						$temp_colors['#FFFEAF']=true;
						break;
			}
			
			$rs->MoveNext();
		}
		$colors='';
		foreach($temp_colors as $key => $value){
			$colors.='"'.$key.'",';
		}
		break;
	default:
		break;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
<head>
	  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	  <title><?php if($type_graph=='pie'){ echo 'Pie'; }elseif($type_graph=='bar'){ echo 'Bar';}?> Charts</title>
	  <!--[if IE]><script language="javascript" type="text/javascript" src="../js/jqplot/excanvas.js"></script><![endif]-->
	  
	  <link rel="stylesheet" type="text/css" href="../js/jqplot/jquery.jqplot.css" />
		
	  <!-- BEGIN: load jquery -->
	  <script language="javascript" type="text/javascript" src="../js/jqplot/jquery-1.4.2.min.js"></script>
	  <!-- END: load jquery -->
	  
	  <!-- BEGIN: load jqplot -->
	  <script language="javascript" type="text/javascript" src="../js/jqplot/jquery.jqplot.min.js"></script>
	  <?php if($type_graph=='pie'){ ?>
	  <script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.pieRenderer.js"></script>
	  <?php }elseif($type_graph=='bar'){ ?>
	  <script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.barRenderer.js"></script>
	  <script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.categoryAxisRenderer.min.js"></script>
	  <script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.pointLabels.min.js"></script>
	  <script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.canvasTextRenderer.js"></script>
	  <script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.canvasAxisTickRenderer.js"></script>
	 <?php }elseif($type_graph=='barCumulative'){ ?>
		<script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.categoryAxisRenderer.js"></script>
		<script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.dateAxisRenderer.js"></script>
		<script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.barRenderer.js"></script>
		<script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.pointLabels.js"></script>
		<script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.enhancedLegendRenderer.js"></script>
	<?php } ?> 
	  
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
            if (typeof(url)!='undefined' && url!='') top.frames['main'].location.href = url;
        }
        var isShowing = -1;
		function myMoveHandler(ev, gridpos, datapos, neighbor, plot) {
			if (neighbor == null) {
	            $('#myToolTip').hide().empty();
	            isShowing = -1;
	        }
	        if (neighbor != null) {
	        	if (neighbor.pointIndex!=isShowing) {
	            	$('#myToolTip').html(neighbor.data[0]).css({left:gridpos.x, top:gridpos.y-5}).show();
	            	isShowing = neighbor.pointIndex
	            }
	        }
        }
            		
		$(document).ready(function(){
					
			$.jqplot.config.enablePlugins = true;
			$.jqplot.eventListenerHooks.push(['jqplotClick', myClickHandler]); 
			$.jqplot.eventListenerHooks.push(['jqplotMouseMove', myMoveHandler]);
			
		<?php if($type_graph=='pie'){ ?>
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
		<?php }elseif($type_graph=='bar'){
				$lineValue=implode(",", $datay);
				$ticksValue='';
				foreach($labelx as $value){
					$ticksValue.='"'._($value).'",';
				}
			?>
				line1=[<?php echo $lineValue; ?>];
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
			
		<?php }elseif($type_graph=='barCumulative'){
				$ticksValue='';
				foreach($legend as $value){
					$ticksValue.="'".$value."',";
				}
				//
				$lineValue=array('');
				$label='';
				foreach($final_values as $key => $value){
					foreach($value as $key2 => $value2){
						if($key2==0){
							$label.="{label: '".$value2."'},";
						}else{
							$lineValue[$key][$key2]=$value2;
						}
					}
				}
				$lineValueName='';
				foreach($lineValue as $key => $value){
					echo 'line'.$key.'=['.implode(',',$value).']; ';
					$lineValueName.='line'.$key.',';
				}
			?>
				plot1 = $.jqplot('chart', [<?php echo substr ($lineValueName, 0, -1);?>], {
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
					rendererOptions:{barWidth: 50}
				},
				series: [
					<?php echo substr ($label, 0, -1);?>],
			    grid: { background: '#F5F5F5', shadow: false },
			    axes:{
					xaxis:{
						renderer:$.jqplot.CategoryAxisRenderer,
			        	ticks:[<?php echo substr ($ticksValue, 0, -1); ?>]
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
		<div id="chart" style="width:100%; height:<?php echo $h; ?>px;"></div>
	</body>
</html>

