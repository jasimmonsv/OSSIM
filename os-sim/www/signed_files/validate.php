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


require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
require_once ('utils.php');


$config    = parse_ini_file("everything.ini");
$path      = $config['sf_dir'];

$file         = base64_decode(GET('f'));
$signature    = base64_decode(GET('s'));
$signed_files = get_signed_files();


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<link rel="stylesheet" href="../style/style.css"/>
	<style type='text/css'>
	    body { width: 99%; margin: auto;}
		#container_center {width: 90%; margin:auto; margin: 30px auto 10px auto;}
		table {background: #FFFFFF !important; width: 100%; border: solid 1px #D2D2D2; }
		th {text-align:center; height: 20px;}
		span {font-weight: bold;}
		.error_message {font-weight: bold; text-align:center; font-size: 11px !important;}
		.cont_message {margin:15px auto; width:100%;}
		.ossim_error {width: auto;}
		.v_ok {font-weight: bold; color:green;}
		.v_ko {font-weight: bold; color:red;}
		
	</style>
</head>
<body>

<?php

$error = false;


if ( array_key_exists($file, $signed_files) && $signed_files[$file][3][0] == true )
{
	if ( file_exists($path.$signature) )
	{
	
		$sig_content    = file_get_contents($path.$signature);
        $sig_dec        = base64_decode($sig_content);
        $f              = @fopen("/tmp/sig_decoded", "wb");
        $res            = @fwrite($f, $sig_dec);
		
		if ($res === false)
		{
			$error = true;
			$error_messages = _("Fail to check signature file <i>$signature</i>");
		}
		else
		{
			$cmdv     = "openssl dgst -sha1 -verify ".trim(str_replace("file://","",$config["pubkey"]))." -signature /tmp/sig_decoded '" . $path.$file . "'";
       		$status   = exec($cmdv, $res);
			$verified = (preg_match("/Verified OK/i", $status)) ? 1 : 0;
		
		}
		
        fclose($f);
    }
	else
	{
		$error = true;
		$error_messages = _("Signature file <i>$signature</i> not found.<br/>If the event is less than one hour old it will not be generated yet. (2)");
	}
}
else
{
	$error = true;
	$error_messages = _("Signature file <i>$signature</i> not found.<br/>If the event is less than one hour old it will not be generated yet. (1)");
}


if ($error == true)
{
	$message = "<div class='ossim_error error_message'>".$error_messages."</div>";
}
else
{
	if ($verified == 1)
		$message  = "<span>"._("Verification")." <span class='v_ok'>"._("OK")."</span></span><br/>";
	else if ($verified == 0)
		$message  = "<span>"._("Verification")." <span class='v_ko'>"._("Failed")."</span></span>";
	else
	{
		$message  = "<span>"._("Verification")." <span class='v_ko'>"._("Failed")."</span></span>"." ";
		$message .= openssl_error_string();
    }
}

?>

	<div id='container_center'>
		<table>
			<thead><th><?php echo _("Verification results")?></th></thead>
			<tbody><tr><td class='noborder'><div class='cont_message'><?php echo _($message) ?></div></td></tr></tbody>
		</table>
		
	</div>
</body>
</html>
