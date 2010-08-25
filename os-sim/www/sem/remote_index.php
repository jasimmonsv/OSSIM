<?php
/*****************************************************************************
*
*    License:
*
*   Copyright (c) 2007-2010 AlienVault
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
require_once ('classes/Session.inc');
require_once ('ossim_conf.inc');
Session::logcheck("MenuEvents", "ControlPanelSEM");
$conf = $GLOBALS["CONF"];
if ($conf->get_conf("server_remote_logger", FALSE)) {
    $remote_user = $conf->get_conf("server_remote_logger_user", FALSE);
    $remote_pass = base64_encode($conf->get_conf("server_remote_logger_pass", FALSE));
    $remote_url = $conf->get_conf("server_remote_logger_ossim_url", FALSE);
    ?>
    <script> function redir(url) { document.location.href = url } </script>
    <img src="../pixmaps/loading.gif" border=0>
    <iframe src="<?=$remote_url."/session/login.php?user=".urlencode($remote_user)."&pass=".$remote_pass?>" style="display:none" onload="redir('<?=$remote_url?>/sem/index.php')"></iframe>
    <?
}
?>
