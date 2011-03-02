<?
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
* - check_writable_relative()
* Classes list:
*/
require_once 'classes/Session.inc';
Session::logcheck("MenuControlPanel", "BusinessProcesses");

if (!Session::menu_perms("MenuControlPanel", "BusinessProcessesEdit")) {
print _("You don't have permissions to edit risk indicators");
exit();
}

require_once 'ossim_db.inc';
$db = new ossim_db();
$conn = $db->connect();

$map = GET("map");
$url = GET("url");
$data = str_replace("url_slash","/",GET("data"));
$data = str_replace("url_quest","?",$data);
$data = str_replace("url_equal","=",$data);
$element_type = GET("type");
$iconbg = GET('iconbg');
$iconsize = (GET('iconsize') != "") ? GET('iconsize') : 0;

ossim_valid($url, OSS_NULLABLE, OSS_SCORE, OSS_ALPHA, OSS_DIGIT, OSS_SPACE, ";,.:\/\?=&()%&", 'illegal:'._("data"));
ossim_valid($map, OSS_DIGIT,'illegal:'._("map"));
ossim_valid($data, OSS_NULLABLE, OSS_SCORE, OSS_ALPHA, OSS_DIGIT, OSS_SPACE, ";,.:\/\?=&()%&", 'illegal:'._("data"));
ossim_valid($element_type, OSS_NULLABLE, OSS_ALPHA, 'illegal:'._("element_type"));
ossim_valid($iconbg, OSS_ALPHA, OSS_NULLABLE, 'illegal:'._("iconbg"));
ossim_valid($iconsize, OSS_DIGIT, 'illegal:'._("iconsize"));

if (ossim_error()) {
	die(ossim_error());
}
if ($element_type =="rect") {
	$sql = "insert into risk_indicators (name,map,url,type,type_name,icon,x,y,w,h) values ('rect',?,?,'','','',100,100,50,50)";
	$params = array($map, $url);
       	if (!$rs = &$conn->Execute($sql, $params)) {
           		print $conn->ErrorMsg();
       	} else {
		$query = "select last_insert_id() as id";
	        if (!$rs = &$conn->Execute($query)) {
	            print $conn->ErrorMsg();
	        } else {
	                if(!$rs->EOF){
				$id = $rs->fields["id"];
			}
		}
	echo "drawRect('$id',100,100,50,50);\n";
	}
} else { 
	$dt = explode(";",$data);
	$type = $dt[1];
	$ip = $type_name = $dt[2];
	$what = "name";
	$icon = ($iconbg != "" && $iconbg != "transparent") ? $dt[0]."#".$iconbg : $dt[0];
	$valid_types = array("host", "net", "server", "sensor");
	if(in_array($type, $valid_types)){
		if($type == "host"){
			$what = "hostname";
		}
		$query = "select ip from $type where $what = \"$type_name\"";
	   	if ($rs = &$conn->Execute($query)) {
			$ip = $rs->fields["ip"];
		}
	}


	$sql = "insert ignore into bp_asset_member values (0, ?, ?)";
	$params = array($ip, $type);
	$conn->Execute($sql, $params);
		
	$sql = "insert into risk_indicators (url,map,icon,type_name,name,type,x,y,w,h,size) values (?,?,?,?,?,?,100,100,90,60,?)";
	$params = array($dt[4], $map, $icon, $dt[2], $dt[3], $dt[1], $iconsize);
	$conn->Execute($sql, $params);

	$query = "select last_insert_id() as id";
    if (!$rs = &$conn->Execute($query)) {
       print $conn->ErrorMsg();
    } else {
		if(!$rs->EOF){
        	$id = $rs->fields["id"];
        }
    }
    echo "drawDiv('$id','".$dt[3]."','','".$icon."','".$dt[4]."',100,100,90,60,'".$dt[1]."','".$dt[2]."', $iconsize);\n";
}
$conn->close();
?>
