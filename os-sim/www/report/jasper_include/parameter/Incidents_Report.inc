<?php include_once('function.php');
    $reportOrd[$report['name']].='
<ul>
    <li>
        <div class="widget">
            <label for="_Incidents_Report_date_from">'._('Date from').'</label>
            <input id="_Incidents_Report_date_from" name="date_from" value="'.parserDate(1).'" type="text">
            <div class="widgetCalendar" id="_Incidents_Report_date_from_widgetCalendar"></div>
        </div>
    </li>
    <li>
        <div class="widget">
            <label for="_Incidents_Report_date_to">'._('Date to').'</label>
            <input id="_Incidents_Report_date_to" name="date_to" value="'.parserDate(0).'" type="text">
            <div class="widgetCalendar" id="_Incidents_Report_date_to_widgetCalendar"></div>
        </div>
    </li>
    <li>
        <label>'._('Status').'</label>
        <select id="_Incidents_Report_Status" name="Status">
          <option value="ALL">'._('All').'</option>
          <option value="Open">'._('Open').'</option>
          <option value="Closed">'._('Closed').'</option>
        </select>
    </li>
    <li>
        <label>'._('Type').'</label>
        <select id="_Incidents_Report_Type" name="Type">
          <option value="ALL">'._('ALL').'</option>
          <option  value="Generic">'._('Generic').'</option>
          <option  value="Expansion Virus">'._('Expansion Virus').'</option>
          <option  value="Corporative Nets Attack">'._('Corporative Nets Attack').'</option>
          <option  value="Policy Violation">'._('Policy Violation').'</option>
          <option  value="Security Weakness">'._('Security Weakness').'</option>
          <option  value="Net Performance">'._('Net Performance').'</option>
          <option  value="Applications and Systems Failures">'._('Applications and Systems Failures').'</option>
          <option  value="Anomalies">'._('Anomalies').'</option>
          <option  value="Nessus Vulnerability">'._('Nessus Vulnerability').'</option>
        </select>
    </li>
    <li>
        <table border="0" class="noborder" width="100%" style="width:100px">
            <tr>
                <td>'._('Priority').'</td>
            </tr>
            <tr>
                <td><input id="_Incidents_Report_Priority_H" name="High" class="inputCheckbox" checked="checked" type="checkbox"> '._('High').'</td>
            </tr>
            <tr>
                <td><input id="_Incidents_Report_Priority_M" name="Medium" class="inputCheckbox" checked="checked" type="checkbox"> '._('Medium').'</td>
            </tr>
            <tr>
                <td><input id="_Incidents_Report_Priority_L" name="Low" class="inputCheckbox" checked="checked" type="checkbox"> '._('Low').'</td>
            </tr>
        </table>
    </li>
    <li class="email" style="margin-top:55px;#margin-top:0px">
        <div>
        <label for="_Incidents_Report_email">'._('Email').'</label>
        <input id="_Incidents_Report_email" name="email" type="text">
        </div>
    </li>
</ul>';
    ?>