<?php
/**
* Class and Function List:
* Function list:
* - BaseUserPrefs()
* Classes list:
* - BaseUserPrefs
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
defined('_BASE_INC') or die('Accessing this file directly is not allowed.');
class BaseUserPrefs {
    var $db;
    function BaseUserPrefs() {
        // Constructor
        GLOBAL $DBlib_path, $DBtype, $db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password;
        $db = NewBASEDBConnection($DBlib_path, $DBtype);
        $db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);
        $this->db = $db;
    }
}
?>