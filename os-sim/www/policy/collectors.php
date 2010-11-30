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
require_once 'classes/Security.inc';
require_once 'classes/Session.inc';
require_once 'classes/Collectors.inc';
require_once 'ossim_db.inc';

Session::logcheck("MenuConfiguration", "ConfigurationPlugins");
$db = new ossim_db();
$conn = $db->connect();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
</head>
<body style="height: auto; margin: 0 0 10px 0">

<?php
include ("../hmenu.php");

$name = POST('name');
$description = POST('description');
$type = POST('type');
$enable = POST('enable');
$create = POST('create');
$process = POST('process');
$plugin_id = POST('plugin_id');
$source_type = POST('source_type');
$source = POST('source');
$location = POST('location');
$start = POST('start');
$stop = POST('stop');
$startup_command = POST('startup_command');
$stop_command = POST('stop_command');
$sample_log = POST('sample_log');
$action = (POST('action')!="") ? POST('action') : GET('action');
$id = (POST('id')!="") ? POST('id') : GET('id');

ossim_valid($id, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("id"));
ossim_valid($action, OSS_LETTER, OSS_NULLABLE, 'illegal:' . _("action"));
ossim_valid($name, OSS_SCORE, OSS_NULLABLE, OSS_LETTER, OSS_DIGIT, 'illegal:' . _("name"));
ossim_valid($description, OSS_NULLABLE, OSS_TEXT, 'illegal:' . _("description"));
ossim_valid($type, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("type"));
ossim_valid($plugin_id, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("plugin id"));
ossim_valid($source_type, OSS_NULLABLE, OSS_ALPHA, OSS_SPACE, OSS_SCORE, 'illegal:' . _("source type"));
ossim_valid($enable, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("enable"));
ossim_valid($create, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("create"));
ossim_valid($process, OSS_NULLABLE, OSS_TEXT, 'illegal:' . _("process"));
ossim_valid($source, OSS_NULLABLE, OSS_LETTER, 'illegal:' . _("source"));
ossim_valid($location, OSS_NULLABLE, OSS_TEXT, 'illegal:' . _("location"));
ossim_valid($start, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("start"));
ossim_valid($stop, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("stop"));
ossim_valid($startup_command, OSS_NULLABLE, OSS_TEXT, 'illegal:' . _("startup command"));
ossim_valid($stop_command, OSS_NULLABLE, OSS_TEXT, 'illegal:' . _("stop command"));
$sample_log = "";

if (ossim_error()) {
   die(ossim_error());
}

if (is_uploaded_file($_FILES['sample_log']['tmp_name'])) {
	$conf = $GLOBALS["conf"];
	$uploads_dir = $conf->get_conf("repository_upload_dir");
	$sample_log = $uploads_dir."/".$_FILES['sample_log']['name'];
    move_uploaded_file($_FILES['sample_log']['tmp_name'], $sample_log);
}
if ($action=="new" && $id=="") {
	Collectors::insert($conn, $name, $description, $type, $plugin_id, $source_type, $enable, $source, $location, $create, $process, $start, $stop, $startup_command, $stop_command, $sample_log);
}
if ($action=="modify" && $id!="") {
	Collectors::update($conn, $id, $name, $description, $type, $plugin_id, $source_type, $enable, $source, $location, $create, $process, $start, $stop, $startup_command, $stop_command, $sample_log);
}
if ($action=="delete" && $id!="") {
	Collectors::delete($conn, $id);
}

$collectors = Collectors::get_list($conn);
?>

<script>
$(document).ready(function(){
}); 
</script>
<center>
<table width="90%" class="noborder" style="background:transparent;" cellspacing="0" cellpadding="0">
    <tr><td style="text-align:right;" class="nobborder">
    <form><input type="button" class="button" onclick="document.location.href='modifycollectors.php?action=new'" value="<?=_("Insert new collector")?>"></form>
    </td>
    </tr>
</table>
</center>
<br>
<table width="90%" align="center" class="noborder" cellspacing="0" cellpadding="0">
    <tr>
        <td height="30" class="plfieldhdr pall"><?php echo _("ID") ?></td>
        <td height="30" class="plfieldhdr ptop pbottom pright"><?php echo _("Name") ?></td>
        <td height="30" class="plfieldhdr ptop pbottom pright"><?php echo _("Description") ?></td>
        <td height="30" class="plfieldhdr ptop pbottom pright"><?php echo _("Type") ?></td>
        <td height="30" class="plfieldhdr ptop pbottom pright"><?php echo _("Plugin ID") ?></td>
        <td height="30" class="plfieldhdr ptop pbottom pright"><?php echo _("Plugin Source Type") ?></td>
        <td height="30" class="plfieldhdr ptop pbottom pright"><?php echo _("Source") ?></td>
        <td height="30" class="plfieldhdr ptop pbottom pright"><?php echo _("Actions") ?></td>
    </tr>
    <?php
    if (count($collectors)==0) {
    ?>
    <tr>
        <td height="30" colspan="7" class="pleft ptop pbottom pright"><?php echo _("No custom collectors defined") ?></td>
    </tr>    
    <?	
    }
	// SHOW COLLETORS  
	$i = 0;
	foreach($collectors as $coll) {
	    $id = $coll->get_id();
	    $color = ($i%2==0) ? "lightgray" : "blank";
	    $type = $coll->get_type();
	    $type = ($type==1) ? "Detector" : ($type==2 ? "Monitor" : ($type==3 ? "Scanner" : "Data"));
	?>
        <tr class="<?=$color?>" txt="<?=$id?>">
            <td class="pleft"><b><?=$coll->get_id()?></b></td>
            <td><?=$coll->get_name();?></td>
            <td style="text-align:left;padding-left:10px"><?=$coll->get_description();?></td>
            <td><?=$type?></td>
            <td><?=$coll->get_plugin_id();?></td>
            <td><?=$coll->get_source_type();?></td>
            <td><?=$coll->get_source();?></td>
            <td class="pright" style="padding:3px 0px 3px 0px" nowrap>
            <a href="modifycollectors.php?action=modify&id=<?=$coll->get_id()?>"><img src="../vulnmeter/images/pencil.png" border="0"></a>
            <a href="collectors.php?action=delete&id=<?=$coll->get_id()?>"><img src="../vulnmeter/images/delete.gif" border="0"></a>  
			&nbsp;&nbsp;&nbsp;<a href="collector_rules.php?idc=<?=$coll->get_id()?>&action=new"><img src="../pixmaps/rules_edit.png" border="0"></a>                      
            </td>
        </tr>
	<?php $i++;
} ?>
</table>
<?php
$db->close($conn);
?>