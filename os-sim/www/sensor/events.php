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
ob_implicit_flush();
require_once ('classes/Session.inc');
require_once ('classes/Server.inc');
require_once ('classes/Plugin.inc');
require_once 'ossim_db.inc';
include("lib/xmlrpc.inc");
Session::useractive("../session/login.php");
//
$db = new ossim_db();
$conn = $db->connect();	
$servers = Server::get_list($conn, "");
list ($categories,$subcategories) = Plugin::get_categories($conn);
$csclist = array("0:0" => "ANY"); foreach ($categories as $id => $name) foreach ($subcategories[$id] as $sid => $sname) $csclist["$id:$sid"] = "$name - $sname";
$db->close($conn);
//
if (GET("refresh")==1) {
	$client=new xmlrpc_client("http://127.0.0.1:8000");
	$msg=new xmlrpcmsg('status',array());
	$status = $client->send($msg);
	echo '<a href="javascript:refresh()"><img src="../pixmaps/refresh.png" border="0"></a><br>';
	//print_r($status->val->me);
	if (count($status->val->me['array'])>0) {
		$stops = array();
		echo '<table width="100%">
				<th>Run</th>
				<th>Conn</th>
				<th>Agent IP</th>
				<th>Server</th>
				<th>EPS</th>
				<th>Count</th>
				<th>Seconds</th>
				<th>Real EPS</th>
				<th>Lc Src</th>
				<th>Lc Dst</th>
				<th>Taxonomy</th>
				<th></th>
		';
		foreach ($status->val->me['array'] as $id => $res) {
			echo "<tr>\n"; //echo $res->me['string'];
			preg_match("/Running=(\d+) Connected=(\d+) AgentIP=(.*?) Server=(.*?) EPS=(\d+) Count=(\d+) Seconds=(\d+\.\d\d)\d* RealEPS=(0.0|\d+\.\d\d\d)\d* LocalSrc=(\d+) LocalDst=(\d+) RndPayload=\d+ Category=(\d+) Subcategory=(\d+)/i",$res->me['string'],$fnd);
			for($i=1;$i<count($fnd);$i++) {
				if ($i<count($fnd)-2)
					echo "<td>".$fnd[$i]."</td>";
				if ($i==count($fnd)-1)
					echo "<td>".$csclist[$fnd[$i-1].":".$fnd[$i]]."</td>";
			}
			$stops[] = $id;
			echo '<td><a target="main" href="events.php?stop='.$id.'" style="text-decoration:none" class="button">STOP</a></td>';
			echo "</tr>\n";
		}
		echo "</table>\n";
		echo '<a target="main" href="events.php?stop='.urlencode(implode(",",$stops)).'" style="text-decoration:none" class="button">STOP ALL</a>';
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
		$tax = "";
        if (GET('tax')!='0' && GET('tax')!='') $tax = ",".GET('tax');
        $str = "$agents,$eps,$server,$src,$dst,$payload".$tax;
		$msg=new xmlrpcmsg('do',array(new xmlrpcval($str, "string")));
		$status = $client->send($msg);
		echo _("Agents launched successfully!")." "._("Please refresh status").".<br>";
	}
	if (GET('stop')!="") {
		$agents = explode(",",GET('stop'));
		foreach ($agents as $agent) {
			$msg=new xmlrpcmsg('stop',array(new xmlrpcval(0, "int")));
			$status = $client->send($msg);
			echo _("Agent [$agent] Stopped successfully!")." "._("Please refresh status").".<br>";	
		}
	}	
	# Ping status
	$msg=new xmlrpcmsg('ping',array());
	$status = $client->send($msg);
	$online = ($status->val->me['boolean']==1) ? true : false;
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
		<?=_("Taxonomy")." (Cat/SubCat)"?>: <select name="tax" style="width:180px">
		<?php
			foreach ($csclist as $id => $name) {
				if ($id=="0:0") $id="0";
				echo "<option value='$id'>$name</option>\n";
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

