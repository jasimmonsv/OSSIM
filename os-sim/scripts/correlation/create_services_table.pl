#!/usr/bin/perl


open(PROCESO, "cat /etc/services|");

@services;

while(<PROCESO>){
if(/(\S+)\s+(\d+)\/.*/){
$services[$2] = $1;
}
}

close PROCESO;

for($i=0;$i<65536;$i++){
if(exists($services[$i])){
print "INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5002, $i, NULL, NULL, 1, 1, \"$services[$i]\");\n";
} else {
print "INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5002, $i, NULL, NULL, 1, 1, \"Port $i\");\n";
}
}


