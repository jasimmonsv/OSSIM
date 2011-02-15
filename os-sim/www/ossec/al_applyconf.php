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

require_once ('classes/Session.inc');
require_once ('classes/Ossec.inc');
require_once ('classes/Security.inc');
require_once ('conf/_conf.php');
require_once ('utils.php');

$info_error    = null;

$path  		   =  $ossec_conf;
$path_tmp      =  "/tmp/".uniqid()."_tmp.conf";
$path_reload   =  "/var/ossec/agentless/.reload";	
$path_passlist =  "/var/ossec/agentless/.passlist";	
$path_tmp2     =  "/tmp/".uniqid()."_tmp.passlist";


if ( @copy ($path , $path_tmp) == false )
	$info_error = _("Failed to create temporary copy of")." <b>$ossec_conf</b>";
	
if ( @copy ($path_passlist , $path_tmp2) == false )
	$info_error = _("Failed to create temporary copy of")." <b>$path_passlist</b>";
else
{	
	exec("cat /dev/null > $path_passlist", $output, $retval);	
	$output = null;
}
	
$result = test_conf(); 
	
if ( $result !== true )
{
	$info_error = "<div class='errors_ossec'>$result</div>";
	$error_conf = true;
}
	
	
$db 	         = new ossim_db();
$conn   	     = $db->connect();
$info_error      = null;
$error           = array();
$agentless_list  = array();
$agentless_xml   = array();


$agentless_list = Agentless::get_list($conn, "");

if ( !empty($agentless_list) && empty($info_error) )
{

	foreach ($agentless_list as $k => $v)
	{
		$ip = $k;
						
		$extra     = "WHERE ip = '$ip'";
		$monitoring_entries = Agentless::get_list_m_entries($conn, $extra);
		
		$user   = $v->get_user();
		$host   = ( !empty($ppass) ) ? "use_su " : "";
		$host  .= $user."@".$ip;
		$pass   = $v->get_pass();
		$ppass  = $v->get_ppass();
		$status = $v->get_status();
		
		if ( $status == "1" || $status == "2" )
		{
			$res = Agentless::enable_host($ip, $user, $pass, $ppass);
			
			if ($res == false)
			{
				$info_error = _("Error to add Agentless Host");
				@copy ($path_tmp2, $path_passlist);
				break;
			}
		}
		
		if ( is_array($monitoring_entries) && $status != 0 )
		{
			foreach ($monitoring_entries as $i => $entry)
			{
				
				$arguments       = ( !empty($entry['arguments']) ) ? "<arguments>".$entry['arguments']."</arguments>" : "";
								
				$agentless_xml[] = "<agentless>";
				$agentless_xml[] = "<type>".$entry['id_type']."</type>";
				$agentless_xml[] = "<frequency>".$entry['frequency']."</frequency>";
				$agentless_xml[] = "<host>$host</host>";
				$agentless_xml[] = "<state>".$entry['state']."</state>";
				$agentless_xml[] = "$arguments";
				$agentless_xml[] = "</agentless>";
				
			}
		}
	}
	
	$db->close($conn);
	
	if ( !empty($agentless_xml) && empty($info_error) )
	{
		$conf_file   = @file_get_contents($ossec_conf);
		$pattern     = '/\s*[\r?\n]+\s*/';
		$conf_file   = preg_replace($pattern, "", $conf_file);
		$copy_cf     = $conf_file;
		
		
		$pattern     = array('/<\/\s*agentless\s*>/');
		$replacement = array("</agentless>\n");
		$conf_file   = preg_replace($pattern, $replacement, $conf_file);
		
		
		preg_match_all('/<\s*agentless\s*>.*<\/agentless>/', $conf_file, $match);
		
		$size_m    = count($match[0]);
		$unique_id = uniqid();
						
		if ($size_m > 0)
		{
			for ($i=0; $i<$size_m-1; $i++)
			{
				$pattern   = trim($match[0][$i]);
				$copy_cf   = str_replace($pattern, "", $copy_cf);
			}
			
			$pattern   = trim($match[0][$size_m-1]);
			$copy_cf   = str_replace($pattern, $unique_id, $copy_cf);
		}
		else
		{
			if ( preg_match("/<\s*ossec_config\s*>/", $copy_cf) )
				$copy_cf = preg_replace("/<\/\s*ossec_config\s*>/", "$unique_id</ossec_config>", $copy_cf, 1);
			else
				$copy_cf = "<ossec_config>$unique_id</ossec_config>";
		}
						
		$agentless_xml = implode("",$agentless_xml);
		$copy_cf       = preg_replace("/$unique_id/", $agentless_xml, $copy_cf);
		$output 	   = formatXmlString($copy_cf);
		
		if (@file_put_contents($path, $output, LOCK_EX) == false)
		{
			@unlink ($path);
			@copy ($path_tmp, $path);
			$info_error = _("Failure to update")." <b>$ossec_conf</b>";
		}
		else
		{
			$result = test_conf(); 
	
			if ( $result !== true )
			{
				$info_error = "<div class='errors_ossec'>$result</div>";
				$error_conf = true;
				@copy ($path_tmp, $path);
				@copy ($path_tmp2, $path_passlist);
			}
			else
			{
				$result     = system("sudo /var/ossec/bin/ossec-control restart > /tmp/ossec-action 2>&1");
				$result     = file('/tmp/ossec-control');
				$size       = count($result);
				$info_error = ( preg_match('/Completed/', $result[$size]) == false ) ? _("Error to restart Ossec.  Try manually") : null;
			}
		}
	}
}

if ($info_error == null)
	@unlink ($path_reload);


@unlink ($path_tmp2);	
@unlink ($path_tmp);


?>

	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title> <?php echo _("OSSIM Framework"); ?> </title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
		<meta http-equiv="Pragma" content="no-cache"/>
		<link rel="stylesheet" type="text/css" href="../style/style.css"/>
		<link rel="stylesheet" type="text/css" href="css/ossec.css"/>
		<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
		<script type="text/javascript">
			function go_sec(sec)
			{
				if (sec == "config")
					document.location.href='config.php';
				else if (sec == "rules")
					document.location.href='index.php';
			}
		</script>
		<style type='text/css'>
			#apply_ok { font-size: 13px; padding: 10px; width: 80%; margin: auto; text-align: center;}
		</style>
	</head>

	<body>
		<?php include("../hmenu.php"); ?>
		<h1> <?php echo gettext("Apply configuration"); ?> </h1>

	<?php
		if ($info_error != null)
		{
			Util::print_error($info_error);	
			if ( $error_conf == true )
			{
				?>
				<div style='margin:auto; width:90%'>
					<form method='POST' name='sec' id='sec'>
						<div style='margin:auto; text-align: center;'>
							<input type='button' name='send1' id='send1' class='button' value='<?php echo _("Go to Config")?>' onclick='go_sec("config");'/>
							<input type='button' name='send2' id='send2' class='button' value='<?php echo _("Go to Edit Rules")?>' onclick='go_sec("rules");'/>
						</div>
					</form>
				</div>
				<?php
			}
			else
				Util::make_form("POST", "agentless.php");
		}
		else
		{
			echo "<div id='apply_ok'>"._("Configuration applied")."</div>";
			echo "<script>document.location.href='agentless.php'</script>";
		}
	?>
	  
	</body>
	</html>

