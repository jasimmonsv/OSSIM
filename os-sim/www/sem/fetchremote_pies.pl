#!/usr/bin/perl
$|=1;
use Time::Local;

if(!$ARGV[10]){
	print "Expecting: start end uniqueid user ip_list\n";
	print "Don't forget to escape the strings\n";
	exit;
}

$start = $ARGV[0];
$end = $ARGV[1];
$uniqueid = $ARGV[8];
$user = $ARGV[9];
$ips = $ARGV[10];

if ($ips !~ /^(\d+\.\d+\.\d+\.\d+\,?)+$/) {
	print "Parameters error\n";
	exit;
}
if ($start !~ /^\d+\-\d+\-\d+\s+\d+\:\d+\:\d+$/) {
	print "Parameters error\n";
	exit;
}
if ($end !~ /^\d+\-\d+\-\d+\s+\d+\:\d+\:\d+$/) {
	print "Parameters error\n";
	exit;
}
if ($uniqueid !~ /^[a-z0-9]+\.[0-9]+$/) {
	print "Parameters error\n";
	exit;
}
if ($user !~ /^[a-zA-Z0-9]+$/) {
	print "Parameters error\n";
	exit;
}

my @ips_arr = split(/\,/,$ips);
print "{";
$flag = 0;
foreach $ip (@ips_arr) {
	if ($ip == "127.0.0.1") {
		$cmd = "php pies.php '$start' '$end' '$uniqueid' '$user'";
		print $cmd;exit;
	} else {
		$cmd = "ssh $ip \"cd /usr/share/ossim/www/sem;php pies.php '$start' '$end' '$uniqueid' '$user'\"";
	}
	print "," if ($flag);
	print '"'.$ip.'":';
	system($cmd);
	$flag = 1;
}
print "}\n";
