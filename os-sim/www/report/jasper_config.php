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
Session::logcheck("MenuReports", "ReportsReportServer");
?>
<html>
<head>
  <title> <?php echo gettext("OSSIM Framework"); ?> - Jasper Reports </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <link rel="stylesheet" type="text/css" href="../style/jasper.css">
  <link rel="stylesheet" href="../style/colorpicker.css" type="text/css" />
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/jquery.colorpicker.js"></script>
  <script language="javascript">
        function restoreOriginalStyle(id){
            var backgroundTitle='#8CC221';
            var txtTitle='#000000';
            var backgroundSubtitle='#7A7A7A';
            var txtSubtitle='#FFFFFF';
            var txtContent='#000000';

            var idForm="#"+id+"_3";

            $('#backgroundTitle'+id+' div input').attr('value',backgroundTitle);
            $('#colorTitle'+id+' div input').attr('value',txtTitle);
            $('#backgroundSubtitle'+id+' div input').attr('value',backgroundSubtitle);
            $('#colorSubtitle'+id+' div input').attr('value',txtSubtitle);
            $('#colorContent'+id+' div input').attr('value',txtContent);

            $(idForm).submit();
        }
  </script>
</head>
<body>
    <table border="0" class="noborder" id="reportTable" width="90%" align="center">
    <tr>
        <td class="noborder" valign=top>
            <table cellspacing="0" cellpadding="0" border="0" width="100%" class="noborder">
                <tr>
                    <td class="headerpr"><?= _("Reports") ?></td>
                </tr>
                <tr>
                    <td class="noborder">
                        <table border="0" width="100%" id="listReport">
                            <?php

                                require_once ('ossim_conf.inc');
                                $client = new JasperClient($conf);

                                $report_unit = "/OSSIM_Complete_Report_p";
                                $report_format = "PDF";
                                $report_params = array();
                                $result = $client->requestReport($report_unit, $report_format,$report_params,'list');
                                $tempJS='';

                                foreach($result as $key => $report){ $i++;

                                  $reportOrd[$report['name']]='<tr class="CLASS_KEY">
                                          <td colspan="2" class="reportName" style="text-align: left; padding-left: 30px">
                                                  <h3>'._($report['label']).'</h3>';
                                
                                                        $uriStyle=$report['uriString'].'_files/Style.jrtx';

                                                        $client = new JasperClient($conf);

                                                        $result = $client->getResource($uriStyle,'jrtx');
                                                        $backgroundTitle=$result[0]['backcolor'];
                                                        $colorTitle=$result[1]['forecolor'];
                                                        $backgroundSubtitle=$result[2]['backcolor'];
                                                        $colorSubtitle=$result[3]['forecolor'];
                                                        $colorContent=$result[4]['forecolor'];
                                   $reportOrd[$report['name']].='
                                          </td>
                                  </tr>
						  <tr class="CLASS_KEY">
                             <td class="reportName" style="text-align: left; padding-left: 30px;">
                                <form method="POST" action="jasper_config_modify.php" id="'.$report['name'].'_3" enctype="multipart/form-data">
									<table cellspacing="0" cellpadding="0" border="0" width="100%" class="noborder tableColorSelector" style="min-width:300px;">
                                    <tr>
										<td></td>
										<td>'._("Background Color").'</td>
										<td>'._("Foreground Color").'</td>
									</tr>
                                    <tr>
										<td>'._("Title").'</td>
										<td style="padding-left:25px">
                                              <div id="backgroundTitle'.$report['name'].'" class="colorSelector">
                                                  <div style="background-color: '.$backgroundTitle.';">
                                                      <input type="hidden" name="backgroundTitle" value="'.$backgroundTitle.'">
                                                  </div>
                                              </div>';
                                                                                    $tempJS.="
                                                  <script type='text/javascript'>
                                                    //<![CDATA[
                                                    $('#backgroundTitle".$report['name']."').ColorPicker({
                                                        color: '".$backgroundTitle."',
                                                        onShow: function (colpkr) {
                                                                $(colpkr).fadeIn(500);
                                                                return false;
                                                        },
                                                        onHide: function (colpkr) {
                                                                $(colpkr).fadeOut(500);
                                                                return false;
                                                        },
                                                        onChange: function (hsb, hex, rgb) {
                                                                $('#backgroundTitle".$report['name']." div').css('backgroundColor', '#' + hex);
                                                                $('#backgroundTitle".$report['name']." div input').attr('value','#' + hex);
                                                        }
                                                });
                                                    //]]>
                                                  </script>";
                                         $reportOrd[$report['name']].='</td>
                                         <td style="padding-left:25px">
                                              <div id="colorTitle'.$report['name'].'" class="colorSelector">
                                                  <div style="background-color: '.$colorTitle.';">
                                                      <input type="hidden" name="colorTitle" value="'.$colorTitle.'">
                                                  </div>
                                              </div>';
                                            $tempJS.="
                                                  <script type='text/javascript'>
                                                    //<![CDATA[
                                                    $('#colorTitle".$report['name']."').ColorPicker({
                                                        color: '".$colorTitle."',
                                                        onShow: function (colpkr) {
                                                                $(colpkr).fadeIn(500);
                                                                return false;
                                                        },
                                                        onHide: function (colpkr) {
                                                                $(colpkr).fadeOut(500);
                                                                return false;
                                                        },
                                                        onChange: function (hsb, hex, rgb) {
                                                                $('#colorTitle".$report['name']." div').css('backgroundColor', '#' + hex);
                                                                $('#colorTitle".$report['name']." div input').attr('value','#' + hex);
                                                        }
                                                });
                                                    //]]>
                                                </script>";
                                $reportOrd[$report['name']].='
                                         </td>
                                    </tr>
                                    <tr>
										<td>'._("Subtitle").'</td>
                                        <td style="padding-left:25px">
                                              <div id="backgroundSubtitle'.$report['name'].'" class="colorSelector">
                                                  <div style="background-color: '.$backgroundSubtitle.';">
                                                      <input type="hidden" name="backgroundSubtitle" value="'.$backgroundSubtitle.'">
                                                  </div>
                                              </div>';
                                                $tempJS.="
                                                  <script type='text/javascript'>
                                                    //<![CDATA[
                                                    $('#backgroundSubtitle".$report['name']."').ColorPicker({
                                                        color: '".$backgroundSubtitle."',
                                                        onShow: function (colpkr) {
                                                                $(colpkr).fadeIn(500);
                                                                return false;
                                                        },
                                                        onHide: function (colpkr) {
                                                                $(colpkr).fadeOut(500);
                                                                return false;
                                                        },
                                                        onChange: function (hsb, hex, rgb) {
                                                                $('#backgroundSubtitle".$report['name']." div').css('backgroundColor', '#' + hex);
                                                                $('#backgroundSubtitle".$report['name']." div input').attr('value','#' + hex);
                                                        }
                                                });
                                                    //]]>
                                                  </script>";
                                $reportOrd[$report['name']].='
                                          </td>
                                          <td style="padding-left:25px">
                                              <div id="colorSubtitle'.$report['name'].'" class="colorSelector">
                                                  <div style="background-color: '.$colorSubtitle.';">
                                                      <input type="hidden" name="colorSubtitle" value="'.$colorSubtitle.'">
                                                  </div>
                                              </div>';
                                        $tempJS.="
                                                  <script type='text/javascript'>
                                                    //<![CDATA[
                                                    $('#colorSubtitle".$report['name']."').ColorPicker({
                                                        color: '".$colorSubtitle."',
                                                        onShow: function (colpkr) {
                                                                $(colpkr).fadeIn(500);
                                                                return false;
                                                        },
                                                        onHide: function (colpkr) {
                                                                $(colpkr).fadeOut(500);
                                                                return false;
                                                        },
                                                        onChange: function (hsb, hex, rgb) {
                                                                $('#colorSubtitle".$report['name']." div').css('backgroundColor', '#' + hex);
                                                                $('#colorSubtitle".$report['name']." div input').attr('value','#' + hex);
                                                        }
                                                });
                                                    //]]>
                                                  </script>";
                                $reportOrd[$report['name']].='
                                          </td>
                                      </tr>
                                      <tr>
                                          <td>'._("Content").'</td>
                                          <td>&nbsp;</td>
                                          <td style="padding-left:25px">
                                              <div id="colorContent'.$report['name'].'" class="colorSelector">
                                                  <div style="background-color: '.$colorContent.';">
                                                      <input type="hidden" name="colorContent" value="'.$colorContent.'">
                                                  </div>
                                              </div>';
                                                     $tempJS.="
                                                  <script type='text/javascript'>
                                                    //<![CDATA[
                                                    $('#colorContent".$report['name']."').ColorPicker({
                                                        color: '".$colorContent."',
                                                        onShow: function (colpkr) {
                                                                $(colpkr).fadeIn(500);
                                                                return false;
                                                        },
                                                        onHide: function (colpkr) {
                                                                $(colpkr).fadeOut(500);
                                                                return false;
                                                        },
                                                        onChange: function (hsb, hex, rgb) {
                                                                $('#colorContent".$report['name']." div').css('backgroundColor', '#' + hex);
                                                                $('#colorContent".$report['name']." div input').attr('value','#' + hex);
                                                        }
                                                });
                                                    //]]>
                                                  </script> ";
                                $reportOrd[$report['name']].='
                                          </td>
                                      </tr>
                                      <tr>
                                          <td colspan="3" style="text-align:center;border-bottom:0px">
                                            <input type="hidden" name="action" value="changeColors">
                                            <input type="hidden" name="reportUnit" value="'.$report['name'].'">
                                            <input id="btn_'.$report['name'].'_3" class="button" type="submit" value="'._('Modify Colors').'" />
                                            <input id="btn_'.$report['name'].'_3" class="button" type="button" onclick="javascript:restoreOriginalStyle(\''.$report['name'].'\');" value="'._('Restore Original').'" />
                                          </td>
                                      </tr>
                                  </table>
                                 </form>
                             </td>
                             <td class="reportConfig" valign="top">
                                 <form method="POST" action="jasper_config_modify.php" id="'.$report['name'].'" enctype="multipart/form-data">
                                 <ul>
                                     <li>';
                                         $uriHead=$report['uriString'].'_files/head.gif';
                                         $reportOrd[$report['name']].='
                                         <a href="jasper_image.php?report_unit='.$uriHead.'" target="_blank"><img src="jasper_image.php?report_unit='.$uriHead.'" width="400" height="41" /></a>
                                         <span>* '._("Click To Zoom").'</span>
                                     </li>
                                     <li>';
                                        
                                         $id_temp=str_replace('/','_',$report['uriString']);
                                       $reportOrd[$report['name']].='
                                        <input id="'.$id_temp.'_file" name="'.$id_temp.'_file" type="file" size="25">
                                        <span>* '._("Only .gif").'</span>
                                    </li>
                                    <li>
                                        <input type="hidden" name="reportUnit" value="'.$report['name'].'">
                                        <input id="btn_'.$report['name'].'" class="button" type="submit" value="'._('Modify').'" />
                                    </li>
                                 </ul>
                                 </form>
                                 <form method="POST" action="jasper_config_modify.php" id="'.$report['name'].'_2" enctype="multipart/form-data">
                                     <ul>
                                         <li>
                                             <input type="hidden" name="action" value="RestoreOriginal">
                                             <input type="hidden" name="reportUnit" value="'.$report['name'].'">
                                             <input id="btn_'.$report['name'].'_2" class="button" type="submit" value="'._('Restore Original').'" />
                                         </li>
                                     </ul>
                                 </form>';
                                    
                                    //$result2 = $client->requestReport($report_unit, $report_format,$report_params,'get');
                                    //$result2 = $client->getResource($report_unit,'img');
                                   //echo $result2;
                                    //echo $client->getInputControlHTML($result2,'TYPE_CONTROL');
                                  //echo $client->getInputControlHTML($result2); 
                                  //echo $client->getParameterHtml($report_unit);
                            $reportOrd[$report['name']].='
                             </td>
                            </tr>';
                                }
                                //
                               if (Session::menu_perms("MenuEvents", "EventsForensics")) {
                                   $class = ($key++%2==0) ? "par" : "impar";
                                    echo str_replace("CLASS_KEY",$class,$reportOrd['Security_Report']);
                               }
                                //
                               if (Session::menu_perms("MenuEvents", "EventsForensics")) {
                                   $class = ($key++%2==0) ? "par" : "impar";
                                    echo str_replace("CLASS_KEY",$class,$reportOrd['Security_DB_Events']);
                               }
                                //
                               if (Session::menu_perms("MenuEvents", "EventsForensics")) {
                                   $class = ($key++%2==0) ? "par" : "impar";
                                    echo str_replace("CLASS_KEY",$class,$reportOrd['Security_DB_Unique_Events']);
                               }
                               //
                              if (Session::menu_perms("MenuEvents", "EventsForensics")) {
                                   $class = ($key++%2==0) ? "par" : "impar";
                                    echo str_replace("CLASS_KEY",$class,$reportOrd['Security_DB_Sensors']);
                               }
                                //
                               if (Session::menu_perms("MenuEvents", "EventsForensics")) {
                                   $class = ($key++%2==0) ? "par" : "impar";
                                    echo str_replace("CLASS_KEY",$class,$reportOrd['Security_DB_Unique_Address']);
                               }
                                //
                               if (Session::menu_perms("MenuEvents", "EventsForensics")) {
                                   $class = ($key++%2==0) ? "par" : "impar";
                                    echo str_replace("CLASS_KEY",$class,$reportOrd['Security_DB_Source_Port']);
                               }
                                //
                               if (Session::menu_perms("MenuEvents", "EventsForensics")) {
                                   $class = ($key++%2==0) ? "par" : "impar";
                                    echo str_replace("CLASS_KEY",$class,$reportOrd['Security_DB_Destination_Port']);
                               }
                                //
                               if (Session::menu_perms("MenuEvents", "EventsForensics")) {
                                   $class = ($key++%2==0) ? "par" : "impar";
                                    echo str_replace("CLASS_KEY",$class,$reportOrd['Security_DB_Unique_Plugin']);
                               }
                                //
                               if (Session::menu_perms("MenuEvents", "EventsForensics")) {
                                   $class = ($key++%2==0) ? "par" : "impar";
                                    echo str_replace("CLASS_KEY",$class,$reportOrd['SIEM_Events_Unique_IP_Links']);
                               }
                                //
                               if (Session::menu_perms("MenuEvents", "EventsForensics")) {
                                   $class = ($key++%2==0) ? "par" : "impar";
                                    echo str_replace("CLASS_KEY",$class,$reportOrd['Security_DB_Unique_Country_Events']);
                               }
                            // Logger
                            if (Session::menu_perms("MenuEvents", "ControlPanelSEM")) {
                                    $class = ($key++%2==0) ? "par" : "impar";
                                    echo str_replace("CLASS_KEY",$class,$reportOrd['SEM_Report']);
                            }
                            //<!-- Alarms REPORT -->
                            if (Session::menu_perms("MenuIncidents", "ControlPanelAlarms")) {
                                    $class = ($key++%2==0) ? "par" : "impar";
                                    echo str_replace("CLASS_KEY",$class,$reportOrd['Alarms_Report']);
                            }
                            //<!-- Business_and_Compliance_ISO_PCI REPORT -->
                            if (Session::menu_perms("MenuReports", "ReportsReportServer")) {
                                    $class = ($key++%2==0) ? "par" : "impar";
                                    echo str_replace("CLASS_KEY",$class,$reportOrd['Business_and_Compliance_ISO_PCI_Report']);
                            }

                            if (Session::menu_perms("MenuControlPanel", "ControlPanelMetrics")) {
                                    $class = ($key++%2==0) ? "par" : "impar";
                                    echo str_replace("CLASS_KEY",$class,$reportOrd['Metrics_Report']);
                            }
                            // <!-- Geographic_Report REPORT -->
                            if (Session::menu_perms("MenuEvents", "EventsForensics")) {
                                $class = ($key++%2==0) ? "par" : "impar";
								echo str_replace("CLASS_KEY",$class,$reportOrd['Geographic_Report']);
                             }
                            //<!-- Geographic_Report REPORT -->
                             //<!-- Incidents_Report REPORT -->
                             if (Session::menu_perms("MenuReports", "ReportsReportServer")) {
                                $class = ($key++%2==0) ? "par" : "impar";
								echo str_replace("CLASS_KEY",$class,$reportOrd['Incidents_Report']);
                             }
                            //    <!-- OTHER REPORTS -->

                            foreach($reportOrd as $key2 => $value){
                                if($key2!='SIEM_Events_Unique_IP_Links'&&$key2!='Security_DB_Unique_Country_Events'&&$key2!='Security_DB_Destination_Port'&&$key2!='Security_DB_Source_Port'&&$key2!='Security_DB_Unique_Address'&&$key2!='Security_DB_Unique_Plugin'&&$key2!='Security_DB_Unique_Events'&&$key2!='Security_DB_Sensors'&&$key2!='Security_DB_Events'&&$key2!='Security_Report'&&$key2!='SEM_Report'&&$key2!='Alarms_Report'&&$key2!='Business_and_Compliance_ISO_PCI_Report'&&$key2!='Metrics_Report'&&$key2!='Geographic_Report'&&$key2!='Incidents_Report'){
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
    <?=$tempJS?>
</body>
</html>
