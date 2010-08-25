#!/usr/bin/perl
#$ret = `ps ax|grep -v grep|awk '\$2 !~ /'\$\$'/{print}' | grep -v nocount|grep generate_stats.pl|wc -l`;
$ret = `ps ax|grep -v grep| grep -v nocount|grep generate_stats.pl|wc -l`;
$ret =~ s/\s*//g;
exit(0) if ($ret>2);

if(!$ARGV[0]){
print "Accepts folder with *log files\n";
exit;
}
$debug = 1; # 1 for debuging info
$folder = $ARGV[0];
$folder =~ s/\/$//;
$qfolder = quotemeta $folder;
$force = ($ARGV[1] eq "force") ? 1 : 0;
$param = ($ARGV[1] ne "") ? $ARGV[1] : "nocount";
$wehavedata = 0;

%stats = ();
%already = ();
%sensor_stats = ();
%sensor_folders = ();
#open (F,"find $folder | grep \".log\$\" |");
$head = (!$force) ? "| head -48" : "";
$find = "locate.findutils -d /var/ossim/logs/locate.index $folder | grep -E \"count.total\$\" | sort -ur $head";
#print "$find\n" if ($debug);
open (F,"$find |");
LOG: while ($file=<F>) {
	chomp($file); $file =~ s/count\.total$//;
	my $dir = $file; $dir =~ s/$qfolder//;
	my @fields = split(/\//,$dir);
	if ($fields[1] =~ /(^\d+$)/) { # not an hour directory, recurse inside
		my $val = $1;
		if ($val>200) { # root log directory
			my $subfolder = $folder."/".$val."/".$fields[2]."/".$fields[3]."/".$fields[4];
			if (!$already{$subfolder}++) {
				print "Recursive into $subfolder $param\n" if ($debug);
				system ("perl /usr/share/ossim/scripts/sem/generate_stats.pl \"$subfolder\" $param");
			}
		}
		elsif ($val>=1 && $val<=12) { # year log directory
			my $subfolder = $folder."/".$val."/".$fields[2]."/".$fields[3];
			if (!$already{$subfolder}++) {
				print "Recursive into $subfolder $param\n" if ($debug);
				system ("perl /usr/share/ossim/scripts/sem/generate_stats.pl \"$subfolder\" $param");
			}
		}
		elsif ($val>=1 && $val<=31 && $fields[2] =~ /^\d+$/) { # month log directory
			my $subfolder = $folder."/".$val."/".$fields[2];
			if (!$already{$subfolder}++) {
				print "Recursive into $subfolder $param\n" if ($debug);
				system ("perl /usr/share/ossim/scripts/sem/generate_stats.pl \"$subfolder\" $param");
			}
		}
		else { # day directory 
			my $subfolder = $folder."/".$val;
			if (!$already{$subfolder}++) {
				print "Recursive into $subfolder $param\n" if ($debug);
				system ("perl /usr/share/ossim/scripts/sem/generate_stats.pl \"$subfolder\" $param");
			}
		}
	} else {
		print "Searching logs into $file\n" if ($debug);
		
		open (L,"find $file -name '*.log' |");
		while ($logfile=<L>) {
            chomp($logfile);
            
            print "Reading from $logfile\n" if ($debug);
            # Current sensor grep for user filtering:
            my @campos = split(/\//,$logfile);
            my $current_sensor = $campos[8];
            my $filet = $logfile; $filet =~ s/log$/ind/;
            my $sensorfilestat = $logfile; $sensorfilestat =~ s/[^\/]+$/data.stats/;
            $sensor_folders{$sensorfilestat} = $current_sensor;
            if (-e $filet && !$force && -e $sensorfilestat && -s $sensorfilestat > 1) {
                print "Skipping $file. Already exists. stats.data size: ".(-s $sensorfilestat)."\n";
                next LOG;
            }
            %rangos = ();
            $lasttime = -1;
            $lastdate = 4102444800;
            $line = 0;
            
            open (G,"tac '$logfile' |");
            while (<G>) {
                chomp;
                #if (/ id='(\d+)' .* date='(\d+)' plugin_id='([^']+)' sensor='([^']+)' src_ip='([^']+)' dst_ip='([^']+)' src_port='([^']+)' dst_port='([^']+)' tzone='([^']+)' data='([^']+)'/) {
                if (/ id='(\d+)' .* date='(\d+)' plugin_id='([^']+)' sensor='([^']+)' src_ip='([^']+)' dst_ip='([^']+)' src_port='([^']+)' dst_port='([^']+)'/) {
                    $line++; $id = $1; $fecha = $2;
                    my @timeData = localtime($fecha);
                    if ($timeData[1] != $lasttime && $fecha<$lastdate) {
                        $lasttime = $timeData[1];
                        $lastdate = $fecha;
                        $rangos{$fecha} = "$line:$id";
                    }
                    $plugin_id = $3;
                    $stats{"plugin_id"}{$3}++;
                    $stats{"sensor"}{$4}++;
                    $stats{"src_ip"}{$5}++;
                    $stats{"dst_ip"}{$6}++;
                    $stats{"src_port"}{$7}++;
                    $stats{"dst_port"}{$8}++;
                    #$stats{"time_zone"}{$9}++;
                    #$stats{"data"}{$10}++;
                    $wehavedata++;
                    
                    $sensor_stats{$current_sensor}{"plugin_id"}{$3}++;
                    $sensor_stats{$current_sensor}{"sensor"}{$4}++;
                    $sensor_stats{$current_sensor}{"src_ip"}{$5}++;
                    $sensor_stats{$current_sensor}{"dst_ip"}{$6}++;
                    $sensor_stats{$current_sensor}{"src_port"}{$7}++;
                    $sensor_stats{$current_sensor}{"dst_port"}{$8}++;
                    
                    # optional plugin_sid stats
                    if (/ plugin_sid='([^']+)'/) {
                        $stats{"plugin_sid"}{"$plugin_id,".$1}++;
                        $sensor_stats{$current_sensor}{"plugin_sid"}{"$plugin_id,".$1}++;
                    }
                }
            }
            close G;
            print "\tGenerate Index $filet\n" if ($debug);
            open (S,">$filet");
            foreach $date (sort {$b<=>$a} keys (%rangos)) {
                print S "$date:$rangos{$date}\n";
            }
            print S "lines:$line\n";
            close S;
        
        }
        close L;
           
	}
}
close F;

# sort stats
@arr = ("plugin_id","sensor","src_ip","dst_ip","src_port","dst_port","plugin_sid");
if ($wehavedata>0) {
	print "Writing $folder/data.stats\n" if ($debug);
	open(F,">$folder/data.stats");
	foreach $type (@arr) {
		foreach $value (sort {$stats{$type}{$b}<=>$stats{$type}{$a}} keys (%{$stats{$type}})) {
			print F $type.":".$value.":".$stats{$type}{$value}."\n";
		}
	}
	close F;
	
	print "Writing Sensor STATS\n";
	foreach $sensorfilestat (keys (%sensor_folders)) {
		$s = $sensor_folders{$sensorfilestat};
		print "\tWriting $sensorfilestat\n";
		open(F,">$sensorfilestat");
		foreach $type (@arr) {
			foreach $value (sort {$sensor_stats{$s}{$type}{$b}<=>$sensor_stats{$s}{$type}{$a}} keys (%{$sensor_stats{$s}{$type}})) {
				print F $type.":".$value.":".$sensor_stats{$s}{$type}{$value}."\n";
			}
		}
		close F;
	}
}
print "Done.\n" if ($debug);
