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

require_once 'ossim_db.inc';
require_once 'classes/Security.inc';
require_once 'panel/Ajax_Panel.php';

$url = POST('url');
if(empty($url)){
	die(_('Error no url for add'));
}

$name=POST('name');
if(empty($name)){
	die(_('Error no name for add'));
}
//
require_once 'classes/User_config.inc';
$login = Session::get_session_user();
$db = new ossim_db();
$conn = $db->connect();
$config = new User_config($conn);
//
//$panel_urls = Window_Panel_Ajax::getPanelUrls();
$panel_urls = Window_Panel_Ajax::getPanelTabs();
if(empty($panel_urls)){
	$panel_urls = array();
	$key_ini=1;
}
// check exist
$flag=true;
// clean smenu && hmenu
$url=base64_decode($url);
// check exist ?
if(strpos($url,'?')===false){
	$url.='?';
}
//
$url=str_replace('hmenu', 'older-hmenu', $url);
$url=str_replace('smenu', 'older-smenu', $url);
$url.='&hmenu=dashboards&smenu=dashboards';
//
foreach($panel_urls as $key => $value){
	$key_ini=$key;
	if(!empty($value['tab_url'])){
		if($value['tab_url']==$url){
			$flag=false;
			break;
		}
	}
}
//
if($flag){
	$panel_urls[++$key_ini]=array(
			'tab_name'=>$name,
			'tab_icon_url'=>'',
			'disable'=>0,
			'tab_url'=>$url
			);
}
//

//Window_Panel_Ajax::setPanelUrls($panel_urls);
Window_Panel_Ajax::setPanelTabs($panel_urls);
// clean var
unset($panel_urls);
unset($flag);
unset($key_ini);
?>
<script type="text/javascript">
	window.top.frames["main"].document.location.href='../panel/panel.php';
</script>
