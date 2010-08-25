#!/usr/bin/perl

if(!$ARGV[1]){
print "Supply two strings, the longest substring will be returned";
exit;
}

@a = split(//, $ARGV[0]);
@b = split(//, $ARGV[1]);
$result = "";

for($i=0;$i<$#a;$i++){
	if($a[$i] eq $b[$i]){
    $result .= $a[$i];
		next;
	} else {
		last;
	}
}

if($a[$i-1] eq "-"){
	$result = substr($result, 0, $i-1);
}

$result = substr($result, 0, 10);

print "$result\n";
