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
require_once ('ossim_conf.inc');
require_once ('classes/Session.inc');
Session::logcheck("MenuReports", "ReportsReportServer");


$conf = $GLOBALS["CONF"];
/* Generate reporting server url */
switch ($conf->get_conf("bi_type", FALSE)) {
    case "jasperserver":
    default:
        if ($conf->get_conf("bi_host", FALSE) == "localhost") {
            $bi_host = $_SERVER["SERVER_ADDR"];
        } else {
            $bi_host = $conf->get_conf("bi_host", FALSE);
        }
        if (!strstr($bi_host, "http")) {
            $reporting_link = "http://";
        }
        $bi_link = $conf->get_conf("bi_link", FALSE);
        $bi_link = str_replace("USER", $conf->get_conf("bi_user", FALSE) , $bi_link);
        $bi_link = str_replace("PASSWORD", $conf->get_conf("bi_pass", FALSE) , $bi_link);
        $reporting_link.= $bi_host;
        $reporting_link.= ":";
        $reporting_link.= $conf->get_conf("bi_port", FALSE);
        //$reporting_link.= $bi_link;
        $reporting_link = $bi_link;
}
header('Location: '.$reporting_link);
?>