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
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript">
  function load_subcategory (category_id) {
		$("#ajaxSubCategory").html("<img src='../pixmaps/loading.gif' width='20' alt='Loading'>Loading");
		$.ajax({
			type: "GET",
			url: "modifypluginsid_ajax.php",
			data: { category_id:category_id },
			success: function(msg) {
				$("#ajaxSubCategory").html(msg);
			}
		});
	}
  </script>
</head>
<body>

<?php
include ("../hmenu.php");
require_once ('classes/Security.inc');
require_once ('classes/Collectors.inc');
require_once ('ossim_db.inc');
require_once ('ossim_conf.inc');

$action = GET('action');
$id = GET('id');

ossim_valid($id, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("id"));
ossim_valid($action, OSS_LETTER, 'illegal:' . _("action"));

if (ossim_error()) {
   die(ossim_error());
}

$db = new ossim_db();
$conn = $db->connect();
// Category
if ($action=="modify") {
	$coll = Collectors::get_list($conn,"WHERE id=$id");
	$name = $coll[0]->get_name();
	$description = $coll[0]->get_description();
	$plugin_id = $coll[0]->get_plugin_id();
	$type = $coll[0]->get_type();
	$enable = $coll[0]->get_enable();
	$process = $coll[0]->get_process();
	$source = $coll[0]->get_source();
	$create = $coll[0]->get_create();
	$location = $coll[0]->get_location();
	$start = $coll[0]->get_start();
	$stop = $coll[0]->get_stop();
	$startup_command = $coll[0]->get_startup_command();
	$stop_command = $coll[0]->get_stop_command();
	$sample_log = $coll[0]->get_sample_log();
}
if ($action=="new") {
	$type = 1;
	$enable = 1;
	$location = "/var/log/syslog";
	$source = "log";
	$start = 0;
	$stop = 0;
	$plugin_id = Collectors::get_max_id($conn,$type);
}
?>
    
<form method="post" action="collectors.php" enctype="multipart/form-data">
  <input type="hidden" name="action" value="<?=$action?>">
  <input type="hidden" name="id" value="<?=$id?>">
  <table align="center">
  <tr>
    <th> <?php echo gettext("Plugin Name"); ?> (*)</th>
    <td class="left"><input type="text" name="name" size="42" value="<?php echo $name?>"/></td>
  </tr>
  <tr>
    <th> <?php echo gettext("Plugin Description"); ?> </th>
    <td class="left"><textarea name="description" rows="2" cols="39"><?php echo $description?></textarea></td>
  </tr>
  <tr>
    <th> <?php echo gettext("Plugin Type"); ?> </th>
    <td class="left">
        <select name="type">
		<option value='1'<?= ($type==1) ? " selected" : "" ?>><?=_("Detector")?></option>
		<option value='2'<?= ($type==2) ? " selected" : "" ?>><?=_("Monitor")?></option>
		<option value='3'<?= ($type==3) ? " selected" : "" ?>><?=_("Scanner")?></option>
		<option value='4'<?= ($type==4) ? " selected" : "" ?>><?=_("Data")?></option>
        </select>
    </td>
  </tr>
  <tr>
    <th> <?php echo gettext("Plugin ID"); ?> (*)</th>
    <td class="left"><input type="text" name="plugin_id" size="42" value="<?php echo $plugin_id?>"/></td>
  </tr>  
  <tr>
    <th> <?php echo gettext("Enable"); ?> </th>
    <td class="left">
        <select name="enable">
		<option value='1'<?= ($enable==1) ? " selected" : "" ?>><?=_("Yes")?></option>
		<option value='0'<?= ($enable==0) ? " selected" : "" ?>><?=_("No")?></option>
        </select>
    </td>
  </tr>  
  <tr>
    <th> <?php echo gettext("Source"); ?> </th>
    <td class="left">
        <select name="source">
		<option value='command'<?= ($source=='command') ? " selected" : "" ?>><?=_("command")?></option>
		<option value='database'<?= ($source=='database') ? " selected" : "" ?>><?=_("database")?></option>
		<option value='http'<?= ($source=='http') ? " selected" : "" ?>><?=_("http")?></option>
		<option value='log'<?= ($source=='log') ? " selected" : "" ?>><?=_("log")?></option>
		<option value='remote_command'<?= ($source=='remote_command') ? " selected" : "" ?>><?=_("remote_command")?></option>
		<option value='sdee'<?= ($source=='sdee') ? " selected" : "" ?>><?=_("sdee")?></option>
		<option value='session'<?= ($source=='session') ? " selected" : "" ?>><?=_("session")?></option>
		<option value='snortlog'<?= ($source=='snortlog') ? " selected" : "" ?>><?=_("snortlog")?></option>
		<option value='unix_socket'<?= ($source=='unix_socket') ? " selected" : "" ?>><?=_("unix_socket")?></option>
		<option value='wmi'<?= ($source=='wmi') ? " selected" : "" ?>><?=_("wmi")?></option>
        </select>
    </td>
  </tr>
  <tr>
    <th> <?php echo gettext("Location"); ?> (*)</th>
    <td class="left"><input type="text" name="location" size="42" value="<?php echo $location?>"/></td>
  </tr>
  <tr>
    <th> <?php echo gettext("Create File"); ?> </th>
    <td class="left">
        <select name="create">
		<option value='1'<?= ($create==1) ? " selected" : "" ?>><?=_("True")?></option>
		<option value='0'<?= ($create==0) ? " selected" : "" ?>><?=_("False")?></option>
        </select>
    </td>
  </tr>  
  <tr>
    <th> <?php echo gettext("Process"); ?> </th>
    <td class="left"><input type="text" name="process" size="42" value="<?php echo $process?>"/></td>
  </tr>
  <tr>
    <th> <?php echo gettext("Start?"); ?> </th>
    <td class="left">
        <select name="start">
		<option value='1'<?= ($start==1) ? " selected" : "" ?>><?=_("Yes")?></option>
		<option value='0'<?= ($start==0) ? " selected" : "" ?>><?=_("No")?></option>
        </select>
    </td>
  </tr>  
  <tr>
    <th> <?php echo gettext("Stop?"); ?> </th>
    <td class="left">
        <select name="stop">
		<option value='1'<?= ($stop==1) ? " selected" : "" ?>><?=_("Yes")?></option>
		<option value='0'<?= ($stop==0) ? " selected" : "" ?>><?=_("No")?></option>
        </select>
    </td>
  </tr>  
  <tr>
    <th> <?php echo gettext("Startup command"); ?> </th>
    <td class="left"><input type="text" name="startup_command" size="42" value="<?php echo $startup_command?>"/></td>
  </tr>
  <tr>
    <th> <?php echo gettext("Shutdown command"); ?> </th>
    <td class="left"><input type="text" name="stop_command" size="42" value="<?php echo $stop_command?>"/></td>
  </tr>  
  <tr>
    <th> <?php echo gettext("Sample log"); ?> </th>
    <td class="left"><input type="file" name="sample_log" size="30"></td>
  </tr>  
  <tr>
    <td colspan="2" align="center" class="noborder">
      <input type="submit" value="<?=_("OK")?>" class="button" style="font-size:12px">
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
?>
