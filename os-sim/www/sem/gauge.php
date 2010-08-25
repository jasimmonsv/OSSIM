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
/**
* Class and Function List:
* Function list:
* Classes list:
*/
require_once 'classes/Session.inc';
Session::logcheck("MenuEvents", "ControlPanelSEM");
?>
<html>
<head>
<title><?=_("Bindows gauge sample")?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<script type="text/javascript" src="bindows_gauges/bindows_gauges.js"></script>
<script type="text/javascript">
function getXMLHttp()
{
  var xmlHttp

  try
  {
    //Firefox, Opera 8.0+, Safari
    xmlHttp = new XMLHttpRequest();
  }
  catch(e)
  {
    //Internet Explorer
    try
    {
      xmlHttp = new ActiveXObject("Msxml2.XMLHTTP");
    }
    catch(e)
    {
      try
      {
        xmlHttp = new ActiveXObject("Microsoft.XMLHTTP");
      }
      catch(e)
      {
        alert("Your browser does not support AJAX!")
        return false;
      }
    }
  }
  return xmlHttp;
}

function MakeCacheRequest(action)
{
  var xmlHttp = getXMLHttp();
  
  xmlHttp.onreadystatechange = function()
  {
    if(xmlHttp.readyState == 4)
    {
      HandleCacheResponse(xmlHttp.responseText);
    }
  }

  xmlHttp.open("GET", "handle_cache.php?action=" + action, true); 
  xmlHttp.send(null);
}

function HandleCacheResponse(response)
{
  var responses = response.split(":");
  if(responses[0] == "pct"){
  	gauge.needle.setValue(responses[1]);
  } else {
	document.getElementById('gauge_text').innerHTML = response;
  }
}

function updateGauge() {
	MakeCacheRequest("update");
	MakeCacheRequest("print");
}

</script>
</head>
<body>
<div id="gaugeDiv" style="width: 150; height: 150" ></div>
<script type="text/javascript">
	var gauge = bindows.loadGaugeIntoDiv("gauge.xml", "gaugeDiv");
	updateGauge();
	// dynamically update the gauge at runtime
	setInterval(updateGauge, 1000);
</script>
<div id="gauge_text">
</div>
<div id="actions">
<?=_("Act on the Cache")?>:
<dl>
<li><a href="javascript:MakeCacheRequest('clean')"><?=_("Clean")?></a> (<?=_("Clean entries older than 1 day")?>)</li>
<li><a href="javascript:MakeCacheRequest('purge')"><?=_("Purge")?></a> (<?=_("Clean everything")?>)</li>
</dl>
</div>
</body>
</html>
