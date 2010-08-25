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
* - Plugin_Custom_cloud extends Panel
*/
class Plugin_Custom_cloud extends Panel {
	var $defaults = array(
        'cloud_db' => 'ossim',
        'cloud_sql' => 'SELECT inet_ntoa(src_ip) AS ip, count(*) AS num FROM alarm 
                       WHERE DATE_SUB(CURDATE(),INTERVAL 30 DAY) <= timestamp 
                       GROUP BY ip ORDER BY num DESC LIMIT 15',
        'cloud_link' => 'http://localhost/ossim/report/menu.php?host=_TAG_&section=metrics',
        'cloud_tag_max_len' => 0,
        'cloud_resolv_ip' => 0
    );
    function getCategoryName() {
        return _("Custom Tag-Cloud");
    }
    function showSubCategoryHTML() {
        $html = '';
        $check_ossim = $check_snort = '';
        if ($this->get('cloud_db') == 'snort') {
            $check_snort = 'checked';
        } else {
            $check_ossim = 'checked';
        }
        $resolv_yes = $resolv_no = '';
        if ($this->get('cloud_resolv_ip') == '1') {
            $resolv_yes = 'checked';
        } else {
            $resolv_no = 'checked';
        }
        $html.= _("Database") . ':<br/>
            <input type="radio" name="cloud_db" value="ossim" ' . $check_ossim . '>Ossim<br/>
            <input type="radio" name="cloud_db" value="snort" ' . $check_snort . '>Snort
            <br/>
            <hr noshade>
        ';
        $html.= _("SQL code") . ':<br/>';
        $html.= '<textarea name="cloud_sql" rows="6" cols="55" wrap="soft">';
        $html.= $this->get('cloud_sql');
        $html.= '</textarea><br/>';
        $html.= _("Cloud link. Use _TAG_ for placeholder");
        $html.= ': <input type ="text" name="cloud_link" size="30" value ="' . $this->get('cloud_link') . '"><br/>';
        $html.= _("Cloud tag max length, 0 means unlimited");
        $html.= ': <input type ="text" name="cloud_tag_max_len" value ="' . $this->get('cloud_tag_max_len') . '"><br/>';
        $html.= "<hr noshade>";
        $html.= _("Resolve hostname on column?") . ':<br/>
            <input type="radio" name="cloud_resolv_ip" value="1" ' . $resolv_yes . '>' . _("Yes") . '<br/>
            <input type="radio" name="cloud_resolv_ip" value="0" ' . $resolv_no . '>' . _("No") . '
            <br/>
        ';
        return $html;
    }
    function showSettingsHTML() {
        return _("No extra settings needed for this category");
    }
    function showWindowContents() {
        require_once 'ossim_db.inc';
		require_once 'classes/Event_viewer.inc';
        $dbname = $this->get('cloud_db');
        $link = $this->get('cloud_link');
        $max_len = $this->get('cloud_tag_max_len');
        $resolv_hostname = $this->get('cloud_resolv_ip');
        if (ossim_error()) {
            die(ossim_error());
        }
        $method = $dbname == 'snort' ? 'snort_connect' : 'connect';
        $db = new ossim_db;
        $conn = $db->$method();
		
		// User sensor filtering
		$sensor_where = "";
		if (Session::allowedSensors() != "") {
			$user_sensors = explode(",",Session::allowedSensors());
			$snortsensors = Event_viewer::GetSensorSids($conn);
			$sensor_str = "";
			foreach ($user_sensors as $user_sensor)
				if (count($snortsensors[$user_sensor]) > 0) $sensor_str .= ($sensor_str != "") ? ",".implode(",",$snortsensors[$user_sensor]) : implode(",",$snortsensors[$user_sensor]);
			if ($sensor_str == "") $sensor_str = "0";
			$sensor_where = " sid in (" . $sensor_str . ")";
		}
		
        $sql = $this->get('cloud_sql');
        if (!preg_match('/^\s*\(?\s*SELECT\s/i', $sql) || preg_match('/\sFOR\s+UPDATE/i', $sql) || preg_match('/\sINTO\s+OUTFILE/i', $sql) || preg_match('/\sLOCK\s+IN\s+SHARE\s+MODE/i', $sql)) {
            return _("SQL Query invalid due security reasons");
        }
		
		if ($sensor_where != "") {
			if (preg_match("/where/",$sql)) $sql = str_replace ("where","where ".$sensor_where." AND ",$sql);
			else $sql = str_replace ("GROUP BY","where ".$sensor_where." GROUP BY",$sql);
		}
		
		//echo "Ejecutando en $dbname: $sql";
		
        if (!$rs = $conn->Execute($sql)) {
            echo "Error was: " . $conn->ErrorMsg() . "\n\nQuery was: " . $sql;
            exit();
        }
        if ($resolv_hostname) {
            require_once ("classes/Host.inc");
        }
        $tags = array();
        while (!$rs->EOF) {
            if ($resolv_hostname) {
                $tag_names[$rs->fields[0]] = Host::ip2hostname($conn, $rs->fields[0], $is_sensor = false, $force_no_dns = true);
            }
            $tags[$rs->fields[0]] = $rs->fields[1];
            $rs->MoveNext();
        }
        $db->close($conn);
        if (!count($tags)) {
            return "";
        }
        // Default font sizes
        $min_font_size = 8;
        $max_font_size = 35;
        $minimum_count = min(array_values($tags));
        $maximum_count = max(array_values($tags));
        $spread = $maximum_count - $minimum_count;
        if ($spread == 0) {
            $spread = 1;
        }
        if ($link == '') {
            $link = '#';
        }
        $cloud_html = '';
        $cloud_tags = array(); // create an array to hold tag code
        foreach($tags as $tag => $count) {
            $local_link = str_replace("_TAG_", $tag, $link);
            $local_name = $tag;
            if ($resolv_hostname) $local_name = $tag_names[$tag];
            if ($max_len > 0) $tag = substr($tag, 0, $max_len);
            $size = $min_font_size + ($count - $minimum_count) * ($max_font_size - $min_font_size) / $spread;
            $cloud_tags[] = '<a style="font-size: ' . floor($size) . 'px' . '" class="tag_cloud" href="' . htmlspecialchars($local_link) . '" title="\'' . $tag . '\' returned a count of ' . $count . '">' . htmlspecialchars(stripslashes($local_name)) . '</a>&nbsp;';
        }
        $cloud_html = join("\n", $cloud_tags) . "\n";
        return $cloud_html;
    }
}
?>