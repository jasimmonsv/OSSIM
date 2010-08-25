#!/usr/bin/perl
#
###############################################################################
#
#    License:
#
#   Copyright (c) 2003-2006 ossim.net
#   Copyright (c) 2007-2009 AlienVault
#   All rights reserved.
#
#   This package is free software; you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation; version 2 dated June, 1991.
#   You may not use, modify or distribute this program under any other version
#   of the GNU General Public License.
#
#   This package is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this package; if not, write to the Free Software
#   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#   MA  02110-1301  USA
#
#
# On Debian GNU/Linux systems, the complete text of the GNU General
# Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
# Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt

# Insert old backups

use ossim_conf;
use DBI;
use strict;
use warnings;

sub getCurrentTimestamp {
    my $second;
    my $minute;
    my $hour;
    my $day;
    my $month;
    my $year;
    my $weekDay;
    my $dayOfYear;
    my $isDST;
    ($second, $minute, $hour, $day, $month, $year, $weekDay, $dayOfYear, $isDST) = localtime(time);
    $year += 1900;
    $month += 1;
    my $current = "$year-$month-$day $hour:$minute:$second";
    return $current;
}

my $list = $ARGV[0];
my $user = $ARGV[1];


my $pidfile = "/tmp/ossim-restoredb.pid";
if (-e $pidfile) {
    print "The file: $pidfile exist (remove it)\n";
    exit;
}

open(PID, ">$pidfile") or die "Unable to open $pidfile\n";
print PID $$;
close(PID);

# Data Source 
my $ossim_type = $ossim_conf::ossim_data->{"ossim_type"};
my $ossim_name = $ossim_conf::ossim_data->{"ossim_base"};
my $ossim_host = $ossim_conf::ossim_data->{"ossim_host"};
my $ossim_port = $ossim_conf::ossim_data->{"ossim_port"};
my $ossim_user = $ossim_conf::ossim_data->{"ossim_user"};
my $ossim_pass = $ossim_conf::ossim_data->{"ossim_pass"};

my $ossim_dsn = "dbi:" . $ossim_type . ":" . $ossim_name . ":" . $ossim_host . ":" . $ossim_port . ":";
#connect to DB
my $ossim_conn = DBI->connect($ossim_dsn, $ossim_user, $ossim_pass) or die "Can't connect to Database\n";
my $pid = $$;
    
my @dates = split(",", $list);

foreach my $date (@dates) {
    my $tmpfile = "/tmp/DBinsert$date.sql";
    if (-e $tmpfile) {
        my $curr = getCurrentTimestamp();
        my $query = "INSERT INTO restoredb_log (date, pid, users, data, status, percent) VALUES ('$curr', $pid, '$user', 'insert: $date', 1, 0)";
        my $stm = $ossim_conn->prepare($query);
        $stm->execute();
        $query = "SELECT LAST_INSERT_ID()";
        $stm = $ossim_conn->prepare($query);
        $stm->execute();
        my @row = $stm->fetchrow_array;
        my $id = $row[0];
        
        # Disconnect from database 
        $ossim_conn->disconnect();
        
        #insert to DB
        my $command = "ossim-db < \"$tmpfile\"";
        system($command);
        #delete temporal file
        $command = "rm -f \"$tmpfile\"";
        system($command);
        
        # Connect to Database
        my $ossim_conn = DBI->connect($ossim_dsn, $ossim_user, $ossim_pass) or die "Can't connect to Database\n";
        
        $query = "UPDATE restoredb_log SET status = 2,percent = 100 WHERE id = $id";
        $stm = $ossim_conn->prepare($query);
        $stm->execute();
        $stm->finish();
        }
    }
    
    #disconnect from DB
    $ossim_conn->disconnect();
    
    unlink $pidfile;
