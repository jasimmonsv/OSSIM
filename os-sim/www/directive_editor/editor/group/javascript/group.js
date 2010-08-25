
/* must erase the name if "New group" */
var must_erase_name = true;

/* Event. */
function onFocusName() {

	var name = getElt("name");

	/* erase the name if "New group" */
	if (must_erase_name && name.value.search(new RegExp(
			"^New group$"
		)) != -1) {

		/* erase the name */
		name.value = "";
	}
	else
		/* the name was modified, don't erase
			the name when the field get the focus */
		must_erase_name = false;
}

/* Event. */
function onChangeName(old_name) {

	var name = getElt("name");

	/* delete some illegal characters */
	name.value = name.value.replace(/"|'/gi, "");

	/* check the new value */
	if (name.value == "") {

		/* empty name, restore the last one */
		name.value = name.title;
	}
	/* if the name was modified */
	else if (name.value != name.title) {

		var is_free_group = utils(
				"is_free_group",
				"&name=" + name.value
			);
		
		/* correct name, don't erase it
			even if this is "New category" */
		must_erase_name = false;

		if (is_free_group != "true" && name.value != old_name)
		{
			name.style.color = "red";
			name.style.fontWeight = "bold";
			getElt("save").disabled = "disabled";
		}
		else
		{
			name.style.color = "";
			name.style.fontWeight = "";
			getElt("save").disabled = "";
		}
		/* save the new name */
		name.title = name.value;
	}
}


/* Event. */
function onChangelist(force_check) {

	var list = getElt("list");

	/* check the new value */
	if (!force_check && list.value.search(new RegExp(
			"^!?[0-9]+(,[0-9]+)*$"
		)) == -1) {

		/* doesn't match */
		list.value = list.title;

	}
	else if(force_check || list.value != list.title) {

		if (list.value == "") {

			/* reset the color and font if the list is empty */
			list.style.color = "";
			list.style.fontWeight = "";
		}
		else {

			/* check the directive list */
			var is_directive_list = utils(
				"is_directive_list",
				"&directive_list=" + list.value
			);

			if (is_directive_list != "true") {

				/* at least one sid does not exist */
				list.style.color = "red";
				list.style.fontWeight = "bold";
			}
			else {

				/* ok, default color and font */
				list.style.color = "";
				list.style.fontWeight = "";
			}
		}

		/* save the new value */
		list.title = list.value;
	}
}


/* Event. */
function onClickCancel(directive, level) {

	/* reload the page */
	window.open(
		"../../viewer/index.php",
		"right"
	);
}
