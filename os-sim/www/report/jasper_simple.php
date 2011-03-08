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
require_once ('classes/JasperReport.inc');
require_once ('classes/Log_config.inc');
Session::useractive();
// Host & Network Report
require_once ('classes/Host.inc');
require_once ('classes/Net.inc');
$db = new ossim_db();
$conn = $db->connect();
list($_sensors, $_hosts) = Host::get_ips_and_hostname($conn,true);
$_nets = Net::get_all($conn,true);
$networks = $hosts = "";
foreach ($_nets as $_net) $networks .= '{ txt:"'.$_net->get_name().' ['.$_net->get_ips().']", id: "'.$_net->get_ips().'" },';
foreach ($_hosts as $_ip => $_hostname) {
    if ($_hostname!=$_ip) $hosts .= '{ txt:"'.$_ip.' ['.$_hostname.']", id: "'.$_ip.'" },';
    else $hosts .= '{ txt:"'.$_ip.'", id: "'.$_ip.'" },';
}
// Plugin
require_once ('classes/Plugin.inc');
$_plugin=Plugin::get_id_and_name($conn);
$plugins='';
foreach($_plugin as $id => $name) $plugins .= '{ txt:"'.$name.'", id: "'.$id.'" },';

// User Log lists
$session_list = Session::get_list($conn, "ORDER BY login");
if (preg_match("/pro|demo/",$conf->get_conf("ossim_server_version", FALSE)) && !Session::am_i_admin()) {
	require_once('classes/Acl.inc');
	$myusers = Acl::get_my_users($conn,Session::get_session_user());
	if (count($myusers) > 0) $is_pro_admin = 1;
}
$code_list = Log_config::get_list($conn, "ORDER BY descr");

// Sensor list for availability
require_once ('classes/Sensor.inc');
$sensor_list = Sensor::get_all($conn, "ORDER BY name");
require_once ('ossim_conf.inc');
$nagios_default = parse_url($conf->get_conf("nagios_link"));
/* nagios link */
$scheme = isset($nagios_default["scheme"]) ? $nagios_default["scheme"] : "http";
$path = isset($nagios_default["path"]) ? $nagios_default["path"] : "/nagios/";
$path = str_replace("//", "/", $path);
if ($path[0] != "/") {
    $path = "/" . $path;
}
$port = isset($nagios_default["port"]) ? ":" . $nagios_default["port"] : "";
$nagios = "$port" . "$path";

$db->close($conn);
?>
<html>
<head>
  <title> <?php echo gettext("OSSIM Framework - Reports"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">

  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
  <link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css">
  <link rel="stylesheet" type="text/css" href="../style/jasper.css">

  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>
  <script type="text/javascript" src="../js/greybox.js"></script>
  <script src="../js/datepicker.js" type="text/javascript"></script>
  <script language="javascript">
      var userAgent = navigator.userAgent.toLowerCase();
	jQuery.browser = {
	    version: (userAgent.match( /.+(?:rv|it|ra|ie|me)[\/: ]([\d.]+)/ ) || [])[1],
	    chrome: /chrome/.test( userAgent )
	};
        function emailValidate(id,reportUnit)
	    {
	        if(/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test($(id).val()))
	        {
	            openGreyBox($("#"+reportUnit+"_label").val());
                    setTimeout("exportReport('"+reportUnit+"','email')",500);
                   
	            return true;
	        }else{
	            alert("<?php echo _("Please, add a valid email"); ?>");
	            $(id).focus();
	            return false;
	        }
	    }
		
        function executeReport(id){
            var idDiv="#ajax_"+id;
            var pre="#pre_"+id;
            var btn="#btn_"+id;
            var parameter=getParameters(id);
            $.ajax({
                type: "GET",
                url: 'jasper_execute_report.php',
                data: 'report_unit='+id+parameter,
                beforeSend: function(){
                    $(pre).attr({
                      style: "visibility:visible;"
                    });
                    $(btn).attr({
                      value: "Generating"
                    });
                },
                success: function(msg) {
                    $(idDiv).html(msg);
                    $(pre).attr({
                      style: "visibility:hidden;"
                    });
                    $(btn).attr({
                      value: "Generate"
                    });
                }
            });
        }
        function getParameters(id){
          var data='';
          $(':input[type=text],:select').each(function(){
               var re = new RegExp(id,"i");
                if (re.exec($(this).attr('id'))) {
                  data+='&'+$(this).attr('name')+"="+$(this).val();
                }
           });
           
          return data;
        }
        function exportReport(id,format){
            var idForm="#"+id;
            $(idForm).attr('action','jasper_export.php?format='+format);
            if(format=='email'){
                $(idForm).attr('target','GB_frame');
            }else if($.browser.chrome) {
                $(idForm).attr('target','_self');
            }else{
                $(idForm).attr('target','_blank');
            }
            $(idForm).submit();
        }
        GB_TYPE = 'w';
		function open_userlog(url,tit){
			url += "?user="+document.forms['logfilter'].user.value+"&code="+document.forms['logfilter'].code.value;
			GB_show(tit,url,'80%','80%');
			return false;
		}
                function openGreyBox(tittle) {
                    //var t = this.title || $(this).text() || this.href;
                    GB_show(tittle,'jasper_include/index.php','50%','50%');
                    return false;
                }
        $(document).ready(function() {
            // CALENDAR
	<?
	$y = strftime("%Y", time() - ((24 * 60 * 60) * 30));
	$m = strftime("%m", time() - ((24 * 60 * 60) * 30));
	$d = strftime("%d", time() - ((24 * 60 * 60) * 30));
	?>
        $('input[name!=Month][name!=Year]').parent().children('.widgetCalendar').DatePicker({
		flat: true,
		format: 'Y-m-d',
		date: '<?php echo date("Y") ?>-<?php echo date("m") ?>-<?php echo date("d") ?>',
                current: '<?php echo date("Y") ?>-<?php echo date("m") ?>-<?php echo date("d") ?>',
		calendars: 1,
		mode: 'single',
                position: 'bottom',
                starts: 1,
                onChange: function(formated){
                    $(this).parent().parent().find('input').attr('value',formated);
                    //$(this).parent().DatePickerHide();
		},
                onShow: function(){
                    $(this).DatePickerSetDate($(this).parent().parent().find('input').attr('value'),1);
                }
	});
        $('input[name=Month]').parent().children('.widgetCalendar').DatePicker({
            flat: true,
            format: 'm',
            date: '<?php echo date("Y") ?>-<?php echo date("m") ?>-<?php echo date("d") ?>',
            calendars: 1,
            view: 'moths',
            mode: 'single',
            position: 'bottom',
            select: 'm',
            starts: 1,
            onChange: function(formated){
                    $(this).parent().parent().find('input').attr('value',formated);
                    //$(this).parent().DatePickerHide();
            },
            onShow: function(){
                $(this).DatePickerSetDate($(this).parent().parent().find('input').attr('value'),1);
            }
        });
        $('input[name=Year]').parent().children('.widgetCalendar').DatePicker({
            flat: true,
            format: 'Y',
            date: '<?php echo date("Y") ?>-<?php echo date("m") ?>-<?php echo date("d") ?>',
            calendars: 1,
            view: 'years',
            mode: 'single',
            position: 'bottom',
            select: 'y',
            starts: 1,
            onChange: function(formated,dates){
                    //$(this).parent().parent().find('input').attr('value',formated);
                    var ob=$(this).parent().parent().find('input').val(formated);
                    for(var i in ob){
                         //alert('propiedad: '+i+' valor: '+ob[i]);
                    }
                    //alert($('#closeOnSelect input').attr('class'));
                    //alert($(this).parent().fillValue);
                    if ($('#closeOnSelect input').attr('checked')) {
			$(this).parent().DatePickerHide();
                    }
            },
            onBeforeShow: function(){
                $(this).DatePickerSetDate($(this).parent().parent().find('input').attr('value'),true);
            }
        });
        $(this).find('.widget input').bind('click', function(){
                $('#'+$(this).next().attr('id')).DatePickerShow();
		return false;
	});
        $('.widgetCalendar div.datepicker').css('position', 'absolute');
        $('.widgetCalendar div.datepicker').css('z-index', '89');
        $('.widgetCalendar').DatePickerHide();
            var hosts = [
                <?= $hosts ?>
                <?= preg_replace("/,$/","",$networks); ?>
            ];
            $("#hosts").autocomplete(hosts, {
                minChars: 0,
                width: 225,
                matchContains: "word",
                autoFill: true,
                formatItem: function(row, i, max) {
                    return row.txt;
                }
            }).result(function(event, item) {
                $("#hosts").val(item.id);
            });

            var plugins = [
                <?php echo $plugins; ?>
                <?= preg_replace("/,$/","",$plugins); ?>
            ];
            $("#_SEM_Report_plugin").autocomplete(plugins, {
                minChars: 0,
                width: 225,
                matchContains: "word",
                autoFill: true,
                formatItem: function(row, i, max) {
                    return row.txt;
                }
            }).result(function(event, item) {
                $("#_SEM_Report_plugin").val(item.id);
                $("#_SEM_Report_pluginName").val(item.txt);
            });

        });
        function showhide(layer,img){
		$(layer).toggle();
		if ($(img).attr('src').match(/plus/))
			$(img).attr('src','../pixmaps/minus.png')
		else
			$(img).attr('src','../pixmaps/plus.png')
	}
	
	function nagios_link (baselink,sensor,link) {
		var fr_down = baselink+sensor+link;
		//top.php?option=6&soption=0&url=
		var url = "../nagios/index.php?opc=reporting&sensor="+sensor+"&fr_down="+fr_down+"&hmenu=Availability&smenu=Reporting";
		parent.location.href=url;
	}
  </script>
</head>
<body>
    <table border="0" class="noborder" id="reportTable" width="90%" align="center">
    <tr>
        <td class="noborder" valign=top>
            <table cellspacing="0" cellpadding="0" border="0" width="100%" class="noborder">
                <tr>
                    <td class="headerpr"><?=_("Reports")?>
                    

                    </td>
                </tr>
                <tr>
                    <td class="noborder">
                        <table border="0" width="100%" id="listReport">
                            <tr>
                              <th class="reportName"><?=_("Report Name")?></th>
                              <th class="reportOptions"><?=_("Report Options")?></th>
                              <th class="export">&nbsp;</th>
                            </tr>
                            <?php
                                // Jasper Reports
                                require_once ('ossim_conf.inc');
                                $client = new JasperClient($conf);

                                $report_unit = "/OSSIM_Complete_Report_p";
                                $report_format = "PDF";
                                $report_params = array();
                                $result = $client->requestReport($report_unit, $report_format,$report_params,'list');
                                foreach($result as $key => $report){
                                    if($report['name']!='Security_DB_EventsTEMPTEMPTEMP'){
                                        $desplegable=false;
                                    }else{
                                        $desplegable=true;
                                    }
                                    $reportOrd[$report['name']]='<form method="POST" action="#" id="'.$report['name'].'">
                            <tr class="CLASS_KEY">
                                <td class="reportName" style="text-align: left; padding-left: 30px">
                                  <h3>';
                                if($desplegable){
                                    $reportOrd[$report['name']].='<a href="javascript:;" onclick="showhide(\'.cell'.$report['name'].'\',\'#img'.$report['name'].'\')"><img src="../pixmaps/plus-small.png" id="img'.$report['name'].'" align="absmiddle" border="0">'._($report['label']).'</a>';
                                }else{
                                    $reportOrd[$report['name']].=_($report['label']);
                                }
                                $reportOrd[$report['name']].='</h3>'; /* (<?= date("Y-m-d H:i:s",$report['creationDate']/1000) ?>)*/
                                    $report_unit = $report['uriString'];
                                    $result2 = $client->requestReport($report_unit, $report_format,$report_params,'listSubreport');
                             $reportOrd[$report['name']].='
                                  <ul>
                                      '.$client->getInputControlHTML($result2,'TYPE_CONTROL').'
                                  </ul>
                             </td>';
                                $reportOrd[$report['name']].='<td class="reportOptions">&nbsp;';
                                if($desplegable){
                                    $reportOrd[$report['name']].='<div class="cell'.$report['name'].'" style="display:none">';
                                }
                                 $reportOrd[$report['name']].='<ul>'.$client->getInputControlHTML($result2).'</ul>
                                 ';
                                if($parameterHtmlTemp=$client->getParameterHtml($report_unit)){
                                    include($parameterHtmlTemp);
                                }
                                if($desplegable){
                                    $reportOrd[$report['name']].='</div>';
                                }
                                $reportOrd[$report['name']].='
                             </td>';
                                 $reportOrd[$report['name']].='<td class="export">&nbsp;';
                                if($desplegable){
                                    $reportOrd[$report['name']].='<div class="cell'.$report['name'].'" style="display:none">';
                                }
                             $reportOrd[$report['name']].='
                                 <input type="hidden" name="reportUser" value="'.$_SESSION['_user'].'">
                                 <input type="hidden" name="reportUnit" value="'.$report['name'].'">
                                 <input type="hidden" name="reportLabel" id="'.$report['name'].'_label" value="'._($report['label']).'">
                                 <table width="150" class="noborder" align="center">
                                    <tr>
                                        <td>
                                 <div>
                                     <div id="pre_'.$report['name'].'" style="visibility:hidden;" class="left">
                                         <img src="../pixmaps/loading.gif" width="16" height="16" />
                                     </div>
                                     <input id="btn_'.$report['name'].'" class="button left" type="button" value="'._('Generate').'" onclick="javascript:executeReport(\''.$report['name'].'\')" />
                                 </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                 <div id="ajax_'.$report['name'].'">
                            ';
                                require('jasper_execute_report.php');
                                $reportOrd[$report['name']].='
                                 </div>
                                        </td>
                                    </tr>
                                 </table>
                            ';
                                if($desplegable){
                                    $reportOrd[$report['name']].='</div>';
                                }
                             $reportOrd[$report['name']].='</td>
                            </tr>
                            </form>';
                                }
                                $key++;
                          $class = ($key++%2==0) ? "par" : "impar";
						  ?>
                            <!-- HOST REPORT -->
							<? if (($hosts!="" || $networks!="") && Session::menu_perms("MenuReports", "ReportsHostReport")) { ?>
                            <form method="GET" action="index.php">
                            <tr class="<?=($key++%2==0) ? "par" : "impar"?>">
                                <td class="reportName" style="text-align: left; padding-left: 30px">
                                  <h3><?= _("Asset Report") ?></h3><?php /* (<?= date("Y-m-d H:i:s",filemtime("host_report.php")) ?>)*/ ?>
                             </td>
                             <td class="reportOptions">
                                <ul><li><label><?= _("Host Name/IP")."<br>"._("Network/CIDR") ?></label><div><input type="text" name="host" id="hosts"></div></li></ul>
                             </td>
                             <td class="export">
                                 <input class="button" type="submit" value="<?= _("Generate") ?>" />
                             </td>
                            </tr>
                            </form>
							<? } ?>
                            <!-- SIM REPORT -->
                            <? if (Session::menu_perms("MenuEvents", "EventsForensics")) {
                            $listSiemDB=' <a href="javascript:;" onclick="showhide(\'#myMenu1\',\'#imgPlusSIEM\')"><img src="../pixmaps/plus.png" border="0" style="position: absolute; top:3px; right:-20px;*right:auto;" id="imgPlusSIEM"></a></h3>
                                        <div id="myMenu1" style="position:absolute; width: 500px; *width:200px; display:none">
                                             <ul id="myMenu" class="contextMenu" style="-moz-user-select: none; display: block;">
                                                <li><a target="main" href="../forensics/base_stat_alerts.php?hmenu=Forensics&smenu=Forensics&sort_order=occur_d"><strong>'._("Unique Events").'</strong><img src="../pixmaps/arrow-000-small.png" border="0" width="16px" height="16px" /></a></li>
                                                <li><a target="main" href="../forensics/base_stat_sensor.php?hmenu=Forensics&smenu=Forensics&sort_order=occur_d"><strong>'._("Sensors").'</strong><img src="../pixmaps/arrow-000-small.png" border="0" width="16px" height="16px" /></a></li>
                                                <li><a target="main" href="../forensics/base_stat_uaddr.php?hmenu=Forensics&smenu=Forensics&sort_order=occur_d&addr_type=1"><strong>'._("Unique Src Addresses").'</strong><img src="../pixmaps/arrow-000-small.png" border="0" width="16px" height="16px" /></a></li>
                                                <li><a target="main" href="../forensics/base_stat_uaddr.php?hmenu=Forensics&smenu=Forensics&sort_order=occur_d&addr_type=2"><strong>'._("Unique Dst Addresses").'</strong><img src="../pixmaps/arrow-000-small.png" border="0" width="16px" height="16px" /></a></li>
                                                <li><a target="main" href="../forensics/base_stat_ports.php?hmenu=Forensics&smenu=Forensics&sort_order=occur_d&proto=-1&port_type=1"><strong>'._("Source TCP/UDP Ports").'</strong><img src="../pixmaps/arrow-000-small.png" border="0" width="16px" height="16px" /></a></li>
                                                <li class="destTcUdPorts"><a target="main" href="../forensics/base_stat_ports.php?hmenu=Forensics&smenu=Forensics&sort_order=occur_d&port_type=2&proto=-1"><strong>'._("Destination TCP/UDP Ports").'</strong><img src="../pixmaps/arrow-000-small.png" border="0" width="16px" height="16px" /></a></li>
                                                <li><a target="main" href="../forensics/base_stat_plugins.php?hmenu=Forensics&smenu=Forensics&sort_order=occur_d"><strong>'._("Unique Plugins").'</strong><img src="../pixmaps/arrow-000-small.png" border="0" width="16px" height="16px" /></a></li>
                                                <li><a target="main" href="../forensics/base_stat_iplink.php?hmenu=Forensics&smenu=Forensics&sort_order=events_d&fqdn=no"><strong>'._("Unique IP links").'</strong><img src="../pixmaps/arrow-000-small.png" border="0" width="16px" height="16px" /></a></li>
                                                <li><a target="main" href="../forensics/base_stat_country.php?hmenu=Forensics&smenu=Forensics"><strong>'._("Unique Country Events").'</strong><img src="../pixmaps/arrow-000-small.png" border="0" width="16px" height="16px" /></a></li>
                                            </ul>
                                        </div>
';
                            //echo '<!-- '.$reportOrd['Security_Report'].' -->';




								$class = ($key++%2==0) ? "par" : "impar";
								$reportOrd['Security_Report']=str_replace("CLASS_KEY",$class,$reportOrd['Security_Report']);
                                                                $reportOrd['Security_Report']=str_replace("<h3>",'<h3 style="position:relative">',$reportOrd['Security_Report']);
                                                                echo $reportOrd['Security_Report']=str_replace("</h3>",$listSiemDB,$reportOrd['Security_Report']);

                            } ?>
                            <!-- Security DB Events REPORT
                            <? if (Session::menu_perms("MenuEvents", "EventsForensics")) {/* ?>
			<form method="GET" action="../forensics/base_qry_main.php" target="main">
                            <input type="hidden" name="hmenu" value="Forensics">
                            <input type="hidden" name="smenu" value="Forensics">
                            <input type="hidden" name="num_result_rows" value="-1">
                            <input type="hidden" name="submit" value="Query+DB">
                            <input type="hidden" name="current_view" value="-1">
                            <tr class="<?=($key++%2==0) ? "par" : "impar"?>">
                                <td class="reportName" style="text-align: left; padding-left: 30px">
                                  <h3><?= _("SIEM Events events") ?></h3><?php /*  (<?= date("Y-m-d H:i:s",filemtime("../vulnmeter/index.php")) ?>) */ ?>
                             </td>
                             <td class="reportOptions">
                                &nbsp;
                             </td>
                             <td class="export">
                                 <input class="button" type="submit" value="<?= _("View") ?>" />
                             </td>
                            </tr>
                            </form> -->
                            <? } /*?>
                            <!-- SIEM Events Unique Events -->
                            <? if (Session::menu_perms("MenuEvents", "EventsForensics")) { ?>
                            <tr class="<?=($key++%2==0) ? "par" : "impar"?>">
                                 <td class="reportName" style="text-align: left; padding-left: 30px">
                                     <table border="0" width="100%" id="listReport" class="noborder" style="background-color: transparent;">
                                         <tr>
                                             <td style="border-bottom: none;text-align: left;vertical-align: top;width:100px"><h3><?= _("SIEM DB") ?></h3></td>
                                             <td style="border-bottom: none;">
                                                 <table border="0" width="100%" id="listReport" class="noborder siemDb">
                                                    <tr>
                                                        <td><a target="main" href="../forensics/base_stat_alerts.php?hmenu=Forensics&smenu=Forensics&sort_order=occur_d"><?= _("Unique Events") ?><img src="../pixmaps/arrow-000-small.png" border="0" width="16px" height="16px" /></a></td>
                                                    </tr>
                                                    <tr>
                                                        <td><a target="main" href="../forensics/base_stat_sensor.php?hmenu=Forensics&smenu=Forensics&sort_order=occur_d"><?= _("Sensors") ?><img src="../pixmaps/arrow-000-small.png" border="0" width="16px" height="16px" /></a></td>
                                                    </tr>
                                                    <tr>
                                                        <td><a target="main" href="../forensics/base_stat_uaddr.php?hmenu=Forensics&smenu=Forensics&sort_order=occur_d&addr_type=1"><?= _("Unique Src Addresses") ?><img src="../pixmaps/arrow-000-small.png" border="0" width="16px" height="16px" /></a></td>
                                                    </tr>
                                                    <tr>
                                                        <td><a target="main" href="../forensics/base_stat_uaddr.php?hmenu=Forensics&smenu=Forensics&sort_order=occur_d&addr_type=2"><?= _("Unique Dst Addresses") ?><img src="../pixmaps/arrow-000-small.png" border="0" width="16px" height="16px" /></a></td>
                                                    </tr>
                                                    <tr>
                                                        <td><a target="main" href="../forensics/base_stat_ports.php?hmenu=Forensics&smenu=Forensics&sort_order=occur_d&proto=-1&port_type=1"><?= _("Source TCP/UDP Ports") ?><img src="../pixmaps/arrow-000-small.png" border="0" width="16px" height="16px" /></a></td>
                                                    </tr>
                                                    <tr>
                                                        <td><a target="main" href="../forensics/base_stat_ports.php?hmenu=Forensics&smenu=Forensics&sort_order=occur_d&port_type=2&proto=-1"><?= _("Destination TCP/UDP Ports") ?><img src="../pixmaps/arrow-000-small.png" border="0" width="16px" height="16px" /></a></td>
                                                    </tr>
                                                    <tr>
                                                        <td><a target="main" href="../forensics/base_stat_plugins.php?hmenu=Forensics&smenu=Forensics&sort_order=occur_d"><?= _("Unique Plugins") ?><img src="../pixmaps/arrow-000-small.png" border="0" width="16px" height="16px" /></a></td>
                                                    </tr>
                                                    <tr>
                                                        <td><a target="main" href="../forensics/base_stat_iplink.php?hmenu=Forensics&smenu=Forensics&sort_order=events_d&fqdn=no"><?= _("Unique IP links") ?><img src="../pixmaps/arrow-000-small.png" border="0" width="16px" height="16px" /></a></td>
                                                    </tr>
                                                    <tr>
                                                        <td><a target="main" href="../forensics/base_stat_country.php?hmenu=Forensics&smenu=Forensics"><?= _("Unique Country Events") ?><img src="../pixmaps/arrow-000-small.png" border="0" width="16px" height="16px" /></a></td>
                                                    </tr>
                                                </table>
                                             </td>
                                         </tr>
                                     </table>
                                 </td>
                                 <td class="reportOptions">
                                    &nbsp;
                                 </td>
                                 <td class="export">
                                     &nbsp;
                                 </td>
                            </tr>
                            <? } */?>
                            <!-- SIEM Events Unique Events -->
                            <? /*if (Session::menu_perms("MenuEvents", "EventsForensics")) { ?>
			<form method="GET" action="../forensics/base_stat_alerts.php" target="main">
                            <input type="hidden" name="hmenu" value="Forensics">
                            <input type="hidden" name="smenu" value="Forensics">
                            <input type="hidden" name="sort_order" value="occur_d">
                            <tr class="<?=($key++%2==0) ? "par" : "impar"?>">
                                <td class="reportName" style="text-align: left; padding-left: 30px">
                                  <h3><?= _("SIEM Events Unique Events") ?></h3><?php /*  (<?= date("Y-m-d H:i:s",filemtime("../vulnmeter/index.php")) ?>) */ /* ?>
                             </td>
                             <td class="reportOptions">
                                &nbsp;
                             </td>
                             <td class="export">
                                 <input class="button" type="submit" value="<?= _("View") ?>" />
                             </td>
                            </tr>
                            </form>
                            <? } ?>
                            <!-- SIEM Events Sensors -->
                            <? if (Session::menu_perms("MenuEvents", "EventsForensics")) { ?>
			<form method="GET" action="../forensics/base_stat_sensor.php" target="main">
                            <input type="hidden" name="hmenu" value="Forensics">
                            <input type="hidden" name="smenu" value="Forensics">
                            <input type="hidden" name="sort_order" value="occur_d">
                            <tr class="<?=($key++%2==0) ? "par" : "impar"?>">
                                <td class="reportName" style="text-align: left; padding-left: 30px">
                                  <h3><?= _("SIEM Events Sensors") ?></h3><?php /*  (<?= date("Y-m-d H:i:s",filemtime("../vulnmeter/index.php")) ?>) */ /* ?>
                             </td>
                             <td class="reportOptions">
                                &nbsp;
                             </td>
                             <td class="export">
                                 <input class="button" type="submit" value="<?= _("View") ?>" />
                             </td>
                            </tr>
                            </form>
                            <? } ?>
                            <!-- SIEM Events Unique Source Addresses -->
                            <? if (Session::menu_perms("MenuEvents", "EventsForensics")) { ?>
			<form method="GET" action="../forensics/base_stat_uaddr.php" target="main">
                            <input type="hidden" name="hmenu" value="Forensics">
                            <input type="hidden" name="smenu" value="Forensics">
                            <input type="hidden" name="sort_order" value="occur_d">
                            <input type="hidden" name="addr_type" value="1">
                            <tr class="<?=($key++%2==0) ? "par" : "impar"?>">
                                <td class="reportName" style="text-align: left; padding-left: 30px">
                                  <h3><?= _("SIEM Events Unique Src Addresses") ?></h3><?php /*  (<?= date("Y-m-d H:i:s",filemtime("../vulnmeter/index.php")) ?>) */ /*?>
                             </td>
                             <td class="reportOptions">
                                &nbsp;
                             </td>
                             <td class="export">
                                 <input class="button" type="submit" value="<?= _("View") ?>" />
                             </td>
                            </tr>
                            </form>
                            <? } ?>
                            <!-- SIEM Events Unique Destination Addresses -->
                            <? if (Session::menu_perms("MenuEvents", "EventsForensics")) { ?>
			<form method="GET" action="../forensics/base_stat_uaddr.php" target="main">
                            <input type="hidden" name="hmenu" value="Forensics">
                            <input type="hidden" name="smenu" value="Forensics">
                            <input type="hidden" name="sort_order" value="occur_d">
                            <input type="hidden" name="addr_type" value="2">
                            <tr class="<?=($key++%2==0) ? "par" : "impar"?>">
                                <td class="reportName" style="text-align: left; padding-left: 30px">
                                  <h3><?= _("SIEM Events Unique Dst Addresses") ?></h3><?php /*  (<?= date("Y-m-d H:i:s",filemtime("../vulnmeter/index.php")) ?>) */ /*?>
                             </td>
                             <td class="reportOptions">
                                &nbsp;
                             </td>
                             <td class="export">
                                 <input class="button" type="submit" value="<?= _("View") ?>" />
                             </td>
                            </tr>
                            </form>
                            <? } ?>
                            <!-- SIEM Events Source TCP/UDP Ports -->
                            <? if (Session::menu_perms("MenuEvents", "EventsForensics")) { ?>
			<form method="GET" action="../forensics/base_stat_ports.php" target="main">
                            <input type="hidden" name="hmenu" value="Forensics">
                            <input type="hidden" name="smenu" value="Forensics">
                            <input type="hidden" name="sort_order" value="occur_d">
                            <input type="hidden" name="proto" value="-1">
                            <input type="hidden" name="port_type" value="1">
                            <tr class="<?=($key++%2==0) ? "par" : "impar"?>">
                                <td class="reportName" style="text-align: left; padding-left: 30px">
                                  <h3><?= _("SIEM Events Source TCP/UDP Ports") ?></h3><?php /*  (<?= date("Y-m-d H:i:s",filemtime("../vulnmeter/index.php")) ?>) */ /*?>
                             </td>
                             <td class="reportOptions">
                                &nbsp;
                             </td>
                             <td class="export">
                                 <input class="button" type="submit" value="<?= _("View") ?>" />
                             </td>
                            </tr>
                            </form>
                            <? } ?>
                            <!-- SIEM Events Destination TCP/UDP Ports -->
                            <? if (Session::menu_perms("MenuEvents", "EventsForensics")) { ?>
			<form method="GET" action="../forensics/base_stat_ports.php" target="main">
                            <input type="hidden" name="hmenu" value="Forensics">
                            <input type="hidden" name="smenu" value="Forensics">
                            <input type="hidden" name="sort_order" value="occur_d">
                            <input type="hidden" name="port_type" value="2">
                            <input type="hidden" name="proto" value="-1">
                            <tr class="<?=($key++%2==0) ? "par" : "impar"?>">
                                <td class="reportName" style="text-align: left; padding-left: 30px">
                                  <h3><?= _("SIEM Events Destination TCP/UDP Ports") ?></h3><?php /*  (<?= date("Y-m-d H:i:s",filemtime("../vulnmeter/index.php")) ?>) */ /*?>
                             </td>
                             <td class="reportOptions">
                                &nbsp;
                             </td>
                             <td class="export">
                                 <input class="button" type="submit" value="<?= _("View") ?>" />
                             </td>
                            </tr>
                            </form>
                            <? } ?>
                            <!-- SIEM Events Unique Plugins -->
                            <? if (Session::menu_perms("MenuEvents", "EventsForensics")) { ?>
			<form method="GET" action="../forensics/base_stat_plugins.php" target="main">
                            <input type="hidden" name="hmenu" value="Forensics">
                            <input type="hidden" name="smenu" value="Forensics">
                            <input type="hidden" name="sort_order" value="occur_d">
                            <tr class="<?=($key++%2==0) ? "par" : "impar"?>">
                                <td class="reportName" style="text-align: left; padding-left: 30px">
                                  <h3><?= _("SIEM Events Unique Plugins") ?></h3><?php /*  (<?= date("Y-m-d H:i:s",filemtime("../vulnmeter/index.php")) ?>) */ /*?>
                             </td>
                             <td class="reportOptions">
                                &nbsp;
                             </td>
                             <td class="export">
                                 <input class="button" type="submit" value="<?= _("View") ?>" />
                             </td>
                            </tr>
                            </form>
                            <? } ?>
                            <!-- SIEM Events Unique IP links -->
                            <? if (Session::menu_perms("MenuEvents", "EventsForensics")) { ?>
			<form method="GET" action="../forensics/base_stat_iplink.php" target="main">
                            <input type="hidden" name="hmenu" value="Forensics">
                            <input type="hidden" name="smenu" value="Forensics">
                            <input type="hidden" name="sort_order" value="events_d">
                            <input type="hidden" name="fqdn" value="no">
                            <tr class="<?=($key++%2==0) ? "par" : "impar"?>">
                                <td class="reportName" style="text-align: left; padding-left: 30px">
                                  <h3><?= _("SIEM Events Unique IP links") ?></h3><?php /*  (<?= date("Y-m-d H:i:s",filemtime("../vulnmeter/index.php")) ?>) */ /*?>
                             </td>
                             <td class="reportOptions">
                                &nbsp;
                             </td>
                             <td class="export">
                                 <input class="button" type="submit" value="<?= _("View") ?>" />
                             </td>
                            </tr>
                            </form>
                            <? } ?>
                            <!-- SIEM Events Unique Country Events -->
                            <? if (Session::menu_perms("MenuEvents", "EventsForensics")) { ?>
			<form method="GET" action="../forensics/base_stat_country.php" target="main">
                            <input type="hidden" name="hmenu" value="Forensics">
                            <input type="hidden" name="smenu" value="Forensics">
                            <tr class="<?=($key++%2==0) ? "par" : "impar"?>">
                                <td class="reportName" style="text-align: left; padding-left: 30px">
                                  <h3><?= _("SIEM Events Unique Country Events") ?></h3><?php /*  (<?= date("Y-m-d H:i:s",filemtime("../vulnmeter/index.php")) ?>) */ /*?>
                             </td>
                             <td class="reportOptions">
                                &nbsp;
                             </td>
                             <td class="export">
                                 <input class="button" type="submit" value="<?= _("View") ?>" />
                             </td>
                            </tr>
                            </form>
                            <? } */?>
							<?/* if (Session::menu_perms("MenuEvents", "EventsForensics")) {
								$class = ($key++%2==0) ? "par" : "impar";								
                                                                echo str_replace("CLASS_KEY",$class,$reportOrd['Security_DB_Events']);
							} */?>
                            <!-- SEM REPORT -->
							<? if (Session::menu_perms("MenuEvents", "ControlPanelSEM")) {
								$class = ($key++%2==0) ? "par" : "impar";
								echo str_replace("CLASS_KEY",$class,$reportOrd['SEM_Report']);
							} ?>
                            <!-- Alarms REPORT -->
							<? if (Session::menu_perms("MenuIncidents", "ControlPanelAlarms")) {
								$class = ($key++%2==0) ? "par" : "impar";
								echo str_replace("CLASS_KEY",$class,$reportOrd['Alarms_Report']);
							} ?>
                            <!-- Business_and_Compliance_ISO_PCI REPORT -->
							<? if (Session::menu_perms("MenuReports", "ReportsReportServer")) {
								$class = ($key++%2==0) ? "par" : "impar";
								echo str_replace("CLASS_KEY",$class,$reportOrd['Business_and_Compliance_ISO_PCI_Report']);
							} ?>
                            <!-- VULNERABILITIES REPORT -->
                            <? if (Session::menu_perms("MenuEvents", "EventsVulnerabilities")) { ?>
							<form method="POST" action="../vulnmeter/respdf.php?ipl=all&scantype=M" target="main">
                            <input type="hidden" name="hmenu" value="Vulnerabilities">
                            <input type="hidden" name="smenu" value="Vulnerabilities">
                            <tr class="<?=($key++%2==0) ? "par" : "impar"?>">
                                <td class="reportName" style="text-align: left; padding-left: 30px">
                                  <h3><?= _("Vulnerabilities Report") ?></h3>
                             </td>
                             <td class="reportOptions">
                                &nbsp;
                             </td>
                             <td class="export">
                                 <input class="button" type="submit" value="<?= _("View") ?>" />
                             </td>
                            </tr>
                            </form>
                            <?php /*
                            <!-- VULNERABILITIES REPORT -->
                            <? if (Session::menu_perms("MenuEvents", "EventsVulnerabilities")) { ?>
							<form method="GET" action="../vulnmeter/index.php" target="main">
                            <input type="hidden" name="hmenu" value="Vulnerabilities">
                            <input type="hidden" name="smenu" value="Vulnerabilities">
                            <tr class="<?=($key++%2==0) ? "par" : "impar"?>">
                                <td class="reportName" style="text-align: left; padding-left: 30px">
                                  <h3><?= _("Vulnerabilities Report") ?></h3>
                             </td>
                             <td class="reportOptions">
                                &nbsp;
                             </td>
                             <td class="export">
                                 <input class="button" type="submit" value="<?= _("View") ?>" />
                             </td>
                            </tr>
                            </form>
                            */ ?>
                            <!-- THREADS AND VULNERABILITIES DATABASE -->
							<form method="GET" action="../vulnmeter/threats-db.php" target="main">
                            <input type="hidden" name="hmenu" value="Vulnerabilities">
                            <input type="hidden" name="smenu" value="Database">
                            <tr class="<?=($key++%2==0) ? "par" : "impar"?>">
                                <td class="reportName" style="text-align: left; padding-left: 30px">
                                  <h3><?= _("Threats & Vulnerabilities Database") ?></h3><?php /*  (<?= date("Y-m-d H:i:s",filemtime("../vulnmeter/index.php")) ?>) */ ?>
                             </td>
                             <td class="reportOptions">
                                &nbsp;
                             </td>
                             <td class="export">
                                 <input class="button" type="submit" value="<?= _("View") ?>" />
                             </td>
                            </tr>
                            </form>
							<? } ?>
                             <!-- ANOMALIES REPORT -->
							 <? if (Session::menu_perms("MenuEvents", "EventsAnomalies")) { ?>
                            <form method="GET" action="../control_panel/anomalies.php" target="main">
                            <input type="hidden" name="hmenu" value="Anomalies">
                            <input type="hidden" name="smenu" value="Anomalies">
                            <tr class="<?=($key++%2==0) ? "par" : "impar"?>">
                                <td class="reportName" style="text-align: left; padding-left: 30px">
                                  <h3><?= _("Anomalies Report") ?></h3><?php /*  (<?= date("Y-m-d H:i:s",filemtime("../control_panel/anomalies.php")) ?>) */ ?>
                             </td>
                             <td class="reportOptions">
                                &nbsp;
                             </td>
                             <td class="export">
                                 <input class="button" type="submit" value="<?= _("View") ?>" />
                             </td>
                            </tr>
                            </form>
							<? } ?>
							<!-- Tickets Status REPORT -->
							 <? if (Session::menu_perms("MenuIncidents", "IncidentsIncidents")) { ?>
                            <form method="GET" action="../report/incidentreport.php" target="main">
                            <input type="hidden" name="hmenu" value="Tickets">
                            <input type="hidden" name="smenu" value="Report">
                            <tr class="<?=($key++%2==0) ? "par" : "impar"?>">
                                <td class="reportName" style="text-align: left; padding-left: 30px">
                                  <h3><?= _("Tickets Status") ?></h3><?php /*  (<?= date("Y-m-d H:i:s",filemtime("../control_panel/anomalies.php")) ?>) */ ?>
                             </td>
                             <td class="reportOptions">
                                &nbsp;
                             </td>
                             <td class="export">
                                 <input class="button" type="submit" value="<?= _("View") ?>" />
                             </td>
                            </tr>
                            </form>
							<? } ?>
                             <!-- Metrics_Report REPORT -->
                            <? if (Session::menu_perms("MenuControlPanel", "ControlPanelMetrics")) { 
								$class = ($key++%2==0) ? "par" : "impar";
								echo str_replace("CLASS_KEY",$class,$reportOrd['Metrics_Report']);
							} ?>
                             <!-- Geographic_Report REPORT -->
                             <? if (Session::menu_perms("MenuEvents", "EventsForensics")) {
                                $class = ($key++%2==0) ? "par" : "impar";
								echo str_replace("CLASS_KEY",$class,$reportOrd['Geographic_Report']);
                             } ?>
                            <!-- Geographic_Report REPORT -->
                             <!-- Incidents_Report REPORT -->
                             <? if (Session::menu_perms("MenuReports", "ReportsReportServer")) {
                                $class = ($key++%2==0) ? "par" : "impar";
								echo str_replace("CLASS_KEY",$class,$reportOrd['Incidents_Report']);
                             }?>
                            <!-- Geographic_Report REPORT -->
                            <?php //echo $reportOrd['Mexico_EventWindows-Seguridad-lista'] ?>

                            <?php //var_dump($reportOrd); ?>
							<!-- User Log REPORT -->
							<? if (Session::menu_perms("MenuConfiguration", "ToolsUserLog")) { ?>
							<form name="logfilter" method="GET" action="<?php echo $_SERVER["SCRIPT_NAME"]?>">
							<tr class="<?=($key++%2==0) ? "par" : "impar"?>">
                                <td class="reportName" style="text-align: left; padding-left: 30px">
                                  <h3><?= _("User Activity Report") ?></h3>
                             </td>
                             <td class="reportOptions">
                                <!-- filter -->
									<ul>
									<li>
										<label><?php echo gettext("User"); ?></label><br>
										<select name="user">
										<? $user = Session::get_session_user(); ?>
										
										<? if (Session::am_i_admin()) { ?>
											<option <?php if ("" == $user) echo " selected " ?> value=""><?=_('All') ?></option>
										<?php
								if ($session_list) {
									foreach($session_list as $session) {
										$login = $session->get_login();
								?>
											<option  <?php if ($login == $user) echo " selected "; ?> value="<?php echo $login; ?>"><?php echo $login; ?></option>                
										<?php
									}
								}
								?>
										<? } elseif ($is_pro_admin) { ?>
										<? foreach ($myusers as $myuser) { ?>
										<option value="<?=$myuser['login']?>"><?=$myuser['login']?></option>
										<? } ?>
										<? } else { ?>
										<option value="<?=$user?>"><?=$user?></option>
										<? } ?>
										</select>
									  </li>
									  <li>
										<br><label><?php echo gettext("Action"); ?></label><br>
										<select name="code" style="width:100px">
											<option <?php
								if ("" == $code) echo " selected " ?>
												 value=""><?=_('All') ?></option>"; ?>
										<?php
								if ($code_list) {
									foreach($code_list as $code_log) {
										$code_aux = $code_log->get_code();
								?>
												 <option  <?php
										if ($code_aux == $code) echo " selected "; ?>
												  value="<?php
										echo $code_aux; ?>"><?php
										echo "[" . sprintf("%02d", $code_aux) . "] " . _(preg_replace('|%.*?%|', " ", $code_log->get_descr())); ?>
												</option>                
										<?php
									}
								}
								?>
										</select>
									  </li>
									  </ul><br><br>
                             </td>
                             <td class="export">
                                 <input class="button" type="button" onclick="open_userlog('../userlog/user_action_log.php','<?=_("User Log Report")?>')" value="<?= _("View") ?>" />
                             </td>
                            </tr>
							</form>
							<? } ?>
							
							<!-- Availability REPORT -->
							<? if (Session::menu_perms("MenuMonitors", "MonitorsAvailability")) { ?>
							<form name="avfilter" method="GET" action="<?php echo $_SERVER["SCRIPT_NAME"]?>">
							<input type="hidden" name="nagioslink" value="<?=urlencode($scheme.'://')?>">
							<tr class="<?=($key++%2==0) ? "par" : "impar"?>">
                                <td class="reportName" style="text-align: left; padding-left: 30px">
                                  <h3><?= _("Availability Report") ?></h3>
                             </td>
                             <td class="reportOptions">
                                <!-- filter -->
									<ul>
									<li>
										<label><?php echo gettext("Sensor"); ?></label><br>
										<select name="sensor">
											<?
											foreach($sensor_list as $s) {
												/*
												* one more option for each sensor (at policy->sensors)
												*/
												$option = "<option ";
												if ($sensor == $s->get_ip()) $option.= " SELECTED ";
												$option.= ' value="'. $s->get_ip() . '">' . $s->get_name() . '</option>';
												print "$option\n";
											}
											?>
										</select>
									  </li>
									  <li>
										<br><label><?php echo gettext("Section"); ?></label><br>
										<select name="section">
											<option value="<?=urlencode($nagios."cgi-bin/trends.cgi")?>"><?=_("Trends")?>
											<option value="<?=urlencode($nagios."cgi-bin/avail.cgi")?>"><?=_("Availability")?>
											<option value="<?=urlencode($nagios."cgi-bin/histogram.cgi")?>"><?=_("Event Histogram")?>
											<option value="<?=urlencode($nagios."cgi-bin/history.cgi?host=all")?>"><?=_("Event History")?>
											<option value="<?=urlencode($nagios."cgi-bin/summary.cgi")?>"><?=_("Event Summary")?>
											<option value="<?=urlencode($nagios."cgi-bin/notifications.cgi?contact=all")?>"><?=_("Notifications")?>
											<option value="<?=urlencode($nagios."cgi-bin/showlog.cgi")?>"><?=_("Performance Info")?>
										</select>
									  </li>
									  </ul><br><br>
                             </td>
                             <td class="export">
                                 <input class="button" type="button" onclick="nagios_link(document.avfilter.nagioslink.value,document.avfilter.sensor.value,document.avfilter.section.value)" value="<?= _("View") ?>" />
                             </td>
                            </tr>
							</form>
							<? } ?>
							
                            <!-- OTHER REPORTS -->
                            <?php
                            foreach($reportOrd as $key2 => $value){
                                if($key2!='AssetReport'&&$key2!='SIEM_Events_Unique_IP_Links'&&$key2!='Security_DB_Unique_Country_Events'&&$key2!='Security_DB_Destination_Port'&&$key2!='Security_DB_Source_Port'&&$key2!='Security_DB_Unique_Address'&&$key2!='Security_DB_Unique_Plugin'&&$key2!='Security_DB_Unique_Events'&&$key2!='Security_DB_Sensors'&&$key2!='Security_DB_Events'&&$key2!='Security_Report'&&$key2!='SEM_Report'&&$key2!='Alarms_Report'&&$key2!='Business_and_Compliance_ISO_PCI_Report'&&$key2!='Metrics_Report'&&$key2!='Geographic_Report'&&$key2!='Incidents_Report'){
                                    $class = ($key++%2==0) ? "par" : "impar";
                                    echo str_replace("CLASS_KEY",$class,$value);
                                }
                            }
                            ?>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
