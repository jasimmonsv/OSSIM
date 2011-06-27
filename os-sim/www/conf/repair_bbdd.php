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
ob_implicit_flush();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>Alienvault Unified SIEM. Repair BBDD</title>
<link rel="stylesheet" type="TEXT/CSS" href="style/top.css">
</head>

<body marginwidth=0 marginheight=0 topmargin=10 leftmargin=10>
<?php
	echo _("Launching mysqloptimize -A. Please wait a few minutes...\n<br>");
	$host = `grep db_ip /etc/ossim/ossim_setup.conf | cut -f 2 -d '='`;
	if ($host=="") $host = "localhost";
	$cmd = "mysqloptimize -A -u`grep ^user= /etc/ossim/ossim_setup.conf | cut -f 2 -d '='` -h$host  -p`grep ^pass= /etc/ossim/ossim_setup.conf | cut -f 2 -d '='`";
	system($cmd);
	echo _("Finished. Try to login again");
?>
<script>document.location.href = '/ossim/session/login.php'</script>
</body>
</html>
