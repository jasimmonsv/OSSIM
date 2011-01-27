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
Session::logcheck("MenuIncidents", "IncidentsTypes");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
</head>
<script type='text/javascript' language='JavaScript'>
var options = ["Checkbox", "Select box", "Radio button", "Slider"];

Array.prototype.in_array = function(p_val) {
    for(var i = 0, l = this.length; i < l; i++) {
        if(this[i] == p_val) {
            return true;
        }
    }
    return false;
}

function enable_ta(opt)
{
	var select_opt = $('#custom_typef option:selected').attr('value');
	
	if (options.in_array(select_opt) == true)
	{
		$('#custom_optionsf').attr("disabled", "");
		$('#custom_optionsf').removeClass();
		$('#custom_optionsf').addClass("custom_optionsf_en");
		$('#custom_optionsf').val(opt);
	}
	else
	{
		$('#custom_optionsf').attr("disabled", "disabled");
		$('#custom_optionsf').removeClass();
		$('#custom_optionsf').addClass("custom_optionsf_dis");
		$('#custom_optionsf').val('');
	}
	
}

function checked_form()
{
	var select_opt = $('#custom_typef option:selected').attr('value');
	var fieldname = $('#custom_namef').val();
	var old_fieldname = $('#old_name').val();
	var msg = '';
		
	if (fieldname == "")
		msg = '<?=_("Field name is empty.")?><br/>';
	else
	{
		
		if (typeof (old_field_name) != 'undefined' && old_fieldname.toLowerCase() != fieldname.toLowerCase())
		{
			var fieldnames = $('.ct_name').text();
					
			$('.ct_name').each(function(index) {
				if ( $(this).text() == fieldname )
					msg = '<?=_("Field name already exists.")?><br/>';
				
			});
		}
	}
	
	if (select_opt == "")
		msg += '<?=_("Field type not selected.")?><br/>';
	else
	{	
		if (options.in_array(select_opt) == true)
		{
			var optfield = $('#custom_optionsf').val();
			if ( optfield == '')
			{
				msg += '<?=_("Field options is empty.")?>';
			}
		}
	}
	
	return msg;

}


function add_ticket()
{
	var msg = checked_form();
	$('#modify').val('add');
	
	if (msg == '')
		$("#crt").submit();
	else
	{
		msg = "<div style='padding-left: 10px'>"+msg+"</div>";
		$("#info_error").html(msg);
		$("#info_error").css("display", "block");
		window.scrollTo(0,0);
		return false;
	}
}

function delete_ticket(name)
{
	$('#modify').val('delete');
	$('#custom_namef').val(name);
	$("#crt").submit();
}

function modify_ct()
{
	var msg = checked_form();
	
	$('#modify').val('modify_ct');
		
	if (msg == '')
		$("#crt").submit();
	else
	{
		msg = "<div style='padding-left: 10px'>"+msg+"</div>";
		$("#info_error").html(msg);
		$("#info_error").css("display", "block");
		window.scrollTo(0,0);
		return false;
	}
}

function modify_ticket()
{
	$('#modify').val('modify');
	$("#crt").submit();
}

function edit_ticket(id)
{
	var oldid = id;
	var id="#"+id;
	$('#modify').val('modify_ct');
	$("#info_error").css("display", "none");
	$("#info_error").html('');
	
	var id_ct     =  $("#id_crt").val();
		
	var name      =  $(id+"_name").text();
	
	var type      =  $(id+"_type").text();
	var options   =  $(id+"_options").text();
	var required  =  $(id+"_required").attr('alt').match(/Tick/);
	
			
	$("#header_nct").html("<?=_("Modify Custom Type")?>");
	
	if ($("#id_crt").length > 1)
		$("#id_crt").attr("value", old_names[oldid]);
	else
		$("#id_crt").after("<input type='hidden' name='old_name' id='old_name' value='"+old_names[oldid]+"'/>")
	
	$('#custom_namef').val(name);
	$('#custom_typef').val(type);
	
	enable_ta(options);
	
	if ( required ) $('#custom_requiredf').attr("checked", "true");
	
	$('.ct_add').html("<input type='button' id='add_button' value='<?=_("Update")?>' class='button' onclick=\"modify_ct();\"/>");
	
	$("#cancel_cont").html("<input type='button' id='cancel' class='button' value='<?=_("Cancel")?>' onclick=\"cancel_ticket();\"/>"); 
	window.scrollTo(0, 0);	
	$("#custom_namef").focus();

	
}

function move_field(id,oldpos,newid)
{
	var oldid = id;
	var id="#"+id;

	$("#info_error").css("display", "none");
	$("#info_error").html('');

	$('#modify').val('modify_pos');
	$('#custom_namef').val($(id+"_name").text());
	$('#custom_typef').val($(id+"_type").text());
	$('#oldpos').val(oldpos);
	$('#newpos').val(positions[newid]);
			
	if ($("#id_crt").length > 1)
		$("#id_crt").attr("value", old_names[oldid]);
	else
		$("#id_crt").after("<input type='hidden' name='old_name' id='old_name' value='"+old_names[oldid]+"'/>")
		
	$("#crt").submit();
	
}

function cancel_ticket()
{
	$('#modify').val('add');
	$("#header_nct").html("<?=_("New Custom Type")?>");
	$("#cancel, #old_name").remove();
	
	$('#custom_namef, #custom_typef').attr("value", "");
	$('#custom_optionsf').attr("disabled", "disabled");
	$('#custom_optionsf').removeClass();
	$('#custom_optionsf').addClass("custom_optionsf_dis");
	$('#custom_optionsf').val('');
	$('#custom_requiredf').attr("checked", "");
	
	$('.ct_add').html("<input type='button' id='add_button' value='<?=_("Add")?>' class='button' onclick=\"add_ticket();\"/>");
	
}



$(document).ready(function() {
	$('#custom_typef').bind("change", function() { enable_ta('');});
	$('#ct_table tr:even').css('background', "#F2F2F2");
});


	
</script>	
<body>

<?php
include ("../hmenu.php"); ?>

<?php
require_once 'classes/Security.inc';
$inctype_id = GET('id');
ossim_valid($inctype_id, OSS_ALPHA, OSS_SPACE, OSS_PUNC, 'illegal:' . _("Incident type"));
if (ossim_error()) {
    die(ossim_error());
}
require_once ('ossim_db.inc');
require_once ("classes/Incident_type.inc");
$db = new ossim_db();
$conn = $db->connect();
//
$custom_fields = array();
if ($inctype_list = Incident_type::get_list($conn, "WHERE id = '$inctype_id'")) {
    $inctype = $inctype_list[0];
    $custom = (preg_match("/custom/",$inctype->get_keywords())) ? 1 : 0;
    $custom_fields = Incident_type::get_custom_list($conn,$inctype_id);
}

?>

<form method="post" id="crt" action="modifyincidenttype.php">
<div id='info_error' class='ct_error'></div>
<input type="hidden" id="modify" name="modify" value="modify"/>
<input type="hidden" name="id" id='id_crt' value="<?php echo $inctype->get_id(); ?>" />
<input type="hidden" id="oldpos" name="oldpos" value="0"/>
<input type="hidden" id="newpos" name="newpos" value="0"/>

<table align="center" width='700px'>
	<tr>
		<th> <?php echo gettext("Ticket type"); ?> </th>
		<th class="left"><?php echo $inctype->get_id(); ?></th>
	</tr>
	<tr>
		<th> <?php echo gettext("Description"); ?> </th>
		<td class="nobborder ct_pad5"><textarea name="descr" id='ct_descr'><?php echo $inctype->get_descr(); ?></textarea></td>
	</tr>
	<tr>
		<th> <?php echo gettext("Custom"); ?> </th>
		<td class="left">
			<input type="checkbox" name="custom" onclick="$('#custom_type').toggle()" value="1"<?=($custom) ? " checked" : ""?>>
		</td>
	</tr>   
	<tr id="custom_type" <?=(!$custom) ? "style='display:none'" : ""?>>
		<th class='thr'><?=_("Custom fields")?>:</th>
		<td class='ct_pad5'>
			<table class='noborder' width='100%'>
				<tr>
					<td class='noborder' colspan='5'>
						<table width='100%' class='noborder' id='table_form_crt'>
							<tbody>
							<tr><td class='headerpr header_ct' colspan='3' id='header_nct'><?=_("New Custom Type")?></td></tr>
							<tr>
								<th class='ct'><?=_("Field Name")?></th>
								<td class="noborder left" colspan='2'><input type="text" id="custom_namef" name="custom_namef"/></td>
							</tr>
							<tr>
								<th><?=_("Required Field")?></th>
								<td class="noborder left" colspan='2'><input type="checkbox" id="custom_requiredf" name="custom_requiredf" value='1'/></td>
							</tr>
							<tr>
								<th class='ct'><?=_("Field Type")?></th>
								<td class="noborder left" colspan='2'>
									<select type="text" id="custom_typef" name="custom_typef">
									<option  value=''>-- <?=_("Select Types")?> --</option>
									
									<?php
									$types = array("Asset", "Check Yes/No", "Check True/False", "Checkbox", "Date", "Date Range", "Map", "Radio button", "Select box", "Slider", "Textarea", "Textbox", "File");
									sort($types);
									foreach($types as $k => $v)
										echo "<option style='text-align: left;' value='"._($v)."'>"._($v)."</option>";
									?>
									</select>
								</td>
							</tr>
							<tr>
								<th class='ct'><?=_("Field Options")?></th>
								<td class="noborder left">
									<textarea type="text" id="custom_optionsf" class='custom_optionsf_dis' name="custom_optionsf" disabled="disabled"></textarea>
								</td>
								<td class='noborder ct_mandatory left'>
									<div class="balloon">  
									<a style='cursor:pointer'><img src="../pixmaps/help-small.png" alt='Help'/></a> 
									<span class="tooltip">      
									<span class="top"></span>      
									<span class="middle ne11">          
										<table class='ct_opt_format' border='1'>
											<tbody>
											<tr><td class='ct_bold noborder left'><span class='ct_title'><?=_("Options Format Allowed")?></span></td></tr>
											<tr>
												<td class='noborder'>
													<div class='ct_opt_subcont'>
														<img src='../pixmaps/bulb.png' align='absmiddle' alt='Bulb'/><span class='ct_bold'><?=_("Type Radio and Check")?>:</span>
														<div class='ct_padl25'>
															<span><?=_("Value1:Name1")?></span><br/>
															<span><?=_("Value2:Name2:Checked")?></span><br/>
															<span><?=_("...")?></span>
														</div>
													</div>
												</td>
											</tr>
											<tr>
												<td class='noborder'>
													<div class='ct_opt_subcont'>
														<img src='../pixmaps/bulb.png' align='absmiddle' alt='Bulb'/><span class='ct_bold'><?=_("Type Slider")?>:</span> 
														<div class='ct_padl25'>
															<span><?=_("Min, Max, Step")?></span><br/>
														</div>
													</div>
												</td>
											</tr>
											<tr>
												<td class='noborder'>							
													<div class='ct_opt_subcont'>
														<img src='../pixmaps/bulb.png' align='absmiddle' alt='Bulb'/><span class='ct_bold'><?=_("Type Select Box")?>:</span> 
														<div class='ct_padl25'>
															<span><?=_("Value1:Text1")?></span><br/>
															<span><?=_("Value2:Text2:Selected")?></span><br/>
															<span><?=_("...")?></span><br/>
														</div>
													</div>
												</td>
											</tr>
											</tbody>
										</table>
									</span>      
									<span class="bottom"></span>  
									</span>
									</div>
								</td>
							</tr>
							<tr><td class='noborder ct_sep' colspan='3'></td></tr>						
							<tr>
								<td class="noborder left" id='cancel_cont'></td>
								<td class='noborder' width='100%'>&nbsp;</td>
								<td class="noborder ct_add">
									<div><input type="button" id="add_button" value="<?=_("Add")?>" class="button" onclick="add_ticket();"/></div>
								</td>
							</tr>
							<tr><td class='noborder ct_sep' colspan='3'></td></tr>		
							</tbody>
						</table>
					</td>
				</tr>
				
				<?php if (count($custom_fields) > 0) { ?>
				
				<tr>
					<td class='noborder'>
						<table width='100%' class='noborder' id='ct_table'>
						<tbody>
							<tr><td class='headerpr header_ct' colspan='5'><?=_("Custom Types Added")?></td></tr>
							<tr>
								<th><?=_("Field Name")?></th>
								<th style='width: 100px;'><?=_("Field Type")?></th>
								<th><?=_("Options")?></th>
								<th><?=_("Required")?></th>
								<th><?=_("Actions")?></th>
							</tr>
							<script>
								var old_names = new Array(<?=count($custom_fields)?>);
								var positions = new Array(<?=count($custom_fields)?>);
							</script>
							<?php 
							foreach ($custom_fields as $cf) 
							{
								$c++;
								$unique_id = "tr$c";
							?>
							
							<tr id='<?=$unique_id?>'>
								<td id='<?=$unique_id."_name"?>' class="noborder left ct_name"><?=$cf["name"]?></td>
								<td id='<?=$unique_id."_type"?>' class="noborder ct_type"><?=$cf["type"]?></td>
								<td id='<?=$unique_id."_options"?>' class="noborder left"><?=implode("<br/>", explode("\n",$cf["options"]))?></td>
								<td class="noborder ct_required">
									<? 
										$path_image = '../pixmaps/tables/';
										$image_required = ( $cf["required"] == 1 ) ? 'tick-small-circle.png' : 'cross-small-circle.png';
										$alt_required   = ( $cf["required"] == 1 ) ? 'Tick Circle' : 'Cross Circle';
										echo "<img id='".$unique_id."_required' src='".$path_image.$image_required."' alt='".$alt_required."'/>"; 
									?>
								</td>
								<td class="noborder ct_actions">
									<script>
										old_names['<?=$unique_id?>'] = "<?=$cf["name"]?>";
										positions['<?=$unique_id?>'] = "<?=$cf["ord"]?>";
									</script>
									<input type="image" src="../vulnmeter/images/delete.gif" class="ct_icon" onclick="delete_ticket('<?=$cf["name"]?>');"/>
									<a style='cursor:pointer' class="ct_icon" onclick="edit_ticket('tr<?=$c?>');"><img src="../vulnmeter/images/pencil.png" alt='<?=_("Edit")?>' title='<?=_("Edit")?>'/></a>

									<? if ($c<count($custom_fields)) { ?>

									<a style='cursor:pointer' class="ct_icon" onclick="move_field('tr<?=$c?>','<?=$cf["ord"]?>','tr<?=$c+1?>');"><img src="../pixmaps/theme/arrow-skip-270.png" alt='<?=_("Down")?>' title='<?=_("Down")?>'/></a>

									<? } else { ?>
                                    
                                    <img src="../pixmaps/theme/arrow-skip-270.png" style="filter: alpha(opacity=30); opacity: .3"/>
                                    
                                    <? }
									   if ($c>1)  { ?>

									<a style='cursor:pointer' class="ct_icon" onclick="move_field('tr<?=$c?>','<?=$cf["ord"]?>','tr<?=$c-1?>');"><img src="../pixmaps/theme/arrow-skip-090.png" alt='<?=_("Up")?>' title='<?=_("Up")?>'/></a>

                                    <? } else { ?>
                                    
                                    <img src="../pixmaps/theme/arrow-skip-090.png" style="filter: alpha(opacity=30); opacity: .3"/>

									<? } ?>

								</td>
							</tr>
							<? } ?>
						</tbody>
						</table>
					</td>
				</tr>
				
				<?php } ?>
				
			</table>
		</td>
	</tr>
	<tr>
		<td colspan="2" style="text-align:center; height: 30px;" class="nobborder">
			<input type="button" value="<?=_("Update")?>" class="button" onclick="modify_ticket();"/>
			<input type="reset" value="<?=_("Clear form")?>" class="button"/>
		</td>
	</tr>
</table>

</form>



</body>
</html>

