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
* - update_display()
* - set_on()
* - set_off()
* - is_on()
* - WebIndicator()
* Classes list:
* - WebIndicator
*/
class WebIndicator {
    var $indicators = array();
    function update_display() {
        echo "<script language=\"JavaScript\" type=\"text/javascript\">\n";
        foreach($this->indicators as $key => $indics) {
            foreach($indics as $indic) {
                if (isset($indic["frame"])) {
                    echo "var webindic=parent." . $indic["frame"] . ".document.getElementById(\"" . $indic["id"] . "\");\n";
                } else {
                    echo "var webindic=document.getElementById(\"" . $indic["id"] . "\");\n";
                }
                echo "if (webindic) { webindic.style.display = ";
                if (WebIndicator::is_on($key)) {
                    echo "''";
                } else {
                    echo "'none'";
                }
                echo "; }\n";
            }
        }
        echo "</script>\n";
    }
    function set_on($name) {
        $_SESSION["_WebIndicator_" . $name] = true;
    }
    function set_off($name) {
        unset($_SESSION["_WebIndicator_" . $name]);
    }
    function is_on($name) {
        return isset($_SESSION["_WebIndicator_" . $name]);
    }
    function WebIndicator() {
        // Define arrays of Web indicator
        // Each array can contain one or more sub-arrays that define uniq html element
        // Each sub-array contain the id of html element to show/hide
        // and an optional frame name for this id
        // Switching on/off a web indicator switch on/off each of its html element
        /*
        $this->indicators["Reload_policies"] = array (
        array (
        "id" => "ReloadPolicy1",
        ),
        array (
        "frame" => "topmenu",
        "id" => "ReloadPolicy2",
        ),
        array (
        "frame" => "topmenu",
        "id" => "ReloadPolicy3",
        ),
        );
        */
        $this->indicators["Reload_policies"] = array(
            array(
                "frame" => "topmenu",
                "id" => "Reload_policies",
            ) ,
        );
        $this->indicators["Reload_nets"] = array(
            array(
                "frame" => "topmenu",
                "id" => "Reload_nets",
            ) ,
        );
        $this->indicators["Reload_hosts"] = array(
            array(
                "frame" => "topmenu",
                "id" => "Reload_hosts",
            ) ,
        );
        $this->indicators["Reload_sensors"] = array(
            array(
                "frame" => "topmenu",
                "id" => "Reload_sensors",
            ) ,
        );
        $this->indicators["Reload_plugins"] = array(
            array(
                "frame" => "topmenu",
                "id" => "Reload_plugins",
            ) ,
        );
        $this->indicators["Reload_directives"] = array(
            array(
                "frame" => "topmenu",
                "id" => "Reload_directives",
            ) ,
        );
        $this->indicators["Reload_servers"] = array(
            array(
                "frame" => "topmenu",
                "id" => "Reload_servers",
            ) ,
        );
        $this->indicators["ReloadPolicy"] = array(
            array(
                "frame" => "topmenu",
                "id" => "ReloadPolicy",
            ) ,
        );
    }
}
$OssimWebIndicator = new WebIndicator();
?>
