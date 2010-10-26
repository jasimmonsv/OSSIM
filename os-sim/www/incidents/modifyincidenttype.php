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
Session::logcheck("MenuIncidents", "IncidentsTypes");
?>

<html>
<head>
  <title> <?php echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>

<h1> <?php echo gettext("Modify Action type"); ?> </h1>

<?php
require_once 'classes/Security.inc';

$options = array ("Checkbox", "Select box", "Radio button", "Map", "Slider");

$inctype_id = POST('id');
$inctype_descr = POST('descr');
$action = POST('modify');
$custom = intval(POST('custom'));

$custom_name = strtoupper(POST('custom_namef'));
$custom_old_name = strtoupper(POST('old_name'));
$custom_type = POST('custom_typef');
$custom_options = strtoupper(POST('custom_optionsf'));
$custom_required = strtoupper(POST('custom_requiredf'));

if ( $action=="modify" )
	;
else if ( $action=="delete" )
	ossim_valid($custom_name, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SCORE, 'illegal:' . _("Custom field name"));
else if ( $action=="add" ||  $action=="modify_ct")
{
	ossim_valid($custom_name, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SCORE, 'illegal:' . _("Custom field name"));
	ossim_valid($custom_type, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SCORE, 'illegal:' . _("Custom field type"));
	ossim_valid($custom_options, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SCORE,  OSS_NULLABLE, OSS_NL, ";", 'illegal:' . _("Custom field options"));
	ossim_valid($custom_required, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Required Field"));
	if ( $action=="modify_ct" )
		ossim_valid($custom_old_name, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SCORE, 'illegal:' . _("Custom field name"));
}
else
	die(ossim_error('illegal:' . _("action")));


ossim_valid($inctype_descr, OSS_NULLABLE, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_AT, 'illegal:' . _("Description"));
ossim_valid($inctype_id, OSS_ALPHA, OSS_SPACE, OSS_PUNC, 'illegal:' . _("id"));
ossim_valid($action, OSS_ALPHA, OSS_SCORE, 'illegal:' . _("action"));

if (ossim_error()) {
    die(ossim_error());
}
if (!Session::am_i_admin()) {
    require_once ("ossim_error.inc");
    $error = new OssimError();
    $error->display("ONLY_ADMIN");
}
require_once ('ossim_db.inc');
require_once ('classes/Incident_type.inc');
$db = new ossim_db();
$conn = $db->connect();

if ($action=="modify") 
{
	Incident_type::update($conn, $inctype_id, $inctype_descr,(($custom==1) ? "custom" : ""));
	$location = "incidenttype.php";
} 
elseif ($action=="modify_ct") 
{
	Incident_type::update_custom($conn, $custom_name, $custom_type, $custom_options, $custom_required, $inctype_id, $custom_old_name);
	$location = "modifyincidenttypeform.php?id=".urlencode($inctype_id);
}
elseif ($action=="add" && trim($custom_name)!="" && trim($custom_type)!="") 
{
	if ( (in_array($custom_type, $options) && $custom_options !='' ) || !in_array($custom_type, $options) )
	{
		$params = array($inctype_id, $custom_name, $custom_type, $custom_options, $custom_required);
		Incident_type::insert_custom($conn, $params);
		$location = "modifyincidenttypeform.php?id=".urlencode($inctype_id);
	}
} 
elseif ($action=="delete" && trim($custom_name)!="") 
{
	Incident_type::delete_custom($conn, $inctype_id, $custom_name);
	$location = "modifyincidenttypeform.php?id=".urlencode($inctype_id);
}

$db->close($conn);
?>
    <p> <?php echo gettext("Action type succesfully updated"); ?> </p>
<?php
sleep(1);
echo "<script>window.location='$location';</script>";
?>
</body>
</html>

