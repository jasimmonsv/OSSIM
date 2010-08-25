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
* - finddirective()
* - rule_table_header()
* - rule_table_foot()
* - rule_table()
* Classes list:
*/
require_once ('classes/Session.inc');
Session::logcheck("MenuIntelligence", "CorrelationDirectives");
?>

<?php
$XML_FILE = '/etc/ossim/server/directives.xml';
?>

<html>
<head>
  <title> <?php
echo gettext("Directive Editor"); ?> </title>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" href="../style/style.css"/>
  <link rel="stylesheet" href="../style/directives.css"/>
</head>

<script language="JavaScript1.5" type="text/javascript">
<!--

function Menus(Objet)
{
        VarUL=document.getElementById(Objet);
	if(VarUL.className=="menucache") {
	    VarUL.className="menuaffiche";
	} else {
	    VarUL.className="menucache";
	}
}
//-->
</SCRIPT>
											

<body>
<h1 align="center"> <?php
echo gettext("Directive Editor"); ?> </h1>

<?php
if (version_compare(PHP_VERSION, '5', '>=') && extension_loaded('xsl')) {
    require_once ('domxml-php4-to-php5.php');
}
require_once ('classes/Plugin.inc');
require_once ('classes/Plugin_sid.inc');
require_once ('ossim_db.inc');
require_once ('classes/Security.inc');
$directive_id = GET('directive');
$level = GET('level');
ossim_valid($directive_id, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("directive_id"));
ossim_valid($level, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("level"));
if (ossim_error()) {
    die(ossim_error());
}
$db = new ossim_db();
$conn = $db->connect();
function finddirective($dom, $directive_id) {
    foreach($dom->get_elements_by_tagname('directive') as $directive) {
        $id = $directive->get_attribute('id');
        $name = $directive->get_attribute('name');
        if (!strcmp($id, $directive_id)) return $directive;
    }
    return NULL;
}
function rule_table_header($directive_id, $level, $directive_priority) {
?>
    <!-- rule table -->
    <table align="center">
      <tr><th colspan=<?php
    echo $level + 12; ?>>
        <?php
    echo gettext("Directive"); ?> <?php
    echo $directive_id ?> (
        <?php
    echo gettext("Priority"); ?>: <?php
    echo $directive_priority ?> )</th></tr>
      <tr>
        <td colspan=<?php
    echo $level; ?>></td>
        <th> <?php
    echo gettext("Name"); ?> </th>
        <th> <?php
    echo gettext("Reliability"); ?> </th>
        <th> <?php
    echo gettext("Time_out"); ?> </th>
        <th> <?php
    echo gettext("Occurrence"); ?> </th>
        <th> <?php
    echo gettext("From"); ?> </th>
        <th> <?php
    echo gettext("To"); ?> </th>
        <th> <?php
    echo gettext("Port_from"); ?> </th>
        <th> <?php
    echo gettext("Port_to"); ?> </th>
        <th> <?php
    echo gettext("Sensor"); ?> </th>
        <th> <?php
    echo gettext("Plugin ID"); ?> </th>
        <th> <?php
    echo gettext("Plugin SID"); ?> </th>
      </tr>
<?php
}
function rule_table_foot() {
?>
    </table>
    <br/>
    <!-- end main table: directives -->
<?php
}
function rule_table($dom, $directive_id, $directive, $level, $ilevel) {
    global $conn;
    if ($directive->has_child_nodes()) {
        $rules = $directive->child_nodes();
        $branch = 0;
        foreach($rules as $rule) {
            if (($rule->type == XML_ELEMENT_NODE) && ($rule->tagname() == 'rule')) {
                if ($ilevel != $level) {
                    $indent = "<td colspan=" . ($ilevel - $level) . ">";
                } else {
                    $indent = '';
                }
                if ($level == 1) { ?>
      <tr><?php
                    echo $indent;
                } elseif ($level == 2) { ?>
      <tr bgcolor="#CCCCCC"><?php
                    echo $indent;
                } elseif ($level == 3) { ?>
      <tr bgcolor="#999999"><?php
                    echo $indent;
                } elseif ($level == 4) { ?>
      <tr bgcolor="#9999CC"><?php
                    echo $indent;
                } elseif ($level == 5) { ?>
      <tr bgcolor="#6699CC"><?php
                    echo $indent;
                } ?>
      
        <!-- expand -->
        <td class="left" colspan=<?php
                echo $level; ?>>
    <?php
                if (($level == 1) && ($rule->has_child_nodes())) {
?>
            <a href="<?php
                    echo $_SERVER["SCRIPT_NAME"] ?>?directive=<?php
                    echo $directive_id
?>&level=<?php
                    echo $ilevel + 1 ?>"><?php
                    echo "+"
?></a>
    <?php
                } elseif ($rule->has_child_nodes()) { ?>
            <a href="<?php
                    echo $_SERVER["SCRIPT_NAME"] ?>?directive=<?php
                    echo $directive_id
?>&level=<?php
                    echo $ilevel - $level + 1 ?>"><?php
                    echo '-'
?></a>
    <?php
                } ?>
        </td>
        <!-- end expand -->
        
        <td><?php
                echo $rule->get_attribute('name'); ?></td>
        <td><?php
                echo $rule->get_attribute('reliability'); ?>&nbsp;</td>
        <td><?php
                echo $rule->get_attribute('time_out'); ?>&nbsp;</td>
        <td><?php
                echo $rule->get_attribute('occurrence'); ?>&nbsp;</td>
        <td><?php
                echo $rule->get_attribute('from'); ?>&nbsp;</td>
        <td><?php
                echo $rule->get_attribute('to'); ?>&nbsp;</td>
        <td><?php
                echo $rule->get_attribute('port_from'); ?>&nbsp;</td>
        <td><?php
                echo $rule->get_attribute('port_to'); ?>&nbsp;</td>
        <td><?php
                echo $rule->get_attribute('sensor'); ?>&nbsp;</td>
        <td>
<?php
                $plugin_id = $rule->get_attribute('plugin_id');
                if ($plugin_list = Plugin::get_list($conn, "WHERE id = $plugin_id")) {
                    $name = $plugin_list[0]->get_name();
                    echo "<a href=\"../conf/pluginsid.php?id=$plugin_id&" . "name=$name\">$name</a> ($plugin_id)";
                }
?>
        </td>
        <td> 
<?php
                $plugin_sid = $rule->get_attribute('plugin_sid');
                $plugin_sid_list = split(',', $plugin_sid);
                if (count($plugin_sid_list) > 30) {
?>
        <a style="cursor:hand;" TITLE="To view or hide the list of plugin sid click here." onclick="Menus('plugsid')"> <?php
                    echo gettext("Expand / Collapse"); ?> </a>
        <div id="plugsid" class="menucache">
<?php
                }
                foreach($plugin_sid_list as $sid_negate) {
                    $sid = $sid_negate;
                    if (!strncmp($sid_negate, "!", 1)) $sid = substr($sid_negate, 1);
                    /* sid == ANY */
                    if (!strcmp($sid, "ANY")) {
                        echo gettext("ANY");
                    }
                    /* sid == X:PLUGIN_SID */
                    elseif (strpos($sid, "PLUGIN_SID")) {
                        echo gettext("$sid");
                    }
                    /* get name of plugin_sid */
                    elseif ($plugin_list = Plugin_sid::get_list($conn, "WHERE plugin_id = $plugin_id AND sid = $sid")) {
                        $name = $plugin_list[0]->get_name();
                        echo "<a title=\"$name\">$sid_negate</a>&nbsp; ";
                    }
                }
                if (count($plugin_sid_list) > 30) {
?>
         </div>
<?php
                }
?>
	</td>
      </tr>
                
<?php
                if ($level > 1) {
                    if ($rule->has_child_nodes()) {
                        $rules = $rule->child_nodes();
                        foreach($rules as $rule) {
                            rule_table($dom, $directive_id, $rule, $level - 1, $ilevel);
                        }
                    }
                }
                $branch++;
            }
        } /* foreach */
    }
}
/* create dom object from a XML file */
if (!$dom = @domxml_open_file($XML_FILE, DOMXML_LOAD_SUBSTITUTE_ENTITIES)) {
    echo "Error while parsing the document\n";
    exit;
}
if (!empty($directive_id)) {
    $directive = finddirective($dom, $directive_id);
    if ($directive_id && !is_null($directive)) {
        if (empty($level)) $level = 1;
        $_SESSION["path"] = 0;
        rule_table_header($directive_id, $level, $directive->get_attribute('priority'));
        rule_table($dom, $directive_id, $directive, $level, $level);
        rule_table_foot();
    }
    $db->close($conn);
?>
  </table>

<?php
} else {
?>
<?php
    echo gettext("Click on the left side to view a directive"); ?>.<br/>
<?php
    echo gettext("Click on the categories of directives to expand or collapse them"); ?>.

<hr/><h2 style="text-align: left;"><?php
    echo gettext("Directive numbering"); ?></h2>

<table>
<tr><th> <?php
    echo gettext("Category"); ?> 
<th> <?php
    echo gettext("Numbers"); ?> 
<tr><td> <?php
    echo gettext("Generic ossim"); ?> <td>1-2999
<tr><td> <?php
    echo gettext("Attack correlation"); ?> <td>3000-5999
<tr><td> <?php
    echo gettext("Virus and Worms"); ?> <td>6000-8999
<tr><td> <?php
    echo gettext("Web attack correlation"); ?> <td>9000-11999
<tr><td> <?php
    echo gettext("DoS"); ?> <td>12000-14999
<tr><td> <?php
    echo gettext("Portscan/scan"); ?> <td>15000-17999
<tr><td> <?php
    echo gettext("Behaviour anomalies"); ?> <td>18000-20999
<tr><td> <?php
    echo gettext("Network abuse and error"); ?> <td>21000-23999
<tr><td> <?php
    echo gettext("Trojans"); ?> <td>24000-26999
<tr><td> <?php
    echo gettext("Miscellaneous"); ?> <td>27000-34999
<tr><td> <?php
    echo gettext("User contributed"); ?> <td>500000+
</table>

<hr/><h2 style="text-align: left;"><?php
    echo gettext("Element of a directive"); ?></h2>

<h3 style="text-align: left;">Type</h3>
<?php
    echo gettext("What type of rule is this. There are two possible types as of today"); ?> :
<ol>
<li> <?php
    echo gettext("Detector"); ?> <br/>
<?php
    echo gettext("Detector rules are those received automatically from the agent as they are recorded. This includes snort, spade, apache, etc"); ?> ...
<li> <?php
    echo gettext("Monitor"); ?> <br/>
<?php
    echo gettext("Monitor rules must be queried by the server ntop data and ntop sessions"); ?> .
</ol>
<h3 style="text-align: left;">Name</h3>
<?php
    echo gettext("The  rule name shown within the event database when the level is matched"); ?> .<br/>
<?php
    echo gettext("Accepts: UTF-8 compliant string"); ?> .
<h3 style="text-align: left;">Priority</h3>
<?php
    echo gettext("When we talk about priority we're talking about threat. It's the importance of the isolated attack. It has nothing to do with your equipment or environment, it only measures the relative importance of the attack"); ?> .<br/>
<?php
    echo gettext("This will become clear using a couple of examples"); ?> .
<ol>
<li> <?php
    echo gettext("Your unix server running samba gets attacked by the sasser worm"); ?> .<br/>
<?php
    echo gettext("The attack") . " "; ?> 
<i> <?php
    echo gettext("per se") . " "; ?></i>
<?php
    echo gettext("is dangerous, it has compromised thousands of hosts and is very easy to accomplish. But. does it really matter to you? Surely not, but it's a big security hole so it'll have a high priority"); ?> .
<li> <?php
    echo gettext("You're running a CVS server on an isolated network that is only accessible by your friends and has only access to the outside. Some new exploit tested by one of your friends hits it"); ?> .<br/>
<?php
    echo gettext("Again, the attack is dangerous, it could compromise your machine but surely your host is patched against that particular attack and you don't mind being a test-platform for one of your friends"); ?> .
</ol>
<?php
    echo gettext("Default value"); ?> : 1.
<h3 style="text-align: left;"> Reliability </h3>
<?php
    echo gettext("When talking about classic risk-assessment this would be called") . " "; ?> &quot;
<?php
    echo gettext("probability") . " "; ?> &quot;. 
<?php
    echo gettext("Since it's quite difficult to determine how probable it is that our network being attacked through one or another vulnerability, we'll transform this term into something more IDS related: reliability"); ?> .<br/>
<?php
    echo gettext("Surely many of you have seen unreliable signatures on every available NIDS. A host pinging a non-live destination is able to rise hundreds of thousands spade events a day. Snort's recent http-inspect functionality for example, although good implemented needs some heavy tweaking in order to be reliable or you'll get thousands of false positives a day"); ?> .<br/>
<?php
    echo gettext("Coming back to our worm example. If a hosts connects to 5 different hosts on their own subnet using port 445, that could be a normal behaviour. Unreliable for IDS purposes. What happens if they connect to 15 hosts? We're starting to get suspicious. And what if they contact 500 different hosts in less than an hour? That's strange and the attack is getting more and more reliable"); ?> .<br/>
<?php
    echo gettext("Each rule has it's own reliability, determining how reliable this particular rule is within the whole attack chain"); ?> .<br/>
<?php
    echo gettext("Accepts: 0-10. Can be specified as absolute value (i.e. 7) or relative (i.e. +2 means two more than the previous level)"); ?> .<br/>
<?php
    echo gettext("Default value"); ?> : 1.
<h3 style="text-align: left;"> Ocurrence </h3>
<?php
    echo gettext("How many times we have to match a unique") . " "; ?>
&quot;from, to, port_from, port_to, plugin_id &amp; plugin_sid&quot; <?php
    echo " " . gettext("in order to advance one correlation level"); ?> .
<h3 style="text-align: left;">Time_out</h3>
<?php
    echo gettext("We wait a fixed amount of seconds until a rule expires and the directives lifetime is over"); ?> .
<h3 style="text-align: left;">From</h3>
<?php
    echo gettext("Source IP. There are various possible values for this field"); ?> :
<ol>
<li>ANY<br/>
<?php
    echo gettext("Just that, any ip address would match"); ?> .<br/>
<li> <?php
    echo gettext("Dotted numerical Ipv4"); ?> (x.x.x.x)<br/>
<?php
    echo gettext("Self explaining"); ?> .<br/>
<li> <?php
    echo gettext("Comma separated Ipv4 addresses without netmask"); ?> .<br/>
<?php
    echo gettext("You can use any number of ip addresses separated by commas"); ?> .<br/>
<li> <?php
    echo gettext("Using a network name"); ?> .<br/>
<?php
    echo gettext("You can use any network name defined via web"); ?> .<br/>
<li> <?php
    echo gettext("Relative"); ?> .<br/>
<?php
    echo gettext("This is used to reference ip addresses from previous levels. This should be easier to understand using examples"); ?> <br/>
1:SRC_IP <?php
    echo gettext("means use the source ip referenced within the previous rule"); ?> .<br/>
2:DST_IP <?php
    echo gettext("means use the destination ip referenced two rules below as source address"); ?> .<br/>
<li> <?php
    echo gettext("Negated"); ?> .<br/>
<?php
    echo gettext("You can also use negated elements. I.e."); ?> :<br/>
&quot;!192.168.2.203,INTERNAL_NETWORK&quot;.<br/>
<?php
    echo gettext("If ") . " "; ?> INTERNAL_NETWORK == 192.168.2.0/24 
<?php
    echo " " . gettext("this would match the whole class C except"); ?> 192.168.2.203.
<li>HOME_NET<br/>
<?php
    echo gettext("This var refers to all the defined networks in database"); ?> .<br>
</ol>
<h3 style="text-align: left;">To</h3>
<?php
    echo gettext("Destination IP. There are various possible values for this field"); ?> :
<ol>
<li>ANY<br/>
<?php
    echo gettext("Just that, any ip address would match"); ?> .<br/>
<li> <?php
    echo gettext("Dotted numerical Ipv4"); ?> (x.x.x.x)<br/>
<?php
    echo gettext("Self explaining"); ?> .<br/>
<li> <?php
    echo gettext("Comma separated Ipv4 addresses without netmask"); ?> .<br/>
<?php
    echo gettext("You can use any number of ip addresses separated by commas"); ?> .<br/>
<li> <?php
    echo gettext("Using a network name"); ?> .<br/>
<?php
    echo gettext("You can use any network name defined via web"); ?> .<br/>
<li> <?php
    echo gettext("Relative"); ?> .<br/>
<?php
    echo gettext("This is used to reference ip addresses from previous levels. This should be easier to understand using examples"); ?> <br/>
1:SRC_IP <?php
    echo gettext("means use the source ip referenced within the previous rule"); ?> .<br/>
2:DST_IP <?php
    echo gettext("means use the destination ip referenced two rules below as source address"); ?> .<br/>
<li> <?php
    echo gettext("Negated"); ?> .<br/>
<?php
    echo gettext("You can also use negated elements. I.e."); ?> :<br/>
&quot;!192.168.2.203,INTERNAL_NETWORK&quot;.<br/>
<?php
    echo gettext("If") . " "; ?> INTERNAL_NETWORK == 192.168.2.0/24 
<?php
    echo " " . gettext("this would match the whole class C except") . " "; ?> 192.168.2.203.
<li>HOME_NET<br/>
<?php
    echo gettext("This var refers to all the defined networks in database"); ?> .<br>
</ol>
<?php
    echo gettext("The") . " "; ?> &quot;To&quot; <?php
    echo " " . gettext("field is the field used when referencing monitor data that has no source"); ?> .<br/>
<?php
    echo gettext("Both \"From\" and \"To\" fields should accept input from the database in the near future. Host and Network objects are on the TODO list."); ?>
<h3 style="text-align: left;">Sensor</h3>
<?php
    echo gettext("Sensor IP. There are various possible values for this field"); ?> :
<ol>
<li>ANY<br/>
<?php
    echo gettext("Just that, any ip address would match"); ?> .<br/>
<li> <?php
    echo gettext("Dotted numerical Ipv4"); ?> (x.x.x.x)<br/>
<?php
    echo gettext("Self explaining"); ?> .<br/>
<li> <?php
    echo gettext("Comma separated Ipv4 addresses without netmask"); ?> .<br/>
<?php
    echo gettext("You can use any number of ip addresses separated by commas"); ?> .<br/>
<li> <?php
    echo gettext("Using a sensor name"); ?> .<br/>
<?php
    echo gettext("You can use any sensor name defined via web"); ?> .<br/>
<li> <?php
    echo gettext("Relative"); ?> .<br/>
<?php
    echo gettext("This is used to reference sensor ip addresses from previous levels. This should be easier to understand using examples"); ?> <br/>
1:SENSOR <?php
    echo gettext("means use the sensor ip referenced within the previous rule"); ?> .<br/>
<li> <?php
    echo gettext("Negated"); ?> .<br/>
<?php
    echo gettext("You can also use negated elements. I.e."); ?> :<br/>
&quot;!192.168.2.203&quot;.<br/>
</ol>

<h3 style="text-align: left;">Port_from / Port_to</h3>
<?php
    echo gettext("This can be a port number or a sequence of comma separated port numbers. ANY port can also be used"); ?>.<br/>
<?php
    echo gettext("Hint: 1:DST_PORT or 1:SRC_PORT would mean level 1 src and dest port respectively. They can be used too. (level 2 would be 2:DST_PORT for example)"); ?>.
<br> <br>
<?php
    echo gettext("Also you can negate ports. This will negate ports 22 and 21 in the directive"); ?>:
<br><br>
port="!22,25,110,!21"
<br>
<?php
    echo gettext("You can use a port range, or negated port range (wich will negate all the ports inside the range)"); ?>:<br>
port="1-5,!6-10"<br><br>


<h3 style="text-align: left;">Protocol</h3>
<?php
    echo gettext("This can be one of the following strings"); ?>:<br><br>
<li> TCP
<li> UDP
<li> ICMP
<li> Host_ARP_Event
<li> Host_OS_Event
<li> Host_Service_Event
<li> Host_IDS_Event
<li> Information_Event
<br><br>
<li> <?php
    echo gettext("Additionally, you can put just a number with the protocol"); ?>.
<br><br>
<?php
    echo gettext("Although Host_ARP_Event, Host_OS_Event, etc, are not really a protocol, you can use them if you want to do directives with ARP, OS, IDS or Service events. You can also use relative referencing like in 1:TCP, 2:Host_ARP_Event, etc.."); ?>.
<br><br>
<?php
    echo gettext("You can negate the protocol also like this"); ?>: 
protocol="!Host_ARP_Event,UDP,!ICMP"
<?php
    echo gettext("This will negate Host_ARP_Event and ICMP, but will match with UDP"); ?>.
<br/>


<h3 style="text-align: left;">Plugin_id</h3>
<?php
    echo gettext("The numerical id assigned to the referenced plugin"); ?>.
<h3 style="text-align: left;">Plugin_sid</h3>
<?php
    echo gettext("The nummerical sub-id assigned to each plugins events, functions or the like"); ?>.<br/>
<?php
    echo gettext("For example, plugin id 1001 (snort) references it.s rules as normal plugin_sids"); ?>.<br/>
<?php
    echo gettext("Plugin id 1501 (apache) uses the response codes as plugin_sid"); ?> (200 OK, 404 NOT FOUND, ...)<br/>
<?php
    echo gettext("ANY can be used too for plugin_sid"); ?>.
<br><br><?php
    echo gettext("You can negate plugin_sid's: plugin_sid=\"1,2,3,!4\" will negate just the plugin_sid 4"); ?>.

<h3 style="text-align: left;">Condition</h3>
<?php
    echo gettext("This parameter and the following three are only valid for \"monitor\" and certain \"detector\" type rules"); ?>.<br/>
<?php
    echo gettext("The logical condition that has to be met for the rule to match"); ?>:
<ol>
<li>eq - <?php
    echo gettext("Equal"); ?>
<li>ne - <?php
    echo gettext("Not equal"); ?>
<li>lt - <?php
    echo gettext("Less than"); ?>
<li>gt - <?php
    echo gettext("Greater than"); ?>
<li>le - <?php
    echo gettext("Less or equal"); ?>
<li>ge - <?php
    echo gettext("Greater or equal"); ?>
</ol>
<h3 style="text-align: left;">Value</h3>
<?php
    echo gettext("The value that has to be matched using the previous directives"); ?>.
<h3 style="text-align: left;">Interval</h3>
<?php
    echo gettext("This value is similar to time_out but used for \"monitor\" type rules"); ?>.
<h3 style="text-align: left;">Absolute</h3>
<?php
    echo gettext("Determines if the provided value is absolute or relative"); ?>.<br/>
<?php
    echo gettext("For example, providing 1000 as a value, gt as condition and 60 (seconds) as interval, querying ntop for HttpSentBytes would mean"); ?>:<br/>
<ul>
<li><?php
    echo gettext("Absolute true: Match if the host has more than 1000 http sent bytes within the next 60 seconds. Report back when (and only if) this absolute value is reached"); ?>.
<li><?php
    echo gettext("Absolute false: Match if the host shows an increase of 1000 http sent bytes within the next 60 seconds. Report back as soon as this difference is reached (if it was reached...)"); ?>
</ul>
<h3 style="text-align: left;">Sticky</h3>
<?php
    echo gettext("A bit more difficult to explain. Take the worm rule. At the end we want to match 20000 connections involving the same source host and same destination port but we want to avoid 20000 directives from spawning so this is our little helper. Just set this to true or false depending on how you want the system to behave. If it's true, all the vars that aren't ANY or fixed (fixed means defined source or dest host, port or plugin id or sid.) are going to be made sticky so they won't spawn another directive"); ?>.<br/>
<?php
    echo gettext("In our example at level 2 there are two vars that are going to be fixed at correlation level 2: 1:SRC_IP and 1:DST_PORT. Of course plugin_id is already fixed (1104 == spade) and all the other ANY vars are still going to be ANY"); ?>.
<h3 style="text-align: left;">Sticky_different</h3>
<?php
    echo gettext("Only suitable for rules with more than one occurrence. We want to make sure that the specified parameter happens X times (occurrence) and that all the occurrences are different"); ?>.<br/>
<?php
    echo gettext("Take one example. A straight-ahead port-scanning rule. Fix destination with the previous sticky and set sticky_different=\"DST_PORT\". This will assure we're going to match \"X occurrences\" against the same hosts having X different destination ports"); ?>.<br/>
<?php
    echo gettext("In our worm rule the most important var is the DST_IP because as the number increases the reliability increases as well. Which (normally operating) host is going to do thousands of connections for the same port against different hosts. Please also remember to define the referenced variable (in this example, it is: to='ANY')"); ?>??<br/>
<h3 style="text-align: left;">Groups</h3>
<?php
    echo gettext("As sticky but involving more than one directive. If an event matches against a directive defined within a group and the group is set as \"sticky\" it won't match any other directive"); ?>.
<br><br>
<h3 style="text-align: left;">Username, password, filename, userdata1, userdata2, userdata3, userdata4, userdata5, userdata6, userdata7, userdata8, userdata9</h3>
<?php
    echo gettext("This keywords are optional. They can be used to store special data from agents. Obviously, this only will work if the event has this modificators. The following things are accpeted"); ?>:<br>
<?php
    echo gettext("You can insert any string to match here. If you want that this matches with any keyword, you can skip these keywords, or use ANY as the value"); ?>. <br/>
<ol>
<li> ANY <br> <?php
    echo gettext("Just that, this will match with any word. You can also avoid this keyword, and it will match too"); ?>.
<li> <?php
    echo gettext("Comma separated list"); ?><br> 
<?php
    echo gettext("You can use any number of words separated by commas"); ?>
<li> <?php
    echo gettext("Relative value"); ?><br> 
<?php
    echo gettext("This is used to reference keywords from previous levels, for example"); ?>:<br>
1:FILENAME -> <?php
    echo gettext("Means use the filename referenced in the first rule level"); ?><br>
2:USERDATA5 -> <?php
    echo gettext("Means use some data from USERDATA5 keyword referenced in the second rule level"); ?>
<li> <?php
    echo gettext("Negated: You can also use negated keywords, i.e"); ?>: <br>
"!johndoe,foobar".<br>
<?php
    echo gettext("This will match with foobar, but not johndoe"); ?>
</ol>
<?php
    echo gettext("Here you can see an example of what can be done"); ?>: <br>

username="one,two,three,!four4444,five" filename="1:FILENAME,/etc/password,!/etc/shadow" userdata5="el cocherito lere me dijo anoche lere,!2:USERDATA5"
<br><br>
NOTE: There are some kind of events that stores by default some of that fields:<br>
<li>  Arpwatch events:&nbsp;&nbsp;&nbsp; Userdata1 = MAC
<li>  Pads events:&nbsp;&nbsp;&nbsp; Userdata1 = application ; Userdata2 = service
<li>  P0f Events:&nbsp;&nbsp;&nbsp; Userdata1 = O.S.<br>
<li>  Syslog Events:&nbsp;&nbsp;&nbsp; Username = dest username ; Userdata1 = src username ; Userdata2 = src user uid ; Userdata3 = service<br>

<br>
<hr/><h2 style="text-align: left;">Risk</h2>
<?php
    echo gettext("The main formula for risk calculation would look like this"); ?>:<br/>

Risk = (<?php
    echo gettext("Asset") . " * " . gettext("Priority") . " * " . gettext("Reliability"); ?>) / 25<br/>
<?php
    echo gettext("Where"); ?>:<ul>
<li><?php
    echo gettext("Asset"); ?> (0-5).
<li><?php
    echo gettext("Priority"); ?> (0-5).
<li><?php
    echo gettext("Reliability"); ?> (0-10).
</ul>
<?php
}
?>
<br/>
</body>
</html>
