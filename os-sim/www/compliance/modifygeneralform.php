<?php
/*****************************************************************************
*
*    License:
*
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
*/
require_once ('classes/Session.inc');
Session::logcheck("MenuIntelligence", "ComplianceMapping");
?>
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>

<?php
require_once 'ossim_db.inc';
require_once 'classes/Security.inc';
require_once 'classes/Compliance.inc';
$sid = GET('sid');
ossim_valid($sid, OSS_DIGIT, 'illegal:' . _("sid"));
if (ossim_error()) {
    die(ossim_error());
}
$db = new ossim_db();
$conn = $db->connect();

if (list($category_list,$total) = Compliance::get_category($conn, "AND plugin_sid.sid = $sid")) {
    $cat = $category_list[0];
}
?>
<?php include ("../hmenu.php"); ?>

<form method="post" action="modifygeneral.php">
	<input type="hidden" name="insert" value="insert"/>
	
	<table align="center">
		<tr>
			<th> <?php echo gettext("SID"); ?> (*)</th>
			<td class="left"><input type="text" name="sid" value="<?php echo $cat->get_sid(); ?>"></td>
		</tr>
		<!--
		  <tr>
			<th> <?php
			//echo gettext("Description"); ?></th>
			<td class="left">
				<textarea name="descr" cols="30" rows="6"><?php
			//echo $cat->get_descr(); ?></textarea>
			</td>
		  </tr>
		-->
		<tr>
			<th> <?php echo gettext("Targeted"); ?></th>
			<td class="left">
				<select name="targeted">
					<option <?php if ($cat->get_targeted() == 1) echo " selected='selected' "; ?> value="1"><?php echo gettext("Yes"); ?></option>
					<option <?php if ($cat->get_targeted() == 0) echo " selected='selected' "; ?> value="0"><?php echo gettext("No"); ?></option>
				</select>
			</td>
		</tr>
		
		<tr>
			<th> <?php echo gettext("UnTargeted"); ?></th>
				<td class="left">
					<select name="untargeted">
						<option <?php if ($cat->get_untargeted() == 1) echo " selected='selected' "; ?> value="1"><?php echo gettext("Yes"); ?></option>
						<option <?php if ($cat->get_untargeted() == 0) echo " selected='selected' "; ?> value="0"><?php echo gettext("No"); ?></option>
				</select>
			</td>
		</tr>
		
		<tr>
			<th> <?php echo gettext("Approach"); ?></th>
			<td class="left">
				<select name="approach">
				<option  <?php if ($cat->get_approach() == 1) echo " selected='selected' "; ?> value="1"><?php echo gettext("Yes"); ?></option>
				<option <?php if ($cat->get_approach() == 0) echo " selected='selected' "; ?> value="0"><?php echo gettext("No"); ?></option>
			</select>
			</td>
		</tr>
	
		<tr>
			<th> <?php echo gettext("Exploration"); ?></th>
			<td class="left">
				<select name="exploration">
					<option <?php if ($cat->get_exploration() == 1) echo " selected='selected' "; ?> value="1"><?php echo gettext("Yes"); ?></option>
					<option <?php if ($cat->get_exploration() == 0) echo " selected='selected' "; ?> value="0"><?php echo gettext("No"); ?></option>
				</select>
			</td>
		</tr>
	
		<tr>
			<th> <?php echo gettext("Penetration");?> </th>
			<td class="left">
				<select name="penetration">
					<option <?php if ($cat->get_penetration() == 1) echo " selected='selected' "; ?> value="1"><?php echo gettext("Yes"); ?></option>
					<option <?php if ($cat->get_penetration() == 0) echo " selected='selected' "; ?> value="0"><?php echo gettext("No"); ?></option>
				</select>
			</td>
		</tr>
	  
		<tr>
			<th> <?php echo gettext("General Malware"); ?></th>
			<td class="left">
				<select name="generalmalware">
					<option <?php if ($cat->get_generalmalware() == 1) echo " selected='selected' "; ?> value="1"><?php echo gettext("Yes"); ?></option>
					<option <?php if ($cat->get_generalmalware() == 0) echo " selected='selected' "; ?> value="0"><?php echo gettext("No"); ?></option>
				</select>
			</td>
		</tr>
  
		<tr>
			<th> <?php echo gettext("Impact: QOS"); ?></th>
			<td class="left">
				<select name="imp_qos">
				<option
				<?php if ($cat->get_imp_qos() == 1) echo " selected='selected' "; ?> value="1"><?php echo gettext("Yes"); ?></option>
				<option
				<?php if ($cat->get_imp_qos() == 0) echo " selected='selected' "; ?> value="0"><?php echo gettext("No"); ?></option>
				</select>
			</td>
		</tr>
		
		<tr>
			<th> <?php echo gettext("Impact: Infleak"); ?></th>
			<td class="left">
				<select name="imp_infleak">
					<option <?php if ($cat->get_imp_infleak() == 1) echo " selected='selected' "; ?> value="1"><?php echo gettext("Yes"); ?></option>
					<option <?php if ($cat->get_imp_infleak() == 0) echo " selected='selected' "; ?> value="0"><?php echo gettext("No"); ?></option>
				</select>
			</td>
		</tr>
		
		<tr>
			<th> <?php echo gettext("Impact: Lawful"); ?></th>
			<td class="left">
				<select name="imp_lawful">
					<option <?php if ($cat->get_imp_lawful() == 1) echo " selected='selected' "; ?> value="1"><?php echo gettext("Yes"); ?></option>
					<option <?php if ($cat->get_imp_lawful() == 0) echo " selected='selected' "; ?> value="0"><?php echo gettext("No"); ?></option>
				</select>
			</td>
		</tr>
  
		<tr>
			<th> <?php echo gettext("Impact: Image"); ?></th>
			<td class="left">
				<select name="imp_image">
					<option <?php if ($cat->get_imp_image() == 1) echo " selected='selected' "; ?> value="1"><?php echo gettext("Yes"); ?></option>
					<option <?php if ($cat->get_imp_image() == 0) echo " selected='selected' "; ?> value="0"><?php echo gettext("No"); ?></option>
				</select>
			</td>
		</tr>
	  
		<tr>
			<th> <?php echo gettext("Impact: Financial"); ?></th>
			<td class="left">
				<select name="imp_financial">
					<option <?php if ($cat->get_imp_financial() == 1) echo " selected='selected' "; ?> value="1"><?php echo gettext("Yes"); ?></option>
					<option <?php if ($cat->get_imp_financial() == 0) echo " selected='selected' "; ?> value="0"><?php echo gettext("No"); ?></option>
				</select>
			</td>
		</tr>
		
		<tr>
			<th> <?php echo gettext("Availability"); ?></th>
			<td class="left">
				<select name="D">
					<option <?php if ($cat->get_D() == 1) echo " selected='selected' "; ?> value="1"><?php echo gettext("Yes"); ?></option>
					<option <?php if ($cat->get_D() == 0) echo " selected='selected' "; ?> value="0"><?php echo gettext("No"); ?></option>
				</select>
			</td>
		</tr>
	
		<tr>
			<th> <?php echo gettext("Integrity"); ?></th>
			<td class="left">
				<select name="I">
					<option <?php if ($cat->get_I() == 1) echo " selected='selected' "; ?> value="1"><?php echo gettext("Yes"); ?></option>
					<option <?php if ($cat->get_I() == 0) echo " selected='selected' "; ?> value="0"><?php echo gettext("No"); ?></option>
				</select>
			</td>
		</tr>
		
		<tr>
			<th> <?php echo gettext("Confidentiality"); ?></th>
			<td class="left">
				<select name="C">
					<option <?php if ($cat->get_C() == 1) echo " selected='selected' "; ?> value="1"><?php echo gettext("Yes"); ?></option>
					<option <?php if ($cat->get_C() == 0) echo " selected='selected' "; ?> value="0"><?php echo gettext("No"); ?></option>
				</select>
			</td>
		</tr>

		<tr>
			<th> <?php echo gettext("Network Anomaly"); ?></th>
			<td class="left">
				<select name="net_anomaly">
					<option <?php if ($cat->get_net_anomaly() == 1) echo " selected='selected' "; ?> value="1"><?php echo gettext("Yes"); ?></option>
					<option <?php if ($cat->get_net_anomaly() == 0) echo " selected='selected' "; ?> value="0"><?php echo gettext("No"); ?></option>
				</select>
			</td>
		</tr>
	  
		<tr>
			<td colspan="2" align="center">
				<input type="submit" value="<?=_("OK")?>" class="button" style="font-size:12px">
				<input type="reset" value="<?=_("Reset")?>" class="button" style="font-size:12px">
			</td>
		</tr>    
	</table>

</form>

<p align="center"><i><?php echo gettext("Values marked with (*) are mandatory"); ?></b></i></p>

</body>
</html>
<?php $db->close($conn); ?>
