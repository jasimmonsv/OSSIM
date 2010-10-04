#!/usr/bin/perl -w 
#
###############################################################################
#
#    License:
#
#   Copyright (c) 2003-2006 ossim.net
#   Copyright (c) 2007-2010 AlienVault
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

use DBI;
use Date::Manip;
use MIME::Base64;
use Net::IP;
use IO::Socket;
use Date::Calc qw( Delta_DHMS Add_Delta_YMD Days_in_Month );
use Switch;
use strict;
use warnings;

$| = 1;


#Declare constants
use constant TRUE => 1;
use constant FALSE => 0;


my $file = $ARGV[0];
my $rdata = decode_base64($ARGV[1]); # report_name;user
my $asset_insertion = $ARGV[2];

my ($report_name, $user) = split(/;/,$rdata);

my %CONFIG = ();

my $dbhost = `grep db_ip /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`; chomp($dbhost);
$dbhost = "localhost" if ($dbhost eq "");
my $dbuser = `grep user /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`; chomp($dbuser);
my $dbpass = `grep pass /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`; chomp($dbpass);

$CONFIG{'DATABASENAME'} = "ossim";
$CONFIG{'DATABASEHOST'} = $dbhost;
$CONFIG{'UPDATEPLUGINS'} = 0;
$CONFIG{'DATABASEDSN'} = "DBI:mysql";
$CONFIG{'DATABASEUSER'} = $dbuser;
$CONFIG{'DATABASEPASSWORD'} = $dbpass;
$CONFIG{'nameservers'} = "";

#GLOBAL VARIABLES
my $sid = 0;
my $debug              = 0;
my $log_level          = 3;
my $compliance_plugins = "21156 21157 24760 33814 33929 33930 33931 40472 42083 46689";
my $isTop100Scan = FALSE;
my $no_results = FALSE;
my $scantime = "";
my $cred_name = "";

my %loginfo;                     #LOGWRITER RISK VALUES - PREDECLARED FOR EXTENSIVE RE-USE
   $loginfo{'1'} = "FATAL";
   $loginfo{'2'} = "ERROR";
   $loginfo{'3'} = "WARN";
   $loginfo{'4'} = "INFO";
   $loginfo{'5'} = "DEBUG";

my ( $dbh, $sth_sel, $sql );   #DATABASE HANDLE TO BE USED THROUGHOUT PROGRAM
my %nessus_vars = ();
$dbh = conn_db();
$sql = qq{ select * from config where conf = 'vulnerability_incident_threshold' or conf = 'use_resolv'};
$sth_sel=$dbh->prepare( $sql );
$sth_sel->execute;
while ( my ($conf, $value) = $sth_sel->fetchrow_array ) {
   $nessus_vars{$conf} = $value;
}
disconn_db($dbh);

my $vuln_incident_threshold = $nessus_vars{'vulnerability_incident_threshold'};
my $use_resolv = $nessus_vars{'use_resolv'};


main( );

exit; #end

sub main {
    my @issues = get_results_from_file($file);
    my $dbh = conn_db();
    my %hostHash = pop_hosthash(\@issues);
    process_results(\%hostHash, $report_name, "M", $user, $sid, $scantime, "");
    disconn_db($dbh);
}

#read in data from results file <- returns ( array of hashes ) $issues
sub get_results_from_file {
    my ( $outfile ) = @_;

    if ( ! -r $outfile ) { $no_results = TRUE; return FALSE; }
    
    my ($year, $month, $mday, $hour, $min, $sec ) = (localtime(time()))[5,4,3,2,1,0];

    $scantime = $year.$month.$mday.$hour.$min.$sec;
    
    my @issues;
    my ($rec_type, $domain, $host, $port, $description, $service, $proto, $scan_id, $risk_type );
    my $total_records = 0;
    logwriter("get_results_from_file:Outfile: $outfile", 4);
    # loop through input file and insert into table
    open(INPUT,"<$outfile")|| die("Can't open report file");

     while (<INPUT>) {
        # Initialize field values each time to ensure the are clear
        #
        my ($host, $domain, $scan_id, $description, $service, $app, $port, $proto, $rec_type, $risk_type ) = "";
        ( $rec_type, $domain, $host, $service, $scan_id, $risk_type, $description )=split(/\|/,$_);
        
        if ($service =~ /scan_end|host_end/) {
           my $date = ParseDate($scan_id);
           #logwriter("[date del parsedate: $date]", 4);
           #my ($year, $month, $day, $hour, $min, $sec) = UnixDate($date, "%Y", "%m", "%d", "%H", "%i", "%s");
           #$scantime = $year.$month.$day.$hour.$min.$sec;
           $date =~ s/\://g;
           $scantime = $date;
        }

        if ( $rec_type =~ /results/ ) {
            if ( $service =~ /general/ ) {
                my @temp = split /\//, $service;
                $app = "general";
                $proto = $temp[1];
                $port = "0";
            } else {
                my @temp = split /\s/, $service;
                $app = $temp[0];
                $temp[1] =~ s/\(//;
                $temp[1] =~ s/\)//;
                my @temp2 = split /\//, $temp[1];
                $port = $temp2[0];
                $proto = $temp2[1];
            }
            if (defined($scan_id)){
                #logwriter("get_results_from_file:scan_id:$scan_id", 4);
            }
            if (defined($compliance_plugins)){
                #logwriter("get_results_from_file:compliance_plugins:$compliance_plugins", 4);
            }
            if ( defined($scan_id) && $compliance_plugins =~ /$scan_id/ ) {
                #UPDATE SCANID FOR WIN CHECKS #21156
                if ( $scan_id =~ /21156/ ) {
                    my ( $test_name, $test_policy ) = "";
                    my @temp = split(/\\n/, $description);
                    foreach my $line (@temp) {
                        $line =~ s/\#.*$//;
                        chomp($line);
                        $line =~ s/\s+$//;
                        $line =~ s/^\s+//;
                        if ($line eq "") { next; }
                        $line =~ s/"//g;
                        if ( $line =~ /\[[EFP][AR][IRS][OLS][ER]D*\]/ ) {
                            $test_name = $line;
                            $test_name =~ s/\[[EFP][AR][IRS][OLS][ER]D*\]//;
                            $test_name =~ s/\s+$//;
                            $test_name =~ s/^\s+//;
                            $test_name =~ s/:$//;
                        }
                    }
                    if ( defined($test_name) && $test_name ne ""  ) {
                        #my $sql = qq{ SELECT t1.id FROM vuln_nessus_checks t1
                        #    LEFT JOIN vuln_nessus_checks_audits t2 on t1.id=t2.cid
                        #    WHERE t2.auditfile ='$primaryAuditcheck' AND
                        #    t1.name='$test_name' LIMIT 1 };
                        #logwriter( $sql, 5 );
                        #my $sth_sel = $dbh->prepare( $sql );
                        #$sth_sel->execute(  );
                        #my ( $tmp_scan_id ) = $sth_sel->fetchrow_array(  );
                        #if ( defined( $tmp_scan_id) && $tmp_scan_id >= 60000 ) { $scan_id = $tmp_scan_id; }
                    }
                }
                
                my $risk_value = "";
                if ( $description =~ m/\[PASSED\]/ ) {
                    $risk_value = "Risk factor : \n\nPassed\n";
                } elsif ( $description =~ m/\[FAILED\]/ ) {
                    $risk_value = "Risk factor : \n\nFailed\n";
                } else {
                    $risk_value = "Risk factor : \n\nUnknown\n";
                }
                $description .= "$risk_value";
                logwriter("set compliance description: $risk_value",5);
            }

            if ( $description ) {   #ENSURE WE HAVE SOME DATA
                $description =~ s/\\/\\\\/g;	#FIX TO BACKSLASHES
                $description =~ s/\\\\n/\\n/g;	#FIX TO NEWLINE

                my $temp = {
                    Port            => $port,
                    Host            => $host,
                    Description     => $description,
                    Service         => $app,
                    Proto           => $proto,
                    ScanID          => $scan_id
                };
                logwriter ( "my temp = { Port=>$port, Host=>$host, Description=>$description, Service=>$app, Proto=>$proto, ScanID=>$scan_id };\n", 4);
                push ( @issues, $temp );
                $total_records += 1;
            }
        }
    }

    if ($total_records eq 0 ) { $no_results = TRUE; }

#    for my $href ( @issues ) {
#        print "{ ";
#        for my $role ( keys %$href ) {
#            print "$role=$href->{$role} ";
#        }
#        print "}\n";
#    }

    return @issues;
}

sub process_results {
    my (%hostHash) = %{$_[0]};
    my ($job_title) = $_[1];
    my ($scantype)  = $_[2];
    my ($username)  = $_[3];
    my ($sid)       = $_[4];
    my ($scantime)  = $_[5];
    my ($fk_name)   = $_[6];
    
    my ( $sth_sel, $sql, $sth_sel2, $sql2, $sql_insert, $sql_insert2, $report_id, $report_key, $report_type, $update_stats, $rfield );
    my ( $sth_update, $sql_update, $sth_del, $sql_delete);
    my ( $rpt_key, $sqli, $sth_ins);
    my ( $nname);
    my ( $fp_sel, $fp_service, $fp);
    my %ntargets = ();
    my %acnets = ();
    my @sensors=();

    #List of sensors for insert hosts
    $sql = qq{ SELECT name FROM sensor};
    $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;

    while ( my ($sensor_name )=$sth_sel->fetchrow_array ) {
        if ($sensor_name) {
            push(@sensors, $sensor_name);
        }
    }

    my $bSInfo = FALSE;		    #TRACK SERVER SCAN INFO WAS SAVED
    #if ( $primaryAuditcheck ) { $rfield = "creport_id"; } else { $rfield = "report_id"; } #GET CORRECT FIELD BASED ON AUDIT TYPE
    if ( !defined( $fk_name) || $fk_name eq "" ) { $fk_name = "NULL"; } #else { $fk_name = "'".$fk_name."'"; }
    logwriter("isTop100Scan: $isTop100Scan", 4);
    if ( !$isTop100Scan ) { # GENERATE FULL REPORT WHEN NOT TOP100 AUDIT

    #MOVING REPORT CREATION OUT TO A NEW SUB ROUTINE / SO WE CAN USE FOR FAILED JOBS TOO.
        $report_id = create_report ( $job_title, $scantype, $username, $sid, $scantime, $fk_name, "0", "" );
        logwriter("Report id: $report_id", 4);
        if ( ! $report_id ) {
            logwriter( "failed to lookup report after insert for scan $scantime", 2 );
            return 0;
        }
    }

    logwriter( "nessus_scan: Start Processing Results", 5 );
    $sql_insert = "";
    my $i = 0;
    my %TOTALRISKS = ( 1, 0, 2, 0, 3, 0, 4, 0, 5, 0, 6, 0, 7, 0);   #TRACK COUNT ALL SCANNED RISKS

    foreach my $host ( sort keys %hostHash ) {
        my ( $hostip, $hostname, $mac_address, $os, $workgroup, $ip_org, $ip_site, $open_issues ) = " ";

        my $host_id = "0";
        my $localchecks = "-1";
        my $host_rating = "0";
        my $rating_text = " ";
        my %HOSTRISKS = ( 1, 0, 2, 0, 3, 0, 4, 0, 5, 0, 6, 0, 7, 0); #RESET FOR EACH HOST PROCESSED

        if ( $hostHash{$host}{'ip'}     ) {      $hostip  = $hostHash{$host}{'ip'};      }
        if ( $hostHash{$host}{'fqdn'}   ) {    $hostname  = $hostHash{$host}{'fqdn'};    }
        if ( $hostHash{$host}{'mac'}    ) { $mac_address  = $hostHash{$host}{'mac'};     }
        if ( $hostHash{$host}{'os'}     ) {           $os = $hostHash{$host}{'os'};      }
        if ( $hostHash{$host}{'wgroup'} ) {    $workgroup = $hostHash{$host}{'wgroup'};  }
        if ( $hostHash{$host}{'org'}    ) {       $ip_org = $hostHash{$host}{'org'};     }
        if ( $hostHash{$host}{'site'}   ) {      $ip_site = $hostHash{$host}{'site'};    }
        if ( $hostHash{$host}{'checks'} ) {  $localchecks = $hostHash{$host}{'checks'};  }
        if ( $hostHash{$host}{'rating'} ) {  $rating_text = $hostHash{$host}{'rating'};  }
    
    $hostname = "";
    if ($host =~ /(\d+)\.(\d+)\.(\d+)\.(\d+)/ && $hostname eq "") {
        $hostname = ip2hostname($host, FALSE, FALSE);
    }
    elsif ($host !~ /(\d+)\.(\d+)\.(\d+)\.(\d+)/) {
        $hostname = $host;
        $hostip = hostname2ip($hostname, TRUE);
        if($hostip !~ /(\d+)\.(\d+)\.(\d+)\.(\d+)/) { next; }
    }
    my $hostname_in_host = in_host($hostip);

    if ($asset_insertion == TRUE && $hostname_in_host eq "") {
        insert_host($hostip, $hostname, 2, 60, 60, "", 0, 0, "", \@sensors);
    }
    
    #logwriter ("HOSTNAME: $hostname",4);
    
    #before delete extract data
    my $sql_extract_data = qq{SELECT count(risk) as count, risk FROM vuln_nessus_latest_results
                                        WHERE report_id = inet_aton('$hostip') and username = '$username' and sid = '$sid' 
                                        AND falsepositive='N' GROUP BY risk};
    logwriter( $sql_extract_data, 5 );    
                                        
    my $sth_extract=$dbh->prepare($sql_extract_data); 
    $sth_extract->execute;
    
    my @risks_stats = ("0","0","0","0","0");
    
    while ( my ( $risk_count, $risk )=$sth_extract->fetchrow_array ) {
        if($risk==7) {
            $risks_stats[4] = $risk_count;
        }
        if($risk==6) {
            $risks_stats[3] = $risk_count;
        }
        if($risk==3) {
            $risks_stats[2] = $risk_count; 
        }
        if($risk==2) {
            $risks_stats[1] = $risk_count;
        }
        if($risk==1) {
            $risks_stats[0] = $risk_count;
        }
    }
    my $last_string = join(";",@risks_stats);
    
    #logwriter("Ip: $hostip",4);
    #logwriter("Hostname: $hostname",4);
    
    #delete vuln_nessus_latest_results results
    $sql_delete = qq{ DELETE FROM vuln_nessus_latest_results WHERE report_id = inet_aton('$hostip') and username = '$username' and sid = '$sid' };
    $sth_del = $dbh->prepare( $sql_delete );
    $sth_del->execute;
    
    $sql_delete = qq{ DELETE FROM vuln_nessus_latest_reports WHERE report_id = inet_aton('$hostip') and username = '$username' and sid = '$sid' };
    $sth_del = $dbh->prepare( $sql_delete );
    $sth_del->execute;

    $hostname = trim( $hostname );      #INITIALLY SET IT TO " ";
        #LOOKUP HOSTID
        if ( defined ( $hostname ) && $hostname ne "" ) { #LOOKUP HOST_ID IF HOSTNAME IS NOT NULL
            $host_id = get_host_record( $mac_address, $hostname, $hostip );
        }

	#ESTABLISH A RATING ( BASED ON TEXT MAPPING TO PER CHECK_ACCESS PROCESSING OF PLUGIN 10394 )
  	#0  	No Access		    /no access/
	#1 	No Administrator Password   /administrator no password/
	#2 	Blank User Passwords	    /user no password/
	#3 	NULL Access		    /null session/
	#4 	User Access		    /authenticated user/ && localcheck=="0"
	#5 	Admin Access		    /authenticated user/ && localcheck=="1"

	#I ORDERED THEM IN ORDER OF IMPORTANCE

	if ( $rating_text =~ /administrator no password/ ) {
	    $host_rating = 1;
	} elsif ( $rating_text =~ /user no password/  ) {
	    $host_rating = 2;
	} elsif ( $localchecks == "1" && ( $rating_text =~ /authenticated user/ || $rating_text =~ /run linux checks/ )) {
	    $host_rating = 5;
	} elsif ( $localchecks == "0" && ( $rating_text =~ /authenticated user/ || $rating_text =~ /run linux checks/ )) {
	    $host_rating = 4;
	} elsif ( $rating_text =~ /null session/ || $rating_text =~ /authenticated user/ || $rating_text =~ /run linux checks/ ) {
	    $host_rating = 3;
	} elsif ( $rating_text =~ /no access/ ) {
	    $host_rating = 0;
	    if ( $localchecks == "1" ) { $localchecks = "0"; }
	} else {
	    #THROW IN INVALID TO START TRACKING ON SENERIOS THAT MAY NOT MATCH UP
	    $host_rating = -1;
	    if ( $localchecks == "1" ) { $localchecks = "0"; }
            logwriter( "CRITICAL SCRIPT ERROR NO MATCH FOR HOST RATING: [$host_rating]", 2);
	}

	#logwriter( "hostid=[$host_id]\tmac=[$mac_address]\tname=[$hostname]\tip=[$hostip]\tos=[$os]\torg=[$ip_org]", 5 );
    
        # load fps
        my %host_fp = ();
        $sql = qq{ SELECT scriptid,service FROM vuln_nessus_latest_results WHERE hostIP='$hostip' and falsepositive='Y' UNION SELECT scriptid,service FROM vuln_nessus_results WHERE hostIP='$hostip' and falsepositive='Y' };
        $fp_sel = $dbh->prepare( $sql );
        $fp_sel->execute;
        while ((my $fp_scriptid,$fp_service) = $fp_sel->fetchrow_array) {
            $host_fp{$fp_scriptid}{$fp_service} = 1;
        }

        my %recordshash = %{$hostHash{$host}{'results'}};
        my %vuln_resume = ();

        foreach my $record ( sort keys %recordshash ) {
            my ( $scanid, $service, $app, $port, $proto, $risk, $domain, $record_type, $desc ) = " ";
            my $isCheck = "0"; #IS A COMPLIANCE CHECK SCRIPTID ( NOT A TENABLE PLUGIN ID )

            $scanid = $hostHash{$host}{'results'}{$record}{'scanid'};
            #logwriter("debug1: ".$scanid, 4 ); #DEBUGGG
            $service = $hostHash{$host}{'results'}{$record}{'service'};
            $app = $hostHash{$host}{'results'}{$record}{'app'};
            $proto = $hostHash{$host}{'results'}{$record}{'proto'};
            $port = $hostHash{$host}{'results'}{$record}{'port'};
            $desc = $hostHash{$host}{'results'}{$record}{'desc'};
            $desc =~ s/^ *| *$//g;
            $desc =~ s/^(\\n|\n)+//g;
            $desc =~ s/(\\n|\n)+$//g;
            $risk = $hostHash{$host}{'results'}{$record}{'risk'};
            $domain = $hostHash{$host}{'results'}{$record}{'domain'};
            $record_type = $hostHash{$host}{'results'}{$record}{'record'};
            $open_issues .= "$scanid\n";    #USED TO TRACK ISSUES TO BE CLOSED
            $TOTALRISKS{"$risk"} += 1;	    #USING ASSOC ARRAY TO TRACK SCAN RISK COUNT ON THE FLY
            $HOSTRISKS{"$risk"} += 1;	    #USING ASSOC ARRAY TO TRACK HOST RISK COUNT ON THE FLY

            #if ( !$bSInfo && $scanid eq "19506" ) {   #CODE TO UPDATE SERVER FEED VERSION/DATE
            #    set_serverinfo( $report_id, $desc );  #bSInfo should enable it to be only run once per scan
            #}

	    #$desc=~ s/\\/\\\\/g;        #FIX TO ENSURE "\" BACKSLASHES ARE INSERTED.

            logwriter( "record=$record\t 'scanid' => [$scanid], 'port' => [$port], 'record' => [$record_type], 'service' => [$service],"
                ." 'proto' => [$proto], 'risk' => [$risk], 'desc' => [$desc]\n", 4); 

            #if ( defined( $host_id ) && $host_id > 0 ) { #CREATE/UPDATE INCIDENTS
            #    if ( $scanid < 60000 ) {	#ALLOW TO SPECIFY RISK LEVEL TO TRACK
                    #$sql = qq{ SELECT id FROM vuln_Incidents WHERE host_id='$host_id' AND scriptid='$scanid' AND service='$service' LIMIT 1 };
                    #logwriter( $sql, 5 );
                    #$sth_sel = $dbh->prepare( $sql );
                    #$sth_sel->execute;
                    #my ( $incident_id ) = $sth_sel->fetchrow_array;

                    #PLUGINS HIGHER THAN 60000 is mapped as compliance check
                    #if ( $scanid >= 60000 ) { $isCheck = "1"; }	 # INCASE DECIDE TO TRACKING COMPLAINCE PER INCIDENTS TABLE

                    #if ( defined($incident_id ) && $incident_id ne "" ) {
                    #    $sql = qq{ UPDATE vuln_Incidents SET risk='$risk', msg='$desc', isCompCheck='$isCheck',
                    #        date_lastseen='$scantime', date_resolved=NULL, status='open' WHERE id="$incident_id" };
                    #} else {
                    #    $sql = qq{ INSERT INTO vuln_Incidents ( host_id, scriptid, service, risk, msg, isCompCheck, 
                    #        date_open, date_lastseen ) VALUES ( '$host_id', '$scanid', '$service', '$risk', '$desc', '$isCheck', '$scantime', '$scantime' );};
                    #}
                    #safe_db_write ( $sql, 4 );

		    #UPDATE vuln_host_software
		    #if ( $scanid eq "20811" ) {
		        #update_vuln_host_software ( $host_id, $scantime, $desc );
		    #}

		    #UPDATE vuln_host_services
		    #if ( $scanid eq "10456" ) {
				#update_host_service ( $host_id, $scantime, $desc );
		    #}

		    #UPDATE HOST_ADMINSGROUP
		    #if ( $scanid eq "10902" ) {
				#update_host_admins ( $host_id, $scantime, $desc );
		    #}

		    #UPDATE vuln_host_users
		    #if ( $scanid eq "10860" ) {
				#update_vuln_host_users ( $host_id, $scantime, $desc );
		    #}

		    #UPDATE HOST_DISABLEDUSERS
		    #if ( $scanid eq "10913" ) {	#WILL NOT IMPORT IF USER PLUGIN WAS NOT IMPORTED FIRST
				#update_host_disabled_users ( $host_id, $scantime, $desc );
		    #}

                #}
            #} #FINISH CREATE/UPDATE INCIDENTS
            if ( !$isTop100Scan ) {	#LOAD INTOTO vuln_nessus_results
                if ( !defined( $sql_insert ) || $sql_insert eq "" ) {

                    #FIRST ITERATION OR RESET VARIABLE AFTER IMPORTING 100 RECORDS
                    $sql_insert = "INSERT INTO vuln_nessus_results ( report_id, scantime, hostip, hostname, record_type, service, port, protocol , app, scriptid, risk, msg, falsepositive )\nVALUES\n";
                    $sql_insert2 = "INSERT INTO vuln_nessus_latest_results ( report_id, username, sid, scantime, hostip, hostname, record_type, service, port, protocol , app, scriptid, risk, msg, falsepositive )\nVALUES\n";
                    #delete host_plugin_sid results
                    $sql_delete = qq{ DELETE FROM host_plugin_sid WHERE host_ip = inet_aton('$hostip') and plugin_id = 3001 };
                    logwriter( $sql_delete, 5 );
                    $sth_del = $dbh->prepare( $sql_delete );
                    $sth_del->execute;
                    #delete host_plugin_sid results
                    my @arr = split(/\./, rand() );
                    if ( $arr[1] && is_number($arr[1]) ) { $rpt_key = $arr[1]; }
                    else { $rpt_key = 0; }
                    $sqli = qq{ INSERT INTO vuln_nessus_latest_reports ( report_id, username, name, fk_name, sid, scantime, report_type, scantype, report_key, cred_used, note, failed ) VALUES (inet_aton('$hostip'), '$username', '$hostip', NULL, '$sid', '$scantime', 'N', '$scantype', '$rpt_key', NULL, '0;0;0;0;0','0' ) ON DUPLICATE KEY UPDATE name='$hostip',scantime='$scantime',failed=results_sent,note='$last_string' };
                    logwriter( $sqli, 5 );
                    $sth_ins = $dbh->prepare( $sqli );
                    $sth_ins->execute;
                    $i=0;
                }
                $i += 1;
                $fp = (defined($host_fp{$scanid}{$service}) && $host_fp{$scanid}{$service} == 1) ? 'Y' : 'N';
                $sql_insert .= " ('$report_id', '$scantime', '$hostip', '$hostname', '$record_type', '$service', '$port', '$proto', '$app', '$scanid', '$risk', '$desc', '$fp' ),\n";
                $sql_insert2 .= " (inet_aton('$hostip'), '$username', '$sid', '$scantime', '$hostip', '$hostname', '$record_type', '$service', '$port', '$proto', '$app', '$scanid', '$risk', '$desc', '$fp' ),\n";
                    
                if ( $i >= 100 ) {
                    chop($sql_insert);
                    chop($sql_insert);
                    chop($sql_insert2);
                    chop($sql_insert2);
                    $sql_insert .= ";";
                    $sql_insert2 .= ";";
                    #CONNECT DB AND INSERT 100 RECORDS
                    safe_db_write( $sql_insert, 5 );
                    logwriter( "[$job_title] - inserted $i results records", 4 );
                    safe_db_write( $sql_insert2, 5 );
                    logwriter( "[$job_title] - inserted $i latest_results records", 4 );

                    $sql_insert = "";
                    $sql_insert2 = "";
                    $i = 0;
                }
            }
            $vuln_resume{$hostip}++;
            # incidents
            update_ossim_incidents($hostip, $port, $risk, $desc, $scanid, $username);
        } #END FOR EACH RECORD
        
        #CHECK FOR RECORDS WHICH REMAIN NOT INSERTED FOR HOST  
        if ( !$isTop100Scan ) {          
            if ( defined( $sql_insert ) && $sql_insert ne "" ) {
                chop($sql_insert);
                chop($sql_insert);
                chop($sql_insert2);
                chop($sql_insert2);
                $sql_insert .= ";";
                $sql_insert2 .= ";";
                #CONNECT DB AND INSERT REMAINDER OF RECORDS
                safe_db_write( $sql_insert, 5 );
                logwriter( "[$job_title] - inserted $i results records", 4 );
                safe_db_write( $sql_insert2, 5 );
                logwriter( "[$job_title] - inserted $i latest_results records", 4 );
                $sql_insert = "";
                $sql_insert2 = "";
            }
        }      
        
        foreach $hostip (keys %vuln_resume) {
            $sql = qq{ SELECT scriptid FROM vuln_nessus_latest_results WHERE hostIP='$hostip' };
            logwriter( $sql, 5 );
            $sth_sel = $dbh->prepare( $sql );
            $sth_sel->execute;
            my $vt = 0;
            while ((my $scanid) = $sth_sel->fetchrow_array) {
                #logwriter( "Scan id: $scanid", 5 );
                # plugin_sid
                $sql_update = qq{ INSERT IGNORE INTO host_plugin_sid (host_ip, plugin_id, plugin_sid) VALUES (inet_aton('$hostip'), 3001, $scanid) };
                logwriter( $sql_update, 5 );
                $sth_update = $dbh->prepare( $sql_update );
                $sth_update->execute;
                #
                $vt++;
            }
            # net accumulation
            foreach my $anet (keys %ntargets) {
                $ntargets{$anet} =~ s/^\s*|\s*$//g;
                $acnets{$ntargets{$anet}} += $vt if (ipinnet($hostip,$anet));
            }
            # host_vulnerability
            $sql_update = qq{ INSERT INTO host_vulnerability VALUES ('$hostip', '$scantime', $vt) ON DUPLICATE KEY UPDATE vulnerability=$vt  };
            logwriter( $sql_update, 5 );
            $sth_update = $dbh->prepare( $sql_update );
            $sth_update->execute;
            
            # vulnerabilities 
            $sql_update = qq{SELECT count( * ) AS vulnerability FROM (SELECT DISTINCT hostip, port, protocol, app, scriptid, msg, risk
                        FROM vuln_nessus_latest_results WHERE report_id =inet_aton('$hostip') AND falsepositive='N') AS t GROUP BY hostip};
            logwriter( $sql_update, 5 );
            $sth_update=$dbh->prepare( $sql_update );
            $sth_update->execute;
            my $vuln_host = $sth_update->fetchrow_array;
            
            # update vulns into vuln_nessus_latest_reports - sort facility
            $sql_update = qq{ UPDATE vuln_nessus_latest_reports SET results_sent=$vuln_host WHERE report_id=inet_aton('$hostip') AND username='$username' };
            logwriter( $sql_update, 5 );
            $sth_update = $dbh->prepare( $sql_update );
            $sth_update->execute;
        }

        foreach my $nname (keys %acnets) {
            my $nt = $acnets{$nname};
            $sql_update = qq{ INSERT INTO net_vulnerability VALUES ('$nname', '$scantime', $nt) ON DUPLICATE KEY UPDATE vulnerability=$nt };
            logwriter( $sql_update, 5 );
            $sth_update = $dbh->prepare( $sql_update );
            $sth_update->execute;
        }

        #PER EACH HOST UPDATE HOST RECORD/STATS
	if ( defined ( $hostname ) && $hostname ne "" ) {
            #update_host_record ( \%HOSTRISKS, $mac_address, $hostname, $hostip, $os, $workgroup, $ip_org, $ip_site, $report_id, $scantime, $localchecks, $host_rating, $update_stats );
	}
        undef ( %HOSTRISKS );

    } #END FOREACH HOST LOOP

    if ( !$isTop100Scan ) { #TOTALLY DONE LOOP ( OPTION TO DUMP TO .NESSUS FILE FROM RESULTS DATA )

        #logwriter( "Completed SQL Import", 4 );
        #update_stats ( $job_id, $job_title, $report_id, $scantime );

        #if ( $CONFIG{'archiveNessus'} eq "1" ) {
        #    my $cmd = "$CONFIG{'ROOTDIR'}/sbin/archive_report.pl -r $report_id";
        #    my $ex = qx{ $cmd };
        #}
    }
    
    #my $sql_check_email = qq{ SELECT meth_Wfile FROM vuln_jobs WHERE report_id = '$report_id' };
    #logwriter( $sql_check_email, 5 );
    #my $sth_check = $dbh->prepare( $sql_check_email );
    #$sth_check->execute;
    #my ( $semail ) = $sth_check->fetchrow_array; 
    #if($semail eq "1") {
    #    my $cmde = qq{/usr/bin/php /usr/share/ossim/scripts/vulnmeter/send_notification.php '$report_id'};
    #    logwriter("Send email for report_id: $report_id ...", 5);
    #    open(EMAIL,"$cmde 2>&1 |") or die "failed to fork :$!\n";
    #    while(<EMAIL>){
    #        chomp;
    #        logwriter("send_notification output: $_", 5);
    #    }
    #    close EMAIL;
    #}
    
    return TRUE;
}

#pop hosthash will process the results to make the most of the data.  This will improve reporting / tracking of scanned hosts
#this is the heart of the inprotect code ( this feeds host_tracking (culumative/results) database tables ).
sub pop_hosthash {
    my (@issues ) = @{$_[0]};
    
    logwriter("Number of results: ".$#issues, 5);
    

    my ( $sth_sel, $sql, $domain, $hostname, $mac_address, $report_key, $report_type, $record_type );

    # WAITED TO LOAD CUSTOM RISK TO LOAD RESULTS ( IMPORT ROUTINES MAY USE IT )
    my $custom_risks = get_custom_risks( );

    my %hostHash;
    # set up some error counters
    my $errCount = 0;
    my @errMsg;
    my $ctable = {};        #STORE NETBLOCKS FOR ORG LOOKUP

    if ( $no_results ) {
        logwriter( "NO Results to Import or Host offline", 2 );
        return FALSE;
    }

    #$sql = qq{ SELECT id, CIDR FROM vuln_subnets WHERE 1 };
    #$sth_sel = $dbh->prepare( $sql );
    #$sth_sel->execute;
    #while( my ( $subID, $CIDR )=$sth_sel->fetchrow_array){
    #    my $net_table = new2  Net::Netmask($CIDR); 
    #    $net_table->storeNetblock( $ctable );
    #}
    logwriter( "LOADED ALL Netblocks", 5 ); 

    my $ih = 0;

    #GET POPULATE HOSTHASH WITH HOSTNAME /DOMAIN FROM PLUGIN 10150
    logwriter( "nessus_scan: Start Populating HostHash", 5 );
    foreach( @issues ) {
        my $issue = $_;
        my ($scanid, $host, $hostname, $hostip, $service, $app, $port, $proto, $desc,
            $record_type, $domain, $mac_address, $os, $org, $site, $sRating, $sCheck, $sLogin ) = " ";


        $scanid = $issue->{ScanID};
        $scanid =~ s/.*\.(\d+)$/$1/g;
        $port = $issue->{Port};
        $desc = $issue->{Description};
        $service = $issue->{Service};
        $proto = $issue->{Proto};
        $host = $issue->{Host};

        $app = $service;
        if(defined($service) && $service ne "") {
            if(defined($proto) && $proto ne "") {
                $service = "$service ($port/$proto)";
            } else {
                $app = "general";
                $proto = $service;
                $port = "";
                $service = "general/$service";
            }
        }

        if( $host eq "" ) { next; }
        if ( ! $hostHash{$host}{'mac'} ) { $hostHash{$host}{'mac'} = "unknown"; }

	#SET Default for local checks based on if a credential was supplied
	# -1 ( No Credential Used ), 1 ( Credential Used )
	# Then if hits against 21745 ( there was issue with credential such as invalid / etc )
	if ( ! $hostHash{$host}{'checks'} ) {
	    $hostHash{$host}{'rating'} = " ";
	    if ( !defined ( $cred_name ) || $cred_name eq "" ) {
		$hostHash{$host}{'checks'} = "-1";
		logwriter( "nessus_scan: [$host] localchecks = -1 cred_name=[$cred_name]", 4 );
	    } else {
		$hostHash{$host}{'checks'} = "1";
		logwriter( "nessus_scan: [$host] localchecks = 1 cred_name=[$cred_name]", 4 );
	    }
	}

        if ( !exists( $hostHash{$host}{'dns'}) ) {

            #DETERMINE IF IT IS AN IP (CRITICAL STEP AS SCANLITE RETURNS EITHER HOSTIP/HOSTNAME)
            my $isIP = FALSE;
            if ( $host =~ m/^(\d\d?\d?)\.(\d\d?\d?)\.(\d\d?\d?)\.(\d\d?\d?)/ ){
                if($1 <= 255 && $2 <= 255 && $3 <= 255 && $4 <= 255) {
                    $hostip=$host;
                    $isIP = TRUE;
                }
            }

            if ( $isIP == FALSE ) {            #MUST BE A NAME ATTEMPT TO RESOLVE ELSE FAILED MISERABLY
                $hostname=$host;            #LETS AT LEAST SET NAME IN CASE ALL ELSE FAILS
                my $tmp_hostip = resolve_name2ip( $host );
                if ( defined( $tmp_hostip) && $tmp_hostip ne "" ) { $hostip = $tmp_hostip; }
            }

            if ( defined( $hostip ) && $hostip ne "" ) {
                #my $match = findNetblock($hostip, $ctable);
                #if ( $match ) {
                    #$sql = qq{ SELECT id, site_code, ORG FROM vuln_subnets WHERE CIDR = "$match" LIMIT 1 };
                    #$sth_sel = $dbh->prepare( $sql );
                    #$sth_sel->execute;
                    #my ( $subID, $site_code, $ORG) = $sth_sel->fetchrow_array( );
                #    my $subID = ""; my $site_code = ""; my $ORG = "";
                #    $hostHash{$host}{'org'} = "$ORG";                                #FOUND ORG
                #    $hostHash{$host}{'site'} = "$site_code";                    #FOUND SITE
                #    logwriter( "FOUND ORG MATCH FOR hostip=$hostip ORG=$ORG site=$site_code", 5 );
                #}
                #ATTEMPT TO CONSULT VARIOUS DNS SERVERS IN CASE SCANLITE RETURNTED IP
                my $tmp_hostname = resolve_host( $hostip );
                
                if ( defined( $tmp_hostname ) && $tmp_hostname ne "" ) { $hostname = $tmp_hostname; }
            }

            $hostHash{$host}{'ip'} = $hostip; 
            if( defined( $hostname ) && $hostname ne "" ) {
                $hostHash{$host}{'fqdn'} = $hostname;
                $hostHash{$host}{'dns'} = "1";                                  #INDICATE RESOLVED BY NAME WAS SUCCESS
                logwriter( "nessus_scan: successfully looked up name [$host]", 5 );
            } else {
                $hostHash{$host}{'dns'} = "-1";                                 #INDICATE RESOLVED BY NAME FAILED
            }
        } 

        if ( $scanid eq "11936" ) {                                             #OS FINGERPRINT PLUGIN
            my $os = extract_os( $desc );
            $hostHash{$host}{'os'} = $os;
        }

        if ( $scanid eq "10150" ) {                                             #NBTSCAN PLUGIN
            my %hostinfo = extract_hostinfo( $desc );
            $hostHash{$host}{'mac'} = $hostinfo{'mac'};

            if ( $hostHash{$host}{'dns'} eq "-1" && $hostinfo{'dns'} eq "1" ) { #ONLY UPDATE NAME FROM 10150 WHEN DNS FAILS
                $hostHash{$host}{'fqdn'} = $hostinfo{'hostname'};
                $hostHash{$host}{'wgroup'} = $hostinfo{'wgroup'};
                $hostHash{$host}{'dns'} = '1'; 
                logwriter( "nessus_scan: success plugin 10150 to look up name [" . $hostinfo{'hostname'} . "]", 5 );
            }
        }


        #IDENTIFY SCAN ACCESS LEVEL
        if ( $scanid eq "10394" || $scanid eq "12634" ) {
	    #need to check message against known rating texts
	    #10394 WINDOWS 12634 LINUX
	    #STORE TO $Rating UNTIL POST PROCESS ROUTINE TO SEE IF WE HIT ON 21745
	    $hostHash{$host}{'rating'} = check_access( $desc );
	}

	#IDENTIFY IF LOCAL CHECKS FAILED
        if ( $scanid eq "21745" ) {
	    $hostHash{$host}{'checks'} = "0";
	}

        # get the risk value from the text in the description
        my $risk='7';
        $risk='1'  if ($desc =~ m/Risk [fF]actor\s*:\s*(\\n)*Serious/s);
        $risk='1'  if ($desc =~ m/Risk [fF]actor\s*:\s*(\\n)*Critical/s);
        $risk='2'  if ($desc =~ m/Risk [fF]actor\s*:\s*(\\n)*High/s);
        $risk='3'  if ($desc =~ m/Risk [fF]actor\s*:\s*(\\n)*Medium/s);
        $risk='4'  if ($desc =~ m/Risk [fF]actor\s*:\s*(\\n)*Medium\/Low/s);
        $risk='5'  if ($desc =~ m/Risk [fF]actor\s*:\s*(\\n)*Low\/Medium/s);
        $risk='6'  if ($desc =~ m/Risk [fF]actor\s*:\s*(\\n)*Low/s);
        $risk='7'  if ($desc =~ m/Risk [fF]actor\s*:\s*(\\n)*Info/s);
        $risk='7'  if ($desc =~ m/Risk [fF]actor\s*:\s*(\\n)*[nN]one/s);
        #$risk='8' if ($desc =~ m/Risk [fF]actor\s*:\s*(\\n)*Exception/s);       #EXCEPTIONS ARE CALCULATED FROM EXCEPTION DATA NOT BY A STORED RISK VALUE
        $risk='7'  if ($desc =~ m/Risk [fF]actor\s*:\s*(\\n)*Passed/s);          #PLAN TO RECLASSIFY Compliance Audit Values
        $risk='3' if ($desc =~ m/Risk [fF]actor\s*:\s*(\\n)*Unknown/s);         #PLAN TO RECLASSIFY Compliance Audit Values
        $risk='2' if ($desc =~ m/Risk [fF]actor\s*:\s*(\\n)*Failed/s);          #PLAN TO RECLASSIFY Compliance Audit Values

        #remove the Risk Factor from the description
        $desc=~ s/Risk [fF]actor\s*:\s*(\\n)*Serious((\\n)+| \/ |$)//s;
        $desc=~ s/Risk [fF]actor\s*:\s*(\\n)*Critical((\\n)+| \/ |$)//s;
        $desc=~ s/Risk [fF]actor\s*:\s*(\\n)*High((\\n)+| \/ |$)//s;
        $desc=~ s/Risk [fF]actor\s*:\s*(\\n)*Medium((\\n)+| \/ |$)//s;
        $desc=~ s/Risk [fF]actor\s*:\s*(\\n)*Medium\/Low((\\n)+| \/ |$)//s;
        $desc=~ s/Risk [fF]actor\s*:\s*(\\n)*Low\/Medium((\\n)+| \/ |$)//s;
        $desc=~ s/Risk [fF]actor\s*:\s*(\\n)*Low((\\n)+| \/ |$)//s;
        $desc=~ s/Risk [fF]actor\s*:\s*(\\n)*Info((\\n)+| \/ |$)//s;
        $desc=~ s/Risk [fF]actor\s*:\s*(\\n)*[nN]one to High((\\n)+|(\s)+| \/ |$)//;
        $desc=~ s/Risk [fF]actor\s*:\s*(\\n)*[nN]one((\\n)+| \/ |$)//s;
        $desc=~ s/Risk [fF]actor\s*:\s*(\\n)*Passed((\\n)+| \/ |$)//s;
        $desc=~ s/Risk [fF]actor\s*:\s*(\\n)*Unknown((\\n)+| \/ |$)//s;
        $desc=~ s/Risk [fF]actor\s*:\s*(\\n)*Failed((\\n)+| \/ |$)//s;

        #CUSTOM RISK CODE
        if ( $custom_risks->{$scanid} ) { 
            $risk = $custom_risks->{$scanid};
            logwriter( "ASSIGNED PLUGIN: $scanid CUSTOM RISK VALUE $risk", 5 );
        }

        $service =~ s/(\\n)+$//;
        $desc =~ s/(\\n)+$//;
        $desc =~ s/\\n+$//;
        $desc =~ s/\\+$//;

        #MEANS TO TRACK FILTER ON THE REPORTS
        if ( $scanid >= 60000 ) { $record_type = "C"; } else { $record_type = "N"; }

        $service = htmlspecialchars($service);
        $desc = htmlspecialchars($desc);

        #print "i=$i\n 'scanid' => $scanid, 'port' => $port, 'desc' => $desc, 'service' => $service, 'proto' => $proto \n";
        #my $key = $port.$proto.$scanid;
        my $key = $ih; 
        $hostHash{$host}{'results'}{$key} = { 'scanid' => $scanid, 'port' => $port, 'app' => $app, 'service' => $service,
            'proto' => $proto, 'risk' => $risk, 'record' => $record_type, 'desc' => $desc };
        #logwriter("Ip: $host", 4);
        $ih++;
    }
    logwriter( "nessus_scan: Finished Populating HostHash: $ih", 5 );


    return (%hostHash);
}

#connect to db
sub conn_db {
    # VER: 1.0 MODIFIED: 3/29/07 13:03
    $dbh = DBI->connect("$CONFIG{'DATABASEDSN'}:$CONFIG{'DATABASENAME'}:$CONFIG{'DATABASEHOST'}",
        "$CONFIG{'DATABASEUSER'}","$CONFIG{'DATABASEPASSWORD'}", {
        PrintError => 0,
        RaiseError => 1,
        AutoCommit => 1 } ) or die("Failed to connect : $DBI::errstr\n");
    return $dbh;
}

#disconnect from db
sub disconn_db {
    # VER: 1.0 MODIFIED: 3/29/07 13:03
    my ( $dbh ) = @_;
    $dbh->disconnect or die("Failed to disconnect : $DBI::errstr\n");
}

#routine to do log writing
sub logwriter {
   # VER: 1.0 MODIFIED: 4/21/08 20:19
    my ( $message, $specified_level ) = @_;

    if ( !defined($specified_level) || $specified_level eq "" ) { $specified_level = 5; }

    my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
    $year+=1900;
    $mon++;
    my $now = sprintf("%04d-%02d-%02d %02d:%02d:%02d",$year, $mon, $mday, $hour, $min, $sec);

    $message = "$now [$$] $loginfo{$specified_level} $message";

    if ( $debug || $log_level ge $specified_level )  { print $message ."\n"; }

}

sub create_report {
    my ($job_title) = $_[0];
    my ($scantype)  = $_[1];
    my ($username)  = $_[2];
    my ($sid)       = $_[3];
    my ($scantime)  = $_[4];
    my ($fk_name)   = $_[5];
    my ($failed)    = $_[6];
    my ($note)    = $_[7];

    if ( $failed ne "1" ) { $failed = "0"; }
    if ( !defined ( $note ) || $note eq "" ) { $note = "NULL"; } else { $note = "'$cred_name'"; }

    
    my ( $sth_sel, $sql, $report_id, $report_key, $report_type, $rfield );

    #Build a report_key value to secure reports.
    my @arr = split(/\./, rand() );
    if ( $arr[1] && is_number($arr[1]) ) {
        $report_key = $arr[1];
    } else {
        logwriter( "Failed Report Key generation", 3 );
    }

    if ( !defined ( $cred_name ) || $cred_name eq "" ) { $cred_name = "NULL"; } else { $cred_name = "'$cred_name'"; }

    #CHOSE RECORD TYPE
    #if ( $isNessusScan && $isComplianceAudit ) {
    #    $report_type = "B";
    #} elsif ( $isComplianceAudit ) {
    #    $report_type = "C";
    #    print "Compliance Audit =TRUE\n";
    #} else {        #DEFAULT NESSUS SCAN
    #    $report_type = "N";
    #}
    
    $report_type = "I";
    
    #logwriter("fk_name: $fk_name $cred_name", 4);
    $sql = qq{ INSERT INTO vuln_nessus_reports ( username, name, fk_name, sid, scantime, report_type, scantype, report_key, cred_used, note, failed ) VALUES (
        '$username', '$job_title', NULL, '$sid', '$scantime', '$report_type', '$scantype', '$report_key', NULL, $note, '$failed' ); };
    safe_db_write ( $sql, 4 );

    $sql = qq{ SELECT report_id FROM vuln_nessus_reports WHERE scantime='$scantime' AND report_key='$report_key' ORDER BY scantime DESC LIMIT 1 };
    logwriter( $sql, 5 );
    $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;
    $report_id = $sth_sel->fetchrow_array( );

    return $report_id;
}

sub update_ossim_incidents {
        my $hostip = shift;
        my $port = shift;
        my $risk = shift;
        my $desc = shift;
        my $scanid = shift;
        my $username = shift;
        
        my ($sql_inc, $sth_inc);
        
        my $id_pending = 65001;
        my $id_false_positive = 65002;
        
        $risk = 8 - $risk; # convert into ossim risk
        #logwriter("update_ossim_incidents - risk = $risk",5);
        #logwriter("update_ossim_incidents - threshold = $vuln_incident_threshold",5);
        
        return if ($vuln_incident_threshold >= $risk);  
        
        #Check if exists a vulnerability already create
        $sql_inc = qq{ SELECT incident_id FROM incident_vulns WHERE ip = '$hostip' AND port = '$port' AND nessus_id = '$scanid' };
        $sth_inc = $dbh->prepare($sql_inc);
        $sth_inc->execute();
        my ($id_inc) = $sth_inc->fetchrow_array;
        $sth_inc->finish;

        if ($id_inc ne "") {
            $sql_inc = qq{ UPDATE incident SET last_update = now() WHERE id = '$id_inc' };
            safe_db_write( $sql_inc, 4 );
            $sql_inc = qq{ SELECT priority FROM incident WHERE status='Closed' and id = '$id_inc' };
            $sth_inc = $dbh->prepare($sql_inc);
            $sth_inc->execute();
            #logwriter("SELECT priority FROM incident WHERE status='Closed' and id = '$id_inc'",4);
            my $priority = $sth_inc->fetchrow_array;
            $sth_inc->finish;
            if ($priority ne "") {
                $sql_inc = qq{SELECT incident_id FROM incident_tag WHERE incident_tag.incident_id = '$id_inc' AND incident_tag.tag_id = '$id_false_positive' };
                $sth_inc = $dbh->prepare($sql_inc);
                $sth_inc->execute();
                my ($hash_false_incident) = $sth_inc->fetchrow_array;
                $sth_inc->finish;
                if ($hash_false_incident eq "") {
                    $sql_inc = qq{ UPDATE incident SET status = 'Open' WHERE id = '$id_inc', in_charge = '$username' };
                    safe_db_write( $sql_inc, 4 );
                    my $ticket_id = genID("incident_ticket_seq");
                    my $sql_ticket = qq { INSERT INTO incident_ticket (id, incident_id, date, status, priority, users, description) values ('$ticket_id', '$id_inc', now(), 'Open', '$priority', 'admin','Automatic open of the incident') };
                    $sth_inc = $dbh->prepare($sql_ticket);
                    $sth_inc->execute();
                    $sth_inc->finish;
                    }
            }
        }
        else {
            $sql_inc = qq{SELECT name,reliability,priority FROM plugin_sid where plugin_id = 3001 and sid = '$scanid'};
            $sth_inc = $dbh->prepare($sql_inc);
            $sth_inc->execute();
            my ($name_psid, $reliability_psid, $priority_psid) = $sth_inc->fetchrow_array;
            $sth_inc->finish;
            my $vuln_name = "";
            if ($name_psid ne "") {
                $vuln_name = $name_psid;
            }
            else{
                $vuln_name = "Vulnerability - Unknown detail";
            }
            my $priority = calc_priority($risk, $hostip, $scanid);
            $sql_inc = qq{ INSERT INTO incident(title, date, ref, type_id, priority, status, last_update, in_charge, submitter, event_start, event_end) VALUES('$vuln_name', now(), 'Vulnerability', 'Nessus Vulnerability', '$priority', 'Open', now(), '$username', 'nessus', '0000-00-00 00:00:00', '0000-00-00 00:00:00') };
            safe_db_write ($sql_inc, 4);
            # TODO: change this for a sequence
            $sql_inc = qq{ SELECT MAX(id) id from incident };
            $sth_inc = $dbh->prepare($sql_inc);
            $sth_inc->execute();
            my ($incident_id) = $sth_inc->fetchrow_array;
            $sth_inc->finish;
            #sanity check
            $desc =~ s/\"/\'/g;
            $desc =~ s/^ *| *$//g;
            $desc =~ s/^[\n\r\t]*//g;
            my $incident_vulns_id = genID("incident_vulns_seq");
            $sql_inc = qq{ INSERT INTO incident_vulns(id, incident_id, ip, port, nessus_id, risk, description) VALUES('$incident_vulns_id', '$incident_id', '$hostip', '$port', '$scanid', '$risk', \"$desc\") };
            safe_db_write ($sql_inc, 4);
            $sql_inc = qq{ INSERT INTO incident_tag(tag_id, incident_id) VALUES($id_pending, '$incident_id') };
            safe_db_write ($sql_inc, 4);
        }
}

# LOOKUP ALL PLUGINS WITH A CUST RISK VALUE ( NO NEED TO RESTRICT TO THIS PROFILE ONLY )
sub get_custom_risks {

    my $plugins = {};

    my $sql = qq{ SELECT id, custom_risk FROM vuln_nessus_plugins
         WHERE custom_risk IS NOT NULL };
    my $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;

    while ( my ($plug_id, $plug_risk )=$sth_sel->fetchrow_array ) {
        if ($plug_id) {
            $plugins->{$plug_id} = $plug_risk;
        }
    } #end if
    return($plugins);
}

#needed for host updated / load results
sub resolve_host {
    # VER: 2.0 MODIFIED: 5/06/08 15:30
    my ( $hostip ) = @_;

    if ( ! defined ( $hostip) || $hostip eq "" ) { return ""; }

    # ATTEMPT GET HOST BY ADDRESS WILL CHECK FILES/DNS PRIMARY
    my $iaddr = inet_aton( $hostip ); # or whatever address
    my $namer  = gethostbyaddr($iaddr, AF_INET);

    if ( defined($namer ) ) {
        my $thost = lc ( $namer );
        #logwriter( $thost, 5 );
        return $thost;
    } else {

        if ( $CONFIG{'nameservers'} ne "" ) {
            my @nameservers = split /,/, $CONFIG{'nameservers'};
            foreach my $nameserver ( @nameservers ) {
                $nameserver =~ s/\s+//g;
                my $namer = nslookup(host => "$hostip", type => "PTR", server => "$nameserver" );
                if ( defined($namer ) && $namer ne "" ) {
                    my $thost = lc ( $namer );
                    return $thost;
                }
            }
        } 
    }
    logwriter( "REVERSE IP [$hostip] TO NAME FAILED\n", 3 );
    return "";
}

#filter html special characters
sub htmlspecialchars {
    # VER: 1.0 MODIFIED: 3/29/07 13:03
    my $tmpSTRmsg = $_[0];
    $tmpSTRmsg =~ s/&/&amp;/g;
    $tmpSTRmsg =~ s/\'/&#039;/g;
    $tmpSTRmsg =~ s/\"/&quot;/g;
    $tmpSTRmsg =~ s/</&lt;/g;
    $tmpSTRmsg =~ s/>/&gt;/g;
    return $tmpSTRmsg;
}

#is this a num
sub is_number{
    # VER: 1.0 MODIFIED: 3/29/07 13:03
    my($n)=@_;

    if ( $n ) { 
        return ($n=~/^\d+$/);
    } else {
        return;
    }
}

#safe write code to help prevent complete job failure
sub safe_db_write {
    # VER: 1.0 MODIFIED: 1/16/08 7:36
    my ( $sql_insert, $specified_level ) = @_;

    logwriter( $sql_insert, $specified_level );
    eval {
        $dbh->do( $sql_insert );
    };
    warn "[$$] FAILED - $sql_insert\n" . $dbh->errstr . "\n\n" if ($@);

    if ( $@ ) { return 0; }
}

sub trim {
    my ( $string ) = @_;

    if ( defined ($string) && $string ne "" ) {
	$string =~ s/^\s+//;
	$string =~ s/\s+$//;
	return $string;
    } else {
	return "";
    }
}

sub calc_priority {
    my $risk = shift;
    my $hostip = shift;
    my $nessusid = shift;
    
    # If it's not set, set it to 1
    my $risk_value = 1;
    my ($sql_inc, $sth_inc, $priority);
    
    if ($risk eq "NOTE") {
        $risk_value = 0;
    }
    elsif ($risk eq "INFO") {
        $risk_value = 1;
    }
    elsif ($risk eq "Security Note") {
        $risk_value = 1;
    }
    elsif ($risk eq "LOW") {
        $risk_value = 3;
    }
    elsif ($risk eq "Security Warning") {
        $risk_value = 3;
    }
    elsif ($risk eq "MEDIUM") {
        $risk_value = 5;
    }
    elsif ($risk eq "HIGH") {
        $risk_value = 8;
    }
    elsif ($risk eq "Security Hole") {
        $risk_value = 8;
    }
    elsif ($risk eq "REPORT") {
        $risk_value = 10;
    }

    $sql_inc = qq{ SELECT asset FROM host WHERE ip = '$hostip' };
    $sth_inc = $dbh->prepare($sql_inc);
    $sth_inc->execute();
    my ($asset) = $sth_inc->fetchrow_array;
    $sth_inc->finish;
    
    if ($asset eq "") {
        $asset = 0;
    }
    
    $sql_inc = qq{ SELECT reliability FROM plugin_sid WHERE sid = '$nessusid' };
    $sth_inc = $dbh->prepare($sql_inc);
    $sth_inc->execute();
    my ($reliability) = $sth_inc->fetchrow_array;
    $sth_inc->finish;
    
    if ($reliability eq "") {
        $reliability = 0;
    }
        # FIXME: check this formula once the values are clear. This is most definetivley wrong.
        $priority = int( ($risk_value + $asset + $reliability) / 1.9 );
        return $priority;
}

sub genID {
    my $table = shift;
    my $sth_lastid;
    
    my $sql_genID = qq {UPDATE $table SET id=LAST_INSERT_ID(id+1) };
    safe_db_write( $sql_genID, 4 );
    
    my $last_id_query = qq{SELECT LAST_INSERT_ID() as lastid};
    $sth_lastid = $dbh->prepare($last_id_query);
    $sth_lastid->execute;
    my ($last_id) = $sth_lastid->fetchrow_array;
    $sth_lastid->finish;
    return $last_id;
}

sub get_host_record {
    # VER: 1.7 MODIFIED: 12/29/08 15:18
    my ( $mac_address, $hostname, $hostip )  = @_;

    my ( $sql, $sth_sel );
    my ( $host_id ) = "0";

    my $now = getCurrentDateTime("datetime");
    #ENSURE HOST_ID IS LOOKED UP AFTER INSERT OR QUIT 
    #LOOKUP HOST_ID AGAIN FOR UPDATE OF MACS / INCIDENT TRACKER CODE
    $sql = qq{ SELECT id FROM vuln_hosts WHERE hostname = '$hostname' LIMIT 1 };
    logwriter( $sql, 5 );
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;
    ( $host_id ) = $sth_sel->fetchrow_array;

    if ( defined( $host_id ) && $host_id > 0 ) {

    } else {  #ADD NEW BARE RECORD FOR HOST
        $sql = "INSERT INTO vuln_hosts ( hostip, hostname, status, lastscandate, createdate ) VALUES (
            '$hostip', '$hostname', 'Production', '$now', '$now' );";
        safe_db_write ( $sql, 4 );

        $sql = qq{ SELECT id FROM vuln_hosts WHERE hostname = '$hostname' LIMIT 1 };
        logwriter( $sql, 5 );
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
        ( $host_id ) = $sth_sel->fetchrow_array;
    }
    return $host_id;
}

#get current date/time
sub getCurrentDateTime {
    # VER: 2.0 MODIFIED: 4/03/08 20:35
    my ( $format ) = @_;

    my @days = qw(Su Mo Tu We Th Fr Sa);
    my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
    $year+=1900;
    $mon++;

    if ( $format ) {
        switch (lc($format)) {
            case "datetime"        { return sprintf("%04d%02d%02d%02d%02d%02d",$year, $mon, $mday, $hour, $min, $sec); }
            case "date"                { return sprintf("%04d%02d%02d",$year, $mon, $mday); }
            case "time"                { return sprintf("%02d%02d%02d", $hour, $min, $sec); }
            case "today"        { return $days[$wday]; }
            else                { return sprintf("%04d%02d%02d%02d%02d%02d",$year, $mon, $mday, $hour, $min, $sec); }
        }
    } else {
        return sprintf("%04d%02d%02d%02d%02d%02d",$year, $mon, $mday, $hour, $min, $sec);
    }

}
sub ip2hostname {
    my ( $ip, $is_sensor, $force_no_dns) = @_;
        
    my ( $sql, $sth_sel );
    my ( $hostname ) = "";
    
    if ($is_sensor == TRUE) {
        $sql = qq{ SELECT name FROM sensor WHERE ip = '$ip' };
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
        $hostname = $sth_sel->fetchrow_array;
        
        if( $hostname !~ /(\d+)\.(\d+)\.(\d+)\.(\d+)/ &&  $hostname ne "") {
            return $hostname;
        }
    }

    $sql = qq{ SELECT hostname FROM host WHERE ip = '$ip' };
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;
    $hostname = $sth_sel->fetchrow_array;

    if( $hostname !~ /(\d+)\.(\d+)\.(\d+)\.(\d+)/ &&  $hostname ne "") {
        return $hostname;
        
    }

    if($use_resolv == TRUE && $force_no_dns == FALSE) {
        my $iaddr = inet_aton($ip); # or whatever address
        $hostname  = gethostbyaddr($iaddr, AF_INET);
        if ($hostname !~ /(\d+)\.(\d+)\.(\d+)\.(\d+)/ &&  $hostname ne "")  { return $hostname; }
        else { return $ip; }
    }
    else {
        return $ip;
    }
}

sub hostname2ip {
    my ( $hostname, $resolv) = @_;
    my ( $sql, $sth_sel, $cmd );
    my ($ip) = "";

    $sql = qq{ SELECT ip FROM host WHERE hostname = '$hostname' };
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;
    $ip = $sth_sel->fetchrow_array;
    if ($ip ne "") { return $ip; }
    else {
        $sql = qq{ SELECT ip FROM server WHERE name = '$hostname' };
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
        $ip = $sth_sel->fetchrow_array;
        if ($ip ne "") { return $ip; }
        elsif ($resolv == TRUE) {
            $cmd = qq{/usr/bin/dig '$hostname' A +short | /usr/bin/tail -1};
            open(RESOLV,"$cmd 2>&1 |") or die "failed to fork :$!\n";
            while(<RESOLV>){
                chomp;
                $ip = $_;
            }
            close RESOLV;
            return $ip;
        }
        else {return "";}
    }
}

sub in_host {
    my ($ip) = @_;
    my ($sql_in_host, $sth_sel_in_host, $hostname);
    $sql_in_host = qq{ SELECT hostname FROM host WHERE ip = '$ip' };
    $sth_sel_in_host = $dbh->prepare( $sql_in_host );
    $sth_sel_in_host->execute;
    $hostname = $sth_sel_in_host->fetchrow_array;
    
    return $hostname;
}

sub insert_host {
    my ($ip) = $_[0];
    my ($hostname) = $_[1];
    my ($asset) = $_[2];
    my ($threshold_c) = $_[3];
    my ($threshold_a) = $_[4];
    my ($rrd_profile) = $_[5];
    my ($alert) = $_[6];
    my ($persistence) = $_[7];
    my ($nat) = $_[8];
    my (@sensors) = @{$_[9]};

    my $sql = "INSERT INTO host (ip, hostname, asset, threshold_c, threshold_a, rrd_profile, 
                alert, persistence, nat) VALUES ('$ip', '$hostname', '$asset', '$threshold_c', '$threshold_a', '$rrd_profile',
                '$alert', '$persistence', '$nat')";
    safe_db_write ( $sql, 4 );

    foreach (@sensors) {
        $sql = "INSERT INTO host_sensor_reference VALUES ('$ip', '$_')";
        safe_db_write ( $sql, 4 );
     } 

}
sub resolve_name2ip {
    # VER: 2.0 MODIFIED: 5/06/08 15:30
    my ( $hostname ) = @_;
    if ( ! defined ( $hostname ) || $hostname eq "" ) { return ""; }

    # ATTEMPT GET HOST BY ADDRESS WILL CHECK FILES/DNS PRIMARY
    my $packed_ip = gethostbyname( $hostname );

    if ( defined( $packed_ip ) ) {
        my $c_ip = inet_ntoa($packed_ip);
        return $c_ip;
    } else {
        #TRY OTHER NAMES SERVERS
        if ( $CONFIG{'nameservers'} ne "" ) {
            my @nameservers = split /,/, $CONFIG{'nameservers'};

            foreach my $nameserver ( @nameservers ) {
                $nameserver =~ s/\s+//g;
                my $namer = nslookup(host => "$hostname", server => "$nameserver" );
                if ( defined($namer ) && $namer ne "" ) {
                    my $thost = lc ( $namer );
                        return $thost;
                }
            }
        } 
    }
    logwriter( "RESOLVE [$hostname] TO IP FAILED\n", 3 );
    return "";
}

# extract os info  <- assuming msg from plugin #11936 is supplied
sub extract_os {
    # VER: 1.0 MODIFIED: 11/15/07 15:43
    my ( $txt_msg ) = @_;
    my $os = "";
    my @arrMSG = split /\\n\\n|\n\n|\r/, $txt_msg;
    foreach my $line (@arrMSG) {
        $line =~ s/\#.*$//;
               chomp($line);
        $line =~ s/\s+$//;
        $line =~ s/^\s+//;
        if ($line =~ "Remote operating system") {
            my @temp=split(/:/,$line,2);
            $os = $temp[1];
            $os =~ s/\(English\)//;
            if ( $os =~ /\\n/ ) {
                @temp=split(/\\n/,$os,2);
                $os =$temp[0];
            }
            $os =~ s/\s+$//;
            $os =~ s/^\s+//;
            logwriter ( "OS=[$os]\n", 5 );
            return $os;
        } else { next; }
    }
    return "";
}

# extract host info <- assuming msg from plugin #10150 is supplied
sub extract_hostinfo {
    # VER: 1.0 MODIFIED: 11/15/07 15:43
    my ( $txt_msg ) = @_;

    #changed $domain to $wgroup ( did not want to confuse with domain field per nessus nbe results
    my ( $hostname, $wgroup, $mac_address ) = "";

    logwriter( "nessus_scan: plugin 10150 data: [[$txt_msg]]", 5 );
    my @arrMSG = split /\\n\\n|\n\n|\r/, $txt_msg;
    foreach my $line (@arrMSG) {
        $line =~ s/\#.*$//;
        chomp($line);
        $line =~ s/\s+$//;
        $line =~ s/^\s+//;
        logwriter( "nessus_scan: LINE=[$line]", 5 ); 
        if ($line =~ /computer\sname/i ) {
            my @temp=split(/=/,$line,2);
            $temp[0] =~ s/\s+$//;
            $temp[0] =~ s/^\s+//;
            $hostname = lc( $temp[0] );
            logwriter( "nessus_scan: hostname=[$hostname]", 5 );
        } elsif ($line =~ /Workgroup/i ) {
            my @temp=split(/=/,$line,2);
            $temp[0] =~ s/\s+$//;
            $temp[0] =~ s/^\s+//;
            $wgroup = lc( $temp[0] );
            logwriter( "nessus_scan: wgroup=[$wgroup]", 5 );
        } elsif ($line =~ /^([0-9a-fA-F][0-9a-fA-F]:){5}([0-9a-fA-F][0-9a-fA-F])$/ ) {
            $mac_address = uc( $line );
            $mac_address =~ s/[:-]//g;
        }
        next;
    }

    if ( ! $mac_address ) { $mac_address = "unknown"; }
    if ( ! $wgroup ) { $wgroup = "unknown"; }
    if ( $hostname =~ /Synopsis:/i ) { $hostname = ""; }

    if ( defined ( $hostname ) && $hostname ne "" ) {
       logwriter ( "my %hostinfo = ( 'dns' => '1', 'hostname' => '$hostname', 'wgroup' => '$wgroup', 'mac' => '$mac_address' );\n", 5 );
       my %hostinfo = ( 'dns' => '1', 'hostname' => $hostname, 'wgroup' => $wgroup, 'mac' => $mac_address );
       return %hostinfo;
    } else {
       logwriter ( "my %hostinfo = ( 'dns' => '-1', 'mac' => $mac_address );\n", 5 );
       my %hostinfo = ( 'dns' => '-1', 'mac' => $mac_address );
       return %hostinfo;
    }
}

sub check_access {
    my ( $txtData ) = @_;

    $txtData =~ s/&#039;/\"/g;
    $txtData =~ s/&amp;#039/\"/g;

    my $txt_output = "";

    if ( $txtData =~ /\"administrator\" account has no password set/ ) {
	$txt_output = "administrator no password";
    }elsif ( $txtData =~ /has no password set/ ) {
    	$txt_output = "user no password";
    }elsif ( $txtData =~ /SMB tests will be done as/ ) {
    	$txt_output = "authenticated user"; #WIN
    }elsif ( $txtData =~ /Local security checks have been enabled for this host/ ) {
    	$txt_output = "run linux checks"; #LINUX
    }elsif ( $txtData =~ /It was not possible to log into the remote host via ssh/ ) {
    	$txt_output = "invalid userpass"; #LINUX
    }elsif ( $txtData =~ /NULL sessions are enabled on the remote host/ ) {
    	$txt_output = "null session";
    } else {
	logwriter( "CHECK ACCESS - NO MATCH FOR ENTRY [$txtData]", 5 );
	$txt_output = "no match";
    }
    logwriter( "access_text=[$txt_output]", 3 );
    return $txt_output;
}