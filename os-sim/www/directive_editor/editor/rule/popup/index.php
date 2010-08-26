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
$top = GET('top');
ossim_valid($top, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("top"));
if (ossim_error()) {
    die(ossim_error());
}
if ($top == 'plugin_id') {
    $plugin_id = $_GET['plugin_id'];
    $variables = '?plugin_id=' . $plugin_id;
} else if ($top == 'plugin_sid') {
    $plugin_id = $_GET['plugin_id'];
    $plugin_sid = $_GET['plugin_sid'];
    $plugin_sid_list = $_GET['plugin_sid_list'];
    $variables = '?plugin_id=' . $plugin_id;
    $variables.= '&plugin_sid=' . $plugin_sid;
    $variables.= '&plugin_sid_list=' . $plugin_sid_list;
} elseif ($top == 'from') {
    $from = $_GET['from'];
    $from_list = $_GET['from_list'];
    $variables = '?from=' . $from;
    $variables.= '&from_list=' . $from_list;
} elseif ($top == 'to') {
    $to = $_GET['to'];
    $to_list = $_GET['to_list'];
    $variables = '?to=' . $to;
    $variables.= '&to_list=' . $to_list;
} elseif ($top == 'sensor') {
    $sensor = $_GET['sensor'];
    $sensor_list = $_GET['sensor_list'];
    $variables = '?sensor=' . $sensor;
    $variables.= '&sensor_list=' . $sensor_list;
} else {
    $variables = '';
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
	<frameset rows="100%,60">
		<frame src="top/<?php
echo $top; ?>.php<?php
echo $variables; ?>" name="top" frameborder="0">
		<frame src="bottom_loading.php" name="bottom" frameborder="0" scrolling="no">
	</frameset>
</html>
