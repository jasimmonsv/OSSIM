String.prototype.trim = function() { return this.replace(/^\s+|\s+$/g, ""); }
String.prototype.stripTags = function() { return this.replace(/<[^>]+>/g,'');} 

var zindex=100;

function get_value(id)
{
	var tag_name = document.getElementById(id).tagName;	
	var values = '';
	
	if (tag_name == 'INPUT')
	{
		var type = $("#"+id).attr("type");
		
		if (type == "checkbox" || type == "radio")
		{
			var name = $("#"+id).attr("name");
			
			$("input[name='"+name+"']:checked").each(function (index) {
				values += ( index == 0) ? $(this).val() :  "_#_" + $(this).val() ;
					
			});
			return values;
		}
		
	}
	else 
	{
		if ( tag_name == 'SELECT' )
		{
			$("#"+id+" option:selected").each(function (index) {
				values += ( index == 0) ? $(this).val() :  "_#_" + $(this).val();
						
			});
			
			return values;
		}
	}
	
	return $("#"+id).val();
	
}
		
	function check_form()
	{
		var required_fields = new Array();
		var msg = '';
		var txt_error = '';
		var req_error = false;
		var val_error = false;
		zindex = 1000;
				
		$("#send").val(messages[4]);
					
		$(".req_field").each(function(index) {
			var name = $("label[for="+$(this).attr('id')+"]").text()
			required_fields[name] = get_value($(this).attr('id'));
		});
		
		for(var i in required_fields)
		{
			var element = required_fields[i];
			element = ( element == null ) ? '' : element.trim();
											
			if ( element.length == 0) 
			{
				req_error = true;
				msg += i +"<br/>";
			}
		}
		
		var res = validate_all_field();
		
		if ( res == '' )
		{
			msg = messages[2];
			val_error = true;
		}
		else 
		{
			if ( res != 0)
			{
				msg = '';
				var msg_split = res.split("<br/>");
				
				for (var i=0; i<msg_split.length; i++)
				{
					msg += msg_split[i].stripTags() +"<br/>";
				}
								
				val_error = true;
			}
		}		
					
		if ( req_error == true || val_error == true )
		{
			if ( val_error == true )
			{
				txt_error = "<div style='padding-left: 10px;'>"+messages[3]+"<div><div style='padding-left: 20px;'>"+msg+"</div>";
				
				$(".vfield").each(function(index) {
					var form_id = $('form[method="post"]').attr("id");
					var url     = $('#'+form_id).attr("action");
					validate_field($(this).attr("id"), url);
				});	
			}
			else
			{
				if ( req_error == true )
					txt_error = "<div style='padding-left: 10px;'>"+messages[3]+"<div><div style='padding-left: 20px;'>"+msg+"</div>";
			}
			
						
			$("#info_error").html(txt_error);
			$("#info_error").css('display', 'block');
						
			if (typeof ajax_postload == 'function') 
				ajax_postload();
			
			$("#send").val(messages[5]);
			
			window.scrollTo(0,0);
			
			return false;
		}
		else
		{
            $("#info_error").html("");
			$("#info_error").css('display', 'none');
			return true;
		}
	}
		
function validate_field(id, url)
{
	var name  = $("#"+id).attr("name");
	var data  = $("#"+id).serialize();
	var error_msg = ''
				
	$.ajax({
		type: "GET",
		url: url,
		data: data + "&name=" + name + "&ajax_validation=true",
		success: function(html){

			var status = parseInt(html);
								
			if ( isNaN(status) )
			{
				var l_zindex = zindex--;
				var r_zindex = zindex--;
				
				var error_msg= format_error_msg(html);
								
				var msg_error = "<div class='cont_val' id='error_"+id+"'>";
					msg_error +="<div style='z-index: "+l_zindex+"' class='val_error_l'></div>";
					msg_error +="<div style='z-index: "+r_zindex+"' class='val_error_r'>";
					msg_error +="<div style='padding:5px;'>"+error_msg+"</div></div>";
					msg_error +="</div>";
				
				if ($("#error_"+id).length < 1)
				{
					$("#"+id).addClass("invalid");
				}
				else
				{
					$("#error_"+id).remove();
				}
				
				$("#"+id).before(msg_error);
				
			}
			else
			{
				if (status == 1)
				{
					$("#info_error").html(messages[1]);
					$("#info_error").css('display', 'block');
				}
				else
				{
					$("#error_"+id).remove();
					$("#"+id).removeClass("invalid");
				}
			}
													
		}
	});
}

function validate_all_field()
{
	var form_id = $('form[method="post"]').attr("id");
	var url     = $('#'+form_id).attr("action");
	var data    = $('#'+form_id).serialize();
					
	var ret = $.ajax({
		url: url,
		global: false,
		type: "POST",
		data: data+ "&ajax_validation_all=true",
		dataType: "text",
		async:false
		}
	).responseText;
		
	return ret;
}


function submit_form()
{
	if (check_form() == true)
	{
		var form_id = $('form[method="post"]').attr("id");
		$('#'+form_id).submit(); 
		return true;
	}
	else
	{
		if ( $(".invalid").length >= 1 )
			$(".invalid").get(0).focus();
		return false;
	}
}


function format_error_msg(msg)
{

var txt = msg.split("<br/>");
var error_msg = '';

if ( txt.length > 2)
{
	var msg1 = txt[0].stripTags();
		
	if ( msg1.match(/Error!/i) )
		msg1 = '';
	else
		msg1 = msg1 + "<br/>";
	
	var msg2 = txt[1].stripTags();
		
	if ( msg2.match(/Sorry\,/i) )
		msg2 = 'Content not allowed for security reasons';
			
	error_msg = msg1 + msg2;
}
else
	error_msg = txt[0].stripTags();

	return error_msg;
}






