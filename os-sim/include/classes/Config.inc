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
* - Config()
* - has_conf()
* - get_conf()
* - get_list()
* - insert()
* - update()
* - reset()
* - common_reset()
* - debian_reset()
* - fedora_reset()
* - default_reset()
* Classes list:
* - Config
*/
require_once 'classes/Security.inc';
require_once 'ossim_db.inc';
require_once 'ossim_sql.inc';
class Config {
    var $confs = array();
    var $conn = null;
    function Config() {
        $ossimdb = new ossim_db();
        $this->conn = $ossimdb->Nconnect();
        $sql = "SELECT * FROM config";
        if (!$rs = & $this->conn->Execute($sql)) {
            print $this->conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                $key = $rs->fields["conf"];
                $value = $rs->fields["value"];
                $this->confs[$key] = $value;
                $rs->MoveNext();
            }
        }
    }
    function has_conf($key) {
        return array_key_exists($key, $this->confs);
    }
    function get_conf($key) {
        return $this->confs[$key];
    }
    function get_list() {
        return $this->confs;
    }
    function insert($key, $value) {
        ossim_valid($key, array(
            OSS_LETTER,
            OSS_SCORE,
            OSS_DIGIT
        )); // update() uses that too
        ossim_valid($value, array(
            OSS_NULLABLE
        ));
        if (ossim_error()) die(ossim_error());
        $sql = "INSERT INTO config (conf, value) VALUES (?, ?)";
        $params = array(
            $key,
            $value
        );
        if (!$this->conn->Execute($sql, $params)) die($this->conn->ErrorMsg());
    }
    function update($key, $value) {
        ossim_valid($key, array(
            OSS_LETTER,
            OSS_SCORE,
            OSS_DIGIT
        )); // insert() uses that too
        if (ossim_error()) die(ossim_error());
        $sql = "DELETE FROM config WHERE conf = ?";
        if (!$this->conn->Execute($sql, array(
            $key
        ))) die($this->conn->ErrorMsg());
        Config::insert($key, $value);
    }
    /*  Reset default values */
    function reset() {
        require_once ('classes/Log_action.inc');
        Log_action::log(8, NULL);
        $sql = "DELETE FROM config";
        if ($this->conn->Execute($sql) === false) {
            print 'error deleting: ' . $this->conn->ErrorMsg() . '<BR>';
            exit;
        }
        Config::common_reset();
        if (preg_match("/debian/i", $_SERVER["SERVER_SOFTWARE"])) Config::debian_reset();
        elseif (preg_match("/redhat|fedora/i", $_SERVER["SERVER_SOFTWARE"])) Config::fedora_reset();
        else Config::default_reset();
    }
    /* Common values */
    function common_reset() {
        /* Snort: Snort database and paths configuration */
        Config::insert('snort_path', '/etc/snort/');
        Config::insert('snort_rules_path', '/etc/snort/rules/');
        Config::insert('snort_type', 'mysql');
        Config::insert('snort_base', 'snort');
        Config::insert('snort_user', 'root');
        Config::insert('snort_pass', 'ossim');
        Config::insert('snort_host', 'localhost');
        Config::insert('snort_port', '3306');
        /* Osvdb: Osvdb database configuration */
        Config::insert('osvdb_type', 'mysql');
        Config::insert('osvdb_base', 'osvdb');
        Config::insert('osvdb_user', 'root');
        Config::insert('osvdb_pass', 'ossim');
        Config::insert('osvdb_host', 'localhost');
        /* Server: Configure where the server is listening to */
        Config::insert('server_address', 'localhost');
        Config::insert('server_port', '40001');
        /* Metrics: Configure metric settings */
        Config::insert('recovery', '1');
        Config::insert('threshold', '30');
        /* PHP: PHP Configuration (graphs, acls, database api) */
        Config::insert('phpgacl_path', '/usr/share/phpgacl/');
        Config::insert('fpdf_path', '/usr/share/fpdf/');
        Config::insert('xajax_php_path', 'xajax/');
        Config::insert('xajax_js_path', '../js/');
        Config::insert('report_graph_type', 'images');
        Config::insert('use_svg_graphics', '0');
        Config::insert('use_resolv', '0');
        Config::insert('jpgraph_path', '/usr/share/jpgraph/');
        /* RRD: RRD Configuration (graphing) */
        Config::insert('graph_link', '../report/graphs/draw_rrd.php');
        Config::insert('rrdtool_lib_path', '/usr/lib/perl5/');
        Config::insert('rrdtool_path', '/usr/bin/');
        Config::insert('mrtg_path', '/usr/bin/');
        Config::insert('mrtg_rrd_files_path', '/var/lib/ossim/rrd/');
        Config::insert('rrdpath_host', '/var/lib/ossim/rrd/host_qualification/');
        Config::insert('rrdpath_net', '/var/lib/ossim/rrd/net_qualification/');
        Config::insert('rrdpath_global', '/var/lib/ossim/rrd/global_qualification/');
        Config::insert('rrdpath_level', '/var/lib/ossim/rrd/level_qualification/');
        /* Links: Links to other applications */
        Config::insert('ntop_link', 'http://localhost/ntop');
        Config::insert('use_ntop_rewrite', '0');
        Config::insert('ossim_link', '/ossim/');
        Config::insert('nagios_link', '/nagios/');
        Config::insert('use_munin', '0');
        Config::insert('munin_link', '/munin/');
        /* Backup: Backup configuration: backup database, directory, interval */
        Config::insert('backup_type', 'mysql');
        Config::insert('backup_base', 'snort');
        Config::insert('backup_user', 'root');
        Config::insert('backup_pass', 'ossim');
        Config::insert('backup_host', 'localhost');
        Config::insert('backup_port', '3306');
        Config::insert('backup_dir', '/var/lib/ossim/backup');
        Config::insert('backup_day', '5');
        /* Nessus: Nessus client configuration */
        Config::insert('nessus_user', 'ossim');
        Config::insert('nessus_pass', 'ossim');
        Config::insert('nessus_host', 'localhost');
        Config::insert('nessus_port', '1241');
        Config::insert('nessus_distributed', '0');
        Config::insert('nessus_path', '/usr/bin/nessus');
        Config::insert('nessus_rpt_path', '/usr/share/ossim/www/vulnmeter/');
        /* ACID: Acid cache configuration */
        Config::insert('acid_user', 'admin');
        Config::insert('acid_pass', 'admin');
        Config::insert('ossim_web_user', 'admin');
        Config::insert('ossim_web_pass', 'admin');
        /* External applications: Path to other applications */
        Config::insert('nmap_path', '/usr/bin/nmap');
        Config::insert('p0f_path', '/usr/sbin/p0f');
        Config::insert('arpwatch_path', '/usr/sbin/arpwatch');
        Config::insert('mail_path', '/usr/bin/mail');
        Config::insert('touch_path', '/bin/tail');
        Config::insert('wget_path', '/usr/bin/wget');
        Config::insert('have_scanmap3d', '0');
        /* Language */
        Config::insert('language', 'en_GB');
        Config::insert('locale_dir', '/usr/share/locale');
        /* User action logging*/
        Config::insert('user_action_log', '1');
        Config::insert('log_syslog', '1');
        /*Real time event viewer*/
        Config::insert('max_event_tmp', '10000');
        /* Login option */
        Config::insert('login_enforce_existing_user', 'yes');
        Config::insert('login_enable_ldap', 'no');
        Config::insert('login_ldap_server', '127.0.0.1');
        Config::insert('login_ldap_o', 'o=company');
        Config::insert('login_ldap_ou', 'ou=people');
    }
    /* Debian specific values */
    function debian_reset() {
        /* PHP: PHP Configuration (graphs, acls, database api) */
        Config::insert('adodb_path', '/usr/share/php/adodb/');
        /* RRD: RRD Configuration (graphing) */
        Config::insert('rrdpath_ntop', '/var/lib/ntop/rrd');
        Config::insert('font_path', '/usr/share/fonts/truetype/ttf-bitstream-vera/Vera.ttf');
        /* ACID: Acid cache configuration */
        Config::insert('event_viewer', 'base');
        Config::insert('acid_link', '/ossim/forensics/');
        Config::insert('acid_path', '/usr/share/ossim/www/forensics/');
    }
    /* fedora/redhat specific values */
    function fedora_reset() {
        /* PHP: PHP Configuration (graphs, acls, database api) */
        Config::insert('adodb_path', '/var/www/adodb/');
        /* RRD: RRD Configuration (graphing) */
        Config::insert('rrdpath_ntop', '/usr/share/ntop/rrd/');
        Config::insert('font_path', '/usr/share/ossim/fonts/Vera.ttf');
        /* ACID: Acid cache configuration */
        Config::insert('event_viewer', 'acid');
        Config::insert('acid_link', 'http://localhost/acid/');
        Config::insert('acid_path', '/var/www/acid/');
    }
    /* default values */
    function default_reset() {
        Config::debian_reset();
    }
}
?>
