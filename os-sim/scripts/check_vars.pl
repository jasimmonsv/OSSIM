#!/usr/bin/perl

if(!$ARGV[0]){
print "Specify a directory to check\n";
exit;
}

use File::Find;

@directories = ();

$directories[0] = $ARGV[0];

find(\&wanted,  @directories);

sub wanted {
#if(/.php$/s || /.inc$/s){ 
if(/.php$/s){
print "Checking $File::Find::name\n"; 
&check($File::Find::name);
}
}

sub check {
@union = @intersection = @difference = ();
@vars;
@validates;
%count = ();
%vars = ();
$code_file = shift;
open(INPUT, "<$code_file");
while(<INPUT>){
if(/(\$.*)=.*(POST|GET|REQUEST)\(('|")(.*)('|")\)/){

if(!exists($vars{$1}{'request'})){
print "Assigning $4 to $1\n";
$vars{$1}{"request"} = $4;
} else {
if($vars{$1}{"request"} ne $4){
print "Warning, $1 request redefined:" . $vars{$1}{"request"} . " != " . $4 . "\n";
}
}

next;
} # end checking for the var assignment

if(/ossim_valid\((\$[^,]*),(.*)$/){

if(!exists($vars{$1}{'ossim_valid'})){
print "Validating $1 using $2\n";
$vars{$1}{"ossim_valid"} = $2;
} else {
if($vars{$1}{"ossim_valid"} ne $2){
print "Warning, $1 validation redefined:" . $vars{$1}{"ossim_valid"} . " != " . $2 . "\n";
}
}

} # end checking for ossim_valid

if(/(^.*(\$\w+).*$)/){
print "Using $2 at $1\n";
} # end use checking before validation

}
close INPUT;

foreach $element (@array1, @array2) { $count{$element}++ }
foreach $element (keys %count) {
push @union, $element;
push @{ $count{$element} > 1 ? \@intersection : \@difference }, $element;
}  


}
