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

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Content-type: text/xml");

require_once 'classes/Session.inc';
require_once 'ossim_conf.inc';
require_once 'ossim_db.inc';
require_once 'classes/Security.inc';
require_once 'classes/Host_os.inc';
require_once 'classes/Ossec.inc';
require_once 'conf/_conf.php';

function clean_tags($tag, $string)
{
	if ( preg_match("/<\s*$tag\s*>(.*)<\/\s*$tag\s*>/", $string, $match ) )
		return $match[1];
	else
		return false;
}


echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>";

$page  		= POST('page');
$rp    		= POST('rp');
$field      = POST('qtype');
$search     = GET('query');
$search     = ( empty($search) ) ? POST('query') : $search;

if ( !empty($search) && !empty($field) ) 
{
	if ( strtolower($field) == "ip" )
		$search = "WHERE ip like '%$search%' OR hostname like '%$search%'";
	else
		$search = "WHERE $field like '%$search%'";
}



$sortname	= ( !empty($_POST['sortname'])  ) ? POST('sortname')  : GET('sortname');
$sortname   = ( $sortname == "ip" ) ? "INET_ATON(ip)" : $sortname;

$sortorder  = ( !empty($_POST['sortorder']) ) ? POST('sortorder') : GET('sortorder');

$page  		= ( empty($page) )  ? 1  : $page;
$rp    		= ( empty($rp) )    ? 25 : $rp;


$sortname	= ( !empty($sortname)  ) ? $sortname  : "hostname,status";
$sortorder	= ( !empty($sortorder) && strtolower($sortorder) == "desc" ) ? "DESC" : "ASC";

$order= $sortname." ".$sortorder;


ossim_valid($sortname,  "(,)", OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("Order Name"));
ossim_valid($sortorder, "(,)", OSS_LETTER, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("Sort Order"));
ossim_valid($field, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("Field"));
ossim_valid($page, OSS_DIGIT, 'illegal:' . _("Page"));
ossim_valid($rp, OSS_DIGIT, 'illegal:' . _("Rp"));

if (ossim_error()) {
    die(ossim_error());
}

	
	
$start  = ( ($page - 1) * $rp );
$limit  = "LIMIT $start, $rp";
$db 	= new ossim_db();
$conn   = $db->connect();
$error  = true;

$ossec_list     = Agentless::get_list_ossec($conn);

$agentless_list = Agentless::get_list($conn, "");

foreach ($ossec_list as $k => $v)
{
	if ( !is_object($agentless_list[$k]) )
	{
		Agentless::add_host_data($conn, $v->get_ip(), $v->get_hostname(), $v->get_user(), $v->get_pass(), $v->get_ppass(), null, $v->get_status());
		
		$agentless_entry_ossec[$v->get_user()."@".$v->get_ip()] = null;
	}
}


if ( !empty ($agentless_entry_ossec))
{
	$conf_file = file_get_contents($ossec_conf);
	$pattern   = '/[\r?\n]+\s*/';
	$conf_file = preg_replace($pattern, "\n", $conf_file);
	$conf_file = explode("\n", trim($conf_file));
		
		
	$i 	     = 0;
	$size_cf = count($conf_file);
					
			
	while ($i<count($conf_file))
	{
		if ( preg_match("/<\s*agentless\s*>/", $conf_file[$i], $match) )
		{
			$entry = null;
						
			for ($j=$i+1; $j<$size_cf; $j++)
			{
				$entry[] = $conf_file[$j];
								
				if ( preg_match("/<\/\s*agentless\s*>/", $conf_file[$j], $match) )
				{
					$i      = $j++;
					array_pop($entry);
					sort($entry);
					
					if ( count($entry) == 5 )
					{
					
						$host_tag = clean_tags("host", $entry[2]);
																	
						if ( array_key_exists($host_tag, $agentless_entry_ossec) )
						{
							$host        = explode("@", $host_tag);
							$ip          = $host[1];
							$arguments   = clean_tags("arguments", $entry[0]);
							$frequency   = clean_tags("frequency", $entry[1]);
							$state  	 = clean_tags("state", $entry[3]);
							$type        = clean_tags("type", $entry[4]);
														
							Agentless::add_monitoring_entry($conn, $ip, $type, $frequency, $state, $arguments);
						}
					}
					
					$entry = null;
					$i     = $j++;
					
					break;
				}
			}
		}
		else
			$i++;
	}

}


$agentless_list = null;

$extra = ( !empty($search) ) ? $search." ORDER BY $order $limit" : "ORDER BY $order $limit";

$agentless_list = Agentless::get_list_pag($conn, $extra);


if ( !empty($agentless_list) )
{
    $key   = array_keys($agentless_list);
	$total = $agentless_list[$key[0]]->get_foundrows();
    if ($total == 0) 
		$total = count($agentless_list);
} 
else 
	$total = 0;
	

$xml.= "<rows>\n";
$xml.= "<page>$page</page>\n";
$xml.= "<total>$total</total>\n";


foreach($agentless_list as $host)
{
    $ip   		= $host->get_ip();
	$hostname 	= "<a style='font-weight:bold;' href='./al_modifyform.php?ip=".urlencode($ip)."'>" .$host->get_hostname() . "</a>" . Host_os::get_os_pixmap($conn, $ip);
	$user 		= $host->get_user();
	
	if ( $host->get_status() == 0 )
		$status = "<img src='../pixmaps/tables/cross.png' alt='"._("Disabled")."' title='"._("Disabled")."'/>";
	else if ( $host->get_status() == 1 )
		$status = "<img src='../pixmaps/tables/tick.png' alt='"._("Enabled")."' title='"._("Enabled")."'/>";
	else
		$status = "<img src='../pixmaps/tables/exclamation.png' alt='"._("Not configured")."' title='"._("Not configured")."'/>";
		
	$desc 		= ( $host->get_descr() == '' ) ? "&nbsp;" :  Util::htmlentities($host->get_descr());
  
  
    $xml.= "<row id='$ip'>";
		$xml.= "<cell><![CDATA[" .  $hostname  . "]]></cell>";
		$xml.= "<cell><![CDATA[" .  $ip        . "]]></cell>";
		$xml.= "<cell><![CDATA[" .  $user      . "]]></cell>";
		$xml.= "<cell><![CDATA[" .  $status    . "]]></cell>";
		$xml.= "<cell><![CDATA[" .  $desc      . "]]></cell>";
    $xml.= "</row>\n";
}

$xml.= "</rows>\n";

echo $xml;
$db->close($conn);
?>