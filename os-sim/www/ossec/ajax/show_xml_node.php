<?php

require_once ('classes/Session.inc');
require_once ('classes/Xml_parser.inc');
require_once ('../utils.php');
require_once ('../conf/_conf.php');

//Show a rule

$__level_key      = POST('key');
$_level_key_name  = $_SESSION['_level_key_name'];

$tree_lr    = $_SESSION["_tree"];

$child 	    = getChild($tree_lr, $__level_key);

$rule = array ("@attributes"=> array($_level_key_name => "1"), "0" => array("rule" => $child['tree']));

if ( !empty($child) )
{
	$_level_key_name = $_SESSION['_level_key_name'];
	$xml_obj         = new xml($_level_key_name);
	$output          = $xml_obj->array2xml($rule);
	
	echo "1###".formatOutput($output, $_level_key_name);
}
else
{
	echo "error###"._("Failure: Information not available"); 
	exit();
}

  