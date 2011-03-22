<?php
/*****************************************************************************
*
*    License:
*
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
* - Compliance()
* - insert()
* - delete()
* - get_all()
*/
require_once 'classes/Security.inc';
require_once 'ossim_db.inc';
require_once 'classes/Log_action.inc';

class Compliance {
    var $descr;
	var $plugin_name;
	var $sid;
	var $targeted;
	var $untargeted;
	var $approach;
	var $exploration;
	var $penetration;
	var $generalmalware;
	var $imp_qos;
    var $imp_infleak;
	var $imp_lawful;
	var $imp_image;
	var $imp_financial;
	var $D;
	var $I;
	var $C;
	var $net_anomaly;

    function Compliance($descr, $plugin_name, $sid, $targeted, $untargeted, $approach, $exploration, $penetration, $generalmalware, $imp_qos, $imp_infleak, $imp_lawful, $imp_image, $imp_financial, $D, $I, $C, $net_anomaly) {
		$this->descr = $descr;
		$this->plugin_name = $plugin_name;
		$this->sid = $sid;
		$this->targeted = $targeted;
		$this->untargeted = $untargeted;
		$this->approach = $approach;
		$this->exploration = $exploration;
		$this->penetration = $penetration;
		$this->generalmalware = $generalmalware;
		$this->imp_qos = $imp_qos;
        $this->imp_infleak=$imp_infleak;
		$this->imp_lawful = $imp_lawful;
		$this->imp_image = $imp_image;
		$this->imp_financial = $imp_financial;
		$this->D = $D;
		$this->I = $I;
		$this->C = $C;
		$this->net_anomaly = $net_anomaly;
    }
	
	function get_descr() {
		return $this->descr;
	}
	function get_plugin_name() {
		return $this->plugin_name;
	}
	function get_sid() {
		return $this->sid;
	}
	function get_targeted() {
		return $this->targeted;
	}
	function get_untargeted() {
		return $this->untargeted;
	}
	function get_approach() {
		return $this->approach;
	}
	function get_penetration() {
		return $this->penetration;
	}
	function get_exploration() {
		return $this->exploration;
	}
	function get_generalmalware() {
		return $this->generalmalware;
	}
	function get_imp_qos() {
		return $this->imp_qos;
	}
	function get_imp_infleak() {
		return $this->imp_infleak;
	}
	function get_imp_lawful() {
		return $this->imp_lawful;
	}
	function get_imp_image() {
		return $this->imp_image;
	}
	function get_imp_financial() {
		return $this->imp_financial;
	}
	function get_D() {
		return $this->D;
	}
	function get_I() {
		return $this->I;
	}
	function get_C() {
		return $this->C;
	}
	function get_net_anomaly() {
		return $this->net_anomaly;
	}
	
    function update($conn, $descr, $sid, $targeted, $untargeted, $approach, $exploration, $penetration, $generalmalware, $imp_qos, $imp_infleak, $imp_lawful, $imp_image, $imp_financial, $D, $I, $C, $net_anomaly) {
		$sql = "INSERT INTO datawarehouse.category (sid,targeted,untargeted,approach,exploration,penetration,generalmalware,imp_qos,imp_infleak,imp_lawful,imp_image,imp_financial,D,I,C,net_anomaly)
                            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) 
                            ON DUPLICATE KEY UPDATE targeted=$targeted, untargeted=$untargeted, approach=$approach,
                            exploration=$exploration, penetration=$penetration, generalmalware=$generalmalware,
                            imp_qos=$imp_qos, imp_infleak=$imp_infleak, imp_lawful=$imp_lawful, imp_image=$imp_image,
                            imp_financial=$imp_financial, D=$D, I=$I, C=$C, net_anomaly=$net_anomaly";
		$params = array(
			$sid,
			$targeted,
			$untargeted,
			$approach,
			$exploration,
			$penetration,
			$generalmalware,
			$imp_qos,
			$imp_infleak,
			$imp_lawful,
			$imp_image,
			$imp_financial,
			$D,
			$I,
			$C,
			$net_anomaly
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
            exit;
        }
        $infolog = array($sid);
        Log_action::log(88, $infolog);
    }
    function delete($conn,$sid) {
		$sql = "DELETE FROM datawarehouse.category WHERE sid=?";
        $params = array(
            $sid
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
            exit;
        }
        $infolog = array($sid);
        Log_action::log(89, $infolog);
	}
	function get_category($conn,$str="") { 
        if(preg_match('/sid/', $str)) {
            preg_match("/.*(\d+).*/",$str,$found);
            $str = "and category.sid=".$found[1]; 
        }
        $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT category.*,ssi.descr, plugin_sid.name as plugin_name FROM datawarehouse.category 
                        left join datawarehouse.ssi on ssi.sid=category.sid
                        left join ossim.plugin_sid on category.sid=plugin_sid.sid
                        where plugin_sid.plugin_id=1505 and plugin_sid.name<>'' $str";
                        
        //echo $sql; 
        $ret = array();
        if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
            exit;
        }
        // Total rows
        $total = & $conn->Execute("SELECT FOUND_ROWS() as counter");
        $num_rows = 0;
        if (!$total->EOF) $num_rows = $total->fields['counter'];
        while (!$rs->EOF) {
            $ret[] = new Compliance($rs->fields["descr"],$rs->fields["plugin_name"],$rs->fields["sid"],$rs->fields["targeted"],
                                    $rs->fields["untargeted"],$rs->fields["approach"],$rs->fields["exploration"],$rs->fields["penetration"],$rs->fields["generalmalware"],
                                    $rs->fields["imp_qos"],$rs->fields["imp_infleak"],$rs->fields["imp_lawful"],$rs->fields["imp_image"],$rs->fields["imp_financial"],
                                    $rs->fields["D"],$rs->fields["I"],$rs->fields["C"],$rs->fields["net_anomaly"]);
            $rs->MoveNext();
        }
        //print_r($ret); 
        return array($ret,$num_rows);
    }
}

class ISO27001 {
	//var $x;
	function ISO27001 () {
	
	}
	
	function get_groups ($conn,$args="") {
		$tables = array(
			"A05_Security_Policy",
			"A06_IS_Organization",
			"A07_Asset_Mgnt",
			"A08_Human_Resources",
			"A09_Physical_security",
			"A10_Com_OP_Mgnt",
			"A11_Acces_control",
			"A12_IS_acquisition",
			"A13_IS_incident_mgnt",
			"A14_BCM",
			"A15_Compliance"
		);
		foreach ($tables as $table) {
			$sql = "SELECT * FROM ISO27001An.$table $args";
			if (!$rs = & $conn->Execute($sql)) {
				print $conn->ErrorMsg();
				exit;
			}
			while (!$rs->EOF) {
				if (preg_match("/^\<\-+/",$rs->fields['Security_controls'])) {
					$gr = trim($rs->fields['Ref']);
					$groups[$gr] = array("title" => $rs->fields['Security_controls'], "subgroups" => array());
				} else {
					$groups[$gr]['subgroups'][$rs->fields['Ref']] = array("Ref"=>$rs->fields['Ref'],"Security_controls"=>$rs->fields['Security_controls'],"Selected"=>$rs->fields['Selected'],"Excluded"=>$rs->fields['Excluded'],"Implemented"=>$rs->fields['Implemented'],"Justification"=>$rs->fields['Justification'],"SIDSS_Ref"=>$rs->fields['SIDSS_Ref'],"table"=>$table);
				}
				$rs->MoveNext();
			}
		}
		return $groups;
	}
	
	function update_sids ($conn,$table,$ref,$sids) {
		$sql = "UPDATE ISO27001An.$table SET SIDSS_Ref='$sids' WHERE Ref='$ref'";
		
		if (!$rs = & $conn->Execute($sql)) {
			print $conn->ErrorMsg();
			exit;
		}
	}
	
	function update_attr ($conn,$table,$ref,$attr) {
		$more = ($attr=="Selected") ? ",Excluded=NOT(Excluded)" : ",Selected=NOT(Selected)";
		$sql = "UPDATE ISO27001An.$table SET $attr=NOT($attr) $more WHERE Ref='$ref'";
		
		if (!$rs = & $conn->Execute($sql)) {
			print $conn->ErrorMsg();
			exit;
		}
	}
	
	function get_text ($conn,$table,$ref) {
		$sql = "SELECT Justification FROM ISO27001An.$table WHERE Ref='$ref'";
		
		if (!$rs = & $conn->Execute($sql)) {
			print $conn->ErrorMsg();
			exit;
		}
		
		return $rs->fields['Justification'];
	}
	
	function save_text ($conn,$table,$ref,$text) {
		$sql = "UPDATE ISO27001An.$table SET Justification=\"$text\" WHERE Ref='$ref'";
		
		if (!$rs = & $conn->Execute($sql)) {
			print $conn->ErrorMsg();
			exit;
		}
	}
}

class PCI {
	//var $x;
	function PCI () {
	
	}
	
	function get_groups ($conn,$args="") {
		$tables = array(
			"R01_FW_Config",
			"R02_Vendor_default",
			"R03_Stored_cardholder",
			"R04_Data_encryption",
			"R05_Antivirus",
			"R06_System_app",
			"R07_Access_control",
			"R08_UniqueID",
			"R09_Physical_Access",
			"R10_Monitoring",
			"R11_Security_test",
			"R12_IS_Policy"
		);		
		foreach ($tables as $table) {
			$sql = "SELECT * FROM PCI.$table $args";
			if (!$rs = & $conn->Execute($sql)) {
				print $conn->ErrorMsg();
				exit;
			}
			if (!$rs->EOF) {
				$title = str_replace("_"," ",$table);
				$gr = preg_replace("/\s.*/","",$title);
				$groups[$gr] = array("title" => $title, "subgroups" => array());
				while (!$rs->EOF) {
					$key = $rs->fields['x1'].$rs->fields['x2'].$rs->fields['x3'].$rs->fields['x4'];
					$ref = $rs->fields['x1'].".".$rs->fields['x2'];
					if ($rs->fields['x3'] != "") $ref .= ".".$rs->fields['x3'];
					if ($rs->fields['x4'] != "") $ref .= ".".$rs->fields['x4'];
					$groups[$gr]['subgroups'][$key] = array("Ref"=>$ref,"Security_controls"=>$rs->fields['Security_control'],"operational"=>$rs->fields['operational'],"not_operational"=>$rs->fields['not_operational'],"comments"=>$rs->fields['comments'],"SIDSS_Ref"=>$rs->fields['SIDSS_ref'],"testing_procedures"=>$rs->fields['testing_procedures'],"table"=>$table);
					$rs->MoveNext();
				}
			}
		}
		return $groups;
	}
	
	function update_sids ($conn,$table,$ref,$sids) {
		$sql = "UPDATE PCI.$table SET SIDSS_Ref='$sids' WHERE concat(x1,x2,x3,x4) = '$ref'";
		
		if (!$rs = & $conn->Execute($sql)) {
			print $conn->ErrorMsg();
			exit;
		}
	}
	
	function update_attr ($conn,$table,$ref) {
		// Null patch
		$sql = "UPDATE PCI.$table SET operational=0 WHERE concat(x1,x2,x3,x4) = '$ref' AND operational is NULL";
		if (!$rs = & $conn->Execute($sql)) {
			print $conn->ErrorMsg();
			exit;
		}
		
		$sql = "UPDATE PCI.$table SET not_operational=0 WHERE concat(x1,x2,x3,x4) = '$ref' AND not_operational is NULL";
		if (!$rs = & $conn->Execute($sql)) {
			print $conn->ErrorMsg();
			exit;
		}
		
		// Update
		$sql = "UPDATE PCI.$table SET operational=NOT(operational), not_operational=NOT(not_operational) WHERE concat(x1,x2,x3,x4) = '$ref'";
		
		if (!$rs = & $conn->Execute($sql)) {
			print $conn->ErrorMsg();
			exit;
		}
	}
	
	function get_text ($conn,$table,$ref) {
		$sql = "SELECT comments FROM PCI.$table WHERE concat(x1,x2,x3,x4)='$ref'";
			
		if (!$rs = & $conn->Execute($sql)) {
			print $conn->ErrorMsg();
			exit;
		}
		
		return $rs->fields['comments'];
	}
	
	function save_text ($conn,$table,$ref,$text) {
		$sql = "UPDATE PCI.$table SET comments=\"$text\" WHERE concat(x1,x2,x3,x4)='$ref'";
		
		if (!$rs = & $conn->Execute($sql)) {
			print $conn->ErrorMsg();
			exit;
		}
	}
}
?>
