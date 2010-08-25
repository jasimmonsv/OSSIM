<?
include('classes/Security.inc');
include('classes/Plugin_sid.inc');
require_once ('ossim_db.inc');
/* connect to db */
$db = new ossim_db();
$conn = $db->connect();
$plugin_id = GET('id');
$plugin_sid = GET('sid');
$prio = GET('prio');
$rel = GET('rel');
ossim_valid($plugin_id, OSS_DIGIT, 'illegal:' . _("plugin_id"));
ossim_valid($plugin_sid, OSS_DIGIT, 'illegal:' . _("plugin_sid"));
ossim_valid($prio, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("prio"));
ossim_valid($rel, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("rel"));
if (ossim_error()) {
    die(ossim_error());
}
if (GET('modify') != "") {
	Plugin_sid::update($conn,$plugin_id,$plugin_sid,$prio,$rel);
	?><script type="text/javascript">parent.GB_hide();</script><?
}
$plugins = Plugin_sid::get_list($conn,"WHERE plugin_id=$plugin_id AND sid=$plugin_sid");
$plugin = $plugins[0];
$rel = $plugin->get_reliability();
$prio = $plugin->get_priority();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <link rel="stylesheet" href="../style/style.css"/>
</head>
<body>
<table class="transparent" align="center">
<form method="get">
<input type="hidden" name="modify" value="1">
<input type="hidden" name="id" value="<?=$plugin_id?>">
<input type="hidden" name="sid" value="<?=$plugin_sid?>">
	<tr>
		<td colspan="2" class="center nobborder" style="padding:10px"><b><?=$plugin->get_name()?></b></td>
	</tr>
	<tr>
		<td class="nobborder"><?=_("Priority")?>:
			<select name="prio">
			<? for ($i = 0; $i <= 8; $i++) { ?>
			<option value="<?=$i?>" <? if ($prio == $i) echo "selected"?>><?=$i?>
			<? } ?>
			</select>
		</td>
		<td class="nobborder" style="padding-left:10px"><?=_("Reliability")?>:
			<select name="rel">
			<? for ($i = 0; $i <= 10; $i++) { ?>
			<option value="<?=$i?>" <? if ($rel == $i) echo "selected"?>><?=$i?>
			<? } ?>
			</select>
		</td>
	</tr>
	<tr>
		<td colspan="2" class="center nobborder" style="padding:10px"><input type="submit" value="<?=_("OK")?>" class="btn"></td>
	</tr>
</form>
</table>
</body>
</html>
