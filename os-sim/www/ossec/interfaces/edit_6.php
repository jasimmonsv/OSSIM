
<div id='edit_container'>

<form name='form_m' id='form_m'>
	<table id='header_rule'>
		<tbody>
			<tr><th class='rule_title'><?php echo _("Clone rules file")?></th></tr>
		</tbody>
	</table>
   
	<table class='er_container' id='erc1'>
		<tbody id='erb_c1'>
			<tr>
				<td class='n_name n_clone'><?php echo _("Name")?></td>
				<td class='n_value left' colspan='2'><span id='filename'><?=$_POST['file']?></span></td>
			</tr>
			<tr style='height: 15px'><td></td></tr>
			<tr>
				<td class='n_name n_clone'><?php echo _("New Name")?></td>
				<td class='n_value'><textarea name='new_filename' id='new_filename' class='new_name'></textarea></td>
				<td class='left'><span style='font-size:12px;'>.xml</span></td>
			</tr>
		</tbody>
	</table>

	<div class='buttons_box' style='padding-right:10px;'>
		<div><input type='button' class='clone' onclick="clone_rf();" value='<?php echo _("clone")?>'/></div>
	</div>
	
</form>
</div>