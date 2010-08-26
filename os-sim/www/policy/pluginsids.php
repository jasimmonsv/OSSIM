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
require_once 'classes/Plugin_sid.inc';
Session::logcheck("MenuConfiguration", "PluginGroups");

$id = (GET('id')!="")? GET('id'):POST('id');
$sids = POST('sids');

ossim_valid($id, OSS_DIGIT, 'illegal:' . _("ID"));
ossim_valid($sids, OSS_NULLABLE, OSS_DIGIT, ",", "ANY", OSS_SPACE);
if (ossim_error()) {
    die(ossim_error());
}
$db = new ossim_db();
$conn = $db->connect();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title> <?php echo gettext("Plugin SIDs"); ?> </title>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
    <link rel="stylesheet" type="text/css" href="../style/style.css"/>
    <link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css" />
    <link type="text/css" rel="stylesheet" href="../style/ui.multiselect.css" rel="stylesheet" />
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
    <script type="text/javascript" src="../js/jquery.tmpl.1.1.1.js"></script>
    <script type="text/javascript" src="../js/ui.multiselect.js"></script>
    <script type="text/javascript" src="../js/combos.js"></script>    
    <script type="text/javascript" src="../js/split.js"></script>    
    <script>
        var customDataParser = function(data) {
            if ( typeof data == 'string' ) {
                var pattern = /^(\s\n\r\t)*\+?$/;
                var selected, line, lines = data.split(/\n/);
                data = {};
                $('#msg').html('');
                for (var i in lines) {
                    line = lines[i].split("=");
                    if (!pattern.test(line[0])) {
                        if (i==0 && line[0]=='Total') {
                            $('#msg').html("<?=_("Total plugin sids found:")?> <b>"+line[1]+"</b>");
                        } else {
                            // make sure the key is not empty
                            selected = (line[0].lastIndexOf('+') == line.length - 1);
                            if (selected) line[0] = line.substr(0,line.length-1);
                            // if no value is specified, default to the key value
                            data[line[0]] = {
                                selected: false,
                                value: line[1] || line[0]
                            };
                        }
                    }
                }
            } else {
                this._messages($.ui.multiselect.constante.MESSAGE_ERROR, $.ui.multiselect.locale.errorDataFormat);
                data = false;
            }
            return data;
        };
        $(document).ready(function(){
            if ('<?=POST("id")?>'=='') {
                $('#sids').val(parent.getfield());
                $('#formpluginsids').submit();
            } else {
                $(".multiselect").multiselect({
                    searchDelay: 700,
                    dividerLocation: 0.5,
                    remoteUrl: 'get_plugin_sids.php',
                    remoteParams: { plugin_id: '<?=$id?>' },
                    nodeComparator: function (node1,node2){ return 1 },
                    dataParser: customDataParser,
                });
            }
        });
        function makesel() { 
            sids = getselectedcombovalue('pluginsids');
            if (sids=='') sids='ANY'
            parent.changefield(sids);
            parent.GB_hide();
        }
    </script>
</head>
<body>
    </script>
    <form id="formpluginsids" action="pluginsids.php" method="POST" style="dislay:none">
    <input type="hidden" name="id" value="<?=$id?>">
    <input type="hidden" name="sids" id="sids" value="">
    </form>
    <form>
    <select id="pluginsids" class="multiselect" multiple="multiple" name="sids[]">
    <?
    if ($sids!="ANY" && $sids!="") {
        $plugin_list = Plugin_sid::get_list($conn, "WHERE plugin_id=$id AND sid in ($sids)");
        foreach($plugin_list as $plugin) {
            $id = $plugin->get_sid();
            $name = "$id - ".trim($plugin->get_name());
            if (strlen($name)>73) $name=substr($name,0,70)."...";
            echo "<option value='$id' selected>$name</option>\n";
        }
    }
    ?>
    </select><br><span id="msg"></span><br><br>
    <input type="button" onclick="makesel()" value="Submit selection">
    </form>
</body>
</html>
<?
$db->close($conn);
?>
