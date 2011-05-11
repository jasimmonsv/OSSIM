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
require_once ('classes/Security.inc');
require_once ('ossim_db.inc');
require_once ('ossim_conf.inc');
require_once ('classes/Sensor.inc');
require_once ('classes/Net.inc');
require_once ('classes/Net_sensor_reference.inc');
require_once ('classes/Net_group.inc');
require_once ('classes/Net_group_scan.inc');
require_once ('classes/RRD_config.inc');


Session::logcheck("MenuPolicy", "PolicyNetworks");
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title> <?php echo gettext("OSSIM Framework"); ?> </title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <link type="text/css" rel="stylesheet" href="../style/style.css"/>
    <link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
    <link rel="stylesheet" type="text/css" href="../style/tree.css" />
    <script type="text/javascript" src="../js/combos.js"></script>
    <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
    <script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
    <script type="text/javascript" src="../js/jquery.simpletip.js"></script>
    <script type="text/javascript" src="../js/ajax_validator.js"></script>
    <script type="text/javascript" src="../js/jquery.dynatree.js"></script>
    <script type="text/javascript" src="../js/ui.multiselect.js"></script>
    <script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
    <script type="text/javascript" src="../js/messages.php"></script>
    <script type="text/javascript" src="../js/utils.js"></script>
    <script type="text/javascript">
        function remove_success_message()
        {
            if ( $('#success_message').length == 1 )    
                $("#success_message").remove();
        }
        function load_tree(filter) {
            combo = 'nets';
            $("#nets_tree").remove();
            $('#td_nets').append('<div id="nets_tree" style="width:100%"></div>');
            $("#nets_tree").dynatree({
                initAjax: { url: "draw_nets.php", data: {filter: filter} },
                clickFolderMode: 2,
                onActivate: function(dtnode) {
                        if (!dtnode.hasChildren()) {
                            // add from a final node
                            addto(combo,dtnode.data.title,dtnode.data.key)
                        } else {
                            // simulate expand and load
                            addnodes = true;
                            dtnode.toggleExpand();
                        }
                },
                onDeactivate: function(dtnode) {},
                onLazyRead: function(dtnode){
                    dtnode.appendAjax({
                        url: "draw_nets.php",
                        data: {key: dtnode.data.key, filter:filter}
                    });
                }
            });
        }
        $(document).ready(function(){
            $('textarea').elastic();
            $('.vfield').bind('blur', function() {
                 validate_field($(this).attr("id"), "newnetgroup.php");
            });
            load_tree('');
        });
    </script>
	
	<style type='text/css'>
		<?php
		if ( GET('withoutmenu') == "1" )
		{
			echo "#table_form {width: 500px;}";
		    echo "#table_form th {width: 185px;}";
		}
		else
		{
			echo "#table_form {width: 460px;}";
		    echo "#table_form th {width: 150px;}";
		}
		?>
		
		input[type='text'], select, textarea {width: 90%; height: 18px;}
		textarea { height: 45px;}
		label {border: none; cursor: default;}
		.bold {font-weight: bold;}
		div.bold {line-height: 18px;}
        #del_selected {float:right; padding-top: 5px; width: 52px;}
	</style>
</head>

<body>
                                                                                
<?php

$db = new ossim_db();
$conn = $db->connect();

$ngname = GET('name');
$update  = intval(GET('update'));

$style_success = "style='display: none;'";

if ($update==1) {
    $success_message = gettext("Network Group succesfully updated");
    $style_success   = "style='display: block;text-align:center;'"; 
}

if ( isset($_SESSION['_netgroup']) )
{
	$ngname       = $_SESSION['_netgroup']['ngname'];    
	$networks     = $_SESSION['_netgroup']['networks'];
	$descr        = $_SESSION['_netgroup']['descr'];       
	$threshold_a  = $_SESSION['_netgroup']['threshold_a']; 
	$threshold_c  = $_SESSION['_netgroup']['threshold_c']; 
	$rrd_profile  = $_SESSION['_netgroup']['rrd_profile'];  
	
	
	unset($_SESSION['_netgroup']);
}
else
{
	$conf = $GLOBALS["CONF"];
	$threshold_a = $threshold_c = $conf->get_conf("threshold");
	$descr  = "";
	$networks  = array();
	
	if ($ngname != '')
	{
		ossim_valid($ngname, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE, OSS_SQL, 'illegal:' . _(" Network Group Name"));
		
		if (ossim_error()) 
			die(ossim_error());			
			
		if ($net_group_list = Net_group::get_list($conn, "name = '$ngname'")) {
			$net_group = $net_group_list[0];

			$descr        = $net_group->get_descr();
			$threshold_c  = $net_group->get_threshold_c();
			$threshold_a  = $net_group->get_threshold_a();
			$obj_networks = $net_group->get_networks($conn);
			
			foreach($obj_networks as $net)
				$networks[] = $net->get_net_name();
																				
			$rrd_profile = $net_group->get_rrd_profile();
			if (!$rrd_profile) 
				$rrd_profile = "None";
																		
		}
	}
	
}

if (GET('withoutmenu') != "1") 
	include ("../hmenu.php"); 

if (GET('name') != "" || GET('clone') == 1)
	$action = "modifynetgroup.php";
else
	$action = "newnetgroup.php";

?>

<div id='success_message' class='ossim_success' <?php echo $style_success ?>><?php echo $success_message;?></div>
<div id='info_error' class='ossim_error' style='display: none;'></div>

<form name='form_ng' id='form_ng' method="POST" action="<?php echo $action;?>">

<input type="hidden" name="insert" value="insert"/>
<input type="hidden" name="withoutmenu" id='withoutmenu' value="<?php echo GET('withoutmenu')?>"/>

<table align="center" class="transparent">
    <tr>
    <td class="nobborder" valign="top">
        <table align="center" id='table_form'>
            <tr>
                <th><label for='ngname'><?php echo gettext("Name"); ?></label></th>
                    
                <td class="left">
                    <?php if (GET('name') == "" ) {?>
                        <input type='text' name='ngname' id='ngname' class='vfield req_field' value="<?php echo $ngname?>"/>
                        <span style="padding-left: 3px;">*</span>
                    <?php } else { ?>	
                        <input type='hidden' name='ngname' id='ngname' class='vfield req_field' value="<?php echo $ngname?>"/>
                        <div class='bold'><?php echo $ngname?></div>
                    <?php }  ?>
                </td>
                
            </tr>

            <tr>
                <th> 
                    <label for='mboxs1'><?php echo gettext("Networks");?></label><br/>
                    <span><a href="newnetform.php"> <?php echo gettext("Insert new network"); ?> ?</a></span>
                </th> 
                        
                <td class="left nobborder">
                    <select style="width:250px;height:90%" multiple="multiple" size="19" class="req_field" name="nets[]" id="nets">
                    <?php
                    /* ===== Networks ==== */
                    if ($network_list = Net::get_list($conn)) 
                    {
                        foreach($network_list as $network) 
                        {
                            $net_name = $network->get_name();
                            $net_ips  = $network->get_ips();
                            
                            if(in_array($net_name, $networks))    echo "<option value='$net_name'>$net_name ($net_ips)</option>";
                        }
                    }
                    ?>
                    </select>
                    <span style="padding-left: 3px; vertical-align: top;">*</span>
                    <div id='del_selected'><input type="button" value=" [X] " onclick="deletefrom('nets')" class="lbutton"/></div>
                </td>
            </tr>

            <tr>
                <th><label for='descr'><?php echo gettext("Description"); ?></label><br/>
                <td class="left"><textarea name="descr" id='descr' class='vfield'><?php echo $descr; ?></textarea></td>
            </tr>
            
            <tr>
                <td style="text-align: left; border:none; padding-top:3px;">
                    <a onclick="$('.advanced').toggle()" style="cursor:pointer;">
                    <img border="0" align="absmiddle" src="../pixmaps/arrow_green.gif"/><?php echo _("Advanced");?></a>
                </td>
            </tr>

          
            <tr class="advanced" style="display:none;">
                <th> 
                    <label for='rrd_profile'><?php echo gettext("RRD Profile"); ?></label><br/>
                    <span><a href="../rrd_conf/new_rrd_conf_form.php"><?php echo gettext("Insert new profile"); ?> ?</a></span>
                </th>
                <td class="left">
                    <select name="rrd_profile" id='rrd_profile' class='vfield'>
                        <option value="" selected='selected'><?php echo gettext("None"); ?></option>
                        <?php
                        foreach(RRD_Config::get_profile_list($conn) as $profile) {
                            if (strcmp($profile, "global"))
                            {
                                $selected = ( $rrd_profile == $profile  ) ? " selected='selected'" : '';
                                echo "<option value=\"$profile\" $selected>$profile</option>\n";
                            }
                        }
                        ?>
                    </select>
                </td>
            </tr>

            <tr class="advanced" style="display:none;">
                <th><label for='threshold_c'><?php echo gettext("Threshold C"); ?></label></th>
                <td class="left">
                    <input type="text" name="threshold_c" id='threshold_c' class='req_field vfield' value="<?php echo $threshold_c?>"/>
                    <span style="padding-left: 3px;">*</span>
                </td>
            </tr>

            <tr class="advanced" style="display:none;">
                <th><label for='threshold_a'><?php echo gettext("Threshold A"); ?></label></th>
                <td class="left">
                    <input type="text" name="threshold_a" id='threshold_a' class='req_field vfield' value="<?php echo $threshold_a?>"/>
                    <span style="padding-left: 3px;">*</span>
                </td>
            </tr>

            <tr>
                <td colspan="2" align="center" style="padding: 10px;" class='noborder'>
                    <input type="button" class="button" id='send' value="<?php echo _("Update")?>" onclick="remove_success_message();selectall('nets'); submit_form()">
                    <input type="reset" class="button" value="<?=_("Clear form")?>"/>
                </td>
            </tr>
                
        </table>
    </td>
    <td class="nobborder" valign="top" width="300">
        <div style="float:left;width:100%;">
            <table class="transparent" align='center' width="100%">
                <tr>
                    <td class="left nobborder">
                        <?=_("Filter")?>: <input type="text" id="filtern" name="filtern" style="height: 18px;width: 65%;" />
                        &nbsp;<input type="button" class="lbutton" value="<?=_("Apply")?>" onclick="load_tree(this.form.filtern.value)" /> 
                    </td>
                </tr>
                <tr>
                    <td class="nobborder" id="td_nets">
                    <div id="nets_tree"></div>
                    </td>
                </tr>
            </table>
        </div>
    </td>
</tr>
    <tr>
        <td colspan="2" class="nobborder">
            <p align="center" style="font-style: italic;"><?php echo gettext("Values marked with (*) are mandatory"); ?></p>
        </td>
    </tr>
</table>
</form>

<?php $db->close($conn); ?>
	</body>
</html>

