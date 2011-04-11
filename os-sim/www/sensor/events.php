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
require_once ('classes/Server.inc');
require_once ('classes/Plugin.inc');
require_once 'ossim_db.inc';
include("lib/xmlrpc.inc");
Session::useractive("../session/login.php");
//
if (GET("refresh")==1) {
	$client=new xmlrpc_client("http://127.0.0.1:8000");
	$msg=new xmlrpcmsg('status',array());
	$status = $client->send($msg);
	echo '<a href="javascript:refresh()"><img src="../pixmaps/refresh.png" border="0"></a><br>';
	//print_r($status->val->me);
	if (count($status->val->me['array'])>0) {
		echo '<table width="100%">
				<th>Running</th>
				<th>Connected</th>
				<th>Agent IP</th>
				<th>Server</th>
				<th>EPS</th>
				<th>Count</th>
				<th>Global Cnt</th>
				<th>Seconds</th>
				<th>Local Src</th>
				<th>Local Dst</th>
				<th></th>
		';
		foreach ($status->val->me['array'] as $id => $res) {
			echo "<tr>\n";
			preg_match("/Running=(\d+) Connected=(\d+) AgentIP=(.*?) Server=(.*?) EPS=(\d+) Count=(\d+) GlobalCount=(\d+) Seconds=(\d+) LocalSrc=(\d+) LocalDst=(\d+)/",$res->me['string'],$fnd);
			for($i=1;$i<count($fnd);$i++) echo "<td>".$fnd[$i]."</td>";
			echo '<td><a target="main" href="events.php?stop='.$id.'" style="text-decoration:none" class="button">STOP</a></td>';
			echo "</tr>\n";
		}
		echo "</table>\n";
	} else {
		echo _("No agents running.");
	}
	exit;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title> <?php echo gettext("Agent Emulator"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <style tyle="text/css">
  	.g { color:darkgreen; font-weight:bold; }
  	.r { color:red; font-weight:bold; }
  </style>
  <script>
  	 function refresh() {
        $.ajax({
            type: "GET",
            url: "events.php?refresh=1",
            data: "",   
            success: function(msg){
                $('#content').html(msg);
            }
        });
  	 }
  	 $(document).ready(function(){ refresh(); });
  </script>
</head>
<body><br>
<?php
	include("../hmenu.php"); 
	#
	# vagent_server.py => listen in port 8000
	# methods:
	#   ping() =>  Check if XMLRPC connection is active. Return True if it's OK.
	#   do(n_agents, n_eps, server[:port], [local_src, local_dst], [random payload], [category:subcategory]) => Create/add virtual sensors
	#   status() => Return an array with status of the virtual agents
	#   stop([id]) => Stop all agents if not id or id < 0, or stop one agent of the status array (start with 0)
	#
	#$msg=new xmlrpcmsg('do',array(new xmlrpcval("10,10,localhost", "string")));
	$client=new xmlrpc_client("http://127.0.0.1:8000");
	#
	if (GET('action')=="launch") { // launch agent
		$agents = intval(GET('agents')); if ($agents<=0) $agents = 1;
		$eps = intval(GET('eps')); if ($eps<=0) $eps = 5;
		$server = GET('server');
		$src = intval(GET('src'));
		$dst = intval(GET('dst'));
		$payload = 0;
		$tax = GET('tax');
		$msg=new xmlrpcmsg('do',array(new xmlrpcval("$agents,$eps,$server,$src,$dst,$payload,$tax", "string")));
		$status = $client->send($msg);
		echo _("Agents launched successfully!")." "._("Please refresh status").".<br>";
	}
	if (GET('stop')!="") {
		$agent = intval(GET('stop'));
		$msg=new xmlrpcmsg('stop',array(new xmlrpcval($agent, "int")));
		$status = $client->send($msg);
		echo _("Agent [$agent] Stopped successfully!")." "._("Please refresh status").".<br>";
	}	
	# Ping status
	$msg=new xmlrpcmsg('ping',array());
	$status = $client->send($msg);
	$online = ($status->val->me['boolean']==1) ? true : false;
	//
	$db = new ossim_db();
	$conn = $db->connect();	
	$servers = Server::get_list($conn, "");
	list ($categories,$subcategories) = Plugin::get_categories($conn);
	$db->close($conn);
?>
<table width="100%">
<th colspan="2"><?php echo _("Agent Emulator")?> &nbsp;&nbsp;=>&nbsp;&nbsp; <?=_("Server Status")?>: <?= ($online) ? "<span class=g>"._("UP")."</span>" : "<span class=r>"._("DOWN")."</span>" ?> </th>
<tr>
	<td valign="top" width="200" class="left noborder" style="padding-left:20px" nowrap>
		<form action="events.php">
		<input type="hidden" name="action" value="launch">
		<?=_("Agents")?>: <input type="text" name="agents" value="10" size="4"><br>
		<?=_("EPS")?>: <input type="text" name="eps" value="20" size="4"><br>
		<?=_("Server")?>: <select name="server" style="width:180px"><option value='127.0.0.1:40001'>127.0.0.1</option>
		<? foreach ($servers as $server) echo "<option value='".$server->get_ip().":".$server->get_port()."'>".Util::htmlentities($server->get_name())."-".$server->get_ip()."</option>"; ?>
		</select><br>
		<?=_("Local Src")?>: <select name="src"><option value="0"><?=_("No")?></option><option value="1"><?=_("Yes")?></option></select><br>
		<?=_("Local Dst")?>: <select name="dst"><option value="0"><?=_("No")?></option><option value="1"><?=_("Yes")?></option></select><br>
		<?=_("Taxonomy")?>: <select name="tax" style="width:180px"><option value='0:0'>ANY</option>
		<?php
			foreach ($categories as $id => $name) {
				echo "<option value='$id:0'>$name</option>\n";
				foreach ($subcategories[$id] as $sid => $sname) {
					echo "<option value='$id:$sid'>$name - $sname</option>\n";
				}
			}
		?>
		</select><br>
		<input type="submit" class="button" value="<?=_("Launch")?>">
		</form>
	</td>
	
	<td valign="top" class="left noborder" id="content">
		<a href="javascript:refresh()"><img src="../pixmaps/refresh.png" border="0"></a><br>
	</td>

</tr>
</table>
</body>
</html>

