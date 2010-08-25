#!/usr/bin/perl

if(!$ARGV[1]){
print "Accepts two epoch_timestamps as commands, loglines as stdin. Only prints out those within the two timestamps\n";
exit;
}

$start = $ARGV[0];
$end = $ARGV[1];

while($line = <STDIN>){
if($line =~ / date='([^']+)'/){
if($1 > $start and $1 < $end){
print $line;
}
}
}
