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
Session::logcheck("MenuConfiguration", "ConfigurationPlugins");
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

<?php
include ("../hmenu.php");
require_once ('classes/Security.inc');
require_once ('classes/Plugin_sid.inc');
?>

<?php
require_once ('ossim_db.inc');
require_once ('ossim_conf.inc');


$plugin = GET('plugin');
$name = GET('name');
$sid = GET('sid');
$reliability = GET('reliability');
$priority = GET('priority');

ossim_valid($plugin, OSS_DIGIT, 'illegal:' . _("plugin"));
ossim_valid($name, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_SPACE, OSS_PUNC, 'illegal:' . _("name"));
ossim_valid($sid, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("sid"));
ossim_valid($reliability, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("reliability"));
ossim_valid($priority, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("priority"));

if (ossim_error()) {
   die(ossim_error());
}

$db = new ossim_db();
$conn = $db->connect();

if($name!="" && $sid!=""){
    if (in_array($sid, Plugin_sid::get_sids_by_id($conn, $plugin))){
        pluginsid_inputs_error("Sid $sid already exists");
    }
    else {
        Plugin_sid::insert($conn, $plugin, $name, $sid, $reliability, $priority);?>
        <p><?php echo _("Plugin succesfully updated") ?></p>
        <script type="text/javascript">
        //<![CDATA[
            document.location.href='plugin.php';
        //]]>
        </script>
    <?
    
    }
}

?>
    
<form method="get" action="newpluginsidform.php">
    <input type="hidden" name="plugin" value="<?php echo GET('plugin')?>"/>
    <table align="center">
  <tr>
    <th> <?php echo gettext("Name"); ?> (*)</th>
    <td class="left"><textarea name="name" rows="2" cols="40"><?php echo GET('name')?></textarea>
</td>
  </tr>
  <tr>
    <th> <?php echo gettext("sid"); ?> (*)</th>
    <td class="left"><input type="text" name="sid" value="<?php echo GET('sid')?>"/></td>
  </tr>
  <tr>
    <th> <?php echo gettext("Reliability"); ?></th>
    <td class="left">
        <select name="reliability">
            <option value="0" <?=((intval($reliability)==0)? " selected":"")?>>0</option>
            <option value="1" <?=((intval($reliability)==1)? " selected":"")?>>1</option>
            <option value="2" <?=((intval($reliability)==2)? " selected":"")?>>2</option>
            <option value="3" <?=((intval($reliability)==3)? " selected":"")?>>3</option>
            <option value="4" <?=((intval($reliability)==4)? " selected":"")?>>4</option>
            <option value="5" <?=((intval($reliability)==5)? " selected":"")?>>5</option>
            <option value="6" <?=((intval($reliability)==6)? " selected":"")?>>6</option>
            <option value="7" <?=((intval($reliability)==7)? " selected":"")?>>7</option>
            <option value="8" <?=((intval($reliability)==8)? " selected":"")?>>8</option>
            <option value="9" <?=((intval($reliability)==9)? " selected":"")?>>9</option>
            <option value="10" <?=((intval($reliability)==10)? " selected":"")?>>10</option>
        </select>
    </td>
  </tr>
  <tr>
    <th><?php  echo gettext("Priority"); ?></th>
    <td class="left">
        <select name="priority">
            <option value="0" <?=((intval($priority)==0)? " selected":"")?>>0</option>
            <option value="1" <?=((intval($priority)==1)? " selected":"")?>>1</option>
            <option value="2" <?=((intval($priority)==2)? " selected":"")?>>2</option>
            <option value="3" <?=((intval($priority)==3)? " selected":"")?>>3</option>
            <option value="4" <?=((intval($priority)==4)? " selected":"")?>>4</option>
            <option value="5 <?=((intval($priority)==5)? " selected":"")?>">5</option>
        </select>
    </td>
  </tr>
  <tr>
    <td colspan="2" align="center">
      <input type="submit" value="<?=_("OK")?>" class="button" style="font-size:12px">
      <input type="reset" value="<?=_("reset")?>" class="button" style="font-size:12px">
    </td>
  </tr>
</table>
</form>

<p align="center"><i><?php
echo gettext("Values marked with (*) are mandatory"); ?></b></i></p>

</body>
</html>

<?php
$db->close($conn);
function pluginsid_inputs_error($message) {
    echo "<p style =\"border: 3px dotted rgb(255, 191, 0); margin-left: 50px; margin-right: 50px; padding: 5px; text-align: center; background-color: rgb(255, 242, 131);\">";
    echo "<b>Invalid: $message</b>";
    echo "</p>";
}


?>
