#!/usr/bin/perl

use DBI;
use ossim_conf;

my $usage = << "USAGE";
$0 [num_partitions] (max 1000)
USAGE

if(!$ARGV[0]){
print $usage;
exit;
}

$partitions = $ARGV[0];
if($partitions > 1000){
print $usage;
exit;
}

$| = 1;

my $dsn = 'dbi:mysql:'.$ossim_conf::ossim_data->{"snort_base"}.':'.$ossim_conf::ossim_data->{"snort_host"}.':'.  $ossim_conf::ossim_data->{"snort_port"};
my $dbh = DBI->connect($dsn, $ossim_conf::ossim_data->{"snort_user"}, $ossim_conf::ossim_data->{"snort_pass"}) or die "Can't connect to DBI\n";

my $query = "select TO_DAYS(NOW()) as today;";

my $sth = $dbh->prepare($query);
$sth->execute();
$row = $sth->fetchrow_hashref;

$today = $row->{today};

print "alter table acid_event partition by range (to_days(timestamp))\n("; 

for($i=1;$i<$partitions;$i++){
print "partition p$i values less than ($today),\n";
$today++;
}
print "partition p$partitions values less than MAXVALUE);\n"; 
