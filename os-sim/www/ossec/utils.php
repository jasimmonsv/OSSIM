<?php
require_once ('classes/Session.inc');

// Edit Section Functions

function display_xml_error($error, $xml)
{
    $return  = $xml[$error->line - 1] . "<br/>";
    
    switch ($error->level) {
        case LIBXML_ERR_WARNING:
            $return .= _("Warning $error->code: ");
            break;
         case LIBXML_ERR_ERROR:
            $return .= _("Error $error->code: ");
            break;
        case LIBXML_ERR_FATAL:
            $return .= _("Fatal Error $error->code: ");
            break;
    }

    $return .= trim($error->message) .
               "<br/>  Line: $error->line" .
               "<br/>  Column: $error->column";

    if ($error->file) {
        $return .= _("<br/>  File: $error->file");
    }

    return $return;
}

function parserArray($array_xml){
	$_level_key_name = $_SESSION['_level_key_name'];
	$image_url = "../../../pixmaps/theme/";
	
	foreach($array_xml as $key => $value)
	{
		$size = count($array_xml);
		if ( is_array($value) && !isset($value['@attributes']) && $key !== '@attributes')
		{
			$json .= parserArray($value, '');
			continue;
		}
				
		elseif ( is_array($value) && isset($value['@attributes']))
		{
			$icon = $image_url.getIcon($key, $value);
			$title = getTitle($key, $value);	
			$json .= "{title: '$title', addClass: 'size10', key:'".$value['@attributes'][$_level_key_name]."', isFolder:'true', icon:'$icon'";
			$aux  =  parserArray($value);
			$json .= ( !empty($aux) ) ? ", children:  [". $aux."]" : '' ;
			$json .= "},\n";
		}
		elseif ( is_array($value) && $key === '@attributes' )
		{
			$keys = array_keys ($value);
			if (!(count($keys) == 1 && $keys[0] == $_level_key_name))
			{
				$json .= "{title: '<span>"._("Attributes")."</span>', addClass: 'size10', key:'attr_".$value[$_level_key_name]."', icon:'".$image_url."gear-small.png', children: [";
				foreach ($value as $k => $v)
				{
					if ($k !== $_level_key_name)
						$json .= "{title: '<span>".clean_string($k)."</span>=".clean_string($v)."', addClass: 'size10', key:'".$value[$_level_key_name]."', icon:'".$image_url."ticket-small.png'},\n";
				}
				
				$json = preg_replace('/,$/', '', $json);
				$json .= "]";
				$json .= "},\n";
			}
		}
		
	}
			
	return  $json;
		
}

function getIcon($key, $item)
{
	$_level_key_name = $_SESSION['_level_key_name'];
	
	$icon = null;
	
	$__level_key = $item['@attributes'][$_level_key_name];
	
	$depth_level = count(explode("_", $__level_key));
		
	if ($depth_level == 2){
		if ($key == "group" )
			$icon = 'ruler-triangle.png';	
		else if ($key == "var" )
			$icon = 'switch.png';
		else
			$icon = 'ruler-triangle.png';	
	}
	
	if ($depth_level == 3){
		if ($key == "rule")
			$icon = 'ruler.png';
	}
	
	if ($depth_level == 4){
		$icon = 'ticket-small.png';
	}
	
	return $icon;
}

function getTitle($key, $item)
{
	$_level_key_name = $_SESSION['_level_key_name'];
	
	switch ($key){
		    
		case "var":
			if ( isset ($item['@attributes']['name']) )
				$title= "<span>".clean_string($key)."</span> name=\"".clean_string($item['@attributes']['name'])."\"";
			else
			{
				if ( count($item['@attributes']) > 1 && isset($item[0]))
				{
					unset($item['@attributes'][$_level_key_name]);
					$keys = array_keys($item['@attributes']);
					$title= "<span>".clean_string($key)."</span> ".$keys[0]."=\"".clean_string($item['@attributes'][$keys[0]])."\"";
				}
				else
					$title = "<span>".clean_string($key)."</span>";
							
			}
		break;
		
		case "group":
			if ( isset( $item['@attributes']['name']) )
			{
				$name = preg_replace ('/,$/','', clean_string($item['@attributes']['name']));
				$title= "<span>".clean_string($key)."</span> name=\"".clean_string($name)."\"";
			}
			else
			{
				if ( count($item['@attributes']) > 1 && isset($item[0]))
				{
					unset($item['@attributes'][$_level_key_name]);
					$keys = array_keys($item['@attributes']);
					$title= "<span>".clean_string($key)."</span> ".clean_string($keys[0])."=\"".clean_string($item['@attributes'][$keys[0]])."\"";
				}
				else
					$title = "<span>".clean_string($key)."</span>";
							
			}
		break;
				
		case "rule":
			if ( isset ($item['@attributes']['id']) )
				$title= "<span>".clean_string($key)." </span> id=\"".clean_string($item['@attributes']['id'])."\"";
			else
			{
				if ( count($item['@attributes']) > 1 && isset($item[0]))
				{
					unset($item['@attributes'][$_level_key_name]);
					$keys = array_keys($item['@attributes']);
					$title= "<span>".clean_string($key)."</span> ".clean_string($keys[0])."=\"".clean_string($item['@attributes'][$keys[0]])."\"";
				}
				else
					$title = "<span>".clean_string($key)."</span>";
							
			}
		break;
		
		default:
			$item[0] =trim($item[0]);
			
			if ( is_string ($item[0]) && strlen($item[0])>0 )
			{		
				
				$value = ( strlen($item[0]) > 18) ? substr($item[0], 0, 18)."..." : $item[0];
				$value = clean_string($value);
				$title= "<span>".clean_string($key)."</span> = ".$value;
			}
			else
				$title= "<span>".clean_string($key)."</span>";
	}
	
		
	return $title;
}




function array2json ($array_xml, $filename)
{
	$_level_key_name = $_SESSION['_level_key_name'];
	$image_url = "../../../pixmaps/theme/";
	
	$json .= "{";
	$at = $array_xml['@attributes'];
	$at = preg_replace("/,$/",'',$at);
	$_level_key_name = $_SESSION['_level_key_name'];
	
	$icon = $image_url.'any.png';
	$json .= "title: '<span>".clean_string($filename)."</span>', addClass:'size12', key:'".$at[$_level_key_name]."', isFolder:'true', icon:'$icon', children:  \n[\n";
		
	unset ($array_xml['@attributes']);
	$json .= parserArray($array_xml);
	$json = preg_replace('/,$/', '', $json);
	$json .= "]";
	$json .= "}";
	$json = preg_replace('/,\\n]/', ']', $json);
	return $json;
}

/*
*
*  Print Functions
*
*/


function print_subheader ($type, $editable, $show_actions=true)
{
	if ($type == "attributes")
	{
		$actions = ( $editable == true) ? "<th class='r_subheader actions_at'>"._("Actions")."</th>" : "";
		$colspan = ( $editable == true ) ? "colspan='3'" : "colspan='2'";
		
		$subheader = "<tr><th class='at_header' $colspan><img src='images/arrow.png' alt='Arrow' align='top'/><span>"._("Attribute(s)")."</span></th></tr>
						<tr id='subheader1'>
							<th class='r_subheader'>"._("Name")."</th>
							<th class='r_subheader'>"._("Value")."</th>";
					
		$subheader .= ( $show_actions == true ) ? $actions : "";
		$subheader .= "</tr>";
	}
	else if ($type == "txt_nodes")
	{
		$subheader  = "<tr><th class='txt_node_header' colspan='3'><img src='images/arrow.png' alt='Arrow' align='top'/><span>"._("Text Node(s)")."</span></th></tr>
					   <tr id='subheader2'>
							<th class='r_subheader'>"._("Name")."</th>
							<th class='r_subheader'>"._("Value")."</th>";
					
		$subheader .= ( $show_actions == true ) ? "<th class='r_subheader'>"._("Actions")."</th>" : "";
		$subheader .= "</tr>";
	}
	else if ($type == "rules")
	{
		$class = ( $editable == true) ? "class='r_subheader actions_tn'" : "class='r_subheader' style='width:60px;'";
		$subheader  = "<tr><th class='txt_node_header' colspan='2'><img src='images/arrow.png' alt='Arrow' align='top'/><span>"._("Rules(s)")."</span></th></tr>
						<tr id='subheader2'>
							<th class='r_subheader'>"._("Name")."</th>";
						
		$subheader .= ( $show_actions == true ) ? "<th $class>"._("Actions")."</th>" : "";
		$subheader .= "</tr>";
	}
	else
	{
		$actions = ( $editable == true) ? "<th class='r_subheader actions_node'>"._("Actions")."</th>" : "";
		$colspan = ( $editable == true ) ? "colspan='2'" : "";
		
		$subheader = "<tr><th class='txt_node_header' $colspan><img src='images/arrow.png' alt='Arrow' align='top'/><span>"._("Children")."</span></th></tr>
						<tr id='subheader2'>
							<th class='r_subheader' $colspan'>"._("Node")."</th>";
		
		$subheader .= ( $show_actions == true ) ? $actions : "";
		$subheader .= "</tr>";
	}

	return $subheader;			

}


function print_subfooter($params, $editable)
{
	if ($editable == true)
    {
		$footer .= "<div id='buttons_box_edit'>";

		$opt1 =  "<div class='button'><input type='button' id='send' class='save_edit' onclick=\"javascript: ".$params[0]."('".$params[1]."');\" value='"._("save")."'/></div>";
		$opt2 =  "<div class='button'><input type='button' id='dis_send' class='save_edit' value='"._("save")."'/></div></div>";

		$footer .= ($params[2] == false ) ? $opt1 : $opt2;
	}
	else
	{
		$footer = '';
	}
    
	return $footer;

}

function print_attributes($attributes, $editable, $path='images')
{
	$_level_key_name = $_SESSION['_level_key_name'];
	
	$__level_key = $attributes[$_level_key_name];
	
	$cont = 1;
	
	foreach ($attributes as $k => $value)
	{
		if ($k !== $_level_key_name)
		{
			$unique_id = $__level_key."_at".$cont;
			
			$tr .= "<tr id='$unique_id'>";
			
			if ($editable == true)
			{
				$tr .= "<td class='n_name' id='cont_n_label-$unique_id'><input type='text' class='n_input auto_c' name='n_label-$unique_id' id='n_label-$unique_id' value='$k'/></td>
					<td class='n_value' id='cont_n_txt-$unique_id'><textarea name='n_txt-$unique_id' id='n_txt-$unique_id'>$value</textarea></td>
					<td class='actions_bt_at' style='width:75px;'>
						<a onclick=\"add_at('$unique_id', 'ats', '$path');\"><img src='$path/add.png' alt='"._("Add")."' title='"._("Add Attribute")."'/></a>
						<a onclick=\"delete_at('$unique_id','ats', '$path');\"><img src='$path/delete.gif' alt='"._("Delete")."' title='"._("Delete Attribute")."'/></a>
						<a onclick=\"clone_at('$unique_id');\"><img src='$path/clone.png' alt='"._("Clone")."' title='"._("Clone Attribute")."'/></a>
					</td>";
			}
			else
			{
				$tr .= "<th class='n_name'><div class='read_only'>$k</div></th>
					    <td class='n_value'><div class='read_only'>$value</div></td>";
			}
			$tr .= "</tr>\n";
			$cont++;
		}
	}
	
	return $tr;
}


function print_txt_nodes($txt_nodes, $editable, $path='images')
{
	$_level_key_name = $_SESSION['_level_key_name'];
	foreach ($txt_nodes as $k => $value)
	{
		$key = array_keys ($value);
		$name = $key[0];
		$attributes = $value[$name]['@attributes'];
		$__level_key = $attributes[$_level_key_name];
		$v = $value[$name][0];
		
		$colspan = ( $editable == true ) ? "colspan='3'" : "colspan='2'";		
		$tr .= "<tr id='$__level_key'>";
				
			if ($editable == true)
			{
				$tr .= "<td class='n_name' id='cont_n_label-$__level_key'><input type='text' class='n_input auto_c' name='n_label-$__level_key' id='n_label-$__level_key' value='$name'/></td>
						<td class='n_value' id='cont_n_txt-$__level_key'><textarea name='n_txt-$__level_key' id='n_txt-$__level_key'>$v</textarea></td>
						<td class='actions_bt_tn' style='width:95px;'>
							<a onclick=\"add_node('$__level_key', 'txt_nodes', '$path');\"><img src='$path/add.png' alt='"._("Add")."' title='"._("Add Text Node")."'/></a>
							<a onclick=\"delete_at('$__level_key','txt_nodes', '$path');\"><img src='$path/delete.gif' alt='"._("Delete")."' title='"._("Delete Text Node")."'/></a>
							<a onclick=\"clone_node('$__level_key','txt_nodes', '$path');\"><img src='$path/clone.png' alt='"._("Clone")."' title='"._("Clone Text Node")."'/></a>
							<a onclick=\"show_at('ats_$__level_key');\"><img src='$path/show.png' alt='"._("Show Attributes")."' title='"._("Show Attributes")."'/></a>
						</td>";
			}
			else
			{
				$tr .= "<td class='n_name' id='cont_n_label-$__level_key'><div class='read_only'>$name</div></td>
					    <td class='n_value' id='cont_n_txt-$__level_key'><div class='read_only'>$v</div></td>
						<td class='actions_bt_tn' style='width:60px;'>";
					
				if ( count($attributes) > 1 )
					$tr .= "<a onclick=\"show_at('ats_$__level_key');\"><img src='$path/show.png' alt='"._("Show Attributes")."' title='"._("Show Attributes")."'/></a>";
				else
					$tr .= "<img src='$path/show.png' class='dis_icon' alt='"._("Show Attributes")."' title='"._("Show Attributes")."'/>";
				
				$tr .= "</td>";
			
			}
		
		$tr .= "</tr>";
		
		if ($editable == true || ($editable != true && count($attributes) > 1)  )
		{
			$tr .= "<tr id='ats_$__level_key' style='display: none;'>
				<td colspan='3'>
					<div class='cont_ats_txt_node'>
						<table class='er_container'>
							<tbody id='erb_$__level_key'>
								<tr id='subheader_$__level_key'>
									<th class='txt_node_header' $colspan>
										<div class='fleft'><img src='images/arrow.png' alt='arrow' align='top'/><span>"._("Text Node Attributes")."</span></div>
										<div class='fright'><a style='float: right' onclick=\"hide_at('ats_$__level_key');\"><img src='images/arrow-up.png' alt='arrow' title='"._("Hide Attributes")."' align='absmiddle'/></a></div>
									</th>
								</tr>";
			
			$actions = ( $editable == true ) ? "<th class='actions_bt_tn' style='width:60px;'>"._("Actions")."</th>" : "";	
			
			$tr .= "	<tr id='subheader2_$__level_key'>
							<th class='r_subheader'>"._("Name")."</th>
							<th class='r_subheader'>"._("Value")."</th>
							$actions
						</tr>";
		
		
			if ( count($attributes) <= 1)	
				$attributes = array ($_level_key_name => $__level_key, "" => "");
			
			$tr .= print_attributes($attributes, $editable, 'images');
						
		
		$tr .="</tbody>
			   </table>						
			</div>
		</td>
		</tr>";
	
		}
	}
	
	return $tr;
}


function print_children($children, $editable, $path='images')
{
	require ('../conf/_conf.php');
		
	$_level_key_name = $_SESSION['_level_key_name'];
	foreach ($children as $k => $value)
	{
		$key 		 = array_keys ($value);
		$name 		 = $key[0];
		$attributes  = $value[$name]['@attributes'];
		$__level_key = $attributes[$_level_key_name];
		$v 			 = $value[$name][0];
		$name_at     = '';
		
		foreach ($attributes as $k_at => $v_at)
		{
			if ($k_at != $_level_key_name)
				$name_at .= $k_at."=\"$v_at\" ";
		}
		
		$name = trim($name." ".$name_at);
		
		$tr .= "<tr id='$__level_key' class='__lk-###'>";
		
		$class = ( count($children) <= 1 ) ? "class='delete_c unbind'" : "class='delete_c'";
		
		if ($editable == true)
			{
				$tr .= "<td class='n_name n_node' id='cont_n_label-$__level_key'>$name</td>
						<td class='actions_bt_node'>
							<a class='edit_c' onclick=\"edit_child('$__level_key');\"><img src='$path/edit.png' alt='"._("Edit Rule")."' title='"._("Edit Rule")."'/></a>
							<a $class onclick=\"delete_child('$__level_key','$path');\"><img src='$path/delete.gif' alt='"._("Delete")."' title='"._("Delete Rule")."'/></a>
							<a onclick=\"clone_child('$__level_key', '$path');\"><img src='$path/clone.png' alt='"._("Clone")."' title='"._("Clone Rule")."'/></a>
						</td>";
			}
		else
		{
			$tr .= "<td class='n_name n_node' id='cont_n_label-$__level_key'>$name</td>
					<td class='actions_bt_node'>
						<a class='edit_c' onclick=\"edit_child('$__level_key');\"><img src='$path/show.png' alt='"._("Show Rule")."' title='"._("Show Rule")."'/></a>
						<a onclick=\"copy_rule('$__level_key');\"><img src='$path/clone.png' alt='"._("Clone Rule to".$editable_files[0])."' title='"._("Clone Rule to ".$editable_files[0])."'/></a>
					</td>";
		}
		
		$tr .= "</tr>";
		
	}
	
	return $tr;
}




/* Utils for tree*/

function getChild($tree, $key, $name_node='', $parents='')
{
	$_level_key_name = $_SESSION['_level_key_name'];
	$at_key = ( preg_match("/^attr_/", $key, $match) != false )  ? true : false;
	
	if ($name_node !== '') 
		$parents [] = "'".$name_node."'";
	
	if ( is_array ($tree) )
	{
		if ( isset ($tree['@attributes']) )
		{
			//echo "Analizando: ". $tree['@attributes'][$_level_key_name]." contra ". $key." AT: $at_key<br/>";
			
			$key2 = ($at_key == true) ? preg_replace("/^attr_/", '', $key) : $key;
			if ($tree['@attributes'][$_level_key_name] === $key2 )
			{
				$tree = ($at_key == true) ? $tree['@attributes'] : $tree;
				
				$name_node = ($at_key == true) ? "@at_".$name_node : $name_node;
				
				return array ('node' => $name_node, 'tree' => $tree, 'parents' => $parents);
			}
				
		}
		
		foreach ($tree as $k => $children)
		{
			if ( $k !== '@attributes' )
			{
				$found = getChild($children, $key, $k, $parents);
				if ( !empty($found) )
					return $found;
			}
	    }
	
	}
}

/*
* Types:
*	[1]  Attribute
*	[2]  Attributes
*	[3]  Text Node
*	[4]  Node with level <=2
*	[5]  Node with level > 2
*/

function getNodeType ($node_name, $node)
{
	if ( isset ($node['tree']['@attributes'][$node_name]) )
		return 1;
	else if ( $node_name == _("Attributes") )
		return 2;
	elseif ( !isset ($node['tree']['@attributes'][$node_name]) && !is_array($node['tree'][0]) )
		return 3;
	elseif ( getLevel($node['tree']) <= 2 )
		return 4;
	else
		return 5;
}

function getLevel ($tree, $level='0')
{
	$max_level = 0;
	if ( is_array ($tree) )
	{
		$level = $level + 1;
		foreach ($tree as $k => $children)
		{
			if ($k !== '@attributes')
			{
				$nl = getLevel($children, $level);
				$max_level = ( $max_level > $nl ) ? $max_level : $nl;
			}
		}
		return $max_level;
	}
	else
		return $level-1;
}


function set_new_lk($tree, $lk, $new_lk)
{
	$_level_key_name = $_SESSION['_level_key_name'];
	$pattern = "/$lk/";
	
	if ( is_array($tree) )
	{
		foreach ($tree as $k => $v)
		{
			if ($k === '@attributes')
				$tree[$k][$_level_key_name] = preg_replace($pattern, $new_lk, $v[$_level_key_name]);
			else
				$tree[$k] = set_new_lk($tree[$k], $lk, $new_lk);
		}
	}
	
	return $tree;
	
}


function formatXmlString($xml) {  
  
  // add marker linefeeds to aid the pretty-tokeniser (adds a linefeed between all tag-end boundaries)
  $xml = preg_replace('/(>)(<)(\/*)/', "$1\n$2$3", $xml);
  
  // now indent the tags
  $token      = strtok($xml, "\n");
  $result     = ''; // holds formatted version as it is built
  $pad        = 0; // initial indent
  $matches    = array(); // returns from preg_matches()
  
  // scan each line and adjust indent based on opening/closing tags
  while ($token !== false) : 
  
    // test for the various tag states
    
    // 1. open and closing tags on same line - no change
    if (preg_match('/.+<\/\w[^>]*>$/', $token, $matches)) : 
      $indent=0;
    // 2. closing tag - outdent now
    elseif (preg_match('/^<\/\w/', $token, $matches)) :
      $pad= $pad-4;
    // 3. opening tag - don't pad this one, only subsequent tags
    elseif (preg_match('/^<\w[^>]*[^\/]>.*$/', $token, $matches)) :
      $indent=4;
    // 4. no indentation needed
    else :
      $indent = 0; 
    endif;
    
    // pad the line with the required number of leading spaces
    $line    = str_pad($token, strlen($token)+$pad, ' ', STR_PAD_LEFT);
    $result .= $line . "\n"; // add to the cumulative result, with linefeed
    $token   = strtok("\n"); // get the next token
    $pad    += $indent; // update the pad size for subsequent lines    
  endwhile; 
  
  return $result;
}

function set_key_name($_level_key_name, $file_xml)
{
	$continue = true;
	$cont = 1;
		
	$aux = $_level_key_name;
			
	while ($continue == true && $cont < 50)
	{
		$pattern = "/".$_level_key_name."/";
		if ( preg_match ($pattern, $file_xml) != false)
		{
			$change = true;
			$_level_key_name = $aux."_".$cont;
		}
		else
			$continue = false;
		
		$cont++;
	}
	
	return $_level_key_name;
	
}


function formatOutput($input, $_level_key_name)
{
	$input = formatXmlString($input);
			
	$pattern = array ( 
		'/\<\?xml.*\?\>/',
		'/\<__rootnode .*\">|\<\/__rootnode\>/',
		"/ $_level_key_name=\".*?\"/"
	);
	
				
	$input = preg_replace($pattern, '', $input);
	
	$pattern = array ( 
		'/ \>/',
		'/^[\r?\n]+/',
		'/^    /',	
		'/\n    /'		
	);
	
	$replacement = array ( 
		'>',
		'',
		'',
		"\n"		
	);		
	
	$output = preg_replace($pattern, $replacement, $input);
	
	return $output;
}

function get_files($file, $num_files=0)
{
	
	$dir = @opendir($file);
	
	if ($dir == false)
		return array();
		
	while ( $element = readdir($dir) )
	{
		if ($element != "." && $element != ".." && preg_match("/\.xml$/", $element))
			$rules[] = $element;
	}

	
	closedir($dir);

	if ( is_array ($rules) )
	{
		sort($rules);
		if ($num_files > 0)
			$rules = array_slice($rules, 0, $num_files);
	}
	
	return $rules;

}

function getAcType($parents)
{
	$pattern = "/^'?var|group|rule'?$/";
	$size = count($parents);
	for ($i=$size-1; $i>=0; $i--)
	{
		if (preg_match($pattern, $parents[$i]))
		{
			$res= array("parent" => preg_replace("/'/", "", $parents[$i]), "current_node" => preg_replace("/'/", "", $parents[$size-1]));
			return $res;
		}
	}
}

function clean_string($string)
{
	$char_list = "\t\n\r\0\x0B";
	$string  = trim($string, $char_list);
	$string  = htmlentities($string, ENT_QUOTES, "UTF-8");
	
	return $string;
}


function getTree($file)
{
	include_once ('classes/Xml_parser.inc');
	include ('conf/_conf.php');
	
	$_SESSION["_current_file"] = $file;
	$filename                  = $rules_file.$file;
			
	if ( file_exists( $filename) )
	{
		
		$file_xml = @file_get_contents ($filename, false);
			
		$_level_key_name             = set_key_name($_level_key_name, $file_xml);
		$_SESSION['_level_key_name'] = $_level_key_name;
							
		if ($file_xml == false)
		{
			return "2###"._("Failure to read XML file");
		}
		else
		{
			$xml_obj=new xml($_level_key_name);
			$xml_obj->load_file($filename);
			
			if ($xml_obj->errors['status'] == false)
				return "3###". _("Format not allowed:")."<br/><div class='errors_xml'>".implode("<br/>", $xml_obj->errors['msg'])."</div>";
			else
				return $xml_obj->xml2array();
		}	
		
	}
	else
	{
		return "2###"._("XML file not found");
	}
}


// Config Section Functions

function get_actions ($agent)
{
	$path = '../pixmaps';
	
	if ( preg_match('/Local$/', $agent[3], $match) == false)
	{
		$key    = "<a id='_key##".$agent[0]."'><img src='$path/key--arrow.png' align='absmiddle' alt='Extract Key' title='Extract Key'/></a>";
		$delete = "<a id='_del##".$agent[0]."'><img src='$path/delete.gif' align='absmiddle' alt='Delete Agent' title='Delete Agent'/></a>";
	}
	else
	{
		$key    = "<span class='unbind'><img src='$path/key--arrow.png' align='absmiddle' alt='Extract Key' title='Extract Key'/></span>";
		$delete = "<span class='unbind'><img src='$path/delete.gif' align='absmiddle' alt='Delete Agent' title='Delete Agent'/></span>";
	}
		
	$restart = "<a id='_restart##".$agent[0]."'><img src='$path/clock.png' align='absmiddle' alt='Restart Agent' title='Restart Agent'/></a>";
	$check   = "<a id='_check##".$agent[0]."'><img src='$path/tick-circle.png' align='absmiddle' alt='Integrity/rootkit checking' title='Integrity/rootkit checking'/></a>";
		
	return $restart.$check.$key.$delete;
}










?>
