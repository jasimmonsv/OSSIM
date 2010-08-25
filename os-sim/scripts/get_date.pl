#!/usr/bin/perl
# RRD get date

use ossim_conf;
use strict;
use warnings;

my $rrdtool = "$ossim_conf::ossim_data->{\"rrdtool_path\"}/rrdtool";

sub usage{
print "$0 IP RANGE [compromise|attack] [host|net|global]\n";
exit(1);
}

if (!$ARGV[3]) {
   usage();
}


my $ip = $ARGV[0];
my $range = $ARGV[1];
my $what = $ARGV[2];
my $type = $ARGV[3];
my $rrdpath;

if($type eq "host"){
$rrdpath = $ossim_conf::ossim_data->{rrdpath_host};
} elsif($type eq "net"){
$rrdpath = $ossim_conf::ossim_data->{rrdpath_net};
} elsif($type eq "global"){
$rrdpath = $ossim_conf::ossim_data->{rrdpath_global};
}




$what = "1" if $what eq "compromise";   # First column
$what = "2" if $what eq "attack";       # Second column

my $date = 0;
my $temp = "";
my $greatest = "";
my $major = 0;
my $medium = 0;
my $minor = 0;

open(INPUT,"$rrdtool fetch $rrdpath/$ip.rrd MAX -s N-$range -e N|") or die "Can't execute..";
while(<INPUT>){
    if(/^(\d+):\s(\d+)\.(\d+)e\+(\d+)\s(\d+)\.(\d+)e\+(\d+)$/){
        if($_ =~ /nan/){next;};
        if($what eq "1"){
        $temp = "$4|$2|$3";
        if($temp gt $greatest){
        $greatest = $temp;
        $major = $4;
        $medium = $2;
        $minor = $3;
        $date = $1;
        }
        } elsif ($what eq "2"){
        $temp = "$7|$5|$6";
        if($temp gt $greatest){
        $greatest = $temp;
        $major = $7;
        $medium = $5;
        $minor = $6;
        $date = $1;
        }
        }
    }
}
close(INPUT);

printf("$date\n");


exit 0;
