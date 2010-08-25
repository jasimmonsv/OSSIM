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
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<!-- Forensics Console <?php
echo $BASE_VERSION; ?> -->
<HTML>
  <HEAD>
    <META name="Author" content="Kevin Johnson">
    <TITLE>BASE: </TITLE>
  <LINK rel="stylesheet" type="text/css" HREF="styles/base_style.css">

</HEAD>

<BODY>
<a name="language"><b>Language Selection:</b><br>
This is the language that the program will be displayed in.  Currently this is a global setting.<hr>
<br><a name="adodb"><b>Path to ADODB:</b><br>
Path to the DB abstraction library 
  (Note: DO NOT include a trailing backslash after the directory)
   e.g. <ul><li>"/tmp"      [OK]
        <li>"/tmp/"     [WRONG]
        <li>"c:\tmp"    [OK]
        <li>"c:\tmp\"   [WRONG]</ul><hr>
<br><a name="chartpath"><b>Path to the Chart Library:</b><br>
Path to the graphing library <br>
(Note: DO NOT include a trailing backslash after the directory)<hr>
<br>
<br><a name="dbtype"><b>Database Type:</b><br>
Please select the type of Database that Snort is logging its alerts too.
<br><a name="usearchive"><b>Use an Archive Database:</b><br>
If you would like the ability to archive alerts from your active database, sleect this box.
If so, you must also answer the questions below.
<br><a name="useauth"><b>Use the User Authentication System:</b><br>
This check box enables you to set up a user authentication system for BASE.
If you do not want to have people log in before they can view BASE, do not select this.
</BODY>

</HTML>
