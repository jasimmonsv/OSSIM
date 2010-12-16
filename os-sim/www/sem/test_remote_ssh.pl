#!/usr/bin/perl
$|=1;
$ip = $ARGV[0];
if ($ip !~ /^\d+\.\d+\.\d+\.\d+$/) {
	exit;
}
if ($ip eq "127.0.0.1") {
	print "OK\n";
	exit;
}
$cmd = 'ssh -q -o "BatchMode=yes" -o "ConnectTimeout=5" root@'.$ip.' "echo 2>&1" && echo "OK" || echo "NOK" |';
open(S,$cmd);
while(<S>) {
	chomp;
	next if $_ eq "";
	print "$_\n";
}
close(S);
