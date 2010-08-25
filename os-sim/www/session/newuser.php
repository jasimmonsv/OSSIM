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
Session::logcheck("MenuConfiguration", "ConfigurationUsers");
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
echo gettext("New User"); ?> </h1>

<?php
require_once ('classes/Security.inc');
require_once ('classes/User_config.inc');
$user = POST('user');
$pass1 = POST('pass1');
$pass2 = POST('pass2');
$name = POST('name');
$email = POST('email');
$nnets = POST('nnets');
$nsensors = POST('nsensors');
$company = POST('company');
$department = POST('department');
$language = POST('language');
$first_login = POST('first_login');
//$copy_panels = POST('copy_panels');
//ossim_valid($copy_panels, OSS_DIGIT, 'illegal:' . _("Copy Panels"));
ossim_valid($user, OSS_USER, 'illegal:' . _("User name"));
ossim_valid($name, OSS_ALPHA, OSS_PUNC, OSS_AT, OSS_SPACE, 'illegal:' . _("Name"));
ossim_valid($email, OSS_MAIL_ADDR, OSS_NULLABLE, 'illegal:' . _("e-mail"));
ossim_valid($nnets, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("nnets"));
ossim_valid($nsensors, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("nsensors"));
ossim_valid($company, OSS_ALPHA, OSS_PUNC, OSS_AT, OSS_NULLABLE, 'illegal:' . _("Company"));
ossim_valid($department, OSS_ALPHA, OSS_PUNC, OSS_AT, OSS_NULLABLE, 'illegal:' . _("Department"));
ossim_valid($language, OSS_ALPHA, OSS_PUNC, OSS_AT, OSS_NULLABLE, 'illegal:' . _("Language"));
ossim_valid($first_login, OSS_DIGIT, 'illegal:' . _("First Login"));
if (ossim_error()) {
    die(ossim_error());
}
if (!Session::am_i_admin()) {
    require_once ("ossim_error.inc");
    $error = new OssimError();
    $error->display("ONLY_ADMIN");
}
/* check passwords */
elseif (0 != strcmp($pass1, $pass2)) {
    require_once ("ossim_error.inc");
    $error = new OssimError();
    $error->display("PASSWORDS_MISMATCH");
}
/* check OK, insert into DB */
elseif (POST("insert")) {
    require_once ('ossim_db.inc');
    require_once ('ossim_acl.inc');
    require_once ('classes/Session.inc');
    require_once ('classes/Net.inc');
    $perms = Array();
    foreach($ACL_MAIN_MENU as $menus) {
        foreach($menus as $key => $menu) {
            if (POST($key) == "on") $perms[$key] = true;
            else $perms[$key] = false;
        }
    }
    $db = new ossim_db();
    $conn = $db->connect();
	
    
    User_config::copy_panel($conn, "admin", $user);
    
	
    $nets = "";
    for ($i = 0; $i < $nnets; $i++) {
        $net_name = POST("net$i");
        ossim_valid($net_name, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("net$i"));
        if (ossim_error()) {
            die(ossim_error());
        }
        if ($net_list = Net::get_list($conn, "WHERE name = '$net_name'")) {
            foreach($net_list as $net) {
                if ($nets == "") $nets = $net->get_ips();
                else $nets.= "," . $net->get_ips();
            }
        }
    }
    $sensors = "";
    for ($i = 0; $i < $nsensors; $i++) {
        ossim_valid(POST("sensor$i") , OSS_LETTER, OSS_DIGIT, OSS_DOT, OSS_NULLABLE, 'illegal:' . _("sensor$i"));
        if (ossim_error()) {
            die(ossim_error());
        }
        if ($sensors == "") $sensors = POST("sensor$i");
        else $sensors.= "," . POST("sensor$i");
    }
    Session::insert($conn, $user, $pass1, $name, $email, $perms, $nets, $sensors, $company, $department, $language, $first_login);
    $db->close($conn);
?>
    <p> <?php
    echo gettext("User succesfully inserted"); ?> </p>
<?php
    $location = "users.php";
    sleep(2);
    echo "<script>
///history.go(-1);
window.location='$location';
</script>
";
?>

<?php
}
?>


</body>
</html>

