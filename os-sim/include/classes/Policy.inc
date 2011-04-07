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
* - Policy()
* - get_id()
* - get_priority()
* - get_active()
* - get_order()
* - get_group()
* - get_descr()
* - get_foundrows()
* - get_hosts()
* - get_nets()
* - get_host_groups()
* - get_net_groups()
* - get_sensors()
* - get_ports()
* - get_targets()
* - get_role()
* - get_policy_groups()
* - get_signatures()
* - get_plugingroups()
* - get_time()
* - get_list()
* - activate()
* - get_next_order()
* - swap_orders()
* - change_group()
* - reorder()
* - get_group_from_order()
* - get_order_from_group()
* - get_near_order()
* - insert()
* - update()
* - delete()
* Classes list:
* - Policy
*/
require_once 'classes/Policy_host_reference.inc';
require_once 'classes/Policy_host_group_reference.inc';
require_once 'classes/Policy_net_reference.inc';
require_once 'classes/Policy_net_group_reference.inc';
require_once 'classes/Policy_sensor_reference.inc';
require_once 'classes/Policy_target_reference.inc';
require_once 'classes/Policy_role_reference.inc';
require_once 'classes/Policy_port_reference.inc';
require_once 'classes/Policy_group.inc';
require_once 'classes/Policy_time.inc';
require_once 'classes/Policy_action.inc';
require_once ('classes/Log_action.inc');
require_once 'ossim_db.inc';
require_once 'classes/WebIndicator.inc';
require_once 'classes/Util.inc';

class Policy {
    var $id;
    var $priority;
    var $port_group_name;
    var $descr;
    function Policy($id, $priority, $active, $descr, $order, $group, $foundrows = 0) {
        $this->id = $id;
        $this->priority = $priority;
        $this->active = $active;
        $this->order = $order;
        $this->group = $group;
        $this->descr = Util::htmlentities($descr);
        $this->foundrows = $foundrows;
    }
    function get_id() {
        return $this->id;
    }
    function get_priority() {
        return $this->priority;
    }
    function get_active() {
        return $this->active;
    }
    function get_order() {
        return $this->order;
    }
    function get_group() {
        return $this->group;
    }
    function get_descr() {
        return $this->descr;
    }
    function get_foundrows() {
        return $this->foundrows;
    }
    function get_hosts($conn, $direction) {
        return Policy_host_reference::get_list($conn, $this->id, $direction);
    }
    function get_nets($conn, $direction) {
        return Policy_net_reference::get_list($conn, $this->id, $direction);
    }
    function get_host_groups($conn, $direction) {
        return Policy_host_group_reference::get_list($conn, $this->id, $direction);
    }
    function get_net_groups($conn, $direction) {
        return Policy_net_group_reference::get_list($conn, $this->id, $direction);
    }
    function get_sensors($conn) {
        return Policy_sensor_reference::get_list($conn, $this->id);
    }
    function get_ports($conn) {
        return Policy_port_reference::get_list($conn, $this->id);
    }
    function get_targets($conn) {
        return Policy_target_reference::get_list($conn, $this->id);
    }
    function get_role($conn) {
        return Policy_role_reference::get_list($conn, $this->id);
    }
    function get_policy_groups($conn) {
        return Policy_group::get_list($conn, "ORDER BY policy_group.order");
    }
    /*
    * @deprecated
    */
    function get_signatures($conn) {
        return Policy_sig_reference::get_list($conn, $this->id);
    }
    /*
    * XXXX cox: Seems that the functionality of the $get_all_groups param is
    * broken. See Plugingroup::get_list() for a similar feature
    */
    function get_plugingroups($conn, $policy_id, $get_all_groups = false) {
        $list = array();
        if (!$get_all_groups) {
            $where = "WHERE policy_plugin_group_reference.policy_id = ?";
            $params = array(
                $policy_id
            );
        } else {
            $where = "";
            $params = array();
        }
        /*$sql = "SELECT
                    plugin_group_descr.group_id,
                    plugin_group_descr.name,
                    policy_plugin_group_reference.policy_id
                FROM
                    plugin_group_descr
                LEFT JOIN policy_plugin_group_reference ON
                    plugin_group_descr.group_id = policy_plugin_group_reference.group_id
                $where
                ORDER BY
                    plugin_group_descr.name";*/
        $sql = "SELECT
                    plugin_group_descr.group_id,
                    plugin_group_descr.name,
                    policy_plugin_group_reference.policy_id
                FROM
                    policy_plugin_group_reference
                LEFT JOIN plugin_group_descr ON
                    policy_plugin_group_reference.group_id = plugin_group_descr.group_id
                $where
                ORDER BY
                    plugin_group_descr.name";                    
        if (!$rs = $conn->Execute($sql, $params)) {
            die($conn->ErrorMsg());
        }
        while (!$rs->EOF) {
            $list[] = array(
                'id' => ($rs->fields['group_id']!="") ? $rs->fields['group_id'] : 0,
                'name' => ($rs->fields['name']!="") ? $rs->fields['name'] : "ANY",
                'policy_id' => $rs->fields['policy_id']
            );
            $rs->MoveNext();
        }
        return $list;
    }
    function exist_sensors($conn){
        $list_sensor_reference=$this->get_sensors($conn);
        $list = array();
        // check sensor exist
        foreach($list_sensor_reference as $sensor){
            if(count(Policy_sensor_reference::exist_sensors($conn, $sensor->get_sensor_name()))==0){
                if($sensor->get_sensor_name()=='ANY'||$sensor->get_sensor_name()=='any'){
                    $exist='true';
                }else{
                    $exist='false';
                }
            }else{
                $exist='true';
            }
            $list[$sensor->get_sensor_name()]=$exist;
        }

        return $list;
    }
    function get_time($conn) {
        return Policy_time::get_list($conn, $this->id);
    }
    function get_list($conn, $args = "") {
        $list = array();
        $iffoundrows = preg_match("/LIMIT/i", $args) ? true : false;
        $select = ($iffoundrows) ? "SQL_CALC_FOUND_ROWS *" : "*";
        $query = OssimQuery("SELECT $select FROM policy $args");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $foundrows = 0;
            if ($iffoundrows) {
                if (!$rf = & $conn->Execute("SELECT FOUND_ROWS() as total")) print $conn->ErrorMsg();
                else $foundrows = $rf->fields["total"];
            }
            while (!$rs->EOF) {
                $list[] = new Policy($rs->fields["id"], $rs->fields["priority"], $rs->fields["active"], $rs->fields["descr"], $rs->fields["order"], $rs->fields["group"], $foundrows);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    /*function get_policy_groups($conn)
    {
    $list = array();
    $query = OssimQuery("SELECT DISTINCT policy.group FROM policy ORDER BY policy.order");
    if (!$rs = &$conn->Execute($query)) {
    print $conn->ErrorMsg();
    } else {
    while (!$rs->EOF) {
    $list[] = $rs->fields["group"];
    $rs->MoveNext();
    }
    }
    return $list;
    }*/
    function activate($conn, $id, $action='null') {
        // Toggle active field
        if($action=='null'){
            $active='not active WHERE policy.order';
        }else{
            $active=((int)$action).' WHERE policy.id';
        }
        $sql = "UPDATE policy SET active=".$active."=$id";
        if (!$conn->Execute($sql)) {
            die($conn->ErrorMsg());
        }
    }
    function get_next_order($conn, $group = - 1) {
        // Max(order)+1
        $where = ($group >= 0) ? "WHERE policy.group=$group" : "";
        $sql = "SELECT max(policy.order) as max FROM policy $where";
        if (!$rs = & $conn->Execute($sql)) {
            die($conn->ErrorMsg());
        } else {
            return $rs->fields["max"] + 1;
        }
        return Policy::get_near_order($conn, $group);
    }
	
	function swap_simple_orders($conn, $ord_src, $ord_dst) {
		$query = OssimQuery("SELECT id,policy.order,policy.group FROM policy WHERE policy.order=$ord_src");
		if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            if (!$rs->EOF) {
				$id_src = $rs->fields['id'];
			}
		}
		$query = OssimQuery("SELECT id,policy.order,policy.group FROM policy WHERE policy.order=$ord_dst");
		if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            if (!$rs->EOF) {
				$id_dst = $rs->fields['id'];
			}
		}
		
		$sql = "UPDATE policy SET policy.order=$ord_dst WHERE id=$id_src";
		$conn->Execute($sql);
		$sql = "UPDATE policy SET policy.order=$ord_src WHERE id=$id_dst";
		$conn->Execute($sql);
	}
	
    function swap_orders($conn, $ord_src, $ord_dst, $group = - 1, $change = "src") {
        $upt = array();
        if ($ord_src == $ord_dst) return;
        if ($ord_src > $ord_dst) {
            $tmp = $ord_src;
            $ord_src = $ord_dst;
            $ord_dst = $tmp;
        }
        $query = OssimQuery("SELECT id,policy.order,policy.group FROM policy WHERE policy.order>=$ord_src AND policy.order<=$ord_dst ORDER BY policy.order");
		if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                $id = $rs->fields["id"];
                $ord = $rs->fields["order"];
                $new_ord = ($ord == $ord_dst) ? $ord_src : $ord + 1;
                if ($change == "src" && $ord == $ord_src && $group >= 0) $new_ord.= ", policy.group=$group";
                if ($change == "dst" && $ord == $ord_dst && $group >= 0) $new_ord.= ", policy.group=$group";
                $upt[] = "UPDATE policy SET policy.order=$new_ord WHERE id=$id";
				//echo "UPDATE policy SET policy.order=$new_ord WHERE id=$id<br>";
				$rs->MoveNext();
            }
        }
        foreach($upt as $sql) $conn->Execute($sql); //launch updating
        
    }
    function change_group($conn, $ord, $group) {
        $query = OssimQuery("UPDATE policy SET policy.group=$group WHERE policy.order=$ord");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        }
    }
    function reorder($conn, $ord, $type = "insert") {
        if ($type == "insert") $conn->Execute("UPDATE policy SET policy.order=policy.order+1 WHERE policy.order>=$ord");
        elseif ($type == "delete") $conn->Execute("UPDATE policy SET policy.order=policy.order-1 WHERE policy.order>$ord");
    }
    function get_group_from_order($conn, $ord) {
        $query = OssimQuery("SELECT policy.group FROM policy WHERE policy.order=$ord");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            if (!$rs->EOF) return $rs->fields["group"];
        }
        return 0;
    }
    function get_order_from_group($conn, $group, $src_group, $type = "max") {
        $query = OssimQuery("SELECT " . $type . "(policy.order) as neworder FROM policy WHERE policy.group=$group");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            if (!$rs->EOF) {
                if (is_numeric($rs->fields["neworder"])) return $rs->fields["neworder"];
            }
        }
        // Order not found, search near by next policy group
        $query = OssimQuery("SELECT policy_group.order FROM policy_group WHERE group_id=$group");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            if (!$rs->EOF) $group_order = $rs->fields["order"];
        }
        // Order not found, search near by next policy group (SOURCE)
        $query = OssimQuery("SELECT policy_group.order FROM policy_group WHERE group_id=$src_group");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            if (!$rs->EOF) $group_order_src = $rs->fields["order"];
        }
        return Policy::get_near_order($conn, $group, $group_order, $group_order_src);
    }
    function get_near_order($conn, $group, $group_order, $group_order_src) {
        $last = 0;
        //$query = OssimQuery("SELECT p.id,p.order as orden,g.order as gorder FROM policy p,policy_group g WHERE p.group=g.group_id ORDER BY orden");
		$query = OssimQuery("SELECT id,policy.order as orden,'0' as gorder FROM policy WHERE policy.group=0 UNION SELECT p.id,p.order as orden,g.order as gorder FROM policy p,policy_group g WHERE p.group=g.group_id ORDER BY orden");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            if (!$rs->EOF) $last = $rs->fields["orden"];
            while (!$rs->EOF) {
				//echo "\nlast en el bucle: ".$last."\n";
				//echo "group_order: ".$group_order."\n";
				//echo "group_order_src: ".$group_order_src."\n\n";
                if ($rs->fields["gorder"] > $group_order) return ($group_order > $group_order_src) ? $last : $rs->fields["orden"];
                $last = $rs->fields["orden"];
                $rs->MoveNext();
            }
        }
        return $last;
    }
    function insert($conn, $priority, $active, $group, $order, $begin_hour, $end_hour, $begin_day, $end_day, $descr, $host_source_list, $host_group_source_list, $host_dest_list, $host_group_dest_list, $net_source_list, $net_group_source_list, $net_dest_list, $net_group_dest_list, $port_list, $plug_groups, $sensor_list, $target_list, $correlate, $cross_correlate, $store, $qualify, $resend_alarm, $resend_event, $sign, $sem, $sim, $id = null) {
        $descr = Util::htmlentities($descr);
        $priority = intval($priority);
        $correlate = intval($correlate);
        $cross_correlate = intval($cross_correlate);
        $store = intval($store);
        $qualify = intval($qualify);
        $resend_alarm = intval($resend_alarm);
        $resend_event = intval($resend_event);
        if (!$id) {
            $id = $conn->GenID('policy_seq');
        }
        // re-order before insert
        Policy::reorder($conn, $order, "insert");
        $sql = "INSERT INTO policy (`id`, `priority`, `active`, `group`, `order`, `descr`) VALUES (?, ?, ?, ?, ?, ?)";
        $params = array(
            $id,
            $priority,
            $active,
            $group,
            $order,
            $descr
        );
        if (!$conn->Execute($sql, $params)) {
            die($conn->ErrorMsg());
        }
		//echo $sql." ($id,$priority,$active,$group,$order,$descr)<br>";
        Policy_host_reference::insert($conn, $host_source_list, 'source', $id);
        Policy_host_group_reference::insert($conn, $host_group_source_list, 'source', $id);
        Policy_host_reference::insert($conn, $host_dest_list, 'dest', $id);
        Policy_host_group_reference::insert($conn, $host_group_dest_list, 'dest', $id);
        Policy_net_reference::insert($conn, $net_source_list, 'source', $id);
        Policy_net_group_reference::insert($conn, $net_group_source_list, 'source', $id);
        Policy_net_reference::insert($conn, $net_dest_list, 'dest', $id);
        Policy_net_group_reference::insert($conn, $net_group_dest_list, 'dest', $id);
        Policy_port_reference::insert($conn, $port_list, $id);
        $conn->Execute("DELETE FROM policy_plugin_group_reference WHERE policy_id=$id");
        $sql = "INSERT INTO policy_plugin_group_reference
                (policy_id, group_id) VALUES (?, ?)";
        foreach($plug_groups as $group_id) {
            $conn->Execute($sql, array(
                $id,
                $group_id
            ));
        }
        Policy_sensor_reference::insert($conn, $sensor_list, $id);
        Policy_role_reference::insert($conn, $correlate, $cross_correlate, $store, $qualify, $resend_alarm, $resend_event, $id, $sign, $sem, $sim);
        Policy_target_reference::insert($conn, $target_list, $id);
        Policy_time::insert($conn, $begin_hour, $end_hour, $begin_day, $end_day, $id);
        // don't log if it is an update
        $bt = debug_backtrace();
        if (!isset($bt[1]) || (isset($bt[1])) && ($bt[1]["function"] != "update")) {
            $infolog = array(
                $id,
                "(" . $descr . ")"
            );
            Log_action::log(46, $infolog);
        }
        WebIndicator::set_on("Reload_policies");
        WebIndicator::set_on("ReloadPolicy");
        
		//echo "Policy.inc insertados $id<br>";
		
		return $id;
    }
    function update($conn, $id, $priority, $active, $group, $order, $begin_hour, $end_hour, $begin_day, $end_day, $descr, $host_source_list, $host_group_source_list, $host_dest_list, $host_group_dest_list, $net_source_list, $net_group_source_list, $net_dest_list, $net_group_dest_list, $port_list, $plug_groups, $sensor_list, $target_list, $correlate, $cross_correlate, $store, $qualify, $resend_alarm, $resend_event, $sign, $sem, $sim) {
        $descr = Util::htmlentities($descr);
        $id = intval($id);
        $correlate = intval($correlate);
        $cross_correlate = intval($cross_correlate);
        $store = intval($store);
        $qualify = intval($qualify);
        $resend_alarm = intval($resend_alarm);
        $resend_event = intval($resend_event);
        Policy::delete($conn, $id);
        Policy::insert($conn, $priority, $active, $group, $order, $begin_hour, $end_hour, $begin_day, $end_day, $descr, $host_source_list, $host_group_source_list, $host_dest_list, $host_group_dest_list, $net_source_list, $net_group_source_list, $net_dest_list, $net_group_dest_list, $port_list, $plug_groups, $sensor_list, $target_list, $correlate, $cross_correlate, $store, $qualify, $resend_alarm, $resend_event, $sign, $sem, $sim, $id);
        $infolog = array(
            $id,
            "(" . $descr . ")"
        );
        Log_action::log(48, $infolog);
        WebIndicator::set_on("Reload_policies");
        WebIndicator::set_on("ReloadPolicy");
    }
    function delete($conn, $id) {
        $id = intval($id);
        $policy = Policy::get_list($conn, "where id=$id");
        if (count($policy) != 1) return;
        $current_order = $policy[0]->get_order();
        $descr = $policy[0]->get_descr();
        Policy_host_reference::delete($conn, $id);
        Policy_net_reference::delete($conn, $id);
        Policy_host_group_reference::delete($conn, $id);
        Policy_net_group_reference::delete($conn, $id);
        Policy_port_reference::delete($conn, $id);
        Policy_sensor_reference::delete($conn, $id);
        Policy_target_reference::delete($conn, $id);
        Policy_role_reference::delete($conn, $id);
        Policy_time::delete($conn, $id);
		Policy_action::delete($conn, $id);
        $sql = "DELETE FROM policy_plugin_group_reference WHERE policy_id = ?";
        $params = array(
            $id
        );
        $conn->Execute($sql, $params);
        $sql = "DELETE FROM policy WHERE id = ?";
        $conn->Execute($sql, $params);
        // re-order after delete
        Policy::reorder($conn, $current_order, "delete");
        // don't log if it is an update'
        $bt = debug_backtrace();
        if (!isset($bt[1]) || (isset($bt[1])) && ($bt[1]["function"] != "update")) {
            $infolog = array(
                $id,
                "(" . $descr . ")"
            );
            Log_action::log(47, $infolog);
        }
        WebIndicator::set_on("Reload_policies");
        WebIndicator::set_on("ReloadPolicy");
    }
}
?>
