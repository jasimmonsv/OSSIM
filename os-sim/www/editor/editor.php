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
Session::logcheck("MenuTools", "ToolsRuleViewer");
?>

<html>
<head>
  <title>OSSIM Framework</title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>
  
  <h1><?php
echo gettext("Rule viewer"); ?></h1>

<?php
require_once ('ossim_conf.inc');
require_once ('dir.php');
$ossim_conf = $GLOBALS["CONF"];
$snort_rules_path = $ossim_conf->get_conf("snort_rules_path");
?>

  <table align="center">
<?php
$files = getDirFiles($snort_rules_path);
/* local snort rule directory */
if ($files == NULL) {
    require_once ("ossim_error.inc");
    $error = new OssimError();
    $error->display("RULES_NOT_FOUND", array(
        $snort_rules_path
    ));
}
foreach($files as $file) {
    /* only show .rules files */
    $f = split("\.", $file);
    if ($f[1] == 'rules') {
?>
    <tr><td>
    <a href="rule.php?name=<?php
        echo $file; ?>"><?php
        echo $f[0]; ?></a>
    </td></tr>
<?php
    }
}
?>
  </table>
</body>
</html>
