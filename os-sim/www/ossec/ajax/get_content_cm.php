<?php
require_once ('classes/Session.inc');
require_once ('../conf/_conf.php');
require_once ('classes/Security.inc');

$file = POST('file');

ossim_valid($file, OSS_SCORE, OSS_ALPHA, OSS_DOT, 'illegal:' . _("XML file"));
		
if ( ossim_error() )
	echo "1";
else
{
	$filename = $rules_file.$file;
	$_SESSION["_current_file"]  = $file;
	
	if ( file_exists( $filename) )
	{
		$file_xml = file_get_contents ($filename, false);
	  
		if ($file_xml == false)
			echo "2";
		else
			echo $file_xml;
	}
	else
		echo "3";
}
?>