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
require_once 'classes/Host_vulnerability.inc';
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

if (!Session::menu_perms("MenuControlPanel", "BusinessProcessesEdit")) {
print _("You don't have permissions to view the risk maps reports");
exit();

}

$db = new ossim_db();
$conn = $db->connect();

$id = request("id");

ossim_valid($id, OSS_DIGIT, OSS_ALPHA, OSS_SCORE, OSS_PUNC, OSS_SPACE, ".", 'illegal:'._("id"));


if (ossim_error()) 
{
	die("Error!!".ossim_error());
}

$host=Host::get_list($conn," where ip='".$id."' or hostname='".$id."'");

$id=$host[0]->ip;
$hostname=$host[0]->hostname;
$scanrow=Host_vulnerability::get_list($conn," where ip='".$id."' and vulnerability>0 ", " order by scan_date desc", false);
if(!$scanrow)
{
	echo "<br>Not scaned yet.<br>";
 ?><a href="" target="_self" onclick="history.go(-1);return false;">[ <?php echo gettext("Go Back"); ?> ]</a><?
	exit;
}


$ipstr=ereg_replace("\.","_",$scanrow[0]->ip);
$fdate=$scanrow[0]->scan_date;
$sdate=ereg_replace(":","",$scanrow[0]->scan_date);
$sdate=ereg_replace("-","",$sdate);
$sdate=ereg_replace(" ","",$sdate);


$sql = 'select p.reliability,p.priority,p.name from host_plugin_sid h, plugin_sid p where p.plugin_id=3001 and h.plugin_id=3001 and h.plugin_sid=p.sid and h.host_ip=inet_aton( ? ) order by reliability desc,priority desc';
$params = array($id);

echo "<h1>Host $id scan results at $fdate</h1>";
if (!$rs = &$conn->Execute($sql, $params))
	print $conn->ErrorMsg();
else
{
	echo '    <table width="100%">';
		echo "<tr>";
		echo "<th>";
		echo "name";
		echo "</th>";
		echo "<th>";
		echo "priority";
		echo "</th>";
		echo "<th>";
		echo "reliability";
		echo "</th>";
		echo "</tr>";
	while(!$rs->EOF)
	{
		echo "<tr>";
		echo "<td>";
		echo $rs->Fields("name");
		echo "</td>";
		echo "<td>";
		echo $rs->Fields("priority");
		echo "</td>";
		echo "<td>";
		echo $rs->Fields("reliability");
		echo "</td>";
		echo "</tr>";
		$rs->MoveNext();
	}
	echo "    </table>";
}

?>

<center>
<A HREF="javascript:void(0)" onclick="window.open('/ossim/vulnmeter/<?=$sdate?>/<?=$ipstr?>/index.html', 'nessus report','width=750,height=400,menubar=no,status=no, location=yes,toolbar=no,scrollbars=yes')"> <?php echo gettext("View nessus report"); ?> </A>

	<hr />
</center>
<br />
<center>
<br />
 <a href="" target="_self" onclick="history.go(-1);return false;">[ <?php echo gettext("Go Back"); ?> ]</a>
</center>
</body>
</html>

