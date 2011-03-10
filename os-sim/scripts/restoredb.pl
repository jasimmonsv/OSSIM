#!/usr/bin/perl
$|=1;

# Script 
#
# 2004-07-28 Fabio Ospitia Trujillo <fot@ossim.net>
# 2009-05-13 jmalbarracin
# 2011-02-18 Pablo Vargas

# restoredb.pl action YYYYMMDD user nomerge entity|user
# perl /usr/share/ossim/scripts/restoredb.pl insert 20100601 admin nomerge 1 
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

if ($ARGV[3] eq "") {
	print "USAGE: restoredb.pl action YYYYMMDD user nomerge|null [entity|user]\n";
	exit;
}

my $today = getCurrentTimestamp();

my $nomerge = ($ARGV[3] eq "nomerge") ? 1 : 0;

my $filter_by = ($ARGV[4]) ? $ARGV[4] : "";
if ($filter_by ne "" && $filter_by !~ /^[a-zA-Z0-9\-\_\.]+$/) {
	print "Parameters error\n";
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

open(LOG, ">/tmp/restoredb.log");

my $backup_dir = $ossim_conf::ossim_data->{"backup_dir"};
my $backup_day = $ossim_conf::ossim_data->{"backup_day"};

# Data Source 
my $snort_type = $ossim_conf::ossim_data->{"snort_type"};
my $snort_name = ($nomerge) ? "snort_restore_".$today."_".$filter_by : $ossim_conf::ossim_data->{"snort_base"};
$snort_name =~ s/(\s|\-|\:)/_/g;
my $snort_host = $ossim_conf::ossim_data->{"snort_host"};
my $snort_port = $ossim_conf::ossim_data->{"snort_port"};
my $snort_user = $ossim_conf::ossim_data->{"snort_user"};
my $snort_pass = $ossim_conf::ossim_data->{"snort_pass"};

# Data Source 
my $ossim_type = $ossim_conf::ossim_data->{"ossim_type"};
my $ossim_name = $ossim_conf::ossim_data->{"ossim_base"};
my $ossim_host = $ossim_conf::ossim_data->{"ossim_host"};
my $ossim_port = $ossim_conf::ossim_data->{"ossim_port"};
my $ossim_user = $ossim_conf::ossim_data->{"ossim_user"};
my $ossim_pass = $ossim_conf::ossim_data->{"ossim_pass"};

my $ossim_dsn = "dbi:" . $ossim_type . ":" . $ossim_name . ":" . $ossim_host . ":" . $ossim_port . ":";
my $ossim_conn = DBI->connect($ossim_dsn, $ossim_user, $ossim_pass) or die "Can't connect to Database\n";

my $cmdline = "mysql -u$snort_user -p$snort_pass -h$snort_host -P$snort_port '$snort_name'";

# Create aux database for restore
if ($ARGV[0] eq "insert" && $nomerge) {
	my $dbh=DBI->connect('dbi:mysql:',$ossim_conf::ossim_data->{"ossim_user"},$ossim_conf::ossim_data->{"ossim_pass"}, {RaiseError=>1}) or die "Couldn't connect:".DBI->errstr();
	$dbh->do("create database if not exists $snort_name");
	createAuxDBStructure();
}

my $snort_dsn = "dbi:" . $snort_type . ":" . $snort_name . ":" . $snort_host . ":" . $snort_port;
my $snort_conn = DBI->connect($snort_dsn, $snort_user, $snort_pass) or die "Can't connect to Database\n";

my $line_curr = 0;
my $lines = 0;

sub die_clean {
    unlink $pidfile;
    $ossim_conn->disconnect();
    $snort_conn->disconnect();
    return;
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
    my ($id, $file, $action) = @_;
	
	# Patch: When action == remove
	my $remove_egrep = "";
	if ($action eq "remove") {
		$remove_egrep = "| egrep -v ' data | ossim_event | event |alertsclas'";
	}
	
    my $cmd = "zcat \"$file\" $remove_egrep|egrep -vi ' `event` '|perl -npe 's/DELETE \\*/DELETE/i'|perl /usr/share/ossim/scripts/restoredb_sql_patches.pl| $cmdline";
    print LOG "Execute $cmd\n";
    open (F,"$cmd |");
    while (<F>) {
        print $_;
    }
    close F;
}

sub createAuxDBStructure {
    my $cmd = "cat /usr/share/ossim/db/00-create_snort_tbls_mysql.sql | $cmdline";
    print LOG "Execute $cmd\n";
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
    my $nomerge_param = shift;
    my $filtered_by = shift;
    $filtered_by = "" if (!defined $filtered_by);

	my $nomerge = ($nomerge_param eq "nomerge") ? 1 : 0;

    return unless (($action eq "insert") || ($action eq "remove"));

    my @dates = split(",", $list);

	print LOG "Snort Backup Log (action = $action, list = $list, user = $user, filtered_by = $filtered_by)\n\n";

    my $curr = getCurrentTimestamp();
    
    my $query = "INSERT INTO restoredb_log (date, pid, users, data, status, percent) VALUES ('$curr', $pid, '$user', '$action: $list', 1, 0)";
    my $stm = $ossim_conn->prepare($query);
    $stm->execute();
    $query = "SELECT LAST_INSERT_ID()";
    $stm = $ossim_conn->prepare($query);
    $stm->execute();
    my @row = $stm->fetchrow_array; 
    my $id = $row[0];
	$stm->finish();
    # Disconnect from database
    $ossim_conn->disconnect();
    
    my $date;
    foreach $date (@dates) {
        $date =~ s/-//g;

        my $file;
        if ($action eq "insert") {
            $file = "$backup_dir/insert-$date.sql.gz";
        } elsif ($action eq "remove") {
            $file = "$backup_dir/delete-$date.sql.gz";
	    }
	
		next unless (-e $file);
		
		my $date_format = $date;
		$date_format =~ s/(\d\d\d\d)(\d\d)(\d\d)/$3-$2-$1/;
		print "Launching date: $date_format\n";
		print LOG "Launching file: $file\n";
		sleep(1);
		executeFile($id, $file, $action);
    }

    # Connect to Database
    my $ossim_conn = DBI->connect($ossim_dsn, $ossim_user, $ossim_pass) or die "Can't connect to Database\n";
    
    $query = "UPDATE restoredb_log SET status = 2,percent = 100 WHERE id = $id";
    $stm = $ossim_conn->prepare($query);
    $stm->execute();
    
    $stm->finish();
    
    # Selective insert. Filtering by php script
    if ($filtered_by ne "" || $nomerge) {
    	my $cmd = "php /usr/share/ossim/scripts/restoredb_filter.php '$snort_name' $nomerge_param '$filtered_by' nodebug";
    	system($cmd);
    }
    
    die_clean();
}

main(@ARGV);

close(LOG);
