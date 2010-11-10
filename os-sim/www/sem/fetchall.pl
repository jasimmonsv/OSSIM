#!/usr/bin/perl
$|=1;
use Time::Local;

#use DBI;
#$db="ossim";
#$host="localhost";
#$userid="root";
#$passwd="ossim";
#$connectionInfo="dbi:mysql:$db;$host";
#$dbh = DBI->connect($connectionInfo,$userid,$passwd);
#$sth = $dbh->prepare("SELECT login FROM users");
#$sth->execute();
#$sth->bind_columns(\$login);
#while ($sth->fetch()) {
#}
#$sth->finish() if ($sth);
#$dbh->disconnect;

if(!$ARGV[6]){
print "Expecting: start_date end_date query start_line num_lines order_by operation cache_file\n";
print "Don't forget to escape the strings\n";
exit;
}

$debug="";

$start = $ARGV[0];
$end = $ARGV[1];
$query = $ARGV[2];
$start_line = $ARGV[3];
$num_lines = $ARGV[4];
$order_by = $ARGV[5];
$operation = $ARGV[6];
$cache_file = $ARGV[7];
$idsesion = $ARGV[8];

$user = $ARGV[9]; # Could be user OR server IP if remote call
$debug = $ARGV[10];

if ($user =~ /\d+\.\d+\.\d+\.\d+/) {
	$server = $user;
	$user = "admin";
} else {
	$server = "127.0.0.1";
}

$cache_file = "" if ($cache_file !~ "/var/ossim/cache/.*cache.*");

# Possible values for operation: logs or a parameter to group on: date, fdate, src_ip, dst_ip, src_port, dst_port, data

# Possible values for order_by: date, date_desc, none

############
###### Convert stuff
############


$index_file = "/var/ossim/logs/forensic_storage.index";

if ($start =~ /(\d+)-(\d+)-(\d+)\s+(\d+):(\d+):(\d+)/) {
	$start_epoch = timegm($6, $5, $4, $3, $2-1, $1);
# Temporary fix until server fix
#$start_epoch += 25200;
}
if ($end =~ /(\d+)-(\d+)-(\d+)\s+(\d+):(\d+):(\d+)/) {
	$end_epoch = timegm($6, $5, $4, $3, $2-1, $1);
# Temporary fix until server fix
#$end_epoch += 25200;
}


#$grep_str = `perl format_params.pl "$query"`;
#chop($grep_str);
#$grep_str =~ s/^ *| *$//g;


$loc_db = "/var/ossim/logs/locate.index";

$common_date = `perl return_sub_dates_locate.pl \"$start\" \"$end\"`;
#print "perl return_sub_dates.pl $start $end`;
chop($common_date);


if (!$cache_file) {
	#$swish = "for i in `locate.findutils -d $loc_db $common_date | grep \".log\$\"`; do cat \$i; done";
	$sort = ($order_by eq "date") ? "sort" : "sort -r";
	$swish = "locate.findutils -d $loc_db $common_date | grep -E \".(log|log.gz)\$\" | php check_perms.php $user | $sort";
} else {
	$swish = "echo $cache_file";
}


############
###### Start stuff
############

if($operation eq "logs") {
	# Call swish-e for a list of the files
	# cat the files
	# grep them
	# filter on epoch
	# order them

	# debug, missing swish and part
	$cmd = "$swish | perl filter_range_and_sort.pl $start_epoch $end_epoch $start_line $num_lines \"$query\" $order_by $server $idsesion $debug";
	print "$cmd\n" if ($idsesion eq "debug");
	system($cmd);
	 if ($debug ne "") {
		open (L,">>$debug");
		print L "FETCHALL.pl: $cmd\n";
		close L;
	 }
} else {
	$cmd = "$swish | perl extract_stats.pl $operation $start_epoch $end_epoch $idsesion";
	print "$cmd\n" if ($idsesion eq "debug");
	system($cmd);
	 if ($debug ne "") {
		open (L,">>$debug");
		print L "FETCHALL.pl: $cmd\n";
		close L;
 }
}
