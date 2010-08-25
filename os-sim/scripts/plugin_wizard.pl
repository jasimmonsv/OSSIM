#!/usr/bin/perl

use Getopt::Std;

my $save_filename = "windows_plugins.ossim";
my $plugin_dir = "win_plugins";



# -----------------------------------------------------------------------
# Keywords for substitution:
# - PLUGIN_ID
# - PLUGIN_DESC_SHORT
# - PLUGIN_NAME

my $plugin_sql_header .= << "END";
-- PLUGIN_NAME
-- plugin_id: PLUGIN_ID;
DELETE FROM plugin WHERE id = "PLUGIN_ID";
DELETE FROM plugin_sid where plugin_id = "PLUGIN_ID";

INSERT INTO plugin (id, type, name, description) VALUES (PLUGIN_ID, 1, 'PLUGIN_DESC_SHORT', 'PLUGIN_NAME');
INSERT IGNORE INTO plugin_sid(plugin_id, sid, category_id, class_id, priority, reliability, name) VALUES (PLUGIN_ID, 99999, NULL, NULL, 1, 5, 'PLUGIN_DESC_SHORT: Unknown event for this type of plugin, please check the payload for more information and send a mail to plugins\@alienvault.com with the contents.');

END



# -----------------------------------------------------------------------
# Keywords for substitution:
# - PLUGIN_ID
# - PLUGIN_NAME
# - TRANSLATE_PLACEHOLDER


my $plugin_cfg .= << "END";
;; PLUGIN_NAME
;; plugin_id: PLUGIN_ID
;;


[DEFAULT]
plugin_id=PLUGIN_ID

[config]
type=detector
enable=yes

source=log
location=/var/log/syslog
create_file=true

process=
start=no
stop=no
startup=
shutdown=

[translation]
TRANSLATE_PLACEHOLDER
_DEFAULT_=99999

[PLUGIN_DESC_SHORT]
event_type=event
regexp="^(?P<all>(?P<date>\\w+\\s+\\d{1,2}\\s\\d\\d:\\d\\d:\\d\\d)\\s+(?P<sensor>\\S+)\\s+.*MSWinEventLog;\\d+;\\w+;\\d+;(?P<date2>\\w+\\s+\\w+\\s+\\d{1,2}\\s\\d\\d:\\d\\d:\\d\\d\\s+\\d+);(?P<plugin_sid>\\d+);[^;]+;(?P<username>[^;]+);PLUGIN_NAME;(?P<reminder>.*))\$"
date={normalize_date(\$date)}
sensor={resolv(\$sensor)}
src_ip={resolv(\$sensor)}
dst_ip={resolv(\$sensor)}
plugin_id=PLUGIN_ID
plugin_sid={translate(\$plugin_sid)}
username={\$username}
userdata1={\$reminder}
userdata2={\$all}

END

# -----------------------------------------------------------------------
# Keywords for substitution:
# - PLUGIN_ID
# - PLUGIN_SID
# - PLUGIN_DESC_SHORT
# - SID_NAME
my $sid_query = "INSERT IGNORE INTO plugin_sid(plugin_id, sid, category_id, class_id, priority, reliability, name) VALUES (PLUGIN_ID, PLUGIN_SID, NULL, NULL, 1, 5, 'PLUGIN_DESC_SHORT: SID_NAME');";
# -----------------------------------------------------------------------

sub usage {
    print "                   \n";
    print "Usage:\n";
    print "$0 [-l] [-s string]\n";
    print "                   \n";
    print "Options:\n";
    print "    -l             List all available plugins\n";
    print "                   \n";
    print "    -s string      Search for a plugin matching \"string\"\n";
    print "                   \n";
    print "    -i             Feed new data from stdin.\n";
    print "                   \n";
    print "    -w             Save generated data to statusfile.\n";
    print "                   \n";
    print "    -f             Ignore existing plugin output dir, force execution. Avoids -g complaining about it's existence.\n";
    print "                   \n";
    print "    -g             Generate the .cfg and .sql files for the matching plugins\n";
    print "                   Can be used with -s in order to generate a subset of plugins\n";
    print "                   Will be prefixed with win_ in current directory\n";
    print "                   Additionally, a 'win_config.cfg' file will be created with the /etc/ossim/agent/config.cfg entries\n";
    print "                   \n";
    print "    -h             This help\n";
    print "                   \n";
    exit 0
}

if(!$ARGV[0]){
usage();
}

my %options=();
getopts("s:lihwfvg",\%options);

if(defined $options{h}){
usage();
}

if(defined $options{g}){
if(!defined $options{f}){
unless(mkdir $plugin_dir, "1777"){
print "Unable to create plugin output dir: $plugin_dir\n";
print "Please remove this dir. Exiting\n";
exit;
}
}
}


my $plugin_id = 12001;

my %plugins;
my %plugins_rev;
my %plugin_ids;

sub parse_entry{
  my $sid_id = 0;
  my $plugin_name = "";
  my $sid_name = "";

  $sid_id = shift;

	while(<STDIN>){
  	if(/Source.*bgcolor="#FEFEFE">([^<]+)</){
	  	$plugin_name = $1;
		}
  	if(/Description.*bgcolor="#FEFEFE">(.*)<\/td><\/tr>/){
	  	$sid_name = $1;
			consolidate_entry($plugin_name, $sid_id, $sid_name);
			return;
		}
	}

}

sub consolidate_entry{
   my $plugin_name = ucfirst(shift);
   my $sid_id = shift;
   my $sid_name = shift;

	 if(!exists($plugins{$plugin_name})){
			$plugins{$plugin_name} = $plugin_id++;
	 }
      $plugin_ids{$plugin_name}{$sid_id} = $sid_name;
}

sub add_plugin{
   my $plug_id = shift;
   my $plugin_name = shift;
	 if(!exists($plugins{$plugin_name})){
			$plugins{$plugin_name} = $plug_id;
	 }
}

sub add_plugin_sid{
   my $plugin_id = shift;
   my $sid_id  = shift;
   my $sid_name = shift;
   my $plugin_name = "";


   $plugin_name = $plugins_rev{$plugin_id};
   
   if($plugin_name ne ""){
      $plugin_ids{$plugin_name}{$sid_id} = $sid_name;
   } else {
      print "Savefile $save_filename corrupted. Sid $sid_id found that's got no matching plugin_id $plugin_id\n";
   }
}

sub shorten_name{
   my $plugin_name = shift;
   $plugin_name =~ s/ /_/g;
   $plugin_name = "win_" . lc($plugin_name);
   $plugin_name = substr($plugin_name, 0, 50);

   return $plugin_name;
}

sub print_plugins_rev{
	foreach my $key (keys %plugins_rev){
	 print "\t" .$plugins_rev{$key} . " $key\n";
	 }
}

sub print_plugins{
	foreach my $key (keys %plugins){
	 print "\t" . $plugins{$key} . " $key\n";
	 }
}

sub load_plugin_data{
	unless(open(PLUGFILE, "<$save_filename")){
	print "Unable to open plugin savefile: $save_filename\n";
	return;
	}
	while(<PLUGFILE>){
	   #plugin###plugin_id###plugin_sid
	   if(/plugin###(\d+)###(.*$)/){
	    add_plugin($1,$2);
	   }
	}
        close PLUGFILE;
}

sub load_sid_data{
	unless(open(PLUGFILE, "<$save_filename")){
	print "Unable to open plugin savefile: $save_filename\n";
	return;
	}
	while(<PLUGFILE>){
	   #plugin_sid###plugin_id_number###plugin_sid_number###plugin_sid_name###plugin_sid_priority###plugin_sid_reliability
	   if(/plugin_sid###(\d+)###(\d+)###(.*$)/){
	    add_plugin_sid($1,$2,$3);
	   }
	}
        close PLUGFILE;
}



sub print_plugin_data{
	foreach my $key (sort keys(%plugins)) {
	 my $name = $key;
	 my $plugin_id = $plugins{$key};
	 my $num_sids = keys %{$plugin_ids{$key}};
         if(defined($options{s})){
       	   if($name =~ /$options{s}/i){
	   print $name . " \n\tID:\t\t" . $plugin_id . "\n\tNum sids:\t". $num_sids . "\n";
	   if(defined($options{v})){
	    print_sid_data($name);
	    }
     print "----------------------------------------------------\n";
	   }
	 } else {
	 print $name . " \n\tID:\t\t" . $plugin_id . "\n\tNum sids:\t". $num_sids . "\n";
 	 if(defined($options{v})){
	  print_sid_data($name);
	 }
     print "----------------------------------------------------\n";
	}
    }
}

sub print_sid_data{
	my $plugin = shift;
        
	foreach $key (keys %{$plugin_ids{$plugin}}){
            print "\t$key\t\t$plugin_ids{$plugin}{$key}\n";
	}


}

sub save_plugin_data{
	open(PLUGFILE,">$save_filename");
	print PLUGFILE "plugin###plugin_id_number###plugin_name\n";
	print PLUGFILE "plugin_sid###plugin_id_number###plugin_sid_number###plugin_sid_name###plugin_sid_priority###plugin_sid_reliability\n";
	foreach my $key (sort keys(%plugins)) {
	 $name = $key;
	 $plugin_id = $plugins{$key};
	 print PLUGFILE "plugin###$plugin_id###$name\n";
	 $num_sids = keys %{$plugin_ids{$key}};
	 foreach $key2 (keys %{$plugin_ids{$key}}){
	 $sid_name = $plugin_ids{$key}{$key2};
	 print PLUGFILE "plugin_sid###$plugin_id###$key2###$sid_name3\n";
	 }
	}
	close PLUGFILE;
}

sub read_data_from_stdin{
    while(<STDIN>){
	if(/Event ID.*bgcolor="#FEFEFE">(\d+)/){
		parse_entry($1)
	}
    }
}

sub write_plugin{
	my $plugin_id = shift;
        my $plugin_name = $plugins_rev{$plugin_id};
	my $plugin_desc_short = shorten_name($plugin_name);

        my $local_plugin_sql = $plugin_sql_header;
        my $local_plugin_cfg = $plugin_cfg;
	my $sid_querys = "";

        my $sid_translations = "";

        $local_plugin_sql =~ s/PLUGIN_ID/$plugin_id/g;
        $local_plugin_sql =~ s/PLUGIN_NAME/$plugin_name/g;
        $local_plugin_sql =~ s/PLUGIN_DESC_SHORT/$plugin_desc_short/g;

        $local_plugin_cfg =~ s/PLUGIN_ID/$plugin_id/g;
        $local_plugin_cfg =~ s/PLUGIN_NAME/$plugin_name/g;
        $local_plugin_cfg =~ s/PLUGIN_DESC_SHORT/$plugin_desc_short/g;

        foreach my $key (keys %{$plugin_ids{$plugin_name}}){
	    my $local_sid_query = $sid_query;
            $local_sid_query =~ s/PLUGIN_ID/$plugin_id/g;
            $local_sid_query =~ s/PLUGIN_SID/$key/g;
            $local_sid_query =~ s/PLUGIN_DESC_SHORT/$plugin_desc_short/g;
            $local_sid_query =~ s/SID_NAME/$plugin_ids{$plugin_name}{$key}/g;
	    $sid_querys .= $local_sid_query . "\n";
            $sid_translations .= "$key=$key\n";
        }

 	chomp($sid_translations);

        $local_plugin_cfg =~ s/TRANSLATE_PLACEHOLDER/$sid_translations/g;

        $local_plugin_sql .= $sid_querys;

        open(SQL_OUT, ">$plugin_dir/$plugin_desc_short.sql");
        open(CFG_OUT, ">$plugin_dir/$plugin_desc_short.cfg");
        open(AGENT_CFG_OUT, ">>$plugin_dir/win_config.cfg");

        print "Writing $plugin_name plugin files...\n";
	print SQL_OUT $local_plugin_sql;
	print CFG_OUT $local_plugin_cfg;
        print AGENT_CFG_OUT "$plugin_desc_short=/etc/ossim/agent/plugins/$plugin_desc_short.cfg\n";

	close SQL_OUT;
	close CFG_OUT;
	close AGENT_CFG_OUT;
}

sub write_plugins{
        print "Start writing plugin files.\n";
        system("rm -f $plugin_dir/win_config.cfg");
        foreach my $key (sort keys(%plugins)) {
	 my $name = $key;
         my $plugin_id = $plugins{$key};
         if(defined($options{s})){
           if($name =~ /$options{s}/i){
	   write_plugin($plugin_id);
           }
         } else {
	   write_plugin($plugin_id);
        }
    }
        print "Done writing plugin files.\n";
}


# Start doing things

load_plugin_data();
%plugins_rev = reverse %plugins;
load_sid_data();

if(defined $options{i}){
read_data_from_stdin();
}

if(defined $options{l} || defined $options{s}){
if(!defined $options{g}){
print_plugin_data();
}
}

if(defined $options{g}){
write_plugins();
}

if(defined $options{w}){
save_plugin_data();
}
