#!/usr/bin/perl
my $sum = 0;
$ARGV[0]="." if ($ARGV[0] eq "");
open (F,"find $ARGV[0] -name 'count.total' -type f |");
while (<F>) {
	chomp;
	$lines = `cat $_`;
	if ($lines =~ /(\d+)/) { $sum += $1; }
}
close F;
print $sum;
