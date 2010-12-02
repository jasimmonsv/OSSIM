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

%ini = read_ini();
$pubkey = $ini{'main'}{'pubkey'};
$pubkey = "/var/ossim/keys/rsapub.pem" if ($pubkey eq "");
$pubkey =~ s/file\:\/\///;

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
	$verify = "openssl dgst -sha1 -verify $pubkey -signature '$outdir/$logfilename.signa' '$logfile' |";
	open(V,$verify);
	my @aux = <V>;
	if ($aux[0] =~ /Verified OK/) {
		print "Copied Successfully. Verified OK\n";
	}
	else {
		print "Copied Successfully. Verified Failed\n";
	}
}

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
