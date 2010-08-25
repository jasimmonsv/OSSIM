#!/usr/bin/perl

use strict;
use warnings;
use Sys::Syslog;

use DBI;
use ossim_conf;

$| = 1;

my $dsn = 'dbi:mysql:'.$ossim_conf::ossim_data->{"ossim_base"}.':'.$ossim_conf::ossim_data->{"ossim_host"}.':'.  $ossim_conf::ossim_data->{"ossim_port"};
my $dbh = DBI->connect($dsn, $ossim_conf::ossim_data->{"ossim_user"}, $ossim_conf::ossim_data->{"ossim_pass"}) or 
    die "Can't connect to DBI\n";


my $SLEEP = 14400; # 4 hours
                                                                                while(1) {

my $query = "SELECT ip FROM host";
my $sth = $dbh->prepare($query);
$sth->execute();
while (my $row = $sth->fetchrow_hashref) 
{
    my $ip = $row->{ip};
    my $name = '';
    my $wgroup = '';
    
    open(NMB, "nmblookup -A $ip|");

    while(<NMB>){
        if (/([\w\-\_]+)\s+\<20\>/) {
            $name = $1;
            next;
        }
        if (/([\w\-\_]+)\s+\<1e\>/) {
            $wgroup = $1;
            next;
        }

        if ($name && $wgroup) {
            my $query = "SELECT * FROM host_netbios WHERE ip = '$ip'";
            my $sth = $dbh->prepare($query);
            $sth->execute();
            if ($sth->rows == 0) {
                $query = "INSERT INTO host_netbios 
                            VALUES ('$ip', '$name', '$wgroup')";
                $sth = $dbh->prepare($query);
                $sth->execute();
            } else {
                $query = "UPDATE host_netbios 
                            SET name = '$name', wgroup = '$wgroup' 
                            WHERE ip = '$ip';";
                $sth = $dbh->prepare($query);
                $sth->execute();
            }
    
            $name = $wgroup = '';
            last;
        }
    }
    close(NMB);
}

sleep($SLEEP);
}

$dbh->disconnect;
exit 0;

