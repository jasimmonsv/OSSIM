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
require_once 'ossim_db.inc';
require_once 'classes/Security.inc';
require_once 'classes/Session.inc';
require_once 'classes/Log_action.inc';

Session::logcheck("MenuControlPanel", "BusinessProcesses");

$infolog = array("Indicator Risk Maps");
Log_action::log(49, $infolog);

if (!Session::menu_perms("MenuControlPanel", "BusinessProcessesEdit")) {
print _("You don't have permissions to edit risk indicators");
exit();

}

$db = new ossim_db();
$conn = $db->connect();

$map = GET("map");
$data = GET("data");
$name = GET("name");
$icon = GET("icon");
$url = GET("url");
$ida = GET("id");
$type = GET("type");
$type_name = GET("type_name");
$iconbg = GET('iconbg');
$iconsize = (GET('iconsize') != "") ? GET('iconsize') : 0;

$icon = str_replace("url_slash","/",$icon);
$icon = str_replace("url_quest","?",$icon);
$icon = str_replace("url_equal","=",$icon);
$url = str_replace("url_slash","/",$url);
$url = str_replace("url_quest","?",$url);
$url = str_replace("url_equal","=",$url);

ossim_valid($map, OSS_DIGIT,'illegal:'._("map"));
ossim_valid($ida, OSS_DIGIT, OSS_NULLABLE, 'illegal:'._("map"));
ossim_valid($data, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_DIGIT, ";,.", 'illegal:'._("data"));
ossim_valid($url, OSS_NULLABLE, OSS_SCORE, OSS_ALPHA, OSS_DIGIT, OSS_SPACE, ";,.:\/\?=&()%&", 'illegal:'._("data"));
ossim_valid($name, OSS_NULLABLE, OSS_SCORE, OSS_ALPHA, OSS_DIGIT, OSS_SPACE, ";,.:\/\?=&()%&", 'illegal:'._("name"));
ossim_valid($icon, OSS_NULLABLE, OSS_SCORE, OSS_ALPHA, OSS_DIGIT, OSS_SPACE, ";,.:\/\?=&()%&", 'illegal:'._("icon"));
ossim_valid($type, OSS_NULLABLE, OSS_SCORE, OSS_ALPHA, OSS_DIGIT, OSS_SPACE, ";,.:\/\?=&()%&", 'illegal:'._("type"));
ossim_valid($type_name, OSS_NULLABLE, OSS_SCORE, OSS_ALPHA, OSS_DIGIT, OSS_SPACE, ";,.:\/\?=&()%&", 'illegal:'._("type_name"));
ossim_valid($iconbg, OSS_ALPHA, OSS_NULLABLE, 'illegal:'._("iconbg"));
ossim_valid($iconsize, OSS_DIGIT, 'illegal:'._("iconsize"));
//var_dump($type);
//var_dump($type_name);

$path = explode("pixmaps",$icon);
if (count($path)>1) $icon = "pixmaps".$path[1];

if (ossim_error()) {
die(ossim_error());
}


    $indicators = array();
    $delete_list = array();
    $elems = explode(";",$data);
    foreach ($elems as $elem) if (trim($elem)!="") {
        $param = explode(",",$elem);
        $id = str_replace("rect","",str_replace("alarma","",$param[0]));
        $indicators[$id]["x"] = str_replace("px","",$param[1]);
        $indicators[$id]["y"] = str_replace("px","",$param[2]);
        $indicators[$id]["w"] = str_replace("px","",$param[3]);
        $indicators[$id]["h"] = str_replace("px","",$param[4]);
    }

    $active = array_keys($indicators);
    $query = "select id from risk_indicators where map=?";
    $params = array($map);
        if (!$rs = &$conn->Execute($query, $params)) {
            $log = $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                if (in_array($rs->fields["id"],$active)) {
                    $pos = $indicators[$rs->fields["id"]];
                    $query = "update risk_indicators set x= ?,y= ?, w= ?, h= ? where id= ?";
                    $params = array($pos["x"],$pos["y"],$pos["w"],$pos["h"], $rs->fields["id"]);
                    $conn->Execute($query, $params);
                } else {
                    $delete_list[] = $rs->fields["id"];
                }
            $rs->MoveNext();
        }
    }
    if($icon != "") {
        if ($ida !="" && $name !="") {
            $icon = ($iconbg != "" && $iconbg != "transparent") ? $icon."#".$iconbg : $icon;
			$query = "update risk_indicators set icon= ?, name= ?, url= ?, type= ?, type_name= ?, size= ? where id= ?";
            $params = array($icon, $name, $url, $type, $type_name, $iconsize, $ida);
            $conn->Execute($query,$params);
            echo "refresh_indicators();\n";
        }
    } else {
        if ($ida !="" && $name !="") {
            $query = "update risk_indicators set  name= ?, url= ?, type= ?, type_name= ?, size= ? where id= ?";
            $params = array($name, $url, $type, $type_name, $iconsize, $ida);
            $conn->Execute($query,$params);
            echo "refresh_indicators();\n";
        }
    }
    foreach ($delete_list as $idb) {
        $query = "delete bp_asset_member.* from bp_asset_member, risk_indicators where risk_indicators.type_name = bp_asset_member.member and risk_indicators.type = bp_asset_member.member_type and risk_indicators.id = ?";
        $params = array($idb);
        $conn->Execute($query, $params);
        $query = "delete from risk_indicators where id= ?";
        $conn->Execute($query, $params);
    }
    $conn->close();
?>
