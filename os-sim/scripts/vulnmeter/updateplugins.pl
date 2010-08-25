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
#
###############################################################################
#                          Last update: 07.5.2008                             #
#-----------------------------------------------------------------------------#
#                                 Inprotect                                   #
#-----------------------------------------------------------------------------#
# Copyright (C) 2008 Inprotect.net                                            #
#                                                                             #
# This program is free software; you can redistribute it and/or modify it     #
# under the terms of version 2 of the GNU General Public License as published #
# by the Free Software Foundation.                                            #
#                                                                             #
# This program is distributed in the hope that it will be useful, but WITHOUT #
# ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or       #
# FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for    #
# more details.                                                               #
#                                                                             #
# You should have received a copy of the GNU General Public License along     #
# with this program; if not, write to the Free Software Foundation, Inc.,     #
# 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA                       #
#                                                                             #
# Contact Information:                                                        #
# inprotect-devel@lists.sourceforge.net                                       #
# http://www.inprotect.net                                                    #
###############################################################################
# See the README.txt and/or help files for more information on how to use &   #
# configuration.                                                              #
# See the LICENSE.txt file for more information on the License this software  #
# is distributed under.                                                       #
#                                                                             #
# This program is intended for use in an authorized manner only, and the      #
# author can not be held liable for anything done with this program, code, or #
# items discovered with this program's use.                                   #
###############################################################################
##
##    PRGM AUTHOR: Various
##   PROGRAM NAME: updatePlugin
##   PROGRAM DATE: 02/23/2009
##  PROGM VERSION: 1.1
##  REVISION HIST:
##        04/23/2008 -  FIRST VERSION OF RECODE TO ADD DEBUG AND WORK OUT TYPICAL
##                      FLAWS THAT ARE AFFECTING PROFILES CONFIGS
##      06/29/2008 -    Bug fix now was created/modified/deleted being populated by $now as was not defined
##      01/01/2009 -    Significant Recode to vastly improve Import / Update time.  ( Removed all the unnecessary
##                      queries that contstantly were touching the DB to use a hash to track/compare the numerous tables.
##      02/23/2009 -    More bug fixes ( hopefully this should be the last of the issues going back as far as two years.
##                      per nessus_settings_plugins records being created for each profile.
##      04/07/2009 -    Major fix to handle backslashes, single-quotes, etc in name, summary, description fields
$| = 1;
use ossim_conf;
use DBI;
use Getopt::Std;

#use vars qw/%CONFIG/;

#&load_configs("/etc/inprotect.cfg");

my %CONFIG = ();

my %profiles = ();
$profiles{'PortScan|PortScan|F|admin|4|4'} = "Port scanners";
$profiles{'Mac|MACOSX Test|F|admin|4|4'} = "MacOS X Local Security Checks";
$profiles{'Firewalls|Firewalls Tests|F|admin|4|4'} = "Firewalls";
$profiles{'Linux|Linux Test|F|admin|1|1'} = "Databases|Debian Local Security Checks|Default Unix Accounts|Finger abuses|FTP|Gain a shell remotely|Gain root remotely|General|Gentoo Local Security Checks|Port scanners|Red Hat Local Security Checks|Remote file access|RPC|Service detection|SLAD|SMTP problems|SNMP|Useless services|Web Servers";
$profiles{'CISCO|Cisco Test|F|admin|4|1'} = "CISCO";
$profiles{'UNIX|UNIX Test|F|admin|4|4'} = "AIX Local Security Checks|Default Unix Accounts|Finger abuses|FTP|Gain a shell remotely|Gain root remotely|MacOS X Local Security Checks|RPC|Service detection|SMTP problems|Useless services|Web Servers";
$profiles{'Perimeter|External Perimeter Scan|F|admin|1|1'} = "Backdoors|CGI abuses|CGI abuses : XSS|CISCO|Databases|Finger abuses|Firewalls|FTP|Gain a shell remotely|Gain root remotely|General|Netware|NIS|Port scanners|Remote file access|RPC|Service detection|SMTP problems|SNMP|Useless services|Web Servers|Windows|Windows : Microsoft Bulletins|Windows : User management";
$profiles{'Mail||F|admin|1|1'} = "SMTP problems";
$profiles{'Windows||F|admin|1|1'} = "Windows|Windows : Microsoft Bulletins|Windows : User management";
$profiles{'Database||F|admin|1|1'} = "Databases";
$profiles{'Info||C|admin|1|1'} = "infos|settings";
$profiles{'DOS|Denial of Service|C|admin|1|1'} = "denial|destructive_attack|flood|kill_host";
$profiles{'Web Scan||F|admin|1|1'} = "CGI abuses|CGI abuses : XSS|Web Servers";
$profiles{'Stealth||C|admin|1|1'} = "infos|scanner|settings";
$profiles{'Default|Non Destructive Global Scan|C|admin|2|2'} = "attack|end|infos|init|mixed|scanner|settings";


my $dbhost = `grep db_ip /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`; chomp($dbhost);
$dbhost = "localhost" if ($dbhost eq "");
my $dbuser = `grep user /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`; chomp($dbuser);
my $dbpass = `grep pass /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`; chomp($dbpass);

$CONFIG{'DATABASENAME'} = "ossim";
$CONFIG{'DATABASEHOST'} = $dbhost;
$CONFIG{'UPDATEPLUGINS'} = ($ARGV[0] eq "update") ? 1 : 0;
$CONFIG{'MIGRATEDB'} = ($ARGV[0] eq "migrate") ? 1 : 0;
$CONFIG{'DATABASEDSN'} = "DBI:mysql";
$CONFIG{'DATABASEUSER'} = $dbuser;
$CONFIG{'DATABASEPASSWORD'} = $dbpass;

my ( $dbh, $sth_sel, $sql );   #DATABASE HANDLE TO BE USED THROUGHOUT PROGRAM
my %nessus_vars = ();
$dbh = conn_db();
$sql = qq{ select * from config where conf like 'nessus%' };
$sth_sel=$dbh->prepare( $sql );
$sth_sel->execute;
while ( my ($conf, $value) = $sth_sel->fetchrow_array ) {
   $nessus_vars{$conf} = $value;
}

# Quick and dirty test to see if this should run
$tmp_sql = qq{ select count(*) from vuln_jobs};
eval {
$dbh->do( $tmp_sql );
};

#print "Tables not created yet, please upgrade from the web interface and run again.\n"; 
#exit(0);




#$CONFIG{'SERVERID'} = 2;
$CONFIG{'CHECKINTERVAL'} = 300;

if (-e $nessus_vars{'nessus_updater_path'}) {
    $CONFIG{'NESSUSUPDATEPLUGINSPATH'} = $nessus_vars{'nessus_updater_path'};
}
else {
    $CONFIG{'NESSUSUPDATEPLUGINSPATH'} = ($nessus_vars{'nessus_path'} =~ /nessus/) ? "/usr/sbin/nessus-update-plugins" : "/usr/sbin/openvas-nvt-sync";
}
$CONFIG{'NESSUSPATH'} = $nessus_vars{'nessus_path'};
$CONFIG{'NESSUSHOST'} = $nessus_vars{'nessus_host'};
$CONFIG{'NESSUSUSER'} = $nessus_vars{'nessus_user'};
$CONFIG{'NESSUSPASSWORD'} = $nessus_vars{'nessus_pass'};
$CONFIG{'NESSUSPORT'} = $nessus_vars{'nessus_port'};
$CONFIG{'MYSQLPATH'} = "/usr/bin/mysql";

$CONFIG{'ROOTDIR'} = $nessus_vars{'nessus_rpt_path'};

$mysqlpath = "$CONFIG{'MYSQLPATH'}";                         #PATH TO MYSQL EXECUTABLE
$tempfile = $CONFIG{'ROOTDIR'}."tmp/plugins.sql";        #Temp Nessus plugins file

my $updateplugins="$CONFIG{'UPDATEPLUGINS'}";

my %loginfo;         # plot information hash
   $loginfo{'1'} = "FATAL";
   $loginfo{'2'} = "ERROR";
   $loginfo{'3'} = "WARN";
   $loginfo{'4'} = "";
   $loginfo{'5'} = "DEBUG";

my $debug            = 0;
my $log_level        = 4;

#my ( $serverid );

my ( $dsn);        #DATABASE HANDLE TO BE USED THROUGHOUT PROGRAM

my ( $nessus, $nessus_user, $nessus_pass, $nessus_host, $nessus_port);

getopts("dh?",\%options);

main( );
disconn_db($dbh);

exit;

sub main {

    my ( $sth_sel, $sql );

    if( $options{d} ) {                #ENABLE DEBUGGING
        print "Debugging mode\n";
        $debug = 1;
    }

    $nessus = $CONFIG{'NESSUSPATH'};
    $nessus_user = $CONFIG{'NESSUSUSER'};
    $nessus_pass = $CONFIG{'NESSUSPASSWORD'};
    $nessus_host = $CONFIG{'NESSUSHOST'};
    $nessus_port = $CONFIG{'NESSUSPORT'};
    
    #load_db_configs ( );

    #$serverid = get_server_credentialsA( $CONFIG{'SERVERID'} );  #GET THE SERVER ID'S FOR WORK PROCESSING
    #if ($serverid == 0 ) {  #CHECK FOR VALID SERVER ID)
    #    logwriter( "[$$]\tWARNING: ServerID is Invalid --CAN NOT CONTINUE", 1 );
    #    exit;
    #}
    logwriter( "host=$nessushost, port=$nessusport, user=$nessususer, pass=$nessuspassword", 5 );

    #PROCEED WITH FORCE NESSUS TO UPDATE PLUGINS
    if ($updateplugins==1) {
        logwriter( "updateplugins: executing nessus-update-plugins", 4 );
        perform_update( );
    } else {
        logwriter( "updateplugins: configured to not updateplugins", 4 );
    }
    
    if($CONFIG{'MIGRATEDB'}==1) {
        logwriter( "updateplugins: configured to migrate DB", 4 );
        
        $sql = qq{TRUNCATE vuln_nessus_category};
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
            
        $sql = qq{TRUNCATE vuln_nessus_family}; 
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
           
        $sql = qq{TRUNCATE vuln_nessus_plugins};
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
            
        $sql = qq{TRUNCATE vuln_nessus_preferences};
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
            
        $sql = qq{TRUNCATE vuln_nessus_preferences_defaults};
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
        
        $sql = qq{TRUNCATE vuln_nessus_settings};
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
           
        $sql = qq{TRUNCATE vuln_nessus_settings_category};
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
            
        $sql = qq{TRUNCATE vuln_nessus_settings_family};
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
            
        $sql = qq{TRUNCATE vuln_nessus_settings_plugins};
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
            
        $sql = qq{TRUNCATE vuln_nessus_settings_preferences};
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
    }
    else {
        logwriter( "updateplugins: configured to not migrate DB", 4 );
    }

    disconn_db($dbh);
    $dbh = conn_db();
    dump_plugins();
    
    disconn_db($dbh);
    $dbh = conn_db();
    import_plugins( );

    disconn_db($dbh);
    $dbh = conn_db();
    update_categories( );
    
    disconn_db($dbh);
    $dbh = conn_db();
    update_families( );
    
    disconn_db($dbh);
    $dbh = conn_db();
    update_nessus_plugins();
    
    disconn_db($dbh);
    $dbh = conn_db();
    update_settings_plugins();
    
    disconn_db($dbh);
    $dbh = conn_db();
    update_preferences();
    
    disconn_db($dbh);
    $dbh = conn_db();
    generate_profiles(\%profiles);

    disconn_db($dbh);
    $dbh = conn_db();
    $sql = qq{ DROP TABLE `vuln_plugins`; };
    safe_db_write( $sql, 5 );
    
    disconn_db($dbh);
    #
    print "Updating plugin_sid vulnerabilities scanner ids\n";
    system("perl /usr/share/ossim/scripts/vulnmeter/update_nessus_ids.pl");
    #end of main
    exit;

}

sub perform_update {

    logwriter( "BEGIN - PERFORM UPDATE", 4 );
    my $time_start = time();

    if ( -e $CONFIG{'NESSUSUPDATEPLUGINSPATH'} ) { 
       logwriter( "$CONFIG{'NESSUSUPDATEPLUGINSPATH'} >> /tmp/update_scanner_plugins_rsync.log", 4 ); 
       system ("sudo $CONFIG{'NESSUSUPDATEPLUGINSPATH'} >> /tmp/update_scanner_plugins_rsync.log 2>&1") == 0 or logwriter( "updateplugins: No new plugins installed", 3 ); 

       #If used patch for nessus-update-plugins, we may pass parameter on the command line.
       #system ("nessus-update-plugins.141 -u $inprotect_url/nessus") == 0 or die localtime(time)." updateplugins: No new plugins installed";

       logwriter( "updateplugins: sleeping for 120sec to allow nessus to restart", 4 );

       #Sleep 300 sec, allow nessusd to restart
       sleep 120;
   } else {
      logwriter( "INVALID PATH/FILE nessus-update-plugins named \"$CONFIG{'NESSUSUPDATEPLUGINSPATH'}\"", 1);
      die( "Invalid Path to nessus-update-plugins");
   }

   my $time_run = time() - $time_start;
   logwriter( "FINISH - PERFORM UPDATE [ Process took $time_run seconds ]", 4 );
}

sub dump_plugins {

    logwriter( "BEGIN - DUMP PLUGINS", 4 );
    my $time_start = time();

    #Delete existing temporary file
    unlink $tempfile if -e $tempfile;

    #Dump Nessus plugins info into a file
    #my $cmd = "$CONFIG{'NESSUSPATH'} -xpS -q $nessus_host $nessus_port $nessus_user $nessus_pass | perl -npe 's/ plugins(;|\\s)/ vuln_plugins\$1/g' | perl -npe 's/DROP TABLE/DROP TABLE IF EXISTS/g'| perl -npe 's/INSERT/INSERT IGNORE/g' | perl -npe 's/\\\\(\\\\+)/\\\\/g' > $tempfile";
    my $cmd = "$CONFIG{'NESSUSPATH'} -xpS -q $nessus_host $nessus_port $nessus_user $nessus_pass | perl /usr/share/ossim/scripts/vulnmeter/nessus_filter.pl > $tempfile";
    #my $cmd = "$CONFIG{'NESSUSPATH'} -xpS -q $nessus_host $nessus_port $nessus_user $nessus_pass > $tempfile";
    #print "$cmd\n";
    logwriter( "$cmd", 5 );
    my $imp = system ( $cmd );

    if ( $imp != 0 ) { die "". logwriter( "updateplugins: Failed Dump Plugins", 2 ); }

    my $time_run = time() - $time_start;
    logwriter( "FINISH - DUMP PLUGINS [ Process took $time_run seconds ]", 4 );
    return 1;
}

sub import_plugins {

    logwriter( "BEGIN - IMPORT PLUGINS", 4 );
    my $time_start = time();

#    $sql = qq{ TRUNCATE TABLE `vuln_plugins`; };
#    safe_db_write( $sql, 5 );


    #Dump Nessus plugins info into a file
    my $cmd = "$mysqlpath --force --user=$CONFIG{'DATABASEUSER'} --password=$CONFIG{'DATABASEPASSWORD'} --host=$CONFIG{'DATABASEHOST'} $CONFIG{'DATABASENAME'} < $tempfile";

    logwriter( "$cmd", 5 );
    my $imp = system ( $cmd );

    if ( $imp != 0 ) { die "". logwriter( "updateplugins: Failed Import Plugins", 2 ); }
    
    $sql = qq{ UPDATE vuln_plugins SET family='Others' WHERE family=''};
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;
    
    $sql = qq{ UPDATE vuln_plugins SET category='Others' WHERE category=''};
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;

    #Delete existing temporary file
    unlink $tempfile if -e $tempfile;

    my $time_run = time() - $time_start;
    logwriter( "FINISH - IMPORT PLUGINS [ Process took $time_run seconds ]", 4 );
    return 1;

}

sub update_categories {
    my ( $sth_sel, $sth_selc, $sth_ins, $sth_insc, $sql );

    logwriter( "BEGIN - UPDATE CATEGORIES", 4 );
    my $time_start = time();

    #Updating family and category tables
    $sql = qq{ select distinct vuln_plugins.category from vuln_plugins left join vuln_nessus_category on 
        vuln_plugins.category = vuln_nessus_category.name where vuln_nessus_category.id is null order by category };
    logwriter( "$sql", 5 );
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;

    while (my ($categoryname) = $sth_sel->fetchrow_array ) {
        if ($categoryname ne "") {
            $sql = qq{ insert into vuln_nessus_category (name) values('$categoryname') };
            safe_db_write( $sql, 5 );

            $sql = qq{ select id from vuln_nessus_category where name='$categoryname' };
            logwriter( "$sql", 5 );
            $sth_selc=$dbh->prepare( $sql );
            $sth_selc->execute;
            ($catid)=$sth_selc->fetchrow_array;

            $sql = qq{ select id, auto_cat_status from vuln_nessus_settings };
            logwriter( "$sql", 5 );
            $sth_selc=$dbh->prepare( $sql );
            $sth_selc->execute;
            while (($setid,$status) =$sth_selc->fetchrow_array ) {
                $sql = qq{ insert into vuln_nessus_settings_category (sid, cid, status) values ($setid, $catid, $status) on duplicate key update status=$status };
                safe_db_write( $sql, 5 );
            }
            $sth_selc->finish();
        }
    }

    my $time_run = time() - $time_start;
    logwriter( "FINISH - UPDATE CATEGORIES [ Process took $time_run seconds ]", 4 );

}

sub update_families {
    my ( $sth_sel, $sth_self, $sth_ins, $sth_insf, $sql );

    logwriter( "BEGIN - UPDATE FAMILIES", 4 );
    my $time_start = time();

    #Updating family and category tables
    $sql = qq{ select distinct vuln_plugins.family from vuln_plugins left join vuln_nessus_family on 
            vuln_plugins.family = vuln_nessus_family.name where vuln_nessus_family.id is null order by family };
    logwriter( "$sql", 5 );
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;

    while (my ($familyname) = $sth_sel->fetchrow_array ) {
        if ($familyname ne "") {
            $sql = qq{ insert into vuln_nessus_family (name) values('$familyname') };
            safe_db_write( $sql, 5 );

            $sql = qq{ select id from vuln_nessus_family where name='$familyname' };
            logwriter( "$sql", 5 );
            $sth_self=$dbh->prepare( $sql );
            $sth_self->execute;

            ($famid)=$sth_self->fetchrow_array;

            $sql = qq{ select id,auto_fam_status from vuln_nessus_settings };
            logwriter( "$sql", 5 );
            $sth_self=$dbh->prepare( $sql );
            $sth_self->execute;
            while (($setid,$status) =$sth_self->fetchrow_array ) {
                $sql = qq{ insert into vuln_nessus_settings_family (sid, fid, status) values ($setid, $famid, $status) on duplicate key update status=$status };
                safe_db_write( $sql, 5 );
            }
            $sth_self->finish();
        }
    }

    my $time_run = time() - $time_start;
    logwriter( "FINISH - UPDATE FAMILIES [ Process took $time_run seconds ]", 4 );

}

sub update_nessus_plugins {

    logwriter( "BEGIN - UPDATE NESSUS_PLUGINS", 4 );
    my $time_start = time();

    my ( $sth_sel, $sth_sel2, $sth_sel3, $sth_ins, $sql );
    my $now = genScanTime();

    #USE TO MAKE SURE PLUGINS TABLE IS NOT EMPTY (OTHERWISE LATER CODE WOULD FLAG ALL NESSUS_PLUGINS DELETED )
    my $plugin_count = 0;

    #ANOTHER REWRITE TO CLEANUP UNNECESSARY DB HEAVY LIFTING
    #FIST LESTS PROCESS ALL RECORDS PER THE PLUGINS TABLE TO SEE
    #	1.  ALL PLUGINS THAT NEED ADDED
    #	2.  ALL PLUGINS THAT EXIST TO BE UPDATED

    #THEN NEED A FOLLOWUP RUN AGAINST ALL PLUGINS THAT NEED FLAGGED DELETED ( IF ANY )

    $sql = qq{ SELECT t1.id, t1.oid, t1.name, t3.id, t4.id, t1.copyright, t1.summary, t1.description,
	t1.version, t2.id, t2.version, t2.custom_risk, t1.cve_id, t1.bugtraq_id, t1.xref
            FROM vuln_plugins t1
	    LEFT JOIN vuln_nessus_plugins t2 on t1.id=t2.id
            LEFT JOIN vuln_nessus_family t3 ON t1.family = t3.name
	    LEFT JOIN vuln_nessus_category t4 ON t1.category = t4.name
    };

    logwriter( "$sql", 5 );
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;
    while ( my ( $pid, $oid, $pname, $pfamily, $pcategory, $pcopyright, $psummary, $pdescription, $pversion,
	$pluginid, $pluginversion, $plugin_crisk, $pcve_id, $p_bug, $p_xref )= $sth_sel->fetchrow_array ) {  
    
    if ($pname ne "" && $pfamily ne "" && $pcategory ne "") {
        
        #$pcve_id =~ s/(\d+\-\d+)/CVE-$1/g  if ( ($pcve_id !~ /^CVE/) && ($pcve_id !~ /^CAN/) ); 
        my @pcve_ids = split(/,/, $pcve_id);
        my @pcve_tmp=();
        foreach (@pcve_ids){
            s/^ *| *$//g;
            s/(\d+\-\d+)/CVE-$1/ if ($_ !~ /^CVE/);
            push @pcve_tmp,$_;
        }
        $pcve_id = join(", ", @pcve_tmp);
        
        $pcve_id = ""  if ( $pcve_id !~ /-/);
        $pcve_id = ""  if ($pcve_id =~ /NOCVE/); 
        $p_bug = ""  if ($p_bug =~ /NOBID/); 
        #print "pid: $pid\n";
        #print "name: $pname\n";
        
    	$pname =~ s/'/\\'/g;
    	$psummary =~ s/'/\\'/g;
            $pdescription =~ s/\\/\\\\/g;
    	$pdescription =~ s/'/\\'/g;

    	if ( !defined( $plugin_crisk ) || $plugin_crisk eq "" ) { $plugin_crisk = "NULL"; }

            my $risk=7;
            $risk=1 if ($pdescription =~ m/Risk [fF]actor\s*:+;*\s*(\\n)*Serious/s);
            $risk=1 if ($pdescription =~ m/Risk [fF]actor\s*:+;*\s*(\\n)*Critical/s);
            $risk=2 if ($pdescription =~ m/Risk [fF]actor\s*:+;*\s*(\\n)*High/s);
            $risk=3 if ($pdescription =~ m/Risk [fF]actor\s*:+;*\s*(\\n)*Medium/s);
            $risk=4 if ($pdescription =~ m/Risk [fF]actor\s*:+;*\s*(\\n)*Medium\/Low/s);
            $risk=5 if ($pdescription =~ m/Risk [fF]actor\s*:+;*\s*(\\n)*Low\/Medium/s);
            $risk=6 if ($pdescription =~ m/Risk [fF]actor\s*:+;*\s*(\\n)*Low/s);
            $risk=7 if ($pdescription =~ m/Risk [fF]actor\s*:+;*\s*(\\n)*Info/s);
            $risk=7 if ($pdescription =~ m/Risk [fF]actor\s*:+;*\s*(\\n)*[nN]one/s);

    	if ( !defined( $pluginid ) || $pluginid eq "" ) {
    	    $plugins{$pid}{'do'} = "insert";
    	    $sql = qq{ INSERT INTO vuln_nessus_plugins ( id, oid, name, copyright, summary, description, cve_id, bugtraq_id, 
    		xref, enabled, version, created, modified, deleted, category, family, risk, custom_risk ) VALUES
    		( '$pid', '$oid', '$pname', '$pcopyright', '$psummary', '$pdescription', '$pcve_id', '$p_bug', '$p_xref',
                      'Y','$pversion', '$now', null, null, '$pcategory', '$pfamily', '$risk', NULL ); };
                safe_db_write( $sql, 4 );
            #print "[$sql]\n"; 

    	} else {
    	    $plugins{$pid}{'do'} = "update";
                if ($pluginversion ne $pversion) {
                    $sql = qq{ UPDATE vuln_nessus_plugins SET enabled='Y', version='$pversion', risk='$risk', modified='$now', 
                        description='$pdescription', cve_id='$pcve_id', bugtraq_id='$p_bug'
    		    WHERE id='$pluginid' };
                    safe_db_write( $sql, 5 );

                }
    	}
    	$plugin_count +=1;
        }
    }

    #UPDATE RISK WITH CUSTOM VALUE AS NEEDED
    $sql = qq{ UPDATE vuln_nessus_plugins SET risk=custom_risk WHERE custom_risk IS NOT NULL AND custom_risk > 0 }; 
    safe_db_write( $sql, 3 );

    #UPDATE DELETED PLUGINS
    if ( $plugin_count > 25000 ) {	    #MAKE SURE SOMETHING REALLY NEEDS DELETED
        $sql = qq{ SELECT t1.id FROM vuln_nessus_plugins t1
	    LEFT JOIN vuln_plugins t2 on t1.id=t2.id
	    WHERE t1.enabled='Y' AND t2.id IS NULL 
        };

	logwriter( "$sql", 5 );
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
	while ( my ( $pluginid )= $sth_sel->fetchrow_array ) { 
    
	    $sql = qq{ UPDATE vuln_nessus_plugins SET enabled='N', deleted='$now' WHERE id='$pluginid' };
            safe_db_write( $sql, 5 );

	    $sql = qq{ DELETE FROM vuln_nessus_settings_plugins WHERE id='$pluginid' };
            safe_db_write( $sql, 5 );

        }
    }

    my $time_run = time() - $time_start;
    print "\n";
    logwriter( "FINISH - UPDATE NESSUS_PLUGINS [ Process took $time_run seconds ]", 4 );

}

sub update_settings_plugins {

    logwriter( "BEGIN - UPDATE SETTINGS_PLUGINS", 4 );
    my $time_start = time();

    my ( $sth_sel, $sth_sel2, $sth_sel3, $sth_ins, $sql );
    my $now = genScanTime();

    my %autoenable;
    my %autofam;
    my %autocat;
    my %settings;
    my $profile_count = 0;

    #CREATE ARRAY OF AUTOENABLE PER PROFILES
    $sql = qq{ SELECT id, autoenable FROM vuln_nessus_settings };
    logwriter( "$sql", 5 );
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;
    while ( my ($sid, $value)=$sth_sel->fetchrow_array) { 
        $autoenable->{$sid} = $value;
        #print "sid=$sid\tvalue=$value\tautocat=" . $autoenable->{$sid} ."\n";
        $profile_count = $profile_count + 1;
    }

    #CREATE AUTOENABLE CATEGORY ARRAY
    $sql = qq{ select sid, cid, status from vuln_nessus_settings_category };
    logwriter( "$sql", 5 );
    $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;
    while ( my ($sid, $cid, $status) = $sth_sel->fetchrow_array ) {
       $autocat{$sid}->{$cid} = $status;
       #print "sid=$sid\tcid=$cid\tstatus=$status\tautocat=" . $autocat{$sid}{$cid} ."\n";
    }

    #CREATE AUTOENABLE FAMILY ARRAY
    $sql = qq{ select sid, fid, status from vuln_nessus_settings_family };
    logwriter( "$sql", 5 );
    $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;
    while ( my ($sid, $fid, $status) = $sth_sel->fetchrow_array ) {
       $autofam{$sid}->{$fid} = $status;
       #print "sid=$sid\tfid=$fid\tstatus=$status\tautofam=" . $autofam{$sid}{$fid} ."\n";
    }

    #POPULATE A SETTING HASH ARRAY TO OFFLOAD HEAVY LIFTING FROM THE DB.
    $sql = qq{ SELECT id, sid, enabled, category, family FROM vuln_nessus_settings_plugins };
    logwriter( "$sql", 5 );
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;

    while ( my ($pid, $sid, $enabled, $pcategory, $pfamily ) = $sth_sel->fetchrow_array ) {
	$settings{$pid}->{$sid}->{'enabled'} = $enabled;
	$settings{$pid}->{$sid}->{'category'} = $pcategory;
	$settings{$pid}->{$sid}->{'family'} = $pfamily;
        $settings{$pid}->{$sid}->{'count'} += 1;
    }

    $sql = qq{ SELECT id, category, family FROM vuln_nessus_plugins WHERE enabled='Y' };
    logwriter( "$sql", 5 );
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;

    while ( my ($pid, $pcategory, $pfamily ) = $sth_sel->fetchrow_array ) {
	foreach my $sid (sort(keys(%{$autoenable}))) {
            my $task = "";
            my $cfStatus = "-1";
	    my $statusvalue = "";
            $sql2 = "";
	    #THrew this in there to handle issue where it may have been populated ""
            $statusvalue = $settings{$pid}{$sid}{'enabled'};

	    if ( $autoenable->{$sid} eq "C" ) {
                $cfStatus = $autocat{$sid}{$pcategory};
	    } elsif ( $autoenable->{$sid} eq "F" ) {
	        $cfStatus = $autofam{$sid}{$pfamily};
            }

            if ( $cfStatus eq "1" ) { $statusvalue = "Y"; } #SET THEM ENABLED ( ALL ONLY )
            if ( $cfStatus eq "3" ) { $statusvalue = "N"; } #SET THEM ENABLED ( ALL ONLY )

            if ( !defined( $settings{$pid}{$sid}{'enabled'} ) && $settings{$pid}{$sid}{'enabled'} eq "" ) {
                if ( $cfStatus eq "2" ) { $statusvalue = "Y"; } #SET THEM ENABLED ( NEW )
                if ( $cfStatus eq "4" ) { $statusvalue = "N"; } #SET THEM DISABLED ( NEW )
                if ( $cfStatus eq "-1" ) { $statusvalue = "N"; } #SET THEM DISABLED ( NO AUTOENABLE FOR NEW )
                $task="create";
            }

            if ( $task eq "" && ( $cfStatus eq "1" || $cfStatus eq "3" ) && $settings{$pid}{$sid}{'enabled'} ne $statusvalue ) {
                $task="update";
            }

            if ( $settings{$pid}{$sid}{'count'} > 1 ) {
                my $scount = $settings{$pid}{$sid}{'count'};
                my $limit = $scount - 1;
                print "something is wrong: check sid=$sid\tcount=$scount\n";
                print "removing duplicates:\n";
                $sql2 = qq{ DELETE FROM vuln_nessus_settings_plugins WHERE id='$pid' AND sid='$sid' };
                safe_db_write( $sql2, 3 );
            }

            if ( $task eq "create" ) {
                $sql2 = qq{ INSERT INTO vuln_nessus_settings_plugins (id, sid, enabled, category, family ) VALUES 
                    ('$pid', '$sid', '$statusvalue', '$pcategory', '$pfamily' ); };
                safe_db_write( $sql2, 4 );
            } elsif ( $task eq "update" ) {
                $sql2 = qq{ UPDATE vuln_nessus_settings_plugins SET enabled='$statusvalue' 
                        WHERE id='$pid' AND sid='$sid' };
                safe_db_write( $sql2, 4 );
            #} else {
            #    logwriter( "no update for record pid=$pid\tsid=$sid\n", 2);
            }
            #print "sid=$sid\tpid=$pid\tcfstatus=$cfStatus\tvalue=$statusvalue\ttask=$task\n";
            #print "sql2=$sql2\n";
	}
    }

    my $time_run = time() - $time_start;
    logwriter( "FINISH - UPDATE SETTINGS_PLUGINS [ Process took $time_run seconds ]", 4 );
}

sub update_preferences {

    logwriter( "BEGIN - UPDATE NESSUS_PREFERENCES", 4 );
    my $time_start = time();

    my ( $sql, $sth_sel, $sth_upd );

    my $now = genScanTime();

    # Create a table the first time we run this program if needed
    $sql = qq{show tables like "vuln_nessus_preferences_defaults"};
    logwriter( $sql, 4 );
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;

    $foo=$sth_sel->fetchrow_array;
    if (!$foo) {
        $sql = qq{ 
CREATE TABLE `vuln_nessus_preferences_defaults` (
  `nessus_id` varchar(255) NOT NULL default '',
  `nessusgroup` varchar(255) default NULL,
  `type` varchar(255) default NULL,
  `field` varchar(255) default NULL,
  `value` varchar(255) default NULL,
  `category` varchar(255) default NULL,
  `flag` char(1) default NULL,
  PRIMARY KEY  (`nessus_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

};
        safe_db_write( $sql, 3 );
    }

    $sql = qq{ update vuln_nessus_preferences_defaults set flag=null };
    safe_db_write( $sql, 5 );

    my ($cmd);
    my ($f0, $f1, $f2, $f3, $f4, $rhs, $rhs2, $sql);

    logwriter( "updateprefs: Getting plugin preferences from nessusd", 4 );

    $cmd = qq{$CONFIG{'NESSUSPATH'} -qxP $CONFIG{'NESSUSHOST'} $CONFIG{'NESSUSPORT'} $CONFIG{'NESSUSUSER'} $CONFIG{'NESSUSPASSWORD'}};
    logwriter( $cmd, 5 );
    open(PROC, "$cmd |") or die "failed to fork :$!\n";
    while (<PROC>){
        if (/\]:/) {
            # PLUGINS_PREFS
            $f5 = "PLUGINS_PREFS";
            ($f1,$rhs) = split(/\[/);
            ($f2,$rhs2) = split(/\]:/,$rhs);
            ($f3,$f4) = split(/=/, $rhs2);
             $f3 =~ s/\s+$//;    # Remove trailing whitespace 
            $f4 =~ s/^ //;        # Remove leading whitespace
            $f4 =~ s/\n$//;        # Remove trailing newline

            $f0 = $f1."[".$f2."]:".$f3;
            $f2 =~ s/entry/T/;        # Text box
            $f2 =~ s/radio/R/;        # Radio button
            $f2 =~ s/checkbox/C/;        # Checkbox
            $f2 =~ s/password/P/;        # Password
            $f2 =~ s/file/T/;        # File

        } else {
            # SERVER_PREFS
            $f5 = "SERVER_PREFS";

            $f1 = "ServerPrefs";
            ($f3,$f4) = split(/=/);
            $f3 =~ s/\s+$//;    # Remove trailing whitespace
            $f4 =~ s/\n$//;        # Remove trailing newline
            $f4 =~ s/^ //;        # Remove leading whitespace
            $f2 = "T";
            $f0 = $f3;
        }

        # Does the current record exist? If not
        $sql = qq{ SELECT count(*) from vuln_nessus_preferences_defaults WHERE nessus_id = "$f0" };
        logwriter( $cmd, 5 );
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;

        $foo=$sth_sel->fetchrow_array;
        if ($foo == 0) {
            $sql = qq{insert into vuln_nessus_preferences_defaults (nessus_id, nessusgroup, type, field, 
                value, category,flag) values ("$f0", "$f1", "$f2", "$f3", "$f4", "$f5","T" );};
        } else {
            $sql = qq{UPDATE vuln_nessus_preferences_defaults SET nessusgroup="$f1", type="$f2", field="$f3",
                value="$f4", category="$f5", flag="T" WHERE nessus_id = "$f0" };
        }
        safe_db_write( $sql, 5 );

    }
    $sql = "UPDATE vuln_nessus_preferences_defaults set type = 'C' WHERE nessusgroup = 'ServerPrefs' and value in ('yes', 'no')";
    safe_db_write( $sql, 5 );

    $sql = "DELETE FROM vuln_nessus_preferences_defaults where flag is null";
    safe_db_write( $sql, 5 );

    my $time_run = time() - $time_start;
    print "\n";
    logwriter( "FINISH - UPDATE NESSUS_PREFERENCES [ Process took $time_run seconds ]", 4 );

}

sub get_server_credentialsA {
    # VER: 1.1 MODIFIED: 4/23/08 12:33
    my ( $select_id ) = @_;
  
    my ($sql, $sth_sel, $tmpserverid);

    $sql = qq{ SELECT id, hostname, port, user, password FROM vuln_nessus_servers WHERE id=$select_id };
    logwriter( $sql, 5 );

    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;
    ($tmp_serverid, $nessushost, $nessusport, $nessususer, $nessuspassword)=$sth_sel->fetchrow_array;
    $sth_sel->finish;
    return $tmp_serverid;

}

sub genScanTime {
    # VER: 1.0 MODIFIED: 3/29/07 13:03
    my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
    $year+=1900;
    $mon++;
    return sprintf("%04d%02d%02d%02d%02d%02d",$year, $mon, $mday, $hour, $min, $sec);
}

sub is_number{
    # VER: 1.0 MODIFIED: 3/29/07 13:03
    my($n)=@_;

    if ( $n ) { 
        return ($n=~/^\d+$/);
    } else {
        return;
    }
}

#read settings from db (overrides settings in file)
sub load_db_configs {
    # VER: 1.0 MODIFIED: 4/1/08 12:39
    my ($sth_sel);

    my $sql = qq{ SELECT settingName, settingValue FROM vuln_settings };
    logwriter( $sql, 5 );
    $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;

    while ( my ( $name,$value) = $sth_sel->fetchrow_array ) {
       if ( $name eq "mailSignature" ) { $value =~ s/&lt;br&gt;/\n/g; }
       if ( $name ne "") { $CONFIG{$name}=$value; }
    }

    $sth_sel->finish;
    return;
}

sub load_configs {
    # VER: 1.1 MODIFIED: 4/12/07 9:17
    my ( $configfile ) = @_;

    my $noconfig=0;
    open(CONF,"<$configfile") || $noconfig++;
    my @CONFILE=<CONF>;
    close(CONF);
    if ($noconfig) { print localtime(time)." port_scan: No config.txt file found.\n"; }
    foreach my $line (@CONFILE) {
        $line =~ s/\#.*$//;
        chomp($line);
        $line =~ s/\s+$//;
        $line =~ s/^\s+//;
        if ($line eq "") { next; }
        my @temp=split(/=/,$line,2);
        if ($temp[0] ne "") { $CONFIG{$temp[0]}=$temp[1]; }
    }
    return;
}

sub safe_db_write {
    # VER: 1.0 MODIFIED: 1/16/08 7:36
    my ( $sql_insert, $specified_level ) = @_;

    #logwriter( $sql_insert, $specified_level );
    logwriter( ".", $specified_level );
    
    eval {
        $dbh->do( $sql_insert );
    };
    warn "FAILED - $sql_insert\n" . $dbh->errstr . "\n\n" if ($@);

    if ( $@ ) { return 0; }

}

sub safe_db_query {
    # VER: 1.0 MODIFIED: 1/16/08 7:36
    my ( $sql, $specified_level ) = @_;

    logwriter( $sql, $specified_level );

    my ( $sth_sel );

    my @data = ();

    eval {
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;

        @data= $sth_sel->fetchrow_array;
    };

    $sth_sel->finish;

    return @data;

}

sub trim {
    my $string = @_;
    $string =~ s/^\s+//;
    $string =~ s/\s+$//;
    return $string;
}


sub check_dbOK {
    # VER: 1.1 MODIFIED: 11/26/07 10:08
    my $sql = "SELECT count( hostname ) FROM vuln_nessus_servers WHERE 1";

    eval {
            $dbh->do( $sql );
    };

    warn "FAILED - Connection Test\n" . $dbh->errstr . "\n\n" if ($@);

    if ( $@ ) { return 0; }

    return 1;

}

sub logwriter {
   # VER: 1.0 MODIFIED: 4/21/08 20:19
    my ( $message, $specified_level ) = @_;

    if ( !defined($specified_level) || $specified_level eq "" ) { $specified_level = 5; }

    my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
    $year+=1900;
    $mon++;
    my $now = sprintf("%04d-%02d-%02d %02d:%02d:%02d",$year, $mon, $mday, $hour, $min, $sec);
    
    if($message ne "."){

        $message = "$now $loginfo{$specified_level} $message";

        if ( $debug || $log_level ge $specified_level )  { print $message ."\n"; }
    
    }
    else {  print ".";  }

}

sub conn_db {
    # VER: 2.0 MODIFIED: 9/26/08 9:47

    if ( $CONFIG{'DATABASEPORT'} eq "" ) { $CONFIG{'DATABASEPORT'} = "3306"; }
    if ( $CONFIG{'DATABASESOCKET'} eq "" ) { $CONFIG{'DATABASESOCKET'} = "/var/lib/mysql/mysql.sock"; }

    $dbh = DBI->connect( "$CONFIG{'DATABASEDSN'}:$CONFIG{'DATABASENAME'};host=$CONFIG{'DATABASEHOST'};"
        ."port=$CONFIG{'DATABASEPORT'};socket=$CONFIG{'DATABASESOCKET'}",
        "$CONFIG{'DATABASEUSER'}","$CONFIG{'DATABASEPASSWORD'}", {
        PrintError => 0,
        RaiseError => 1,
        AutoCommit => 1 } ) or die("Failed to connect : $DBI::errstr\n");
    return $dbh;
}

sub disconn_db {
    # VER: 1.0 MODIFIED: 3/29/07 13:03
    my ( $dbh ) = @_;
    $dbh->disconnect or die("Failed to disconnect : $DBI::errstr\n");
}

sub generate_profiles {
    my (%profiles) = %{$_[0]};

    foreach my $nd (keys %profiles) {
        disconn_db($dbh);
        $dbh = conn_db();
        my @tmp = split(/\|/,$nd);
        my @values = split(/\|/,$profiles{$nd});

        $sql = qq{SELECT id from vuln_nessus_settings where name like '$tmp[0]' and owner='$tmp[3]' and deleted='0'};
        $sth_sel = $dbh->prepare($sql);
        $sth_sel->execute();
        my ($id) = $sth_sel->fetchrow_array;
        $sth_sel->finish;

        if ($id eq "") {
            print "Creating profile $tmp[0]...\n";
            $sql = qq{INSERT INTO vuln_nessus_settings (name, description, autoenable, owner, auto_cat_status, auto_fam_status)
                    values('$tmp[0]', '$tmp[1]', '$tmp[2]', '$tmp[3]', '$tmp[4]', '$tmp[5]')};
            $sth_sel = $dbh->prepare($sql);
            $sth_sel->execute;
            $sth_sel->finish;
            
            $sql = qq{SELECT LAST_INSERT_ID() as lastid};
            $sth_sel = $dbh->prepare($sql);
            $sth_sel->execute;
            my ($idprofile) = $sth_sel->fetchrow_array;
            $sth_sel->finish;
            
            # category
            print "Filling categories...";
            
            $sql = qq{ select id, name from vuln_nessus_category };
            $sth_self=$dbh->prepare( $sql );
            $sth_self->execute;
            while (my ($idcategory, $namecategory) =$sth_self->fetchrow_array ) {
                $namecategory =~ s/\t+//g;
                print ".";
                if($tmp[2] eq "F" || ($tmp[2] eq "C" && !in_array(\@values,$namecategory))) { #category off
                    $sql = qq{ insert into vuln_nessus_settings_category (sid, cid, status) values ($idprofile, $idcategory, 4)};
                }
                else { # category on
                    $sql = qq{ insert into vuln_nessus_settings_category (sid, cid, status) values ($idprofile, $idcategory, 1)};
                }
                $sth_sel = $dbh->prepare($sql);
                $sth_sel->execute;
                $sth_sel->finish();
            }
            $sth_self->finish();
            print " Done\n";
            
            # family
            print "Filling families...";
            
            $sql = qq{ select id, name from vuln_nessus_family };
            $sth_self=$dbh->prepare( $sql );
            $sth_self->execute;
            while (my ($idfamily, $namefamily) =$sth_self->fetchrow_array ) {
                $namefamily =~ s/\t+//g;
                print ".";
                if($tmp[2] eq "C" || ($tmp[2] eq "F" && !in_array(\@values,$namefamily))) { #family off
                    $sql = qq{ insert into vuln_nessus_settings_family (sid, fid, status) values ($idprofile, $idfamily, 4)};
                }
                else { # family on
                    $sql = qq{ insert into vuln_nessus_settings_family (sid, fid, status) values ($idprofile, $idfamily, 1)};
                }
                $sth_sel = $dbh->prepare($sql);
                $sth_sel->execute;
                $sth_sel->finish();
            }
            $sth_self->finish();
            print " Done\n";
            
            # plugins
            print "Filling plugins...";
            $sql = qq{ select id, category, family from vuln_nessus_plugins };
            $sth_self=$dbh->prepare( $sql );
            $sth_self->execute;
            while (my ($idplugin, $idcategory, $idfamily) =$sth_self->fetchrow_array ) {
                #print ".";
                $sqlc = qq{SELECT status as statusc from vuln_nessus_settings_category where sid='$idprofile' and cid = '$idcategory' };
                $sth_sc = $dbh->prepare($sqlc);
                $sth_sc->execute;
                my ($statusc) = $sth_sc->fetchrow_array;
                $sth_sc->finish;

                $sqlf = qq{SELECT status as statusf from vuln_nessus_settings_family where sid='$idprofile' and fid = '$idfamily' };
                $sth_sf = $dbh->prepare($sqlf);
                $sth_sf->execute;
                my ($statusf) = $sth_sf->fetchrow_array;
                $sth_sf->finish;

                if($statusc eq "1" || $statusf eq "1") { #plugin on
                    $sql = qq{ insert into vuln_nessus_settings_plugins (id, sid, enabled, category, family) 
                               values ($idplugin, $idprofile, 'Y', $idcategory, $idfamily) };
                }
                else { # plugin off
                    $sql = qq{ insert into vuln_nessus_settings_plugins (id, sid, enabled, category, family) 
                               values ($idplugin, $idprofile, 'N', $idcategory, $idfamily) };
                }
                $sth_sel = $dbh->prepare($sql);
                $sth_sel->execute;
                $sth_sel->finish();
            }
            # Special case, disable plugins 11219(synscan), 10335(tcp_scanner), 80009(portscan_strobe), 80001(pnscan), 80002(portbunny) for all profiles
            $sql = qq{ update vuln_nessus_settings_plugins set enabled='N' where (id=11219 or id=10335 or id=80009 or id=80001 or id=80002) and sid=$idprofile };
            $sth_sel = $dbh->prepare($sql);
            $sth_sel->execute;
            $sth_sel->finish();
            # end disabled
            $sth_self->finish();
            print " Done\n";
            
            # preferences
            print "Filling preferences...\n";
            
            # special case Ping Host[checkbox]
            $ping = 0;
            $sql = qq{ select id, nessus_id, value, category, type from vuln_nessus_preferences };
            $sth_self=$dbh->prepare( $sql );
            $sth_self->execute;
            while (my ($idp, $nessus_idp, $valuep, $categoryp, $typep) =$sth_self->fetchrow_array ) {
                print ".";
                if ($nessus_idp =~ /Ping Host.*Mark unrechable Hosts as dead/) {
                    $valuep = "yes";
                    $ping = 1;
                }
                $nessus_idp = quotemeta $nessus_idp;
                $sql = qq{ insert into vuln_nessus_settings_preferences (sid, id, nessus_id, value, category, type) 
                        values ('$idprofile', '$idp', '$nessus_idp', '$valuep', '$categoryp', '$typep') };
                $sth_sel = $dbh->prepare($sql);
                $sth_sel->execute;
                $sth_sel->finish();
            }
            $sth_self->finish();
            if (!$ping) {
                $sql = qq{ INSERT INTO vuln_nessus_settings_preferences (sid, id, nessus_id, value, category, type) 
                    VALUES($idprofile, NULL, 'Ping Host[checkbox]:Mark unrechable Hosts as dead (not scanning)', 'yes', 'PLUGINS_PREFS', 'C') };
                $sth_sel = $dbh->prepare($sql);
                $sth_sel->execute;
                $sth_sel->finish();
            }
            print "\nProfile $tmp[0] inserted\n";
            
        }
        else {
            print "Profile $tmp[0] already exists\n";
        }
    }   # end foreach
}

sub in_array {
    my @arr = @{$_[0]};
    my $search_for = $_[1];

    foreach my $value (@arr) {
        return 1 if ($value eq $search_for);
    }
    return 0;
}

