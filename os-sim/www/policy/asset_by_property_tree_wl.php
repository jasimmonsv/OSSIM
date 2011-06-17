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
* - cmpf()
* Classes list:
*/
require_once 'classes/Security.inc';
require_once ('classes/Host.inc');
require_once ('classes/Host_os.inc');
require_once ('classes/Host_services.inc');
require_once ('classes/Host_mac.inc');
require_once ('classes/Util.inc');
require_once ('ossim_db.inc');

if ( !Session::menu_perms("MenuPolicy", "PolicyHosts") )
{
	$msg = _("You don\'t have permission to see this tree")." [Assets -> Assets -> Hosts menu permission]";
	
	echo "{title:'<span>"._("Asset by Property")."</span>', icon:'../../pixmaps/theme/any.png', addClass:'size11n', isFolder:'true', expand:true, key:'-1', 
			children:[{title: '<span>".$msg."</span>', icon:'../../pixmaps/theme/ltError.gif', addClass:'size10_red', expand:true, key:'-2'}]}";
	exit;
}

$key  = GET('key');
$page = intval(GET('page')); 

ossim_valid($key, OSS_NULLABLE, OSS_TEXT, OSS_PUNC, 'illegal:' . _("Key"));
ossim_valid($page, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("Page"));

if (ossim_error()) {
    die(ossim_error());
}

if ( $page == "" || $page<=0 )
	$page = 1;
	
$maxresults  = 50;
$to          = $page * $maxresults;
$from        = $to - $maxresults;
$nextpage    = $page + 1;

$length_name = 70;
$h           = 570; 

$buffer = "";

$db   = new ossim_db(); 
$conn = $db->connect();

if(preg_match("/cclass_(\d+)_(.+)/",$key) || preg_match("/all_\d+\.\d+\.\d+_(\d+)_(.+)/",$key) ||  preg_match("/all/",$key) || preg_match("/all_\d+\.\d+\.\d+/",$key)) 
{
    $all_cclass_hosts = array();
	
	if(preg_match("/cclass_(\d+)_(.+)/",$key, $found) || preg_match("/all_\d+\.\d+\.\d+_(\d+)_(.+)/",$key, $found)) 
	{
        if( $found[1] == 7 ) // MAC
		{ 
            if( $found[2] == "Unknown" ) 
			{ 
				// doesn't exit mac in host_mac_vendors
                $sql = "SELECT DISTINCT (hp.ip), h.hostname
                               FROM host_properties AS hp LEFT JOIN host AS h ON hp.ip = h.ip
                               WHERE hp.property_ref=".$found[1]." AND SUBSTRING(hp.value, 1, 8 ) NOT IN (SELECT DISTINCT  mac FROM host_mac_vendors) ORDER BY hp.ip";
            }
            else 
			{
                $sql = "SELECT DISTINCT (hp.ip), h.hostname
                        FROM host_properties AS hp LEFT JOIN host AS h ON hp.ip = h.ip, host_mac_vendors AS hmv
                        WHERE hp.property_ref=".$found[1]." AND SUBSTRING( hp.value, 1, 8 )=hmv.mac AND MD5(hmv.vendor)='".$found[2]."' ORDER BY hp.ip";
            }
        }
        elseif( $found[1]==8 ) // Services
		{ 
            $sql = "SELECT DISTINCT (hp.ip), h.hostname
                    FROM host_properties AS hp LEFT JOIN host AS h
                    ON hp.ip = h.ip
                    WHERE hp.property_ref=".$found[1]." AND MD5(hp.value)='".$found[2]."' 
                    UNION 
					SELECT inet_ntoa(hs.ip) as ip, h.hostname
					FROM host_services AS hs LEFT JOIN host AS h
					ON inet_ntoa(hs.ip) = h.ip LEFT JOIN protocol p ON p.id=hs.protocol
					WHERE MD5(CONCAT(hs.service,' (',hs.port,'/',LCASE(p.alias),')'))='".$found[2]."'
					ORDER BY ip";
        }
        else 
		{
			$sql = "SELECT DISTINCT (hp.ip), h.hostname
                    FROM host_properties AS hp LEFT JOIN host AS h
                    ON hp.ip = h.ip
                    WHERE hp.property_ref=".$found[1]." AND MD5(hp.value)='".$found[2]."' ORDER BY hp.ip";
				   
		}
    }
    else 
	{
        $sql = "SELECT DISTINCT (hp.ip), h.hostname FROM host_properties AS hp LEFT JOIN host AS h ON hp.ip = h.ip";
    }
	
    
	//print_r($sql);
    if (!$rs = & $conn->Execute($sql)){ 
        print $conn->ErrorMsg();
        exit();
    }
    else
    {
        while (!$rs->EOF) 
		{
        	if (Session::hostAllowed($conn,$rs->fields['ip'])) 
			{
	    		$cclass = preg_replace("/(\d+\.)(\d+\.)(\d+)\.\d+/", "\\1\\2\\3", $rs->fields['ip']);
	    		$all_cclass_hosts[$cclass][] = array($rs->fields['ip'], $rs->fields['hostname']);
	    	}
        	$rs->MoveNext();
        }
    }
}


if( $key == "" ) 
{
    $props   = Host::get_properties_types($conn);
    $buffer .= "[ {title: '"._("Asset by Property")."', isFolder: true, key:'main', icon:'../../pixmaps/theme/any.png', expand:true, children:[\n";
    
	$icons = array (
		"software" 		   => "software",
		"operating-system" => "host_os",
		"cpu" 			   => "cpu",
		"service" 		   => "ports",
		"memory" 		   => "ram",
		"department" 	   => "host_group",
		"macaddress"       => "mac",
		"workgroup" 	   => "net_group",
		"role" 		       => "server_role",
		"acl" 		       => "acl",
		"storage"	       => "storage",
		"route" 		   => "route"
	);
	
	
	foreach ($props as $prop) 
	{
        $png  = $icons[strtolower($prop["name"])];
		$png  = ( empty($png) ) ? "folder" : $png;
		
        if(count(Host::get_property_values($conn, $prop["id"]))>0) 
            $buffer .= "{ key:'p".$prop["id"]."', isFolder:true, isLazy:true, expand:false, icon:'../../pixmaps/theme/$png.png', title:'"._($prop["description"])."' },\n";
        else 
            $buffer .= "{ icon:'../../pixmaps/theme/$png.png', title:'"._($prop["name"])."' },\n";
    }
	
    $buffer .= "{ key:'all', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_add.png', title:'"._("All Hosts")."' }\n";
    $buffer .= "] } ]";
}
else if(preg_match("/p(.*)/",$key,$found)) 
{
    $value_list = Host::get_property_values($conn, $found[1]);
        
    $j = 0;
    
	if( intval($found[1])!=7 ) 
	{
		foreach($value_list as $v) 
		{
			if($j>=$from && $j<$to) 
			{
				$value   = ( strlen($v["value"]) > $length_name ) ? substr($v["value"], 0, $length_name)."..." : $v["value"];
				
				$title   = $value." <font style=\"font-weight:normal;font-size:80%\">(" . $v["total"] . ")</font>";
				$tooltip = $v["value"]." (".$v["total"].")";
				$buffer .= "{ key:'cclass_".$found[1]."_".md5($v["value"])."', isLazy:true , title:'$title', tooltip:'$tooltip', isFolder:true},";
			}
			$j++;
		}
	}
	else 
	{
		foreach($value_list as $v) 
		{
			if($j>=$from && $j<$to)
			{
				$vendor  = ( strlen($v["vendor"]) > $length_name ) ? substr($v["vendor"], 0, $length_name)."..." : $v["vendor"];
				
				$title   = ( $vendor == "" ) ? _("Unknown") : $vendor;
				$title  .= " <font style=\"font-weight:normal;font-size:80%\">(" . $v["total"] . ")</font>";
				
				$v_tooltip = ( $v["vendor"] == "" ) ? _("Unknown") :  $v["vendor"] ;
				$tooltip   =   $v_tooltip." (".$v["total"].")";
				
				$buffer .= "{ icon:'../../pixmaps/theme/mac.png', key:'cclass_".$found[1]."_".(($v["vendor"]=="") ? _("Unknown") : md5($v["vendor"]))."', isLazy:true , title:'$title', tooltip:'$tooltip' ,isFolder:true},";
			}
			$j++;
		}
	}
	
	if ($j>$to) {
		$buffer .= "{ key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_add.png', title:'"._("next")." $maxresults '},";
	}
    
	$buffer = "[ ".preg_replace("/,$/","",$buffer)." ]";
}
else if(preg_match("/cclass_(\d+)_(.*)/",$key,$found)) 
{
    $j = 0;
    foreach($all_cclass_hosts as $cclass => $hg) 
	{
        if($j>=$from && $j<$to) 
		{
            $title   = "$cclass <font style=\"font-weight:normal;font-size:80%\">(" . count($hg) . " "._("hosts").")</font>";
			$buffer .= "{ key:'all_".$cclass."_".$found[1]."_".$found[2]."', isLazy:true, icon:'../../pixmaps/theme/host_add.png', title:'$title'\n},";
        }
        $j++;
    }
	
    if ($j>$to) {
        $buffer .= "{ key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_add.png', title:'"._("next")." $maxresults "._("cclass")."'},";
    }

    $buffer = "[ ".preg_replace("/,$/","",$buffer)." ]";
}
else if(preg_match("/all_(\d+\.\d+\.\d+)_(\d+)_(.*)/",$key,$found)) 
{
    $length_hn = 60;
	foreach($all_cclass_hosts as $cclass => $host_list)
	{	
		if( $cclass==$found[1] ) 
		{
			$j = 0;
			
			foreach ($host_list as $host_data) 
			{
				if($j>=$from && $j<$to) 
				{
					if( $host_data[1] != "" && ($host_data[1] != $host_data[0]) )
					{
						$hname     = ( strlen($host_data[1]) > $length_hn ) ? substr($host_data[1], 0, $length_hn)."..." : $host_data[1];
						$url       = "url:'../host/modifyhostform.php?ip=".$host_data[0]."',";
						
						$title     = $host_data[0]." <font style=\"font-size:80%\">(".Util::htmlentities(utf8_encode($hname)).")</font>";
						$tooltip   = $host_data[0]." (".Util::htmlentities(utf8_encode($host_data[1])).")";
					}
					else 
					{
						$url       = "url:'../host/newhostform.php?ip=".$host_data[0]."',";
						$title     = $tooltip = $host_data[0];
					}
					
					$buffer.= "{ ".$url." icon:'../../pixmaps/theme/host.png', title:'$title', h:'$h', tooltip:'$tooltip' },";
				}
				$j++;
			}
		}
	}
    
    if ($j>$to) {
        $buffer .= "{ key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_add.png', title:'"._("next")." $maxresults "._("hosts")."'},";
    }
    
    $buffer = "[ ".preg_replace("/,$/","",$buffer)." ]";
}
else if( $key=="all" ) 
{
    $j = 0;
    foreach($all_cclass_hosts as $cclass => $hg) 
	{
        if($j>=$from && $j<$to) 
		{
            $title   = "$cclass <font style=\"font-weight:normal;font-size:80%\">(" . count($hg) . " "._("hosts").")</font>";
			$buffer .= "{ key:'all_$cclass', isLazy:true, icon:'../../pixmaps/theme/host_add.png', title:'$title'\n},";
        }
        $j++;
    }
    
	if ($j>$to) {
        $buffer .= "{ key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_add.png', title:'"._("next")." $maxresults "._("cclass")."'},";
    }
    
	$buffer = "[ ".preg_replace("/,$/","",$buffer)." ]";
}
else if(preg_match("/all_(\d+\.\d+\.\d+)/",$key,$found)) 
{
    $length_hn = 60;
	
	foreach($all_cclass_hosts as $cclass => $host_list) 
	{
		if( $cclass==$found[1] ) 
		{
			$j = 0;
			foreach ($host_list as $host_data) 
			{
				if($j>=$from && $j<$to) 
				{
					if( $host_data[1] != "" &&  ($host_data[0] != $host_data[1]) )
					{
						$hname     = ( strlen($host_data[1]) > $length_hn ) ? substr($host_data[1], 0, $length_hn)."..." : $host_data[1];
												
						$url = "url:'../host/modifyhostform.php?ip=".$host_data[0]."',";
						
						$title     = $host_data[0]." <font style=\"font-size:80%\">(".Util::htmlentities(utf8_encode($hname)).")</font>";
						$tooltip   = $host_data[0]." (".Util::htmlentities(utf8_encode($host_data[1])).")";
					}
					else 
					{
						$url       = "url:'../host/newhostform.php?ip=".$host_data[0]."',";
						$title     = $tooltip = $host_data[0];
					}
					$buffer.= "{ ".$url." icon:'../../pixmaps/theme/host.png', title:'$title', tooltip:'$tooltip' },";
				}
				$j++;
			}
		}
	}

    if ($j>$to) {
        $buffer .= "{ key:'$key', page:'$nextpage', isFolder:true, isLazy:true, icon:'../../pixmaps/theme/host_add.png', title:'"._("next")." $maxresults "._("hosts")."'},";
    }
    
    $buffer = "[ ".preg_replace("/,$/","",$buffer)." ]";
}

if ( $buffer == "" || $buffer == "[  ]")
    $buffer = "[{title:'"._("No Data found")."', noLink:true}]";
    
echo $buffer;
$db->close($conn);

?>
