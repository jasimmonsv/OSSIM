#!/usr/bin/perl

if(!$ARGV[0])
{
	print "\nUsage: $0 gen-msg.map\n\n";
	print "This file is used to extract all the plugin sids from snort preprocessors. Insert the results into the OSSIM DB. You may do something like:\n";
	print "# ./create_sidmap_preprocessors.pl /tmp/snort/snort-2.6.1.5/etc/gen-msg.map | mysql ossim -p\n\n";
	exit();
}

open(IN,"<$ARGV[0]") or die "Can't open $ARGV[0]";

if($ARGV[0] =~ /\/([^\/]*)$/)
{
	print "---- $1\n";
}

while(<IN>)
{ 
	#line example:
	#100 || 1 || spp_portscan: Portscan Detected
	if(/(\d\d\d)\s\|\|\s(\d+)\s\|\|(.+)/)
	{
		$plugin_id = $1;
		$plugin_sid = $2;
		$str = $3;
		print "INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1$plugin_id, $plugin_sid, NULL, NULL, '$str');\n";
	}
}
close IN;


