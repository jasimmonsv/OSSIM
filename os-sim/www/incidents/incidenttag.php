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
/*
* Manage TAGS from this a single script. Different states are
* handled by the $_GET['action'] var. Possible states:
*
* list (default): List TAGs
* new1step: Form for inserting tag
* new2step: Values validation and insertion in db
* delete: Validation and deletion from the db
* mod1step: Form for updating a tag
* mod2step: Values validation and update db
*
*/
require_once 'classes/Security.inc';
require_once 'classes/Session.inc';
Session::logcheck("MenuIncidents", "IncidentsTags");
require_once 'ossim_db.inc';
require_once 'classes/Incident_tag.inc';
// Avoid the browser resubmit POST data stuff
if (GET('redirect')) {
    header('Location: ' . $_SERVER['SCRIPT_NAME']);
    exit;
}
$db = new ossim_db();
$conn = $db->connect();
$tag = new Incident_tag($conn);
$vals = array(
    'id' => array(
        OSS_DIGIT,
        OSS_NULLABLE,
        'error:' . _("ID not valid")
    ) ,
    'name' => array(
        OSS_LETTER,
        OSS_PUNC,
        OSS_NULLABLE,
        'error:' . _("<b>Name</b> required, should be only letters and underscores")
    ) ,
    'descr' => array(
        OSS_TEXT,
        OSS_NULLABLE,
        'error:' . _("<b>Description</b> required and should contain valid characters")
    )
);
$action = GET('action') ? GET('action') : 'list';
$id = GET('id');
$name = POST('name');
$descr = POST('descr');
ossim_valid($id, $vals['id']);
ossim_valid($name, $vals['name']);
ossim_valid($descr, $vals['descr']);
ossim_valid($action, $vals['action']);
if (ossim_error()) {
    die(ossim_error());
}
if (in_array($action, array(
    'new2step',
    'delete',
    'mod2step'
))) {
    switch ($action) {
        case 'new2step':
            $tag->insert($name, $descr);
            break;

        case 'delete':
            $tag->delete($id);
            break;

        case 'mod2step':
            $tag->update($id, $name, $descr);
            break;

        default:
            header('Location: ' . $_SERVER['SCRIPT_NAME'] . '?redirect=1');
            exit;
    }
}
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
include ("../hmenu.php"); ?>
<?php
/*
* FORM FOR NEW/EDIT TAG
*/
if ($action == 'new1step' || $action == 'mod1step') {
    if ($action == 'mod1step' && !ossim_error() && ossim_valid($id, $vals['id'])) {
        $f = $tag->get_list("WHERE td.id = $id");
        $name = $f[0]['name'];
        $descr = $f[0]['descr'];
    }
?>
<form method="post" action="?action=<?php echo str_replace('1', '2', $action) ?>&id=<?php echo $id ?>" name="f">
<table align="center" width="50%">
    <tr>
        <th><?php echo _("Name") ?></th>
        <td class="left"><input type="input" name="name" size="37" value="<?php echo $name ?>"></td>
    </tr>
    <tr>
        <th><?php echo _("Description") ?></th>
        <td class="left"><textarea name="descr" cols="35" rows="15"><?php echo $descr ?></textarea></td>
    </tr>
    <tr><th colspan="2" align="center">
        <input type="submit" value="<?=_("OK")?>" class="btn">&nbsp;
        <input type="button" class="btn" onClick="document.location = '<?php echo $_SERVER['SCRIPT_NAME'] ?>'" value="<?php echo _("Cancel") ?>">
    </th></tr>
</table>    
</form>
<script>document.f.name.focus();</script>
<?php
    /*
    * LIST TAGS
    */
    //} elseif ($action == 'list') {
    
} else {
?>
<table align="center" width="70%">
    <tr>
        <th><?php echo _("Id") ?></th>
        <th><?php echo _("Name") ?></th>
        <th><?php echo _("Description") ?></th>
        <th><?php echo _("Actions") ?></th>
    </tr>
<?php
    foreach($tag->get_list() as $f) { ?>
    <?php //printr($f); exit;
         ?>
    <tr>
        <td valign="top"><b><?php echo $f['id'] ?></b></td>
        <td valign="top" style="text-align: left;" NOWRAP><?php echo htm($f['name']) ?></td>
        <td valign="top" style="text-align: left;"><?php echo htm($f['descr']) ?></td>
        <td NOWRAP> 
<?php
        if (($f['id'] != '65001') && ($f['id'] != '65002')) { ?>
	    [<a href="?action=mod1step&id=<?php echo $f['id'] ?>"><?=_("Modify")?></a>]&nbsp;
            [<a href="?action=delete&id=<?php echo $f['id'] ?>"
              <?php
            if ($f['num'] >= 1) { ?>
              onClick="return confirm('<?php
                printf(_("There are %d incidents using this tag. Do you really want to delete it?") , $f['num']) ?>');" 
              <?php
            } ?>
             ><?=_("Delete")?></a>]
	   <?php
        } ?>
	   &nbsp;
        </td>
    </tr>
<?php
    } ?>
    <tr><th colspan="4" align="center">
        <a href="?action=new1step"><?php echo _("Add new tag") ?></a>
    </th></tr>
</table>

<?php
}
?>
