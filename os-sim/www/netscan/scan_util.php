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
* - __os2pixmap()
* - scan2html()
* Classes list:
*/
/*
* scan_util.php
*
* methods not included in Scan.inc go here.
*/
function __os2pixmap($os) {
    $pixmap_dir = "../pixmaps/";
    if (preg_match('/win/i', $os)) {
        return "<img src=\"$pixmap_dir/os/win.png\" alt=\"Windows\" />";
    } elseif (preg_match('/linux/i', $os)) {
        return "<img src=\"$pixmap_dir/os/linux.png\" alt=\"Linux\" />";
    } elseif (preg_match('/bsd/i', $os)) {
        return "<img src=\"$pixmap_dir/os/bsd.png\" alt=\"BSD\" />";
    } elseif (preg_match('/mac/i', $os)) {
        return "<img src=\"$pixmap_dir/os/mac.png\" alt=\"MacOS\" />";
    } elseif (preg_match('/sun/i', $os)) {
        return "<img src=\"$pixmap_dir/os/sunos.png\" alt=\"SunOS\" />";
    } elseif (preg_match('/solaris/i', $os)) {
        return "<img src=\"$pixmap_dir/os/sunos.png\" alt=\"Solaris\" />";
    }
}
function scan2html($scan) {
    $count = 0;
    $html = "<br/>";
    
	foreach($scan as $host) 
	{
        $html.= "<tr>";
        $html.= "<td width='120px' nowrap='nowrap'>" . $host['ip'] . "</td>\n";
        $html.= "<td nowrap='nowrap'>" . $host['mac'];
        $html.= "&nbsp;" . $host['mac_vendor'] . "</td>\n";
        $html.= "<td nowrap='nowrap'>" . $host['os'] . "&nbsp;";
        $html.= __os2pixmap($host['os']) . "&nbsp;</td>\n";
        $html.= "<td>";
        
		foreach($host["services"] as $k => $service) {
            $title = $service["port"] . "/" . $service["proto"] . " " . $service["version"];
            $html.= " <span title=\"$title\"> ";
            $html.= ($service["service"]!="") ? $service["service"] : $k;
            $html.= "</span>&nbsp;";
        }
        
		$html.= "&nbsp</td>\n";
        $html.= "<td><input checked='checked' type=\"checkbox\" 
                value=\"" . $host['ip'] . "\" name=\"ip_$count\"/></td>\n";
        $html.= "</tr>";
        $count+= 1;
    }
	
	?>
	<form action="../host/newhostform.php" method="POST">
		<input type="hidden" name="scan" value="1" />
		<input type="hidden" name="ips" value='<?php echo $count?>'/>
		
		<table align="center" width="80%" cellpadding="2">
			<tr>
				<th colspan='5'><?php echo _("Scan results")?></th>
			</tr>
			</tr>
				<th style='padding: 3px;'><?php echo _("Host")?></th>
				<th style='padding: 3px;'><?php echo _("Mac")?></th>
				<th style='padding: 3px;'><?php echo _("OS")?></th>
				<th style='padding: 3px;'><?php echo _("Services")?></th>
				<th style='width:50px; padding: 3px;'><?php echo _("Insert")?></th>
			</tr>
			<?php echo $html?>
			<tr>
				<td colspan="5" class='center' style='padding: 10px 0px'>
					<input type='submit'class='button' value='<?php echo _("Update database values")?>'/>
					<input type="button" class="buttond" onclick="document.location.href='../netscan/index.php?clearscan=1'" value='<?php echo _('Clear scan result')?>'/>
				</td>
			</tr>
		</table>
	</form>
    <br/>
    <br/>
	<?php
}
?>
