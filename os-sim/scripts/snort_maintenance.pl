#!/usr/bin/perl
$|=1;

# Snort maintenance script
#
# snort_maintenance.pl [repair|clear]
# 
use ossim_conf;
use DBI;

# Conection
if ($ARGV[0] ne "repair" && $ARGV[0] ne "clear") {
	print "USAGE: snort_maintenance.pl [repair|clear]\n";
	exit;
}

my $schema = "/usr/share/ossim/www/forensics/scripts/schema.sql";

# Data Source 
my $snort_type = $ossim_conf::ossim_data->{"snort_type"};
my $snort_name = $ossim_conf::ossim_data->{"snort_base"};
my $snort_host = $ossim_conf::ossim_data->{"snort_host"};
my $snort_port = $ossim_conf::ossim_data->{"snort_port"};
my $snort_user = $ossim_conf::ossim_data->{"snort_user"};
my $snort_pass = $ossim_conf::ossim_data->{"snort_pass"};

my $cmdline = "mysql -u$snort_user -p$snort_pass -h$snort_host -P$snort_port '$snort_name'";


# REPAIR
if ($ARGV[0] eq "repair") {
	if (-e $schema) {
		print "Repairing SNORT BBDD ...";
		system("$cmdline < $schema > /tmp/repair_snort_schema_log");
	} else {
		print "$schema NOT FOUND.\n";
	}
}

# CLEAR
if ($ARGV[0] eq "clear") {
	my $snort_dsn = "dbi:" . $snort_type . ":" . $snort_name . ":" . $snort_host . ":" . $snort_port;
	my $snort_conn = DBI->connect($snort_dsn, $snort_user, $snort_pass) or die "Can't connect to Database\n";
	@querys = ("TRUNCATE acid_event","TRUNCATE acid_event_input","TRUNCATE icmphdr","TRUNCATE iphdr","TRUNCATE sensor","TRUNCATE tcphdr","TRUNCATE udphdr","TRUNCATE extra_data","TRUNCATE opt","TRUNCATE ac_sensor_sid","TRUNCATE ac_sensor_signature","TRUNCATE ac_sensor_ipsrc","TRUNCATE ac_sensor_ipdst","TRUNCATE ac_alerts_sid","TRUNCATE ac_alerts_signature","TRUNCATE ac_alerts_ipsrc","TRUNCATE ac_alerts_ipdst","TRUNCATE ac_srcaddr_ipdst","TRUNCATE ac_srcaddr_ipsrc","TRUNCATE ac_srcaddr_sid","TRUNCATE ac_dstaddr_ipdst","TRUNCATE ac_dstaddr_ipsrc","TRUNCATE ac_dstaddr_sid","TRUNCATE ac_layer4_sport","TRUNCATE ac_layer4_sport_sid","TRUNCATE ac_layer4_sport_signature","TRUNCATE ac_layer4_sport_ipsrc","TRUNCATE ac_layer4_sport_ipdst","TRUNCATE ac_layer4_dport","TRUNCATE ac_layer4_dport_sid","TRUNCATE ac_layer4_dport_signature","TRUNCATE ac_layer4_dport_ipsrc","TRUNCATE ac_layer4_dport_ipdst");
	foreach	$q (@querys) {
		print "$q\n";
    	my $stm = $snort_conn->prepare($q);
    	$stm->execute();
    }
	$snort_conn->disconnect();
}

print "Done.\n";
