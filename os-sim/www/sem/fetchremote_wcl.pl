#!/usr/bin/perl
$|=1;
use Time::Local;

if(!$ARGV[3]){
	print "Expecting: start end user IP_list\n";
	print "Don't forget to escape the strings\n";
	exit;
}

$user = $ARGV[0];
$start = $ARGV[1];
$end = $ARGV[2];
$ips = $ARGV[3];

if ($start !~ /^\d+\-\d+\-\d+\s+\d+\:\d+\:\d+$/) {
	print "Parameters error in start date\n";
	exit;
}
if ($end !~ /^\d+\-\d+\-\d+\s+\d+\:\d+\:\d+$/) {
	print "Parameters error in end date\n";
	exit;
}
if ($user !~ /^[a-zA-Z]+$/) {
	print "Parameters error in date\n";
	exit;
}
if ($ips !~ /^(\d+\.\d+\.\d+\.\d+\,?)+$/) {
	print "Parameters error\n";
	exit;
}

my @ips_arr = split(/\,/,$ips);
foreach $ip (@ips_arr) {
	if ($ip eq "127.0.0.1") {
		$cmd = "perl wcl.pl '$user' '$start' '$end'";
	} else {
		$cmd = "ssh $ip \"cd /usr/share/ossim/www/sem;perl wcl.pl '$user' '$start' '$end'\"";
	}
	system($cmd);
}