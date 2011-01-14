<?php
/*****************************************************************************
*
*    License:
*
*   Copyright (c) 2003-2006 ossim.net
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

require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
require_once ('classes/Ossec.inc');

$ip = GET('ip');

?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title> <?php echo _("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
</head>

<body>
	<?php include("../hmenu.php"); ?>
	<h1> <?php echo gettext("Enable/Disable Agentless Host"); ?> </h1>


<?php

	$txt_error = null;
	ossim_valid($ip, OSS_IP_ADDR, 'illegal:' . _("Ip Address"));

	if ( ossim_error() ) 
		$txt_error = ossim_get_error();
	else
	{
		$db    = new ossim_db();
		$conn  = $db->connect();
		$agentless_list = Agentless::get_list($conn, "WHERE ip='$ip'");
				
		if ($agentless_list[0])
		{
			if ($agentless_list[0]->get_status() == 0 )
			{
				$res = $agentless_list[0]->change_status($conn, 1);
				$txt_error = ($res == true) ? null : _("Error to enable Agentless Host");
				$db->close($conn);
			}
			elseif ($agentless_list[0]->get_status() == 1 )
			{
				$res = $agentless_list[0]->change_status($conn, 0);
				$txt_error = ($res == true) ? null : _("Error to disabled Agentless Host");
				$db->close($conn);
			}
			else
			{
				if ($agentless_list[0]->get_status() == 2 )
				{
					header("Location: al_modifyform.php");
					exit();
				}
			}
		}
		else
			$txt_error = _("Ip Address not found");
	}
			
	
	if ( !empty($txt_error) )
	{
		Util::print_error($txt_error);	
		Util::make_form("POST", "agentless.php");
	}
	else
	{
		$state = ( $agentless_list[0]->get_status() == 0 ) ? "enabled" : "disabled";
		echo "<p>"._("Host succesfully $state")."</p>";
		echo "<script>document.location.href='agentless.php'</script>";
	}

?>

	
	
</body>
</html>


