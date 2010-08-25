
/* must erase the name if "New category" */
var must_erase_name = true;

/* Event. */
function onFocusName() {

	var name = getElt("name");

	/* erase the name if "New category" */
	if (must_erase_name && name.value.search(new RegExp(
			"^New category [0-9]+$"
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
function onChangeName(old_xml_file) {

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

    var str = getElt("xml_file").value.substr(0,13);
    /*if(str.lenght >= 13)
      {
        str = str.substr(0,13);
      }*/
      
    if(str == 'new_category_')
      {
        /* set the xml file */
        getElt("xml_file").value = name.value.split(" ")[0].toLowerCase();
        onFocusXmlFile();
    		onChangeXmlFile(old_xml_file);
      }
		
		/* correct name, don't erase it
			even if this is "New category" */
		must_erase_name = false;

		/* save the new name */
		name.title = name.value;
	}
}

/* must erase the name if "new_category_X.xml" ? */
var must_erase_xml_file = true;

/* Event. */
function onFocusXmlFile() {

	var xml_file = getElt("xml_file");

	/* delete some illegal characters */
	xml_file.value = xml_file.value.replace(/"|'/gi, "");

	/* erase the name if "new_category_X.xml" */
	if (must_erase_xml_file && xml_file.value.search(new RegExp(
			"^new_category_[0-9]+[.]xml$"
		)) != -1) {

		/* erase the xml file name */
		xml_file.value = ".xml";

		/* set the cursor position */
		if (xml_file.createTextRange) {

			var range = xml_file.createTextRange();
			range.move('character', 0);
			range.select();
		}
		else {

			if (xml_file.selectionStart) {
				xml_file.focus();
				xml_file.setSelectionRange(0, 0);
			}
			else
				xml_file.focus();
		}
	}
	else
		/* the xml file name was modified, don't erase
			the xml file name when the field get the focus */
		must_erase_xml_file = false;
}

/* Event. */
function onChangeXmlFile(old_xml_file) {

	var xml_file = getElt("xml_file");

	/* delete some illegal characters */
	xml_file.value = xml_file.value.replace(/ |"|'/gi, "");

	/* check the new value */
	if (xml_file.value == "") {

		/* empty xml file name, restore the last one */
		xml_file.value = xml_file.title;
	}
	/* if the xml file name was modified */
	else if (xml_file.value != xml_file.title) {

		/* add ".xml" */
		if (xml_file.value.search(new RegExp(
				"[.]xml$"
			)) == -1)

			xml_file.value += ".xml";

		if (xml_file.value == ".xml") {

			/* incorrect xml file name */
			xml_file.value = xml_file.title;

			return;
		}

		if (xml_file.value != old_xml_file) {

			/* check if the xml file already exists */
			var file_exists = utils(
				"file_exists",
				"&xml_file=" + xml_file.value
			);

			if (file_exists == "true") {

				/* the file already exists */
				xml_file.style.color = "red";
				xml_file.style.fontWeight = "bold";
				getElt("save").disabled = "disabled";
			}
		}
		else {

			/* the file doesn't exist */
			xml_file.style.color = "";
			xml_file.style.fontWeight = "";
			getElt("save").disabled = "";
		}

		/* correct xml file name, don't erase the xml
			file name even if this is "new_category_X.xml" */
		must_erase_xml_file = false;

		/* save the new xml file name */
		xml_file.title = xml_file.value;
	}
}

/* Event. */
function onChangeMini(category_id) {

	var mini = getElt("mini");
	var maxi = getElt("maxi");

	/* check the new value */
	if (mini.value.search(new RegExp(
			"^[1-9][0-9]*$"
		)) == -1) {

		/* bad value, restore the last one */
		mini.value = mini.title;
	}
	/* if mini was modified */
	else if (mini.value != mini.title) {

		if (parseInt(mini.value) >= parseInt(maxi.value)) {

			/* set the maxi */
			for (i = 2900; i <= 3900; i++) {

				if ((parseInt(mini.value) + i) % 1000 == 0) {

					maxi.value = parseInt(mini.value) + i - 1;
					maxi.title = maxi.value;
					break;
				}
			}
		}

		/* check if the new interval is free */
		var check_mini_maxi = utils(
			"check_mini_maxi",
			"&current_category_id=" + category_id +
			"&mini=" + mini.value +
			"&maxi=" + maxi.value
		);

		if (check_mini_maxi == "true") {

			/* the interval is free */
			mini.style.color = "";
			mini.style.fontWeight = "";
			maxi.style.color = "";
			maxi.style.fontWeight = "";
			getElt("save").disabled = "";
		}
		else {

			/* the interval is not free */
			mini.style.color = "red";
			mini.style.fontWeight = "bold";
			maxi.style.color = "red";
			maxi.style.fontWeight = "bold";
			getElt("save").disabled = "disabled";
		}

		/* save the new mini */
		mini.title = mini.value;
	}
}

/* Event. */
function onChangeMaxi(category_id) {

	var mini = getElt("mini");
	var maxi = getElt("maxi");

	/* check the new value */
	if (maxi.value.search(new RegExp(
			"^[1-9][0-9]*$"
		)) == -1) {

		/* bad value, restore the last one */
		maxi.value = maxi.title;
	}
	else if (maxi.value != maxi.title) {

		if (parseInt(mini.value) >= parseInt(maxi.value)) {

			/* set the mini */
			for (i = 2900; i <= 3900; i++) {

				if ((parseInt(maxi.value) - i) % 1000 == 0) {

					if ( (parseInt(maxi.value) - i) < 0) {
						mini.value = "1";
						mini.title = "1";
					}
					else {
						mini.value = parseInt(maxi.value) - i;
						mini.title = mini.value;
					}

					break;
				}
			}
		}

		/* check if the new interval is free */
		var check_mini_maxi = utils(
			"check_mini_maxi",
			"&current_category_id=" + category_id +
			"&mini=" + mini.value +
			"&maxi=" + maxi.value
		);

		if (check_mini_maxi == "true") {

			/* the interval is free */
			mini.style.color = "";
			mini.style.fontWeight = "";
			maxi.style.color = "";
			maxi.style.fontWeight = "";
			getElt("save").disabled = "";
		}
		else {

			/* the interval is not free */
			mini.style.color = "red";
			mini.style.fontWeight = "bold";
			maxi.style.color = "red";
			maxi.style.fontWeight = "bold";
			getElt("save").disabled = "disabled";
		}

		/* save the new maxi */
		maxi.title = maxi.value;
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
