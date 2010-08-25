
/* */
function onLoadRuleEditor(plugin_sid,from,to,port_from,port_to,sensor) {

	var neg = plugin_sid.indexOf("!");
	var plugin_sid_value = plugin_sid;

	if (neg != -1) {
		plugin_sid_value = "!" + plugin_sid.substr(neg+1).replace(/!/gi,"");
	}

	getElt("plugin_sid_list").value = plugin_sid_value;
	getElt("plugin_sid_list").title = plugin_sid_value;
	onChangePluginSidList(true);
	getElt("from_list").value = from;
	getElt("from_list").title = from;
	getElt("to_list").value = to;
	getElt("to_list").title = to;
	getElt("port_from_list").value = port_from;
	getElt("port_from_list").title = port_from;
	getElt("port_to_list").value = port_to;
	getElt("port_to_list").title = port_to;
	getElt("sensor_list").value = sensor;
	getElt("sensor_list").title = sensor;
}

/* must erase the name if "New rule" ? */
var must_erase_name = true;

/* Event. */
function onFocusName() {

	var name = getElt("name");

	/* erase the name if "New rule" */
	if (must_erase_name && name.value == "New rule") {
		name.value = "";
	}
}

/* Event. */
function onChangeName() {

	var name = getElt("name");

	/* delete some characters */
	name.value = name.value.replace(/"|'/gi, "");

	if (name.value == "") {

		/* incorrect/empty name */
		name.value = name.title;
	}
	else {

		/* correct name, don't erase it
			even if this is "New rule" */
		must_erase_name = false;

		/* save the new name */
		name.title = name.value;
	}
}

/* Event. */
function onChangePluginId() {
	
	var plugin_id = getElt("plugin_id");
	
	/* check the new value */
	if (plugin_id.value.search(new RegExp(
			"^[0-9]+$"
		)) == -1) {
		
		/* doesn't match */
		plugin_id.value = plugin_id.title;
	}
	else if (plugin_id.value != plugin_id.title) {

		/* get the plugin name */
		var plugin_name = utils(
			"get_plugin_name",
			"&plugin_id=" + plugin_id.value
		);

		if (plugin_name == "") {

			/* the plugin id does not exist */
			plugin_id.value = plugin_id.title;
		}
		else {

			/* get the plugin type */
			var plugin_type = utils(
				"get_plugin_type",
				"&plugin_id=" + plugin_id.value
			);

			/* save the type in a hidden field */
			getElt("type").value = plugin_type;

			/* print the plugin info (name and type) */
			plugin_info = "<b>&nbsp;" + plugin_name +
				"&nbsp;&nbsp;(type: " + plugin_type + ")</b>";
			getElt("plugin_name").innerHTML = plugin_info;

			/* set the plugin sid to "ANY" */
			getElt("plugin_sid").selectedIndex = "ANY";

			/* disable the plugin sid list */
			var plugin_sid_list = getElt("plugin_sid_list");
			plugin_sid_list.value = "";
			plugin_sid_list.title = "";
			plugin_sid_list.disabled = "disabled";
			plugin_sid_list.style.color = "";
			plugin_sid_list.style.fontWeight = "";

			/* enable or disable the monitor part */
			var disabled = (plugin_type == "monitor") ? "" : "disabled";
			getElt("condition").disabled = disabled;
			getElt("value").disabled = disabled;
			getElt("interval").disabled = disabled;
			getElt("absolute").disabled = disabled;

			/* enable the "save" button */
			getElt("popup_plugin_sid").disabled = "";
			getElt("save").disabled = "";

			/* save the new value */
			plugin_id.title = plugin_id.value;
		}
	}
}

/* Event. */
function onChangePluginSid() {

	var plugin_sid = getElt("plugin_sid");
	var plugin_sid_list = getElt("plugin_sid_list");

	if (plugin_sid.value == "LIST") {

		/* enable the plugin sid list */
		plugin_sid_list.disabled = "";

		/* check the sid list (and set the text color) */
		onChangePluginSidList(true);

		/* force the focus to the plugin sid if the list is empty */
		if (plugin_sid_list.value == "") giveFocus("plugin_sid_list");
	}
	else if (plugin_sid.value == "ANY"){

		/* disable the plugin sid list */
		plugin_sid_list.disabled = "disabled";
		plugin_sid_list.style.color = "";
		plugin_sid_list.style.fontWeight = "";
	}
	else {
	
	//* enable the plugin sid list */
		plugin_sid_list.disabled = "";

	/* add new sid */
	var sid_list = plugin_sid_list.value;
	if (sid_list != "")
		getElt("plugin_sid_list").value += ",";
	getElt("plugin_sid_list").value += getElt("plugin_sid").value;
	getElt("plugin_sid").value = "LIST";
	}
}

/* Event. */
function onChangePluginSidList(force_check) {

	var plugin_sid = getElt("plugin_sid");
	var plugin_sid_list = getElt("plugin_sid_list");

	/* check the new value */
	if (!force_check && plugin_sid_list.value.search(new RegExp(
			"^!?(([0-9]+)|([0-9]+:PLUGIN_SID))((,[0-9]+)|(,[0-9]+:PLUGIN_SID))*$"
		)) == -1) {

		/* doesn't match */
		plugin_sid_list.value = plugin_sid_list.title;

		if (plugin_sid_list.value == "") {

			/* force the plugin sid to "ANY" if the list is empty */
			plugin_sid.value = "ANY";
			onChangePluginSid();
		}
	}
	else if(force_check || plugin_sid_list.value != plugin_sid_list.title) {

		if (plugin_sid_list.value == "") {

			/* reset the color and font if the list is empty */
			plugin_sid_list.style.color = "";
			plugin_sid_list.style.fontWeight = "";
		}
		else {

			/* build an array without the starting "!" */
			var plugin_sid_array = (plugin_sid_list.value.charAt(0) == "!") ?
				plugin_sid_list.value.substr(1) : plugin_sid_list.value;

			/* check the plugin sid list */
			var is_plugin_sid_list = utils(
				"is_plugin_sid_list",
				"&plugin_id=" + getElt("plugin_id").value +
				"&plugin_sid_list=" + plugin_sid_array
			);

			if (is_plugin_sid_list != "true") {

				/* at least one sid does not exist */
				plugin_sid_list.style.color = "red";
				plugin_sid_list.style.fontWeight = "bold";
			}
			else {

				/* ok, default color and font */
				plugin_sid_list.style.color = "";
				plugin_sid_list.style.fontWeight = "";
			}
		}

		/* save the new value */
		plugin_sid_list.title = plugin_sid_list.value;
	}
}

/* Event. */
function onChangeIPSelectBox(id) {

	var id_list = getElt(id + "_list");

	if (getElt(id).value == "LIST") {

		/* enable the list */
		id_list.disabled = "";

		/* force the focus to the text box if the list is empty */
		if (id_list.value == "") giveFocus(id + "_list");
	}
	else if(getElt(id).value == "ANY"){

		/* disable the list */
		id_list.disabled = "disabled";
	}
	else {
	
	/* enable the list */
		id_list.disabled = "";

	/* add new port */
	var ip_list = id_list.value;
	if (ip_list != "")
		getElt(id + "_list").value += ",";
	getElt(id + "_list").value += getElt(id).value;
	getElt(id).value = "LIST";
	}
}

/* Event. */
function onChangeIPList(ip_list) {
	
	ip_list = getElt(ip_list);
	
	/* check the new value */
	if (ip_list.value.search(new RegExp(
			"^!?(([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}(\/[0-9]{1,2})?)|([a-zA-Z0-9\-_]*)|([0-9]+:SRC_IP)|([0-9]+:DST_IP))" +
			"((,[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}(\/[0-9]{1,2})?)|(,[a-zA-Z0-9\-_]*)|([0-9]+:SRC_IP)|([0-9]+:DST_IP))*$"
		)) == -1) {
		
		/* doesn't match */
		ip_list.value = ip_list.title;

		if (ip_list.value == "") {

			/* force the select box to "ANY" if the list is empty */
			var selectbox_id = ip_list.name.split("_list")[0];
			getElt(selectbox_id).value = "ANY";
			onChangeIPSelectBox(selectbox_id);
		}
	}
	else if (ip_list.value != ip_list.title) {

		var ip_array;
		var starting_char;

		/* split the list and build an array without the starting "!" */
		if (ip_list.value.charAt(0) == "!") {

			ip_array = ip_list.value.substr(1).split(",");
			starting_char = "!";
		}
		else {

			ip_array = ip_list.value.split(",");
			starting_char = "";
		}

		ip_list.value = starting_char;

		/* for each ip */
		for (i = 0; i < ip_array.length; i++) {

			var nb_array = ip_array[i].split(".");

			/* for each number */
			for (j = 0; j < nb_array.length; j++) {

				/* check the range of the number */
				if (nb_array[j] < 0 || nb_array[j] > 255) {

					/* bad number */
					ip_list.value = ip_list.title;
					return;
				}
			}

			/* correct ip */
			if (ip_list.value != starting_char) ip_list.value += ",";
			ip_list.value += ip_array[i];
		}

		/* save the new value */
		ip_list.title = ip_list.value;
	}
}

/* Event. */
function onChangePortSelectBox(id) {

	var id_list = getElt(id + "_list");

	if (getElt(id).value == "LIST") {

		/* enable the list */
		id_list.disabled = "";

		/* force the focus to the text box if the list is empty */
		if (id_list.value == "") giveFocus(id + "_list");
	}
	else  if (getElt(id).value == "ANY"){

		/* disable the list */
		id_list.disabled = "disabled";
	}
	else {
	
	/* enable the list */
		id_list.disabled = "";

	/* add new port */
	var port_list = id_list.value;
	if (port_list != "")
		getElt(id + "_list").value += ",";
	getElt(id + "_list").value += getElt(id).value;
	getElt(id).value = "LIST";
	}
}

/* Event. */
function onChangePortList(port_list) {
	
	port_list = getElt(port_list);
	
	/* check the new value */
	if (port_list.value.search(new RegExp(
			"^\!?(([0-9]+)|([0-9]+:SRC_PORT)|([0-9]+:DST_PORT))(,[0-9]+)*(,[0-9]+:SRC_PORT)*(,[0-9]+:DST_PORT)*$"
		)) == -1) {

		/* doesn't match */
		port_list.value = port_list.title;

		if (port_list.value == "") {

			/* force the select box to "ANY" if the list is empty */
			var selectbox_id = port_list.name.split("_list")[0];
			getElt(selectbox_id).value = "ANY";
			onChangePortSelectBox(selectbox_id);
		}
	}
	else if (port_list.value != port_list.title) {

		var port_array;
		var starting_char;

		/* split the list and build an array without the starting "!" */
		if (port_list.value.charAt(0) == "!") {

			port_array = port_list.value.substr(1).split(",");
			starting_char = "!";
		}
		else {

			port_array = port_list.value.split(",");
			starting_char = "";
		}

		port_list.value = starting_char;

		/* for each port */
		for (i = 0; i < port_array.length; i++) {

			if (port_array[i] < 0 || port_array[i] > 65535) {
					
				/* bad port */
				port_list.value = port_list.title;
				return;
			}

			/* correct port */
			if (port_list.value != starting_char) port_list.value += ",";
			port_list.value += port_array[i];
		}

		/* save the new value */
		port_list.title = port_list.value;
	}
}

/* Event. */
function onClickProtocolAny(level) {

	if (getElt("protocol_any").checked != "") {

		/* check all the protocols */
		getElt("protocol_tcp").checked = "checked";
		getElt("protocol_udp").checked = "checked";
		getElt("protocol_icmp").checked = "checked";

		for (i = 1; i <= level-1; i++)
			getElt("protocol_" + i).checked = "checked";
	}
	else {
		
		/* uncheck all the protocols */
		getElt("protocol_tcp").checked = "";
		getElt("protocol_udp").checked = "";
		getElt("protocol_icmp").checked = "";

		for (i = 1; i <= level-1; i++)
			getElt("protocol_" + i).checked = "";

		/* check at least one default protocol */
		getElt("protocol_tcp").checked = "checked";
	}
}

/** Return true if all the protocols are checked. */
function allProtocolChecked(level) {

	if (getElt("protocol_tcp").checked == "") return false;
	if (getElt("protocol_udp").checked == "") return false;
	if (getElt("protocol_icmp").checked == "") return false;

	for (i = 1; i <= level-1; i++)
		if (getElt("protocol_" + i).checked == "") return false;

	return true;
}

/** Return true if no protocol is checked. */
function noneProtocolChecked(level) {

	if (getElt("protocol_tcp").checked != "") return false;
	if (getElt("protocol_udp").checked != "") return false;
	if (getElt("protocol_icmp").checked != "") return false;

	for (i = 1; i <= level-1; i++)
		if (getElt("protocol_" + i).checked != "") return false;

	return true;
}

/* Event. */
function onClickProtocol(id, level) {

	if (allProtocolChecked(level)) {
		/* check "ANY" if all the protocols are checked */
		getElt("protocol_any").checked = "checked";
	}
	else {
		/* uncheck "ANY" if no protocol is checked */
		getElt("protocol_any").checked = "";
	}

	/* cannot uncheck the protocol if this is the last one */
	if (noneProtocolChecked(level))
		getElt(id).checked = "checked";
}

/* Event. */
function onClickCancel(directive, level) {

	window.open(
		"../../include/utils.php" + 
		"?query=del_new_rule" +
		"&level=" + level,
		"right"
	);
}

function displayPopup(fichier)
{
  window.open(fichier,"","width=640,height=4801,left=200,top=200,toolbar=no,l­ocation=no,directories=no,status=no,menubar=no,scrollbars=yes ,resizable=no");
}
