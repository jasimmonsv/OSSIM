<?php
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
Session::logcheck("MenuIntelligence", "ComplianceMapping");
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
echo gettext("Update compliance category"); ?> </h1>

<?php
require_once ('classes/Security.inc');
$insert = POST('insert');
$sid = POST('sid');
//$descr = POST('descr');
$targeted = POST('targeted');
$untargeted = POST('untargeted');
$approach = POST('approach');
$exploration = POST('exploration');
$penetration = POST('penetration');
$generalmalware = POST('generalmalware');
$imp_qos = POST('imp_qos');
$imp_infleak = POST('imp_infleak');
$imp_lawful = POST('imp_lawful');
$imp_image = POST('imp_image');
$imp_financial = POST('imp_financial');
$D = POST('D');
$I = POST('I');
$C = POST('C');
$net_anomaly = POST('net_anomaly');

ossim_valid($sid, OSS_DIGIT, 'illegal:' . _("sid"));
ossim_valid($insert, OSS_NULLABLE, OSS_ALPHA, 'illegal:' . _("insert"));
//ossim_valid($descr, OSS_NULLABLE, OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_PUNC, OSS_AT, 'illegal:' . _("descr"));
ossim_valid($targeted, OSS_DIGIT, 'illegal:' . _("targeted"));
ossim_valid($untargeted, OSS_DIGIT, 'illegal:' . _("untargeted"));
ossim_valid($approach, OSS_DIGIT, 'illegal:' . _("approach"));
ossim_valid($exploration, OSS_DIGIT, 'illegal:' . _("exploration"));
ossim_valid($penetration, OSS_DIGIT, 'illegal:' . _("penetration"));
ossim_valid($generalmalware, OSS_DIGIT, 'illegal:' . _("generalmalware"));
ossim_valid($imp_qos, OSS_DIGIT, 'illegal:' . _("imp_qos"));
ossim_valid($imp_infleak, OSS_DIGIT, 'illegal:' . _("imp_infleak"));
ossim_valid($imp_lawful, OSS_DIGIT, 'illegal:' . _("imp_lawful"));
ossim_valid($imp_image, OSS_DIGIT, 'illegal:' . _("imp_image"));
ossim_valid($imp_financial, OSS_DIGIT, 'illegal:' . _("imp_financial"));
ossim_valid($D, OSS_DIGIT, 'illegal:' . _("D"));
ossim_valid($I, OSS_DIGIT, 'illegal:' . _("I"));
ossim_valid($C, OSS_DIGIT, 'illegal:' . _("C"));
ossim_valid($net_anomaly, OSS_DIGIT, 'illegal:' . _("net_anomaly"));

if (ossim_error()) {
    die(ossim_error());
}
if (!empty($insert)) {
    require_once 'ossim_db.inc';
    require_once 'classes/Compliance.inc';
    $db = new ossim_db();
    $conn = $db->connect();
    $descr = "";
	Compliance::update($conn, $descr, $sid, $targeted, $untargeted, $approach, $exploration, $penetration, $generalmalware, $imp_qos, $imp_infleak, $imp_lawful, $imp_image, $imp_financial, $D, $I, $C, $net_anomaly);
    $db->close($conn);
}
?>
    <p><?php echo _("Category succesfully updated") ?></p>
    <script>document.location.href="general.php"</script>
<?php
// update indicators on top frame
$OssimWebIndicator->update_display();
?>

</body>
</html>

