function show_tab_content(tab)
{
	$("ul.oss_tabs li").removeClass("active"); //Remove any "active" class
	$(tab).addClass("active"); //Add "active" class to selected tab
	$(".tab_content").hide(); //Hide all tab content
	var activeTab = $(tab).find("a").attr("href"); //Find the rel attribute value to identify the active tab + content
	$(activeTab).show(); //Fade in the active content
	return false;
}
		
function load_config_tab(tab)
{
	
	//Add Load img
	if ($('#cnf_load').length < 1)
	{
		$(tab+" div").css('display', 'none');
		var load ="<div id='cnf_load'>"+messages[0]+"</div>";
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
		url: "ajax/load_config_tab.php",
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
						
						$(".multiselect").multiselect({
							searchDelay: 500,
							nodeComparator: function (node1,node2){ return 1 },
							dividerLocation: 0.5
						});
						
						$(tab+" div").css('display', 'block');
					}
					else if (tab == "#tab2")
					{
						$(tab).html(status[1]);	
						
						$(tab+" div").css('display', 'block');
						$('textarea').elastic();
						$('#table_sys_directories table').css('background', 'transparent');
						$('#table_sys_directories .dir_tr:odd').css('background', '#EFEFEF');
						$('#table_sys_ignores table').css('background', 'transparent');
						$('#table_sys_ignores .dir_tr:odd').css('background', '#EFEFEF');
					}
					else if (tab == "#tab3")
					{
						if (editor == null)
						{
							editor = new CodeMirror(CodeMirror.replace("code"), {
								parserfile: "parsexml.js",
								stylesheet: "../style/xmlcolors.css",
								path: "../js/codemirror/",
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
							stylesheet: "../style/xmlcolors.css",
							path: "../js/codemirror/",
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
		document.location.href='config.php';
}


function save_config_tab()
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
				
	var data= "tab="+tab;
	
	switch(tab){
		case "#tab1":
			data += "&"+ $('#cnf_form_rules').serialize();
		break;
		
		case "#tab2":
			data += "&"+ $('#form_syscheck').serialize();
		break;
		
		case "#tab3":
			data += "&"+"data="+Base64.encode(htmlentities(editor.getCode(), 'HTML_ENTITIES'));
		break;
	}
								
	$.ajax({
		type: "POST",
		url: "ajax/save_config_tab.php",
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
					if ( tab == "#tab2" )
						setTimeout('$("#cont_cnf_message").fadeOut(4000);', 25000);
					else
						setTimeout('$("#cont_cnf_message").fadeOut(4000);', 4000);
				}
			}		
		}
	});
}


function add_row(id, action)
{
	$.ajax({
		type: "POST",
		url: "ajax/config_actions.php",
		data: "action="+action,
		success: function(msg){
			
			var status = msg.split("###");
													
			if (status[0] != "error")
			{
				if ( id.match("tbody_") != null )
					$(id).html(status[1]);
				else
					$('#'+id).after(status[1]);
				
				$('textarea').elastic();
				
				switch (action)
				{
					case "add_directory":
						$('#table_sys_directories table').css('background', 'transparent');
						$('#table_sys_directories .dir_tr:odd').css('background', '#EFEFEF');
					break;
					
					case "add_wentry":
						$('#table_sys_wentries table').css('background', 'transparent');
						$('#table_sys_wentries .went_tr:odd').css('background', '#EFEFEF');
					break;
					
					case "add_reg_ignore":
						$('#table_sys_reg_ignores table').css('background', 'transparent');
						$('#table_sys_reg_ignores .regi_tr:odd').css('background', '#EFEFEF');
					break;
					
					case "add_ignore":
						$('#table_sys_ignores table').css('background', 'transparent');
						$('#table_sys_ignores .ign_tr:odd').css('background', '#EFEFEF');
					break;
				}
				
			}
		}
	});
}


function delete_row(id, action)
{
	if ( confirm (messages[5]) )
	{
		
		if ( $('#'+id).length >= 1 )
		{
			$('#'+id).remove();
			switch (action)
			{
				case "delete_directory":
					var tbody 	   = "#tbody_sd";
					var table 	   = "#table_sys_directories table";
					var tr   	   = "#table_sys_directories .dir_tr:odd";
					var add_action = "add_directory";
				break;
				
				case "delete_wentry":
					var tbody      = "#tbody_swe";
					var table      = "#table_sys_wentries table";
					var tr         = "#table_sys_wentries .went_tr:odd";
					var add_action = "add_wentry";
				break;
				
				case "delete_reg_ignore":
					var tbody      = "#tbody_sri";
					var table      = "#table_sys_reg_ignores table";
					var tr         = "#table_sys_reg_ignores .regi_tr:odd";
					var add_action = "add_reg_ignore";
				break;
				
				case "delete_ignore":
					var tbody      = "#tbody_si";
					var table      = "#table_sys_ignores table";
					var tr         = "#table_sys_ignores .ign_tr:odd";
					var add_action = "add_ignore";
				break;
			}
			
			if ($(tbody + " tr").length <= 0)
				add_row(tbody, add_action);
			else
			{
				$('textarea').elastic();
				$(table).css('background', 'transparent');
				$(tr).css('background', '#EFEFEF');
			}
		}
	}

}


