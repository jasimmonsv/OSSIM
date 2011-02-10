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
require_once ('../conf/_conf.php');
require_once ('../utils.php');

$_level_key_name = $_SESSION['_level_key_name'];

$file     = $_SESSION["_current_file"];
$path     = $rules_file.$file; 
$editable = false;

if ( in_array($file, $editable_files) )
	$editable = true;

$_SESSION["_current_file"]      = $file;
$file_tmp                       = $rules_file."tmp_".$file;
$_level_key_name                = $_SESSION['_level_key_name'];

$node                           = explode ("</span>", $_POST["node"]);
$node_name                      = preg_replace("/<span>/", '', $node[0]);
$_SESSION["_current_node"]      = $node_name;

$__level_key                    = $_POST['__level_key'];
$_SESSION["_current_level_key"] = $__level_key;

$tree  							= $_SESSION["_tree"];
$child 							= getChild($tree, $__level_key);
$_SESSION["_current_branch"]    = $child;
$parents 						= $child['parents'];
$ac_data 						= getAcType($parents);

echo implode("##__##",$ac_data)."##__##";


$node_type                      = getNodeType($node_name, $child);
$_SESSION["_current_node_type"] = $node_type;

$params = array ('modify', $__level_key , $error);

switch ($node_type){

	case 1:
	$attributes = array ($node_name => $child['tree']['@attributes'][$node_name], $_level_key_name =>  $child['tree']['@attributes'][$_level_key_name]);
	$unique_id  = $__level_key."_at1";
	include "../interfaces/edit_1.php";
	break;
	
	case 2:
	$attributes = $child['tree'];
	include "../interfaces/edit_2.php";
	break;
	
	case 3:
	$attributes = $child['tree']['@attributes'];	
	
	if (count ($attributes) <= 1)
		$attributes = array ('' => '', $_level_key_name =>  $child['tree']['@attributes'][$_level_key_name]);
	
	$__level_key = $child['tree']['@attributes'][$_level_key_name];	
	$txt_nodes   = $child['tree'];
	include "../interfaces/edit_3.php";
	break;
	
	case 4:
	
	$attributes = $child['tree']['@attributes'];
	
	unset($child['tree']['@attributes']);
	
	if (count ($attributes) <= 1)
		$attributes = array ('' => '', $_level_key_name =>  $child['tree']['@attributes'][$_level_key_name]);
	
	$txt_nodes  = $child['tree'];
	include "../interfaces/edit_4.php";
	break;
	
	case 5:
	$params[0]   ='modify_node';
	$attributes  = $child['tree']['@attributes'];
	
	if (count ($attributes) <= 1)
		$attributes = array ('' => '', $_level_key_name =>  $child['tree']['@attributes'][$_level_key_name]);
	
	unset($child['tree']['@attributes']);
	$children = $child['tree'];
	include "../interfaces/edit_5.php";
	break;
}
?>