
<?php $title = ($editable == true) ? _("Edit node:  $node_name ") : _("Show node:  $node_name "); ?>

<div id='edit_container'>

<form name='form_m' id='form_m'>
	<table id='header_rule'>
		<tbody>
			<tr><th class='rule_title'><?php echo $title;?></th></tr>
		</tbody>
	</table>
   
    <?php if ( $editable == true || ($editable != true && count($child['tree']['@attributes']) > 1) ) { ?>
	<table class='er_container' id='erc1'>
		<tbody id='erb_c1'>
			<?php echo print_subheader("attributes", $editable) ?>
			<?php echo print_attributes($attributes, $editable); ?>
		</tbody>
	</table>

	<?php } ?>
	
	<table class='er_container' id='erc2'>
		<tbody id='erb_c2'>
			<?php 
				$show_actions = ($editable == true) ? true : false;
				
				echo print_subheader("txt_nodes", $editable, $show_actions);
			
				if ($editable == true)
				{
			?>
		
			<tr id='<?php echo $__level_key?>'>
				<th class='n_name'><input type='text' class='n_input auto_c' name='n_label-<?=$__level_key?>' id='n_label_$<?=$__level_key?>' value='<?=$child['node']?>'/></th>
				<td class='n_value'><textarea name='n_txt_<?=$__level_key?>' id='n_txt-<?=$__level_key?>'><?=$child['tree'][0]?></textarea></td>
				<td class='actions_bt_at' style='width:80px;'>
					<a onclick="delete_at('<?=$__level_key?>','txt_node', 'images');"><img src='images/delete.gif' alt='Delete' title='Delete Text Node'/></a>
				</td>
			
			<?php } else { 
			
				?>
				<th class='n_name'><div class='read_only'><?php echo $child['node']?></div></th>
				<td class='n_value'><div class='read_only'><?php echo $child['tree'][0]?></div></td>
			<?php } ?>
			</tr>
					
		</tbody>
	</table>

	<?php echo print_subfooter($params, $editable);?>

	</form>
</div>