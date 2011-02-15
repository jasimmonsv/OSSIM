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

require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
require_once ('classes/Util.inc');
require_once ('../conf/_conf.php');
require_once ('../utils.php');

$error      = false;
$tab_ok     = null;
$no_action  = false;
$path  		= $agent_conf;
$path_tmp   = "/tmp/".uniqid()."_tmp.conf";

if ( @copy ($path , $path_tmp) == false )
{
	echo "2###"._("Failure to update")." <b>$agent_conf</b>";
	exit();
}

if($tab == "#tab2")
{
	$data   = html_entity_decode(base64_decode($_POST['data']),ENT_QUOTES, "UTF-8");
	$tab_ok = "1###<b>$agent_conf "._("updated sucessfully")."</b>";
}
else
{
	$no_action = true;
	echo "2###"._("Error: Illegal action");
}

if ($no_action == false)
{
	if ( @file_put_contents($path, $data, LOCK_EX) == false )
	{
		echo "2###"._("Failure to update")." <b>$agent_conf</b> (2)";
		echo $tab_error;
	}
	else
	{
		$result = test_agents(); 	
					
		if ( $result !== true )
		{
			echo "3###".$result;
			$error = true;
		}
		else
			echo $tab_ok;
	}
	
	if ( $error == true )
	{
		@unlink ($path);
		@copy ($path_tmp, $path);
	}	
}

@unlink($path_tmp);	

?>