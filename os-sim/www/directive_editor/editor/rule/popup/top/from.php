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
require_once ("../../../../include/utils.php");
dbConnect();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="../../../../style/directives.css">

		<script type="text/javascript" language="javascript" src="javascript/top.js"></script>
	</head>

	<body>

<?php
$from = GET('from');
$from_list = GET('from_list');
ossim_valid($from, OSS_ALPHA, OSS_PUNC_EXT, 'illegal:' . _("from"));
ossim_valid($from_list, OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE, 'illegal:' . _("from_list"));
if (ossim_error()) {
    die(ossim_error());
}
?>
		<center>
			<table>
				<tr>
					<th width="70px">
						<button class="th" id="all" onclick="onClickAllIp()">+</button>/
						<button class="th" id="inv" onclick="onClickInvIp()">-</button>
					</th>
					<th><?php
echo gettext('Hostname'); ?></th>
					<th><?php
echo gettext('IP'); ?></th>
				</tr>

<?php
if (substr($from_list, 0, 1) == '!') {
    $default_checked = ' checked="checked"';
    $from_list = substr($from_list, 1);
} else $default_checked = '';
if ($host_list = getHostList()) {
    foreach($host_list as $host) {
        $hostname = $host->get_hostname();
        $ip = $host->get_ip();
        if ($from == 'ANY') {
            $checked = ' checked="checked"';
        } elseif (in_array($ip, split(',', $from_list))) {
            $checked = ($default_checked == '') ? ' checked="checked"' : '';
        } else {
            $checked = $default_checked;
        }
?>

				<tr>
					<td>
						<input type="checkbox" id="hosttab" name="chk"
							value="<?php
        echo $ip; ?>"
							<?php
        echo $checked; ?>
							onClick="onClickChk()"
						>
					</td>
					<td><?php
        echo $hostname; ?></td>
					<td><?php
        echo $ip; ?></td>
				</tr>

<?php
    }
}
if ($net_list = getNetList()) {
?>
  <tr>
					<th width="70px">
					  <button class="th" id="all" onclick="onClickAllNet()">+</button>/
						<button class="th" id="inv" onclick="onClickInvNet()">-</button>
					</th>
					<th><?php
    echo gettext('Netname'); ?></th>
					<th><?php
    echo gettext('IPs'); ?></th>
				</tr>
  
  
  <?php
    foreach($net_list as $net) {
        $netname = $net->get_name();
        $ips = $net->get_ips();
        if ($from == 'ANY') {
            $checked = ' checked="checked"';
        } elseif (in_array($netname, split(',', $from_list))) {
            $checked = ($default_checked == '') ? ' checked="checked"' : '';
        } else {
            $checked = $default_checked;
        }
?>

				<tr>
					<td>
						<input type="checkbox" id="nettab" name="chk"
							value="<?php
        echo $netname; ?>"
							<?php
        echo $checked; ?>
							onClick="onClickChk()"
						>
					</td>
					<td><?php
        echo $netname; ?></td>
					<td><?php
        echo $ips; ?></td>
				</tr>

<?php
    }
} ?>

			</table>
			

			
		</center>

		<script type="text/javascript" language="JavaScript">
			window.open("../bottom.php?param=from", "bottom");
		</script>
	</body>
</html>

<?php
dbClose();
?>
