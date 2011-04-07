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
* - Collectors()
* - get_id()
* - get_name()
* - get_description()
* - get_list()
* Classes list:
* - Collectors
*/
class Collectors {
    var $id; // collector id
    var $name; // collector name
    var $description; // collector description
	var $type; // 1=>Detector, 2=>Monitor, 3=>Scanner, 4=>Data
	var $plugin_id;
	var $source_type; // taxonomy
	var $enable; // yes/no
	var $source; // command,database,http,log,remote_command,sdee,session,snortlog,unix_socket,wmi
	var $location; // /var/log/syslog
	var $create; // true/false
	var $process;
	var $start; // yes/no
	var $stop; // yes/no
	var $startup_command;
	var $stop_command;
	var $sample_log; // to upload file
    var $foundrows;    
    
    function Collectors($id, $name, $description, $type, $plugin_id, $source_type, $enable, $source, $location, $create, $process, $start, $stop, $startup_command, $stop_command, $sample_log, $foundrows) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->type = $type;
        $this->plugin_id = $plugin_id;
        $this->source_type = $source_type;
        $this->enable = $enable;
        $this->source = $source;
        $this->location = $location;
        $this->create = $create;
        $this->process = $process;
        $this->start = $start;
        $this->stop = $stop;
        $this->startup_command = $startup_command;
        $this->stop_command = $stop_command;
        $this->sample_log = $sample_log;
        $this->foundrows = $foundrows;        
    }
    function get_id() {
        return $this->id;
    }
    function get_name() {
        return $this->name;
    }
    function get_description() {
        return $this->description;
    }
    function get_type() {
        return $this->type;
    }
    function get_plugin_id() {
        return $this->plugin_id;
    }
    function get_source_type() {
        return $this->source_type;
    }
    function get_enable() {
        return $this->enable;
    }
    function get_source() {
        return $this->source;
    }
    function get_location() {
        return $this->location;
    }
    function get_create() {
        return $this->create;
    }
    function get_process() {
        return $this->process;
    }
    function get_start() {
        return $this->start;
    }
    function get_stop() {
        return $this->stop;
    }
    function get_startup_command() {
        return $this->startup_command;
    }    
    function get_stop_command() {
        return $this->stop_command;
    }
    function get_sample_log() {
        return $this->sample_log;
    }    
    function get_foundrows() {
        return $this->foundrows;
    }    
    /*
    * @static
    */
    function get_list($conn, $where = '') {
        mysql_escape_string($where);
        $iffoundrows = preg_match("/LIMIT/i", $where) ? true : false;
        $select = ($iffoundrows) ? "SQL_CALC_FOUND_ROWS *" : "*";
        $query = OssimQuery("SELECT $select FROM custom_collectors $where");
		//echo $query;
	    $list = array();
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $foundrows = 0;
            if ($iffoundrows) {
                if (!$rf = & $conn->Execute("SELECT FOUND_ROWS() as total")) print $conn->ErrorMsg();
                else $foundrows = $rf->fields["total"];
            }
            while (!$rs->EOF) {
                $list[] = new Collectors($rs->fields["id"], $rs->fields["name"], $rs->fields["description"],
                    $rs->fields["type"], $rs->fields["plugin_id"], $rs->fields["source_type"], $rs->fields["enable"], $rs->fields["source"],
                    $rs->fields["location"], $rs->fields["create"], $rs->fields["process"], $rs->fields["start"], $rs->fields["stop"],
                    $rs->fields["startup_command"], $rs->fields["stop_command"], $rs->fields["sample_log"], $foundrows);
                    $rs->MoveNext();
            }
        }
        return $list;
    }
    
    function insert($conn, $name, $description, $type, $plugin_id, $source_type, $enable, $source, $location, $create, $process, $start, $stop, $startup_command, $stop_command, $sample_log) {
        $sql = "INSERT INTO custom_collectors (name, description, type, plugin_id, source_type, enable, source, location, `create`, process, start, stop, startup_command, stop_command, sample_log) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $params = array($name, $description, $type, $plugin_id, $source_type, $enable, $source, $location, $create, $process, $start, $stop, $startup_command, $stop_command, $sample_log);
        if (!$rs = & $conn->Execute($sql, $params)) {
            print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
        }
    }
    
    function update($conn, $id, $name, $description, $type, $plugin_id, $source_type, $enable, $source, $location, $create, $process, $start, $stop, $startup_command, $stop_command, $sample_log) {
    	if ($sample_log=="") {
	        $sql = "UPDATE custom_collectors SET name=?, description=?, type=?, plugin_id=?, source_type=?, enable=?, source=?, location=?, `create`=?, process=?, start=?, stop=?, startup_command=?, stop_command=? WHERE id=?";
	        $params = array($name, $description, $type, $plugin_id, $source_type, $enable, $source, $location, $create, $process, $start, $stop, $startup_command, $stop_command, $id);
	    } else {
	        $sql = "UPDATE custom_collectors SET name=?, description=?, type=?, plugin_id=?, source_type=?, enable=?, source=?, location=?, `create`=?, process=?, start=?, stop=?, startup_command=?, stop_command=?, sample_log=?  WHERE id=?";
	        $params = array($name, $description, $type, $plugin_id, $source_type, $enable, $source, $location, $create, $process, $start, $stop, $startup_command, $stop_command, $sample_log, $id);
	    }
        if (!$rs = & $conn->Execute($sql, $params)) {
            print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
        }
    }
    
    function get_max_id($conn,$type) {
        $max = 9000;
        $query = OssimQuery("select max(id)+1 from plugin where type=$type");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
            exit;
        } else {
            $max = $rf->fields[0];
            if ($max<9000) $max=9000;
        }
        return $max;
    }
    
    function get_next_sid($conn,$plugin_id) {
        $max = 1;
        $query = OssimQuery("select max(sid)+1 from plugin_sid where plugin_id=$plugin_id");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
            exit;
        } else {
            $max = $rf->fields[0];
            if (is_null($max) || !$max) $max=1;
        }
        return $max;
    }
    
    function get_source_types($conn) {
        $list = array();
        $query = OssimQuery("select distinct source_type from plugin order by source_type");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
            exit;
        } else {
            while (!$rs->EOF) {
            	$list[] = $rs->fields[0];
            	$rs->MoveNext();
            }
        }
        return $list;
    }
        
    function delete($conn, $id) {
        $sql = "DELETE FROM custom_collector_rules WHERE idc = ?";
        $params = array($id);
        if (!$rs = & $conn->Execute($sql, $params)) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        $sql = "DELETE FROM custom_collectors WHERE id = ?";
        $params = array($id);
        if (!$rs = & $conn->Execute($sql, $params)) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }        
    }

}

class CollectorRule {
    var $id; // rule id
    var $idc; // collector id
    var $name; // rule name
    var $description; // rule description
	var $type; // event
	var $expression;
	var $prio; // 0-5
	var $rel; // 0-10
	var $plugin_sid;
	var $category_id; // taxonomy
	var $subcategory_id; // taxonomy
	var $date;
	var $sensor;
	var $interface;
	var $protocol;
	var $src_ip;
	var $src_port;
	var $dst_ip;
    var $dst_port;
	var $username;
	var $password;
	var $filename;
	var $userdata1;
	var $userdata2;
	var $userdata3;
	var $userdata4;
	var $userdata5;
	var $userdata6;
	var $userdata7;
	var $userdata8;
	var $userdata9;
    
    function CollectorRule($id, $idc, $name, $description, $type, $expression, $prio, $rel, $plugin_sid, $category_id, $subcategory_id, $date, $sensor, $interface, $protocol, $src_ip, $src_port, $dst_ip,  $dst_port, $username, $password, $filename, $userdata1, $userdata2, $userdata3, $userdata4, $userdata5, $userdata6, $userdata7, $userdata8, $userdata9, $foundrows) {
		$this->id = $id;
		$this->idc = $idc;
		$this->name = $name;
		$this->description = $description;
		$this->type = $type;
		$this->expression = $expression;
		$this->prio = $prio;
		$this->rel = $rel;
		$this->plugin_sid = $plugin_sid;
		$this->category_id = $category_id;
		$this->subcategory_id = $subcategory_id;
		$this->date = $date;
		$this->sensor = $sensor;
		$this->interface = $interface;
		$this->protocol = $protocol;
		$this->src_ip = $src_ip;
		$this->src_port = $src_port;
		$this->dst_ip = $dst_ip;
		$this->dst_port = $dst_port;
		$this->username = $username;
		$this->password = $password;
		$this->filename = $filename;
		$this->userdata1 = $userdata1;
		$this->userdata2 = $userdata2;
		$this->userdata3 = $userdata3;
		$this->userdata4 = $userdata4;
		$this->userdata5 = $userdata5;
		$this->userdata6 = $userdata6;
		$this->userdata7 = $userdata7;
		$this->userdata8 = $userdata8;
		$this->userdata9 = $userdata9;
        $this->foundrows = $foundrows;        
    }
    function get_id() {
		return $this->id;
	}
	function get_idc() {
		return $this->idc;
	}
	function get_name() {
		return $this->name;
	}
	function get_description() {
		return $this->description;
	}
	function get_type() {
		return $this->type;
	}
	function get_expression() {
		return $this->expression;
	}
	function get_prio() {
		return $this->prio;
	}
	function get_rel() {
		return $this->rel;
	}
	function get_plugin_sid() {
		return $this->plugin_sid;
	}
	function get_category_id() {
		return $this->category_id;
	}
	function get_subcategory_id() {
		return $this->subcategory_id;
	}	
	function get_date($empty=false) {
		return ($this->date!="" || !$empty) ? $this->date : "<small>EMPTY</small>";
	}
	function get_sensor($empty=false) {
		return ($this->sensor!="" || !$empty) ? $this->sensor : "<small>EMPTY</small>";
	}
	function get_interface($empty=false) {
		return ($this->interface!="" || !$empty) ? $this->interface : "<small>EMPTY</small>";
	}
	function get_protocol($empty=false) {
		return ($this->protocol!="" || !$empty) ? $this->protocol : "<small>EMPTY</small>";
	}
	function get_src_ip($empty=false) {
		return ($this->src_ip!="" || !$empty) ? $this->src_ip : "<small>EMPTY</small>";
	}
	function get_src_port($empty=false) {
		return ($this->src_port!="" || !$empty) ? $this->src_port : "<small>EMPTY</small>";
	}
	function get_dst_ip($empty=false) {
		return ($this->dst_ip!="" || !$empty) ? $this->dst_ip : "<small>EMPTY</small>";
	}
	function get_dst_port($empty=false) {
		return ($this->dst_port!="" || !$empty) ? $this->dst_port : "<small>EMPTY</small>";
	}
	function get_username($empty=false) {
		return ($this->username!="" || !$empty) ? $this->username : "<small>EMPTY</small>";
	}
	function get_password($empty=false) {
		return ($this->password!="" || !$empty) ? $this->password : "<small>EMPTY</small>";
	}
	function get_filename($empty=false) {
		return ($this->filename!="" || !$empty) ? $this->filename : "<small>EMPTY</small>";
	}
	function get_userdata1($empty=false) {
		return ($this->userdata1!="" || !$empty) ? $this->userdata1 : "<small>EMPTY</small>";
	}
	function get_userdata2($empty=false) {
		return ($this->userdata2!="" || !$empty) ? $this->userdata2 : "<small>EMPTY</small>";
	}
	function get_userdata3($empty=false) {
		return ($this->userdata3!="" || !$empty) ? $this->userdata3 : "<small>EMPTY</small>";
	}
	function get_userdata4($empty=false) {
		return ($this->userdata4!="" || !$empty) ? $this->userdata4 : "<small>EMPTY</small>";
	}
	function get_userdata5($empty=false) {
		return ($this->userdata5 !=""|| !$empty) ? $this->userdata5 : "<small>EMPTY</small>";
	}
	function get_userdata6($empty=false) {
		return ($this->userdata6!="" || !$empty) ? $this->userdata6 : "<small>EMPTY</small>";
	}
	function get_userdata7($empty=false) {
		return ($this->userdata7!="" || !$empty) ? $this->userdata7 : "<small>EMPTY</small>";
	}
	function get_userdata8($empty=false) {
		return ($this->userdata8!="" || !$empty) ? $this->userdata8 : "<small>EMPTY</small>";
	}
	function get_userdata9($empty=false) {
		return ($this->userdata9!="" || !$empty) ? $this->userdata9 : "<small>EMPTY</small>";
	}
    function get_foundrows() {
        return $this->foundrows;
    }    
    /*
    * @static
    */
    function get_list($conn, $where = '') {
        mysql_escape_string($where);
        $iffoundrows = preg_match("/LIMIT/i", $where) ? true : false;
        $select = ($iffoundrows) ? "SQL_CALC_FOUND_ROWS *" : "*";
        $query = OssimQuery("SELECT $select FROM custom_collector_rules $where");
		//echo $query;
	    $list = array();
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $foundrows = 0;
            if ($iffoundrows) {
                if (!$rf = & $conn->Execute("SELECT FOUND_ROWS() as total")) print $conn->ErrorMsg();
                else $foundrows = $rf->fields["total"];
            }
            while (!$rs->EOF) {
                $list[] = new CollectorRule($rs->fields["id"], $rs->fields["idc"], $rs->fields["name"], $rs->fields["description"], $rs->fields["type"], $rs->fields["expression"], $rs->fields["prio"], $rs->fields["rel"], $rs->fields["plugin_sid"], $rs->fields["category_id"],
                $rs->fields["subcategory_id"], $rs->fields["date"], $rs->fields["sensor"], $rs->fields["interface"],
                $rs->fields["protocol"], $rs->fields["src_ip"], $rs->fields["src_port"], $rs->fields["dst_ip"], $rs->fields["dst_port"],
                $rs->fields["username"], $rs->fields["password"], $rs->fields["filename"], $rs->fields["userdata1"],
                $rs->fields["userdata2"], $rs->fields["userdata3"], $rs->fields["userdata4"], $rs->fields["userdata5"],
                $rs->fields["userdata6"], $rs->fields["userdata7"], $rs->fields["userdata8"], $rs->fields["userdata9"], $foundrows);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    
    function insert($conn, $idc, $name, $description, $type, $expression, $prio, $rel, $plugin_sid, $category_id, $subcategory_id, $date, $sensor, $interface, $protocol, $src_ip, $src_port, $dst_ip,  $dst_port, $username, $password, $filename, $userdata1, $userdata2, $userdata3, $userdata4, $userdata5, $userdata6, $userdata7, $userdata8, $userdata9) {
        $sql = "INSERT INTO custom_collector_rules (idc, name, description, type, expression, prio, rel, plugin_sid, category_id, subcategory_id, date, sensor, interface, protocol, src_ip, src_port, dst_ip,  dst_port, username, password, filename, userdata1, userdata2, userdata3, userdata4, userdata5, userdata6, userdata7, userdata8, userdata9) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $params = array($idc, $name, $description, $type, $expression, $prio, $rel, $plugin_sid, $category_id, $subcategory_id, $date, $sensor, $interface, $protocol, $src_ip, $src_port, $dst_ip,  $dst_port, $username, $password, $filename, $userdata1, $userdata2, $userdata3, $userdata4, $userdata5, $userdata6, $userdata7, $userdata8, $userdata9);
        if (!$rs = & $conn->Execute($sql, $params)) {
            print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
        }
    }
    
    function update($conn, $id, $idc, $name, $description, $type, $expression, $prio, $rel, $plugin_sid, $category_id, $subcategory_id, $date, $sensor, $interface, $protocol, $src_ip, $src_port, $dst_ip,  $dst_port, $username, $password, $filename, $userdata1, $userdata2, $userdata3, $userdata4, $userdata5, $userdata6, $userdata7, $userdata8, $userdata9) {
        $sql = "UPDATE custom_collector_rules SET idc=?, name=?, description=?, type=?, expression=?, prio=?, rel=?, plugin_sid=?, category_id=?, subcategory_id=?, date=?, sensor=?, interface=?, protocol=?, src_ip=?, src_port=?, dst_ip=?, dst_port=?, username=?, password=?, filename=?, userdata1=?, userdata2=?, userdata3=?, userdata4=?, userdata5=?, userdata6=?, userdata7=?, userdata8=?, userdata9=? WHERE id=?";
        $params = array($idc, $name, $description, $type, $expression, $prio, $rel, $plugin_sid, $category_id, $subcategory_id, $date, $sensor, $interface, $protocol, $src_ip, $src_port, $dst_ip, $dst_port, $username, $password, $filename, $userdata1, $userdata2, $userdata3, $userdata4, $userdata5, $userdata6, $userdata7, $userdata8, $userdata9, $id);
        if (!$rs = & $conn->Execute($sql, $params)) {
            print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
        }
    }
    
    function delete($conn, $id) {
        $sql = "DELETE FROM custom_collector_rules WHERE id = ?";
        $params = array($id);
        if (!$rs = & $conn->Execute($sql, $params)) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
        }
    }

	function get_categories($conn) {
	    $categories = array();
        if (!$rs = & $conn->Execute("select * from ossim.category order by name")) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                $categories[$rs->fields["id"]] = str_replace("_"," ",$rs->fields["name"]);
                $rs->MoveNext();
            }
        }
	    return $categories;
	}

	function get_subcategories($conn,$categories) {
	    $scategories = array();
	    foreach ($categories as $idcat => $namecat) {
	        if (!$rs = & $conn->Execute("select * from ossim.subcategory where cat_id=$idcat order by name")) {
	            print $conn->ErrorMsg();
	        } else {
	            while (!$rs->EOF) {
	                $scategories[$idcat][$rs->fields["id"]] = str_replace("_"," ",$rs->fields["name"]);
	                $rs->MoveNext();
	            }
	        }
	    }
	    return $scategories;
	}
	
}

?>
