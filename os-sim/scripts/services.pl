#!/usr/bin/perl

use strict;
use warnings;
use Sys::Syslog;

use DBI;
use ossim_conf;

#NOTE: Deprecated file, please use Tools->Net Scan from the web console to update host_services data

$| = 1;

my $dsn = 'dbi:mysql:'.$ossim_conf::ossim_data->{"ossim_base"}.':'.$ossim_conf::ossim_data->{"ossim_host"}.':'.  $ossim_conf::ossim_data->{"ossim_port"};
my $dbh = DBI->connect($dsn, $ossim_conf::ossim_data->{"ossim_user"}, $ossim_conf::ossim_data->{"ossim_pass"}) or 
    die "Can't connect to DBI\n";


my $nmap = $ossim_conf::ossim_data->{"nmap_path"};
my $SLEEP = 14400; # 4 hours

while(1) {

my $query = "SELECT ip FROM host";
my $sth = $dbh->prepare($query);
$sth->execute();
while (my $row = $sth->fetchrow_hashref) 
{
    my $ip = $row->{ip};

    # delete to update values
    my $query = "DELETE FROM host_services WHERE ip = '$ip'";
    my $sth = $dbh->prepare($query);
    $sth->execute();

    open(NMAP, "$nmap -sV $ip|");

    my $service = '';
    my $version = '';

    while(<NMAP>){
        if (/open\s+([\w\-\_]+)\s+(.*)$/) {
        
            $service = $1;
            $version = $2;
        
            my $query = "INSERT INTO host_services (ip, service, version)
                        VALUES ('$ip', '$service', '$version');";
            my $sth = $dbh->prepare($query);
            $sth->execute();
            next;
        }
    }
    close(NMAP);
}

sleep($SLEEP);
}

$dbh->disconnect;
exit 0;

