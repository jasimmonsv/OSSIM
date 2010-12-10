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

$tab     = POST('tab');

if($tab == "#tab1")
{
	$rules = array();
	$conf_file = file_get_contents($ossec_conf);
	
	$pattern   = '/[\r?\n]+\s*/';
	$conf_file = preg_replace($pattern, "\n", $conf_file);
	$conf_file = explode("\n", trim($conf_file));
	
		
	if ( is_array($conf_file) )
	{
		$start_tag = 0;
		$end_tag   = 0;
		$num_found = 0;
		$rules     = $rules_enabled = $rules_disabled = array(); 
				
		foreach ($conf_file as $k => $v)
		{
			if ( preg_match("/<rules>|<\/rules>/", $v, $match) )
			{
				if ($match[0] == "<rules>")
				{
					$num_found++;
					$start_tag++;
					$rules[$num_found-1]['start'] = $k;
					$rules[$num_found-1]['end']   = null;
				}
				
				if ($match[0] == "</rules>")
				{
					$end_tag++;
					$rules[$num_found-1]['end'] = $k;
				}
			}
			else
			{
				if ( $num_found > 0 && ($end_tag == $start_tag-1) )
				{
					
					$pattern1 = "/^<include>(.*)<\/include>/";
					$pattern2 = "/<!--\s*<include>(.*)<\/include>\s*-->/";
					if ( preg_match($pattern1, $v, $match) )
					{
						$rules[$num_found-1]['rules'][$match[1]]['value']  = $v;
						$rules[$num_found-1]['rules'][$match[1]]['status'] = "enabled";
						$rules_enabled[] = $match[1];
					}
					else
					{ 
						if ( preg_match($pattern2, $v, $match) )
						{
							$rules[$num_found-1]['rules'][$match[1]]['value']  = $v;
							$rules[$num_found-1]['rules'][$match[1]]['status'] = "disabled";
							$rules_disabled[] = $match[1];
						}
					}
				}	
			}
		
		}
				
		
		//Check Parse errors
		foreach ($rules as $k => $v)
		{
			$error = false;
			
			if ( !is_numeric($rules[$k]['start']) && !is_numeric($rules[$k]['start']) ) 
				$error = true;
			else if ( $rules[$k]['start'] > $rules[$k]['end'] )
				$error = true;
			else
			{
				if (count($rules[$k]['rules']) <= 0)
					$error = true;
			}
			
			if ($error == true)
			{
				echo "error###"._("Error to read configuration file (2)");
				exit;
			}
		}
		
		$_SESSION['_cnf_rules'] = $rules;
		
		
		echo "1###<div id='cnf_rules_cont'><table class='cnf_rules_table'>";
		
			$all_rules       = get_files ($rules_file);
						
			$no_added_rules  = array_diff($all_rules, $rules_enabled);	
			
			sort($rules_enabled);
			sort($no_added_rules);
			
						
			echo "<tr><td style='padding: 10px 0px;'>"._('* Drag & Drop the file you want to add/remove or use [+] and [-] links')."</td></tr>";
			echo "<tr><td class='cnf_rule_title'>";
			echo "<div style='float: left; width: 48%'>"._("Enabled Rules")."</div><div style='float: right; width: 48%'>"._("Disabled Rules")."</div></td></tr>";
			echo "<tr>";
				echo "<td style='padding: 3px 0px 20px 0px;'><form name='cnf_form_rules' id='cnf_form_rules' method='POST'><select id='rules_added' class='multiselect' multiple='multiple' name='rules_added[]'>";
					foreach($rules_enabled as $k => $v)
						echo "<option value='$v' selected='selected'>$v</option>";
					
					foreach($no_added_rules as $k => $v)
						echo "<option value='$v'>$v</option>";
				echo "</select></form></td>";
			echo "</tr>";
			
			echo "<tr><td><input type='button' class='button' id='send' value='"._('Send')."' onclick=\"save_config_tab();\"/></td></tr>";
		
		echo "</table></div>";
		
	}
	else
		echo "error###"._("Error to read configuration file (1)");
		
}
else if ($tab == '#tab2')
{
	if ( file_exists( $ossec_conf) )
	{
		$file_xml = file_get_contents ($ossec_conf, false);
	  
		if ($file_xml == false)
			$txt = "error###"._("<b>$ossec_conf</b> not found or you don't have have permission to access (1)");
		else
			echo $file_xml;
	}
	else
		$txt = "error###"._("<b>$ossec_conf</b> not found or you don't have have permission to access (2)");

}
else
	echo "error###"._("Error: Illegal actions");





?>