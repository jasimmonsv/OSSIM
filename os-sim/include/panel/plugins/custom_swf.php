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
* - getCategoryName()
* - showSubCategoryHTML()
* - showSettingsHTML()
* - showWindowContents()
* - generateURL()
* - generateCustomCode()
* - loadPlugin()
* - loadPlugins()
* - generateRealArray()
* Classes list:
* - Plugin_Custom_SWF extends Panel
*/
class Plugin_Custom_SWF extends Panel {
    var $defaults = array(
        'customswf' => '',
        'which_graph' => '',
        'Template' => '',
        'Par' => array()
    );
    var $config_dir = "/etc/ossim/framework/panel/swf_plugin_templates/";
    var $Debug = false;
    function getCategoryName() {
        return _("Custom SWF graph");
    }
    function showSubCategoryHTML() {
        $plst = $this->loadPlugins();
        if (!$plst) return "No plugin template loaded... Review your config<br>";
        $html = _("Select the graph you want to display in this box: <br/><br/><table cellspacing=0 cellpading=0 border=0>");
        foreach($plst as $sgraph) {
            if ($this->get('which_graph') === $sgraph['Name']) {
                $html.= '<tr><td align="left" width="160" style="font-size:9pt"><b><input type="radio" name="which_graph" value="' . $sgraph['Name'] . '" checked />' . $sgraph['Name'] . ':</b></td> <td align="left" width="*" style="font-size:8pt"><br>- Revision: ' . $sgraph['Name'] . ' (' . $sgraph['Revision'] . ')<br>- Category: <i>' . $sgraph['Category'] . '</i><br>- Homepage: <a href="' . $sgraph['Homepage'] . '" title="' . $sgraph['Homepage'] . '">' . $sgraph['Homepage'] . '</a><br>- Description: <i>' . $sgraph['Description'] . '</i><hr></td></tr>';
                $html.= '<input type="hidden" name="Template" value="' . $sgraph['Template'] . '" >';
            } else $html.= '<tr><td align="left" width="160" style="font-size:9pt"><input type="radio" name="which_graph" value="' . $sgraph['Name'] . '" />' . $sgraph['Name'] . ':</td> <td align="left" style="font-size:8pt"><br>- Revision: ' . $sgraph['Name'] . ' (' . $sgraph['Revision'] . ')<br>- Category: <i>' . $sgraph['Category'] . '</i><br>- Homepage: <a href="' . $sgraph['Homepage'] . '" title="' . $sgraph['Homepage'] . '">' . $sgraph['Homepage'] . '</a><br>- Description: <i>' . $sgraph['Description'] . '</i><hr></td></tr>';
        }
        $html.= _("</table>");
        return $html;
    }
    function showSettingsHTML() {
        $gname = $this->get('which_graph');
        $plst = $this->loadPlugins();
        if (!$plst) return "No plugin template loaded... Review your config<br>";
        $params = $this->get();
        $params = $params[plugin_opts][Par];
        if ($gname === "") $html = "No plugin selected<br>";
        else {
            $sgraph = $plst[$gname];
            $html = 'Loaded Revision: <b>' . $sgraph['Name'] . ' (' . $sgraph['Revision'] . ')</b><br><br>Allowed parameters:<br><br>';
            foreach($sgraph['Parameters'] as $graph_opt) if (strlen($params[$graph_opt[Name]]) > 0) //There are existing values
            $html.= '- <i>' . $graph_opt['Description'] . '</i>:<br>&nbsp;&nbsp;<input type="text" name="Par[' . $graph_opt['Name'] . ']" value="' . $params[$graph_opt[Name]] . '" /> <span style="font-size:7pt">[ Real varname: ' . $graph_opt['Name'] . ' ]</span><br><br>';
            else
            //Taking default values
            $html.= '- <i>' . $graph_opt['Description'] . '</i>:<br>&nbsp;&nbsp;<input type="text" name="Par[' . $graph_opt['Name'] . ']" value="' . $graph_opt['Value'] . '" /> <span style="font-size:7pt">[ Real varname: ' . $graph_opt['Name'] . ' ]</span><br><br>';
            $realarray = $this->generateRealArray($params, $graph_opt, $sgraph);
            $srcpath = $this->generateURL($sgraph, $realarray);
            if ($this->Debug === true) {
                $html.= _("The generated code is") . ':<br/>';
                $html.= '<textarea name="customswf" rows="20" cols="55" wrap="on" readonly>';
                $html.= $this->generateCustomCode($this->generateURL($sgraph, $realarray) , $realarray);
                $html.= '</textarea>';
                $html.= _("<br> This code is generated automatically (read-only). No changes are allowed (yet). Review it if something goes wrong for debugging.") . '<br/>';
            }
        }
        return $html;
    }
    function showWindowContents() {
        $gname = $this->get('which_graph');
        if ($gname === "") {
            $plst = $this->loadPlugins();
            if (!$plst) return "No plugin template loaded... Review your config<br>";
            $sgraph = array_pop($plst);
        } else {
            $sgraph = $this->loadPlugin($this->get('Template'));
            if (!$sgraph) {
                $plst = $this->loadPlugins();
                $sgraph = $plst[$gname];
                if (!$plst) return "No plugin template loaded... Review your config<br>";
                $this->Template = $sgraph['Template'];
            }
        }
        $params = $this->get();
        $params = $params[plugin_opts][Par];
        $realarray = $this->generateRealArray($params, $graph_opt, $sgraph);
        $srcpath = $this->generateURL($sgraph, $realarray);
        return $this->generateCustomCode($this->generateURL($sgraph, $realarray) , $realarray);
    }
    /**** Plugins Management functions(template list) ****/
    function generateURL(&$sgraph, &$realarray) {
        $urlgraph = $sgraph['URL'];
        $srcpath = "/ossim/graphs/draw_swf_graph.php?source_graph=" . $urlgraph;
        foreach($realarray as $key => $value) $srcpath.= "&$key=$value";
        return $srcpath;
    }
    function generateCustomCode($srcpath, &$realarray) {
        if ($realarray["width"] > 50 && $realarray["width"] <= 2048) $w = $realarray["width"] + 30;
        else if ($realarray["width"] <= 50) $w = 150;
        else $w = 2048;
        if ($realarray["height"] > 50 && $realarray["height"] <= 2048) $h = $realarray["height"] + 30;
        else if ($realarray["height"] <= 50) $h = 150;
        else $h = 2048;
        $html.= '<iframe src="' . $srcpath . '" height="' . $h . '" width="' . $w . '" frameborder="0">';
        return $html;
    }
    function loadPlugin() {
        if (preg_match("/^.*\.inc *$/", $this->get('Template'))) {
            if (!is_readable($this->config_dir . $this->get('Template'))) {
                echo "<br>Error! can't read the dir $this->config_dir <br>";
                return null;
            }
            include $this->config_dir . $this->get('Template');
            if (!(isset($item[Name]) && isset($item[Description]) && isset($item[Homepage]) && isset($item[Category]) && isset($item[Revision]) && isset($item[Parameters]) && count($item[Parameters]) > 0)) echo "Ignoring file: Invalid SWF Plugin Template in " . $this->config_dir . $f . "<br>";
            else {
                $item['Template'] = $this->get('Template');
                return $item;
            }
        }
        return null;
    }
    function loadPlugins() {
        // Returns a plugins array
        $swfplugins = array();
        if (!is_readable($this->config_dir)) {
            echo "<br>Error! can't read the dir $this->config_dir <br>";
            return null;
        }
        $file_dir = opendir($this->config_dir);
        if (!$file_dir) {
            echo "<br>Error! can't open the dir $this->config_dir <br>";
            return null;
        }
        while (($f = readdir($file_dir)) !== false) {
            if ($f !== '.' && $f !== '..' && preg_match("/^.*\.inc *$/", $f)) {
                include $this->config_dir . $f;
                if (!(isset($item[Name]) && isset($item[Description]) && isset($item[Homepage]) && isset($item[Revision]) && isset($item[Parameters]) && count($item[Parameters]) > 0)) echo "Ignoring file: Invalid SWF Plugin Template in " . $this->config_dir . $f . "<br>";
                else if (!isset($swfplugins[$item['Name']])) {
                    $item['Template'] = $f;
                    $swfplugins[$item['Name']] = $item;
                } else echo "Ignoring file: Duplicated name for the SWF Plugin Template '" . $item[Name] . "' in " . $this->config_dir . $f . "<br>";
            }
        }
        closedir($file_dir);
        //sort($swfplugins);
        if (count($swfplugins) > 0) return $swfplugins;
        echo "No plugins where loaded in " . $this->config_dir . "<br>";
        return null;
    }
    function generateRealArray(&$params, &$graph_opt, &$sgraph) {
        foreach($sgraph['Parameters'] as $graph_opt) if (strlen($params[$graph_opt[Name]]) > 0) //There are existing values
        $realarray[$graph_opt[Name]] = $params[$graph_opt[Name]];
        else
        //Taking default values
        $realarray[$graph_opt[Name]] = $graph_opt['Value'];
        return $realarray;
    }
}
?>
