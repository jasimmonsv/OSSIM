#!/usr/bin/perl
$|=1;
# (c) Alienvault, DK 2011/02/11

# TODO: 
# Add support for more than a single log type per ip
# (Different ports? a keyword in the log?)

# \
# \n

if(!$ARGV[0]){
print "Usage: $0 log_source_ip [plugin_to_enable.cfg]\n";
exit;
}


$plugin = "";
$ip = $ARGV[0];
$logfile = "/var/log/ossim/$ip.log";
$tmp_logfile = "/tmp/logfile" . "_$ip" . "_$$";
$debug = 1;
$agent_conf_file = "/etc/ossim/agent/config.cfg";
$agent_conf_file_orig = "/etc/ossim/agent/config.cfg.orig";
$ossim_setup_conf = "/etc/ossim/ossim_setup.conf";

$skip_detection = 0;
$plugin_dir = "/etc/ossim/agent/plugins/";

if($ARGV[1]){
    $plugin = $ARGV[1];
    $skip_detection = 1;
    if (!-f "$plugin_dir/$plugin") {
        print "Error: $plugin_dir/$plugin not exists\n";
        exit;
    }
}

# Validate IP
if($debug){print "[+] Validating IP\n";}

########################################
######## Start doing things ############
########################################

if(!$skip_detection){

# Enable source in rsyslog
if($debug){print "[+] Enabling source in rsyslog\n";}

system("rm -f /etc/rsyslog.d/$ip.conf");
system("echo ':FROMHOST, isequal, \"$ip\" -$logfile' >> /etc/rsyslog.d/$ip.conf");
system("echo '& ~' >> /etc/rsyslog.d/$ip.conf");

# Restart syslog
if($debug){print "[+] Restarting syslog\n";}

system("/etc/init.d/rsyslog restart 2>&1>/dev/null");

# Sleep 10 seconds, test if target file is non-zero, wait another 10 seconds up to 60 seconds and stop if no logs are coming in.

$sleep_count = 0;
$sleep_time = 10;
if($debug){print "[+] Sleeping $sleep_time second(s) \n";}
sleep($sleep_time);

while(1){
if(-s $logfile){
last;
}
if($debug){print "[+] Sleeping $sleep_time second(s) \n";}
if($sleep_count > 4){
print "Breaking";
last;
}
sleep($sleep_time) unless -s $logfile;
$sleep_count++;
}

# Copy 100 lines of file to tmp
if($debug){print "[+] Copying last 100 log lines to $tmp_logfile\n";}
system("rm -f $tmp_logfile");
system("tail -n 100 $logfile > $tmp_logfile");


# Count how many lines have been copied
open(LINES, "wc -l $tmp_logfile | cut -f 1 -d \" \"|");
$actual_lines = <LINES>;
chomp($actual_lines);
close LINES;
if($debug){print "[+] Copied $actual_lines log lines\n";}

# Run regexp.py on file with all standard plugins
if($debug){print "[+] Testing log file \n";}

my %plugins_matched = ();

opendir(DIR, $plugin_dir) || die "can't opendir $plugin_dir: $!";
@plugin_files = grep { /.*.cfg$/ && -f "$plugin_dir/$_" } readdir(DIR);
closedir DIR;

foreach $plugin_file (sort(@plugin_files)){
if($plugin_file =~ /eth/){next} # skip specific interface files
if($plugin_file =~ /-monitor.cfg/){next} # skip monitor files
if($plugin_file =~ /wmi.*logger.cfg/){next} # skip wmi logger regexps
if($plugin_file =~ /post_correlation/){next} # skip false matching ones
if($plugin_file =~ /forensics-db-1/){next} # skip false matching ones
print "[+]\tTesting $plugin_file\n";
	open(TEST_PLUGIN, "python /usr/share/ossim/scripts/regexp.py $tmp_logfile $plugin_dir/$plugin_file q|");
	while(<TEST_PLUGIN>){
		if(/Matched\s+(\d+)\s+lines/ && $plugin_file !~ /\d+\.\d+\.\d+\.\d+/){
			$plugins_matched{$plugin_file} = $1;
		}
	}
	close TEST_PLUGIN;
}

if($debug){print "\n";}

# Output top 5 matching plugins
if($debug){print "[+] Top 5 matching plugins: \n";}


foreach $key (sort keys %plugins_matched){
$matched_lines = $plugins_matched{$key};
if($matched_lines > 0){
$percentg = (($matched_lines / $actual_lines) * 100);
print "\tPlugin $key: Matched $percentg%\n";
}
}

# Cleanup tmp file
if($debug){print "[+] Cleaning tmp log file \n";}
system("rm -f $tmp_logfile");


} else {

# Enable specified plugin
if($debug){print "[+] Enabling plugin $plugin \n";}

# Enable steps:
# - Calculate log location
$log_location = "/var/log/ossim/$ip.log";
$log_location =~ s/\//\\\//g;

# - Calculate original location
$orig_location = "$plugin_dir/$plugin";

# - Calculate plugin name (remove .cfg, add _ip)

@plugin_arr = split(/\./,$plugin);

$plugin_name = $plugin_arr[0] . "_$ip";
$plugin_location = "$plugin_dir/$plugin_name.cfg";


# - Calculate plugin line name, equals, location

$plugin_line = "$plugin_name=$plugin_location";

# - Copy plugin to new file
system("cp $orig_location $plugin_location");

# - Edit plugin, change location
system("sed -i -e 's/location.*=.*/location=$log_location/g' $plugin_location");

# - Edit plugin, change create
system("sed -i -e 's/create_file.*=.*/create_file=true/g' $plugin_location");


# - Check existance, if not add plugin name and location to config.cfg.orig

if(system("grep -q $plugin_name $agent_conf_file_orig")){
open(AGENT_CONF, "<$agent_conf_file_orig") or die "Can't open $agent_conf_file_orig\n";
system("rm -f /tmp/config.cfg.$$");
open(AGENT_CONF_TMP, ">/tmp/config.cfg.$$");
while(<AGENT_CONF>){

if(/^\s*\[plugins\]\s*$/){
print AGENT_CONF_TMP;
print AGENT_CONF_TMP "$plugin_line\n";
} else {
print AGENT_CONF_TMP;
}

}
close AGENT_CONF_TMP;
close AGENT_CONF;
$fsize = `cat /tmp/config.cfg.$$ | wc -c`; $fsize =~ s/[\s\r\n]*$//g;
system("mv /tmp/config.cfg.$$ $agent_conf_file_orig") if (-s $agent_conf_file_orig <= $fsize);
}

# - Check existance, if not add plugin name and location to config.cfg

if(system("grep -q $plugin_name $agent_conf_file")){
open(AGENT_CONF, "<$agent_conf_file") or die "Can't open $agent_conf_file\n";
system("rm -f /tmp/config.cfg.$$");
open(AGENT_CONF_TMP, ">/tmp/config.cfg.$$");
while(<AGENT_CONF>){

if(/^\s*\[plugins\]\s*$/){
print AGENT_CONF_TMP;
print AGENT_CONF_TMP "$plugin_line\n";
} else {
print AGENT_CONF_TMP;
}

}
close AGENT_CONF_TMP;
close AGENT_CONF;
$fsize = `cat /tmp/config.cfg.$$ | wc -c`; $fsize =~ s/[\s\r\n]*$//g;
system("mv /tmp/config.cfg.$$ $agent_conf_file") if (-s $agent_conf_file <= $fsize);
}
# - Check existance, if not add plugin name to ossim_setup.conf
#$ossim_setup_conf = "/etc/ossim/ossim_setup.conf";

# - Restart agent



if(system("grep -q $plugin_name $ossim_setup_conf")){
open(AGENT_CONF, "<$ossim_setup_conf") or die "Can't open $ossim_setup_conf\n";
system("rm -f /tmp/config.cfg.$$");
open(AGENT_CONF_TMP, ">/tmp/config.cfg.$$");
while(<AGENT_CONF>){

if(/^(\s*detectors\s*=\s*.*)$/){
$detector_line = $1 . ", $plugin_name";
print AGENT_CONF_TMP "$detector_line\n";
} else {
print AGENT_CONF_TMP;
}

}
close AGENT_CONF_TMP;
close AGENT_CONF;
$fsize = `cat /tmp/config.cfg.$$ | wc -c`; $fsize =~ s/[\s\r\n]*$//g;
system("mv /tmp/config.cfg.$$ $ossim_setup_conf") if (-s $ossim_setup_conf <= $fsize);
}
# Restart agent
if($debug){print "[+] Restarting agent \n";}
system("killall ossim-agent; ossim-agent -d 2>&1 >/dev/null &");

}

if($debug){print "[+] Done. \n";}

