<?php $title = ($editable == true) ? _("Edit node:  $node_name ") : _("Show node:  $node_name "); ?>

<div id='edit_container'>

<form name='form_m' id='form_m'>
	<table id='header_rule'>
		<tbody>
			<tr><th class='rule_title'><?php echo $title;?></th></tr>
		</tbody>
	</table>
   
	<table class='er_container' id='erc1'>
		<tbody id='erb_c1'>
		
			<?php echo print_subheader("attributes", $editable) ?>
			
			<tr id='<?=$unique_id?>'>
			<?php if ( $editable == true ) { ?>
				<th class='n_name'><input type='text' class='n_input auto_c' name='n_label-<?=$unique_id?>' id='n_label_<?=$unique_id?>' value='<?=$node_name?>'/></th>
				<td class='n_value'><textarea name='n_txt-<?=$unique_id?>' id='n_txt-<?=$unique_id?>'><?=$attributes[$node_name]?></textarea></td>
				<td class='actions_bt_at'>
					<a onclick="delete_at('<?=$unique_id?>','at', 'images');"><img src='images/delete.gif' alt='Delete' title='Delete Attribute'/></a>
				</td>
			<?php } else { ?>
				
				<th class='n_name'><div class='read_only'><?php echo $node_name?></div></th>
				<td class='n_value'><div class='read_only'><?php echo $attributes[$node_name]?></div></td>
										
			<?php } ?>
			</tr>
		</tbody>
	</table>

	<?php echo print_subfooter($params, $editable);?>

</form>
</div>