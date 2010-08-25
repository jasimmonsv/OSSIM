<?php
/**
* Class and Function List:
* Function list:
* - PrintPortscanEvents()
* - PrintEventsByIP()
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
$start = time();
$sig = array();
include ("base_conf.php");
require_once ('ossim_conf.inc');
require_once ('ossim_db.inc');
require_once ('classes/Sensor.inc');
include ("$BASE_path/includes/base_constants.inc.php");
include ("$BASE_path/includes/base_include.inc.php");
include_once ("$BASE_path/base_db_common.php");
include_once ("$BASE_path/base_common.php");
$et = new EventTiming($debug_time_mode);
$cs = new CriteriaState("base_stat_ipaddr.php");
$cs->ReadState();
$ip = ImportHTTPVar("ip", VAR_DIGIT | VAR_PERIOD);
$netmask = ImportHTTPVar("netmask", VAR_DIGIT);
$action = ImportHTTPVar("action", VAR_ALPHA);
$submit = ImportHTTPVar("submit", VAR_ALPHA | VAR_SPACE);
// Check role out and redirect if needed -- Kevin
$roleneeded = 10000;
$BUser = new BaseUser();
if (($BUser->hasRole($roleneeded) == 0) && ($Use_Auth_System == 1)) base_header("Location: " . $BASE_urlpath . "/index.php");
$page_title = $ip . '/' . $netmask;
PrintBASESubHeader($page_title, $page_title, $cs->GetBackLink() , 1);
function PrintPortscanEvents($db, $ip) {
    GLOBAL $portscan_file;
    if (!$portscan_file) {
        ErrorMessage(_PSEVENTERR . _PSEVENTERRNOFILE);
        return;
    }
    $fp = fopen($portscan_file, "r");
    if (!$fp) {
        ErrorMessage(_PSEVENTERR . _PSEVENTERROPENFILE . " '" . $portscan_file . "'");
        return;
    }
    echo '<TABLE border="0" width="100%" cellspacing="0" cellpadding="5">
        <TR>
           <TD CLASS="headerbasestat">' . _PSDATETIME . '</TD>
           <TD CLASS="headerbasestat">' . _PSSRCIP . '</TD>
           <TD CLASS="headerbasestat">' . _PSSRCPORT . '</TD>
           <TD CLASS="headerbasestat">' . _PSDSTIP . '</TD>
           <TD CLASS="headerbasestat">' . _PSDSTPORT . '</TD>
           <TD CLASS="headerbasestat">' . _PSTCPFLAGS . '</TD>
        </TR>';
    $total = 0;
    while (!feof($fp)) {
        $contents = fgets($fp, 255);
        if (ereg($ip, $contents)) {
            $total++;
            if ($i % 2 == 0) {
                $color = "DDDDDD";
            } else {
                $color = "FFFFFF";
            }
            $contents = ereg_replace("  ", " ", $contents);
            $elements = explode(" ", $contents);
            echo '<tr bgcolor="' . $color . '"><td align="center">' . $elements[0] . ' ' . $elements[1] . ' ' . $elements[2] . '</td>';
            ereg("([0-9]*\.[0-9]*\.[0-9]*\.[0-9]*):([0-9]*)", $elements[3], $store);
            echo '<td align="center">' . $store[1] . '</td>';
            echo '<td align="center">' . $store[2] . '</td>';
            ereg("([0-9]*\.[0-9]*\.[0-9]*\.[0-9]*):([0-9]*)", $elements[5], $store);
            echo '<td align="center">' . $store[1] . '</td>';
            echo '<td align="center">' . $store[2] . '</td>';
            echo '<td align="center">' . $elements[7] . '</td></tr>';
        }
    }
    fclose($fp);
    echo '<TR>
         <TD CLASS="headerbasestat" align="left">' . _PSTOTALHOSTS . '</TD>
         <TD CLASS="headerbasestat">' . $total . '</TD>
         <TD CLASS="headerbasestat" colspan="4">&nbsp;</TD>
        </TR>
        </TABLE>';
}
function PrintEventsByIP($db, $ip) {
    GLOBAL $debug_mode;
    $count = 0;
    /* Jeffs stuff */
    /* Count total events for the given address */
    $event_cnt = EventCntByAddr($db, $ip);
    /* Grab unique alerts and count them */
    $unique_events = UniqueEventCntByAddr($db, $ip, $count);
    $unique_event_cnt = count($unique_events);
    printf("<B>" . _PSDETECTAMONG . "/32</B><BR>", $unique_event_cnt, $event_cnt, $ip);
    /* Print the Statistics on Each of the Unique Alerts */
    echo '<TABLE BORDER=0>
        <TR>
           <TD CLASS="headerbasestat">' . _PSTCPFLAGS . '</TD>
           <TD CLASS="headerbasestat">' . _PSTOTALOCC . '</TD>
           <TD CLASS="headerbasestat">' . _PSNUMSENSORS . '</TD>
           <TD CLASS="headerbasestat">' . _PSFIRSTOCC . '</TD>
           <TD CLASS="headerbasestat">' . _PSLASTOCC . '</TD>
        </TR>';
    for ($i = 0; $i < $unique_event_cnt; $i++) {
        $current_event = $unique_events[$i];
        $total = UniqueEventTotalsByAddr($db, $ip, $current_event);
        $num_sensors = UniqueSensorCntByAddr($db, $ip, $current_event);
        $start_time = StartTimeForUniqueEventByAddr($db, $ip, $current_event);
        $stop_time = StopTimeForUniqueEventByAddr($db, $ip, $current_event);
        $cellcolor = ($i % 2 != 0) ? "bgcolor='#f2f2f2'" : "";
        /* Print out */
        echo "<TR $cellcolor>";
        if ($debug_mode > 1) {
            SQLTraceLog(__FILE__ . ":" . __LINE__ . ":" . __FUNCTION__ . ": Before BuildSigByID()");
        }
        $signame = BuildSigByPlugin($unique_events[$i][0], $unique_events[$i][1], $db);
        echo "  <TD ALIGN='center'> " . str_replace("##","",html_entity_decode($signame));
        if ($debug_mode > 1) {
            SQLTraceLog(__FILE__ . ":" . __LINE__ . ":" . __FUNCTION__ . ": After BuildSigByID()");
        }
        $tmp_iplookup = 'base_qry_main.php?new=1&sig_type=1&sig%5B0%5D=%3D&sig%5B1%5D=' . urlencode($unique_events[$i][0].";".$unique_events[$i][1]) . '&num_result_rows=-1&submit=' . _QUERYDBP . '&current_view=-1&ip_addr_cnt=2' . BuildIPFormVars($ip);
        $tmp_sensor_lookup = 'base_stat_sensor.php?sig_type=1&sig%5B0%5D=%3D&sig%5B1%5D=' . urlencode($unique_events[$i][0].";".$unique_events[$i][1]) . '&ip_addr_cnt=2' . BuildIPFormVars($ip);
        echo "  <TD align='center'> <A HREF=\"$tmp_iplookup\">$total</A> ";
        echo "  <TD align='center'> <A HREF=\"$tmp_sensor_lookup\">$num_sensors</A> ";
        //echo "  <TD align='center'> $num_sensors";
        echo "  <TD align='center'> $start_time";
        echo "  <TD align='center' valign='middle'> $stop_time";
        echo '</TR>';
    }
    echo "</TABLE>\n";
}
/* Connect to the Alert database */
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);
if ($event_cache_auto_update == 1) UpdateAlertCache($db);
if (sizeof($sig) != 0 && strstr($sig[1], "spp_portscan")) $sig[1] = "";
/*  Build new link for criteria-based sensor page
*                    -- ALS <aschroll@mitre.org>
*/
$tmp_sensor_lookup = 'base_stat_sensor.php?ip_addr_cnt=2' . BuildIPFormVars($ip);
$tmp_srcdst_iplookup = 'base_qry_main.php?new=2' . '&amp;num_result_rows=-1' . '&amp;submit=' . _QUERYDBP . '&amp;current_view=-1&amp;ip_addr_cnt=2' . BuildIPFormVars($ip);
$tmp_src_iplookup = 'base_qry_main.php?new=2' . '&amp;num_result_rows=-1' . '&amp;submit=' . _QUERYDBP . '&amp;current_view=-1&amp;ip_addr_cnt=1' . BuildSrcIPFormVars($ip);
$tmp_dst_iplookup = 'base_qry_main.php?new=2' . '&amp;num_result_rows=-1' . '&amp;submit=' . _QUERYDBP . '&amp;current_view=-1&amp;ip_addr_cnt=1' . BuildDstIPFormVars($ip);
echo '<CENTER>';
echo '<table border=0 cellpadding=0 cellspacing=0 width="90%">';
echo '<tr height=\'20px\'><td>&nbsp;</td><td>&nbsp;</td></tr>';
echo '<tr style="background-color:#F2F2F2;"><td align=\'right\'>';
printf("<FONT>" . _PSALLALERTSAS . ":</FONT>", $ip, $netmask);
echo '</td>';
echo '<td align=\'left\' style=\'padding-left:15px;\'>
 <A HREF="' . $tmp_src_iplookup . '">' . _SCSOURCE . '</A> | 
 <A HREF="' . $tmp_dst_iplookup . '">' . _SCDEST . '</A> | 
 <A HREF="' . $tmp_srcdst_iplookup . '">' . _SCSOURCE . '/' . _SCDEST . '</A><BR></td></tr>';
 
echo '<tr><td align=\'right\'>';
echo _PSSHOW . ':</td><td align=\'left\' style=\'padding-left:15px;\'>
       <A HREF="base_stat_ipaddr.php?ip=' . $ip . '&amp;netmask=' . $netmask . '&amp;action=events">' . _PSUNIALERTS . '</A>
       &nbsp; | &nbsp;
       <A HREF="base_stat_ipaddr.php?ip=' . $ip . '&amp;netmask=' . $netmask . '&amp;action=portscan">' . _PSPORTSCANEVE . '</A>
       <BR></td></tr>';

echo "<tr style=\"background-color:#F2F2F2;\"><td style=\"text-align:right;\">";
echo '<FONT>' . _PSREGWHOIS . ': </td><td align=\'left\' style=\'padding-left:15px;\'>';
echo '
       <A HREF="http://ws.arin.net/cgi-bin/whois.pl?queryinput=' . $ip . '" target="_NEW">ARIN</A> |
       <A HREF="http://www.ripe.net/perl/whois?query=' . $ip . '" target="_NEW">RIPE</A> | 
       <A HREF="http://www.apnic.net/apnic-bin/whois.pl?search=' . $ip . '" target="_NEW">APNIC</A> | 
       <A HREF="http://lacnic.net/cgi-bin/lacnic/whois?lg=EN&amp;qr=' . $ip . '" target="_NEW">LACNIC</A><BR></FONT></td></tr>';
$octet = preg_split("/\./", $ip);
$classc = sprintf("%03s.%03s.%03s", $octet[0], $octet[1], $octet[2]);

echo '<tr><td align=\'right\'><FONT>' . _PSEXTERNAL . ': </td><td align=\'left\' style=\'padding-left:15px;\'>' . '<A HREF="' . $external_dns_link . $ip . '" target="_NEW">DNS</A>';
echo ' | <A HREF="' . $external_whois_link . $ip . '" target="_NEW">whois</A> | ' . '<A HREF="' . $external_all_link . $ip . '" target="_NEW">Extended whois</A>';
echo ' | <A HREF="http://www.dshield.org/ipinfo.php?ip=' . $ip . '&amp;Submit=Submit" target="_NEW">DShield.org IP Info</A>';
echo ' | <A HREF="http://www.trustedsource.org/query.php?q=' . $ip . '" target="_NEW">TrustedSource.org IP Info</A>';
//echo ' | <A HREF="http://www.openrbl.org/?i=' . $ip . '" target="_NEW">Openrbl.org IP Info</A>';
echo ' | <A HREF="http://www.spamhaus.org/query/bl?ip=' . $ip . '" target="_NEW">Spamhaus.org IP Info</A>';
echo ' | <A HREF="http://www.spamcop.net/w3m?action=checkblock&ip=' . $ip . '" target="_NEW">Spamcop.net IP Info</A>';
echo ' | <A HREF="http://www.senderbase.org/senderbase_queries/detailip?search_string=' . $ip . '" target="_NEW">Senderbase.org IP Info</A>';
echo ' | <A HREF="http://isc.sans.org/ipinfo.html?ip=' . $ip . '" target="_NEW">ISC Source/Subnet Report</A>';
echo ' | <A HREF="http://www.mywot.com/en/scorecard/' . $ip . '" target="_NEW">WOT Security Scorecard</A>';
echo ' | <A HREF="http://www.malwareurl.com/ns_listing.php?ip=' . $ip . '" target="_NEW">MalwareURL</A>';
echo ' | <A HREF="http://www.google.com/search?q=' . $ip . '" target="_NEW">Google</A>';
echo '<BR> </FONT></td></tr></table>';
?>
</CENTER>
<HR>

<FORM METHOD="POST" ACTION="base_stat_ipaddr.php">

<?php
if ($debug_mode == 1) echo '<TABLE BORDER=1>
             <TR><TD>action</TD><TD>submit</TD><TD>ip</TD><TD>netmask</TD></TR>
             <TR><TD>' . $action . '</TD><TD>' . $submit . '</TD>
                 <TD>' . $ip . '</TD><TD>' . $netmask . '</TD></TR>
           </TABLE>';
/* Print the Statistics the IP address */
$db_object = new ossim_db();
if (is_array($_SESSION["server"]) && $_SESSION["server"][0]!="")
	$conn_object = $db_object->custom_connect($_SESSION["server"][0],$_SESSION["server"][2],$_SESSION["server"][3]);
else
	$conn_object = $db_object->connect();
//$conn_object = $db_object->connect();
echo '<CENTER><B>' . $ip . '</B> ( '
?> 
  <a href="<?php
echo Sensor::get_sensor_link($conn_object, $ip) . "/$ip.html" ?>">See host Detail</a>
  <?php
$db_object->close($conn_object);
echo ') <BR>FQDN: <B>';
if ($resolve_IP == 0) echo '  (' . _PSNODNS . ')';
else {
    if ($ip != "255.255.255.255") echo baseGetHostByAddr($ip, $db, $dns_cache_lifetime);
    else echo $ip . ' (Broadcast)';
}
if (VerifySocketSupport()) echo '&nbsp;&nbsp;( <A HREF="base_stat_ipaddr.php?ip=' . $ip . '&amp;netmask=' . $netmask . '&amp;action=whois">local whois</A> )';
echo '</B>
        <TABLE BORDER=0>
        <TR>
           <TD CLASS="headerbasestat">' . _PSNUMSENSORSBR . '</TD>
           <TD CLASS="headerbasestat">' . _PSOCCASSRC . '</TD>
           <TD CLASS="headerbasestat">' . _PSOCCASDST . '</TD>
           <TD CLASS="headerbasestat">' . _PSFIRSTOCC . '</TD>
           <TD CLASS="headerbasestat">' . _PSLASTOCC . '</TD>
        </TR>';
$ip_src32 = baseIP2long($ip);
$ip_dst32 = $ip_src32;
/* Number of Sensors, First, and Last timestamp */
$temp = "SELECT COUNT(DISTINCT sid), MIN(timestamp), MAX(timestamp) FROM acid_event " . "WHERE (ip_src = '$ip_src32' OR ip_dst = '$ip_dst32' )";
$result2 = $db->baseExecute($temp);
$row2 = $result2->baseFetchRow();
$num_sensors = $row2[0];
$start_time = $row2[1];
$stop_time = $row2[2];
$result2->baseFreeRows();
/* Unique instances as Source Address  */
$temp = "SELECT COUNT(sid) from acid_event WHERE ip_src='$ip_src32'";
$result2 = $db->baseExecute($temp);
$row2 = $result2->baseFetchRow();
$num_src_ip = $row2[0];
$result2->baseFreeRows();
/* Unique instances Dest. Address  */
$temp = "SELECT COUNT(sid) from acid_event WHERE ip_dst='$ip_dst32'";
$result2 = $db->baseExecute($temp);
$row2 = $result2->baseFetchRow();
$num_dst_ip = $row2[0];
$result2->baseFreeRows();
/* Print out */
echo '<TR>
         <TD ALIGN="center" bgcolor="#F2F2F2"><A HREF="' . $tmp_sensor_lookup . '">' . $num_sensors . '</A>';
if ($num_src_ip == 0) echo '<TD ALIGN="center" bgcolor="#F2F2F2">' . $num_src_ip;
else echo '<TD ALIGN="center" bgcolor="#F2F2F2"><A HREF="' . $tmp_src_iplookup . '">' . $num_src_ip . '</A>';
if ($num_dst_ip == 0) echo '<TD ALIGN="center" bgcolor="#F2F2F2">' . $num_dst_ip;
else echo '<TD ALIGN="center" bgcolor="#F2F2F2"><A HREF="' . $tmp_dst_iplookup . '">' . $num_dst_ip . '</A>';
echo '
         <TD align="center" bgcolor="#F2F2F2">' . $start_time . '
         <TD align="center" bgcolor="#F2F2F2" valign="middle">' . $stop_time . '
       </TR>
      </TABLE></CENTER>';
if ($action == "events") {
    echo '<HR>
            <CENTER><P>';
    PrintEventsByIP($db, $ip);
    echo ' </CENTER>';
} else if ($action == "whois") {
    echo "\n<B>" . _PSWHOISINFO . "</B>" . "<PRE>" . baseGetWhois($ip, $db, $whois_cache_lifetime) . "</PRE>";
} else if ($action == "portscan") {
    echo '<HR>
            <CENTER><P>';
    PrintPortscanEvents($db, $ip);
    echo ' </CENTER>';
}
echo "\n</FORM>\n";
PrintBASESubFooter();
$et->PrintTiming();
echo "</body>\r\n</html>";
?>
