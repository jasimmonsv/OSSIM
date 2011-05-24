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
require_once ('classes/Security.inc');
Session::logcheck("MenuPolicy", "ToolsScan");
ob_implicit_flush();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<style type='text/css'>
		
		.ossim_success, .ossim_error { width: 80%;}
		.ossim_success a {
			color:#4F8A10;
			font-weight: bold;
		}
		
		.agent { padding: 3px 5px;}
		.scan_jobs { padding: 3px 5px;}
		.actions { width: 50px; padding: 3px;}
		
		.row { padding: 3px 5px;}
		
	</style>
</head>
<body>

<?php

include ("../hmenu.php");
$scan   = GET('scan');
$delete = GET('delete');


ossim_valid($scan, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("Scan"));
ossim_valid($delete, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("Scan"));

if (ossim_error()) {
    die(ossim_error());
}

require_once ('classes/Scan.inc');

$rscan = new RemoteScan("","");

if ( $delete!="" ) 
	$rscan->delete_scan($delete);

if ( $scan != "" ) 
{
	$rscan->import_scan($scan);
	
	if ( $rscan->err() !="" ) 
		echo ossim_error( _("Failed remote network scan: ").$rscan->err() );
	else
		$rscan->save_scan();
	
	echo "<div class='ossim_success'>";
	echo _("Scan imported successfully") . "<span style='margin-left:5px'><a href='index.php#results'>[". _("Click here to show the results")."]</a></span>";
	echo "</div>";
} 
else 
{

	if ($rscan->available_scan()) {
		
		?>
		<table align="center" cellpadding="2" cellspacing="2">
			<tr>
				<th class='agent'><?php echo _("Agent")?></th>
				<th class='scan_jobs'><?php echo _("Available remote scan jobs")?></th>
				<th class='actions'><?php echo _("Actions")?></th>
			</tr>
			<?php
			$reports = $rscan->get_scans();
			
			if ( count($reports)==0 ) 
			{
				echo "<tr><td colspan='3' style='height: 30px' class='nobborder center'><i>"._("No remote agents connected")."</i></td>";
			}
			else
			{
				foreach ($reports as $id => $scans) 
				{
					echo "<tr><td valing='middle' rowspan='".count($scans)."' class='left row'><img src='../pixmaps/arrow-315-small.png' align='absmiddle'/>&nbsp;<strong>$id</strong></td>";
					
						foreach ($scans as $scan) 
						{
							?>
								<td class='left row' nowrap='nowrap'><?php echo $scan?></td>
								<td style='text-align:center;' class='row'>
									<a href='?scan=<?php echo urlencode($scan)?>'><img src='../pixmaps/page_add.png' align='absmiddle' alt='<?php echo _("Import")?>'/></a>
									<a href='?delete=<?php echo urlencode($scan)?>'><img src='../pixmaps/delete.gif' align='absmiddle' alt='<?php echo _("Delete")?>'/></a>
								</td>
							</tr>
							<?php
						}
				}
			}
			?>
		</table>
		<?php
		
	} 
	else 
		echo ossim_error( _("Unable to launch remote network scan: ").$rscan->err() );	
	
}
?>

</body>
</html>


