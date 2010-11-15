(function($) {

	$.fn.tagit = function(options) {

		var el = this;

        var KEY = {
                SPACE: 32, 
                TAB: 9,  
                ENTER: 13,
                COMMA: 44,
                BACKSPACE: 8 
        };

		// add the tagit CSS class.
		el.addClass("tagit");

		// create the input field.
		//var html_input_field = "<li class=\"tagit-new\"><input class=\"tagit-input\" tabindex=\"1\" type=\"text\" /> <input class=\"tagit-hidden\" tabindex=\"2\" type=\"text\" /></li>\n";
		var html_input_field = "<li class=\"tagit-new\"><input class=\"tagit-input\" tabindex=\"1\" type=\"text\" /> </li>\n";
		el.html (html_input_field);

		tag_input		= el.children(".tagit-new").children(".tagit-input");
		//$(".tagit-hidden").focus(function(){
		//	tag_input.focus();
		//});
		
		$(this).click(function(e){
			if (e.target.tagName == 'A') {
				// Removes a tag when the little 'x' is clicked.
				// Event is binded to the UL, otherwise a new tag (LI > A) wouldn't have this event attached to it.
				$(e.target).parent().remove();
				if (options.changeFunction) options.changeFunction();
			}
			else {
				// Sets the focus() to the input field, if the user clicks anywhere inside the UL.
				// This is needed because the input field needs to be of a small size.
				tag_input.focus();
			}
		});

		tag_input.keydown(function(event){			
			if (event.which == KEY.TAB) {
				event.preventDefault();

				var typed = tag_input.val();
				typed = typed.replace(/(,|\s|\t|\r|\n)+$/g,"");
				typed = typed.trim();

				if (typed != "") {
					if (is_new (typed)) {
						create_choice (typed);
					}
					// Cleaning the input.
					tag_input.val("");
				}
				return false;
			}

		});

		tag_input.keypress(function(event){
			if (event.which == KEY.BACKSPACE) {
				if (tag_input.val() == "") {
					// When backspace is pressed, the last tag is deleted.
					$(el).children(".tagit-choice:last").remove();
					if (options.changeFunction) options.changeFunction();
				}
			}
			// Comma/Space/Enter are all valid delimiters for new tags.
			//else if (event.which == KEY.COMMA || event.which == KEY.SPACE || event.which == KEY.ENTER) {
			else if (event.which == KEY.COMMA || event.which == KEY.ENTER) {
				event.preventDefault();

				var typed = tag_input.val();
				typed = typed.replace(/(,|\s|\t|\r|\n)+$/g,"");
				typed = typed.trim();

				if (typed != "") {
					if (is_new (typed)) {
						create_choice (typed);
					}
					// Cleaning the input.
					tag_input.val("");
				}
			}
		});

		tag_input.autocomplete({
			multiple: true,
			source: function(req, add){
				//pass request to server
				var param = tag_input.val();
				$.getJSON("autocomplete.php?str="+param, req, function(data) {
					//create array for response objects  
					var suggestions = [];  
					//process response  
					$.each(data, function(i, val){  
						suggestions.push(val.name);  
					});  
					
					//pass array to callback  
					add(suggestions);  
				});  
			},
			select: function(event,ui){
				if (is_new (ui.item.value)) {
					create_choice (ui.item.value);
				}
				// Cleaning the input.
				tag_input.val("");

				// Preventing the tag input to be update with the chosen value.
				return false;
			}
		});

		function is_new (value){
			value = value.replace(/\<b\>/g,"");
			value = value.replace(/\<\/b\>/g,"");
			if (options.autoFormat) {
				if (!value.match(/\=/) && !is_operator(value)) value = "data="+value;
			}
			var is_new = true;
			this.tag_input.parents("ul").children(".tagit-choice").each(function(i){
				n = $(this).children("input").val();
				if (value == n) {
					is_new = false;
				}
			})
			return is_new;
		}
		function create_choice (value){
			if (options.autoFormat) {
				if (!value.match(/\=/) && !is_operator(value)) value = "<b>data</b>="+value;
				if (value.match(/\=/) && !value.match(/\<b\>/)) {
					value = "<b>"+value;
					if (value.match("!=")) {
						value = value.replace("!=","</b>!=");
					} else {
						value = value.replace("=","</b>=");
					}
				}
			}
			var el = "";
			el  = "<li class=\"tagit-choice\">\n";
			el += value + "\n";
			el += "<a class=\"close\">x</a>\n";
			value = value.replace(/\<b\>/g,"");
			value = value.replace(/\<\/b\>/g,"");
			el += "<input type=\"hidden\" style=\"display:none;\" class=\"search_atom\" value=\""+value+"\" name=\"item[tags][]\">\n";
			el += "</li>\n";
			var li_search_tags = this.tag_input.parent();
			$(el).insertBefore (li_search_tags);
			this.tag_input.val("");
			if (options.changeFunction) options.changeFunction();
			this.tag_input.focus();
		}
	};

	String.prototype.trim = function() {
		return this.replace(/^\s+|\s+$/g,"");
	};

})(jQuery);
