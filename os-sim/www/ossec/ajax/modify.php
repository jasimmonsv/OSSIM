<?php

require_once ('classes/Session.inc');
require_once ('classes/Xml_parser.inc');
require_once ('../utils.php');
require_once ('../conf/_conf.php');

$_level_key_name = $_SESSION['_level_key_name'];
$file            = $_SESSION["_current_file"];

ossim_valid($file, $editable_files[0], 'illegal:' . _("XML file"));

if ( ossim_error() )
{
	echo "4###"._("XML file can not be edited");
	exit();
}

$path        = $rules_file.$file;

$file_tmp    = uniqid($file)."_tmp.xml";
$path_tmp    = "/tmp/tmp_".$file_tmp;

$node_name   = $_SESSION["_current_node"];
$__level_key = $_SESSION["_current_level_key"];
$__level_key = preg_replace("/^attr_/", '', $__level_key);

$tree        = $_SESSION["_tree"];
$child       = $_SESSION["_current_branch"];
$node_type   = $_SESSION["_current_node_type"];

$branch      = '['.implode("][", $child['parents']).']';

$ok = null;

$char_list  = "\t\n\r\0\x0B";
$clean_post = array();

foreach ($_POST as $k => $v)
{
	$clean_post[$k]  = trim($v, $char_list);
	$clean_post[$k]  = ltrim(rtrim($v, $char_list));
}
	
	
switch ($node_type){

	//One attribute
	case 1:
	
		$ac = $branch."['@attributes']['$node_name']";
		
		$ok = @eval ("unset(\$tree$ac);");
		
		if ( $clean_post["n_label-".$__level_key."_at1"] != '' && $clean_post["n_txt-".$__level_key."_at1"] != '' && $ok !== false)
		{
			$key   = $clean_post["n_label-".$__level_key."_at1"];
			$value = $clean_post["n_txt-".$__level_key."_at1"];
			$ac = $branch."['@attributes']['$key'] = $value";
			$ok = @eval ("\$tree$ac;");
		}
				
		
	break;
	
	//Several Attributes
	case 2:
	
		$ac = $branch."['@attributes']";
		
		$attributes = array();
		
		$attributes[$_level_key_name] = $__level_key;
		unset($clean_post[$_level_key_name]);
		
				
		$keys = array_keys($clean_post);
		$num  = count($keys);
		
				
		for ($i=0; $i<$num; $i=$i+2)
		{	
			$j=$i+1;
			if ( $clean_post[$keys[$i]] != '' && $clean_post[$keys[$j]] != '')
				$attributes[$clean_post[$keys[$i]]] = $clean_post[$keys[$j]];
		}
		
		$ok = @eval ("\$tree$ac= \$attributes;");
									
	break;	
	
	//Text Nodes
	case 3:
	
		$txt_nodes  = array();
		$attributes = array();
		
		$attributes[$_level_key_name] = $__level_key;
		unset($clean_post[$_level_key_name]);
		
		$keys = array_keys($clean_post);
		$num  = count($keys);
						
		if ( $clean_post[$keys[$num-2]] != '' )
		{
			for ($i=0; $i<$num-3; $i=$i+2)
			{	
				$j=$i+1;
				if ($clean_post[$keys[$i]] != '' && $clean_post[$keys[$j]] != '' )
					$attributes[$clean_post[$keys[$i]]] = $clean_post[$keys[$j]];
			}
		    
			$txt_nodes['@attributes'] = $attributes;
			$txt_nodes[0] = $clean_post[$keys[$num-1]];
			
				
			$ok     = @eval ("unset(\$tree$branch);");
			$child['parents'][count($child['parents'])-1] = $clean_post[$keys[$num-2]];
			$branch = '['.implode("][", $child['parents']).']';
			
			$ok = @eval ("\$tree$branch= \$txt_nodes;");
		}
		else
			$ok = @eval ("unset(\$tree$branch);");
						
	break;
	
	//Rules
	case 4:
	
		$txt_nodes  = array();
		$attributes = array();
		$node       = array();
		$found      = false;
		
		$attributes[$_level_key_name] = $__level_key;
		unset($clean_post[$_level_key_name]);
		
		
		foreach ($clean_post as $k => $v)
		{
			if ($k == 'sep')
			{
				$found = true;
				continue;
			}
			
			if ($found == false)
				$at_keys[] = $k;
			else
				$txt_nodes_keys[] = $k;
		}
		
		
		$num_at  = count($at_keys);
		$num_txt = count($txt_nodes_keys);
		
				
		for ($i=0; $i<$num_at; $i=$i+2)
		{	
			if ($clean_post[$at_keys[$i]] != '' )
			{
				$j=$i+1;
				$attributes[$clean_post[$at_keys[$i]]] = $clean_post[$at_keys[$j]];
			}
		}
		
		$node['@attributes'] = $attributes;
		
				
		$cont = 0;
		$i = 0;
		
		while ($i < $num_txt )
		{
			$insert    = true;
			$txt_nodes = array();
			
			$id          = explode("-", $txt_nodes_keys[$i], 2);
			$__level_key = $id[1];
			
			$name_node = $clean_post[$txt_nodes_keys[$i]];
			
			if ($name_node != '')
			{
				$txt_nodes[$name_node]['@attributes'][$_level_key_name] = $__level_key;
				$txt_nodes[$name_node][0] = $clean_post[$txt_nodes_keys[$i+1]];
			}
			else
				$insert = false;
						
			$i = $i+2;
			
			$id = explode("-", $txt_nodes_keys[$i], 2);
											
			while ( preg_match("/$__level_key/", $id[1]) != false )
			{
				if ($clean_post[$txt_nodes_keys[$i]] != '' && $name_node != '')
					$txt_nodes[$name_node]['@attributes'][$clean_post[$txt_nodes_keys[$i]]]= $clean_post[$txt_nodes_keys[$i+1]];
				
				$i = $i + 2;
				$id = explode("-", $txt_nodes_keys[$i], 2);
			}
			
			if ($insert == true)
			{
				$node[$cont] = $txt_nodes;
				$cont++;
			}
		}
		
		@eval ("\$tree$branch= \$node;");
	
	break;
	
	case 5:
	
	$nodes = array();
			
	$nodes['@attributes'][$_level_key_name] = $__level_key;
	unset($clean_post[$_level_key_name]);
	
			
	foreach ($clean_post as $k => $v)
	{
		if ($k == 'sep')
		{
			$found = true;
			continue;
		}
		
		if ($found == false)
			$at_keys[] = $k;
		else
			$nodes_keys[$k] = $v;
		
	}
	
	$num_at = count($at_keys);
			
	for ($i=0; $i<$num_at; $i=$i+2)
	{	
		if ($clean_post[$at_keys[$i]] != '' )
		{
			$j=$i+1;
			$nodes['@attributes'][$clean_post[$at_keys[$i]]] = $clean_post[$at_keys[$j]];
			unset($clean_post[$at_keys[$i]]);
		}
	}
	
	$cont = 1;
	
	foreach ($nodes_keys as $k => $v)
	{
		if ( preg_match("/^clone/", $v) == false )
		{
			$child_node =  getChild($child, $v);
			$nodes[$cont-1][$child_node['node']] = set_new_lk($child_node['tree'], $child_node['tree']['@attributes'][$_level_key_name], $__level_key."_".$cont);
		}
		else
		{
			$key =  preg_replace("/clone###/", "", $v);
			$child_node =  getChild($child, $key);
			$nodes[$cont-1][$child_node['node']] = set_new_lk($child_node['tree'],  $child_node['tree']['@attributes'][$_level_key_name], $__level_key."_".$cont);
		}
		$cont++;
	}	
	
		
	$ok = eval ("\$tree$branch= \$nodes;");
			
	break;
}


if ($ok === false)
	echo "2###"._("Failure to update XML File")." (1)";
else
{
	$xml    = new xml($_level_key_name);
	$output = $xml->array2xml($tree);
					
	if (!file_exists($path))
		echo "3###"._("XML file not found");
	else
	{
		if (@copy ($path , $path_tmp) == false )
			echo "2###"._("Failure to update XML File"). " (2)";
		else
		{   
		   	
			$output = formatOutput($output, $_level_key_name);
			$output = utf8_decode($output);		
						
									
			if (@file_put_contents($path, $output, LOCK_EX) == false)
			{
				@unlink ($path);
				@rename ($path_tmp, $path);
				echo "2###"._("Failure to update XML File"). " (3)";
			}
			else
			{
				
				$res = getTree($file);
								
				if ( !is_array($res) )
					echo $res;
				else
				{
					$tree		            = $res;
					$tree_json              = array2json($tree, $path);
					$_SESSION['_tree_json'] = $tree_json;
					$_SESSION['_tree']      = $tree;
					
					echo "1###"._("XML file update successfully")."###".base64_encode($tree_json);
				}	
			}
			
			@unlink($path_tmp);
		}
	}
}

?>