<?
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
require_once 'classes/Server.inc';
require_once 'server_get_servers.php';
require_once 'classes/Plugin.inc';
require_once ('ossim_db.inc');

$key = GET('key');
ossim_valid($key, OSS_NULLABLE, OSS_TEXT, OSS_PUNC, 'illegal:' . _("key"));

$page = intval(GET('page')); 
ossim_valid($page, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("page")); 

if (ossim_error()) {
    die(ossim_error());
}

if ($page == "" || $page<=0) $page = 1;
$maxresults = 200;
$to = $page * $maxresults;
$from = $to - $maxresults;
$nextpage = $page + 1;

/* connect to db */
$db = new ossim_db();
$conn = $db->connect();

list($server_list, $err) = server_get_servers($conn);
$server_list_aux = $server_list; //here are stored the connected servers
$server_stack = array(); //here will be stored the servers wich are in DDBB
$server_configured_stack = array();
if ($server_list) {
    foreach($server_list as $server_status) {
        if (in_array($server_status["servername"], $server_stack)) continue;
        array_push($server_stack, $server_status["servername"]);
    }
}
$active_servers = 0;
$total_servers = 0;
$xml = "";
$server_list = Server::get_list($conn, "ORDER BY name ASC");
if ($server_list[0]) {
    $total = $server_list[0]->get_foundrows();
    if ($total == 0) $total = count($server_list);
} else $total = 0;

echo "[";
$flag = 0;
foreach($server_list as $server) {
    $total_servers++;
    $name = $server->get_name();
    $ip = $server->get_ip();
    if ($flag) { echo ", "; }
    echo "{ key:'server_$name', url:'', icon:'../../pixmaps/theme/host.png', title:'$name' }";
    $flag = 1;
}
echo "]";
?>
