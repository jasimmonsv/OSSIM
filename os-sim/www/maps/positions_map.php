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
* - save_position()
* - load_position()
* - remove_position()
* - show_list()
* Classes list:
*/
require_once 'classes/Security.inc';
require_once 'classes/Session.inc';
require_once 'classes/Xajax.inc';
require_once 'classes/Member_status.inc';
Session::logcheck("MenuConfiguration", "ConfigurationMaps");
$map_id = GET('map_id');
$type = GET('type');
ossim_valid($map_id, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("map_id"));
ossim_valid($type, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("type"));
if (ossim_error()) {
    die(ossim_error());
}
$limit = 12; // 12 items per page
$db = new ossim_db();
$conn = $db->connect();
$status = new Member_status;
$xajax = new xajax();
$xajax->registerFunction("save_position");
$xajax->registerFunction("load_position");
$xajax->registerFunction("remove_position");
$xajax->registerFunction("show_list");
function save_position($e_id, $x, $y) {
    global $conn, $map_id, $type;
    $resp = new xajaxResponse();
    //return xajax_debug($map_id.' '.$type.'-', $resp);
    // Delete previous element in case it was already in the DB
    $sql = "DELETE FROM map_element WHERE map_id=? AND type=? AND ossim_element_key=?";
    $params = array(
        $map_id,
        $type,
        $e_id
    );
    if (!$conn->Execute($sql, $params)) {
        $resp->addAssign("errors", "innerHTML", $conn->ErrorMsg());
        return $resp;
    }
    // Insert the element
    $id = $conn->GenID();
    $sql = "INSERT INTO map_element (id, type, ossim_element_key, map_id, x, y)
            VALUES (?, ?, ?, ?, ?, ?)";
    $params = array(
        $id,
        $type,
        $e_id,
        $map_id,
        $x,
        $y
    );
    if (!$conn->Execute($sql, $params)) {
        $resp->addAssign("errors", "innerHTML", $conn->ErrorMsg());
    } else {
        $resp->addAssign("errors", "innerHTML", '<font color="green"><b>' . _("Position saved successfully") . '</b></font>');
        $resp->addScript("Element.setStyle('$e_id', {color: 'green'})");
    }
    return $resp;
}
function load_position($e_id) {
    global $conn, $map_id, $type;
    $resp = new xajaxResponse();
    $sql = "SELECT x, y FROM map_element WHERE map_id=? AND type=? AND ossim_element_key=?";
    $row = $conn->GetRow($sql, array(
        $map_id,
        $type,
        $e_id
    ));
    if ($row === false || !count($row)) {
        $resp->addScript('markers.clearMarkers();');
    } else {
        $resp->addScript('create_marker(' . $row['x'] . ', ' . $row['y'] . ');');
    }
    return $resp;
}
function remove_position($e_id) {
    global $conn, $map_id, $type;
    $resp = new xajaxResponse();
    $sql = "DELETE FROM map_element WHERE map_id=? AND type=? AND ossim_element_key=?";
    $params = array(
        $map_id,
        $type,
        $e_id
    );
    if (!$conn->Execute($sql, $params)) {
        $resp->addAssign("errors", "innerHTML", $conn->ErrorMsg());
    } else {
        $resp->addAssign("errors", "innerHTML", '<font color="green"><b>' . _("Position removed successfully") . '</b></font>');
        $resp->addScript('markers.clearMarkers();');
        $resp->addScript("Element.setStyle('$e_id', {color: ''})");
    }
    return $resp;
}
function show_list($from) {
    global $conn, $map_id, $type, $limit, $status;
    $resp = new xajaxResponse();
    $sql = "SELECT ossim_element_key FROM map_element WHERE map_id=? AND type=?";
    if (!$rs = $conn->Execute($sql, array(
        $map_id,
        $type
    ))) {
        die(ossim_error($conn->ErrorMsg()));
    }
    $positions = array();
    while (!$rs->EOF) {
        $positions[] = $rs->fields['ossim_element_key'];
        $rs->MoveNext();
    }
    $items = array();
    switch ($type) {
        case 'sensor':
            include_once 'classes/Sensor.inc';
            $list = Sensor::get_list($conn, 'ORDER BY name');
            foreach($list as $obj) {
                $items[$obj->get_name() ] = array(
                    $obj->get_name() ,
                    $obj->get_ip()
                );
            }
            $icon = $status->get_icon('sensor', 'ok');
            break;
    }
    // Pager stuff
    $pager = Util::pager_get_data($from, $limit, count($items));
    // Manual limit could be improved
    $items = array_slice($items, $pager['from'] - 1, $limit);
    $html = '<table width="100%" align="center" style="border-width: 0px"><tr>';
    // previous link
    if ($pager['current'] > 1) {
        $prev = '<a href="#" onClick="javascript: xajax_show_list(' . $pager['prev'] . ')">&lt; ' . _("Previous") . '</a>';
    } else {
        $prev = '&nbsp;';
    }
    $html.= "<td style=\"border-width: 0px\">$prev</td><td style=\"border-width: 0px\" nowrap>";
    // pages links
    if (count($pager['pages']) > 0) {
        foreach($pager['pages'] as $page => $row) {
            if ($page == $pager['current']) {
                $page = '<font color="red">' . $page . '</font> ';
            }
            $html.= '&nbsp;<a href="#" onClick="javascript: xajax_show_list(' . $row . ')">' . $page . '</a>';
        }
    }
    // next link
    if ($pager['current'] < $pager['numpages']) {
        $next = '<a href="#" onClick="javascript: xajax_show_list(' . $pager['next'] . ')">' . _("Next") . ' &gt;</a>';
    } else {
        $next = '&nbsp;';
    }
    $html.= "</td><td style=\"border-width: 0px\">$next</td></tr></table>";
    $html.= '
    <table width="100%" align="left" style="border-width: 0; padding: 0px; margin: 0px">
    <tr>
        <th>' . _("Item name") . "</th>
        <th>" . _("Actions") . "</th>
    </tr>";
    foreach($items as $id => $data) {
        $has_pos = in_array($id, $positions) ? "color: green" : "";
        $html.= '
    <tr>
        <td id="' . $id . '" style="text-align: left; ' . $has_pos . '">' . $data[0] . ' - ' . $data[1] . '</td>
        <td>
        [<a href="#" title="' . _("Set/View position in map") . '"
            onClick="javascript: helpmsg(\'' . $data[0] . ' - ' . $data[1] . '\', \'' . $id . '\'); return false;">O</a>]&nbsp;
        [<a href="#" title="' . _("Remove position") . '"
            onClick="javascript: xajax_remove_position(\'' . $data[0] . '\'); return false">X</a>]
        </td>
    </tr>';
    }
    $html.= "</table>";
    $resp->addAssign("items", "innerHTML", $html);
    return $resp;
}
$xajax->setRequestURI($_SERVER["REQUEST_URI"]);
$xajax->processRequests();
/************ END AJAX **************/
//engine_data3, engine_data4 are width and height if the engine is openlayers_image
$sql = "SELECT id, name, engine, center_x, center_y, zoom, engine_data3, engine_data4
        FROM map
        WHERE id = ?";
$row = $conn->GetRow($sql, array(
    $map_id
));
switch ($row['engine']) {
    case 'openlayers_op':
        $layer = 'op';
        break;

    case 'openlayers_ve':
        $layer = 've';
        break;

    case 'openlayers_image':
        $layer = 'image';
        $width = $row['engine_data3'];
        $height = $row['engine_data4'];
        break;
}
$icon = $status->get_icon($type, 'ok');
//printr($nitems);
//printr($items);exit;

?>
<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
    <style type="text/css">  
    #map {
            width: 512px;
            height: 450px;
            border: 1px solid gray;
        }
    </style>
    
    <script src="../js/prototype.js" type="text/javascript"></script>
    <?php echo $xajax->printJavascript('', XAJAX_JS); ?>
    
    <?php
if ($layer == 've') { ?>
        <script src='http://dev.virtualearth.net/mapcontrol/v3/mapcontrol.js'></script>
    <?php
} ?>
    <script src="../js/OpenLayers/OpenLayers.js"></script>
    <script type="text/javascript">
        <!--
        var zoom = <?php echo $row['zoom'] ?>;
        var lat = <?php echo $row['center_x'] ?>;
        var lon = <?php echo $row['center_y'] ?>;
        var map, layer, markers;
        var last_x = 0;
        var last_y = 0;

        function viewCenter()
        {
            var lonlat = map.getCenter();
            $('lat').innerHTML = lonlat.lon;
            $('lon').innerHTML = lonlat.lat;
        }

        function create_marker(lon, lat)
        {
            var size = new OpenLayers.Size(21,25);
            var offset = new OpenLayers.Pixel(-(size.w/2), -size.h);
            //var icon = new OpenLayers.Icon('<?php echo $icon ?>', size, offset);
            var icon = new OpenLayers.Icon('<?php echo $icon ?>', size);

            var marker = new OpenLayers.Marker(new OpenLayers.LonLat(lon, lat),icon);
            last_x = lon;
            last_y = lat;
            markers.clearMarkers();
            markers.addMarker(marker);
        }
        

        function init()
        {
            map = new OpenLayers.Map('map');

            var options = {
                           resolutions: [1, 0.5, 0.3],
                           maxResolution: 'auto',
                           numZoomLevels: 4
                          };

         <?php
if ($layer == 'image') { ?>
            layer = new OpenLayers.Layer.Image(
                                'Custom Image',
                                './output_image_map.php?map_id=<?php echo $map_id
?>',
                                new OpenLayers.Bounds(-180, -90, 90, 180),
                                new OpenLayers.Size(<?php echo $width
?>, <?php echo $height ?>),
                                options);
            //map.zoomToMaxExtent();
         <?php
} ?>

         <?php
if ($layer == 'op') { ?>
            layer = new OpenLayers.Layer.WMS( "OpenLayers WMS", 
                        "http://labs.metacarta.com/wms/vmap0", {layers: 'basic'} );
         <?php
} ?>
         
         <?php
if ($layer == 've') { ?>
            layer = new OpenLayers.Layer.VirtualEarth(
                                "VE",
                                {'type': VEMapStyle.Road});
         <?php
} ?>
            map.addLayer(layer);

            /*
            var yahoo = new OpenLayers.Layer.Yahoo("Yahoo");
            map.addLayer(yahoo);
            //*/

            markers = new OpenLayers.Layer.Markers( "Markers" );
            map.addLayer(markers);
            
            map.setCenter(new OpenLayers.LonLat(lon, lat), zoom);

            map.events.register("zoomend", map, function(e) {
                markers.redraw();
            });
            map.events.register("move", map, function(e) {
                markers.redraw();
            });

            map.events.register("click", map, function(e) {
                var lonlat = map.getLonLatFromViewPortPx(e.xy);
                //$('xajax_debug').innerHTML = 'x: '+lonlat.lon+' // y: '+lonlat.lat;
                last_x = lonlat.lon;
                last_y = lonlat.lat;
                create_marker(lonlat.lon, lonlat.lat);
            });
            //map.zoomToMaxExtent();
        }
        
        function helpmsg(name, id)
        {
            $('errors').innerHTML = '&nbsp;';
            last_x = last_y = 0;
            xajax_load_position(id);
            var func = 'js_save_position(\''+id+'\');';
            $('helpbar').innerHTML = '<b>'+name+'</b>: <?php echo _("Click on the map to set the position and then click") ?>: '+
                                     '<a href="javascript: '+func+'"><b><?php echo _("Save position") ?></b></a>';
        }
        
        function js_save_position(id)
        {
            if (last_x == 0 && last_y == 0) {
                $('errors').innerHTML = '<font color="red"><b><?php echo _("Please set a position in map first") ?></b></font>';
            } else {
                xajax_save_position(id, last_x, last_y);
            }
        }
        
        // -->
    </script>
</head><body onLoad="javascript: init(); xajax_show_list(0);">

<div style="width=100%; text-align: right">[<a href="./"><?php echo _("Back to maps") ?></a>]</div>
<center><div id="helpbar" style="width: 80%; border: 1px solid; border-color: #7F7F7F; text-align: left; padding: 2px;">&nbsp;</div></center>
<div id="errors">&nbsp;</div>

<table align="center" width="95%">
<tr><td id="items" width="30%" valign="top" style="border-width: 0">

</td><td id="map" valign="top" width="70%">

</td></tr>
</table>
<i><span style="color: green">(*) <?php echo _("Items in green have already a position set") ?></span></i>
<div id="xajax_debug"></div>
</body></html>
