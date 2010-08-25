#!/usr/bin/perl

###################################################################################
###                                                                              ##
### what=anomaly                                                                 ##
###   draw graph from $file.rrd for $ip host from $start to $end                 ##
###                                                                              ##
### what=tune                                                                    ##
###   idem that anomaly but also tune $file.rrd with $hwparam=$hwvalue           ##
###                                                                              ##
### what=attack or compromise                                                    ##
###   draw graph of attack or compromise of host $ip from $start to $end         ##
###   type=host or net or global or level                                        ##
###                                                                              ##
### optionaly  set a zoom parameter for the graph                                ##
###                                                                              ##
### Graph will be a png image                                                    ##
###                                                                              ##
###################################################################################


use ossim_conf;
use DBI;
use lib $ossim_conf::ossim_data->{"rrdtool_lib_path"};
use RRDs;
use CGI;
use File::Temp;
use strict;
use warnings;

$| = 1;


### Common variables
my $q = new CGI;
my $what;
my $ip;
my $start;
my $end;
my $zoom=1;
my $color1;
my $color2;
my $font=$ossim_conf::ossim_data->{font_path};
my $notfoundpng =$ossim_conf::ossim_data->{base_dir} . "/pixmaps/rrdnotfound.png";
my $tempname=tmpnam();
my $rrdpath;
my @rrdargs;

### Anomaly variables
my $atts;
my $file;
my $main_interface;
my $interfaces;
my $hwparam;
my $hwvalue;
my ($alpha, $beta, $gamma, $gamma_deviation, $delta_pos, $delta_neg, $treshold, $window_length);

### Attack-Compromise variables
my $type;
my $hostname="";
my $threshold_c;
my $threshold_a;
my $ds;

#############################     Exceptions      ##############################
#
#

sub msg_err {
   my ($msg) = @_;
   print $msg;
   exit 0;
}

sub notfound {
   open (NFPNG, "<$notfoundpng") or die "Can't open $notfoundpng\n";
   binmode(NFPNG); binmode(NFPNG);
   while(<NFPNG>){
      print;
   }
   close NFPNG;
   exit 0;
}


#########################     Retrieve parameters      #########################
#
# One sub for each action param
#

sub param_anomaly {
   $zoom = $q->param('zoom') if (defined $q->param('zoom'));
   if (defined $q->param('ip') && defined $q->param('file') && $q->param('start') && $q->param('end')) {
      $ip = $q->param('ip');
      $atts = $q->param('file');
      $start = $q->param('start');
      $end = $q->param('end');
   } else {
      msg_err("Args missing\n");
   }
   if ($what eq 'tune') {
      if (defined $q->param('hwparam') && defined $q->param('hwvalue')) {
         $hwparam = $q->param('hwparam');
         $hwvalue = $q->param('hwvalue');
      } else {
         msg_err("Args missing : hwparam and hwvalue must be present.\n");
      }
   }
   $main_interface = $ossim_conf::ossim_data->{"ossim_interface"};
   $interfaces = "$main_interface"; # todo look for the right interface
   $rrdpath = $ossim_conf::ossim_data->{"rrdpath_ntop"};
   $ip =~ tr/\./\//;
   $file = $rrdpath. "/interfaces/" . $interfaces . "/hosts/". $ip . "/" . $atts . ".rrd";
   $color1 = "#ff0000";
   $color2 = "#31527c";
}

sub param_attack_compromise {
   $zoom = $q->param('zoom') if (defined $q->param('zoom'));
   if (defined $q->param('ip') && defined $q->param('type') && $q->param('start') && $q->param('end')) {
      $ip = $q->param('ip');
      $start = $q->param('start');
      $end = $q->param('end');
      $type = $q->param('type');
   } else {
      msg_err("Args missing : ip, start, end and type must be present.\n");
   }

   if($type eq "host"){
      $rrdpath = $ossim_conf::ossim_data->{rrdpath_host};
   } elsif($type eq "net"){
      $rrdpath = $ossim_conf::ossim_data->{rrdpath_net};
   } elsif($type eq "global"){
      $rrdpath = $ossim_conf::ossim_data->{rrdpath_global};
   } elsif($type eq "level"){
      $rrdpath = $ossim_conf::ossim_data->{rrdpath_level};
   } else {
      msg_err("Wrong type. Type must be global, level, net or host.\n");
   }

   $color1="#0000ff";
   $color2="#ff0000";

   my $dsn = "dbi:mysql:".$ossim_conf::ossim_data->{"ossim_base"}.":".$ossim_conf::ossim_data->{"ossim_host"}.":".$ossim_conf::ossim_data->{"ossim_port"};
   my $dbh = DBI->connect($dsn, $ossim_conf::ossim_data->{"ossim_user"}, $ossim_conf::ossim_data->{"ossim_pass"}) or die "Can't connect to DBI\n";
   
   my ($query, $sth, $row);

   if($hostname ne ""){
         $query = "SELECT threshold_c FROM host WHERE ip = '$ip'";
         $sth = $dbh->prepare($query);
         $sth->execute();
         $row = $sth->fetchrow_hashref;
         $threshold_c = $row->{threshold_c};
         $query = "SELECT threshold_a FROM host WHERE ip = '$ip'";
         $sth = $dbh->prepare($query);
         $sth->execute();
         $row = $sth->fetchrow_hashref;
         $threshold_a = $row->{threshold_a};
   } else {
      $threshold_c = $threshold_a = $ossim_conf::ossim_data->{"threshold"};
      $hostname = $ip;
   }

   if($type eq "net"){ # Networks are supposed to have their own threshold
         $query = "SELECT threshold_c FROM net WHERE name = '$ip'";
         $sth = $dbh->prepare($query);
         $sth->execute();
         $row = $sth->fetchrow_hashref;
         $threshold_c = $row->{threshold_c};
         $query = "SELECT threshold_a FROM net WHERE name = '$ip'";
         $sth = $dbh->prepare($query);
         $sth->execute();
         $row = $sth->fetchrow_hashref;
         $threshold_a = $row->{threshold_a};
      $hostname = $ip;
   }

   $dbh->disconnect;
}


#########################     Main function      ##############################
#
# First retrieve parameters, depends on action
# Then draw appropriate graph
#

sub main {
### Retrieve param
   if (defined $q->param('what')) {
      $what = $q->param('what')
   } else {
      msg_err("Args missing : what\n");
   }
   if ($what eq 'anomaly' || $what eq 'tune') {
      param_anomaly();
   } elsif ($what eq 'compromise' || $what eq 'attack') {
      param_attack_compromise();
   } else {
      msg_err("What do you want to do ? What parameter must be anomaly, attack, compromise or tune.\n");
   }

### tune the rrd if needed
  if ($what eq 'tune') {
    RRDs::tune( "$file", "--$hwparam", $hwvalue );
    my $ERR = RRDs::error();
    warn "Can not change $hwparam in $file : $ERR\n" if $ERR;
  }


### draw the graph
   print $q->header(-type => "image/png", -expires => "+10s");

   if (! -e "$rrdpath/$ip.rrd"){ notfound();}

   if ($what eq 'attack' || $what eq 'compromise') {
      push @rrdargs, "DEF:obs=$rrdpath/$ip.rrd:ds0:AVERAGE";
      push @rrdargs, "DEF:obs2=$rrdpath/$ip.rrd:ds1:AVERAGE";
      push @rrdargs, "CDEF:negcomp=0,obs,-";
      push @rrdargs, "AREA:obs2$color2:Attack";
      push @rrdargs, "AREA:negcomp$color1:Compromise";
      if ($type ne 'level') {
          my $threshold = $ossim_conf::ossim_data->{"threshold"};
          my $upper_limit = $threshold * 2.5;
          my $lower_limit = -($threshold * 2.5);
      
          push @rrdargs, "HRULE:$threshold_a#000000", "HRULE:-$threshold_c#000000";
          push @rrdargs, "-u", int($upper_limit), "-l", int($lower_limit);
      }
      push @rrdargs, "-t", "$hostname Metrics", "-r";
   }
   elsif ($what eq 'anomaly' || $what eq 'tune') {
      my $hash = RRDs::info $file;
      foreach my $key (keys %$hash){
         if ($key =~ /^rra\[(\d+)\]\.alpha/) {
            $alpha = $$hash{$key};
         } elsif ($key =~ /^rra\[(\d+)\]\.beta/) {
            $beta = $$hash{$key};
         } elsif ($key =~ /^rra\[6\]\.gamma/) {  # todo find something better for both gamma
            $gamma = $$hash{$key};
         } elsif ($key =~ /^rra\[7\]\.gamma/) {
            $gamma_deviation = $$hash{$key};
         } elsif ($key =~ /^rra\[(\d+)\]\.delta_pos/) {
            $delta_pos = $$hash{$key};
         } elsif ($key =~ /^rra\[(\d+)\]\.delta_neg/) {
            $delta_neg = $$hash{$key};
         } elsif ($key =~ /^rra\[(\d+)\]\.failure_threshold/) {
            $treshold = $$hash{$key};
         } elsif ($key =~ /^rra\[(\d+)\]\.window_length/) {
            $window_length = $$hash{$key};
         }
      }

      push @rrdargs, "DEF:obs=$file:counter:AVERAGE";
      push @rrdargs, "DEF:pred=$file:counter:HWPREDICT";
      push @rrdargs, "DEF:dev=$file:counter:DEVPREDICT";
      push @rrdargs, "DEF:fail=$file:counter:FAILURES";
      push @rrdargs, "GPRINT:obs:MIN:Min\\: %3.1lf%s";
      push @rrdargs, "GPRINT:obs:MAX:Max\\: %3.1lf%s";
      push @rrdargs, "GPRINT:obs:AVERAGE:Avg\\: %3.1lf%s";
      push @rrdargs, "GPRINT:obs:LAST:Current\\: %3.1lf%s\\n";
      push @rrdargs, "TICK:fail#ffffa0:1.0:Anomaly";
      push @rrdargs, "CDEF:upper=pred,dev,2,*,+";
      push @rrdargs, "CDEF:lower=pred,dev,2,*,-";
      push @rrdargs, "LINE1:upper$color2:Upper";
      push @rrdargs, "LINE1:lower$color2:Lower";
      push @rrdargs, "LINE2:obs$color1:$atts";
      push @rrdargs, "COMMENT:Alpha\\: $alpha   Beta\\: $beta   Gamma\\: $gamma   Gamma-deviation\\: $gamma_deviation   Delta+\\: $delta_pos   delta-\\: $delta_neg";
      push @rrdargs, "COMMENT:Treshold\\: $treshold   Window\\: $window_length";
      push @rrdargs, "-t", " $atts"
   } else {
      msg_err("How could it be ?");
   }

   push @rrdargs, "--font", "TITLE:11:$font", "--font", "AXIS:7:$font", "--zoom", "$zoom";

   my ($prints,$xs,$ys) = RRDs::graph $tempname, 
       "-s", $start,               # --start seconds
       "-e", $end,                 # --end seconds
       @rrdargs;


   my $ERR=RRDs::error;
   msg_err("ERROR while generating graffic: $ERR\n") if $ERR;

   open (FILE,"<$tempname") || die "Error open() $tempname\n";
   binmode(FILE); binmode(STDOUT);
   while(<FILE>){
      print;
   }
   close FILE;
   unlink $tempname;

   print "\n\n";
}

main ();

exit 0;
