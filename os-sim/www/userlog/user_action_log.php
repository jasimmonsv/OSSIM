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
Session::logcheck("MenuMonitors", "ToolsUserLog");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?=_("User action logs")?> </title>
  <meta http-equiv="refresh" content="150">
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" href="../style/style.css"/>
  <? include ("../host_report_menu.php") ?>
</head>

<body>
  
<?php
include ("../hmenu.php");
require_once 'ossim_db.inc';
require_once 'classes/Util.inc';
require_once 'classes/Alarm.inc';
require_once 'classes/Log_action.inc';
require_once 'classes/Log_config.inc';
require_once 'classes/Security.inc';
/* number of logs per page */
$ROWS = 50;
$order = GET('order');
$inf = GET('inf');
$sup = GET('sup');
$user = GET('user');
$code = GET('code');
$action = GET('action');

ossim_valid($order, OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("order"));
ossim_valid($inf, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("inf"));
ossim_valid($sup, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("order"));
ossim_valid($user, OSS_USER, OSS_NULLABLE, 'illegal:' . _("hide_closed"));
ossim_valid($code, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("hide_closed"));
ossim_valid($action, OSS_ALPHA, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("action"));
if (ossim_error()) {
    die(ossim_error());
}
/* connect to db */
$db = new ossim_db();
$conn = $db->connect();

/* delete logs*/
if($action==_("Delete All") && $_SESSION['_user']=="admin"){
    Log_action::delete_by_user_code($conn, $user, $code);
}
else if($action==_("Delete Selected") && $_SESSION['_user']=="admin"){
    foreach ($_GET as $key => $value){
        if(preg_match('/\|/', $key)) {
            $tmp = array();
            $tmp = explode("|", $key);
            Log_action::delete_by_date_info($conn,str_replace("#", " ",$tmp[0]),str_replace("_", " ",$tmp[1]));
        }
    }
}
if (empty($order)) $order = "date DESC";
if (empty($inf)) $inf = 0;
if (empty($sup)) $sup = $ROWS;
if ($_SESSION['_user']=="admin") {
?>

    <!-- filter -->
	
    <form name="logfilter" method="GET" action="<?php
echo $_SERVER["SCRIPT_NAME"]
?>">
    <table align="center">
      <tr colspan="3">
        <th colspan="2"> <?php
echo gettext("Filter"); ?> </th>
      </tr>
      <tr>
      <td class="nobborder" style="text-align:center;">
        <?php
echo gettext("User"); ?>
      </td>
      <td class="nobborder" style="text-align:center;">
         <?php
echo gettext("Action"); ?>
      </td>
      </tr>
      <tr>
      <td class="nobborder">
        <select name="user" onChange="document.forms['logfilter'].submit()">
        <?php
require_once ('classes/Session.inc');
?>
                <option <?php
if ("" == $user) echo " selected " ?>
                 value=""><?php echo _("All");?></option>"; ?>
        <?php
if ($session_list = Session::get_list($conn, "ORDER BY login")) {
    foreach($session_list as $session) {
        $login = $session->get_login();
?>
                 <option  <?php
        if ($login == $user) echo " selected "; ?>
                  value="<?php
        echo $login; ?>"><?php
        echo $login; ?>
                </option>                
        <?php
    }
}
?>
        </select>
      </td>
      <td class="nobborder">
        <select name="code" onChange="document.forms['logfilter'].submit()">
            <option <?php
if ("" == $code) echo " selected " ?>
                 value=""><?php echo _("All");?></option>"; ?>
        <?php
if ($code_list = Log_config::get_list($conn, "ORDER BY descr")) {
    foreach($code_list as $code_log) {
        $code_aux = $code_log->get_code();
?>
                 <option  <?php
        if ($code_aux == $code) echo " selected "; ?>
                  value="<?php
        echo $code_aux; ?>"><?php
        echo "[" . sprintf("%02d", $code_aux) . "] " . preg_replace('|%.*?%|', " ", $code_log->get_descr()); ?>
                </option>                
        <?php
    }
}
?>
        </select>
      </td>
      </tr>  

    </table><br></form>
	
	<? } else $user = $_SESSION['_user']; ?>
    <? if ($_SESSION['_user']=="admin") { ?>
    <form  method="get" action="user_action_log.php">
        <center>
            <input type="hidden" name="user" value="<?=$user?>">
            <input type="hidden" name="code" value="<?=$code?>">
            <input class="button" name="action" type="submit" value="<?php echo _("Delete All");?>">&nbsp;&nbsp;&nbsp;
            <input class="button" name="action" type="submit" value="<?php echo _("Delete Selected");?>">
        </center><br>
    <? } ?>
        <table width="100%">
      <tr>
        <td colspan="6">
<?php
$cfilter = "";
$filter = "";
if (!empty($user)) {
    $filter = " and '$user' = log_action.login ";
}
if (!empty($code)) {
    $filter.= " and '$code' = log_action.code";
}
if ((!empty($code)) and (!empty($user))) {
    $cfilter = "where '" . $user . "' = log_action.login and
        '" . $code . "' = code";
} else {
    if (!empty($code)) {
        $cfilter = "where
           '" . $code . "' = code";
    }
    if (!empty($user)) {
        $cfilter = "where
           '" . $user . "' = login";
    }
}
/*
* prev and next buttons
*/
$inf_link = $_SERVER["SCRIPT_NAME"] . "?order=$order" . "&sup=" . ($sup - $ROWS) . "&inf=" . ($inf - $ROWS);
$sup_link = $_SERVER["SCRIPT_NAME"] . "?order=$order" . "&sup=" . ($sup + $ROWS) . "&inf=" . ($inf + $ROWS);
$count = Log_action::get_count($conn, $cfilter);
if ($inf >= $ROWS) {
    echo "<a href=\"$inf_link\">&lt;-";
    printf(gettext("Prev %d") , $ROWS);
    echo "</a>";
}
if ($sup < $count) {
    echo "&nbsp;&nbsp;(";
    printf(gettext("%d-%d of %d") , $inf, $sup, $count);
    echo ")&nbsp;&nbsp;";
    echo "<a href=\"$sup_link\">";
    printf(gettext("Next %d") , $ROWS);
    echo " -&gt;</a>";
} else {
    echo "&nbsp;&nbsp;(";
    printf(gettext("%d-%d of %d") , $inf, $count, $count);
    echo ")&nbsp;&nbsp;";
}
?>
        </td>
      </tr>
    
      <tr>
      <?if ($_SESSION['_user']=="admin") {?>
        <th>&nbsp;</th>
      <?}?>
        <th><a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php
echo ossim_db::get_order("date", $order); ?><?=(($user!="")?"&user=$user":"")?><?=(($code!="")?"&code=$code":"")?>">
        <?php
echo gettext("Date"); ?></a></th>
        <th><a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php
echo ossim_db::get_order("login", $order); ?><?=(($user!="")?"&user=$user":"")?><?=(($code!="")?"&code=$code":"")?>">
        <?php
echo gettext("User"); ?></a></th>
        <th><a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php
echo ossim_db::get_order("ipfrom", $order); ?><?=(($user!="")?"&user=$user":"")?><?=(($code!="")?"&code=$code":"")?>">
        <?php
echo gettext("Source IP"); ?></a></th>
        <th><a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php
echo ossim_db::get_order("code", $order); ?><?=(($user!="")?"&user=$user":"")?><?=(($code!="")?"&code=$code":"")?>">
        <?php
echo gettext("Code"); ?></a></th>
        <th><a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php
echo ossim_db::get_order("info", $order); ?><?=(($user!="")?"&user=$user":"")?><?=(($code!="")?"&code=$code":"")?>">
        <?php
echo gettext("Action"); ?></a></th>
      </tr>

<?php
$time_start = time();
if ($log_list = Log_action::get_list($conn, $filter, "ORDER by $order", $inf, $sup)) {
    foreach($log_list as $log) {
?>
        <tr>
        <? if ($_SESSION['_user']=="admin") {
            $tmp=str_replace(" ","#",$log->get_date());?>
            <td><input type="checkbox" name="<?=$tmp."|".$log->get_info()?>" value="yes"></td>
        <? } ?>
        <td><?php
        echo $log->get_date(); ?>         
        </td>
        
        <td><?php
        echo $log->get_login(); ?>         
        </td>
        
        <td><div id="<?php echo $log->get_from();?>;<?php echo $log->get_from(); ?>" class="HostReportMenu" style="display:inline"><?php
        echo $log->get_from(); ?>         
        </div></td>
        
        <td><?php
        echo $log->get_code(); ?>         
        </td>
        
        <td><?php
        echo (preg_match('/^[A-Fa-f0-9]{32}$/',$log->get_info())) ? preg_replace('/./','*',$log->get_info()) : $log->get_info(); ?>         
        </td>
        
      </td>
      </tr>
<?php
    } /* foreach alarm_list */
?>
      <tr>
        <td colspan="<?=_(($_SESSION['_user']=="admin") ? "6" : "5")?>">
<?php
    if ($inf >= $ROWS) {
        echo "<a href=\"$inf_link\">&lt;-";
        printf(gettext("Prev %d") , $ROWS);
        echo "</a>";
    }
    if ($sup < $count) {
        echo "&nbsp;&nbsp;(";
        printf(gettext("%d-%d of %d") , $inf, $sup, $count);
        echo ")&nbsp;&nbsp;";
        echo "<a href=\"$sup_link\">";
        printf(gettext("Next %d") , $ROWS);
        echo " -&gt;</a>";
    } else {
        echo "&nbsp;&nbsp;(";
        printf(gettext("%d-%d of %d") , $inf, $count, $count);
        echo ")&nbsp;&nbsp;";
    }
?>

        </td>
      </tr>
<?php
} /* if alarm_list */
?>
        </table>
<?if ($_SESSION['_user']=="admin") {?>
    </form>
<?}?>
    

<?php
$time_load = time() - $time_start;
echo "[ " . gettext("Page loaded in") . " $time_load " . gettext("seconds") . " ]";
$db->close($conn);
?>

</body>
</html>
