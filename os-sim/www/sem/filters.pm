#!/usr/bin/perl

my $dbhost = `grep db_ip /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`; chomp($dbhost);
$dbhost = "localhost" if ($dbhost eq "");
my $dbuser = `grep user /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`; chomp($dbuser);
my $dbpass = `grep pass /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`; chomp($dbpass);

sub GetPluginsBySourceType {
	my $sourcetype = shift;
	
	my @ids = ();
    $temp_sql = "select id from plugin where source_type = \"$sourcetype\"";
    $dbh = conn_db();
	$sql = qq{ $temp_sql };
	$sth_sel=$dbh->prepare( $sql );
	$sth_sel->execute;
	while ( my ($id) = $sth_sel->fetchrow_array ) {
		push(@ids,$id);
	}
	$sth_sel->finish;
	disconn_db($dbh);
    
    return \@ids;
}

sub set_taxonomy_filters {
	my $filter = shift;
	my $and_num = shift;
	my $or_num = shift;
	#$filters{$and_num}{$or_num}{'plugin_id_sid'}{'7017'}{'1002'}++; # For Debug
	my @plugin_ids = ();
	if ($filter =~ /taxonomy\!?='?(.*)-(.*)-(.*)'?/) {
		$has_results = 0;
		my $plugin_query = "";
		if ($1 ne "") {
			my $st = $1;
			$st =~ s/\_/ /g;
			my $pointer = GetPluginsBySourceType($st);
			@plugin_ids = @$pointer;
			$plugin_query = "plugin_id in (".join(",",@plugin_ids).") AND";
		}
		if ($2 ne "" && $2 ne '0') {
			$category_id = $2;
			$dbh = conn_db();
			if ($3 ne "" && $3 ne '0') {
				$subcategory_id = $3;
				$temp_sql = "select distinct plugin_id,sid from plugin_sid WHERE $plugin_query category_id=$category_id AND subcategory_id=$subcategory_id";
		    } else {
				$temp_sql = "select distinct plugin_id,sid from plugin_sid WHERE $plugin_query category_id=$category_id";
		    }
		    $sql = qq{ $temp_sql };
			$sth_sel=$dbh->prepare( $sql );
			$sth_sel->execute;
			while ( my ($plugin_id,$sid) = $sth_sel->fetchrow_array ) {
				$has_results = 1;
				$filters{$and_num}{$or_num}{'plugin_id_sid'}{$plugin_id}{$sid}++;
			}
			$sth_sel->finish;
		    disconn_db($dbh);
		} elsif ($#plugin_ids > 0) {
			$onlyid = true;
			foreach $plugin_id (@plugin_ids) {
				$has_results = 1;
				$filters{$and_num}{$or_num}{'plugin_id'}{$plugin_id}++;
			}
		}
		if (!$has_results) {
			$filters{$and_num}{$or_num}{'plugin_id'}{-1}++;
		}
	}
}

sub set_plugingroup_filters {
	my $filter = shift;
	my $and_num = shift;
	my $or_num = shift;
	#$filters{$and_num}{$or_num}{'plugin_id_sid'}{'7017'}{'1002'}++; # For Debug
	my @plugin_ids = ();
	if ($filter =~ /plugingroup\!?=(.+)/ || $filter =~ /dsgroup\!?=(.+)/) {
		$dbh = conn_db();
		$group_name = $1;
		$temp_sql = "SELECT plugin_group.plugin_id,plugin_group.plugin_sid as sid FROM plugin_group_descr groups, plugin_group WHERE groups.group_id=plugin_group.group_id AND groups.name='$group_name'";
	    $sql = qq{ $temp_sql };
		$sth_sel=$dbh->prepare( $sql );
		$sth_sel->execute;
		$has_results = 0;
		while ( my ($plugin_id,$sid) = $sth_sel->fetchrow_array ) {
			$has_results = 1;
			if ($sid == 0) {
				$filters{$and_num}{$or_num}{'plugin_id'}{$plugin_id}++;
			} else {
				$filters{$and_num}{$or_num}{'plugin_id_sid'}{$plugin_id}{$sid}++;
			}
		}
		if (!$has_results) {
			$filters{$and_num}{$or_num}{'plugin_id'}{-1}++;
		}
		$sth_sel->finish;
	    disconn_db($dbh);
	}
}

# plugin_list=id:sid,sid,sid;id:0;id:sid,sid...
sub set_pluginlist_filters {
	my $filter = shift;
	my $and_num = shift;
	my $or_num = shift;
	my @plugin_ids = ();
	if ($filter =~ /plugin_list\=?(.+)/) {
		my @criterias = split(/\;/,$1);
		foreach my $criteria (@criterias) {
			if ($criteria =~ /(\d+)\:(.+)/) {
				my $plugin_id = $1;
				my @sids = split(/\,/,$2);
				foreach my $sid (@sids) {
					$filters{$and_num}{$or_num}{'plugin_id_sid'}{$plugin_id}{$sid}++;
				}
			} elsif ($criteria =~ /^(\d+)$/) {
				my $plugin_id = $1;
				$filters{$and_num}{$or_num}{'plugin_id'}{$plugin_id}++;
			}
		}
	}
}

# use from fetchall and indexd
sub get_taxonomy_filter {
	my $filter = shift;
	my %filters = ();
	my $str = "";
	#$filters{'7017'}{'1002'}++; # For Debug
	my @plugin_ids = ();
	if ($filter =~ /(.*)-(.*)-(.*)/) {
		$source_type = $1;
		$category_id = $2;
		$subcategory_id = $3;
		$has_results = 0;
		my $plugin_query = "";
		if ($source_type ne "") {
			$source_type =~ s/\_/ /g;
			my $pointer = GetPluginsBySourceType($source_type);
			@plugin_ids = @$pointer;
			$plugin_query = "plugin_id in (".join(",",@plugin_ids).") AND";
		}
		if ($category_id ne "" && $category_id ne '0') {
			$dbh = conn_db();
			if ($subcategory_id ne "" && $subcategory_id ne '0') {
				$temp_sql = "select distinct plugin_id,sid from plugin_sid WHERE $plugin_query category_id=$category_id AND subcategory_id=$subcategory_id";
		    } else {
				$temp_sql = "select distinct plugin_id,sid from plugin_sid WHERE $plugin_query category_id=$category_id";
		    }
		    $sql = qq{ $temp_sql };
			$sth_sel=$dbh->prepare( $sql );
			$sth_sel->execute;
			while ( my ($plugin_id,$sid) = $sth_sel->fetchrow_array ) {
				$has_results = 1;
				$filters{$plugin_id}{$sid}++;
			}
			$sth_sel->finish;
		    disconn_db($dbh);
		} elsif ($#plugin_ids >= 0) {
			$onlyid = true;
			foreach $plugin_id (@plugin_ids) {
				$has_results = 1;
				$filters{$plugin_id}{0}++;
			}
		}
		$filters{0}{0}++ if (!$has_results);
	}
	foreach $pid (keys %filters) {
		#pluging_id:plugin_sid|plugin_sid|plugin_sid;plugin_id|plugin_sid|plugin_sid,plugin_sid
		$str .= ";$pid:".join("|",keys %{$filters{$pid}});
	}
	$str =~ s/^;//;
	return ($str eq "") ? "0|0" : $str;
}

# use from fetchall and indexd
sub get_plugingroup_filter {
	my $filter = shift;
	my %filters = ();
	my $str = "";
	#$filters{'7017'}{'1002'}++; # For Debug
	my @plugin_ids = ();
	if ($filter ne "") {
		$dbh = conn_db();
		$group_name = $filter;
		$temp_sql = "SELECT plugin_group.plugin_id,plugin_group.plugin_sid as sid FROM plugin_group_descr groups, plugin_group WHERE groups.group_id=plugin_group.group_id AND groups.name='$group_name'";
	    $sql = qq{ $temp_sql };
		$sth_sel=$dbh->prepare( $sql );
		$sth_sel->execute;
		$has_results = 0;
		while ( my ($plugin_id,$sid) = $sth_sel->fetchrow_array ) {
			$sid = 0 if ($sid =~ /any/i);
			$sid =~ s/,/\|/g;
			$has_results = 1;
			$filters{$plugin_id}{$sid}++;
		}
		$filters{0}{0}++ if (!$has_results);
		$sth_sel->finish;
	    disconn_db($dbh);
	}
	foreach $pid (keys %filters) {
		#pluging_id:plugin_sid|plugin_sid|plugin_sid;plugin_id|plugin_sid|plugin_sid,plugin_sid
		$str .= ";$pid:".join("|",keys %{$filters{$pid}});
	}
	$str =~ s/^;//;
	return ($str eq "") ? "0|0" : $str;	
}

sub debug_filters {
	foreach $key1 (keys %filters) {
		print "Filter $key1:\n";
		foreach $key2 (keys %{$filters{$key1}}) {
			print "\tOR\n" if ($key2 > 1);
			foreach $type (keys %{$filters{$key1}{$key2}}) {
				if ($type eq "plugin_id_sid" || $type eq "plugin_id") {
					foreach $pid (keys %{$filters{$key1}{$key2}{$type}}) {
						print "   $type = $pid" if ($type eq "plugin_id");
						foreach $psid (keys %{$filters{$key1}{$key2}{$type}{$pid}}) {
							print "   Plugin id-sid = $pid - $psid\n";
						}
						print "\n";
					}
				} elsif ($type =~ /net/) {
					print "   $type = range { ".$filters{$key1}{$key2}{$type}{'from'}." - ".$filters{$key1}{$key2}{$type}{'to'}." }\n"
				} else {
					print "   $type = ".$filters{$key1}{$key2}{$type}."\n";
				}
			}
		}
	}
}

sub in_array {
    my $filter = $_[0];
    my $search_for = $_[1];

    my @arr = split("|",$filter);
    foreach my $value (@arr) {
        return 1 if ($value eq $search_for);
    }
    return 0;
}

sub ip2long {
	#converts an IP address x.x.x.x into a long IP number as used by ulog
	my $ip_address = shift;
	
	my (@octets,$octet,$ip_number,$number_convert);
	
	chomp ($ip_address);
	@octets = split(/\./, $ip_address);
	$ip_number = 0;
	foreach $octet (@octets) {
		$ip_number <<= 8;
		$ip_number |= $octet;
	}
	return $ip_number;
}

sub conn_db {
    $dbh = DBI->connect( "DBI:mysql:ossim;host=$dbhost;port=3306;socket=/var/lib/mysql/mysql.sock", $dbuser, $dbpass, {
        PrintError => 0,
        RaiseError => 1,
        AutoCommit => 1 } ) or die("Failed to connect : $DBI::errstr\n");
    return $dbh;
}
 
sub disconn_db {
    my ( $dbh ) = @_;
    $dbh->disconnect or die("Failed to disconnect : $DBI::errstr\n");
}

sub read_ini {
	my ($hash,$section,$keyword,$value);
    open (INI, "everything.ini") || die "Can't open everything.ini: $!\n";
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

1;