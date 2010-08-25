<?
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
* - check_writable_relative()
* Classes list:
*/
require_once 'classes/Session.inc';
require_once 'ossim_db.inc';
require_once 'classes/Security.inc';
require_once 'classes/Host.inc';

Session::logcheck("MenuControlPanel", "BusinessProcesses");

?>
<html>
<head>
  <title> <?php echo gettext("Object report"); ?> </title>
  <!--  <meta http-equiv="refresh" content="3"> -->
    <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
      <link rel="stylesheet" href="../style/style.css"/>
      </head>

      <body>

<?
require_once 'classes/Measures.inc';

if (!Session::menu_perms("MenuControlPanel", "BusinessProcessesEdit")) {
print _("You don't have permissions to view the risk maps reports");
exit();

}

$db = new ossim_db();
$conn = $db->connect();

$type= $_GET["type"];
$default= $_GET["default"];
$id = $_GET["id"];

ossim_valid($type, OSS_ALPHA, OSS_SCORE ,'illegal:'._("type"));
ossim_valid($id, OSS_DIGIT, OSS_ALPHA, OSS_PUNC, OSS_SPACE, ".", 'illegal:'._("id"));

if (ossim_error()) 
{
	die(ossim_error());
}
switch($type)
{
	case "host":

	$host=Host::get_list($conn," where ip='".$id."' or hostname='".$id."'");

	$id=$host[0]->ip;
	$hostname=$host[0]->hostname;
	
	echo "<h1 align=\"center\">".gettext("Report")." ".gettext("for")." $type $id </h1>";

?>

<center>
[
<a href="/ossim/control_panel/alarm_console.php?hide_search=1&date_from=&date_to=&src_ip=<?=$id?>&dst_ip=<?=$id?>&num_alarms_page=10&hide_closed=1" target="_report"> <?php echo gettext("Alarms"); ?> </a> 
|
	<a href="nessus_report.php?id=<?=$id?>" target="_report"> <?php echo gettext("Vulnerabilities"); ?> </a>
 |
 <a href="/acidbase/base_qry_main.php?search=1&sensor=+&ag=+&sig[0]=LIKE&sig[2]=%3D&sig[1]=nagios&sig_class=+&sig_priority[0]=+&sig_priority[1]=&time[0][0]=+&time[0][1]=+&time[0][2]=+&time[0][3]=&time[0][4]=+&time[0][5]=&time[0][6]=&time[0][7]=&time[0][8]=+&time[0][9]=+&ossim_risk_a=+&ossim_priority[0]=+&ossim_priority[1]=&ossim_type[1]=&ossim_asset_dst[0]=+&ossim_asset_dst[1]=&ossim_reliability[0]=+&ossim_reliability[1]=&ip_addr[0][0]=+&ip_addr[0][1]=ip_both&ip_addr[0][2]=%3D&ip_addr[0][3]=<?=$id;?>&ip_addr[0][8]=+&ip_addr[0][9]=+&ip_field[0][0]=+&ip_field[0][1]=+&ip_field[0][2]=%3D&ip_field[0][3]=&ip_field[0][4]=+&ip_field[0][5]=+&data_encode[0]=+&data_encode[1]=+&data[0][0]=+&data[0][1]=+&data[0][2]=&data[0][3]=+&data[0][4]=+&new=1&sort_order=none&submit=Query+DB&caller=&num_result_rows=-1&current_view=-1&minimal_view" target="_report"> <?php echo gettext("Availability"); ?> </a>|
 <a href="" target="_self" onclick="history.go(-1);return false;"> <?php echo gettext("Go Back"); ?> </a>]
</center>
<br><br>
<center>
<iframe width="100%" height="400" src="<?
switch ($default)
{
	case "vulnerability":
	case "vulnerabilities":
	?>nessus_report.php?id=<?=$id?><?
	break;
	case "availability":
 	?>/acidbase/base_qry_main.php?search=1&sensor=+&ag=+&sig[0]=LIKE&sig[2]=%3D&sig[1]=nagios&sig_class=+&sig_priority[0]=+&sig_priority[1]=&time[0][0]=+&time[0][1]=+&time[0][2]=+&time[0][3]=&time[0][4]=+&time[0][5]=&time[0][6]=&time[0][7]=&time[0][8]=+&time[0][9]=+&ossim_risk_a=+&ossim_priority[0]=+&ossim_priority[1]=&ossim_type[1]=&ossim_asset_dst[0]=+&ossim_asset_dst[1]=&ossim_reliability[0]=+&ossim_reliability[1]=&ip_addr[0][0]=+&ip_addr[0][1]=ip_both&ip_addr[0][2]=%3D&ip_addr[0][3]=<?=$id;?>&ip_addr[0][8]=+&ip_addr[0][9]=+&ip_field[0][0]=+&ip_field[0][1]=+&ip_field[0][2]=%3D&ip_field[0][3]=&ip_field[0][4]=+&ip_field[0][5]=+&data_encode[0]=+&data_encode[1]=+&data[0][0]=+&data[0][1]=+&data[0][2]=&data[0][3]=+&data[0][4]=+&new=1&sort_order=none&submit=Query+DB&caller=&num_result_rows=-1&current_view=-1&minimal_view<?
	break;
	case "risk":
	case "alarms":
	default:
	?>/ossim/control_panel/alarm_console.php?hide_search=1&date_from=&date_to=&src_ip=<?=$id?>&dst_ip=<?=$id?>&num_alarms_page=10&hide_closed=1<?
	break;
}
?>" id="report" name="_report" frameborder="0"> </iframe>
<br />
<p>*Displaying open Alarms only!</p>
</center>
</body>
</html>

<?
	break;

	case "hostgroup":
	case "host_group":
	$sql="SELECT bms.member,bms.status_date,bms.measure_type,bms.severity FROM host_group_reference hg, bp_member_status bms where hg.host_group_name=? and hg.host_ip=bms.member  order by bms.severity desc";
	$params = array($id);

	echo "<h1>Hostgroup $id members</h1>";
	if (!$rs = &$conn->Execute($sql, $params))
	        print $conn->ErrorMsg();
	else
	{

        	while(!$rs->EOF)
	        {
			if(!$res[$rs->Fields("member")])
				$res[$rs->Fields("member")]=array();

			$res[$rs->Fields("member")][$rs->Fields("measure_type")]=$rs->Fields("severity");
			$res[$rs->Fields("member")]["status_date"]=$rs->Fields("status_date");
	                $rs->MoveNext();
        	}
	}

	if($res)
	{
	        echo '<table width="100%">';
                echo "<tr>";
                echo "<th>";
                echo "Host";
                echo "</th>";
                echo "<th>";
                echo "Status Date";
                echo "</th>";
                echo "<th>";
                echo "Risk";
                echo "</th>";
                echo "<th>";
                echo "Vulnerability";
                echo "</th>";
                echo "<th>";
                echo "Availability";
                echo "</th>";
                echo "</tr>";

		foreach($res as $ip=>$values)
		{
	                echo "<tr>";
	                echo "<td>";
        	        echo '<a href="/ossim/risk_maps/reports.php?type=host&id='.$ip.'">'.$ip.'</a>'."\n";
	                echo "</td>";
        	        echo "<td>";
	                echo $values["status_date"];
	                echo "</td>";

			if($values["host_alarm"]<=3)
	                echo '<td bgcolor="green">';
			else if($values["host_alarm"]<=7)
	                echo '<td bgcolor="yellow">';
			else 
	                echo '<td bgcolor="red">';
	                echo '<a href="' .$_SERVER[SCRIPT_NAME]. '?id=' .$ip. '&type=host&default=risk">'.$values["host_alarm"]."</a>";
	                echo "</td>";

			if($values["host_vulnerability"]<=3)
	                echo '<td bgcolor="green">';
			else if($values["host_vulnerability"]<=7)
	                echo '<td bgcolor="yellow">';
			else 
	                echo '<td bgcolor="red">';
	                echo '<a href="' .$_SERVER[SCRIPT_NAME]. '?id=' .$ip. '&type=host&default=vulnerability">'.$values["host_vulnerability"]."</a>";
	                echo "</td>";

			if($values["host_availability"]<=3)
	                echo '<td bgcolor="green">';
			else if($values["host_availability"]<=7)
	                echo '<td bgcolor="yellow">';
			else 
	                echo '<td bgcolor="red">';
	                echo '<a href="' .$_SERVER[SCRIPT_NAME]. '?id=' .$ip. '&type=host&default=availability">'.$values["host_availability"]."</a>";
	                echo "</td>";

	                echo "</tr>";
		}

	        echo "    </table>";
 		?><br /><center>[<a href="" target="_self" onclick="history.go(-1);return false;"> <?php echo gettext("Go Back"); ?> </a>]</center><?
	}

	break;

	case "net":
	echo "net";
	break;

	default:
	break;
}
?>
