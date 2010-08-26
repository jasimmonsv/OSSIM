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
/*******************************************************************************
** Copyright (C) 2008 Alienvault
********************************************************************************
** Authors:
********************************************************************************
** Jaime Blasci <jaime.blasco@alienvault.com>
**
********************************************************************************
*/
include ("base_conf.php");
include ("$BASE_path/includes/base_constants.inc.php");
include ("$BASE_path/includes/base_include.inc.php");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<TITLE>Forensics Console : Alert</TITLE><LINK rel="stylesheet" type="text/css" HREF="styles/ossim_style.css">
</head>
<body>
<? include ("../hmenu.php"); ?>
<div style="border:1px solid #AAAAAA;line-height:24px;width:100%;text-align:center;background:url('../pixmaps/fondo_col.gif') 50% 50% repeat-x;color:#222222;font-size:12px;font-weight:bold">&nbsp;Shellcode Analysis </div>
<?php
// Check role out and redirect if needed -- Kevin
$roleneeded = 10000;
$BUser = new BaseUser();
if (($BUser->hasRole($roleneeded) == 0) && ($Use_Auth_System == 1)) {
    base_header("Location: " . $BASE_urlpath . "/index.php");
    exit();
}
$cid = ImportHTTPVar("cid", VAR_DIGIT);
$sid = ImportHTTPVar("sid", VAR_DIGIT);
//print $cid."<br>";
//print $sid."<br>";
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);
/* Get the Payload from the database: */
$sql2 = "SELECT data_payload FROM extra_data WHERE sid='" . $sid . "' AND cid='" . $cid . "'";
$result2 = $db->baseExecute($sql2);
$myrow2 = $result2->baseFetchRow();
$result2->baseFreeRows();
/* get encoding information for payload */
/* 0 == hex, 1 == base64, 2 == ascii;   */
$sql3 = 'SELECT encoding FROM sensor WHERE sid=' . $sid;
$result3 = $db->baseExecute($sql3);
$myrow3 = $result3->baseFetchRow();
$result3->baseFreeRows();
//print $myrow2[0]."<br>";
$payload = str_replace("\n", "", $myrow2[0]);
$len = strlen($payload);
$counter = 0;
$tmp = tempnam("/tmp", "bin");
$fh = fopen($tmp, "w");
for ($i = 0; $i < ($len + 32); $i+= 2) {
    $counter++;
    if ($counter > ($len / 2)) {
        break;
    }
    $byte_hex_representation = ($payload[$i] . $payload[$i + 1]);
    //echo chr(hexdec($byte_hex_representation));
    fwrite($fh, chr(hexdec($byte_hex_representation)));
    //$bin = $bin + chr(hexdec($byte_hex_representation));
    
}
fclose($fh);
echo "<br>";
$salida = shell_exec('/opt/libemu/bin/sctest -Sgs 1000000000 < ' . $tmp);
$types = array(
    "int",
    "short",
    "long",
    "float",
    "double",
    "char"
);
//$salida = shell_exec('cat test1.txt');
$lines = split("\n", $salida);
//echo $lines[1];
if (preg_match("/failed/i", $lines[1])) {
    echo "<b1>The Shellcode couldn't be analyzed</b1>";
} else {
    print "<p><div class=code><pre>";
    for ($i = 1; $i < count($lines); $i++) {
        $l = $lines[$i];
        $l = str_replace("host=", "<b><font color = \"red\">host=</font></b>", $l);
        $l = str_replace("port=", "<b><font color = \"red\">port=</font></b>", $l);
        foreach($types as $t) {
            $l = str_replace($t, "<b><font color = \"blue\">" . $t . "</font></b>", $l);
        }
        print $l . "<br>";
    }
    print "</pre></div></p>";
}
//$salida = str_replace("\n", "<br>",$salida);
//$salida = str_replace("\t", "	", $salida);
//print $salida;
//$command = "cat";
//system($command);
$tmp2 = tempnam("/tmp", "dot");
$salida2 = shell_exec('/opt/libemu/bin/sctest -Sgs 1000000 -G ' . $tmp2 . ' < ' . $tmp);
$tmp3 = "tmp/test.svg";
$salida3 = shell_exec('dot -Tsvg ' . $tmp2 . ' -o ' . $tmp3);
echo "<a href=\"graph.php?file=$tmp3\"><center><img src=\"graphviz.png\"/><br><br><b>View Graph</b></center></a>";
?>

</body>
</html>
