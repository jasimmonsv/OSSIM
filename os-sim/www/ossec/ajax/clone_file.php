<?php
require_once('../conf/_conf.php');

$new_file      = base64_decode($_POST["new_file"]);
$new_file_ext  = $new_file.".xml";
$path_file     = $rules_file.$editable_files[0];
$path_new_file = $rules_file.$new_file_ext;

$pattern = "/^[0-9a-zA-Z_\-]+$/";  

if ( preg_match($pattern, $new_file) == false )
	echo "1###"._("Filename not allowed.  Characters allowed: A-Za-z0-9_-");
else
{
	 
	if ( file_exists($path_file) == true )
	{
		if ( file_exists($path_new_file) == false )
		{
			if (copy ($path_file , $path_new_file ) == false )
				echo "2###"._("Failure to clone file $file");
			else
				echo "3###"._("Cloned Sucessfully");
		}
		else
			echo "1###"._("File already exists. Choose another name.");
	}
	else
		echo "2###"._("File to clone not found");
			

}
	

?>