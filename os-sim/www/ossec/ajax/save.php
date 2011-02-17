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

$file       = $_SESSION["_current_file"];
$path       = $rules_file.$file; 
$file_tmp   = uniqid($file)."_tmp.xml";
$path_tmp   = "/tmp/".$file_tmp; 

$error      = false;

if ( !in_array($file, $editable_files) )
{
	echo "1###"._("File not editable");
	exit();
}


$_SESSION["_current_file"] = $file;
$_level_key_name           = $_SESSION['_level_key_name'];


$data  =  html_entity_decode(base64_decode($_POST['data']),ENT_QUOTES, "UTF-8");

if (copy ($path , $path_tmp) == false )
{
	echo "2###";
	exit;
}


if ( @file_put_contents($path, $data, LOCK_EX) === false )
{
	$error = true;
	echo "3###";
}
else
{
	$result = test_conf(); 	
	
	if ( $result !== true )
	{
		$error = true;
		echo "4###".$result;
	}
	else
	{
		$xml_obj                = new xml($_level_key_name);
		$xml_obj->load_file($path);
		$array_xml              = $xml_obj->xml2array();
		$tree_json              = array2json($array_xml, $path);
		$_SESSION['_tree_json'] = $tree_json;
		$_SESSION['_tree']      = $array_xml;
		echo "5###".base64_encode($tree_json);
	}
}

if ($error == true)
{
	@unlink ($path);
	@copy ($path_tmp, $path);
}

@unlink($path_tmp);	


?>