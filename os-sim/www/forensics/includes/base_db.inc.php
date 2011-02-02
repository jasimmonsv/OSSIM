<?php
/**
* Class and Function List:
* Function list:
* - baseCon()
* - baseDBConnect()
* - baseConnect()
* - basePConnect()
* - baseClose()
* - baseExecute()
* - baseErrorMessage()
* - baseTableExists()
* - baseIndexExists()
* - baseInsertID()
* - baseTimestampFmt()
* - baseSQL_YEAR()
* - baseSQL_MONTH()
* - baseSQL_DAY()
* - baseSQL_HOUR()
* - baseSQL_MINUTE()
* - baseSQL_SECOND()
* - baseSQL_UNIXTIME()
* - baseSQL_TIMESEC()
* - baseGetDBversion()
* - getSafeSQLString()
* - baseRS()
* - baseFetchRow()
* - baseColCount()
* - baseRecordCount()
* - baseFreeRows()
* - VerifyDBAbstractionLib()
* - NewBASEDBConnection()
* - MssqlKludgeValue()
* - RepairDBTables()
* - ClearDataTables()
* - CleanUnusedSensors()
* Classes list:
* - baseCon
* - baseRS
*/
/*******************************************************************************
** OSSIM Forensics Console
** Copyright (C) 2009 OSSIM/AlienVault
** Copyright (C) 2004 BASE Project Team
** Copyright (C) 2000 Carnegie Mellon University
**
** (see the file 'base_main.php' for license details)
**
** Built upon work by Roman Danyliw <rdd@cert.org>, <roman@danyliw.com>
** Built upon work by the BASE Project Team <kjohnson@secureideas.net>
**/
defined('_BASE_INC') or die('Accessing this file directly is not allowed.');
class baseCon {
    var $DB;
    var $DB_type;
    var $DB_name;
    var $DB_host;
    var $DB_port;
    var $DB_username;
    var $lastSQL;
    var $version;
    var $sql_trace;
    function baseCon($type) {
        $this->DB_type = $type;
    }
    function baseDBConnect($method, $database, $host, $port, $username, $password, $force = 0) {
        GLOBAL $archive_dbname, $archive_host, $archive_port, $archive_user, $archive_password;
        // Check archive cookie to see if they want to be using the archive tables
        // and check - do we force to use specified database even if archive cookie is set
        if ((@$_COOKIE['archive'] == 1) && ($force != 1)) {
            // Connect to the archive tables
            $this->baseConnect($archive_dbname, $archive_host, $archive_port, $archive_user, $archive_password);
        } else {
            // Connect to the main alert tables
            if ($method == DB_CONNECT) $this->baseConnect($database, $host, $port, $username, $password);
            else $this->basePConnect($database, $host, $port, $username, $password);
        }
    }
    function baseConnect($database, $host, $port, $username, $password) {
        GLOBAL $sql_trace_mode, $sql_trace_file;
        $this->DB = NewADOConnection();
        $this->DB_name = $database;
        $this->DB_host = $host;
        $this->DB_port = $port;
        $this->DB_username = $username;
        $this->DB_password = $password;
        if ($sql_trace_mode > 0) {
            $this->sql_trace = fopen($sql_trace_file, "a");
            if (!$this->sql_trace) {
                ErrorMessage(gettext("Unable to open SQL trace file") . " '" . $sql_trace_file . "'");
                die();
            }
        }
        $db = $this->DB->Connect((($port == "") ? $host : ($host . ":" . $port)) , $username, $password, $database);
        if (!$db) {
            $tmp_host = ($port == "") ? $host : ($host . ":" . $port);
            echo '<P><B>' . gettext("Error connecting to DB :") . ' </B>' . $database . '@' . $tmp_host . _ERRSQLCONNECTINFO;
            echo $this->baseErrorMessage();
            die();
        }
        /* Set the database schema version number */
        $sql = "SELECT vseq FROM schema";
        if ($this->DB_type == "mysql") $sql = "SELECT vseq FROM `schema`";
        if ($this->DB_type == "mssql") $sql = "SELECT vseq FROM [schema]";
        $result = $this->DB->Execute($sql);
        if ($this->baseErrorMessage() != "") $this->version = 0;
        else {
            $myrow = $result->fields;
            $this->version = $myrow[0];
            $result->Close();
        }
        if ($sql_trace_mode > 0) {
            fwrite($this->sql_trace, "\n--------------------------------------------------------------------------------\n");
            fwrite($this->sql_trace, "Connect [" . $this->DB_type . "] " . $database . "@" . $host . ":" . $port . " as " . $username . "\n");
            fwrite($this->sql_trace, "[" . date("M d Y H:i:s", time()) . "] " . $_SERVER["SCRIPT_NAME"] . " - db version " . $this->version);
            fwrite($this->sql_trace, "\n--------------------------------------------------------------------------------\n\n");
            fflush($this->sql_trace);
        }
        return $db;
    }
    function basePConnect($database, $host, $port, $username, $password) {
        GLOBAL $sql_trace_mode, $sql_trace_file;
        $this->DB = NewADOConnection();
        $this->DB_name = $database;
        $this->DB_host = $host;
        $this->DB_port = $port;
        $this->DB_username = $username;
        if ($sql_trace_mode > 0) {
            $this->sql_trace = fopen($sql_trace_file, "a");
            if (!$this->sql_trace) {
                ErrorMessage(gettext("Unable to open SQL trace file") . " '" . $sql_trace_file . "'");
                die();
            }
        }
		$db = $this->DB->PConnect((($port == "") ? $host : ($host . ":" . $port)) , $username, $password, $database);
		if (!$db) {
            $tmp_host = ($port == "") ? $host : ($host . ":" . $port);
            echo '<P><B>' . gettext("Error (p)connecting to DB :") . ' </B>' . $database . '@' . $tmp_host . _ERRSQLCONNECTINFO;
            echo $this->baseErrorMessage();
            die();
        }
        /* Set the database schema version number */
        $sql = "SELECT vseq FROM `schema`";
        if ($this->DB_type == "mssql") $sql = "SELECT vseq FROM [schema]";
        if ($this->DB_type == "postgres") $sql = "SELECT vseq FROM schema";
        $result = $this->DB->Execute($sql);
        if ($this->baseErrorMessage() != "") $this->version = 0;
        else {
            $myrow = $result->fields;
            $this->version = $myrow[0];
            $result->Close();
        }
        if ($sql_trace_mode > 0) {
            fwrite($this->sql_trace, "\n--------------------------------------------------------------------------------\n");
            fwrite($this->sql_trace, "PConnect [" . $this->DB_type . "] " . $database . "@" . $host . ":" . $port . " as " . $username . "\n");
            fwrite($this->sql_trace, "[" . date("M d Y H:i:s", time()) . "] " . $_SERVER["SCRIPT_NAME"] . " - db version " . $this->version);
            fwrite($this->sql_trace, "\n--------------------------------------------------------------------------------\n\n");
            fflush($this->sql_trace);
        }
        return $db;
    }
    function baseClose() {
        $this->DB->Close();
    }
    function baseExecute($sql, $start_row = 0, $num_rows = - 1, $die_on_error = true) {
        if (preg_match("/\s+(WHERE|AND)\s+1\s*=\s*1\s*$/i", $sql)) $sql = preg_replace("/(WHERE|AND)\s+1\s*=\s*1\s*$/i", "", $sql);
        GLOBAL $debug_mode, $sql_trace_mode;
        /* ** Begin DB specific SQL fix-up ** */
        if ($this->DB_type == "mssql") $sql = eregi_replace("''", "NULL", $sql);
        $this->lastSQL = $sql;
        $limit_str = "";
        //echo "$sql<br>\n";
        
		/* Check whether need to add a LIMIT / TOP / ROWNUM clause */
        
		if ($num_rows == - 1) $rs = new baseRS($this->DB->Execute($sql) , $this->DB_type);
        else {
            if (($this->DB_type == "mysql") || ($this->DB_type == "mysqlt") || ($this->DB_type == "maxsql")) {
				//echo "Objeto DB:".print_r($this->DB)."<br>";
				//echo "<br>EJECUTANDO: ".$sql . " LIMIT " . $start_row . ", " . $num_rows." en ".$this->DB_type."<br>";
				$tmprow = $this->DB->Execute($sql . " LIMIT " . $start_row . ", " . $num_rows);
				//print_r($_GET);
				//print_r($_SESSION);
				$rs = new baseRS($tmprow , $this->DB_type);
                $limit_str = " LIMIT " . $start_row . ", " . $num_rows;
				//echo "<br>ROW:";
				//print_r($tmprow);
				//echo "<br>ERROR MSG: " . $this->baseErrorMessage(). "<br>";
            } else if ($this->DB_type == "oci8") {
				$rs = new baseRS($this->DB->Execute($sql) , $this->DB_type);
                $limit_str = " LIMIT " . $start_row . ", " . $num_rows;
            } else if ($this->DB_type == "postgres") {
				$rs = new baseRS($this->DB->Execute($sql . " LIMIT " . $num_rows . " OFFSET " . $start_row) , $this->DB_type);
                $limit_str = " LIMIT " . $num_rows . " OFFSET " . $start_row;
            }
            /* Databases which do not support LIMIT (e.g. MS SQL) natively must emulated it */
            else {
				$rs = new baseRS($this->DB->Execute($sql) , $this->DB_type);
                $i = 0;
                while (($i < $start_row) && $rs) {
                    if (!$rs->row->EOF) $rs->row->MoveNext();
                    $i++;
                }
            }
        }
		//echo "<br>ejecutando baseExecute num_rows $num_rows (base_db.inc):$sql<br>Limitstr:$limit_str";
		//echo "<br><br>";
		//print_r($rs);
		
        if ($sql_trace_mode > 0) {
            fputs($this->sql_trace, $sql . "$limit_str\n");
            fflush($this->sql_trace);
        }
        if ((!$rs || $this->baseErrorMessage() != "") && $die_on_error) {
            echo '</TABLE></TABLE></TABLE>
               <FONT COLOR="#FF0000"><B>' . gettext("Database ERROR:") . '</B>' . ($this->baseErrorMessage()) . '</FONT>' . '<P><PRE>' . ($debug_mode > 0 ? ($this->lastSQL) . $limit_str : "") . '</PRE><P>';
            die();
        } else {
            return $rs;
        }
    }
    function baseErrorMessage() {
        GLOBAL $debug_mode;
        if ($this->DB->ErrorMsg() && ($this->DB_type != 'mssql' || (!strstr($this->DB->ErrorMsg() , 'Changed database context to') && !strstr($this->DB->ErrorMsg() , 'Changed language setting to')))) return '</TABLE></TABLE></TABLE>' . '<FONT COLOR="#FF0000"><B>' . gettext("Database ERROR:") . '</B>' . ($this->DB->ErrorMsg()) . '</FONT>' . '<P><CODE>' . ($debug_mode > 0 ? $this->lastSQL : "") . '</CODE><P>';
    }
    function baseTableExists($table) {
        if (in_array($table, $this->DB->MetaTables())) return 1;
        else return 0;
    }
    function baseIndexExists($table, $index_name) {
        if (in_array($index_name, $this->DB->MetaIndexes($table))) return 1;
        else return 0;
    }
    function baseInsertID() {
        /* Getting the insert ID fails on certain databases (e.g. postgres), but we may use it on the once it works
        * on.  This function returns -1 if the dbtype is postgres, then we can run a kludge query to get the insert
        * ID.  That query may vary depending upon which table you are looking at and what variables you have set at
        * the current point, so it can't be here and needs to be in the actual script after calling this function
        *  -- srh (02/01/2001)
        */
        if (($this->DB_type == "mysql") || ($this->DB_type == "mysqlt") || ($this->DB_type == "maxsql") || ($this->DB_type == "mssql")) return $this->DB->Insert_ID();
        else if ($this->DB_type == "postgres" || ($this->DB_type == "oci8")) return -1;
    }
    function baseTimestampFmt($timestamp) {
        // Not used anywhere????? -- Kevin
        return $this->DB->DBTimeStamp($timestamp);
    }
    function baseSQL_YEAR($func_param, $op, $timestamp) {
        if (($this->DB_type == "mysql") || ($this->DB_type == "mysqlt") || ($this->DB_type == "maxsql") || ($this->DB_type == "mssql")) return " YEAR($func_param) $op $timestamp ";
        else if ($this->DB_type == "oci8") return " to_number( to_char( $func_param, 'RRRR' ) ) $op $timestamp ";
        else if ($this->DB_type == "postgres") return " DATE_PART('year', $func_param) $op $timestamp ";
    }
    function baseSQL_MONTH($func_param, $op, $timestamp) {
        if (($this->DB_type == "mysql") || ($this->DB_type == "mysqlt") || ($this->DB_type == "maxsql") || ($this->DB_type == "mssql")) return " MONTH($func_param) $op $timestamp ";
        else if ($this->DB_type == "oci8") return " to_number( to_char( $func_param, 'MM' ) ) $op $timestamp ";
        else if ($this->DB_type == "postgres") return " DATE_PART('month', $func_param) $op $timestamp ";
    }
    function baseSQL_DAY($func_param, $op, $timestamp) {
        if (($this->DB_type == "mysql") || ($this->DB_type == "mysqlt") || ($this->DB_type == "maxsql")) return " DAYOFMONTH($func_param) $op $timestamp ";
        else if ($this->DB_type == "oci8") return " to_number( to_char( $func_param, 'DD' ) ) $op $timestamp ";
        else if ($this->DB_type == "postgres") return " DATE_PART('day', $func_param) $op $timestamp ";
        else if ($this->DB_type == "mssql") return " DAY($func_param) $op $timestamp ";
    }
    function baseSQL_HOUR($func_param, $op, $timestamp) {
        if (($this->DB_type == "mysql") || ($this->DB_type == "mysqlt") || ($this->DB_type == "maxsql")) return " HOUR($func_param) $op $timestamp ";
        else if ($this->DB_type == "oci8") return " to_number( to_char( $func_param, 'HH' ) ) $op $timestamp ";
        else if ($this->DB_type == "postgres") return " DATE_PART('hour', $func_param) $op $timestamp ";
        else if ($this->DB_type == "mssql") return " DATEPART(hh, $func_param) $op $timestamp ";
    }
    function baseSQL_MINUTE($func_param, $op, $timestamp) {
        if (($this->DB_type == "mysql") || ($this->DB_type == "mysqlt") || ($this->DB_type == "maxsql")) return " MINUTE($func_param) $op $timestamp ";
        else if ($this->DB_type == "oci8") return " to_number( to_char( $func_param, 'MI' ) ) $op $timestamp ";
        else if ($this->DB_type == "postgres") return " DATE_PART('minute', $func_param) $op $timestamp ";
        else if ($this->DB_type == "mssql") return " DATEPART(mi, $func_param) $op $timestamp ";
    }
    function baseSQL_SECOND($func_param, $op, $timestamp) {
        if (($this->DB_type == "mysql") || ($this->DB_type == "mysqlt") || ($this->DB_type == "maxsql")) return " SECOND($func_param) $op $timestamp ";
        else if ($this->DB_type == "oci8") return " to_number( to_char( $func_param, 'SS' ) ) $op $timestamp ";
        else if ($this->DB_type == "postgres") return " DATE_PART('second', $func_param) $op $timestamp ";
        else if ($this->DB_type == "mssql") return " DATEPART(ss, $func_param) $op $timestamp ";
    }
    function baseSQL_UNIXTIME($func_param, $op, $timestamp) {
        if (($this->DB_type == "mysql") || ($this->DB_type == "mysqlt") || ($this->DB_type == "maxsql")) {
            return " UNIX_TIMESTAMP($func_param) $op $timestamp ";
        } else if ($this->DB_type == "oci8") return " to_number( $func_param ) $op $timestamp ";
        else if ($this->DB_type == "postgres") {
            if (($op == "") && ($timestamp == ""))
            /* Catches the case where I want to get the UNIXTIME of a constant
            *   i.e. DATE_PART('epoch', timestamp) > = DATE_PART('epoch', timestamp '20010124')
            *                                            (This one /\ )
            */
            return " DATE_PART('epoch', $func_param::timestamp) ";
            else return " DATE_PART('epoch', $func_param::timestamp) $op $timestamp ";
        } else if ($this->DB_type == "mssql") {
            return " DATEDIFF(ss, '1970-1-1 00:00:00', $func_param) $op $timestamp ";
        }
    }
    function baseSQL_TIMESEC($func_param, $op, $timestamp) {
        if (($this->DB_type == "mysql") || ($this->DB_type == "mysqlt") || ($this->DB_type == "maxsql")) return " TIME_TO_SEC($func_param) $op $timestamp ";
        else if ($this->DB_type == "oci8") return " to_number( $func_param ) $op $timestamp ";
        else if ($this->DB_type == "postgres") {
            if (($op == "") && ($timestamp == "")) return " DATE_PART('second', DATE_PART('day', '$func_param') ";
            else return " DATE_PART('second', DATE_PART('day', $func_param) ) $op $timestamp ";
        } else if ($this->DB_type == "mssql") {
            if (($op == "") && ($timestamp == "")) return " DATEPART(ss, DATEPART(dd, $func_parm) ";
            else return " DATEPART(ss, DATE_PART(dd, $func_param) ) $op $timestamp ";
        }
    }
    function baseGetDBversion() {
        return $this->version;
    }
    function getSafeSQLString($str) {
        $t = str_replace("\\", "\\\\", $str);
        if ($this->DB_type != "mssql" && $this->DB_type != "oci8") $t = str_replace("'", "\'", $t);
        else $t = str_replace("'", "''", $t);
        $t = str_replace("\"", "\\\\\"", $t);
        return $t;
    }
}
class baseRS {
    var $row;
    var $DB_type;
    function baseRS($id, $type) {
        $this->row = $id;
        $this->DB_type = $type;
    }
    function baseFetchRow() {
        /* Workaround for the problem, that the database may contain NULL whereas "NOT NULL" has been defined, when it was created */
        if (!is_object($this->row)) {
            if ($debug_mode > 1) {
                echo "<BR><BR>" . __FILE__ . ':' . __LINE__ . ": ERROR: \$this->row is not an object<BR><PRE>";
                debug_print_backtrace();
                echo "<BR><BR>";
                echo "var_dump(\$this):<BR>";
                var_dump($this);
                echo "<BR><BR>";
                echo "var_dump(\$this->row):<BR>";
                var_dump($this->row);
                echo "</PRE><BR><BR>";
            }
            return "";
        }
        if (!$this->row->EOF) {
            $temp = $this->row->fields;
            $this->row->MoveNext();
            return $temp;
        } else return "";
    }
    function baseColCount() {
        // Not called anywhere???? -- Kevin
        return $this->row->FieldCount();
    }
    function baseRecordCount() { // Is This if statement necessary?  -- Kevin
        /* MS SQL Server 7, MySQL, Sybase, and Postgres natively support this function */
        if (($this->DB_type == "mysql") || ($this->DB_type == "mysqlt") || ($this->DB_type == "maxsql") || ($this->DB_type == "mssql") || ($this->DB_type == "sybase") || ($this->DB_type == "postgres") || ($this->DB_type == "oci8")) return $this->row->RecordCount();
        /* Otherwise we need to emulate this functionality */
        else {
            $i = 0;
            while (!$this->row->EOF) {
                ++$i;
                $this->row->MoveNext();
            }
            return $i;
        }
    }
    function baseFreeRows() {
        /* Workaround for the problem, that the database may contain NULL,
        * although "NOT NULL" had been defined when it had been created.
        * In such a case there's nothing to free(). So we can ignore this
        * row and don't have anything to do. */
        if (!is_object($this->row)) {
            if ($debug_mode > 1) {
                echo '<BR><BR>';
                echo __FILE__ . ':' . __LINE__ . ': ERROR: $this->row is not an object.';
                echo '<BR><PRE>';
                debug_print_backtrace();
                echo '<BR><BR>var_dump($this):<BR>';
                var_dump($this);
                echo '<BR><BR>var_dump($this->row):<BR>';
                var_dump($this->row);
                echo '</PRE><BR><BR>';
            }
        } else {
            $this->row->Close();
        }
    }
}
function VerifyDBAbstractionLib($path) {
    GLOBAL $debug_mode;
    if ($debug_mode > 0) echo (gettext("Checking for DB abstraction lib in") . " '$path'<BR>");
    if (!ini_get('safe_mode')) {
        if (is_readable($path)) // is_file
        return true;
        else {
            echo _ERRSQLDBALLOAD1 . '"' . $path . '"' . _ERRSQLDBALLOAD2;
            die();
        }
    }
}
function NewBASEDBConnection($path, $type) {
    GLOBAL $debug_mode;
    if (!(($type == "mysql") || ($type == "mysqlt") || ($type == "maxsql") || ($type == "postgres") || ($type == "mssql") || ($type == "oci8"))) {
        echo "<B>" . gettext("Invalid Database Type Specified") . "</B>" . "<P>:" . _ERRSQLDBTYPEINFO1 . "<CODE>'$type'</CODE>. " . _ERRSQLDBTYPEINFO2;
        die();
    }
    /* Export ADODB_DIR for use by ADODB */
    /** Sometimes it may already be defined. So check to see if it is first -- Tim Rupp**/
    if (!defined('ADODB_DIR')) {
        define('ADODB_DIR', $path);
    }
    $GLOBALS['ADODB_DIR'] = $path;
    $last_char = substr($path, strlen($path) - 1, 1);
    if ($debug_mode > 1) echo "Original path = '" . $path . "'<BR>";
    if ($last_char == "\\" || $last_char == "/") {
        if ($debug_mode > 1) echo "Attempting to load: '" . $path . "adodb.inc.php'<BR>";
        VerifyDBAbstractionLib($path . "adodb.inc.php");
        include ($path . "adodb.inc.php");
    } else if (strstr($path, "/") || $path == "") {
        if ($debug_mode > 1) echo "Attempting to load: '" . $path . "/adodb.inc.php'<BR>";
        VerifyDBAbstractionLib($path . "/adodb.inc.php");
        include ($path . "/adodb.inc.php");
    } else if (strstr($path, "\\")) {
        if ($debug_mode > 1) echo "Attempting to load: '" . $path . "\\adodb.inc.php'<BR>";
        VerifyDBAbstractionLib($path . "\\adodb.inc.php");
        include ($path . "\\adodb.inc.php");
    }
    ADOLoadCode($type);
    return new baseCon($type);
}
function MssqlKludgeValue($text) {
    $mssql_kludge = "";
    for ($i = 0; $i < strlen($text); $i++) {
        $mssql_kludge = $mssql_kludge . "[" . substr($text, $i, 1) . "]";
    }
    return $mssql_kludge;
}
function RepairDBTables($db) {
    /* This function was completely commented in original....
    I will be searching to see where it was called from if at all */
}
function ClearDataTables($db) {
    $db->baseExecute("TRUNCATE acid_event");
    $db->baseExecute("TRUNCATE acid_event_input");
    //$db->baseExecute("TRUNCATE data");
    //$db->baseExecute("TRUNCATE event");
    //$db->baseExecute("TRUNCATE ossim_event");
    $db->baseExecute("TRUNCATE icmphdr");
    $db->baseExecute("TRUNCATE iphdr");
    //$db->baseExecute("TRUNCATE reference");
    //$db->baseExecute("TRUNCATE reference_system");
    //$db->baseExecute("TRUNCATE sig_class");
    //$db->baseExecute("TRUNCATE sig_reference");
    $db->baseExecute("TRUNCATE sensor");
    $db->baseExecute("TRUNCATE tcphdr");
    $db->baseExecute("TRUNCATE udphdr");
    $db->baseExecute("TRUNCATE extra_data");
    $db->baseExecute("TRUNCATE opt");
    // extra ac_* tables
    $db->baseExecute("TRUNCATE ac_sensor_sid");
    $db->baseExecute("TRUNCATE ac_sensor_signature");
    $db->baseExecute("TRUNCATE ac_sensor_ipsrc");
    $db->baseExecute("TRUNCATE ac_sensor_ipdst");
    $db->baseExecute("TRUNCATE ac_alerts_sid");
    $db->baseExecute("TRUNCATE ac_alerts_signature");
    $db->baseExecute("TRUNCATE ac_alerts_ipsrc");
    $db->baseExecute("TRUNCATE ac_alerts_ipdst");
    //$db->baseExecute("TRUNCATE ac_alertsclas_classid");
    //$db->baseExecute("TRUNCATE ac_alertsclas_sid");
    //$db->baseExecute("TRUNCATE ac_alertsclas_signature");
    //$db->baseExecute("TRUNCATE ac_alertsclas_ipsrc");
    //$db->baseExecute("TRUNCATE ac_alertsclas_ipdst");
    $db->baseExecute("TRUNCATE ac_srcaddr_ipdst");
    $db->baseExecute("TRUNCATE ac_srcaddr_ipsrc");
    $db->baseExecute("TRUNCATE ac_srcaddr_sid");
    //$db->baseExecute("TRUNCATE ac_srcaddr_signature");
    $db->baseExecute("TRUNCATE ac_dstaddr_ipdst");
    $db->baseExecute("TRUNCATE ac_dstaddr_ipsrc");
    $db->baseExecute("TRUNCATE ac_dstaddr_sid");
    //$db->baseExecute("TRUNCATE ac_dstaddr_signature");
    $db->baseExecute("TRUNCATE ac_layer4_sport");
    $db->baseExecute("TRUNCATE ac_layer4_sport_sid");
    $db->baseExecute("TRUNCATE ac_layer4_sport_signature");
    $db->baseExecute("TRUNCATE ac_layer4_sport_ipsrc");
    $db->baseExecute("TRUNCATE ac_layer4_sport_ipdst");
    $db->baseExecute("TRUNCATE ac_layer4_dport");
    $db->baseExecute("TRUNCATE ac_layer4_dport_sid");
    $db->baseExecute("TRUNCATE ac_layer4_dport_signature");
    $db->baseExecute("TRUNCATE ac_layer4_dport_ipsrc");
    $db->baseExecute("TRUNCATE ac_layer4_dport_ipdst");
}
// vim:tabstop=2:shiftwidth=2:expandtab
function CleanUnusedSensors($db) {
    $dsensors = array();
    $tmp_result = $db->baseExecute("SELECT sid FROM sensor");
    while ($myrow = $tmp_result->baseFetchRow()) {
        $tmp1_result = $db->baseExecute("SELECT sid,cid FROM acid_event WHERE sid=" . $myrow[0] . " LIMIT 1");
        if (!$mr = $tmp1_result->baseFetchRow()) {
            $dsensors[] = $myrow[0]; // mark to delete sensor
            
        }
        $tmp1_result->baseFreeRows();
    }
    $tmp_result->baseFreeRows();
    foreach($dsensors as $sid) $db->baseExecute("DELETE FROM sensor WHERE sid=$sid");
}
?>
