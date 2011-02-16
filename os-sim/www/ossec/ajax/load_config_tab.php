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

if ($conf_file === false)
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
	exec("egrep \"<[[:space:]]*include[[:space:]]*>.*xml<[[:space:]]*/[[:space:]]*include[[:space:]]*>\" $ossec_conf", $output, $retval);
	
	if ($retval !== 0)
	{
		echo "2###"._("File")." <b>$ossec_conf</b> "._("not found or you don't have have permission to access");
		exit();
	}
	
	foreach ( $output as $k => $v )
	{
		if ( preg_match("/^<\s*include\s*>(.*)<\s*\/include\s*>/", trim($v), $match) )
			$rules_enabled[] = $match[1];
	}
	
	$all_rules       = get_files ($rules_file);
	$no_added_rules  = array_diff($all_rules, $rules_enabled);	
			
		echo "1###<div id='cnf_rules_cont'><table class='cnf_rules_table'>";
											
			echo "<tr><td style='padding: 8px 0px 6px 0px;'>";
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
						echo "<option value='$v' >$v</option>";
				echo "</select></form></td>";
			echo "</tr>";
			
			echo "<tr><td style='padding-bottom:10px;'><input type='button' class='button' id='send' value='"._('Update')."' onclick=\"save_config_tab();\"/></td></tr>";
		
		echo "</table></div>";
			
}
else if ($tab == '#tab2')
{
	
	$xml_obj = new xml("key");
	$xml_obj->load_file($ossec_conf);
	$array_oss_cnf = $xml_obj->xml2array();
	
	$syscheck = get_nodes($array_oss_cnf, 'syscheck');
	
	$directories = get_nodes($syscheck, 'directories');
	$wentries    = get_nodes($syscheck, 'windows_registry');
	$reg_ignores = get_nodes($syscheck, 'registry_ignore');
	$ignores     = get_nodes($syscheck, 'ignore');
	
	
	$frequency       = get_nodes($syscheck, 'frequency');
	$frequency       = $frequency[0][0];
	
	$scan_day        = get_nodes($syscheck, 'scan_day');
	$scan_day        = $scan_day[0][0];
	
	
	$scan_time       = get_nodes($syscheck, 'scan_time');
	$scan_time       = $scan_time[0][0];
	$st              = ( !empty($scan_time) ) ? explode(":", $scan_time) : array();
	
	$auto_ignore     = get_nodes($syscheck, 'auto_ignore');
	$auto_ignore     = ( empty($auto_ignore[0][0]) ) ? "no" : $auto_ignore[0][0];
	
	$alert_new_files = get_nodes($syscheck, 'alert_new_files');
	$alert_new_files = ( empty($alert_new_files[0][0]) ) ? "no" : $alert_new_files[0][0];
	
	$scan_on_start   = get_nodes($syscheck, 'scan_on_start');
	$scan_on_start   = ( empty($scan_on_start[0][0]) ) ? "yes" : $scan_on_start[0][0];
	

	
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
	
	$week_days = array(
			""             => "-- Select a day --",
			"monday"       => "Monday",
			"tuesday"      => "Tuesday", 
			"wednesday"    => "Wednesday", 
			"thursday"     => "Thursday", 
			"friday"       => "Friday", 
			"saturday"     => "Saturday", 
			"sunday"       => "Sunday"
	);
	
	$yes_no = array(
			"yes"     => "Yes",
			"no"      => "No"
	);
	
	echo "1###";
			
	?>
	<form name='form_syscheck' id='form_syscheck'>
	
		<div class='cont_sys'>
			<div class='headerpr'><?php echo _("Configuration parameters")?></div>
			<div id='cont_tsp'>
				<table id='table_sys_parameters'>
					<tr>	
						<th class='sys_parameter'><?php echo _("Frequency")?></th>
						<td class='sys_value'><input type='text' id='frequency' name='frequency' value='<?php echo $frequency?>'/></td>
						
						<th class='sys_parameter'><?php echo _("Scan time")?></th>
						<td class='sys_value'>
							<input type='text' class='time' maxlength='2' id='scan_time_h' name='scan_time_h' value='<?php echo $st[0]?>'/>
							<span style='margin: 0px 2px'>:</span>
							<input type='text' class='time' maxlength='2' id='scan_time_m' name='scan_time_m' value='<?php echo $st[1]?>'/>
						</td>
					</tr>
					
					<tr>	
						<th class='sys_parameter'><?php echo _("Scan_day")?></th>
						<td class='sys_value'>
							<select id='scan_day' name='scan_day' class='select_wd'>
								<?php 
									foreach ($week_days as $k => $v)
									{
										$selected = ( $k == $scan_day ) ? "selected='selected'" : "";
										echo "<option value='$k' $selected>$v</option>";
									}
								?>
							</select>
						</td>
						
						<th class='sys_parameter'><?php echo _("Auto ignore")?></th>
						<td class='sys_value'>
							<select id='auto_ignore' name='auto_ignore' class='select_yn'>
								<?php 
									foreach ($yes_no as $k => $v)
									{
										$selected = ( $k == $auto_ignore ) ? "selected='selected'" : "";
										echo "<option value='$k' $selected>$v</option>";
									}
								?>
							</select>
						</td>
					</tr>
					
					<tr>	
						<th class='sys_parameter'><?php echo _("Alert new files")?></th>
						<td class='sys_value'>
							<select id='alert_new_files' name='alert_new_files' class='select_yn'>
								<?php 
									foreach ($yes_no as $k => $v)
									{
										$selected = ( $k == $alert_new_files ) ? "selected='selected'" : "";
										echo "<option value='$k' $selected>$v</option>";
									}
								?>
							</select>
						</td>
						
						<th class='sys_parameter'><?php echo _("Scan on start")?></th>
						<td class='sys_value'>
							<select id='scan_on_start' name='scan_on_start' class='select_yn'>
								<?php 
									foreach ($yes_no as $k => $v)
									{
										$selected = ( $k == $scan_on_start ) ? "selected='selected'" : "";
										echo "<option value='$k' $selected>$v</option>";
									}
								?>
							</select>
						</td>
					</tr>
				</table>
			</div>
			
			<div class='cont_savet2'><input type='button' class='button' id='send' value='<?php echo _("Update")?>' onclick="save_config_tab();"/></div>
			
		</div>
		
		<div class='cont_sys'>
			<div class='headerpr'><?php echo _("Windows registry entries monitored (Windows system only)")?></div>
		
			<div>
					
				<table id='table_sys_wentries' width='100%'>
					<thead>
						<tr>	
							<th class='sys_wentry'><?php echo _("Windows registry entry")?></th>
							<th class='sys_actions'><?php echo _("Actions")?></th>
						</tr>
					</thead>	
					
					<tbody id='tbody_swe'>
					<?php
					
					if ( empty($wentries) ) 
					{
						$k           = 0;
						$wentries = array(array("@attributes" => null, "0"=> null));
					}
					
					foreach ($wentries as $k => $v)
					{
						echo "<tr class='went_tr' id='went_$k'>";
							echo "<td style='text-align: left;'><input class='sreg_ignore' name='".$k."_value_went' id='".$k."_value_went' value='".$wentries[$k][0]."'/></td>";
							echo "<td>
									<a onclick='delete_row(\"went_$k\", \"delete_wentry\");'><img src='../vulnmeter/images/delete.gif' align='absmiddle'/></a>
									<a onclick='add_row(\"went_$k\", \"add_wentry\");'><img src='images/add.png' align='absmiddle'/></a>	
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
			<div class='headerpr'><?php echo _("Registry entries ignored")?></div>
		
			<div>
					
				<table id='table_sys_reg_ignores' width='100%'>
					<thead>
						<tr>	
							<th class='sys_reg_ignores'><?php echo _("Registry entry ignored")?></th>
							<th class='sys_actions'><?php echo _("Actions")?></th>
						</tr>
					</thead>
					
					<tbody id='tbody_sri'>
					<?php
					
					if ( empty($reg_ignores) ) 
					{
						$k           = 0;
						$reg_ignores = array(array("@attributes" => null, "0"=> null));
					}
					
					foreach ($reg_ignores as $k => $v)
					{
						echo "<tr class='regi_tr' id='regi_$k'>";
							echo "<td style='text-align: left;'><input class='sreg_ignore' name='".$k."_value_regi' id='".$k."_value_regi' value='".$reg_ignores[$k][0]."'/></td>";
							echo "<td>
									<a onclick='delete_row(\"regi_$k\", \"delete_reg_ignore\");'><img src='../vulnmeter/images/delete.gif' align='absmiddle'/></a>
									<a onclick='add_row(\"regi_$k\", \"add_reg_ignore\");'><img src='images/add.png' align='absmiddle'/></a>	
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
			<div class='headerpr'><?php echo _("Files/Directories monitored")?></div>
		
			<div>
					
				<table id='table_sys_directories' width='100%'>
						<thead>
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
					</thead>	
					
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
									<a onclick='delete_row(\"dir_$k\", \"delete_directory\");'><img src='../vulnmeter/images/delete.gif' align='absmiddle'/></a>
									<a onclick='add_row(\"dir_$k\", \"add_directory\");'><img src='images/add.png' align='absmiddle'/></a>	
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
					<thead>
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
					</thead>
					
					<tbody id='tbody_si'>
					<?php
													
					foreach ($ignores as $k => $v)
					{
						echo "<tr class='ign_tr' id='ign_$k'>";
							echo "<td style='text-align: left;'><textarea name='".$k."_value_ign' id='".$k."_value_ign'>".$ignores[$k][0]."</textarea></td>";
							$checked = ( !empty($ignores[$k]['@attributes']['type']) ) ? 'checked="checked"' : '';
							echo "<td style='text-align: center;'><input type='checkbox' name='".$k."_type' id='".$k."_type' $checked/></td>";
							echo "<td>
									<a onclick='delete_row(\"ign_$k\", \"delete_ignore\");'><img src='../vulnmeter/images/delete.gif' align='absmiddle'/></a>
									<a onclick='add_row(\"ign_$k\", \"add_ignore\");'><img src='images/add.png' align='absmiddle'/></a>
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