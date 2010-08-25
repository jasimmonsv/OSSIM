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

/*****************************************************************************
* 
This script updates custom_report_types.dr field with subreport counter value
****************************************************************************/


$path_class = '/usr/share/ossim/include/';
ini_set('include_path', $path_class);
require_once ('ossim_db.inc');

$path_class = '/usr/share/ossim/www/report/';
ini_set('include_path', $path_class);
require_once ('plugin_filters.php');



function get_parameters($sr, $dbconn)
{
    $inpt = array();
    if ($sr["inputs"]!="") {
    $input = explode(";",$sr["inputs"]);
    foreach ($input as $inpu) {
        $inpus = explode(":",$inpu);
        $default = ($inpus[2]=="select") ? $inpus[5] : $inpus[4];
        if ($inpus[4]=="CATEGORY" && $default) { $category = $default; $default = GetPluginCategoryName($default, $dbconn); }
        if ($inpus[4]=="SUBCATEGORY" && $default) { $default = GetPluginSubCategoryName(array($category, $default), $dbconn); }
        if ($inpus[2]=="checkbox") $default = (!$default) ? "false" : "true";
        $inpt[] = $inpus[0].($default ? ": <b>$default</b>" : "");
    }

  }
  return $inpt;
}


function menu_type($type)
{
   $needle = 'Product';
   $pos    = strripos($type, $needle);
   if ($pos === false)
   {
       $needle = 'Category';
       $pos = strripos($type, $needle);
      if ($pos === false)
        $ret = 1;
      else
        $ret = 2;
   }
   else
   {
      $needle = 'Category';
      $pos    = strripos($type, $needle);
      if ($pos === false)
        $ret = 4;
      else
        $ret = 3;
   }

   return $ret;
}


function calculate_combinatory($type, $sql, $dbconn)
{
	$num = 0;
	
	switch ($type) {
    case "1":
        $num = 1;
        break;
		
    case "2":
        $categories = GetPluginCategories($dbconn, $sql);
		$num+= count($categories) ;
		/*foreach ($categories as $k => $categorie)
		{
			$subcategories= GetPluginSubCategory($dbconn,$k, $sql);
			$num += count($subcategories);
			$num++;
		}*/
		
		$num++;
		break;
		
    case "3":
        $sourcetypes = GetSourceTypes($dbconn);
		foreach ($sourcetypes as $sourcetype)
		{
			$sql = " AND plugin.source_type='".$sourcetype."'";
			$categories = GetPluginCategories($dbconn, $sql );
			//$num+= count($categories);
			$num++;
			/*foreach ($categories as $k => $categorie)
			{
				$subcategories= GetPluginSubCategory($dbconn,$k, $sql);
				$num += count($subcategories);
				$num++;
			}*/
		}
		$num++;
		break;
		
	case "4":
        $num = count(GetSourceTypes($dbconn)) + 1;
        break;	
}
	
	
    
	return $num;

}

$db = new ossim_db();
$dbconn = $db->connect();

$result = $dbconn->Execute("SELECT id, name, type, inputs, `sql`, dr FROM custom_report_types ORDER BY type,id asc");

while ( !$result->EOF )
{
	$subreports[]=$result->fields;
	$result->MoveNext();
}

$modules = array();

foreach ($subreports as $sr)
  $modules[$sr['type']][] = array('id' => $sr['id'], 'name' => $sr['name'], 'parameters' => get_parameters($sr, $dbconn), 'sql' => $sr['sql'], 'dr' => $sr['dr']);

$cont=0;
foreach ($modules as $name => $module){  
  echo "=================> ".$name." \n";
  foreach ($module as $item){
	$parameters = implode(', ',$item['parameters']);
	$type = menu_type($parameters);
	$res = calculate_combinatory($type, $item['sql'], $dbconn);
	$sql= "UPDATE custom_report_types SET dr = '".$res."' WHERE id='".$item['id']."';";
	$result = $dbconn->Execute($sql);
	
	echo "Item ".$item['name']."(".$item['id']."): ".$res."\n";
	$cont = $cont + $res;
	}
  echo "==============================================================\n\n";	
  	
}

echo "------------------------------------------\n";
echo "Report Modules Available: ". $cont;
echo "\n------------------------------------------\n";

$dbconn->disconnect(); 

?>
