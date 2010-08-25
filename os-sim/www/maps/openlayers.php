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
require_once 'classes/Security.inc';
require_once 'classes/Session.inc';
require_once 'classes/User_config.inc';
Session::logcheck("MenuConfiguration", "ConfigurationMaps");
$layer = GET('layer');
ossim_valid($layer, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("layer"));
if (ossim_error()) {
    die(ossim_error());
}
$db = new ossim_db();
$conn = $db->connect();
$config = new User_config($conn);
$login = Session::get_session_user();
if (GET('insert')) {
    $x = GET('x');
    $y = GET('y');
    $zoom = GET('zoom');
    $name = GET('map_name');
    ossim_valid($x, OSS_DIGIT, OSS_DOT, OSS_SCORE, 'illegal:X');
    ossim_valid($y, OSS_DIGIT, OSS_DOT, OSS_SCORE, 'illegal:Y');
    ossim_valid($zoom, OSS_DIGIT, 'illegal:Zoom');
    ossim_valid($name, OSS_INPUT, OSS_SPACE, 'illegal:' . _("Map name"));
    if (ossim_error()) {
        echo ossim_error();
    } else {
        $id = $conn->GenID('map_seq');
        if ($layer == 'image') {
            $image = $config->get($login, 'maps_tmp_image');
            $img_type = $config->get($login, 'maps_tmp_image_type');
            $width = $config->get($login, 'maps_tmp_image_width');
            $height = $config->get($login, 'maps_tmp_image_height');
            $sql = "INSERT INTO map
                    (id, name, engine, engine_data2, engine_data3, engine_data4, center_x, center_y, zoom, engine_data1)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = array(
                $id,
                $name,
                'openlayers_image',
                $img_type,
                $width,
                $height,
                $x,
                $y,
                $zoom,
                $image
            );
            if (!$conn->Execute($sql, $params)) {
                echo ossim_error($conn->ErrorMsg());
            } else {
                header("Location: ./");
                exit;
            }
        } else {
            $sql = "INSERT INTO map (id, name, engine, center_x, center_y, zoom) VALUES (?, ?, ?, ?, ?, ?)";
            $params = array(
                $id,
                $name,
                'openlayers_' . $layer,
                $x,
                $y,
                $zoom
            );
            if (!$conn->Execute($sql, $params)) {
                echo ossim_error($conn->ErrorMsg());
            } else {
                header("Location: ./");
                exit;
            }
        }
    }
}
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
    <?php
if ($layer == 've') { ?>
        <script src='http://dev.virtualearth.net/mapcontrol/v3/mapcontrol.js'></script>
    <?php
} ?>
    <script src="../js/OpenLayers/OpenLayers.js"></script>
    <script type="text/javascript">
        <!--
        var zoom = 0;
        var lat = 0;
        var lon = 0;
        var map, layer;

        function viewCenter()
        {
            var lonlat = map.getCenter();
            $('lat').innerHTML = lonlat.lon;
            $('lon').innerHTML = lonlat.lat;
        }

        function init()
        {
            map = new OpenLayers.Map('map');

            /*var options = {
                           resolutions: [1, 0.5, 0.3],
                           maxResolution: 'auto',
                           numZoomLevels: 4
                          };*/
            var options = { };
         <?php
if ($layer == 'image') {
    $width = $config->get($login, 'maps_tmp_image_width');
    $height = $config->get($login, 'maps_tmp_image_height');
    $nocache = rand(100000, 99999999);
?>
            layer = new OpenLayers.Layer.Image(
                                'Custom Image',
                                './output_image_map.php?tmp_image=1&nochache=<?php echo $nocache
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

            map.setCenter(new OpenLayers.LonLat(lat, lon), zoom);

            map.events.register("zoomend", map, function(e) {
                $('opzoom').innerHTML = map.getZoom();
            });
            map.events.register("move", map, function(e) {
                viewCenter();
            });

            map.events.register("click", map, function(e) {
                var lonlat = map.getLonLatFromViewPortPx(e.xy);
                $('lon').innerHTML = lonlat.lon;
                $('lat').innerHTML = lonlat.lat;
            });
            map.zoomToMaxExtent();
        }
        
        function submit_form()
        {
            var name = $('map_name').value;
            if (!name) {
                $('error').innerHTML = '<?php echo _("Please set a map name") ?>';
            } else {
                $('x').value = $('lon').innerHTML;
                $('y').value = $('lat').innerHTML;
                $('zoom').value = $('opzoom').innerHTML;
                document.myform.submit();
            }
        }
        
        // -->
    </script>
</head><body onLoad="javascript: init();">
<br>
<form name="myform" method="get" action="<?php echo $_SERVER['SCRIPT_NAME'] ?>">
<center><i><?php echo _("Choose the zoom and center position you wish by default") ?></i></center>
<table align="center" width="90%">
<tr><td width="20%" valign="top">
    <table width="100%" align="left" style="border-width: 0">
    <tr><th width="10%" nowrap><?php echo _("Map name") ?></th><td><input type="text" id="map_name" name="map_name"></td>
    <tr><th width="10%">X</th><td><span id="lon">0</span></td>
    <tr><th>Y</th><td><span id="lat">0</span></td>
    <tr><th>Zoom</th><td><span id="opzoom">0</span></td>
    </tr></table><br>&nbsp;<br>
    <center><input type="button" name="foo" value="<?php echo _("Accept") ?>"
                   onClick="javascript: submit_form();"></center><br>
    <center><span id="error" style="color: red; font-weight: bold;"></span></center>
</td><td id="map" width="80%">

</td></tr>
</table>
<input type="hidden" id="x" name="x">
<input type="hidden" id="y" name="y">
<input type="hidden" id="zoom" name="zoom">
<input type="hidden" id="layer" name="layer" value="<?php echo $layer ?>">
<input type="hidden" id="insert" name="insert" value="1">
</form>
</body></html>