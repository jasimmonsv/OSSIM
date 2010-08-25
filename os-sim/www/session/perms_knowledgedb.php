<?
include ("classes/Session.inc");
include ("classes/User_config.inc");
require_once 'ossim_db.inc';
$db = new ossim_db();
$conn = $db->connect();
$users = Session::get_list($conn);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>

<script type="text/javascript">
function get_checks () {
	var items = document.fperms.elements;
	var str = "";
	for (var i=0;i<items.length;i++) {
		if (items[i].name.match("user") && items[i].checked == true)
			if (str == "") str = str+items[i].value;
			else str = str+','+items[i].value;
	}
	return str;
}
</script> 
  
 </head>
<body>
<table width="100%">
	<form name="fperms" method="POST">
	<tr><th>Select users for document permissions</th></tr>
<?
$user_perms = new User_config($conn);
if (GET('user') != "") {
	$perms = $user_perms->get(GET('user'),"user_docs",'php',"knowledgedb");
	if ($perms == "") {
		$perms = array("admin" => 1, GET('user') => 1);
	}
}
else {
	$perms = array("admin" => 1);
}
$i = 1;
foreach ($users as $user) {
	?>
	<tr><td class="left"><input type="checkbox" name="user<?=$i?>" value="<?=$user->get_login()?>" <? if ($perms[$user->get_login()]) echo "checked"?>> <?=$user->get_login()?></td></tr>
<? $i++; } ?>
	<tr><td class="nobborder" style="padding:20px;text-align:center"><input type="button" class="btn" value="Update" onclick="parent.kdbperms(get_checks());parent.GB_hide();">
	</form>
</table>
</body>
</html>
