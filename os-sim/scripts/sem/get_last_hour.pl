#!/usr/bin/perl

# date format:
#
# 2008-15-08 12:34:38
#
# it mas be passed in the first argument, for example: ndate.pl "2008-15-08 12:34:38"
#

use POSIX;
use warnings;


if($ARGV[0]=~/(\d{4})-(\d{1,2})-(\d{1,2}) (\d{1,2}):(\d{1,2}):(\d{1,2})/)
{
	$c1=$1 - 1900;
	$c2=$2 - 1;
	$c3=$3;
	$c4=$4;
	$c5=$5;
	$c6=$6;

	$date=mktime ($c6, $c5, $c4, $c3, $c2, $c1, 0, 0);
	$date=$date - 18000;
	$fdate=strftime "%F-%H", localtime($date);
	print "$fdate ";

	$date=mktime ($c6, $c5, $c4, $c3, $c2, $c1, 0, 0);
	$date=$date - 14400;
	$fdate=strftime "%F-%H", localtime($date);
	print "$fdate ";

	$date=mktime ($c6, $c5, $c4, $c3, $c2, $c1, 0, 0);
	$date=$date - 10800;
	$fdate=strftime "%F-%H", localtime($date);
	print "$fdate ";

	$date=mktime ($c6, $c5, $c4, $c3, $c2, $c1, 0, 0);
	$date=$date - 7200;
	$fdate=strftime "%F-%H", localtime($date);
	print "$fdate ";

	$date=mktime ($c6, $c5, $c4, $c3, $c2, $c1, 0, 0);
	$date=$date - 3600;
	$fdate=strftime "%F-%H", localtime($date);
	print "$fdate\n";

}
