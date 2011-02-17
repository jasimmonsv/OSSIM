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
require_once ('classes/Xml_parser.inc');
require_once ('../utils.php');
require_once ('../conf/_conf.php');

//Create new rule to add

$__level_key      = POST('key');
$_level_key_name  = $_SESSION['_level_key_name'];

$file  	 	= $editable_files[0];
$path  	 	= $rules_file.$file;

$file_tmp   = uniqid($editable_files[0])."_tmp.xml";
$path_tmp   = "/tmp/".$file_tmp;


$tree_lr    = $_SESSION["_tree"];
$tree_cp    = $_SESSION["_tree"];
$child 	    = getChild($tree_lr, $__level_key);

$new_key    = null;
$error      = false;

if ( @copy ($path , $path_tmp) == false )
{
	echo "2###"._("Failure to update XML File")." (1)";
	exit;
}

$result = test_conf(); 
	
if ( $result !== true )
{
	echo "4###".$result;
	exit;
}

$file_to_text    = file_get_contents ($path, false);
$_level_key_name = set_key_name($_level_key_name, $file_to_text);
	
$new_rule['rule'] = $child['tree'];   

//Tree local_rules.xml

$xml_obj = new xml($_level_key_name);
$xml_obj->load_file($path);

$tree_lr=$xml_obj->xml2array();
		
foreach ($tree_lr as $k => $v)
{
	if ( isset($tree_lr[$k]['group']) )
	{
		$__level_key = $tree_lr[$k]['group']['@attributes']['__level_key'];
		$child =  getChild($tree_lr, $__level_key);
		$keys  =  array_keys($child['tree']);
		
		if ( is_numeric($keys[count($keys)-1]) )
		{
			$aux_key = $keys[count($keys)-1] + 1;
			$new_key = ( !in_array($aux_key, $keys) ) ? $aux_key : uniqid(mt_rand("1", mt_getrandmax()));
		}
		else
			$new_key = uniqid(mt_rand("1", mt_getrandmax()));
	   
		break;
	}
}
	
if ( empty($new_key) )
{
	echo "3###"._("Failure: Format not allowed in file")." ".$editable_files[0]." (2)"; 
	$error = true;
}
else
{
	$branch = '['.implode("][", $child['parents']).'][\''.$new_key.'\']';
	$ok = eval ("\$tree_lr$branch= \$new_rule;");
}

	

if ($ok === false && $error == false)
{
	echo "3###"._("Failure to update XML File")." (2)";
	$error = true;
}
else
{
	$output = $xml_obj->array2xml($tree_lr);
	$output = formatOutput($output, $_level_key_name);
	$output = utf8_decode($output);		

	
	if (@file_put_contents($path, $output, LOCK_EX) === false)
	{
		$error = true;
		echo "3###"._("Failure to update XML File")." (3)";
	}
	else
	{
		$result = test_conf(); 	
	
		if ( $result !== true )
		{
			$error = true;
			echo "4###".$result;
		}
		
	}
}


if ( $error == true )
{
	@unlink($path);
	@copy  ($path_tmp, $path);
	$_SESSION['_tree']       = $tree_cp;
	$_SESSION['_tree_json']  = array2json($tree_cp, $path);
}
else
{
	$_SESSION["_current_file"] = $editable_files[0];
	echo "1###"._("Rule cloned successfully")."###".$__level_key;
}

@unlink ($path_tmp);


	
?>