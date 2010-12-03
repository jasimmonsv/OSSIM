<?php
require_once ('classes/Session.inc');
require_once ('classes/Xml_parser.inc');
require_once ('../utils.php');
require_once ('../conf/_conf.php');

$res      = getTree(POST('file'));
$filename = $rules_file.POST('file');
								
if ( !is_array($res) )
	echo $res;
else
{
	$tree		            = $res;
	$tree_json              = array2json($tree, $filename);
	$_SESSION['_tree_json'] = $tree_json;
	$_SESSION['_tree']      = $tree;
	
	echo "1###"._("Click on a brach to edit a node")."###".base64_encode($tree_json);
}



?>