<?php
/*****************************************************************************
*
*    License:
*
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
$incident_list2 = Incident::get_list_filter_ips($conn, $host, "ORDER BY date DESC");
$i_date = "-";
$i_maxprio = 0;
$i_maxprio_id = 1;
?><script type="text/javascript">$("#pbar").progressBar(75);$("#progressText").html('<?=gettext("Loading")?> <b><?=gettext("Tickets")?></b>...');</script><?
ob_flush();
flush();
usleep(500000);
$tick_num = count($incident_list2);
?>
<script type="text/javascript">
document.getElementById('tickets_num').innerHTML = '<a href="../top.php?option=1&soption=1&url=<?php echo urlencode("incidents/index.php?status=Open&hmenu=Tickets&smenu=Tickets") ?>" target="topmenu" class="whitepn"><?=Util::number_format_locale((int)$tick_num,0)?></a>';
</script>
<table align="center" width="100%" height="100%" class="bordered">
	<tr>
		<td class="headerpr" height="20"><a style="color:black" href="../top.php?option=1&soption=1&url=<?=urlencode("incidents/index.php?status=Open&hmenu=Tickets&smenu=Tickets&with_text=$host")?>" target='topmenu'>Tickets</a></td>
	</tr>
	<? if (count($incident_list2) < 1) { ?>
	<tr><td><?=gettext("No Tickets Found for")?> <i><?=$host?></i></td></tr>
	<? } else { ?>
	<tr>
		<td class="nobborder" valign="top">
			<table class="noborder">
				<tr>
					<th><?=gettext("Ticket")?></th>
					<th><?=gettext("Title")?></th>
					<? if ($network == 9) { ?><th>IP</th><? } ?>
					<th><?=gettext("Priority")?></th>
					<th><?=gettext("Status")?></th>
				</tr>
			<? $i = 0; foreach ($incident_list2 as $incident) { if ($i > 5) continue;
				if ($i_date == "-") { 
					$i_date = $incident->get_date();
					?>
					<script type="text/javascript">
					document.getElementById('tickets_date').innerHTML = "<?=$i_date?>";
					</script>
					<?
				}
			?>
				<tr <?php
					if ($row++ % 2) echo 'bgcolor="#E1EFE0"'; ?> valign="center">
					<td>
					<a href="../incidents/incident.php?id=<?php echo $incident->get_id() ?>"<? if ($greybox) echo " target='main'" ?>>
					<?php echo $incident->get_ticket(); ?></a>
					</td>
					<td><b>
					<a href="../incidents/incident.php?id=<?php echo $incident->get_id() ?>"<? if ($greybox) echo " target='main'" ?>>

						<?php echo $incident->get_title(); ?></a></b>
					<?php
					if ($incident->get_ref() == "Vulnerability") {
						$vulnerability_list = $incident->get_vulnerabilities($conn);
						// Only use first index, there shouldn't be more
						if (!empty($vulnerability_list)) {
							echo " <font color=\"grey\" size=\"1\">(" . $vulnerability_list[0]->get_ip() . ":" . $vulnerability_list[0]->get_port() . ")</font>";
						}
					}
					?>
					</td>
					<? if ($network == 9) { ?><td><? echo $incident->get_src_ips()?></td><? } ?>
					<?php
					$priority = $incident->get_priority();
					if ($priority > $i_maxprio) { $i_maxprio = $priority; $i_maxprio_id = $incident->get_id(); }
					?>
					<td><?php echo Incident::get_priority_in_html($priority) ?></td>
					<td><?php
					Incident::colorize_status($incident->get_status()) ?></td>
				</tr>
			<? $i++; } ?>
			<script type="text/javascript">
			document.getElementById('statusbar_incident_max_priority').innerHTML = '<?=preg_replace("/\n|\r/","",Incident::get_priority_in_html($i_maxprio))?>';
			document.getElementById('statusbar_incident_max_priority').href = '../top.php?option=1&soption=1&url=<?php echo urlencode("incidents/incident.php?id=$i_maxprio_id&hmenu=Tickets&smenu=Tickets") ?>';
			document.getElementById('statusbar_incident_max_priority_txt').href = '../top.php?option=1&soption=1&url=<?php echo urlencode("incidents/incident.php?id=$i_maxprio_id&hmenu=Tickets&smenu=Tickets") ?>';
			</script>
			</table>
		</td>
	</tr>
	<tr><td style="text-align:right;padding-right:20px"><a style="color:black" href="../top.php?option=1&soption=1&url=<?=urlencode("incidents/index.php?status=Open&hmenu=Tickets&smenu=Tickets&with_text=$host")?>" target='topmenu'><b><?=gettext("More")?> >></b></a></td></tr>
	<tr><td></td></tr>
	<? } ?>
</table>
