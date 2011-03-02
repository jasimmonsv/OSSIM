<?php
/*****************************************************************************
*
*    License:
*
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
ini_set('session.bug_compat_warn','off');

require_once ('classes/Session.inc');
Session::logcheck("MenuPolicy", "5DSearch");
require_once ('classes/User_config.inc');
require_once ('classes/Util.inc');
require_once ('ossim_db.inc');
require_once ('ossim_conf.inc');
include ("functions.php");

$db     = new ossim_db();
$conn   = $db->connect();
$config = new User_config($conn);
$user   = Session::get_session_user();

// Read config file with filters rules
$rules = get_rulesconfig ();


ossim_valid(GET('num'), OSS_DIGIT, OSS_NULLABLE,                         'illegal:' . _("num"));
ossim_valid(GET('operator'), "and", "or", OSS_NULLABLE,                  'illegal:' . _("operator"));
ossim_valid(GET('descr'), OSS_ALPHA, OSS_SPACE, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Description"));
if (ossim_error()) {
    exit;
}

if (GET('inv_do') == "export")
{
	$inv_session = array();
	
	for ($i = 1; $i <= GET('num'); $i++)
	{
		
		$type    = null;
		$subtype = null;
		$match   = null;
		$value   = null;
		$value2  = null;
		
		$type    = GET('type_'.$i);
		$subtype = GET('subtype_'.$i);
		$match   = GET('match_'.$i);
		
		
		$value   = (mb_detect_encoding(GET('value_'.$i)." ",'UTF-8,ISO-8859-1')  == 'UTF-8') ? GET('value_'.$i)  : mb_convert_encoding(GET('value_'.$i), 'UTF-8', 'ISO-8859-1');
		$value   = Util::utf8entities($value);
		
		$value2  = (mb_detect_encoding(GET('value2_'.$i)." ",'UTF-8,ISO-8859-1') == 'UTF-8') ? GET('value2_'.$i) : mb_convert_encoding(GET('value2_'.$i), 'UTF-8', 'ISO-8859-1');
		$value2  = Util::utf8entities($value2);
		
		
		ossim_valid($type,    OSS_ALPHA, OSS_SPACE     , 'illegal:' . _("type"));
		ossim_valid($subtype, OSS_ALPHA, OSS_SPACE     , 'illegal:' . _("subtype"));
		ossim_valid($match,   OSS_ALPHA, OSS_NULLABLE  , 'illegal:' . _("match"));
		
		if (ossim_error()) {
		    echo "error###"._("There was an error while saving the profile");
			exit;
		}
		
		$filter = array(
			"type"    => $type,
			"subtype" => $subtype,
			"value"   => $value,
			"value2"  => $value2,
			"match"   => $match
		);
		$inv_session['data'][$i] = $filter;
	}
	$inv_session['op']          = GET('operator');
	
	$description                = (mb_detect_encoding(GET('description')." ",'UTF-8,ISO-8859-1') == 'UTF-8') ? GET('description') : mb_convert_encoding(GET('description'), 'UTF-8', 'ISO-8859-1');
	$description                =  Util::utf8entities(GET('description'));
	$inv_session['description'] = $description;
	
	$serialized_inv             = serialize ($inv_session);
	
	
	$cur_name = (mb_detect_encoding(GET('cur_name')." ",'UTF-8,ISO-8859-1') == 'UTF-8') ? GET('cur_name') : mb_convert_encoding(GET('cur_name'), 'UTF-8', 'ISO-8859-1');
	$name     =  Util::utf8entities($cur_name);
	
	$name_iso =  trim(mb_convert_encoding($name, 'ISO-8859-1','UTF-8'));
		
	$config->set($user, $name_iso, $serialized_inv, 'simple', "inv_search");
	
	$_SESSION['profile'] = base64_encode($name);
	echo "1###".$_SESSION['profile'];
}
elseif (GET('inv_do') == "export_last")
{
	$inv_session = array();
	for ($i = 1; $i <= $_SESSION['inventory_last_search']['num']; $i++) {
				
		ossim_valid($_SESSION['inventory_last_search']['num']['type'],     OSS_ALPHA, OSS_SPACE    , 'illegal:' . _("type"));
		ossim_valid($_SESSION['inventory_last_search']['num']['subtype'],  OSS_ALPHA, OSS_SPACE    , 'illegal:' . _("subtype"));
		ossim_valid($_SESSION['inventory_last_search']['num']['match'],    OSS_ALPHA, OSS_NULLABLE , 'illegal:' . _("match"));
		
		if (ossim_error())
		{
				echo "error###"._("There was an error while saving the profile");
				exit;
		}
		
		$inv_session['data'][$i] = $_SESSION['inventory_last_search'][$i];
	}
		
	
	$inv_session['op'] = $_SESSION['inventory_last_search_op'];
	$serialized_inv    = serialize ($inv_session);
	
	$name = (mb_detect_encoding(GET('name')." ",'UTF-8,ISO-8859-1') == 'UTF-8') ? GET('name') : mb_convert_encoding(GET('name'), 'ISO-8859-1', 'UTF-8');
	$name =  Util::utf8entities($name);
	
	$_SESSION['profile'] = base64_encode($name);
	
	$name =  mb_convert_encoding($name, 'UTF-8', 'ISO-8859-1');
	
	$config->set($user, $name, $serialized_inv, 'simple', "inv_search");
	
	echo "1###".$_SESSION['profile'];
}
elseif (GET('inv_do') == "import")
{
	$profile_name = base64_decode(GET('name'));
	
	$name = (mb_detect_encoding($profile_name." ",'UTF-8,ISO-8859-1') == 'UTF-8') ? $profile_name : mb_convert_encoding($profile_name, 'UTF-8', 'ISO-8859-1');
	$name =  Util::utf8entities($name);
	$name =  mb_convert_encoding($name, 'ISO-8859-1','UTF-8');
	
	$data = $config->get($user, $name, 'php', "inv_search");
	
	if( !is_array($data) || empty($data) ) 
		exit();
		
	echo "{\"dt\":[";
	$coma = "";
	
	foreach ($data['data'] as $i=>$filter)
	{
		echo $coma;
		
		$value  = null;
		$value2 = null;
		
		$value  = (mb_detect_encoding($filter['value']." ",'UTF-8,ISO-8859-1')  == 'UTF-8') ? $filter['value']  : mb_convert_encoding($filter['value'],  'UTF-8', 'ISO-8859-1');
		$value2 = (mb_detect_encoding($filter['value2']." ",'UTF-8,ISO-8859-1') == 'UTF-8') ? $filter['value2'] : mb_convert_encoding($filter['value2'], 'UTF-8', 'ISO-8859-1');
		
		echo "{\"type\":\"".$filter['type']."\",\"subtype\":\"".$filter['subtype']."\",\"match\":\"".$filter['match']."\",\"value\":\"".$value."\",\"value2\":\"".$value2."\"}";
		$coma = ",";
	}
	
	$description = (mb_detect_encoding($data['description']." ",'UTF-8,ISO-8859-1') == 'UTF-8') ? $data['description'] : mb_convert_encoding($data['description'], 'UTF-8', 'ISO-8859-1');
	
	echo "],\"op\":\"".$data['op']."\",\"descr\":\"".$description."\"}";
	
}
elseif (GET('inv_do') == "delete")
{
    $profile_name = base64_decode(GET('name'));
	
	$name = (mb_detect_encoding($profile_name." ",'UTF-8,ISO-8859-1') == 'UTF-8') ? $profile_name : mb_convert_encoding($profile_name, 'UTF-8', 'ISO-8859-1');
	$name =  Util::utf8entities($name);
	$name =  mb_convert_encoding($name, 'ISO-8859-1','UTF-8');
	
	$config->del($user, $name, "inv_search");
	
	unset($_SESSION['inventory_search']);
	unset($_SESSION['inventory_last_search']);
	unset($_SESSION['profile']);
}
elseif (GET('inv_do') == "getall")
{
	$data = $config->get_all($user, "inv_search");
	
	if( !is_array($data) || empty($data) ) 
		exit();
		
	foreach ($data as $k => $v)
	{
		$profile = Util::utf8entities(mb_convert_encoding($v, 'UTF-8', 'ISO-8859-1'));
		$profiles[$k] = base64_encode($profile)."###".$profile;
	}

	$profiles = implode(",",$profiles);
	echo $profiles;
}
elseif (GET('inv_do') == "last_search")
{
	$data    = $_SESSION['inventory_last_search'];
	$op      = $_SESSION['inventory_last_search_op'];
	$descr   = $_SESSION['inventory_last_descr'];
	
	$descr   = (mb_detect_encoding($descr." ",'UTF-8,ISO-8859-1') == 'UTF-8') ? $descr : mb_convert_encoding($descr, 'UTF-8', 'ISO-8859-1');
	
	if ( $data['num'] < 1 )
		exit();
	
	echo "{\"dt\":[";
	$coma = "";
	for ($i=1;$i<=$data['num'];$i++) {
		$filter = $data[$i];
		echo $coma;
		
		$value  = (mb_detect_encoding($filter['value']." ",'UTF-8,ISO-8859-1')  == 'UTF-8') ? $filter['value']  : mb_convert_encoding($filter['value'], 'UTF-8', 'ISO-8859-1');
		$value2 = (mb_detect_encoding($filter['value2']." ",'UTF-8,ISO-8859-1') == 'UTF-8') ? $filter['value2'] : mb_convert_encoding($filter['value2'], 'UTF-8', 'ISO-8859-1');
		
		echo "{\"type\":\"".$filter['type']."\",\"subtype\":\"".$filter['subtype']."\",\"match\":\"".$filter['match']."\",\"value\":\"".$value."\",\"value2\":\"".$value2."\"}";
		$coma = ",";
	}
	echo "],\"op\":\"".$op."\",\"descr\":\"".$descr."\"}";
}
elseif (GET('inv_do') == "clean")
{
	unset($_SESSION['inventory_search']);
	unset($_SESSION['inventory_last_search']);
	unset($_SESSION['inventory_last_descr']);
	unset($_SESSION['profile']);
}
?>
