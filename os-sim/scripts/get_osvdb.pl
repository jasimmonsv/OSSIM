#!/usr/bin/perl

# Gets OSVDB <--> Nessus relationship. Gets OSVDB plugin_sids and fills in the right table

use ossim_conf;
use DBI;
use POSIX;

use warnings;

$| = 1;

sub usage{
print "$0 osvdb_export_xml.xml ossim_osvdb_entries.sql\n";
exit(1);
}

if (!$ARGV[1]) {
   usage();
}

# Vars
$osvdb_plugin_id = 5003;
$osvdb_file=$ARGV[0];
$sql_file=$ARGV[1];

my $dsn = "dbi:mysql:".$ossim_conf::ossim_data->{"ossim_base"}.":".$ossim_conf::ossim_data->{"ossim_host"}.":".$ossim_conf::ossim_data->{"ossim_port"};
my $dbh = DBI->connect($dsn, $ossim_conf::ossim_data->{"ossim_user"},$ossim_conf::ossim_data->{"ossim_pass"}) or die "Can't connect to DBI\n";

$query = "DELETE FROM plugin_reference WHERE plugin_id = $osvdb_plugin_id and reference_id = 3001;";
$sth = $dbh->prepare($query);
$sth->execute();
$query = "DELETE FROM plugin_sid WHERE plugin_id = $osvdb_plugin_id;";
$sth = $dbh->prepare($query);
$sth->execute();


open (SQL_FILE, ">$sql_file") or die "Can't open output file $sql_file" ;
print SQL_FILE  "DELETE FROM plugin_reference WHERE plugin_id = $osvdb_plugin_id and reference_id = 3001;\n";
print SQL_FILE  "DELETE FROM plugin_sid WHERE plugin_id = $osvdb_plugin_id;\n";

open(DATA, $osvdb_file) || die("Could not open input xml file: $osvdb_file!");
while(<DATA>){
    if(m/vuln osvdb_id="(\d+)"/){
        my $osvdb_id = $1;
        my $nessus_id = 0;
    	while($temp  = <DATA>){
            if($temp =~ m/Nessus.*indirect=.*"\>(\d+)\<.*/){
               $nessus_id = $1;
               $query = "INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES ($osvdb_plugin_id, $osvdb_id, 3001, $nessus_id);";
               $sth = $dbh->prepare($query);
               $sth->execute();
	       print SQL_FILE "$query\n";
            }
            if($temp =~ m/\<\/ext_refs\>/){
               last;
            }
        }
    }
}
close(DATA);

print SQL_FILE  "----------------------------------------------------\n";

open(DATA, $osvdb_file) || die("Could not open file!");
while(<DATA>){
    if(m/vuln osvdb_id="(\d+)"/){
        $osvdb_id = $1;
        $osvdb_name = "";
        while($temp  = <DATA>){
                if($temp =~ m/<osvdb_title>(.*)<\/osvdb_title>/){
                        $osvdb_name = $1;
                        $query = "INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES ($osvdb_plugin_id, $osvdb_id, NULL, NULL, \"$osvdb_name\", 1, 1);";
                        $sth = $dbh->prepare($query);
                        $sth->execute();
	       		print SQL_FILE "$query\n";
                }
                if($temp =~ m/\<\/ext_refs\>/){
                        last;
                }
        }
    }
}
close(DATA);

close(SQL_FILE);
