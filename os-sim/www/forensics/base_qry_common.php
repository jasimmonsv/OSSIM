<?php
/**
* Class and Function List:
* Function list:
* - PrintCriteriaState()
* - FieldRows2sql()
* - FormatTimeDigit()
* - addSQLItem()
* - array_count_values_multidim()
* - DateTimeRows2sql()
* - FormatPayload()
* - DataRows2sql()
* - PrintCriteria()
* - QuerySignature()
* - ProcessCriteria()
* Classes list:
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
*/
defined('_BASE_INC') or die('Accessing this file directly is not allowed.');
include_once ("$BASE_path/includes/base_signature.inc.php");
function PrintCriteriaState() {
    GLOBAL $layer4, $new, $submit, $sort_order, $num_result_rows, $current_view, $caller, $action, $action_arg, $sort_order;
    if ($GLOBALS['debug_mode'] >= 2) {
        echo "<PRE>";
        echo "<B>" . _SENSOR . ":</B> " . $_SESSION['sensor'] . "<BR>\n" . "<B>AG:</B> " . $_SESSION['ag'] . "<BR>\n" . "<B>" . _QCSIG . "</B>\n";
        print_r($_SESSION['sig']);
        echo "<BR><B>time struct (" . $_SESSION['time_cnt'] . "):</B><BR>";
        print_r($_SESSION['time']);
        echo "<BR><B>" . _QCIPADDR . " (" . $_SESSION['ip_addr_cnt'] . "):</B><BR>";
        print_r($_SESSION['ip_addr']);
        echo "<BR><B>" . _QCIPFIELDS . " (" . $_SESSION['ip_field_cnt'] . "):</B><BR>";
        print_r($_SESSION['ip_field']);
        echo "<BR><B>" . _QCTCPPORTS . " (" . $_SESSION['tcp_port_cnt'] . "):</B><BR>";
        print_r($_SESSION['tcp_port']);
        echo "<BR><B>" . _QCTCPFLAGS . "</B><BR>";
        print_r($_SESSION['tcp_flags']);
        echo "<BR><B>" . _QCTCPFIELD . " (" . $_SESSION['tcp_field_cnt'] . "):</B><BR>";
        print_r($_SESSION['tcp_field']);
        echo "<BR><B>" . _QCUDPPORTS . " (" . $_SESSION['udp_port_cnt'] . "):</B><BR>";
        print_r($_SESSION['udp_port']);
        echo "<BR><B>" . _QCUDPFIELDS . " (" . $_SESSION['udp_field_cnt'] . "):</B><BR>";
        print_r($_SESSION['udp_field']);
        echo "<BR><B>" . _QCICMPFIELDS . " (" . $_SESSION['icmp_field_cnt'] . "):</B><BR>";
        print_r($_SESSION['icmp_field']);
        echo "<BR><B>RawIP field (" . $_SESSION['rawip_field_cnt'] . "):</B><BR>";
        print_r($_SESSION['rawip_field']);
        echo "<BR><B>" . _QCDATA . " (" . $_SESSION['data_cnt'] . "):</B><BR>";
        print_r($_SESSION['data']);
        echo "</PRE>";
    }
    if ($GLOBALS['debug_mode'] >= 1) {
        echo "<PRE>
            <B>new:</B> '$new'   
            <B>submit:</B> '$submit'
            <B>sort_order:</B> '$sort_order'
            <B>num_result_rows:</B> '$num_result_rows'  <B>current_view:</B> '$current_view'
            <B>layer4:</B> '$layer4'  <B>caller:</B> '$caller'
            <B>action:</B> '$action'  <B>action_arg:</B> '$action_arg'
            </PRE>";
    }
}
function FieldRows2sql($field, $cnt, &$s_sql) {
    $tmp2 = "";
    if (!is_array($field)) $field = array();
    for ($i = 0; $i < $cnt; $i++) {
        $tmp = "";
        if ($field[$i][3] != "" && $field[$i][1] != " ") {
            $tmp = $field[$i][0] . " " . $field[$i][1] . " " . $field[$i][2] . " '" . $field[$i][3] . "' " . $field[$i][4] . " " . $field[$i][5];
        } else {
            if ($field[$i][3] != "" && $field[$i][1] == " ") ErrorMessage("<B>" . _QCERRCRITWARN . "</B> " . _QCERRVALUE . " '" . $field[$i][3] . "' " . _QCERRSPECFIELD);
            if (($field[$i][1] != " " && $field[$i][1] != "") && $field[$i][3] == "") ErrorMessage("<B>" . _QCERRCRITWARN . "</B> " . _QCERRFIELD . " '" . $field[$i][1] . "' " . _QCERRSPECVALUE);
        }
        $tmp2 = $tmp2 . $tmp;
        if ($i > 0 && $field[$i - 1][5] == ' ') ErrorMessage("<B>" . _QCERRCRITWARN . "</B> " . _QCERRBOOLEAN);
    }
    if ($tmp2 != "") {
        $s_sql = $s_sql . " AND ( " . $tmp2 . " )";
        return 1;
    }
    return 0;
}
function FormatTimeDigit($time_digit) {
    if (strlen(trim($time_digit)) == 1) $time_digit = "0" . trim($time_digit);
    return $time_digit;
}
function addSQLItem(&$sstring, $what_to_add) {
    $sstring = (strlen($sstring) == 0) ? "($what_to_add" : "$sstring AND $what_to_add";
}
function array_count_values_multidim($a, $out = false) {
    if ($out === false) $out = array();
    if (is_array($a)) {
        foreach($a as $e) $out = array_count_values_multidim($e, $out);
    } else {
        if (array_key_exists($a, $out)) $out[$a]++;
        else $out[$a] = 1;
    }
    return $out;
}
function DateTimeRows2sql($field, $cnt, &$s_sql) {
    GLOBAL $db;
    $tmp2 = "";
    $allempty = FALSE;
    $time_field = array(
        "mysql" => ":",
        "mssql" => ":"
    );
    $minsec = array(
        ">=" => "00",
        "<=" => "59"
    );
    //print_r($field)."<br><br>";
    if ($cnt >= 1 && count($field) == 0) return 0;
    for ($i = 0; $i < $cnt; $i++) {
        $tmp = "";
        if (isset($field[$i]) && $field[$i][1] != " " && $field[$i][1] != "") {
            //echo "entrando $i\n";
            $op = $field[$i][1];
            $t = "";
            /* Build the SQL string when >, >=, <, <= operator is used */
            if ($op != "=") {
                /* date */
                if ($field[$i][4] != " ") {
                    /* create the date string */
                    $t = $field[$i][4]; /* year */
                    if ($field[$i][2] != " ") {
                        $t = $t . "-" . $field[$i][2]; /* month */
                        echo "<!-- \n\n\n\n\n\n\n dia: -" . $field[$i][3] . "- -->\n\n\n\n\n\n";
                        if ($field[$i][3] != "") $t = $t . "-" . FormatTimeDigit($field[$i][3]); /* day */
                        else $t = (($i == 0) ? $t . "-01" : $t = $t . "-31");
                    } else $t = $t . "-01-01";
                }
                /* time */
                // For MSSQL, you must have colons in the time fields.
                // Otherwise, the DATEDIFF function will return Arithmetic Overflow
                if ($field[$i][5] != "") {
                    $t = $t . " " . FormatTimeDigit($field[$i][5]); /* hour */
                    if ($field[$i][6] != "") {
                        $t = $t . $time_field[$db->DB_type] . FormatTimeDigit($field[$i][6]); /* minute */
                        if ($field[$i][7] != "") $t = $t . $time_field[$db->DB_type] . FormatTimeDigit($field[$i][6]);
                        else $t = $t . $time_field[$db->DB_type] . $minsec[$op];
                    } else $t = $t . $time_field[$db->DB_type] . $minsec[$op] . $time_field[$db->DB_type] . $minsec[$op];
                }
                /* fixup if have a > by adding an extra day */
                else if ($op == ">" && $field[$i][4] != " ") $t = $t . " 23:59:59";
                /* fixup if have a <= by adding an extra day */
                else if ($op == "<=" && $field[$i][4] != " ") $t = $t . " 23:59:59";
                /* neither date or time */
                if ($field[$i][4] == " " && $field[$i][5] == "") ErrorMessage("<B>" . _QCERRCRITWARN . "</B> " . _QCERROPER . " '" . $field[$i][1] . "' " . _QCERRDATEVALUE);
                /* date or date/time */
                else if (($field[$i][4] != " " && $field[$i][5] != "") || $field[$i][4] != " ") {
                    if ($db->DB_type == "oci8") {
                        $tmp = $field[$i][0] . " timestamp " . $op . "to_date( '$t', 'YYYY-MM-DD HH24MISS' )" . $field[$i][8] . ' ' . $field[$i][9];
                    } else {
                        if (count($field) > 1) {
                            // Better fix for bug #1199128
                            // Number of values in each criteria line
                            //print_r($field[$i]);
                            $count = array_count_values_multidim($field[$i]);
                            // Number of empty values
                            $empty = $count[""];
                            // Total number of values in the criteria line (empty or filled)
                            $array_count = count($count);
                            // Check to see if any fields were left empty
                            //if(isset($count[""]))
                            // If the number of empty fields is greater than (impossible) or equal to (possible) the number of values in the array, then they must all be empty
                            //if ($empty >= $array_count)
                            //$allempty = TRUE;
                            // Trim off white space
                            $field[$i][9] = trim($field[$i][9]);
                            // And if the certain line was empty, then we dont care to process it
                            if ($allempty)
                            // So move on
                            continue;
                            else {
                                // Otherwise process it
                                if ($i < $cnt - 1) $tmp = $field[$i][0] . " timestamp " . $op . "'$t'" . $field[$i][8] . ' ' . CleanVariable($field[$i][9], VAR_ALPHA);
                                else $tmp = $field[$i][0] . " timestamp " . $op . "'$t'" . $field[$i][8];
                            }
                        } else {
                            // If we just have one criteria line, then do with it what we must
                            if ($i < $cnt - 1) $tmp = $field[$i][0] . " timestamp " . $op . "'$t'" . $field[$i][8] . ' ' . CleanVariable($field[$i][9], VAR_ALPHA);
                            else $tmp = $field[$i][0] . " timestamp " . $op . "'$t'" . $field[$i][8];
                        }
                    }
                }
                /* time */
                else if (($field[$i][5] != " ") && ($field[$i][5] != "")) {
                    ErrorMessage("<B>" . _QCERRCRITWARN . "</B> " . _QCERRINVHOUR);
                }
            }
            /* Build the SQL string when the = operator is used */
            else {
                $query_str = "";
                $query_str = $field[$i][4] . "-";
                $query_str.= $field[$i][2] . "-";
                $query_str.= $field[$i][3] . " ";
                $query_str.= $field[$i][5] . ":";
                $query_str.= $field[$i][6] . ":";
                $query_str.= $field[$i][7] . "";
                $query_str = preg_replace("/\s*\:+\s*$/", "", $query_str);
                addSQLItem($tmp, "timestamp like \"$query_str%\"");
                /* neither date or time */
                if ($tmp == "") ErrorMessage("<B>" . _QCERRCRITWARN . "</B> " . _QCERROPER . " '" . $field[$i][1] . "' " . _QCERRDATECRIT);
                else if ($i < $cnt - 1) $tmp = $field[$i][0] . $tmp . ')' . $field[$i][8] . CleanVariable($field[$i][9], VAR_ALPHA);
                else $tmp = $field[$i][0] . $tmp . ')' . $field[$i][8];
            }
        } else {
            if (isset($field[$i])) {
                if (($field[$i][2] != "" || $field[$i][3] != "" || $field[$i][4] != "") && $field[$i][1] == "") ErrorMessage("<B>" . _QCERRCRITWARN . "</B> " . _QCERRDATETIME . " '" . $field[$i][2] . "-" . $field[$i][3] . "-" . $field[$i][4] . " " . $field[$i][5] . ":" . $field[6] . ":" . $field[7] . "' " . _QCERROPERSELECT);
            }
        }
        if ($i > 0 && $field[$i - 1][9] == ' ' && $field[$i - 1][4] != " ") ErrorMessage("<B>" . _QCERRCRITWARN . "</B> " . _QCERRDATEBOOL);
        $tmp2 = (preg_match("/\s+(AND|OR)\s*$/", $tmp2) || $i == 0) ? $tmp2 . $tmp : $tmp2 . " AND " . $tmp;
    }
    $tmp2 = trim(preg_replace("/(\s*(AND|OR)\s*)+$/", "", $tmp2));
    if ($tmp2 != "" && $tmp2 != "AND" && $tmp2 != "OR") {
        $s_sql = $s_sql . " AND ( " . $tmp2 . " ) ";
        return 1;
    }
    return 0;
}
function FormatPayload($payload_str, $data_encode)
/* Accepts a payload string and decides whether any conversion is necessary
to create a sql call into the DB.  Currently we only are concerned with
hex <=> ascii.
*/ {
    /* if the source is hex strip out any spaces and \n */
    if ($data_encode == "hex") {
        $payload_str = str_replace("\n", "", $payload_str);
        $payload_str = str_replace(" ", "", $payload_str);
    }
    /* If both the source type and conversion type are the same OR
    no conversion type is specified THEN return the plain string */
    if (($data_encode[0] == $data_encode[1]) || $data_encode[1] == " ") {
        return $payload_str;
    } else {
        $tmp = "";
        /* hex => ascii */
        if ($data_encode[0] == "hex" && $data_encode[1] == "ascii") for ($i = 0; $i < strlen($payload_str); $i+= 2) {
            $t = hexdec($payload_str[$i] . $payload_str[$i + 1]);
            if ($t > 32 && $t < ord("z")) $tmp = $tmp . chr($t);
            else $tmp = $tmp . '.';
        }
        /* ascii => hex */
        else if ($data_encode[0] == "ascii" && $data_encode[1] == "hex") for ($i = 0; $i < strlen($payload_str); $i++) $tmp = $tmp . dechex(ord($payload_str[$i]));
        return strtoupper($tmp);
    }
    return ""; /* should be unreachable */
}
function DataRows2sql($field, $cnt, $data_encode, &$s_sql) {
    $tmp2 = "";
    //print "cnt para $field: $cnt<br>";
    for ($i = 0; $i < $cnt; $i++) {
        $tmp = "";
        if ($field[$i][2] != "" && $field[$i][1] != " ") {
            //$tmp = $field[$i][0]." data_payload ".$field[$i][1]." '%".FormatPayload($field[$i][2], $data_encode).
            //       "%' ".$field[$i][3]."".$field[$i][4]." ".$field[$i][5];
            $data_encode1 = array(
                "ascii",
                "hex"
            );
            $tmp = " acid_event.sid=extra_data.sid AND acid_event.cid=extra_data.cid AND (MATCH(data_payload) AGAINST ('" . FormatPayload($field[$i][2], $data_encode) . "' IN BOOLEAN MODE) OR data_payload LIKE '%" . FormatPayload($field[$i][2], $data_encode1) . "%')";
            //$tmp = " acid_event.sid=extra_.sid AND acid_event.cid=extra_.cid AND data_payload LIKE '%".FormatPayload($field[$i][2], $data_encode)."%'";
            
        } else {
            if ($field[$i][2] != "" && $field[$i][1] == " ") ErrorMessage("<B>" . _QCERRCRITWARN . "</B> " . _QCERRPAYLOAD . " '" . $field[$i][2] . "' " . _QCERRPAYCRITOPER);
            if (($field[$i][1] != " " && $field[$i][1] != "") && $field[$i][2] == "") ErrorMessage("<B>" . _QCERRCRITWARN . "</B> " . _QCERROPER . " '" . $field[$i][1] . "' " . _QCERRPAYCRITVALUE);
        }
        $tmp2 = $tmp2 . $tmp;
        if ($i > 0 && $field[$i - 1][4] == ' ') ErrorMessage("<B>" . _QCERRCRITWARN . "</B> " . _QCERRPAYBOOL);
    }
    if ($tmp2 != "") {
        $s_sql = $s_sql . " AND ( " . $tmp2 . " )";
        return 1;
    }
    return 0;
}
function PrintCriteria($caller) {
    GLOBAL $db, $cs, $last_num_alerts, $save_criteria;
    /* Generate the Criteria entered into a human readable form */
    $criteria_arr = array();
    /* If printing any of the LAST-X stats then ignore all the other criteria */
    if ($caller == "last_tcp" || $caller == "last_udp" || $caller == "last_icmp" || $caller == "last_any") {
        $save_criteria = $save_criteria . '&nbsp;&nbsp;';
        if ($caller == "last_tcp") $save_criteria.= _LAST . ' ' . $last_num_alerts . ' TCP ' . _ALERT;
        else if ($caller == "last_udp") $save_criteria.= _LAST . ' ' . $last_num_alerts . ' UDP ' . _ALERT;
        else if ($caller == "last_icmp") $save_criteria.= _LAST . ' ' . $last_num_alerts . ' ICMP ' . _ALERT;
        else if ($caller == "last_any") $save_criteria.= _LAST . ' ' . $last_num_alerts . ' ' . _ALERT;
        $save_criteria.= '&nbsp;&nbsp;</TD></TR></TABLE>';
        echo $save_criteria;
        return;
    }
    $tmp_len = strlen($save_criteria);
    //$save_criteria .= $cs->criteria['sensor']->Description();
    //$save_criteria .= $cs->criteria['sig']->Description();
    //$save_criteria .= $cs->criteria['sig_class']->Description();
    //$save_criteria .= $cs->criteria['sig_priority']->Description();
    //$save_criteria .= $cs->criteria['ag']->Description();
    //$save_criteria .= $cs->criteria['time']->Description();
    //$criteria_arr['meta'] = preg_replace ("/\[\d+\,\d+.*\]\s*/","",$cs->criteria['sensor']->Description());
    $criteria_arr['meta'] = $cs->criteria['sensor']->Description();
    $criteria_arr['meta'].= $cs->criteria['plugin']->Description();
    $criteria_arr['meta'].= $cs->criteria['plugingroup']->Description();
    $criteria_arr['meta'].= $cs->criteria['userdata']->Description();
    $criteria_arr['meta'].= $cs->criteria['sourcetype']->Description();
    $criteria_arr['meta'].= $cs->criteria['category']->Description();    
    $criteria_arr['meta'].= $cs->criteria['sig']->Description();
    $criteria_arr['meta'].= $cs->criteria['sig_class']->Description();
    $criteria_arr['meta'].= $cs->criteria['sig_priority']->Description();
    $criteria_arr['meta'].= $cs->criteria['ag']->Description();
    $criteria_arr['meta'].= $cs->criteria['time']->Description();
    $criteria_arr['meta'].= $cs->criteria['ossim_risk_a']->Description();
    $criteria_arr['meta'].= $cs->criteria['ossim_priority']->Description();
    $criteria_arr['meta'].= $cs->criteria['ossim_reliability']->Description();
    $criteria_arr['meta'].= $cs->criteria['ossim_asset_dst']->Description();
    $criteria_arr['meta'].= $cs->criteria['ossim_type']->Description();
    if ($criteria_arr['meta'] == "") {
        $criteria_arr['meta'].= '<I> ' . _ANY . ' </I>';
        $save_criteria.= '<I> ' . _ANY . ' </I>';
    }
    $save_criteria.= '&nbsp;&nbsp;</TD>';
    $save_criteria.= '<TD>';
    if (!$cs->criteria['ip_addr']->isEmpty() || !$cs->criteria['ip_field']->isEmpty()) {
        $criteria_arr['ip'] = $cs->criteria['ip_addr']->Description();
        $criteria_arr['ip'].= $cs->criteria['ip_field']->Description();
        $save_criteria.= $cs->criteria['ip_addr']->Description();
        $save_criteria.= $cs->criteria['ip_field']->Description();
    } else {
        $save_criteria.= '<I> &nbsp;&nbsp; ' . _ANY . ' </I>';
        $criteria_arr['ip'] = '<I> ' . _ANY . ' </I>';
    }
    $save_criteria.= '&nbsp;&nbsp;</TD>';
    $save_criteria.= '<TD CLASS="layer4title">';
    $save_criteria.= $cs->criteria['layer4']->Description();
    $save_criteria.= '</TD><TD>';
    if ($cs->criteria['layer4']->Get() == "TCP") {
        if (!$cs->criteria['tcp_port']->isEmpty() || !$cs->criteria['tcp_flags']->isEmpty() || !$cs->criteria['tcp_field']->isEmpty()) {
            $criteria_arr['layer4'] = $cs->criteria['tcp_port']->Description();
            $criteria_arr['layer4'].= $cs->criteria['tcp_flags']->Description();
            $criteria_arr['layer4'].= $cs->criteria['tcp_field']->Description();
            $save_criteria.= $cs->criteria['tcp_port']->Description();
            $save_criteria.= $cs->criteria['tcp_flags']->Description();
            $save_criteria.= $cs->criteria['tcp_field']->Description();
        } else {
            $criteria_arr['layer4'] = '<I> ' . _ANY . ' </I>';
            $save_criteria.= '<I> &nbsp;&nbsp; ' . _ANY . ' </I>';
        }
        $save_criteria.= '&nbsp;&nbsp;</TD>';
    } else if ($cs->criteria['layer4']->Get() == "UDP") {
        if (!$cs->criteria['udp_port']->isEmpty() || !$cs->criteria['udp_field']->isEmpty()) {
            $criteria_arr['layer4'] = $cs->criteria['udp_port']->Description();
            $criteria_arr['layer4'].= $cs->criteria['udp_field']->Description();
            $save_criteria.= $cs->criteria['udp_port']->Description();
            $save_criteria.= $cs->criteria['udp_field']->Description();
        } else {
            $criteria_arr['layer4'] = '<I> ' . _ANY . ' </I>';
            $save_criteria.= '<I> &nbsp;&nbsp; ' . _ANY . ' </I>';
        }
        $save_criteria.= '&nbsp;&nbsp;</TD>';
    } else if ($cs->criteria['layer4']->Get() == "ICMP") {
        if (!$cs->criteria['icmp_field']->isEmpty()) {
            $criteria_arr['layer4'] = $cs->criteria['icmp_field']->Description();
            $save_criteria.= $cs->criteria['icmp_field']->Description();
        } else {
            $criteria_arr['layer4'] = '<I> ' . _ANY . ' </I>';
            $save_criteria.= '<I> &nbsp;&nbsp; ' . _ANY . ' </I>';
        }
        $save_criteria.= '&nbsp;&nbsp;</TD>';
    } else if ($cs->criteria['layer4']->Get() == "RawIP") {
        if (!$cs->criteria['rawip_field']->isEmpty()) {
            $criteria_arr['layer4'] = $cs->criteria['rawip_field']->Description();
            $save_criteria.= $cs->criteria['rawip_field']->Description();
        } else {
            $criteria_arr['layer4'] = '<I> ' . _ANY . ' </I>';
            $save_criteria.= '<I> &nbsp&nbsp ' . _ANY . ' </I>';
        }
        $save_criteria.= '&nbsp;&nbsp;</TD>';
    } else {
        $criteria_arr['layer4'] = '<I> ' . _NONE . ' </I>';
        $save_criteria.= '<I> &nbsp;&nbsp; ' . _NONE . ' </I></TD>';
    }
    /* Payload ************** */
    $save_criteria.= '
        <TD>';
    if (!$cs->criteria['data']->isEmpty()) {
        $criteria_arr['payload'] = $cs->criteria['data']->Description();
        $save_criteria.= $cs->criteria['data']->Description();
    } else {
        $criteria_arr['payload'] = '<I> ' . _ANY . ' </I>';
        $save_criteria.= '<I> &nbsp;&nbsp; ' . _ANY . ' </I>';
    }
    $save_criteria.= '&nbsp;&nbsp;</TD>';
    if (!setlocale(LC_TIME, _LOCALESTR1)) if (!setlocale(LC_TIME, _LOCALESTR2)) setlocale(LC_TIME, _LOCALESTR3);
    
    // Report Data
    $report_data = array();
    $r_meta = preg_replace("/\<a (.*?)\<\/a\>|\&nbsp;|,\s+$/i","",preg_replace("/\<br\>/i",", ",$criteria_arr['meta']));
    $r_payload = preg_replace("/\<a (.*?)\<\/a\>|\&nbsp;/i","",$criteria_arr['payload']);
    $r_ip = preg_replace("/\<a (.*?)\<\/a\>|\&nbsp;/i","",$criteria_arr['ip']);
    $r_l4 = preg_replace("/\<a (.*?)\<\/a\>|\&nbsp;/i","",$criteria_arr['layer4']);
    $report_data[] = array (_("META"),strip_tags($r_meta),"","","","","","","","","",0,0,0);
    $report_data[] = array (_("PAYLOAD"),strip_tags($r_payload),"","","","","","","","","",0,0,0);
    $report_data[] = array (_("IP"),strip_tags($r_ip),"","","","","","","","","",0,0,0);
    $report_data[] = array (_("LAYER 4"),strip_tags($r_l4),"","","","","","","","","",0,0,0);
    SaveCriteriaReportData($report_data);
?>
<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=0 WIDTH="100%">
	<TR>
		<TD style="padding-top:10px;padding-bottom:10px">
			<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=0 WIDTH="100%">
				<TR><TD height="27" align="center" style="background:url('../pixmaps/fondo_col.gif') repeat-x;border:1px solid #CACACA;color:#333333;font-size:14px;font-weight:bold">&nbsp;Current Search Criteria&nbsp;&nbsp; [<a href="base_qry_main.php?time_range=all&clear_allcriteria=1&submit=Query+DB" style="font-weight:normal;color:black">...Clear All Criteria...</a>]</TD></TR>
				<TR>
					<TD style="border:1px solid #CACACA">
						<table cellpadding=0 cellspacing=0 border=0 WIDTH="100%">
							<tr>
								<th style="border-right:1px solid #CACACA;border-bottom:1px solid #CACACA;background-color:#eeeeee"><?=_("META")?></th>
								<th style="padding-left:5px;padding-right:5px;border-right:1px solid #CACACA;border-bottom:1px solid #CACACA;background-color:#eeeeee"><?=_("PAYLOAD")?></th>
								<th style="border-right:1px solid #CACACA;border-bottom:1px solid #CACACA;background-color:#eeeeee" width="60"><?=_("IP")?></th>
								<th style="padding-left:5px;padding-right:5px;border-bottom:1px solid #CACACA;background-color:#eeeeee" nowrap><?=_("LAYER 4")?></th>
							</tr>
							<tr>
								<td align=center valign="top" style="border-right:1px solid #CACACA"><?php echo $criteria_arr['meta'] ?></td>
								<td align=center valign="top" style="border-right:1px solid #CACACA"><?php echo $criteria_arr['payload'] ?></td>
								<td align=center valign="top" style="border-right:1px solid #CACACA"><?php echo $criteria_arr['ip'] ?></td>
								<td align=center valign="top"><?php echo $criteria_arr['layer4'] ?></td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<?php
}
function SaveCriteriaReportData($data) {
    GLOBAL $db, $criteria_report_type;
    $db->baseExecute("DELETE FROM datawarehouse.report_data WHERE id_report_data_type=$criteria_report_type and user='".$_SESSION["_user"]."'");
    foreach ($data as $arr) {
        $more = "";
        foreach ($arr as $val) $more .= ",'".str_replace("'","\'",$val)."'";
        $sql = "INSERT INTO datawarehouse.report_data (id_report_data_type,user,dataV1,dataV2,dataV3,dataV4,dataV5,dataV6,dataV7,dataV8,dataV9,dataV10,dataV11,dataI1,dataI2,dataI3) VALUES ($criteria_report_type,'".$_SESSION["_user"]."'".$more.")";
        //echo $sql."<br>";
        $db->baseExecute($sql, $db);
    }
}
/********************************************************************************************/
//function QuerySignature($q, $cmd) {
//    GLOBAL $db;
//    $ids = "";
//    if (preg_match("/.* OR .*|.* AND .*/",$q)) {
//        $or_str = ($cmd == "=") ? "' OR sig_name = '" : "%' OR sig_name LIKE '%";
//        $and_str = ($cmd == "=") ? "' AND sig_name = '" : "%' AND sig_name LIKE '%";
//        $q = str_replace(" OR ",$or_str,$q);
//        $q = str_replace(" AND ",$and_str,$q);
//    }
//    $op = ($cmd == "=") ? "sig_name = '$q'" : "sig_name LIKE '%" . $q . "%'";
//    $sql = "SELECT sig_id FROM signature WHERE $op";
//    if ($result = $db->baseExecute($sql)) {
//        while ($row = $result->baseFetchRow()) $ids.= $row[0] . ",";
//    }
//    $ids = preg_replace("/\,$/", "", $ids);
//    $result->baseFreeRows();
//    if ($ids == "") $ids = "0";
//    return $ids;
//}
/********************************************************************************************/
function QueryOssimSignature($q, $cmd, $cmp) {
    GLOBAL $db;
    $ids = "";
    if (preg_match("/.* OR .*|.* AND .*/",$q)) {
        $or_str = ($cmd == "=") ? "' OR plugin_sid.name = '" : "%' OR plugin_sid.name LIKE '%";
        $and_str = ($cmd == "=") ? "' AND plugin_sid.name = '" : "%' AND plugin_sid.name LIKE '%";
        $q = str_replace(" OR ",$or_str,$q);
        $q = str_replace(" AND ",$and_str,$q);
    }
    $op = ($cmd == "=") ? "plugin_sid.name = '$q'" : "plugin_sid.name LIKE '%" . $q . "%'";
    return $op;
    /*
    $sql = "SELECT plugin_id,sid FROM ossim.plugin_sid WHERE $op";
    if ($result = $db->baseExecute($sql)) {
        while ($row = $result->baseFetchRow()) 
            if ($cmp == "!=")
                $ids.= "(plugin_id<>".$row[0]." AND plugin_sid<>".$row[1].")AND";
            else
                $ids.= "(plugin_id=".$row[0]." AND plugin_sid=".$row[1].")OR";
    }
    $ids = preg_replace("/(OR|AND)$/", "", $ids);
    $result->baseFreeRows();
    return trim($ids);*/
}
/********************************************************************************************/
function QueryOssimPluginGroup($pgid) {
    GLOBAL $db;
    $ids = "";
    $sql = "SELECT plugin_id,plugin_sid FROM ossim.plugin_group WHERE group_id=$pgid";
    if ($result = $db->baseExecute($sql)) {
        while ($row = $result->baseFetchRow()) {
            if ($row["plugin_sid"] == "0" || $row["plugin_sid"] == "ANY")
                $ids.= "(acid_event.plugin_id=".$row["plugin_id"].")OR";
            else {
                $sids = explode(",",$row["plugin_sid"]);
                foreach ($sids as $sid)
                    $ids.= "(acid_event.plugin_id=".$row["plugin_id"]." AND acid_event.plugin_sid=".$sid.")OR";
            }
        }
    }
    $ids = preg_replace("/(OR|AND)$/", "", $ids);
    $result->baseFreeRows();
    return trim($ids);
}
/********************************************************************************************/
function GetPluginListBySourceType($sourcetype) {
    GLOBAL $db;
    $ids = array(0);
    $sql = "SELECT id FROM ossim.plugin WHERE source_type='".str_replace("'","\'",$sourcetype)."'";
    if ($result = $db->baseExecute($sql)) {
        while ($row = $result->baseFetchRow())
            $ids[] = $row["id"];
    }
    $result->baseFreeRows();
    return implode(",",$ids);
}
/********************************************************************************************/
function GetPluginListByCategory($category,$byidsid=false) {
    GLOBAL $db;
    //
    $ids = ""; 
    if ($byidsid) { // plugin_id,sid list
	    $sql = "SELECT plugin_id,sid FROM ossim.plugin_sid WHERE category_id=".$category[0];
	    if ($category[1]!=0) $sql .= " and subcategory_id=".$category[1];
	    if ($result = $db->baseExecute($sql)) {
	        while ($row = $result->baseFetchRow())
	            $ids.= "(acid_event.plugin_id=".$row["plugin_id"]." AND acid_event.plugin_sid=".$row["sid"].")OR";
	    }
	    if ($ids!="")
	        $ids = " AND (".preg_replace("/(OR|AND)$/", "", $ids).")";
	    else
	        $ids = " AND (acid_event.plugin_id=0 AND acid_event.plugin_sid=0)";
	    $result->baseFreeRows();
	}
	else { // where on plugin_sid table
	    $ids = " AND plugin_sid.category_id=".$category[0];
	    if ($category[1]!=0) $ids .= " AND plugin_sid.subcategory_id=".$category[1];
	}
    return $ids;
}
/********************************************************************************************/
function ProcessCriteria() {
    GLOBAL $db, $join_sql, $where_sql, $criteria_sql, $sql, $debug_mode, $caller, $DBtype;
    /* XXX-SEC */
    GLOBAL $cs;

    /* the JOIN criteria */
    $ip_join_sql = " LEFT JOIN iphdr ON acid_event.sid=iphdr.sid AND acid_event.cid=iphdr.cid ";
    $tcp_join_sql = " LEFT JOIN tcphdr ON acid_event.sid=tcphdr.sid AND acid_event.cid=tcphdr.cid ";
    $udp_join_sql = " LEFT JOIN udphdr ON acid_event.sid=udphdr.sid AND acid_event.cid=udphdr.cid ";
    $icmp_join_sql = " LEFT JOIN icmphdr ON acid_event.sid=icmphdr.sid AND acid_event.cid=icmphdr.cid ";
    $rawip_join_sql = " LEFT JOIN iphdr ON acid_event.sid=iphdr.sid AND acid_event.cid=iphdr.cid ";
    $sig_join_sql= " LEFT JOIN ossim.plugin_sid ON acid_event.plugin_id=plugin_sid.plugin_id AND acid_event.plugin_sid=plugin_sid.sid ";
    $sig_join = false;
    //$data_join_sql = ",extra_data ";
    $data_join_sql = " LEFT JOIN extra_data ON acid_event.sid=extra_data.sid AND acid_event.cid=extra_data.cid ";
    $ag_join_sql = " LEFT JOIN acid_ag_alert ON acid_event.sid=acid_ag_alert.ag_sid AND acid_event.cid=acid_ag_alert.ag_cid ";
    //$sig_join_sql = "";
    //$sql = "SELECT SQL_CALC_FOUND_ROWS acid_event.sid, acid_event.cid, signature, timestamp, " . "acid_event.ip_src, acid_event.ip_dst, acid_event.ip_proto, " . "acid_event.ossim_type, acid_event.ossim_priority, acid_event.ossim_reliability, " . "acid_event.ossim_asset_src, acid_event.ossim_asset_dst, " . "acid_event.ossim_risk_c, acid_event.ossim_risk_a, " . "acid_event.layer4_sport, acid_event.layer4_dport FROM acid_event";
    $sql = "SELECT SQL_CALC_FOUND_ROWS acid_event.*,extra_data.userdata1,extra_data.userdata2,extra_data.userdata3,extra_data.userdata4,extra_data.userdata5,extra_data.userdata6,extra_data.userdata7,extra_data.userdata8,extra_data.userdata9,extra_data.username,extra_data.password,extra_data.filename FROM acid_event";
    // This needs to be examined!!! -- Kevin
    $where_sql = " WHERE ";
    //$where_sql = "";
    // $criteria_sql = " acid_event.sid > 0";
    // Initially show last 24hours events
    if ($_GET['time_range'] == "") $criteria_sql = " ( timestamp >='" . date("Y-m-d") . "' ) ";
    else $criteria_sql = " 1 ";
    //$criteria_sql = " ( timestamp <= CURDATE() ) ";
    //$criteria_sql = " 1 ";
    $join_sql = "";
    /* ********************** Meta Criteria ******************************************** */
    $sig = $cs->criteria['sig']->criteria;
    $sig_type = $cs->criteria['sig']->sig_type;
    $sig_class = $cs->criteria['sig_class']->criteria;
    $sig_priority = $cs->criteria['sig_priority']->criteria;
    $ag = $cs->criteria['ag']->criteria;
    $sensor = $cs->criteria['sensor']->criteria;
    $plugin = $cs->criteria['plugin']->criteria;
    $plugingroup = $cs->criteria['plugingroup']->criteria;
    $userdata = $cs->criteria['userdata']->criteria;
    $sourcetype = $cs->criteria['sourcetype']->criteria;
    $category = $cs->criteria['category']->criteria;
    $time = $cs->criteria['time']->criteria;
    $time_cnt = $cs->criteria['time']->GetFormItemCnt();
    $ip_addr = $cs->criteria['ip_addr']->criteria;
    $ip_addr_cnt = $cs->criteria['ip_addr']->GetFormItemCnt();
    $layer4 = $cs->criteria['layer4']->criteria;
    $ip_field = $cs->criteria['ip_field']->criteria;
    $ip_field_cnt = $cs->criteria['ip_field']->GetFormItemCnt();
    $tcp_port = $cs->criteria['tcp_port']->criteria;
    $tcp_port_cnt = $cs->criteria['tcp_port']->GetFormItemCnt();
    $tcp_flags = $cs->criteria['tcp_flags']->criteria;
    $tcp_field = $cs->criteria['tcp_field']->criteria;
    $tcp_field_cnt = $cs->criteria['tcp_field']->GetFormItemCnt();
    $udp_port = $cs->criteria['udp_port']->criteria;
    $udp_port_cnt = $cs->criteria['udp_port']->GetFormItemCnt();
    $udp_field = $cs->criteria['udp_field']->criteria;
    $udp_field_cnt = $cs->criteria['udp_field']->GetFormItemCnt();
    $icmp_field = $cs->criteria['icmp_field']->criteria;
    $icmp_field_cnt = $cs->criteria['icmp_field']->GetFormItemCnt();
    $rawip_field = $cs->criteria['rawip_field']->criteria;
    $rawip_field_cnt = $cs->criteria['rawip_field']->GetFormItemCnt();
    $data = $cs->criteria['data']->criteria;
    $data_cnt = $cs->criteria['data']->GetFormItemCnt();
    $cs->criteria['data']->data_encode; //$data_encode[0] = "ascii"; $data_encode[1] = "hex";
    /* OSSIM */
    $ossim_type = $cs->criteria['ossim_type']->criteria;
    $ossim_priority = $cs->criteria['ossim_priority']->criteria;
    $ossim_reliability = $cs->criteria['ossim_reliability']->criteria;
    $ossim_asset_dst = $cs->criteria['ossim_asset_dst']->criteria;
    $ossim_risk_a = $cs->criteria['ossim_risk_a']->criteria;
    $tmp_meta = "";
    /* Sensor */
    if ($sensor != "" && $sensor != " ") $tmp_meta = $tmp_meta . " AND acid_event.sid in (" . $sensor . ")";
    else {
		$cs->criteria['sensor']->Set("");
		// Filter by user perms if no criteria
		if (Session::allowedSensors() != "") {
			$user_sensors = explode(",",Session::allowedSensors());
			$snortsensors = GetSensorSids($db);
			$sensor_str = "";
			foreach ($user_sensors as $user_sensor)
				if (count($snortsensors[$user_sensor]) > 0) $sensor_str .= ($sensor_str != "") ? ",".implode(",",$snortsensors[$user_sensor]) : implode(",",$snortsensors[$user_sensor]);
			if ($sensor_str == "") $sensor_str = "0";
			$tmp_meta .= " AND acid_event.sid in (" . $sensor_str . ")";
		}
	}
    /* Plugin */
    if ($plugin != "" && $plugin != " ") $tmp_meta = $tmp_meta . " AND acid_event.plugin_id in (" . $plugin . ")";
    /* Plugin Group */    
    if ($plugingroup != "" && $plugingroup != " ") {
        $pg_ids = QueryOssimPluginGroup($plugingroup);
        if ($pg_ids != "")
            $tmp_meta = $tmp_meta . " AND ($pg_ids) ";
        else
            $tmp_meta = $tmp_meta." AND (acid_event.plugin_id=-1 AND acid_event.plugin_sid=-1)";
    }
    /* User Data */
    //print_r($_SESSION);
    //echo "User Data:$userdata";
    if ($userdata != "") {
    	$data_join_sql = ",extra_data ";
    	$tmp_meta .= " AND acid_event.sid=extra_data.sid AND acid_event.cid=extra_data.cid AND (extra_data.userdata1 LIKE \"%$userdata%\" OR extra_data.userdata2 LIKE \"%$userdata%\" OR extra_data.userdata3 LIKE \"%$userdata%\" OR extra_data.userdata4 LIKE \"%$userdata%\" OR extra_data.userdata5 LIKE \"%$userdata%\" OR extra_data.userdata6 LIKE \"%$userdata%\" OR extra_data.userdata7 LIKE \"%$userdata%\" OR extra_data.userdata8 LIKE \"%$userdata%\" OR extra_data.userdata9 LIKE \"%$userdata%\")";
    }
    /* Source Type */
    if (trim($sourcetype) != "") $tmp_meta = $tmp_meta . " AND acid_event.plugin_id in (" . GetPluginListBySourceType($sourcetype) . ")";
    /* Category */
    if ($category[0] != 0) {
    	$sig_join = true;
    	$tmp_meta = $tmp_meta . GetPluginListByCategory($category);
    }
    /* Alert Group */
    if ($ag != "" && $ag != " ") {
        $tmp_meta = $tmp_meta . " AND ag_id =" . $ag;
        $join_sql = $join_sql . $ag_join_sql;
    } else $cs->criteria['ag']->Set("");
    /* Signature */
    if ((isset($sig[0]) && $sig[0] != " " && $sig[0] != "") && (isset($sig[1]) && $sig[1] != "")) {
        if ($sig_type==1) { // sending sig[1]=plugin_id;plugin_sid
            $pidsid = preg_split("/[\s;]+/",$sig[1]);
            $tmp_meta = $tmp_meta." AND (acid_event.plugin_id=".intval($pidsid[0])." AND acid_event.plugin_sid=".intval($pidsid[1]).")";
        } else { // free string
            $sig_ids = QueryOssimSignature($sig[1], $sig[0], $sig[2]);
            $sig_join = true;
            $tmp_meta = $tmp_meta . " AND ($sig_ids)";
            //if ($sig_ids != "")
            //  $tmp_meta = $tmp_meta . " AND ($sig_ids) ";
            //else
            //  $tmp_meta = $tmp_meta." AND (plugin_id=-1 AND plugin_sid=-1)";
        }
    } else $cs->criteria['sig']->Set("");
    
    /* Signature Classification
    if ($sig_class != " " && $sig_class != "" && $sig_class != "0") {
        $tmp_meta = $tmp_meta . " AND sig_class_id = '" . $sig_class . "'";
    } else if ($sig_class == "0") {
        $tmp_meta = $tmp_meta . " AND (sig_class_id is null OR sig_class_id = '0')";
    } else $cs->criteria['sig_class']->Set(""); */
    
    /* Signature Priority 
    if ($sig_priority[1] != " " && $sig_priority[1] != "" && $sig_priority[1] != "0") {
        $tmp_meta = $tmp_meta . " AND sig_priority " . $sig_priority[0] . " '" . $sig_priority[1] . "'";
    } else if ($sig_priority[1] == "0") {
        $tmp_meta = $tmp_meta . " AND (sig_priority is null OR sig_priority = '0')";
    } else $cs->criteria['sig_priority']->Set("");*/
    
    /* Date/Time
    if ( DateTimeRows2sql($time, $time_cnt, $tmp_meta) == 0 )
    $cs->criteria['time']->SetFormItemCnt(0); */
    /*
    * OSSIM Code
    */
    /* OSSIM Type */
    if ($ossim_type[1] != " " && $ossim_type[1] != "" && $ossim_type[1] != "0") {
        $tmp_meta = $tmp_meta . " AND acid_event.ossim_type = '" . $ossim_type[1] . "'";
    } else if ($ossim_type[1] == "0") {
        $tmp_meta = $tmp_meta . " AND (acid_event.ossim_type is null OR acid_event.ossim_type = '0')";
    } else $cs->criteria['ossim_type']->Set("");
    /* OSSIM Priority */
    if ($ossim_priority[1] != " " && $ossim_priority[1] != "" && $ossim_priority[1] != "0") {
        $tmp_meta = $tmp_meta . " AND acid_event.ossim_priority  " . $ossim_priority[0] . " '" . $ossim_priority[1] . "'";
    } else if ($ossim_priority[1] == "0") {
        $tmp_meta = $tmp_meta . " AND (acid_event.ossim_priority is null OR acid_event.ossim_priority = '0')";
    } else $cs->criteria['ossim_priority']->Set("");
    /* OSSIM Reliability */
    if ($ossim_reliability[1] != " " && $ossim_reliability[1] != "" && $ossim_reliability[1] != "0") {
        $tmp_meta = $tmp_meta . " AND acid_event.ossim_reliability " . $ossim_reliability[0] . " '" . $ossim_reliability[1] . "'";
    } else if ($ossim_reliability[1] == "0") {
        $tmp_meta = $tmp_meta . " AND (acid_event.ossim_reliability is null OR acid_event.ossim_reliability = '0')";
    } else $cs->criteria['ossim_reliability']->Set("");
    /* OSSIM Asset DST */
    if ($ossim_asset_dst[1] != " " && $ossim_asset_dst[1] != "" && $ossim_asset_dst[1] != "0") {
        $tmp_meta = $tmp_meta . " AND acid_event.ossim_asset_dst " . $ossim_asset_dst[0] . " '" . $ossim_asset_dst[1] . "'";
    } else if ($ossim_asset_dst[1] == "0") {
        $tmp_meta = $tmp_meta . " AND (acid_event.ossim_asset_dst is null OR acid_event.ossim_asset_dst = '0')";
    } else $cs->criteria['ossim_asset_dst']->Set("");
    /* OSSIM Risk A */
    if ($ossim_risk_a != " " && $ossim_risk_a != "" && $ossim_risk_a != "0") {
        if ($ossim_risk_a == "low") {
            //$tmp_meta = $tmp_meta." AND ossim_risk_a >= 1 AND ossim_risk_a <= 4 ";
            $tmp_meta = $tmp_meta . " AND acid_event.ossim_risk_a < 1 ";
        } else if ($ossim_risk_a == "medium") {
            //$tmp_meta = $tmp_meta." AND ossim_risk_a >= 5 AND ossim_risk_a <= 7 ";
            $tmp_meta = $tmp_meta . " AND acid_event.ossim_risk_a = 1 ";
        } else if ($ossim_risk_a == "high") {
            //$tmp_meta = $tmp_meta." AND ossim_risk_a >= 8 AND ossim_risk_a <= 10 ";
            $tmp_meta = $tmp_meta . " AND acid_event.ossim_risk_a > 1 ";
        }
    } else $cs->criteria['ossim_risk_a']->Set("");
    /* Date/Time */
    if (DateTimeRows2sql($time, $time_cnt, $tmp_meta) == 0) $cs->criteria['time']->SetFormItemCnt(0);
    $criteria_sql = $criteria_sql . $tmp_meta;
	
    /* ********************** IP Criteria ********************************************** */
    /* IP Addresses */
    $tmp2 = "";
    for ($i = 0; $i < $ip_addr_cnt; $i++) {
        $tmp = "";
        if (isset($ip_addr[$i][3]) && $ip_addr[$i][1] != " ") {
            if (($ip_addr[$i][3] != "") && ($ip_addr[$i][4] != "") && ($ip_addr[$i][5] != "") && ($ip_addr[$i][6] != "")) {
                /* if use illegal 256.256.256.256 address then
                *  this is the special case where need to search for portscans
                */
                if (($ip_addr[$i][3] == "256") && ($ip_addr[$i][4] == "256") && ($ip_addr[$i][5] == "256") && ($ip_addr[$i][6] == "256")) {
                    $tmp = $tmp . " acid_event." . $ip_addr[$i][1] . " IS NULL" . " ";
                } else {
                    if ($ip_addr[$i][10] == "") {
                        $tmp = $tmp . " acid_event." . $ip_addr[$i][1] . $ip_addr[$i][2] . "'" . baseIP2long($ip_addr[$i][3] . "." . $ip_addr[$i][4] . "." . $ip_addr[$i][5] . "." . $ip_addr[$i][6]) . "' ";
                    } else {
                        $mask = getIPMask($ip_addr[$i][3] . "." . $ip_addr[$i][4] . "." . $ip_addr[$i][5] . "." . $ip_addr[$i][6], $ip_addr[$i][10]);
                        if ($ip_addr[$i][2] == "!=") $tmp_op = " NOT ";
                        else $tmp_op = "";
                        $tmp = $tmp . $tmp_op . " (acid_event." . $ip_addr[$i][1] . ">= '" . baseIP2long($mask[0]) . "' AND " . "acid_event." . $ip_addr[$i][1] . "<= '" . baseIP2long($mask[1]) . "')";
                    }
                }
            }
            /* if have chosen the address type to be both source and destination */
            if (ereg("ip_both", $tmp)) {
                $tmp_src = ereg_replace("ip_both", "ip_src", $tmp);
                $tmp_dst = ereg_replace("ip_both", "ip_dst", $tmp);
                if ($ip_addr[$i][2] == '=') $tmp = "(" . $tmp_src . ') OR (' . $tmp_dst . ')';
                else $tmp = "(" . $tmp_src . ') AND (' . $tmp_dst . ')';
            }
            if ($tmp != "") $tmp = $ip_addr[$i][0] . "(" . $tmp . ")" . $ip_addr[$i][8] . $ip_addr[$i][9];
        } else if ((isset($ip_addr[$i][3]) && $ip_addr[$i][3] != "") || $ip_addr[$i][1] != " ") {
            /* IP_addr_type, but MALFORMED IP address */
            if ($ip_addr[$i][1] != " " && $ip_addr[$i][3] == "" && ($ip_addr[$i][4] != "" || $ip_addr[$i][5] != "" || $ip_addr[$i][6] != "")) ErrorMessage("<B>" . _QCERRCRITWARN . "</B> " . _QCERRINVIPCRIT . " ' *." . $ip_addr[$i][4] . "." . $ip_addr[$i][5] . "." . $ip_addr[$i][6] . " '");
            /* ADDRESS, but NO IP_addr_type was given */
            if (isset($ip_addr[$i][3]) && $ip_addr[$i][1] == " ") ErrorMessage("<B>" . _QCERRCRITWARN . "</B> " . _QCERRIP . " '" . $ip_addr[$i][3] . "." . $ip_addr[$i][4] . "." . $ip_addr[$i][5] . "." . $ip_addr[$i][6] . "' " . _QCERRCRITADDRESSTYPE);
            /* IP_addr_type IS FILLED, but no ADDRESS */
            if (($ip_addr[$i][1] != " " && $ip_addr[$i][1] != "") && $ip_addr[$i][3] == "") ErrorMessage("<B>" . _QCERRCRITWARN . "</B> " . _QCERRIPTYPE . " '" . $ip_addr[$i][1] . "' " . _QCERRCRITIPADDRESSNONE1 . $i . ") " . _QCERRCRITIPADDRESSNONE);
        }
        $tmp2 = $tmp2 . $tmp;
        if (($i > 0 && $ip_addr[$i - 1][9] == ' ' && $ip_addr[$i - 1][3] != "")) ErrorMessage("<B>" . _QCERRCRITWARN . "</B> " . _QCERRCRITIPIPBOOL . " #$i and #" . ($i + 1) . ".");
    }
    if ($tmp2 != "") $criteria_sql = $criteria_sql . " AND ( " . $tmp2 . " )";
    else $cs->criteria['ip_addr']->SetFormItemCnt(0);
    /* IP Fields */
    if (FieldRows2sql($ip_field, $ip_field_cnt, $criteria_sql) == 0) $cs->criteria['ip_field']->SetFormItemCnt(0);
    /* Layer-4 encapsulation */
    if ($layer4 == "TCP") $criteria_sql = $criteria_sql . " AND acid_event.ip_proto= '6'";
    else if ($layer4 == "UDP") $criteria_sql = $criteria_sql . " AND acid_event.ip_proto= '17'";
    else if ($layer4 == "ICMP") $criteria_sql = $criteria_sql . " AND acid_event.ip_proto= '1'";
    else if ($layer4 == "RawIP") $criteria_sql = $criteria_sql . " AND acid_event.ip_proto= '255'";
    else $cs->criteria['layer4']->Set("");
    /* Join the iphdr table if necessary */
    if (!$cs->criteria['ip_field']->isEmpty()) $join_sql = $ip_join_sql . $join_sql;
    /* ********************** TCP Criteria ********************************************** */
    if ($layer4 == "TCP") {
        $proto_tmp = "";
        /* TCP Ports */
        if (FieldRows2sql($tcp_port, $tcp_port_cnt, $proto_tmp) == 0) $cs->criteria['tcp_port']->SetFormItemCnt(0);
        $criteria_sql = $criteria_sql . $proto_tmp;
        $proto_tmp = "";
        /* TCP Flags */
        if (isset($tcp_flags) && sizeof($tcp_flags) == 8) {
            if ($tcp_flags[0] == "contains" || $tcp_flags[0] == "is") {
                $flag_tmp = $tcp_flags[1] + $tcp_flags[2] + $tcp_flags[3] + $tcp_flags[4] + $tcp_flags[5] + $tcp_flags[6] + $tcp_flags[7] + $tcp_flags[8];
                if ($tcp_flags[0] == "is") $proto_tmp = $proto_tmp . ' AND tcp_flags=' . $flag_tmp;
                else if ($tcp_flags[0] == "contains") $proto_tmp = $proto_tmp . ' AND (tcp_flags & ' . $flag_tmp . ' = ' . $flag_tmp . " )";
                else $proto_tmp = "";
            }
        }
        /* TCP Fields */
        if (FieldRows2sql($tcp_field, $tcp_field_cnt, $proto_tmp) == 0) $cs->criteria['tcp_field']->SetFormItemCnt(0);
        /* TCP Options
        *  - not implemented
        */
        if (!$cs->criteria['tcp_port']->isEmpty() || !$cs->criteria['tcp_flags']->isEmpty() || !$cs->criteria['tcp_field']->isEmpty()) {
            $criteria_sql = $criteria_sql . $proto_tmp;
            if (!$cs->criteria['tcp_flags']->isEmpty() || !$cs->criteria['tcp_field']->isEmpty()) $join_sql = $tcp_join_sql . $join_sql;
        }
    }
    /* ********************** UDP Criteria ********************************************* */
    if ($layer4 == "UDP") {
        $proto_tmp = "";
        /* UDP Ports */
        if (FieldRows2sql($udp_port, $udp_port_cnt, $proto_tmp) == 0) $cs->criteria['udp_port']->SetFormItemCnt(0);
        $criteria_sql = $criteria_sql . $proto_tmp;
        $proto_tmp = "";
        /* UDP Fields */
        if (FieldRows2sql($udp_field, $udp_field_cnt, $proto_tmp) == 0) $cs->criteria['udp_field']->SetFormItemCnt(0);
        if (!$cs->criteria['udp_port']->isEmpty() || !$cs->criteria['udp_field']->isEmpty()) {
            $criteria_sql = $criteria_sql . $proto_tmp;
            if (!$cs->criteria['udp_field']->isEmpty()) $join_sql = $udp_join_sql . $join_sql;
        }
    }
    /* ********************** ICMP Criteria ******************************************** */
    if ($layer4 == "ICMP") {
        $proto_tmp = "";
        /* ICMP Fields */
        if (FieldRows2sql($icmp_field, $icmp_field_cnt, $proto_tmp) == 0) $cs->criteria['icmp_field']->SetFormItemCnt(0);
        if (!$cs->criteria['icmp_field']->isEmpty()) {
            $criteria_sql = $criteria_sql . $proto_tmp;
            $join_sql = $icmp_join_sql . $join_sql;
        }
    }
    /* ********************** Packet Scan Criteria ************************************* */
    if ($layer4 == "RawIP") {
        $proto_tmp = "";
        /* RawIP Fields */
        if (FieldRows2sql($rawip_field, $rawip_field_cnt, $proto_tmp) == 0) $cs->criteria['rawip_field']->SetFormItemCnt(0);
        if (!$cs->criteria['rawip_field']->isEmpty()) {
            $criteria_sql = $criteria_sql . $proto_tmp;
            $join_sql = $rawip_join_sql . $join_sql;
        }
    }
    /* ********************** Payload Criteria ***************************************** */
    //$tmp_payload = "";
    if (DataRows2sql($data, $data_cnt, $data_encode, $tmp_payload) == 0) $cs->criteria['data']->SetFormItemCnt(0);
	//echo "<br><br><br>";
	//print_r($data);
	//print_r("data_cnt: [".$data_cnt."]");
	//print_r($cs->criteria['data']->isEmpty());
	//print_r("criteria_ sql: [".$criteria_sql."]");
	//print_r("tmp_payload: [".$tmp_payload."]");
    if (!$cs->criteria['data']->isEmpty()) {
        $criteria_sql = $criteria_sql . $tmp_payload;
    }
    if ($sig_join) $join_sql = $join_sql . $sig_join_sql;
    $join_sql = $join_sql . $data_join_sql;
    $csql[0] = $join_sql;
    $csql[1] = $criteria_sql;
    //print_r($csql);
    return $csql;
}
?>
