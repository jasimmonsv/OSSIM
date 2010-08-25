#!/usr/bin/perl
use File::Basename;
use Time::Local;

if(!$ARGV[0]){
print "Must enter one of:\n";
print "all, plugin_id, time, sensor, src_ip, dst_ip, ftime, src_port, dst_port, data\n\n";
exit;
}
$debug = 0; # 1 for debuging info
$what = $ARGV[0];
$start = $ARGV[1];
$end = $ARGV[2];
$debug = 1 if ($ARGV[3] eq "debug");

%already = ();
%stats = ();
while ($file=<STDIN>) {
	chomp($file);
	my @fields = split(/\//,$file);
	my $sdirtime = timegm(0, 0, $fields[7], $fields[6], $fields[5]-1, $fields[4]);
	my $edirtime = timegm(59, 59, $fields[7], $fields[6], $fields[5]-1, $fields[4]);
	if ($edirtime > $start && $sdirtime < $end) {
		$sf = dirname($file);
		#$sf =~ s/\/((25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.){3}(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])$//;
		$sf .= "/data.stats";
		#print "Abriendo $sf\n";
		if (!$already{$sf}++) {
			print "Reading $sf\n" if ($debug);
			open (F,$sf);
			while (<F>) {
				chomp;
                if ($what eq "all" && /^(.*?)\:(.*)\:(\d+)/) {
                    $stats{$1}{$2} += $3;
                } elsif (/^$what\:(.*)\:(\d+)/) {
					$stats{$1} += $2;
				}
			}
			close F;
		}
	}
}
#
@ks = keys (%stats);
if (@ks>0) {
    if ($what eq "all") {
        @ks = keys (%stats);
        foreach $what (@ks) {
            $i=1;
            STAT: foreach $value (sort {$stats{$what}{$b}<=>$stats{$what}{$a}} keys (%{$stats{$what}})) {
                print " $what $stats{$what}{$value} $value\n";
                last STAT if ($i++>=10);
            }
        }
    } else {
        $i=1;
        STAT: foreach $value (sort {$stats{$b}<=>$stats{$a}} @ks) {
            print " $stats{$value} $value\n";
            last STAT if ($i++>=10);
        }
    }
} else {
    print "0 none\n";
}
