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

$error   = false;

$path  		= $ossec_conf;
$path_tmp   = "/tmp/".uniqid()."_tmp.conf";


$tab     = POST('tab');

if($tab == "#tab1")
{
	$rules_enabled  = $disabled_rules = $xml_rules = array();
	
	$rules             = $_SESSION['_cnf_rules'];
	$rules_enabled     = POST('rules_added');
	
	$all_rules         = get_files ($rules_file);
	$disabled_rules    = array_diff($all_rules, $rules_enabled);
	
	$conf_file 		   = file_get_contents($ossec_conf);
	
	$pattern   		   = '/[\r?\n]+\s*/';
	$conf_file         = preg_replace($pattern, "\n", $conf_file);
	$conf_file         = explode("\n", trim($conf_file));
	$copy_cf           = $conf_file;
	
	
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
			if ( preg_match("/<\/ossec_config>/", $conf_file[$i]) )
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
	
	if ( @copy ($path , $path_tmp) == false )
		echo "error###"._("Failure to update")." <b>$ossec_conf</b> (1)";
	else
	{  
		$conf_file_str = implode("\n", $conf_file);
		$output        = formatXmlString($conf_file_str);
		
		if (@file_put_contents($path, $output, LOCK_EX) == false)
		{
			@unlink ($path);
			@copy ($path_tmp, $path);
			echo "error###"._("Failure to update")." <b>$ossec_conf</b> (2)";
		}
		else
			echo "1###<b>$ossec_conf "._("updated sucessfully")."</b>";
			
		@unlink ($path_tmp);
	}
	
	
}
else if($tab == "#tab2")
{

}
else if($tab == "#tab3")
{
	$aux_path  = explode("/", $ossec_conf);
	$filename  = $aux_path[count($aux_path)-1]; 
	$file_tmp  = uniqid($filename)."_tmp.conf";
	$path      = $ossec_conf;
	$path_tmp  = "/tmp/".$file_tmp; 
	$data      = html_entity_decode(base64_decode($_POST['data']),ENT_QUOTES, "UTF-8");

	if (copy ($path , $path_tmp) == false )
		echo "error###"._("Error to update")." <b>$ossec_conf</b> (1)";
	else
	{
		if ( @file_put_contents($path, $data, LOCK_EX) == false )
		{
			@unlink ($path);
			@copy ($path_tmp, $path);
			echo "error###"._("Error to update")." <b>$ossec_conf</b> (2)";
		}
		else
		{
			echo "1###<b>$ossec_conf "._("updated sucessfully")."</b>";
		}
		
		@unlink($path_tmp);	
	}
}
else
	echo "error###"._("Error: Illegal actions");





?>