
/* must erase the name if "New directive" ? */
var must_erase_name = true;

/* Event. */
function onFocusName() {

	var name = getElt("name");

	/* erase the name if "New rule" */
	if (must_erase_name && name.value.search(new RegExp(
			"^New directive$"
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
function onChangeName() {

	var name = getElt("name");

	/* delete some illegal characters */
	name.value = name.value.replace(/"|'/gi, "");

	/* check the new value */
	if (name.value == "") {

		/* bad name, restore the last one */
		name.value = name.title;
	}
	else {

		/* the name was modified, don't erase
			the name even if this is "New directive" */
		must_erase_name = false;

		/* save the new name */
		name.title = name.value;
	}
}

/* Event. */
function onChangeCategory(old_id) {
	/* get the first free directive id */
	var catfile = getElt('category').value;
	var mini = document.getElementById(catfile+'_mini').value;
	var new_directive_id = utils(
		'get_new_directive_id',
		'&category=' + catfile + '&mini=' + mini
	);
  
	/* set the directive field */
	getElt('iddir').value = new_directive_id;
	getElt('iddir').title = new_directive_id;
	onChangeId(old_id);
}

/* Event. */
function onChangeId(old_id) {
	
	// *******************************************
	return false; // Do not change category by id
	// *******************************************
	
	var iddir = getElt("iddir");

	/* check the new value */
	if (iddir.value.search(new RegExp(
			"^[1-9][0-9]*$"
		)) == -1) {

		/* bad id, restore the last one */
		iddir.value = iddir.title;
	}
	else if (iddir.value != old_id &&
		utils("is_free", "&directive=" + iddir.value) == "false") {

		/* directive already exists */
		iddir.style.color = "red";
		iddir.style.fontWeight = "bold";
		getElt("save").disabled = "disabled";
	}
	else {

		/* get the category id of the directive  */
		var category_id = utils(
			"get_category_id_by_directive_id",
			"&directive_id=" + iddir.value
		);

		if (category_id == "") {

			/* the directive id is not in a category */
			iddir.style.color = "red";
			iddir.style.fontWeight = "bold";
			getElt("save").disabled = "disabled";

			return;
		}

		/* the directive id is in a category */
		iddir.style.color = "";
		iddir.style.fontWeight = "";
		getElt("save").disabled = "";

		/* set the category field */
		getElt("category").value = category_id;

		/* save the new value */
		iddir.title = iddir.value;
	}
}

/* Event. */
function onChangelist(force_check) {

	var list = getElt("list");

	/* check the new value */
	if (!force_check && list.value.search(new RegExp(
			"^!?\w+(,\w+)*$"
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

			/* check the group list */
			var is_group_list = utils(
				"is_group_list",
				"&group_list=" + list.value
			);

			if (is_group_list != "true") {

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
		"../../viewer/index.php" +
    "?directive=" + directive +
    "&level=" + level,
		"right"
	);
}

/* Event. */
function onClickCancel2() {

	/* reload the page */
	window.open(
		"../../main.php",
		"main"
	);
}
