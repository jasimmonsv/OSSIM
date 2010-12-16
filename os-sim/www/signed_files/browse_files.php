<?php

require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
require_once ('utils.php');

$date = POST('date');
$d    = explode("-", $date);

if ( @checkdate($d[1], $d[0], $d[2]) == false )
{
	echo "1###<tr><td colspan='4' class='no_files'><span style='color:red'>"._("Error: Invalid date")."</span></td></tr>";
	exit();
}
			
$signed_files = get_signed_files($date);

if ($signed_files == -1)
	echo "2###<div class='ossim_error error_message'>"._("Error to list files")."</div>";
else
{
	if ( count($signed_files) >= 1 )
	{
		echo "3###";
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
		echo "4###<tr><td colspan='4' class='no_files'><span style='color:red'>"._("No files available for this date")."</span></td></tr>";
}
 


