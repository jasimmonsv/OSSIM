#!/usr/bin/perl

# Script 
#
# 2004-07-28 Fabio Ospitia Trujillo <fot@ossim.net>
# 2009-05-13 jmalbarracin

use ossim_conf;
use DBI;
use POSIX;
use Compress::Zlib;
#setlocale(LC_ALL, "es_US");

use strict;
use warnings;

#sub byebye {
#    print "$0: forking into background...\n";
#    exit;
#}
#
#fork and byebye;

my $base_dir = $ossim_conf::ossim_data->{"base_dir"};
unless ($base_dir) {
    print "The var: base_dir not exist\n";
    exit;
}

my $pidfile = "/tmp/ossim-restoredb.pid";

my $pid = $$;

if (-e $pidfile) {
    print "The file: $pidfile exist (remove it)\n";
    exit;
}

open(PID, ">$pidfile") or die "Unable to open $pidfile\n";
print PID $$;
close(PID);

my $backup_dir = $ossim_conf::ossim_data->{"backup_dir"};
my $backup_day = $ossim_conf::ossim_data->{"backup_day"};

# Data Source 
my $snort_type = $ossim_conf::ossim_data->{"snort_type"};
my $snort_name = $ossim_conf::ossim_data->{"snort_base"};
my $snort_host = $ossim_conf::ossim_data->{"snort_host"};
my $snort_port = $ossim_conf::ossim_data->{"snort_port"};
my $snort_user = $ossim_conf::ossim_data->{"snort_user"};
my $snort_pass = $ossim_conf::ossim_data->{"snort_pass"};


my $snort_dsn = "dbi:" . $snort_type . ":" . $snort_name . ":" . $snort_host . ":" . $snort_port;
my $snort_conn = DBI->connect($snort_dsn, $snort_user, $snort_pass) or die "Can't connect to Database\n";

# Data Source 
my $ossim_type = $ossim_conf::ossim_data->{"ossim_type"};
my $ossim_name = $ossim_conf::ossim_data->{"ossim_base"};
my $ossim_host = $ossim_conf::ossim_data->{"ossim_host"};
my $ossim_port = $ossim_conf::ossim_data->{"ossim_port"};
my $ossim_user = $ossim_conf::ossim_data->{"ossim_user"};
my $ossim_pass = $ossim_conf::ossim_data->{"ossim_pass"};

my $ossim_dsn = "dbi:" . $ossim_type . ":" . $ossim_name . ":" . $ossim_host . ":" . $ossim_port . ":";
my $ossim_conn = DBI->connect($ossim_dsn, $ossim_user, $ossim_pass) or die "Can't connect to Database\n";

my $cmdline = "mysql -u$snort_user -p$snort_pass -h$snort_host -P$snort_port $snort_name";

my $line_curr = 0;
my $lines = 0;

sub die_clean {
    unlink $pidfile;
    $ossim_conn->disconnect();
    $snort_conn->disconnect();
    exit;
}

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

sub linesFile {
    my ($file) = @_;

    my $gz = gzopen("$file", "r") or die "Can't open file log $file";
    while ($gz->gzreadline($_) > 0) {
	$lines++;
    }
    $gz->gzclose;
}

sub executeFile {
    my ($id, $file) = @_;

    my $cmd = "zcat \"$file\" |egrep -vi ' `event` '|perl -npe 's/DELETE \\*/DELETE/i'| $cmdline";
    print "Execute $cmd\n";
    open (F,"$cmd |");
    while (<F>) {
        print $_;
    }
    close F;
}

sub main {
    my $action = shift;
    my $list = shift;
    my $user = shift;

    return unless (($action eq "insert") || ($action eq "remove"));

    my @dates = split(",", $list);

    my $curr = getCurrentTimestamp();
    my $query = "INSERT INTO restoredb_log (date, pid, users, data, status, percent) VALUES ('$curr', $pid, '$user', '$action: $list', 1, 0)";
    my $stm = $ossim_conn->prepare($query);
    $stm->execute();
    $query = "SELECT LAST_INSERT_ID()";
    $stm = $ossim_conn->prepare($query);
    $stm->execute();
    my @row = $stm->fetchrow_array; 
    my $id = $row[0];

    # Disconnect from database
    $ossim_conn->disconnect();
    
    my $date;
    foreach $date (@dates) {
        $date =~ s/-//g;

        my $file;
        if ($action eq "insert") {
            $file = "$backup_dir/insert-$date.sql.gz";
        } elsif ($action eq "remove") {
            #$file = "$backup_dir/delete-$date.sql.gz";

            system("rm -f /tmp/delete_sql*; zcat $backup_dir/delete-$date.sql.gz | egrep -v ' data | ossim_event | event |alertsclas' > /tmp/delete_sql; gzip /tmp/delete_sql");
            $file = "/tmp/delete_sql.gz";
    }

	next unless (-e $file);

	print "Launching sql file: $file\n";
	executeFile($id, $file);
    }

    # Connect to Database
    my $ossim_conn = DBI->connect($ossim_dsn, $ossim_user, $ossim_pass) or die "Can't connect to Database\n";
    
    $query = "UPDATE restoredb_log SET status = 2,percent = 100 WHERE id = $id";
    $stm = $ossim_conn->prepare($query);
    $stm->execute();
    
    $stm->finish();
    die_clean();
}

main(@ARGV);
