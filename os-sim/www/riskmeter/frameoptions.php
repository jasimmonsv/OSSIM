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
require_once ("classes/Security.inc");
$ip = GET('ip');
ossim_valid($ip, OSS_IP_ADDR, OSS_NULLABLE, 'illegal:' . _("IP"));
if (ossim_error()) {
    die(ossim_error());
}
require_once ('ossim_conf.inc');
$conf = $GLOBALS["CONF"];
$acid_link = $conf->get_conf("acid_link");
$acid_prefix = $conf->get_conf("event_viewer");
?>
<html>
<frameset rows="15%,85%" border="0" frameborder="0">
<frame src="options.php?ip=<?php
echo $ip ?>">
<frame src="<?php
echo "$acid_link/" . $acid_prefix . "_qry_main.php?new=2&num_result_rows=-1&submit=Query+DB&current_view=-1&ip_addr_cnt=2&ip_addr%5B0%5D%5B0%5D=+&ip_addr%5B0%5D%5B1%5D=ip_src&ip_addr%5B0%5D%5B2%5D=%3D&ip_addr%5B0%5D%5B3%5D=$ip&ip_addr%5B0%5D%5B8%5D=+&ip_addr%5B0%5D%5B9%5D=OR&ip_addr%5B1%5D%5B0%5D=+&ip_addr%5B1%5D%5B1%5D=ip_dst&ip_addr%5B1%5D%5B2%5D=%3D&ip_addr%5B1%5D%5B3%5D=$ip&ip_addr%5B1%5D%5B8%5D=+&ip_addr%5B1%5D%5B9%5D=+&sort_order=time_d" ?>" 
       name="main">
<body>
</body>
</html>

