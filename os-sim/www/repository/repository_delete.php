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
require_once ('classes/Session.inc');
require_once ("ossim_db.inc");
Session::logcheck("MenuIncidents", "Osvdb");

$user        = $_SESSION["_user"];
$error       = false;
$id_document = GET('id_document');


ossim_valid($id_document, OSS_DIGIT, 'illegal:' . _("Id_document"));

if ( ossim_error() ) 
{
   $error_txt =  ossim_get_error();
   $error     = true;
}
else
{
	$db   = new ossim_db();
	$conn = $db->connect();
	Repository::delete($conn, $id_document);
	$db->close($conn);
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" CONTENT="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<style type='text/css'>
		.cont_delete {
			width: 80%;
			text-align:center;
			margin: 10px auto;
		}
		
		.ossim_error, .ossim_success { width: auto;}
		
		body { margin: 0px;}
	</style>
</head>

<body>
	<?php 
		if ($error == true ) 
		{ 
			?>
			<div class='cont_delete'><div class='ossim_error'><?php echo $error_txt;?></div></div>
			<?php 
		} 
		else 
		{ 
			?>
			<div class='cont_delete'>
				<div class='ossim_success'>
				<?php 
					echo _("Delete Repository document id").": <strong>$id_document</strong>"; 
					echo "<br/>". _("Document successfully deleted");
				?>
				</div>
			</div>
			<?php 
		} 
		?>
				
		<div class='cont_delete'>
				<input class="button" type="button" value="<?php echo _("OK")?>" onclick="parent.document.location.href='index.php'"/>
		</div>

</body>

</html>
