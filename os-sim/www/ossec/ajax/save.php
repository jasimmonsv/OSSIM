<?php

require_once ('classes/Session.inc');
require_once ('classes/Xml_parser.inc');
require_once ('../utils.php');
require_once ('../conf/_conf.php');

$file = $_SESSION["_current_file"];
$path = $rules_file.$file; 

ossim_valid($file, $editable_files[0], 'illegal:' . _("XML file"));
		
if ( ossim_error() )
{
	echo "1###"._("XML file can not be edited");
	exit();
}
else
{
	$_SESSION["_current_file"] = $file;
	$_level_key_name           = $_SESSION['_level_key_name'];
}

if ( !in_array($file, $editable_files) )
{
	echo "2###"._("File not editable");
	exit();
}

$file_tmp      =  uniqid($file)."_tmp.xml";
$path_tmp      =  "/tmp/".$file_tmp; 
$data          =  html_entity_decode(base64_decode($_POST['data']),ENT_QUOTES, "UTF-8");

if (copy ($path , $path_tmp) == false )
	echo "3###";
else
{
	if ( @file_put_contents($path, $data, LOCK_EX) == false )
	{
		@unlink ($path);
		@copy ($path_tmp, $path);
		echo "4###";
	}
	else
	{
		$xml_obj=new xml($_level_key_name);
		$xml_obj->load_file($path);
							
		if ($xml_obj->errors['status'] == false)
		{
			echo "5###<span style='padding-left:45px;'>"._("Format not allowed:")."</span><br/><div class='errors_xml'>".implode("\n", $xml_obj->errors['msg'])."</div>";
			@copy ($path_tmp, $path);
		}
		else
		{
			$array_xml=$xml_obj->xml2array();
			$tree_json              = array2json($array_xml, $path);
			$_SESSION['_tree_json'] = $tree_json;
			$_SESSION['_tree']      = $array_xml;
			echo "6###".base64_encode($tree_json);
		}

		@unlink($path_tmp);	
		
	}
}


?>