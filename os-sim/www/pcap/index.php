<?php
/*****************************************************************************
*
*   Copyright (c) 2007-2011 AlienVault
*   All rights reserved.
*
****************************************************************************/

require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
require_once ('classes/Sensor.inc');
require_once ('classes/Scan.inc');

Session::logcheck("MenuMonitors", "MonitorsNetwork");

$error      = false;
$info_error = array();

$db     = new ossim_db();
$dbconn = $db->connect();

$scan = new TrafficScan();

$states = array("0" => _("Idle"), "1" => _("A Pending Scan"), "2" => _("Scanning"), "-1" => _("Error When Scanning"));

$scans_by_sensor = $scan->get_scans();
$sensors_status = $scan->get_status();

if(!$scans_by_sensor) $scans_by_sensor = array();
if(!$sensors_status)  $sensors_status  = array();

$message_info = "";

// Parameters to delete scan

$op           = GET("op");
$scan_name    = GET("scan_name");
$sensor_name  = GET("sensor_name");

// Others parameters

$soptions     = intval(GET("soptions"));

ossim_valid($op, OSS_NULLABLE, 'delete', 'illegal:' . _("Option"));
ossim_valid($scan_name, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_DOT, 'illegal:' . _("Scan name"));
ossim_valid($sensor_name, OSS_NULLABLE,OSS_ALPHA, OSS_SPACE, OSS_PUNC, 'illegal:' . _("Sensor name"));

if(GET("command") == _("Launch scan")) {

    // Parameters to launch scan

    $timeout          = $parameters['timeout'] = GET("timeout");
    $sensor_data      = GET("sensor");
    $tmp              = explode("-",$sensor_data);
    $sensor_ip        = $parameters['sensor_ip']        = $tmp[0];
    $sensor_interface = $parameters['sensor_interface'] = $tmp[1];
    
    if(!Session::sensorAllowed($sensor_ip)) $sensor_ip = $sensor_interface = "";
    
    $src              = GET("src");
    $dst              = GET("dst");


    $validate  = array (
            "timeout"          => array("validation" => "OSS_DIGIT"           , "e_message" => 'illegal:' . _("Timeout")),
            "sensor_ip"        => array("validation" => "OSS_IP_ADDR"         , "e_message" => 'illegal:' . _("Sensor")),
            "sensor_interface" => array("validation" => "OSS_ALPHA, OSS_PUNC" , "e_message" => 'illegal:' . _("Interface"))
        );

        foreach ($parameters as $k => $v )
        {
            eval("ossim_valid(\$v, ".$validate[$k]['validation'].", '".$validate[$k]['e_message']."');");

            if ( ossim_error() )
            {
                $info_error[] = ossim_get_error();
                ossim_clean_error();
                $error  = true;
            }
        }


    // sources
    
    ossim_valid($src, OSS_DIGIT, OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_PUNC, OSS_NL, '\.\,\/', 'illegal:' . _("Source"));
    if( ossim_error() )  
    {
        $info_error[] = ossim_get_error();
        ossim_clean_error();
        $error  = true;
    }

    if($src!="") {
        $all_sources = explode("\n", $src);
        $tsources     = array(); // sources for tshark
        foreach($all_sources as $source) 
        {
            $source      = trim($source);
            $source_type = null;
            
            if ( ossim_error() == false )
            {
                if(!preg_match("/\//",$source)) 
                {
                    if(!preg_match('/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/', $source))  {  $source = Host::hostname2ip($dbconn, $source, true);  } // resolve to ip
                    ossim_valid($source, OSS_IP_ADDR, 'illegal:' . _("Source ip"));
                    $source_type = 'host';
                }
                else 
                {
                    ossim_valid($source, OSS_IP_CIDR, 'illegal:' . _("Source cidr"));
                    $source_type = 'net';
                }
            }
            
            if( ossim_error() )  
            {
                $info_error[] = ossim_get_error();
                ossim_clean_error();
                $error  = true;
            }
            else
            {
                if ( $source_type == 'host' )
                {
                    if( Session::hostAllowed($dbconn, $source) ) 
                        $tsources[] = $source;
                }
                elseif ( $source_type == 'net' )
                {
                    if(Session::netAllowed($dbconn, $source))   
                        $tsources[] = $source;
                }
            }
                
            
        }
    }
    
    ossim_valid($dst, OSS_DIGIT, OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_PUNC, OSS_NL, '\.\,\/', 'illegal:' . _("Destination"));
    
    if( ossim_error() )  
    {
        $info_error[] = ossim_get_error();
        ossim_clean_error();
        $error  = true;
    }

    // destinations

    if($dst!="") {
        $all_destinations  = explode("\n", $dst);
        $tdestinations     = array(); // sources for tshark
        foreach($all_destinations as $destination) 
        {
            $destination      = trim($destination);
            $destination_type = null;
            
            if ( ossim_error() == false )
            {
                if(!preg_match("/\//",$destination)) 
                {
                    if(!preg_match('/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/', $destination))  {  $destination = Host::hostname2ip($dbconn, $destination, true);  } // resolve to ip
                    ossim_valid($destination, OSS_IP_ADDR, 'illegal:' . _("Destination ip"));
                    $destination_type = 'host';
                }
                else 
                {
                    ossim_valid($destination, OSS_IP_CIDR, 'illegal:' . _("Destination cidr"));
                    $destination_type = 'net';
                }
            }
            
            if( ossim_error() )  
            {
                $info_error[] = ossim_get_error();
                ossim_clean_error();
                $error  = true;
            }
            else
            {
                if ( $destination_type == 'host' )
                {
                    if( Session::hostAllowed($dbconn, $destination) ) 
                        $tdestinations[] = $destination;
                }
                elseif ( $destination_type == 'net' )
                {
                    if(Session::netAllowed($dbconn, $destination))   
                        $tdestinations[] = $destination;
                }
            }
                
            
        }
    }

    // launch scan
    
    $info_sensor = $sensors_status[Sensor::get_sensor_name($dbconn, $sensor_ip)];

    if(count($tsources)>0 && count($tdestinations)>0 && $sensor_ip!="" && $sensor_interface!="" && intval($timeout)>0 && count($info_error)==0 && ($info_sensor[0]==0 || $info_sensor[0]==-1)) {
        $rlaunch_scan = $scan->launch_scan($tsources, $tdestinations, $sensor_ip, $sensor_interface, $timeout);
        $message_info="<div class='ossim_success'>"._("Launched scan")."</div>";
    }
    else if($info_sensor[0]!= -1 && ($info_sensor[0]== 1 || $info_sensor[0]== 2)){
        $message_info="<div class='ossim_alert'>"._("The sensor is busy")."</div>";
    }
}

// delete scan
if($op=="delete" && $scan_name!="" && $sensor_name!="") {
    $scan_info = explode("_", $scan_name);
    $users = Session::get_users_to_assign($dbconn);
    
    $my_users = array();
    foreach( $users as $k => $v ) {  $my_users[$v->get_login()]=1;  }
    
    if($my_users[$scan_info[1]]==1)  $scan->delete_scan($scan_name,$sensor_name);
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> - Traffic capture </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<META http-equiv="Pragma" content="no-cache">
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
	<link rel="stylesheet" type="text/css" href="../style/tree.css" />
    <link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
	<script type="text/javascript" src="../js/jquery.tmpl.1.1.1.js"></script>
	<script type="text/javascript" src="../js/jquery.dynatree.js"></script>
    <script src="../js/greybox.js" type="text/javascript"></script>
    <script type="text/javascript" src="../js/utils.js"></script>
    <script type="text/javascript">
$(document).ready(function() {
		var sfilter = "";
		$("#stree").dynatree({
			initAjax: { url: "../vulnmeter/draw_tree.php", data: {filter: sfilter} },
			clickFolderMode: 2,
			onActivate: function(dtnode) {
                dtnode.data.url = html_entity_decode(dtnode.data.url);
				var ln = ($('#src').val()!='') ? '\n' : '';
				var inside = 0;
				if (dtnode.data.url.match(/NODES/)) {
					// add childrens if is a C class
					var children = dtnode.tree.getAllNodes(dtnode.data.key.replace('.','\\.')+'\\.');
					for (c=0;c<children.length; c++) {
						if (children[c].data.url != '') {
							var ln = ($('#src').val()!='') ? '\n' : '';
							$('#src').val($('#src').val() + ln + children[c].data.url)
							inside = true;
						}
					}
					if (inside==0 && dtnode.data.key.match(/^hostgroup_/)) {
						dtnode.appendAjax({
					    	url: "../vulnmeter/draw_tree.php",
					    	data: {key: dtnode.data.key, page: dtnode.data.page},
			                success: function(msg) {
			                    dtnode.expand(true);
			                    var children = dtnode.tree.getAllNodes(dtnode.data.key.replace('.','\\.')+'\\.');
								for (c=0;c<children.length; c++) {
									if (children[c].data.url != '') {
										var ln = ($('#src').val()!='') ? '\n' : '';
										$('#src').val($('#src').val() + ln + children[c].data.url)
									}
								}
			                }
						});
					}
				} else {
					if (dtnode.data.url != '') $('#src').val($('#src').val() + ln + dtnode.data.url)
				}
			},
			onDeactivate: function(dtnode) {},
            onLazyRead: function(dtnode){
                dtnode.appendAjax({
                    url: "../vulnmeter/draw_tree.php",
                    data: {key: dtnode.data.key, page: dtnode.data.page}
                });
            }
		});
		var dfilter = "";
		$("#dtree").dynatree({
			initAjax: { url: "../vulnmeter/draw_tree.php", data: {filter: dfilter} },
			clickFolderMode: 2,
			onActivate: function(dtnode) {
                dtnode.data.url = html_entity_decode(dtnode.data.url);
				var ln = ($('#dst').val()!='') ? '\n' : '';
				var inside = 0;
				if (dtnode.data.url.match(/NODES/)) {
					// add childrens if is a C class
					var children = dtnode.tree.getAllNodes(dtnode.data.key.replace('.','\\.')+'\\.');
					for (c=0;c<children.length; c++) {
						if (children[c].data.url != '') {
							var ln = ($('#dst').val()!='') ? '\n' : '';
							$('#dst').val($('#dst').val() + ln + children[c].data.url)
							inside = true;
						}
					}
					if (inside==0 && dtnode.data.key.match(/^hostgroup_/)) {
						dtnode.appendAjax({
					    	url: "../vulnmeter/draw_tree.php",
					    	data: {key: dtnode.data.key, page: dtnode.data.page},
			                success: function(msg) {
			                    dtnode.expand(true);
			                    var children = dtnode.tree.getAllNodes(dtnode.data.key.replace('.','\\.')+'\\.');
								for (c=0;c<children.length; c++) {
									if (children[c].data.url != '') {
										var ln = ($('#dst').val()!='') ? '\n' : '';
										$('#dst').val($('#dst').val() + ln + children[c].data.url)
									}
								}
			                }
						});
					}
				} else {
					if (dtnode.data.url != '') $('#dst').val($('#dst').val() + ln + dtnode.data.url)
				}
			},
			onDeactivate: function(dtnode) {},
            onLazyRead: function(dtnode){
                dtnode.appendAjax({
                    url: "../vulnmeter/draw_tree.php",
                    data: {key: dtnode.data.key, page: dtnode.data.page}
                });
            }
		});
});
    function confirmDelete(data){
        var ans = confirm("<?php echo gettext("Are you sure you want to delete this scan?")?>");
        if (ans) document.location.href='index.php?'+data;
    }
    GB_TYPE = 'w';
    function showGreybox(title, width, dest){
        GB_show(title,dest,450,width);
    }
    function GB_onclose() {
        document.location.href='index.php';
    }
    </script>
</head>
<body>
<?php
include ("../hmenu.php");

if ( $error == true )
		echo display_errors($info_error);
if ( $message_info !="") 
        echo $message_info;
?>

<table cellspacing="0" align="center" cellpadding="0" border="0" width="70%">
    <tr><td class="headerpr" style="border:0;"><?php echo gettext("Sensors Status") ?></td></tr>
</table>
<?php

if(count($sensors_status)==0) {
?>
    <table width="70%" align="center">
        <tr>
            <td class="nobborder" style="text-align:center"><?php echo _("No available sensors")?></td>
        </tr>
    </table>
<?php
}
else {
?>
    <table width="70%" align="center">
        <tr>
            <th width="30%"><?php echo _("Sensor Name")?>     </th>
            <th width="30%"><?php echo _("Sensor IP")?>       </th>
            <th width="20%"><?php echo _("Total Scans")?> </th>
            <th width="20%"><?php echo _("Status")?>          </th>
        </tr>
            <?php
                $i=1;
                foreach($sensors_status as $sensor_name => $sensor_info) {
                    $seclass="";
                    if(count($scans_by_sensor[$sensor_name])>0 || count($sensor_status)==$i) $seclass = "class=\"nobborder\"";
                    $i++;
                    ?>
                    <tr><td style="text-align:center;" <?php echo $seclass ?>><?php echo $sensor_name;?></td>
                        <td style="text-align:center;" <?php echo $seclass ?>><?php echo Sensor::get_sensor_ip($dbconn,$sensor_name); ?></td>
                        <td style="text-align:center;" <?php echo $seclass ?>><?php echo count($scans_by_sensor[$sensor_name])?></td>
                        <td style="text-align:center;" <?php echo $seclass ?>><?php echo $states[$sensor_info[0]] ?></td>
                    </tr>
                    <?php if(count($scans_by_sensor[$sensor_name])>0) { ?>
                        <tr><td colspan="4" class="nobborder">
                            <table width="80%" style="margin:auto">
                                <tr>
                                    <th width="30%"><?php echo gettext("Scan Start Time"); ?></th>
                                    <th width="20%"><?php echo gettext("Duration (seconds)"); ?></th>
                                    <th width="30%"><?php echo gettext("User"); ?></th>
                                    <th width="20%"><?php echo gettext("Action"); ?></th>
                                </tr>
                            
                            <?php
                                $j=1;
                                foreach($scans_by_sensor[$sensor_name] as $data) {
                                    $scclass="";
                                    if(count($scans_by_sensor[$sensor_name])==$j) $scclass = "class=\"nobborder\"";
                                    $j++;
                            ?>
                                <tr>
                                    <td style="text-align:center" <?php echo $scclass;?>><?php 
                                        $scan_info = explode("_",$data);
                                        echo date("Y-m-d H:i:s", $scan_info[2] );
                                      ?>
                                    </td>
                                    <td style="text-align:center" <?php echo $scclass;?>><?php echo $scan_info[3]?></td>
                                    <td style="text-align:center" <?php echo $scclass;?>><?php echo $scan_info[1]?></td>
                                    <td style="text-align:center" <?php echo $scclass;?>>
                                        <a href="javascript:;" onclick="return confirmDelete('op=delete&scan_name=<?php echo $data?>&sensor_name=<?php echo $sensor_name?>');">
                                            <img align="absmiddle" src="../vulnmeter/images/delete.gif" title="<?php echo gettext("Delete")?>" alt="<?php echo gettext("Delete")?>" border=0>
                                        </a>
                                        <a href="download.php?scan_name=<?php echo $data?>&sensor_name=<?php echo $sensor_name?>">
                                        <img align="absmiddle" src="../pixmaps/theme/mac.png" title="<?php echo gettext("Download")?>" alt="<?php echo gettext("Download")?>" border="0">
                                        <a onclick="showGreybox('<?php echo _("Scan details:") ?>',650,'payload_pcap.php?scan_name=<?php echo $data?>&sensor_name=<?php echo $sensor_name?>');" class="greybox" href="javascript:;">
                                            <img align="absmiddle" src="../pixmaps/wireshark.png" title="<?php echo _("View Payload") ?>" alt="<?php echo _("View Payload") ?>" border="0">
                                        </a>
                                    </td>
                                 </tr>
                            <?php
                            }
                            ?>
                                </table>
                            </td></tr>
                        <?php
                    }
                }
            ?>
    </table>
<?php
}
?>

<br />

<table width="70%" align="center" class="transparent" cellspacing="0" cellpadding="0">
    <tr>
        <td style="text-align:left" class="nobborder">
            <a href="javascript:;" onclick="$('.tscans').toggle();$('#message_show').toggle();$('#message_hide').toggle();" colspan="2"><img src="../pixmaps/arrow_green.gif" align="absmiddle" border="0">
                <span id="message_show" <?php echo ((count($info_error)>0 || $soptions==1 )? "style=\"display:none\"":"")?>><?php echo gettext("Run Scan Now")?></span>
                <span id="message_hide"<?php echo ((count($info_error)>0 || $soptions==1 )? "":"style=\"display:none\"")?>><?php echo gettext("Hide Scan Options")?></span>
            </a>
        </td>
    </tr>
</table>

<form method="get">
    <br />
    <table cellspacing="0" align="center" cellpadding="0" border="0" width="70%" class="tscans" <?php echo (count($info_error)>0 || $soptions==1 )? "":"style=\"display:none;\""?>>
        <tr><td class="headerpr" style="border:0;"><?php echo gettext("Scan Options") ?></td></tr>
    </table>
	<table border="0" width="70%" align="center" class="tscans" <?php echo (count($info_error)>0 || $soptions==1 )? "":"style=\"display:none;\""?>>
		<tr>
           <td class="nobborder">
                <table align="center" class="nobborder">
                    </tr>
            <th width="30"> <?php echo _("Timeout");?> </th>
            <td class="nobborder">
                <select name="timeout" style="width:50px;">
                  <option <?php echo (($timeout=="10") ? "selected=\"selected\"":"") ?>>10</option>
                  <option <?php echo (($timeout=="20") ? "selected=\"selected\"":"") ?>>20</option>
                  <option <?php echo (($timeout=="30") ? "selected=\"selected\"":"") ?>>30</option>
                  <option <?php echo (($timeout=="60") ? "selected=\"selected\"":"") ?>>60</option>
                  <option <?php echo (($timeout=="90") ? "selected=\"selected\"":"") ?>>90</option>
                  <option <?php echo (($timeout=="120") ? "selected=\"selected\"":"") ?>>120</option>
                  <option <?php echo (($timeout=="180") ? "selected=\"selected\"":"") ?>>180</option>
                </select>  <?php echo _("seconds");?>
            </td>
                </tr>
                </table>
            </td>
    	</tr>
        <tr>
			<td class="nobborder" width="100%">
				<table class="transparent" width="100%">
					<tr>
						<th colspan="3"><?php echo _("Settings")?></th>
					</tr>
                    <tr>
                        <th><?php echo _("Sensors");?></th>
                        <th><?php echo _("Source");?></th>
                        <th><?php echo _("Destination");?></th>
                    </tr>
					<tr>
						<td width="33%" valign="top" style="padding:4px;" class="nobborder">
                        <?php
                        $sensor_list = $scan->get_sensors();
                        if(count($sensor_list)==0) { echo _("No available sensors"); }
                        
                       
                        foreach($sensor_list as $ip => $sensor_data) {
                            $interfaces = explode(",",$sensor_data[1]);
                            if($interfaces[0]!="") $hinterfaces = true;
                            else                    $hinterfaces = false;
                            ?>
                            <table class="transparent">
                            <?php
                            foreach($interfaces as $interface) {
                                ?>
                                <tr><td class="nobborder">
                                <?php
                                $checked = "";
                                if($sensor_ip==$ip && $interface==$sensor_interface) $checked = "checked=\"checked\"";
                                
                                $disabled = "";
                                if(!$hinterfaces || !Session::sensorAllowed($ip)) $disabled = " disabled=\"disabled\"";
                                
                            ?>
                                <input type="radio" <?php echo $checked?> name="sensor" value="<?php echo $ip."-".$interface;?>" <?php echo $disabled ?>/>
                                <?php
                                if($hinterfaces)
                                    echo $ip." (".$sensor_data[0]." / ".$interface.")"; 
                                else
                                    echo $ip." (".$sensor_data[0]." / <a onclick=\"showGreybox('"._("Edit sensor:")."',900,'../sensor/interfaces.php?sensor=$ip&name=".$sensor_data[0]."&withoutmenu=1');\" class=\"greybox\" href=\"javascript:;\">"._("NOT FOUND")."</a>)"; 
                                ?>
                                </td>
                                </tr>
                                <?php
                            }
                            ?>
                            </table>
                            <?php
                        }
                        ?>
                        </td>
                        <td width="33%" valign="top" style="padding-top:4px;" class="nobborder">
                            <table width="100%" class="transparent">
                                <tr><td class="nobborder" style="text-align:center"><textarea rows="8" cols="32" id="src" name="src"><?php echo $src ?></textarea></td></tr>
                                <tr><td class="nobborder"><div id="stree" style="width:300px;margin:auto"></div></td></tr>
                            </table>
                        </td>
                        <td width="33%" valign="top" style="padding-top:4px;" class="nobborder">
                            <table width="100%" class="transparent">
                                <tr><td class="nobborder" style="text-align:center"><textarea rows="8" cols="32" id="dst" name="dst"><?php echo $dst ?></textarea></td></tr>
                                <tr><td class="nobborder"><div id="dtree" style="width:300px;margin:auto"></div></td></tr>
                            </table>
                        </td>
					</tr>
				</table>
			</td>
		</tr>
        <tr>
            <td style="text-align:right;padding:0px 5px 5px 0px" class="nobborder" s>
                <input type="submit" class="button" name="command" value="<?php echo _("Launch scan");?>" />
                
            </td>
        </tr>
	</table>

</form>

</body>
</html>
<?php

$db->close($dbconn);

function display_errors($info_error)
{
	$errors       = implode ("</div><div style='padding-top: 3px;'>", $info_error);
	$error_msg    = "<div>"._("We found the following errors:")."</div><div style='padding-left: 15px;'><div>$errors</div></div>";
							
	return "<div class='ossim_error'>$error_msg</div>";
}
?>
