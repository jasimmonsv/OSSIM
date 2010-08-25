#!/usr/bin/perl

use ossim_conf;
use strict;
use warnings;

$| = 1;

sub usage {

print "$0 start_time end_time rrd_file ";
print "[compromise|attack|ntop] [MAX|MIN|AVERAGE]\n";
print "time can be: relative, using N-1H, N-2H, etc...\n";
print "or using AT style syntax\n";
exit 0;
}

usage() if !(exists $ARGV[4]);

my $start = $ARGV[0];
my $end = $ARGV[1];
my $rrd_file = $ARGV[2];
my $what = $ARGV[3];
my $type = $ARGV[4];

$what = "ds0" if $what eq "compromise";
$what = "ds1" if $what eq "attack";
$what = "counter" if $what eq "ntop";

my @result= `$ossim_conf::ossim_data->{"rrdtool_path"}/rrdtool graph /dev/null -s $start -e $end -X 2 DEF:obs=$rrd_file:$what:AVERAGE PRINT:obs:$type:%lf`;

print "$result[1]";

exit 0;

