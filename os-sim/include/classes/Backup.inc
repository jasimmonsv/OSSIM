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
* - Insert()
* - Delete()
* - str2date()
* - array2str()
* - running_restoredb()
* Classes list:
* - Backup
*/
require_once ('classes/Session.inc');
require_once 'classes/Util.inc';
require_once 'ossim_db.inc';
require_once ('classes/Log_action.inc');
class Backup {
    function Insert($insert, $filter_by = "", $nomerge = "merge") {
        $newfiles = array();
        $oldfiles = array();
        $message = "";
        $files = "";
        $conf = $GLOBALS["CONF"];
        $data_dir = $conf->get_conf("data_dir");
        $backup_dir = $conf->get_conf("backup_dir");
        $user = Session::get_session_user();
        $user = escapeshellcmd($user);
		
        foreach ($insert as $file) {
            $command = "zcat $backup_dir/insert-$file.sql.gz|grep 'acid_event_input'|wc -l";
            $count = `$command`;

            if(intval($count)==0) {
                $command = "zcat $backup_dir/insert-$file.sql.gz|grep -i 'insert into'|wc -l";
                $count = `$command`;
                if(intval($count)>0) {
                    $currentdir =str_replace("/index.php", "", $_SERVER["SCRIPT_FILENAME"]);
                    shell_exec("rm -f /tmp/DBinsert$file.sql 2>/dev/null");
                    shell_exec("touch /tmp/DBinsert$file.sql 2>/dev/null");
                    shell_exec("cat $currentdir/DBconversor_firststep.sql >> /tmp/DBinsert$file.sql 2>&1");
                    shell_exec("zcat $backup_dir/insert-$file.sql.gz >> /tmp/DBinsert$file.sql 2>&1");
                    shell_exec("cat $currentdir/DBconversor_thirdstep.sql >> /tmp/DBinsert$file.sql 2>&1");
                    $oldfiles[] = $file;
                }
                else {
                    $message .= _("File: $backup_dir/insert-$file.sql.gz doesn't have events to insert")."<br>";
                }
            }
            else {
                $command = "zcat $backup_dir/insert-$file.sql.gz|grep -i 'insert into'|wc -l";
                $count = `$command`;
                if(intval($count)>0) {
                    //$res = popen("$data_dir/scripts/restoredb.pl insert $files $user &", "r");
                    $newfiles[] = $file;
                }
                else {
                    $message .= _("File: $backup_dir/insert-$file.sql.gz doesn't have events to insert\n")."<br>";
                }
            }
        }
        
        # insert to DB
        if (count($newfiles)>0 && count($oldfiles)>0) {
            $message = _("Can't be inserted the old and new format");
        }
        else if (count($oldfiles)>0) {
            $files = Backup::array2str($oldfiles);
            $files = escapeshellcmd($files);
            //echo "Launching: $data_dir/scripts/insertdb.pl $files $user\n";
            shell_exec("nohup perl $data_dir/scripts/insertdb.pl $files $user > /tmp/insertdb.log 2>&1 &");
        
        }
        else if (count($newfiles)>0) {
        	$total = count($newfiles);
        	if ($total < 1) { $total = 1; }
        	$inc = 100/$total;
        	$files = Backup::array2str($newfiles);
            $files = escapeshellcmd($files);
            //echo "Launching: $data_dir/scripts/restoredb.pl insert $files $user $nomerge $filter_by\n";exit;
            //shell_exec("nohup perl $data_dir/scripts/restoredb.pl insert $files $user $filter_by > /tmp/restoredb.log 2>&1 &");
        	?><script type="text/javascript">$("#pbar").progressBar(0);$("#progressText").html('<?php echo _("Executing insert script") ?>...');</script><?php
            $fp = popen("nohup perl $data_dir/scripts/restoredb.pl insert $files $user $nomerge $filter_by 2>>/dev/null", "r");
            $perc = 0;
            $error = "";
            while (!feof($fp)) {
            	ob_flush();
    			flush();
            	$line = trim(fgets($fp));
    			if (preg_match("/^Launching/",$line)) {
    				$perc += $inc;
    				?><script type="text/javascript">$("#pbar").progressBar(<?php echo floor($perc) ?>);$("#progressText").html('<?php echo $line ?>...');</script><?php
    			} elseif ($line != "") {
    				$error .= $line."<br>";
    			}
            }
            fclose($fp);
            if ($error != "") {
            	?><script type="text/javascript">$("#loading").hide();</script><?php
    			echo _("An error has occured executing")." '<b>$data_dir/scripts/restoredb.pl</b>':<br><b>'$error'</b>";
            } else {
            	?><script type="text/javascript">parent.reload_backup()</script><?php
            }
        }
        
        $infolog = array(
            $files
        );
        Log_action::log(44, $infolog);
        return $message;
    }
    function Delete($delete) {
        $conf = $GLOBALS["CONF"];
        $data_dir = $conf->get_conf("data_dir");
        $backup_dir = $conf->get_conf("backup_dir");
        $total = count($delete);
        if ($total < 1) { $total = 1; }
        $inc = 100/$total;
        $files = Backup::array2str($delete);
        if ($files != "") {
            $user = Session::get_session_user();
            $files = escapeshellcmd($files);
            $user = escapeshellcmd($user);
            //$res = popen("$data_dir/scripts/restoredb.pl delete $files $user &", "r");
            //echo "<br><br><br>nohup perl $data_dir/scripts/restoredb.pl remove $files $user";exit;
            //shell_exec("nohup perl $data_dir/scripts/restoredb.pl remove $files $user >/tmp/restoredb.log 2>&1 &");
            ?><script type="text/javascript">$("#pbar").progressBar(2);$("#progressText").html('<?php echo _("Executing remove script") ?>...');</script><?php
            $fp = popen("nohup perl $data_dir/scripts/restoredb.pl remove $files $user null 2>>/dev/null", "r");
            $perc = 0;
            $error = "";
            while (!feof($fp)) {
            	ob_flush();
    			flush();
            	$line = trim(fgets($fp));
    			if (preg_match("/^Launching/",$line)) {
    				$perc += $inc;
    				?><script type="text/javascript">$("#pbar").progressBar(<?php echo floor($perc) ?>);$("#progressText").html('<?php echo $line ?>...');</script><?php
    			} elseif ($line != "") {
    				$error .= $line."<br>";
    			}
            }
            fclose($fp);
        	if ($error != "") {
            	?><script type="text/javascript">$("#loading").hide();</script><?php
    			echo _("An error has occured executing")." '<b>$data_dir/scripts/restoredb.pl</b>':<br><b>'$error'</b>";
            } else {
            	?><script type="text/javascript">parent.reload_backup()</script><?php
            }
        }
        $infolog = array(
            $files
        );
        Log_action::log(45, $infolog);
    }
    function str2date($str) {
        list($year, $month, $day) = sscanf($str, "%4s%2s%2s");
        return "$year-$month-$day";
    }
    function array2str($arr) {
        $str = "";
        if (is_array($arr)) {
            while (list($key, $value) = each($arr)) {
                if ($str == "") $str = $value;
                else $str.= "," . $value;
            }
        }
        return $str;
    }
    function running_restoredb() {
        if (file_exists("/tmp/ossim-restoredb.pid")) {
            return 1;
        } else {
            return 0;
        }
    }
} //class backup

?>

