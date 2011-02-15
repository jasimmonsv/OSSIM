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
require_once ('classes/Security.inc');
require_once ('classes/Util.inc');
require_once ('classes/Xml_parser.inc');
require_once ('../conf/_conf.php');
require_once ('../utils.php');

$error      = false;
$tab_ok     = null;
$no_action  = false;
$path  		= $ossec_conf;
$path_tmp   = "/tmp/".uniqid()."_tmp.conf";

if ( @copy ($path , $path_tmp) == false )
{
	echo "2###"._("Failure to update")." <strong>$ossec_conf</strong>";
	exit();
}

$tab = POST('tab');

if($tab == "#tab1")
{
	$rules_enabled  = $disabled_rules = $xml_rules = array();
	
	$rules          = $_SESSION['_cnf_rules'];
	$rules_enabled  = POST('rules_added');
	
	$all_rules      = get_files ($rules_file);
	$disabled_rules = array_diff($all_rules, $rules_enabled);
	
	$conf_file 		= file_get_contents($ossec_conf);
	
	if ($conf_file === false)
	{
		$info_error	= "<div style='text-align:left; padding-left: 60px;'>"._("Failure to read")." <strong>$ossec_conf</strong></div>";
		echo "2###".$info_error;
		@unlink ($path);
		@copy ($path_tmp, $path);
		exit();
	}
	
	
	$conf_file      = formatXmlString($conf_file);
	$pattern   		= '/[\r?\n]+\s*/';
	$conf_file      = preg_replace($pattern, "\n", $conf_file);
	$conf_file      = explode("\n", trim($conf_file));
	$copy_cf        = $conf_file;
	
	
	foreach ($rules_enabled as $k => $v)
		$xml_rules[] = "<include>".$v."</include>"; 
				
	foreach ($disabled_rules as $k => $v)
		$xml_rules[] = "<!-- <include>".$v."</include> -->"; 
		
	
	if ( !empty($rules) )
	{
		if ( count($rules)> 1 )
		{
			for ($i=1; $i<count($rules); $i++)
			{
				for ($j=$rules[$i]['start']; $j <=$rules[$i]['end']; $j++)
					unset($conf_file[$j]);
			}
		}
		
		$aux_1     = array_slice($conf_file, 0, $rules[0]['start']+1);
		$aux_2     = array_slice($conf_file, $rules[0]['end']);
		$conf_file = array_merge($aux_1, $xml_rules, $aux_2);
		
	}
	else
	{
		$xml_rules = array_merge(array("<rules>"), $xml_rules, array("</rules>"));
		
		$size  = count($conf_file);
		$found = false;
						
		for ($i=$size-1; $i>0; $i--)
		{
			if ( preg_match("/<\s*ossec_config\s*>/", $conf_file[$i]) )
			{
				unset($conf_file[$i]);
				$aux       = array_slice($copy_cf, $i);
				$conf_file = array_merge($conf_file, $xml_rules, $aux);
				$found     = true;
				break;
			}
			else
				unset($conf_file[$i]);
		}
		
		if ($found == false)
			$conf_file = array_merge(array("<ossec_config>"), $xml_rules, array("</ossec_config>"));
	}
		
	
	
	$conf_file_str = implode("\n", $conf_file);
	$output        = formatXmlString($conf_file_str);
	
	$tab_ok        = "1###<b>$ossec_conf "._("updated sucessfully")."</b>";
		
}
else if($tab == "#tab2")
{
	
	$info_error  = null;
	$directories = array();
	$ignores     = array();
	$wentries    = array();
	$reg_ignores = array();
	
	$dir_checks_names = array("realtime", "report_changes", "check_all", "check_sum","check_sha1sum", "check_size","check_owner","check_group","check_perm");
		
	unset($_POST['tab']);
	
	$node_sys  = "<syscheck>";
	
	
	$parameters ['frequency']       = POST('frequency'); 
	$parameters ['scan_day']        = POST('scan_day'); 
	$parameters ['scan_time']       = ( empty($_POST['scan_time_h']) && empty($_POST['scan_time_m'])) ? null : POST('scan_time_h').":".POST('scan_time_m'); 
	$parameters ['auto_ignore']     = POST('auto_ignore'); 
	$parameters ['alert_new_files'] = POST('alert_new_files'); 
	$parameters ['scan_on_start']   = POST('scan_on_start'); 
	
	$regex_wd    = "'monday|tuesday|wednesday|thursday|friday|saturday|sunday'";
	$regex_time  = "'regex:[0-1][0-9]|2[0-3]:[0-5][0-9]'";
	$regex_yn    = "'yes|no'";
	
	$validate  = array (
				"frequency"       => array("validation" => "OSS_DIGIT" ,"e_message" => 'illegal:' . _("Frequency")),
				"scan_day"        => array("validation" => $regex_wd   ,"e_message" => 'illegal:' . _("Scan day")),
				"scan_time"       => array("validation" => $regex_time ,"e_message" => 'illegal:' . _("Scan time")),
				"auto_ignore"     => array("validation" => $regex_yn   ,"e_message" => 'illegal:' . _("Auto ignore")),
				"alert_new_files" => array("validation" => $regex_yn   ,"e_message" => 'illegal:' . _("Alert new files")),
				"scan_on_start"   => array("validation" => $regex_yn   ,"e_message" => 'illegal:' . _("Scan on start")));
	
	foreach ($parameters as $k => $v )
	{
		if ( !empty ($v) )
		{
			eval("ossim_valid(\$v, ".$validate[$k]['validation'].", '".$validate[$k]['e_message']."');");
		
			if ( ossim_error() )
			{
				$info_error[] = ossim_get_error();
				ossim_clean_error();
			}
			else
				$node_sys .= "<$k>$v</$k>";
				
		}
		
		unset($_POST[$k]);
			
	}
	
	$dir = $ign = $went = $regi = 0;
	
	$regex      = array ("dir"   =>  "(.*)_value_dir", 
						 "ign"   =>  "(.*)_value_ign", 
						 "went"  =>  "(.*)_value_went", 
						 "regi"  =>  "(.*)_value_regi");
	
	$err_msn    = array ("dir"   =>  _("Directory/File monitored"), 
						 "ign"   =>  _("Directory/File ignored"),
	                     "went"  =>  _("Windows registry entry"), 
	                     "regi"  =>  _("Registry ignore"));
	
	$keys       = array ();  
	
	$indexes    = array ("dir"   =>  0, 
						 "ign"   =>  0,
	                     "went"  =>  0, 
	                     "regi"  =>  0);
	
	
	foreach ($_POST as $k => $v)
	{
		foreach ( $regex as $i => $r )
		{
			if ( preg_match("/$r/", $k, $match) )
			{
				$indexes[$i]         = $indexes[$i]++;
				$keys[$i][$match[1]] = $v;
								
				ossim_valid($v, OSS_ALPHA, OSS_PUNC_EXT, OSS_SLASH, OSS_NULLABLE, 'illegal:' . $err_msn[$i]);
				
				if ( ossim_error() )
				{
					$info_error[] = ossim_get_error().". Input num. " . $indexes[$i]; 
					ossim_clean_error();
				}
				break;
			}
		}
	}
	
	if ( !empty($info_error) )
	{
				
		$format_error  = "<div id='parse_errors'><span style='font-weight: bold;'>"._("There are several errors")."<a onclick=\"$('#msg_errors').toggle();\"> ["._("View errors")."]</a><br/></span>";
		$format_error .= "<div id='msg_errors'>".implode( "<br/>", $info_error)."</div></div>";
					
		
		echo "2###".$format_error;
		@unlink ($path);
		@copy ($path_tmp, $path);
		exit();
	}	
	
		
	foreach ($keys['dir'] as $k => $v)
	{
		$node_sys  .= "<directories";
		for ($i=0; $i<=9; $i++)
		{
			$name = $dir_checks_names[$i]."_".$k."_".($i+1);
			if ( isset($_POST[$name]) )
				$node_sys .= " ".$dir_checks_names[$i]."=\"yes\""; 
		
		}
		$node_sys  .= ">$v</directories>";
	
	}
	
	foreach ($keys['ign'] as $k => $v)
	{
		$node_sys  .= "<ignore";
		$name = $k."_type";
		if ( isset($_POST[$name]) )
				$node_sys .= " type=\"sregex\""; 
		$node_sys  .= ">$v</ignore>";
	}
	
	foreach ($keys['went'] as $k => $v)
		$node_sys  .= "<windows_registry>$v</windows_registry>";
	
	
	foreach ($keys['regi'] as $k => $v)
		$node_sys  .= "<registry_ignore>$v</registry_ignore>";
		
	$node_sys .= "</syscheck>";
	
	
	$conf_file = file_get_contents($ossec_conf);
	
	if ($conf_file === false)
	{
		$info_error	= "<div style='text-align:left; padding-left: 60px;'>"._("Failure to read")." <strong>$ossec_conf</strong></div>";
		echo "2###".$info_error;
		@unlink ($path);
		@copy ($path_tmp, $path);
		exit();
	}
	
			
	
	$pattern     = '/\s*[\r?\n]+\s*/';
	$conf_file   = preg_replace($pattern, "", $conf_file);
	$copy_cf     = $conf_file;
	
	$pattern     = array('/<\/\s*syscheck\s*>/');
	$replacement = array("</syscheck>\n");
	$conf_file   = preg_replace($pattern, $replacement, $conf_file);
	
	
	preg_match_all('/<\s*syscheck\s*>.*<\/syscheck>/', $conf_file, $match);
	
	$size_m    = count($match[0]);
	$unique_id = uniqid();
	
	if ($size_m > 0)
	{
		for ($i=0; $i<$size_m-1; $i++)
		{
			$pattern = trim($match[0][$i]);
			$copy_cf = str_replace($pattern, "", $copy_cf);
		}
		
		$pattern = trim($match[0][$size_m-1]);
		$copy_cf = str_replace($pattern, $unique_id, $copy_cf);
	}
	else
	{
		if ( preg_match("/<\s*ossec_config\s*>/", $copy_cf) )
			$copy_cf = preg_replace("/<\/\s*ossec_config\s*>/", "$unique_id</ossec_config>", $copy_cf, 1);
		else
			$copy_cf = "<ossec_config>$unique_id</ossec_config>";
	}
	
	
	$copy_cf = preg_replace("/$unique_id/", $node_sys, $copy_cf);
	$output  = formatXmlString($copy_cf);
	
	$tab_ok  = "1###<b>$ossec_conf</b> "._("updated sucessfully");
}
else if($tab == "#tab3")
{
	$output   = html_entity_decode(base64_decode($_POST['data']),ENT_QUOTES, "UTF-8");
	$tab_ok   = "1###<b>$ossec_conf "._("updated sucessfully")."</b>";
}
else
{
	$no_action = true;
	echo "2###"._("Error: Illegal actions");
}

if ($no_action == false)
{
	if ( @file_put_contents($path, $output, LOCK_EX) == false )
	{
		echo "2###"._("Failure to update")." <b>$ossec_conf</b> (2)";
		echo $tab_error;
	}
	else
	{
		$result = test_conf(); 	
					
		if ( $result !== true )
		{
			echo "3###".$result;
			$error = true;
		}
		else
			echo $tab_ok;
	}
	
	if ( $error == true )
	{
		@unlink ($path);
		@copy ($path_tmp, $path);
	}	

	
}

@unlink($path_tmp);	


	






?>