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
// menu authentication
require_once ('classes/Session.inc');
Session::logcheck("MenuTools", "ToolsScan");
ob_implicit_flush();
?>

<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>

<?php
require_once 'classes/Security.inc';
include ("../hmenu.php");
$scan = GET('scan');
$delete = GET('delete');
ossim_valid($scan, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("Scan"));
ossim_valid($delete, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("Scan"));
if (ossim_error()) {
    die(ossim_error());
}
require_once ('classes/Scan.inc');

$rscan = new RemoteScan("","");
if ($delete!="") $rscan->delete_scan($delete);
if ($scan!="") {

	$rscan->import_scan($scan);
	if ($rscan->err()!="") 
		echo _("Failed remote network scan: ") . "<font color=red>".$rscan->err() ."</font><br/>\n";
	else
		$rscan->save_scan();
	echo gettext("Scan imported successfully") . ".<br/><br/>";
	echo "<a href=\"index.php#results\">" . gettext("Click here to show the results") . "</a>";
	
} else {

	if ($rscan->available_scan()) {
		
		?>
		<table align="center" cellpadding="2" cellspacing="2">
		<tr>
			<th><?=_("Available remote scan jobs")?></th>
			<th><?=_("Actions")?></th>
		</tr>
		<?
		$reports = $rscan->get_scans();
		if (count($reports)==0) {
			echo "<tr><td colspan='2'><i>"._("No remote agents connected")."</i></td>";

		}
		foreach ($reports as $id => $scans) {
			echo "<tr><td colspan='2' style='text-align:left'><img src='../pixmaps/arrow-315-small.png' align='absmiddle'/&nbsp;<b>$id</b></td>";
			foreach ($scans as $scan) {
				echo "<tr><td style='text-align:left;padding-left:20px' nowrap>&nbsp;$scan</td>";
				echo "<td><a href='?scan=".urlencode($scan)."'>"._("Import")."</a>&nbsp;&nbsp;<a href='?delete=".urlencode($scan)."'>"._("Delete")."</a></td></tr>";
			}
		}
		?>
		</table>
		<?
		
	} else {
	
		echo _("Unable to launch remote network scan: ") . "<font color=red>".$rscan->err() ."</font><br/>\n";
			
	}
}
?>

</body>
</html>


