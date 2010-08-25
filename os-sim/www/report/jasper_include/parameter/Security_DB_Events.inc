<?php include_once('function.php');
    $reportOrd[$report['name']].='
<ul>
    <li>
        <label for="_Security_DB_Events_date_from">'._('Date from').'</label>
        <input id="_Security_DB_Events_date_from" name="date_from" value="'.parserDate(1).'" type="text">
    </li>
    <li>
        <label for="_Security_DB_Events_date_to">'._('Date to').'</label>
        <input id="_Security_DB_Events_date_to" name="date_to" value="'.parserDate(0).'" type="text">
    </li>
    <li>
        <label for="_Security_DB_Events_plugin">'._('Plugin').'</label>
        <input id="_Security_DB_Events_plugin" name="plugin" type="text">
    </li>
    <li>
        <label for="_Security_DB_Events_signature">'._('Signature').'</label>
        <input id="_Security_DB_Events_signature" name="signature" type="text">
    </li>
    <li>
        <label for="_Security_DB_Events_ip">'._('IP').'</label>
        <input id="_Security_DB_Events_ip" name="ip" type="text">
    </li>
</ul>';
    ?>