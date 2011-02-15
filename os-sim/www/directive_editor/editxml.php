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
/**
* Class and Function List:
* Function list:
* Classes list:
*/
require_once ('classes/Session.inc');
Session::logcheck("MenuIntelligence", "CorrelationDirectives");
require_once ('classes/Security.inc');
require_once ('classes/Xml_parser.inc');
require_once ('../ossec/utils.php');

$withoutmenu = (GET("withoutmenu")=="1" || POST("withoutmenu")=="1") ? 1 : 0;
$xml_file = (GET("xml_file")!="") ? GET("xml_file") : POST("xml_file");
$code = POST("data");
ossim_valid($xml_file, OSS_ALPHA, OSS_DOT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("xml_file"));
if (ossim_error()) {
    die(ossim_error());
}
$orig_xml_file = $xml_file;
if ($xml_file!="") $xml_file = "/etc/ossim/server/".$xml_file;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<link rel="stylesheet" href="../style/style.css" />
		<link rel="stylesheet" type="text/css" href="../ossec/css/ossec.css" />
		<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
		<script type="text/javascript" src="../js/codemirror/codemirror.js"></script>		
		<script type="text/javascript" language="javascript">
			var editor;
			$(document).ready(function() {
				editor = new CodeMirror.fromTextArea("data", {
					parserfile: "parsexml.js",
					stylesheet: "../style/xmlcolors.css",
					path: "../js/codemirror/",
					continuousScanning: 500,
					height: "500px",
					content: $('#data').val(),
					lineNumbers: true
				});
			});
		</script>
	</head>
	<body><br>
	<?php
		if (!$withoutmenu) include("../hmenu.php");

		if (file_exists($xml_file) && $code!="")
		{
			$xml_obj=new xml("_key");
            $xml_obj->load_string($code);
			if ($xml_obj->errors['status'] == false)
			{
				echo "<div class='oss_error' style='padding-left:70px;width:90%'>"._("Format not allowed").": ".implode("", $xml_obj->errors['msg'])."</div>";
			}
			else
			{
				// save without errors
				copy($xml_file,$xml_file."-old");
				file_put_contents($xml_file,$code);
			}
        }  
	?>
	
	<form action="editxml.php" method="post" id="fo" onsubmit="$('#data').val(editor.getCode())">
	<table width="100%"><tr>
		<td style="text-align:left;padding-bottom:4px"><span style="color:gray"><?php echo _("Editing directives file").": <b>$xml_file</b>" ?></span></td>
		<td style="text-align:right;padding-bottom:4px">
			<a href="main.php" target="main" style="text-decoration:none"><span class="buttonlink"><img src="../pixmaps/theme/any.png" border="0" align="absmiddle" style="padding-bottom:2px;padding-right:5px"><?php echo _("Cancel") ?></span></a>
			<a href="javascript:;" onclick="$('#fo').submit()" style="text-decoration:none"><span class="buttonlink"><img src="../ossec/images/database-ins.png" border="0" align="absmiddle" style="padding-bottom:2px"><?php echo _("Save") ?></span></a>
		</td>
	</tr>
	</table>
	<input type="hidden" name="xml_file" value="<?php echo $orig_xml_file?>"/>
	<input type="hidden" name="withoutmenu" value="<?php echo $withoutmenu?>"/>
	<textarea name="data" id="data"><?php
		if ($code!="") echo $code;
		elseif (file_exists($xml_file)) readfile($xml_file);
	?></textarea>
	</form>
	</body>
</html>
