
/* Give the focus to an element. */
function giveFocus(name) {

	document.forms[0].elements[name].focus();
}

/* Return the element with the ID past in argument. */
function getElt(id) {

	return document.getElementById(id);
}

/* Open a popup. */
function openPopup(addr) {

	/* default size */
	var width = 1024;
	var height = 512;

	/* center the popup to the screen */
	var top = (screen.height - height) / 2;
	var left = (screen.width - width) / 2;

	window.open(
		addr,
		"_blank",
		"top=" + top + ", " +
		"left=" + left + ", " +
		"width=" + width + ", " +
		"height=" + height + ", " +
		"scrollbars=yes"
	);
}

/* Get a value from the OSSIM database by loading "utils.php". */
function utils(query, args) {

	var xhr_object;

	if (window.XMLHttpRequest)
		xhr_object = new XMLHttpRequest();
	else if (window.ActiveXObject)
		xhr_object = new ActiveXObject("Microsoft.XMLHTTP");
	else
		return ""; /* error */

	xhr_object.open("GET", "../../include/utils.php?query=" + query + args, false);
	xhr_object.send(null);

	if (xhr_object.readyState == 4)
		return xhr_object.responseText;

	return ""; /* error */
}

/* Get the current event object. */
function getEvent(event) {
	
	if (window.event) return window.event.keyCode;
	if (event) return event.which;
}

/* Call the "onChange()" handler if "Enter" is pressed. */
function onKeyPressElt(elt, evt) {

	if (getEvent(evt) == 13) {

		try {elt.onchange();}
		catch(err) {}
	}
}
