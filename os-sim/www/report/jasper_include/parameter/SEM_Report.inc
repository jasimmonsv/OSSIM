<?php include_once('function.php');
    $reportOrd[$report['name']].='
<ul>
    <li>
        <div class="widget">
            <label for="_SEM_Report_date_from">'._('Date from').'</label>
            <input id="_SEM_Report_date_from" name="date_from" value="'.parserDate(1).'" type="text">
            <div class="widgetCalendar" id="_SEM_Report_date_from_widgetCalendar"></div>
        </div>
    </li>
    <li>
        <div class="widget">
            <label for="_SEM_Report_date_to">'._('Date to').'</label>
            <input id="_SEM_Report_date_to" name="date_to" value="'.parserDate(0).'" type="text">
            <div class="widgetCalendar" id="_SEM_Report_date_to_widgetCalendar"></div>
        </div>
    </li>
<!--
    <li>
        <label for="_SEM_Report_plugin">'._('Plugin').'</label>
        <input id="_SEM_Report_plugin" name="pluginId" value="" type="text">
        <input id="_SEM_Report_pluginName" name="plugin" value="" type="hidden">
    </li>
-->
    <li class="email">
        <div>
        <label for="_SEM_Report_email">'._('Email').'</label>
        <input id="_SEM_Report_email" name="email" type="text">
        </div>
    </li>
</ul>';
    ?>