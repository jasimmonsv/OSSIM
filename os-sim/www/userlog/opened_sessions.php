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
Session::useractive("../session/login.php");
require_once ('classes/Security.inc');
require_once ('ossim_db.inc');
require_once ('ossim_conf.inc');
require_once ('classes/DateDiff.inc');
include_once ("geoip.inc");

$db         = new ossim_db();
$dbconn     = $db->connect();

$conf       = $GLOBALS["CONF"];
$version    = $conf->get_conf("ossim_server_version", FALSE);
$pro        = ( preg_match("/pro|demo/i",$version) ) ? true : false;

$my_session = session_id();

function is_expired($time)
{
	$conf     = $GLOBALS["CONF"];
	$activity = strtotime($time);
	
	if (!$conf) {
		require_once 'ossim_db.inc';
		require_once 'ossim_conf.inc';
		$conf = new ossim_conf();
	}
		
	$expired_timeout = intval($conf->get_conf("session_timeout", FALSE)) * 60;
	
	if ($expired_timeout == 0)
		return false;
	
	$expired_date = $activity + $expired_timeout;
    $current_date = strtotime(date("Y-m-d H:i:s"));
	
	if ( $expired_date < $current_date )
		return true;
	else
		return false;
}


function get_country($ccode, $cname)
{
	if( $ccode == "" )
	    $flag = "";
	else
	{
	    if(ccode=="me"||ccode=="eu"||ccode=="ap"){
	        $flag = "";
	    }
		elseif ($ccode=="local")
		    $flag = '../forensics/images/homelan.png';
	    else
		    $flag = '../pixmaps/flags/'.$ccode.'.png';
	}
	
	return $flag;
}


function get_user_icon($login,$pro)
{
	require_once ('ossim_db.inc');
		
	$db      = new ossim_db();
    $dbconn  = $db->connect();
	$user    = Session::get_list($dbconn, "WHERE login='$login'");
					
	if ($pro)
	{
		// Pro-version
		if ($login == ACL_DEFAULT_OSSIM_ADMIN || $user[0]->get_is_admin())
			return "../pixmaps/user-gadmin.png";
		elseif (Acl::is_proadmin($dbconn,$user[0]->get_login()))
			return "../pixmaps/user-business.png";
		else 
			return "../pixmaps/user-green.png";
	} 
	else 
	{
		// Opensource
		if ($login == ACL_DEFAULT_OSSIM_ADMIN || $user[0]->get_is_admin())
			return "../pixmaps/user-gadmin.png";
		else
			return "../pixmaps/user-green.png";
	}
}

$where         = "";
$users         = array();
$allowed_users = array();


if  ( Session::am_i_admin() || ($pro && Acl::am_i_proadmin()) )
{
	if ( Session::am_i_admin() )
		$users_list = Session::get_list($dbconn, "ORDER BY login");
	else
		$users_list = Acl::get_my_users($dbconn,Session::get_session_user());
	
	
	if ( is_array($users_list) && !empty($users_list) )
	{
		foreach($users_list as $k => $v)
			$users[] = ( is_object($v) )? $v->get_login() : $v["login"];
		
		$users[] = Session::get_session_user();
			
		$where = "WHERE login in ('".implode("','",$users)."')";
	}
}
else 
	$where = "WHERE login = '".Session::get_session_user()."'";

$allowed_users = Session_activity::get_list($dbconn, $where." ORDER BY activity desc");


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<title> <?=_("Opened Sessions")?> </title>
		<META HTTP-EQUIV="Pragma" content="no-cache"/>
		<link rel="stylesheet" href="../style/style.css"/>
		<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
		<script type="text/javascript" src="../js/jquery.simpletip.js"></script>
		
		<script type="text/javascript">
						
			function logout(id_session)
			{
				$("#ops_load").html(msg[0]);
				
				$.ajax({
					type: "POST",
					url: "forced_logout.php",
					data: "id="+ id_session,
					success: function(html){
						var status = html.split("###");
						$('#ops_load').html("<div id='ops_info'></div>");
						
						if ( status[0] == "error")
						{
							$('#ops_info').addClass('ossim_error');
							$("#ops_info").html(status[1]);
							$('#ops_load').fadeIn(2000);
							setTimeout('$("#ops_load").fadeOut(4000);', 4000);
						}
						else
						{	
							if (status[0] == 1)
							{
								$('#'+id_session).remove();
								
								if ( $('#ops_table tbody tr').length == 0 )
								{
									$('#ops_table thead').remove();
									$('#ops_table tbody').append("<tr><td colspan='8' id='no_sessions' class='nobborder'><div class='ossim_info'>"+msg[1]+"</td></tr>")	
								}
							}
						}		
												
						
					}
				});
			}
			
			$(document).ready(function(){
				$(".info_agent").simpletip({
					position: 'right',
					baseClass: 'ytooltip',
					fixed: true, 
					position: ["0", "25"],
					onBeforeShow: function() { 
						var txt = this.getParent().attr('txt');
						this.update(txt);
					}
				});
			});
			
		</script>
		
		<script type="text/javascript">
			
			var msg = new Array();
				msg[0]  = '<img src="../pixmaps/loading3.gif" border="0" align="absmiddle"/><span style="padding-left: 5px;"><?php echo _("Processing action")?>...</span>';
				msg[1]  = '<span><?php echo _("No active sessions")?></span>';
		</script>
		
		
		<style type='text/css'>
			a {cursor: pointer; }
			a:hover { text-decoration: none;}
			#container_center { width:90%; margin:30px auto 10px auto;}
			#ops_table { width: 100%; border: none;}
			#ops_table th {padding: 3px 0px;}
			.user_icon {margin: 0px 3px 0px 10px;}
			.ops_user {text-align: left; write-space: nowrap;}
			.ops_actions { width: 50px;}
			.dis_logout {
				filter:alpha(opacity=50);
				-moz-opacity:0.5;
				-khtml-opacity: 0.5;
				opacity: 0.5; 
				cursor: default;
			}
			.ops_host img {margin-left: 5px;}
			
			#cont_ops_load {width: 80%; margin:auto; position:relative;}
			#ops_load {position: absolute; width: 100%; top: 0px; left:0px; text-align:center;}
			
			.ossim_success, .ossim_error {width: auto;}
			.ossim_info {text-align: center;}
			
			.ytooltip {
				text-align:left;
				position: absolute;
				padding: 5px;
				z-index: 10;

				color: #303030;
				background-color: #f5f5b5;
				border: 1px solid #DDDDDD;

				font-family: arial;
				font-size: 12px;
				text-decoration: none;
			}
			
			.ops_refresh {margin-top: 20px; width: 100%; height: 30px;}
			.ops_refresh img { float: right; margin-right: 5px;}
			
			
		</style>
		
	</head>
	
	<body>
	
	<?php include ("../hmenu.php"); ?>
		
	<div id='cont_ops_load'><div id='ops_load'></div></div>
		
	<div id='container_center'>
		
		<div class='ops_refresh'>
			<a href='opened_sessions.php'><img src='../pixmaps/refresh.png' alt='<?php echo _("Refresh"); ?>' title='<?php echo _("Refresh"); ?>'/></a>
		</div>
									
		<table id='ops_table'>
			<?php  if ( !empty($allowed_users) ) { ?>
			<thead>
				<tr>
					<th><?php echo _("Username")?></th>
					<th><?php echo _("IP Address")?></th>
					<th><?php echo _("Hostname")?></th>
					<th><?php echo _("Agent")?></th>
					<th><?php echo _("Session ID")?></th>
					<th><?php echo _("Logon")?></th>
					<th><?php echo _("Last activity")?></th>
					<th><?php echo _("Actions")?></th>
				</tr>
			</thead>
			<?php } ?>
			
			<tbody>
			<?php
				
				if ( !empty($allowed_users) )
				{
					foreach ($allowed_users as $user)
					{
						if ($user->get_id() == $my_session)
						{
							$me = "style='font-weight: bold;'";
							$action = "<img class='dis_logout' src='../pixmaps/menu/logout.gif' alt='".$user->get_login()."' title='".$user->get_login()."'/>";
						}
						else
						{
							$action = "<a onclick=\"logout('".$user->get_id()."');\"><img src='../pixmaps/menu/logout.gif' alt='"._("Logout")." ".$user->get_login()."' title='"._("Logout")." ".$user->get_login()."'/></a>";	
							$me = null;
						}						
						$gi             = geoip_open("/usr/share/geoip/GeoIP.dat", GEOIP_STANDARD);
						$s_country      = strtolower(geoip_country_code_by_addr($gi, $user->get_ip()));
						$s_country_name = geoip_country_name_by_addr($gi, $user->get_ip());
						$geo_code       = get_country($s_country, $s_country_name);
						$flag           = ( !empty($geo_code) ) ?  "<img src='".$geo_code."' border='0' align='top'/>" : "";
						
						$style          = ( is_expired($user->get_activity()) ) ? "background:#EFE1E0;" : "background:#EFFFF7;";	
						$expired        = ( is_expired($user->get_activity()) ) ? "<span style='color:red'>("._("Expired").")</span>" : "";
						$agent          = explode("###", $user->get_agent()); 	
						if ($agent[1]=="av report scheduler") $agent = array("AV Report Scheduler","wget");
						
						echo "  <tr style='$style' id='".$user->get_id()."'>
									<td class='ops_user' $me><img class='user_icon' src='".get_user_icon($user->get_login(), $pro)."' alt='"._("User icon")."' title='"._("User icon")."' align='absmiddle'/> ".$user->get_login()."</td>
									<td class='ops_ip'>".$user->get_ip()."</td>
									<td class='ops_host'>".Host::ip2hostname($dbconn, $user->get_ip()).$flag."</td>
									<td class='ops_agent'><a txt='".$agent[1]."' class='info_agent'>".$agent[0]."</a></td>
									<td class='ops_id'>".$user->get_id()." $expired</td>
									<td class='ops_logon'>".$user->get_logon_date()."</td>							
									<td class='ops_activity'>"._(TimeAgo(strtotime($user->get_activity())))."</td>
									<td class='ops_actions'>$action</td>	
								</tr>";
					}
				}
				else
					echo "<tr><td colspan='8' id='no_sessions' class='nobborder'><div class='ossim_info'>"._("No active sessions")."</td></tr>";
			?>
			</tbody>
		</table>
				
	</div>

	</body>
</html>

<?
$db->close($dbconn);
?>






