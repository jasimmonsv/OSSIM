<?php
/**
* Class and Function List:
* Function list:
* - GetSignatureName()
* - GetSignaturePriority()
* - GetSignatureID()
* - GetRefSystemName()
* - GetSingleSignatureReference()
* - LoadSignatureReference()
* - GetSignatureReference()
* - BuildSigLookup()
* - BuildSigByID()
* - GetSigClassID()
* - GetSigClassName()
* - GetTagTriger()
* Classes list:
*/
/*******************************************************************************
** OSSIM Forensics Console
** Copyright (C) 2009 OSSIM/AlienVault
** Copyright (C) 2004 BASE Project Team
** Copyright (C) 2000 Carnegie Mellon University
**
** (see the file 'base_main.php' for license details)
**
** Built upon work by Roman Danyliw <rdd@cert.org>, <roman@danyliw.com>
** Built upon work by the BASE Project Team <kjohnson@secureideas.net>
**/
require_once 'classes/Util.inc';
defined('_BASE_INC') or die('Accessing this file directly is not allowed.');
/*
function GetSignatureName($sig_id, $db) {
    if (!isset($_SESSION['acid_sig_names'])) $_SESSION['acid_sig_names'] = array();
    if (isset($_SESSION['acid_sig_names'][$sig_id])) {
        return $_SESSION['acid_sig_names'][$sig_id];
    }
    $name = "";
    $temp_sql = "SELECT sig_name FROM signature WHERE sig_id='" . addslashes($sig_id) . "'";
    $tmp_result = $db->baseExecute($temp_sql);
    if ($tmp_result) {
        $myrow = $tmp_result->baseFetchRow();
        $name = $myrow[0];
        $tmp_result->baseFreeRows();
    } else $name = "[" . gettext("SigName unknown") . "]";
    $_SESSION['acid_sig_names'][$sig_id] = Util::htmlentities($name, ENT_COMPAT, "UTF-8");
    return $name;
}
function GetSignaturePriority($sig_id, $db) {
    $priority = "";
    $temp_sql = "SELECT sig_priority FROM signature WHERE sig_id='" . addslashes($sig_id) . "'";
    $tmp_result = $db->baseExecute($temp_sql);
    if ($tmp_result) {
        $myrow = $tmp_result->baseFetchRow();
        $priority = $myrow[0];
        $tmp_result->baseFreeRows();
    } else $priority = "[" . gettext("SigPriority unknown") . "]";
    return $priority;
}
function GetSignatureID($sig_id, $db) {
    $id = "";
    if ($sig_id == "") return $id;
    $temp_sql = "SELECT sig_id FROM signature WHERE sig_name='" . addslashes($sig_id) . "'";
    if ($db->DB_type == "mssql") $temp_sql = "SELECT sig_id FROM signature WHERE sig_name LIKE '" . MssqlKludgeValue($sig_id) . "' ";
    $tmp_result = $db->baseExecute($temp_sql);
    if ($tmp_result) {
        $myrow = $tmp_result->baseFetchRow();
        $id = $myrow[0];
        $tmp_result->baseFreeRows();
    }
    return $id;
}*/
function GetRefSystemName($ref_system_id, $db) {
    if ($ref_system_id == "") return "";
    $ref_system_name = "";
    $tmp_sql = "SELECT ref_system_name FROM reference_system WHERE ref_system_id='" . addslashes($ref_system_id) . "'";
    $tmp_result = $db->baseExecute($tmp_sql);
    if ($tmp_result) {
        $myrow = $tmp_result->baseFetchRow();
        $ref_system_name = $myrow[0];
        $tmp_result->baseFreeRows();
    }
    return trim($ref_system_name);
}
function GetSingleSignatureReference($ref_system, $ref_tag, $style) {
    $tmp_ref_system_name = strtolower($ref_system);
    
	if (in_array($tmp_ref_system_name, array_keys($GLOBALS['external_sig_link']))) {
        if ($style == 1) return "<FONT SIZE=-1>[" . "<A HREF=\"" . $GLOBALS['external_sig_link'][$tmp_ref_system_name][0] . $ref_tag . $GLOBALS['external_sig_link'][$tmp_ref_system_name][1] . "\" " . "TARGET=\"_ACID_ALERT_DESC\">" . $ref_system . "</A>" . "]</FONT> ";
        else if ($style == 2) return "[" . $ref_system . "/$ref_tag] ";
    } else {
        return $ref_system;
    }
}
function LoadSignatureReference($db) {
    /* Cache Sig refs */
    $sig_ref_array = array();
    $temp_sql = "SELECT sig_id, ref_seq, ref_id FROM sig_reference";
    $tmp_sig_ref = $db->baseExecute($temp_sql);
    $num_rows = $tmp_sig_ref->baseRecordCount();
    for ($i = 0; $i < $num_rows; $i++) {
        $myrow = $tmp_sig_ref->baseFetchRow();
        if (!isset($sig_ref_array[$myrow[0]])) {
            $sig_ref_array[$myrow[0]] = array();
        }
        $sig_ref_array[$myrow[0]][$myrow[1]] = $myrow[2];
    }
    if (!isset($_SESSION['acid_sig_refs'])) $_SESSION['acid_sig_refs'] = $sig_ref_array;
}
/*
function GetSignatureReference($sig_id, $db, $style) {
    $ref = "";
    GLOBAL $BASE_display_sig_links;
    if (!isset($_SESSION['acid_sig_refs'])) {
        LoadSignatureReference($db);
    }
    if ($BASE_display_sig_links == 1) {
        $num_references = $tmp_sig_ref = count($_SESSION['acid_sig_refs'][$sig_id]);
        if ($tmp_sig_ref) {
            for ($i = 0; $i < $num_references; $i++) {
                //  $mysig_ref = $tmp_sig_ref->baseFetchRow();
                $mysig_ref = $_SESSION['acid_sig_refs'][$sig_di][$i];
                if ($ref_id != "") {
                    $temp_sql = "SELECT ref_system_id, ref_tag FROM reference WHERE ref_id='" . addslashes($mysig_ref[1]) . "'";
                    $tmp_ref_tag = $db->baseExecute($temp_sql);
                } else {
                    $tmp_ref_tag = NULL;
                }
                if ($tmp_ref_tag) {
                    $myrow = $tmp_ref_tag->baseFetchRow();
                    $ref_tag = $myrow[1];
                    $ref_system = GetRefSystemName($myrow[0], $db);
                }
                $ref = $ref . GetSingleSignatureReference($ref_system, $ref_tag, $style);
                if ($ref_system == "cve") $ref = $ref . GetSingleSignatureReference("icat", $ref_tag, $style);
                if ($ref_id != "") {
                    $tmp_ref_tag->baseFreeRows();
                }
            }
            //         $tmp_sig_ref->baseFreeRows();
            
        }
        if (!isset($_SESSION['acid_sig_sids'])) $_SESSION['acid_sig_sids'] = array();
        if (!isset($_SESSION['acid_sig_gids'])) $_SESSION['acid_sig_gids'] = array();
        if (isset($_SESSION['acid_sig_gids'][$sig_id]) && isset($_SESSION['acid_sig_sids'][$sig_id])) {
            $sig_gid = $_SESSION['acid_sig_gids'][$sig_id];
            $sig_sid = $_SESSION['acid_sig_sids'][$sig_id];
        } else {
            if ($db->baseGetDBversion() >= 103) {
                if ($db->baseGetDBversion() >= 107) $tmp_sql = "SELECT sig_sid, sig_gid FROM signature WHERE sig_id='" . addslashes($sig_id) . "'";
                else $tmp_sql = "SELECT sig_sid FROM signature WHERE sig_id='" . addslashes($sig_id) . "'";
                $tmp_sig_sid = $db->baseExecute($tmp_sql);
                if ($tmp_sig_sid) {
                    $myrow = $tmp_sig_sid->baseFetchRow();
                    $sig_sid = $myrow[0];
                    if ($db->baseGetDBversion() >= 107) $sig_gid = $myrow[1];
                }
            } else $sig_sid = "";
            $sig_gid = "";
        }
        if ($sig_sid == "") $sig_sid = " ";
        if ($sig_gid == "") $sig_gid = " ";
        $_SESSION['acid_sig_gids'][$sig_id] = $sig_gid;
        $_SESSION['acid_sig_sids'][$sig_id] = $sig_sid;
        $href = "";
        if ((is_numeric($sig_id)) && ($sig_sid >= 103)) {
            $ref = $ref . GetSingleSignatureReference("local", $sig_sid, $style);
        }
        if ($sig_sid != "") {
            if ($db->baseGetDBversion() >= 107)
            if ($sig_gid != "") {
                $ref = $ref . GetSingleSignatureReference("snort", $sig_gid . ':' . $sig_sid, $style);
            } else {
                $ref = $ref . GetSingleSignatureReference("snort", $sig_sid, $style);
            } else $ref = $ref . GetSingleSignatureReference("snort", $sig_sid, $style);
        }
    }
    return $ref;
}*/
function BuildSigLookup($signature, $style)
/* - Paul Harrington <paul@pizza.org> : reference URL links
* - Michael Bell <michael.bell@web.de> : links for IP address in spp_portscan alerts
*/ {
    if ($style == 2) return $signature;
    /* create hyperlinks for references */
    $pattern = array(
        "/(IDS)(\d+)/",
        "/(IDS)(0+)(\d+)/",
        "/BUGTRAQ ID (\d+)/",
        "/MCAFEE ID (\d+)/",
        "/(CVE-\d+-\d+)/"
    );
    $replace = array(
        "<A HREF=\"http://www.whitehats.com/\\1/\\2\" TARGET=\"_ACID_ALERT_DESC\">\\1\\2</A>",
        "<A HREF=\"http://www.whitehats.com/\\1/\\3\" TARGET=\"_ACID_ALERT_DESC\">\\1\\2\\3</A>",
        "<A HREF=\"" . $GLOBALS['external_sig_link']['bugtraq'][0] . "\\1\" TARGET=\"_ACID_ALERT_DESC\">BUGTRAQ ID \\1</A>",
        "<A HREF=\"" . $GLOBALS['external_sig_link']['mcafee'][0] . "\\1\" TARGET=\"_ACID_ALERT_DESC\">MCAFEE ID \\1</A>",
        "<A HREF=\"" . $GLOBALS['external_sig_link']['cve'][0] . "\\1\" TARGET=\"_ACID_ALERT_DESC\">\\1</A>"
    );
    $msg = preg_replace($pattern, $replace, $signature);
    /* fixup portscan message strings */
    if (stristr($msg, "spp_portscan")) {
        /* replace "spp_portscan: portscan status" => "spp_portscan"  */
        $msg = preg_replace("/spp_portscan: portscan status/", "spp_portscan", $msg);
        /* replace "spp_portscan: PORTSCAN DETECTED" => "spp_portscan detected" */
        $msg = preg_replace("/spp_portscan: PORTSCAN DETECTED/", "spp_portscan detected", $msg);
        /* create hyperlink for IP addresses in portscan alerts */
        $msg = preg_replace("/([0-9]*\.[0-9]*\.[0-9]*\.[0-9]*)/", "<A HREF=\"base_stat_ipaddr.php?ip=\\1&amp;netmask=32\">\\1</A>", $msg);
    }
    return $msg;
}
function BuildSigByID($sig_id, $sid, $cid, $db, $style = 1)
/*
* sig_id: DB schema dependent
*         - < v100: a text string of the signature
*         - > v100: an ID (key) of a signature
* db    : database handle
* style : how should the signature be returned?
*         - 1: (default) HTML
*         - 2: text
*
* RETURNS: a formatted signature and the associated references
*/ {
	if ($sid=="" || $cid=="") return ""; // GetSignatureName($sig_id, $db);

    if ($db->baseGetDBversion() >= 100) {
        /* Catch the odd circumstance where $sig_id is still an alert text string
        * despite using normalized signature as of DB version 100.
        */
        if (!is_numeric($sig_id)) return $sig_id;
        $sig_name = ""; //$sig_name = GetSignatureName($sig_id, $db);
        if ($sig_name != "") {
            return GetSignatureReferences($sid, $cid, $db) . " " . BuildSigLookup($sig_name, $style);
        } else {
            if ($style == 1) return "($sig_id)<I>" . gettext("SigName unknown") . "</I>";
            else return "($sig_id) " . gettext("SigName unknown");
        }
    } else return BuildSigLookup($sig_id, $style);
}

function GetSignatureReferences($sid, $cid, $db) {
	$external_sig_link = $GLOBALS['external_sig_link'];
	$str = "";
	$temp_sql = "SELECT r.ref_tag,rs.ref_system_name,rs.url,rs.icon,rs.ref_system_id FROM sig_reference s, reference r, reference_system rs,ossim_event o WHERE rs.ref_system_id=r.ref_system_id AND r.ref_id=s.ref_id AND s.plugin_id=o.plugin_id and s.plugin_sid=o.plugin_sid and o.sid=$sid and o.cid=$cid";
	$tmp_result = $db->baseExecute($temp_sql);
	if ($tmp_result) {
		while ($row = $tmp_result->baseFetchRow()) {
			$url_src = $row["url"];
			$link = "";
			$row["ref_tag"] = trim($row["ref_tag"]);
			$row["ref_system_name"] = strtolower(trim($row["ref_system_name"]));
			if ($url_src != "") {
				$url = str_replace("%value%",rawurlencode($row["ref_tag"]),$url_src);
				$target = (preg_match("/^http/",$url)) ? "_blank" : "main";
				$anchor = ($row["icon"] != "") ? "<img src='manage_references_icon.php?id=".$row['ref_system_id']."' alt='".$row['ref_system_name']."' title='".$row['ref_system_name']."' border='0'>" : "[".$row["ref_system_name"]."]";
				$link = "<a href='".urldecode($url)."' target='$target'>".$anchor."</a>";
			}
			/*
			if (isset($external_sig_link[$row["ref_system_name"]])) {
				$url = $external_sig_link[$row["ref_system_name"]][0].rawurlencode($row["ref_tag"]).$external_sig_link[$row["ref_system_name"]][1];
				$target = ($external_sig_link[$row["ref_system_name"]][2]!="") ? $external_sig_link[$row["ref_system_name"]][2] : "_blank";
				$link = "<a href='$url' target='$target'>".$row["ref_system_name"]."</a>";
			}*/
			if ($link!="") $str .= " ".$link;
		}
		$tmp_result->baseFreeRows();
	}
	return $str."##";
}

function BuildSigByPlugin($plugin_id, $plugin_sid, $db) {
    $sig_name = GetOssimSignatureName($plugin_id, $plugin_sid, $db);
    if ($sig_name != "") {
        return GetOssimSignatureReferences($plugin_id, $plugin_sid, $db)." ".$sig_name;
    } else {
        return "($plugin_id,$plugin_sid) " . gettext("SigName unknown");
    }
}

function TranslateSignature($name, $arr) {
    $translations = array(
        '/SRC_IP/' => 'baseLong2IP($arr["ip_src"])',
        "/DST_IP/" => 'baseLong2IP($arr["ip_dst"])',
        "/SRC_PORT/" => '$arr["layer4_sport"]',
        "/DST_PORT/" => '$arr["layer4_dport"]',
        "/PROTOCOL/" => 'IPProto2str($arr["ip_proto"])',
        "/PLUGIN_ID/" => '$arr["plugin_id"]',
        "/PLUGIN_SID/" => '$arr["plugin_sid"]',
        "/FILENAME/" => 'htmlspecialchars($arr["filename"],ENT_QUOTES)',
        "/USERNAME/" => 'htmlspecialchars($arr["username"],ENT_QUOTES)',
        "/USERDATA1/" => 'htmlspecialchars($arr["userdata1"],ENT_QUOTES)',
        "/USERDATA2/" => 'htmlspecialchars($arr["userdata2"],ENT_QUOTES)',
        "/USERDATA3/" => 'htmlspecialchars($arr["userdata3"],ENT_QUOTES)',
        "/USERDATA4/" => 'htmlspecialchars($arr["userdata4"],ENT_QUOTES)',
        "/USERDATA5/" => 'htmlspecialchars($arr["userdata5"],ENT_QUOTES)',
        "/USERDATA6/" => 'htmlspecialchars($arr["userdata6"],ENT_QUOTES)',
        "/USERDATA7/" => 'htmlspecialchars($arr["userdata7"],ENT_QUOTES)',
        "/USERDATA8/" => 'htmlspecialchars($arr["userdata8"],ENT_QUOTES)',
        "/USERDATA9/" => 'htmlspecialchars($arr["userdata9"],ENT_QUOTES)'
    );
    foreach($translations as $k => $replacement) {
        $pattern = '$name = preg_replace("' . $k . '", %s, $name);';
        $str = sprintf($pattern, $replacement);
        eval($str);
    }
    return $name;
}

function GetPluginNameDesc($plugin_id, $db) {
    if (!isset($_SESSION['acid_plugin_namedesc'])) $_SESSION['acid_plugin_namedesc'] = array();
    if (!isset($_SESSION['acid_plugin_namedesc'][$plugin_id])) {       
        $name = "";
        $temp_sql = "SELECT name,description FROM ossim.plugin WHERE id=$plugin_id";
        $tmp_result = $db->baseExecute($temp_sql);
        if ($tmp_result) {
            $myrow = $tmp_result->baseFetchRow();
            $name = $myrow[0];
            $desc = $myrow[1];
            $tmp_result->baseFreeRows();
        }
        $_SESSION['acid_plugin_namedesc'][$plugin_id] = Util::htmlentities($name.";".$desc, ENT_COMPAT, "UTF-8");
    }
    return explode(";",$_SESSION['acid_plugin_namedesc'][$plugin_id]);
}

function GetOssimSignatureName($plugin_id, $plugin_sid, $db) {
    if (!isset($_SESSION['acid_sig_names'])) $_SESSION['acid_sig_names'] = array();
    if (isset($_SESSION['acid_sig_names'][$plugin_id." ".$plugin_sid])) {
        return $_SESSION['acid_sig_names'][$plugin_id." ".$plugin_sid];
    }
    if ($plugin_id=="" || $plugin_sid=="") return "";
    $name = "";
    $temp_sql = "SELECT name FROM ossim.plugin_sid WHERE plugin_id=$plugin_id AND sid=$plugin_sid";
    $tmp_result = $db->baseExecute($temp_sql);
    if ($tmp_result) {
        $myrow = $tmp_result->baseFetchRow();
        $name = $myrow[0];
        $tmp_result->baseFreeRows();
    }
    $_SESSION['acid_sig_names'][$plugin_id." ".$plugin_sid] = Util::htmlentities($name, ENT_COMPAT, "UTF-8");
    return $name;
}

function GetOssimSignatureReferences($plugin_id, $plugin_sid, $db) {
    $external_sig_link = $GLOBALS['external_sig_link'];
    $str = "";
    $temp_sql = "SELECT r.ref_tag,rs.ref_system_name,rs.url,rs.icon,rs.ref_system_id FROM sig_reference s, reference r, reference_system rs WHERE rs.ref_system_id=r.ref_system_id AND r.ref_id=s.ref_id AND s.plugin_id=$plugin_id and s.plugin_sid=$plugin_sid";
    //print_r($temp_sql);
    $tmp_result = $db->baseExecute($temp_sql);
    if ($tmp_result) {
        while ($row = $tmp_result->baseFetchRow()) {
            $url_src = $row["url"];
            $link = "";
            $row["ref_tag"] = trim($row["ref_tag"]);
            $row["ref_system_name"] = strtolower(trim($row["ref_system_name"]));
            if ($url_src != "") {
            	if (preg_match("/^http/",$row["ref_tag"])) $url_src = str_replace("http://","",$url_src);
                $url = str_replace("%value%",rawurlencode($row["ref_tag"]),$url_src);
                $target = (preg_match("/^http/",$url)) ? "_blank" : "main";
                $anchor = ($row["icon"] != "") ? "<img src='manage_references_icon.php?id=".$row['ref_system_id']."' alt='".$row['ref_system_name']."' title='".$row['ref_system_name']."' border='0'>" : "[".$row["ref_system_name"]."]";
                $link = "<a href='".urldecode($url)."' target='$target'>".$anchor."</a>";
            }
            if ($link!="") $str .= " ".$link;
        }
        $tmp_result->baseFreeRows();
    }
    return $str."##";
}
/*
function GetSigClassID($sig_id, $db) {
    if (!isset($_SESSION['acid_sig_class_id'])) $_SESSION['acid_sig_class_id'] = array();
    if (isset($_SESSION['acid_sig_class_id'][$sig_id])) {
        return $_SESSION['acid_sig_class_id'][$sig_id];
    }
    $sql = "SELECT sig_class_id FROM signature " . "WHERE sig_id = '$sig_id'";
    $result = $db->baseExecute($sql);
    $row = $result->baseFetchRow();
    $_SESSION['acid_sig_class_id'][$sig_id] = $row[0];
    return $row[0];
}*/
function GetSigClassName($class_id, $db) {
    if ($class_id == "") return "<I>unclassified</I>";
    if (!isset($_SESSION['acid_sig_class_name'])) $_SESSION['acid_sig_class_name'] = array();
    if (isset($_SESSION['acid_sig_class_name'][$sig_id])) {
        return $_SESSION['acid_sig_class_name'][$sig_id];
    }
    $sql = "SELECT sig_class_name FROM sig_class " . "WHERE sig_class_id = '$class_id'";
    $result = $db->baseExecute($sql);
    $row = $result->baseFetchRow();
    if ($row == "") {
        $_SESSION['acid_sig_class_name'][$sig_id] = "<I>" . gettext("unclassified") . "</I>";
        return "<I>" . gettext("unclassified") . "</I>";
    } else {
        $_SESSION['acid_sig_class_name'][$sig_id] = Util::htmlentities($row[0], ENT_COMPAT, "UTF-8");
        return $row[0];
    }
}
/*
function GetTagTriger($current_sig, $db, $sid, $cid) {
    if (stristr($current_sig, "Tagged Packet")) {
        if (in_array("reference", $db->DB->MetaColumnNames('event'))) {
            $sql2 = "SELECT signature, reference FROM acid_event ";
            $sql2.= "WHERE sid='" . $sid . "' AND cid='" . $cid . "'";
            $result2 = $db->baseExecute($sql2);
            $row2 = $result2->baseFetchRow();
            $result2->baseFreeRows();
            $event_sig = $row2[0];
            $event_reference = $row2[1];
            if (($event_sig == "") || ($event_reference == "")) return $current_sig;
            $sql2 = "SELECT signature, sid, cid FROM acid_event WHERE sid='" . $sid . "' ";
            $sql2.= "AND reference='" . $event_reference . "' AND NOT signature='" . $event_sig . "'";
            $result2 = $db->baseExecute($sql2);
            $row2 = $result2->baseFetchRow();
            $result2->baseFreeRows();
            $triger_sig = $row2[0];
            $triger_sid = $row2[1];
            $triger_cid = $row2[2];
            if ($triger_sig != "") {
                $sql2 = "SELECT sig_name FROM signature ";
                $sql2.= "WHERE sig_id='" . $triger_sig . "'";
                $result2 = $db->baseExecute($sql2);
                $row2 = $result2->baseFetchRow();
                $result2->baseFreeRows();
                $triger_sig_name = $row2[0];
                if ($triger_sig_name != "") {
                    $current_sig.= " <i>(<a href=\"base_qry_alert.php?submit=" . rawurlencode("#(0-" . $triger_sid . "-" . $triger_cid . ")") . "\">";
                    $current_sig.= "#(" . $triger_sid . "-" . $triger_cid . "</a>) " . $triger_sig_name . ")</i>";
                }
            }
        }
    }
    return $current_sig;
} */
?>
