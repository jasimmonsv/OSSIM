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
require_once ('classes/Session.inc');
Session::logcheck("MenuPolicy", "PolicyServers");
?>

<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>
                                                                                
  <h1> <?php
echo gettext("New database server"); ?> </h1>

<?php
require_once 'classes/Security.inc';
$name = POST('name');
$ip = POST('ip');
$port = POST('port');
$user = POST('user');
$pass = POST('pass');
ossim_valid($name, OSS_ALPHA, OSS_PUNC, OSS_SPACE, 'illegal:' . _("Database server name"));
ossim_valid($ip, OSS_IP_ADDR, 'illegal:' . _("Ip address"));
ossim_valid($port, OSS_DIGIT, 'illegal:' . _("Port number"));
ossim_valid($user, OSS_ALPHA, OSS_PUNC, OSS_SPACE, 'illegal:' . _("User"));
ossim_valid($pass, OSS_ALPHA, OSS_PUNC, OSS_SPACE, 'illegal:' . _("Password"));

if (ossim_error()) {
    die(ossim_error());
}
if (POST('insert')) {
    require_once 'ossim_db.inc';
    require_once 'classes/Databases.inc';
    $db = new ossim_db();
    $conn = $db->connect();
    $icon = "";
    if (is_uploaded_file($HTTP_POST_FILES['icon']['tmp_name'])) {
       $icon = file_get_contents($HTTP_POST_FILES['icon']['tmp_name']);
    }
    Databases::insert($conn, $name, $ip, $port, $user, $pass, $icon);
    $db->close($conn);
}
?>
    <p> <?php
echo gettext("Database server succesfully inserted"); ?> </p>
    <? if ($_SESSION["menu_sopc"]=="DBs") { ?><script>document.location.href="dbs.php"</script><? } ?>

</body>
</html>
