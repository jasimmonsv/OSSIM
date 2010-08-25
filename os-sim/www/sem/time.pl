#!/usr/bin/perl
@timeData = localtime(time);
print join('-', @timeData)."\n";
