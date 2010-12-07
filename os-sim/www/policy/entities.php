<?php
/*****************************************************************************
*
*   Copyright (c) 2007-2010 AlienVault
*   All rights reserved.
*
****************************************************************************/
/**
* Class and Function List:
* Function list:
* Classes list:
*/
require_once ('classes/Session.inc');
require_once ('ossim_conf.inc');

$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);
$opensource = (!preg_match("/pro|demo/i",$version)) ? true : false;
$withusers = intval(GET('users'));
$_SESSION["_with_users"] = $withusers;

if (!$opensource) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?php echo gettext("OSSIM Framework"); ?> - Asset Structure</title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <link rel="stylesheet" type="text/css" href="../style/greybox.css"/>  
  <link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
  <link rel="stylesheet" type="text/css" href="../style/tree.css" />
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
  <script type="text/javascript" src="../js/jquery.tmpl.1.1.1.js"></script>
  <script type="text/javascript" src="../js/jquery.dynatree.js"></script>
  <script type="text/javascript" src="../js/greybox.js"></script>  
  <script type="text/javascript">
    $(document).ready(function(){
        $("#atree").dynatree({
            initAjax: { url: "entities_tree.php" },
            clickFolderMode: 2,
            onActivate: function(dtnode) {
                if(dtnode.data.url!='' && typeof(dtnode.data.url)!='undefined') {
                    GB_edit(dtnode.data.url+'&withoutmenu=1');
                }
            },
            onDeactivate: function(dtnode) {},
            onLazyRead: function(dtnode){
                dtnode.appendAjax({
                    url: "entities_tree.php",
                    data: {key: dtnode.data.key}
                });
                if (typeof(parent.doIframe2)=="function") parent.doIframe2();
            }
        });
        $("#aptree").dynatree({
            initAjax: { url: "asset_by_property_tree.php" },
            clickFolderMode: 2,
            onActivate: function(dtnode) {
                if(dtnode.data.url!='' && typeof(dtnode.data.url)!='undefined') {
                    GB_edit(dtnode.data.url+'&withoutmenu=1');
                }
            },
            onDeactivate: function(dtnode) {}
        });        
        setTimeout('refresh_tree()',1000);
    });
    function refresh_tree() {
    	$('#refreshing').show();
    	$.getJSON("asset_by_property_tree.php?from=0",
	        function(data) {
               $.each(data, function(i,item) {
               	  // add info to tree
                  addto_tree(item);
               });
               $('#refreshing').hide();
               if (typeof(parent.doIframe2)=="function") parent.doIframe2();
               setTimeout('refresh_tree()',10000);
	        }
		 );    
	}
	function addto_tree (item) {
    	var rn = $("#aptree").dynatree("getRoot").childList[0];
    	if ( rn.childList ) {
           for (var i=0; i<rn.childList.length; i++) {
              var node = rn.childList[i];
              var rnode = rn.childList[i];           
              if (node.data.key == item.ref) {
              	 // add here
              	 var found = false;
              	 if (node.childList) {
              	 	// search if already exists
              	 	for (var j=0; j<node.childList.length; j++) {
              	 		if (node.childList[j].data.key == item.key) {
              	 			rnode = node.childList[j];
              	 			found = true;
              	 		}
              	 	}
              	 }
              	 if (found) {
              	 	// found again?
              	 	found = false;
					for (var j=0; j<rnode.childList.length; j++) {
              	 		if (rnode.childList[j].data.key == item.ip) {
              	 			found = true;
              	 		}
              	 	}
              	 	if (!found)
					    rnode.addChild({
					        title: item.ip,
					        key: item.ip,
					        icon: "../../pixmaps/theme/host.png"
		 			    });
              	 } else {
              	 	var childNode = rnode.addChild({
				        title: item.value,
				        tooltip: item.extra,
				        key: item.key,
				        isFolder: true
				    });
				    childNode.addChild({
				        title: item.ip,
				        key: item.ip,
				        icon: "../../pixmaps/theme/host.png"
	 			    });
	 			    rnode = childNode;
              	 }
              	 var tt = rnode.data.title.replace(/\s\<font.*/,'');
	             rnode.data.title = tt+' <font style="font-weight:normal">('+rnode.childList.length+')</font>';
              }
              // all ips
              if (node.data.key == "all" && item.ip) {
              	  var found = false;
              	  if (node.childList) {
              	  	// search if already exists
              	 	for (var j=0; j<node.childList.length; j++) {
              	 		if (node.childList[j].data.key == item.ip) {
              	 			found = true;
              	 		}
              	 	}
              	  }
			      if (!found) {
			      	 if (item.name) {
			      	 	hostname = item.name;
			            url = '../host/modifyhostform.php?ip='+item.ip;
			         } else {
			      	 	hostname = item.ip;
			            url = '../host/newhostform.php?ip='+item.ip;
			         }
			         rnode.addChild({
			            title: hostname,
			            key: item.ip,
			            url: url,
			            icon: "../../pixmaps/theme/host.png"
   			         });
   			         var tt = rnode.data.title.replace(/\s\<font.*/,'');
   			         rnode.data.title = tt+' <font style="font-weight:normal">('+rnode.childList.length+')</font>';
   			      }
              }
           }
        }
	}
    //
    //
    GB_TYPE = 'w';
    function GB_onclose() {
    	document.location.reload();
    }
    function GB_edit(url) {
        GB_show("Edit Asset",url,"80%","80%");
        return false;
    }
</script>
</head>
<body>
<? include("../hmenu.php"); ?>

<table border="0" width="90%" class="noborder" align="center" cellspacing="0" cellpadding="0" style="background-color:transparent">
<tr><td valign="top" class="noborder" width="49%">

	<!-- All Assets -->
	<table border="0" width="100%" class="noborder" align="center" cellspacing="0" cellpadding="0">
	    <tr>
	        <td class="headerpr"><?=_("Asset Structure")?></td>
	    </tr>
	</table>
	<table border="0" width="100%" align="center" cellspacing="0" cellpadding="0">
	    <tr>
	        <td class="nobborder">
	  			<div id="atree" style="text-align:left;width:98%;padding:8px 8px 0px 8px"></div>
	        </td>
	    </tr>
	    <tr>
	        <td class="nobborder" style="padding:3px 0px 5px 5px;background-color:transparent">
	  			<a href="?users=<?=($withusers) ? "0" : "1"?>"><img src="../pixmaps/arrow_green.gif" align="absmiddle" border="0"><b><?=($withusers) ? _("Without Users") : _("With Users")?></b></a>
	        </td>
	    </tr>
	</table>

</td><td width="2%" class="noborder"></td><td valign="top" class="noborder" width="49%">

	<!-- Asset by Property -->
	<table border="0" width="100%" class="noborder" align="center" cellspacing="0" cellpadding="0">
	    <tr>
	        <td class="headerpr"><?=_("Assets")?></td>
	    </tr>
	</table>
	<table border="0" width="100%" align="center" cellspacing="0" cellpadding="0">
	    <tr>
	        <td class="nobborder">
	  			<div id="aptree" style="text-align:left;width:98%;padding:8px"></div>
	        </td>
	    </tr>
	</table>
	<!--<a href="javascript:refresh_tree()">refresh</a>-->

</td></tr>
</table>

</body>
</html>
<?
}
?>
