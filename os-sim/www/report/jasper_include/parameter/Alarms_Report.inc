<?php include_once('function.php');
    $reportOrd[$report['name']].='
<ul>
    <li>
        <div class="widget">
            <label for="_Alarms_Report_date_from">'._('Date from').'</label>
            <input id="_Alarms_Report_date_from" name="date_from" value="'.parserDate(1).'" type="text">
            <div class="widgetCalendar" id="_Alarms_Report_date_from_widgetCalendar"></div>
        </div>
    </li>
    <li>
        <div class="widget">
            <label for="_Alarms_Report_date_to">'._('Date to').'</label>
            <input id="_Alarms_Report_date_to" name="date_to" value="'.parserDate(0).'" type="text">
            <div class="widgetCalendar" id="_Alarms_Report_date_to_widgetCalendar"></div>
        </div>
    </li>
    <li class="email">
        <div>
        <label for="_Alarms_Report_email">'._('Email').'</label>
        <input id="_Alarms_Report_email" name="email" type="text">
        </div>
    </li>
</ul>';
    ?>