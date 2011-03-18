<?php
	
function getProtocolUrl()
{
    $protocol_url = ( empty($_SERVER["HTTPS"]) ) ? 'http://'.$_SERVER['SERVER_ADDR'] : 	'https://'.$_SERVER['SERVER_ADDR'];
	return $protocol_url;	
		
}

function check_size($num, $width, $height) 
{
	if ($num == 1) 
		return ($width < 310 && $height < 70 && $width > 290 && $height > 50) ? 1 : 0;
	
	if ($num == 2) 
		return ($width < 220 && $height < 50 && $width > 200 && $height > 35) ? 1 : 0;
	
	if ($num == 3) 
		return ($width < 1250 && $height < 135 && $width > 1230 && $height > 120) ? 1 : 0;
	
	return 0;
}


function upload($num)
{
	$error           = null;
	$msg             = null;
	$w               = null;
	$fileElementName = 'fileToUpload'.$num;
	
	$error_messages = array ("1"   => _("The uploaded file exceeds the upload_max_filesize directive in php.ini"),
							 "2"   => _("The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form"),
							 "3"   => _("The uploaded file was only partially uploaded"),
							 "4"   => _("No file was uploaded."),
							 "6"   => _("Missing a temporary folder"),
							 "7"   => _("Failed to write file to disk"),
							 "8"   => _("File upload stopped by extension"),
							 "999" => _("No error code avaiable")
						);
	
	
	if(!empty($_FILES[$fileElementName]['error']))
	{
		$key   = $_FILES[$fileElementName]['error'];
		$error = ( array_key_exists($key, $error_messages ) ) ? $error_messages[$key] : $error_messages["999"];
		
	} 
	elseif ( empty($_FILES[$fileElementName]['tmp_name']) || $_FILES[$fileElementName]['tmp_name'] == 'none' )
	{
		$error = _('No file was uploaded.');
	} 
	elseif ( $num == 3 && !preg_match("/\.(png)$/i",$_FILES[$fileElementName]['name']) ) 
	{
		$error =  _("The report header must be a valid <strong>png</strong> file");
	} 
	elseif ( !preg_match("/\.(jpg|jpeg|gif|png)$/i",$_FILES[$fileElementName]['name']) )
	{
		$error =  _("The logo must be a valid <strong>jpeg</strong>, <strong>gif</strong> or <strong>png</strong> file");
	} 
	elseif (preg_match("/\.(php|phtml|html|js|shtml|pl|py)/",$_FILES[$fileElementName]['name']))
	{
		$error =  _("The logo must be a valid <strong>jpeg</strong>, <strong>gif</strong> or <strong>png</strong> file");
	} 
	else
	{
	
		list($width, $height, $type, $attr) = getimagesize($_FILES[$fileElementName]['tmp_name']);
						
		if (!check_size($num,$width,$height)) 
		{
			$error =  _("The image size is not correct");
		}
		else 
		{
			$filename = $_FILES[$fileElementName]['name'];
			
			$filesize = @filesize($_FILES[$fileElementName]['tmp_name']);
			
			if ( $filename != "" && $filesize > 0 && ($type >= 1 && $type <=3 ) && check_size($num,$width,$height) ) 
			{
			
				if ($num == "1")
				{
					$tmpfname = "../tmp/headers/_login_logo.png";
				} 
				elseif ($num == "2") 
				{
					$tmpfname = "../tmp/headers/_header_logo.png";
				} 
				elseif ($num == "3") 
				{
					$tmpfname = "../tmp/headers/default.png";
					if (!file_exists("../tmp/headers/default_copy.png")) 
					{
						@copy("../tmp/headers/default.png", "../tmp/headers/default_copy.png");
					}
				}
				
				@move_uploaded_file($_FILES[$fileElementName]['tmp_name'], $tmpfname);
								
				$msg = str_replace("../tmp/headers/","",$tmpfname);
			} 
			else
			{
				$error = _("Error in the image format file");
			}
		}
	}

	echo "{";
	echo	"error: '" . $error . "',\n";
	echo	"msg: '"   . $msg . "'\n";
	echo "}";

}

?>