<?
include_once('classes/Session.inc');
include_once('classes/Security.inc');
include_once('classes/Plugin_sid.inc');
Session::logcheck("MenuEvents", "EventsForensics");

require_once ('ossim_db.inc');
/* connect to db */
$db = new ossim_db();
$conn = $db->connect();
$plugin_id = GET('id');
$plugin_sid = GET('sid');
$prio = GET('prio');
$rel = GET('rel');
$category = GET('category');
$subcategory = GET('subCategory');
ossim_valid($plugin_id, OSS_DIGIT, 'illegal:' . _("plugin_id"));
ossim_valid($plugin_sid, OSS_DIGIT, 'illegal:' . _("plugin_sid"));
ossim_valid($prio, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("prio"));
ossim_valid($rel, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("rel"));
ossim_valid($category, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("category"));
ossim_valid($subcategory, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("subcategory"));
if (ossim_error()) {
    die(ossim_error());
}
if (GET('modify') != "") {
	Plugin_sid::update($conn,$plugin_id,$plugin_sid,$prio,$rel,$category,$subcategory);
	?><script type="text/javascript">parent.GB_hide();</script><?
}
// Category
require_once 'classes/Category.inc';
$list_categories=Category::get_list($conn);
// Plugin sid data
$plugins = Plugin_sid::get_list($conn,"WHERE plugin_id=$plugin_id AND sid=$plugin_sid");
$plugin = $plugins[0];

$error_message = "";

if(!isset($plugins[0])){
    $error_message = _("Plugin id or plugin sid doesn't exist");
}
else {
    $rel = $plugin->get_reliability();
    $prio = $plugin->get_priority();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <link rel="stylesheet" href="../style/style.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript">
  function load_subcategory (category_id) {
		$("#ajaxSubCategory").html("<img src='../pixmaps/loading.gif' width='20' alt='Loading'>Loading");
		$.ajax({
			type: "GET",
			url: "../conf/modifypluginsid_ajax.php",
			data: { category_id:category_id },
			success: function(msg) {
				$("#ajaxSubCategory").html(msg);
			}
		});
	}
  </script>  
</head>
<body>
<?php
if ($error_message!=""){
    ?>
    <table class="transparent" align="center">
        <tr height="10">
            <td class="nobborder">&nbsp;
            </td>
        </tr>
        <tr>
            <td class="nobborder"><?php echo $error_message ?>
            </td>
        </tr>
        <tr height="10">
            <td class="nobborder">&nbsp;
            </td>
        </tr>
        <tr>
            <td class="nobborder" style="text-align:center">
                <form>
                    <input class="button" type="button" value="<?php echo _("Back")?>" onclick="parent.GB_hide();" />
                </form>
            </td>
        </tr>
    </table>
<?php
}
else {
?>
    <form method="get">
    <input type="hidden" name="modify" value="1">
    <input type="hidden" name="id" value="<?=$plugin_id?>">
    <input type="hidden" name="sid" value="<?=$plugin_sid?>">
    <table class="transparent" align="center">
		<tr>
		    <td colspan="2" class="center nobborder" style="padding:10px"><b><?=$plugin->get_name()?></b></td>
		</tr>
		<tr>
		    <td class="nobborder"> <?=_("Priority")?>: </td>
		    <td class="nobborder left">
		        <select name="prio">
		        <? for ($i = 0; $i <= 5; $i++) { ?>
		        <option value="<?=$i?>" <? if ($prio == $i) echo "selected"?>><?=$i?>
		        <? } ?>
		        </select>
		    </td>
		</tr>
		<tr>
		    <td class="nobborder"> <?=_("Reliability")?>:</td>
		    <td class="nobborder left">
		        <select name="rel">
		        <? for ($i = 0; $i <= 10; $i++) { ?>
		        <option value="<?=$i?>" <? if ($rel == $i) echo "selected"?>><?=$i?>
		        <? } ?>
		        </select>
		    </td>
		</tr>
		<tr>
		  <td class="nobborder"> <?php echo gettext("Category"); ?>: </td>
		  <td class="nobborder left">
		        <select name="category" onchange="load_subcategory(this.value);">
					<option value='NULL'<?php if ($plugin->get_category_id()=='') { echo ' SELECTED'; } ?>>&nbsp;</option>
				<?php foreach ($list_categories as $category) { ?>
					<option value='<?php echo $category->get_id(); ?>'<?php if ($plugin->get_category_id()==$category->get_id()) { echo ' SELECTED'; } ?>><?php echo  str_replace('_', ' ', $category->get_name()); ?></option>
				<?php } ?>
		        </select>
		  </td>
		</tr>
		<tr>
		    <td class="nobborder"> <?php echo gettext("Subcategory"); ?>: </td>
		    <td class="nobborder left">
			<div id="ajaxSubCategory">
				<select name="subCategory">
				<?php if ($plugin->get_subcategory_id()=='') { ?>
					<option value='NULL' SELECTED>&nbsp;</option>
				<?php
				}else{
				// Subcategory
				require_once 'classes/Subcategory.inc';
		
				$list_subcategories=Subcategory::get_list($conn,'WHERE cat_id='.$plugin->get_category_id().' ORDER BY name');
				foreach ($list_subcategories as $subcategory) {
				?>
					<option value='<?php echo $subcategory->get_id(); ?>'<?php if ($plugin->get_subcategory_id()==$subcategory->get_id()) { echo ' SELECTED'; } ?>><?php echo  str_replace('_', ' ', $subcategory->get_name()); ?></option>
				<?php
					}
				}
				?>
				</select>
			</div>
		  </td>
		</tr>      
        <tr>
            <td colspan="2" class="center nobborder" style="padding:10px"><input type="submit" value="<?=_("Update")?>" class="button"></td>
        </tr>
    </table>
    </form>
<?php
}
?>
</body>
</html>
