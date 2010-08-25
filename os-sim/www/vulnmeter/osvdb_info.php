<?
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

require_once('ossim_conf.inc');
require_once ('classes/Session.inc');
Session::logcheck("MenuEvents", "EventsVulnerabilities");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
  <title> <?php
echo gettext("Vulnmeter"); ?> </title>
<!--  <meta http-equiv="refresh" content="3"> -->
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>
<?

$scriptid = $_GET["scriptid"];

ossim_valid($scriptid, OSS_DIGIT, 'illegal:' . _("scriptid"));
if (ossim_error()) {
    die(_("Invalid Parameter scriptid"));
}

$path_conf = $GLOBALS["CONF"];

$db = new ossim_db();
$dbconn = $db->connect();
$dbconn->Execute('use osvdb');

// test if table exits
$old=1;
$query = "show tables";
$result = $dbconn->Execute($query);
while ( !$result->EOF ) {
    if ($result->fields[0]=="ext_references") $old=0;
    $result->MoveNext(); 
}
if ($old) {
    echo "<table width=\"100%\" class=\"noborder\" style=\"background:transparent;\">";
    echo "<tr><td style=\"text-align:center;padding-top:10px;\" class=\"nobborder\">"._("OSVDB ext_references table not found. Please update.")."</td></tr>";
    echo "</table></body></html>";
    exit(0);
}
//

$cve_references = array();
$bugtraq_references = array();

$dbconn->Execute('use ossim');
$query = "select cve_id, bugtraq_id from vuln_nessus_plugins
            where id=$scriptid";
//echo $query;
$result = $dbconn->Execute($query);

$cve_references = explode(", ",str_replace("CVE-", "",$result->fields['cve_id']));
$bugtraq_references = explode(", ",$result->fields['bugtraq_id']);

$cve_data = "";
foreach ($cve_references as $cve) {
    $cve_data = $cve_data."'".$cve."',";
}
$cve_data = substr($cve_data,0,strlen($cve_data)-1);

$bugtraq_data = "";
foreach ($bugtraq_references as $bug) {
    $bugtraq_data = $bugtraq_data."'".$bug."',";
}
$bugtraq_data = substr($bugtraq_data,0,strlen($bugtraq_data)-1); 

//var_dump($cve_references);
//var_dump($bugtraq_references);

if ($bugtraq_data == "")  $bugtraq_data = "0";
if ($cve_data == "")  $cve_data = "0";

$query = "select distinct(vulnerability_id) from osvdb.ext_references where (ext_reference_type_id = 3 and value in ($cve_data)) or (ext_reference_type_id = 5 and value in ($bugtraq_data))";
//print $query;
$result = $dbconn->Execute($query);

$vulns_ids = "";
while ( !$result->EOF ) {
    $vulns_ids = $vulns_ids."'".$result->fields['vulnerability_id']."',";
    $result->MoveNext(); 
}

$vulns_ids = substr($vulns_ids,0,strlen($vulns_ids)-1); 

if ($vulns_ids=="") $vulns_ids = "0";

$query = "select id, description, solution from osvdb.vulnerabilities where id in ($vulns_ids)";
//print $query;
$result = $dbconn->Execute($query);

$desc = ($result->fields['short_description']!="") ? $result->fields['short_description'] : (($result->fields['description']!="") ? $result->fields['description'] : $result->fields['t_description']);

while ( !$result->EOF ) {
    echo "<table width='100%'>";
    echo "<tr><th style=\"text-align:left;\" width=\"100\">"._("Description:")."</th>";
    if ($desc!="")
        echo "<td style=\"text-align:left;\" width=\"700\">".$desc."</td></tr>";
    else 
        echo "<td style=\"text-align:left;\" width=\"700\">"._("Not Available")."</td></tr>";
        
    echo "<tr><th style=\"text-align:left;\" width=\"100\">"._("Solution:");"</th>";
    if ($result->fields['solution']!="")
        echo "<td style=\"text-align:left;\" width=\"700\">".$result->fields['solution']."</td></tr>";
    else 
        echo "<td style=\"text-align:left;\" width=\"700\">"._("Not Available")."</td></tr>";
        
    echo "<tr><th style=\"text-align:left;\" width=\"100\">"._("Classification:")."</th><td class=\"nobborder\" style=\"text-align:left;\">";
        echo "<table width=\"100%\" class=\"noborder\">";
        $sql_cl = "SELECT ct.name as ctname, c.longname
                    FROM osvdb.classification_types AS ct, osvdb.classifications AS c, osvdb.classification_items AS ci
                    WHERE ci.vulnerability_id = '".$result->fields['id']."' AND ci.classification_id = c.id AND c.classification_type_id = ct.id";
        
        $result_cl = $dbconn->Execute($sql_cl);
        while ( !$result_cl->EOF ) {
            echo "<tr><td style=\"text-align:left;\"><b>".$result_cl->fields['ctname'].":</b> ".$result_cl->fields['longname']."</td></tr>";
            $result_cl->MoveNext();
        }
        $result->MoveNext();
        echo "</table>";
    echo "</td></tr></table>";
    echo "<br>";
}

if ($vulns_ids=="0") {
    echo "<table width=\"100%\" class=\"noborder\" style=\"background:transparent;\">";
    echo "<tr><td style=\"text-align:center;padding-top:10px;\" class=\"nobborder\">"._("No data available")."</td></tr>";
    echo "</table>";

}
?>

</body>
</html>