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
require_once ('classes/Security.inc');
include      ("base_conf.php");
include_once ($BASE_path."includes/base_db.inc.php");
include_once ("$BASE_path/includes/base_state_query.inc.php");
include_once ("$BASE_path/includes/base_state_common.inc.php");


/* Connect to the Alert database */
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);
$qs = new QueryState();

$msg_error  = null;
$show_form  = false;
	

if ( isset($_POST['send']) && !empty($_POST['send']) )
{
	$id   = POST('id');
	$name = POST('name');
    $url  = POST('url');
	
	
	ossim_valid($id, OSS_DIGIT, 'illegal:' . _("Id"));
	ossim_valid($name, OSS_DIGIT, OSS_ALPHA, OSS_SPACE, OSS_NULLABLE,              'illegal:' . _("Name"));
	ossim_valid($url,  OSS_ALPHA, OSS_DIGIT, OSS_URL, OSS_PUNC, '%', OSS_NULLABLE, 'illegal:' . _("Url"));
	
	if (ossim_error()) {
		die(ossim_error());
	}
	
	if ($name != "")
	{
		$icon = "";
		if (is_uploaded_file($HTTP_POST_FILES['icon']['tmp_name']))
		{
		   $icon = addslashes(file_get_contents($HTTP_POST_FILES['icon']['tmp_name']));
		   $sql = "UPDATE reference_system SET ref_system_name=\"$name\",url=\"$url\",icon=\"$icon\" WHERE ref_system_id=$id";
		} 
		else
		{
			$sql = "UPDATE reference_system SET ref_system_name=\"$name\",url=\"$url\" WHERE ref_system_id=$id";
		}
		$result_update = $qs->ExecuteOutputQueryNoCanned($sql, $db);
	}
	
}
else
{
	$show_form = true;
	$id   = GET('id');
	ossim_valid($id, OSS_DIGIT, 'illegal:' . _("Id"));

	if (ossim_error()) {
		die(ossim_error());
	}
	
	
	$sql    = "SELECT * FROM reference_system WHERE ref_system_id=$id";
	$result = $qs->ExecuteOutputQuery($sql, $db);
	$myrow  = $result->baseFetchRow();
		
	if ( empty($myrow) )
		$msg_error = _("Error to get reference type");
}


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<!-- <?php echo gettext("Forensics Console " . $BASE_installID) . $BASE_VERSION; ?> -->
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=iso-8859-1"/>
	<meta http-equiv="pragma" content="no-cache"/>
	<link rel="stylesheet" href="../style/style.css"/>
	<?php
	$archiveDisplay = (isset($_COOKIE['archive']) && $_COOKIE['archive'] == 1) ? "-- ARCHIVE" : "";
	echo ('<title>' . gettext("Forensics Console " . $BASE_installID) . $BASE_VERSION . $archiveDisplay . '</title>
	<link rel="stylesheet" type="text/css" href="styles/' . $base_style . '">');
	?>
</head>
<body>

<?php
	
	if ( $show_form == false )
	{
		echo "<div class='ossim_success' style='text-align:center; padding: 15px 10px;'>"._("Reference succesfully updated")."</div>";
		echo "<script type='text/javascript'>parent.document.location.href='manage_references.php'</script>";
		exit;
	}
	else
	{	
	
		if (!empty($msg_error) )
			echo "<div style='margin-top: 20px; width:80%' class='ossim_error'>$msg_error</div>";
		else
		{
		?>
			<form name="ref_form" method="post" enctype="multipart/form-data">
		
			<input type="hidden" name="id" value="<?=$id?>"/>
				
			<table class="transparent" align="center">
				<tr>
					<th><?php echo _("Name")?></th>
					<td class="nobborder"><input type="text" name="name" value="<?=$myrow[1]?>" style='width:242px;'/></td>
				</tr>
				<tr>
					<th><?php echo _("Icon")?></th>
					<td class="nobborder">
						<img style='margin-right: 10px;' src="manage_references_icon.php?id=<?=$myrow[0]?>"/>
						<input style="border:1px solid black" type="file" name="icon" size='25'/></td>
				</tr>
				<tr>
					<th><?php echo _("URL")?></th>
					<td class="nobborder"><textarea name="url" rows="2" cols="40"><?=$myrow[3]?></textarea></td>
				</tr>
				<tr>
					<td colspan="2" class="center nobborder" style='padding-top:5px;'>
						<input type="submit" name='send' value="<?php echo _("Update")?>" class="button"/>
					</td>
				</tr>
			</table>
		</form>

		<?php
		}
	}
?>



</body>
</html>