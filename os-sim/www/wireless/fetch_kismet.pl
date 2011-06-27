#!/usr/bin/perl
use DBI;

$numproc = &num_processes;
if ($numproc>1){
  print "$0 already running, exit.\n";
  exit(0);
}

sub num_processes {
    my $count=0;
    while (!$count) {
        $count = `ps ax | grep fetch_kismet | grep -v grep | grep -v vi | grep -v 'sh -c' | wc -l`;
        $count =~ s/\s*//g;
    }
    return $count;
}



# database connect
my $dbhost = `grep ^db_ip= /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`; chomp($dbhost);
$dbhost = "localhost" if ($dbhost eq "");
my $dbuser = `grep ^user= /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`; chomp($dbuser);
my $dbpass = `grep ^pass= /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`; chomp($dbpass);
#my $dbh = DBI->connect("DBI:mysql:ossim:$dbhost", $dbuser,$dbpass, { PrintError => 0, RaiseError => 1, AutoCommit => 1 } ) or die("Failed to connect : $DBI::errstr\n");

#
%sites = ();
# Format needed REMOTE IP = REMOTE DIR
#$sites{'192.168.1.2'}='/var/log/kismet';

$home = "/var/ossim/kismet";
$logdir = "/var/log";
$syslog = "/var/log/syslog";

foreach $ip (keys %sites) {
   print "Pinging $ip...";
   $pingresult = `ping -q -w 5 $ip | grep transmitted | awk '{ print \$4 }'`; chomp($pingresult);
   print "Got $pingresult packets back\n";
   if ($pingresult eq '0') {
	print "ERROR: $ip is unpingable. Skipping...\n";
	next;
   }

   $location = $sites{$ip};
   $old_dir = "$home/parsed/$ip";
   $work_dir = "$home/work/$ip";
   mkdir $old_dir, 0755;
   mkdir $work_dir, 0755;
   print "old:$old_dir, work=$work_dir\n";

   $last_filename = `ls -ltr '$old_dir/'*'.xml' | awk '{print \$9}' | perl -npe 's/.*\\///g' | head -n 1`; chomp($last_filename);

   $filename=`ssh -o StrictHostKeyChecking=no $ip "cd $location; ls -ltr *.xml;" | awk '{ print \$9 }'`; chomp($filename);
   @removefiles = split(/\n/,$filename);
   $lastremote = $removefiles[$#removefiles];

   print "Copying from $ip:$location\n";
   system("scp -o StrictHostKeyChecking=no -p $ip:$location/$ip*.xml $work_dir");

   $now = localtime; $now =~ s/\s+/_/g;

   my $dbh = DBI->connect("DBI:mysql:ossim:$dbhost", $dbuser,$dbpass, { PrintError => 0, RaiseError => 1, AutoCommit => 1 } ) or die("Failed to connect : $DBI::errstr\n");
   $sql = qq{ update ossim.wireless_sensors set last_scraped = now() where sensor in (select name from ossim.sensor where ip = '$ip') };
   $sth_selm=$dbh->prepare( $sql );
   $sth_selm->execute;
   $sth_selm->finish;
   $dbh->disconnect or die("Failed to disconnect : $DBI::errstr\n");

   print "Importing xmls from $work_dir\n";
   system ("perl /usr/share/ossim/www/wireless/kismet_import.pl");

   foreach $filename (@removefiles) {
      # delete from server if not last remote file or last local file
      #print "------\n"; 
      #print "file:$filename lastremote:$lastremote lastlocal:$last_filename\n"; 
      if ($filename ne $lastremote) {
        if ($filename ne $last_filename) {
          print "Deleting remote file:$filename\n";
          system("ssh $ip \"cd $location; rm -f $filename\"");
        }
      }
   }

}
print "Done.\n";
