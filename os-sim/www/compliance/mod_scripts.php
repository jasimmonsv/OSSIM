<?php
/*****************************************************************************
*
*    License:
*
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
Session::logcheck("MenuIntelligence", "ComplianceMapping");
$action = GET('action');
ossim_valid($action, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("action"));
if (ossim_error()) {
    die(ossim_error());
}
if ($action == "launch") {
	system('/usr/bin/perl -I"/usr/share/ossim/compliance/scripts/datawarehouse/perl" "/usr/share/ossim/compliance/scripts/datawarehouse/OSSIM_ETL.job_ReportingETL.pl" --context=Default 2>&1 &');
	system('/usr/bin/perl /usr/share/ossim/compliance/scripts/datawarehouse/iso27001sid.pl 2>&1 &');
}
$cmd = "ps ax | grep 'compliance'";
$psresponse = explode("\n",`$cmd`);
$inprogress = false;
foreach ($psresponse as $line) {
	if (preg_match("/compliance\/scripts\/datawarehouse/",$line)) { $inprogress = true; }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?php echo gettext("OSSIM Framework"); ?> - Compliance </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>
<? include("../hmenu.php"); ?>
<form action="mod_scripts.php">
<input type="hidden" name="action" value="<?php echo ($inprogress) ? "" : "launch"?>">
<table class="transparent">
	<?php if ($inprogress) { ?>
	<tr><td class="nobborder"><?php echo _("The compliance scripts are running right now") ?>.</td></tr>
	<?php } elseif ($action == "launch") { ?>
	<tr><td class="nobborder"><b><?php echo _("The compliance scripts has been successfully launched") ?>.</b></td></tr>
	<?php } else { ?>
	<tr>
		<td class="nobborder"><?php echo _("Click here to launch now the compliance scripts") ?></td>
	</tr>
	<tr><td class="nobborder"><input type="submit" value="<?php echo _("Run") ?>"></td></tr>
	<?php } ?>
</table>
</form>
</body>
</html>
