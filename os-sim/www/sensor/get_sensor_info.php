<?php
require_once ('classes/Session.inc');
require_once ('classes/Plugin.inc');
require_once ('get_sensor_plugins.php');
require_once ('ossim_db.inc');

$ip= GET('sensor_ip');

ossim_valid($ip, OSS_IP_ADDR, 'illegal:' . _("Sensor ip"));

if (ossim_error()) {
    die(ossim_error());
}

if( !Session::sensorAllowed($ip) ) return;

$sensor_plugins_list = server_get_sensor_plugins($ip);
$db                  = new ossim_db();
$conn                = $db->connect();
$conn_snort          = $db->snort_connect();

?>

<table class="noborder" width="100%" height="100%">
	<tr height="100%">
		<td class="nobborder" width="36" height="100%">
			<table border='0' cellpadding='0' cellspacing='0' width="36" height="100%" class="noborder">
				<tr><td class="nobborder" height="29"><img src="../pixmaps/bktop.gif" border='0'/></td></tr>
				<tr><td class="nobborder" style="background:url(../pixmaps/bkbg.gif) repeat-y">&nbsp;</td></tr>
				<tr><td class="nobborder" height="51"><img src="../pixmaps/bkcenter.gif" border='0'/></td></tr>
				<tr><td class="nobborder" style="background:url(../pixmaps/bkbg.gif) repeat-y"/>&nbsp;</td></tr>
				<tr><td class="nobborder" height="29"><img src="../pixmaps/bkdown.gif" border='0'/></td></tr>
			</table>
		</td>
		
		<td class="nobborder" style="background:#E0EFC2;padding:5px">
			<table align="left" width="100%">
				<tr>
					<th></th>
					<th> <?php echo gettext("Plugin"); ?> </th>
					<th> <?php echo gettext("Process Status"); ?> </th>
					<th> <?php echo gettext("Action"); ?> </th>
					<th> <?php echo gettext("Plugin status"); ?> </th>
					<th> <?php echo gettext("Action"); ?> </th>
					<th> <?php echo gettext("Last SIEM Event"); ?> </th>
				</tr>
				
				<?php
				if ($sensor_plugins_list) 
				{	
					foreach($sensor_plugins_list as $sensor_plugin) 
					{
						if ($sensor_plugin["sensor"] == $ip) 
						{
							$id      = $sensor_plugin["plugin_id"];
							$state   = $sensor_plugin["state"];
							$enabled = $sensor_plugin["enabled"];
							if ($plugin_list = Plugin::get_list($conn, "WHERE id = $id")) 
							{
								$plugin_name = $plugin_list[0]->get_name();
							} 
							else 
							{
								$plugin_name = $id;
							}
							
							$event = Plugin::get_latest_SIM_Event($conn_snort,$id,$plugin_name);
						?>
							<tr>
								<td width="16"><a href="javascript:;" onclick="load_lead('<?=$id?>')"><img src="../pixmaps/plus-small.png" border="0" align="absmiddle"></a></td>
								<td><?php echo $plugin_name ?></td>
									<?php 
									if ($state == 'start') 
									{ 
										?>
										<td><span style='color:green; font-weight: bold;'><?php echo gettext("UP"); ?></span></td>
										<td><a href="" onclick="GB_show('Command','<?php echo "sensor_plugins.php?sensor=$ip&ip=$ip&cmd=stop&id=$id" ?>',250,530);return false"><?php echo gettext("Stop"); ?> </a></td>
										<?php
									} 
									elseif ($state == 'stop') 
									{
										?>
										<td><span style='color:red; font-weight: bold;'><?php echo gettext("DOWN"); ?></span></td>
										<td><a href="" onclick="GB_show('Command','<?php echo "sensor_plugins.php?sensor=$ip&ip=$ip&cmd=start&id=$id" ?>',250,530);return false""><?php echo gettext("Start"); ?> </a></td>
										<?php
									} 
									else 
									{
										?>
										<td><?php echo gettext("Unknown"); ?></td>
										<td>-</td>
										<?php
									}
									
									if ($enabled == 'true') 
									{
										?>
										<td><span style='color:green; font-weight: bold;'><?php echo gettext("ENABLED"); ?></span></td>
										<td><a href="" onclick="GB_show('Command','<?php echo "sensor_plugins.php?sensor=$ip&ip=$ip&cmd=disable&id=$id" ?>',250,530);return false"><?php echo gettext("Disable"); ?> </a></td>
										<?php
									} 
									else 
									{
										?>
										<td><span style='color:red; font-weight: bold;'><?php echo gettext("DISABLED"); ?></span></td>
										<td><a href="" onclick="GB_show('Command','<?php echo "sensor_plugins.php?sensor=$ip&ip=$ip&cmd=enable&id=$id" ?>',250,530);return false"><?php echo gettext("Enable"); ?> </a></td>
										<?php
									}
								?>
								<td>
									<table class="noborder">
										<tr>
											<td class="small nobborder" nowrap='nowrap'><i><?php echo $event["timestamp"]?></i>&nbsp;</td>
											<td class="small nobborder"><a href="<?php echo $acid_main_link."&plugin=".urlencode($event["plugin_id"])?>"><b><?php echo $event["sig_name"]?></b></a></td>
										</tr>
									</table>
								</td>
							</tr>
							
							<tr>
								<td colspan="2" id="selector_<?php echo $id?>" style="display:none;padding-left:10px;border-bottom:none">
									<form style="margin:0px">
										<table class="noborder center">
											<tr>
												<td class="noborder"><img src="../pixmaps/flag_yellow.png" border="0"></td>
												<td class="noborder"><input type="text" size="4" id="yellow_<?php echo $id?>" value="12"> <?=_("hours")?></td>
											</tr>
											
											<tr>
												<td class="noborder"><img src="../pixmaps/flag_red.png" border="0"></td>
												<td class="noborder"><input type="text" size="4" id="red_<?php echo $id?>" value="48"> <?=_("hours")?></td>
											</tr>
											<tr>
												<td colspan="2" class="noborder" align="center"><input type="button" class="lbutton" onclick="mark('<?php echo $id?>')" value="<?php echo _("Mark")?>"></td>
											</tr>
										</table>
									</form>
								</td>    
								<td colspan="5" id="plugin_<?php echo $id?>" style="display:none;padding-left:0px;border-bottom:none"></td>
							</tr>
							<?php
						} // if
        
					} // foreach
    
					?>
					<tr>
						<td colspan="7" class='nobborder center'>
							<a href="<?php echo "sensor_plugins.php?sensor=$ip" ?>"> <?php echo _("Refresh")?> </a>
						</td>
					</tr>
				<?php
				} // if
				?>
			</table>
		</td>
	</tr>
</table>

<?php
$db->close($conn);
$db->close($conn_snort);
?>