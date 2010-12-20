<?php

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
$child 	    = getChild($tree_lr, $__level_key);

$new_key    = null;


if ( file_exists($path) )
{
	$file_to_text    = file_get_contents ($path, false);
	$_level_key_name = set_key_name($_level_key_name, $file_to_text);
		
	$pattern = "/\<rule.+id\s*=\s*\"(.*?)\".*\>/";
	
	if ( preg_match_all ($pattern, $file_to_text, $ids) )
	{
		sort($ids[1], SORT_NUMERIC);
		$ids    = $ids[1];
		$max_id = $ids[count($ids)-1]+1;
	}
	else
	{
		echo "3###"._("Failure: Format not allowed in file")." ".$editable_files[0]." (1)"; 
		exit();
	}
}
else
{
	echo "2###"._("Failure: File")." ".$editable_files[0]." does not exist"; 
	exit();
}

$id = $child['tree']['@attributes']["id"];
$child['tree']['@attributes']["id"] = $max_id;

$index    		  = ( array_key_exists('@attributes', $child['tree']) ) ? count('@attributes') -1 : count('@attributes');
$if_sid  		  = array("$index" => array("if_sid" => array("@attributes" => array("__level_key" => ""), "0"=>$id)));
$new_rule['rule'] = array_merge ($child['tree'], $if_sid);   

//Tree local_rules.xml
	
	$xml_obj = new xml($_level_key_name);
	$xml_obj->load_file($path);
	
	if ($xml_obj->errors['status'] == false)
	{
		echo "4###"._("Error to parse XML file")." ".$editable_files[0];
		exit();
	}
	else
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
	exit();
}


$branch = '['.implode("][", $child['parents']).'][\''.$new_key.'\']';

$ok = eval ("\$tree_lr$branch= \$new_rule;");

if ($ok === false)
    echo "5###"._("Failure to update XML File")." (1)";
else
{
    $output = $xml_obj->array2xml($tree_lr);
	       
	if ( @copy ($path , $path_tmp) == false )
		echo "5###"._("Failure to update XML File")." (2)";
	else
	{  
		$output = formatOutput($output, $_level_key_name);
		$output = utf8_decode($output);		

		
		if (@file_put_contents($path, $output, LOCK_EX) == false)
		{
			@unlink ($path);
			@rename ($path_tmp, $path);
			echo "5###"._("Failure to update XML File")." (3)";
		}
		else
		{
			$_SESSION["_current_file"] = $editable_files[0];
			echo "1###"._("Rule cloned successfully")."###".$__level_key;
		}
		
		@unlink ($path_tmp);
	}
}
	
?>