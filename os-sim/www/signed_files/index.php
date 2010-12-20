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
			$("#sf_tbody tr:odd").css("background-color", "#EEEEEE");
									
			if ($("#sf_tbody tr").length > 1 )
			{
				var options = {
				  currPage : 1, 
				  rowsPerPage : 15,
				  optionsForRows: [5,15,25,50],
				  firstArrow : (new Image()).src="../pixmaps/first.gif",
				  prevArrow : (new Image()).src="../pixmaps/prev.gif",
				  lastArrow : (new Image()).src="../pixmaps/last.gif",
				  nextArrow : (new Image()).src="../pixmaps/next.gif"
				}
				
				$("#sf_table").tablePagination(options);
			}
			
			$('#date').bind('change', function() { browse_files(); } );
							
		
		});
		
		function validate_signature(f, s, d) {
			GB_show('<?=_("Validate signature")?>','validate.php?f='+f+'&s='+s+'&d='+d,300,600);
		}
		
		function browse_files()
		{
			var date = $('#date').val();
			$.ajax({
				type: "POST",
				url: "browse_files.php",
				data: "date="+ date,
				success: function(html){
					var status = html.split("###");
					
					$("#sf_load").html(msg_load);
													
					if ( status[0] == "2")
					{
						$("#container_center").html(status[1]);
					}
					else 
					{
						$("#tablePagination").remove();
						$("#sf_tbody").html(status[1]);
						
						if ( status[0] == "3")
						{
							if ($("#sf_tbody tr").length > 1 )
							{
								var options = {
								  currPage : 1, 
								  optionsForRows: [5,15,25,50],
								  rowsPerPage : 15,
								  firstArrow : (new Image()).src="../pixmaps/first.gif",
								  prevArrow : (new Image()).src="../pixmaps/prev.gif",
								  lastArrow : (new Image()).src="../pixmaps/last.gif",
								  nextArrow : (new Image()).src="../pixmaps/next.gif"
								}
														
								$("#sf_table").tablePagination(options);
								$("#sf_tbody tr:odd").css("background-color", "#EEEEEE");
							}
						}
											
					}
					
					$("#sf_load").html('');
				}
			});
		}
				
		
	</script>
	
	<script type="text/javascript">
		var	msg_load  = '<img src="../pixmaps/loading3.gif" border="0" align="absmiddle"/><span style="padding-left: 5px;"><?php echo _("Loading data... ")?></span>';
	</script>
	
	<style type='text/css'>
		a {cursor:pointer;}
		#container_center {width: 80%; margin:auto; margin: 30px auto 10px auto;}
		#sf_table {width: 100%; background: #FFFFFF !important; border:none; }
		#sf_table th { height: 20px;}
		#sf_table td { text-align: left; font-size:11px; padding: 3px 5px;}
		.sf_tit_date{ width: 150px;}
		.sf_tit_size{ width: 80px;}
		.sf_tit_options{ width: 60px;}
		.sf_name img {margin: 0px 3px;}
		.sf_date {width:150px; text-align:center !important;}
		.sf_size {width:80px; text-align:right !important;}
		.sf_options {width:60px;}
		.sf_options img {margin: 0px 3px;}
		.bborder_none { border-bottom: none !important; background: #FFFFFF !important;}
		.error_message {font-weight: bold; text-align:center;}
		#cont_dates {padding-left: 3px; padding-bottom: 10px;}
		.no_files {text-align: center !important; padding: 10px 0px !important; background:#FFFFFF !important;}
		#cont_sf_load {width: 80%; margin:auto; position:relative;}
		#sf_load {position: absolute; width: 100%; top: 10px; left:0px; text-align:center;}
	</style>
		
</head>
<body>



<?php 

	include ("../hmenu.php"); 
			
?>
<div id='cont_sf_load'><div id='sf_load'></div></div>
<div id='container_center'>
											
		<?php
			$dates        = available_dates();
			$dates        = ( is_array($dates) ) ? array_keys($dates) : array();
											
			$signed_files = get_signed_files($dates[0]);
																		
			if ($signed_files == -1)
				echo "<div class='ossim_error error_message'>"._("Error to list files")."</div>";
			else
			{
							
			?>
				<div id='cont_dates'>
					<?php echo _("Browse available dates")?>: 
					<select name="date" id="date">
					<?php
						if ( !empty($dates) )
						{
							foreach ($dates as $k => $v)
								echo "<option value='$v'>$v</option>";
						}
						else
							echo "<option value=''>-- "._("No dates found")." --</option>";
					?>
					</select>
				</div>
				
				<table id='sf_table'>
						<thead>
							<tr>
								<th ><?php echo _("Name")?></th>
								<th class='sf_tit_size'><?php echo _("Date")?></th>
								<th class='sf_tit_date'><?php echo _("Size")?></th>
								<th class='sf_tit_options'><?php echo _("Options")?></th>
							</tr>
						</thead>
						
						<tbody id='sf_tbody'>
			<?php	
				if ( count($signed_files) >= 1 )
				{
					foreach ($signed_files as $k => $v)
					{
						echo "<tr>";
							echo "<td class='sf_name'><img src='../pixmaps/".$v[2][0]."' alt='"._($v[2][1])."' title='"._($v[2][1])."' align='absmiddle'/>$k</td>";
							echo "<td class='sf_date'>".$v[0]."</td>";
							echo "<td class='sf_size'>".$v[1]."</td>";
							echo "<td class='sf_options'>";
								echo "<a href='download_file.php?file=".urlencode($k)."&date=".urlencode($v[0])."' target='_blank'><img src='../pixmaps/exportScreen.png' alt='"._($v[2][1])."' title='"._("Download ".$v[2][1])."' align='absmiddle'/></a>"; 
							
							if ($v[3][0] === true)
								echo "<a onclick=\"validate_signature('".base64_encode($k)."', '".base64_encode($v[3][1])."', '".base64_encode($v[0])."'); return false;\"><img border='0' src='../pixmaps/lock.png' alt='"._("Validate")."' title='"._("Validate ".$v[2][1])."' align='absmiddle'></a>";
							echo "</td>";
						echo "</tr>";	
					
					}
				}
				else
				{
					echo "<tr><td colspan='4' class='no_files'><span style='color:red'>"._("No files available for this date")."</span></td></tr>";
				}
			?>		
					</tbody>
				</table>
	<?php   }  ?>
	
	</div>
</body>
</html>


