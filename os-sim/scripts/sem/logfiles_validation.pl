#!/usr/bin/perl
use File::Copy;

$logsfile = $ARGV[0];
$outdir = $ARGV[1];

if ($logsfile eq "" || $outdir eq "") {
	print "USE: logfiles_validation.pl [logs listing file] [output directory]";
	exit;
}
if (!-e $logsfile) {
	print "File '$logsfile' not found.\n";
	exit;
}
if (!-d $outdir) {
	print "Directory '$outdir' not found.\n";
	exit;
}

open(F,$logsfile);
my @logs = <F>;
print "\nProcessing ".($#logs)." files:\n\n";
foreach $logfile (@logs) {
	chomp ($logfile);
	my $logfilename = $logfile;
	$logfilename =~ s/.*\///;
	print "$logfilename: ";
	if (!copy($logfile,$outdir)) {
		print "Copy Failed\n";
		next;
	}
	if (!-e "$logfile.sig") {
		print "Signature file not found\n";
		next;
	}
	$cmd = "base64 -d '$logfile.sig' > '$outdir/$logfilename.signa'";
	system($cmd);
	$verify = "openssl dgst -sha1 -verify /var/ossim/keys/rsapub.pem -signature '$outdir/$logfilename.signa' '$logfile' |";
	open(V,$verify);
	my @aux = <V>;
	if ($aux[0] =~ /Verified OK/) {
		print "Copied Successfully. Verified OK\n";
	}
	else {
		print "Copied Successfully. Verified Failed\n";
	}
}
