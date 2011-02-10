<?php
/**
* Class and Function List:
* Function list:
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
*/
require ("base_conf.php");
include ("$BASE_path/includes/base_constants.inc.php");
include ("$BASE_path/includes/base_include.inc.php");
include_once ("$BASE_path/includes/base_action.inc.php");
include_once ("$BASE_path/base_db_common.php");
include_once ("$BASE_path/base_common.php");
include_once ("$BASE_path/base_qry_common.php");
include_once ("$BASE_path/base_ag_common.php");
($debug_time_mode >= 1) ? $et = new EventTiming($debug_time_mode) : '';
$cs = new CriteriaState("base_ag_main.php");
$cs->ReadState();
$qs = new QueryState();
$submit = ImportHTTPVar("submit", VAR_ALPHA | VAR_SPACE, array(
    gettext("Delete Selected"),
    gettext("Delete ALL on Screen"),
    _ENTIREQUERY
));
$ag_action = ImportHTTPVar("ag_action", VAR_ALPHA | VAR_USCORE);
//$ag_id = ImportHTTPVar("ag_id", VAR_DIGIT);
$ag_id = filterSql(ImportHTTPVar("ag_id", VAR_DIGIT));
$ag_name = filterSql(ImportHTTPVar("ag_name"));
$ag_desc = filterSql(ImportHTTPVar("ag_desc"));
// Check role out and redirect if needed -- Kevin
$roleneeded = 10000;
$BUser = new BaseUser();
if (($BUser->hasRole($roleneeded) == 0) && ($Use_Auth_System == 1)) base_header("Location: " . $BASE_urlpath . "/index.php");
$page_title = gettext("Event Group (AG) Maintenance");
PrintBASESubHeader($page_title, $page_title, $cs->GetBackLink() , 1);
/* Connect to the Alert database */
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);
/* a browsing button was clicked */
if (is_numeric($submit)) {
    if ($debug_mode > 0) ErrorMessage("Browsing Clicked ($submit)");
    $qs->MoveView($submit);
    $ag_action = "view";
}
?>

<div style='margin:auto'>
 <a href="base_ag_main.php?ag_action=list"><?php
echo gettext("List All"); ?></a> | 
 <a href="base_ag_main.php?ag_action=create"><?php
echo gettext("Create"); ?></a> |
 <a href="base_ag_main.php?ag_action=view"><?php
echo gettext("View"); ?></a> |
 <a href="base_ag_main.php?ag_action=edit"><?php
echo gettext("Edit"); ?></a> |
 <a href="base_ag_main.php?ag_action=delete"><?php
echo gettext("Delete"); ?></a> |
 <a href="base_ag_main.php?ag_action=clear"><?php
echo gettext("Clear"); ?></a>
</div>
<hr />

<form name="PacketForm" id="PacketForm" action="base_ag_main.php" method="post">
<?php
if ($debug_mode == 1) {
    echo "
  <table border='1'>
    <tr>
      <td>ag_action</td>
      <td>submit</td>
      <td>ag_id</td>
    </tr>
    <tr><td>".htmlspecialchars($ag_action)."</td>
    <td>$submit</td>
    <td>".htmlspecialchars($ag_id)."</td>
  </tr>
  </table>
";
}
$qs->AddValidAction("del_alert");
//$qs->AddValidAction("email_alert");
//$qs->AddValidAction("email_alert2");
//$qs->AddValidAction("clear_alert");
$qs->AddValidActionOp(gettext("Delete Selected"));
$qs->AddValidActionOp(gettext("Delete ALL on Screen"));
$qs->AddValidActionOp(gettext("Delete Entire Query"));
$qs->SetActionSQL("SELECT ag_sid, ag_cid FROM acid_ag_alert WHERE ag_id='" . $ag_id . "'");
($debug_time_mode > 0) ? $et->Mark("Initialization") : '';
$qs->RunAction($submit, PAGE_QRY_AG, $db);
($debug_time_mode > 0) ? $et->Mark("Alert Action") : '';
switch ($ag_action) {
    case "create":
        echo '<h3>' . gettext("Create Group") . '</h3>';
        break;

    case "view":
        echo '<h3>' . gettext("View Group") . '</h3>';
        break;

    case ("edit" || "save"):
        echo '<h3>' . gettext("Edit Group") . '</h3>';
        break;

    case ("delete" || "delete_confirm"):
        echo '<h3>' . gettext("Delete Group") . '</h3>';
        break;

    case ("clear" || "clear_confirm"):
        echo '<h3>' . gettext("Clear Group") . '</h3>';
        break;

    case "list":
        echo '<h3>' . gettext("List Groups") . '</h3>';
        break;

    default:
        $ag_action = "list";
}
if ($submit != "") {
    if ($ag_action == "create") {
        $ag_id = CreateAG($db, $ag_name, $ag_desc);
        $ag_action = "view";
    } else if ($ag_action == "save") {
        $sql = "UPDATE acid_ag " . "SET ag_name='" . $ag_name . "', ag_desc='" . $ag_desc . "' " . "WHERE ag_id='" . $ag_id . "'";
        $db->baseExecute($sql, -1, -1, false);
        if ($db->baseErrorMessage() != "") {
            FatalError(gettext("Error updating the AG"));
        }
        $ag_action = "view";
    } else if ($ag_action == "delete_confirm") {
        /* Delete the packet list associated with the AG */
        $sql = "DELETE FROM acid_ag_alert WHERE ag_id='" . $ag_id . "'";
        $db->baseExecute($sql, -1, -1, false);
        if ($db->baseErrorMessage() != "") {
            FatalError(gettext("Error deleting packet list for the AG:") . " " . $sql);
        }
        /* Delete the AG */
        $sql = "DELETE FROM acid_ag WHERE ag_id='" . $ag_id . "'";
        $db->baseExecute($sql, -1, -1, false);
        if ($db->baseErrorMessage() != "") {
            FatalError(gettext("Error deleting the AG") . $sql);
        }
    } else if ($ag_action == "clear_confirm") {
        /* Delete the packet list associated with the AG */
        $sql = "DELETE FROM acid_ag_alert WHERE ag_id='" . $ag_id . "'";
        $db->baseExecute($sql, -1, -1, false);
        if ($db->baseErrorMessage() != "") {
            FatalError(gettext("Error deleting packet list for the AG:") . " " . $sql);
        }
        $ag_action = "view";
    }
    if ($ag_action == "delete_confirm") {
        ErrorMessage("<strong>" . gettext("DELETED successfully") . "</strong>");
        $ag_action = "view";
        $ag_name = $ag_desc = "<em>" . gettext("information deleted") . "</em>";
    } else {
        /* Re-Query the information to print the AG info out */
        if ($ag_id > 0) {
            $sql = "SELECT ag_id, ag_name, ag_desc FROM acid_ag WHERE ag_id='" . $ag_id . "'";
        } else {
            $sql = "SELECT ag_id, ag_name, ag_desc FROM acid_ag WHERE ag_name='" . $ag_name . "'";
        }
        $result = $db->baseExecute($sql, -1, -1, false);
        if ($db->baseErrorMessage() != "") {
            ErrorMessage(gettext("The entered search criteria is invalid.  Try again!"));
            $submit = "";
        } else if ($result->baseRecordCount() < 1) {
            ErrorMessage(gettext("No AG found with that criteria.") . $sql);
            $submit = "";
        } else {
            $myrow = $result->baseFetchRow();
            $ag_id = $myrow[0];
            $ag_name = $myrow[1];
            $ag_desc = $myrow[2];
        }
    }
}
if ($ag_action == "list") {
    $sql = "SELECT ag_id, ag_name, ag_desc FROM acid_ag";
    $result = $db->baseExecute($sql);
    $num = $result->baseRecordCount();
    if ($num < 1) {
        echo "<div style='margin:auto'><strong>" . gettext("There are no Event Groups") . "</strong></div>";
    } else {
        echo '
             <table border="1" style="border-spacing:0;padding:0" width="100%">
             <tr>
               <td class="plfieldhdr">' . gettext("ID") . '</td>
               <td class="plfieldhdr">' . gettext("Name") . '</td>
               <td class="plfieldhdr">' . gettext("# Events") . '</td>
               <td class="plfieldhdr">' . gettext("Description") . '</td>
               <td class="plfieldhdr">' . gettext("Actions") . '</td>
             </tr>';
        for ($i = 0; $i < $num; $i++) {
            $myrow = $result->baseFetchRow();
            /* count the number of alerts in the AG */
            $result2 = $db->baseExecute("SELECT count(ag_cid) FROM acid_ag_alert WHERE ag_id='" . $myrow[0] . "'");
            $myrow2 = $result2->baseFetchRow();
            $num_alerts = $myrow2[0];
            $result2->baseFreeRows();
            echo '<tr>
                    <td class="plfield">
                      <a href="base_ag_main.php?ag_action=view&amp;ag_id=' . htmlspecialchars($myrow[0]) . '&amp;submit=x">' . htmlspecialchars($myrow[0]) . '</a></td>
                      <td class="plfield">' . htmlspecialchars($myrow[1]) . '</TD>
                      <td class="plfield">' . $num_alerts . '</TD>
                      <td class="plfield">' . htmlspecialchars($myrow[2]) . '</TD>
                      <td class="plfield"> 
                        <a href="base_ag_main.php?ag_action=edit&amp;ag_id=' . urlencode($myrow[0]) . '&amp;submit=x">' . gettext("Edit") . '</a> |
                        <a href="base_ag_main.php?ag_action=delete&amp;ag_id=' . urlencode($myrow[0]) . '&amp;submit=x">' . gettext("Delete") . '</a> |
                        <a href="base_ag_main.php?ag_action=clear&amp;ag_id=' . urlencode($myrow[0]) . '&amp;submit=x">' . gettext("Clear") . '</a>
                      </td>
                  </tr>';
        }
        echo '</table>';
        $result->baseFreeRows();
    }
}
if ($ag_action != "list") {
    echo '<table width="100%" border="2" class="query">
          <tr>
           <td width="10%"><strong>ID #</strong></td>
           <td>';
    if ($ag_action == "create" && $submit == "") {
        echo '&nbsp;<em> ' . gettext("not assigned yet") . ' </em>&nbsp';
    } else if ($submit == "") {
        echo '<input type="text" name="ag_id" value="' . htmlspecialchars($ag_id) . '">';
    } else if (($ag_action == "view" || $ag_action == "edit" || $ag_action == "delete" || $ag_action == "clear") && $submit != "") {
        echo '<input type="hidden" name="ag_id" value="' . htmlspecialchars($ag_id) . '">';
        echo $ag_id;
    }
    echo ' </td>
         <tr>
           <td valign="top"><strong>' . gettext("Name") . '</strong></td>
           <td>';
    if ($ag_action == "create" && $submit == "") {
        echo '<input type="text" name="ag_name" size="40" value="' . htmlspecialchars($ag_name) . '">';
    } else if ($submit == "") {
        echo '<select name="ag_name">
              <option value="">{ AG Name }';
        $sql = "select ag_name FROM acid_ag;";
        $result = $db->baseExecute($sql);
        if ($result) {
            while ($myrow = $result->baseFetchRow()) {
                echo '<option value="' . htmlspecialchars($myrow[0]) . '">' . htmlspecialchars($myrow[0]);
            }
            $result->baseFreeRows();
        }
        echo '</select>';
    } else if ($ag_action == "edit" && $submit != "") {
        echo '<input type="text" name="ag_name" size="40" value="' . htmlspecialchars($ag_name) . '">';
    } else if (($ag_action == "view" || $ag_action == "delete" || $ag_action = "clear") && $submit != "") {
        echo htmlspecialchars($ag_name);
    }
    echo ' </td>';
    if (($ag_action == "create" && $submit == "") || (($ag_action == "view" || $ag_action == "edit" || $ag_action == "delete" || $ag_action == "clear") && $submit != "")) {
        echo '
        <tr>
         <td valign="top"><strong>' . gettext("Description") . '</strong></td>
         <td>';
        if ($ag_action == "create" && $submit == "") {
            echo '<textarea name="ag_desc" cols="70" rows=4>' . htmlspecialchars($ag_desc) . '</textarea>';
        } else if ($ag_action == "edit" && $submit != "") {
            echo '<textarea name="ag_desc" cols="70" rows=4>' . htmlspecialchars($ag_desc) . '</textarea>';
        } else if (($ag_action == "view" || $ag_action == "delete" || $ag_action == "clear") && $submit != "") {
            echo htmlspecialchars($ag_desc);
        }
        echo '
             </td>
           </tr>';
    }
    echo '</table>';
    /* Print the Appropriate button */
    if ($submit == "" || $ag_action == "edit" || $ag_action == "delete" || $ag_action == "clear") {
        echo '<div style="margin:auto">';
        if ($ag_action == "create") {
            $button_text = gettext("Create Group");
        } else if ($ag_action == "view") {
            $button_text = gettext("View Group");
        } else if ($ag_action == "edit" && $submit == "") {
            $button_text = gettext("Edit Group");
        } else if ($ag_action == "edit" && $submit != "") {
            $button_text = gettext("Save Changes");
            $ag_action = "save";
        } else if ($ag_action == "delete" && $submit == "") {
            $button_text = gettext("Delete Group");
        } else if ($ag_action == "delete" && $submit != "") {
            $button_text = gettext("Confirm Delete");
            $ag_action = "delete_confirm";
        } else if ($ag_action == "clear" && $submit == "") {
            $button_text = gettext("Clear Group");
        } else if ($ag_action == "clear" && $submit != "") {
            $button_text = gettext("Confirm Clear");
            $ag_action = "clear_confirm";
        }
        echo '<input type="submit" name="submit" value="' . $button_text . '">';
        echo '</div>';
    }
} // if ($ag_action != "list")
echo '<input type="hidden" name="ag_action" value="' . htmlspecialchars($ag_action) . '">';
if ($ag_action == "view" && $submit != "") {
    /* Calculate the Number of Alerts */
    $cnt_sql = "SELECT count(ag_sid) FROM acid_ag_alert WHERE ag_id='" . $ag_id . "'";
    $save_sql = "SELECT acid_event.sid, acid_event.cid, signature, timestamp, " . "ip_src, ip_dst, ip_proto " . "FROM acid_event " . "LEFT JOIN acid_ag_alert ON acid_event.sid=ag_sid AND acid_event.cid=ag_cid " . "WHERE acid_event.cid > '0' AND ag_id = '" . $ag_id . "'";
    $printing_ag = true;
    $ag = $ag_id;
    include ("$BASE_path/base_qry_sqlcalls.php");
}
$qs->SaveState();
/* Export action_arg = current AG ID, so that Actions work */
ExportHTTPVar($ag_id, "action_arg");
echo "\n</form>\n";
PrintBASESubFooter();
if ($debug_time_mode > 0) {
    $et->Mark("Get Query Elements");
    $et->PrintTiming();
}
echo "</body>\r\n</html>";
?>
