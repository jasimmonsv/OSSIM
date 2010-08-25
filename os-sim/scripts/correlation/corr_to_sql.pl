
#!/usr/bin/perl

while(<STDIN>){
if(/(\d+),(\d+),(\d+),(\d+)/){
print "INSERT INTO plugin_reference (plugin_id, plugin_sid,  
reference_id, reference_sid) VALUES ($1, $2, $3, $4);\n";
} else {
print;
}
}

