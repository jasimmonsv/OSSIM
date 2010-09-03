#!/usr/bin/perl

sub complex(){

$a = shift;

if($a =~ /^\s*(.*)!=(.*)$/){
return " grep -v -i \"$1='$2'\" ";
} elsif ($a =~ /^\s*(.*)=(.*)$/){
	if ($1 eq "src_net" || $1 eq "dst_net") {
		$value = $2;
		$netfield = $1;
		$netfield =~ s/net/ip/;
		return " grep -i \"$netfield='$value\" ";
	}
	elsif ($1 eq "id" || $1 eq "fdate" || $1 eq "date" || $1 eq "plugin_id" || $1 eq "sensor" || $1 eq "src_ip" || $1 eq "dst_ip" || $1 eq "src_port" || $1 eq "dst_port" || $1 eq "tzone"|| $1 eq "data"){
		$aux = $2;
		$par = $1;
		$aux =~ s/'+//g;
		return " egrep -i \"$par='$aux'\" ";
	} elsif ($1 eq "ip") {
		return " egrep -i \"src_ip='$2'|dst_ip='$2'\" "
	} elsif ($1 eq "net") {
		return " egrep -i \"src_ip='$2|dst_ip='$2\" "
	} else {
		return " egrep -i \"$1=$2\" ";
	}

}
}

if(!$ARGV[0]){
exit;
}

$grep_str = "";

$ARGV[0] =~ s/ or /|/ig;

@args = split(/\s+/, $ARGV[0]);

$first = 1;

$negation = 0;

foreach $arg (@args){
	if($arg eq "and" || $arg eq "AND") {next;}
	if($arg eq " ") {next;}
	if($arg eq "") {next;}
  
	if($arg =~ /=/){ 
		$ret = &complex($arg);
		$grep_str .= "|" unless $first == 1;
		$first = 0;
    	$grep_str .= $ret;
		next;
	}

	if($arg eq "not"){
		$negation = 1;
	} else {
		$grep_str .= "|" unless $first == 1;
		$first = 0;
		 if($negation){
			 $grep_str .= " egrep -i -v '$arg' ";
		 } else {
			 $grep_str .= " egrep -i '$arg' ";
		 }
		$negation = 0;
	}
}

$grep_str .= "\n";

print $grep_str;
