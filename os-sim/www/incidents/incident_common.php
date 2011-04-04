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
* - format_user()
* - get_my_users_vision()
* - get_my_entities_vision()
*/

//Format user data

function format_user($user, $html = true, $show_email = false) 
{
    if (is_a($user, 'Session')) 
	{
        $login   = $user->get_login();
        $name    = $user->get_name();
        $depto   = $user->get_department();
        $company = $user->get_company();
        $mail    = $user->get_email();
    } 
	elseif (is_array($user))
	{
        $login   = $user['login'];
        $name    = $user['name'];
        $depto   = $user['department'];
        $company = $user['company'];
        $mail    = $user['email'];
    } 
	else 
	{
        return '';
    }
	
    $ret = $name;
    
	if ($depto && $company)   $ret.= " / $depto / $company";
    if ($mail && $show_email) $ret = "$ret &lt;$mail&gt;";
    if ($login)               $ret = "<label title=\"Login: $login\">$ret</label>";
    
	if ($mail)
        $ret = '<a href="mailto:' . $mail . '">' . $ret . '</a>';
    else 
        $ret = "$ret <font size='small' color='red'><i>(No email)</i></font>";
    
    return $html ? $ret : strip_tags($ret);
}


//Get in charge name for user or entity
function format_charge_name($in_charge_name, $conn)
{

	if ( !preg_match("/^\d+$/",$in_charge_name) )
	{
		return $in_charge_name;
	}
	else 
	{
		$querye  = "SELECT ae.name as ename, aet.name as etype FROM acl_entities AS ae, acl_entities_types AS aet WHERE ae.type = aet.id AND ae.id=".$in_charge_name;
		$resulte = $conn->execute($querye);
		list($entity_name, $entity_type) = $resulte->fields;
		return $entity_name." [".$entity_type."]";
	}
}

function get_params_field($field, $map_key){
	
	$unique_id = md5( uniqid() );
	$fld       = "custom_".$unique_id;
	$name      = "custom_".base64_encode($field['name']."_####_".$field['type']);
	$required  = ( $field['required'] == 1 ) ? "req_field" : "";
	
	switch ($field['type']){
		case "Asset":
			$params = array("name" => $name, "id"=>$fld, "class"=>trim($required." ct_assets_sel"));
		break;
		
		case "Check Yes/No":
			$params = array("name" => $name, "id"=>$fld, "class"=>$required);
		break;
		
		case "Check True/False":
			$params = array("name" => $name, "id"=>$fld, "class"=>$required);
		break;
		
		case "Checkbox":
		
			if ($field["options"] != '')
				$options = explode("\n", $field["options"]);
			else
				$options = '';
				
			$num_opt = count($options);
			
			$num_chk = ($options[$num_opt-1] == '' ) ? $num_opt-1 : $num_opt;
			
			for ($i=0; $i<$num_chk; $i++)
				$ids[] = $fld."_".($i+1);
											
			$params = array("name" => $name, "id"=>$ids, "class"=>$required, "values" => $options);
			
		break;
		
		case "Date":
			$params = array("name" => $name, "id"=>$fld, "class"=>$required);
		break;
		
		case "Date Range":
			$params = array("name" => $name, "id"=>$fld, "class"=>$required);
		break;
		
		case "Map":
			$params = array("name" => $name, "id"=>$fld, "class"=>$required, "values"=>array($map_key));
		break;
			
		case "Radio button":
			
			if ($field["options"] != '')
				$options = explode("\n", $field["options"]);
			else
				$options = '';
				
			$num_opt = count($options);
			
			$num_radio = ($options[$num_opt-1] == '' ) ? $num_opt-1 : $num_opt;
			
			for ($i=0; $i<$num_radio; $i++)
				$ids[] = $fld."_".$i;
			
			$params = array("name" => $name, "id"=>$ids, "class"=>$required, "values"=> $options);
			
		break;
		
		case "Select box":
			
			if ($field["options"] != '')
				$options = explode("\n", $field["options"]);
			else
				$options = '';
				
			$params = array("name" => $name, "id"=>$fld, "class"=>$required, "values"=> $options);
		break;
		
		case "Slider":
			
			if ($field["options"] != '')
				$options = explode(",", $field["options"]);
			else
				$options = '';
				
			$params = array("name" => $name, "id"=>$fld, "class"=>$required, "values"=> $options);
		break;
					
		case "Textarea":
			$params = array("name" => $name, "id"=>$fld, "class"=>$required, "rows"=>"3", "cols"=>"80", "wrap"=>"hard");
		break;
		
		case "Textbox":
			$params = array("name" => $name, "id"=>$fld, "class"=>$required);
		break;
		
		case "File":
			$params = array("name" => $name, "id"=>$fld, "class"=>$required);
		break;
			
	}
	
	return $params;

}


?>