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

require_once('ossim_conf.inc');
require_once ('classes/Session.inc');
Session::logcheck("MenuEvents", "EventsForensics");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
  <title> <?php echo gettext("Matching Snort rule"); ?> </title>
<!--  <meta http-equiv="refresh" content="3"> -->
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body style="font-size:14px" lefmargin="10" topmargin="10">
<?php
// get params
//
$plugin_sid=GET('sid');
ossim_valid($plugin_sid, OSS_DIGIT, 'illegal:' . _("plugin_sid"));
if (ossim_error()) {
    die(ossim_error());
}
$result = exec("grep -n 'sid:$plugin_sid;' /etc/snort/rules/*.rules");
// format: /etc/snort/rules/ddos.rules:53:alert tcp $EXTERNAL_NET any -> $HOME_NET 15104 (msg:"DDOS mstream client to handler"; flow:stateless; flags:S,12; reference:arachnids,111; reference:cve,2000-0138; classtype:attempted-dos; sid:249; rev:8;)
preg_match("/(.*?):\d+:(.*?) \((.*?)\)/",$result,$found);
if (trim($result)=="" || count($found)<=1) {
	echo "<br><center>"._("No rules found for sid")." <b>$plugin_sid</b></center><br>\n";
} else {
	$file = basename($found[1]);
	echo "<b>File:</b> $file<br>\n";
	$rule = $found[2];
	echo "<b>Rule:</b> $rule<br>\n";
	$more = explode(";",$found[3]);
	foreach ($more as $dat) {
		$val = explode(":",$dat);
		if ($val[0]!="") echo "&nbsp;&nbsp;&nbsp;&nbsp;<b>".trim($val[0]).":</b> ".$val[1]."<br>\n";
	}
}
?>
</body>
</html>