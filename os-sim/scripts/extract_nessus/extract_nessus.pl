#!/usr/bin/perl

if(!$ARGV[0])
{
	print "Usage: $0 filename\n";
	exit();
}

open(IN,"<$ARGV[0]") or die "Can't open $ARGV[0]";

if($ARGV[0] =~ /\/([^\/]*)$/)
{
	print "---- $1\n";
}

while(<IN>)
{
	if(/alert.*reference:nessus,(\d+).*sid:(\d+)/)
	{
		$nessus_id = $1;
		$sid = $2;
		print "REPLACE INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, $sid, 3001, $nessus_id);\n";
	}
}
close IN;
