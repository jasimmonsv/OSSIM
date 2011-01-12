<?php 
/*****************************************************************************
*
*    License:
*
*   Copyright (c) 2007-2011 AlienVault
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
require_once ('classes/Security.inc');
require_once "ossim_conf.inc";
$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);
if (!preg_match("/pro|demo/i",$version)) {
	echo "<html><body><a href='http://www.alienvault.com/information.php?interest=ProfessionalSIEM' target='_blank' title='Profesional SIEM'><img src='../pixmaps/sem_pro.png' border=0></a></body></tml>";
	exit;
}
ossim_valid(GET('del_export'), OSS_DIGIT, OSS_ALPHA, OSS_NULLABLE, '=', 'illegal:' . _("del_export"));
ossim_valid(GET('action'), OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("action"));
if (ossim_error()) {
    die(ossim_error());
}

if (GET('action') == "ps") {
	$cmd = "ps ax | grep wget | grep -v grep";
	$output = explode("\n",`$cmd`);
	if (count($output) > 1) {
		foreach ($output as $line) if (trim($line) != "" && preg_match("/\-\-load\-cookies\=(.+)\/cookie\.txt/",$line,$found)) {
			if (is_dir($found[1])) {
				$cmd = "du -ch '".$found[1]."' | tail -1 | awk '{print $1}'";
				$size = explode("\n",`$cmd`);
				echo "(".$size[0]." written)";
			} else {
				echo "(unknown)";
			}
			echo "\n";
		}
	}
	exit;
}

$config = parse_ini_file("everything.ini");
if (is_dir($config["searches_dir"])) {
	$user = Session::get_session_user();
	$find_str = $config["searches_dir"].$user;
	$cmd = "ls -t '$find_str'*/results.txt";
	$res = explode("\n",`$cmd`);
	foreach ($res as $line) if (preg_match("/$user\_(\d\d\d\d\-\d\d\-\d\d \d\d\:\d\d\:\d\d)\_(\d\d\d\d\-\d\d\-\d\d \d\d\:\d\d\:\d\d)\_(none|date|date\_desc)\_(.*)\/results\.txt/",$line,$found)) {
		$name = $found[1].$found[2].$found[3].$found[4];
		$filename = trim($line);
		if ((GET('del_export') != "" && $name == base64_decode(GET('del_export')) && file_exists($filename)) || GET('del_export') == "all") {
			unlink($filename);
			$dirname = str_replace("/results.txt","",$filename);
			if (file_exists($dirname."/loglist.txt")) {
				unlink($dirname."/loglist.txt");
			}
			if (file_exists($dirname."/cookie.txt")) {
				unlink($dirname."/cookie.txt");
			}
			if (file_exists($dirname."/bgt.txt")) {
				unlink($dirname."/bgt.txt");
			}
			if (is_dir($dirname)) {
				rmdir($dirname);
			}
		} else {
			$exports[$filename] = array($found[1],$found[2],$found[3],$found[4]);
		}
	}
}
?>
<?php if (count($exports) < 1) { ?>
<i><?php echo _("No export files found") ?>.</i>
<?php } else { ?>
<table style="border:0px">
	<tr>
		<th><?php echo _("From") ?></th>
		<th><?php echo _("To") ?></th>
		<th><?php echo _("Query") ?></th>
		<th><?php echo _("Size") ?></th>
		<td align="right"><a href="" onclick="if(confirm('<?php echo _("Are you sure?") ?>')) delete_export('all');return false;"><img src="../vulnmeter/images/delete.gif" alt="<?php echo _("Delete all"); ?>" title="<?php echo _("Delete all"); ?>" border="0"></img></a></td>
	</tr>
<? $i=0;
foreach ($exports as $filename=>$name) {
						$size = (filesize($filename)/1024 > 2000) ? floor(filesize($filename)/1024/1024)."MB" : floor(filesize($filename)/1024)."KB";
                        $i++;    ?>
                        <tr class="<?php if($i%2==0){ echo 'impar'; }else{ echo 'par'; } ?>" style="padding-top:4px">
                        <td><b><?php echo $name[0] ?></b></td>
                        <td><b><?php echo $name[1] ?></b></td>
                        <td><b><?php echo ($name[3] != "") ? "yes" : "no" ?></b></td>
                        <td><?php echo $size ?></td>
                        <td>
                        <a href="download.php?query=<?php echo $name[3] ?>&start=<?php echo $name[0] ?>&end=<?php echo $name[1] ?>&sort=<?php echo $name[2] ?>"><img src="../pixmaps/download.png" alt="<?php echo _("Download"); ?>" title="<?php echo _("Download"); ?>" border="0" /></a>
                        <a href="" onclick="if(confirm('<?php echo _("Are you sure?") ?>')) delete_export('<?php echo base64_encode($name[0].$name[1].$name[2].$name[3]) ?>');return false;"><img src="../vulnmeter/images/delete.gif" alt="<?php echo _("Delete"); ?>" title="<?php echo _("Delete"); ?>" border="0" /></a>
                        </td>
                        </tr>
                        <? } ?>
                        </table>
                        <?php } ?>
