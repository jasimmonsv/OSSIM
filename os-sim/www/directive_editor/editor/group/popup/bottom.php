<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="../../../style/directives.css">

		<script type="text/javascript" language="JavaScript">

	function cancel() {
        var fenetre = window.parent.window.parent.document.getElementById('fenetre');
        var fond = window.parent.window.parent.document.getElementById('fond');
        fenetre.childNodes[0].src = "";
				fenetre.style.display = 'none';
				fond.style.display = 'none';
	}
      
	function ok(param) {
alert(window.parent.document.getElementById("bottom"));
        var chemin = window.parent.window.parent.document.getElementById("bottom").contentWindow;
		if (param == "directive_id") {

			var chks = parent.frames[0].document.getElementsByName("chk");

			var result = "";

			for (i = 0; i < chks.length; i++) {

				var new_value = chks[i].value;

				if (chks[i].checked != "") {

					comma = (result == "") ? "" : ",";
					result += comma + new_value;
				}
			}

			var list = chemin.document.getElementById("list");

			list.value =result;

			list.title = list.value;

			chemin.onChangelist();

			cancel();
		}
	}

		</script>
	</head>

	<body>
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
require_once ('classes/Security.inc');
$param = GET('param');
$disabled = GET('disabled');
ossim_valid($param, OSS_ALPHA, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("param"));
ossim_valid($disabled, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("disabled"));
if (ossim_error()) {
    die(ossim_error());
}
?>
		<center>
			<button style="width: 80px; margin-top:8px; cursor:pointer;"
				id="cancel"
				onclick="cancel()"
			><?php
echo gettext('Cancel'); ?></button>
			&nbsp;
			<button style="width: 40px; margin-top:8px; cursor:pointer;"
				id="ok"
				onclick="ok('<?php
echo $param; ?>')"
				<?php
if ($disabled == 'true') echo 'disabled="disabled"' ?>
			><?php
echo gettext('OK'); ?></button>
		</center>
	</body>
</html>
