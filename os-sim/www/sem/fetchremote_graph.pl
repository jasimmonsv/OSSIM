#!/usr/bin/perl
$|=1;
use Time::Local;

if(!$ARGV[2]){
	print "Expecting: gt cat ip_list\n";
	print "Don't forget to escape the strings\n";
	exit;
}

$gt = $ARGV[0];
$cat = $ARGV[1];
$ips = $ARGV[2];

my @ips_arr = split(/\,/,$ips);
foreach $ip (@ips_arr) {
	$cmd = "cd /usr/share/ossim/www/sem;php forensic_source.php '$gt' '$cat'";
	#print "ssh $ip \"$cmd\"\n";
	system($cmd);
}
