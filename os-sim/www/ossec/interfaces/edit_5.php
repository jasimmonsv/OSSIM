
<?php $title = ($editable == true) ? _("Edit node:  $node_name ") : _("Show node:  $node_name ");?>

<div id='edit_container'>

<form name='form_m' id='form_m'>
	<table id='header_rule'>
		<tbody>
			<tr><th class='rule_title'><?php echo $title?></th></tr>
		</tbody>
	</table>
   
	<table class='er_container' id='erc1'>
		<tbody id='erb_c1'>
			<?php echo print_subheader("attributes", $editable) ?>
			<?php echo print_attributes($attributes, $editable); ?>
		</tbody>
	</table>
	
	<input id='sep' name='sep' type='hidden' value='1'/>

	<table class='er_container' id='erc2'>
		<tbody id='erb_c2'>
			<?php echo print_subheader("rules", $editable) ?>
			<?php echo print_children($children, $editable); ?>
		</tbody>
	</table>

	<?php echo print_subfooter($params, $editable);?>

	</form>
</div>