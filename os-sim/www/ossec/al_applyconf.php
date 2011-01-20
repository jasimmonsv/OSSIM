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
require_once ('classes/Util.inc');
require_once ('conf/_conf.php');
require_once ('utils.php');


$info_error  = null;

$path  		   =  $ossec_conf;
$path_tmp      =  "/tmp/".uniqid()."_tmp.conf";
$path_reload   =  "/var/ossec/agentless/.reload";	
$path_passlist =  "/var/ossec/agentless/.passlist";	
$path_tmp2     =  "/tmp/".uniqid()."_tmp.passlist";

exec("cat /dev/null > $path_passlist", $output, $retval);	
$retval = 0;
$output = null;

if ( @copy ($path , $path_tmp) == false )
	$info_error = _("Failed to create temporary copy of")." <b>$ossec_conf</b>";
	
if ( @copy ($path_passlist , $path_tmp2) == false || $retval != 0 )
	$info_error = _("Failed to create temporary copy of")." <b>$path_passlist</b>";
	

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
		
		if (is_array($monitoring_entries))
		{
			foreach ($monitoring_entries as $i => $entry)
			{
				$user   = $v->get_user();
				$host   = ( !empty($ppass) ) ? "use_su " : "";
				$host  .= $user."@".$ip;
				$pass   = $v->get_pass();
				$ppass  = $v->get_ppass();
				$status = $v->get_status();
				
				$arguments       = ( !empty($entry['arguments']) ) ? "<arguments>".$entry['arguments']."</arguments>" : "";
								
				$agentless_xml[] = "<agentless>";
				$agentless_xml[] = "<type>".$entry['type']."</type>";
				$agentless_xml[] = "<frequency>".$entry['frecuency']."</frequency>";
				$agentless_xml[] = "<host>$host</host>";
				$agentless_xml[] = "<state>".$entry['state']."</state>";
				$agentless_xml[] = "$arguments";
				$agentless_xml[] = "</agentless>";
				
				
				if ( $status == "1" || $status == "2" )
				{
				    $res = Agentless::enable_host($ip, $user, $pass, $ppass);
					
					if ($res == false)
					{
						$info_error = _("Error to add Agentless Host");
						@copy ($path_tmp2, $path_passlist);
						break 2;
					}
				}
				
			}
		}
	}
	
	$db->close($conn);
	
	
	if ( !empty($agentless_xml) && empty($info_error) )
	{
		$conf_file = file_get_contents($ossec_conf);
		$pattern   		   = '/[\r?\n]+\s*/';
		$conf_file         = preg_replace($pattern, "\n", $conf_file);
		$conf_file         = explode("\n", trim($conf_file));
		$copy_cf           = $conf_file;
		
		$agentless = array();
		
		$i 	 = 0;
		$num = 0;
						
				
		while ($i<count($conf_file))
		{
			if ( preg_match("/<\s*agentless\s*>/", $conf_file[$i], $match) )
			{
				$agentless[$num]['start'] = $i;
				for ($j=$i+1; $j<count($conf_file); $j++)
				{
					if ( preg_match("/<\/\s*agentless\s*>/", $conf_file[$j], $match) )
					{
						$agentless[$num]['end'] = $j;
						$num++;
						$i = $j++;
						break;
					}
				}
			}
			else
				$i++;
		}
								
		foreach ($agentless as $k => $v)
		{
			if ( is_numeric($v['start']) && is_numeric($v['end']) )
			{
				for ($i=$v['start']; $i<=$v['end']; $i++)
					unset($conf_file[$i]);
			}
			else
			{
				$info_error = "error###"._("Not valid format in")." <b>$ossec_conf</b>"; 
				break;
			}
		}
				
		
		//No agents are inserted
		if ( $num > 0 && empty($info_error) )
		{	
			$size = count($copy_cf);
							
			for ($i=$size-1; $i>0; $i--)
			{
				if ( preg_match("/<\/ossec_config>/", $copy_cf[$i]) )
				{
					unset($conf_file[$i]);
					$aux       = array_slice($copy_cf, $i, $size);
					$conf_file = array_merge($conf_file, $agentless_xml, $aux);
					break;
				}
				else
					unset($conf_file[$i]);
			}
		}
						
		
		$conf_file_str = implode("\n", $conf_file);
		$output 	   = formatXmlString($conf_file_str);
			
		if (@file_put_contents($path, $output, LOCK_EX) == false)
		{
			@unlink ($path);
			@copy ($path_tmp, $path);
			$info_error = _("Failure to update")." <b>$ossec_conf</b>";
		}
		else
		{
			@unlink ($path_tmp);
			@unlink ($path_reload);
			exec ("sudo /var/ossec/bin/ossec-control restart", $result, $ret);
			$info_error = ( $ret != 0 ) ? _("Error to restart Ossec.  Try manually") : null;
		}	
	}
}

?>

	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title> <?php echo _("OSSIM Framework"); ?> </title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
		<meta http-equiv="Pragma" content="no-cache"/>
		<link rel="stylesheet" type="text/css" href="../style/style.css"/>
		<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	</head>

	<body>
		<?php include("../hmenu.php"); ?>
		<h1> <?php echo gettext("Apply configuration"); ?> </h1>

	<?php
		if ($info_error != null)
		{
			Util::print_error($info_error);	
			Util::make_form("POST", "agentless.php");
		}
		else
		{
			echo "<p>"._("Configuration applied")."</p>";
			echo "<script>document.location.href='agentless.php'</script>";
		}
	?>
	  
	</body>
	</html>

