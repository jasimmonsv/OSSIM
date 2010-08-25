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
* Classes list:
*/
require_once ('classes/Security.inc');
$sid = GET('sid');
$cid = GET('cid');
ossim_valid($sid, OSS_DIGIT, 'illegal:' . _("sid"));
ossim_valid($cid, OSS_DIGIT, 'illegal:' . _("cid"));
if (ossim_error()) {
	die(ossim_error());
}
$pcapfile = "/var/tmp/base_packet_" . $sid . "-" . $cid . ".pcap";
$pdmlfile = "/var/tmp/base_packet_" . $sid . "-" . $cid . ".pdml";
// TSAHRK: show packet in web page
$cmd = "tshark -V -r '$pcapfile' -T pdml > '$pdmlfile'";
//echo $cmd;
system($cmd);
/*
echo "<pre>";
$tshark_lines = file ("/var/tmp/base_packet_".$sid."-".$cid.".pdml");

foreach ($tshark_lines as $line) {
echo "linea: ".$line."<br>";
}

echo "</pre>";
*/
//echo "<h2>pcap File:</h2><ul>\n";
//echo $pdmlfile;

?>
<ul style="display:none"><li id="key1" data="isFolder:true, icon:'../../images/any.png'">
<?php
if (file_exists($pdmlfile) && filesize($pdmlfile) > 0) {
    $i = 1;
    $xml = simplexml_load_file($pdmlfile);
    foreach($xml->packet->proto as $key => $xml_entry) {
        $atr_tit = $xml_entry->attributes();
        if ($atr_tit['name'] != "eth") {
            if ($atr_tit['name'] == "geninfo") $img = "information.png";
            elseif ($atr_tit['name'] == "tcp" || $atr_tit['name'] == "udp") $img = "proto.png";
            elseif ($atr_tit['name'] == "ip") $img = "flow_chart.png";
            elseif ($atr_tit['name'] == "frame") $img = "wrench.png";
            elseif ($atr_tit['name'] == "eth") $img = "eth.png";
            else $img = "host_os.png";
            echo "<li id=\"key1.$i\"  data=\"isFolder:true, icon:'../../images/$img'\"><b>" . strtoupper($atr_tit['name']) . "</b>\n<ul>\n";
            $j = 1;
            foreach($xml_entry as $key2 => $xml_entry2) {
                $k = 1;
                $atr = $xml_entry2->attributes();
                $showname = ($atr_tit['name'] == "geninfo") ? $atr['showname'] . ": <b>" . $atr['show'] . "</b>" : preg_replace("/(.*?):(.*)/", "\\1: <b>\\2</b>", $atr['showname']);
                echo "<li id=\"key1.$i.$j\" data=\"isFolder:true, icon:'../../images/host.png'\">" . $showname . "\n";
                echo "<ul>";
                foreach($atr as $key3 => $value) {
                    if ($key3 == "showname") continue;
                    echo "<li id=\"key1.$i.$j.$k\" data=\"isFolder:false, icon:'../../images/host.png'\">" . $key3 . ": <b>" . $value . "</b>\n";
                    $k++;
                }
                echo "</ul>\n";
                $j++;
            }
            echo "</ul>\n";
            $i++;
        }
    }
    echo "</ul>";
}
// Clean temp files
if (file_exists($pcapfile)) unlink($pcapfile);
if (file_exists($pdmlfile)) unlink($pdmlfile);
?>
</ul>
