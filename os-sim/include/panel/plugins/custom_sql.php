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
* Classes list:
* - Plugin_Custom_SQL extends Panel
*/
/*
* TODO:
* - X Y Axis titles
* - True Type Fonts
* - Scale Labels
* - Graph Sizes and Plot positioning
* - Split classes in Custom_Graph and Custom_Graph_FromSQL
* - Adapt Points Graph to new options
*/
require_once 'ossim_db.inc';
class Plugin_Custom_SQL extends Panel {
    var $defaults = array(
        'graph_db' => 'ossim',
        'graph_sql' => '',
        'graph_title' => '',
        'graph_type' => 'pie',
        'graph_legend_field' => 'col',
        'graph_plotshadow' => 0,
        'graph_pie_theme' => 'sand',
        'graph_pie_3dangle' => 0,
        'graph_pie_explode' => 'none',
        'graph_pie_explode_pos' => 1,
        'graph_pie_antialiasing' => 0,
        'graph_pie_center' => 0.3,
        'graph_point_legend' => '',
        'graph_show_values' => 1,
        'graph_color' => '#000080',
        'graph_gradient' => '',
        'graph_link' => '',
        'graph_radar_fill' => '1',
        'graph_y_min' => 0,
        'graph_y_max' => 0,
        'graph_x_min' => 0,
        'graph_x_max' => 0,
        'graph_y_top' => 0,
        'graph_y_bot' => 0,
        'graph_x_top' => 0,
        'graph_x_bot' => 0
    );
    function getCategoryName() {
        return _("Custom SQL graph");
    }
    function showSubCategoryHTML() {
        $html = '';
        $check_ossim = $check_snort = '';
        if ($this->get('graph_db') == 'snort') {
            $check_snort = 'checked';
        } else {
            $check_ossim = 'checked';
        }
        $html.= 'Database:
            <input type="radio" name="graph_db" value="ossim" ' . $check_ossim . '>Ossim
            <input type="radio" name="graph_db" value="snort" ' . $check_snort . '>Snort
            <br/>
        ';
        $html.= _("SQL code") . ':<br/>';
        $html.= '<textarea name="graph_sql" rows="17" cols="55" wrap="soft">';
        $html.= $this->get('graph_sql');
        $html.= '</textarea>';
        return $html;
    }
    function showSettingsHTML() {
        $html = '';
        //
        // Graph Title
        //
        $html.= _("Graph Title");
        $html.= ': <input type="text" name="graph_title" value="' . $this->get('graph_title') . '"><br/>';
        //
        // Graph Link
        //
        $html.= _("Graph Link");
        $html.= ': <input type="text" name="graph_link" value="' . $this->get('graph_link') . '">';
        //
        // Graph types (pie, bars)
        //
        $html.= '<br/>' . _("Graph Type") . ': <select name="graph_type">';
        $types = array(
            'pie' => _("Pie") ,
            'points' => _("Points") ,
            'radar' => _("Radar") ,
            'bars' => _("Bars")
        );
        foreach($types as $value => $label) {
            $checked = $this->get('graph_type') == $value ? 'selected' : '';
            $html.= "<option value='$value' $checked>$label</option>";
        }
        $html.= '</select><br/>';
        //
        // Shadow
        //
        $html.= _("Plot Shadow") . ": ";
        $opts = array(
            0 => _("No") ,
            1 => _("Yes")
        );
        foreach($opts as $value => $label) {
            $check = $this->get('graph_plotshadow') == $value ? 'checked' : '';
            $html.= "<input type='radio' name='graph_plotshadow' value='$value' $check>$label ";
        }
        //
        // Legend field (col, row)
        //
        $html.= '<br/>' . _("Legend Field") . ': <select name="graph_legend_field">';
        $types = array(
            'col' => _("Columns") ,
            'row' => _("Rows")
        );
        foreach($types as $value => $label) {
            $checked = $this->get('graph_legend_field') == $value ? 'selected' : '';
            $html.= "<option value='$value' $checked>$label</option>";
        }
        $html.= '</select><br/>';
        /********************************************************
        PIE OPTIONS
        ********************************************************/
        $html.= "<hr/><b>" . _("Pie Options") . "</b><hr/>";
        //
        // Color Theme
        //
        $html.= _("Color Theme") . ': <select name="graph_pie_theme">';
        $themes = array(
            'sand' => _("Sand") ,
            'earth' => _("Earth") ,
            'pastel' => _("Pastel") ,
            'water' => _("Water")
        );
        foreach($themes as $value => $label) {
            $selected = $this->get('graph_pie_theme') == $value ? 'selected' : '';
            $html.= "<option value='$value' $checked>$label</option>";
        }
        $html.= '</select><br/>';
        //
        // 3D Angle
        //
        $html.= _("3D Angle") . ': <input type="text" name="graph_pie_3dangle"' . ' value="' . $this->get('graph_pie_3dangle') . '" size=2>';
        //
        // Explode slice
        //
        $html.= '<br/>' . _("Explode Slice") . ": ";
        $opts = array(
            'none' => _("None") ,
            'all' => _("All") ,
            'pos' => 'Pos <input type="text" name="graph_pie_explode_pos" ' . 'value="' . $this->get('graph_pie_explode_pos') . '" size=2>'
        );
        foreach($opts as $value => $label) {
            $check = $this->get('graph_pie_explode') == $value ? 'checked' : '';
            $html.= "<input type='radio' name='graph_pie_explode' value='$value' $check>$label ";
        }
        //
        // Center position
        //
        $html.= '<br/>' . _("Center Position") . ': <input type="text" name="graph_pie_center"' . ' value="' . $this->get('graph_pie_center') . '" size=2>';
        //
        // Antialiasing
        //
        $html.= '<br/>' . _("Use Antialiasing") . ": ";
        $opts = array(
            0 => _("No") ,
            1 => _("Yes")
        );
        foreach($opts as $value => $label) {
            $check = $this->get('graph_pie_antialiasing') == $value ? 'checked' : '';
            $html.= "<input type='radio' name='graph_pie_antialiasing' value='$value' $check>$label ";
        }
        /********************************************************
        RADAR AND POINT OPTIONS
        ********************************************************/
        $html.= "<hr/><b>" . _("Point Options") . "</b><hr/>";
        //
        // Point legend
        //
        $html.= _("Points / Radar Legend");
        $html.= ': <input type="text" name="graph_point_legend" value="' . $this->get('graph_point_legend') . '">';
        /********************************************************
        RADAR OPTIONS
        ********************************************************/
        $html.= "<hr/><b>" . _("Radar Options") . "</b><hr/>";
        //
        // Radar fill
        //
        $html.= _("Fill Radar") . ": ";
        $opts = array(
            0 => _("No") ,
            1 => _("Yes")
        );
        foreach($opts as $value => $label) {
            $check = $this->get('graph_radar_fill') == $value ? 'checked' : '';
            $html.= "<input type='radio' name='graph_radar_fill' value='$value' $check>$label ";
        }
        /********************************************************
        BAR, RADAR & POINTS OPTIONS
        ********************************************************/
        $html.= "<hr/><b>" . _("Bar, Radar & Points Options") . "</b><hr/>";
        //
        // Show values in graph
        //
        $html.= _("Show values") . ": ";
        $show_values = array(
            0 => _("No") ,
            1 => _("Yes")
        );
        foreach($show_values as $key => $label) {
            $check = ($this->get('graph_show_values') == $key) ? 'checked' : '';
            $html.= " <input type='radio' name='graph_show_values' value='$key' $check>" . $label;
        }
        $html.= "<br/>\n";
        //
        // Color settings
        //
        $color = $this->get('graph_color');
        $label = _("Color");
        $html.= <<<END
        <input type="hidden" id="graph_color" name="graph_color" value="$color" size=7>
        <table border=0><tr>
        <td>$label:&nbsp;</td>
        <td id="color_sample"
            style="border: 1px gray solid; width: 15px; height: 20px; font-size: 1px;
                   cursor: pointer; background: $color;"
            onClick="javscript: Control.ColorPalette.toggle('palette');">
        &nbsp;
        </td>
        </tr></table>
        <div id="palette" style="position:absolute; z-index: 100; display: none; padding: 0px"></div>
END;
        //
        // Gradient settings
        // (jpgraph-1.20.3/docs/html/729Usinggradientfillforbargraphs.html#7_2_9)
        // contant values defined at: jpgraph/src/jpgraph_gradient.php
        $gradients = array(
            0 => _("Plain") ,
            1 => _("Middle Vertical") ,
            2 => _("Middle Horizontal") ,
            3 => _("Horizontal") ,
            4 => _("Vertical") ,
            5 => _("Wide Middle Vertical") ,
            6 => _("Wide Middle Horizontal") ,
            7 => _("Center") ,
            8 => _("Reflection Left") ,
            9 => _("Reflection Right") ,
            10 => _("Raised")
        );
        $html.= _("Gradient") . ': <select name="graph_gradient">';
        foreach($gradients as $value => $label) {
            $check = $this->get('graph_gradient') == $value ? 'selected' : '';
            $html.= "<option value='$value' $check>$label</option>";
        }
        $html.= '</select>';
        //
        // Min and Max values for X and Y axis
        //
        $html.= "<br/>" . _("Axis Scale Values") . ":<br/>";
        $y_min = $this->get('graph_y_min') ? $this->get('graph_y_min') : 0;
        $y_max = $this->get('graph_y_max') ? $this->get('graph_y_max') : 0;
        $x_min = $this->get('graph_x_min') ? $this->get('graph_x_min') : 0;
        $x_max = $this->get('graph_x_max') ? $this->get('graph_x_max') : 0;
        $html.= "Y: <input type='text' name='graph_y_min' value='$y_min' size=3>min
                  <input type='text' name='graph_y_max' value='$y_max' size=3>max<br/>
                  X: <input type='text' name='graph_x_min' value='$x_min' size=3>min
                  <input type='text' name='graph_x_max' value='$x_max' size=3>max";
        //
        // Axis grace (jpgraph-1.20.3/docs/ref/LinearScale.html#_LINEARSCALE_SETGRACE)
        //
        $html.= "<br/>" . _("Axis Scale Grace") . ":<br/>";
        $y_top = $this->get('graph_y_top') ? $this->get('graph_y_top') : 0;
        $y_bot = $this->get('graph_y_bot') ? $this->get('graph_y_bot') : 0;
        $x_top = $this->get('graph_x_top') ? $this->get('graph_x_top') : 0;
        $x_bot = $this->get('graph_x_bot') ? $this->get('graph_x_bot') : 0;
        $html.= "Y: <input type='text' name='graph_y_top' value='$y_top' size=3>%top
                  <input type='text' name='graph_y_bot' value='$y_bot' size=3>%bottom<br/>
                  X: <input type='text' name='graph_x_top' value='$x_top' size=3>%top
                  <input type='text' name='graph_x_bot' value='$x_bot' size=3>%bottom";
        return $html;
    }
    function showWindowContents() {
        if (!$this->get('graph_sql')) {
            return _("Please configure options at the Sub-category tab");
        }
        if (!$this->get('graph_type')) {
            return _("Please configure options at the Settings tab");
        }
        // Return the image link
        $nocache = rand(100000, 99999999);
        $html = '<img border="0" src="custom_graph.php?panel_id=' . GET('panel_id') . '&id=' . $this->get('id', 'window_opts') . '&nocache=' . $nocache . '">';
        $link = $this->get('graph_link');
        if (!empty($link) && $link != '#') {
            $html = '<a href="' . $this->get('graph_link') . '">' . $html . '</a>';
        }
        return $html;
    }
}
?>
