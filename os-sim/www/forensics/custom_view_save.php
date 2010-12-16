<?
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
require_once('classes/Session.inc');
require_once('classes/User_config.inc');
Session::logcheck("MenuEvents", "EventsForensics");
$login = Session::get_session_user();
$db = new ossim_db();
$conn = $db->connect();
$config = new User_config($conn);
$session_data = $_SESSION;
foreach ($_SESSION as $k => $v) {
	if (preg_match("/^(_|alarms_|back_list|current_cview|views|ports_cache|acid_|report_|graph_radar|siem_event|siem_current_query|siem_current_query_graph|deletetask).*/",$k))
		unset($session_data[$k]);
} 
$_SESSION['views'][$_SESSION['current_cview']]['data'] = $session_data;
$config->set($login, 'custom_views', $_SESSION['views'], 'php', 'siem');
?>
