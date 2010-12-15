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

require_once ('classes/Session.inc');
require_once ('utils.php');

$signed_files = get_signed_files();

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/greybox.js"></script>
	<script type="text/javascript" src="../js/jquery.tablePagination.js"></script>
	<script type="text/javascript" src="../js/jquery.cookie.js"></script>
		
	<script type="text/javascript">
		$(document).ready(function(){	
			$("#sf_table tr:odd").css("background-color", "#EEEEEE");
			
			if ($("#sf_table").length >= 1 )
			{
				var options = {
				  currPage : 1, 
				  optionsForRows: [<?php echo generate_sequence(count($signed_files)); ?>],
				  rowsPerPage : 5,
				  firstArrow : (new Image()).src="../pixmaps/first.gif",
				  prevArrow : (new Image()).src="../pixmaps/prev.gif",
				  lastArrow : (new Image()).src="../pixmaps/last.gif",
				  nextArrow : (new Image()).src="../pixmaps/next.gif"
				}
				
				$("#sf_table").tablePagination(options);
			}
		
		});
		
		function validate_signature(file, signature) {
			GB_show('<?=_("Validate signature")?>','validate.php?f='+file+'&s='+signature,300,600);
		}
		
		
		
	</script>
	
	<style type='text/css'>
		a {cursor:pointer;}
		#container_center {width: 80%; margin:auto; margin: 30px auto 10px auto;}
		#sf_table {width: 100%; background: #FFFFFF !important; border:none; }
		#sf_table th { height: 20px;}
		#sf_table td { text-align: left; font-size:11px; padding: 3px 5px;}
		.sf_name img {margin: 0px 3px;}
		.sf_date {width:150px; text-align:center !important;}
		.sf_size {width:80px; text-align:right !important;}
		.sf_options {width:60px;}
		.sf_options img {margin: 0px 3px;}
		.bborder_none { border-bottom: none !important; background: #FFFFFF !important;}
		.error_messages {font-weight: bold; text-align:center;}
	
	</style>
		
</head>
<body>



<?php 

	include ("../hmenu.php"); 
			
?>

<div id='container_center'>
											
		<?php
								
			if ( is_array($signed_files) && !empty ($signed_files) )
			{
		?>		<table id='sf_table'>
					<thead>
						<tr>
							<th><?php echo _("Name")?></th>
							<th><?php echo _("Last Modified")?></th>
							<th><?php echo _("Size")?></th>
							<th><?php echo _("Options")?></th>
						</tr>
					</thead>
					
					<tbody>
		
		<?php
				foreach ($signed_files as $k => $v)
				{
					echo "<tr>";
						echo "<td class='sf_name'><img src='../pixmaps/".$v[2][0]."' alt='"._($v[2][1])."' title='"._($v[2][1])."' align='absmiddle'/>$k</td>";
						echo "<td class='sf_date'>".$v[0]."</td>";
						echo "<td class='sf_size'>".$v[1]."</td>";
						echo "<td class='sf_options'>";
							echo "<a href='download_file.php?file=".urlencode($k)."' target='_blank'><img src='../pixmaps/exportScreen.png' alt='"._($v[2][1])."' title='"._("Download ".$v[2][1])."' align='absmiddle'/></a>"; 
						
						if ($v[3][0] === true)
							echo "<a onclick=\"validate_signature('".base64_encode($k)."', '".base64_encode($v[3][1])."'); return false;\"><img border='0' src='../pixmaps/lock.png' alt='"._("Validate")."' title='"._("Validate ".$v[2][1])."' align='absmiddle'></a>";
						echo "</td>";
					echo "</tr>";	
				
				}
		?>		</tbody>
			</table>
		<?php		
			}
			else
			{	if ($signed_files == -1)
				{
					$error_messages   = _("Error to list signed files");
					$class = "ossim_error";
				}
				else
				{
					$error_messages   = _("No signed files available");
					$class = "ossim_info";
				}
				echo "<div class='$class error_messages'>$error_messages</div>";
			}
						
		?>
	</table>

</body>
</html>


