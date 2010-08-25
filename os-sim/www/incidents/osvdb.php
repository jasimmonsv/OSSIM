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
require_once 'classes/Session.inc';
require_once 'classes/Security.inc';
Session::logcheck("MenuIncidents", "Osvdb");
require_once 'classes/Osvdb.inc';
require_once 'ossim_db.inc';
$db = new ossim_db();
$conn = $db->osvdb_connect();
$osvdb_id = intval(GET("id"));
$osvdb = Osvdb::get_osvdb($conn, $osvdb_id);
?>

<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>
<table align="center" width="100%">
	<tr>
		<th><center><h2><?php echo $osvdb->get_title() ?></h2></center></th>
	</tr>
</table>

<br>
<b>OSVDB ID:</b> <?php echo $osvdb->get_id() ?>
<br><br>
<b>Disclosure Date:</b> <?php echo $osvdb->get_disclosure_date() ?>
<br><br>
<b>Description:</b><br>
<?php echo $osvdb->get_description() ?>
<br><br>
<b>Technical Description:</b><br>
<?php echo $osvdb->get_technical_description() ?>
<br><br>
<?php
$classifications_list = $osvdb->get_classifications();
if (count($classifications_list) > 0) {
    echo "<b>Vulnerability Classification:</b><br><ul>";
    foreach($classifications_list as $classification) {
        echo "<li>" . $classification . "</li>";
    }
    echo "</ul><br>";
}
?>
<b>Products:</b>
<br>
<ul>
        <?php
$products_list = $osvdb->get_products();
foreach($products_list as $product) {
    echo "<li>" . $product . "</li>";
}
?>
</ul>
<br>
<b>Solution:</b><br>
<?php echo $osvdb->get_solution() ?>
<br><br>
<b>Manual Testing Notes:</b><br>
<?php echo $osvdb->get_manual_test() ?>
<br><br>
<b>External References:</b>
<br>
<ul>
        <?php
$external_refs_list = $osvdb->get_external_refs();
foreach($external_refs_list as $external_ref) {
    echo "<li>" . $external_ref . "</li>";
}
?>
</ul>
<br>
</body>
</html>
