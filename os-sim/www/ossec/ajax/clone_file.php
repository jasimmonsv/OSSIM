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
	 
	if ( @file_exists($path_file) == true )
	{
		if ( @file_exists($path_new_file) == false )
		{
			if (@copy ($path_file , $path_new_file ) == false )
				echo "2###"._("Failure to clone file")." $file";
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