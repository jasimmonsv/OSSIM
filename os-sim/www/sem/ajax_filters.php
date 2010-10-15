<?php
/*****************************************************************************
*
*    License:
*
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
require_once('classes/Security.inc');
require_once('classes/User_config.inc');
require_once ('ossim_db.inc');
$db_aux = new ossim_db();
$conn_aux = $db_aux->connect();
$uconfig = new User_config($conn_aux);

$mode = GET('mode');
$filter_name = GET('filter_name');
$start = GET('start');
$end = GET('end');
$query = GET('query');
ossim_valid($mode, OSS_ALPHA, 'illegal:' . _("mode"));
ossim_valid($filter_name, OSS_ALPHA, OSS_DIGIT, OSS_SPACE, 'illegal:' . _("filter_name"));
ossim_valid($start, OSS_DIGIT, OSS_COLON, OSS_SCORE, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("start date"));
ossim_valid($end, OSS_DIGIT, OSS_COLON, OSS_SCORE, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("end date"));
ossim_valid($query, OSS_TEXT, OSS_NULLABLE, '[', ']', 'illegal:' . _("query"));
if (ossim_error()) {
    die(ossim_error());
}
if ($mode == "new") {
	$_SESSION['logger_filters'][$filter_name]['start_aaa'] = $start;
	$_SESSION['logger_filters'][$filter_name]['end_aaa'] = $end;
	$_SESSION['logger_filters'][$filter_name]['query'] = $query;
	$uconfig->set(Session::get_session_user(), 'logger_filters', $_SESSION['logger_filters'], 'php', 'logger');
	?>
        <input type="hidden" name="filter" id="filter" value="<?php echo $filter_name; ?>" />
        <ul>
        <? $i=0;
        foreach ($_SESSION['logger_filters'] as $name=>$attr) {
            $i++;
            ?>
            <li class="<?php if($i%2==0){ echo 'impar'; }else{ echo 'par'; } ?>" style="clear:left">
                <div style="float:left">
                    <a onclick="change_filter('<?php echo $name ?>')" href="#" id="filter_<?php echo $name ?>">
                        <?php if ($filter_name == $name){ ?><strong><?php } ?>
                            <?php echo $name ?>
                        <?php if ($filter_name == $name){ ?></strong><?php } ?>
                    </a>
                </div>
                <div style="position: absolute;right:2px;float:left;width: 40px;<?php if ($filter_name != $name){ ?>
                     opacity:0.4;filter:alpha(opacity=40)<?php } ?>">
                    <?php if ($filter_name == $name){ ?>
                    <a href="#" onclick="save_filter('<?php echo $name ?>')" alt="<?=_("Update")?>" title="<?=_("Update")?>">
                    <?php } ?>
                        <img src="../pixmaps/disk-gray.png" alt="<?=_("Update")?>" border="0" />
                    <?php if ($filter_name == $name){ ?>
                    </a>
                    <a href="#" onclick="delete_filter('<?php echo $name ?>')" alt="<?=_("Delete")?>" title="<?=_("Delete")?>">
                    <?php } ?>
                        <img src="../vulnmeter/images/delete.gif" alt="<?=_("Delete")?>" border="0" />
                    <?php if ($filter_name == $name){ ?>
                    </a>
                    <?php } ?>
                </div>
            </li>
        <? } ?>
        </ul>
	<?
}
if ($mode == "load") {
	$filters = $uconfig->get(Session::get_session_user(), 'logger_filters', 'php', "logger");
	$filter = $filters[$filter_name];
        ?>
        <input type="hidden" name="filter_data" id="filter_data" value="##<?php echo $filter['start_aaa']."##".$filter['end_aaa']."##".$filter['query']; ?>##" />
        <input type="hidden" name="filter" id="filter" value="<?php echo $filter_name; ?>" />
        <ul>
        <? $i=0;
        foreach ($_SESSION['logger_filters'] as $name=>$attr) {
            $i++;
            ?>
            <li class="<?php if($i%2==0){ echo 'impar'; }else{ echo 'par'; } ?>" style="clear:left">
                <div style="float:left">
                    <a onclick="change_filter('<?php echo $name ?>')" href="#" id="filter_<?php echo $name ?>">
                        <?php if ($filter_name == $name){ ?><strong><?php } ?>
                            <?php echo $name ?>
                        <?php if ($filter_name == $name){ ?></strong><?php } ?>
                    </a>
                </div>
                <div style="position: absolute;right:2px;float:left;width: 40px;<?php if ($filter_name != $name){ ?>
                     opacity:0.4;filter:alpha(opacity=40)<?php } ?>">
                    <?php if ($filter_name == $name){ ?>
                    <a href="#" onclick="save_filter('<?php echo $name ?>')" alt="<?=_("Update")?>" title="<?=_("Update")?>">
                    <?php } ?>
                        <img src="../pixmaps/disk-gray.png" alt="<?=_("Update")?>" border="0" />
                    <?php if ($filter_name == $name){ ?>
                    </a>
                    <a href="#" onclick="delete_filter('<?php echo $name ?>')" alt="<?=_("Delete")?>" title="<?=_("Delete")?>">
                    <?php } ?>
                        <img src="../vulnmeter/images/delete.gif" alt="<?=_("Delete")?>" border="0" />
                    <?php if ($filter_name == $name){ ?>
                    </a>
                    <?php } ?>
                </div>
            </li>
        <? } ?>
        </ul>
        <?php
}
if ($mode == "delete") {
	unset($_SESSION['logger_filters'][$filter_name]);
	$uconfig->set(Session::get_session_user(), 'logger_filters', $_SESSION['logger_filters'], 'php', 'logger');
	?>
        <input type="hidden" name="filter" id="filter" value="default" />
        <ul>
        <? $i=0;
            foreach ($_SESSION['logger_filters'] as $name=>$attr) {
            $i++;    ?>
            <li class="<?php if($i%2==0){ echo 'impar'; }else{ echo 'par'; } ?>" style="clear:left">
                <div style="float:left">
                    <a onclick="change_filter('<?php echo $name ?>')" href="#" id="filter_<?php echo $name ?>">
                            <?php echo $name ?>
                    </a>
                </div>
                <div style="position: absolute;right:2px;float:left;width: 40px;opacity:0.4;filter:alpha(opacity=40)">
                        <img src="../pixmaps/disk-gray.png" alt="<?=_("Update")?>" border="0" />
                        <img src="../vulnmeter/images/delete.gif" alt="<?=_("Delete")?>" border="0" />
                </div>
            </li>
        <? } ?>
        </ul>
	<?
}
?>