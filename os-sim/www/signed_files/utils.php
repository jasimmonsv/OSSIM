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
	$ext = strtolower(array_pop($ext));
	
	if ( array_key_exists( $ext, $allowed_ext) )
		return $allowed_ext[$ext];
	else
		return array("document.png", "File");
	
}

function is_signed($file)
{
	$config = parse_ini_file("everything.ini");
    $path   = $config['sf_dir'];
	
	$file_aux      = explode(".", $file);
	$ext           = array_pop($file_aux);
	$signed_file   = implode("", $file_aux).".sig";
	
	$validate_sig  = ( file_exists($path.$signed_file) ) ? array(true, "$signed_file") : array(false, "");
	return $validate_sig;
}


function get_signed_files($date_filter='all')
{
	$files = get_files();
						
	if ($files != -1 )
	{
		if ( is_array($files) && !empty ($files) )
		{
			foreach ($files as $k => $v)
			{
				$date = get_date($v, "d-m-Y");
				
				if ($date_filter == "all" || ( $date_filter != "all" && $date_filter == $date) )
				{
					$name                  = basename($v);
					$size                  = get_sizef($v);
					$ext                   = get_extension($v);
					$info_files[$name]     = array($date, $size, $ext, is_signed($name)); 
				}
			}
			return $info_files;
		}
		else
			return array();
	}
	else
		return -1;
		
}

function available_dates()
{
	$dates = array();
	$files = get_files();
			
	if ( is_array($files) )
	{	
		foreach ($files as $k => $v)
		{
			$pattern = "/[[:alpha:]]+[[:digit:]]{4}([[:digit:]]{4})([[:digit:]]{2})([[:digit:]]{2})/";
			if (preg_match($pattern, basename($v), $match) != false )
				$date = $match[1].$match[2].$match[3];
			else
			{
				$match = array ("",date("Y"),date("m"),date("d"));
				$date  = date("Ymd");
			}
					
			$dates[$match[3]."-".$match[2]."-".$match[1]] = $date;
		}
		
		arsort($dates);
		
		return $dates;
	}
	else
		return -1;
}


function get_date($filename, $format)
{
	$date_ok = false;
	$pattern = "/[[:alpha:]]+[[:digit:]]{4}([[:digit:]]{4})([[:digit:]]{2})([[:digit:]]{2})/";
	
	if (preg_match($pattern, $filename, $match) != false )
	{
		if ( @checkdate($match[2], $match[3], $match[1]) )
			$date_ok = true;
	}
	
	if ($date_ok == false)
		$match = array ("",date("Y"),date("m"),date("d"));
			
	switch ($format)
	{
		case "d-m-Y":
			return $match[3]."-".$match[2]."-".$match[1];
		break;
		
		case "m-d-Y":
			return $match[2]."-".$match[3]."-".$match[1];
		break;
			
		default:
			return $match[1].$match[2].$match[3];
	}
}	
	

function get_signatures()
{
	$sigf   = array();
	$config = parse_ini_file("everything.ini");
    $path   = $config['sf_dir'];
	exec ("ls $path*.sig", $sigf, $ret);
			
	if ($ret != 0)
		return -1;
	else
		return $sigf;

}

function get_files()
{
	$files  = array();
	$config = parse_ini_file("everything.ini");
    $path   = $config['sf_dir'];
	$ret1   =  $ret2  =  0;
	
	$ret1   = get_signatures();
			
	exec ("ls $path*", $all_files, $ret2);
	
	if ( is_array($ret1) && (is_array($all_files) && $ret2 === 0) )
	{
		$files = array_diff($all_files, $ret1);
		return $files;
	}
	else
		return -1;
}


?>