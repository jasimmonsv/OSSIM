#!/usr/bin/perl
# only return lines for the last day
# input format: ./2010/06/23/17/xxx.xxx.xxx.xxx
my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime (time-86400); $mon++; $year+=1900;
my $yesterday = ($year.($mon<10 ? "0".$mon : $mon).($mday<10 ? "0".$mday : $mday).($hour<10 ? "0".$hour : $hour)) + 0;
while (<STDIN>) {
    $orig = $_;
	chomp;
    my @t = split /\//;
    my $time = ($t[1].$t[2].$t[3].$t[4]) + 0;
    #print "$orig $time - $yesterday\n";
    print $orig if ($time>$yesterday);
}
