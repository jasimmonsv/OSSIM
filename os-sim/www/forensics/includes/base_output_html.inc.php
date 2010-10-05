<?php
/**
* Class and Function List:
* Function list:
* - PrintBASESubHeader()
* - PrintBASESubFooter()
* - PrintFramedBoxHeader()
* - PrintCustomViews()
* - PrintFramedBoxFooter()
* - PrintFreshPage()
* - chk_select()
* - chk_check()
* - dispYearOptions()
* - PrintBASEAdminMenuHeader()
* - PrintBASEAdminMenuFooter()
* - PrintBASEHelpLink()
* Classes list:
*/
/*******************************************************************************
** OSSIM Forensics Console
** Copyright (C) 2009 OSSIM/AlienVault
** Copyright (C) 2004 BASE Project Team
** Copyright (C) 2000 Carnegie Mellon University
**
** (see the file 'base_main.php' for license details)
**
** Built upon work by Roman Danyliw <rdd@cert.org>, <roman@danyliw.com>
** Built upon work by the BASE Project Team <kjohnson@secureideas.net>
**/
defined('_BASE_INC') or die('Accessing this file directly is not allowed.');
function PrintBASESubHeader($page_title, $page_name, $back_link, $refresh = 0, $page = "") {
    GLOBAL $debug_mode, $BASE_VERSION, $BASE_path, $BASE_urlpath, $html_no_cache, $max_script_runtime, $Use_Auth_System, $stat_page_refresh_time, $base_style, $refresh_stat_page, $ossim_servers, $sensors, $hosts, $database_servers, $DBlib_path, $DBtype, $db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password;
    if (ini_get("safe_mode") != true) set_time_limit($max_script_runtime);
    echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<!-- ' . _TITLE . $BASE_VERSION . ' -->
<HTML>
  <HEAD><meta http-equiv="Content-Type" content="text/html; charset=' . _CHARSET . '">';
    if ($html_no_cache == 1) echo '<META HTTP-EQUIV="pragma" CONTENT="no-cache">';
    echo '<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />';
	if ($refresh == 1 && !$_SESSION['norefresh']) PrintFreshPage($refresh_stat_page, $stat_page_refresh_time);
    if (@$_COOKIE['archive'] == 0) echo '<TITLE>' . _TITLE . ': ' . $page_title . '</TITLE>';
    else echo '<TITLE>' . _TITLE . ': ' . $page_title . ' -- ARCHIVE</TITLE>';
    echo '<LINK rel="stylesheet" type="text/css" HREF="' . $BASE_urlpath . '/styles/' . $base_style . '">
        </HEAD>
        <BODY>';
    if (!array_key_exists("minimal_view", $_GET)) {
        include ("$BASE_path/base_hdr1.php");
        $db = NewBASEDBConnection($DBlib_path, $DBtype);
        $db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);
        include ("$BASE_path/base_hdr2.php");
    }
    //echo "<TABLE WIDTH=\"100%\"><TR><TD ALIGN=RIGHT>".$back_link."</TD></TR></TABLE><BR>";
    if ($debug_mode > 0) PrintPageHeader();
}
function PrintBASESubFooter() {
    GLOBAL $BASE_VERSION, $BASE_path, $BASE_urlpath, $Use_Auth_System;
    echo "\n\n<!-- BASE Footer -->\n" . "<P>\n";
    //include("$BASE_path/base_footer.php");
    echo "\n\n";
}
function PrintFramedBoxHeader($title, $fore, $back) {
    echo '
<TABLE WIDTH="100%" CELLSPACING=0 CELLPADDING=0 BORDER=0>
<TR><TD>
  <TABLE WIDTH="100%" CELLSPACING=0 CELLPADDING=0 BORDER=0>
  <TR><TD height="27" align="center" style="background:url(\'../pixmaps/fondo_col.gif\') repeat-x;color:#333333;border:1px solid #CACACA;font-size:14px;font-weight:bold">&nbsp;' . $title . '&nbsp;</TD></TR>
    <TR><TD>';
}
function PrintFramedBoxFooter() {
    echo '
  </TD></TR></TABLE>
</TD></TR></TABLE>';
}
function PrintCustomViews() {
	?>
	<table cellpadding=0 cellspacing=0 class="headermenu" style="background-color:white;border:0px solid white" width="100%">
		<tr><td align="center" style="padding:5px;border:1px solid #CCCCCC;background:url('../pixmaps/fondo_hdr2.png') repeat-x"><table cellpadding=0 cellspacing=0>
		<tr>
			<td width="30" id="customview_msg"></td>
			<td style="color: black; font-size: 12px; font-weight: bold" nowrap><?=_("Select View")?>:&nbsp;</td>
			<td>
				<select name="customview" onchange="change_view(this.value)">
					<? foreach ($_SESSION['views'] as $name=>$attr) { ?>
					<option value="<?=$name?>" <? if ($_SESSION['current_cview'] == $name) echo "selected"?>><?=$name?>
					<? } ?>
				</select>
			</td>
			<td style="padding:2px"><input type="button" value="<?=_("Modify")?>" onclick="GB_show('<?=_("Edit custom view")?>','custom_view_edit.php?edit=1',420,600);" class="button"></td>
			<td style="padding:2px"><input type="button" value="<?=_("Save Current")?>" onclick="save_view()" class="button"></td>
			<td style="padding:2px">|</td>
			<td style="padding:2px"><input type="button" value="<?=_("Create New View")?>" onclick="GB_show('<?=_("Create new custom view")?>','custom_view_edit.php',420,600);" class="button"></td>
			<td width="30"></td>
		</tr>
		</table></td></tr>
	</table>
	<?
}

function PrintPredefinedViews() {
	?>

   <a style='cursor:pointer; font-weight:bold;' class='ndc' onclick="$('#views').toggle()"><img src="../pixmaps/arrow_green.gif" align="absmiddle" border="0"/><?php echo _("Custom Views")?></a>
   <div style="position:relative">
		<div id="views" style="position:absolute;right:0;top:0;display:none">
			<table cellpadding='0' cellspacing='0' align="center" >
				<tr>
					<th style="padding-right:3px">
						<div style='float:left; width:70%; text-align: right;'><?php echo _("Select View")?></div>
						<div style='float:right; width:18%; padding-top: 2px;  text-align: right;'><a style="cursor:pointer; text-align: right;" onclick="$('#views').toggle()"><img src="../pixmaps/cross-circle-frame.png" alt="<?php echo _("Close"); ?>" title="<?php echo _("Close"); ?>" border="0" align='absmiddle'/></a></div>
					</th>
				</tr>
				<tr class="noborder">
					<td id="viewsbox" colspan='2'>
					<table cellpadding='0' cellspacing='0' style='border: none;'>
					<? $i=0;
					foreach ($_SESSION['views'] as $name=>$attr) {
					$i++;    
					$color = ($i%2==0) ? "impar" : "par"; 
					?>
						<tr class='noborder'>
							<?php 
							  if ( $_SESSION['current_cview'] == $name ){
								 $style = 'font-weight: bold;';
								 $opacidad = '';
								 $boton0 = "<a style='cursor:pointer;' onclick=\"GB_show('"._('Edit custom view')."','custom_view_edit.php?edit=1&forcesave=1',460,600);\"><img src='../pixmaps/documents-save.png' alt='"._('Save as report')."' title='"._('Save as report')."' border='0'/></a>&nbsp;";
								 $boton1 = "<a style='cursor:pointer;' onclick=\"save_view('save_".$i."');\"><img id='save_".$i."' src='../pixmaps/disk-gray.png' alt='"._('Save Current')."' title='"._('Save')."' border='0'/></a>&nbsp;";
								 $boton2 = "<a style='cursor:pointer;' onclick=\"GB_show('"._('Edit custom view')."','custom_view_edit.php?edit=1',460,600);\"><img src='../vulnmeter/images/pencil.png' alt='"._('Modify')."' title='"._('Modify')."' border='0'/></a>";
							  }
							  else{
								 $style='';
								 $opacidad = 'opacity:0.4;filter:alpha(opacity=40);';
								 $boton0 = "";
								 $boton1 = "<img id='save_".$i."' src='../pixmaps/disk-gray.png' alt='"._('Save Current')."' title='"._('Save')."' border='0'/>&nbsp;";
								 $boton2 = "<img src='../vulnmeter/images/pencil.png' alt='"._('Modify') ."' title='"._('Modify')."' border='0'/>";
							  }
							
							?>
							
							<td class="noborder <?=$color?>" style="padding: 0px 90px 3px 5px; text-align: left;"><a style="cursor:pointer;<?=$style?>" onclick="change_view('<?=$name?>');" id="view_<?= $name?>"><span><?=$name?></span></a></td>
							<td class="noborder <?=$color?>" style="<?=$opacidad?> padding-right:5px;text-align:right"><?=$boton0.$boton1.$boton2;?></td>
							<td class="noborder <?=$color?>" <?php if ($name == "default") { ?>style="<?=$opacidad?>"<?php } ?>><?php if ($name != "default") { ?><a style="cursor:pointer" onclick="if(confirm('<?php echo _("Are you sure?")?>')) delete_view('<?php echo $name?>')"><img src="../pixmaps/delete.gif" border="0" alt="<?php echo _("Delete") ?>" title="<?php echo _("Delete") ?>"></img></a><?php } ?></td>
						</tr>
						<? } ?>
					</table>
					</td>
				</tr>
				<tr>
					<td style='text-align: center; padding: 7px; font-size: 10px;' class="noborder">
					  <input type="button" value="<?=_("Create New View")?>" onclick="GB_show('<?=_("Create new custom view")?>','custom_view_edit.php',460,600);" class="button">
					</td>
				</tr>
			</table>
		</div>
</div>
	<?
}





function PrintFreshPage($refresh_stat_page, $stat_page_refresh_time) {
    if ($refresh_stat_page)
    //echo '<META HTTP-EQUIV="REFRESH" CONTENT="'.$stat_page_refresh_time.'; URL='. htmlspecialchars(CleanVariable($_SERVER["REQUEST_URI"], VAR_FSLASH | VAR_PERIOD | VAR_DIGIT | VAR_PUNC | VAR_LETTER), ENT_QUOTES).'">'."\n";
    echo '<META HTTP-EQUIV="REFRESH" CONTENT="' . $stat_page_refresh_time . '">';
}
function chk_select($stored_value, $current_value) {
    if (strnatcmp($stored_value, $current_value) == 0) return " SELECTED";
    else return " ";
}
function chk_check($stored_value, $current_value) {
    if ($stored_value == $current_value) return " CHECKED";
    else return " ";
}
function dispYearOptions($stored_value) {
    // Creates the years for drop down boxes
    $thisyear = date("Y");
    $options = "";
    $options = "<OPTION VALUE=' ' " . chk_select($stored_value, " ") . ">" . _DISPYEAR . "\n";
    for ($i = 1999; $i <= $thisyear; $i++) {
        $options = $options . "<OPTION VALUE='" . $i . "' " . chk_select($stored_value, $i) . ">" . $i . "\n";
    }
    $options = $options . "</SELECT>";
    return ($options);
}
function PrintBASEAdminMenuHeader() {
    $menu = "<table width='100%' border=0><tr><td width='15%'>";
    $menu = $menu . "<div class='mainheadermenu'>";
    $menu = $menu . "<table border='0' class='mainheadermenu'>";
    $menu = $menu . "<tr><td class='menuitem'>" . _USERMAN . "<br>";
    $menu = $menu . "<hr><a href='base_useradmin.php?action=list' class='menuitem'>" . _LISTU . "</a><br>";
    $menu = $menu . "<a href='base_useradmin.php?action=create' class='menuitem'>" . _CREATEU . "</a><br>";
    $menu = $menu . "<br>" . _ROLEMAN . "<br><hr>";
    $menu = $menu . "<a href='base_roleadmin.php?action=list' class='menuitem'>" . _LISTR . "</a><br>";
    $menu = $menu . "<a href='base_roleadmin.php?action=create' class='menuitem'>" . _CREATER . "</a><br>";
    $menu = $menu . "</td></tr></table></div></td><td>";
    echo ($menu);
}
function PrintBASEAdminMenuFooter() {
    $footer = "</td></tr></table>";
    echo ($footer);
}
function PrintBASEHelpLink($target) {
    /*
    This function will accept a target variable which will point to
    an anchor in the base_help.php file.  It will output a help icon
    that will link to that target in a new window.
    */
}
?>
