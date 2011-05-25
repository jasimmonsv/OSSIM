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
* - check_writable_relative()
* Classes list:
*/

ob_implicit_flush();

require_once 'classes/Session.inc';
require_once 'ossim_conf.inc';
include("riskmaps_functions.php");

$conf    = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);

Session::logcheck("MenuControlPanel", "BusinessProcesses");

if (!Session::menu_perms("MenuControlPanel", "BusinessProcessesEdit") ) 
{
	Session::unallowed_section();
	exit();
}

function mapAllowed($perms_arr,$version)
{
	if (Session::am_i_admin()) return true;
	$ret = false;
	foreach ($perms_arr as $perm=>$val) {
		
		if (preg_match("/^\d+$/",$perm))
		{
			if (preg_match("/pro|demo/i",$version) && $_SESSION['_user_vision']['entity'][$perm]) // ENTITY 
				$ret = true;
		} 
		elseif ( Session::get_session_user() == $perm )  // USER
			$ret = true;  
	}
	return $ret;
}

function check_writable_relative($dir){
	$uid         = posix_getuid();
	$gid         = posix_getgid();
	$user_info   = posix_getpwuid($uid);
	$user        = $user_info['name'];
	$group_info  = posix_getgrgid($gid);
	$group       = $group_info['name'];
	$fix_cmd     = '. '._("To fix that, execute following commands as root").':<br><br>'.
			   "cd " . getcwd() . "<br>".
					   "mkdir -p $dir<br>".
					   "chown $user:$group $dir<br>".
					   "chmod 0700 $dir";
	if (!is_dir($dir)) {
		 die(_("Required directory " . getcwd() . "$dir does not exist").$fix_cmd);
	}
	$fix_cmd .= $fix_extra;


	if (!$stat = stat($dir)) {
		die(_("Could not stat configs dir").$fix_cmd);
	}

	// 2 -> file perms (must be 0700)
	// 4 -> uid (must be the apache uid)
	// 5 -> gid (must be the apache gid)

	if ($stat[2] != 16832 || $stat[4] !== $uid || $stat[5] !== $gid)
	{
		die(_("Invalid perms for configs dir").$fix_cmd);
	}
}

check_writable_relative("./maps");
check_writable_relative("./pixmaps/uploaded");

/*

Requirements: 
- web server readable/writable ./maps
- web server readable/writable ./pixmaps/uploaded
- standard icons at pixmaps/standard
- Special icons at docroot/ossim_icons/


TODO: Rewrite code, beutify, use ossim classes for item selection, convert operations into ossim classes

*/

require_once 'classes/Security.inc';
require_once 'ossim_db.inc';


$erase_element = GET('delete');
$erase_type    = GET('delete_type');
$map           = (POST("map") != "") ? POST("map") : ((GET("map") != "") ? GET("map") : (($_SESSION["riskmap"]!="") ? $_SESSION["riskmap"] : 1));
$type          = (GET("type")!="") ? GET("type") : "host";
$name          = POST('name');

ossim_valid($erase_element, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_DIGIT, ";,.", 'illegal:'._("erase_element"));
ossim_valid($erase_type , "map", "icon", OSS_NULLABLE, 'illegal:'._("erase_type"));
ossim_valid($type, OSS_ALPHA, OSS_DIGIT, 'illegal:'._("type"));
ossim_valid($name, OSS_ALPHA, OSS_NULLABLE, OSS_DIGIT, OSS_SCORE, ".,%", 'illegal:'._("name"));
ossim_valid($map, OSS_DIGIT, 'illegal:'._("type"));

if (ossim_error()) {
	die(ossim_error());
}

// Cleanup a bit

$name 		   = str_replace("..","",$name);
$erase_element = str_replace("..","",$erase_element);

$uploaded_icon = false;

if (is_uploaded_file($_FILES['fichero']['tmp_name'])) 
{
	if (exif_imagetype ($_FILES['fichero']['tmp_name']) == IMAGETYPE_JPEG || exif_imagetype ($_FILES['fichero']['tmp_name']) == IMAGETYPE_GIF ) 
	{
		$size = getimagesize($_FILES['fichero']['tmp_name']);
        if ($size[0] < 400 && $size[1] < 400)
		{
                $uploaded_icon = true;
                $filename = "pixmaps/uploaded/" . $name . ".jpg";
                move_uploaded_file($_FILES['fichero']['tmp_name'], $filename);
        } 
		else 
            echo _("<span style='color:#FF0000;'>The file uploaded is too big (Max image size 400x400 px).</span>");
        
    }
	else
        echo _("<span style='color:#FF0000;'>The image format should be .jpg or .gif.</span>");
    
}

if (is_uploaded_file($_FILES['ficheromap']['tmp_name'])) 
{
	$filename = "maps/" . $name . ".jpg";
	
	if(getimagesize($_FILES['ficheromap']['tmp_name'])){
		move_uploaded_file($_FILES['ficheromap']['tmp_name'], $filename);
	}
}

if ($erase_element != "") 
{
	switch($erase_type)
	{
		case "map":
			if(getimagesize("maps/" . $erase_element))
				unlink("maps/" . $erase_element);
		break;
		
		case "icon":
			if(getimagesize("pixmaps/uploaded/" . $erase_element))
				unlink("pixmaps/uploaded/" . $erase_element);
			
		break;
		
	}
}


$db   = new ossim_db();
$conn = $db->connect();

$perms = array();

$query = "SELECT map,perm FROM risk_maps";

if ($result = $conn->Execute($query)) 
{
	while (!$result->EOF) 
	{
		$perms[$result->fields['map']][$result->fields['perm']]++;
		$result->MoveNext();
	}
}

if (is_array($perms[$map]) && !mapAllowed($perms[$map],$version)) 
{
	Session::unallowed_section();
	exit;
}


if (preg_match("/MSIE/",$_SERVER['HTTP_USER_AGENT'])) 
{ 
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<?php 
} 
?>

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<title><?php echo  _("Risk Maps") ?>  - <?php echo  _("Edit") ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<link rel="stylesheet" type="text/css" href="custom_style.css">
	<link rel="stylesheet" href="lytebox.css" type="text/css" media="screen" />
	<link rel="stylesheet" type="text/css" href="../style/greybox.css" />
	<link rel="stylesheet" type="text/css" href="../style/tree.css" />
	<script type="text/javascript" src="lytebox.js"></script>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/greybox.js"></script>
	<script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
	<script type="text/javascript" src="../js/jquery.cookie.js"></script>
	<script type="text/javascript" src="../js/jquery.dynatree.js"></script>


	<script type='text/javascript'>

		function GB_onclose() {}

		function loadLytebox()
		{
			var cat = document.getElementById('category').value;
			var id = cat + "-0";
			myLytebox.start(document.getElementById(id));
		}

		function choose_icon(icon)
		{
			var cat   = document.getElementById('category').value;
			var timg = document.getElementById('chosen_icon');
			timg.src = icon
			changed = 1;
			document.getElementById('save_button').className = "lbutton_unsaved";
		}

		function toggleLayer( whichLayer )
		{
		  var elem, vis;
		  if( document.getElementById ) // this is the way the standards work
			elem = document.getElementById( whichLayer );
		  else if( document.all ) // this is the way old msie versions work
			  elem = document.all[whichLayer];
		  else if( document.layers ) // this is the way nn4 works
			elem = document.layers[whichLayer];
		  vis = elem.style;
		  // if the style.display value is blank we try to figure it out here
		  if(vis.display==''&&elem.offsetWidth!=undefined&&elem.offsetHeight!=undefined)
			vis.display = (elem.offsetWidth!=0&&elem.offsetHeight!=0)?'block':'none';
		  vis.display = (vis.display==''||vis.display=='block')?'none':'block';
		}

		function findPos(obj) {
			var curleft = curtop = 0;
			if (obj.offsetParent) {
				do {
					curleft += obj.offsetLeft;
					curtop += obj.offsetTop;
				} while (obj = obj.offsetParent);
				return [curleft,curtop];
			}
		}

		var moz = document.getElementById && !document.all;
		var moving = false;
		var resizing = false;
		var dobj;	
		var changed = 0;
    
		function dragging(e){
			if (moving) {
				x = moz ? e.clientX : event.clientX;
				y = moz ? e.clientY : event.clientY;
				sx = (typeof(window.scrollX) != 'undefined') ? window.scrollX : ((typeof(document.body.scrollLeft) != 'undefined') ? document.body.scrollLeft : 0);
				sy = (typeof(window.scrollY) != 'undefined') ? window.scrollY : ((typeof(document.body.scrollTop) != 'undefined') ? document.body.scrollTop : 0);
				document.getElementById('state').innerHTML = "<?php echo  _("Moving...") ?>";
				document.f.posx.value = x + sx
				document.f.posy.value = y + sy
				dobj.style.left = x + sx - parseInt(dobj.style.width.replace('px',''))/2;
				dobj.style.top = y + sy - parseInt(dobj.style.height.replace('px',''))/2;
				// Check if it's under the wastebin icon
				var waste = document.getElementById("wastebin")
				var waste_pos = [];
				waste_pos  = findPos(waste);
				if ( x>= waste_pos[0] && x<= waste_pos[0] + 48 && y>=waste_pos[1] && y<= waste_pos[1] + 53 ) {
					dobj.style.visibility = 'hidden'
				}
				changed = 1;
				document.getElementById('save_button').className = "lbutton_unsaved";
				return false;
			}
			
			if (resizing) {
				sx = (typeof(window.scrollX) != 'undefined') ? window.scrollX : ((typeof(document.body.scrollLeft) != 'undefined') ? document.body.scrollLeft : 0);
				sy = (typeof(window.scrollY) != 'undefined') ? window.scrollY : ((typeof(document.body.scrollTop) != 'undefined') ? document.body.scrollTop : 0);
				x = moz ? e.clientX+10+ sx : event.clientX+10+ sx;
				y = moz ? e.clientY+10+ sy : event.clientY+10+ sy;
				document.getElementById('state').innerHTML = "<?php echo  _("Resizing...") ?>";
				document.f.posx.value = x + sx;
				document.f.posy.value = y + sy;
				xx = parseInt(dobj.style.left.replace('px','')) + 5;
				yy = parseInt(dobj.style.top.replace('px','')) + 5;
				w = (x > xx) ? x-xx : xx
				h = (y > yy) ? y-yy : yy
				dobj.style.width = w
				dobj.style.height = h 
				changed = 1;
				document.getElementById('save_button').className = "lbutton_unsaved";
				return false;
			}
		}
	
		function releasing(e) {
			moving = false;
			resizing = false;
			document.getElementById('state').innerHTML = ""
			if (dobj != undefined) {
				dobj.style.cursor = 'pointer';
			}
		}

		function reset_values() {
			// Reset form values
			$('.itcanbemoved').css("border","1px solid transparent");
			document.f.url.value        = "";
			document.f.alarm_id.value   = "";
			document.f.alarm_name.value = "";
			document.f.type.value       = "";
			document.getElementById('check_report').checked = false;
			document.getElementById('elem').value = "";
			document.getElementById('selected_msg').innerHTML = "";
			document.getElementById('chosen_icon').src = "pixmaps/standard/default.png";
			document.getElementById('linktoreport').style.display = 'none';
		}
		
		function pushing(e) {
			var fobj = moz ? e.target : event.srcElement;
			var button = moz ? e.which : event.button;

			$('.itcanbemoved').css("border","1px solid transparent");
			
			if (typeof fobj.tagName == 'undefined') {
				return false;
			}
			while (fobj.tagName.toLowerCase() != "html" && fobj.className != "itcanbemoved" && fobj.className != "itcanberesized") {
				fobj = moz ? fobj.parentNode : fobj.parentElement;
			}
			if (fobj.className == "itcanberesized") {
				resizing = true;
				fobj = moz ? fobj.parentNode : fobj.parentElement;
				dobj = fobj
				return false;
			}
			else if (fobj.className == "itcanbemoved") {
				fobj.style.border = "1px dotted red";
				var ida = fobj.id.replace("indicator","").replace("rect","");
				if (document.getElementById('dataname'+ida)) {
					if (document.getElementById('dataurl'+ida).value=="REPORT") {
						document.getElementById('check_report').checked=1;
					}
					else {
						document.getElementById('linktomapurl').style.display = '';
						document.getElementById('linktomapmaps').style.display = '';
						document.getElementById('check_report').checked=0;
					}
					document.f.url.value = document.getElementById('dataurl'+ida).value
					document.f.alarm_id.value = ida
					if (!fobj.id.match(/rect/)) {
						document.f.alarm_name.value = document.getElementById('dataname'+ida).value
						document.f.type.value = document.getElementById('datatype'+ida).value
						var id_type = 'elem_'+document.getElementById('datatype'+ida).value
						document.getElementById('elem').value = document.getElementById('type_name'+ida).value
						change_select()
						if(document.getElementById('dataicon' + ida) != null) {
							document.getElementById('chosen_icon').src = document.getElementById('dataicon'+ida).value
						}
						if(document.getElementById('dataiconsize' + ida) != null) {
							document.getElementById('iconsize').value = document.getElementById('dataiconsize'+ida).value
						}
						if(document.getElementById('dataiconbg' + ida) != null) {
							document.getElementById('iconbg').value = document.getElementById('dataiconbg'+ida).value
						}
					}
				}
				moving = true;
				fobj.style.cursor = 'move';
				dobj = fobj
				return false;
			}
		}
		
		document.onmousedown = pushing;
		document.onmouseup   = releasing;
		document.onmousemove = dragging;
		
		function urlencode(str) { return escape(str).replace('+','%2B').replace('%20','+').replace('*','%2A').replace('/','%2F').replace('@','%40'); }

		function drawDiv (id, name, valor, icon, url, x, y, w, h, type, type_name, size) {
			if (size == 0) size = '100%';
			if (icon.match(/\#/)) {
				var aux = icon.split(/\#/);
				var iconbg = aux[1];
				icon = aux[0];
			} else {
				var iconbg = "transparent";
			}
			var el = document.createElement('div');
			var the_map= document.getElementById("map_img")
			var map_pos = [];
			map_pos = findPos(the_map);
			el.id='indicator'+id
			el.className='itcanbemoved'
			el.style.position = 'absolute';
			el.style.left = x + map_pos[0];
			el.style.top = y
			el.style.width = w
			el.style.height = h
			el.innerHTML = "<img src='../pixmaps/loading.gif'>";
			el.style.visibility = 'visible'
			document.body.appendChild(el);
			document.getElementById('tdnuevo').innerHTML += '<input type="hidden" name="dataname' + id + '" id="dataname' + id + '" value="' + name + '">\n';
			document.getElementById('tdnuevo').innerHTML += '<input type="hidden" name="datatype' + id + '" id="datatype' + id + '" value="' + type + '">\n';
			document.getElementById('tdnuevo').innerHTML += '<input type="hidden" name="type_name' + id + '" id="type_name' + id + '" value="' + type_name + '">\n';
			document.getElementById('tdnuevo').innerHTML += '<input type="hidden" name="dataurl' + id + '" id="dataurl' + id + '" value="' + url + '">\n';
			document.getElementById('tdnuevo').innerHTML += '<input type="hidden" name="dataicon' + id + '" id="dataicon' + id + '" value="' + icon + '">\n';
			document.getElementById('tdnuevo').innerHTML += '<input type="hidden" name="dataiconsize' + id + '" id="dataiconsize' + id + '" value="' + size + '">\n';
			document.getElementById('tdnuevo').innerHTML += '<input type="hidden" name="dataiconbg' + id + '" id="dataiconbg' + id + '" value="' + iconbg + '">\n';
			
			document.getElementById('state').innerHTML = "<?php echo  _("New") ?>"
		}

		function initDiv () {
			
			$('#loading').hide();
			var x = 0;
			var y = 0;
			var el = document.getElementById('map_img');
			var obj = el;
			do {
				x += obj.offsetLeft;
				y += obj.offsetTop;
				obj = obj.offsetParent;
			} while (obj);	
			var objs = document.getElementsByTagName("div");
			var txt = ''
			for (var i=0; i < objs.length; i++) {
				if (objs[i].className == "itcanbemoved") {
					xx = parseInt(objs[i].style.left.replace('px',''));
					objs[i].style.left = xx + x;
					yy = parseInt(objs[i].style.top.replace('px',''));
					objs[i].style.top = yy + y; 
					objs[i].style.visibility = "visible"
				}
			}
			refresh_indicators();
			// greybox
			$("a.greybox").click(function(){
			   var t = this.title || $(this).text() || this.href;
			   var url = this.href + "?dir=" + document.getElementById('category').value;
			   GB_show(t,url,420,"50%");
			   return false;
			});
			$("a.greybox2").click(function(){
			   var t = this.title || $(this).text() || this.href;
			   var url = this.href + "&dir=" + document.getElementById('category').value;
			   GB_show(t,url,200,200);
			   return false;
			});
		}

		var layer = null;
		var nodetree = null;
		var suf = "c";
		var i=1;
		
		function load_tree(filter) {
			if (nodetree!=null) {
				nodetree.removeChildren();
				$(layer).remove();
			}
			layer = '#srctree'+i;
			$('#tree').append('<div id="srctree'+i+'" class="tree_container"></div>');
			$(layer).dynatree({
				initAjax: { url: "type_tree.php", data: {filter: filter} },
				clickFolderMode: 2,
				onActivate: function(dtnode) {
                    if (dtnode.data.key.indexOf(';')!=-1) 
					{
                        var keys = dtnode.data.key.split(/\;/);
                        
						document.getElementById('type').value = keys[0];
                        document.getElementById('elem').value = keys[1];
                        
						if (keys[0] == "host" || keys[0] == "net" || keys[0] == "sensor") 
							document.getElementById('check_report').checked = true;
                        else 
							document.getElementById('check_report').checked = false;
                        
						var style = 'background-color:#EFEBDE; padding:2px 5px 2px 5px; border:1px dotted #cccccc; font-size:11px; width: 90%';
						
						document.getElementById('selected_msg').innerHTML = "<div style='"+style+"'<strong><?php echo _("Selected type")?></strong>: "+document.f.type.value+" - "+document.f.elem.value+"</div>";
                       
					    if (document.f.type.value == "host_group" || document.f.type.value == "server") 
                            document.getElementById('linktoreport').style.display = 'none';
                        
                        else 
                            document.getElementById('linktoreport').style.display = '';
                        
                    }
                    else 
                        dtnode.toggleExpand();
                    
				},
				onDeactivate: function(dtnode) {},
				onLazyRead: function(dtnode){
					dtnode.appendAjax({
						url: "type_tree.php",
						data: {key: dtnode.data.key, filter: filter, page: dtnode.data.page}
					});
				}
			});
			nodetree = $(layer).dynatree("getRoot");
			i=i+1
		}
		
		/*function get_echars(data){
			var echars='';
			//alert(data);
			//alert(data.match(/&#(\d{4,5});/));
			alert(data.match(/^[a-zA-Z]*$/));
						
			//var echars = ( preg_match_all('/&#(\d{4,5});/', $data, $match) != false ) ? $match[1] : array();
			return echars;
		}*/
		
		function addnew(map,type) {
			
			document.f.alarm_id.value = ''
			
			if (type == 'alarm') 
			{
				if (document.f.alarm_name.value != '') 
				{
					var txt = '';
					var robj = document.getElementById("chosen_icon").src;
					robj = robj.replace(/.*\/ossim\/risk\_maps\//,"");
					txt = txt + urlencode(robj) + ';';
					type = document.f.type.value;
					elem = document.getElementById('elem').value;
					txt = txt + urlencode(type) + ';' + urlencode(elem) + ';';
					var temp_value=document.f.alarm_name.value;
					if(temp_value.match(/^[a-zA-Z0-9ó]$/)==null) {
						txt = txt + document.f.alarm_name.value + ';';
					} else {
						txt = txt + urlencode(document.f.alarm_name.value) + ';';
					}
					txt = txt + urlencode(document.f.url.value) + ';';
					txt = txt.replace(/\//g,"url_slash");
					txt = txt.replace(/\%3F/g,"url_quest");
					txt = txt.replace(/\%3D/g,"url_equal");
					document.getElementById('state').innerHTML = "<img src='../pixmaps/loading.gif' width='20'>";
					$.ajax({
					   type: "GET",
					   url: 'responder.php?map=' + map + '&data=' + txt + '&iconbg=' + document.f.iconbg.value + '&iconsize=' + document.f.iconsize.value,
					   success: function(msg){
							eval(msg);
							refresh_indicators();
							document.getElementById('state').innerHTML = '<?php echo  _("New Indicator created") ?>';
					   }
					});	
				} else {
					alert("<?php echo  _("Indicator name can't be void") ?>")
				}	
			} 
			else 
			{
				document.getElementById('state').innerHTML = "<img src='../pixmaps/loading.gif' width='20'>";
				$.ajax({
				   type: "GET",
				   url: 'responder.php?map=' + map + '&type=rect&url=' + urlencode(document.f.url.value),
				   success: function(msg){
					   	eval(msg);
					   	document.getElementById('state').innerHTML = '<?php echo  _("New Rectangle created") ?>';
						refresh_indicators();
				   }
				});	
			}
			
			changed = 1;
			document.getElementById('save_button').className = "lbutton_unsaved";
		}

		function drawRect (id,x,y,w,h)
		{
			var el = document.createElement('div');
			var the_map= document.getElementById("map_img")
			var map_pos = [];
			map_pos = findPos(the_map)
			el.id='rect'+id
			el.className='itcanbemoved'
			el.style.position = 'absolute';
			el.style.left = x + map_pos[0];
			el.style.top = y
			el.style.width = w
			el.style.height = h
			el.innerHTML = "<div style='position:absolute;bottom:0px;right:0px'><img src='../pixmaps/resize.gif' border=0></div>";
			el.style.visibility = 'visible'
			document.body.appendChild(el);
			document.getElementById('state').innerHTML = "<?php echo  _("New") ?>"
			changed = 1;
			document.getElementById('save_button').className = "lbutton_unsaved";
		}

		function save(map) {
			var x = 0;
			var y = 0;
			var el = document.getElementById('map_img');
			var obj = el;
			do {
				x += obj.offsetLeft;
				y += obj.offsetTop;
				obj = obj.offsetParent;
			} while (obj);
			
			var objs = document.getElementsByTagName("div");
			var txt = ''
			
			for (var i=0; i < objs.length; i++) {
				if (objs[i].className == "itcanbemoved" && objs[i].style.visibility != "hidden") {
					xx = objs[i].style.left.replace('px','');
					yy = objs[i].style.top.replace('px','');
					txt = txt + objs[i].id + ',' + (xx-x) + ',' + (yy-y) + ',' + objs[i].style.width + ',' + objs[i].style.height + ';';
				}
			}
			
			var id_type = 'elem_'+document.f.type.value;
			var url_aux = urlencode(document.f.url.value);
			if (document.f.type.value == "host" || document.f.type.value == "net" || document.f.type.value == "sensor") {
				url_aux = (document.getElementById('check_report').checked) ? "REPORT" : "";
			}
			
			var icon_aux = urlencode(document.getElementById("chosen_icon").src);
			url_aux = url_aux.replace(/\//g,"url_slash");
			url_aux = url_aux.replace(/\%3F/g,"url_quest");
			url_aux = url_aux.replace(/\%3D/g,"url_equal");
			icon_aux = icon_aux.replace(/\//g,"url_slash");
			icon_aux = icon_aux.replace(/\%3F/g,"url_quest");
			icon_aux = icon_aux.replace(/\%3D/g,"url_equal");
						
			var type        = $("#type").serialize()
			var type_name   = $("#elem").serialize()
			var id          = document.f.alarm_id.value;
			var alarm_name  = $("#alarm_name").serialize()
			var iconbg      = document.f.iconbg.value;
			var iconsize    = document.f.iconsize.value; 
			
			urlsave = 'save.php?'+ type +'&'+ type_name +'&map=' + map + '&id=' + id + '&' + alarm_name + '&url=' + url_aux + '&icon=' + icon_aux + '&data=' + txt + '&iconbg=' + iconbg + '&iconsize=' + iconsize;

			document.getElementById('state').innerHTML = "<img src='../pixmaps/loading.gif' width='20' align='absmiddle'> <?php echo _("Saving changes") ?>...";
			$.ajax({
			   	type: "GET",
			   	url: urlsave,
			   	success: function(msg){
			   		document.getElementById('state').innerHTML = "<?php echo _("Indicators saved.") ?>";
			   		changed = 0;
			   		document.getElementById('save_button').className = "lbutton";
			   		refresh_indicators();
			   							
					$('#type_name'+id).value    = document.getElementById('elem').value;
			   		$('#datatype'+id).value     = document.f.type.value;
					$('#datanurl'+id).value     = document.f.url.value;
			   		$('#dataicon'+id).value     = $("#chosen_icon").attr("src");
			   		$('#dataiconsize'+id).value = document.f.iconsize.value;
			   		$('#dataiconbg'+id).value   = document.f.iconbg.value;
				}
			});
		}

		function refresh_indicators() 
		{
			document.getElementById('state').innerHTML = "<img src='../pixmaps/loading.gif' width='20' align='absmiddle'/> <?php echo _("Refreshing indicators") ?>...";
			$.ajax({
			   type: "GET",
			   url: "get_indicators.php?map=<?php echo $map ?>&print_inputs=1",
			   success: function(msg){
				// Output format ID_1####DIV_CONTENT_1@@@@ID_2####DIV_CONTENT_2...
				   var indicators = msg.split("@@@@");
				   for (i = 0; i < indicators.length; i++) if (indicators[i].match(/\#\#\#\#/)) 
				   {
						var data = indicators[i].split("####");
						if (data[0] != null) 
							document.getElementById(data[0]).innerHTML = data[1];
						
				   }
				   document.getElementById('state').innerHTML = "";
			   }
			});	
		}

		function chk(fo) {
			if  (fo.name.value=='') {
				alert("<?php echo _("Icon requires a name!") ?>");
				return false;
			}
			return true;
		}
		
		function view() { document.location.href = '<? echo $SCRIPT_NAME ?>?map=<? echo $map ?>&type=' + document.f.type.value }	
		
		function change_select()
		{
			var style = 'background-color:#EFEBDE; padding:2px 5px 2px 5px; border:1px dotted #cccccc; font-size:11px; width: 90%';
			document.getElementById('selected_msg').innerHTML = "<div style='"+style+"'><strong><?php echo _("Selected type")?></strong>: "+document.f.type.value+" - "+document.f.elem.value+"</div>";
			
			if (document.f.type.value == "host_group") {
				document.getElementById('linktoreport').style.display = 'none';
			}
			else {
				document.getElementById('linktoreport').style.display = '';
			}
			if (document.f.url.value.match(/view\.php/)) {
				document.getElementById('link_option_map').checked = true;
				show_maplink();
			} else {
				document.getElementById('link_option_asset').checked = true;
				show_assetlink();
			}
		}
		
		function show_maplink() {
			document.getElementById('link_map').style.display = "block";
			document.getElementById('link_asset').style.display = "none";
			if (!document.f.url.value.match(/view\.php/)) document.f.url.value = "";
			document.getElementById('check_report').checked = false;
			document.f.type.value = "";
			document.f.elem.value = "";
			document.getElementById('selected_msg').innerHTML = "";
		}
		function show_assetlink() {
			document.getElementById('link_map').style.display = "none";
			document.getElementById('link_asset').style.display = "block";
		}

		function set_changed() {
			document.getElementById('save_button').className = 'lbutton_unsaved';
			changed=1;
		}
		
		function checkSaved()
		{
			// Disable, this seems to break something
			if(changed)
			{
				//(if(0){
				var x=window.confirm("<?php echo  _("Unsaved changes, want to save them before exiting?"); ?>");
				if(x)
				{
					save('<?php echo $map ?>');
					return true;
				} 
				else 
					return true;
			}		
		}
		
		$(document).ready(function() {
			initDiv();
			
			// Tree
			load_tree("");
		});
				
		
	</script>
	
	<style type="text/css">
				
		#loading {
			position: absolute; 
			width: 99%; 
			height: 99%; 
			margin: auto; 
			text-align: center;
			background: #FFFFFF;
			z-index: 10000;
		}
		
		#loading div{
			position: relative;
			top: 40%;
			margin:auto;
		}
		
		#loading div span{
			margin-left: 5px;
			font-weight: bold;	
		}
		
		#tree{ position: relative;}
		
		.tree_container{
			width: 300px;
			postion: absolute;
			top: 0px;
			left: 0px;
			z-index: 100;
		}
		
	</style>
	
</head>

<body class='ne1' oncontextmenu="return true;" onunload='checkSaved();'>

<div id='loading'>
	<div><img src='../pixmaps/loading3.gif' alt='<?php echo _("Loading")?>'/><span><?php echo _("Loading")?>...</span></div>
</div>

<table class='noborder' border='0' cellpadding='0' cellspacing='0'>
	
	<?php
		$maps = explode("\n",`ls -1 'maps'`);
		$i=0; $n=0; $linkmaps = "";
		foreach ($maps as $ico) if (trim($ico)!="") 
		{
				if(is_dir("maps/" . $ico) || !getimagesize("maps/" . $ico))
					continue;
				$n = str_replace("map","",str_replace(".jpg","",$ico));
				$linkmaps .= "<td><a href='javascript:;' onclick='document.f.url.value=\"view.php?map=$n\"'><img src='maps/$ico' border=0 width=50 height=50 style='border:1px solid #cccccc' alt='$ico' title='$ico'></a></td>";
				$i++; if ($i % 3 == 0) $linkmaps .= "</tr><tr>";
		}
	?>
	
	<tr>
		<td valign='top' class='ne1' nowrap='nowrap' style="padding:5px;">
		
		<table width="100%">
			<tr><th colspan="2" class='rm_tit_section'><?php echo _("Icons")?></th></tr>
			<tr>
				<td colspan='2' class='ne1' id="uploadform" style="display:none;">
					<form action="index.php" method='post' name='f2' enctype="multipart/form-data" onsubmit="return chk(document.f2)">
						<table id='rm_up_icon' width='100%'>
							<tr>
								<th><?php echo _("Name Icon")?>:</th>
								<td><input type='text' class='ne1' name='name'/></td>
							</tr>
							<tr>
								<th><?php echo _("Upload icon file")?>:</th>
								<td>
									<input type='file' class='ne1' size='15' name='fichero'/>
									<input type='hidden' value="<?php echo $map ?>" name='map'>
								</td>
							</tr>
							<tr><td class='cont_submit' colspan='2'><input type='submit' value="<?php echo  _("Upload") ?>" class="lbutton"/></td></tr>
						</table>
					</form>
				</td>
			</tr>
			
		<form name="f" action="modify.php">
			<tr>
				<td>
					<div style="display:none">
						<input type='hidden' name="alarm_id" value=""/> x <input type='text' size='1' name='posx'/> y <input type='text' size='1' name='posy'/>
					</div>
				</td>
			</tr>
			
			<tr>
				<td>
				<?php
					$docroot = "/var/www/";
					$resolution = "128x128";
					$icon_cats = explode("\n",`ls -1 '$docroot/ossim_icons/Regular/'`);
					
					echo "<select id='category' name='categories'>
							<option value=\"standard\">Default Icons</option>
							<option value=\"flags\">Country Flags</option>
							<option value=\"custom\">Own Uploaded</option>";
						
							foreach($icon_cats as $ico_cat)
							{
								if(!$ico_cat)continue;
								
								echo "<option value=\"$ico_cat\">$ico_cat</option>";
							}
					echo "</select>";

					/*
					$resolutions = array("16x16", "24x24", "32x32", "48x48", "72x72", "128x128", "256x256");
					print "<br/>";
					$i = 0;
					foreach($resolutions as $ress){
					print "<input type='radio' name='resolution2' value='$ress' ".($ress==$resolution ? "checked" : "")."><small>$ress</small>";
					$i++; if ($i % 3 == 0) echo "</br>";
					}*/
				?>
				</td>
			
				<td rowspan="2" align="center" valign="middle" width="40%">
					<img src="<?php echo (($uploaded_icon) ? $filename : "pixmaps/standard/default.png")?>" name="chosen_icon" id="chosen_icon"/>
				</td>
			</tr>
		
			<tr>
				<td align="left">
					<a href="chooser.php" title="Icon browser" class="greybox" style="font-size:12px"><?php echo _("Browse all")?></a>
					<span> / </span>
					<!-- <a href="javascript:loadLytebox()" id="lytebox_misc" title="Icon chooser" style="font-size:12px" rev="width: 400px; height: 300px;scrolling: no;"><?php echo _("Choose from list")?></a> -->
					<a href="chooser.php?mode=slider" title="Icon chooser" class="greybox2" style="font-size:12px"><?php echo _("Choose from list")?></a>
					<span> / </span>
					<a onclick="$('#uploadform').show();return false" style="font-size:12px"><?php echo _("Upload your own icon")?></a><br/>
				</td>
			</tr>
			<tr>
				<td colspan="2" class='bold'><?php echo _("Background")?>: 
					<select name="iconbg" id="iconbg" onchange="set_changed()">
						<option value=""><?php echo _("Transparent")?></option>
						<option value="white"><?php echo _("White")?></option>
					</select>
				</td>
			</tr>
			
			<tr>
				<td colspan="2" class='bold'><?php echo _("Size")?>: 
					<select name="iconsize" id="iconsize" onchange="set_changed()">
						<option value="0"><?php echo _("Default")?></option>
						<option value="30"><?php echo _("Small")?></option>
						<option value="40"><?php echo _("Medium")?></option>
						<option value="50"><?php echo _("Big")?></option>
					</select>
				</td>
			</tr>
		</table>
	
	<?php
	if(0)
	{
		?>
		<table>
		<!-- iconos -->
		<tr>
			<td class='ne1' colspan='2'>
				<table>
					<tr>
					<?php
						$ico_std = explode("\n",`ls -1 'pixmaps/standard'`);
						$i=0;
						foreach ($ico_std as $ico)
						{ 
							if (trim($ico)!="") 
							{
								if(is_dir("pixmaps/standard/" . $ico) || !getimagesize("pixmaps/standard/" . $ico)) 
									continue;
								
								echo "<td><img src='pixmaps/standard/$ico' border='0'/></td>
									  <td align='center'><input type='radio' name='icon' value='pixmaps/standard/$ico'".(($i==0) ? " checked='checked'" : "")."></td>";
							$i++; 
							
							if ($i % 6 == 0) 
							
							echo "</tr><tr>";
							}
						
						}
						$ico_std = explode("\n",`ls -1 'pixmaps/uploaded'`);
						foreach ($ico_std as $ico) 
						{
							if (trim($ico)!="") 
							{
								if(is_dir("pixmaps/uploaded/" . $ico) || !getimagesize("pixmaps/uploaded/" . $ico))
									continue;
								
								echo "<td><img src='pixmaps/uploaded/$ico' border='0'></td>
								      <td align='center'><input type='radio' name='icon' value='pixmaps/uploaded/$ico'/>
									  <br/><a href='$SCRIPT_NAME?map=$map&delete_type=icon&delete=".urlencode("$ico")."'><img src='images/delete.png' border='0'/></a>
									  <a href=\"pixmaps/uploaded/$ico\" rel=\"lytebox[test]\" title=\"&lt;a href='javascript:alert(&quot;placeholder&quot;);'&gt;Click HERE!&lt;/a&gt;\">AAAAA</a></td>";
							$i++; if ($i % 6 == 0) echo "</tr><tr>";
						}	
						}	
					?>
					</tr>
				</table>
			</td>
		</tr>
	 <?php
	 } // end if(0)
	 ?>
 
 
 <!-- types -->
 <br/>
 <input type="hidden" name="type" id="type" value=""/>
 <input type="hidden" name="elem" id="elem" value=""/>
 
 <table width="100%">
	<tr>
		<td class='ne1'>
			<table width="100%" class="noborder">
				<tr>
					<th class='rm_tit_section' style="font-size:12px" nowrap='nowrap'><?php echo  _("Indicator Name"); ?></th>
					<td><input type='text' size='30' name="alarm_name" id='alarm_name' class='ne1'/></td>
				</tr>
				<tr><td colspan="2" id="selected_msg"></td></tr>
				
				<tr>
					<td colspan="2" class='ne1'><input type="radio" onclick="show_assetlink()" name="link_option" id="link_option_asset" value="asset" checked='checked'></input><?php echo _("Link to Asset") ?></td>
				</tr>
				
				<tr>
					<td colspan="2">
						<table width="100%" id="link_asset" style="display:block;border:0px">
							<tr>
								<td class='nobborder'>
									<div id="tree"></div>
								</td>
							</tr>
							<tr id="linktoreport" style="display:none">
								<td class="nobborder">
									<table style="border:0px"><tr>
										<td><input type="checkbox" id="check_report"/></td>
										<td class='ne1' nowrap='nowrap'><i><?php echo  _("Link to Asset Report"); ?></i></td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>

				<tr>
					<td colspan="2" class='ne1'><input type="radio" onclick="show_maplink()" name="link_option" id="link_option_map" value="map"></input><?php echo _("Link to Map") ?></td>
				</tr>
				<tr>
					<td colspan="2">
						<table id="link_map" style="display:none">
						<tr id="linktomapurl">
							<td class='ne11'> <?php echo  _("URL"); ?> </td>
							<td><input type='text' size='30' name="url" class='ne1'/></td>
						</tr>
						<tr id="linktomapmaps">
							<td class='ne1 bold'><i> <?php echo  _("Choose map to link") ?> </i></td>
							<td><table><tr><? echo $linkmaps ?></tr></table></td>
						</tr>
						</table>
					</td>
				</tr>
				<tr><td colspan="2" id="state" class="ne" height="30">&nbsp;</td></tr>
				<tr>
					<td colspan="2" nowrap='nowrap'>
						<input type='button' value="<?php echo  _("New Indicator") ?>" onclick="addnew('<? echo $map ?>','alarm')" class="lbutton" /> 
						<input type='button' value="<?php echo  _("New Rect") ?>" onclick="addnew('<? echo $map ?>','rect')" class="lbutton"/> 
						<input id="save_button" type='button' value="<?php echo  _("Save Changes") ?>" onclick="save('<?php echo $map ?>')" class="lbutton"/>
					</td>
				</tr>	
			</table>
		</td>
	</tr>
	
	<tr><td id="tdnuevo"></td></tr>
</table>


<?php
// *************** Print Indicators DIVs (print_inputs = true) ******************
print_indicators($map,true);

$conn->close();

$uploaded_dir  = "pixmaps/uploaded/";
$uploaded_link = "pixmaps/uploaded/";

$icons = explode("\n",`ls -1 '$uploaded_dir'`);
print "<div style=\"display:none;\">";
$i     = 0;

foreach($icons as $ico)
{
	if(!$ico)
		continue;
	if(is_dir($uploaded_dir . "/" .  $ico) || !getimagesize($uploaded_dir . "/" . $ico)) 
		continue;
			
	print "<a href=\"$uploaded_link/$ico\" id=\"custom-$i\" rel=\"lytebox[custom]\" title=\"&lt;a href='javascript:choose_icon(&quot;$uploaded_link/$ico&quot;);'&gt;" . htmlspecialchars(_("Choose this one")) . ".&lt;/a&gt;\">custom</a>";
	$i++;
}

$uploaded_dir  = "pixmaps/flags/";
$uploaded_link = "pixmaps/flags/";

$icons = explode("\n",`ls -1 '$uploaded_dir'`);
print "<div style=\"display:none;\">";

$i = 0;
foreach($icons as $ico)
{
	if(!$ico)
		continue;
		if(is_dir($uploaded_dir . "/" .  $ico) || !getimagesize($uploaded_dir . "/" . $ico))
			continue;
	print "<a href=\"$uploaded_link/$ico\" id=\"flags-$i\" rel=\"lytebox[flags]\" title=\"&lt;a href='javascript:choose_icon(&quot;$uploaded_link/$ico&quot;);'&gt;" . htmlspecialchars(_("Choose this one")) . ".&lt;/a&gt;\">flags</a>";
	$i++;
}


$standard_dir  = "pixmaps/standard/";
$standard_link = "pixmaps/standard/";

$icons = explode("\n",`ls -1 '$standard_dir'`);
print "<div style=\"display:none;\">";

$i = 0;

foreach($icons as $ico)
{
	if(!$ico)
		continue;
	if(is_dir($standard_dir . "/" . $ico) || !getimagesize($standard_dir . "/" . $ico))
		continue;

		print "<a href=\"$standard_link/$ico\" id=\"standard-$i\" rel=\"lytebox[standard]\" title=\"&lt;a href='javascript:choose_icon(&quot;$standard_link/$ico&quot;);'&gt;" . htmlspecialchars(_("Choose this one")) . ".&lt;/a&gt;\">standard</a>";

	$i++;
}

print "</div>\n";
?>
	</form>
		</td>
		<td width="48" valign='top'>
			<img src='images/wastebin.gif' id="wastebin" border='0'/>
		</td>
		<td valign='top' id="map">
			<img src="maps/map<?php echo $map ?>.jpg" id="map_img" onclick="reset_values()" border='0'/>
		</td>
	</tr>
</table>
</body>
</html>
