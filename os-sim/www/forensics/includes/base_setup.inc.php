<?php
/**
* Class and Function List:
* Function list:
* - BaseSetup()
* - CheckConfig()
* - writeConfig()
* - displayConfig()
* Classes list:
* - BaseSetup
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
class BaseSetup {
    var $file;
    function BaseSetup($filename) {
        // Passes in the filename... This is for the CheckConfig
        $this->file = $filename;
    }
    function CheckConfig($distConfigFile) {
        // Compares variables in distConfigFile to $this->file
        
    }
    function writeConfig() {
        //writes the config file
        
    }
    function displayConfig() {
        /*displays current config
        * Not to be confused with the end display on the
        * set up pages!
        */
    }
}
?>