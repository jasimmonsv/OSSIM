#!/usr/bin/perl

use SnortUnified(qw(:DEFAULT :record_vars));

$file = shift;
$debug = 0;
$UF_Data = {};
$record = {};

$UF_Data = openSnortUnified($file);
die unless $UF_Data;

if ( $UF_Data->{'TYPE'} eq 'LOG' ) {
    @fields = @$log_fields;
} else {
    @fields = @$alert_fields;
}

print("row");
foreach $field ( @fields ) {
    if ( $field ne 'pkt' ) { 
        print("," . $field);
    }
}
print("\n");

$i = 1;
while ( $record = readSnortUnifiedRecord() ) {
    
    print($i++);;
    
    foreach $field ( @fields ) {
        if ( $field ne 'pkt' ) {
            print("," . $record->{$field});
        }
    }
    print("\n");

}

closeSnortUnified();

