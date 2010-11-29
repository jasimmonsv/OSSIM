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
  <link rel="stylesheet" type="text/css" href="../style/greybox.css" />  
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/greybox.js"></script>
  <script type="text/javascript" src="../js/utils.js"></script>
</head>
<body style="height: auto; margin: 0 0 10px 0">

<?php
include ("../hmenu.php");

$action = GET('action');
if ($action=="") $action = "new";
$id = GET('id');
$idc = GET("idc");
$name = GET("name");
$description = utf8_decode(base64_decode(GET("description")));
$type = GET("type");
$expression = utf8_decode(base64_decode(GET("expression")));
$prio = GET("prio");
$rel = GET("rel");
$plugin_sid = GET("plugin_sid");
$date = GET("date");
$sensor = GET("sensor");
$interface = GET("interface");
$protocol = GET("protocol");
$src_ip = GET("src_ip");
$src_port = GET("src_port");
$dst_ip = GET("dst_ip");
$dst_port = GET("dst_port");
$username = GET("username");
$password = GET("password");
$filename = GET("filename");
$userdata1 = GET("userdata1");
$userdata2 = GET("userdata2");
$userdata3 = GET("userdata3");
$userdata4 = GET("userdata4");
$userdata5 = GET("userdata5");
$userdata6 = GET("userdata6");
$userdata7 = GET("userdata7");
$userdata8 = GET("userdata8");
$userdata9 = GET("userdata9");

ossim_valid($action, OSS_LETTER, OSS_NULLABLE, 'illegal:' . _("action"));
ossim_valid($id, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("id"));
ossim_valid($idc, OSS_DIGIT, "illegal:" . _("idc"));
ossim_valid($name, OSS_NULLABLE, OSS_SCORE, OSS_TEXT, OSS_SPACE, "illegal:" . _("name"));
ossim_valid($description, OSS_NULLABLE, OSS_SCORE, OSS_TEXT, OSS_SPACE, "illegal:" . _("description"));
ossim_valid($type, OSS_NULLABLE, OSS_LETTER, "illegal:" . _("type"));
ossim_valid($expression, OSS_NULLABLE, OSS_PUNC_EXT, OSS_TEXT, OSS_SPACE, OSS_SCORE, "\>\<\}\{\$", "illegal:" . _("expression"));
ossim_valid($prio, OSS_NULLABLE, OSS_DIGIT, "illegal:" . _("prio"));
ossim_valid($rel, OSS_NULLABLE, OSS_DIGIT, "illegal:" . _("rel"));
ossim_valid($plugin_sid, OSS_NULLABLE, OSS_DIGIT, "illegal:" . _("plugin_sid"));
ossim_valid($date, OSS_NULLABLE, OSS_PUNC_EXT, OSS_TEXT, OSS_SPACE, OSS_SCORE, "\>\<\}\{\$", "illegal:" . _("date"));
ossim_valid($sensor, OSS_NULLABLE, OSS_PUNC_EXT, OSS_TEXT, OSS_SPACE, OSS_SCORE, "\>\<\}\{\$", "illegal:" . _("sensor"));
ossim_valid($interface, OSS_NULLABLE, OSS_PUNC_EXT, OSS_TEXT, OSS_SPACE, OSS_SCORE, "\>\<\}\{\$", "illegal:" . _("interface"));
ossim_valid($protocol, OSS_NULLABLE, OSS_PUNC_EXT, OSS_TEXT, OSS_SPACE, OSS_SCORE, "\>\<\}\{\$", "illegal:" . _("protocol"));
ossim_valid($src_ip, OSS_NULLABLE, OSS_PUNC_EXT, OSS_TEXT, OSS_SPACE, OSS_SCORE, "\>\<\}\{\$", "illegal:" . _("src_ip"));
ossim_valid($src_port, OSS_NULLABLE, OSS_PUNC_EXT, OSS_TEXT, OSS_SPACE, OSS_SCORE, "\>\<\}\{\$", "illegal:" . _("src_port"));
ossim_valid($dst_ip, OSS_NULLABLE, OSS_PUNC_EXT, OSS_TEXT, OSS_SPACE, OSS_SCORE, "\>\<\}\{\$", "illegal:" . _("dst_ip"));
ossim_valid($dst_port, OSS_NULLABLE, OSS_PUNC_EXT, OSS_TEXT, OSS_SPACE, OSS_SCORE, "\>\<\}\{\$", "illegal:" . _("dst_port"));
ossim_valid($username, OSS_NULLABLE, OSS_PUNC_EXT, OSS_TEXT, OSS_SPACE, OSS_SCORE, "\>\<\}\{\$", "illegal:" . _("username"));
ossim_valid($password, OSS_NULLABLE, OSS_PUNC_EXT, OSS_TEXT, OSS_SPACE, OSS_SCORE, "\>\<\}\{\$", "illegal:" . _("password"));
ossim_valid($filename, OSS_NULLABLE, OSS_PUNC_EXT, OSS_TEXT, OSS_SPACE, OSS_SCORE, "\>\<\}\{\$", "illegal:" . _("filename"));
ossim_valid($userdata1, OSS_NULLABLE, OSS_PUNC_EXT, OSS_TEXT, OSS_SPACE, OSS_SCORE, "\>\<\}\{\$", "illegal:" . _("userdata1"));
ossim_valid($userdata2, OSS_NULLABLE, OSS_PUNC_EXT, OSS_TEXT, OSS_SPACE, OSS_SCORE, "\>\<\}\{\$", "illegal:" . _("userdata2"));
ossim_valid($userdata3, OSS_NULLABLE, OSS_PUNC_EXT, OSS_TEXT, OSS_SPACE, OSS_SCORE, "\>\<\}\{\$", "illegal:" . _("userdata3"));
ossim_valid($userdata4, OSS_NULLABLE, OSS_PUNC_EXT, OSS_TEXT, OSS_SPACE, OSS_SCORE, "\>\<\}\{\$", "illegal:" . _("userdata4"));
ossim_valid($userdata5, OSS_NULLABLE, OSS_PUNC_EXT, OSS_TEXT, OSS_SPACE, OSS_SCORE, "\>\<\}\{\$", "illegal:" . _("userdata5"));
ossim_valid($userdata6, OSS_NULLABLE, OSS_PUNC_EXT, OSS_TEXT, OSS_SPACE, OSS_SCORE, "\>\<\}\{\$", "illegal:" . _("userdata6"));
ossim_valid($userdata7, OSS_NULLABLE, OSS_PUNC_EXT, OSS_TEXT, OSS_SPACE, OSS_SCORE, "\>\<\}\{\$", "illegal:" . _("userdata7"));
ossim_valid($userdata8, OSS_NULLABLE, OSS_PUNC_EXT, OSS_TEXT, OSS_SPACE, OSS_SCORE, "\>\<\}\{\$", "illegal:" . _("userdata8"));
ossim_valid($userdata9, OSS_NULLABLE, OSS_PUNC_EXT, OSS_TEXT, OSS_SPACE, OSS_SCORE, "\>\<\}\{\$", "illegal:" . _("userdata9"));

if (ossim_error()) {
   die(ossim_error());
}

if ($action == "new" && $idc!="" && $name!="") {
	CollectorRule::insert($conn, $idc, $name, $description, $type, $expression, $prio, $rel, $plugin_sid, $date, $sensor, $interface, $protocol, $src_ip, $src_port, $dst_ip,  $dst_port, $username, $password, $filename, $userdata1, $userdata2, $userdata3, $userdata4, $userdata5, $userdata6, $userdata7, $userdata8, $userdata9);
}
if ($action == "modify" && $id!="" && $idc!="" && $name!="") {
	CollectorRule::update($conn, $id, $idc, $name, $description, $type, $expression, $prio, $rel, $plugin_sid, $date, $sensor, $interface, $protocol, $src_ip, $src_port, $dst_ip,  $dst_port, $username, $password, $filename, $userdata1, $userdata2, $userdata3, $userdata4, $userdata5, $userdata6, $userdata7, $userdata8, $userdata9);
	$action = "edit";
}
if ($action == "delete" && $id!="") {
	CollectorRule::delete($conn, $id);
	$action = "new";
}

$collectors = Collectors::get_list($conn,"WHERE id=$idc");
?>
<style type="text/css">
small { color:darkgray; }
.buttonplus {
    border-width: 0px !important;
    color: #545454 !important;
    height:20px !important;
    background: url(../pixmaps/theme/bg_button.png) 50% 50% repeat-x !important;
    padding-bottom:2px !important;
    font-family: arial,verdana,helvetica,sans-serif !important;
    font-size: 12px !important;
    font-weight:bold !important;
    margin-right:5px;
}
.buttonplus:hover {
    color: white !important;
    background: url(../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important;
}
</style>
<script>
function encode() {
	$('#fo textarea[name=description]').val(Base64.encode($('#fo textarea[name=description]').val()));
	$('#fo textarea[name=expression]').val(Base64.encode($('#fo textarea[name=expression]').val()));
	return true;
}
function toggle_info(id) {
    $('#tr'+id).toggle($('#tr'+id).css('display') == 'none');
    var img = '#img'+id;
    if ($(img).attr('src').match(/minus/)) {
        $(img).attr('src','../pixmaps/plus-small.png');
    } else {
        $(img).attr('src','../pixmaps/minus-small.png');
    }
}
function view(section,idc) {
	GB_show("<?=_("Generate & View")?> ."+section,"collector_generate.php?download=0&section="+section+"&idc="+idc,"75%","80%");
}
function download(section,idc) {
	document.location.href="collector_generate.php?download=1&section="+section+"&idc="+idc;
}
function validate(section,idc) {
	GB_show("<?=_("Validate")?> ."+section,"collector_generate.php?download=2&section="+section+"&idc="+idc,"75%","80%");
}
function insert(idc) {
	GB_show("<?=_("Insert")?> .sql","collector_generate.php?download=3&section=sql&idc="+idc,"75%","80%");
}
$(document).ready(function(){
    $('.blank,.lightgray').disableTextSelect();
    $('.clickable').click(function(event) {
        toggle_info($(this).attr('alt'));
        return false;
    });
});
</script>
<center>
<table width="90%" align="center" class="noborder" cellspacing="0" cellpadding="0">
    <tr>
        <td height="30" class="plfieldhdr pall"><?php echo _("Name") ?></td>
        <td height="30" class="plfieldhdr ptop pbottom pright"><?php echo _("Description") ?></td>
        <td height="30" class="plfieldhdr ptop pbottom pright"><?php echo _("Type") ?></td>
        <td height="30" class="plfieldhdr ptop pbottom pright"><?php echo _("Plugin ID") ?></td>
        <td height="30" class="plfieldhdr ptop pbottom pright"><?php echo _("Source") ?></td>
    </tr>
    <?php
$i = 0;
foreach($collectors as $coll) {
    $color = ($i%2==0) ? "lightgray" : "blank";
    $type = $coll->get_type();
    $type = ($type==1) ? "Detector" : ($type==2 ? "Monitor" : ($type==3 ? "Scanner" : "Data"));
    $plugin_id = $coll->get_plugin_id();
    $rules = CollectorRule::get_list($conn,"WHERE idc=$idc ORDER BY name");
?>
        <tr class="<?=$color?>" height="22">
            <td class="pleft"><?=$coll->get_name();?></td>
            <td style="text-align:left;padding-left:10px"><?=$coll->get_description();?></td>
            <td><?=$type?></td>
            <td><?=$plugin_id?></td>
            <td class="pright"><?=$coll->get_source();?></td>
        </tr>
<?php $i++;
} ?>
</table>
<table width="90%" align="center" class="noborder" cellspacing="0" cellpadding="0">
    <tr>
        <td height="30" class="plfieldhdr pleft pbottom pright"><?php echo _("Rule Name") ?></td>
        <td height="30" class="plfieldhdr pbottom pright"><?php echo _("Rule Label/Descr") ?></td>
        <td height="30" width="50" class="plfieldhdr pbottom pright"><?php echo _("Prio") ?></td>
        <td height="30" width="50" class="plfieldhdr pbottom pright"><?php echo _("Rel") ?></td>
        <td height="30" width="80" class="plfieldhdr pbottom pright"><?php echo _("Plugin Sub-ID") ?></td>
        <td height="30" class="plfieldhdr pbottom pright"><?php echo _("Actions") ?></td>
    </tr>
<? if (count($rules)>0) {
     $i = 0;
     foreach ($rules as $rule) {   
        $color = ($i%2==0) ? "lightgray" : "blank"; 
        if ($action=="edit" && $id===$rule->get_id()) {
			$name = $rule->get_name();
			$description = $rule->get_description();
			$type = $rule->get_type();
			$expression = $rule->get_expression();
			$prio = $rule->get_prio();
			$rel = $rule->get_rel();
			$plugin_sid = $rule->get_plugin_sid();
			$date = $rule->get_date();
			$sensor = $rule->get_sensor();
			$interface = $rule->get_interface();
			$protocol = $rule->get_protocol();
			$src_ip = $rule->get_src_ip();
			$src_port = $rule->get_src_port();
			$dst_ip = $rule->get_dst_ip();
			$dst_port = $rule->get_dst_port();
			$username = $rule->get_username();
			$password = $rule->get_password();
			$filename = $rule->get_filename();
			$userdata1 = $rule->get_userdata1();
			$userdata2 = $rule->get_userdata2();
			$userdata3 = $rule->get_userdata3();
			$userdata4 = $rule->get_userdata4();
			$userdata5 = $rule->get_userdata5();
			$userdata6 = $rule->get_userdata6();
			$userdata7 = $rule->get_userdata7();
			$userdata8 = $rule->get_userdata8();
			$userdata9 = $rule->get_userdata9();
        }
    ?>
        <tr class="<?=$color?>" height="22">
            <td class="pleft left" style="padding-left:10px">
            	 <img id="img<?=$i?>" class="clickable" alt="<?=$i?>" src="../pixmaps/plus-small.png" align="absmiddle" border="0">
            	 <b><?=$rule->get_name()?></b>
            </td>
            <td style="text-align:left;padding-left:10px"><?=$rule->get_description();?></td>
            <td><?=$rule->get_prio()?></td>
            <td><?=$rule->get_rel()?></td>
            <td><?=$rule->get_plugin_sid()?></td>
            <td class="pright" style="padding:3px 0px 3px 0px">
            <a href="?action=edit&idc=<?=$idc?>&id=<?=$rule->get_id()?>"><img src="../vulnmeter/images/pencil.png" border="0"></a>
            <a href="?action=delete&idc=<?=$idc?>&id=<?=$rule->get_id()?>"><img src="../vulnmeter/images/delete.gif" border="0"></a>            
            </td>
        </tr>
        <tr class="<?=$color?>" id="tr<?=$i?>" style="display:none;background:#FFFFFF;padding-left:4px">
            <td class="pleft left pright" colspan="6" style="padding-left:30px">
            	<table width="100%" class="noborder">
            	<tr>
					<td class="nobborder"><b><?=_("date")?></b>: <?=$rule->get_date(true)?></td>
					<td class="nobborder"><b><?=_("sensor")?></b>: <?=$rule->get_sensor(true)?></td>
					<td class="nobborder"><b><?=_("interface")?></b>: <?=$rule->get_interface(true)?></td>
					<td class="nobborder"><b><?=_("protocol")?></b>: <?=$rule->get_protocol(true)?></td>
            	</tr>
            	<tr>					
					<td class="nobborder"><b><?=_("src_ip")?></b>: <?=$rule->get_src_ip(true)?></td>
					<td class="nobborder"><b><?=_("src_port")?></b>: <?=$rule->get_src_port(true)?></td>
					<td class="nobborder"><b><?=_("dst_ip")?></b>: <?=$rule->get_dst_ip(true)?></td>
					<td class="nobborder"><b><?=_("dst_port")?></b>: <?=$rule->get_dst_port(true)?></td>
            	</tr>
            	<tr>
					<td class="nobborder"><b><?=_("username")?></b>: <?=$rule->get_username(true)?></td>
					<td class="nobborder"><b><?=_("password")?></b>: <?=$rule->get_password(true)?></td>
					<td class="nobborder"><b><?=_("filename")?></b>: <?=$rule->get_filename(true)?></td>
					<td class="nobborder"><b><?=_("userdata1")?></b>: <?=$rule->get_userdata1(true)?></td>
            	</tr>
            	<tr>
					<td class="nobborder"><b><?=_("userdata2")?></b>: <?=$rule->get_userdata2(true)?></td>
					<td class="nobborder"><b><?=_("userdata3")?></b>: <?=$rule->get_userdata3(true)?></td>
					<td class="nobborder"><b><?=_("userdata4")?></b>: <?=$rule->get_userdata4(true)?></td>
					<td class="nobborder"><b><?=_("userdata5")?></b>: <?=$rule->get_userdata5(true)?></td>
            	</tr>
            	<tr>
					<td class="nobborder"><b><?=_("userdata6")?></b>: <?=$rule->get_userdata6(true)?></td>
					<td class="nobborder"><b><?=_("userdata7")?></b>: <?=$rule->get_userdata7(true)?></td>
					<td class="nobborder"><b><?=_("userdata8")?></b>: <?=$rule->get_userdata8(true)?></td>
					<td class="nobborder"><b><?=_("userdata9")?></b>: <?=$rule->get_userdata9(true)?></td>
            	</tr>
            	</table>
            </td>
        </tr>        
    <?	$i++;
     }
  } else { ?>
    <tr>
        <td height="30" colspan="6" class="pleft ptop pbottom pright"><?php echo _("No rules defined") ?></td>
    </tr>
<? } ?>
</table>

<!-- NEW RULES -->
<br><br>
<?
if ($action=="new" || $action=="") {
	$expression =  "(?P<sample_data>.*)";
	$prio = 1;
	$rel = 1;
	$plugin_sid = Collectors::get_next_sid($conn,$plugin_id);
}
?>
<form method="get" action="collector_rules.php" onsubmit="return encode()" id="fo">
<table width="90%" align="center" class="noborder" cellspacing="0" cellpadding="0">
<tr><td align="left" class="noborder" style="background:white" valign="top">

  <input type="hidden" name="action" value="<?= ($action=="edit") ? "modify" : "new" ?>">
  <input type="hidden" name="idc" value="<?=$idc?>">
  <input type="hidden" name="id" value="<?=$id?>">

  <table align="left"><tr><td class="noborder" valign="top">
	  <table align="left" class="noborder">
	  <tr>
	    <th> <?php echo gettext("Rule Name"); ?> (*)</th>
	    <td class="left" style="width:200px"><input type="text" name="name" size="42" value="<?php echo $name?>"/><br>
	    <small><?=_("Rules will be processed in alphabetical order, please precede with
	numbers / letters in order to impact evaluation order. This is not the
	label that will be shown in reports/interface")?></small></td>
	  </tr>
	  <tr>
	    <th> <?php echo gettext("Rule Label/Description"); ?> </th>
	    <td class="left"><textarea name="description" rows="5" cols="39"><?php echo $description?></textarea><br>
	    <small>this is the name that will be displayed in reports</small></td>
	  </tr>
	  <tr>
	    <th> <?php echo gettext("Event Type"); ?> </th>
	    <td class="left">
	        <select name="type">
			<option value='event'><?=_("event")?></option>
	        </select>
	    </td>
	  </tr>
	  <tr>
	    <th> <?php echo gettext("Regular Expression"); ?> (*)</th>
	    <td class="left"><textarea name="expression" rows="12" cols="39"><?php echo $expression?></textarea><br>
	    <small>this is the name that will be displayed in reports</small></td>
	  </tr>  
	  <tr>
	    <th> <?php echo gettext("Event Priority"); ?> </th>
	    <td class="left">
	        <select name="prio">
			<option value='0'<?= ($prio==0) ? " selected" : "" ?>>0</option>
			<option value='1'<?= ($prio==1) ? " selected" : "" ?>>1</option>
			<option value='2'<?= ($prio==2) ? " selected" : "" ?>>2</option>
			<option value='3'<?= ($prio==3) ? " selected" : "" ?>>3</option>
			<option value='4'<?= ($prio==4) ? " selected" : "" ?>>4</option>
			<option value='5'<?= ($prio==5) ? " selected" : "" ?>>5</option>
	        </select>
	    </td>
	  </tr>   
	  <tr>
	    <th> <?php echo gettext("Event Reliability"); ?> </th>
	    <td class="left">
	        <select name="rel">
			<option value='0'<?= ($rel==0) ? " selected" : "" ?>>0</option>
			<option value='1'<?= ($rel==1) ? " selected" : "" ?>>1</option>
			<option value='2'<?= ($rel==2) ? " selected" : "" ?>>2</option>
			<option value='3'<?= ($rel==3) ? " selected" : "" ?>>3</option>
			<option value='4'<?= ($rel==4) ? " selected" : "" ?>>4</option>
			<option value='5'<?= ($rel==5) ? " selected" : "" ?>>5</option>
			<option value='6'<?= ($rel==6) ? " selected" : "" ?>>6</option>
			<option value='7'<?= ($rel==7) ? " selected" : "" ?>>7</option>
			<option value='8'<?= ($rel==8) ? " selected" : "" ?>>8</option>
			<option value='9'<?= ($rel==9) ? " selected" : "" ?>>9</option>
			<option value='10'<?= ($rel==10) ? " selected" : "" ?>>10</option>
	        </select>
	    </td>
	  </tr>  
	  <tr>
	    <th> <?php echo gettext("Plugin Sub-ID"); ?> (*)</th>
	    <td class="left"><input type="text" name="plugin_sid" size="42" value="<?php echo $plugin_sid?>"/></td>
	  </tr> 
	  </table>
  </td><td class="noborder" valign="top">
  	  <table class="noborder">
	  <tr>
	    <th> <?php echo gettext("date"); ?></th>
	    <td class="left"><input type="text" name="date" size="40" value="<?php echo $date?>"/></td>
	  </tr>
	  <tr>
	    <th> <?php echo gettext("sensor"); ?></th>
	    <td class="left"><input type="text" name="sensor" size="40" value="<?php echo $sensor?>"/></td>
	  </tr>	  
	  <tr>
	    <th> <?php echo gettext("interface"); ?></th>
	    <td class="left"><input type="text" name="interface" size="40" value="<?php echo $interface?>"/></td>
	  </tr>	  
	  <tr>
	    <th> <?php echo gettext("protocol"); ?></th>
	    <td class="left"><input type="text" name="protocol" size="40" value="<?php echo $protocol?>"/></td>
	  </tr>	  
	  <tr>
	    <th> <?php echo gettext("src_ip"); ?></th>
	    <td class="left"><input type="text" name="src_ip" size="40" value="<?php echo $src_ip?>"/></td>
	  </tr>	  
	  <tr>
	    <th> <?php echo gettext("src_port"); ?></th>
	    <td class="left"><input type="text" name="src_port" size="40" value="<?php echo $src_port?>"/></td>
	  </tr>	  
	  <tr>
	    <th> <?php echo gettext("dst_ip"); ?></th>
	    <td class="left"><input type="text" name="dst_ip" size="40" value="<?php echo $dst_ip?>"/></td>
	  </tr>	  
	  <tr>
	    <th> <?php echo gettext("dst_port"); ?></th>
	    <td class="left"><input type="text" name="dst_port" size="40" value="<?php echo $dst_port?>"/></td>
	  </tr>	  
	  <tr>
	    <th> <?php echo gettext("username"); ?></th>
	    <td class="left"><input type="text" name="username" size="40" value="<?php echo $username?>"/></td>
	  </tr>	  
	  <tr>
	    <th> <?php echo gettext("password"); ?></th>
	    <td class="left"><input type="text" name="password" size="40" value="<?php echo $password?>"/></td>
	  </tr>	  
	  <tr>
	    <th> <?php echo gettext("filename"); ?></th>
	    <td class="left"><input type="text" name="filename" size="40" value="<?php echo $filename?>"/></td>
	  </tr>	  
	  <tr>
	    <th> <?php echo gettext("userdata1"); ?></th>
	    <td class="left"><input type="text" name="userdata1" size="40" value="<?php echo $userdata1?>"/></td>
	  </tr>	  
	  <tr>
	    <th> <?php echo gettext("userdata2"); ?></th>
	    <td class="left"><input type="text" name="userdata2" size="40" value="<?php echo $userdata2?>"/></td>
	  </tr>	  
	  <tr>
	    <th> <?php echo gettext("userdata3"); ?></th>
	    <td class="left"><input type="text" name="userdata3" size="40" value="<?php echo $userdata3?>"/></td>
	  </tr>	  
	  <tr>
	    <th> <?php echo gettext("userdata4"); ?></th>
	    <td class="left"><input type="text" name="userdata4" size="40" value="<?php echo $userdata4?>"/></td>
	  </tr>	  
	  <tr>
	    <th> <?php echo gettext("userdata5"); ?></th>
	    <td class="left"><input type="text" name="userdata5" size="40" value="<?php echo $userdata5?>"/></td>
	  </tr>	  
	  <tr>
	    <th> <?php echo gettext("userdata6"); ?></th>
	    <td class="left"><input type="text" name="userdata6" size="40" value="<?php echo $userdata6?>"/></td>
	  </tr>	  
	  <tr>
	    <th> <?php echo gettext("userdata7"); ?></th>
	    <td class="left"><input type="text" name="userdata7" size="40" value="<?php echo $userdata7?>"/></td>
	  </tr>	  
	  <tr>
	    <th> <?php echo gettext("userdata8"); ?></th>
	    <td class="left"><input type="text" name="userdata8" size="40" value="<?php echo $userdata8?>"/></td>
	  </tr>	  
	  <tr>
	    <th> <?php echo gettext("userdata9"); ?></th>
	    <td class="left"><input type="text" name="userdata9" size="40" value="<?php echo $userdata9?>"/></td>
	  </tr>	  
	  </table>
  </td>
  <tr>
    <td colspan="2" align="center" class="noborder">
      <input type="submit" value="<?=_("Add/Update rule")?>" class="button" style="font-size:12px">
    </td>
  </tr>
</table>

</td>
<td align="left" class="noborder" style="background:white" valign="top">

	<table width="100%" class="noborder" style="background:transparent">
	<tr>
		<td class="nobborder">
			<input type="button" class="button" value="<?=_("View .cfg")?>" style="width:120px" onclick="view('cfg','<?=$idc?>')">
		</td>
	</tr>
	<tr>
		<td class="nobborder">
			<input type="button" class="button" value="<?=_("Download .cfg")?>" style="width:120px" onclick="download('cfg','<?=$idc?>')">
		</td>
	</tr>	
	<tr>
		<td class="nobborder">
			<input type="button" class="buttonplus" value="<?=_("Validate .cfg")?>" style="width:120px" onclick="validate('cfg','<?=$idc?>')">
		</td>
	</tr>	
	<tr>
		<td class="nobborder" height="20"></td>
	</tr>		
	<tr>
		<td class="nobborder">
			<input type="button" class="button" value="<?=_("View .sql")?>" style="width:120px" onclick="view('sql','<?=$idc?>')">
		</td>
	</tr>
	<tr>
		<td class="nobborder">
			<input type="button" class="button" value="<?=_("Download .sql")?>" style="width:120px" onclick="download('sql','<?=$idc?>')">
		</td>
	</tr>	
	<tr>
		<td class="nobborder">
			<input type="button" class="buttonplus" value="<?=_("Insert .sql")?>" style="width:120px" onclick="if (confirm('<?=_("Are you sure?")?>')) insert('<?=$idc?>')">
		</td>
	</tr>	
    </table>

</td></tr>
</table>

</form>

<?php
$db->close($conn);
?>