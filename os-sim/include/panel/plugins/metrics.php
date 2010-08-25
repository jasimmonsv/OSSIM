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
* - Plugin_Metrics extends Panel
*/
class Plugin_Metrics extends Panel {
    var $defaults = array(
        'metric_max_entries' => '0',
        'metric_max_categories' => '0',
        'metric_restrict_to_tab' => '0',
        'metric_show_tab_names' => '1'
    );
    function getCategoryName() {
        return _("Metrics Metapanel");
    }
    function showSubCategoryHTML() {
        $show_tab_names_yes = $show_tab_names_no = '';
        $restrict_to_tab_yes = $restrict_to_tab_no = '';
        if ($this->get('metric_show_tab_names') == '1') {
            $show_tab_names_yes = 'checked';
        } else {
            $show_tab_names_no = 'checked';
        }
        if ($this->get('metric_restrict_to_tab') == '1') {
            $restrict_to_tab_yes = 'checked';
        } else {
            $restrict_to_tab_no = 'checked';
        }
        $html = "";
        $html.= _("Max panels per category");
        $html.= ':<br/><input type ="text" name="metric_max_entries" value ="' . $this->get('metric_max_entries') . '"><br/>';
        $html.= _("Max categories");
        $html.= ':<br/> <input type ="text" name="metric_max_categories" value ="' . $this->get('metric_max_categories') . '"><br/>';
        $html.= "<hr noshade>";
        $html.= _("Separate by tab names?") . ':<br/>
            <input type="radio" name="metric_show_tab_names" value="1" ' . $show_tab_names_yes . '>' . _("Yes") . '<br/>
            <input type="radio" name="metric_show_tab_names" value="0" ' . $show_tab_names_no . '>' . _("No") . '
            <br/>
        ';
        $html.= _("Restrict to this tab's panels?") . ':<br/>
            <input type="radio" name="metric_restrict_to_tab" value="1" ' . $restrict_to_tab_yes . '>' . _("Yes") . '<br/>
            <input type="radio" name="metric_restrict_to_tab" value="0" ' . $restrict_to_tab_no . '>' . _("No") . '
            <br/>
        ';
        return $html;
    }
    function showSettingsHTML() {
        return _("No extra settings needed for this category");
    }
    function showWindowContents() {
        require_once 'panel/Ajax_Panel.php';
        $conf = & $GLOBALS['conf'];
        $configs_dir = $conf->get_conf('panel_configs_dir');
        $tabs = Window_Panel_Ajax::getPanelTabs();
        // If there aren't any tabs, simulate an empty one
        if (empty($tabs)) {
            $tabs[1] = array(
                'tab_name' => '',
                'tab_icon_url' => ''
            );
        }
        $user = Session::get_session_user();
        $ajax = & new Window_Panel_Ajax();
        $tab_num = 0;
        $panel_num = 0;
        $panel_id = GET('panel_id') ? GET('panel_id') : 0;
        $results_array = array();
        $results_array[0] = array();
        $results_array[1] = array();
        $results_array[2] = array();
        $results_array[3] = array();
        $results_array[4] = array();
        $html = "<ul>\n";
        foreach($tabs as $tab_id => $tab_name) {
            if ($this->get('metric_restrict_to_tab') && $tab_id != $panel_id) continue;
            $tab_num++;
            if (($this->get('metric_max_categories') > 0) && ($tab_num > $this->get('metric_max_categories'))) continue;
            if ($this->get('metric_show_tab_names')) {
                if (strlen($tabs[$tab_id]["tab_icon_url"]) > 0) {
                    $image_string = "<img src=\"" . $tabs[$tab_id]["tab_icon_url"] . "\">";
                } else {
                    $image_string = "";
                }
                $html.= "<br/>" . $tabs[$tab_id]["tab_name"] . $image_string . "<br/><hr noshade>\n";
            }
            $options = $ajax->loadConfig(null, $configs_dir . "/" . $user . "_" . $tab_id);
            // TODO: Check out why some config files get written with "_1" behind them
            if (empty($options)) {
                $options = $ajax->loadConfig(null, $configs_dir . "/" . $user . "_" . $tab_id . "_1");
            }
            $panel_num = 0;
            if (!empty($options)) {
                foreach($options as $panel) {
                    if (($this->get('metric_max_entries') > 0) && ($panel_num >= $this->get('metric_max_entries'))) continue;
                    $indicator = " <img src=\"../pixmaps/traffic_light0.gif\"/> ";
                    if (isset($panel['metric_opts']['enable_metrics']) && $panel['metric_opts']['enable_metrics'] == 1 && isset($panel['metric_opts']['metric_sql']) && strlen($panel['metric_opts']['metric_sql']) > 0) {
                        $panel_num++;
                        $sql = $panel['metric_opts']['metric_sql'];
                        if (!preg_match('/^\s*\(?\s*SELECT\s/i', $sql) || preg_match('/\sFOR\s+UPDATE/i', $sql) || preg_match('/\sINTO\s+OUTFILE/i', $sql) || preg_match('/\sLOCK\s+IN\s+SHARE\s+MODE/i', $sql)) {
                            die(_("SQL Query invalid due security reasons"));
                        }
                        $db = new ossim_db;
                        $conn = $db->connect();
                        if (!$rs = $conn->Execute($sql)) {
                            echo "Error was: " . $conn->ErrorMsg() . "\n\nQuery was: " . $sql;
                            exit();
                        }
                        $metric_value = $rs->fields[0];
                        $db->close($conn);
                        $low_threshold = $panel['metric_opts']['low_threshold'];
                        $high_threshold = $panel['metric_opts']['high_threshold'];
                        $first_comp = $low_threshold - ($low_threshold / 4);
                        $second_comp = $low_threshold + ($low_threshold / 4);
                        $third_comp = $high_threshold + ($high_threshold / 4);
                        $fourth_comp = $high_threshold + ($high_threshold / 4);
                        $title = "";
                        if (isset($panel["window_opts"]["title"])) {
                            $title = $panel["window_opts"]["title"];
                        }
                        if ($metric_value <= $first_comp) {
                            $indicator = " <img src=\"../pixmaps/traffic_light1.gif\"/> ";
                            array_push($results_array[4], "<li>$indicator<small>" . $title . "</small>\n");
                        } elseif ($metric_value > $first_comp && $metric_value <= $second_comp) {
                            $indicator = " <img src=\"../pixmaps/traffic_light2.gif\"/> ";
                            array_push($results_array[3], "<li>$indicator<small>" . $title . "</small>\n");
                        } elseif ($metric_value > $second_comp && $metric_value <= $third_comp) {
                            $indicator = " <img src=\"../pixmaps/traffic_light3.gif\"/> ";
                            array_push($results_array[2], "<li>$indicator<small>" . $title . "</small>\n");
                        } elseif ($metric_value > $third_comp && $metric_value <= $fourth_comp) {
                            $indicator = " <img src=\"../pixmaps/traffic_light4.gif\"/> ";
                            array_push($results_array[1], "<li>$indicator<small>" . $title . "</small>\n");
                        } elseif ($metric_value > $fourth_comp) {
                            $indicator = " <img src=\"../pixmaps/traffic_light5.gif\"/> ";
                            array_push($results_array[0], "<li>$indicator<small>" . $title . "</small>\n");
                        } else {
                            $indicator = " <img src=\"../pixmaps/traffic_light0.gif\"/> ";
                        }
                        $html.= "<li>$indicator<small>" . $title . "</small>\n";
                    } // if(isset(sql))
                    
                }
            } // if(!empty($options))
            
        }
        $html.= "</ul>\n";
        if (!$this->get('metric_show_tab_names')) {
            // Since we don't separate by name, let's give thema  nice order.
            $html = '';
            $html.= "<ul>\n";
            foreach($results_array as $temp_array) {
                foreach($temp_array as $metric) {
                    $html.= "$metric\n";
                }
            }
            $html.= "</ul>\n";
        }
        return $html;
    }
}
?>
