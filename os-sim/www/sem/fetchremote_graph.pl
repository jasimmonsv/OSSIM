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

if ($ips !~ /^(\d+\.\d+\.\d+\.\d+\,?)+$/) {
	print "Parameters error\n";
	exit;
}
if ($gt !~ /^[a-z]+$/ && $gt !~ /^[a-z]+\_[a-z]+$/) {
	print "Parameters error\n";
	exit;
}
if ($cat !~ /^[a-zA-Z]+\%2C\+\d\d\d\d$/ && $cat !~ /^[a-zA-Z]+\s+\d+\,\s+\d\d\d\d$/ && $cat !~ /^[a-zA-Z]+\,\s\d\d\d\d/ && $cat !~ /^\d\d\d\d$/ && $cat ne "") {
	print "Parameters error\n";
	exit;
}

my @ips_arr = split(/\,/,$ips);
print "{";
$flag = 0;
foreach $ip (@ips_arr) {
	if ($ip == "127.0.0.1") {
		$cmd = "php forensic_source.php '$gt' '$cat'";
	} else {
		$cmd = "ssh $ip \"cd /usr/share/ossim/www/sem;php forensic_source.php '$gt' '$cat'\"";
	}
	print "," if ($flag);
	print '"'.$ip.'":';
	system($cmd);
	$flag = 1;
}
print "}\n";
