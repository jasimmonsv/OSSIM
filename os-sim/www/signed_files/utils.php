<?php

function get_sizef($file , $decimal = 2)
{
	$size      = filesize($file);
	
	if ($size != 0)
	{
		$unit_data = array(" Bytes", " KB", " MB", " GB", " TB");  
		return 	round($size/pow(1024,($i = floor(log($size, 1024)))),$decimal ).$unit_data[$i];
	}
	else
		return "0 KB";
}

function get_extension($file)
{
	$allowed_ext = array("pdf" => array("document-pdf.png", "PDF File"), "xml" => array("document-table.png", "XML File"));
	$ext = explode(".", $file);
	$ext = array_pop($ext);
	
	if ( array_key_exists( $ext, $allowed_ext) )
		return $allowed_ext[$ext];
	else
		return array("document.png", "File");
	
}

function is_signed($file, $path)
{
	$file_aux      = explode(".", $file);
	$ext           = array_pop($file_aux);
	$signed_file   = implode("", $file_aux).".sig";
	
	$validate_sig  = ( file_exists($path.$signed_file) ) ? array(true, "$signed_file") : array(false, "");
	return $validate_sig;
}


function get_signed_files()
{
	$signed_files = array();
	$config = parse_ini_file("everything.ini");
    $path   = $config['sf_dir'];
	
	exec ("ls $path*", $sf, $ret1);
	exec ("ls $path*.sig", $sigf, $ret2);
		
	
	if ($ret1 === 0 && $ret2 === 0)
	{
		if ( is_array($sf) && !empty ($sf) )
		{
			$sf = array_diff($sf, $sigf);
									
			foreach ($sf as $k => $v)
			{
				$name                  = basename($v);
				$size                  = get_sizef($v);
				$ext                   = get_extension($v);
				$signed_files[$name]   = array(date("d-m-Y H:i:s", filemtime($v)), $size, $ext, is_signed($name, $path)); 
			}
			return $signed_files;
		}
		else
			return $signed_files;
	}
	else
	{
		return -1;
	}	
	
}


function generate_sequence($max_seq)
{
	$sequence = $inc =  0;

	while ( $inc <= $max_seq )
	{
		$inc       = $inc + 5; 
		$sequence .= ", $inc";
	}
	
	return $sequence;

}





?>