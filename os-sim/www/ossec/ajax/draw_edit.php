<?php

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


