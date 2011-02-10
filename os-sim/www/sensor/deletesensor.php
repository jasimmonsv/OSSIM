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
Session::logcheck("MenuConfiguration", "PolicySensors");
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
<?php include ("../hmenu.php"); ?>
  <h1> <?php
echo gettext("Delete sensor"); ?> </h1>

<?php
require_once 'classes/Security.inc';
$name = GET('name');
ossim_valid($name, OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_SCORE, 'illegal:' . _("Sensor name"));
if (ossim_error()) {
    die(ossim_error());
}
if (!GET('confirm')) {
?>
    <p> <?php
    echo gettext("Are you sure?"); ?> </p>
    <p><a 
      href="<?php
    echo $_SERVER["SCRIPT_NAME"] . "?name=$name&confirm=yes"; ?>">
      <?php
    echo gettext("Yes"); ?> </a>
      &nbsp;&nbsp;&nbsp;<a href="sensor.php"> 
      <?php
    echo gettext("No"); ?> </a>
    </p>
<?php
    exit();
}
require_once 'ossim_db.inc';
require_once 'classes/Sensor.inc';
require_once 'classes/Sensor_interfaces.inc';
$db = new ossim_db();
$conn = $db->connect();
//
require_once 'classes/Policy_sensor_reference.inc';
$list_policy_sensor_reference=Policy_sensor_reference::get_policy_by_sensor($conn,$name);

if(count($list_policy_sensor_reference)!=0){
    // this sensor have a policy
    if (GET('policyConfirm')!='yes') {
        ?>
            <p> <strong><?php
            echo gettext("This sensor have a Policy"); ?></strong>, <?php
            echo gettext("Are you sure?"); ?> </p>
            <p><a
              href="<?php
            echo $_SERVER["SCRIPT_NAME"] . "?name=$name&confirm=yes&policyConfirm=yes"; ?>" class="buttonlink">
              <?php
            echo gettext("Yes"); ?></a>
              &nbsp;&nbsp;&nbsp;<a href="sensor.php" class="buttonlink">
              <?php
            echo gettext("No"); ?></a>
            </p>
            <?php
            exit();
    }else{
        // delete sensor and deactivate policy
        require_once 'classes/Policy.inc';
        foreach($list_policy_sensor_reference as $policy_sensor_reference){
            Policy::activate($conn, $policy_sensor_reference->get_policy_id(),'0');
        }
    }

}
//
if ($sensor_interface_list = Sensor_interfaces::get_list($conn, $name)) {
    foreach($sensor_interface_list as $s_int) {
        Sensor_interfaces::delete_interfaces($conn, $name, $s_int->get_interface());
    }
}
Sensor::delete($conn, $name);
$db->close($conn);
?>

    <p> <?php
echo gettext("Sensor deleted"); ?> </p>
    <script>document.location.href="sensor.php"</script>
<?php
// update indicators on top frame*/
$OssimWebIndicator->update_display();
?>

</body>
</html>

