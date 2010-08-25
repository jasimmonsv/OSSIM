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
Session::logcheck("MenuPolicy", "PolicyPorts");
require_once 'classes/Port_group.inc';
require_once 'classes/Port.inc';
require_once 'classes/Port_group_reference.inc';
require_once 'ossim_db.inc';
require_once 'classes/Security.inc';

$name = "";
$descr = "";
$list_ports = "";

if (GET('portname')!="") $name = GET('portname');
if (POST('name')!="") $name = POST('name');
$descr = POST('descr');
$list_ports = POST('ports');
ossim_valid($list_ports, OSS_ALPHA, OSS_SPACE, "#", OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("list_ports"));
ossim_valid($name, OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("Port group name"));
ossim_valid($descr, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_AT, OSS_NULLABLE, 'illegal:' . _("Description"));
if (ossim_error()) {
    die(ossim_error());
}
$db = new ossim_db();
$conn = $db->connect();

if ($port_group_list = Port_group::get_list($conn, "WHERE name = '$name'")) {
        $port_group = $port_group_list[0];
}

$ports = array();
$actives = array();
$arr_ports_input=array();
$ports_input="";
if ($port_list = Port::get_list($conn)) {
    foreach($port_list as $port) {
        $ports[$port->get_protocol_name() ][] = $port->get_port_number();
        $actives[$port->get_protocol_name() ][$port->get_port_number() ] = Port_group_reference::in_port_group_reference($conn, $port_group->get_name() , $port->get_port_number() , $port->get_protocol_name());
        $arr_ports_input[] = '{ txt:"'.$port->get_port_number().'-'.$port->get_protocol_name().'", id: "'.$port->get_port_number().'-'.$port->get_protocol_name().'" }';
        }
}
$ports_input = implode(",", $arr_ports_input);
$db->close($conn);
?>
<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.1.js"></script>
  <script type="text/javascript" src="../js/combos.js"></script>
  <script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>
  <link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css">
  <script language="javascript">
  $(document).ready(function() {
    var ports = [
                <?= $ports_input ?>
            ];
            $("#ports").autocomplete(ports, {
                minChars: 0,
                width: 225,
                matchContains: "word",
                autoFill: true,
                formatItem: function(row, i, max) {
                    return row.txt;
                }
            }).result(function(event, item) {
                $("#ports").val(item.id);
                addto('selected_ports',item.id,item.id);
            });
            })
  </script>
</head>
<body>
                                                                                
<?php
if (GET('withoutmenu') != "1") include ("../hmenu.php");
 ?>
<form method="post" action="modifyport.php" onsubmit="selectall('selected_ports');">
<table align="center">
  <input type="hidden" name="insert" value="insert">
  <tr>
    <th> <?php
echo gettext("Name"); ?> </th>
      <input type="hidden" name="name"
             value="<?php
        if($name!="")
            echo $name;
        else
            echo $port_group->get_name();
        ?>">
    <td style="text-align:left" class="nobborder">
      <b><?php
        if($name!="")
            echo $name;
        else
            echo $port_group->get_name();
    ?></b>
    </td>
  </tr>
  <tr>
    <th> <?php
echo gettext("Ports"); ?> </th>
    <td style="text-align:left" class="nobborder">
        <input type="text" id="ports" value="" size="32"><br>
        <select id="selected_ports" name="protocols[]" size="18" multiple="multiple" style="width:212px;margin-top:5px;">
        <?php
            if ($list_ports!="") {
                $arr_ports = explode ("#",$list_ports);
                foreach ($arr_ports as $p) {
                    echo "<option value=\"$p\">$p";
                }
            }
            else {
                foreach($ports as $protocol => $list) { 
                    foreach($list as $port) {
                        if($actives[$protocol][$port]!=""){
                            echo "<option value='$port-$protocol' $sel>$port-$protocol";
                        }
                    }
                }
            }
        ?>
        </select>
        <input type="button" value=" [X] " onclick="deletefrom('selected_ports');" class="btn">
    </td>
  </tr>
  <tr>
    <th> <?php
echo gettext("Description"); ?> :&nbsp;</th>
    <td style="text-align:left" class="nobborder">
      <textarea name="descr" rows="2" style="width:212px"><?php
        if ($descr!="")
            echo $descr;
        else 
            echo $port_group->get_descr();
        ?>
      </textarea>
    </td>
  </tr>
  <tr>
    <td colspan="2"  style="text-align:center" class="nobborder">
      <input type="submit" value="<?=_("OK")?>" class="btn" style="font-size:12px">
      <input type="reset" value="<?=_("reset")?>" class="btn" style="font-size:12px">
    </td>
  </tr>
</table>
</form>

</body>
</html>

