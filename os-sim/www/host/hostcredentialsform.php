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
* 
* Classes list:
*/
require_once ('classes/Session.inc');
require_once ('classes/Host.inc');
require_once ('ossim_db.inc');
require_once ('classes/RRD_config.inc');
require_once ('classes/Security.inc');

Session::logcheck("MenuPolicy", "PolicyHosts");

$ip  = GET('ip');
$id  = GET('id');
$action = GET('action');

ossim_valid($ip, OSS_IP_ADDR, OSS_NULLABLE, 'illegal:' . _("Ip"));
ossim_valid($action, OSS_NULLABLE, "edit", 'illegal:' . _("Action"));
ossim_valid($id, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("Id"));

if (ossim_error()) 
	die(ossim_error());

$db   = new ossim_db();
$conn = $db->connect();

$credential_type = Host::get_credentials_type($conn);


if ( isset($_SESSION['_credentials']) )
{
	$hostname  = $_SESSION['_credentials']['hostname'];  
	$ip        = $_SESSION['_credentials']['ip']; 
	$type      = $_SESSION['_credentials']['type']; 
	$user_ct   = $_SESSION['_credentials']['user_ct'];   
	$pass_ct   = $_SESSION['_credentials']['pass_ct']; 
	$pass_ct2  = $_SESSION['_credentials']['pass_ct2']; 
	$extra     = $_SESSION['_credentials']['extra'];  
	
	unset($_SESSION['_credentials']);
		
}
else
{
    if ($host_list = Host::get_list($conn, "WHERE ip = '$ip'")) 
        $host = $host_list[0];

    if (!empty($host))
    {
        $hostname = $host->get_hostname();
        $ip = $host->get_ip();
        
        $credentials = array();
        $credentials = Host::get_credentials_ip($conn, $ip);

        if($action=="edit") {
            $credentials_id = Host::get_credentials_id($conn, $id);
            
            $type     = $credentials_id['type'];
            $user_ct  = $credentials_id['username'];
            $pass_ct  = $pass_ct2 = Util::fake_pass($credentials_id['password']);
            $extra    = $credentials_id['extra'];
        }
    }
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title> <?php echo gettext("OSSIM Framework"); ?> </title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <link rel="stylesheet" type="text/css" href="../style/style.css"/>
    <link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
    <link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css"/>
    <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
    <script type="text/javascript" src="../js/ajax_validator.js"></script>
    <script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
    <script type="text/javascript" src="../js/messages.php"></script>
    <script type="text/javascript" src="../js/utils.js"></script>
    <script type="text/javascript">
  
    $(document).ready(function() {
        $('#send').bind('click', function() {
			$('#action').val("edit");
        	submit_form();
				$('#send').val("Update");
			});
        
        $('#clean_c').bind('click', function() {
                            
            /*$('#type').removeClass("req_field vfield");
            $('#user_ct').removeClass("req_field vfield");
            $('#pass_ct').removeClass("req_field vfield");
            $('#pass_ct2').removeClass("req_field vfield");*/
            
            submit_form();
            
            $('#send').val("Clean Credentials");
            $('#send').attr("id", "clean_c");
        });
        
        $('textarea').elastic();
                
        $('.vfield').bind('blur', function() {
             validate_field($(this).attr("id"), "modifycredentials.php");
        });
        
    });
            
</script>
</head>

<style type='text/css'>

    <?php
	
	if ( GET('withoutmenu') == "1" )
    {
        echo "#table_form {width: 400px;}";
        echo "#table_form th {width: 120px;}";
	}
    else
    {
        echo "#table_form { width: 750px;}";
        echo "#table_form th {width: 200px;}";
        echo "#table_data th.extra {width: 340px;}";
        echo "#table_data th.action {width: 70px;}";
    }
    ?>

    input[type='text'], input[type='password'], select, textarea {width: 90%; height: 18px;}
    textarea { height: 45px;}
    label {border: none; cursor: default;}
    .bold {font-weight: bold;}
    div.bold {line-height: 18px;}
    a {cursor:pointer;}
	#table_form td.ipname {width: 55px; text-align:left;}
	#table_data { width: 80%; margin: auto; text-align: center;}
	
</style>
<body>

<?php
if (GET('withoutmenu') != "1") 
	include ("../hmenu.php");

?>



<div id='info_error' class='ossim_error' style='display: none;'></div>

<form method="POST" name='credential_form' id='credential_form' action="modifycredentials.php">

<input type="hidden" name="action" id='action'/>

<?php $ucredentials = array(); ?>
<table id='table_form' align='center'>
    <tr>
		<td colspan="2" class='nobborder'>
			<table class="transparent" style='width: 300px;'>
				<tr>
					<td class='ipname nobborder'><label for='hostname'><div class='bold'><?php echo gettext("Hostname:"); ?></div></label></td>
					<td class="left nobborder">
						<input type="hidden" class='req_field vfield' name="hostname" id="hostname" value="<?php echo $hostname;?>"/>
						<?php echo $hostname?>
					</td>
				</tr>
				<tr>
					<td class='ipname nobborder'><label for='ip'><div class='bold'><?php echo gettext("Ip:"); ?></div></label></td>
					<td class="left nobborder">
						<input type="hidden" class='req_field vfield' name="ip" id="ip" value="<?php echo $ip;?>"/>
						<?php echo $ip?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	
    <?php
    if(count($credentials)!=0) {
    ?>
    <tr>
        <td colspan="2" style="padding-top:5px;" class="nobborder" nowrap='nowrap'>
            <table class="transparent" id='table_data'>
            <tr>
                <th><?php echo gettext("Type"); ?></th>
                <th><?php echo gettext("Username"); ?></th>
                <th><?php echo gettext("Password"); ?></th>
                <th class="extra"><?php echo gettext("Extra"); ?></th>
                <th class="action"><?php echo gettext("Action"); ?></th>
            </tr>
            <?php
            $color = 0;
            foreach ($credentials as $credential)
            {
            ?>
            <tr <?php echo (($color%2==1) ? "bgcolor='#F2F2F2'":"")?>>
                <td class="nobborder" style="text-align:center;">
                <?php
                    foreach ($credential_type as $k => $v) {
                        if ($v['id'] == $credential['type']) {
                            echo $v['name'];
                            $ucredentials[] = $v['id'];
                        }
                    }
                ?>
                </td>
                <td class="nobborder" style="text-align:center;">
                    <?php echo $credential['username'];?>
                </td>
                <td class="nobborder" style="text-align:center;">
                    <?php
                    $credential['password'] = preg_replace('/./', '*', $credential['password']);
                    echo $credential['password'];?>
                </td>
                <td class="nobborder" style="text-align:left;"><?php echo nl2br($credential['extra']);?></td>
                <td class="nobborder" style="text-align:center;">
                    <a href="hostcredentialsform.php?action=edit&id=<?php echo $credential['id']?>&ip=<?php echo $ip;?>">
                        <img border="0" align="absmiddle" title="<?php echo _("Edit");?>" alt="<?php echo _("Edit");?>"  src="../vulnmeter/images/pencil.png"/>
                    </a>
                    <a href="modifycredentials.php?action=delete&id=<?php echo $credential['id']?>&ip=<?php echo $ip;?>">
                    <img border="0" align="absmiddle" title="<?php echo _("Delete");?>" alt="<?php echo _("Delete");?>" src="../vulnmeter/images/delete.gif"/> 
                </td>
            </tr>
            <?php 
            $color++;
            }
            ?>
            </table>
        </td>
        </tr>
        <?php
        }
        
        if (count($ucredentials)<count($credential_type) || $action=='edit') 
        {
        ?>
        <tr>
        <td colspan="2" style="padding:10px 0px 10px 0px;" class="nobborder" nowrap>
        <table align='center' class="transparent" id="table_data">
            <tr>
                <th><label for='type'><?php echo gettext("Type"); ?></label></th>
                <td class="left nobborder">
                    <select name='type' id='type' class='req_field vfield'>
                    <?php
                        foreach ($credential_type as $k => $v) {
                            $selected = ($v['id'] == $type) ? " selected='selected'" : "";
                            echo "<option value='".$v['id']."' ".$selected.">".$v['name']."</option>";
                        }
                    ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for='user_ct'><?php echo gettext("Username"); ?></label></th>
                <td class="left nobborder">
                    <input type="text" class='req_field vfield' name="user_ct" id="user_ct" value="<?php echo $user_ct;?>"/>
                    <span style="padding-left: 3px;">*</span>
                </td>
            </tr>
            <tr>
                <th><label for='pass_ct'><?php echo gettext("Password"); ?></label></th>
                <td class="left nobborder">
					<input type="password" class='req_field vfield' name="pass_ct" id="pass_ct" value="<?php echo $pass_ct;?>"/>
                    <span style="padding-left: 3px;">*</span>
                </td>
            </tr>
            <tr>
                <th><label for='pass_ct2'><?php echo gettext("Repeat Password"); ?></label></th>
                <td class="left nobborder">
					<input type="password" class='req_field vfield' name="pass_ct2" id="pass_ct2" value="<?php echo $pass_ct2;?>"/>
                    <span style="padding-left: 3px;">*</span>
                </td>
            </tr>
            <tr>
                <th><label for='extra'><?php echo gettext("Extra"); ?></label></th>
                <td class="left nobborder"><textarea name='extra' id='extra'><?php echo $extra;?></textarea></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td colspan="2" align="center" style="border-bottom: none; padding: 10px;">
            <input type="button"class="button" name="edit_c"  id='send' value="<?=_("Update")?>"/>
        <!--    <input type="button" class="button" name="clean_c" id='clean_c' value="<?=_("Clean Credentials")?>"/>-->
        </td>
    </tr>
    
    <?php
    }
    ?>
</table>

<?php
if (count($ucredentials) < count($credential_type) || $action=='edit')
{
    ?>
        <p align="center" style="font-style: italic;"><?php echo gettext("Values marked with (*) are mandatory"); ?></p>
    <?php
}
?>
</form>


</body>
</html>
<?php $db->close($conn); ?>
