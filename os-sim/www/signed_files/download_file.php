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
require_once ("classes/Session.inc");
require_once ("classes/Security.inc");
require_once ("utils.php");

$file         = urldecode(GET('file'));
$date         = urldecode(GET('date'));

$config       = parse_ini_file("everything.ini");
$path         = $config['sf_dir'];

$signed_files = get_signed_files($date);

if ( array_key_exists($file, $signed_files) )
{
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename='.$file);
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    ob_clean();
    flush();
    readfile($path.$file);
}
else
{
?>
	<html>
		<head>
			<title> <?php echo gettext("OSSIM Framework"); ?> </title>
			<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
			<meta http-equiv="Pragma" content="no-cache"/>
			<link rel="stylesheet" type="text/css" href="../style/style.css"/>
					
			<style type='text/css'>
			#container_center {width: 80%; margin:auto; margin: 30px auto 10px auto;}
			.error_messages {font-weight: bold; text-align:center;}
			</style>
				
		</head>
		<body>
			<div class='ossim_error error_messages'><?php echo _("$file not found")?></div>
		</body>
	</html>

<?php }  ?>
