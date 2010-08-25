#!/usr/bin/perl

use Time::Local;

if(!$ARGV[6]){
print "Expecting: start_date end_date query start_line num_lines order_by operation\n";
print "Don't forget to escape the strings\n";
exit;
}


$start = $ARGV[0];
$end = $ARGV[1];
$query = $ARGV[2];
$start_line = $ARGV[3];
$num_lines = $ARGV[4];
$order_by = $ARGV[5];
$operation = $ARGV[6];

# Possible values for operation: logs or a parameter to group on: date, fdate, src_ip, dst_ip, src_port, dst_port, data

# Possible values for order_by: date, date_desc, none

############
###### Convert stuff
############

$index_file = "/var/ossim/logs/forensic_storage.index";

if($start =~ /(\d+)-(\d+)-(\d+)\s+(\d+):(\d+):(\d+)/){
$start_epoch = timegm($6, $5, $4, $3, $2-1, $1);
# Temporary fix until server fix
#$start_epoch += 25200;
}
if($end =~ /(\d+)-(\d+)-(\d+)\s+(\d+):(\d+):(\d+)/){
$end_epoch = timegm($6, $5, $4, $3, $2-1, $1);
# Temporary fix until server fix
#$end_epoch += 25200;
}

$grep_str = `perl format_params_grep.pl "$query"`;
chop($grep_str);

if($order_by eq "date"){
$sort = "sort -k 6d,6 -t \\' |";
} elsif ($order_by eq "date_desc") {
$sort = "sort -k 6d,6r -t \\' | ";
} else {
$sort = "";
}

$a = $start_line + $num_lines;

$heads_tails = "head -n $a | tail -n $num_lines";
$loc_db= "/var/ossim/logs/locate.index";

$use_swish = "0";
$swish_bin="/usr/local/bin/swish-e";
if($use_swish){

	$common_date = `perl return_sub_dates.pl \"$start\" \"$end\"`;
	#print "perl return_sub_dates.pl $start $end`;
	chop($common_date);

	if($grep_str){
		$swish = "for i in `$swish_bin -f $index_file -H 0 -w \"entry.fdate=$common_date*\" | cut -f 2 -d \" \"`; do cat \$i | $grep_str ; done";
	} else {
		$swish = "for i in `$swish_bin -f $index_file -H 0 -w \"entry.fdate=$common_date*\" | cut -f 2 -d \" \"`; do cat \$i ; done";
	}
} else {

	$common_date = `perl return_sub_dates_locate.pl \"$start\" \"$end\"`;
	#print "perl return_sub_dates.pl $start $end`;
	chop($common_date);

	if($grep_str){
		$swish = "for i in `locate.findutils -d $loc_db $common_date | grep \".log\$\"`; do cat \$i | $grep_str ; done";
	} else {
		$swish = "for i in `locate.findutils -d $loc_db $common_date | grep \".log\$\"`; do cat \$i; done";
	}
}
#$swish = "cat dk.log";

############
###### Start stuff
############

if($operation eq "logs"){
# Call swish-e for a list of the files
# cat the files
# grep them
# filter on epoch
# order them

# debug, missing swish and part
print "$swish | perl filter_range.pl $start_epoch $end_epoch | $sort $heads_tails\n";
system("$swish | perl filter_range.pl $start_epoch $end_epoch | $sort $heads_tails\n");
} else {
print "$swish | perl filter_range.pl $start_epoch $end_epoch | perl extract.pl $operation | sort | uniq -c | sort -r\n";
system("$swish | perl filter_range.pl $start_epoch $end_epoch | perl extract.pl $operation | sort | uniq -c | sort -r");
}
