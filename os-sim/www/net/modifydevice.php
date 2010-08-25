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
require_once 'classes/Device.inc';
require_once 'ossim_db.inc';
if (!Session::am_i_admin()) die(_("You don't have permissions for Asset Discovery"));

$db = new ossim_db();
$conn = $db->connect();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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
if (!(GET('withoutmenu')==1 || POST('withoutmenu')==1)) include ("../hmenu.php"); 

$ip = "";
$community = "";
$descr = "";

$ip = ((GET('ip')!="")? GET('ip'): POST('ip'));
ossim_valid($ip, OSS_IP_ADDR, OSS_NULLABLE, 'illegal:' . _("ip"));
if (ossim_error()) {
    die(ossim_error());
}
if(GET('ip')!=""){
    $devices = Device::get_list($conn, "where ip='".ip2long(GET('ip'))."'");
    foreach($devices as $device){
        $community = $device->get_community();
        $descr = $device->get_descr();
    }
}
else {
    $community = POST('community');
    ossim_valid($community, OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_PUNC,OSS_NULLABLE, 'illegal:' . _("name"));
    $descr = POST('descr');
    ossim_valid($descr, OSS_ALPHA, OSS_NULLABLE, OSS_SPACE, OSS_PUNC, OSS_AT, 'illegal:' . _("Description"));
    if (ossim_error()) {
        die(ossim_error());
    }
}

if ($community!="" && GET('ip')=="") {
    Device::update($conn, $ip, $community, $descr);
    echo "<p>"._("Device succesfully updated")."</p>";
    ?><script>document.location.href="nedi.php"</script><?
}
?>

<form method="post" action="modifydevice.php">
<input type="hidden" name="ip" value="<?=$ip?>"/>
<table align="center">
  <tr>
    <th> <?php
    echo gettext("Ip"); ?> </th>
    <td style="text-align:left;padding-left:3px;" class="nobborder"><?=$ip?></td>
  </tr>
  <tr>
    <th> <?php
    echo gettext("Community"); ?> </th>
    <td style="text-align:left;padding-left:3px;" class="nobborder"><input type="text" name="community" value="<?=$community?>" size="32"/></td>
  </tr>
  <tr>
    <th> <?php
    echo gettext("Description"); ?> </th>
    <td style="text-align:left;padding-left:3px;" class="nobborder">
      <textarea name="descr" rows="2" style="width:212px"><?=$descr?></textarea>
    </td>
  </tr>
  <tr>
    <td colspan="2" style="text-align:center;" class="nobborder">
      <input type="submit" value="<?=_("OK")?>" class="btn" style="font-size:12px">
      <input type="reset" value="<?=_("reset")?>" class="btn" style="font-size:12px">
    </td>
  </tr>
</table>
</form>

</body>
</html>
<?
$db->close($conn);
?>
