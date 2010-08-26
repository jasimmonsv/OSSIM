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
require_once ('classes/Session.inc');
Session::logcheck("MenuIntelligence", "CorrelationDirectives");
require_once ('ossim_conf.inc');
require_once ('classes/Security.inc');
/* directories */
$conf = $GLOBALS["CONF"];
$base_dir = $conf->get_conf("base_dir");
$css_dir = '../../style';
$js_dir = '../javascript';
$js_dir_group = 'javascript';
/* connection to the OSSIM database */
require_once ('../../include/directive.php');
dbConnect();
/* get the group */
$group = get_group_by_name($_GET['name']);
$framed = ($_GET['framed'] != "") ? 1 : 0;
if (!isset($group)) $group = new Group(NULL, NULL, NULL, NULL);
$_SESSION['group'] = serialize($group);
/* width */
$list_width = '300px';

if (!$dom = domxml_open_file('/etc/ossim/server/directives.xml', DOMXML_LOAD_SUBSTITUTE_ENTITIES)) {
    echo "Error while parsing the document\n";
    exit;
}
$table = array();
$table_dir = $dom->get_elements_by_tagname('directive');
foreach($table_dir as $dir) {
    $table[$dir->get_attribute('id') ] = $dir->get_attribute('name');
}
ksort($table);

$list = "";
if ($group->list != null) {
    foreach($group->list as $dir) {
        if ($list != "") $list.= ",";
        $list.= $dir;
    }
    $list = trim($list);
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
	<head>
		<link type="text/css" rel="stylesheet"
			href="<?php
echo $css_dir . '/directives.css'; ?>" />
<link rel="stylesheet" type="text/css" href="../../style/greybox.css"/>

		<style>
			input.editable {width: <?php
echo $right_text_width; ?>}
			select.editable {width: <?php
echo $right_select_width; ?>}
		</style>

		<script type="text/javascript" language="javascript"
			src="<?php
echo $js_dir . '/editor.js'; ?>"></script>
<script type="text/javascript" src="../../js/jquery-1.3.2.min.js"></script>
<script type="text/javascript" src="../../js/greybox.js"></script>
		<script type="text/javascript" language="javascript"
			src="<?php
echo $js_dir . '/editableSelectBox.js'; ?>"></script>

		<script type="text/javascript" language="javascript"
			src="<?php
echo $js_dir_group . '/group.js'; ?>"></script>

		<script type="text/javascript" language="javascript">
		function taille()
    {
        if (document.body)
        {
        var larg = (window.parent.document.body.clientWidth);
        var haut = (window.parent.document.body.clientHeight);
        }
        else
        {
        var larg = (window.parent.window.innerWidth);
        var haut = (window.parent.window.innerHeight);
        }
        /* default size */
    	   var width = 890;
    	   var height = 550;
    
    	   /* center the popup to the screen */
    	   if (width < larg)
    	   {
    	     var left = (larg - width) / 2;
    	   }
    	   else
    	   {
            width = larg - 20;
            left = 10;
         }
         
         if (height < haut)
    	   {
           var top = (haut - height) / 2;
    	   }
    	   else
    	   {
            height = haut - 20;
            top = 10;
         }
         
         window.parent.document.getElementById('fenetre').style.top = top;
         window.parent.document.getElementById('fenetre').style.left = left;
         window.parent.document.getElementById('fenetre').style.width = width;
         window.parent.document.getElementById('fenetre').style.height = height;
         
    }

	function open_frame(url){
		<?php if ($framed) { ?>
		document.location.href="../../"+url;
		<?php } else {?>
		GB_show('Group edit',url,520,'90%');
		<?php }?>
		/*
	    var iframe = window.parent.document.getElementById('fenetre');
	    var fond = window.parent.document.getElementById('fond');
	    iframe.childNodes[0].src = url;    
	    taille();
	    fond.style.display = 'block';
	    iframe.style.display = 'block';
	    */
	   }
	function check_directive(directive_id,checked) {
		var dir_string = document.getElementById('list').value;
		
		if (checked == true) {
			// Add to list
			if (dir_string != "") dir_string = dir_string + "," + directive_id;
			else dir_string = directive_id;
		} else {
			var aux = dir_string.split(/\,/);
			dir_string = "";
			for (i = 0; i < aux.length; i++) {
				// Delete from list
				if (aux[i] != directive_id) {
					if (dir_string != "") dir_string = dir_string + "," + aux[i];
					else dir_string = aux[i];
				}
			}
		}
		document.getElementById('list').value = dir_string;
	}
		</script>

	</head>

	<body>
	<!-- #################### main container #################### -->
	<form method="POST" action="../../include/utils.php?query=save_group">
	<input type="hidden" style="width: <?php echo $list_width; ?>"
		name="list"
		id="list"
		value="<?php print $list; ?>"
		title="<?php print $list; ?>"
		onkeypress="onKeyPressElt(this,event)"
		onchange="onChangelist('<?php print $list; ?>')"
		onblur="onChangelist('<?php print $list; ?>')">
	</input>
	<table class="container" width="100%" style="border-width: 0px;background-color:transparent" align="center">
	<tr>

	<!-- #################### left container #################### -->
	<td class="container" style="vertical-align: top;border:0px">
	<table width="100%" class="container" style="background-color:transparent">

	<tr><td class="container" style="border:0px">
	<?php
include ("$base_dir/directive_editor/editor/group/global.inc.php"); ?>
	</td></tr>

	<tr><td class="container" style="border:0px">
		<input type="button" class="btn" style="width: 100px"
			value="<?php
echo gettext('Cancel'); ?>"
			onclick="onClickCancel()"
		/>
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="button" class="btn" style="width: 100px"
			id="save"
			value="<?php
echo gettext('Save'); ?>"
			onclick="submit()"
		/>
	</td></tr>

	</table>
	</td>
	<!-- #################### END: left container #################### -->

	</tr>
	</table>
	</form>

	<!-- #################### END: main container #################### -->

	</body>
</html>

<?php
dbClose();
?>
