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

        var chemin = window.parent.window.parent.document.getElementById("bottom").contentWindow;
				if (param == "plugin_id") {

					var chks = parent.frames[0].document.getElementsByName("chk");

					for (i = 0; i < chks.length; i++) {

						if (chks[i].checked != "") {

							chemin.document.getElementById("plugin_id").value = chks[i].value;
							chemin.onChangePluginId();
							break;
						}
					}

					cancel();
				}
				else if (param == "plugin_sid") {

					var chks = parent.frames[0].document.getElementsByName("chk");

					var pos_result = "";
					var neg_result = "!";

					for (i = 0; i < chks.length; i++) {

						var new_value = chks[i].value;

						if (chks[i].checked != "") {

							comma = (pos_result == "") ? "" : ",";
							pos_result += comma + new_value;
						}
						else {

							comma = (neg_result == "!") ? "" : ",";
							neg_result += comma + new_value;
						}
					}

					var plugin_sid_list = chemin.document.getElementById("plugin_sid_list");

					if (pos_result.split(",").length == chks.length) {

						plugin_sid_list.value = "";

						chemin.document.getElementById("plugin_sid").value = "ANY";
					}
					else {

						plugin_sid_list.value =
							(pos_result.length < neg_result.length) ? pos_result : neg_result;

						chemin.document.getElementById("plugin_sid").value = "LIST";
					}

					plugin_sid_list.title = plugin_sid_list.value;
					plugin_sid_list.disabled = "";

					chemin.onChangePluginSid();
					chemin.onChangePluginSidList();

					cancel();
				}
				else if (param == "from" || param == "to") {

					var chks = parent.frames[0].document.getElementsByName("chk");

					var pos_result = "";
					var neg_result = "!";

					for (i = 0; i < chks.length; i++) {

						var new_value = chks[i].value;

						if (chks[i].checked != "") {

							comma = (pos_result == "") ? "" : ",";
							pos_result += comma + new_value;
						}
						else {

							comma = (neg_result == "!") ? "" : ",";
							neg_result += comma + new_value;
						}
					}
					var from_to_list = chemin.document.getElementById(param + "_list");

					if (pos_result.split(",").length == chks.length) {

						from_to_list.value = "";

						chemin.document.getElementById(param).value = "ANY";
					}
					else {

						from_to_list.value =
							(pos_result.length < neg_result.length) ? pos_result : neg_result;

						chemin.document.getElementById(param).value = "LIST";
					}

					from_to_list.title = from_to_list.value;
					from_to_list.disabled = "";

					chemin.onChangeIPSelectBox(param);
					chemin.onChangeIPList(param + "_list");

					cancel();
				}
				else if (param == "sensor") {

					var chks = parent.frames[0].document.getElementsByName("chk");

					var pos_result = "";
					var neg_result = "!";

					for (i = 0; i < chks.length; i++) {

						var new_value = chks[i].value;

						if (chks[i].checked != "") {

							comma = (pos_result == "") ? "" : ",";
							pos_result += comma + new_value;
						}
						else {

							comma = (neg_result == "!") ? "" : ",";
							neg_result += comma + new_value;
						}
					}
					var sensor_list = chemin.document.getElementById("sensor_list");

					if (pos_result.split(",").length == chks.length) {

						sensor_list.value = "";

						chemin.document.getElementById("sensor").value = "ANY";
					}
					else {

						sensor_list.value =
							(pos_result.length < neg_result.length) ? pos_result : neg_result;

						chemin.document.getElementById("sensor").value = "LIST";
					}

					sensor_list.title = sensor_list.value;
					sensor_list.disabled = "";

					chemin.onChangeIPSelectBox(param);
					chemin.onChangeIPList("sensor_list");
					
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
		
    <div style="
      background-color:#17457c;
      width:100%;
      position:fixed;
      height:2px;
      left:0px;"></div>
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
