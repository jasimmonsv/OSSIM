<?php 

//Key attribute
$_level_key_name = "__level_key";

//Directory ossec rules files
$rules_file = "/var/ossec/rules2/";

//Editable rules files
$editable_files = array("local_rules.xml");

//Ossec conf
$ossec_conf= "/var/ossec/etc/ossec2.conf";

//Permission for copy

@chmod($rules_file.$editable_files[0], 0770);
@chown($rules_file.$editable_files[0], "www-data");

?>