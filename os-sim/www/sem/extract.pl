#!/usr/bin/perl

if(!$ARGV[0]){
print "Must enter one of:\n";
print "plugin_id, time, sensor, src_ip, dst_ip, ftime, src_port, dst_port, data\n\n";
exit;
}

$what = $ARGV[0];

while(<STDIN>){
if(/ $what='([^']+)'/){
print "$1\n";
}
}
