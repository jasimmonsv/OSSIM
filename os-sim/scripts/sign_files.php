<?php

/*****************************************************************************
*
*    License:
*
*   Copyright (c) 2003-2006 ossim.net
*   Copyright (c) 2007-2009 AlienVault
*   All rights reserved.
*
*   This package is free software; you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation; version 2 dated June, 1991.
*   You may not use, modify or distribute this program under any other version
*   of the GNU General Public License.
*
*   This package is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this package; if not, write to the Free Software
*   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
*   MA  02110-1301  USA
*
*
* On Debian GNU/Linux systems, the complete text of the GNU General
* Public License can be found in `/usr/share/common-licenses/GPL-2'.
*
* Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
****************************************************************************/

/*****************************************************************************
* This script apply SQL schema upgrade files automatically
****************************************************************************/



function get_no_signed_files()
{
	require_once('utils.php');
	
	$no_signed_files = array();
	$config          = parse_ini_file("everything.ini");
	$path            = $config['sf_dir'];
	$files           = get_files();
	
	if ( is_array($files) )
	{
		foreach ($files as $k =>$v)
		{
			$file                 = basename($v);
			$name                 = explode(".", $file);
			$name[count($name)-1] = "sig";
			
			$sig_file             = implode(".", $name);
			
			if ( !file_exists($path.$sig_file) )
				$no_signed_files[$file] = $sig_file;
		}
	}
	
	return $no_signed_files;

}

$path_directory = '/usr/share/ossim/www/signed_files/';
$path_files     = '/var/ossim/files/';

ini_set('include_path', $path_directory);


$no_signed_files = get_no_signed_files();

foreach ($no_signed_files as $k => $v)
{
	echo _("Signing")." ".$v;
	$last_line = exec("openssl dgst -sha1 -sign /var/ossim/keys/rsaprv.pem -passin pass:`grep ^pass= /etc/ossim/ossim_setup.conf | cut -f 2 -d \"=\"` $path_files$k | base64 > $path_files$v", $output, $ret); 
		
	if ( $ret === 0  )
		echo sprintf("%'.30s\n", _("Done"));
	else
		echo sprintf("%'.30s\n", _("Fail"));
}












	

?>
