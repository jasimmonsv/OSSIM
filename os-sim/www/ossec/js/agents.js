function show_tab_content(tab)
{
	$("ul.oss_tabs li").removeClass("active"); //Remove any "active" class
	$(tab).addClass("active"); //Add "active" class to selected tab
	$(".tab_content").hide(); //Hide all tab content
	var activeTab = $(tab).find("a").attr("href"); //Find the rel attribute value to identify the active tab + content
	$(activeTab).show(); //Fade in the active content
	return false;
}

function show_agent(id)
{
	if ( $("#"+id).hasClass("visible") )
	{
		$("#"+id).show();
		$("#"+id).removeClass("visible");
		$("#"+id).addClass("no_visible");
	}
	else
	{
		$("#"+id).hide();
		$("#"+id).removeClass("no_visible");
		$("#"+id).addClass("visible");
	}
}
	
	
function add_agent()
{
	var form_id = $('form[method="post"]').attr("id");
	
	$(".oss_load").html(messages[0]);
				
	$.ajax({
		type: "POST",
		url: "ajax/agent_actions.php",
		data: $('#'+form_id).serialize() + "&action=add_agent",
		success: function(html){
			var status = html.split("###");
			if ( status[0] == "error")
			{
				$(".oss_load").html('');
				
				if ( status[1].match("<br/>") == null )
					var style= '';
				else
					var style = "class='error_left'";
				
				$(".info").html("<div class='oss_error'><div "+style+">"+status[1]+"</div></div>");
				$(".info").fadeIn(2000);
			}
			else
			{
				$(".oss_load").html('');
				
				if ( $('#agent_table .no_agent').length == 1 )
					$('#cont_no_agent').remove();
				
				$('#agent_table tr:last').after(status[3]);					
										
				$('#cont_agent_'+status[2]+' .agent_actions a').bind('click', function() {
					var id = $(this).attr("id");
					get_action(id);
				});
				
				$('#agent_'+status[2]+ ' .agent_id').bind('click', function() {
					var id   = $(this).text();
					var src  = $(this).find("img").attr("src");
					var src1 = "../pixmaps/minus-small.png";
					var src2 = "../pixmaps/plus-small.png";
					
					if (src == src1)
					{
						$("#minfo_"+id).css('display', 'none');
						$(this).find("img").attr("src", src2);
					}
					else
					{
						$("#minfo_"+id).css('display', '');
						$(this).find("img").attr("src", src1);
					}
								
				});
										
				$(".info").html("<div class='oss_success'>"+status[1]+"</div>");
				$(".info").fadeIn(4000);
			}
		}
	});
}

function get_action(id)
{
	var action = null;
	if ( id.match("_key##") != null )	
		send_action(id, 'extract_key');
	else if ( id.match("_del##") != null )
		send_action(id, 'delete_agent');
	else if ( id.match("_check##") != null )
		send_action(id, 'check_agent');	
	else 
	{
		if ( id.match("_restart##") != null )
			send_action(id, 'restart_agent');
	}
}

function send_action(id, action)
{
	var id = id.split("##")
	
	//Load img
	$(".oss_load").html(messages[1]);
	
	$.ajax({
		type: "POST",
		url: "ajax/agent_actions.php",
		data: "id="+ id[1] + "&action="+action,
		success: function(html){
			var status = html.split("###");
			
			if ( status[0] == "error")
			{
				$(".oss_load").css('display', 'none');
				$(".info").html("<div class='oss_error'>"+status[1]+"</div>");
				$(".info").fadeIn(4000);
			}
			else
			{
				$(".oss_load").html('');
				switch (action){
					case "extract_key":
						$(".info").html("<div class='oss_info'>"+status[1]+"</div>");
						$(".info").fadeIn(4000);
					break;
					
					case "delete_agent":
						$("#agent_"+id[1]).parent().remove();
						$(".info").html("<div class='oss_success'>"+status[1]+"</div>"); 
						$(".info").fadeIn(4000);
					break;
					
					case "check_agent":
						$(".info").html("<div class='oss_success'>"+status[1]+"</div>"); 
						$(".info").fadeIn(4000);
					break;
					
					case "restart_agent":
						$(".info").html("<div class='oss_success'>"+status[1]+"</div>");
						$(".info").fadeIn(4000);
					break;
				}
			}
		}
	});
}


function load_agent_tab(tab)
{
	//Add Load img
	if ($('#cnf_load').length < 1)
	{
		$(tab+" div").css('display', 'none');
		var load ="<div id='cnf_load'>"+messages[2]+"</div>";
		$(tab).append(load);
	}
													
	//Remove error message
							
	if ($('#cnf_message').length >= 1)
	{
		$('#cnf_message').removeClass();
		$('#cnf_message').html('<div id="cont_cnf_message"></div>');
	}

		
	$.ajax({
		type: "POST",
		url: "ajax/load_agent_tab.php",
		data: "tab="+tab,
		success: function(msg){
													
			//Remove load img
			
			if ( $('#cnf_load').length >= 1 )
				$('#cnf_load').remove();
				
			var status = msg.split("###");
			var txt    = null;
			
			switch( status[0] )
			{
				case "1":
					if (tab == "#tab1")
					{
						
						$(tab).html(status[1]);
													
						$('#show_agent').bind('click', function() { show_agent("cont_add_agent") });
						$('#send').bind('click', function() { add_agent() });
						
						$("#agent_table tr[id^='cont_agent_']").each(function(index) {
							
							if (index % 2 == 0)
								$(this).css("background-color", "#EEEEEE");
						});
						
						$("#agent_table tr[id^='minfo_']").each(function(index) {
							
							if (index % 2 != 0)
								$(this).css("background-color", "#EEEEEE");
						});		
									
						$('.vfield').bind('blur', function() {
							 validate_field($(this).attr("id"), "ajax/agent_actions.php");
						});
						
						$('#agent_table .agent_actions a').bind('click', function() {
							var id = $(this).attr("id");
							get_action(id);
						});
						
						$('#agent_table .agent_id').bind('click', function() {
							var id = $(this).text();
							var src  = $(this).find("img").attr("src");
							var src1 = "../pixmaps/minus-small.png";
							var src2 = "../pixmaps/plus-small.png";
							if (src == src1)
							{
								$("#minfo_"+id).css('display', 'none');
								$(this).find("img").attr("src", src2);
							}
							else
							{
								$("#minfo_"+id).css('display', '');
								$(this).find("img").attr("src", src1);
							}
										
						});
						
						$(tab).css('display', 'block');
											
					}
					else if (tab == "#tab2")
					{
						if (editor == null)
						{
							editor = new CodeMirror(CodeMirror.replace("code"), {
								parserfile: "parsexml.js",
								stylesheet: "css/xmlcolors.css",
								path: "codemirror/",
								continuousScanning: 500,
								content: status[1],
								lineNumbers: true
							});
						}
						else
							editor.setCode(status[1]);
						
						$(tab+" div").css('display', 'block');
					}
					
				break;
				
				case "2":
					txt = "<div id='msg_init_error'><div class='oss_error'><div style='margin-left: 70px; text-align:center;'>"+status[1]+"</div></div></div>";
					$(tab).html(txt);
					$(tab+" div").css('display', 'block');
				break;
				
				case "3":
					
					$('#cont_cnf_message').hide();
					txt   = "<span style='font-weight: bold;'>"+messages[3]+"<a onclick=\"$('#msg_errors').toggle();\"> ["+messages[4]+"]</a><br/></span>";
					txt  += "<div id='msg_errors'>"+status[2]+"</div>";
						
					$('#cont_cnf_message').append("<div id='parse_errors'></div>");
					$('#parse_errors').addClass("oss_error");
					$('#parse_errors').html(txt);
					
										
					if (editor == null)
					{
						editor = new CodeMirror(CodeMirror.replace("code"), {
							parserfile: "parsexml.js",
							stylesheet: "css/xmlcolors.css",
							path: "codemirror/",
							continuousScanning: 500,
							content: status[1],
							lineNumbers: true
						});
					}
					else
						editor.setCode(status[1]);
					
					$(tab+" div").show();
					$('#cont_cnf_message').show();

					window.scroll(0,0);
					setTimeout('$("#cont_cnf_message").fadeOut(4000);', 25000);	
				
				break;
			}
		}
	});
}

function countdown(seconds)
{
	var cont = seconds - 1;
	if ( cont != 0 )
	{
		$("#countdown").html(cont);
		setTimeout("countdown("+cont+")",1000);
	}
	else
		document.location.href='agent.php';
	}


function save_agent_conf()
{
	
	var tab = $(".active a").attr("href");
				
	if ($('#cnf_message').length >= 1)
	{
		$('#cnf_message').removeClass();
		$('#cnf_message').html('<div id="cont_cnf_message"></div>');
	}
	
	
	if (tab == '')
	{
		$('#cont_cnf_message').addClass("oss_error");
		$('#cont_cnf_message').html(messages[2]);
		return;
	}
	
	//Add Load img
				
	$('#cont_cnf_message').html("<div id='cnf_wait_save'></div>");
	$('#cnf_wait_save').html(messages[1]);
			
	var data = "tab="+ tab + "&"+"data="+Base64.encode(htmlentities(editor.getCode(), 'HTML_ENTITIES'));
									
	$.ajax({
		type: "POST",
		url: "ajax/save_agent_conf.php",
		data: data,
		success: function(msg){
													
			//Remove load img
			if ( $('#cnf_wait_save').length >= 1 )
				$('#cnf_wait_save').remove();
									
			var status = msg.split("###");
													
			if ( status[0] == "1" )
			{
				var msg = status[1];
				$('#cont_cnf_message').addClass("oss_success");
								
				if ( $('#link_tab1').hasClass("dis_tab") )
				{
					var cont = 3;
					msg += " .<span style='margin-left: 5px'>"+messages[6]+" <span id='countdown'>"+cont+"</span> "+messages[7]+" ...</span>";
					$('#cont_cnf_message').html(msg);
					setTimeout("countdown("+cont+")",1000);
				}
				else
				{
					$("#cont_cnf_message").html(status[1]);
					setTimeout('$("#cont_cnf_message").fadeOut(4000);', 4000);
				}
			}
			else
			{
				if ( status[0] == "3" )
				{
					var html   =  "<span style='font-weight: bold;'>"+messages[3]+"<a onclick=\"$('#msg_errors').toggle();\"> ["+messages[4]+"]</a><br/></span>";
						html  += "<div id='msg_errors'>"+status[1]+"</div>";
						
					$('#cont_cnf_message').append("<div id='parse_errors'></div>");
					$('#parse_errors').addClass("oss_error");
					$('#parse_errors').html(html);
					window.scroll(0,0);
					setTimeout('$("#cont_cnf_message").fadeOut(4000);', 25000);
				}
				else
				{
					$('#cont_cnf_message').addClass("oss_error");
					$('#cont_cnf_message').html(status[1]);
					window.scroll(0,0);
					setTimeout('$("#cont_cnf_message").fadeOut(4000);', 4000);
				}
			}		
		}
	});
}
