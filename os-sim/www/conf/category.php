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
Session::logcheck("MenuConfiguration", "ConfigurationPlugins");
require_once 'ossim_db.inc';
require_once 'ossim_conf.inc';
require_once 'classes/Category.inc';
require_once 'classes/Subcategory.inc';
$db = new ossim_db();
$conn = $db->connect();

// Actions
$action=POST('action');
if(empty($action)){
	$action=GET('action');
}
ossim_valid($action, 'addSubCategory','addCategory','deleteSubcategory','deleteCategory', 'expand', 'renameCategory', 'renameSubcategory', OSS_NULLABLE, 'illegal:' . _("Action"));
//
if($action=='addSubCategory'){
	$idCategory=POST('id');
	ossim_valid($idCategory, OSS_ALPHA, 'illegal:' . _("Category"));
	//
	$nameSubCategory=POST('nameSubCategory');
	ossim_valid($nameSubCategory, OSS_SCORE, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Name Subcategory"));
	
	if(Subcategory::insert($conn, $idCategory, $nameSubCategory)){
		// insert ok
		$msg='Ok! Add Subcategory';
	}else{
		// fail insert
		$msg='Error no add Subcategory';
	}
}elseif($action=='addCategory'){
	$nameCategory=POST('nameCategory');
	ossim_valid($nameCategory, OSS_SCORE, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Name Subcategory"));
	
	if(Category::insert($conn, $nameCategory)){
		// insert ok
		$msg='Ok! Add Category';
	}else{
		// fail insert
		$msg='Error no add Category';
	}
}elseif($action=='deleteCategory'){
	$idCategory=GET('id');
	ossim_valid($idCategory, OSS_ALPHA, 'illegal:' . _("Category"));

	if(Category::delete($conn, $idCategory)){
		// insert ok
		$msg='Ok! Delete Category';
	}else{
		// fail insert
		$msg='Error no delete Category';
	}
	//Header('Location: category.php'); 
}elseif($action=='deleteSubcategory'){
	$idSubcategory=GET('id');
	ossim_valid($idSubcategory, OSS_ALPHA, 'illegal:' . _("Subcategory"));
	$idCategory=GET('idCategory');
	ossim_valid($idCategory, OSS_ALPHA, 'illegal:' . _("Category"));

	if(Subcategory::delete($conn, $idCategory, $idSubcategory)){
		// insert ok
		$msg='Ok! Delete Subcategory';
	}else{
		// fail insert
		$msg='Error no delete Subcategory';
	}
	//Header('Location: category.php?action=expand&id='.$idCategory); 
}elseif($action=='expand'){
	$idCategory=POST('id');
	ossim_valid($idCategory, OSS_ALPHA, 'illegal:' . _("Category"));
}elseif($action=='renameCategory'){
	$idCategory=POST('id');
	ossim_valid($idCategory, OSS_ALPHA, 'illegal:' . _("Category"));
	$nameCategory=POST('nameCategory');
	ossim_valid($nameCategory, OSS_SCORE, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Name Category"));
	
	if(Category::edit($conn, $idCategory, $nameCategory)){
		// insert ok
		$msg='Ok! Rename Category';
	}else{
		// fail insert
		$msg='Error no rename Category';
	}
}elseif($action=='renameSubcategory'){
	$idSubcategory=POST('id');
	ossim_valid($idSubcategory, OSS_ALPHA, 'illegal:' . _("Subcategory"));
	$nameSubCategory=POST('nameSubCategory');
	ossim_valid($nameSubCategory, OSS_SCORE, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Name Subcategory"));
	
	if(Subcategory::edit($conn, $idSubcategory, $nameSubCategory)){
		// insert ok
		$msg='Ok! Rename Subcategory';
	}else{
		// fail insert
		$msg='Error no rename Subcategory';
	}
}
//
if (ossim_error()) {
	die(ossim_error());
}

$list_categories=Category::get_list($conn);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title> <?php echo gettext("Priority and Reliability configuration"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
   <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
  <script type="text/javascript" src="../js/jquery.simpletip.js"></script>
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>

  <script type="text/javascript">
    function toggle_tr(id) {
      if ($("."+id).css('display')=='none')
      	$("."+id).css("display","");
      else
      	$("."+id).css("display","none");
     }
	
	function change_icon(id, src, src2)
	{
	   var img = $('#'+id).attr('src').split('/');
	   img = img[img.length-1];
	   var url = '../pixmaps/';
	  	  	   
	   if ( img == src)
	     $('#'+id).attr('src', url+src2);
	   else
	     $('#'+id).attr('src', url+src);
	}
	
	function cleanEditInput(){
		jQuery.each($('input[name="idAjax"]'), function(index, value2) {
			$('#'+value2.value).html('');
		});
	}
	
	function edit(type,id,name){
		if(type=='category'){
			partId1='category';
		}else{
			partId1='subcategory';
		}
		
		if($('input[name="idAjax"]',$('#'+partId1+'_ajax_'+id)).html()==null){
			cleanEditInput();
			if(type=='category'){
				action='renameCategory';
				nameInput='nameCategory';
			}else if(type=='subcategory'){
				action='renameSubcategory';
				nameInput='nameSubCategory';
			}else{
				alert('<?php echo _('Fail no edit');?>');
				return false;
			}
			
			$('#'+partId1+'_ajax_'+id).html('<form action="category.php" method="POST"><input type="hidden" name="idAjax" value="'+partId1+'_ajax_'+id+'" /><input type="hidden" name="action" value="'+action+'" /><input type="hidden" name="id" value="'+id+'" /><input type="text" name="'+nameInput+'" value="'+name+'" /> <input type="submit" value="<?php echo _('Rename'); ?>" class="lbutton" /></form>');
		}else{
			cleanEditInput();
		}

		return true;
	}
	
	function confirmDelete(type,id,idCategory){
		if(type=='category'){
			item='Category';
		}else{
			item='Subcategory';
		}
        var ans = confirm('<?php echo _('Are you sure you want to delete this');?> '+item+'?');
        if (ans){
			url='category.php?';
			if(type=='category'){
				url+='action=deleteCategory&';
			}else if(type=='subcategory'){
				url+='action=deleteSubcategory&';
			}else{
				alert('<?php echo _('Fail in delete');?>');
				return false;
			}
			url+='id='+id;
			if(idCategory!=null){
				url+='&idCategory='+idCategory;
			}

			document.location.href=url;
		}
    }
	
	$(document).ready(function(){
<?php	if($action=='addSubCategory'||$action=='deleteSubcategory'){ ?>
		toggle_tr('family_<?php echo $idCategory;?>');
<?php	} ?>
		$(".scriptinfo").simpletip({
				position: 'right',
				offset: [10, 5],
				content: '',
				baseClass: 'ytooltip',
				onBeforeShow: function() { 
					var txt = this.getParent().attr('txt');
					this.update(txt);
				}
		});
	 });

  </script>
  <style type="text/css">
  .desactive {
	filter: alpha(opacity=50);
	opacity: 0.5;
	}
  </style>
</head>
<body>
<?php include ("../hmenu.php"); ?>
    <table class="reporting_mod" border="0" width="90%" align="center" cellspacing="2" cellpadding="0">
        <tr>
			<td class="headerpr" id='rma'>
				<span style='padding-right:5px;'><?php echo _("Taxonomy")?></span>
			</td>
		</tr>
        <tr>
			<th><?php echo _("Categories")?></th>
		</tr>
        <?php
			foreach ($list_categories as $category) {
				$color = ($color=="") ? " bgcolor='#f2f2f2'" : "";
		?>
		<tr <?php echo $color;?>>
			<td style='text-align: left; padding:2px 0px 2px 10px; font-size: 12px; font-weight: bold;'>
				<div style="float:left">
					<a style='padding-left:0px; cursor:pointer;' onclick="toggle_tr('family_<?php echo $category->get_id();?>'); change_icon('img_family_<?php echo $category->get_id();?>', 'plus-small.png','minus-small.png'); return false;">
						<img id='img_family_<?php echo $category->get_id();?>' title='<?php echo _("Show details");?>' src='../pixmaps/plus-small.png' align='absmiddle'/>
					</a>
					<span style='margin-left:8px;'>
						<a href="javascript:void(0);" onclick="edit('category',<?php echo $category->get_id().",'".$category->get_name()."'";?>)" title="<?php echo _("Edit")?>"><img border="0" align="absmiddle" src="../vulnmeter/images/pencil.png" height="12" /></a>
						<?php if(!$category->get_inUse()){?><a href="javascript:void(0);" onclick="confirmDelete('category',<?php echo $category->get_id();?>)" title="<?php echo _("Delete")?>"><?php } ?><img border="0" align="absmiddle" src="../vulnmeter/images/delete.gif" height="12" class="<?php if($category->get_inUse()){?>desactive<?php } ?>" /><?php if(!$category->get_inUse()){?></a><?php } ?>
					</span>
					<span style='margin-left:5px;padding-right: 5px'><a href="" onclick="GB_show('Data Sources - <?php echo $category->get_name() ?>','plugin.php?nohmenu=1&category_id=<?php echo $category->get_id() ?>',400,800);return false"><?php echo $category->get_name(); ?></a></span>
				</div>
				<div id="category_ajax_<?php echo $category->get_id(); ?>" style="margin-left:10px;float:left"></div>
			</td>
		</tr>
		<tr class='family_<?php echo $category->get_id();?>' style='display: none;'>
			<td>
				<table width='98%' class='noborder' cellpadding='0' style='margin:10px;'>
				<?php
					$list_subcategories=Subcategory::get_list($conn,'WHERE cat_id='.$category->get_id().' ORDER BY name');
					foreach ($list_subcategories as $subcategory) {
				?>
					<tr>
						<td class="nobborder" style="padding-left:40px">
							<div style="float:left">
								<span style='margin-right:8px;'>
									<a href="javascript:void(0);" onclick="edit('subcategory',<?php echo $subcategory->get_id().",'".$subcategory->get_name()."'";?>)" title="<?php echo _("Edit")?>"><img border="0" align="absmiddle" src="../vulnmeter/images/pencil.png" height="12" /></a>
									<?php if(!$subcategory->get_inUse()){?><a href="javascript:void(0);" onclick="confirmDelete('subcategory',<?php echo $subcategory->get_id();?>,<?php echo $category->get_id();?>)" title="<?php echo _("Delete")?>"><?php } ?><img border="0" align="absmiddle" src="../vulnmeter/images/delete.gif" height="12" class="<?php if($subcategory->get_inUse()){?>desactive<?php } ?>" /><?php if(!$subcategory->get_inUse()){?></a><?php } ?>
								</span>
								<strong><a href="" onclick="GB_show('Data Sources - <?php echo $subcategory->get_name() ?>','plugin.php?nohmenu=1&category_id=<?php echo $category->get_id() ?>&subcategory_id=<?php echo $subcategory->get_id() ?>',400,800);return false"><?php echo $subcategory->get_name();?></a></strong>
							</div>
							<div id="subcategory_ajax_<?php echo $subcategory->get_id(); ?>" style="margin-left:10px;float:left"></div>
						</td>				
					</tr>
				<?php } ?>
					<tr>
						<td class="nobborder" style="padding-left:40px">
							<form action="category.php" method="POST">
								<input type="hidden" name="action" value="addSubCategory" />
								<input type="hidden" name="id" value="<?php echo $category->get_id();?>" />
								<input type="text" name="nameSubCategory" value="" />
								<input type="submit" value="<?php echo _('Add'); ?>" class="lbutton" />
							</form>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<?php
			}
        ?>
		<tr>
			<td class="nobborder" style="padding-left:10px">
				<form action="category.php" method="POST">
					<input type="hidden" name="action" value="addCategory" />
					<input type="text" name="nameCategory" value="" />
					<input type="submit" value="<?php echo _('Add Category'); ?>" class="lbutton" />
				</form>
			</td>
		</tr>
     </table>
    <br/>
    <br/>
   </body>
</html>
<?php $db->close($conn); ?>