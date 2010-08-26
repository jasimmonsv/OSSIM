<?php
/*****************************************************************************
*
*   Copyright (c) 2007-2010 AlienVault
*   All rights reserved.
*
****************************************************************************/
/**
* Class and Function List:
* Function list:
* Classes list:
*/
require_once ('classes/Session.inc');
require_once ('ossim_conf.inc');

$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);
$opensource = (!preg_match("/pro|demo/i",$version)) ? true : false;
$withusers = intval(GET('users'));
$_SESSION["_with_users"] = $withusers;

if (!$opensource) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?php echo gettext("OSSIM Framework"); ?> - Asset Structure</title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <link rel="stylesheet" type="text/css" href="../style/greybox.css"/>  
  <link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
  <link rel="stylesheet" type="text/css" href="../style/tree.css" />
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
  <script type="text/javascript" src="../js/jquery.tmpl.1.1.1.js"></script>
  <script type="text/javascript" src="../js/jquery.dynatree.js"></script>
  <script type="text/javascript" src="../js/greybox.js"></script>  
  <script type="text/javascript">
    $(document).ready(function(){
        $("#atree").dynatree({
            initAjax: { url: "entities_tree.php" },
            clickFolderMode: 2,
            onActivate: function(dtnode) {
                if(dtnode.data.url!='' && typeof(dtnode.data.url)!='undefined') {
                    GB_edit(dtnode.data.url+'&withoutmenu=1');
                }
            },
            onDeactivate: function(dtnode) {},
            onLazyRead: function(dtnode){
                dtnode.appendAjax({
                    url: "entities_tree.php",
                    data: {key: dtnode.data.key}
                });
            }
        });
    });
    GB_TYPE = 'w';
    function GB_onclose() {
    }
    function GB_edit(url) {
        GB_show("Edit Asset",url,"80%","80%");
        return false;
    }
</script>
</head>
<body>
<? include("../hmenu.php"); ?>
<table border="0" width="750" class="noborder" align="center" cellspacing="0" cellpadding="0">
    <tr>
        <td class="headerpr"><?=_("Asset Structure")?></td>
    </tr>
</table>
<table border="0" width="750" align="center" cellspacing="0" cellpadding="0">
    <tr>
        <td class="nobborder">
  			<div id="atree" style="text-align:left;width:98%;padding:8px 8px 0px 8px"></div>
        </td>
    </tr>
    <tr>
        <td class="nobborder" style="padding:3px 0px 5px 5px;background-color:transparent">
  			<a href="?users=<?=($withusers) ? "0" : "1"?>"><img src="../pixmaps/arrow_green.gif" align="absmiddle" border="0"><b><?=($withusers) ? _("Without Users") : _("With Users")?></b></a>
        </td>
    </tr>
</table>

</body>
</html>
<?
}
?>
