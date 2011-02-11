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
require_once ('classes/Xml_parser.inc');
require_once ('../utils.php');
require_once ('../conf/_conf.php');

//Show a rule

$__level_key      = POST('key');
$_level_key_name  = $_SESSION['_level_key_name'];

$tree_lr = $_SESSION["_tree"];
$child 	 = getChild($tree_lr, $__level_key);

$rule = array ("@attributes"=> array($_level_key_name => "1"), "0" => array("rule" => $child['tree']));

if ( !empty($child) )
{
	$_level_key_name = $_SESSION['_level_key_name'];
	$xml_obj         = new xml($_level_key_name);
	$output          = $xml_obj->array2xml($rule);
	
	echo "1###".formatOutput($output, $_level_key_name);
}
else
	echo "error###"._("Failure: Information not available"); 


  