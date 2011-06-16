#!/usr/bin/perl
$|=1;
use Time::Local;
use DBI;
require "filters.pm";

use Data::Dumper;

if(!$ARGV[6]){
print "Expecting: start_date end_date query start_line num_lines order_by operation cache_file\n";
print "Don't forget to escape the strings\n";
exit;
}

%ini = read_ini();
$loc_db = $ini{'main'}{'locate_db'};
$loc_db = "/var/ossim/logs/locate.index" if ($loc_db eq "");

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

if ($start =~ /(\d+)-(\d+)-(\d+)\s+(\d+):(\d+):(\d+)/) {
	$start_epoch = timegm($6, $5, $4, $3, $2-1, $1);
    $start_utc = $start;
	# Temporary fix until server fix
	#$start_epoch += 25200;
}
if ($end =~ /(\d+)-(\d+)-(\d+)\s+(\d+):(\d+):(\d+)/) {
	$end_epoch = timegm($6, $5, $4, $3, $2-1, $1);
    $end_utc = $end;
	# Temporary fix until server fix
	#$end_epoch += 25200;
}

#
# Use indexer / searcher if exists
#
#open (L,">>/tmp/fetch");
#print L "QUERY: $query\n";
#close L;
		
if($operation eq "logs" && $idsesion ne "NOINDEX" && -f $ini{'main'}{'searcher'} && -f $ini{'main'}{'indexer'})
{
	# Filter traslate
	$filtertr{'plugin_id'} = "plugin_id";
	$filtertr{'plugin_sid'} = "plugin_sid";
	$filtertr{'taxonomy'} = "taxonomy";
	$filtertr{'plugingroup'} = "plugingroup";
	$filtertr{'plugin_list'} = "plugin_list";
	$filtertr{'dsgroup'} = "plugingroup";
	$filtertr{'sensor'} = "sensor";
	$filtertr{'src_ip'} = "ip_src"; $filtertr{'src'} = "ip_src";
	$filtertr{'dst_ip'} = "ip_dst"; $filtertr{'dst'} = "ip_dst";
	$filtertr{'data'} = "text";
	$filtertr{'src_port'} = "sport";
	$filtertr{'dst_port'} = "dport";
	#	
	$param = "s_utc_time='$start_utc',e_utc_time='$end_utc',server=$server";
	# filters
	if ($query ne "") {
		# get different filters
		@filtrs = split(/ AND /i,$query);
		foreach $ff (@filtrs) {
			@ors = split(/#| OR /i,$ff);
			$filter = "";
			foreach $f1 (@ors) {
				$f1 =~ /\s*(.*?)=(.*)/i;
				$fname = $1; $fvalue = $2;
				if ($fname eq "data") {
					$fvalue = "'$fvalue'" if ($fvalue !~ /^\'/);
					$filter .= ($filter eq "") ? $filtertr{$fname}."=".$fvalue : ";".$fvalue;
				} else {
					$fvalue =~ s/\'//g;
					$filter .= ($filter eq "") ? $filtertr{$fname}."=".$fvalue : "|".$fvalue;
				}
			}
			$filter =~ s/SPACESCAPE/ /g;
			if ($filter =~ /^sensor=(.*)/i) { # perms filters
				$requested = $1;  
				$allowed_sensors = `php get_sensor_filter.php $user $requested`;
				$allowed_sensors =~ s/[\s\n\r]*$//g;
				$filter = ($allowed_sensors eq "") ? "" : "sensor=$allowed_sensors";
			}
			elsif ($filter =~ /^taxonomy=(.*)/i) { # taxonomy preprocess
				$filter = "taxonomy=".get_taxonomy_filter($1);
			}
			elsif ($filter =~ /^plugingroup=(.*)/i) { # plugin group preprocess
				$filter = "taxonomy=".get_plugingroup_filter($1);
			}
			elsif ($filter =~ /^plugin_list=([\d\|\:\,\;]+)/i) { # plugin list preprocess
				$idsids = $1;
				$idsids =~ s/\,/|/g;
				$filter = "taxonomy=".$idsids;
			}
			$param .= ",$filter" if ($filter ne "");
		}
	}
	# print "$param\n"; die;
	# limits and order
	$param .= ",count=$num_lines,first=$start_line";
	$param .= ($order_by eq "date") ? ",order_first" : ",order_last";
	$param =~ s/\"/\\"/g;
	$cmd = 'echo "'.$param.'" | '.$ini{'main'}{'searcher'}.' -p '.$ini{'main'}{'log_dir'};
	if ($debug ne "") {
		#open (L,">>/tmp/fetch");
		open (L,">>$debug");
		print L "FETCHALL.pl: $cmd\n";
		close L;
	}	
	system($cmd);
}

else

{

	#$grep_str = `perl format_params.pl "$query"`;
	#chop($grep_str);
	#$grep_str =~ s/^ *| *$//g;
	
	
	$common_date = `perl return_sub_dates_locate.pl \"$start\" \"$end\"`;
	chop($common_date);
	
	if (!$cache_file) {
		#$swish = "for i in `locate.findutils -d $loc_db $common_date | grep \".log\$\"`; do cat \$i; done";
		$sort = ($order_by eq "date") ? "sort -u" : "sort -r -u";
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
		$cmd = "$swish | perl filter_range_and_sort.pl $start_epoch $end_epoch $start_line $num_lines '$query' $order_by $server $idsesion $debug";
	
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


}
