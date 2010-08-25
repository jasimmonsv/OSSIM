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
Session::logcheck("MenuConfiguration", "ConfigurationRRDConfig");
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
echo gettext("New RRD Profile"); ?> </h1>

<?php
require_once 'classes/Security.inc';
$profile = REQUEST('profile');
ossim_valid($profile, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("Profile"));
if (ossim_error()) {
    die(ossim_error());
}
require_once ('classes/RRD_config.inc');
require_once ('ossim_db.inc');
$db = new ossim_db();
$conn = $db->connect();
if ($rrd_list = RRD_Config::get_list($conn, "WHERE profile = 'Default'")) {
    foreach($rrd_list as $rrd) {
        $attrib = $rrd->get_rrd_attrib();
        if (POST("$attrib#rrd_attrib")) {
            if (POST("$attrib#enable") == "on") $enable = 1;
            else $enable = 0;
            ossim_valid(POST("$attrib#rrd_attrib") , OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("$attrib#rrd_attrib"));
            ossim_valid(POST("$attrib#threshold") , OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("$attrib#threshold"));
            ossim_valid(POST("$attrib#priority") , OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("$attrib#priority"));
            ossim_valid(POST("$attrib#alpha") , OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("$attrib#alpha"));
            ossim_valid(POST("$attrib#beta") , OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("$attrib#beta"));
            ossim_valid(POST("$attrib#persistence") , OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("$attrib#persistence"));
            if (ossim_error()) {
                die(ossim_error());
            }
            RRD_Config::insert($conn, $profile, POST("$attrib#rrd_attrib") , POST("$attrib#threshold") , POST("$attrib#priority") , POST("$attrib#alpha") , POST("$attrib#beta") , POST("$attrib#persistence") , $enable);
        }
    }
}
$db->close($conn);
?>
    <p> <?php
echo gettext("RRD Config succesfully inserted"); ?> </p>
<?php
$location = "rrd_conf.php";
sleep(2);
echo "<script>
///history.go(-1);
window.location='$location';
</script>
";
?>

?>
</body>
</html>

