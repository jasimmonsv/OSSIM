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
* - getOptions()
* - printFirstOption()
* - parseOption()
* - isSetOption()
* Classes list:
*/
require_once ('classes/Session.inc');
Session::logcheck("MenuTools", "ToolsRuleViewer");
?>

<?php
require_once ('classes/Security.inc');
$rule_name = GET('name');
ossim_valid($rule_name, OSS_ALPHA, OSS_SCORE, OSS_DOT, 'illegal:' . _("name"));
if (ossim_error()) {
    die(ossim_error());
}
function getOptions($option, $line) {
    $pattern = "/$option:\s*([^;]+);/";
    if (preg_match_all($pattern, $line, $regs)) {
        return $regs[0];
    }
}
function printFirstOption($option, $line) {
    $pattern = "/$option:\s*([^;]+);/";
    if (preg_match($pattern, $line, $regs)) {
        return $regs[1];
    }
}
function parseOption($option) {
    $pattern = "/:\s*([^;]+);/";
    if (preg_match($pattern, $option, $regs)) {
        return $regs[1];
    }
}
function isSetOption($option, $line) {
    $pattern = "/$option;/";
    return preg_match($pattern, $line, $regs);
}
?>

<html>
<head>
  <title> <?php
echo gettext("Rule editor"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>

  <h1> <?php
echo gettext("OSSIM Framework"); ?> </h1>

  <h2> <?php
echo gettext("Rule editor"); ?> </h2>

<?php
require_once ('ossim_conf.inc');
require_once ('dir.php');
require_once ('options.php');
$ossim_conf = $GLOBALS["CONF"];
$snort_rules_path = $ossim_conf->get_conf("snort_rules_path");
?>

<h2><u><?php
echo $rule_name; ?></u></h2>

<table align="center" width="100%">
  <tr>
    <th><font size="-2">
    <?php
echo gettext("Name"); ?> </font></th>
    <th><font size="-2">
    <?php
echo gettext("Action"); ?> </font></th>
    <th><font size="-2">
    <?php
echo gettext("Protocol"); ?> </font></th>
    <th><font size="-2">
    <?php
echo gettext("SRC IP"); ?> </font></th>
    <th><font size="-2">
    <?php
echo gettext("SRC Ports"); ?> </font></th>
    <th><font size="-2">
    <?php
echo gettext("Dir"); ?> </font></th>
    <th><font size="-2">
    <?php
echo gettext("DEST IP"); ?> </font></th>
    <th><font size="-2">
    <?php
echo gettext("DEST Ports"); ?> </font></th>
    <th><font size="-2">
    <?php
echo gettext("Content"); ?> </font></th>
    <th><font size="-2">
    <?php
echo gettext("Options"); ?> </font></th>
  </tr>

<?php
if (!file_exists("$snort_rules_path/$rule_name")) {
    echo "Uknown rule file\n";
    exit;
}
if (!$fd = fopen("$snort_rules_path/$rule_name", "r")) {
    echo "Error opening file\n";
    exit;
}
$nline = 0;
while (!feof($fd)) {
    $line = fgets($fd, 4096);
    $nline++;
    /*
    Rule action (alert, log, pass, activate, dynamic)
    Protocol (tcp, udp, icmp, ip)
    SRC IP Address (any, xxx.xxx.xxx.xxx/mask, $VARIABLE)
    SRC Ports (any, single_port, port:port, !negated_port, $VARIABLE)
    Direction (->, <>)
    DEST IP Address (any, xxx.xxx.xxx.xxx/mask, $VARIABLE)
    DEST Ports (any, single_port, port:port, !negated_port. $VARIABLE)
    
    */
    if (preg_match("/^(alert|log|pass|activate|dynamic)" . /* rule action     */
    "\s*(tcp|udp|icmp|ip)" . /* protocol        */
    "\s*([^\s]+)\s?([^\s]+)" . /* SRC IP & Ports  */
    "\s*(->|<>)" . /* direction       */
    "\s*([^\s]+)\s?([^\s]+)/", /* SRC IP & Ports  */
    $line, $regs)) {
?>
    <tr>
      <td align="center"><?php
        echo printFirstOption("msg", $line); ?></td>
      <td align="center"><?php
        echo $regs[1] ?></td>
      <td align="center"><?php
        echo $regs[2] ?></td>
      <td align="center"><?php
        echo $regs[3] ?></td>
      <td align="center"><?php
        echo $regs[4] ?></td>
      <td align="center"><?php
        echo $regs[5] ?></td>
      <td align="center"><?php
        echo $regs[6] ?></td>
      <td align="center"><?php
        echo $regs[7] ?></td>
      <td align="center">
        <?php
        $options = getOptions("content", $line);
        $count = count($options);
        for ($i = 0; $i < $count; $i++) {
            if ($i > 0) echo "<br>";
            echo parseOption($options[$i]);
        }
?>
      </td>
      <td align="left">
        <table width="100%">



<?php
        /* opciones con argumentos */
        foreach($ruleOptions as $roption) {
            if ($options = getOptions($roption, $line)) {
                $count = count($options);
                for ($i = 0; $i < $count; $i++) {
?>
          <tr><td align="left">
<?php
                    echo "<b>$roption</b>: " . parseOption($options[$i] . "\n");
                }
?>
          </td></tr>
<?php
            }
        }
        /* opciones sin argumentos */
        foreach($ruleSingleOptions as $soption) {
            if (isSetOption($soption, $line)) {
?>          <tr><td align="left">
<?php
                echo "<b>$soption</b>\n";
?>          </td></tr>
<?php
            }
        }
?>
        </table>
      </td>
    </tr>
<?php
    }
}
fclose($fd);
?>

</table>

</body>
</html>

