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
include ('ossim_conf.inc');
include ('ossim_acl.inc');
require_once ('classes/Session.inc');
Session::logcheck("MenuConfiguration", "ConfigurationUsers");
$conf = $GLOBALS["CONF"];
$phpgacl = $conf->get_conf("phpgacl_path");
require_once ("$phpgacl/gacl.class.php");
require_once ("$phpgacl/gacl_api.class.php");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>

<?

include ("../hmenu.php");
$gacl_api = new gacl_api($ACL_OPTIONS);
if (isset($_SERVER['HTTP_REFERER'])) { ?>
<br>
<center>
    <form><input type="button" class="btn" onclick="document.location.href='<?=$_SERVER['HTTP_REFERER']?>'" value="<?=_("Back")?>">
    </form>
</center>
<?php
}
/* Domain access */
echo gettext("Setting up domain access") . "...<br/>";
$gacl_api->add_object_section(ACL_DEFAULT_DOMAIN_SECTION, ACL_DEFAULT_DOMAIN_SECTION, 1, 0, 'ACO');
echo "  * " . gettext("Users") . "...<br/>";
$gacl_api->add_object(ACL_DEFAULT_DOMAIN_SECTION, ACL_DEFAULT_DOMAIN_ALL, ACL_DEFAULT_DOMAIN_ALL, 1, 0, 'ACO');
$gacl_api->add_object(ACL_DEFAULT_DOMAIN_SECTION, ACL_DEFAULT_DOMAIN_LOGIN, ACL_DEFAULT_DOMAIN_LOGIN, 2, 0, 'ACO');
echo "  * " . gettext("Networks") . "...<br/>";
$gacl_api->add_object(ACL_DEFAULT_DOMAIN_SECTION, ACL_DEFAULT_DOMAIN_NETS, ACL_DEFAULT_DOMAIN_NETS, 3, 0, 'ACO');
echo "  * " . gettext("Sensors") . "...<br/><br/>";
$gacl_api->add_object(ACL_DEFAULT_DOMAIN_SECTION, ACL_DEFAULT_DOMAIN_SENSORS, ACL_DEFAULT_DOMAIN_SENSORS, 4, 0, 'ACO');
/* Menu access */
$menu_count = 10;
$submenu_count = 1;
echo "Setting up Menu access...<br/>";
foreach($ACL_MAIN_MENU as $menu_name => $menu) {
    $gacl_api->add_object_section($menu_name, $menu_name, $menu_count++, 0, 'ACO');
    foreach($menu as $submenu_name => $submenu) {
        echo "  * " . $submenu["name"] . " ...<br/>";
        $gacl_api->add_object($menu_name, $submenu_name, $submenu_name, $submenu_count++, 0, "ACO");
    }
    $submenu_count = 1;
}
/* Groups */
echo "<br/>Setting up default admin user...<br/><br/>";
$groups['ossim'] = $gacl_api->add_group('ossim', 'OSSIM', 0, 'ARO');
$groups['users'] = $gacl_api->add_group(ACL_DEFAULT_USER_GROUP, 'Users', $groups['ossim'], 'ARO');
/* Default User */
$gacl_api->add_object_section('Users', ACL_DEFAULT_USER_SECTION, 1, 0, 'ARO');
$gacl_api->add_object(ACL_DEFAULT_USER_SECTION, 'Admin', ACL_DEFAULT_OSSIM_ADMIN, 1, 0, 'ARO');
$gacl_api->add_acl(array(
    ACL_DEFAULT_DOMAIN_SECTION => array(
        ACL_DEFAULT_DOMAIN_ALL
    )
) , array(
    ACL_DEFAULT_USER_SECTION => array(
        ACL_DEFAULT_OSSIM_ADMIN
    )
));
?>
<?php
// The upgrade system at include/classes/Upgrade_base.inc includes
// that file like: include 'http://foo/setup/ossim_acl.php'
// In this case, there is not HTTP_REFERER and btw we don't want to show
// this "go back" link.
if (isset($_SERVER['HTTP_REFERER'])) { ?>
<center>
    <form><input type="button" class="btn" onclick="document.location.href='<?=$_SERVER['HTTP_REFERER']?>'" value="<?=_("Back")?>">
    </form>
</center>
<?php
} ?>

</body>
</html>
