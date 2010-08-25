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
* - scapeSql()
* - OssimQuery()
* Classes list:
*/
if (!defined('OSS_DIGIT')) define('OSS_DIGIT', "0-9");
if (!defined('OSS_LETTER')) define('OSS_LETTER', "A-Za-záéíóúñ");
if (!defined('OSS_SPACE')) define('OSS_SPACE', " ");
if (!defined('OSS_ALPHA')) define('OSS_ALPHA', OSS_DIGIT . OSS_LETTER);
if (!defined('OSS_SCORE')) define('OSS_SCORE', "\_\-");
if (!defined('OSS_DOT')) define('OSS_DOT', "\.");
if (!defined('OSS_COLON')) define('OSS_COLON', "\:");
if (!defined('OSS_IP')) define('OSS_IP', OSS_DIGIT . OSS_DOT);
if (!defined('OSS_MAC')) define('OSS_MAC', OSS_ALPHA . OSS_COLON);
if (!defined('OSS_PUNC')) define('OSS_PUNC', "\.\!\#\$\%\^\&\*\_\-\=\+\:\;\,\?\ \(\))\/");
if (!defined('OSS_AT')) define('OSS_AT', "\@");
if (!defined('OSS_MAIL')) define('OSS_MAIL', OSS_AT . OSS_ALPHA . OSS_DOT . OSS_SCORE);
if (!defined('OSS_USER')) define('OSS_USER', OSS_LETTER . OSS_DIGIT . OSS_SCORE . OSS_DOT); // for validating logins/users
/*
* Escapes special characters in a SQL statement
*/
function scapeSql($sql) {
    if ($sql == "") return $sql;
    /* prevent double quoting */
    if (get_magic_quotes_gpc()) $sql = stripslashes($sql);
    if (!is_numeric($sql)) {
        if (function_exists('mysql_real_escape_string')) return mysql_real_escape_string($sql);
        elseif (function_exists('pg_escape_string')) return pg_escape_string($sql);
        else return addslashes($sql);
    }
    return $sql;
}
function OssimQuery($sql, $debug = FALSE) {
    if ($debug) print "Ossim query: $sql<br/>\n";
    $force_ending = array(
        /* attempt to do a 2nd SQL query */
        ";",
        /* attempt to unused part of the SQL query */
        "--|#",
        /* SQL injection like 1=1 or a=a */
        "((OR|AND|LIKE)\s*(\S+))\s*(=|<>)\s*\\4",
    );
    foreach($force_ending as $limit) {
        /*** DEBUG, will be removed.. ***/
        if (1) {
            if (preg_match("/(.*?)$limit.*$/", $sql)) {
                $tmp = preg_replace("/(.*?)$limit.*$/", "\\1", $sql);
                require_once ('ossim_error.inc');
                $error = new ossimWarn();
                $error->display("STRING_NOT_VALID");
            }
        }
        /*** end DEBUG ***/
        $sql = preg_replace("/(.*?)$limit.*$/i", "\\1", $sql);
    }
    return $sql;
}
?>
