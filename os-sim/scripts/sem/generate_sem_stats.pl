#!/usr/bin/perl
use DBI;

#$ret = `ps ax|grep -v grep|awk '\$2 !~ /'\$\$'/{print}' | grep -v nocount|grep generate_stats.pl|wc -l`;
$ret = `ps ax|grep -v grep| grep -v nocount|grep generate_sem_stats.pl|wc -l`;
$ret =~ s/\s*//g;
exit(0) if ($ret>1);

if(!$ARGV[0]){
print "Accepts folder with *log files\n";
exit;
}
%ini = read_ini();
$loc_db = $ini{'main'}{'locate_db'};
$loc_db = "/var/ossim/logs/locate.index" if ($loc_db eq "");

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
%sensor_events = ();
@files = ();
$day = 0;
$nextday = 1;
#open (F,"find $folder | grep \"data.stats\" |");
open (F,"locate.findutils -d $loc_db $folder | grep \"data.stats\" | sort -ur |");
LOG: while ($file=<F>) {
	if ($file =~ /(\d\d\d\d)\/(\d\d)\/(\d\d)\/(\d\d)\/(\d+\.\d+\.\d+\.\d+)/) {
		if ($day==0 || $day==$3) {
			push @files,$file;
			$day = $3;
		} else {
			last LOG if ($nextday>1);
			$nextday++; 
			$day = $3;
		}
	}
}
close F;
foreach $file (@files) {
	# FORMAT /var/ossim/logs/YYYY/MM/DD/HH/SENSOR_IP/data.stats
	if ($file =~ /(\d\d\d\d)\/(\d\d)\/(\d\d)\/(\d\d)\/(\d+\.\d+\.\d+\.\d+)/) {
		$day = $1.$2.$3; $sensor = $5;
		chomp($file);
		print "Reading $file\n" if ($debug);
		
		# data.stats
		open (G,$file);
		while (<G>) {
			# plugin_id:4004:27 => type:value:count
			chomp;
			if (/(.*):(.*):(.*)/) {
				$sensor_stats{$day}{$sensor}{$1}{$2}+=$3;
			}
		}
		close G;
		
		# total_events
		$file =~ s/data\.stats/.total_events/;
		open (G,$file);
		while (<G>) {
			# plugin_id:4004:27 => type:value:count
			chomp;
			if (/^(\d+)/) {
				$sensor_events{$day}{$sensor}+=$1;
				last;
			}
		}
		close G;
	}
}
close F;

# database connect
my $dbhost = `grep ^db_ip= /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`; chomp($dbhost);
$dbhost = "localhost" if ($dbhost eq "");
my $dbuser = `grep "^user=" /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`; chomp($dbuser);
my $dbpass = `grep "^pass=" /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`; chomp($dbpass);
my $dbh = DBI->connect("DBI:mysql:ossim:$dbhost", $dbuser,$dbpass, {
	PrintError => 0,
	RaiseError => 1,
	AutoCommit => 1 } ) or die("Failed to connect : $DBI::errstr\n");
# create tables if not exists
$sql_update = qq{ CREATE TABLE IF NOT EXISTS `ossim`.`sem_stats` (`day` INT( 11 ) NOT NULL ,`sensor` VARCHAR( 15 ) NOT NULL ,`type` VARCHAR( 25 ) NOT NULL ,`value` VARCHAR( 25 ) NOT NULL ,`counter` INT( 11 ) NOT NULL , PRIMARY KEY ( `day` , `sensor` , `type` , `value` )) };
$sth_update = $dbh->prepare( $sql_update );
$sth_update->execute;
$sql_update = qq{ CREATE TABLE IF NOT EXISTS `ossim`.`sem_stats_events` (`day` INT( 11 ) NOT NULL ,`sensor` VARCHAR( 15 ) NOT NULL ,`counter` INT( 11 ) NOT NULL ,PRIMARY KEY ( `day` , `sensor` )) };
$sth_update = $dbh->prepare( $sql_update );
$sth_update->execute;
#
# insert stats into database
print "Inserting/updates sem_stats: " if ($debug);
my $i=0;
foreach $day (keys %sensor_stats) {
	foreach $sensor (keys %{$sensor_stats{$day}}) {
		foreach $type (keys %{$sensor_stats{$day}{$sensor}}) {
			foreach $value (keys %{$sensor_stats{$day}{$sensor}{$type}}) {
				my $counter = $sensor_stats{$day}{$sensor}{$type}{$value};
				$sql_update = qq{ INSERT INTO sem_stats VALUES ('$day', '$sensor', '$type', '$value', $counter) ON DUPLICATE KEY UPDATE counter=$counter };
				$sth_update = $dbh->prepare( $sql_update );
				$sth_update->execute;
				$i++;
				#print $day.":".$sensor.":".$type.":".$value.":".$sensor_stats{$day}{$sensor}{$type}{$value}."\n";
			}
		}
	}
}
print "$i\nInserting/updates sem_stats_events: " if ($debug);
my $i=0;
my $k=0;
foreach $day (keys %sensor_stats) {
	foreach $sensor (keys %{$sensor_stats{$day}}) {
		my $counter = $sensor_events{$day}{$sensor};
		if ($counter > 0) {
			$sql_update = qq{ INSERT INTO sem_stats_events VALUES ('$day', '$sensor', $counter) ON DUPLICATE KEY UPDATE counter=$counter };
			$sth_update = $dbh->prepare( $sql_update );
			$sth_update->execute;
			$k++;
		}
		$i++;
	}
}
print "$k/$i\n" if ($debug);
$dbh->disconnect;
print "Done.\n" if ($debug);

sub read_ini {
	my ($hash,$section,$keyword,$value);
    open (INI, "/usr/share/ossim/www/sem/everything.ini") || die "Can't open everything.ini: $!\n";
    while (<INI>) {
        chomp;
        if (/^\s*\[(\w+)\].*/) {
            $section = $1;
        }
        if (/^\W*(.+?)=(.+?)\W*(#.*)?$/) {
            $keyword = $1;
            $value = $2 ;
            # put them into hash
            $hash{$section}{$keyword} = $value;
        }
    }
    close INI;
    return %hash;
}

