#!/usr/bin/perl
# running?
$ret = `ps ax|grep -v grep| grep $0|wc -l`;
$ret =~ s/\s*//g;
if ($ret>1) {
    print "Already running...exit\n";
    exit(0);
}

# IMPORT FROM STORAGE DB
# ossim-server writes mysql dump files into storage directory. This script inserts into snort database from ossim-server dumps
# Install into cron like this: */15 * * * *  /usr/share/ossim/scripts/import_from_storage_db.pl >> /var/log/ossim/import_from_storage.log 2>&1
#
$|=1;
$deletefiles = 0;

use lib "/usr/share/ossim/include";
use ossim_conf;

my $storage_dir = "/var/ossim/slave";
# Data Source 
my $snort_name = $ossim_conf::ossim_data->{"snort_base"};
my $snort_type = $ossim_conf::ossim_data->{"snort_type"};
my $snort_host = $ossim_conf::ossim_data->{"snort_host"};
my $snort_port = $ossim_conf::ossim_data->{"snort_port"};
my $snort_user = $ossim_conf::ossim_data->{"snort_user"};
my $snort_pass = $ossim_conf::ossim_data->{"snort_pass"};
my $snort_dsn = "DBI:" . $snort_type . ":" . $snort_name . ":" . $snort_host . ":" . $snort_port;
my $cmdline = "mysql -u$snort_user -p$snort_pass -h$snort_host -P$snort_port $snort_name";

$filename=`ls -t1 $storage_dir/*sql`; chomp($filename);
@importfiles = split(/\n/,$filename);
foreach $file (@importfiles) {
    if ($file =~ /\_(\d\d\d\d\d\d\d\d\d\d\d\d\d\d)/) {
        $sign = $1;
        print "Importing $file... ";
        $start = time;
        system("$cmdline < $file");
        $end = time - $start;
        print "$end seconds\n";
        # check time mark (sign)
        $dbh = DBI->connect($snort_dsn, $snort_user, $snort_pass) or die "Error: Can't connect to Database\n";
        $sql = qq{ select date from last_update };
        $sth_selm=$dbh->prepare( $sql );
        $sth_selm->execute;
        $date_to_check=$sth_selm->fetchrow_array;
        $date_to_check =~  s/[\-\:\s]+//g;
        $sth_selm->finish;
        $dbh->disconnect;
        #
        if ($date_to_check==$sign) {
            print "Validate $sign OK. Deleting $file\n";
            system ("rm -f $file") if ($deletefiles);
        } else {
            print "Error: $sign != $date_to_check. Keeping $file\n";
        }
    }
}
