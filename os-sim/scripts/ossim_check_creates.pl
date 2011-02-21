#!/usr/bin/perl

# Create temporary OSSIM and SNORT database Structure
# IMPORTANT: Execute this each time you modify /db/*.sql files
# 2011-02-21 Pablo Vargas
use ossim_conf;
use DBI;
use POSIX;
use Compress::Zlib;
#setlocale(LC_ALL, "es_US");

use strict;
use warnings;

#sub byebye {
#    print "$0: forking into background...\n";
#    exit;
#}
#
#fork and byebye;

my $base_dir = "../db";
unless ($base_dir) {
    print "The var: base_dir not exist\n";
    exit;
}

# Data Source SNORT
my $snort_type = $ossim_conf::ossim_data->{"snort_type"};
my $snort_name = "snort_temp";
my $snort_host = $ossim_conf::ossim_data->{"snort_host"};
my $snort_port = $ossim_conf::ossim_data->{"snort_port"};
my $snort_user = $ossim_conf::ossim_data->{"snort_user"};
my $snort_pass = $ossim_conf::ossim_data->{"snort_pass"};
# Data Source OSSIM
my $ossim_type = $ossim_conf::ossim_data->{"ossim_type"};
my $ossim_name = "ossim_temp";
my $ossim_host = $ossim_conf::ossim_data->{"ossim_host"};
my $ossim_port = $ossim_conf::ossim_data->{"ossim_port"};
my $ossim_user = $ossim_conf::ossim_data->{"ossim_user"};
my $ossim_pass = $ossim_conf::ossim_data->{"ossim_pass"};

# Create aux databases
my $dbh=DBI->connect('dbi:mysql:',$ossim_conf::ossim_data->{"ossim_user"},$ossim_conf::ossim_data->{"ossim_pass"}, {RaiseError=>1}) or die "Couldn't connect:".DBI->errstr();
$dbh->do("create database if not exists $snort_name");
$dbh->do("create database if not exists $ossim_name");
$dbh->do("create database if not exists ossim_acl_temp");
$dbh->do("create database if not exists datawarehouse_temp");
$dbh->do("create database if not exists jasperserver_temp");
$dbh->do("create database if not exists osvdb_temp");

my $snort_dsn = "dbi:" . $snort_type . ":" . $snort_name . ":" . $snort_host . ":" . $snort_port;
my $snort_conn = DBI->connect($snort_dsn, $snort_user, $snort_pass) or die "Can't connect to Database\n";

my $ossim_dsn = "dbi:" . $ossim_type . ":" . $ossim_name . ":" . $ossim_host . ":" . $ossim_port . ":";
my $ossim_conn = DBI->connect($ossim_dsn, $ossim_user, $ossim_pass) or die "Can't connect to Database\n";

my $ossim_acl_dsn = "dbi:" . $ossim_type . ":" . "ossim_acl_temp" . ":" . $ossim_host . ":" . $ossim_port . ":";
my $ossim_acl_conn = DBI->connect($ossim_acl_dsn, $ossim_user, $ossim_pass) or die "Can't connect to Database\n";

my $datawarehouse_dsn = "dbi:" . $ossim_type . ":" . "datawarehouse_temp" . ":" . $ossim_host . ":" . $ossim_port . ":";
my $datawarehouse_conn = DBI->connect($datawarehouse_dsn, $ossim_user, $ossim_pass) or die "Can't connect to Database\n";

my $jasperserver_dsn = "dbi:" . $ossim_type . ":" . "jasperserver_temp" . ":" . $ossim_host . ":" . $ossim_port . ":";
my $jasperserver_conn = DBI->connect($jasperserver_dsn, $ossim_user, $ossim_pass) or die "Can't connect to Database\n";

my $osvdb_dsn = "dbi:" . $ossim_type . ":" . "osvdb_temp" . ":" . $ossim_host . ":" . $ossim_port . ":";
my $osvdb_conn = DBI->connect($osvdb_dsn, $ossim_user, $ossim_pass) or die "Can't connect to Database\n";

my $cmdline_snort = "mysql -u$snort_user -p$snort_pass -h$snort_host -P$snort_port $snort_name";
my $cmdline_ossim = "mysql -u$ossim_user -p$ossim_pass -h$ossim_host -P$ossim_port $ossim_name";
my $cmdline_ossim_acl = "mysql -u$ossim_user -p$ossim_pass -h$ossim_host -P$ossim_port ossim_acl_temp";
my $cmdline_datawarehouse = "mysql -u$ossim_user -p$ossim_pass -h$ossim_host -P$ossim_port datawarehouse_temp";
my $cmdline_jasperserver = "mysql -u$ossim_user -p$ossim_pass -h$ossim_host -P$ossim_port jasperserver_temp";
my $cmdline_osvdb = "mysql -u$ossim_user -p$ossim_pass -h$ossim_host -P$ossim_port osvdb_temp";

my $line_curr = 0;
my $lines = 0;

sub clean {
    my $name = shift;
    $dbh->do("DROP DATABASE $name");
}

sub createDatawarehouseStructure {
	my $cmd = "cat ../db/00-create_datawarehouse_tbls_mysql.sql | $cmdline_datawarehouse";
    print "Execute 00-create_datawarehouse_tbls_mysql.sql\n";
    open (F,"$cmd |");
    while (<F>) {
        print $_;
    }
    close F;
}
sub createJasperserverStructure {
	my $cmd = "cat ../db/00-create_jasperserver_tbls_mysql.sql | $cmdline_jasperserver";
    print "Execute 00-create_jasperserver_tbls_mysql.sql\n";
    open (F,"$cmd |");
    while (<F>) {
        print $_;
    }
    close F;
}
sub createOssimAclStructure {
	my $cmd = "cat ../db/00-create_ossim_acl_tbls_mysql.sql | $cmdline_ossim_acl";
    print "Execute 00-create_ossim_acl_tbls_mysql.sql\n";
    open (F,"$cmd |");
    while (<F>) {
        print $_;
    }
    close F;
}
sub createSnortStructure {
	my $cmd = "cat ../db/00-create_snort_tbls_mysql.sql | $cmdline_snort";
    print "Execute 00-create_snort_tbls_mysql.sql\n";
    open (F,"$cmd |");
    while (<F>) {
        print $_;
    }
    close F;
}
sub createOsvdbStructure {
	my $cmd = "cat ../db/00-create_osvdb_tbls_mysql.sql | $cmdline_osvdb";
    print "Execute 00-create_osvdb_tbls_mysql.sql\n";
    open (F,"$cmd |");
    while (<F>) {
        print $_;
    }
    close F;
}
sub createOssimStructure {
	my $cmd = "cat ../db/00-create_ossim_tbls_mysql.sql | $cmdline_ossim";
    print "Execute 00-create_ossim_tbls_mysql.sql\n";
    open (F,"$cmd |");
    while (<F>) {
        print $_;
    }
    close F;
    $cmd = "cat ../db/01-create_ossim_data_config.sql | $cmdline_ossim";
    print "Execute 01-create_ossim_data_config.sql\n";
    open (F,"$cmd |");
    while (<F>) {
        print $_;
    }
    close F;
    $cmd = "cat ../db/02-create_ossim_data_data.sql | $cmdline_ossim";
    print "Execute 02-create_ossim_tbls_mysql.sql\n";
    open (F,"$cmd |");
    while (<F>) {
        print $_;
    }
    close F;
    $cmd = "cat ../db/03-create_ossim_data_croscor_snort_nessus.sql | $cmdline_ossim";
    print "Execute 03-create_ossim_data_croscor_snort_nessus.sql\n";
    open (F,"$cmd |");
    while (<F>) {
        print $_;
    }
    close F;
    $cmd = "cat ../db/04-create_ossim_data_vulnerabilities.sql | $cmdline_ossim";
    print "Execute 04-create_ossim_tbls_mysql.sql\n";
    open (F,"$cmd |");
    while (<F>) {
        print $_;
    }
    close F;
}

sub printNumTables {
	my $conn = shift;
	my $name = shift;
	my $name_orig = $name;
	$name_orig =~ s/\_temp$//;
	
	my $query = "SHOW TABLES";
	
	# ORIGINAL
	my $orig_conn;
	if ($name_orig eq "snort") {
		my $orig_dsn = "dbi:" . $snort_type . ":" . $snort_name . ":" . $snort_host . ":" . $snort_port;
		$orig_conn = DBI->connect($snort_dsn, $snort_user, $snort_pass) or die "Can't connect to Database\n";
	} else {
		my $orig_dsn = "dbi:" . $ossim_type . ":" . $name_orig . ":" . $ossim_host . ":" . $ossim_port . ":";
		$orig_conn = DBI->connect($orig_dsn, $ossim_user, $ossim_pass) or die "Can't connect to Database\n";
	}
	my $stm = $orig_conn->prepare($query);
    $stm->execute();
    my $count_orig = 0;
    while (my @row = $stm->fetchrow_array) {
    	$count_orig++;
    }
	$stm->finish();
    # Disconnect from database
    $orig_conn->disconnect();
	
    # TEMP
    $stm = $conn->prepare($query);
    $stm->execute();
    my $count = 0;
    while (my @row = $stm->fetchrow_array) {
    	$count++;
    }
	$stm->finish();
    # Disconnect from database
    $conn->disconnect();
    print "Created '$name' database with $count tables (original has $count_orig tables).\n";
}

sub main {
	my $filter = shift;
	$filter = "" if (!defined $filter);
	
	createDatawarehouseStructure() if ($filter eq "" || $filter eq "datawarehouse");
	createJasperserverStructure() if ($filter eq "" || $filter eq "jasperserver");
	createOssimAclStructure() if ($filter eq "" || $filter eq "ossim_acl");
	createOsvdbStructure() if ($filter eq "" || $filter eq "osvdb");
	createSnortStructure() if ($filter eq "" || $filter eq "snort");
	createOssimStructure() if ($filter eq "" || $filter eq "ossim");
	
    printNumTables($datawarehouse_conn,"datawarehouse") if ($filter eq "" || $filter eq "datawarehouse");
    printNumTables($jasperserver_conn,"jasperserver") if ($filter eq "" || $filter eq "jasperserver");
    printNumTables($ossim_acl_conn,"ossim_acl") if ($filter eq "" || $filter eq "ossim_acl");
    printNumTables($osvdb_conn,"osvdb") if ($filter eq "" || $filter eq "osvdb");
    printNumTables($snort_conn,"snort") if ($filter eq "" || $filter eq "snort");
    printNumTables($ossim_conn,"ossim") if ($filter eq "" || $filter eq "ossim");
    
    clean("datawarehouse_temp") if ($filter eq "" || $filter eq "datawarehouse");
    clean("jasperserver_temp") if ($filter eq "" || $filter eq "jasperserver");
    clean("ossim_acl_temp") if ($filter eq "" || $filter eq "ossim_acl");
    clean("osvdb_temp") if ($filter eq "" || $filter eq "osvdb");
    clean("snort_temp") if ($filter eq "" || $filter eq "snort");
    clean("ossim_temp") if ($filter eq "" || $filter eq "ossim");
}

main(@ARGV);

$dbh->disconnect();
