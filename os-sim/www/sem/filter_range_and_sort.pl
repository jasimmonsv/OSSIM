#!/usr/bin/perl
use Time::Local;

$|=1; 
if(!$ARGV[5]){
print "Accepts two epoch_timestamps as commands, loglines as stdin. Only prints out those within the two timestamps\n";
exit;
}
$debug = 0; # 1 for debuging info
$debug_log = "";

$start = $ARGV[0];
$end = $ARGV[1];
$start_line = $ARGV[2];
$num_lines = $ARGV[3];
$filter = $ARGV[4];
$order_by = ($ARGV[5] eq "date") ? "perl -e 'print <>'" : "perl -e 'print reverse <>'";
$reverse =  ($ARGV[5] eq "date") ? 0 : 1;
$debug = 1 if ($ARGV[6] eq "debug");
$debug_log = $ARGV[7] if ($ARGV[7] ne "");

$grep_str = `perl format_params_grep.pl "$filter"`;

chop($grep_str);
if ($debug_log ne "") {
	open (L,">>$debug_log");
	print L "FILTER_RANGE_AND_SORT.pl: FORMAT_PARAMS_GREP.pl: $grep_str\n";
	close L;
}

$lines_threshold = $start_line + $num_lines;

$hourday = 0;
$complete_lines = 0;
$currentdate = 0;
my %events = ();
my %already = ();
my @files = ();
#
# add first last hours of current day
#
if ($ARGV[5] ne "date") {
	my @tm = localtime($end); $tm[5]+=1900; $tm[4]++;
	$tm[3] = "0".$tm[3] if (length($tm[3])<2);
	$tm[4] = "0".$tm[4] if (length($tm[4])<2);
	open (L,"find /var/ossim/logs/".$tm[5]."/".$tm[4]."/".$tm[3]."/ -name *log 2>/dev/null | sort -r |");
	while($file=<L>) {
		chomp($file);  
		print "Adding log: $file\n" if ($debug);
		 if ($debug_log ne "") {
			open (L,">>$debug_log");
			print L "FILTER_RANGE_AND_SORT.pl: Adding log: $file\n";
			close L;
		}
		#$already{$file}++;
		#push (@files,$file);
		}
	close L;
}
#
#
while($file = <STDIN>){
	chomp($file);
	push (@files, $file) if (!defined $already{$file});
}
#
foreach my $file (@files) {
	#if ($debug) {
		#print "$file\n";
		#next;
	#}
	next if ($file =~ /Warning/ || $file eq "");
	my @fields = split(/\//,$file);
	my $sdirtime = timegm(0, 0, $fields[7], $fields[6], $fields[5]-1, $fields[4]);
	my $edirtime = timegm(59, 59, $fields[7], $fields[6], $fields[5]-1, $fields[4]);
	if ($start<=$sdirtime && $edirtime<=$end) { #if ($edirtime > $start && $sdirtime < $end) {
		#print "$file: $start - $dirtime - $end\n" if ($debug);
		if ($fields[4].$fields[5].$fields[6].$fields[7]==$hourday || $complete_lines<$lines_threshold) { # read files while same hourday or need more events
			$hourday = $fields[4].$fields[5].$fields[6].$fields[7];
			$lastdate = $currentdate; # last selected event date
			#
			$jumprow = 0;
			if ($grep_str eq "") {
				# calc jump row
				$jumprow = 1;
				%timeline = ();
				my $filet = $file; $filet =~ s/log(\.gz)?$/ind/;
				if (-e $filet) {
					open (F,$filet);
					while (<F>) {
						chomp;
						next if /^lines/;
						my @tmp = split/\:/;
						$timeline{$tmp[0]} = (!$reverse) ? $tmp[2] : $tmp[1];
					}
					close F;
					# calc jump row
					foreach $fecha (sort {$a<=>$b} keys (%timeline)) {
						$jumprow = $timeline{$fecha} if ($reverse && $end>=$fecha);
						$jumprow = $timeline{$fecha} if (!$reverse && $fecha<=$start);
					}
				}
			}
			# read line
			$read_lines = $total_lines = 0;
			if (!-e $file && -e $file.".gz") { $file .= ".gz"; }
			$cmd = ($file =~ /\.gz$/) ? "zcat \"$file\" | $order_by |" : "$order_by \"$file\" |";
			$cmd .= " $grep_str |" if ($grep_str ne "");
			print "Reading $file $jumprow $complete_lines $lines_threshold $start $end $lastdate '$filter' '$cmd'\n" if ($debug);
			if ($debug_log ne "") {
				open (L,">>$debug_log");
				print L "FILTER_RANGE_AND_SORT.pl: Reading $file $jumprow $complete_lines $lines_threshold $start $end $lastdate '$filter' '$cmd'\n";
				close L;
			}
			open (F,$cmd);
			LINE: while (<F>) {
				next LINE if ($total_lines++<$jumprow);
				if (/ date='(\d+)' /i) {
					$currentdate = $1;
					last LINE if ($reverse && $complete_lines>=$lines_threshold && $currentdate<$lastdate); # jump innecesary events
					last LINE if (!$reverse && $complete_lines>=$lines_threshold && $currentdate>$lastdate); # jump innecesary events
					#print "Evento: $currentdate > $start && $currentdate < $end\n";
					if ($currentdate > $start && $currentdate < $end) {
						chomp;
						$events{$_.";$file"} = $currentdate;
						$complete_lines++; $read_lines++;
						#print "found $complete_lines;$_;$currentdate;$lines_threshold\n" if ($debug);
						last LINE if ($read_lines>=$lines_threshold); # jump innecesary events
					}
				}
			}
			close F;
		}
	}
}
print "$complete_lines $lines_threshold $start $end $lastdate '$filter'\n" if ($debug);
 if ($debug_log ne "") {
	open (L,">>$debug_log");
	print L "FILTER_RANGE_AND_SORT.pl: $complete_lines $lines_threshold $start $end $lastdate '$filter'\n";
	close L;
}



# sort events
$from = 0;
if (!$reverse) {
	foreach $event (sort {$events{$a}<=>$events{$b}} keys (%events)) {
		if ($from>=$start_line && $from<$lines_threshold) {
			print "$event\n";
		}
		$from++;
	}
} else {
	foreach $event (sort {$events{$b}<=>$events{$a}} keys (%events)) {
		if ($from>=$start_line && $from<$lines_threshold) {
			print "$event\n";
		}
		$from++;
	}
}
