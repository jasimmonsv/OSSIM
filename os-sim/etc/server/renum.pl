#!/usr/bin/perl

if(!$ARGV[2]){
print "Usage: $0 original_directives new_directives category\n";
print "Categories:\n";
print "1.  ossim\n";
print "2.  attacks\n";
print "3.  virus-worms\n";
print "4.  web-attacks\n";
print "5.  dos\n";
print "6.  port-scan\n";
print "7.  behaviour-anomaly\n";
print "8.  network-abuse-error\n";
print "9.  trojans\n";
print "10. misc\n";
print "11. user-contributed\n";
}

my %categories = {};
$categories{ossim} = "1-2999";
$categories{attacks} = "3000-5999";
$categories{virus-worms} = "6000-8999";
$categories{web-attacks} = "9000-11999";
$categories{dos} = "12000-14999";
$categories{port-scan} = "15000-17999";
$categories{behaviour-anomaly} = "18000-20999";
$categories{network-abuse-error} = "21000-23999";
$categories{trojans} = "24000-26999";
$categories{misc} = "27000-34999";
$categories{user-contributed} = "500000-1000000";

sub get_avail {
my ($directive_file,$start,$end) = @_;
my $max = $start-1;

$err = open(DIREC, "<$directive_file") || die("Can't open $directive_file: $err\n");

    while(<DIREC>){
        if(/<directive id="(\d+)".*/){
            if(($1 > $max) && ($1 < $end) && ($1 > $start)){
            $max = $1;
            }
        }
    }
    return $max;
}


my $old_directive_file = $ARGV[0];
my $new_directive_file = $ARGV[1];
my $category = $ARGV[2];
my $avail = -1;

if(defined($categories{$category})){
my @range = split("-",$categories{$category});
$avail = &get_avail($old_directive_file, $range[0], $range[1]);
} else {
print "Wrong category: $category\n";
exit();
}

$err = open(OLD, "<$old_directive_file") || die("Can't open $old_directive_file: $err\n");
$err = open(NEW, "<$new_directive_file") || die("Can't open $new_directive_file: $err\n");
while(<OLD>){
if(/\<\/directives>/){} else { print;}
}
while(<NEW>){
    if(/<directive id="(\d+)"(.*)/){
        my $new_id = $1 + $avail;
        print "<directive id=\"$new_id\"$2\n";
    } elsif (/\<?xml version/) {
    ;
    } elsif(/<directives>/){
    ;
    } else {
        print;
    }
}
close(OLD);
close(NEW);


