function load_tree(encode_tree, key, mode){
	var tree = "[" + Base64.decode(encode_tree) + "]";
	var lk = null;
	
	if (nodetree!=null) {
		nodetree.removeChildren();
		$(layer).remove();
	}
	
	layer = '#srctree'+i;
	$('#tree_container_bt').append('<div id="srctree'+i+'" style="width:100%"></div>');
	$(layer).html(messages[0]);
	$(layer).dynatree({
		onActivate: function(dtnode) {
			
			if (dtnode.data.key != "load_error")
				draw(dtnode);					
		},
		children: eval(tree)
	});
	nodetree = $(layer).dynatree("getRoot");
	
	activate_node(key, mode);
	
	i=i+1;
}


function activate_node(key, mode)
{
	if ($(layer).dynatree("getTree").getNodeByKey(key) != null)
		lk = key;
	else
	{
		var parent = key.substring(0, key.lastIndexOf('_')); 
		
		if ($(layer).dynatree("getTree").getNodeByKey(parent) != null)
			lk = parent;
	}
		
	if (lk != null)
	{
		$(layer).dynatree("getTree").getNodeByKey(lk).focus();
		$(layer).dynatree("getTree").getNodeByKey(lk).expand(true);
		if (mode == "normal")
			$(layer).dynatree("getTree").getNodeByKey(lk).activate();
		else
			$(layer).dynatree("getTree").getNodeByKey(lk).activateSilently();
		
	}

}


function load_tab1()
{
	var node = $(layer).dynatree("getTree").getActiveNode();
	
	//Loading div
	$(".tab_content").css('display', 'none');
		
	if ( $("#msg_load").length >= 1 )
		$("#msg_load").remove();
	
	$(".tab_container").before("<div id='msg_load'>"+messages[1]+"</div>");		
	
	if (node != null)
		draw(node);
	
	$("#msg_load").remove();
	$("#tab1").css('display', 'block');
}

function load_tab2(editor)
{				
	var file = $('#rules option:selected').attr('value');
	
	//Loading div
	$("#tab2 div").css('display', 'none');
	
	if ( $("#msg_load").length >= 1 )
		$("#msg_load").remove();
	
	$("#tab2").append("<div id='msg_load'>"+messages[1]+"</div>");								
		
	$.ajax({
		type: "POST",
		data: "file="+ file,
		url: "ajax/get_content_cm.php",
		success: function(msg){

			var msg_int = parseInt(msg);
			var txt = "";
			var style = "";
																	
			if ( isNaN(msg_int) )
			{	
				editor.setCode(msg);
								
				if (file != editable_files[0])
					$(".button").remove();
				else
				{
					if ( $('.button').length < 1 )
					{
						var button_save = "<div class='button'><input type='button' class='save' id='send' value='"+label[12]+"'/></div>";
						$(".buttons_box").html(button_save);
						$('#send').bind('click', function() { save(editor); });
					}
				}
				
			}
			else
			{
				style = 'oss_error';
				editor.setCode('');
				switch (msg_int){
					case 1:
					txt = messages[12];
					break;
					
					case 2:
					txt = messages[3];
					break;
					
					case 3:
					txt = messages[2];
					break;
				}
				
				if ( file != editable_files[0] )
					$(".button").remove();
				else
				{
					if ( $('.button').length<1 )
					{
						var button_save = "<div class='button'><input type='button' class='save' id='dis_send' disabled='disabled' value='"+label[12]+"'/></div>";
						$(".buttons_box").html(button_save);
					}
					
				}
												
				if ( $("#results").length >= 1 )
				{
					$('#results').html('');
					$('#results').append("<div id='msg_edit'></div>");
					$('#msg_edit').addClass(style);
					$('#msg_edit').html(txt);
					$('#msg_edit').fadeIn(2000);
					setTimeout('$("#msg_edit").fadeOut(4000);', 4000);
				}
			}
			
			$("#msg_load").remove();
			$("#tab2 div").css('display', '');
			
		}
	});
		
}

function save(editor){
	
	$.ajax({
		type: "POST",
		url: "ajax/save.php",
		data: "data="+Base64.encode(htmlentities(editor.getCode(), 'HTML_ENTITIES')),
		success: function(msg){
		 
			var txt    = "";
			var style  = "";
			var status = msg.split("###");
			var code   = parseInt(status[0]);
			$(".save").css("width", "110px");
			$("#send").val(label[13]);
				 
			switch (code){
				case 1:
					txt = status[1];
					style = 'oss_error';
				break;
				
				case 2:
					txt = status[1];
					style = 'oss_error';
				break;
				
				case 3:
					txt = messages[4];
					style = 'oss_error';
				break;
				
				case 4:
					txt = messages[5];
					style = 'oss_error';
				break;
				
				case 5:
					txt =  "<span style='font-weight: bold;'>"+messages[13]+"<a onclick=\"$('#msg_errors').toggle();\"> ["+messages[14]+"']</a><br/></span>";
					txt += "<div id='msg_errors'>"+status[1]+"</div>";
					style = 'oss_error';
				break;
				
				case 6:
					txt = messages[6];
					style = 'oss_success';
					
					var key = $(layer).dynatree("getTree").getActiveNode();
					
										
					if (  key != null )
						key = key.data.key;
					else
						key = 1;
					
					show_tree(false, key, 'silently');
					
				break;
			}
		 
			if ( $('#results').length >= 1 )
			{
				$('#results').html('');
				$('#results').append("<div id='msg_edit'></div>");
			 	
				if (code != 5)
				{
					$('#msg_edit').addClass(style);
					$('#msg_edit').html(txt);
					$('#msg_edit').fadeIn(2000);
					setTimeout('$("#msg_edit").fadeOut(4000);', 4000);
				}
				else
				{
					$('#msg_edit').append("<div id='parse_errors'></div>");
					$('#parse_errors').addClass(style);
					$('#parse_errors').html(txt);
					$('#parse_errors').fadeIn(2000);
					window.scroll(0,0);
					setTimeout('$("#msg_edit").fadeOut(4000);', 20000);
				}
			}
									
			$("#send").val(label[12]);
			$(".save").css("width", "90px");
	    }
	});
}

function add_at(id, type, path)
{
	var new_id = get_new_id(id);
					
	switch (type){
		case 'ats':
			var title= label[0];
			var t_actions = "actions_bt_at";
			var actions = "<td class='"+ t_actions +"' style='width:75px;'>" 
							+ "<a onclick=\"add_at('"+new_id+"', '"+type+"', '"+path+"');\"><img src='"+path+"/add.png' alt='"+label[2]+"' title='"+ label[2] +" "+ title + "'/></a>\n"
							+ "<a onclick=\"delete_at('"+new_id+"', '"+type+"', '"+path+"');\"><img src='"+path+"/delete.gif' alt='"+label[3]+"' title='"+ label[3] +" "+ title + "'/></a>\n"
							+ "<a onclick=\"clone_at('"+new_id+"', '"+type+"', '"+path+"');\"><img src='"+path+"/clone.png' alt='"+label[4]+"' title='"+ label[4] +" "+ title + "'/></a>\n"
						+ "</td>";
		break;
		
		case 'at':
			var title= label[0];
			var t_actions = "actions_bt_at";
			var actions = "<td class='"+ t_actions +"' style='width:75px;'>" 
								+ "<a onclick=\"delete_at('"+new_id+"', '"+type+"', '"+path+"');\"><img src='"+path+"/delete.gif' alt='"+label[3]+"' title='"+ label[3] +" "+ title + "'/></a>"
							+ "</td>";
		break;
		
		case 'txt_node':
			var title=label[1];
			var t_actions = "actions_bt_tn";
			var actions = "<td class='"+ t_actions +"' style='width:75px;'>" 
								+ "<a onclick=\"delete_at('"+new_id+"', '"+type+"', '"+path+"');\"><img src='"+path+"/delete.gif' alt='"+label[3]+"'  title='"+ label[3] +" "+ title + "'/></a>"
							+ "</td>";
		break;	

		
		case 'txt_nodes':
			var title=label[1];
			var t_actions = "actions_bt_tn";
			var actions = "<td class='"+ t_actions +"' style='width:95px;'>" 
							+ "<a onclick=\"add_at('"+new_id+"', '"+type+"', '"+path+"');\"><img src='"+path+"/add.png' alt='"+label[2]+"' title='"+ label[2] +" "+ title + "'/></a>\n"
							+ "<a onclick=\"delete_at('"+new_id+"', '"+type+"', '"+path+"');\"><img src='"+path+"/delete.gif' alt='"+label[3]+"' title='"+ label[3] +" "+ title + "'/></a>\n"
							+ "<a onclick=\"clone_at('"+new_id+"', '"+type+"', '"+path+"');\"><img src='"+path+"/clone.png' alt='"+label[4]+"' title='"+ label[4] +" "+ title + "'/></a>\n"
							+ "<a onclick=\"show_at('"+new_id+"');\"><img src='"+path+"/show.png' alt='"+label[5]+"' title='"+ label[5] + title + "'/></a>\n"
						+ "</td>";
		break;	
							
	}
	
	var element = "<tr id='"+ new_id +"'>"
		+ "<td class='n_name'  id='cont_n_label-"+new_id+"'><input type='text' class='n_input auto_c' name='n_label-"+new_id+"' id='n_label-"+new_id+"' value=''/></td>"
		+ "<td class='n_value' id='cont_n_txt-"+new_id+"'><textarea name='n_txt-"+new_id+"' id='n_txt-"+new_id+"'></textarea></td>"
		+  actions
	+ "</tr>";
	
		
	$('#'+id).after(element);
	
	$('textarea').bind('focus', function() { $(this).css('color', '#2F85CA');});
	$('textarea').bind('blur',  function() { $(this).css('color', '#000000');});
    $('textarea').elastic();	
	$("input[type='text']").bind('focus', function() { $(this).css('color', '#2F85CA');});
	$("input[type='text']").bind('blur',  function() { $(this).css('color', '#000000');});	
	
	set_autocomplete(".auto_c");
}



function delete_at(id, type, path)
{
	var id_txt_nodes = "#ats_"+id;
	var id           = "#"+id;
	var parent       = $(id).parent();
	
	$(id).remove();
		
	if ( $(id_txt_nodes).length >=1)
		$(id_txt_nodes).remove();
	
	var children = parent.children().length;
	
	
	
	if (children == 2)
	{
		var last_child = $("#"+parent.attr('id')+" tr:last-child").attr("id");
		add_at(last_child, type, path);
	}
}

function clone_at(id)
{
	
	
	var new_id   = get_new_id(id);
	var aux_id   = uniqid();
	var reg      = new RegExp(id, "g");
	
	var name     = $("#n_label-"+id).val();
	var value    = $("#n_txt-"+id).val();
	
	
	var element  = "<tr id='"+ aux_id +"' style='display:none;'>"+$("#"+id).clone(true).html()+"</tr>";
	element      = element.replace(reg, aux_id);
	
	var reg2     = new RegExp(aux_id, "g");
	element      = element.replace(reg2, new_id);
	
	$("#"+id).after(element);
	$("#n_label-"+new_id).val(name);
	$("#n_txt-"+new_id).val(value);
	$("#"+new_id).css('display', '');
	
	$('textarea').bind('focus', function() { $(this).css('color', '#2F85CA');});
	$('textarea').bind('blur',  function() { $(this).css('color', '#000000');});	 
	$('textarea').elastic();
	$("input[type='text']").bind('focus', function() { $(this).css('color', '#2F85CA');});
	$("input[type='text']").bind('blur',  function() { $(this).css('color', '#000000');});	
	
	set_autocomplete(".auto_c");
}

function show_at(id) { 

var display = $("#"+id).css('display');

if (display == 'none')
	$("#"+id).fadeIn(1000);
else
	hide_at(id)

}

function hide_at(id) { $("#"+id).fadeOut(1000);}


function add_node(id, type, path)
{
	var new_id = uniqid();
	var id= '#'+id;
	
	var title=label[1];
	var actions = "<td class='actions_bt_tn' style='width:95px;'>" 
					+ "<a onclick=\"add_node('"+new_id+"', '"+type+"', '"+path+"');\"><img src='"+path+"/add.png' alt='"+label[2]+"' title='"+ label[2] +" "+ title + "'/></a>\n"
					+ "<a onclick=\"delete_at('"+new_id+"', '"+type+"', '"+path+"');\"><img src='"+path+"/delete.gif' alt='"+label[3]+"' title='"+ label[3] +" "+ title + "'/></a>\n"
					+ "<a onclick=\"clone_node('"+new_id+"', '"+type+"', '"+path+"');\"><img src='"+path+"/clone.png' alt='"+label[4]+"' title='"+ label[4] +" "+ title + "'/></a>\n"
					+ "<a onclick=\"show_at('ats_"+new_id+"');\"><img src='"+path+"/show.png' alt='"+label[5]+"' title='"+ label[5] + title + "'/></a>\n"
				+ "</td>\n";
		
							
		
	var element = 
		"<tr id='"+ new_id +"'>"
			+ "<td class='n_name'  id='cont_n_label-"+new_id+"'><input type='text' class='n_input auto_c' name='n_label-"+new_id+"' id='n_label-"+new_id+"' value=''/></td>"
			+ "<td class='n_value' id='cont_n_txt-"+new_id+"'><textarea name='n_txt-"+new_id+"' id='n_txt-"+new_id+"'></textarea></td>"
			+  actions
		+ "</tr>\n"
		+ "<tr id='ats_"+ new_id +"' style='display: none;'>\n"
			+ "<td colspan='3'>\n"
				+ "<div class='cont_ats_txt_node'>\n"
					+ "<table class='er_container'>\n"
					+ "<tbody id='erb_"+ new_id +"'>\n"
						+ "<tr id='subheader_"+ new_id +"'>\n"
							+ "<th class='txt_node_header' colspan='3'>\n"
								+ "<div class='fleft'><img src='"+ path +"/arrow.png' alt='"+label[6]+"' align='top'/><span>"+label[7]+"</span></div>\n"
								+ "<div class='fright'><a style='float: right' onclick=\"hide_at('ats_"+ new_id +"');\"><img src='"+ path +"/arrow-up.png' alt='"+label[6]+"' title='"+ label[11] + "' align='absmiddle'/></a></div>\n"
							+ "</th>\n"
						+ "</tr>\n"
						+ "<tr id='subheader2_"+ new_id +"'>\n"
							+ "<th class='r_subheader'>"+label[8]+"</th>\n"
							+ "<th class='r_subheader'>"+label[9]+"</th>\n"
							+ "<th class='r_subheader actions_at'>"+label[10]+"</th>\n"
						+ "</tr>\n"
						+ "<tr id='"+ new_id +"_at1'>\n"
							+ "<td class='n_name' id='cont_n_label-"+ new_id +"_at1'><input type='text' class='n_input auto_c' name='n_label-"+ new_id +"_at1' id='n_label-"+ new_id +"_at1' value=''/></td>\n"
							+ "<td class='n_value' id='cont_n_txt-"+ new_id +"_at1'><textarea name='n_txt-"+ new_id +"_at1' id='n_txt-"+ new_id +"_at1'></textarea></td>\n"
							+ "<td class='actions_bt_at'>\n"
								+ "<a onclick=\"add_at('"+ new_id +"_at1', 'ats', '"+ path +"');\"><img src='"+ path +"/add.png' alt='"+label[2]+"' title='"+ label[2] +" "+ title + "'/></a>\n"
								+ "<a onclick=\"delete_at('"+ new_id +"_at1','ats', '"+ path +"');\"><img src='"+ path +"/delete.gif' alt='"+label[3]+"' title='"+ label[3] +" "+ title + "'/></a>\n"
								+ "<a onclick=\"clone_at('"+ new_id +"_at1');\"><img src='"+ path +"/clone.png' alt='"+label[4]+"' title='"+ label[4] +" "+ title + "'/></a>\n"
							+ "</td>\n"
						+ "</tr>\n"
					+ "</tbody>\n"
					+ "</table>\n"					
				+ "</div>\n"
			+ "</td>\n"
		+ "</tr>";
		
			
	$(id).after(element);
	
	$('textarea').bind('focus', function() { $(this).css('color', '#2F85CA');});
	$('textarea').bind('blur',  function() { $(this).css('color', '#000000');});	 
	$('textarea').elastic();
	$("input[type='text']").bind('focus', function() { $(this).css('color', '#2F85CA');});
	$("input[type='text']").bind('blur',  function() { $(this).css('color', '#000000');});	
	
	set_autocomplete(".auto_c");
}



function clone_node(id)
{
	var new_id   = uniqid();
	var reg      = new RegExp(id, "g");
	
	var element = 
		"<tr id='"+ new_id +"' style='display:none;'>"+$("#"+id).clone(true).html()+"</tr>" + 
		"<tr id='ats_"+ new_id +"' style='display:none;'>"+$("#ats_"+id).clone(true).html()+"</tr>";
	element = element.replace(reg, new_id);
				
	$("#ats_"+id).after(element);
	
	var name  = $("#n_label-"+id).val();
	var value = $("#n_txt-"+id).val();
	$("#n_label-"+new_id).val(name);
	$("#n_txt-"+new_id).val(value);
	
	var inputs          = $("#ats_"+id + " input");
	var textareas       = $("#ats_"+id + " textarea");
	var inputs_clone    = $("#ats_"+new_id + " input");
	var textareas_clone = $("#ats_"+new_id + " textarea");
	
	for (var i=0; i<inputs.length; i++)
	{
		var name  = $("#"+inputs[i].id).val();
		var value = $("#"+textareas[i].id).val();
		$("#"+inputs_clone[i].id).val(name);
		$("#"+textareas_clone[i].id).val(value);
	}
		
	
	$("#"+new_id).css('display', '');
	
	$('textarea').bind('focus', function() { $(this).css('color', '#2F85CA');});
	$('textarea').bind('blur',  function() { $(this).css('color', '#000000');});	 
	$('textarea').elastic();
	$("input[type='text']").bind('focus', function() { $(this).css('color', '#2F85CA');});
	$("input[type='text']").bind('blur',  function() { $(this).css('color', '#000000');});	
	
	set_autocomplete(".auto_c");
}


function delete_child(id, path)
{
	var id = "#"+id;
	var parent = $(id).parent();
	
	
	var children = parent.children().length;
	if (children > 3)
	{
		$(id).remove();
		children = parent.children().length;
		if (children <= 3)
			$(".delete_c").addClass("unbind");
					
	}
	else
	{
		if ( $("#results").length >= 1 )
		{
			$('#results').html('');
			$('#results').append("<div id='msg_edit'></div>");
			$('#msg_edit').addClass('oss_error');
			$('#msg_edit').html(messages[8]);
			$('#msg_edit').fadeIn(2000);
			setTimeout('$("#msg_edit").fadeOut(4000);', 4000);
		}
	}
		
}

function clone_child(id)
{
	var key_parent = '';
	
	var kp = $("#"+id).attr("class").split("-###");
	if (kp[1] == '')	
		key_parent = id;
	else
		key_parent = kp[1];
	
	var new_id = uniqid()+"_clone-"+id;
	var reg = new RegExp(id, "g");
	var element = $("#"+id).clone(true).html();
	element = element.replace(reg, new_id);
	element = "<tr id='"+ new_id +"' style='display:none;' class='__lk-###"+key_parent+"'>"+element+"</tr>";
	$("#"+id).after(element);
	
	var id = "#"+id;
	var parent = $(id).parent();
	var children = parent.children().length;
	if (children > 3)
		$('.delete_c').removeClass("unbind");
	
	set_autocomplete('.auto_c');
	
	$("#"+new_id+" .edit_c").addClass("unbind");
	
	$("#"+new_id).css('display', '');
}


function copy_rule(id)
{
	$.ajax({
		type: "POST",
		url: "ajax/copy_rule.php",
		data: "key="+id,
		success: function(msg){
			var status    = msg.split("###");
			var style     = '';
					
			if ( parseInt(status[0]) != 1)
				style = 'oss_error';
			else
			{
				style = 'oss_success';
				var level_key  = status[2];
				$('#rules').val(editable_files[0]);
				show_tree(false, level_key, 'normal');
			}
											
			if ( $("#results").length >= 1 )
			{
				$('#results').html('');
				$('#results').append("<div id='msg_edit'></div>");
				$('#msg_edit').addClass(style);
				$('#msg_edit').html(status[1]);
				$('#msg_edit').fadeIn(4000);
				setTimeout('$("#msg_edit").fadeOut(4000);', 4000);
			}
				
		}
	});
}


function modify(__level_key)
{
  	$(".save_edit").css("width", "105px");
	$("#send").val(label[13]);
	
	$.ajax({
		type: "POST",
		url: "ajax/modify.php",
		data: $('form').serialize() +"&__level_key="+__level_key,
		success: function(msg){
		 	var status    = msg.split("###");
			var tree      = status[2];	
			var style     = '';
			
			if ( parseInt(status[0]) != 1)
				style = 'oss_error';
			else
			{
				style = 'oss_success';
				load_tree(tree, __level_key, 'silently');	
			}
			
			if ( $("#results").length >= 1 )
			{
				$('#results').html('');
				$('#results').append("<div id='msg_edit'></div>");
				$('#msg_edit').addClass(style);
				$('#msg_edit').html(status[1]);
				$('#msg_edit').fadeIn(2000);
				setTimeout('$("#msg_edit").fadeOut(4000);', 4000);
			}
				
		}
	});
		
	$(".save_edit").css("width", "80px");
	$("#send").val(label[12]);
	
}


function modify_node(__level_key)
{
    var data   = ''
	var nodes  = $("tr[class|=__lk]");
	var id     = ''
	var parent = '';
	var key    = '';
	
	$(".save_edit").css("width", "105px");
	$("#send").val(label[13]);
	
	
	for (var i=0; i<nodes.length; i++)
	{
		data += "&key"+i+"=";
		
		id = nodes[i].id;
		if ( id.match("_clone") == null )		
			data += id;
		else
		{
			parent = $("#"+nodes[i].id).attr("class");
			key = parent.split("-###")
			data += "clone###"+key[1];
		}
	}
	
	$.ajax({
		type: "POST",
		url: "ajax/modify.php",
		data: $('form').serialize()+"&__level_key="+__level_key + data,
		success: function(msg){
		 
			var status = msg.split("###");
			var tree = status[2];
			var style = '';
					
			if ( parseInt(status[0]) == 1)
				load_tree(tree, __level_key, 'silently');
					
			switch (parseInt(status[0])){
				case 1:
					style = 'oss_success';
					break;
				
				case 2:
					style = 'oss_error';
					break;
				
				case 3:
					style = 'oss_error';
					break;
							
			}
		
			if ( $("#results").length >= 1 )
			{
				$('#results').html('');
				$('#results').append("<div id='msg_edit'></div>");
				$('#msg_edit').addClass(style);
				$("#msg_edit").html(status[1]);
				$("#msg_edit").fadeIn(2000);
				setTimeout('$("#msg_edit").fadeOut(4000);', 4000);
			}
				
			
			//Reload right tab if changes have been saved
			
			if ( parseInt(status[0]) == 1 && $(".edit_c").hasClass('unbind') )
				$(layer).dynatree("getTree").getNodeByKey(__level_key).activate();
				
		}
	});
			
	$(".save_edit").css("width", "80px");
	$("#send").val(label[12]);	
	 
}



function draw(dtnode)
{
   var key = dtnode.data.key;
   
   if (key != 1)
   {
	   	var data = "node="+ dtnode.data.title +"&__level_key="+ key;
		
		if ( $('#msg_init').length >= 1 )
			$('#msg_init').remove();
			
		if ( $("#msg_load").length >= 1 )
			$("#msg_load").remove();
	
		$("#tab1").html("<div id='msg_load'>"+messages[1]+"</div>");		
						
		$.ajax({
			type: "POST",
			url:  "ajax/draw_edit.php",
			data: data,
			success: function(msg){
				var params = msg.split("##__##")
				$("#tab1").html(params[2]);
				$('textarea').bind('focus', function() { $(this).css('color', '#2F85CA');});
				$('textarea').bind('blur',  function() { $(this).css('color', '#000000');});	 
				$('textarea').elastic();
				$("input[type='text']").bind('focus', function() { $(this).css('color', '#2F85CA');});
				$("input[type='text']").bind('blur',  function() { $(this).css('color', '#000000');});
				set_autocomplete(".auto_c");
			}
		});
		
		var active_tab = $(".active a").attr("href");
		
		if ( active_tab != "tab1" )
		{
			var tab = $("ul.oss_tabs li:first");
			show_tab_content(tab);
		}
	}
}

function edit_child(level_key)
{
	var key = $(layer).dynatree("getTree").getNodeByKey(level_key);
	
	if (  key != null )
		$(layer).dynatree("getTree").getNodeByKey(level_key).activate();
	else
	{
		if ( $("#results").length >= 1 )
		{
			$('#results').html('');
			$('#results').append("<div id='msg_edit'></div>");
			$('#msg_edit').addClass("oss_info");
			$('#msg_edit').html(messages[16]);
			$('#msg_edit').fadeIn(2000);
			setTimeout('$("#msg_edit").fadeOut(4000);', 4000);
		}

	}
}

function fill_rules(select, file)
{
	$.ajax({
		type: "POST",
		url:  "ajax/fill_rules.php",
		data: "file="+file,
		success: function(html){
			$("#"+select).append(html);
		}
	});

}

function draw_clone()
{
	var clone_file = $('#rules option:selected').attr('value');
		
	if (clone_file == '')
	{
		$('#msg_init').html('');
		var html = "<div class='oss_error'><span>"+messages[9]+"</span></div>";
		$('#msg_init').html(html);
		$('#msg_init').fadeIn(2000);
	}
		
	else
	{
		$.ajax({
			type: "POST",
			url:  "interfaces/edit_6.php",
			data: "file="+clone_file,
			
			success: function(msg){
			
				$("#tab1").html(msg);
				$('textarea').bind('focus', function() { $(this).css('color', '#2F85CA');});
				$('textarea').bind('blur',  function() { $(this).css('color', '#000000');});	
				$('textarea').elastic();	
				$("input[type='text']").bind('focus', function() { $(this).css('color', '#2F85CA');});
				$("input[type='text']").bind('blur',  function() { $(this).css('color', '#000000');});	
			}
			
		});
	}
}

function clone_rf()
{ 
	var new_file = Base64.encode($("#new_filename").val());
	
	$.ajax({
		type: "POST",
		url:  "ajax/clone_file.php",
		data: "new_file="+new_file,
		
		success: function(msg){
		
			var style = '';
			var status = msg.split("###");
		
			switch (parseInt(status[0])){
				
				case 1:
					style = 'oss_error';
					$('textarea').css('color','#D8000C');
					break;
				
				case 2:
					style = 'oss_error';
					break;
				
				case 3:
					style = 'oss_success';
					break;
			}

			if ( $("#results").length >= 1 )
			{
				$('#results').html('');
				$('#results').append("<div id='msg_edit'></div>");
				$('#msg_edit').addClass(style);
				$('#msg_edit').html(status[1]);
				$('#msg_edit').fadeIn(2000);
				setTimeout('$("#msg_edit").fadeOut(4000);', 4000);
			}
		}
			
		});	

}

function show_tree(draw_edit, lk, mode)
{
	var rule_file = $('#rules option:selected').attr('value');
	var tab = null;
	
	if (rule_file == '')
	{
		var html = "<span>"+messages[9]+"</span>";
		
		if ($('#msg_init').length < 1)
		{
			$('#tab1').html('');
			$('#tab1').append("<div id='msg_init'></div>");
		}
		else
			$('#msg_init').html('');
				
		tab = $("ul.oss_tabs li:first");
		
		$('#msg_init').addClass('oss_error');
		$('#msg_init').html(html);
		show_tab_content(tab);
						
	}
	else
	{
		$.ajax({
			type: "POST",
			url: "ajax/get_tree.php",
			data: "file="+ rule_file,
			success: function(msg){
			 
				var status = msg.split("###");
				var tree = status[2];	
								
				if ( parseInt(status[0]) != 1) {
					
					var level_key = "load_error";
					tree = "{title:'<span>"+rules_files+editable_files[0]+"</span>', icon:'../../../pixmaps/theme/any.png', addClass:'size12', isFolder:'true', key:'1', children:[{title: '<span>"+messages[7]+"</span>', icon:'../../../pixmaps/theme/ltError.gif', addClass:'bold_red', key:'"+level_key+"'}]}";
				    tree  = Base64.encode(tree);		 
				}
				else
				{
					level_key = ( lk == '' ) ? "1": lk;
				}
				
				load_tree(tree, level_key, mode);
					
				if (draw_edit == true)
				{
					var style = '';
					var container = '';
					var html = '';
					
					switch (parseInt(status[0])){
						
						case 1:
							style = 'oss_success';
							container = 'msg_init';
							html = "<div class='oss_info'><span>"+status[1]+"</span></div>";
						break;
						
						case 2:
							style = 'oss_error';
							container = 'msg_init';
							html = "<div class='oss_error'><span>"+status[1]+"</span></div>";
						break;
					
						case 3:
							style = 'oss_error';
							container = 'info_file';
							html = "<div id='msg' class='oss_error'>"+status[1]+"</div>";
						break;
					}
					
					var cont_div = "#"+container;
						
										
					$('#tab1').html('');
					$('#tab1').append("<div id="+container+"></div>");
					$(cont_div).html(html);
									
					$(container).fadeIn(2000);
				}
			}
		});
	}
}



function show_tab_content(tab)
{
	$("ul.oss_tabs li").removeClass("active"); //Remove any "active" class
	$(tab).addClass("active"); //Add "active" class to selected tab
	$(".tab_content").hide(); //Hide all tab content
	var activeTab = $(tab).find("a").attr("href"); //Find the rel attribute value to identify the active tab + content
	$(activeTab).show(); //Fade in the active content
	return false;
}

function set_autocomplete(id)
{
	if ($(id).length > 1)
	{	
		$(id).autocomplete(content_ac, {
			minChars: 0,
			width: 250,
			max: 100,
			mustMatch: true,
			autoFill: true
		});
	}
}

function show_actions (editor)
{
    show_tree(true, '', 'silently');
	
	var active = $("ul.oss_tabs li:first").attr("class");
	
	if ( active.match("/active/gi") == null )
		load_tab2(editor);
			   
    var file = $('#rules option:selected').attr('value');
       
    if ( file == editable_files[0] )
    {
        var content = $('#tree_actions').html();
        if (content == '')
        {
            $('#tree_actions').html("<span style='padding-left:10px;'><a id='clone_tree'><img src='images/clone.png' alt='Edit' title='Clone file'/></a></span>");
            $('#clone_tree').bind('click', function() { draw_clone(true); });
        }
    }
    else
        $('#tree_actions').html('');
		
}


function get_new_id(id)
{
	var new_id = null;
	var aux_id = null;
	
	if ( id.match("_clone") == null )
	{
		if (id.match("-") == null)
			new_id = uniqid()+"-"+id;
		else
		{
			aux_id = id.split("-");
			new_id = uniqid()+"-"+aux_id[aux_id.length-1];
		}
	}
	else
	{
		aux_id = id.split("_clone");
		new_id = uniqid()+"_clone-"+aux_id[1];
	}
	
	return new_id;
}





