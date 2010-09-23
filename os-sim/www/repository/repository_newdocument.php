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
require_once ("classes/Repository.inc");
// menu authentication
require_once ('classes/Session.inc');
Session::logcheck("MenuIncidents", "Osvdb");
$user = $_SESSION["_user"];
$full = intval(GET('full'));
require_once ("ossim_db.inc");
$db = new ossim_db();
$conn = $db->connect();

$vuser = POST('user');
$ventity = POST('entity');
ossim_valid($vuser, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_SPACE, 'illegal:' . _("User"));
ossim_valid($ventity, OSS_NULLABLE, OSS_DIGIT, OSS_ALPHA, 'illegal:' . _("Entity"));

if (POST('title') != "" && POST('doctext') != "") {
    // Get a list of nets from db
    if($vuser != "none" && $vuser != "") $user = $vuser;
    if($ventity != "none" && $ventity != "") $user = $ventity;
    $id_inserted = Repository::insert($conn, POST('title') , POST('doctext') , POST('keywords') , $user);
?>
<html>
<head>
  <title> <?php
    echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>

<body style="margin:0">
<table cellpadding=0 cellspacing=2 border=0 width="100%" class="transparent">
    <? if ($full!=1) { ?>
	<tr>
		<th><?=_("NEW DOCUMENT")?></th>
	</tr>
    <? } ?>
	<tr>
		<td class="center"><?=_("Document inserted with id")?>: <?php echo $id_inserted ?></td>
	</tr>
	<tr><td class="center"><?=_("Do you want to attach a document file?")?> <input type="button" class="btn" onclick="document.location.href='repository_attachment.php?id_document=<?php echo $id_inserted ?>'" value="<?=_("YES")?>">&nbsp;<input class="btn" type="button" onclick="parent.document.location.href='index.php'" value="<?=_("NO")?>"></td></tr>
</table>
<?php
} else { ?>
<html>
<head>
  <title> <?php
    echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <link rel="stylesheet" type="text/css" href="../style/jquery.wysiwyg.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/jquery.wysiwyg.js"></script>
  <script type="text/javascript">
	$(document).ready(function() {
		$('#textarea').wysiwyg({
			css : { fontFamily: 'Arial, Tahoma', fontSize : '13px'}
		});
	});
    function switch_user(select) {
        if(select=='entity' && $('#entity').val()!='none'){
            $('#user').val('none');
        }
        else if (select=='user' && $('#user').val()!='none'){
            $('#entity').val('none');
        }
    }
  </script>
</head>

<body style="margin:0">
<table cellpadding=0 cellspacing=2 border=0 width="100%" <? if ($full==1) echo "class='transparent'" ?>>
    <? if ($full!=1) { ?>
	<tr>
		<th class="kdb"><?=_("NEW DOCUMENT")?></th>
	</tr>
    <? } ?>
	<tr>
		<td class="nobborder">
			<!-- repository insert form -->
			<form name="repository_insert_form?full=<?=$full?>" method="POST" action="<?php echo $_SERVER['SCRIPT_NAME'] ?>" enctype="multipart/form-data">
			<table cellpadding=0 cellspacing=2 border=0 class="noborder" width="100%">
				<tr>
					<td class="nobborder" style="padding-left:5px"><b><?=_("Title")?>:</b></td>
				</tr>
				<tr>
					<td class="nobborder" style="padding-left:5px"><input type="text" name="title" style="width:<?= ($full==1) ? "98%" : "473px" ?>" value="<?php echo POST('title') ?>"></td>
				</tr>
				<tr>
					<td class="nobborder" style="padding-left:5px"><b><?php echo _("Text") ?>:</b></td>
				</tr>
				<tr>
					<td class="nobborder" style="padding-left:5px">
						<textarea id="textarea" name="doctext" rows="4" style="width:<?= ($full==1) ? "98%" : "460px" ?>"><?php echo POST('doctext') ?></textarea>
					</td>
				</tr>
				
				<tr>
					<td class="nobborder" style="padding-left:5px"><b><?php echo _("Keywords") ?>:</b></td>
				</tr>
				<tr>
					<td class="nobborder" style="padding-left:5px">
						<textarea name="keywords"  style="width:<?= ($full==1) ? "98%" : "473px" ?>"><?php echo POST('keywords') ?></textarea>
					</td>
				</tr>
                <?
                $conf = $GLOBALS["CONF"];
                $version = $conf->get_conf("ossim_server_version", FALSE);
                if(Session::am_i_admin()) {?>
                <tr>
                    <td class="nobborder">
                        <table cellpadding=0 cellspacing=2 border=0 class="noborder" width="100%">
                        <tr><td class="nobborder" width="160"><?=_("Make this document visible for:")?></td>
                        <td class="nobborder" style="text-align:left;">
                        <table class="noborder">
                         <tr><td class="nobborder"><?
                          echo _("User:")."&nbsp;";
                          ?>
                          </td><td style="text-align:left;" class="nobborder">
                          <select name="user" id="user" onchange="switch_user('user');return false;">
                          <option value="none"><?=_("Not assign")?></option>
                          <?
                           $users = Session::get_list($conn);
                            foreach ($users as $user) {?>
                                <option value="<?=$user->get_login()?>"><?=$user->get_login()?></option>
                          <?}
                          ?>
                          </select>
                          <?
                          if(preg_match("/pro|demo/i",$version)){?>
                              <tr><td class="nobborder">&nbsp;</td><td class="nobborder"><?=_("OR")?></td></tr>
                              <tr><td class="nobborder"><?=_("Entity:")?></td><td class="nobborder">
                              <?
                              $entities_types_aux = Acl::get_entities_types($conn);
                              $entities_types = array();

                              foreach ($entities_types_aux as $etype) { 
                                $entities_types[$etype['id']] = $etype;
                              }
                              list($entities_all,$num_entities) = Acl::get_entities($conn);
                              ?>
                              <select name="entity" id="entity" onchange="switch_user('entity');return false;">
                                <option value="none"><?=_("Not assign")?></option>
                                <?
                                foreach ($entities_all as $entity) {?>
                                    <option value="<?=$entity["id"]?>"><?=$entity["name"]." [".$entities_types[$entity["type"]]["name"]."]"?></option>
                                <?}?>
                              </select>
                              </td>
                              </tr>
                          <?}?>
                          </td></tr>
                        </table>
                        </td>
                        </tr>
                    </table>
                    </td>
                </tr>
                <?}
                else if(preg_match("/pro|demo/i",$version)) {
                    if(Acl::am_i_proadmin()) {?>
                    <tr>
                        <td class="nobborder">
                            <table cellpadding=0 cellspacing=2 border=0 class="noborder" width="100%">
                            <tr><td class="nobborder" width="160"><?=_("Make this document visible for:")?></td>
                            <td class="nobborder" style="text-align:left;">
                            <table class="noborder">
                             <tr><td class="nobborder"><?
                              echo _("User:")."&nbsp;";
                              ?>
                              </td><td style="text-align:left;" class="nobborder">
                              <select name="user" id="user" onchange="switch_user('user');return false;">
                              <option value="none"><?=_("Not assign")?></option>
                              <?
                                $users = Acl::get_my_users($conn,Session::get_session_user());
                                foreach ($users as $user){?>
                                    <option value="<?=$user["login"]?>"><?=$user["login"]?></option>
                                <?}
                              ?>
                              </select>
                                  <tr><td class="nobborder">&nbsp;</td><td class="nobborder"><?=_("OR")?></td></tr>
                                  <tr><td class="nobborder"><?=_("Entity:")?></td><td class="nobborder">
                                  <?
                                  $entities_types_aux = Acl::get_entities_types($conn);
                                  $entities_types = array();

                                  foreach ($entities_types_aux as $etype) { 
                                    $entities_types[$etype['id']] = $etype;
                                  }
                                  list($entities_admin,$num) = Acl::get_entities_admin($conn,Session::get_session_user());
                                  list($entities_all,$num_entities) = Acl::get_entities($conn);
                                  $entities_list = array_keys($entities_admin);
                                  ?>
                                  <select name="entity" id="entity" onchange="switch_user('entity');return false;">
                                    <option value="none"><?=_("Not assign")?></option>
                                    <?
                                    foreach ($entities_all as $entity) if(in_array($entity["id"], $entities_list)) {?>
                                        <option value="<?=$entity["id"]?>"><?=$entity["name"]." [".$entities_types[$entity["type"]]["name"]."]"?></option>
                                    <?}?>
                                  </select>
                                  </td>
                                  </tr>
                            </table>
                            </td>
                            </tr>
                        </table>
                        </td>
                    </tr>
                    <?
                    }
                }
                ?> 
				<tr><td class="nobborder" style="padding-left:5px;text-align:center"><input class="btn" type="submit" value="<?php echo _("Save") ?>"></td></tr>
			</table>
			</form>
			<!-- end of repository insert form -->
		</td>
	</tr>
</table>
<?php
} ?>
</body>
</html>
<?
$db->close($conn);
?>
