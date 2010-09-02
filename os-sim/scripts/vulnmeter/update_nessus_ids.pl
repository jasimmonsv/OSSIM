#!/usr/bin/perl
#
###############################################################################
#
#    License:
#
#   Copyright (c) 2003-2006 ossim.net
#   Copyright (c) 2007-2009 AlienVault
#   All rights reserved.
#
#   This package is free software; you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation; version 2 dated June, 1991.
#   You may not use, modify or distribute this program under any other version
#   of the GNU General Public License.
#
#   This package is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this package; if not, write to the Free Software
#   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#   MA  02110-1301  USA
#
#
# On Debian GNU/Linux systems, the complete text of the GNU General
# Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
# Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt

use ossim_conf;
use DBI;
use POSIX;

use strict;
use warnings;

$| = 1;


my $nessus = $ossim_conf::ossim_data->{"nessus_path"};
my $nessus_user = $ossim_conf::ossim_data->{"nessus_user"};
my $nessus_pass = $ossim_conf::ossim_data->{"nessus_pass"};
my $nessus_host = $ossim_conf::ossim_data->{"nessus_host"};
my $nessus_port = $ossim_conf::ossim_data->{"nessus_port"};


open (PLUGINS, "$nessus -qxp $nessus_host $nessus_port $nessus_user $nessus_pass|");

my @plugin_rel_db = ();
my %plugin_rel_hash = ();
my %plugin_prio_hash = ();
my $index;
my $key;
my $id;

while(<PLUGINS>){
if(/^([^\|]*)\|[^\|]*\|([^\|]*)\|.*\\n(.*)$/){

$id = $1;
my $temp_risk = $3;
my $risk_level = 2;
my $rel = $2;
if ($id =~ /\./){
    my @tmp = split(/\./, $id);
    $id = $tmp[$#tmp];
}
$plugin_rel_hash{$id} = $rel;
my $temp_plugin_id = $id;

    if ($temp_risk =~ /Risk factor : (.*)/) {
    my $risk=$1; 
    $risk =~ s/ \(.*|if.*//g; 
    $risk =~ s/ //g;        
    if ($risk eq "Verylow/none") { $risk_level = 1 }
    if ($risk eq "Low") { $risk_level = 1 }
    if ($risk eq "Low/Medium") { $risk_level = 2 }
    if ($risk eq "Medium/Low") { $risk_level = 2 }
    if ($risk eq "Medium") { $risk_level = 3 }
    if ($risk eq "Medium/High") { $risk_level = 3 }
    if ($risk eq "High/Medium") { $risk_level = 4 }
    if ($risk eq "High") { $risk_level = 4 }
    if ($risk eq "Veryhigh") { $risk_level = 5 }
    }

$plugin_prio_hash{$temp_plugin_id} = $risk_level; 
}
}

close(PLUGINS);
print "plugins fetched\n";

my $dsn = "dbi:mysql:".$ossim_conf::ossim_data->{"ossim_base"}.":".$ossim_conf::ossim_data->{"ossim_host"}.":".$ossim_conf::ossim_data->{"ossim_port"};
my $dbh = DBI->connect($dsn, $ossim_conf::ossim_data->{"ossim_user"},
$ossim_conf::ossim_data->{"ossim_pass"})
    or die "Can't connect to DBI\n";

my $query = "SELECT * from plugin_sid where plugin_id = 3001;";

my $sth = $dbh->prepare($query); 
$sth->execute();

my $row;

while($row = $sth->fetchrow_hashref){
if(exists($plugin_rel_hash{$row->{sid}})){
delete $plugin_rel_hash{$row->{sid}};
delete $plugin_prio_hash{$row->{sid}};
}
}

$query = "INSERT INTO plugin_sid(plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES ";

if(keys %plugin_rel_hash){
print "Updating...\n";
foreach $key (keys %plugin_rel_hash){
print "Script id:$key, Name:$plugin_rel_hash{$key}, Reliability:$plugin_prio_hash{$key}\n";
#$plugin_rel_hash{$key} =~ s/'/''/; 
$plugin_rel_hash{$key} =~ s/'/\\'/gs;
$plugin_rel_hash{$key} =~ s/"/\\"/gs;

my $sid = $key;
if ($key =~ /\./){
    my @tmp = split(/\./, $key);
    $sid = $tmp[$#tmp];
}

$query .= "(3001, $sid, NULL, NULL, $plugin_prio_hash{$key}, 7, 'nessus: $plugin_rel_hash{$key}'),";
}

chop($query);
$query .= ";";

$sth = $dbh->prepare($query);
$sth->execute();
} else {
print "\nDB is up to date\n";
}
