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
require_once ('classes/Xml_parser.inc');

$error     = false;
$tab       = POST('tab');
$conf_file = @file_get_contents($ossec_conf);
	
if ($conf_file == false)
{
	echo "2###"._("File")." <b>$ossec_conf</b> "._("not found or you don't have have permission to access");
	exit();
}

$result = test_conf(); 	
					
if ( $result !== true )
{
	echo "3###".$conf_file."###$result";
	exit;
}

if($tab == "#tab1")
{
	$rules     = array();
	$conf_file = formatXmlString($conf_file);
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
			if ( preg_match("/(<\s*rules\s*>)|(<\s*\/rules\s*>)/", $v, $match) )
			{
				if ( !empty($match[1]) )
				{
					$num_found++;
					$start_tag++;
					$rules[$num_found-1]['start'] = $k;
					$rules[$num_found-1]['end']   = null;
				}
				elseif ( !empty($match[2]) )
				{
					$end_tag++;
					$rules[$num_found-1]['end'] = $k;
				}
			}
			else
			{
				if ( $num_found > 0 && ($end_tag == $start_tag-1) )
				{
					
					$pattern1 = "/^<\s*include\s*>(.*)<\s*\/include\s*>/";
					$pattern2 = "/<!--\s*<\s*includev>(.*)<\s*\/include\s*>\s*-->/";
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
				echo "2###"._("Error to read configuration file")." (2)";
				exit;
			}
		}
		
		$_SESSION['_cnf_rules'] = $rules;
		
		
		echo "1###<div id='cnf_rules_cont'><table class='cnf_rules_table'>";
		
			$all_rules       = get_files ($rules_file);
						
			$no_added_rules  = array_diff($all_rules, $rules_enabled);	
			
			sort($rules_enabled);
			sort($no_added_rules);
			
						
			echo "<tr><td style='padding: 0px 0px 10px 0px;'>";
			echo "<span>(*) "._("You must restart Ossec for the changes to take effect")."</span><br/>";
			echo "<span>(*) "._('Drag & Drop the file you want to add/remove or use [+] and [-] links')."</span>";
			echo  "</td></tr>";
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
		echo "2###"._("Error to read configuration file")." (1)";
		
}
else if ($tab == '#tab2')
{
	
	$xml_obj = new xml("key");
	$xml_obj->load_file($ossec_conf);
	$array_oss_cnf = $xml_obj->xml2array();
	
	$syscheck = get_nodes($array_oss_cnf, 'syscheck');
	
	$directories = get_nodes($syscheck, 'directories');
	$ignores     = get_nodes($syscheck, 'ignore');
	$frequency   = get_nodes($syscheck, 'frequency');
	
	$directory_checks = array(
			"realtime"       => "Realtime",
			"report_changes" => "Report changes", 
			"check_all"      => "Chk all", 
			"check_sum"      => "Chk sum", 
			"check_sha1sum"  => "Chk sha1sum", 
			"check_size"     => "Chk size", 
			"check_owner"    => "Chk owner", 
			"check_group"    => "Chk group", 
			"check_perm"     => "Chk perm"				
	);
	
	echo "1###";
			
	?>
	<form name='form_syscheck' id='form_syscheck'>
	
		<div class='cont_sys'>
			<div class='headerpr'><?php echo _("Configuration parameters")?></div>
			<div id='cont_tsp'>
				<table id='table_sys_parameters'>
					<tr>	
						<th class='sys_frequency'><?php echo _("Frequency")?></th>
						<td class='left'><input type='text' id='frequency' name='frequency' value='<?php echo $frequency[0][0]?>'/></td>
					</tr>
					<tr>
						<td class='right' colspan='2'><input type='button' class='button' id='send' value='<?php echo _("Update")?>' onclick="save_config_tab();"/></td>
					</tr>	
				</table>
			</div>
		</div>
			
		<div class='cont_sys'>
			<div class='headerpr'><?php echo _("Files/Directories monitored")?></div>
		
			<div>
					
				<table id='table_sys_directories' width='100%'>
					<tr>	
						<th class='sys_dir'><?php echo _("Files/Directories")?></th>
						<td class='sys_parameters'>
							<table width='100%'>
								<tr><th colspan='<?php echo count($directory_checks)?>'><?php echo _("Parameters")?></th></tr>
								<tr>
									<?php 
										foreach ($directory_checks as $k => $v)
											echo "<th style='padding: 1px; font-size: 10px; text-align:center; white-space:normal; width: 50px;'>$v</th>";
									?>
								</tr>
							</table>
						</td>
						<th class='sys_actions'><?php echo _("Actions")?></th>
					</tr>
						
					<tbody id='tbody_sd'>
					<?php
					
					if ( empty($directories) ) 
					{
						$k           = 0;
						$directories = array(array("@attributes" => null, "0"=> null));
					}
					
					foreach ($directories as $k => $v)
					{
						echo "<tr class='dir_tr' id='dir_$k'>";
							echo "<td style='text-align: left;'><textarea name='".$k."_value_dir' id='".$k."_value_dir'>".$directories[$k][0]."</textarea></td>";
							echo "<td><table width='100%'>
								  <tr>";
							$i = 0;
							foreach ($directory_checks as $j => $value)
							{
								$i++;
								$checked = ( !empty($directories[$k]['@attributes'][$j]) ) ? 'checked="checked"' : '';
								echo "<td style=' text-align:center;'><input type='checkbox' id='".$j."_".$k."_".$i."' name='".$j."_".$k."_".$i."' $checked/></td>";
							}
							echo "</tr>
								  </table></td>";
							echo "<td>
									<a onclick='delete_dir(\"dir_$k\");'><img src='../vulnmeter/images/delete.gif' align='absmiddle'/></a>
									<a onclick='add_dir(\"dir_$k\");'><img src='images/add.png' align='absmiddle'/></a>	
								 </td>";
						echo "</tr>";
					}
					
						
					?>
					</tbody>
				</table>
			</div>
				
			<div class='cont_savet2'><input type='button' class='button' id='send' value='<?php echo _("Update")?>' onclick="save_config_tab();"/></div>
		</div>
		
		<div class='cont_sys'>
			<div class='headerpr'><?php echo _("Files/Directories ignored")?></div>
			
			<div>
			<?php
			if ( empty($ignores) ) 
			{
				$k       = 0;
				$ignores = array(array("@attributes" => null, "0"=> null));
			}
			?>
				<table id='table_sys_ignores' width='100%'>
					<tr>	
						<th class='sys_ignores'><?php echo _("Files/Directories")?></th>
						<td class='sys_parameters'>
							<table width='100%'>
								<tr><th><?php echo _("Parameters")?></th></tr>
								<tr>
									<th style='font-size: 10px; text-align:center;'><?php echo _("Sregex")?></th>
								</tr>
							</table>
						</td>
						<th class='sys_actions'><?php echo _("Actions")?></th>
					</tr>
					
					<tbody id='tbody_si'>
					<?php
													
					foreach ($ignores as $k => $v)
					{
						echo "<tr class='dir_tr' id='ign_$k'>";
							echo "<td style='text-align: left;'><textarea name='".$k."_value_ign' id='".$k."_value_ign'>".$ignores[$k][0]."</textarea></td>";
							$checked = ( !empty($ignores[$k]['@attributes']['type']) ) ? 'checked="checked"' : '';
							echo "<td style='text-align: center;'><input type='checkbox' name='".$k."_type' id='".$k."_type' $checked/></td>";
							echo "<td>
									<a onclick='delete_ign(\"ign_$k\");'><img src='../vulnmeter/images/delete.gif' align='absmiddle'/></a>
									<a onclick='add_ign(\"ign_$k\");'><img src='images/add.png' align='absmiddle'/></a>
							</td>";
						echo "</tr>";
					}
						
					?>
					</tbody>
				</table>
			</div>
						
			<div class='cont_savet2'><input type='button' class='button' id='send' value='<?php echo _("Update")?>' onclick="save_config_tab();"/></div>
		</div>
		
	</form>
	
	<div class='notice'><div><span>(*)<?php echo _("You must restart Ossec for the changes to take effect")?></span></div></div>

	<?php
}
else if ($tab == '#tab3')
{
	echo "1###".$conf_file;
}
else
	echo "2###"._("Error: Illegal actions");





?>