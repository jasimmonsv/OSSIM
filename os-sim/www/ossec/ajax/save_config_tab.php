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
require_once ('../conf/_conf.php');
require_once ('../utils.php');

$error      = false;
$tab_ok     = null;
$no_action  = false;
$path  		= $ossec_conf;
$path_tmp   = "/tmp/".uniqid()."_tmp.conf";

if ( @copy ($path , $path_tmp) == false )
{
	echo "2###"._("Failure to update")." <b>$ossec_conf</b>";
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
		
		$size = count($conf_file);
						
		for ($i=$size-1; $i>0; $i--)
		{
			if ( preg_match("/<\s*ossec_config\s*>/", $conf_file[$i]) )
			{
				unset($conf_file[$i]);
				$aux       = array_slice($copy_cf, $i);
				$conf_file = array_merge($conf_file, $xml_rules, $aux);
				break;
			}
			else
				unset($conf_file[$i]);
		}
	}
		
	
	$conf_file_str = implode("\n", $conf_file);
	$output        = formatXmlString($conf_file_str);
	
	$tab_ok    = "1###<b>$ossec_conf "._("updated sucessfully")."</b>";
		
}
else if($tab == "#tab2")
{
	$info_error  = null;
	$directories = array();
	$ignores     = array();
	
	$dir_checks_names = array("realtime", "report_changes", "check_all", "check_sum","check_sha1sum", "check_size","check_owner","check_group","check_perm");
		
	unset($_POST['tab']);
	
	$node_sys  = "<syscheck>";
	
	$frequency    = POST('frequency'); 
	ossim_valid($frequency, OSS_DIGIT, 'illegal:' . _("Frequency"));
			
	if ( ossim_error() ) 
	{
		$info_error[] = ossim_get_error();
		ossim_clean_error();
	}
	else
		$node_sys .= "<frequency>$frequency</frequency>";
	
	unset($_POST['frequency']);
	
	$dir = 0;
	$ign = 0;
	
	foreach ($_POST as $k => $v)
	{
		if ( preg_match('/(.*)_value_dir/', $k, $match) )
		{
			$dir++;
			$keys_dir[$match[1]] = $v;
			ossim_valid($v, OSS_ALPHA, OSS_PUNC_EXT, OSS_SLASH, 'illegal:' . _("Directory/File monitored"));
			if ( ossim_error() )
			{
				$info_error[] = ossim_get_error().". Input num. $ign"; 
				ossim_clean_error();
			}
		}
		else if ( preg_match('/(.*)_value_ign/', $k, $match) )
		{
			$ign++;
			$keys_ign[$match[1]] = $v;
			ossim_valid($v, OSS_ALPHA, OSS_PUNC_EXT, OSS_SLASH, 'illegal:' . _("Directory/File ignored"));
			if ( ossim_error() )
			{
				$info_error[] = ossim_get_error().". Input num. $ign"; 
				ossim_clean_error();
			}
		}	
	}

	if ( !empty($info_error) )
	{
		$info_error	= "<div style='text-align:left; padding-left: 60px;'>"._("We Found the following errors").":</div><div style='padding:10px 5px 10px 80px; text-align:left;'>".implode( "<br/>", $info_error)."</div>";
		echo "2###".$info_error;
		@unlink ($path);
		@copy ($path_tmp, $path);
		exit();
	}	
	
	
	foreach ($keys_dir as $k => $v)
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
	
	foreach ($keys_ign as $k => $v)
	{
		$node_sys  .= "<ignore";
		$name = $k."_type";
		if ( isset($_POST[$name]) )
				$node_sys .= " type=\"sregex\""; 
		$node_sys  .= ">$v</ignore>";
	}
	
	$node_sys .= "</syscheck>";
	
	
	$conf_file   = file_get_contents($ossec_conf);
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
		$copy_cf = preg_replace("/<\/\s*ossec_config\s*>/", "$unique_id</ossec_config>", $copy_cf, 1);
	
	
	$copy_cf = preg_replace("/$unique_id/", $node_sys, $copy_cf);
	$output  = formatXmlString($copy_cf);
	
	$tab_ok    = "1###<b>$ossec_conf</b> "._("updated sucessfully");
}
else if($tab == "#tab3")
{
	$data   = html_entity_decode(base64_decode($_POST['data']),ENT_QUOTES, "UTF-8");
	$tab_ok = "1###<b>$ossec_conf "._("updated sucessfully")."</b>";
}
else
{
	$no_action = true;
	echo "2###"._("Error: Illegal actions");
}

if ($no_action == false)
{
	if ( @file_put_contents($path, $data, LOCK_EX) == false )
	{
		echo "2###"._("Failure to update")." <b>$ossec_conf</b> (2)";
		echo $tab_error;
	}
	else
	{
		$result = test_conf(); 	
					
		if ( $result !== true )
			echo "3###".$result;
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