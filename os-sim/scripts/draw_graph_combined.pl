#!/usr/bin/perl

use ossim_conf;
use DBI;
use lib $ossim_conf::ossim_data->{"rrdtool_lib_path"};
use RRDs;
use CGI;
use File::Temp;
use strict;
use warnings;

$| = 1;

sub close_all {
my ($dbh,$msg) = @_;
$dbh->disconnect;
print $msg;
print "\n\n";
exit 0;
}

my $dsn = "dbi:mysql:".$ossim_conf::ossim_data->{"ossim_base"}.":".$ossim_conf::ossim_data->{"ossim_host"}.":".$ossim_conf::ossim_data->{"ossim_port"};
my $dbh = DBI->connect($dsn, $ossim_conf::ossim_data->{"ossim_user"}, $ossim_conf::ossim_data->{"ossim_pass"}) or die "Can't connect to DBI\n";


my $q = new CGI;

print $q->header(-type => "image/png", -expires => "+10s");

#print $q->header();

my $ip;
my $query;
my $hostname="";
my $threshold;
my $color1;
my $color2;
my $what;
my $start;
my $end;
my $type;
my $rrdpath;
my $font=$ossim_conf::ossim_data->{font_path};
my $ds;
my $tempname=tmpnam();
my $zoom=1;

$zoom = $q->param('zoom') if (defined $q->param('zoom'));

if (defined $q->param('ip') && defined $q->param('what') && defined $q->param('start') && defined $q->param('end') && defined $q->param('type')) {
    $ip = $q->param('ip'); 
    $what = $q->param('what'); 
    $start = $q->param('start'); 
    $end = $q->param('end'); 
    $type= $q->param('type'); 
} else {
    close_all($dbh,"Args missing\n");
}

#close_all($dbh, "Wrong IP fmt") if(!($ip =~ m/\d+\.\d+\.\d+\.\d+/) || !($ip eq "global"));

if($type eq "host"){
    $rrdpath = $ossim_conf::ossim_data->{rrdpath_host};
} elsif($type eq "net"){
    $rrdpath = $ossim_conf::ossim_data->{rrdpath_net};
} elsif($type eq "global"){
    $rrdpath = $ossim_conf::ossim_data->{rrdpath_global};
} elsif($type eq "level"){
    $rrdpath = $ossim_conf::ossim_data->{rrdpath_level};
} else {
    close_all($dbh,"Wrong type");
}

if($what eq "compromise"){
    $ds="ds0";
    $color1="0000ff";
    $color2="ff0000";
} elsif ($what eq "attack"){
    $ds="ds1";
    $color1="ff0000";
    $color2="0000ff";
} else {
    close_all($dbh,"Huh ?");
}



if($type eq "host"){
my $query = "SELECT hostname FROM host WHERE ip = '$ip'";
my $sth = $dbh->prepare($query);
$sth->execute();
my $row = $sth->fetchrow_hashref;
$hostname = $row->{hostname};
}

if($hostname ne ""){
    if($what eq "compromise"){
        $query = "SELECT threshold_c FROM host WHERE ip = '$ip'";
        my $sth = $dbh->prepare($query);
        $sth->execute();
        my $row = $sth->fetchrow_hashref;
        $threshold = $row->{threshold_c};
    } elsif($what eq "attack"){
        $query = "SELECT threshold_a FROM host WHERE ip = '$ip'";
        my $sth = $dbh->prepare($query);
        $sth->execute();
        my $row = $sth->fetchrow_hashref;
        $threshold = $row->{threshold_a};
    }
} else {
    $threshold = $ossim_conf::ossim_data->{"threshold"};
    $hostname = $ip;
}

if($type eq "net"){ # Networks are supposed to have their own threshold
    if($what eq "compromise"){
        $query = "SELECT threshold_c FROM net WHERE name = '$ip'";
        my $sth = $dbh->prepare($query);
        $sth->execute();
        my $row = $sth->fetchrow_hashref;
        $threshold = $row->{threshold_c};
    } elsif($what eq "attack"){
        $query = "SELECT threshold_a FROM net WHERE name = '$ip'";
        my $sth = $dbh->prepare($query);
        $sth->execute();
        my $row = $sth->fetchrow_hashref;
        $threshold = $row->{threshold_a};
    }
    $hostname = $ip;
}

my ($prints,$xs,$ys)=RRDs::graph $tempname, "-s", $start, "-e", $end,
#   "DEF:obs=$rrdpath/$ip.rrd:$ds:AVERAGE",
    "DEF:obs=$rrdpath/$ip.rrd:ds0:AVERAGE",
    "DEF:obs2=$rrdpath/$ip.rrd:ds1:AVERAGE",
#    "DEF:pred=$rrdpath/$ip.rrd:$ds:HWPREDICT",
#    "DEF:dev=$rrdpath/$ip.rrd:$ds:DEVPREDICT",
#    "DEF:fail=$rrdpath/$ip.rrd:$ds:FAILURES",
#    "TICK:fail#ffffa0:1.0:Failures",
#    "CDEF:upper=pred,dev,2,*,+",
#    "CDEF:lower=pred,dev,2,*,-",
    "LINE2:obs2#ff0000:Attack",
    "LINE2:obs#0000ff:Compromise",
#    "LINE1:upper#$color2:Upper",
#    "LINE2:lower#$color2:Lower",
    "-t", "$hostname Metrics",
    "--font", "TITLE:12:$font",
    "--font", "AXIS:7:$font",
    # -X argument == multiple of 3...
#    "HRULE:$threshold#adbada", "--no-minor", "-X", "3", "-l", "0","-r",
    "HRULE:$threshold#000000", "-X", "3", "-l", "0","-r",
    "--zoom", "$zoom";

my $ERR=RRDs::error;
close_all($dbh, "ERROR while generating graffic: $ERR\n") if $ERR;

open (FILE,"<$tempname") || die "Error open() $tempname\n";
binmode(FILE); binmode(STDOUT);

while(<FILE>){
print;
}

close FILE;
unlink $tempname;


$dbh->disconnect;
print "\n\n";
exit 0;
