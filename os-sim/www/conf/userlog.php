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
* - submit()
* Classes list:
*/
require_once ('classes/Session.inc');
Session::logcheck("MenuConfiguration", "ConfigurationUserActionLog");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?php
echo gettext("User logging Configuration"); ?> </title>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
</head>

<body>

<?php
require_once ('ossim_db.inc');
require_once ('classes/Log_config.inc');
require_once ('classes/Security.inc');
include ("../hmenu.php");
/* connect to db */
$db = new ossim_db();
$conn = $db->connect();
?>

<form method="POST" action="<?php
echo $_SERVER["SCRIPT_NAME"] ?>" />
 <table align=center>


<?php
function submit($conn) {
?>
    <tr>
      <td colspan="3">
        <input type="submit" name="update" class="button" style="font-size:12px"
            value=" <?php
    echo gettext("Update configuration"); ?> " />
      </td>
    </tr>
<?php
    if (POST('update')) {
        for ($i = 1; $i <= POST('nconfs'); $i++) {
            if (POST("value_$i") == 'on') {
                Log_config::update_log($conn, $i, '1');
            } else {
                Log_config::update_log($conn, $i, '0');
            }
        }
    }
} // submit

?>

<?php
submit($conn);
?>
<tr>
    <!-- <th>#</th> -->
    <th><?php
echo gettext("Action description"); ?></th>
    <th>#</th>
</tr>

<?php
$max = 0;
if ($log_conf_list = Log_config::get_list($conn, "ORDER BY descr")) {
    foreach($log_conf_list as $log_conf) {
?>
	        
       <tr>
	   <!-- <td><?php
        echo $log_conf->get_code(); ?></td> -->
	   <td><?php
        echo preg_replace('|%.*?%|', " ", $log_conf->get_descr()); ?></td>

            <?php
        $input = "<input type=CHECKBOX
            name=\"value_" . $log_conf->get_code() . "\"";
        if ($log_conf->get_log()) {
            $input.= "CHECKED >";
        } else {
            $input.= ">";
        }
?>

	   <td><?php
        echo $input; ?></td>
	   </tr>
<?php
        $input = "";
        $max = max($max, $log_conf->get_code());
    }
}
?>

<?php
submit($conn); ?>    
 </table> 
<input type="hidden" name="nconfs" value="<?php
echo $max ?>" />
</form>
         
<?php
$db->close($conn);
?>
</body>
</html>

