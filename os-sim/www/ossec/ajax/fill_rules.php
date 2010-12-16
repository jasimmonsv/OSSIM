<?php

require_once ('classes/Session.inc');
require_once ('../utils.php');
require_once ('../conf/_conf.php');

$path_file  = explode("/", POST('file'));
$file       = $path_file[count($path_file)-1];

$rules   	= array();
$options	= '';

$rules = get_files($rules_file);


$options .=  "<optgroup label='Editable rule file'>\n<option selected='selected' value='".$editable_files[0]."'>".$editable_files[0]."</option>\n</optgroup>\n";

if ( count($rules)>0 )
{
	$options .=  "<optgroup label='Rules files read-only'>\n";

	foreach ($rules as $k => $v)
	{
		$current_file = explode(".", $v);
		if ( strcmp($v, $file) != 0 && $current_file[1] == 'xml' )
			$options .= "<option style='text-align: left;' value='$v'>$v</option>\n";
	}
	$options .= "</optgroup>\n";	
}

echo $options;








?>

