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
print "[{";
$flag = 0;
foreach $ip (@ips_arr) {
	if ($ip == "127.0.0.1") {
		$cmd = "php forensic_source.php '$gt' '$cat'";
	} else {
		$cmd = "ssh $ip \"cd /usr/share/ossim/www/sem;php forensic_source.php '$gt' '$cat'\"";
	}
	#print "ssh $ip \"$cmd\"\n";
	print "," if ($flag);
	print "'$ip':";
	system($cmd);
	$flag = 1;
}
print "}]\n";
